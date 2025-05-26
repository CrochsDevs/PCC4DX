<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Check if user is from HQ
if ($_SESSION['user']['center_code'] !== 'HQ') {
    header('Location: access_denied.php');
    exit;
}

class CenterTargetManager {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAllCenters() {
        $query = "SELECT center_code, center_name FROM centers WHERE center_code != 'HQ' ORDER BY center_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCurrentTargets($year = null) {
        $query = "SELECT t.*, c.center_name 
                  FROM ai_target t
                  JOIN centers c ON t.center_code = c.center_code";
        
        $params = [];
        if ($year) {
            $query .= " WHERE t.year = :year";
            $params[':year'] = $year;
        }
        
        $query .= " ORDER BY c.center_name";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function setTarget($centerCode, $target, $year) {
        // Check if target already exists for this center and year
        $checkQuery = "SELECT ai_target_id FROM ai_target WHERE center_code = :center_code AND year = :year";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([':center_code' => $centerCode, ':year' => $year]);
        
        if ($checkStmt->rowCount() > 0) {
            // Update existing target
            $query = "UPDATE ai_target SET target = :target WHERE center_code = :center_code AND year = :year";
        } else {
            // Insert new target
            $query = "INSERT INTO ai_target (center_code, target, year) VALUES (:center_code, :target, :year)";
        }
        
        $stmt = $this->db->prepare($query);
        return $stmt->execute([
            ':center_code' => $centerCode,
            ':target' => $target,
            ':year' => $year
        ]);
    }

