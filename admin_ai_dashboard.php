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
    <title>AI Score Card Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
    </style>
</head>


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
            
            <li><a href= "admin_report_ai_dashboard.php" class="nav-link" data-section="quickfacts-section">
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
                    <p class="text-gray-600">50th Week Performance Analysis</p>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" placeholder="Search centers..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                    <div class="flex items-center space-x-2 bg-white px-3 py-2 rounded-lg shadow-sm">
                        <span class="text-gray-600">Week:</span>
                        <span class="font-semibold">50</span>
                    </div>
                    <button class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                        <i class="fas fa-download mr-2"></i>Export
                    </button>
                </div>
            </div>
        </header>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Total Centers</p>
                        <h3 class="text-2xl font-bold text-indigo-600">12</h3>
                    </div>
                    <div class="bg-indigo-100 p-3 rounded-full">
                        <i class="fas fa-university text-indigo-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Active</span>
                        <span>9/12</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill bg-indigo-600" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Total AI Services</p>
                        <h3 class="text-2xl font-bold text-blue-600">12,464</h3>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <i class="fas fa-robot text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Weekly Target</span>
                        <span>214/214</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill bg-blue-600" style="width: 100%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">AI Report %</p>
                        <h3 class="text-2xl font-bold text-green-600">80.97%</h3>
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
                        <div class="progress-fill bg-green-600" style="width: 80.97%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-gray-500 font-medium">Avg. Accomplishment</p>
                        <h3 class="text-2xl font-bold text-purple-600">66.8%</h3>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <i class="fas fa-trophy text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex justify-between text-sm text-gray-500">
                        <span>Top Performer</span>
                        <span>VSU (95.1%)</span>
                    </div>
                    <div class="progress-bar mt-1">
                        <div class="progress-fill bg-purple-600" style="width: 66.8%"></div>
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
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">AI Services</button>
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
                        <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Top 5</button>
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
                <div class="flex space-x-2">
                    <button class="px-3 py-1 text-sm bg-indigo-100 text-indigo-700 rounded-md">All</button>
                    <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Active</button>
                    <button class="px-3 py-1 text-sm bg-gray-100 text-gray-700 rounded-md">Inactive</button>
                </div>
            </div>
            <div class="data-table">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Center</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weekly Target</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total AI</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Perf</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rating</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Accomplishment</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">AI Report %</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">CLSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">30</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,066</td>
                            <td class="px-6 py-4 whitespace-nowrap">30</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-green-100 text-green-800">A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">90.7%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-green-500 rounded-full" style="width: 90.7%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">90.30%</td>
                            <td class="px-6 py-4 whitespace-nowrap">2,131</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">CSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">60</td>
                            <td class="px-6 py-4 whitespace-nowrap">2,530</td>
                            <td class="px-6 py-4 whitespace-nowrap">60</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-blue-100 text-blue-800">B</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">80.1%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-blue-500 rounded-full" style="width: 80.1%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">60.88%</td>
                            <td class="px-6 py-4 whitespace-nowrap">5,061</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">UPLB</td>
                            <td class="px-6 py-4 whitespace-nowrap">0</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,255</td>
                            <td class="px-6 py-4 whitespace-nowrap">X</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-red-100 text-red-800">D</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">35.4%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-red-500 rounded-full" style="width: 35.4%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">74.33%</td>
                            <td class="px-6 py-4 whitespace-nowrap">2,509</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">WVSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">0</td>
                            <td class="px-6 py-4 whitespace-nowrap">888</td>
                            <td class="px-6 py-4 whitespace-nowrap">X</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-red-100 text-red-800">D</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">46.7%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-red-500 rounded-full" style="width: 46.7%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">91.31%</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,776</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">LCSF</td>
                            <td class="px-6 py-4 whitespace-nowrap">0</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,737</td>
                            <td class="px-6 py-4 whitespace-nowrap">X</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-yellow-100 text-yellow-800">C</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">66.3%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-yellow-500 rounded-full" style="width: 66.3%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">103.90%</td>
                            <td class="px-6 py-4 whitespace-nowrap">3,474</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">USF</td>
                            <td class="px-6 py-4 whitespace-nowrap">0</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,160</td>
                            <td class="px-6 py-4 whitespace-nowrap">X</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-red-100 text-red-800">D</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">46.3%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-red-500 rounded-full" style="width: 46.3%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">68.66%</td>
                            <td class="px-6 py-4 whitespace-nowrap">2,319</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">DMMMSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">0</td>
                            <td class="px-6 py-4 whitespace-nowrap">526</td>
                            <td class="px-6 py-4 whitespace-nowrap">X</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-red-100 text-red-800">D</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">41.1%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-red-500 rounded-full" style="width: 41.1%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">80.76%</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,052</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">VSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">21</td>
                            <td class="px-6 py-4 whitespace-nowrap">908</td>
                            <td class="px-6 py-4 whitespace-nowrap">21</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-green-100 text-green-800">A+</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">95.1%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-green-500 rounded-full" style="width: 95.1%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">91.59%</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,816</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">CMU</td>
                            <td class="px-6 py-4 whitespace-nowrap">55</td>
                            <td class="px-6 py-4 whitespace-nowrap">835</td>
                            <td class="px-6 py-4 whitespace-nowrap">55</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-blue-100 text-blue-800">B</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">61.4%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-blue-500 rounded-full" style="width: 61.4%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">69.29%</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,669</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">USM</td>
                            <td class="px-6 py-4 whitespace-nowrap">25</td>
                            <td class="px-6 py-4 whitespace-nowrap">495</td>
                            <td class="px-6 py-4 whitespace-nowrap">25</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-blue-100 text-blue-800">B</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">69.1%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-blue-500 rounded-full" style="width: 69.1%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">86.43%</td>
                            <td class="px-6 py-4 whitespace-nowrap">990</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">MMSU</td>
                            <td class="px-6 py-4 whitespace-nowrap">5</td>
                            <td class="px-6 py-4 whitespace-nowrap">641</td>
                            <td class="px-6 py-4 whitespace-nowrap">5</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-green-100 text-green-800">A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">100.0%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-green-500 rounded-full" style="width: 100%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">67.98%</td>
                            <td class="px-6 py-4 whitespace-nowrap">1,281</td>
                        </tr>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">MLPC</td>
                            <td class="px-6 py-4 whitespace-nowrap">18</td>
                            <td class="px-6 py-4 whitespace-nowrap">426</td>
                            <td class="px-6 py-4 whitespace-nowrap">18</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="rating-cell bg-blue-100 text-blue-800">B</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <span class="mr-2">69.5%</span>
                                    <div class="w-20 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-blue-500 rounded-full" style="width: 69.5%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">79.74%</td>
                            <td class="px-6 py-4 whitespace-nowrap">851</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex items-center mb-4">
                    <div class="bg-indigo-100 p-3 rounded-full mr-4">
                        <i class="fas fa-bullseye text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Team Target</p>
                        <h3 class="text-2xl font-bold text-indigo-600">90.0%</h3>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-indigo-600 h-2.5 rounded-full" style="width: 90%"></div>
                    </div>
                    <span class="ml-4 text-gray-600">80,851</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex items-center mb-4">
                    <div class="bg-green-100 p-3 rounded-full mr-4">
                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Actual Achievement</p>
                        <h3 class="text-2xl font-bold text-green-600">80.97%</h3>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-green-600 h-2.5 rounded-full" style="width: 80.97%"></div>
                    </div>
                    <span class="ml-4 text-gray-600">72,737</span>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                <div class="flex items-center mb-4">
                    <div class="bg-red-100 p-3 rounded-full mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                    </div>
                    <div>
                        <p class="text-gray-500 font-medium">Balance to Target</p>
                        <h3 class="text-2xl font-bold text-red-600">9.03%</h3>
                    </div>
                </div>
                <div class="flex justify-between items-center">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="bg-red-600 h-2.5 rounded-full" style="width: 9.03%"></div>
                    </div>
                    <span class="ml-4 text-gray-600">17,097</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 pt-8 border-t border-gray-200">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <p class="text-gray-600">© 2023 AI Score Card Dashboard. All rights reserved.</p>
                </div>
                <div class="flex space-x-6">
                    <a href="#" class="text-gray-500 hover:text-indigo-600">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-indigo-600">
                        <i class="fab fa-facebook"></i>
                    </a>
                    <a href="#" class="text-gray-500 hover:text-indigo-600">
                        <i class="fab fa-linkedin"></i>
                    </a>
                </div>
            </div>
        </footer>
    </div>

    <script>
        // Bar Chart - Center Performance
        const barCtx = document.getElementById('barChart').getContext('2d');
        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: ['CLSU', 'CSU', 'UPLB', 'WVSU', 'LCSF', 'USF', 'DMMMSU', 'VSU', 'CMU', 'USM', 'MMSU', 'MLPC'],
                datasets: [{
                    label: '% Accomplishment',
                    data: [90.7, 80.1, 35.4, 46.7, 66.3, 46.3, 41.1, 95.1, 61.4, 69.1, 100, 69.5],
                    backgroundColor: [
                        'rgba(79, 70, 229, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(234, 179, 8, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(239, 68, 68, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(59, 130, 246, 0.7)',
                        'rgba(16, 185, 129, 0.7)',
                        'rgba(59, 130, 246, 0.7)'
                    ],
                    borderColor: [
                        'rgba(79, 70, 229, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(234, 179, 8, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(239, 68, 68, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(16, 185, 129, 1)',
                        'rgba(59, 130, 246, 1)'
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
                                return `${context.dataset.label}: ${context.raw}%`;
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

        // Line Chart - Target vs Actual
        const lineCtx = document.getElementById('lineChart').getContext('2d');
        const lineChart = new Chart(lineCtx, {
            type: 'bar',
            data: {
                labels: ['CLSU', 'CSU', 'UPLB', 'WVSU', 'LCSF', 'USF', 'DMMMSU', 'VSU', 'CMU', 'USM', 'MMSU', 'MLPC'],
                datasets: [
                    {
                        label: 'Target AI Reports',
                        data: [14500, 11471, 9775, 9500, 8453, 7400, 5467, 5434, 5434, 4200, 4000, 4200],
                        backgroundColor: 'rgba(79, 70, 229, 0.5)',
                        borderColor: 'rgba(79, 70, 229, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Actual AI Reports',
                        data: [13094, 6984, 7266, 8674, 8783, 5081, 4415, 4977, 3765, 3630, 2719, 3349],
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgba(16, 185, 129, 1)',
                        borderWidth: 1
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
                });x
            };
            
            // Initial check
            fadeInOnScroll();
            
            // Check on scroll
            window.addEventListener('scroll', fadeInOnScroll);
        });
    </script>
</body>
</html>