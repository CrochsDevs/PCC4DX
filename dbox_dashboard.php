<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

$centerCode = $_SESSION['user']['center_code'];

class DairyBoxDashboard {
    private $conn;
    private $centerCode;

    public function __construct($conn, $centerCode) {
        $this->conn = $conn;
        $this->centerCode = $centerCode;
    }

    public function getOperationalStoresCount() {
        $query = "SELECT COUNT(*) FROM dairy_boxes 
                  WHERE center_code = :center_code AND is_operational = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':center_code' => $this->centerCode]);
        return $stmt->fetchColumn();
    }

    public function getTotalJobsCreated() {
        $query = "SELECT SUM(jobs_created) FROM dairy_boxes 
                  WHERE center_code = :center_code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':center_code' => $this->centerCode]);
        return $stmt->fetchColumn() ?? 0;
    }

    public function getMonthlySales($startDate, $endDate) {
        $query = "SELECT 
                    SUM(dairy_box_sales) as dairy_sales,
                    SUM(kadiwa_sales) as kadiwa_sales
                  FROM dbox_reports
                  WHERE center_code = :center_code
                  AND report_date BETWEEN :start_date AND :end_date";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':center_code' => $this->centerCode,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAnnualSalesTrends() {
        $query = "SELECT 
                    DATE_FORMAT(report_date, '%Y-%m') as month,
                    DATE_FORMAT(report_date, '%b %Y') as month_display,
                    SUM(dairy_box_sales) as dairy_sales,
                    SUM(kadiwa_sales) as kadiwa_sales
                  FROM dbox_reports
                  WHERE center_code = :center_code
                  AND report_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)
                  GROUP BY DATE_FORMAT(report_date, '%Y-%m')
                  ORDER BY month ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':center_code' => $this->centerCode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopStores($limit = 5) {
        $query = "SELECT 
                    d.name as store_name,
                    SUM(r.dairy_box_sales) as total_dairy_sales,
                    SUM(r.kadiwa_sales) as total_kadiwa_sales
                  FROM dbox_reports r
                  JOIN dairy_boxes d ON r.store_id = d.id
                  WHERE r.center_code = :center_code
                  GROUP BY r.store_id
                  ORDER BY total_dairy_sales DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':center_code', $this->centerCode);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Initialize dashboard
$dashboard = new DairyBoxDashboard($conn, $centerCode);

// Get date filters
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get dashboard data
$operationalStores = $dashboard->getOperationalStoresCount();
$totalJobs = $dashboard->getTotalJobsCreated();
$monthlySales = $dashboard->getMonthlySales($startDate, $endDate);
$annualTrends = $dashboard->getAnnualSalesTrends();
$topStores = $dashboard->getTopStores();

// Prepare chart data
$chartLabels = array_column($annualTrends, 'month_display');
$dairySalesData = array_column($annualTrends, 'dairy_sales');
$kadiwaSalesData = array_column($annualTrends, 'kadiwa_sales');

