<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

// Define target values (you can adjust these as needed)
$targetValues = [
    'ai' => 1200,
    'bep' => 1500,
    'ih' => 1300,
    'private' => 1300,
    'grand_total' => 5000
];

class DashboardManager {
    private $db;
    private $centerCode;
    
    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }
    
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
    
    public function getMonthlyData() {
        $query = "SELECT 
                    YEAR(date) as year,
                    MONTH(date) as month,
                    SUM(ai) as ai,
                    SUM(bep) as bep,
                    SUM(ih) as ih,
                    SUM(private) as private
                  FROM calf_drop 
                  WHERE center = :center
                  GROUP BY YEAR(date), MONTH(date)
                  ORDER BY year DESC, month DESC
                  LIMIT 5";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute([':center' => $this->centerCode]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$centerCode = $_SESSION['center_code'];
$dashboardManager = new DashboardManager($conn, $centerCode);

$summaryData = $dashboardManager->getSummaryData();
$monthlyData = $dashboardManager->getMonthlyData();

// Prepare data for charts
$monthLabels = [];
$aiData = [];
$bepData = [];
$ihData = [];
$privateData = [];

foreach (array_reverse($monthlyData) as $month) {
    $monthLabels[] = date('M', mktime(0, 0, 0, $month['month'], 1));
    $aiData[] = $month['ai'];
    $bepData[] = $month['bep'];
    $ihData[] = $month['ih'];
    $privateData[] = $month['private'];
}

// Calculate percentages against targets
$grandTotal = $summaryData['grand_total'] ?? 0;
$grandTotalPercentage = round(($grandTotal / $targetValues['grand_total']) * 100);

$aiPercentage = round(($summaryData['total_ai'] / $targetValues['ai']) * 100);
$bepPercentage = round(($summaryData['total_bep'] / $targetValues['bep']) * 100);
$ihPercentage = round(($summaryData['total_ih'] / $targetValues['ih']) * 100);
$privatePercentage = round(($summaryData['total_private'] / $targetValues['private']) * 100);

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
            <!-- --->    
            
    <div class="container mx-auto px-4 py-8">

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Total AI</p>
                        <h3 class="text-2xl font-bold text-indigo-600"><?= number_format($summaryData['total_ai'] ?? 0) ?></h3>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-robot text-indigo-600 text-xl"></i>
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
            <!-- Bar Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Category Distribution</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">Monthly</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Quarterly</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Yearly</button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Line Chart -->
            <div class="bg-white p-6 rounded-xl shadow-md fade-in">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-semibold text-gray-800">Trend Over Time</h2>
                    <div class="flex space-x-2">
                        <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">All</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">AI</button>
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">BEP</button>
                    </div>
                </div>
                <div class="h-80">
                    <canvas id="lineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Pie Chart -->
        <div class="bg-white p-6 rounded-xl shadow-md fade-in mb-10">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">Category Breakdown vs Targets</h2>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">Percentage</button>
                    <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Values</button>
                </div>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 h-96">
                    <canvas id="pieChart"></canvas>
                </div>
                <div class="flex flex-col justify-center">
                    <?php foreach (['ai', 'bep', 'ih', 'private'] as $category): 
                        $current = $summaryData['total_'.$category] ?? 0;
                        $target = $targetValues[$category];
                        $percentage = round(($current / $target) * 100);
                        $color = [
                            'ai' => ['bg' => 'indigo-600', 'text' => 'indigo'],
                            'bep' => ['bg' => 'blue-600', 'text' => 'blue'],
                            'ih' => ['bg' => 'green-600', 'text' => 'green'],
                            'private' => ['bg' => 'purple-600', 'text' => 'purple']
                        ][$category];
                        
                        $statusClass = $percentage >= 100 ? 'status-excellent' : 
                                      ($percentage >= 80 ? 'status-achieved' : 
                                      ($percentage >= 50 ? 'status-progress' : 'status-low'));
                        $statusIcon = $percentage >= 100 ? 'fa-check-circle' : 
                                     ($percentage >= 60 ? 'fa-arrow-up' : 'fa-arrow-down');
                    ?>
                    <div class="mb-6">
                        <div class="flex items-center mb-2">
                            <div class="w-4 h-4 bg-<?= $color['bg'] ?> rounded-full mr-2"></div>
                            <span class="text-sm font-medium uppercase"><?= $category ?></span>
                            <span class="ml-auto text-sm font-semibold">
                                <?= number_format($current) ?>/<?= number_format($target) ?>
                            </span>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar bg-<?= $color['bg'] ?>" style="width: <?= min($percentage, 100) ?>%"></div>
                            <div class="target-marker" style="left: 100%"></div>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-gray-500">
                                <i class="fas <?= $statusIcon ?> mr-1"></i>
                                <span class="status-indicator <?= $statusClass ?>">
                                    <?= $percentage ?>%
                                </span>
                            </span>
                            <span class="text-xs text-gray-500">
                                <?= $percentage >= 100 ? 'Target exceeded' : 
                                    ($percentage >= 80 ? 'On track' : 
                                    ($percentage >= 50 ? 'Making progress' : 'Needs improvement')) ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Bar Chart
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['AI', 'BEP', 'IH', 'Private'],
                datasets: [{
                    label: 'Total Count',
                    data: [
                        <?= $summaryData['total_ai'] ?? 0 ?>,
                        <?= $summaryData['total_bep'] ?? 0 ?>,
                        <?= $summaryData['total_ih'] ?? 0 ?>,
                        <?= $summaryData['total_private'] ?? 0 ?>
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
                        display: false
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
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [
                    {
                        label: 'AI',
                        data: <?= json_encode($aiData) ?>,
                        borderColor: 'rgba(79, 70, 229, 1)',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'BEP',
                        data: <?= json_encode($bepData) ?>,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'IH',
                        data: <?= json_encode($ihData) ?>,
                        borderColor: 'rgba(16, 185, 129, 1)',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.3,
                        fill: true
                    },
                    {
                        label: 'Private',
                        data: <?= json_encode($privateData) ?>,
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

        // Pie Chart
        const pieCtx = document.getElementById('pieChart').getContext('2d');
        const pieChart = new Chart(pieCtx, {
            type: 'doughnut',
            data: {
                labels: ['AI', 'BEP', 'IH', 'Private'],
                datasets: [{
                    data: [
                        <?= $summaryData['total_ai'] ?? 0 ?>,
                        <?= $summaryData['total_bep'] ?? 0 ?>,
                        <?= $summaryData['total_ih'] ?? 0 ?>,
                        <?= $summaryData['total_private'] ?? 0 ?>
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
                                const target = <?= json_encode($targetValues) ?>[context.label.toLowerCase()] || 1;
                                const percentage = Math.round((value / target) * 100);
                                return [
                                    `${label}: ${value.toLocaleString()}`,
                                    `Target: ${target.toLocaleString()}`,
                                    `Progress: ${percentage}%`
                                ];
                            }
                        }
                    },
                    annotation: {
                        annotations: {
                            targetLine: {
                                type: 'line',
                                yMin: 0,
                                yMax: 0,
                                borderColor: 'rgba(0, 0, 0, 0.7)',
                                borderWidth: 2,
                                borderDash: [6, 6],
                                label: {
                                    content: 'Target Levels',
                                    enabled: true,
                                    position: 'top'
                                }
                            }
                        }
                    }
                },
                cutout: '70%'
            }
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


            <!-- --->           
        </div>
    </div>

</html>