<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['user']['center_code'];

class MilkDashboard {
    private $conn;
    private $centerCode;

    public function __construct($conn, $centerCode) {
        $this->conn = $conn;
        $this->centerCode = $centerCode;
    }

    public function getProductionData($page = 1, $perPage = 10, $search = '', $month = null, $week = null) {
        $offset = ($page - 1) * $perPage;
        
        $query = "SELECT 
                    mp.*, 
                    p.partner_name,
                    WEEK(mp.entry_date, 3) as week_number,
                    DATE_FORMAT(mp.entry_date, '%b %d, %Y') as formatted_entry_date,
                    IFNULL(DATE_FORMAT(mp.end_date, '%b %d, %Y'), 'N/A') as formatted_end_date
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if (!empty($search)) {
            $query .= " AND (
                p.partner_name LIKE :search OR
                mp.status LIKE :search OR
                mp.quantity LIKE :search OR
                mp.volume LIKE :search OR
                mp.total LIKE :search OR
                DATE_FORMAT(mp.entry_date, '%b %d, %Y') LIKE :search OR
                DATE_FORMAT(mp.end_date, '%b %d, %Y') LIKE :search
            )";
            $params[':search'] = "%$search%";
        }
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $query .= " ORDER BY mp.entry_date DESC
                  LIMIT :offset, :perPage";
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalRecords($search = '', $month = null, $week = null) {
        $query = "SELECT COUNT(*) as total
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if (!empty($search)) {
            $query .= " AND (
                p.partner_name LIKE :search OR
                mp.status LIKE :search OR
                mp.quantity LIKE :search OR
                mp.volume LIKE :search OR
                mp.total LIKE :search OR
                DATE_FORMAT(mp.entry_date, '%b %d, %Y') LIKE :search OR
                DATE_FORMAT(mp.end_date, '%b %d, %Y') LIKE :search
            )";
            $params[':search'] = "%$search%";
        }
        
