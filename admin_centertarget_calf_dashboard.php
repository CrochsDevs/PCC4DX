<?php
session_start();
require 'auth_check.php';
include('db_config.php');

if ($_SESSION['user']['center_code'] !== 'HQ') {
    header('Location: access_denied.php');
    exit;
}

class CenterTargetManager {
    private $db;
    public $lastError = '';

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
                  FROM cd_target t
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
        $checkQuery = "SELECT calf_target_id FROM cd_target WHERE center_code = :center_code AND year = :year";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([':center_code' => $centerCode, ':year' => $year]);
        
        if ($checkStmt->rowCount() > 0) {
            $this->lastError = 'duplicate';
            return false;
        }
        
        $query = "INSERT INTO cd_target (center_code, target, year) VALUES (:center_code, :target, :year)";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            ':center_code' => $centerCode,
            ':target' => $target,
            ':year' => $year
        ]);
        
        if (!$success) {
            $this->lastError = 'insert_failed';
        }
        return $success;
    }

    public function updateTarget($calfTargetId, $centerCode, $target, $year) {
        $checkQuery = "SELECT calf_target_id FROM cd_target 
                       WHERE center_code = :center_code AND year = :year AND calf_target_id != :calf_target_id";
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([
            ':center_code' => $centerCode,
            ':year' => $year,
            ':calf_target_id' => $calfTargetId
        ]);
        
        if ($checkStmt->rowCount() > 0) {
            $this->lastError = 'duplicate';
            return false;
        }
        
        $query = "UPDATE cd_target SET center_code = :center_code, target = :target, year = :year 
                  WHERE calf_target_id = :calf_target_id";
        $stmt = $this->db->prepare($query);
        $success = $stmt->execute([
            ':calf_target_id' => $calfTargetId,
            ':center_code' => $centerCode,
            ':target' => $target,
            ':year' => $year
        ]);
        
        if (!$success) {
            $this->lastError = 'update_failed';
        }
        return $success;
    }

    public function deleteTarget($calfTargetId) {
        $query = "DELETE FROM cd_target WHERE calf_target_id = :calf_target_id";
        $stmt = $this->db->prepare($query);
        return $stmt->execute([':calf_target_id' => $calfTargetId]);
    }

    public function getAvailableYears() {
        $query = "SELECT DISTINCT year FROM cd_target ORDER BY year DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

$targetManager = new CenterTargetManager($conn);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['set_target'])) {
        $calfTargetId = $_POST['calf_target_id'] ?? null;
        $centerCode = $_POST['center_code'];
        $target = $_POST['target'];
        $year = $_POST['year'];
        
        if ($calfTargetId) {
            $success = $targetManager->updateTarget($calfTargetId, $centerCode, $target, $year);
            $action = "updated";
            $errorType = 'update';
        } else {
            $success = $targetManager->setTarget($centerCode, $target, $year);
            $action = "set";
            $errorType = 'set';
        }
        
        if ($success) {
            $successMessage = "Target successfully $action for " . $_POST['center_name'] . " ($centerCode) in $year";
        } else {
            if ($targetManager->lastError === 'duplicate') {
                $errorMessage = $calfTargetId 
                    ? "Another target already exists for the selected center and year."
                    : "A target for this center and year already exists. Please edit the existing target instead.";
            } else {
                $errorMessage = "Failed to $action target. Please try again.";
            }
        }
    } elseif (isset($_POST['delete_target'])) {
        $calfTargetId = $_POST['calf_target_id'];
        if ($targetManager->deleteTarget($calfTargetId)) {
            $successMessage = "Target successfully deleted.";
        } else {
            $errorMessage = "Failed to delete target.";
        }
    }
}

$currentYear = date('Y');
$selectedYear = $_GET['year'] ?? $currentYear;

