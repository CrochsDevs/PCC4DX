<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

class ReportManager {
    private $conn;
    private $centerCode;
    
    // Marketing activity options
    private $marketingOptions = [
        'Social Media Marketing',
        'Sales Promotion',
        'Sponsorship',
        'Flyering',
        'Free Samples',
        'Coupons',
        'Caravan',
        'Loyalty Programs',
        'Product Expo'
    ];
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function getStores() {
        try {
            $query = "SELECT id, name FROM dairy_boxes WHERE center_code = :center_code ORDER BY name";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([':center_code' => $this->centerCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getReports($startDate = null, $endDate = null) {
        try {
            $query = "SELECT r.*, d.name as store_name 
                      FROM dbox_reports r
                      JOIN dairy_boxes d ON r.store_id = d.id
                      WHERE r.center_code = :center_code";
            
            $params = [':center_code' => $this->centerCode];
            
            if ($startDate && $endDate) {
                $query .= " AND r.report_date BETWEEN :start_date AND :end_date";
                $params[':start_date'] = $startDate;
                $params[':end_date'] = $endDate;
            }
            
            $query .= " ORDER BY r.report_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function addReport($data) {
        try {
            $query = "INSERT INTO dbox_reports 
                     (store_id, report_date, dairy_box_sales, kadiwa_sales, day_of_week, marketing_activities, center_code)
                     VALUES 
                     (:store_id, :report_date, :dairy_box_sales, :kadiwa_sales, :day_of_week, :marketing_activities, :center_code)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':store_id' => $data['store_id'],
                ':report_date' => $data['report_date'],
                ':dairy_box_sales' => $data['dairy_box_sales'],
                ':kadiwa_sales' => $data['kadiwa_sales'],
                ':day_of_week' => $data['day_of_week'],
                ':marketing_activities' => implode(',', $data['marketing_activities']),
                ':center_code' => $this->centerCode
            ]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function updateReport($data) {
        try {
            $query = "UPDATE dbox_reports SET
                     store_id = :store_id,
                     report_date = :report_date,
                     dairy_box_sales = :dairy_box_sales,
                     kadiwa_sales = :kadiwa_sales,
                     day_of_week = :day_of_week,
                     marketing_activities = :marketing_activities
                     WHERE id = :id AND center_code = :center_code";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':store_id' => $data['store_id'],
                ':report_date' => $data['report_date'],
                ':dairy_box_sales' => $data['dairy_box_sales'],
                ':kadiwa_sales' => $data['kadiwa_sales'],
                ':day_of_week' => $data['day_of_week'],
                ':marketing_activities' => implode(',', $data['marketing_activities']),
                ':id' => $data['id'],
                ':center_code' => $this->centerCode
            ]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function deleteReport($id) {
        try {
            $query = "DELETE FROM dbox_reports WHERE id = :id AND center_code = :center_code";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':id' => $id,
                ':center_code' => $this->centerCode
            ]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getMarketingOptions() {
        return $this->marketingOptions;
    }
}

// Initialize report manager
$reportManager = new ReportManager($conn);
$stores = $reportManager->getStores();
$marketingOptions = $reportManager->getMarketingOptions();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $data = [
            'store_id' => $_POST['store_id'],
            'report_date' => $_POST['report_date'],
            'dairy_box_sales' => $_POST['dairy_box_sales'],
            'kadiwa_sales' => $_POST['kadiwa_sales'],
            'day_of_week' => date('N', strtotime($_POST['report_date'])),
            'marketing_activities' => $_POST['marketing_activities'] ?? []
        ];
        
        if ($reportManager->addReport($data)) {
            $_SESSION['message'] = "Report added successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error adding report!";
            $_SESSION['message_type'] = "danger";
        }
        header("Location: dbox_report.php");
        exit;
    }
    elseif (isset($_POST['edit'])) {
        $data = [
            'id' => $_POST['report_id'],
            'store_id' => $_POST['store_id'],
            'report_date' => $_POST['report_date'],
            'dairy_box_sales' => $_POST['dairy_box_sales'],
            'kadiwa_sales' => $_POST['kadiwa_sales'],
            'day_of_week' => date('N', strtotime($_POST['report_date'])),
            'marketing_activities' => $_POST['marketing_activities'] ?? []
        ];
        
        if ($reportManager->updateReport($data)) {
            $_SESSION['message'] = "Report updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error updating report!";
            $_SESSION['message_type'] = "danger";
        }
        header("Location: dbox_report.php");
        exit;
    }
    elseif (isset($_POST['delete'])) {
        if ($reportManager->deleteReport($_POST['report_id'])) {
            $_SESSION['message'] = "Report deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Error deleting report!";
            $_SESSION['message_type'] = "danger";
        }
        header("Location: dbox_report.php");
        exit;
    }
}

// Get reports based on filters
$startDate = $_GET['start_date'] ?? null;
$endDate = $_GET['end_date'] ?? null;
$reports = $reportManager->getReports($startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Sales Reports</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/center.css">
    <style>
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #495057;
        }

        .filter-group input, 
        .filter-group select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            background-color: #0056b3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-filter:hover {
            background-color: #004494;
        }

        .btn-reset {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn-reset:hover {
            background-color: #5a6268;
        }

        .entries-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .entries-table th,
        .entries-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
            font-size: 16px;
        }

        .entries-table th {
            background-color: #0056b3;
            color: white;
            font-weight: bold;
        }

        .entries-table tr:hover {
            background-color: #f1f1f1;
        }

        .text-center {
            text-align: center;
        }

        /* Marketing activities dropdown */
        .marketing-toggle {
            color: #0056b3;
            cursor: pointer;
            text-decoration: underline;
        }
        
        .marketing-details {
            display: none;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            margin-top: 5px;
        }
        
        .marketing-details.show {
            display: block;
        }
        
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            overflow-y: auto;
        }
        
        .modal.show {
            display: block;
        }
        
        .modal-dialog {
            max-width: 800px;
            margin: 2rem auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .modal-header {
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            font-size: 1.25rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #6c757d;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        /* Form styles */
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Marketing activities checkboxes */
        .marketing-container {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .marketing-checkbox {
            display: flex;
            align-items: center;
            padding: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .marketing-checkbox input {
            margin-right: 8px;
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                min-width: 100%;
            }
            
            .entries-table th, 
            .entries-table td {
                padding: 10px;
                font-size: 14px;
            }
            
            .marketing-container {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modal-dialog {
                max-width: 95%;
                margin: 1rem auto;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fas fa-dashboard"></i> Back to quickfacts</a></li>
                <li><a href="dbox_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="dairybox.php" class="nav-link"><i class="fas fa-store"></i> Stores</a></li>
                <li><a href="dbox_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] === 'success' ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $_SESSION['message_type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                <span><?= $_SESSION['message'] ?></span>
            </div>
            <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
        <?php endif; ?>

        <h2>Store Sales Reports</h2>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="start_date">Date From</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate ?? '') ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">Date To</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate ?? '') ?>">
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="dbox_report.php" class="btn-reset">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-header">
            <h3 class="card-title">Sales Reports</h3>
            <button id="createBtn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Report
            </button>
        </div>

        <!-- Reports Table -->
        <table class="entries-table">
            <thead>
                <tr>
                    <th>Store</th>
                    <th>Date</th>
                    <th>Day</th>
                    <th>Dairy Box Sales (₱)</th>
                    <th>Kadiwa Sales (₱)</th>
                    <th>Marketing Activities</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7" class="text-center">No report data available yet</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $report): 
                        $activities = explode(',', $report['marketing_activities']);
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($report['store_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($report['report_date'])) ?></td>
                            <td><?= $report['day_of_week'] ?></td>
                            <td><?= number_format($report['dairy_box_sales'], 2) ?></td>
                            <td><?= number_format($report['kadiwa_sales'], 2) ?></td>
                            <td>
                                <span class="marketing-toggle" onclick="toggleMarketingDetails(this)">
                                    <?= count($activities) ?> activities
                                </span>
                                <div class="marketing-details">
                                    <?php foreach ($activities as $activity): ?>
                                        <div><?= htmlspecialchars($activity) ?></div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-info btn-sm edit-btn"
                                            data-id="<?= $report['id'] ?>"
                                            data-store-id="<?= $report['store_id'] ?>"
                                            data-store-name="<?= htmlspecialchars($report['store_name']) ?>"
                                            data-report-date="<?= htmlspecialchars($report['report_date']) ?>"
                                            data-dairy-box-sales="<?= htmlspecialchars($report['dairy_box_sales']) ?>"
                                            data-kadiwa-sales="<?= htmlspecialchars($report['kadiwa_sales']) ?>"
                                            data-marketing-activities="<?= htmlspecialchars($report['marketing_activities']) ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this report?');" style="display:inline;">
                                        <input type="hidden" name="report_id" value="<?= $report['id'] ?>">
                                        <button type="submit" name="delete" class="btn btn-danger btn-sm delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Create Modal -->
    <div id="createModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Add New Sales Report</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="store_id" class="form-label">Store</label>
                        <select id="store_id" name="store_id" class="form-control" required>
                            <option value="">Select a store</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="report_date" class="form-label">Report Date</label>
                        <input type="date" id="report_date" name="report_date" class="form-control" required>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="dairy_box_sales" class="form-label">Dairy Box Sales (₱)</label>
                            <input type="number" id="dairy_box_sales" name="dairy_box_sales" class="form-control" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="kadiwa_sales" class="form-label">Kadiwa Sales (₱)</label>
                            <input type="number" id="kadiwa_sales" name="kadiwa_sales" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Marketing Activities</label>
                        <small class="text-muted d-block">Select all marketing activities conducted</small>
                        <div class="marketing-container">
                            <?php foreach ($marketingOptions as $option): ?>
                                <div class="marketing-checkbox">
                                    <input type="checkbox" id="marketing_<?= str_replace(' ', '_', strtolower($option)) ?>" 
                                        name="marketing_activities[]" value="<?= htmlspecialchars($option) ?>">
                                    <label for="marketing_<?= str_replace(' ', '_', strtolower($option)) ?>"><?= htmlspecialchars($option) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-cancel">Cancel</button>
                    <button type="submit" name="add" class="btn btn-primary">Save Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 class="modal-title">Edit Sales Report</h3>
                <button class="modal-close">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" id="edit_id" name="report_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_store_id" class="form-label">Store</label>
                        <select id="edit_store_id" name="store_id" class="form-control" required>
                            <option value="">Select a store</option>
                            <?php foreach ($stores as $store): ?>
                                <option value="<?= $store['id'] ?>"><?= htmlspecialchars($store['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_report_date" class="form-label">Report Date</label>
                        <input type="date" id="edit_report_date" name="report_date" class="form-control" required>
                    </div>
                    
                    <div class="form-row" style="display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="edit_dairy_box_sales" class="form-label">Dairy Box Sales (₱)</label>
                            <input type="number" id="edit_dairy_box_sales" name="dairy_box_sales" class="form-control" min="0" step="0.01" required>
                        </div>
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="edit_kadiwa_sales" class="form-label">Kadiwa Sales (₱)</label>
                            <input type="number" id="edit_kadiwa_sales" name="kadiwa_sales" class="form-control" min="0" step="0.01" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Marketing Activities</label>
                        <small class="text-muted d-block">Select all marketing activities conducted</small>
                        <div class="marketing-container" id="edit_marketing_container">
                            <?php foreach ($marketingOptions as $option): ?>
                                <div class="marketing-checkbox">
                                    <input type="checkbox" id="edit_marketing_<?= str_replace(' ', '_', strtolower($option)) ?>" 
                                        name="marketing_activities[]" value="<?= htmlspecialchars($option) ?>">
                                    <label for="edit_marketing_<?= str_replace(' ', '_', strtolower($option)) ?>"><?= htmlspecialchars($option) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary modal-cancel">Cancel</button>
                    <button type="submit" name="edit" class="btn btn-primary">Update Report</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // DOM elements
        const createBtn = document.getElementById('createBtn');
        const createModal = document.getElementById('createModal');
        const editModal = document.getElementById('editModal');
        const modalCloses = document.querySelectorAll('.modal-close, .modal-cancel');
        const editBtns = document.querySelectorAll('.edit-btn');
        const searchForm = document.getElementById('searchForm');
        const searchInput = document.getElementById('searchInput');
        
        // Set end date based on start date
        document.getElementById('start_date')?.addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (this.value && (!endDateInput.value || new Date(endDateInput.value) < new Date(this.value))) {
                endDateInput.value = this.value;
            }
        });
        
        // Toggle marketing details
        function toggleMarketingDetails(element) {
            const details = element.nextElementSibling;
            details.classList.toggle('show');
        }
        
        // Event listeners
        createBtn.addEventListener('click', () => {
            createModal.classList.add('show');
            document.body.style.overflow = 'hidden';
        });
        
        editBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                // Set form values
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_store_id').value = btn.dataset.storeId;
                document.getElementById('edit_report_date').value = btn.dataset.reportDate;
                document.getElementById('edit_dairy_box_sales').value = btn.dataset.dairyBoxSales;
                document.getElementById('edit_kadiwa_sales').value = btn.dataset.kadiwaSales;
                
                // Clear all marketing checkboxes first
                document.querySelectorAll('#edit_marketing_container input[type="checkbox"]').forEach(checkbox => {
                    checkbox.checked = false;
                });
                
                // Check the marketing activities for this report
                const activities = btn.dataset.marketingActivities.split(',');
                activities.forEach(activity => {
                    const checkbox = document.querySelector(`#edit_marketing_container input[value="${activity.trim()}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
                
                editModal.classList.add('show');
                document.body.style.overflow = 'hidden';
            });
        });
        
        // Modal close handlers
        modalCloses.forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.modal').forEach(modal => {
                    modal.classList.remove('show');
                });
                document.body.style.overflow = 'auto';
            });
        });
        
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                e.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });
        
        // Responsive sidebar toggle
        const sidebarToggle = document.createElement('button');
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        sidebarToggle.style.position = 'fixed';
        sidebarToggle.style.bottom = '20px';
        sidebarToggle.style.right = '20px';
        sidebarToggle.style.zIndex = '999';
        sidebarToggle.style.width = '50px';
        sidebarToggle.style.height = '50px';
        sidebarToggle.style.borderRadius = '50%';
        sidebarToggle.style.backgroundColor = '#0056b3';
        sidebarToggle.style.color = 'white';
        sidebarToggle.style.border = 'none';
        sidebarToggle.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        sidebarToggle.style.cursor = 'pointer';
        sidebarToggle.style.display = 'none';
        
        document.body.appendChild(sidebarToggle);
        
        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('show');
        });
        
        function handleResize() {
            if (window.innerWidth <= 992) {
                sidebarToggle.style.display = 'flex';
                sidebarToggle.style.alignItems = 'center';
                sidebarToggle.style.justifyContent = 'center';
            } else {
                sidebarToggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('show');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize();
        
        // Logout confirmation
        document.getElementById('logoutLink')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = this.href;
            }
        });
    </script>
</body>
</html>