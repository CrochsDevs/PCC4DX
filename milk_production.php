<?php
session_start();
require 'auth_check.php';
require 'db_config.php'; // Ensure this has your DB connection setup

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
                <li><a href="#" class="nav-link active" data-section="dashboard-section"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="#" class="nav-link" data-section="entry-section"><i class="fas fa-edit"></i> New Entry</a></li>
                <li><a href="#" class="nav-link" data-section="reports-section"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- Entry Section -->
    <div id="entry-section" class="content-section active">
        <div class="dashboard-card">
            <h2>New Milk Entry</h2>
            <form class="entry-form" action="insert_milk.php" method="POST">
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-input" name="date" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity (Liters)</label>
                    <input type="number" step="0.1" class="form-input" name="quantity" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cooperative</label>
                    <select class="form-input" name="partner_id" required>
                        <option value="">Select Cooperative</option>
                        <?php
                        $query = "SELECT id, partner_name, herd_code FROM partners WHERE is_active = 1";
                        $result = mysqli_query($conn, $query);
                        while ($row = mysqli_fetch_assoc($result)) {
                            $id = $row['id'];
                            $name = htmlspecialchars($row['partner_name']);
                            $herd = htmlspecialchars($row['herd_code']);
                            echo "<option value='$id'>$name ($herd)</option>";
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="submit-btn">Submit Entry</button>
            </form>
        </div>
    </div>

    <!-- Reports Section -->
    <div id="reports-section" class="content-section">
        <div class="dashboard-card">
            <h2>Production Reports</h2>
            <div class="filter-section">
                <div class="form-group">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="start-date" class="form-input">
                </div>
                <div class="form-group">
                    <label class="form-label">End Date</label>
                    <input type="date" id="end-date" class="form-input">
                </div>
                <div class="form-group">
                    <button id="generate-report" class="submit-btn">Generate</button>
                </div>
            </div>

            <table class="milk-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cooperative</th>
                        <th>Quantity (L)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="report-data">
                    <tr>
                        <td>2024-03-01</td>
                        <td>Cooperative A</td>
                        <td>150</td>
                        <td><span class="status-badge completed">Completed</span></td>
                    </tr>
                </tbody>
            </table>

            <button class="export-btn"><i class="fas fa-download"></i> Export CSV</button>
        </div>
    </div>

    <!-- Chart Placeholder (You may insert <canvas id="productionChart"> wherever needed) -->

    <script>
        // Navigation toggle
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                link.classList.add('active');
                document.getElementById(link.dataset.section).classList.add('active');
            });
        });

        // Logout Confirmation
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
                confirmButtonText: 'Yes, logout!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        });

        // Report Button (Placeholder only)
        document.getElementById('generate-report').addEventListener('click', () => {
            console.log('Generating report...');
        });
    </script>
</body>
</html>
