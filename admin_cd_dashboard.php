<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_config.php';
require 'auth_check.php';

// Restrict to HQ users only
if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : null;
$selectedWeek = isset($_GET['week']) ? intval($_GET['week']) : null;

try {
    $pdo = $conn;

    $whereConditions = ["YEAR(date) = :year"];
    $params = [':year' => $selectedYear];

    if ($selectedMonth) {
        $whereConditions[] = "MONTH(date) = :month";
        $params[':month'] = $selectedMonth;
    }

    if ($selectedWeek) {
        $whereConditions[] = "WEEK(date, 1) = :week";
        $params[':week'] = $selectedWeek;
    }

    $whereClause = "WHERE " . implode(" AND ", $whereConditions);

    $calfDropQuery = "
        SELECT center, SUM(ai + bep + ih + private) AS total 
        FROM calf_drop 
        $whereClause
        GROUP BY center
    ";
    $stmt = $pdo->prepare($calfDropQuery);
    $stmt->execute($params);
    $calfDropData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $targetQuery = "SELECT center_code, target FROM cd_target WHERE year = :year";
    $stmt = $pdo->prepare($targetQuery);
    $stmt->execute([':year' => $selectedYear]);
    $targetData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $centerQuery = "
        SELECT center_code, center_name 
        FROM centers 
        WHERE is_active = 1 AND center_type != 'Headquarters'
    ";
    $stmt = $pdo->query($centerQuery);
    $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $yearsQuery = "
        SELECT DISTINCT year FROM (
            SELECT DISTINCT YEAR(date) AS year FROM calf_drop
            UNION
            SELECT DISTINCT year FROM cd_target
        ) AS combined_years
        ORDER BY year DESC
    ";
    $availableYears = $pdo->query($yearsQuery)->fetchAll(PDO::FETCH_COLUMN, 0);

    if (!in_array(date('Y'), $availableYears)) {
        $availableYears[] = date('Y');
        rsort($availableYears);
    }

    $totalProduction = 0;
    $totalTarget = 0;
    $centerPerformance = [];

    // Function to calculate letter grade
    function getLetterGrade($percent) {
        if ($percent >= 90) return 'A';
        if ($percent >= 80) return 'B';
        if ($percent >= 70) return 'C';
        return 'D';
    }

    foreach ($centers as $center) {
        $code = $center['center_code'];
        $name = $center['center_name'];
        $actual = 0;

        foreach ($calfDropData as $drop) {
            if ($drop['center'] === $code) {
                $actual = (int)$drop['total'];
                break;
            }
        }

        $target = $targetData[$code] ?? 0;
        $percent = $target > 0 ? round(($actual / $target) * 100, 2) : 0;
        $letterGrade = getLetterGrade($percent);

        $centerPerformance[] = [
            'name' => $name,
            'target' => $target,
            'actual' => $actual,
            'percent' => $percent,
            'grade' => $letterGrade,
            'wigTarget' => 0,
            'mon' => 0, 'tue' => 0, 'wed' => 0,
            'thu' => 0, 'fri' => 0,
        ];

        $totalProduction += $actual;
        $totalTarget += $target;
    }

    $overallPercentage = $totalTarget > 0 ? round(($totalProduction / $totalTarget) * 100, 2) : 0;
    $weeklyTarget = $totalTarget / 52;
    $weeklyCompletion = $weeklyTarget > 0 ? round(($totalProduction / $weeklyTarget) * 100, 2) : 0;
    $balance = max($totalTarget - $totalProduction, 0);
    $balancePercentage = $totalTarget > 0 ? round(($balance / $totalTarget) * 100, 2) : 0;

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HQ Calf Production Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .progress-bar {
            height: 20px;
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .grade-A {
            background-color: #10B981;
            color: white;
        }
        .grade-B {
            background-color: #3B82F6;
            color: white;
        }
        .grade-C {
            background-color: #F59E0B;
            color: white;
        }
        .grade-D {
            background-color: #EF4444;
            color: white;
        }
        .filter-btn.active {
            background-color: #3B82F6;
            color: white;
            border-color: #3B82F6;
        }
    </style>
</head>

<body class="bg-gray-50 font-sans">
    <!-- Sidebar -->
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
            <li><a href="admin_centertarget_calf_dashboard.php" class="nav-link" data-section="announcement-section">
                <i class="fas fa-file-alt"></i> Center Target</a></li>
            <li><a href="admin_report_calf_dashboard.php" class="nav-link" data-section="quickfacts-section">
                <i class="fas fa-sitemap"></i> Reports</a></li>
        </ul>
    </div>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Calf Production Dashboard</h1>
                    <p class="text-gray-600"><?= date('F d, Y') ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white p-3 rounded-lg shadow-sm flex items-center">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                        <span><?= $selectedYear ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Filter Container -->
        <div class="bg-white p-4 rounded-lg shadow-sm mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Year Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($availableYears as $year): ?>
                            <a href="?year=<?= $year ?>" 
                               class="px-3 py-1 text-sm rounded-full border <?= $year == $selectedYear ? 'bg-blue-500 text-white border-blue-500' : 'bg-gray-100 border-gray-300' ?>">
                                <?= $year ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Month Filter -->
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-sm font-medium text-gray-700">Month</label>
                        <div class="flex gap-1">
                            <button id="selectQuarter1" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">Q1</button>
                            <button id="selectQuarter2" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">Q2</button>
                            <button id="selectQuarter3" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">Q3</button>
                            <button id="selectQuarter4" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">Q4</button>
                            <button id="selectFirstHalf" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">H1</button>
                            <button id="selectSecondHalf" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">H2</button>
                            <button id="clearMonths" class="text-xs px-2 py-1 bg-gray-100 rounded hover:bg-gray-200">Clear</button>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        $months = [
                            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 
                            4 => 'Apr', 5 => 'May', 6 => 'Jun',
                            7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
                            10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                        ];
                        foreach ($months as $num => $name): ?>
                            <a href="?year=<?= $selectedYear ?>&month=<?= $num ?>" 
                               class="px-3 py-1 text-sm rounded-full border <?= $selectedMonth == $num ? 'bg-blue-500 text-white border-blue-500' : 'bg-gray-100 border-gray-300' ?>">
                                <?= $name ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Week Filter (only shown when month is selected) -->
                <?php if ($selectedMonth): ?>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Week</label>
                    <div class="flex flex-wrap gap-2">
                        <?php 
                        // Calculate weeks for selected month
                    $weeksInMonth = ceil(date('t', strtotime("$selectedYear-$selectedMonth-01")) / 7);

                        for ($i = 1; $i <= $weeksInMonth; $i++): ?>
                            <a href="?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>&week=<?= $i ?>" 
                               class="px-3 py-1 text-sm rounded-full border <?= $selectedWeek == $i ? 'bg-blue-500 text-white border-blue-500' : 'bg-gray-100 border-gray-300' ?>">
                                Week <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <a href="?year=<?= $selectedYear ?>&month=<?= $selectedMonth ?>" 
                           class="px-3 py-1 text-sm rounded-full border <?= !$selectedWeek ? 'bg-blue-500 text-white border-blue-500' : 'bg-gray-100 border-gray-300' ?>">
                            All Weeks
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Total Calf Production</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($totalProduction) ?></h2>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-cow text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Target: <?= number_format($totalTarget) ?></span>
                        <span class="font-semibold"><?= $overallPercentage ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                        <div class="bg-green-500 h-2.5 rounded-full" style="width: <?= $overallPercentage ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Balance</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($balance) ?></h2>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-scale-balanced text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Remaining Target</span>
                        <span class="font-semibold"><?= $balancePercentage ?>%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                        <div class="bg-yellow-500 h-2.5 rounded-full" style="width: <?= $balancePercentage ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Average Completion</p>
                        <h2 class="text-3xl font-bold mt-2"><?= $overallPercentage ?>%</h2>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Performance</span>
                        <span class="font-semibold"><?= round($weeklyCompletion / 52, 2) ?>% Weekly</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 mt-2">
                        <div class="bg-blue-500 h-2.5 rounded-full" style="width: <?= $overallPercentage ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Indicators -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Top Performing Centers</h3>
                <div class="space-y-4">
                    <?php 
                    usort($centerPerformance, function($a, $b) {
                        return floatval($b['percent']) <=> floatval($a['percent']);
                    });
                    
                    for ($i = 0; $i < min(3, count($centerPerformance)); $i++) {
                        $center = $centerPerformance[$i];
                        $percent = floatval($center['percent']);
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium"><?= $center['name'] ?></span>
                            <span class="text-sm font-medium"><?= $center['grade'] ?> (<?= $percent ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full <?= 
                                $percent >= 90 ? 'bg-green-500' : 
                                ($percent >= 80 ? 'bg-blue-500' : 
                                ($percent >= 70 ? 'bg-yellow-500' : 'bg-red-500')) 
                            ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Centers Needing Improvement</h3>
                <div class="space-y-4">
                    <?php 
                    usort($centerPerformance, function($a, $b) {
                        return floatval($a['percent']) <=> floatval($b['percent']);
                    });
                    
                    for ($i = 0; $i < min(3, count($centerPerformance)); $i++) {
                        $center = $centerPerformance[$i];
                        $percent = floatval($center['percent']);
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium"><?= $center['name'] ?></span>
                            <span class="text-sm font-medium"><?= $center['grade'] ?> (<?= $percent ?>%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full <?= 
                                $percent >= 90 ? 'bg-green-500' : 
                                ($percent >= 80 ? 'bg-blue-500' : 
                                ($percent >= 70 ? 'bg-yellow-500' : 'bg-red-500')) 
                            ?>" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Weekly Performance by Center</h3>
                <canvas id="centerChart" height="300"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Target vs Actual Comparison</h3>
                <canvas id="targetChart" height="300"></canvas>
            </div>
        </div>

        <!-- Detailed Table -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden fade-in">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-semibold">Detailed Center Performance</h3>
                    <div class="relative">
                        <input type="text" placeholder="Search centers..." class="pl-10 pr-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Center</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WIG Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Mon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tue</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Wed</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thu</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fri</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($centerPerformance as $center): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium"><?= $center['name'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $center['wigTarget'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap <?= $center['mon'] === 0 ? 'text-gray-400' : '' ?>"><?= $center['mon'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap <?= $center['tue'] === 0 || $center['tue'] === "X" ? 'text-gray-400' : '' ?>"><?= $center['tue'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap <?= $center['wed'] === 0 ? 'text-gray-400' : '' ?>"><?= $center['wed'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap <?= $center['thu'] === 0 ? 'text-gray-400' : '' ?>"><?= $center['thu'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap <?= $center['fri'] === 0 ? 'text-gray-400' : '' ?>"><?= $center['fri'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full grade-<?= $center['grade'] ?>">
                                    <?= $center['grade'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap w-32">
                                <div class="flex items-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 mr-2">
                                        <div class="h-2.5 rounded-full <?= 
                                            $center['percent'] >= 90 ? 'bg-green-500' : 
                                            ($center['percent'] >= 80 ? 'bg-blue-500' : 
                                            ($center['percent'] >= 70 ? 'bg-yellow-500' : 'bg-red-500')) 
                                        ?>" style="width: <?= $center['percent'] ?>%"></div>
                                    </div>
                                    <span class="text-xs"><?= $center['percent'] ?>%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($center['target']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($center['actual']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold <?= 
                                $center['percent'] >= 90 ? 'text-green-600' : 
                                ($center['percent'] < 70 ? 'text-red-600' : 'text-yellow-600') 
                            ?>">
                                <?= $center['percent'] ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-between border-t border-gray-200">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Showing 1 to <?= count($centerPerformance) ?> of <?= count($centerPerformance) ?> entries</span>
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 border rounded-md text-sm disabled:opacity-50" disabled>Previous</button>
                    <button class="px-3 py-1 border rounded-md text-sm bg-blue-500 text-white">1</button>
                    <button class="px-3 py-1 border rounded-md text-sm disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data for the dashboard
        const centers = <?= json_encode($centerPerformance) ?>;
        
        // Charts
        // Center Performance Chart
        const centerCtx = document.getElementById('centerChart').getContext('2d');
        const centerChart = new Chart(centerCtx, {
            type: 'bar',
            data: {
                labels: centers.map(c => c.name),
                datasets: [
                    {
                        label: 'Actual Production',
                        data: centers.map(c => c.actual),
                        backgroundColor: centers.map(c => 
                            parseFloat(c.percent) >= 90 ? 'rgba(16, 185, 129, 0.7)' : 
                            parseFloat(c.percent) >= 80 ? 'rgba(59, 130, 246, 0.7)' : 
                            parseFloat(c.percent) >= 70 ? 'rgba(245, 158, 11, 0.7)' : 
                            'rgba(239, 68, 68, 0.7)'
                        ),
                        borderColor: centers.map(c => 
                            parseFloat(c.percent) >= 90 ? 'rgba(16, 185, 129, 1)' : 
                            parseFloat(c.percent) >= 80 ? 'rgba(59, 130, 246, 1)' : 
                            parseFloat(c.percent) >= 70 ? 'rgba(245, 158, 11, 1)' : 
                            'rgba(239, 68, 68, 1)'
                        ),
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const center = centers[context.dataIndex];
                                return `Actual: ${center.actual.toLocaleString()} (${center.percent}%)`;
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

        // Target vs Actual Chart
        const targetCtx = document.getElementById('targetChart').getContext('2d');
        const targetChart = new Chart(targetCtx, {
            type: 'bar',
            data: {
                labels: centers.map(c => c.name),
                datasets: [
                    {
                        label: 'Actual',
                        data: centers.map(c => c.actual),
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        stack: 'stack1',
                    },
                    {
                        label: 'Remaining',
                        data: centers.map(c => c.target - c.actual),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)',
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1,
                        stack: 'stack1',
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const center = centers[context.dataIndex];
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += context.parsed.y.toLocaleString();
                                
                                if (context.datasetIndex === 1) {
                                    label += ` (Remaining: ${ (center.target - center.actual).toLocaleString() })`;
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        stacked: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString(); 
                            }
                        }
                    }
                }
            }
        });

        // Search functionality
        const searchInput = document.querySelector('input[type="text"]');
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const centerName = row.querySelector('td:first-child').textContent.toLowerCase();
                if (centerName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Quarter buttons functionality
        document.getElementById('selectQuarter1').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '1,2,3');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('selectQuarter2').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '4,5,6');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('selectQuarter3').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '7,8,9');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('selectQuarter4').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '10,11,12');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('selectFirstHalf').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '1,2,3,4,5,6');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('selectSecondHalf').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.set('month', '7,8,9,10,11,12');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });

        document.getElementById('clearMonths').addEventListener('click', () => {
            const url = new URL(window.location.href);
            url.searchParams.delete('month');
            url.searchParams.delete('week');
            window.location.href = url.toString();
        });
    </script>
</body>
</html>