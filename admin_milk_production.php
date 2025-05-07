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
    <title>PCC Headquarters Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/admin.css">

    <style>
  .dropdown-menu {
    display: none;
    list-style: none;
    padding-left: 20px;
  }

  .dropdown.open .dropdown-menu {
    display: block;
  }

  .nav-link {
    cursor: pointer;
    text-decoration: none;
    color: inherit;
  }
</style>
    
    <script>
  document.addEventListener("DOMContentLoaded", function () {
    const dropdownToggle = document.querySelector(".dropdown-toggle");
    const dropdown = dropdownToggle.closest(".dropdown");

    dropdownToggle.addEventListener("click", function (e) {
      e.preventDefault(); // Prevent "#" from jumping
      dropdown.classList.toggle("open");
    });
  });
</script>


</head>
<body>
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
    <li><a href="#" class="nav-link" data-section="dashboard-section"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
    <li><a href="#" class="nav-link" data-section="announcement-section"><i class="fas fa-bullhorn"></i> Announcement</a></li>
    <li class="dropdown">
  <ul>
  <li class="dropdown">
    <a href="#" class="nav-link dropdown-toggle">
      <i class="fas fa-user-friends"></i> Services <i class="fas fa-caret-down"></i>
    </a>
    <ul class="dropdown-menu">
      <li>
        <a href="admin_ai_insemination.php" class="nav-link">
          <i class="fas fa-syringe"></i> AI Insemination
        </a>
      </li>
      <li>
        <a href="admin_calf_drop.php" class="nav-link">
          <i class="fas fa-cow"></i> Calf Drop
        </a>
      </li>
      <li>
        <a href="admin_milk_feeding.php" class="nav-link">
          <i class="fas fa-wine-bottle"></i> Milk Feeding
        </a>
      </li>
      <li>
        <a href="admin_milk_production.php" class="nav-link active">
          <i class="fas fa-flask"></i> Milk Production
        </a>
      </li>
    </ul>
  </li>
</ul>

</li>
<li><a href="#" class="nav-link" data-section="programs-section"><i class="fas fa-user-friends"></i> Programs</a></li>
    <li><a href="#" class="nav-link"  data-section="settings-section"><i class="fas fa-cog"></i> Settings</a></li>
</ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-user-shield"></i> Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> (NHQ Admin)</h1>
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


      <!-- Announcements Section -->
      <div id="announcement-section" class="content-section">
        <div class="container mt-5">
            <h2 class="dashboard-title"><i class="fas fa-bullhorn"></i> Announcements</h2>
            <div class="mt-4 mb-3">
                <!-- Announcement management options -->
                <a href="create_announcements.php" class="btn btn-success">Add Announcements</a>
            </div>

            <!-- Announcement List -->
            <h3 class="mt-4">Announcement List</h3>
            <table class="table table-striped mt-3">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    include 'db_config.php';

                    try {
                        $stmt = $conn->prepare("SELECT * FROM announcement ORDER BY created_at DESC");
                        $stmt->execute();

                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
                            echo "<td>
                                    <a href='edit-announcement.php?announcement_id=" . $row['announcement_id'] . "' class='btn btn-warning btn-sm me-2'>Edit</a>
                                    <a href='delete-announcement.php?announcement_id=" . $row['announcement_id'] . "' class='btn btn-danger btn-sm' onclick='return confirm(\"Are you sure you want to delete this announcement?\")'>Delete</a>
                                </td>";
                            echo "</tr>";
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='3' class='text-danger'>Error fetching announcements: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>

            </table>
        </div>
    </div>


        
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
    <div class="card-link theme-toggle-card" onclick="toggleDarkMode()">
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
        
        <div class="dashboard-card">
            <h3 class="card-title"><i class="fas fa-bell"></i> Notification Preferences</h3>
            <p>Configure how you receive notifications.</p>
            <div class="notification-settings">
                <label>
                    <input type="checkbox" checked> Email Notifications
                </label>
                <label>
                    <input type="checkbox" checked> SMS Notifications
                </label>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
<script src="js/admin.js"></script>
<script>
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
    })
    }

    // Dark Mode Functions
    function checkTheme() {
        const isDark = localStorage.getItem('theme') === 'dark';
        document.body.classList.toggle('dark-theme', isDark);
        document.getElementById('theme-toggle').checked = isDark;
        updateChartColors(isDark);
    }

    function toggleDarkMode() {
        const body = document.body;
        const isDark = body.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        document.getElementById('theme-toggle').checked = isDark;
        updateChartColors(isDark);
    }

    function updateChartColors(isDark) {
        const charts = ['usersChart', 'carabaosChart', 'servicesChart', 'requestsChart'];
        charts.forEach(chartId => {
            const chart = Chart.getChart(chartId);
            if (chart) {
                chart.options.scales.x.ticks.color = isDark ? '#fff' : '#666';
                chart.options.scales.y.ticks.color = isDark ? '#fff' : '#666';
                chart.update();
            }
        });
    }

    function updateChartColors(isDark) {
        const textColor = isDark ? '#ffffff' : '#2d3748';
        const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        const charts = ['usersChart', 'carabaosChart', 'servicesChart', 'requestsChart'];
        charts.forEach(chartId => {
            const chart = Chart.getChart(chartId);
            if (chart) {
                // Update axis colors
                chart.options.scales.x.ticks.color = textColor;
                chart.options.scales.y.ticks.color = textColor;
                chart.options.scales.x.grid.color = gridColor;
                chart.options.scales.y.grid.color = gridColor;
                
                // Update dataset colors
                chart.data.datasets.forEach(dataset => {
                    dataset.borderColor = textColor;
                    dataset.backgroundColor = isDark ? 
                        'rgba(255, 255, 255, 0.5)' : 
                        'rgba(0, 86, 179, 0.5)';
                });
                
                chart.update();
            }
        });
    }

    // Initialize charts with theme-appropriate colors
    function initializeCharts() {
    const isDark = document.body.classList.contains('dark-theme');
    const textColor = isDark ? '#ffffff' : '#2d3748';
    
    // Users Chart
    new Chart(document.getElementById('usersChart'), {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Farmers Registered',
                data: [65, 59, 80, 81, 56, 55],
                borderColor: textColor,
                backgroundColor: isDark ? 
                    'rgba(255, 255, 255, 0.5)' : 
                    'rgba(0, 86, 179, 0.5)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    grid: {
                        color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: textColor
                    }
                },
                y: {
                    grid: {
                        color: isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)'
                    },
                    ticks: {
                        color: textColor
                    }
                }
            }
        }
    });

    }

    document.addEventListener('DOMContentLoaded', function() {
        checkTheme();
        initializeCharts();
    });

    // Initialize theme and charts
    document.addEventListener('DOMContentLoaded', function() {
        checkTheme();
    
    // Initialize all charts with theme-appropriate colors
    const chartOptions = {
        scales: {
            x: {
                ticks: {
                    color: document.body.classList.contains('dark-theme') ? '#fff' : '#666'
                }
            },
            y: {
                ticks: {
                    color: document.body.classList.contains('dark-theme') ? '#fff' : '#666'
                }
            }
        }
    };
    
    // Example for usersChart
    new Chart(document.getElementById('usersChart'), {
        type: 'line',
        data: {/* your chart data */},
        options: chartOptions
    });
    
    // Repeat for other charts

    function updateThemeIcon(isDark) {
    const themeIcon = document.getElementById('theme-icon');
    themeIcon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
    }

    // Update sa toggleDarkMode function
    function toggleDarkMode() {
        const body = document.body;
        const isDark = body.classList.toggle('dark-theme');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateChartColors(isDark);
        updateThemeIcon(isDark);
    }

    // Update sa checkTheme function
    function checkTheme() {
        const isDark = localStorage.getItem('theme') === 'dark';
        document.body.classList.toggle('dark-theme', isDark);
        document.getElementById('theme-toggle').checked = isDark;
        updateChartColors(isDark);
        updateThemeIcon(isDark);
    }
    });

</script>
</html>