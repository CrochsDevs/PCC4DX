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

// --- Fetch Dashboard Data with PDO ---

// Get AI Services total (colored part)
$aiStmt = $conn->prepare("SELECT SUM(aiServices) AS total_ai FROM ai_services");
$aiStmt->execute();
$aiTotal = $aiStmt->fetch(PDO::FETCH_ASSOC)['total_ai'] ?? 0;

// Get AI Target total (gray part)
$aiTargetStmt = $conn->prepare("SELECT SUM(target) AS target_ai FROM ai_target");
$aiTargetStmt->execute();
$aiTarget = $aiTargetStmt->fetch(PDO::FETCH_ASSOC)['target_ai'] ?? 0;

// Get Calf Drop totals (colored part)
$calfStmt = $conn->prepare("SELECT SUM(ai + bep + ih + private) AS total_calf FROM calf_drop");
$calfStmt->execute();
$calfTotal = $calfStmt->fetch(PDO::FETCH_ASSOC)['total_calf'] ?? 0;

// Get Calf Drop Target (gray part)
$calfTargetStmt = $conn->prepare("SELECT SUM(target) AS target_calf FROM cd_target");
$calfTargetStmt->execute();
$calfTarget = $calfTargetStmt->fetch(PDO::FETCH_ASSOC)['target_calf'] ?? 0;