// Format numbers
$formattedDairySales = number_format($monthlySales['dairy_sales'] ?? 0, 2);
$formattedKadiwaSales = number_format($monthlySales['kadiwa_sales'] ?? 0, 2);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dairy Box Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary: #2A5C82;
            --primary-light: #3A6C92;
            --secondary: #5CACEE;
            --success: #28a745;
            --info: #17a2b8;
            --warning: #ffc107;
            --danger: #dc3545;
            --light: #f8f9fa;
            --dark: #343a40;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            margin-left: 280px;
            padding-top: 20px;
            color: #495057;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            bottom: 0;
            width: 280px;
            background: linear-gradient(180deg, #0056b3 0%, #3a7fc5 100%);
            color: white;
            padding: 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar.show {
            transform: translateX(0);
        }

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
            border: 3px solid #5CACEE;
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-info h3 {
            margin-bottom: 0.25rem;
            font-size: 1.1rem;
        }

        .profile-info p {
            margin-bottom: 0;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.8);
        }

        nav ul {
            list-style: none;
            padding-left: 0;
        }

        nav li {
            margin-bottom: 0.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .nav-link i {
            margin-right: 0.75rem;
            font-size: 1rem;
            width: 24px;
            text-align: center;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
        }

        .nav-link.active {
            background: #ffc107;
            color: #2A5C82;
            font-weight: 600;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background-color: #dc3545;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 1rem;
        }

        .logout-btn:hover {
            background-color: #c53030;
            color: white;
        }


        /* Header Styles */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 5px solid var(--primary);
        }

        .header-title h2 {
            color: var(--primary);
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .header-title p {
            color: #6c757d;
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        /* Stat Cards */
        .stat-card {
            border-radius: 10px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-left: 4px solid;
            overflow: hidden;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card .card-body {
            padding: 1.5rem;
            position: relative;
        }

        .stat-card .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }

        .stat-card .stat-title {
            font-size: 0.9rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .stat-card .stat-value {
            font-size: 1.75rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-card.primary {
            border-left-color: var(--primary);
        }

        .stat-card.primary .stat-icon {
            color: var(--primary);
        }

        .stat-card.success {
            border-left-color: var(--success);
        }

        .stat-card.success .stat-icon {
            color: var(--success);
        }

        .stat-card.info {
            border-left-color: var(--info);
        }

        .stat-card.info .stat-icon {
            color: var(--info);
        }

        .stat-card.warning {
            border-left-color: var(--warning);
        }

        .stat-card.warning .stat-icon {
            color: var(--warning);
        }

        /* Chart Containers */
        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .chart-container:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-container .card-title {
            color: var(--primary);
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }

        .chart-area {
            height: 300px;
            position: relative;
        }

        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s ease;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            body {
                margin-left: 0;
                padding-top: 0;
            }
            
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1050;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 10px;
        }

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animated-card {
            animation: fadeIn 0.5s ease-out forwards;
        }

        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        .delay-4 { animation-delay: 0.4s; }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h5 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h5>
                <p class="user-email" style="word-break: break-word; font-size: 0.9rem;">
                    <?= htmlspecialchars($_SESSION['user']['email']) ?>
                </p>
            </div>
        </div>

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="dbox_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="dairybox.php" class="nav-link"><i class="fas fa-store"></i> Stores</a></li>
                <li><a href="dbox_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="dashboard-header animated-card">
            <div class="header-title">
                <h2><i class="fas fa-store me-2"></i> Dairy Box Dashboard</h2>
                <p>Center: <?= htmlspecialchars($centerCode) ?> | <?= date('F j, Y') ?></p>
            </div>
            <div class="header-actions">
                <form method="GET" class="d-flex gap-2">
                    <div class="input-group">
                        <input type="date" id="start_date" name="start_date" 
                               class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($startDate) ?>">
                        <span class="input-group-text">to</span>
                        <input type="date" id="end_date" name="end_date" 
                               class="form-control form-control-sm" 
                               value="<?= htmlspecialchars($endDate) ?>">
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                    <a href="?export=csv" class="btn btn-sm btn-success">
                        <i class="fas fa-download"></i> Export
                    </a>
                </form>
            </div>
        </div>

        <!-- Summary Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card primary animated-card delay-1">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h6 class="stat-title">Operational Stores</h6>
                        <h3 class="stat-value"><?= $operationalStores ?></h3>
                        <p class="stat-subtext">Currently active</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card success animated-card delay-2">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <h6 class="stat-title">Jobs Created</h6>
                        <h3 class="stat-value"><?= $totalJobs ?></h3>
                        <p class="stat-subtext">This week</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card info animated-card delay-3">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <h6 class="stat-title">Dairy Box Sales</h6>
                        <h3 class="stat-value">₱<?= $formattedDairySales ?></h3>
                        <p class="stat-subtext">This month</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card card warning animated-card delay-4">
                    <div class="card-body">
                        <div class="stat-icon">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <h6 class="stat-title">Kadiwa Sales</h6>
                        <h3 class="stat-value">₱<?= $formattedKadiwaSales ?></h3>
                        <p class="stat-subtext">This month</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-1">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Annual Dairy Box Sales (₱)</h5>
                    <div class="chart-area">
                        <canvas id="dairySalesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6">
                <div class="chart-container animated-card delay-2">
                    <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Annual Kadiwa Sales (₱)</h5>
                    <div class="chart-area">
                        <canvas id="kadiwaSalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Stores Table -->
        <div class="chart-container animated-card delay-3">
            <h5 class="card-title"><i class="fas fa-trophy me-2"></i>Top Performing Stores</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Store</th>
                            <th>Dairy Box Sales (₱)</th>
                            <th>Kadiwa Sales (₱)</th>
                            <th>Total Sales (₱)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topStores as $store): ?>
                            <tr>
                                <td><?= htmlspecialchars($store['store_name']) ?></td>
                                <td>₱<?= number_format($store['total_dairy_sales'], 2) ?></td>
                                <td>₱<?= number_format($store['total_kadiwa_sales'], 2) ?></td>
                                <td>₱<?= number_format($store['total_dairy_sales'] + $store['total_kadiwa_sales'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Dairy Sales Chart
        const dairyCtx = document.getElementById('dairySalesChart').getContext('2d');
        new Chart(dairyCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Dairy Box Sales (₱)',
                    data: <?= json_encode($dairySalesData) ?>,
                    backgroundColor: 'rgba(42, 92, 130, 0.2)',
                    borderColor: 'rgba(42, 92, 130, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Sales (₱)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });

        // Kadiwa Sales Chart
        const kadiwaCtx = document.getElementById('kadiwaSalesChart').getContext('2d');
        new Chart(kadiwaCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    label: 'Kadiwa Sales (₱)',
                    data: <?= json_encode($kadiwaSalesData) ?>,
                    backgroundColor: 'rgba(255, 193, 7, 0.2)',
                    borderColor: 'rgba(255, 193, 7, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { 
                            display: true, 
                            text: 'Sales (₱)',
                            font: {
                                weight: 'bold'
                            }
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Month',
                            font: {
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${context.raw.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });

        // Set end date based on start date
        document.getElementById('start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (this.value && (!endDateInput.value || new Date(endDateInput.value) < new Date(this.value))) {
                endDateInput.value = this.value;
            }
        });

        // Responsive sidebar toggle
        const sidebarToggle = document.createElement('button');
        sidebarToggle.innerHTML = '<i class="fas fa-bars"></i>';
        sidebarToggle.style.position = 'fixed';
        sidebarToggle.style.bottom = '20px';
        sidebarToggle.style.right = '20px';
        sidebarToggle.style.zIndex = '999';
        sidebarToggle.style.width = '50px';
        sidebarToggle.style.height = '50px';
        sidebarToggle.style.borderRadius = '50%';
        sidebarToggle.style.backgroundColor = '#0056b3';
        sidebarToggle.style.color = 'white';
        sidebarToggle.style.border = 'none';
        sidebarToggle.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        sidebarToggle.style.cursor = 'pointer';
        sidebarToggle.style.display = 'none';

        document.body.appendChild(sidebarToggle);

        sidebarToggle.addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('show');
        });

        function handleResize() {
            if (window.innerWidth <= 992) {
                sidebarToggle.style.display = 'flex';
                sidebarToggle.style.alignItems = 'center';
                sidebarToggle.style.justifyContent = 'center';
                document.querySelector('.main-content').style.marginLeft = '0';
            } else {
                sidebarToggle.style.display = 'none';
                document.querySelector('.sidebar').classList.remove('show');
                document.querySelector('.main-content').style.marginLeft = '280px';
            }
        }

        window.addEventListener('resize', handleResize);
        handleResize();

        // Logout confirmation
        document.getElementById('logoutLink')?.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = this.href;
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>