        if ($month) {
            $query .= " AND DATE_FORMAT(mp.entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(mp.entry_date, 3) = :week";
            $params[':week'] = $week;
        }
        
        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getWeeksInMonth($month) {
        $query = "SELECT DISTINCT WEEK(entry_date, 3) as week_number 
                  FROM milk_production 
                  WHERE center_code = :centerCode 
                  AND DATE_FORMAT(entry_date, '%Y-%m') = :month 
                  ORDER BY week_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':centerCode' => $this->centerCode,
            ':month' => $month
        ]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getSummaryStats($month = null, $week = null) {
        $query = "SELECT 
                    SUM(quantity) as total_quantity,
                    SUM(volume) as total_volume,
                    SUM(total) as total_value,
                    COUNT(DISTINCT partner_id) as partner_count,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
                  FROM milk_production
                  WHERE center_code = :centerCode";
        
        $params = [':centerCode' => $this->centerCode];
        
        if ($month) {
            $query .= " AND DATE_FORMAT(entry_date, '%Y-%m') = :month";
            $params[':month'] = $month;
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(entry_date, 3) = :week";
            $params[':week'] = $week;
        }

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMonthlyTrends() {
        $query = "SELECT 
                    DATE_FORMAT(entry_date, '%Y-%m') as month,
                    DATE_FORMAT(entry_date, '%b %Y') as month_display,
                    SUM(quantity) as quantity,
                    SUM(total) as value
                  FROM milk_production
                  WHERE center_code = :centerCode
                  GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $this->centerCode, PDO::PARAM_STR);
        $stmt->execute();
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getPartnerDistribution() {
        $query = "SELECT 
                    p.partner_name,
                    SUM(mp.quantity) as total_quantity,
                    SUM(mp.total) as total_value
                  FROM milk_production mp
                  JOIN partners p ON mp.partner_id = p.id
                  WHERE mp.center_code = :centerCode
                  GROUP BY p.partner_name
                  ORDER BY total_value DESC
                  LIMIT 5";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $this->centerCode, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Get parameters
$selectedMonth = $_GET['month'] ?? date('Y-m');
$searchTerm = $_GET['search'] ?? '';
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;

// Initialize dashboard
$dashboard = new MilkDashboard($conn, $centerCode);

// Get weeks in selected month
$weeksInMonth = $dashboard->getWeeksInMonth($selectedMonth);
$totalWeeks = count($weeksInMonth);
$selectedWeek = $weeksInMonth[$currentPage - 1] ?? null;

// Get data
$productionData = $dashboard->getProductionData($currentPage, $perPage, $searchTerm, $selectedMonth, $selectedWeek);
$totalRecords = $dashboard->getTotalRecords($searchTerm, $selectedMonth, $selectedWeek);
$summaryStats = $dashboard->getSummaryStats($selectedMonth, $selectedWeek);
$monthlyTrends = $dashboard->getMonthlyTrends();
$partnerDistribution = $dashboard->getPartnerDistribution();

// Calculate total pages
$totalPages = ceil($totalRecords / $perPage);

// Format data for display
$totalQuantity = number_format($summaryStats['total_quantity'] ?? 0, 2);
$totalVolume = number_format($summaryStats['total_volume'] ?? 0, 2) . ' L';
$totalValue = '₱' . number_format($summaryStats['total_value'] ?? 0, 2);
$partnerCount = $summaryStats['partner_count'] ?? 0;
$pendingCount = $summaryStats['pending_count'] ?? 0;

// Prepare chart data
$chartLabels = array_column($monthlyTrends, 'month_display');
$chartQuantities = array_column($monthlyTrends, 'quantity');
$chartValues = array_column($monthlyTrends, 'value');
$partnerLabels = array_column($partnerDistribution, 'partner_name');
$partnerValues = array_column($partnerDistribution, 'total_value');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Production Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #2A5C82;
            --primary-light: #3A6C92;
            --secondary: #5CACEE;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin-left: 280px;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(180deg, #0056b3 0%, #3a7fc5 100%);
            color: white;
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
        }

        .sidebar h2 {
            text-align: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
        }

        .sidebar ul {
            list-style: none;
            padding-left: 0;
        }

        .sidebar li {
            margin-bottom: 1.25rem;
            transition: transform 0.2s;
        }

        .sidebar li:hover {
            transform: translateX(5px);
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 0.9rem 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .sidebar a i {
            margin-right: 1rem;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .sidebar a.active {
            background: #ffc107;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .user-profile {
            text-align: center;
            padding: 1.5rem 1rem;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            overflow: hidden;
            border: 3px solid var(--secondary);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: red;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 2rem;
        }

        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-completed {
            background-color: #d4edda;
            color: #155724;
        }

        #loadingSpinner {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Fixed Sidebar -->
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
                <li><a href="milk_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="mp_entry.php" class="nav-link "><i class="fas fa-users"></i> New Entry</a></li>
                <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="dashboard-header p-4 mb-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1><i class="fas fa-chart-line"></i> Milk Production Dashboard</h1>
                    <p class="mb-0">Center: <?= htmlspecialchars($centerCode) ?></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="d-inline-block me-3">
                        <select id="monthSelect" class="form-select form-select-sm bg-white">
                            <?php 
                            $months = $dashboard->getMonthlyTrends();
                            foreach (array_reverse($months) as $month): 
                                $monthValue = $month['month'];
                            ?>
                                <option value="<?= $monthValue ?>" <?= $monthValue == $selectedMonth ? 'selected' : '' ?>>
                                    <?= $month['month_display'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-sm btn-light">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card mb-4 border-left-primary">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-primary">
                                <i class="fas fa-weight-hanging fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Produced</h6>
                                <h3 class="mb-0"><?= $totalQuantity ?> kg</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card mb-4 border-left-success">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-success">
                                <i class="fas fa-peso-sign fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Revenue</h6>
                                <h3 class="mb-0"><?= $totalValue ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card mb-4 border-left-info">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-info">
                                <i class="fas fa-cow fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Total Volume</h6>
                                <h3 class="mb-0"><?= $totalVolume ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card mb-4 border-left-warning">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="me-3 text-warning">
                                <i class="fas fa-users fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1">Active Partners</h6>
                                <h3 class="mb-0"><?= $partnerCount ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-lg-8">
                <div class="chart-container card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Monthly Production Trend</h5>
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="trendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="chart-container card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Partner Distribution</h5>
                        <div class="chart-area" style="height: 300px;">
                            <canvas id="partnerChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Production Entries -->
        <div class="card data-table mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Production Entries for Week <?= $selectedWeek ?? 'N/A' ?></h5>
                <div class="input-group" style="width: 300px;">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" 
                           placeholder="Search entries..." value="<?= htmlspecialchars($searchTerm) ?>">
                </div>
            </div>
            <div class="card-body">
                <div id="loadingSpinner" class="text-center my-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Entry Date</th>
                                <th>End Date</th>
                                <th>Quantity (kg)</th>
                                <th>Volume (L)</th>
                                <th>Total Value</th>
                                <th>Partner</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="entriesTableBody">
                            <?php foreach ($productionData as $entry): ?>
                            <tr>
                                <td><?= $entry['formatted_entry_date'] ?></td>
                                <td><?= $entry['formatted_end_date'] ?></td>
                                <td><?= number_format($entry['quantity'], 2) ?></td>
                                <td><?= number_format($entry['volume'], 2) ?></td>
                                <td>₱<?= number_format($entry['total'], 2) ?></td>
                                <td><?= htmlspecialchars($entry['partner_name']) ?></td>
                                <td>
                                    <span class="badge <?= $entry['status'] === 'Pending' ? 'badge-pending' : 'badge-completed' ?>">
                                        <?= htmlspecialchars($entry['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <nav aria-label="Table navigation">
                    <ul class="pagination justify-content-center mb-0">
                        <li class="page-item <?= $currentPage == 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?month=<?= $selectedMonth ?>&page=<?= $currentPage - 1 ?>&search=<?= urlencode($searchTerm) ?>" tabindex="-1">Previous</a>
                        </li>
                        
                        <?php 
                        // Show limited pagination links
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            echo '<li class="page-item"><a class="page-link" href="?month='.$selectedMonth.'&page=1&search='.urlencode($searchTerm).'">1</a></li>';
                            if ($startPage > 2) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="?month=<?= $selectedMonth ?>&page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor;
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) {
                                echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                            }
                            echo '<li class="page-item"><a class="page-link" href="?month='.$selectedMonth.'&page='.$totalPages.'&search='.urlencode($searchTerm).'">'.$totalPages.'</a></li>';
                        }
                        ?>
                        
                        <li class="page-item <?= $currentPage == $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?month=<?= $selectedMonth ?>&page=<?= $currentPage + 1 ?>&search=<?= urlencode($searchTerm) ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script>
        // Trend Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [
                    {
                        label: 'Quantity (kg)',
                        data: <?= json_encode($chartQuantities) ?>,
                        backgroundColor: 'rgba(42, 92, 130, 0.7)',
                        borderColor: 'rgba(42, 92, 130, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Value (₱)',
                        data: <?= json_encode($chartValues) ?>,
                        backgroundColor: 'rgba(92, 172, 238, 0.7)',
                        borderColor: 'rgba(92, 172, 238, 1)',
                        borderWidth: 1,
                        type: 'line',
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Quantity (kg)' }
                    },
                    y1: {
                        position: 'right',
                        beginAtZero: true,
                        title: { display: true, text: 'Value (₱)' },
                        grid: { drawOnChartArea: false }
                    }
                }
            }
        });

