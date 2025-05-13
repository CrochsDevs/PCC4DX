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
            <li><a href="center_dashboard.php" class="nav-link" ><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="services.php" class="nav-link active" ><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="settings.php" class="nav-link" data-section="settings-section"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

 <!-- Main Content -->
<div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                    <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
            </div>
            <!-- Notification Section -->
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
        

        <!-- Services Section -->
    <div id="services-section" class="content-section active">
    <h2 class="dashboard-title"><i class="fas fa-concierge-bell"></i> Services Management</h2>
    <p class="dashboard-description">Manage all PCC services offered to farmers and report on service delivery metrics.</p>

    <div class="services-grid">
        <a href="ai_dashboard.php" class="service-card">
        <h3 class="card-title"><i class="fas fa-syringe"></i> Artificial Insemination</h3>
        <p>Report on artificial insemination services for carabaos.</p>
        </a>

        <a href="mf_underdevelopment.php" class="service-card">
        <h3 class="card-title"><i class="fas fa-bottle-droplet"></i> Milk Feeding</h3>
        <p>Report on milk feeding programs and nutritional supplements for calves.</p>
        </a>

        <a href="milk_dashboard.php" class="service-card">
        <h3 class="card-title"><i class="fas fa-bottle-water"></i> Milk Production</h3>
        <p>Report on carabao milk production metrics and quality.</p>
        </a>

        <a href="cd_dashboard.php" class="service-card">
        <h3 class="card-title"><i class="fas fa-cow"></i> Calf Drop</h3>
        <p>Report on successful births and calf health monitoring programs.</p>
        </a>

        <a href="dbox_dashboard.php" class="service-card">
        <h3 class="card-title"><i class="fas fa-solid fa-store"></i> Dairy box</h3>
        <p>Report on Dairy Box hubs supporting farmers through milk marketing and enterprise.</p>
        </a>

        
    </div>
    </div>


</div>

<script>
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