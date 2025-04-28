<?php
session_start();
require 'auth_check.php';
require 'db_config.php'; // Include the file where PDO connection is established

if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['user']['center_code'];


class DatabaseHandler {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function getMilkProduction($centerCode) {
        $query = "
            SELECT 
                mp.id,
                mp.week,
                mp.entry_date,
                mp.end_date,   -- Adjusted column name to 'end_date'
                mp.quantity,
                mp.volume,
                mp.total,
                mp.partner_id,
                mp.center_code,
                mp.created_at,
                mp.status,
                p.partner_name
            FROM 
                pcc_auth_system.milk_production mp
            JOIN 
                pcc_auth_system.partners p ON mp.partner_id = p.id
            WHERE 
                mp.center_code = :centerCode
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $centerCode, PDO::PARAM_STR);
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $result;
    }
    
    public function getPartners() {
        $query = "SELECT id, partner_name FROM pcc_auth_system.partners";
        $result = $this->conn->query($query);
        
        $partners = [];
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $partners[$row['id']] = $row['partner_name'];
        }
        
        return $partners;
    }
    
    public function getProductionSummary($centerCode) {
        $query = "SELECT 
                    SUM(quantity) as total_quantity,
                    SUM(volume) as total_volume,
                    SUM(total) as total_value,
                    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_count
                  FROM pcc_auth_system.milk_production 
                  WHERE center_code = :centerCode";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':centerCode', $centerCode, PDO::PARAM_STR);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function close() {
        $this->conn = null;
    }
}

// Initialize database handler with the PDO connection
$dbHandler = new DatabaseHandler($conn);

// Get data
$milkProduction = $dbHandler->getMilkProduction($centerCode);
$partners = $dbHandler->getPartners();
$summary = $dbHandler->getProductionSummary($centerCode);

$dbHandler->close();

// Calculate percentage changes (replace with actual logic for percentage calculation)
$quantityChange = "+100%";
$volumeChange = "+46.7%";
$valueChange = "+293.3%";
$pendingChange = "+100%";

