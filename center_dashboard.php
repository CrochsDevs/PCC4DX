<?php
session_start();
require 'auth_check.php';

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
 
</head>
<style>
        :root {
            --primary: #0056b3;
            --primary-light: #3a7fc5;
            --primary-lighter: #e6f0fa;
            --secondary: #ffc107;
            --secondary-light: #ffd54f;
            --secondary-lighter: #fff8e6;
            --dark: #2d3748;
            --dark-light: #4a5568;
            --light: #f8f9fa;
            --danger: #e53e3e;
            --danger-light: #feb2b2;
            --danger-lighter: #fde8e8;
            --success: #38a169;
            --success-light: #9ae6b4;
            --success-lighter: #f0fff4;
            --info: #3182ce;
            --warning: #dd6b20;
            --gray: #718096;
            --gray-light: #e2e8f0;
            --gray-lighter: #f7fafc;
            --white: #ffffff;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-xl: 1.5rem;
            --transition: all 0.2s ease-in-out;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: #f7fafc;
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
            color: var(--dark);
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
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
            background: var(--secondary);
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: red;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 2.5rem;
            overflow-y: auto;
        }
        
        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
            position: relative;
        }
        
        .header-left, .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
        
        .notification-container {
            position: relative;
        }
        
        .notification-btn {
            background: none;
            border: none;
            color: var(--dark);
            font-size: 1.25rem;
            cursor: pointer;
            position: relative;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .notification-btn:hover {
            background: var(--gray-light);
            transform: translateY(-2px);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        .notification-dropdown {
            position: absolute;
            right: 0;
            top: 100%;
            width: 350px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            z-index: 100;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s;
        }
        
        .notification-container:hover .notification-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h4 {
            margin: 0;
            font-size: 1rem;
            color: var(--dark);
        }
        
        .mark-all-read {
            color: var(--primary);
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            display: flex;
            padding: 1rem;
            gap: 1rem;
            text-decoration: none;
            color: var(--dark);
            border-bottom: 1px solid var(--gray-light);
            transition: all 0.2s;
        }
        
        .notification-item:hover {
            background: rgba(0, 86, 179, 0.05);
        }
        
        .notification-item.unread {
            background: rgba(0, 86, 179, 0.03);
        }
        
        .notification-icon {
            font-size: 1.25rem;
            color: var(--primary);
        }
        
        .notification-icon .text-success {
            color: var(--success);
        }
        
        .notification-icon .text-danger {
            color: var(--danger);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-content p {
            margin: 0 0 0.25rem 0;
            font-size: 0.9rem;
        }
        
        .notification-content small {
            color: var(--gray);
            font-size: 0.8rem;
        }
        
        .notification-footer {
            padding: 0.75rem 1rem;
            text-align: center;
            border-top: 1px solid var(--gray-light);
        }
        
        .notification-footer a {
            color: var(--primary);
            font-size: 0.85rem;
            text-decoration: none;
            font-weight: 500;
        }
        
        /* Dashboard Styles */
        .dashboard-title {
            margin-bottom: 1.75rem;
            color: var(--primary);
            font-size: 1.5rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .dashboard-title i {
            margin-right: 0.75rem;
            color: var(--secondary);
        }
        
        .dashboard-description {
            color: var(--gray);
            margin-bottom: 2rem;
            font-size: 1.05rem;
            max-width: 800px;
            line-height: 1.6;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
            margin-top: 1.5rem;
        }
        
        .dashboard-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        .dashboard-card.notifications {
            min-height: 180px; /* Slightly shorter */
        }

        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-title {
            color: var(--primary);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 0.75rem;
            font-size: 1.2rem;
        }
        /* Add to your CSS file */
        .card-link {
            display: block;
            color: inherit;
            text-decoration: none;
            
            height: 100%;
            padding: 0; 
        }

        .card-link:hover {
            color: inherit;
        }

        .dashboard-card {
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .chart-info {
            margin-top: 1.5rem;
        }
        
        .chart-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-stats .actual {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .chart-stats .target {
            color: var(--gray);
            font-size: 0.95rem;
            background: var(--gray-light);
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
        }
        
        .chart-change {
            font-size: 0.95rem;
            padding: 0.5rem 0.75rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            font-weight: 500;
        }
        
        .chart-change i {
            margin-right: 0.5rem;
        }
        
        .chart-change.positive {
            background-color: rgba(56, 161, 105, 0.1);
            color: var(--success);
        }
        
        .chart-change.negative {
            background-color: rgba(229, 62, 62, 0.1);
            color: var(--danger);
        }
        
        /* User Profile Styles */
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
        
        .profile-info {
            color: white;
        }
        
        .user-name {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .user-email {
            font-size: 0.85rem;
            opacity: 0.9;
            word-break: break-word;
        }
        
        /* Content Sections */
        .content-section {
            display: none;
        }
        
        .content-section.active {
            display: block;
        }
        
            /* Services Section */
        .services-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1rem;
        }

        .service-card {
        background: white;
        border-radius: 10px;
        padding: 1rem;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
        text-decoration: none;
        color: inherit;
        }

        .service-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .card-title i {
        margin-right: 20px;
        font-size: 1.2em;
        width: 24px;
        text-align: center;
        }
        /* Cooperative Management Section */
        #cooperative-section .dashboard-card {
            padding: 2rem;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        }

        .filter-section {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .form-group {
            display: flex;
        }

        .search-group {
            position: relative;
            flex-grow: 2;
        }

        .search-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            font-size: 1.2rem;
        }

        .add-group {
            flex-grow: 0;
        }

        .form-input {
            width: 100%;
            padding: 0.8rem 1.2rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            border-color: #4299e1;
        }

        .submit-btn {
            background-color: #38a169;
            color: white;
            font-weight: 600;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: none;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #2f855a;
        }

        .milk-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .milk-table thead {
            background-color: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .milk-table th {
            padding: 1rem;
            text-align: left;
            color: #2d3436;
            font-weight: 600;
        }

        .milk-table td {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .milk-table tr:last-child td {
            border-bottom: none;
        }

        .milk-table tr:hover {
            background-color: #f8f9fa;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background-color: rgba(56, 161, 105, 0.1);
            color: #38a169;
        }

        .status-badge.inactive {
            background-color: rgba(229, 62, 62, 0.1);
            color: #e53e3e;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

        .pagination-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }

        .pagination-btn:hover {
            background-color: #f0f0f0;
        }

        .pagination-btn:disabled {
            cursor: not-allowed;
            opacity: 0.6;
        }

        #pageNumber {
            font-size: 1rem;
            font-weight: 600;
        }


        /* Action Buttons */
        .edit-btn, .delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border: none;
            background: none;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .edit-btn {
            color: #4299e1;
        }

        .edit-btn:hover {
            background-color: rgba(66, 153, 225, 0.1);
        }

        .delete-btn {
            color: #e53e3e;
        }

        .delete-btn:hover {
            background-color: rgba(229, 62, 62, 0.1);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .filter-section {
                flex-direction: column;
            }
            
            .milk-table {
                display: block;
                overflow-x: auto;
            }
            
            #cooperative-section .dashboard-card {
                padding: 1rem;
            }
        }


            /* Responsive Design */
            @media (max-width: 768px) {
                .cooperative-table {
                    display: block;
                    overflow-x: auto;
                }
                
                .cooperative-header {
                    flex-direction: column;
                    gap: 1rem;
                    align-items: flex-start;
                }
            }

        
        /* Responsive Design */
        @media (max-width: 1024px) {
            body {
                grid-template-columns: 240px 1fr;
            }
            
            .sidebar {
                padding: 1.5rem 1rem;
            }
            
            .main-content {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            @keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
  } 
        }
    </style>
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

        <ul>
            <li><a href="#" class="nav-link active" data-section="dashboard-section"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="#" class="nav-link" data-section="services-section"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="Partners.php"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="#" class="nav-link" data-section="settings-section"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                    <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
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
                
                
            </div>
        </div>
        
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <h2 class="dashboard-title"><i class="fas fa-chart-line"></i> Performance Dashboard</h2>
            <p class="dashboard-description">Monitor and manage all PCC Headquarters operations. Track key metrics and performance indicators to ensure efficient service delivery.</p>
            
            <div class="dashboard-grid">
                <!-- Farmers Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-users"></i> Farmers</h3>
                    <div class="chart-container">
                        <canvas id="usersChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">1,254</span>
                            <span class="target">Target: 1,500</span>
                        </div>
                        <div class="chart-change positive">
                            <i class="fas fa-arrow-up"></i> 12% increase
                        </div>
                    </div>
                </div>

                <!-- Carabaos Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-paw"></i> Carabaos</h3>
                    <div class="chart-container">
                        <canvas id="carabaosChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">3,421</span>
                            <span class="target">Target: 3,800</span>
                        </div>
                        <div class="chart-change positive">
                            <i class="fas fa-arrow-up"></i> 8% increase
                        </div>
                    </div>
                </div>

                <!-- Services Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-check-circle"></i> Completed Services</h3>
                    <div class="chart-container">
                        <canvas id="servicesChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">892</span>
                            <span class="target">Target: 1,000</span>
                        </div>
                        <div class="chart-change negative">
                            <i class="fas fa-arrow-down"></i> 5% decrease
                        </div>
                    </div>
                </div>

                <!-- Requests Card -->
                <div class="dashboard-card">
                    <h3 class="card-title"><i class="fas fa-clock"></i> Pending Requests</h3>
                    <div class="chart-container">
                        <canvas id="requestsChart"></canvas>
                    </div>
                    <div class="chart-info">
                        <div class="chart-stats">
                            <span class="actual">59</span>
                            <span class="target">Target: 30</span>
                        </div>
                        <div class="chart-change negative">
                            <i class="fas fa-arrow-down"></i> 15% increase
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="services-section" class="content-section">
  <h2 class="dashboard-title"><i class="fas fa-concierge-bell"></i> Services Management</h2>
  <p class="dashboard-description">Manage all PCC services offered to farmers and report on service delivery metrics.</p>

  <div class="services-grid">
    <a href="artificial_insemination.php" class="service-card">
      <h3 class="card-title"><i class="fas fa-syringe"></i> Artificial Insemination</h3>
      <p>Report on artificial insemination services for carabaos.</p>
    </a>

    <a href="milk_feeding.php" class="service-card">
      <h3 class="card-title"><i class="fas fa-bottle-droplet"></i> Milk Feeding</h3>
      <p>Report on milk feeding programs and nutritional supplements for calves.</p>
    </a>

    <a href="milk_production.php" class="service-card">
      <h3 class="card-title"><i class="fas fa-bottle-water"></i> Milk Production</h3>
      <p>Report on carabao milk production metrics and quality.</p>
    </a>

    <a href="calf_drop.php" class="service-card">
      <h3 class="card-title"><i class="fas fa-cow"></i> Calf Drop</h3>
      <p>Report on successful births and calf health monitoring programs.</p>
    </a>
  </div>
</div>

<div id="loader-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:9999; justify-content:center; align-items:center;">
  <div class="spinner" style="border:6px solid #f3f3f3; border-top:6px solid #3498db; border-radius:50%; width:50px; height:50px; animation:spin 1s linear infinite;"></div>
</div>




          


        <!-- Settings Section -->
        <div id="settings-section" class="content-section">
            <h2 class="dashboard-title"><i class="fas fa-cogs"></i> Settings</h2>
            <p class="dashboard-description">Configure system settings and user preferences.</p>
            
            <div class="dashboard-card">
                <a href="center_profile_update.php" class="card-link">
                    <h3 class="card-title"><i class="fas fa-user-cog"></i> Account Settings</h3>
                    <p>Update your account information and password.</p>
                </a>
            </div>

            <div class="dashboard-card">
                <a href="center_update_password.php" class="card-link">
                    <h3 class="card-title"><i class="fas fa-user-cog"></i> Password and Security</h3>
                    <p>Update your account password.</p>
                </a>
            </div>
            
            <div class="dashboard-card">
                <h3 class="card-title"><i class="fas fa-bell"></i> Notification Preferences</h3>
                <p>Configure how you receive notifications.</p>
            </div>
        </div>
    </div>

<script>

document.addEventListener("DOMContentLoaded", function () {
    /*** Navigation Functionality ***/
    const navLinks = document.querySelectorAll(".nav-link");
    const contentSections = document.querySelectorAll(".content-section");
    
    navLinks.forEach(link => {
        link.addEventListener("click", function (e) {
            e.preventDefault();
            
            navLinks.forEach(navLink => navLink.classList.remove("active"));
            contentSections.forEach(section => section.classList.remove("active"));
            
            this.classList.add("active");
            document.getElementById(this.dataset.section).classList.add("active");
        });
    });

    /*** Update Sidebar Profile ***/
    function updateSidebarProfile(data) {
        const profileImg = document.getElementById("sidebar-profile-img");
        if (data.profile_image) {
            profileImg.src = "uploads/profile_images/" + data.profile_image;
        } else {
            profileImg.src = "https://ui-avatars.com/api/?name=" + encodeURIComponent(data.full_name) + "&background=0056b3&color=fff&size=128";
        }

        document.getElementById("sidebar-profile-name").textContent = data.full_name;
        document.getElementById("sidebar-profile-email").textContent = data.email;
    }

    // Profile Update Form
    const profileForm = document.getElementById("profileForm");
    if (profileForm) {
        profileForm.addEventListener("submit", function (e) {
            e.preventDefault();
            
            const btn = document.getElementById("submitBtn");
            const notification = document.getElementById("notification");
            const formData = new FormData(this);
            
            btn.textContent = "Processing...";
            btn.disabled = true;
            notification.style.display = "none";
            
            fetch("update_profile.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSidebarProfile({
                        full_name: formData.get("full_name"),
                        email: formData.get("email"),
                        profile_image: data.profile_image
                    });
                    notification.className = "notification success";
                    notification.innerHTML = "<i class='fas fa-check-circle'></i> " + data.message;
                } else {
                    notification.className = "notification error";
                    notification.innerHTML = "<i class='fas fa-exclamation-circle'></i> " + data.message;
                }
                notification.style.display = "block";
            })
            .catch(error => {
                console.error("Error:", error);
                notification.className = "notification error";
                notification.innerHTML = "<i class='fas fa-exclamation-circle'></i> An error occurred. Please try again.";
                notification.style.display = "block";
            })
            .finally(() => {
                btn.textContent = "Update Profile";
                btn.disabled = false;
            });
        });
    }

    
    
    /*** Chart.js Initialization ***/
    const chartColors = { primary: "#0056b3", success: "#38a169", danger: "#e53e3e", gray: "#e2e8f0" };
    const createChart = (ctx, labels, data, backgroundColor) => {
        return new Chart(ctx, {
            type: "doughnut",
            data: { labels, datasets: [{ data, backgroundColor, borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: "75%" }
                       
                        });
                    };
                    
                    if (document.getElementById("usersChart")) {
                        createChart(document.getElementById("usersChart"), ["Registered", "Remaining"], [1254, 1500-1254], [chartColors.primary, chartColors.gray]);
                    }
                    if (document.getElementById("carabaosChart")) {
                        createChart(document.getElementById("carabaosChart"), ["Carabaos", "Remaining"], [3421, 3800-3421], [chartColors.success, chartColors.gray]);
                    }
                    if (document.getElementById("servicesChart")) {
                        createChart(document.getElementById("servicesChart"), ["Completed", "Remaining"], [892, 1000-892], [chartColors.primary, chartColors.gray]);
                    }
                    if (document.getElementById("requestsChart")) {
                        createChart(document.getElementById("requestsChart"), ["Pending", "Target"], [59, 30], [chartColors.danger, chartColors.gray]);
                    }
                    
    /*** Profile Image Preview ***/
    const profileImageInput = document.getElementById("profile_image");
    if (profileImageInput) {
        profileImageInput.addEventListener("change", function () {
            if (this.files.length > 0) {
                document.getElementById("profilePreview").src = URL.createObjectURL(this.files[0]);
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('logoutLink').addEventListener('click', function(e) {
        e.preventDefault();
        const url = this.href;
        
        Swal.fire({
            title: 'Logout Confirmation',
            text: "Are you sure you want to logout?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    });

    document.getElementById('partners-link').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('loader-overlay').style.display = 'flex';

    // Simulate a short delay then redirect
    setTimeout(function () {
      window.location.href = 'Partners.php';
    }, 1000); // 1 second delay (can adjust)
  });
  
});
</script>
</html>