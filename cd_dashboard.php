<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

// Define target value for grand total only
$targetValues = [
    'grand_total' => 7000
];

class DashboardManager {
    private $db;
    private $centerCode;
    
    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }
    
    // Always get cumulative totals regardless of filters
    public function getSummaryData() {
        $query = "SELECT 
                    SUM(ai) as total_ai,
                    SUM(bep) as total_bep,
                    SUM(ih) as total_ih,
                    SUM(private) as total_private,
                    SUM(ai + bep + ih + private) as grand_total
                  FROM calf_drop 
                  WHERE center = :center";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get filtered data for charts - modified to handle multiple months
    public function getFilteredData($year = null, $months = null, $week = null) {
        $query = "SELECT 
                    SUM(ai) as total_ai,
                    SUM(bep) as total_bep,
                    SUM(ih) as total_ih,
                    SUM(private) as total_private,
                    SUM(ai + bep + ih + private) as grand_total
                  FROM calf_drop 
                  WHERE center = :center";
        
        $params = [':center' => $this->centerCode];
        
        if ($year !== null) {
            $query .= " AND YEAR(date) = :year";
            $params[':year'] = $year;
        }
        
        if ($months !== null && !empty($months)) {
            if (is_array($months)) {
                // Use named parameters instead of positional
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i"; // Create unique named parameters for each month
                    $params[":month$i"] = $month; // Bind month values
                }
                $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
            } else {
                $query .= " AND MONTH(date) = :month";
                $params[':month'] = $months;
            }
        }
        
        if ($week !== null) {
            $query .= " AND WEEK(date, 1) = :week";
            $params[':week'] = $week;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Modified to handle multiple months
    public function getMonthlyData($year = null, $months = null) {
        $query = "SELECT 
                    YEAR(date) as year,
                    MONTH(date) as month,
                    SUM(ai) as ai,
                    SUM(bep) as bep,
                    SUM(ih) as ih,
                    SUM(private) as private
                  FROM calf_drop 
                  WHERE center = :center";
        
        $params = [':center' => $this->centerCode];
        
        if ($year !== null) {
            $query .= " AND YEAR(date) = :year";
            $params[':year'] = $year;
        }
        
        if ($months !== null && !empty($months)) {
            if (is_array($months)) {
                // Use named parameters for each month
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i";  // Create unique named placeholders for each month
                    $params[":month$i"] = $month;  // Bind the month values
                }
                $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
            } else {
                $query .= " AND MONTH(date) = :month";
                $params[':month'] = $months;
            }
        }
        
        $query .= " GROUP BY YEAR(date), MONTH(date)
                    ORDER BY year DESC, month DESC
                    LIMIT 12";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getYearlyData() {
        $query = "SELECT 
                    YEAR(date) as year,
                    SUM(ai) as ai,
                    SUM(bep) as bep,
                    SUM(ih) as ih,
                    SUM(private) as private
                  FROM calf_drop 
                  WHERE center = :center
                  GROUP BY YEAR(date)
                  ORDER BY year DESC
                  LIMIT 5";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getAvailableYears() {
        $query = "SELECT DISTINCT YEAR(date) as year 
                  FROM calf_drop 
                  WHERE center = :center
                  ORDER BY year DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    // Modified to handle multiple months
    public function getWeeksInMonth($year, $months) {
        if (empty($months)) return [];
        
        $query = "SELECT DISTINCT WEEK(date, 1) as week 
                  FROM calf_drop 
                  WHERE center = :center 
                  AND YEAR(date) = :year";
        
        $params = [
            ':center' => $this->centerCode,
            ':year' => $year
        ];
        
        if (is_array($months)) {
            // Create named parameters for each month
            $monthParams = [];
            foreach ($months as $i => $month) {
                $paramName = ":month" . $i;
                $monthParams[] = $paramName;
                $params[$paramName] = $month;
            }
            $query .= " AND MONTH(date) IN (" . implode(',', $monthParams) . ")";
        } else {
            $query .= " AND MONTH(date) = :month";
            $params[':month'] = $months;
        }
        
        $query .= " ORDER BY week";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }

    public function getDailyData($year, $months, $week) {
        if (empty($months)) return [];
    
        $query = "SELECT 
                    DAYNAME(date) as day_name,
                    DATE_FORMAT(date, '%Y-%m-%d') as full_date,
                    DAYOFWEEK(date) as day_number,
                    SUM(ai) as ai,
                    SUM(bep) as bep,
                    SUM(ih) as ih,
                    SUM(private) as private
                  FROM calf_drop 
                  WHERE center = :center
                  AND YEAR(date) = :year
                  AND WEEK(date, 1) = :week";
    
        $params = [
            ':center' => $this->centerCode,
            ':year' => $year,
            ':week' => $week
        ];
    
        // Use named parameters for months
        if (!empty($months)) {
            $placeholders = [];
            foreach ($months as $i => $month) {
                $placeholders[] = ":month$i";  // Create unique named placeholders
                $params[":month$i"] = $month;  // Bind each month to its parameter
            }
            $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
        }
    
        $query .= " GROUP BY DAYNAME(date), DATE_FORMAT(date, '%Y-%m-%d'), DAYOFWEEK(date)
                    ORDER BY DAYOFWEEK(date)";
    
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
}

// Handle AJAX request for weeks
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $year = $_GET['year'] ?? null;
    $months = isset($_GET['months']) ? explode(',', $_GET['months']) : null;
    $centerCode = $_SESSION['center_code'];
    
    if ($year && $months) {
        $dashboardManager = new DashboardManager($conn, $centerCode);
        $weeks = $dashboardManager->getWeeksInMonth($year, $months);
        echo json_encode($weeks);
    } else {
        echo json_encode([]);
    }
    exit;
}

$centerCode = $_SESSION['center_code'];
$dashboardManager = new DashboardManager($conn, $centerCode);

// Get current year and month for default filter
$currentYear = date('Y');
$currentMonth = date('n');
$availableYears = $dashboardManager->getAvailableYears();

// Get filter parameters from request
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$selectedMonths = isset($_GET['months']) ? array_map('intval', explode(',', $_GET['months'])) : null;
$selectedWeek = isset($_GET['week']) ? (int)$_GET['week'] : null;

// Get available weeks only if months are selected
$availableWeeks = $selectedMonths ? $dashboardManager->getWeeksInMonth($selectedYear, $selectedMonths) : [];

// Get cumulative summary data (all-time)
$summaryData = $dashboardManager->getSummaryData();

// Get filtered data for charts
$filteredData = $dashboardManager->getFilteredData($selectedYear, $selectedMonths, $selectedWeek);
$monthlyData = $dashboardManager->getMonthlyData($selectedYear, $selectedMonths);
$yearlyData = $dashboardManager->getYearlyData();

// Prepare data for charts
function prepareChartData($data) {
    $labels = [];
    $aiData = [];
    $bepData = [];
    $ihData = [];
    $privateData = [];

    foreach (array_reverse($data) as $item) {
        if (isset($item['month'])) {
            $labels[] = date('M', mktime(0, 0, 0, $item['month'], 1)) . ' ' . $item['year'];
        } else {
            $labels[] = $item['year'];
        }
        $aiData[] = $item['ai'];
        $bepData[] = $item['bep'];
        $ihData[] = $item['ih'];
        $privateData[] = $item['private'];
    }

    return [
        'labels' => $labels,
        'aiData' => $aiData,
        'bepData' => $bepData,
        'ihData' => $ihData,
        'privateData' => $privateData
    ];
}

$dailyData = [];
if ($selectedWeek && $selectedMonths) {
    $dailyData = $dashboardManager->getDailyData($selectedYear, $selectedMonths, $selectedWeek);
}

// Prepare daily chart data
$dailyChartData = [
    'labels' => [],
    'dates' => [],
    'aiData' => [],
    'bepData' => [],
    'ihData' => [],
    'privateData' => []
];

foreach ($dailyData as $day) {
    $dailyChartData['labels'][] = $day['day_name'] . ' (' . $day['full_date'] . ')'; 
    $dailyChartData['dates'][] = $day['full_date'];
    $dailyChartData['aiData'][] = $day['ai'];
    $dailyChartData['bepData'][] = $day['bep'];
    $dailyChartData['ihData'][] = $day['ih'];
    $dailyChartData['privateData'][] = $day['private'];
}

$monthlyChartData = prepareChartData($monthlyData);
$yearlyChartData = prepareChartData($yearlyData);

// Calculate percentages against targets
$grandTotal = $summaryData['grand_total'] ?? 0;
$grandTotalPercentage = round(($grandTotal / $targetValues['grand_total']) * 100);

// Calculate percentages of each category against grand total
$aiPercentage = $grandTotal > 0 ? round(($summaryData['total_ai'] / $grandTotal) * 100) : 0;
$bepPercentage = $grandTotal > 0 ? round(($summaryData['total_bep'] / $grandTotal) * 100) : 0;
$ihPercentage = $grandTotal > 0 ? round(($summaryData['total_ih'] / $grandTotal) * 100) : 0;
$privatePercentage = $grandTotal > 0 ? round(($summaryData['total_private'] / $grandTotal) * 100) : 0;

// Determine status for grand total
$grandTotalStatus = '';
$grandTotalStatusClass = '';
if ($grandTotalPercentage >= 100) {
    $grandTotalStatus = 'Excellent! Target exceeded';
    $grandTotalStatusClass = 'text-green-600';
} elseif ($grandTotalPercentage >= 95) {
    $grandTotalStatus = 'Target achieved!';
    $grandTotalStatusClass = 'text-green-500';
} elseif ($grandTotalPercentage >= 60) {
    $grandTotalStatus = 'Keep it up!';
    $grandTotalStatusClass = 'text-yellow-600';
} elseif ($grandTotalPercentage >= 20) {
    $grandTotalStatus = 'Nearly there!';
    $grandTotalStatusClass = 'text-orange-500';
} else {
    $grandTotalStatus = 'Getting started';
    $grandTotalStatusClass = 'text-red-500';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <link rel="stylesheet" href="css/calf.css">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .progress-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 10px;
            margin-top: 10px;
            position: relative;
        }
        .progress-bar {
            height: 10px;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .target-marker {
            position: absolute;
            top: -5px;
            width: 2px;
            height: 20px;
            background-color: #000;
        }
        .progress-text {
            margin-top: 5px;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        .status-excellent {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-achieved {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-progress {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-low {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .timeframe-btn.active {
            background-color: #3b82f6;
            color: white;
        }
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .year-filter, .month-filter, .week-filter {
            flex: 1;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .filter-title {
            font-weight: 600;
            color: #4b5563;
        }
        .filter-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr); 
            gap: 8px; 
        }
        .filter-btn {
            padding: 6px 12px;
            background: #f3f4f6;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background: #e5e7eb;
        }
        .filter-btn.active {
            background: #3b82f6;
            color: white;
        }
        .export-btn {
            padding: 6px 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .export-btn:hover {
            background: #059669;
        }

        .chart-container {
            min-height: 400px;
            position: relative;
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
                <!-- Display the uploaded profile image -->
                <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
            <?php else: ?>
                <!-- Fallback to the generated avatar -->
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
                <li><a href="cd_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="calf_drop.php" class="nav-link"><i class="fas fa-plus-circle"></i> Calf Drop</a></li>
                <li><a href="cd_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
</div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <header class="mb-10">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-3xl font-bold ">Calf Drop Dashboard</h1>
                        
                    </div>
                </div>
                </header>
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
                
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Filter Container -->
        <div class="filter-container">
            <div class="year-filter">
                <div class="filter-header">
                    <div class="filter-title">Year</div>
                </div>
                <div class="filter-options" id="yearFilter">
                    <!-- Years will be populated by JavaScript -->
                </div>
            </div>

            <div class="month-filter">
                <div class="filter-header">
                    <div class="filter-title">Month</div>
                    <div class="flex space-x-2">
                        <button id="selectQuarter1" class="text-xs px-2 py-1 bg-gray-100 rounded">Q1</button>
                        <button id="selectQuarter2" class="text-xs px-2 py-1 bg-gray-100 rounded">Q2</button>
                        <button id="selectQuarter3" class="text-xs px-2 py-1 bg-gray-100 rounded">Q3</button>
                        <button id="selectQuarter4" class="text-xs px-2 py-1 bg-gray-100 rounded">Q4</button>
                        <button id="selectFirstHalf" class="text-xs px-2 py-1 bg-gray-100 rounded">H1</button>
                        <button id="selectSecondHalf" class="text-xs px-2 py-1 bg-gray-100 rounded">H2</button>
                        <button id="clearMonths" class="text-xs px-2 py-1 bg-gray-100 rounded">Clear</button>
                    </div>
                </div>
                <div class="filter-options" id="monthFilter">
                    <button class="filter-btn" data-month="1">Jan</button>
                    <button class="filter-btn" data-month="2">Feb</button>
                    <button class="filter-btn" data-month="3">Mar</button>
                    <button class="filter-btn" data-month="4">Apr</button>
                    <button class="filter-btn" data-month="5">May</button>
                    <button class="filter-btn" data-month="6">Jun</button>
                    <button class="filter-btn" data-month="7">Jul</button>
                    <button class="filter-btn" data-month="8">Aug</button>
                    <button class="filter-btn" data-month="9">Sep</button>
                    <button class="filter-btn" data-month="10">Oct</button>
                    <button class="filter-btn" data-month="11">Nov</button>
                    <button class="filter-btn" data-month="12">Dec</button>
                </div>
            </div>
            
            <div class="week-filter" <?= !$selectedMonths ? 'style="display: none;"' : '' ?>>
                <div class="filter-title">Week</div>
                <div class="filter-options" id="weekFilter">
                    <?php if ($selectedMonths): ?>
                        <?php foreach ($availableWeeks as $week): ?>
                            <button class="filter-btn <?= $week == $selectedWeek ? 'active' : '' ?>" data-week="<?= $week ?>">Week <?= $week ?></button>
                        <?php endforeach; ?>
                        <button class="filter-btn <?= !$selectedWeek ? 'active' : '' ?>" data-week="all">All Weeks</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="export-btn-container">
                <button id="exportToExcel" class="export-btn">Export</button>
            </div>
        </div>
        
        <!-- Dashboard Content -->
        <div class="container mx-auto px-4 py-8">
            <!-- Summary Cards (showing cumulative totals) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Total AI</p>
                            <h3 class="text-2xl font-bold text-indigo-600"><?= number_format($summaryData['total_ai'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded-full">
                            <i class="fas fa-cow text-indigo-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-indigo-600 rounded-full" style="width: <?= $aiPercentage ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2"><?= $aiPercentage ?>% of total</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Total BEP</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= number_format($summaryData['total_bep'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-blue-600 rounded-full" style="width: <?= $bepPercentage ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2"><?= $bepPercentage ?>% of total</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Total IH</p>
                            <h3 class="text-2xl font-bold text-green-600"><?= number_format($summaryData['total_ih'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-home text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-green-600 rounded-full" style="width: <?= $ihPercentage ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2"><?= $ihPercentage ?>% of total</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Total Private</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= number_format($summaryData['total_private'] ?? 0) ?></h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-lock text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="h-2 bg-gray-200 rounded-full">
                            <div class="h-2 bg-purple-600 rounded-full" style="width: <?= $privatePercentage ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-500 mt-2"><?= $privatePercentage ?>% of total</p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium" >Grand Total</p>
                            <h3 class="text-2xl font-bold text-red-600" style="color: <?= $grandTotalPercentage >= 100 ? '#10b981' : ($grandTotalPercentage >= 80 ? '#3b82f6' : 
                            ($grandTotalPercentage >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                            <?= number_format($summaryData['grand_total'] ?? 0) ?></h3>
                            <p class="text-sm mt-1">       
                                <span class="status-indicator <?= 
                                    $grandTotalPercentage >= 100 ? 'status-excellent' : 
                                    ($grandTotalPercentage >= 95 ? 'status-achieved' : 
                                    ($grandTotalPercentage >= 60 ? 'status-progress' : 'status-low')) ?>">
                                    <?= $grandTotalStatus ?>
                                    <i class="fas <?= 
                                        $grandTotalPercentage >= 100 ? 'fa-check-circle' : 
                                        ($grandTotalPercentage >= 60 ? 'fa-arrow-up' : 'fa-arrow-down') ?> ml-1"></i>
                                </span>
                            </p>
                        </div>
                        <div class="bg-red-100 p-3 rounded-full">
                            <i class="fas fa-chart-pie text-red-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= min($grandTotalPercentage, 100) ?>%; 
                                background-color: <?= 
                                    $grandTotalPercentage >= 100 ? '#10b981' : 
                                    ($grandTotalPercentage >= 80 ? '#3b82f6' : 
                                    ($grandTotalPercentage >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                            </div>
                            <div class="target-marker" style="left: 100%"></div>
                        </div>
                        <div class="progress-text">
                            <?= $grandTotalPercentage ?>% of target (<?= number_format($summaryData['grand_total'] ?? 0) ?>/<?= number_format($targetValues['grand_total']) ?>)
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Bar Chart (showing filtered data) -->
                <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Category Distribution</h2>
                        <div class="flex space-x-2">
                            <?php if ($selectedWeek): ?>
                            <button id="dailyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="daily">Daily</button>
                            <?php endif; ?>
                            <button id="monthlyBtn" class="px-3 py-1 text-sm timeframe-btn active" data-timeframe="monthly">Monthly</button>
                            <button id="yearlyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-container h-80">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <!-- Line Chart (showing filtered data) -->
                <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Trend Over Time</h2>    
                    </div>
                    <div class="h-80">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
            <!-- Pie Chart (showing filtered data) -->
            <div class="bg-white p-6 rounded-xl shadow-md fade-in mb-10">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Category Breakdown</h2>
                </div>
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2 h-96">
                        <canvas id="pieChart"></canvas>
                    </div>
                    <div class="flex flex-col justify-center">
                        <?php foreach (['ai', 'bep', 'ih', 'private'] as $category): 
                            $current = $filteredData['total_'.$category] ?? 0;
                            $percentage = $grandTotal > 0 ? round(($current / $grandTotal) * 100) : 0;
                            $color = [
                                'ai' => ['bg' => 'indigo-600', 'text' => 'indigo'],
                                'bep' => ['bg' => 'blue-600', 'text' => 'blue'],
                                'ih' => ['bg' => 'green-600', 'text' => 'green'],
                                'private' => ['bg' => 'purple-600', 'text' => 'purple']
                            ][$category];
                        ?>
                        <div class="mb-6">
                            <div class="flex items-center mb-2">
                                <div class="w-4 h-4 bg-<?= $color['bg'] ?> rounded-full mr-2"></div>
                                <span class="text-sm font-medium uppercase"><?= $category ?></span>
                                <span class="ml-auto text-sm font-semibold">
                                    <?= number_format($current) ?>
                                </span>
                            </div>
                            <div class="progress-container">
                                <div class="progress-bar bg-<?= $color['bg'] ?>" style="width: <?= $percentage ?>%"></div>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <span class="text-xs text-gray-500">
                                    <i class="fas <?= $statusIcon ?> mr-1"></i>
                                    <span class="status-indicator <?= $statusClass ?>">
                                        <?= $percentage ?>%
                                    </span>
                                </span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Update the chartData in the script section
            const chartData = {
                monthly: {
                    labels: <?= json_encode($monthlyChartData['labels']) ?>,
                    aiData: <?= json_encode($monthlyChartData['aiData']) ?>,
                    bepData: <?= json_encode($monthlyChartData['bepData']) ?>,
                    ihData: <?= json_encode($monthlyChartData['ihData']) ?>,
                    privateData: <?= json_encode($monthlyChartData['privateData']) ?>
                },
                yearly: {
                    labels: <?= json_encode($yearlyChartData['labels']) ?>,
                    aiData: <?= json_encode($yearlyChartData['aiData']) ?>,
                    bepData: <?= json_encode($yearlyChartData['bepData']) ?>,
                    ihData: <?= json_encode($yearlyChartData['ihData']) ?>,
                    privateData: <?= json_encode($yearlyChartData['privateData']) ?>
                },
                daily: {
                    labels: <?= json_encode($dailyChartData['labels']) ?>,
                    dates: <?= json_encode($dailyChartData['dates']) ?>,
                    aiData: <?= json_encode($dailyChartData['aiData']) ?>,
                    bepData: <?= json_encode($dailyChartData['bepData']) ?>,
                    ihData: <?= json_encode($dailyChartData['ihData']) ?>,
                    privateData: <?= json_encode($dailyChartData['privateData']) ?>
                }
            };

            // Initialize charts with monthly data by default
            const barCtx = document.getElementById('barChart').getContext('2d');
            let barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: chartData.monthly.labels,
                    datasets: [{
                        label: 'AI',
                        data: chartData.monthly.aiData,
                        backgroundColor: 'rgba(79, 70, 229, 0.7)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1
                    }, {
                        label: 'BEP',
                        data: chartData.monthly.bepData,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                        borderColor: 'rgba(59, 130, 246, 1)',
                        borderWidth: 1
                    }, {
                        label: 'IH',
                        data: chartData.monthly.ihData,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Private',
                        data: chartData.monthly.privateData,
                        backgroundColor: 'rgba(124, 58, 237, 0.7)',
                        borderColor: 'rgba(124, 58, 237, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Line Chart
            const lineCtx = document.getElementById('lineChart').getContext('2d');
            const lineChart = new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: chartData.monthly.labels,
                    datasets: [
                        {
                            label: 'AI',
                            data: chartData.monthly.aiData,
                            borderColor: 'rgba(79, 70, 229, 1)',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'BEP',
                            data: chartData.monthly.bepData,
                            borderColor: 'rgba(59, 130, 246, 1)',
                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'IH',
                            data: chartData.monthly.ihData,
                            borderColor: 'rgba(16, 185, 129, 1)',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            tension: 0.3,
                            fill: true
                        },
                        {
                            label: 'Private',
                            data: chartData.monthly.privateData,
                            borderColor: 'rgba(124, 58, 237, 1)',
                            backgroundColor: 'rgba(124, 58, 237, 0.1)',
                            tension: 0.3,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return `${context.dataset.label}: ${context.raw.toLocaleString()}`;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Pie Chart (showing filtered data)
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const pieChart = new Chart(pieCtx, {
                type: 'doughnut',
                data: {
                    labels: ['AI', 'BEP', 'IH', 'Private'],
                    datasets: [{
                        data: [
                            <?= $filteredData['total_ai'] ?? 0 ?>,
                            <?= $filteredData['total_bep'] ?? 0 ?>,
                            <?= $filteredData['total_ih'] ?? 0 ?>,
                            <?= $filteredData['total_private'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            'rgba(79, 70, 229, 0.7)',
                            'rgba(59, 130, 246, 0.7)',
                            'rgba(16, 185, 129, 0.7)',
                            'rgba(124, 58, 237, 0.7)'
                        ],
                        borderColor: [
                            'rgba(79, 70, 229, 1)',
                            'rgba(59, 130, 246, 1)',
                            'rgba(16, 185, 129, 1)',
                            'rgba(124, 58, 237, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const percentage = Math.round((value / <?= $grandTotal ?>) * 100);
                                    return [
                                        `${label}: ${value.toLocaleString()}`,
                                        `Percentage: ${percentage}%`
                                    ];
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });

            // Filter functionality
            $(document).ready(function() {
                // Populate year filter
                const availableYears = <?= json_encode($availableYears) ?>;
                const yearFilter = $('#yearFilter');
                
                availableYears.forEach(year => {
                    const activeClass = year == <?= $selectedYear ?> ? 'active' : '';
                    yearFilter.append(`<button class="filter-btn ${activeClass}" data-year="${year}">${year}</button>`);
                });
                
                // Set current months as active if selected
                <?php if ($selectedMonths): ?>
                    <?php foreach ($selectedMonths as $month): ?>
                        $(`.month-filter .filter-btn[data-month="<?= $month ?>"]`).addClass('active');
                    <?php endforeach; ?>
                <?php endif; ?>
                
                // Month filter click handler - now toggles selection
                $(document).on('click', '.month-filter .filter-btn', function() {
                    $(this).toggleClass('active');
                    updateMonthSelection();
                });
                
                // Quarter selection buttons
                $('#selectQuarter1').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="1"], .month-filter .filter-btn[data-month="2"], .month-filter .filter-btn[data-month="3"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#selectQuarter2').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="4"], .month-filter .filter-btn[data-month="5"], .month-filter .filter-btn[data-month="6"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#selectQuarter3').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="7"], .month-filter .filter-btn[data-month="8"], .month-filter .filter-btn[data-month="9"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#selectQuarter4').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="10"], .month-filter .filter-btn[data-month="11"], .month-filter .filter-btn[data-month="12"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#selectFirstHalf').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="1"], .month-filter .filter-btn[data-month="2"], .month-filter .filter-btn[data-month="3"], .month-filter .filter-btn[data-month="4"], .month-filter .filter-btn[data-month="5"], .month-filter .filter-btn[data-month="6"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#selectSecondHalf').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.month-filter .filter-btn[data-month="7"], .month-filter .filter-btn[data-month="8"], .month-filter .filter-btn[data-month="9"], .month-filter .filter-btn[data-month="10"], .month-filter .filter-btn[data-month="11"], .month-filter .filter-btn[data-month="12"]').addClass('active');
                    updateMonthSelection();
                });
                
                $('#clearMonths').click(function() {
                    $('.month-filter .filter-btn').removeClass('active');
                    updateMonthSelection();
                });
                
                // Function to update month selection and reload data
                function updateMonthSelection() {
                    const selectedMonths = [];
                    $('.month-filter .filter-btn.active').each(function() {
                        selectedMonths.push($(this).data('month'));
                    });
                    
                    // Get selected year
                    const year = $('.year-filter .filter-btn.active').data('year');
                    
                    // Show/hide week filter based on selection
                    if (selectedMonths.length > 0) {
                        $('.week-filter').show();
                        
                        // Show loading state
                        const weekFilter = $('#weekFilter');
                        weekFilter.html('<button class="filter-btn" disabled>Loading weeks...</button>');
                        
                        // Fetch weeks for selected months
                        $.get(window.location.pathname, { 
                            ajax: true,
                            year: year, 
                            months: selectedMonths.join(','),
                            center: '<?= $centerCode ?>'
                        }, function(weeks) {
                            weekFilter.empty();
                            if (weeks.length > 0) {
                                weeks.forEach(week => {
                                    weekFilter.append(`<button class="filter-btn" data-week="${week}">Week ${week}</button>`);
                                });
                                weekFilter.prepend('<button class="filter-btn active" data-week="all">All Weeks</button>');
                            } else {
                                weekFilter.append('<button class="filter-btn" disabled>No data</button>');
                            }
                        }, 'json');
                    } else {
                        $('.week-filter').hide();
                        $('#weekFilter').empty();
                    }
                    
                    // Update URL with new filter
                    updateFilters({ 
                        year: year, 
                        months: selectedMonths.length > 0 ? selectedMonths : null, 
                        week: null 
                    });
                }
                
                // Week filter click handler
                $(document).on('click', '.week-filter .filter-btn', function() {
                    const week = $(this).data('week');
                    $('.week-filter .filter-btn').removeClass('active');
                    $(this).addClass('active');
                    
                    // Get selected year and months
                    const year = $('.year-filter .filter-btn.active').data('year');
                    const selectedMonths = [];
                    $('.month-filter .filter-btn.active').each(function() {
                        selectedMonths.push($(this).data('month'));
                    });
                    
                    // Update URL with new filter
                    updateFilters({ 
                        year: year, 
                        months: selectedMonths.length > 0 ? selectedMonths : null, 
                        week: week === 'all' ? null : week 
                    });
                });
                
                // Year filter click handler
                $(document).on('click', '.year-filter .filter-btn', function() {
                    const year = $(this).data('year');
                    $('.year-filter .filter-btn').removeClass('active');
                    $(this).addClass('active');
                    
                    // Reset month and week filters when year changes
                    $('.month-filter .filter-btn').removeClass('active');
                    $('.week-filter').hide();
                    $('#weekFilter').empty();
                    
                    // Update URL with new filter
                    updateFilters({ year: year, months: null, week: null });
                });
                
                // Timeframe button functionality
                $('.timeframe-btn').click(function() {
                    const timeframe = $(this).data('timeframe');
                    
                    // Update button styles
                    $('.timeframe-btn').removeClass('active bg-indigo-100 text-indigo-700');
                    $('.timeframe-btn').addClass('bg-gray-100 text-gray-700');
                    $(this).removeClass('bg-gray-100 text-gray-700');
                    $(this).addClass('active bg-indigo-100 text-indigo-700');
                    
                    // Only show daily button if a week is selected
                    if (timeframe === 'daily' && !$('#dailyBtn').length && <?= $selectedWeek ? 'true' : 'false' ?>) {
                        $('.timeframe-btn').last().after('<button id="dailyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="daily">Daily</button>');
                    }
                    
                    // Update chart data
                    barChart.data.labels = chartData[timeframe].labels;
                    barChart.data.datasets[0].data = chartData[timeframe].aiData;
                    barChart.data.datasets[1].data = chartData[timeframe].bepData;
                    barChart.data.datasets[2].data = chartData[timeframe].ihData;
                    barChart.data.datasets[3].data = chartData[timeframe].privateData;
                    barChart.update();
                });
                
                // Function to update filters and reload data
                function updateFilters(params) {
                    const currentParams = new URLSearchParams(window.location.search);
                    
                    // Update params
                    if (params.year !== undefined) currentParams.set('year', params.year);
                    
                    if (params.months !== undefined) {
                        if (params.months === null) {
                            currentParams.delete('months');
                        } else {
                            currentParams.set('months', params.months.join(','));
                        }
                    }
                    
                    if (params.week !== undefined) {
                        if (params.week === null) {
                            currentParams.delete('week');
                        } else {
                            currentParams.set('week', params.week);
                        }
                    }
                    
                    // Reload page with new filters
                    window.location.search = currentParams.toString();
                }
                
                // Export to Excel functionality
                $('#exportToExcel').click(function() {
                    // Prepare data for export
                    const data = [
                        ['Category', 'Count', 'Percentage of Total'],
                        ['AI', <?= $filteredData['total_ai'] ?? 0 ?>, <?= $aiPercentage ?> + '%'],
                        ['BEP', <?= $filteredData['total_bep'] ?? 0 ?>, <?= $bepPercentage ?> + '%'],
                        ['IH', <?= $filteredData['total_ih'] ?? 0 ?>, <?= $ihPercentage ?> + '%'],
                        ['Private', <?= $filteredData['total_private'] ?? 0 ?>, <?= $privatePercentage ?> + '%'],
                        ['Grand Total', <?= $grandTotal ?>, <?= $grandTotalPercentage ?> + '%']
                    ];
                    
                    // Create worksheet
                    const ws = XLSX.utils.aoa_to_sheet(data);
                    
                    // Create workbook
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "CalfDropData");
                    
                    // Generate file name
                    let fileName = 'CalfDrop_';
                    let centerCode = '<?= $centerCode ?>';

                    if (<?= $selectedYear ?>) fileName += <?= $selectedYear ?> + '_';
                    if (<?= $selectedMonth ?? 'null' ?>) fileName += <?= $selectedMonth ?? 'null' ?> + '_';
                    if (<?= $selectedWeek ?? 'null' ?>) fileName += <?= $selectedWeek ?? 'null' ?>;

                    fileName += '_' + centerCode ;  

                    fileName += '.xlsx';
                    
                    // Export to Excel
                    XLSX.writeFile(wb, fileName);
                });
            });

            // Add fade-in animation to elements when scrolling
            document.addEventListener('DOMContentLoaded', () => {
                const fadeElements = document.querySelectorAll('.fade-in');
                
                const fadeInOnScroll = () => {
                    fadeElements.forEach(element => {
                        const elementTop = element.getBoundingClientRect().top;
                        const windowHeight = window.innerHeight;
                        
                        if (elementTop < windowHeight - 100) {
                            element.style.opacity = '1';
                            element.style.transform = 'translateY(0)';
                        }
                    });
                };
                
                // Initial check
                fadeInOnScroll();
                
                // Check on scroll
                window.addEventListener('scroll', fadeInOnScroll);
            });
        </script>
    </div>
</body>
</html>