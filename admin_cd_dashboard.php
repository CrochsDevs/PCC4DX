<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db_config.php';
require 'auth_check.php';

if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

// Fetch data from database
try {
    // Use the existing connection from db_config.php ($conn)
    $pdo = $conn;
    
    // Get current week and year (using week 50 as in your original example)
    $currentWeek = 50;
    $currentYear = date('Y');
    
    // 1. Fetch calf drop data for the specified week
    $calfDropQuery = "
        SELECT center, SUM(ai + bep + ih + private) as total 
        FROM calf_drop 
        WHERE YEARWEEK(date, 1) = :yearweek 
        GROUP BY center
    ";
    $calfDropStmt = $pdo->prepare($calfDropQuery);
    $calfDropStmt->execute([':yearweek' => $currentYear . $currentWeek]);
    $calfDropData = $calfDropStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Fetch center targets for 2025 (as in your example)
    $targetQuery = "SELECT center_code, target FROM cd_target WHERE year = '2025'";
    $targetStmt = $pdo->query($targetQuery);
    $targetData = $targetStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // 3. Fetch all active centers
    $centerQuery = "SELECT center_code, center_name FROM centers WHERE is_active = 1";
    $centerStmt = $pdo->query($centerQuery);
    $centers = $centerStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Initialize variables
    $totalProduction = 0;
    $totalTarget = 0;
    $centerPerformance = [];
    
    // Calculate performance for each center
    foreach ($centers as $center) {
        $centerCode = $center['center_code'];
        $actual = 0;
        
        // Find actual production for this center
        foreach ($calfDropData as $drop) {
            if ($drop['center'] === $centerCode) {
                $actual = (int)$drop['total'];
                break;
            }
        }
        
        $target = $targetData[$centerCode] ?? 0;
        $percentage = $target > 0 ? round(($actual / $target) * 100, 2) : 0;
        
        $centerPerformance[] = [
            'name' => $centerCode,
            'wigTarget' => 0, // Placeholder
            'mon' => 0,       // Placeholder
            'tue' => 0,
            'wed' => 0,
            'thu' => 0,
            'fri' => 0,
            'rating' => $percentage . '%',
            'cta' => $percentage . '%',
            'target' => $target,
            'actual' => $actual,
            'percent' => $percentage . '%'
        ];
        
        $totalProduction += $actual;
        $totalTarget += $target;
    }
    
    // Calculate overall metrics
    $overallPercentage = $totalTarget > 0 ? round(($totalProduction / $totalTarget) * 100, 2) : 0;
    $weeklyTarget = $totalTarget / 52; // Weekly target (yearly divided by 52 weeks)
    $weeklyCompletion = $weeklyTarget > 0 ? round(($totalProduction / $weeklyTarget) * 100, 2) : 0;
    $balance = max($totalTarget - $totalProduction, 0);
    $balancePercentage = $totalTarget > 0 ? round(($balance / $totalTarget) * 100, 2) : 0;
    
} catch (PDOException $e) {
    // Log error and show message
    error_log("Database error in admin_cd_dashboard.php: " . $e->getMessage());
    die("An error occurred while loading the dashboard. Please try again later.");
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
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
                    <p class="text-gray-600"><?= $currentWeek ?>th Week Performance (<?= date('d-M-Y', strtotime($currentYear . 'W' . $currentWeek . '1')) ?>)</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white p-3 rounded-lg shadow-sm flex items-center">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                        <span>Week <?= $currentWeek ?>, <?= $currentYear ?></span>
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-download mr-2"></i>Export Report
                    </button>
                </div>
            </div>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
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
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-green-500" style="width: <?= $overallPercentage ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Weekly Target</p>
                        <h2 class="text-3xl font-bold mt-2"><?= number_format($weeklyTarget) ?></h2>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-bullseye text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Completion</span>
                        <span class="font-semibold"><?= $weeklyCompletion ?>%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-purple-500" style="width: <?= $weeklyCompletion ?>%"></div>
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
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-yellow-500" style="width: <?= $balancePercentage ?>%"></div>
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
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-blue-500" style="width: <?= $overallPercentage ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Indicators -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Top Performing Centers</h3>
                <div class="space-y-4">
                    <?php 
                    // Sort centers by performance (descending)
                    usort($centerPerformance, function($a, $b) {
                        return floatval($b['percent']) <=> floatval($a['percent']);
                    });
                    
                    // Show top 3
                    for ($i = 0; $i < min(3, count($centerPerformance)); $i++) {
                        $center = $centerPerformance[$i];
                        $percent = floatval($center['percent']);
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium"><?= $center['name'] ?></span>
                            <span class="text-sm font-medium"><?= $percent ?>%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-green-500" style="width: <?= $percent ?>%"></div>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Centers Needing Improvement</h3>
                <div class="space-y-4">
                    <?php 
                    // Sort centers by performance (ascending)
                    usort($centerPerformance, function($a, $b) {
                        return floatval($a['percent']) <=> floatval($b['percent']);
                    });
                    
                    // Show bottom 3
                    for ($i = 0; $i < min(3, count($centerPerformance)); $i++) {
                        $center = $centerPerformance[$i];
                        $percent = floatval($center['percent']);
                    ?>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium"><?= $center['name'] ?></span>
                            <span class="text-sm font-medium"><?= $percent ?>%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-red-500" style="width: <?= $percent ?>%"></div>
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Accomplished</th>
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
                            <td class="px-6 py-4 whitespace-nowrap"><?= $center['rating'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= $center['cta'] ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($center['target']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?= number_format($center['actual']) ?></td>
                            <td class="px-6 py-4 whitespace-nowrap font-semibold <?= floatval($center['percent']) > 90 ? 'text-green-600' : (floatval($center['percent']) < 70 ? 'text-red-600' : 'text-yellow-600') ?>">
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
                            parseFloat(c.percent) > 90 ? 'rgba(16, 185, 129, 0.7)' : 
                            parseFloat(c.percent) < 70 ? 'rgba(239, 68, 68, 0.7)' : 
                            'rgba(59, 130, 246, 0.7)'
                        ),
                        borderColor: centers.map(c => 
                            parseFloat(c.percent) > 90 ? 'rgba(16, 185, 129, 1)' : 
                            parseFloat(c.percent) < 70 ? 'rgba(239, 68, 68, 1)' : 
                            'rgba(59, 130, 246, 1)'
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
                                return `Actual: ${center.actual.toLocaleString()} (${center.percent})`;
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
                        backgroundColor: 'rgba(16, 185, 129, 0.7)', // Green for Actual
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1,
                        stack: 'stack1', // Stack group 1 (Actual)
                    },
                    {
                        label: 'Remaining',
                        data: centers.map(c => c.target - c.actual),
                        backgroundColor: 'rgba(239, 68, 68, 0.7)', // Red for Remaining
                        borderColor: 'rgba(239, 68, 68, 1)',
                        borderWidth: 1,
                        stack: 'stack1', // Stack group 1 (Remaining)
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
                        stacked: true, // Ensures bars stack on top of each other
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
    </script>
</body>
</html>