    public function getAvailableYears() {
        $query = "SELECT DISTINCT year FROM ai_target ORDER BY year DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

$targetManager = new CenterTargetManager($conn);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_target'])) {
    $centerCode = $_POST['center_code'];
    $target = $_POST['target'];
    $year = $_POST['year'];
    
    if ($targetManager->setTarget($centerCode, $target, $year)) {
        $successMessage = "Target successfully set for " . $_POST['center_name'] . " ($centerCode) in $year";
    } else {
        $errorMessage = "Failed to set target. Please try again.";
    }
}

// Get current year as default
$currentYear = date('Y');
$selectedYear = isset($_GET['year']) ? $_GET['year'] : $currentYear;

$allCenters = $targetManager->getAllCenters();
$currentTargets = $targetManager->getCurrentTargets($selectedYear);
$availableYears = $targetManager->getAvailableYears();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ AI Score Card Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/calf.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .title {
            font-size: 1.875rem;
            font-weight: bold;
            color: #000;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #4B5563;
            margin-bottom: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 16px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #2563eb;
        }
        .btn-secondary {
            background: #6b7280;
        }
        .btn-secondary:hover {
            background: #4b5563;
        }
        .year-filter {
            margin-bottom: 20px;
        }
        .year-btn {
            padding: 6px 12px;
            margin-right: 5px;
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
        .year-btn.active {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .target-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .target-table th, .target-table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        .target-table th {
            background-color: #3a7fc5;
            color: white;
        }
        .target-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .action-btns {
            display: flex;
            gap: 10px;
        }
        .edit-btn {
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .edit-btn:hover {
            background-color: #2563eb;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 900px;
            border-radius: 8px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        .close {
            color: #aaa;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: black;
        }
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .view-targets-btn {
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="sidebar">
        <div class="user-profile" id="sidebar-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture" id="sidebar-profile-img">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture" id="sidebar-profile-img">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3 class="user-name" id="sidebar-profile-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="user-email" id="sidebar-profile-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>                          
        </div>

        <ul>
            <li><a href="admin.php" class="nav-link">
            <i class="fa-solid fa-arrow-left"></i> Back to Admin</a></li>
            <li><a href="admin_ai_dashboard.php" class="nav-link" data-section="dashboard-section">
            <i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="admin_centertarget_ai_dashboard.php" class="nav-link active" data-section="announcement-section">
            <i class="fas fa-file-alt"></i> Center Target</a></li>
            <li><a href="admin_report_ai_dashboard.php" class="nav-link" data-section="quickfacts-section">
            <i class="fas fa-sitemap"></i> Reports</a></li>
        </ul>
    </div>

    <div class="container">
        <h1 class="title">AI Center Targets</h1>
        <p class="subtitle">Set and manage AI targets for each center</p>

        <?php if (isset($successMessage)): ?>
            <div class="alert alert-success"><?= $successMessage ?></div>
        <?php elseif (isset($errorMessage)): ?>
            <div class="alert alert-error"><?= $errorMessage ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>Set New Target</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="center_code">Center</label>
                    <select id="center_code" name="center_code" class="form-control" required>
                        <option value="">Select Center</option>
                        <?php foreach ($allCenters as $center): ?>
                            <option value="<?= $center['center_code'] ?>">
                                <?= htmlspecialchars($center['center_name']) ?> (<?= $center['center_code'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" id="center_name" name="center_name">
                </div>
                <div class="form-group">
                    <label for="year">Year</label>
                    <input type="number" id="year" name="year" class="form-control" value="<?= $currentYear ?>" min="2000" max="2100" required>
                </div>
                <div class="form-group">
                    <label for="target">Target Number of AI Services</label>
                    <input type="number" id="target" name="target" class="form-control" min="0" required>
                </div>
                <button type="submit" name="set_target" class="btn">Set Target</button>
            </form>
        </div>

        <div class="card">
            <div class="year-filter">
                <strong>Filter by Year:</strong>
                <?php foreach ($availableYears as $year): ?>
                    <button class="year-btn <?= $year == $selectedYear ? 'active' : '' ?>" 
                            onclick="window.location.href='?year=<?= $year ?>'">
                        <?= $year ?>
                    </button>
                <?php endforeach; ?>
                <button class="year-btn <?= !in_array($currentYear, $availableYears) && !isset($_GET['year']) ? 'active' : '' ?>" 
                        onclick="window.location.href='?'">
                    All Years
                </button>
            </div>

            <h2>Current Targets</h2>
            <button id="viewTargetsBtn" class="btn view-targets-btn">
                <i class="fas fa-table"></i> View Targets (<?= isset($_GET['year']) ? "Year $selectedYear" : "All Years" ?>)
            </button>
            
            <?php if (count($currentTargets) > 0): ?>
                <!-- This will be shown in the modal -->
                <div id="targetsTableContent" style="display: none;">
                    <table class="target-table">
                        <thead>
                            <tr>
                                <th>Center</th>
                                <th>Center Code</th>
                                <th>Target</th>
                                <th>Year</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currentTargets as $target): ?>
                                <tr>
                                    <td><?= htmlspecialchars($target['center_name']) ?></td>
                                    <td><?= $target['center_code'] ?></td>
                                    <td><?= number_format($target['target']) ?></td>
                                    <td><?= $target['year'] ?></td>
                                    <td class="action-btns">
                                        <button class="edit-btn" 
                                                onclick="editTarget('<?= $target['center_code'] ?>', '<?= $target['center_name'] ?>', <?= $target['target'] ?>, <?= $target['year'] ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No targets set for <?= isset($_GET['year']) ? "year $selectedYear" : "any year" ?>.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Targets Modal -->
    <div id="targetsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">AI Targets - <?= isset($_GET['year']) ? "Year $selectedYear" : "All Years" ?></h3>
                <span class="close">&times;</span>
            </div>
            <div id="modalTableContent">
                <!-- Content will be inserted here by JavaScript -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary close">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill center name when center is selected
        $('#center_code').change(function() {
            const selectedOption = $(this).find('option:selected');
            if (selectedOption.text().includes('(')) {
                const centerName = selectedOption.text().split('(')[0].trim();
                $('#center_name').val(centerName);
            }
        });

        // Function to populate form for editing
        function editTarget(centerCode, centerName, target, year) {
            $('#center_code').val(centerCode);
            $('#center_name').val(centerName);
            $('#year').val(year);
            $('#target').val(target);
            
            // Close modal
            document.getElementById('targetsModal').style.display = 'none';
            
            // Scroll to form
            $('html, body').animate({
                scrollTop: $('.card').first().offset().top - 20
            }, 500);
        }

        // Initialize with current year if no year is selected
        <?php if (!isset($_GET['year'])): ?>
            window.history.replaceState(null, null, '?year=<?= $currentYear ?>');
        <?php endif; ?>

        // Modal functionality
        const modal = document.getElementById('targetsModal');
        const btn = document.getElementById('viewTargetsBtn');
        const span = document.getElementsByClassName('close')[0];
        const modalContent = document.getElementById('modalTableContent');
        const targetsContent = document.getElementById('targetsTableContent').innerHTML;

        btn.onclick = function() {
            modal.style.display = 'block';
            modalContent.innerHTML = targetsContent;
        }

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        // Close modal when clicking the close button in footer
        document.querySelector('.modal-footer .close').onclick = function() {
            modal.style.display = 'none';
        }
    </script>
</body>
</html>