// Calculate remaining (avoid negatives)
$aiRemaining = max($aiTarget - $aiTotal, 0);
$calfRemaining = max($calfTarget - $calfTotal, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCC Headquarters Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin.css">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '#0056b3',
                        secondary: '#6c757d',
                        success: '#28a745',
                        danger: '#dc3545',
                        warning: '#ffc107',
                        info: '#17a2b8',
                        dark: '#343a40',
                        light: '#f8f9fa',
                    }
                }
            }
        }
    </script>
    <style>
        /* Quick Facts Section Styles */
        .quickfacts-container {
            padding: 20px;
            margin-top: 20px;
        }

        .quickfacts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .quickfact-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
        }

        .quickfact-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .quickfact-link {
            display: block;
            padding: 25px;
            color: inherit;
            text-decoration: none;
        }

        .quickfact-content {
            padding: 25px;
        }

        .quickfact-icon {
            font-size: 2rem;
            color: #0056b3;
            margin-bottom: 15px;
        }

        .quickfact-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #2d3748;
        }

        .quickfact-desc {
            color: #4a5568;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Service Status Styles */
        .active-service {
            border-left: 4px solid #0056b3;
        }

        .disabled-service {
            opacity: 0.7;
            background-color: #f8f9fa;
            border-left: 4px solid #6c757d;
        }

        .disabled-service .quickfact-icon {
            color: #6c757d;
        }

        .disabled-service .quickfact-title,
        .disabled-service .quickfact-desc {
            color: #6c757d;
        }

        .development-badge {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            background-color: #ffc107;
            color: #212529;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center; 
        }

        /* Dark mode styles */
        .dark .quickfact-card {
            background: #1e293b;
        }

        .dark .quickfact-title {
            color: #f8fafc;
        }

        .dark .quickfact-desc {
            color: #94a3b8;
        }

        .dark .disabled-service {
            background-color: #334155;
            border-left: 4px solid #64748b;
        }

        .dark .disabled-service .quickfact-icon,
        .dark .disabled-service .quickfact-title,
        .dark .disabled-service .quickfact-desc {
            color: #94a3b8;
        }

        /* Theme toggle switch */
        .theme-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .theme-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #0056b3;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .theme-toggle-card {
            cursor: pointer;
        }

        .theme-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .quickfacts-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900">
    
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
            <li><a class="nav-link active" data-section="dashboard-section">
                <i class="fas fa-tachometer-alt"></i> Dashboard</a></li>

            <li><a class="nav-link" data-section="announcement-section">
                <i class="fas fa-bullhorn"></i> Announcement</a></li>
            
            <li><a class="nav-link" data-section="quickfacts-section">
                <i class="fas fa-sitemap"></i> Quick Facts</a></li>


            <li><a class="nav-link" data-section="programs-section">
                <i class="fas fa-user"></i> Coordinators</a></li>

            <li><a class="nav-link" data-section="settings-section">
                <i class="fas fa-cog"></i> Settings</a></li>
        </ul>

    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header bg-gray-100 dark:bg-gray-800 p-4">
            <div class="header-left">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white flex items-center">
            <i class="fas fa-user-shield mr-2"></i> Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> (NHQ Admin)</h1>
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
                
                <a href="logout.php" class="logout-btn" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <h2 class="dashboard-title"><i class="fas fa-chart-line"></i> Performance Dashboard</h2>
            <p class="dashboard-description">Monitor and manage all PCC Headquarters operations. Track key metrics and performance indicators to ensure efficient service delivery.</p>
            
            <div class="dashboard-grid">
                <!-- AI Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-users"></i> Artificial Insemination</h3>
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual"><?= $aiTotal ?></span>
                            <span class="target">Target: <?= $aiTarget ?></span>
                        </div>
                    </div>
                </div>

                <!-- Calf-Drop Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-paw"></i> Calf Drop</h3>
                    <div class="chart-container">
                        <canvas id="carabaosChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual"><?= $calfTotal ?></span>
                            <span class="target">Target: <?= $calfTarget ?></span>
                        </div>
                    </div>
                </div>

                <!-- Milk Production Card (disabled) -->
                <div class="dashboard-card" style="opacity: 0.5; pointer-events: none;">
                    <h3 class="card-title"><i class="fas fa-check-circle"></i>Milk Production</h3>
                    <div class="chart-container">
                        <canvas id="servicesChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">N/A</span>
                            <span class="target">N/A</span>
                        </div>
                    </div>
                </div>

                <!-- Milk Feeding Card (disabled) -->
                <div class="dashboard-card" style="opacity: 0.5; pointer-events: none;">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Milk Feeding</h3>
                    <div class="chart-container">
                        <canvas id="requestsChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">N/A</span>
                            <span class="target">N/A</span>
                        </div>
                    </div>
                </div>

                    <!-- Dairy Box Card (disabled) -->
                <div class="dashboard-card" style="opacity: 0.5; pointer-events: none;">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Dairy Box</h3>
                    <div class="chart-container">
                        <canvas id="requestsChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">N/A</span>
                            <span class="target">N/A</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
     
        

                <!-- Programs Section -->
        <div id="programs-section" class="content-section">
            <div class="container mt-5">
                <h2 class="dashboard-title"><i class="fas fa-user-friends"></i> Programs</h2>
                <div class="mt-4 mb-3">
                    <!-- Programs management -->
                    <a href="create_program.php" class="btn btn-success">Add Program</a>
                </div>

                <!-- Programs List -->
                <h3 class="mt-4">Program Profiles</h3>
                <table class="table table-striped mt-3">
                    <thead>
                        <tr>
                            <th>Profile</th>
                            <th>Name</th>
                            <th>Title</th>
                            <th>Date Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        include 'db_config.php';

                        try {
                            $stmt = $conn->prepare("SELECT * FROM programs ORDER BY created_at DESC");
                            $stmt->execute();

                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                $profileImage = $row['profile_image'] ? 'uploads/programs/' . htmlspecialchars($row['profile_image']) : 'images/default-profile.png';
                                echo "<tr>";
                                echo "<td><img src='" . $profileImage . "' style='width: 60px; height: 60px; object-fit: cover; border-radius: 50%;'></td>";
                                echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                echo "<td>" . htmlspecialchars(date('F j, Y', strtotime($row['created_at']))) . "</td>";
                                echo "<td>
                                        <a href='edit-program.php?id=" . $row['id'] . "' class='btn btn-warning btn-sm me-2'>Edit</a>
                                        <a href='delete-program.php?id=" . $row['id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this program profile?\")'>Delete</a>
                                    </td>";
                                echo "</tr>";
                            }
                        } catch (PDOException $e) {
                            echo "<tr><td colspan='5' class='text-danger'>Error fetching programs: " . $e->getMessage() . "</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

                            
 <!-- Quick Facts Section -->
<div id="quickfacts-section" class="content-section">
    <h2 class="dashboard-title"><i class="fas fa-sitemap"></i> Quick Facts</h2>
    <p class="dashboard-description">Access quick information and resources about PCC services.</p>

    <div class="quickfacts-container">
        <div class="quickfacts-grid">
            <!-- Active Services -->
            <div class="quickfact-card active-service">
                <a href="admin_ai_dashboard.php" class="quickfact-link">
                    <div class="quickfact-icon">
                        <i class="fas fa-syringe"></i>
                    </div>
                    <h3 class="quickfact-title">Artificial Insemination</h3>
                    <p class="quickfact-desc">Report on artificial insemination services for carabaos.</p>
                </a>
            </div>

            <div class="quickfact-card active-service">
                <a href="admin_cd_dashboard.php" class="quickfact-link">
                    <div class="quickfact-icon">
                        <i class="fas fa-cow"></i>
                    </div>
                    <h3 class="quickfact-title">Calf Drop</h3>
                    <p class="quickfact-desc">Report on successful births and calf health monitoring programs.</p>
                </a>
            </div>

            <!-- Under Development Services -->
            <div class="quickfact-card disabled-service">
                <div class="quickfact-content">
                    <div class="quickfact-icon">
                        <i class="fas fa-bottle-droplet"></i>
                    </div>
                    <h3 class="quickfact-title">Milk Feeding</h3>
                    <p class="quickfact-desc">Report on milk feeding programs and nutritional supplements for calves.</p>
                    <span class="development-badge">Under Development</span>
                </div>
            </div>

            <div class="quickfact-card disabled-service">
                <div class="quickfact-content">
                    <div class="quickfact-icon">
                        <i class="fas fa-bottle-water"></i>
                    </div>
                    <h3 class="quickfact-title">Milk Production</h3>
                    <p class="quickfact-desc">Report on carabao milk production metrics and quality.</p>
                    <span class="development-badge">Under Development</span>
                </div>
            </div>

            <div class="quickfact-card disabled-service">
                <div class="quickfact-content">
                    <div class="quickfact-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3 class="quickfact-title">Dairy Box</h3>
                    <p class="quickfact-desc">Report on Dairy Box hubs supporting farmers through milk marketing.</p>
                    <span class="development-badge">Under Development</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Announcements Section -->
<div id="announcement-section" class="content-section">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col">
            <!-- Header with title and action button -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 dark:text-white">
                        <i class="fas fa-bullhorn mr-2 text-blue-600"></i> Announcements
                    </h2>
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        Create and manage important announcements for all users
                    </p>
                </div>
                <a href="create_announcements.php" 
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                    <i class="fas fa-plus mr-2"></i> New Announcement
                </a>
            </div>

            <!-- Announcement List -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">Recent Announcements</h3>
                    <div class="relative">
                        <select class="block appearance-none bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 py-2 px-4 pr-8 rounded leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            <option>Sort by: Newest</option>
                            <option>Sort by: Oldest</option>
                            <option>Sort by: Title</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700 dark:text-gray-300">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    include 'db_config.php';

                    try {
                        $stmt = $conn->prepare("SELECT * FROM announcement ORDER BY created_at DESC");
                        $stmt->execute();

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo '
                            <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-1">' . htmlspecialchars($row['title']) . '</h4>
                                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                            <i class="far fa-clock mr-1"></i> ' . date('F j, Y \a\t g:i A', strtotime($row['created_at'])) . '
                                        </p>
                                        <p class="text-gray-600 dark:text-gray-300 line-clamp-2">
                                            ' . (!empty($row['content']) ? htmlspecialchars(substr($row['content'], 0, 150)) . '...' : 'No content provided') . '
                                        </p>
                                    </div>
                                    <div class="ml-4 flex-shrink-0 flex space-x-2">
                                        <a href="edit-announcement.php?announcement_id=' . $row['announcement_id'] . '" 
                                        class="inline-flex items-center px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-150">
                                            <i class="fas fa-pencil-alt mr-1 text-sm"></i> Edit
                                        </a>
                                        <a href="delete-announcement.php?announcement_id=' . $row['announcement_id'] . '" 
                                        onclick="return confirm(\'Are you sure you want to delete this announcement?\')"
                                        class="inline-flex items-center px-3 py-1 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-150">
                                            <i class="fas fa-trash mr-1 text-sm"></i> Delete
                                        </a>
                                    </div>
                                </div>
                            </div>';
                        }
                    } catch (PDOException $e) {
                        echo '
                        <div class="p-6">
                            <div class="bg-red-50 dark:bg-red-900/20 border-l-4 border-red-500 p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-500 dark:text-red-300"></i>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700 dark:text-red-300">
                                            Error fetching announcements: ' . $e->getMessage() . '
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>';
                    }
                    ?>
                </div>
                
                <!-- Pagination -->
                <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3 flex items-center justify-between border-t border-gray-200 dark:border-gray-600">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Previous
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                            Next
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">20</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <span class="sr-only">Previous</span>
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-blue-50 dark:bg-blue-900/30 border-blue-500 dark:border-blue-700 text-blue-600 dark:text-blue-300 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </a>
                                <a href="#" class="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-sm font-medium text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <span class="sr-only">Next</span>
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- end max-w-full -->
</div> <!-- end #announcement-section -->

        
       <!-- Settings Section -->
        <div id="settings-section" class="content-section">
            <h2 class="dashboard-title"><i class="fas fa-cogs"></i> Settings</h2>
            <p class="dashboard-description">Configure system settings and user preferences.</p>
            
            <div class="settings-grid">
                <div class="dashboard-card">
                    <a href="update_profile.php" class="card-link">    
                        <h3 class="card-title"><i class="fas fa-user-cog"></i> Account Settings</h3>
                        <p>Update your account information and password.</p>
                    </a>
                </div>

                <div class="dashboard-card">
                    <a href="update_password.php" class="card-link">
                        <h3 class="card-title"><i class="fas fa-lock"></i> Password and Security</h3>
                        <p>Update your account password.</p>
                    </a>
                </div>

                <div class="dashboard-card">
                    <div class="card-link theme-toggle-card">
                        <div class="theme-header">
                            <h3 class="card-title">
                                <i class="fas" id="theme-icon"></i> 
                                Appearance Settings
                            </h3>
                            <label class="theme-switch">
                                <input type="checkbox" id="theme-toggle">
                                <span class="slider round"></span>
                            </label>
                        </div>
                        <p>Toggle between light and dark mode</p>
                    </div>
                </div>
            </div>
        </div>

    <script src="js/admin.js"></script>
    <script>
        // Dark Mode Functions
        function checkTheme() {
            const isDark = localStorage.getItem('theme') === 'dark' || 
                          (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
            
            if (isDark) {
                document.documentElement.classList.add('dark');
                document.getElementById('theme-toggle').checked = true;
                document.getElementById('theme-icon').classList.add('fa-sun');
            } else {
                document.documentElement.classList.remove('dark');
                document.getElementById('theme-toggle').checked = false;
                document.getElementById('theme-icon').classList.add('fa-moon');
            }
            updateChartColors(isDark);
        }

        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('theme', 'light');
                document.getElementById('theme-icon').className = 'fas fa-moon';
            } else {
                html.classList.add('dark');
                localStorage.setItem('theme', 'dark');
                document.getElementById('theme-icon').className = 'fas fa-sun';
            }
            
            document.getElementById('theme-toggle').checked = !isDark;
            updateChartColors(!isDark);
        }

        function updateChartColors(isDark) {
            const textColor = isDark ? '#ffffff' : '#2d3748';
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
            
            const charts = ['usersChart', 'carabaosChart', 'servicesChart', 'requestsChart'];
            charts.forEach(chartId => {
                const chart = Chart.getChart(chartId);
                if (chart) {
                    // Update legend colors
                    chart.options.plugins.legend.labels.color = textColor;
                    
                    // Update dataset colors
                    chart.data.datasets.forEach(dataset => {
                        if (chartId === 'usersChart') {
                            dataset.backgroundColor = isDark ? 
                                ['rgba(0, 86, 179, 0.8)', 'rgba(255, 255, 255, 0.2)'] : 
                                ['#0056b3', '#e2e8f0'];
                        } else if (chartId === 'carabaosChart') {
                            dataset.backgroundColor = isDark ? 
                                ['rgba(56, 161, 105, 0.8)', 'rgba(255, 255, 255, 0.2)'] : 
                                ['#38a169', '#e2e8f0'];
                        }
                    });
                    
                    chart.update();
                }
            });
        }

        // Initialize theme and charts
        document.addEventListener('DOMContentLoaded', function() {
            // Check theme on load
            checkTheme();
            
            // Set up theme toggle event
            document.getElementById('theme-toggle').addEventListener('change', toggleDarkMode);
            
            // Chart colors
            const chartColors = { 
                primary: "#0056b3", 
                success: "#38a169", 
                danger: "#e53e3e", 
                gray: "#e2e8f0" 
            };

            // Get current theme for initial chart colors
            const isDark = document.documentElement.classList.contains('dark');

            // AI Services Chart
            if (document.getElementById("usersChart")) {
                new Chart(document.getElementById("usersChart"), {
                    type: "doughnut",
                    data: {
                        labels: ["Completed", "Remaining"],
                        datasets: [{
                            data: [<?= $aiTotal ?>, <?= max($aiTarget - $aiTotal, 0) ?>],
                            backgroundColor: [chartColors.primary, chartColors.gray],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "75%",
                        plugins: {
                            legend: {
                                labels: {
                                    color: isDark ? '#ffffff' : '#2d3748'
                                }
                            }
                        }
                    }
                });
            }

            // Calf Drop Chart
            if (document.getElementById("carabaosChart")) {
                new Chart(document.getElementById("carabaosChart"), {
                    type: "doughnut",
                    data: {
                        labels: ["Completed", "Remaining"],
                        datasets: [{
                            data: [<?= $calfTotal ?>, <?= max($calfTarget - $calfTotal, 0) ?>],
                            backgroundColor: [chartColors.success, chartColors.gray],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "75%",
                        plugins: {
                            legend: {
                                labels: {
                                    color: isDark ? '#ffffff' : '#2d3748'
                                }
                            }
                        }
                    }
                });
            }

            // Disabled charts (Milk Production)
            if (document.getElementById("servicesChart")) {
                new Chart(document.getElementById("servicesChart"), {
                    type: "doughnut",
                    data: {
                        labels: ["N/A", "N/A"],
                        datasets: [{
                            data: [1, 1],
                            backgroundColor: [chartColors.gray, chartColors.gray],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "75%",
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            // Disabled charts (Milk Feeding)
            if (document.getElementById("requestsChart")) {
                new Chart(document.getElementById("requestsChart"), {
                    type: "doughnut",
                    data: {
                        labels: ["N/A", "N/A"],
                        datasets: [{
                            data: [1, 1],
                            backgroundColor: [chartColors.gray, chartColors.gray],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        cutout: "75%",
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }
        });

        function confirmLogout(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You want to logout?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, logout!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }
    </script>
</body>
</html>