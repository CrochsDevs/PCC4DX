<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Only allow admin users
if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

class AdminDashboardManager {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }

    // Get all regional centers with abbreviation and code
    public function getRegionalCenters() {
        $query = "SELECT center_code, center_name FROM centers WHERE center_type = 'Regional'";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $centers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($centers as &$center) {
            $center['abbr'] = $this->abbreviate($center['center_name']);
        }
        return $centers;
    }

    // Abbreviate name (e.g., Central Luzon State University -> CLSU)
    private function abbreviate($name) {
        // If you have a short_name column, use that instead!
        $words = preg_split('/[\s\-]+/', $name);
        $abbr = '';
        foreach ($words as $w) {
            if (strlen($w) > 2 && ctype_upper($w[0])) {
                $abbr .= $w[0];
            }
        }
        return strtoupper($abbr);
    }

    // Get summary for all regional centers, filterable by year/month/week
    public function getCentersSummary($year, $months = [], $week = null) {
        $centers = $this->getRegionalCenters();
        $summary = [];
        foreach ($centers as $center) {
            $centerCode = $center['center_code'];

            // Build filters
            $where = "WHERE center = :center";
            $params = [':center' => $centerCode];
            if ($year) {
                $where .= " AND YEAR(date) = :year";
                $params[':year'] = $year;
            }
            if ($months && count($months) > 0) {
                $placeholders = [];
                foreach ($months as $i => $m) {
                    $p = ":month$i";
                    $placeholders[] = $p;
                    $params[$p] = $m;
                }
                $where .= " AND MONTH(date) IN (" . implode(',', $placeholders) . ")";
            }
            if ($week) {
                $where .= " AND WEEK(date, 1) = :week";
                $params[':week'] = $week;
            }

            // Get production totals
            $sql = "SELECT SUM(ai+bep+ih+private) as total 
                    FROM calf_drop $where";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $total = (int)($row['total'] ?? 0);

            // Get target for center
            $sqlTarget = "SELECT target FROM cd_target WHERE center_code = :center_code AND year = :year";
            $stmtTarget = $this->db->prepare($sqlTarget);
            $stmtTarget->execute([':center_code' => $centerCode, ':year' => $year]);
            $target = (int)($stmtTarget->fetchColumn() ?: 0);

            $percentage = ($target > 0) ? round(($total/$target)*100, 2) : 0;
            $summary[] = [
                'center_name' => $center['center_name'],
                'abbr' => $center['abbr'],
                'center_code' => $centerCode,
                'total' => $total,
                'target' => $target,
                'percentage' => $percentage
            ];
        }
        return $summary;
    }

    // Aggregate for cards
    public function getDashboardStats($summary) {
        $totalProduction = array_sum(array_column($summary, 'total'));
        $totalTarget = array_sum(array_column($summary, 'target'));
        $averageCompletion = count($summary) ? round(array_sum(array_column($summary, 'percentage'))/count($summary), 2) : 0;
        $completionPercent = $totalTarget > 0 ? round(($totalProduction/$totalTarget)*100, 2) : 0;
        $balance = $totalTarget - $totalProduction;
        $balancePercent = $totalTarget > 0 ? round(($balance/$totalTarget)*100, 2) : 0;
        return [
            'totalProduction' => $totalProduction,
            'totalTarget' => $totalTarget,
            'completionPercent' => $completionPercent,
            'balance' => $balance,
            'balancePercent' => $balancePercent,
            'averageCompletion' => $averageCompletion
        ];
    }
}

// --- FILTER HANDLING ---
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$months = isset($_GET['months']) ? array_map('intval', explode(',', $_GET['months'])) : [];
$week = isset($_GET['week']) ? intval($_GET['week']) : null;

$adm = new AdminDashboardManager($conn);
$centersSummary = $adm->getCentersSummary($year, $months, $week);
$stats = $adm->getDashboardStats($centersSummary);

// Prepare chart data
$labels = array_column($centersSummary, 'abbr');
$totals = array_column($centersSummary, 'total');
$targets = array_column($centersSummary, 'target');
$percentages = array_column($centersSummary, 'percentage');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Regional Summary Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tailwind, FontAwesome, Chart.js -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .card-hover { transition: box-shadow .2s; }
        .card-hover:hover { box-shadow: 0 10px 30px rgba(0,0,0,.08);}
        .progress-bar { width:100%; height:10px; background:#e5e7eb; border-radius:8px; overflow:hidden;}
        .progress-fill { height:10px; border-radius:8px;}
        .fade-in { animation: fadeIn .5s ease;}
        @keyframes fadeIn { from{opacity:0;transform:translateY(20px);} to{opacity:1;transform:translateY(0);} }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Calf Production -->
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Total Calf Production</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($stats['totalProduction']) ?></h2>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-cow text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Target: <?= number_format($stats['totalTarget']) ?></span>
                        <span class="font-semibold"><?= number_format($stats['completionPercent'],2) ?>%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-green-500" style="width: <?= $stats['completionPercent'] ?>%"></div>
                    </div>
                </div>
            </div>
            <!-- Weekly Target -->
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Weekly Target</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($stats['totalProduction']) ?></h2>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-bullseye text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Completion</span>
                        <span class="font-semibold"><?= number_format($stats['completionPercent'],2) ?>%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-purple-500" style="width: <?= $stats['completionPercent'] ?>%"></div>
                    </div>
                </div>
            </div>
            <!-- Balance -->
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Balance</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($stats['balance']) ?></h2>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-scale-balanced text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Remaining Target</span>
                        <span class="font-semibold"><?= number_format($stats['balancePercent'],2) ?>%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-yellow-500" style="width: <?= $stats['balancePercent'] ?>%"></div>
                    </div>
                </div>
            </div>
            <!-- Average Completion -->
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Average Completion</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($stats['averageCompletion'],2) ?>%</h2>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Performance</span>
                        <span class="font-semibold"><?= number_format($stats['averageCompletion']/count($centersSummary),2) ?>% Weekly</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-blue-500" style="width: <?= $stats['averageCompletion'] ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Performance by Center</h3>
                <canvas id="centerChart" height="300"></canvas>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Target vs Actual Comparison</h3>
                <canvas id="targetChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <script>
        // Chart.js - Performance by Center (Bar: % completion)
        const ctx1 = document.getElementById('centerChart').getContext('2d');
        new Chart(ctx1, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Completion (%)',
                    data: <?= json_encode($percentages) ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => ctx.raw + '%' } }
                },
                scales: {
                    x: {
                        min: 0, max: 110,
                        ticks: { stepSize: 10, callback: v=>v+'%' }
                    }
                }
            }
        });

        // Chart.js - Target vs Actual (Grouped Bar)
        const ctx2 = document.getElementById('targetChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [
                    {
                        label: 'Target',
                        data: <?= json_encode($targets) ?>,
                        backgroundColor: 'rgba(139, 92, 246, 0.7)'
                    },
                    {
                        label: 'Actual',
                        data: <?= json_encode($totals) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: ctx => ctx.dataset.label+': '+ctx.raw.toLocaleString() } }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>
</body>
</html>
