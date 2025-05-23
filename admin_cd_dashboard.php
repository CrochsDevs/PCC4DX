<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// I-require ang mga necessary files
require 'db_config.php';
require 'auth_check.php';

// I-check ang user privileges
if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calf Production Dashboard</title>
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

<div class="sidebar">
        <!-- User Profile Section -->
        <div class="user-profile" id="sidebar-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <!-- Display the uploaded profile image -->
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture" id="sidebar-profile-img">
                <?php else: ?>
                    <!-- Fallback to the generated avatar -->
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

            <li><a class="nav-link" data-section="announcement-section">
                <i class="fas fa-file-alt"></i> Center</a></li>
            
            <li><a href="admin_report_calf_dashboard.php" class="nav-link" data-section="quickfacts-section">
                <i class="fas fa-sitemap"></i> Reports</a></li>
        </ul>

    </div>

</head>
<body class="bg-gray-50 font-sans">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="mb-8">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Calf Production Dashboard</h1>
                    <p class="text-gray-600">50th Week Performance (04-May-2025)</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="bg-white p-3 rounded-lg shadow-sm flex items-center">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>
                        <span>Week 50, 2025</span>
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
                        <h2 class="text-3xl font-bold mt-2">30,804</h2>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-cow text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Target: 33,660</span>
                        <span class="font-semibold">91.52%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-green-500" style="width: 91.52%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Weekly Target</p>
                        <h2 class="text-3xl font-bold mt-2">31,977</h2>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-bullseye text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Completion</span>
                        <span class="font-semibold">95.00%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-purple-500" style="width: 95%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Balance</p>
                        <h2 class="text-3xl font-bold mt-2">1,173</h2>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <i class="fas fa-scale-balanced text-yellow-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Remaining Target</span>
                        <span class="font-semibold">3.48%</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-yellow-500" style="width: 3.48%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 font-medium">Average Completion</p>
                        <h2 class="text-3xl font-bold mt-2">55.92%</h2>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <i class="fas fa-chart-line text-red-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm">
                        <span>Performance</span>
                        <span class="font-semibold">6.86% Weekly</span>
                    </div>
                    <div class="progress-bar bg-gray-200 mt-2">
                        <div class="progress-fill bg-blue-500" style="width: 55.92%"></div>
                    </div>
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
                        <!-- Data rows will be inserted here by JavaScript -->
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-50 flex items-center justify-between border-t border-gray-200">
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Showing 1 to 14 of 14 entries</span>
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 border rounded-md text-sm disabled:opacity-50" disabled>Previous</button>
                    <button class="px-3 py-1 border rounded-md text-sm bg-blue-500 text-white">1</button>
                    <button class="px-3 py-1 border rounded-md text-sm disabled:opacity-50" disabled>Next</button>
                </div>
            </div>
        </div>

        <!-- Performance Indicators -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Top Performing Centers</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">WVSU</span>
                            <span class="text-sm font-medium">99.51%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-green-500" style="width: 99.51%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">CLSU</span>
                            <span class="text-sm font-medium">98.61%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-green-500" style="width: 98.61%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">UPLB</span>
                            <span class="text-sm font-medium">91.34%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-blue-500" style="width: 91.34%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm p-6 card-hover transition fade-in">
                <h3 class="text-lg font-semibold mb-4">Centers Needing Improvement</h3>
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">NIZ</span>
                            <span class="text-sm font-medium">2.44%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-red-500" style="width: 2.44%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">GP</span>
                            <span class="text-sm font-medium">5.28%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-red-500" style="width: 5.28%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between mb-1">
                            <span class="text-sm font-medium">CMU</span>
                            <span class="text-sm font-medium">59.76%</span>
                        </div>
                        <div class="progress-bar bg-gray-200">
                            <div class="progress-fill bg-yellow-500" style="width: 59.76%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data for the dashboard
        const centers = [
            { name: "CLSU", wigTarget: 53, mon: 25, tue: 25, wed: 0, thu: 0, fri: 0, rating: "47.62%", cta: "90.65%", target: 7550, actual: 7445, percent: "98.61%" },
            { name: "CSU", wigTarget: 512, mon: 15, tue: 15, wed: 0, thu: 0, fri: 0, rating: "2.93%", cta: "78.05%", target: 3000, actual: 1977, percent: "65.90%" },
            { name: "UPLB", wigTarget: 152, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "34.55%", target: 3500, actual: 3197, percent: "91.34%" },
            { name: "WVSU", wigTarget: 11, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "46.75%", target: 4500, actual: 4478, percent: "99.51%" },
            { name: "USF", wigTarget: 67, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "48.78%", target: 2600, actual: 2467, percent: "94.88%" },
            { name: "LCSF", wigTarget: 114, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "66.26%", target: 2650, actual: 2422, percent: "91.40%" },
            { name: "DMMMSU", wigTarget: 114, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "38.21%", target: 1700, actual: 1472, percent: "86.59%" },
            { name: "MMSU", wigTarget: -123, mon: 2, tue: 2, wed: 0, thu: 0, fri: 0, rating: "-1.63%", cta: "99.59%", target: 1850, actual: 2095, percent: "113.24%" },
            { name: "VSU", wigTarget: 110, mon: 40, tue: 40, wed: 0, thu: 0, fri: 0, rating: "36.36%", cta: "94.72%", target: 1750, actual: 1530, percent: "87.43%" },
            { name: "CMU", wigTarget: 173, mon: 2, tue: 2, wed: 0, thu: 0, fri: 0, rating: "1.16%", cta: "59.76%", target: 1200, actual: 855, percent: "71.25%" },
            { name: "USM", wigTarget: 74, mon: 8, tue: 8, wed: 0, thu: 0, fri: 0, rating: "10.81%", cta: "63.41%", target: 1200, actual: 1052, percent: "87.67%" },
            { name: "MLPC", wigTarget: 24, mon: 6, tue: 6, wed: 0, thu: 0, fri: 0, rating: "25.00%", cta: "54.47%", target: 1200, actual: 1152, percent: "96.00%" },
            { name: "NIZ", wigTarget: 156, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "2.44%", target: 840, actual: 528, percent: "62.86%" },
            { name: "GP", wigTarget: -7, mon: 0, tue: "X", wed: 0, thu: 0, fri: 0, rating: "0.00%", cta: "5.28%", target: 120, actual: 134, percent: "111.67%" }
        ];

        // Populate table
        const tableBody = document.querySelector('tbody');
        centers.forEach(center => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap font-medium">${center.name}</td>
                <td class="px-6 py-4 whitespace-nowrap">${center.wigTarget}</td>
                <td class="px-6 py-4 whitespace-nowrap ${center.mon === 0 ? 'text-gray-400' : ''}">${center.mon}</td>
                <td class="px-6 py-4 whitespace-nowrap ${center.tue === 0 || center.tue === "X" ? 'text-gray-400' : ''}">${center.tue}</td>
                <td class="px-6 py-4 whitespace-nowrap ${center.wed === 0 ? 'text-gray-400' : ''}">${center.wed}</td>
                <td class="px-6 py-4 whitespace-nowrap ${center.thu === 0 ? 'text-gray-400' : ''}">${center.thu}</td>
                <td class="px-6 py-4 whitespace-nowrap ${center.fri === 0 ? 'text-gray-400' : ''}">${center.fri}</td>
                <td class="px-6 py-4 whitespace-nowrap">${center.rating}</td>
                <td class="px-6 py-4 whitespace-nowrap">${center.cta}</td>
                <td class="px-6 py-4 whitespace-nowrap">${center.target.toLocaleString()}</td>
                <td class="px-6 py-4 whitespace-nowrap">${center.actual.toLocaleString()}</td>
                <td class="px-6 py-4 whitespace-nowrap font-semibold ${parseFloat(center.percent) > 90 ? 'text-green-600' : parseFloat(center.percent) < 70 ? 'text-red-600' : 'text-yellow-600'}">
                    ${center.percent}
                </td>
            `;
            
            tableBody.appendChild(row);
        });

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
                                return value.toLocaleString(); // Format numbers with commas
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