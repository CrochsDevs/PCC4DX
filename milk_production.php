<?php
session_start();
require 'auth_check.php';

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
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> - Milk Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #0056b3;
            --primary-light: #3a7fc5;
            --secondary: #ecc94b;
            --secondary-light: #f6e05e;
            --accent: #48bb78;
            --light: #f7fafc;
            --dark: #2d3748;
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
            background-color: var(--light);
            display: grid;
            grid-template-columns: 300px 1fr;
            min-height: 100vh;
            color: var(--dark);
        }

        /* Sidebar Styles */
        .sidebar {
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            padding: 2rem 1rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .user-profile {
            text-align: center;
            margin-bottom: 2rem;
        }

        .profile-picture img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 3px solid var(--secondary);
            margin-bottom: 1rem;
            object-fit: cover;
        }

        .profile-info h3 {
            color: white;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .profile-info p {
            color: var(--gray-light);
            font-size: 0.9rem;
        }

        .sidebar ul {
            list-style: none;
        }

        .sidebar li {
            margin-bottom: 1rem;
        }

        .sidebar a {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 500;
            gap: 1rem;
        }

        .sidebar a:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateX(5px);
        }

        .sidebar a.active {
            background: var(--secondary);
            color: var(--primary);
            font-weight: 600;
        }

        /* Main Content */
        #main-content {
            padding: 2rem;
            background-color: var(--light);
        }

        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        /* Dashboard Section */
        .dashboard-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        /* Entry Form */
        .entry-form {
            max-width: 600px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--gray-light);
            border-radius: 8px;
            font-size: 1rem;
        }

        .submit-btn {
            background: var(--accent);
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }

        /* Reports Section */
        .filter-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .milk-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .milk-table th,
        .milk-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }

        .milk-table th {
            background: var(--primary);
            color: white;
            font-weight: 600;
        }

        .milk-table tr:nth-child(even) td {
            background: #f8fafc;
        }

        .export-btn {
            background: var(--secondary);
            color: var(--dark);
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            margin-top: 1rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            body {
                grid-template-columns: 1fr;
            }

            .sidebar {
                display: none;
            }

            #main-content {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="user-profile">
            <div class="profile-picture">
                <?php if (!empty($_SESSION['user']['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($_SESSION['user']['profile_image']) ?>" alt="Profile">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['user']['full_name']) ?>&background=2c5282&color=fff" alt="Profile">
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h3><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                <p><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                <p><?= htmlspecialchars($_SESSION['user']['center_name']) ?></p>
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

    <!-- Main Content -->
    <div id="main-content">
        <!-- Dashboard Section -->
        <div id="dashboard-section" class="content-section active">
            <div class="dashboard-card">
                <h2>Production Overview</h2>
                <div class="chart-container">
                    <canvas id="productionChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Entry Section -->
        <div id="entry-section" class="content-section">
            <div class="dashboard-card">
                <h2>New Milk Entry</h2>
                <form class="entry-form">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quantity (Liters)</label>
                        <input type="number" step="0.1" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cooperative</label>
                        <select class="form-input" required>
                            <option value="">Select Cooperative</option>
                            <option>Cooperative A</option>
                            <option>Cooperative B</option>
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
                        <!-- Sample Data -->
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
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                
                link.classList.add('active');
                document.getElementById(link.dataset.section).classList.add('active');
            });
        });

        // Chart Initialization
        const ctx = document.getElementById('productionChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Milk Production (Liters)',
                    data: [650, 590, 800, 810, 560, 550],
                    borderColor: '#2c5282',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: { display: true, text: 'Monthly Production' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Report Generation
        document.getElementById('generate-report').addEventListener('click', () => {
            // Add your report generation logic here
            console.log('Generating report...');
        });

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
    </script>
</body>
</html>