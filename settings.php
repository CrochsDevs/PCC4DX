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
    <link rel="stylesheet" href="css/center.css">
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

        <ul>
            <li><a href="center_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="center_dashboard.php"><i class="fas fa-file-medical"></i> 4DX Report</a></li>
            <li><a href="Partners.php"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="settings.php"  class="nav-link active"><i class="fas fa-cogs"></i> Settings</a></li>
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

        <!-- Dashboard Content -->
       
            
            <!-- Recent Activity Section -->
            <div class="activity-section">
                <h3><i class="fas fa-history"></i> Recent Activity</h3>
                <div class="activity-list">
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-user-plus text-success"></i>
                        </div>
                        <div class="activity-content">
                            <p>5 new partners registered today</p>
                            <small>2 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-file-upload text-primary"></i>
                        </div>
                        <div class="activity-content">
                            <p>New 4DX report submitted</p>
                            <small>5 hours ago</small>
                        </div>
                    </div>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <i class="fas fa-exclamation-triangle text-warning"></i>
                        </div>
                        <div class="activity-content">
                            <p>3 pending requests need approval</p>
                            <small>Yesterday</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            /*** Chart.js Initialization ***/
            const chartColors = { 
                primary: "#0056b3", 
                success: "#38a169", 
                danger: "#e53e3e", 
                warning: "#dd6b20",
                gray: "#e2e8f0" 
            };
            
            const createChart = (ctx, labels, data, backgroundColor) => {
                return new Chart(ctx, {
                    type: "doughnut",
                    data: { 
                        labels, 
                        datasets: [{ 
                            data, 
                            backgroundColor, 
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
            };
            
            // Initialize charts
            if (document.getElementById("usersChart")) {
                createChart(
                    document.getElementById("usersChart"), 
                    ["Registered", "Remaining"], 
                    [1254, 1500-1254], 
                    [chartColors.primary, chartColors.gray]
                );
            }
            
            if (document.getElementById("carabaosChart")) {
                createChart(
                    document.getElementById("carabaosChart"), 
                    ["Carabaos", "Remaining"], 
                    [3421, 3800-3421], 
                    [chartColors.success, chartColors.gray]
                );
            }
            
            if (document.getElementById("servicesChart")) {
                createChart(
                    document.getElementById("servicesChart"), 
                    ["Completed", "Remaining"], 
                    [892, 1000-892], 
                    [chartColors.primary, chartColors.gray]
                );
            }
            
            if (document.getElementById("requestsChart")) {
                createChart(
                    document.getElementById("requestsChart"), 
                    ["Pending", "Target"], 
                    [59, 30], 
                    [chartColors.warning, chartColors.gray]
                );
            }

            /*** Logout Confirmation ***/
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
        });
    </script>
</body>
</html>