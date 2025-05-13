<?php
session_start();
require 'auth_check.php';
require 'db_config.php'; // if not already present

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['center_code']; 

class SummaryFetcher {
    private $db;
    private $centerCode;

    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }

    public function getAIData($year, $target) {
        $stmt = $this->db->prepare("SELECT SUM(aiServices) as total_ai FROM ai_services WHERE center = :center AND YEAR(date) = :year");
        $stmt->execute([':center' => $this->centerCode, ':year' => $year]);
        $totalAI = $stmt->fetch(PDO::FETCH_ASSOC)['total_ai'] ?? 0;

        $accomplished = ($target > 0) ? ($totalAI / $target) * 100 : 0;
        return [
            'total' => $totalAI,
            'target' => $target,
            'percent' => round($accomplished, 2)
        ];
    }

    public function getCalfDropData($year, $target) {
        $stmt = $this->db->prepare("SELECT SUM(ai + bep + ih + private) as total FROM calf_drop WHERE center = :center AND YEAR(date) = :year");
        $stmt->execute([':center' => $this->centerCode, ':year' => $year]);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

        $accomplished = ($target > 0) ? ($total / $target) * 100 : 0;
        return [
            'total' => $total,
            'target' => $target,
            'percent' => round($accomplished, 2)
        ];
    }
}

$centerCode = $_SESSION['center_code'];
$fetcher = new SummaryFetcher($conn, $centerCode);
$year = date('Y');

// Define target values
$aiTarget = 13400;
$calfDropTarget = 5000;

$aiPerf = $fetcher->getAIData($year, $aiTarget);
$cdPerf = $fetcher->getCalfDropData($year, $calfDropTarget);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.sheetjs.com/xlsx-0.19.3/package/dist/xlsx.full.min.js"></script>
    <script src="js/center.js"></script>
    <link rel="stylesheet" href="css/center.css">
</head>
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
        .progress-container {
            width: 100%;
            background-color: #e5e7eb;
            border-radius: 10px;
            margin-top: 10px;
            position: relative;
            overflow: hidden;
        }
        .progress-bar {
            height: 10px;
            border-radius: 10px;
            transition: width 0.5s ease;
        }
        .target-marker {
            position: absolute;
            top: -5px;
            width: 2px;
            height: 20px;
            background-color: #000;
        }
        .progress-text {
            margin-top: 5px;
            font-size: 0.8rem;
            color: #6b7280;
        }
        .status-indicator {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-left: 8px;
        }
        .status-excellent {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-achieved {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-progress {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-low {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .timeframe-btn.active {
            background-color: #3b82f6;
            color: white;
        }
        .filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .year-filter, .month-filter, .week-filter {
            flex: 1;
        }
        .filter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .filter-title {
            font-weight: 600;
            color: #4b5563;
        }
        .filter-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr); 
            gap: 8px; 
        }
        .filter-btn {
            padding: 6px 12px;
            background: #f3f4f6;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .filter-btn:hover {
            background: #e5e7eb;
        }
        .filter-btn.active {
            background: #3b82f6;
            color: white;
        }
        .export-btn {
            padding: 6px 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .export-btn:hover {
            background: #059669;
        }

        .chart-container {
            min-height: 400px;
            position: relative;
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
            <li><a href="center_dashboard.php" class="nav-link active" data-section="dashboard-section"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="services.php" class="nav-link" data-section="services-section"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
            <li><a href="settings.php" class="nav-link" data-section="settings-section"><i class="fas fa-cogs"></i> Settings</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
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
        
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <h2 class="dashboard-title"><i class="fas fa-chart-line"></i> Performance Dashboard</h2>
        <p class="dashboard-description">
            Discover key insights about PCC 
            Quickfacts, performance metrics, and goal-tracking updates.
        </p>
            
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-3 gap-6 mb-10">

                <!-- AI Performance Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <h3 class="card-title"><i class="fas fa-syringe"></i> AI Performance</h3>
                    <p>Total: <?= number_format($aiPerf['total']) ?> / <?= number_format($aiPerf['target']) ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min($aiPerf['percent'], 100) ?>%; background-color: <?= 
                            $aiPerf['percent'] >= 100 ? '#10b981' : 
                            ($aiPerf['percent'] >= 80 ? '#3b82f6' : 
                            ($aiPerf['percent'] >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                        </div>
                    </div>
                    <p class="dashboard-description"><?= $aiPerf['percent'] ?>% of target</p>
                </div>

                <!-- Calf Drop Performance Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <h3 class="card-title"><i class="fas fa-cow"></i> Calf Drop</h3>
                    <p>Total: <?= number_format($cdPerf['total']) ?> / <?= number_format($cdPerf['target']) ?></p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min($cdPerf['percent'], 100) ?>%; background-color: <?= 
                            $cdPerf['percent'] >= 100 ? '#10b981' : 
                            ($cdPerf['percent'] >= 80 ? '#3b82f6' : 
                            ($cdPerf['percent'] >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                        </div>
                    </div>
                    <p class="dashboard-description"><?= $cdPerf['percent'] ?>% of target</p>
                </div>

                <!-- Calf Drop Performance Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <h3 class="card-title"><i class="fas fa-cow"></i> Milk Production</h3>
                    <p>Total: </p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min($cdPerf['percent'], 100) ?>%; background-color: <?= 
                            $cdPerf['percent'] >= 100 ? '#10b981' : 
                            ($cdPerf['percent'] >= 80 ? '#3b82f6' : 
                            ($cdPerf['percent'] >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                        </div>
                    </div>
                    <p class="dashboard-description"><?= $cdPerf['percent'] ?>% of target</p>
                </div>

                <!-- Calf Drop Performance Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <h3 class="card-title"><i class="fas fa-cow"></i> Milk Feeding</h3>
                    <p>Total: </p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min($cdPerf['percent'], 100) ?>%; background-color: <?= 
                            $cdPerf['percent'] >= 100 ? '#10b981' : 
                            ($cdPerf['percent'] >= 80 ? '#3b82f6' : 
                            ($cdPerf['percent'] >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                        </div>
                    </div>
                    <p class="dashboard-description">% of target</p>
                </div>
                <!-- Calf Drop Performance Card -->
                <div class="bg-white p-6 rounded-xl shadow-md card-hover fade-in">
                    <h3 class="card-title"><i class="fas fa-box"></i> Dairy Box</h3>
                    <p>Total: </p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: <?= min($cdPerf['percent'], 100) ?>%; background-color: <?= 
                            $cdPerf['percent'] >= 100 ? '#10b981' : 
                            ($cdPerf['percent'] >= 80 ? '#3b82f6' : 
                            ($cdPerf['percent'] >= 50 ? '#f59e0b' : '#ef4444')) ?>;">
                        </div>
                    </div>
                    <p class="dashboard-description">% of target</p>
                </div>
            </div>
        </div>

        <!-- Services Section -->
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

/*** Logout Confirmation ***/
function confirmLogout(event) {
    event.preventDefault();
    const url = event.currentTarget.href;
    
    Swal.fire({
        title: 'Logout Confirmation',
        text: "Are you sure you want to logout?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, logout!',
        cancelButtonText: 'Cancel',
        customClass: {
            popup: 'custom-swal-popup',
            confirmButton: 'custom-confirm-btn',
            cancelButton: 'custom-cancel-btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            // Add loading state
            const logoutBtn = event.currentTarget;
            logoutBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
            
            // Simulate logout delay
            setTimeout(() => {
                window.location.href = url;
            }, 1500);
        }
    });
}
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