$allCenters = $targetManager->getAllCenters();
$currentTargets = $targetManager->getCurrentTargets($selectedYear);
$availableYears = $targetManager->getAvailableYears();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Calf Drop Dashboard</title>
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
        .btn-success {
            background: #10b981;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: #ef4444;
        }
        .btn-danger:hover {
            background: #dc2626;
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
        .delete-btn {
            background-color: #ef4444;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .delete-btn:hover {
            background-color: #dc2626;
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
            width: 100%;
            max-width: 600px;
            border-radius: 8px;
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
            gap: 10px;
        }
        .add-target-btn {
            margin-bottom: 20px;
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

        <nav>
            <ul>
                <li><a href="admin.php#quickfacts-section" class="nav-link">
                    <i class="fa-solid fa-arrow-left"></i> Back to Admin</a></li>

                <li><a href="admin_cd_dashboard.php" class="nav-link" data-section="dashboard-section">
                    <i class="fas fa-chart-line"></i> Dashboard</a></li>

                <li><a href="admin_centertarget_calf_dashboard.php" class="nav-link active" data-section="announcement-section">
                    <i class="fas fa-file-alt"></i> Center Target</a></li>
                
                <li><a href="admin_report_calf_dashboard.php" class="nav-link" data-section="quickfacts-section">
                    <i class="fas fa-sitemap"></i> Reports</a></li>
            </ul>
        </nav>
    </div>

  <div class="container" style="max-width: 100%; width: 95%; margin: 0 auto; font-family: Arial, sans-serif;">
    <h1 class="title" style="font-size: 28px; margin-bottom: 5px;">Calf Drop Center Targets</h1>
    <p class="subtitle" style="font-size: 16px; color: #666; margin-bottom: 20px;">Set and manage Calf Drop targets for each center</p>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success" style="background-color: #d4edda; padding: 10px; border-radius: 5px; margin-bottom: 10px;"><?= $successMessage ?></div>
    <?php elseif (isset($errorMessage)): ?>
        <div class="alert alert-error" style="background-color: #f8d7da; padding: 10px; border-radius: 5px; margin-bottom: 10px;"><?= $errorMessage ?></div>
    <?php endif; ?>

    <div class="card" style="background-color: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
        <div class="year-filter" style="margin-bottom: 15px;">
            <strong>Filter by Year:</strong>
            <?php foreach ($availableYears as $year): ?>
                <button class="year-btn <?= $year == $selectedYear ? 'active' : '' ?>" 
                        onclick="window.location.href='?year=<?= $year ?>'"
                        style="margin: 5px 5px 5px 0; padding: 6px 12px; <?= $year == $selectedYear ? 'background-color:#3a7fc5;color:white;' : '' ?>">
                    <?= $year ?>
                </button>
            <?php endforeach; ?>
        </div>

        <button id="addTargetBtn" class="btn btn-success add-target-btn" style="background-color: #3a7fc5; color: white; padding: 10px 15px; border: none; border-radius: 5px; margin-bottom: 20px;">
            <i class="fas fa-plus"></i> Set New Target
        </button>

        <h2 style="margin-bottom: 15px;">Current Targets</h2>
        
        <?php if (count($currentTargets) > 0): ?>
            <table class="target-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background-color: #f4f4f4;">
                        <th style="border: 1px solid #ccc; padding: 10px;">Center</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Center Code</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Target</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Year</th>
                        <th style="border: 1px solid #ccc; padding: 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($currentTargets as $target): ?>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= htmlspecialchars($target['center_name']) ?></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $target['center_code'] ?></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= number_format($target['target']) ?></td>
                            <td style="border: 1px solid #ddd; padding: 10px;"><?= $target['year'] ?></td>
                            <td style="border: 1px solid #ddd; padding: 10px;">
                                <button class="edit-btn" 
                                        onclick="openEditModal(
                                            '<?= $target['calf_target_id'] ?>',
                                            '<?= $target['center_code'] ?>',
                                            `<?= htmlspecialchars($target['center_name'], ENT_QUOTES) ?>`,
                                             <?= $target['target'] ?>,
                                             <?= $target['year'] ?>
                                          )"
                                        style="background-color: #ffc107; color: #000; border: none; padding: 6px 10px; border-radius: 4px; margin-right: 5px;">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="calf_target_id" value="<?= $target['calf_target_id'] ?>">
                                    <button type="submit" name="delete_target" class="delete-btn" 
                                            onclick="return confirm('Are you sure you want to delete this target?')"
                                            style="background-color: #dc3545; color: white; border: none; padding: 6px 10px; border-radius: 4px;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No targets set for <?= isset($_GET['year']) ? "year $selectedYear" : "any year" ?>.</p>
        <?php endif; ?>
    </div>
</div>


    <!-- Add/Edit Target Modal -->
    <div id="targetModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">Set New Target</h3>
                <span class="close">&times;</span>
            </div>
            <form method="POST" id="targetForm">
                <input type="hidden" id="calf_target_id" name="calf_target_id">
                <div class="form-group">
                    <label for="modal_center_code">Center</label>
                    <select id="modal_center_code" name="center_code" class="form-control" required>
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
                    <label for="modal_year">Year</label>
                    <input type="number" id="modal_year" name="year" class="form-control" value="<?= $currentYear ?>" min="2000" max="2100" required>
                </div>
                <div class="form-group">
                    <label for="modal_target">Target Number of Calf Drops</label>
                    <input type="number" id="modal_target" name="target" class="form-control" min="0" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close">Cancel</button>
                    <button type="submit" name="set_target" class="btn">
                        <span id="submitBtnText">Set Target</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal elements
        const modal = document.getElementById("targetModal");
        const addBtn = document.getElementById("addTargetBtn");
        const closeBtn = document.getElementsByClassName("close")[0];
        const modalTitle = document.getElementById("modalTitle");
        const submitBtnText = document.getElementById("submitBtnText");
        
        // Form elements
        const targetForm = document.getElementById("targetForm");
        const calfTargetId = document.getElementById("calf_target_id");
        const modalCenterCode = document.getElementById("modal_center_code");
        const modalYear = document.getElementById("modal_year");
        const modalTarget = document.getElementById("modal_target");
        const centerName = document.getElementById("center_name");

        // Open modal for adding new target
        addBtn.onclick = function() {
            resetForm();
            modalTitle.textContent = "Set New Target";
            submitBtnText.textContent = "Set Target";
            modal.style.display = "block";
        }

        // Open modal for editing target
        function openEditModal(id, code, name, target, year) {
            calfTargetId.value = id;
            modalCenterCode.value = code;
            modalYear.value = year;
            modalTarget.value = target;
            centerName.value = name;
            
            modalTitle.textContent = "Edit Target";
            submitBtnText.textContent = "Update Target";
            modal.style.display = "block";
        }

        // Close modal
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Reset form
        function resetForm() {
            targetForm.reset();
            calfTargetId.value = "";
            centerName.value = "";
        }

        // Auto-fill center name when center is selected
        modalCenterCode.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.text.includes('(')) {
                const name = selectedOption.text.split('(')[0].trim();
                centerName.value = name;
            }
        });
    </script>
</body>
</html>