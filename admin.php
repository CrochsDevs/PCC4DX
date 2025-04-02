<?php
session_start();
require 'auth_check.php';

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
    <title>PCC Headquarters Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0056b3;
            --primary-light: #3a7fc5;
            --secondary: #ffc107;
            --secondary-light: #ffd54f;
            --dark: #2d3748;
            --light: #f8f9fa;
            --danger: #e53e3e;
            --danger-light: #feb2b2;
            --success: #38a169;
            --success-light: #9ae6b4;
            --gray: #718096;
            --gray-light: #e2e8f0;
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
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 600;
        }
        
        .logout-btn {
            background: var(--danger);
            color: white;
            padding: 0.6rem 1.25rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .logout-btn i {
            margin-right: 0.5rem;
        }
        
        .logout-btn:hover {
            background: #c53030;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
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
        
        @media (max-width: 768px) {
            .notification-dropdown {
                width: 280px;
                right: -50px;
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
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
    <!-- User Profile Section -->
    <div class="user-profile">
        <div class="profile-picture">
            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
        </div>
        <div class="profile-info">
            <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
            <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
        </div>
    </div>
    <ul>
        <li><a href="#" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
        <li><a href="#"><i class="fas fa-concierge-bell"></i> Services</a></li>
        <li><a href="#"><i class="fas fa-chart-pie"></i> Reports</a></li>
        <li><a href="#"><i class="fas fa-cogs"></i> Settings</a></li>
    </ul>
</div>

    <!-- Main Content -->
    <div class="main-content">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1><i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> (HQ Admin)</h1>
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
        
        <!-- Dashboard Content -->
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
<script src="js/admin.js"></script>
</html>