        // Partner Chart
        const partnerCtx = document.getElementById('partnerChart').getContext('2d');
        new Chart(partnerCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($partnerLabels) ?>,
                datasets: [{
                    data: <?= json_encode($partnerValues) ?>,
                    backgroundColor: [
                        'rgba(42, 92, 130, 0.7)',
                        'rgba(92, 172, 238, 0.7)',
                        'rgba(40, 167, 69, 0.7)',
                        'rgba(255, 193, 7, 0.7)',
                        'rgba(220, 53, 69, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });

        // Month selector change handler
        $('#monthSelect').change(function() {
            const month = $(this).val();
            window.location.href = `?month=${month}&page=1&search=<?= urlencode($searchTerm) ?>`;
        });

        // AJAX Search Functionality
        $(document).ready(function() {
            $('#searchInput').on('keyup', function() {
                const searchTerm = $(this).val().trim();
                const month = $('#monthSelect').val();
                
                if(searchTerm.length > 2 || searchTerm.length === 0) {
                    $('#loadingSpinner').show();
                    
                    $.ajax({
                        url: 'search_entries.php',
                        method: 'POST',
                        data: { 
                            search: searchTerm,
                            month: month,
                            centerCode: '<?= $centerCode ?>'
                        },
                        success: function(response) {
                            $('#entriesTableBody').html(response);
                            $('#loadingSpinner').hide();
                        },
                        error: function(xhr, status, error) {
                            console.error(error);
                            $('#entriesTableBody').html('<tr><td colspan="7" class="text-center">Error loading data</td></tr>');
                            $('#loadingSpinner').hide();
                        }
                    });
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>