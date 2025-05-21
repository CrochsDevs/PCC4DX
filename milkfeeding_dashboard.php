<?php
session_start();
require_once 'db_config.php';

class DashboardData {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getStats($program) {
        $table = ($program === 'dswd') ? 'dswd_feeding_program' : 'school_feeding_program';
        
        $stmt = $this->conn->prepare("
            SELECT 
                SUM(beneficiaries) AS total_beneficiaries,
                COUNT(DISTINCT supplier) AS total_coops,
                SUM(milk_packs) AS total_milk,
                SUM(milk_packs * price_per_pack) AS gross_revenue
            FROM $table
            WHERE is_archived = 0
        ");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getDistributionData($program, $timeframe) {
        $table = $program === 'dswd' ? 'dswd_feeding_program' : 'school_feeding_program';
        $groupBy = $timeframe === 'weekly' ? 'YEARWEEK(delivery_date)' : 'DATE_FORMAT(delivery_date, "%Y-%m")';
        
        $stmt = $this->conn->prepare("
            SELECT 
                $groupBy AS period,
                SUM(milk_packs) AS total_milk,
                SUM(beneficiaries) AS total_beneficiaries
            FROM $table
            WHERE delivery_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY $groupBy
            ORDER BY period DESC
            LIMIT 5
        ");
        $stmt->execute();
        return array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

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

    public function getProgramStatus($program) {
        $table = $program === 'dswd' ? 'dswd_feeding_program' : 'school_feeding_program';
        
        $stmt = $this->conn->prepare("
            SELECT 
                status,
                COUNT(*) AS count,
                SUM(beneficiaries) AS beneficiaries,
                SUM(milk_packs * price_per_pack) AS amount
            FROM $table
            WHERE is_archived = 0
            GROUP BY status
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$currentProgram = $_GET['program'] ?? 'dswd';
$dashboard = new DashboardData($conn);
$stats = $dashboard->getStats($currentProgram);
$distributionData = $dashboard->getDistributionData($currentProgram, ($currentProgram === 'dswd' ? 'weekly' : 'monthly'));
$regionalData = $dashboard->getRegionalData($currentProgram);
$statusData = $dashboard->getProgramStatus($currentProgram);
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
    
        .status-badge {
            @apply px-2 py-1 rounded-full text-xs font-medium;
        }
        .status-completed { @apply bg-green-100 text-green-800; }
        .status-ongoing { @apply bg-yellow-100 text-yellow-800; }
        .status-notyetstarted { @apply bg-red-100 text-red-800; }
        .status-moasigning { @apply bg-blue-100 text-blue-800; }
        .status-fundtransfer { @apply bg-purple-100 text-purple-800; }
        .status-documents { @apply bg-pink-100 text-pink-800; }
        .status-procurement { @apply bg-indigo-100 text-indigo-800; }
        .status-milkdeliveries { @apply bg-teal-100 text-teal-800; }
        .status-liquidation { @apply bg-orange-100 text-orange-800; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
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

             <div class="px-6 pt-4">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-8">
                        <a href="?program=dswd" 
                           class="<?= $currentProgram === 'dswd' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' ?> 
                           whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            DSWD Program
                        </a>
                        <a href="?program=deped" 
                           class="<?= $currentProgram === 'deped' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500' ?> 
                           whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
                            DepEd Program
                        </a>
                    </nav>
                </div>
            </div>

            <main class="px-6 py-4">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Beneficiaries -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Total Beneficiaries</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    <?= number_format($stats['total_beneficiaries'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Coops Engaged -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Coops Engaged</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    <?= number_format($stats['total_coops'] ?? 0) ?>
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 text-green-600">
                                <i class="fas fa-handshake text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Milk Distributed -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Milk Distributed</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    <?= number_format($stats['total_milk'] ?? 0) ?>L
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                                <i class="fas fa-wine-bottle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Gross Revenue -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500 truncate">Gross Revenue</p>
                                <p class="mt-1 text-3xl font-semibold text-gray-900">
                                    ₱<?= number_format($stats['gross_revenue'] ?? 0, 2) ?>
                                </p>
                            </div>
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if($currentProgram === 'deped'): ?>
                <!-- DepEd Specific Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Program Status Distribution</h3>
                        <canvas id="statusPieChart" height="250"></canvas>
                        <div class="mt-4 grid grid-cols-2 gap-2" id="pieLegend"></div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Distribution Overview</h3>
                        <canvas id="monthlyDistributionChart" height="250"></canvas>
                    </div>
                </div>
                <?php else: ?>
                <!-- DSWD Specific Charts -->
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
                <?php endif; ?>

                <!-- Regional Data Table -->
                <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900">Regional Implementation Status</h3>
                        <?php if($currentProgram === 'dswd'): ?>
                        <div class="flex gap-2 flex-wrap">
                            <?php 
                            $statusColors = [
                                'MOA Signing' => 'moasigning',
                                'Fund Transfer' => 'fundtransfer',
                                'Documents - OG' => 'documents',
                                'Procurement - OG' => 'procurement',
                                'Milk Deliveries - OG' => 'milkdeliveries',
                                'Liquidation' => 'liquidation',
                                'Not Yet Started' => 'notyetstarted'
                            ];
                            foreach($statusColors as $status => $color): ?>
                                <span class="status-badge status-<?= $color ?>"><?= $status ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
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
                                <?php foreach ($regionalData as $region): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?= htmlspecialchars($region['region']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= number_format($region['beneficiaries']) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        ₱<?= number_format($region['contract_amount'], 2) ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="status-badge status-<?= strtolower(str_replace([' ', '-'], '', $region['status'])) ?>">
                                            <?= htmlspecialchars($region['status']) ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?= htmlspecialchars($region['remarks']) ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        <?php if($currentProgram === 'deped'): ?>
        // DepEd Pie Chart
        const statusData = <?= json_encode($statusData) ?>;
        const pieCtx = document.getElementById('statusPieChart').getContext('2d');
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: statusData.map(item => item.status),
                datasets: [{
                    data: statusData.map(item => item.count),
                    backgroundColor: [
                        '#10B981', '#F59E0B', '#EF4444',
                        '#3B82F6', '#8B5CF6', '#EC4899',
                        '#6366F1', '#14B8A6', '#F97316'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                }
            }
        });

        // Monthly Distribution Chart
        const monthlyData = <?= json_encode($distributionData) ?>;
        const monthlyCtx = document.getElementById('monthlyDistributionChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: monthlyData.map(item => item.period),
                datasets: [{
                    label: 'Milk Distributed (Liters)',
                    data: monthlyData.map(item => item.total_milk),
                    backgroundColor: 'rgba(59, 130, 246, 0.7)'
                }, {
                    label: 'Beneficiaries Reached',
                    data: monthlyData.map(item => item.total_beneficiaries),
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    type: 'line',
                    borderColor: 'rgba(16, 185, 129, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        <?php else: ?>
        // DSWD Charts (Existing weekly charts)
        const weeklyLabels = <?= json_encode(array_column($distributionData, 'period')) ?>;
        const milkData = <?= json_encode(array_column($distributionData, 'total_milk')) ?>;
        const beneficiariesData = <?= json_encode(array_column($distributionData, 'total_beneficiaries')) ?>;

        // Milk Distribution Chart
        new Chart(document.getElementById('milkDistributionChart'), {
            type: 'bar',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: 'Milk Distributed (Liters)',
                    data: milkData,
                    backgroundColor: 'rgba(59, 130, 246, 0.7)'
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });

        // Beneficiaries Chart
        new Chart(document.getElementById('beneficiariesChart'), {
            type: 'line',
            data: {
                labels: weeklyLabels,
                datasets: [{
                    label: 'Beneficiaries Reached',
                    data: beneficiariesData,
                    borderColor: 'rgba(16, 185, 129, 1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>