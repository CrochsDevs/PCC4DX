<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

// Define target value for AI services
$targetValue = 31000; // Example target value for AI services

class AIDashboardManager {
    private $db;
    private $centerCode;
    
    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }
    
    // Get cumulative totals (all-time)
    public function getSummaryData() {
        $query = "SELECT 
                    SUM(aiServices) as total_ai
                  FROM ai_services 
                  WHERE center = :center";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get yearly summary data
    public function getYearlySummaryData($year = null) {
        $query = "SELECT 
                    SUM(aiServices) as total_ai
                  FROM ai_services 
                  WHERE center = :center";
        
        $params = [':center' => $this->centerCode];
        
        if ($year !== null) {
            $query .= " AND YEAR(date) = :year";
            $params[':year'] = $year;
        }
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Get filtered data for charts
    public function getFilteredData($year = null, $months = null, $week = null) {
        $query = "SELECT 
                    SUM(aiServices) as total_ai
                  FROM ai_services 
                  WHERE center = :center";
        
        $params = [':center' => $this->centerCode];
        
        if ($year !== null) {
            $query .= " AND YEAR(date) = :year";
            $params[':year'] = $year;
        }
        
        if ($months !== null && !empty($months)) {
            if (is_array($months)) {
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i";
                    $params[":month$i"] = $month;
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
    
    public function getMonthlyData($year = null, $months = null) {
        $query = "SELECT 
                    YEAR(date) as year,
                    MONTH(date) as month,
                    SUM(aiServices) as ai
                  FROM ai_services 
                  WHERE center = :center";
        
        $params = [':center' => $this->centerCode];
        
        if ($year !== null) {
            $query .= " AND YEAR(date) = :year";
            $params[':year'] = $year;
        }
        
        if ($months !== null && !empty($months)) {
            if (is_array($months)) {
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i";
                    $params[":month$i"] = $month;
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
                    SUM(aiServices) as ai
                  FROM ai_services 
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
                  FROM ai_services 
                  WHERE center = :center
                  ORDER BY year DESC";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
    
    public function getWeeksInMonth($year, $months) {
        if (empty($months)) return [];
        
        $query = "SELECT DISTINCT WEEK(date, 1) as week 
                  FROM ai_services 
                  WHERE center = :center 
                  AND YEAR(date) = :year";
        
        $params = [
            ':center' => $this->centerCode,
            ':year' => $year
        ];
        
        if (is_array($months)) {
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
        $query = "SELECT 
                    DAYNAME(date) as day_name,
                    DATE_FORMAT(date, '%Y-%m-%d') as full_date,
                    DAYOFWEEK(date) as day_number,
                    SUM(aiServices) as ai
                  FROM ai_services 
                  WHERE center = :center
                  AND YEAR(date) = :year
                  AND WEEK(date, 1) = :week";
        
        $params = [
            ':center' => $this->centerCode,
            ':year' => $year,
            ':week' => $week
        ];
        
        if (!empty($months)) {
            if (is_array($months)) {
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i";
                    $params[":month$i"] = $month;
                }
                $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
            } else {
                $query .= " AND MONTH(date) = :month";
                $params[':month'] = $months;
            }
        }
        
        $query .= " GROUP BY date, DAYNAME(date), DAYOFWEEK(date)
                    ORDER BY date";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWeeklyData($year, $months) {
        $query = "SELECT 
                    WEEK(date, 1) as week,
                    SUM(aiServices) as ai
                  FROM ai_services 
                  WHERE center = :center
                  AND YEAR(date) = :year";
        
        $params = [
            ':center' => $this->centerCode,
            ':year' => $year
        ];
        
        if (!empty($months)) {
            if (is_array($months)) {
                $placeholders = [];
                foreach ($months as $i => $month) {
                    $placeholders[] = ":month$i";
                    $params[":month$i"] = $month;
                }
                $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
            } else {
                $query .= " AND MONTH(date) = :month";
                $params[':month'] = $months;
            }
        }
        
        $query .= " GROUP BY WEEK(date, 1)
                    ORDER BY week";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReportRatingData($year = null) {

        $startDate = $year ? "{$year}-01-01" : "{$year}-01-01";
    
        $query = "
            SELECT 
                unique_dates,
                workdays,
                ROUND((unique_dates / workdays) * 100, 2) AS percentage
            FROM (
                SELECT 
                    (SELECT COUNT(DISTINCT date) 
                     FROM ai_services 
                     WHERE center = :center AND date >= :startDate AND date <= CURDATE()) AS unique_dates,
    
                    (SELECT COUNT(*) 
                     FROM (
                        SELECT DATE_ADD(:startDate, INTERVAL n DAY) AS workday
                        FROM (SELECT @rownum := @rownum + 1 AS n 
                              FROM information_schema.columns, (SELECT @rownum := 0) r 
                              LIMIT 365) days
                        WHERE DAYOFWEEK(DATE_ADD(:startDate, INTERVAL n DAY)) NOT IN (1, 7)  -- Exclude Sundays and Saturdays
                        AND DATE_ADD(:startDate, INTERVAL n DAY) <= CURDATE()  -- Ensure date is within current date
                     ) workdays) AS workdays
            ) AS result
        ";
    
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':center' => $this->centerCode,
            ':startDate' => $startDate
        ]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function calculateGrading($targetValue, $actualAchieved, $reportPercentage, ) {
        // Calculate Accomplished Rating
        $accomplishedRating = ($actualAchieved / $targetValue) * 100;

        // Calculate Final Score
        $finalScore = ($reportPercentage) + ($accomplishedRating) / 2;

        // Determine Grade
        $grade = '';
        if ($finalScore >= 90) {
            $grade = 'A (Excellent)';
        } elseif ($finalScore >= 80) {
            $grade = 'B (Good)';
        } elseif ($finalScore >= 70) {
            $grade = 'C (Satisfactory)';
        } elseif ($finalScore >= 60) {
            $grade = 'D (Needs Improvement)';
        } else {
            $grade = 'F (Poor)';
        }
        
        return [
            'accomplished_rating' => round($accomplishedRating, 2),
            'final_score' => round($finalScore, 2),
            'grade' => $grade,
        ];
    }
}

// Handle AJAX request for chart data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    $year = $_GET['year'] ?? null;
    $months = isset($_GET['months']) ? explode(',', $_GET['months']) : null;
    $week = $_GET['week'] ?? null;
    $centerCode = $_SESSION['center_code'];
    
    $dashboardManager = new AIDashboardManager($conn, $centerCode);
    
    $response = [
        'monthlyData' => $dashboardManager->getMonthlyData($year, $months),
        'yearlyData' => $dashboardManager->getYearlyData(),
    ];
    
    if ($months) {
        $response['weeklyData'] = $dashboardManager->getWeeklyData($year, $months);
        
        if ($week) {
            $response['dailyData'] = $dashboardManager->getDailyData($year, $months, $week);
        }
    }
    
    echo json_encode($response);
    exit;
}

$centerCode = $_SESSION['center_code'];
$dashboardManager = new AIDashboardManager($conn, $centerCode);

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

// Get summary data based on selected year
$summaryData = $dashboardManager->getYearlySummaryData($selectedYear);
$allTimeSummaryData = $dashboardManager->getSummaryData();

// Get filtered data for charts
$filteredData = $dashboardManager->getFilteredData($selectedYear, $selectedMonths, $selectedWeek);
$monthlyData = $dashboardManager->getMonthlyData($selectedYear, $selectedMonths);
$yearlyData = $dashboardManager->getYearlyData();

// Get report rating data for the selected year
$reportRatingData = $dashboardManager->getReportRatingData($selectedYear);

// Extract the unique_date, workdays, and report percentage
$uniqueDates = $reportRatingData['unique_dates'] ?? 0;
$workdays = $reportRatingData['workdays'] ?? 0;
$reportPercentage = $reportRatingData['percentage'] ?? 0;

// Calculate percentage against target
$totalAI = $summaryData['total_ai'] ?? 0;
$aiPercentage = round(($totalAI / $targetValue) * 100);

// Calculate grading
$gradingData = $dashboardManager->calculateGrading(
    $targetValue,
    $totalAI,
    $reportPercentage,
    1,  
    1   
);

// Determine status for AI services
$aiStatus = '';
$aiStatusClass = '';
if ($aiPercentage >= 100) {
    $aiStatus = 'Excellent! Target exceeded';
    $aiStatusClass = 'text-green-600';
} elseif ($aiPercentage >= 95) {
    $aiStatus = 'Target achieved!';
    $aiStatusClass = 'text-green-500';
} elseif ($aiPercentage >= 60) {
    $aiStatus = 'Keep it up!';
    $aiStatusClass = 'text-yellow-600';
} elseif ($aiPercentage >= 20) {
    $aiStatus = 'Nearly there!';
    $aiStatusClass = 'text-orange-500';
} else {
    $aiStatus = 'Getting started';
    $aiStatusClass = 'text-red-500';
}

// Prepare data for charts
function prepareChartData($data) {
    $labels = [];
    $aiData = [];

    foreach (array_reverse($data) as $item) {
        if (isset($item['month'])) {
            $labels[] = date('M', mktime(0, 0, 0, $item['month'], 1)) . ' ' . $item['year'];
        } else {
            $labels[] = $item['year'];
        }
        $aiData[] = $item['ai'];
    }

    return [
        'labels' => $labels,
        'aiData' => $aiData
    ];
}

$monthlyChartData = prepareChartData($monthlyData);
$yearlyChartData = prepareChartData($yearlyData);

$dailyData = [];
if ($selectedWeek && $selectedMonths) {
    $dailyData = $dashboardManager->getDailyData($selectedYear, $selectedMonths, $selectedWeek);
}

// Prepare daily chart data
$dailyChartData = [
    'labels' => [],
    'dates' => [],
    'aiData' => []
];

foreach ($dailyData as $day) {
    $dailyChartData['labels'][] = $day['day_name'] . ' (' . date('M j', strtotime($day['full_date'])) . ')'; 
    $dailyChartData['dates'][] = $day['full_date'];
    $dailyChartData['aiData'][] = $day['ai'];
}

// Get weekly data for the selected months
$weeklyData = [];
if ($selectedMonths) {
    $weeklyData = $dashboardManager->getWeeklyData($selectedYear, $selectedMonths);
}

// Prepare weekly chart data
$weeklyChartData = [
    'labels' => [],
    'aiData' => []
];

foreach ($weeklyData as $week) {
    $weeklyChartData['labels'][] = 'Week ' . $week['week'];
    $weeklyChartData['aiData'][] = $week['ai'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> AI Dashboard</title>
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
                <li><a href="ai_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="ai.php" class="nav-link"><i class="fas fa-syringe"></i> AI Services</a></li>
                <li><a href="ai_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
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
                        <h1 class="text-3xl font-bold ">Artificial Insemination Dashboard</h1>
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
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mb-10">
                <!-- AI Services Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Total AI Services</p>
                            <h3 class="text-2xl font-bold text-indigo-600"><?= number_format($summaryData['total_ai'] ?? 0) ?></h3>
                            <p class="text-sm mt-1">       
                                <span class="status-indicator <?= 
                                    $aiPercentage >= 100 ? 'status-excellent' : 
                                    ($aiPercentage >= 95 ? 'status-achieved' : 
                                    ($aiPercentage >= 60 ? 'status-progress' : 'status-low')) ?>">
                                    <?= $aiStatus ?>
                                    <i class="fas <?= 
                                        $aiPercentage >= 100 ? 'fa-check-circle' : 
                                        ($aiPercentage >= 60 ? 'fa-arrow-up' : 'fa-arrow-down') ?> ml-1"></i>
                                </span>
                            </p>
                        </div>
                        <div class="bg-indigo-100 p-3 rounded-full">
                            <i class="fas fa-syringe text-indigo-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= min($aiPercentage, 100) ?>%; 
                                background-color: <?= 
                                    $aiPercentage >= 100 ? '#10b981' : 
                                    ($aiPercentage >= 80 ? '#3b82f6' : 
                                    ($aiPercentage >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                            </div>
                            <div class="target-marker" style="left: 100%"></div>
                        </div>
                        <div class="progress-text">
                            <?= $aiPercentage ?>% of target (<?= number_format($summaryData['total_ai'] ?? 0) ?>/<?= number_format($targetValue) ?>)
                        </div>
                    </div>
                </div>

                <!-- Grading Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Performance Grade</p>
                            <h3 class="text-2xl font-bold text-purple-600"><?= $gradingData['grade'] ?></h3>
                            <p class="text-sm mt-1">
                                <span class="status-indicator <?= 
                                    $gradingData['final_score'] >= 90 ? 'status-excellent' : 
                                    ($gradingData['final_score'] >= 80 ? 'status-achieved' : 
                                    ($gradingData['final_score'] >= 70 ? 'status-progress' : 'status-low')) ?>">
                                    Score: <?= $gradingData['final_score'] ?>%
                                </span>
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-star text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $gradingData['final_score'] ?>%; 
                                background-color: <?= 
                                    $gradingData['final_score'] >= 90 ? '#10b981' : 
                                    ($gradingData['final_score'] >= 80 ? '#3b82f6' : 
                                    ($gradingData['final_score'] >= 70 ? '#f59e0b' : '#ef4444')) ?>;">
                            </div>
                        </div>
                        <div class="progress-text text-xs mt-2">
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <span class="font-medium">Report Rating:</span> <?= $reportPercentage ?>%
                                </div>
                                <div>
                                    <span class="font-medium">Achievement:</span> <?= $gradingData['accomplished_rating'] ?>%
                                </div>
                            </div>
                        </div>
                    </div>
                </div>                

                <!-- Report Rating Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-gray-500 font-medium">Report Rating</p>
                            <h3 class="text-2xl font-bold text-blue-600"><?= number_format($reportPercentage) ?>%</h3>
                            <p class="text-sm mt-1">
                                <span class="status-indicator <?= 
                                    $reportPercentage >= 90 ? 'status-excellent' : 
                                    ($reportPercentage >= 75 ? 'status-achieved' : 
                                    ($reportPercentage >= 50 ? 'status-progress' : 'status-low')) ?>">
                                    <?= 
                                        $reportPercentage >= 90 ? 'Excellent' : 
                                        ($reportPercentage >= 75 ? 'Good' : 
                                        ($reportPercentage >= 50 ? 'Average' : 'Needs Improvement')) ?>
                                </span>
                            </p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-chart-line text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $reportPercentage ?>%; 
                                background-color: <?= 
                                    $reportPercentage >= 90 ? '#10b981' : 
                                    ($reportPercentage >= 75 ? '#3b82f6' : 
                                    ($reportPercentage >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                            </div>
                        </div>
                        <div class="progress-text">
                            <?= number_format($uniqueDates) ?> reports submitted out of <?= number_format($workdays) ?> workdays
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                <!-- Bar Chart -->
                <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">AI Services Distribution</h2>
                        <div class="flex space-x-2" id="timeframeButtons">
                            <?php if ($selectedWeek): ?>
                            <button id="dailyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="daily">Daily</button>
                            <?php endif; ?>
                            <?php if ($selectedMonths): ?>
                            <button id="weeklyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="weekly">Weekly</button>
                            <?php endif; ?>
                            <button id="monthlyBtn" class="px-3 py-1 text-sm timeframe-btn active" data-timeframe="monthly">Monthly</button>
                            <button id="yearlyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="yearly">Yearly</button>
                        </div>
                    </div>
                    <div class="chart-container h-80">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <!-- Line Chart -->
                <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold text-gray-800">Trend Over Time</h2>    
                    </div>
                    <div class="h-80">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Chart data
            const chartData = {
                monthly: {
                    labels: <?= json_encode($monthlyChartData['labels']) ?>,
                    aiData: <?= json_encode($monthlyChartData['aiData']) ?>
                },
                yearly: {
                    labels: <?= json_encode($yearlyChartData['labels']) ?>,
                    aiData: <?= json_encode($yearlyChartData['aiData']) ?>
                },
                weekly: {
                    labels: <?= json_encode($weeklyChartData['labels']) ?>,
                    aiData: <?= json_encode($weeklyChartData['aiData']) ?>
                },
                daily: {
                    labels: <?= json_encode($dailyChartData['labels']) ?>,
                    dates: <?= json_encode($dailyChartData['dates']) ?>,
                    aiData: <?= json_encode($dailyChartData['aiData']) ?>
                }
            };

            // Initialize charts with monthly data by default
            const barCtx = document.getElementById('barChart').getContext('2d');
            let barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: chartData.monthly.labels,
                    datasets: [{
                        label: 'AI Services',
                        data: chartData.monthly.aiData,
                        backgroundColor: 'rgba(79, 70, 229, 0.7)',
                        borderColor: 'rgba(79, 70, 229, 1)',
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
                            label: 'AI Services',
                            data: chartData.monthly.aiData,
                            borderColor: 'rgba(79, 70, 229, 1)',
                            backgroundColor: 'rgba(79, 70, 229, 0.1)',
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
                    
                    // Show/hide week filter and weekly button based on selection
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
                                    const activeClass = week == <?= $selectedWeek ?? 'null' ?> ? 'active' : '';
                                    weekFilter.append(`<button class="filter-btn ${activeClass}" data-week="${week}">Week ${week}</button>`);
                                });
                                const allActiveClass = <?= $selectedWeek ? 'false' : 'true' ?> ? 'active' : '';
                                weekFilter.prepend(`<button class="filter-btn ${allActiveClass}" data-week="all">All Weeks</button>`);
                                
                                // Fetch weekly data
                                fetchWeeklyData(year, selectedMonths);
                                
                                // Show weekly button if not already shown
                                if (!$('#weeklyBtn').length) {
                                    $('#monthlyBtn').before('<button id="weeklyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="weekly">Weekly</button>');
                                }
                            } else {
                                weekFilter.append('<button class="filter-btn" disabled>No data</button>');
                                // Hide weekly button if no data
                                $('#weeklyBtn').remove();
                            }
                        }, 'json');
                    } else {
                        $('.week-filter').hide();
                        $('#weekFilter').empty();
                        // Hide weekly button when no months selected
                        $('#weeklyBtn').remove();
                        // Also hide daily button if no months selected
                        $('#dailyBtn').remove();
                    }
                    
                    // Update URL with new filter
                    updateFilters({ 
                        year: year, 
                        months: selectedMonths.length > 0 ? selectedMonths : null, 
                        week: null 
                    });
                }

                function fetchWeeklyData(year, months) {
                    $.get(window.location.pathname, {
                        ajax: true,
                        year: year,
                        months: months.join(','),
                        weekly: true,
                        center: '<?= $centerCode ?>'
                    }, function(data) {
                        // Update chartData.weekly with the fetched data
                        chartData.weekly = {
                            labels: data.map(item => `Week ${item.week}`),
                            aiData: data.map(item => item.ai)
                        };
                        
                        // If currently viewing weekly data, update the chart
                        if ($('#weeklyBtn').hasClass('active')) {
                            updateBarChart('weekly');
                        }
                    }, 'json');
                }

                function fetchDailyData(year, months, week) {
                    $.get(window.location.pathname, {
                        ajax: true,
                        year: year,
                        months: months.join(','),
                        week: week,
                        daily: true,
                        center: '<?= $centerCode ?>'
                    }, function(data) {
                        // Update chartData.daily with the fetched data
                        chartData.daily = {
                            labels: data.map(day => `${day.day_name} (${new Date(day.full_date).toLocaleDateString('en-US', {month: 'short', day: 'numeric'})})`),
                            dates: data.map(day => day.full_date),
                            aiData: data.map(day => day.ai)
                        };
                        
                        // If currently viewing daily data, update the chart
                        if ($('#dailyBtn').hasClass('active')) {
                            updateBarChart('daily');
                        }
                    }, 'json');
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
                    
                    // Show/hide daily button based on week selection
                    const isSpecificWeek = week !== 'all';
                    if (isSpecificWeek) {
                        // Add daily button if not already present
                        if (!$('#dailyBtn').length) {
                            $('#timeframeButtons').prepend('<button id="dailyBtn" class="px-3 py-1 text-sm timeframe-btn bg-gray-100 text-gray-700 rounded-md" data-timeframe="daily">Daily</button>');
                        }
                        
                        // Fetch daily data for the selected week
                        fetchDailyData(year, selectedMonths, week);
                    } else if ($('#dailyBtn').length) {
                        $('#dailyBtn').remove();
                        // If currently viewing daily, switch to weekly view
                        if ($('#dailyBtn').hasClass('active')) {
                            $('#weeklyBtn').click();
                        }
                    }
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
                    
                    // Hide weekly and daily buttons
                    $('#weeklyBtn').remove();
                    $('#dailyBtn').remove();
                    
                    // Reload the page with the new year filter to update the cards
                    updateFilters({ year: year, months: null, week: null });
                });

                // Timeframe button functionality
                $(document).on('click', '.timeframe-btn', function() {
                    const timeframe = $(this).data('timeframe');
                    
                    // Update button styles
                    $('.timeframe-btn').removeClass('active bg-blue-600 text-white');
                    $('.timeframe-btn').addClass('bg-gray-100 text-gray-700');
                    $(this).removeClass('bg-gray-100 text-gray-700');
                    $(this).addClass('active bg-blue-600 text-white');
                    
                    // Update chart data
                    updateBarChart(timeframe);
                });

                function updateBarChart(timeframe) {
                    barChart.data.labels = chartData[timeframe].labels;
                    barChart.data.datasets[0].data = chartData[timeframe].aiData;
                    barChart.update();
                }

                function updateFilters({year, months, week}) {
                    const params = new URLSearchParams();
                    
                    if (year) params.set('year', year);
                    if (months && months.length > 0) params.set('months', months.join(','));
                    if (week) params.set('week', week);
                    
                    // Update URL and reload the page to update the cards when year changes
                    const newUrl = window.location.pathname + '?' + params.toString();
                    window.location.href = newUrl;
                }

                // Export to Excel functionality
                $('#exportToExcel').click(function() {
                    // Prepare data for export
                    const data = [
                        ['Metric', 'Value'],
                        ['AI Services', <?= $totalAI ?>],
                        ['Target Percentage', <?= $aiPercentage ?> + '%'],
                        ['Report Rating', <?= $reportPercentage ?> + '%']
                    ];
                    
                    // Create worksheet
                    const ws = XLSX.utils.aoa_to_sheet(data);
                    
                    // Create workbook
                    const wb = XLSX.utils.book_new();
                    XLSX.utils.book_append_sheet(wb, ws, "AIServicesData");
                    
                    // Generate file name
                    let fileName = 'AI_Services_';
                    let centerCode = '<?= $centerCode ?>';

                    if (<?= $selectedYear ?>) fileName += <?= $selectedYear ?> + '_';
                    if (<?= $selectedMonths ? json_encode(implode('-', $selectedMonths)) : 'null' ?>) fileName += <?= $selectedMonths ? json_encode(implode('-', $selectedMonths)) : 'null' ?> + '_';
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