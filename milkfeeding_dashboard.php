<?php
session_start();
require_once 'db_config.php';

class DashboardData {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // DSWD Statistics
    public function getDSWDStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(beneficiaries) AS total_beneficiaries,
                COUNT(DISTINCT supplier) AS total_coops,
                SUM(milk_packs) AS total_milk,
                SUM(milk_packs * price_per_pack) AS gross_revenue
            FROM dswd_feeding_program
            WHERE is_archived = 0
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // DepEd Statistics
    public function getDepEdStats() {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(beneficiaries) AS total_beneficiaries,
                COUNT(DISTINCT supplier) AS total_coops,
                SUM(milk_packs) AS total_milk,
                SUM(milk_packs * price_per_pack) AS gross_revenue
            FROM school_feeding_program
            WHERE is_archived = 0
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Weekly Distribution Data
    public function getWeeklyDistribution($program) {
        $table = $program === 'dswd' ? 'dswd_feeding_program' : 'school_feeding_program';
        
        $stmt = $this->conn->prepare("
            SELECT 
                YEARWEEK(delivery_date) AS week,
                SUM(milk_packs) AS total_milk,
                SUM(beneficiaries) AS total_beneficiaries
            FROM $table
            WHERE delivery_date >= DATE_SUB(NOW(), INTERVAL 6 WEEK)
            GROUP BY YEARWEEK(delivery_date)
            ORDER BY week DESC
            LIMIT 5
        ");
        $stmt->execute();
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    // Regional Implementation Data
    public function getRegionalData($program) {
        $table = $program === 'dswd' ? 'dswd_feeding_program' : 'school_feeding_program';
        
        $stmt = $this->conn->prepare("
            SELECT 
                region,
                SUM(beneficiaries) AS beneficiaries,
                SUM(milk_packs * price_per_pack) AS contract_amount,
                status,
                GROUP_CONCAT(DISTINCT remarks SEPARATOR '; ') AS remarks
            FROM $table
            WHERE is_archived = 0
            GROUP BY region, status
            ORDER BY region
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$dashboard = new DashboardData($conn);
$dswdStats = $dashboard->getDSWDStats();
$depEdStats = $dashboard->getDepEdStats();
$weeklyDSWD = $dashboard->getWeeklyDistribution('dswd');
$regionalDSWD = $dashboard->getRegionalData('dswd');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Feeding Program Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
       :root {
            --pcc-blue: #0056b3;
            --pcc-dark-blue: #003366;
            --pcc-light-blue: #e6f0ff;
            --pcc-orange: #ff6b00;
            --pcc-light-orange: #fff3e6;
            --pcc-green: #28a745;
            --pcc-light-green: #e6f7eb;
            --pcc-red: #dc3545;
            --pcc-light-red: #f8d7da;
            --pcc-purple: #6f42c1;
            --pcc-light-purple: #f3e8ff;
            --secondary: #6c757d;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 0.375rem;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #495057;
            transition: margin-left 0.3s;
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
            background-color: var(--pcc-red);
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

        .status-badge {
            @apply px-2 py-1 rounded-full text-xs font-medium;
        }
        .status-completed {
            @apply bg-green-100 text-green-800;
        }
        .status-ongoingmilkdeliveries {
            @apply bg-blue-100 text-blue-800;
        }
        .status-partiallycompleted {
            @apply bg-yellow-100 text-yellow-800;
        }
        .status-notyetstarted {
            @apply bg-gray-100 text-gray-800;
        }
        .legend-item {
            @apply flex items-center mr-4;
        }
        .legend-color {
            @apply w-3 h-3 rounded-full mr-1;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden ">

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
                        <h5 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h5>
                        <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                    </div>
                </div>
                <nav>
                    <ul>
                        <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i>Back to quickfacts</a></li>
                        <li><a href="milkfeeding_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i>DSWD Program Report</a></li>
                        <li><a href="milk_feeding_dswd_report.php" class="nav-link "><i class="fas fa-file-alt"></i> DSWD Program Report</a></li>
                        <li><a href="milk_feeding_deped_report.php" class="nav-link"><i class="fas fa-file-alt"></i> DepEd Program Report</a></li>
                        <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </nav>
            </div>


       <div class="flex-1 h-screen overflow-auto ml-[280px]">
            <!-- Header -->
            <header class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h1 class="text-2xl font-bold text-gray-800">Milk Feeding Program Dashboard</h1>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <i class="fas fa-bell text-gray-500"></i>
                            <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                        </div>
                        <div class="text-sm text-gray-600">Today: <span class="font-medium">June 10, 2023</span></div>
                    </div>
                </div>
            </header>

            <!-- Tabs Navigation -->
            <div class="px-6 pt-4">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <a href="#" class="border-blue-500 text-blue-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            DSWD Program
                        </a>
                        <a href="#" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            DepEd Program
                        </a>
                    </nav>
                </div>
            </div>

            <!-- DSWD Dashboard Content -->
            <main class="px-6 py-4">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Total Beneficiaries</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">24,568</p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-600 text-sm font-medium">+12.5% </span>
                            <span class="text-gray-500 text-sm">from last week</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Coops Engaged</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">42</p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-handshake text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-600 text-sm font-medium">+3 </span>
                            <span class="text-gray-500 text-sm">new coops this month</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Milk Distributed</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">58,920L</p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-wine-bottle text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-600 text-sm font-medium">+8.2% </span>
                            <span class="text-gray-500 text-sm">from last week</span>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Gross Revenue</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">₱12.4M</p>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                        </div>
                        <div class="mt-4">
                            <span class="text-green-600 text-sm font-medium">+5.7% </span>
                            <span class="text-gray-500 text-sm">from last week</span>
                        </div>
                    </div>
                </div>

                <!-- Weekly Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Weekly Milk Distribution</h3>
                        <canvas id="milkDistributionChart" height="250"></canvas>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Weekly Beneficiaries Reached</h3>
                        <canvas id="beneficiariesChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Status Legends -->
                <div class="bg-white rounded-lg shadow p-6 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Program Status Legend</h3>
                    <div class="flex flex-wrap">
                        <div class="legend-item">
                            <div class="legend-color bg-blue-500"></div>
                            <span class="text-sm">MOA Signing</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color bg-green-500"></div>
                            <span class="text-sm">Fund Transfer</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color bg-yellow-500"></div>
                            <span class="text-sm">Procurement - OG</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color bg-purple-500"></div>
                            <span class="text-sm">Milk Deliveries - OG</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color bg-red-500"></div>
                            <span class="text-sm">Liquidation</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-color bg-gray-500"></div>
                            <span class="text-sm">Not Yet Started</span>
                        </div>
                    </div>
                </div>

                <!-- Regional Data Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Regional Implementation Status</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Regional Office</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beneficiaries</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contract Amount</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">NCR</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5,240</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱2,850,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-ongoingmilkdeliveries">
                                            <i class="fas fa-truck mr-1"></i> On-going Milk Deliveries
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Phase 2 of 3 completed</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Region IV-A</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">4,150</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱2,250,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-completed">
                                            <i class="fas fa-check-circle mr-1"></i> Completed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">All phases delivered</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Region III</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3,780</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱2,050,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-partiallycompleted">
                                            <i class="fas fa-check-double mr-1"></i> Partially Completed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Waiting for next delivery</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Region VII</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3,250</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱1,780,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-notyetstarted">
                                            <i class="fas fa-clock mr-1"></i> Not Yet Started
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">MOA signing scheduled</td>
                                </tr>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Region XI</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2,980</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₱1,620,000</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-ongoingmilkdeliveries">
                                            <i class="fas fa-truck mr-1"></i> On-going Milk Deliveries
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Phase 1 of 3 completed</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-500">Showing <span class="font-medium">1</span> to <span class="font-medium">5</span> of <span class="font-medium">17</span> regions</div>
                            <div class="flex space-x-2">
                                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">Previous</button>
                                <button class="px-3 py-1 border border-gray-300 rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Milk Distribution Chart
        const milkCtx = document.getElementById('milkDistributionChart').getContext('2d');
        const milkChart = new Chart(milkCtx, {
            type: 'bar',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Current Week'],
                datasets: [{
                    label: 'Milk Distributed (Liters)',
                    data: [12500, 11800, 14200, 13500, 6900],
                    backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Liters'
                        }
                    }
                }
            }
        });

        // Beneficiaries Chart
        const beneficiariesCtx = document.getElementById('beneficiariesChart').getContext('2d');
        const beneficiariesChart = new Chart(beneficiariesCtx, {
            type: 'line',
            data: {
                labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Current Week'],
                datasets: [{
                    label: 'Beneficiaries Reached',
                    data: [5200, 4800, 6200, 5800, 2568],
                    fill: false,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2,
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Number of Beneficiaries'
                        }
                    }
                }
            }
        });

        // DepEd Program Status Pie Chart (would be shown when DepEd tab is selected)
        const depedStatusCtx = document.createElement('canvas');
        depedStatusCtx.id = 'depedStatusChart';
        const depedStatusChart = new Chart(depedStatusCtx, {
            type: 'pie',
            data: {
                labels: ['Completed', 'On-going Milk Deliveries', 'Partially Completed', 'Not Yet Started'],
                datasets: [{
                    data: [35, 25, 20, 20],
                    backgroundColor: [
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(245, 158, 11, 0.7)',
                        'rgba(156, 163, 175, 0.7)'
                    ],
                    borderColor: [
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(245, 158, 11, 1)',
                        'rgba(156, 163, 175, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'DepEd Program Status Distribution'
                    }
                }
            }
        });
    </script>
</body>
</html>