// Format summary data
$totalQuantity = isset($summary['total_quantity']) ? number_format($summary['total_quantity'], 2) : '0';
$totalVolume = isset($summary['total_volume']) ? 'L ' . number_format($summary['total_volume'], 2) : '0';
$totalValue = isset($summary['total_value']) ? '₱' . number_format($summary['total_value'], 2) : '$0';
$pendingCount = isset($summary['pending_count']) ? $summary['pending_count'] : '0';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Report Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        .animate-pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-indigo-700 text-white shadow-lg">
            <div class="container mx-auto px-4 py-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold">Monthly Report Dashboard</h1>
                        <p class="text-indigo-200">Track and analyze your monthly performance</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div class="relative">
                            <select id="month-select" class="bg-indigo-600 text-white px-4 py-2 rounded-lg appearance-none pr-8 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                                <option value="2025-04">April 2025</option>
                                <option value="2025-03">March 2025</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                                <i class="fas fa-chevron-down text-indigo-300"></i>
                            </div>
                        </div>
                        <button class="bg-white text-indigo-700 px-4 py-2 rounded-lg font-medium hover:bg-indigo-50 transition-colors">
                            <i class="fas fa-download mr-2"></i> Export
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="container mx-auto px-4 py-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Quantity</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $totalQuantity; ?></h3>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-boxes text-blue-500 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4"><span class="text-green-500 font-medium"><?php echo $quantityChange; ?></span> vs last month</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Volume</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $totalVolume; ?></h3>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-weight-hanging text-green-500 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4"><span class="text-green-500 font-medium"><?php echo $volumeChange; ?></span> vs last month</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Value</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $totalValue; ?></h3>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-peso-sign text-purple-500 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4"><span class="text-green-500 font-medium"><?php echo $valueChange; ?></span> vs last month</p>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-yellow-500">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Pending Records</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $pendingCount; ?></h3>
                        </div>
                        <div class="bg-yellow-100 p-3 rounded-full">
                            <i class="fas fa-clock text-yellow-500 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mt-4"><span class="text-green-500 font-medium"><?php echo $pendingChange; ?></span> vs last month</p>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg">Monthly Performance</h3>
                        <div class="flex space-x-2">
                            <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-lg">Quantity</button>
                            <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg">Volume</button>
                            <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-lg">Value</button>
                        </div>
                    </div>
                    <div class="h-64">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-md p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-lg">Partner Distribution</h3>
                        <div class="text-sm text-gray-500">By Total Value</div>
                    </div>
                    <div class="h-64">
                        <canvas id="partnerChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Data Table Section -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="font-bold text-lg">Transaction Details</h3>
                    <div class="relative">
                        <input type="text" placeholder="Search transactions..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>
                
                <div class="overflow-x-auto custom-scrollbar">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Week</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entry Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Volume</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Partner</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Center</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($milkProduction as $record): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['id']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['week']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['entry_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['emd_date']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($record['quantity'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo number_format($record['volume'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">$<?php echo number_format($record['total'], 2); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo isset($partners[$record['partner_id']]) ? htmlspecialchars($partners[$record['partner_id']]) : 'Unknown'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($record['center_code']); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $record['status'] === 'Pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'; ?>">
                                        <?php echo htmlspecialchars($record['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-3"><i class="fas fa-eye"></i></button>
                                    <button class="text-green-600 hover:text-green-900"><i class="fas fa-edit"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-200">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <button class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Previous</button>
                        <button class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">Next</button>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700">
                                Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($milkProduction); ?></span> of <span class="font-medium"><?php echo count($milkProduction); ?></span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <button class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button aria-current="page" class="z-10 bg-indigo-50 border-indigo-500 text-indigo-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">1</button>
                                <button class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">2</button>
                                <button class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </main>


    </div>

    <script>
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Performance Chart
            const performanceCtx = document.getElementById('performanceChart').getContext('2d');
            const performanceChart = new Chart(performanceCtx, {
                type: 'bar',
                data: {
                    labels: ['March 2025', 'April 2025'],
                    datasets: [
                        {
                            label: 'Quantity',
                            data: [500, 1000],
                            backgroundColor: 'rgba(79, 70, 229, 0.7)',
                            borderColor: 'rgba(79, 70, 229, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Volume',
                            data: [60, 88],
                            backgroundColor: 'rgba(16, 185, 129, 0.7)',
                            borderColor: 'rgba(16, 185, 129, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.datasetIndex === 0) {
                                        label += context.raw.toLocaleString();
                                    } else {
                                        label += context.raw.toLocaleString(undefined, {maximumFractionDigits: 2});
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });

            // Partner Distribution Chart
            const partnerCtx = document.getElementById('partnerChart').getContext('2d');
            const partnerChart = new Chart(partnerCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Partner 445', 'Partner 448'],
                    datasets: [{
                        data: [30000, 88000],
                        backgroundColor: [
                            'rgba(99, 102, 241, 0.7)',
                            'rgba(168, 85, 247, 0.7)'
                        ],
                        borderColor: [
                            'rgba(99, 102, 241, 1)',
                            'rgba(168, 85, 247, 1)'
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
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: $${value.toLocaleString()} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Month selector functionality
            document.getElementById('month-select').addEventListener('change', function() {
                // In a real app, this would fetch new data for the selected month
                console.log('Selected month:', this.value);
                // Show loading state
                document.querySelectorAll('.animate-pulse').forEach(el => el.classList.remove('hidden'));
                // Simulate data loading
                setTimeout(() => {
                    document.querySelectorAll('.animate-pulse').forEach(el => el.classList.add('hidden'));
                    // Update charts with new data
                    performanceChart.data.labels = ['March 2025', 'April 2025'];
                    performanceChart.update();
                    partnerChart.update();
                }, 1000);
            });
        });
    </script>
</body>
</html>