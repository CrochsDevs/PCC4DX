<?php
include('db_config.php');
session_start();

class MilkReportManager {
    private $conn;
    private $centerCode;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function getReportData() {
        return [
            'partners' => $this->getActivePartners(),
            'entries' => $this->getFilteredEntries()
        ];
    }

    private function getActivePartners() {
        try {
            $stmt = $this->conn->prepare("SELECT id, partner_name, coop_type 
                FROM partners 
                WHERE center_code = :center_code AND is_active = 1
                ORDER BY partner_name");
            $stmt->execute([':center_code' => $this->centerCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function getFilteredEntries() {
        try {
            // Build the base query
            $query = "SELECT mp.*, p.partner_name 
                FROM milk_production mp
                JOIN partners p ON mp.partner_id = p.id
                WHERE mp.center_code = :center_code";
            
            // Initialize parameters array
            $params = [':center_code' => $this->centerCode];
            
            // Apply filters if they exist
            if (!empty($_GET['start_date'])) {
                $query .= " AND mp.entry_date >= :start_date";
                $params[':start_date'] = $_GET['start_date'];
            }
            
            if (!empty($_GET['end_date'])) {
                $query .= " AND mp.end_date <= :end_date";
                $params[':end_date'] = $_GET['end_date'];
            }
            
            if (!empty($_GET['partner_id']) && $_GET['partner_id'] != 'all') {
                $query .= " AND mp.partner_id = :partner_id";
                $params[':partner_id'] = $_GET['partner_id'];
            }
            
            $query .= " ORDER BY mp.entry_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $_SESSION['message'] = "Error fetching entries: " . $e->getMessage();
            $_SESSION['message_type'] = "danger";
            return [];
        }
    }
}

// Initialize and get report data
$reportManager = new MilkReportManager($conn);
$data = $reportManager->getReportData();
$partners = $data['partners'];
$entries = $data['entries'];

// Calculate totals
$totalmilk_produce = 0;
$totalQuantity = 0;
$totalTotal = 0;
$totalPrice = 0;
$count = 0;

foreach ($entries as $entry) {
    $totalmilk_produce += $entry['milk_produce'];
    $totalQuantity += $entry['quantity'];
    $totalTotal += $entry['total'];
    $totalPrice += $entry['volume'];  
    $count++; 
}

// Get current filter values
$currentStartDate = $_GET['start_date'] ?? '';
$currentEndDate = $_GET['end_date'] ?? '';
$currentPartner = $_GET['partner_id'] ?? 'all';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Production Report</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/center.css">
    <link rel="stylesheet" href="css/partners.css"> 
    <style>
        :root {
            --primary: #0056b3;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --border: #dee2e6;
            --focus-border: #0056b3;
            --alert-success: #d4edda;
            --alert-danger: #f8d7da;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

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

        .filter-group input, .filter-group select {
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
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .entries-table th,
        .entries-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 16px;
        }

        .entries-table th {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
        }

        .entries-table tr:hover {
            background-color: #f1f1f1;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--alert-success);
            color: #155724;
        }

        .alert-danger {
            background-color: var(--alert-danger);
            color: #721c24;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User Profile Section -->
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
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="partners.php" class="nav-link"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="milk_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
            </div>
            
            <div class="header-right">
                <div class="notification-container">
                    <button class="notification-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="notification-dropdown">
                        <div class="notification-header">
                            <h4>Notifications</h4>
                            <span class="mark-all-read">Mark all as read</span>
                        </div>
                        <div class="notification-list">
                            <a href="#" class="notification-item unread">
                                <div class="notification-icon">
                                    <i class="fas fa-users text-primary"></i>
                                </div>
                                <div class="notification-content">
                                    <p>5 new farmers registered today</p>
                                    <small>2 hours ago</small>
                                </div>
                            </a>
                            <a href="#" class="notification-item unread">
                                <div class="notification-icon">
                                    <i class="fas fa-paw text-success"></i>
                                </div>
                                <div class="notification-content">
                                    <p>New carabao health report available</p>
                                    <small>5 hours ago</small>
                                </div>
                            </a>
                            <a href="#" class="notification-item">
                                <div class="notification-icon">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                                <div class="notification-content">
                                    <p>3 pending requests need approval</p>
                                    <small>Yesterday</small>
                                </div>
                            </a>
                        </div>
                        <div class="notification-footer">
                            <a href="#">View all notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <h2>Production Entries</h2>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
                <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="start_date">Week Start From</label>
                        <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($currentStartDate) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="end_date">Week End To</label>
                        <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($currentEndDate) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label for="partner_id">Cooperative</label>
                        <select id="partner_id" name="partner_id">
                            <option value="all">All Cooperatives</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?= $partner['id'] ?>" <?= $currentPartner == $partner['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($partner['partner_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                        <a href="milk_report.php" class="btn-reset">
                            <i class="fas fa-sync-alt"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <table class="entries-table">
            <thead>
                <tr>
                    <th>Partners</th>
                    <th>Week</th>
                    <th>Week Start</th>
                    <th>Week End</th>
                    <th>Milk Produce (kg)</th>
                    <th>Milk Trade (kg)</th>
                    <th>Price/kg</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($entries)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No entries found</td>
                    </tr>
                <?php else: ?>
                    <tr style="background-color:rgb(198, 198, 198); font-weight: bold;">
                        <td colspan="4">Total: <?= number_format($count) ?></td>
                        <td><?= number_format($totalmilk_produce, 2) ?></td>
                        <td><?= number_format($totalQuantity, 2) ?></td>
                        <td>₱<?= number_format($count > 0 ? $totalPrice / $count : 0, 2) ?></td>
                        <td>₱<?= number_format($totalTotal, 2) ?></td>
                    </tr>
                    <?php foreach ($entries as $entry): ?>
                        <tr>
                            <td><?= htmlspecialchars($entry['partner_name']) ?></td>
                            <td><?= date('o-\WW', strtotime($entry['entry_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($entry['entry_date'])) ?></td>
                            <td><?= date('M d, Y', strtotime($entry['end_date'])) ?></td>
                            <td><?= number_format($entry['milk_produce'] ?? 0, 2) ?></td>
                            <td><?= number_format($entry['quantity'] ?? 0, 2) ?></td>
                            <td>₱<?= number_format($entry['volume'] ?? 0, 2) ?></td>
                            <td>₱<?= number_format($entry['total'] ?? 0, 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        // Set end date based on start date (for filters)
        document.getElementById('start_date')?.addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (this.value && (!endDateInput.value || new Date(endDateInput.value) < new Date(this.value))) {
                endDateInput.value = this.value;
            }
        });
    </script>
</body>
</html>