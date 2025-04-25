<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['user']['center_code'];

// Get the data
$data = [];

// Monthly Data
try {
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(entry_date, '%Y-%m') AS month,
            SUM(quantity) AS total_quantity,
            AVG(volume) AS avg_volume,
            SUM(total) AS total_amount
        FROM milk_production
        WHERE center_code = :center_code
        GROUP BY DATE_FORMAT(entry_date, '%Y-%m')
        ORDER BY entry_date DESC
        LIMIT 12
    ");
    $stmt->execute([':center_code' => $centerCode]);
    $data['monthly'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data['monthly'] = [];
    $_SESSION['message'] = "Error loading monthly data: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Annual Data
try {
    $stmt = $conn->prepare("
        SELECT 
            YEAR(entry_date) AS year,
            SUM(quantity) AS total_quantity,
            SUM(total) AS total_amount
        FROM milk_production
        WHERE center_code = :center_code
        GROUP BY YEAR(entry_date)
        ORDER BY year DESC
        LIMIT 5
    ");
    $stmt->execute([':center_code' => $centerCode]);
    $data['annual'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data['annual'] = [];
    $_SESSION['message'] = "Error loading annual data: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Recent Entries
try {
    $stmt = $conn->prepare("
        SELECT mp.*, p.partner_name 
        FROM milk_production mp
        JOIN partners p ON partner_id = p.id
        WHERE mp.center_code = :center_code
        ORDER BY mp.entry_date DESC
        LIMIT 5
    ");
    $stmt->execute([':center_code' => $centerCode]);
    $data['recent'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $data['recent'] = [];
    $_SESSION['message'] = "Error loading recent entries: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Ensure $data is populated
if (empty($data['monthly']) || empty($data['annual']) || empty($data['recent'])) {
    $_SESSION['message'] = "No data available to display.";
    $_SESSION['message_type'] = "warning";
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile Picture">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=0056b3&color=fff&size=128" alt="Profile Picture">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
            </div>
        </div>
        
        <nav>
            <ul>
                <li><a href="milk_dashboard.php" class="nav-link active"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link "><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="new_entry.php" class="nav-link "><i class="fas fa-users"></i> New Entry</a></li>
                <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container mt-5">
            <h1 class="text-center mb-5">Milk Production Dashboard</h1>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3>Total Production (kg)</h3>
                        <div class="stat-value">
                            <?= number_format(array_sum(array_column($data['monthly'], 'total_quantity'))) ?> kg
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                        <h3>Total Revenue</h3>
                        <div class="stat-value">
                            ₱<?= number_format(array_sum(array_column($data['monthly'], 'total_amount')), 2) ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Monthly Trend Chart -->
            <div class="chart-container">
                <h2 class="chart-title">Monthly Production Trend</h2>
                <canvas id="monthlyChart"></canvas>
            </div>

            <!-- Donut Chart (Total kg and Total revenue) -->
            <div class="chart-container">
                <h2 class="chart-title">Total Production and Revenue</h2>
                <canvas id="donutChart"></canvas>
            </div>

            <!-- Annual Comparison Chart -->
            <div class="chart-container">
                <h2 class="chart-title">Annual Production Comparison</h2>
                <canvas id="annualChart"></canvas>
            </div>

            <!-- Recent Entries -->
            <div class="recent-entries">
                <h2 class="chart-title">Recent Entries</h2>
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Cooperative</th>
                            <th>Quantity</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['recent'] as $entry): ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($entry['entry_date'])) ?></td>
                            <td><?= htmlspecialchars($entry['partner_name']) ?></td>
                            <td><?= number_format($entry['quantity'], 2) ?> kg</td>
                            <td>₱<?= number_format($entry['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        // Monthly Production Trend Chart
        new Chart(document.getElementById('monthlyChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($data['monthly'], 'month')) ?>,
                datasets: [{
                    label: 'Production (kg)',
                    data: <?= json_encode(array_column($data['monthly'], 'total_quantity')) ?>,
                    borderColor: '#007bff',
                    tension: 0.3,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });

        // Donut Chart for Total kg and Revenue
        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: ['Total Production (kg)', 'Total Revenue'],
                datasets: [{
                    data: [
                        <?= number_format(array_sum(array_column($data['monthly'], 'total_quantity'))) ?>,
                        <?= number_format(array_sum(array_column($data['monthly'], 'total_amount')), 2) ?>
                    ],
                    backgroundColor: ['#28a745', '#ffc107'],
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { enabled: true }
                }
            }
        });

        // Annual Production Comparison Chart
        new Chart(document.getElementById('annualChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($data['annual'], 'year')) ?>,
                datasets: [{
                    label: 'Total Production (kg)',
                    data: <?= json_encode(array_column($data['annual'], 'total_quantity')) ?>,
                    backgroundColor: '#007bff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' }
                }
            }
        });
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
