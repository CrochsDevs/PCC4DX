<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database connection (no password, using root, XAMPP default)
$host = 'localhost';
$db = 'pcc_auth_system';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Check if user is logged in and has correct privileges
require 'auth_check.php';

if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

// Get current year and month for default filter
$currentYear = date('Y');
$currentMonth = date('n');

// Get filter parameters from request
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : $currentYear;
$selectedMonths = isset($_GET['months']) ? array_map('intval', explode(',', $_GET['months'])) : null;
$selectedWeek = isset($_GET['week']) ? (int)$_GET['week'] : null;

// Function to get available years from database
function getAvailableYears($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT YEAR(date) as year FROM ai_services ORDER BY year DESC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Function: Get center targets
function getCenterTargets($pdo, $year) {
    $stmt = $pdo->prepare("SELECT * FROM ai_target WHERE year = ?");
    $stmt->execute([$year]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function: Get AI services data for the selected period
function getAIServicesData($pdo, $year, $months = null, $week = null) {
    $query = "SELECT center, SUM(aiServices) as total_ai 
              FROM ai_services 
              WHERE YEAR(date) = :year";
    
    $params = [':year' => $year];
    
    if ($months && !empty($months)) {
        $placeholders = [];
        foreach ($months as $i => $month) {
            $placeholders[] = ":month$i";
            $params[":month$i"] = $month;
        }
        $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
    }
    
    if ($week) {
        $query .= " AND WEEK(date, 1) = :week";
        $params[':week'] = $week;
    }
    
    $query .= " GROUP BY center";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function: Get weeks in selected months
function getWeeksInMonth($pdo, $year, $months) {
    if (empty($months)) return [];
    
    $query = "SELECT DISTINCT WEEK(date, 1) as week 
              FROM ai_services 
              WHERE YEAR(date) = :year";
    
    $params = [':year' => $year];
    
    $placeholders = [];
    foreach ($months as $i => $month) {
        $placeholders[] = ":month$i";
        $params[":month$i"] = $month;
    }
    $query .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
    
    $query .= " ORDER BY week";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

// Get available years
$availableYears = getAvailableYears($pdo);

// Get available weeks if months are selected
$availableWeeks = $selectedMonths ? getWeeksInMonth($pdo, $selectedYear, $selectedMonths) : [];

// Fetch data
$centerTargets = getCenterTargets($pdo, $selectedYear);
$aiServices = getAIServicesData($pdo, $selectedYear, $selectedMonths, $selectedWeek);

// Process data
$centerData = [];
$totalAIServices = 0;
$totalTarget = 0;
$totalAchieved = 0;

foreach ($centerTargets as $target) {
    // Updated field names
    $centerName = $target['center_code'] ?? 'Unknown';
    $weeklyTarget = $target['target'] ?? 0;
    $totalTarget += $weeklyTarget;

    // Match AI services
    $aiData = array_filter($aiServices, fn($item) => $item['center'] === $centerName);
    $aiData = reset($aiData);
    $aiCount = $aiData ? $aiData['total_ai'] : 0;
    $totalAIServices += $aiCount;

    // Compute accomplishment
    $accomplishment = $weeklyTarget > 0 ? min(($aiCount / $weeklyTarget) * 100, 100) : 0;
    if ($weeklyTarget > 0 && $aiCount >= $weeklyTarget) {
        $totalAchieved += $weeklyTarget;
    }

    // Rating logic
    if ($accomplishment >= 90) {
        $rating = 'A+';
        $ratingClass = 'bg-green-100 text-green-800';
    } elseif ($accomplishment >= 80) {
        $rating = 'A';
        $ratingClass = 'bg-green-100 text-green-800';
    } elseif ($accomplishment >= 70) {
        $rating = 'B';
        $ratingClass = 'bg-blue-100 text-blue-800';
    } elseif ($accomplishment >= 60) {
        $rating = 'C';
        $ratingClass = 'bg-yellow-100 text-yellow-800';
    } else {
        $rating = 'D';
        $ratingClass = 'bg-red-100 text-red-800';
    }

    $centerData[$centerName] = [
        'weekly_target' => $weeklyTarget,
        'total_ai' => $aiCount,
        'accomplishment' => $accomplishment,
        'rating' => $rating,
        'rating_class' => $ratingClass,
        'ai_report_percentage' => 0, // Placeholder
        'balance' => max($weeklyTarget - $aiCount, 0)
    ];
}

// Sort centers by accomplishment (descending)
uasort($centerData, function($a, $b) {
    return $b['accomplishment'] <=> $a['accomplishment'];
});

// Global metrics
$accomplishmentBalance = $totalTarget > 0 ? ($totalAchieved / $totalTarget) * 100 : 0;
$averageAccomplishment = count($centerData) > 0 ? array_sum(array_column($centerData, 'accomplishment')) / count($centerData) : 0;

// Find top performer
$topPerformer = '';
$topPerformance = 0;
foreach ($centerData as $center => $data) {
    if ($data['accomplishment'] > $topPerformance) {
        $topPerformance = $data['accomplishment'];
        $topPerformer = $center;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Score Card Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/calf.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
        .data-table {
            max-height: 500px;
            overflow-y: auto;
        }
        .data-table::-webkit-scrollbar {
            width: 6px;
        }
        .data-table::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .data-table::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .data-table::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        .rating-cell {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            text-align: center;
            line-height: 20px;
            font-size: 10px;
            font-weight: bold;
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
    </style>
</head>

<div class="sidebar">
    <!-- User Profile Section -->
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
        <li><a href="admin.php#quickfacts-section" class="nav-link">
        <i class="fa-solid fa-arrow-left"></i> Back to Admin</a></li>

        <li><a class="nav-link active" data-section="dashboard-section">
            <i class="fas fa-chart-line"></i> Dashboard</a></li>

        <li><a href="admin_centertarget_ai_dashboard.php" class="nav-link" data-section="announcement-section">
            <i class="fas fa-file-alt"></i> Center Target</a></li>
        
        <li><a href="admin_report_ai_dashboard.php" class="nav-link" data-section="quickfacts-section">
            <i class="fas fa-sitemap"></i> Reports</a></li>
    </ul>
</div>

<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-10">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-indigo-800">AI Score Card Dashboard</h1>
                    <p class="text-gray-600">
                        <?= $selectedWeek ? "Week $selectedWeek" : "" ?>
                        <?= $selectedMonths ? " | Month(s): " . implode(', ', $selectedMonths) : "" ?>
                        <?= " | Year: $selectedYear" ?>
                    </p>
                </div>
            </div>
        </header>

        <!-- Filter Container -->
        <div class="filter-container">
            <div class="year-filter">
                <div class="filter-header">
                    <div class="filter-title">Year</div>
                </div>
                <div class="filter-options" id="yearFilter">
                    <?php foreach ($availableYears as $year): ?>
                        <button class="filter-btn <?= $year == $selectedYear ? 'active' : '' ?>" data-year="<?= $year ?>"><?= $year ?></button>
                    <?php endforeach; ?>
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
                    <button class="filter-btn <?= $selectedMonths && in_array(1, $selectedMonths) ? 'active' : '' ?>" data-month="1">Jan</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(2, $selectedMonths) ? 'active' : '' ?>" data-month="2">Feb</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(3, $selectedMonths) ? 'active' : '' ?>" data-month="3">Mar</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(4, $selectedMonths) ? 'active' : '' ?>" data-month="4">Apr</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(5, $selectedMonths) ? 'active' : '' ?>" data-month="5">May</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(6, $selectedMonths) ? 'active' : '' ?>" data-month="6">Jun</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(7, $selectedMonths) ? 'active' : '' ?>" data-month="7">Jul</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(8, $selectedMonths) ? 'active' : '' ?>" data-month="8">Aug</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(9, $selectedMonths) ? 'active' : '' ?>" data-month="9">Sep</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(10, $selectedMonths) ? 'active' : '' ?>" data-month="10">Oct</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(11, $selectedMonths) ? 'active' : '' ?>" data-month="11">Nov</button>
                    <button class="filter-btn <?= $selectedMonths && in_array(12, $selectedMonths) ? 'active' : '' ?>" data-month="12">Dec</button>
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
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
            <!-- Total AI Services -->
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Total AI Services</p>
                        <h3 class="text-2xl font-bold text-blue-600"><?= number_format($totalAIServices) ?></h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-syringe text-blue-600 text-xl"></i>
                    </div>
                </div>
            </div>

            <!-- Accomplishment Balance -->
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Accomplishment Balance</p>
                        <h3 class="text-2xl font-bold text-green-600"><?= number_format($accomplishmentBalance, 2) ?>%</h3>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <i class="fas fa-chart-pie text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Team Target</span>
                        <span>90.0%</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill bg-green-600" style="width: <?= $accomplishmentBalance ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Average Accomplishment -->
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Avg. Accomplishment</p>
                        <h3 class="text-2xl font-bold text-purple-600"><?= number_format($averageAccomplishment, 1) ?>%</h3>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-trophy text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Top Performer</span>
                        <span><?= $topPerformer ?> (<?= number_format($topPerformance, 1) ?>%)</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill bg-purple-600" style="width: <?= $averageAccomplishment ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
            <!-- Performance Distribution -->
            <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Center Performance Distribution</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">% Accomplishment</button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Target vs Actual -->
            <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Target vs Actual AI Reports</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">All Centers</button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Data Table -->
        <div class="bg-white p-6 rounded-xl shadow-md fade-in mb-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Center Performance Details</h2>
            </div>
            <div class="data-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Center</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total AI</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Accomplishment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($centerData as $center => $data): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900"><?= $center ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $data['weekly_target'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($data['total_ai']) ?></td>
    
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell <?= $data['rating_class'] ?>"><?= $data['rating'] ?></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2"><?= number_format($data['accomplishment'], 1) ?>%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 
                                            <?php 
                                                if ($data['accomplishment'] >= 90) echo 'bg-green-500';
                                                elseif ($data['accomplishment'] >= 80) echo 'bg-green-500';
                                                elseif ($data['accomplishment'] >= 70) echo 'bg-blue-500';
                                                elseif ($data['accomplishment'] >= 60) echo 'bg-yellow-500';
                                                else echo 'bg-red-500';
                                            ?> 
                                            rounded-full" style="width: <?= $data['accomplishment'] ?>%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($data['balance']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const centers = <?= json_encode(array_keys($centerData)) ?>;
        const accomplishments = <?= json_encode(array_column($centerData, 'accomplishment')) ?>;
        const aiCounts = <?= json_encode(array_column($centerData, 'total_ai')) ?>;
        const weeklyTargets = <?= json_encode(array_column($centerData, 'weekly_target')) ?>;
        
        // Bar Chart - Center Performance
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: centers,
                datasets: [{
                    label: '% Accomplishment',
                    data: accomplishments,
                    backgroundColor: accomplishments.map(acc => {
                        if (acc >= 90) return 'rgba(16, 185, 129, 0.7)';
                        if (acc >= 80) return 'rgba(16, 185, 129, 0.7)';
                        if (acc >= 70) return 'rgba(59, 130, 246, 0.7)';
                        if (acc >= 60) return 'rgba(234, 179, 8, 0.7)';
                        return 'rgba(239, 68, 68, 0.7)';
                    }),
                    borderColor: accomplishments.map(acc => {
                        if (acc >= 90) return 'rgba(16, 185, 129, 1)';
                        if (acc >= 80) return 'rgba(16, 185, 129, 1)';
                        if (acc >= 70) return 'rgba(59, 130, 246, 1)';
                        if (acc >= 60) return 'rgba(234, 179, 8, 1)';
                        return 'rgba(239, 68, 68, 1)';
                    }),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toFixed(1)}%`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });

        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'bar',
            data: {
                labels: centers,
                datasets: [
                    {
                        label: 'Target AI Reports',
                        data: weeklyTargets,
                        backgroundColor: 'rgba(239,68,68,0.4)',  
                        borderColor: 'rgba(239,68,68,1)',
                        borderWidth: 1,
                        barPercentage: 1.0,         
                        categoryPercentage: 1.0,
                        order: 1                      
                    },
                    {
                        label: 'Actual AI Reports',
                        data: aiCounts,
                        backgroundColor: 'rgba(16,185,129,0.8)', 
                        borderColor: 'rgba(16,185,129,1)',
                        borderWidth: 1,
                        barPercentage: 0.5,           
                        categoryPercentage: 1.0,
                        order: 1                      
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        stacked: false // Make sure stacking is off
                    },
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
            // Month filter click handler - toggles selection
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
                    
                    // Fetch weeks for selected months via AJAX
                    $.get('get_weeks.php', { 
                        year: year, 
                        months: selectedMonths.join(',')
                    }, function(weeks) {
                        weekFilter.empty();
                        if (weeks.length > 0) {
                            weeks.forEach(week => {
                                const activeClass = week == <?= $selectedWeek ?? 'null' ?> ? 'active' : '';
                                weekFilter.append(`<button class="filter-btn ${activeClass}" data-week="${week}">Week ${week}</button>`);
                            });
                            const allActiveClass = <?= $selectedWeek ? 'false' : 'true' ?> ? 'active' : '';
                            weekFilter.prepend(`<button class="filter-btn ${allActiveClass}" data-week="all">All Weeks</button>`);
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
                
                // Reload the page with the new year filter to update the cards
                updateFilters({ year: year, months: null, week: null });
            });

            function updateFilters({year, months, week}) {
                const params = new URLSearchParams();
                
                if (year) params.set('year', year);
                if (months && months.length > 0) params.set('months', months.join(','));
                if (week) params.set('week', week);
                
                // Update URL and reload the page to update the cards when year changes
                const newUrl = window.location.pathname + '?' + params.toString();
                window.location.href = newUrl;
            }

            // Add fade-in animation to elements when scrolling
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
</body>
</html>