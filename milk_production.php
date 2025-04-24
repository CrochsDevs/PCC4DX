<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$centerCode = $_SESSION['user']['center_code'];

// Handle Milk Entry Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_milk'])) {
    $entry_date = $_POST['entry_date'];
    $quantity = $_POST['quantity'];
    $volume = $_POST['volume'];
    $cooperative_id = $_POST['cooperative'];
    
    try {
        $stmt = $conn->prepare("INSERT INTO milk_production 
            (entry_date, quantity, volume, partner_id, center_code)
            VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $entry_date,
            $quantity,
            $volume,
            $cooperative_id,
            $centerCode
        ]);
        
        $_SESSION['message'] = "Milk entry added successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error adding entry: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Get Partners for Dropdown
try {
    $stmt = $conn->prepare("SELECT id, partner_name, coop_type FROM partners 
                          WHERE center_code = ?");
    $stmt->execute([$centerCode]);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $partners = [];
    $_SESSION['message'] = "Error loading partners: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Get Milk Production Data
try {
    $stmt = $conn->prepare("SELECT mp.*, p.partner_name 
                          FROM milk_production mp
                          JOIN partners p ON partner_id = p.id
                          WHERE mp.center_code = ?
                          ORDER BY mp.entry_date DESC");
    $stmt->execute([$centerCode]);
    $milkEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $milkEntries = [];
    $_SESSION['message'] = "Error loading milk entries: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}

// Calculate the current week's Monday and Sunday
$today = new DateTime();
$dayOfWeek = $today->format('N'); // 1 (Mon) - 7 (Sun)

$monday = clone $today;
$monday->modify('-' . ($dayOfWeek - 1) . ' days');

$sunday = clone $today;
$sunday->modify('+' . (7 - $dayOfWeek) . ' days');

$mondayDate = $monday->format('Y-m-d');
$sundayDate = $sunday->format('Y-m-d');
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

        <nav>
            <ul>
                <li><a href="#" class="nav-link active" data-section="dashboard-section"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="new_entry.php"><i class="fas fa-users"></i> New Entry  </a></li>
                <li><a href="#" class="nav-link" data-section="reports-section"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>


    <!-- Main Content -->
    <div id="main-content">
        <!-- Flash Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <?php 
            unset($_SESSION['message']);
            unset($_SESSION['message_type']); 
            ?>
        <?php endif; ?>

        <!-- Entry Section -->
        <div id="entry-section" class="content-section">
            <div class="dashboard-card">
                <h2>New Milk Entry</h2>
                <form method="POST" class="entry-form">
                <div class="form-group">
    <label class="form-label">Start Date (Monday)</label>
    <input type="date" name="start_date" class="form-input" value="<?= $mondayDate ?>" readonly>
</div>
<div class="form-group">
    <label class="form-label">End Date (Sunday)</label>
    <input type="date" name="end_date" class="form-input" value="<?= $sundayDate ?>" readonly>
</div>

                    <div class="form-group">
                        <label class="form-label">Cooperative</label>
                        <select class="form-input" name="cooperative" required>
                            <option value="">Select Cooperative</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?= $partner['id'] ?>">
                                    <?= htmlspecialchars($partner['partner_name']) ?> 
                                    (<?= htmlspecialchars($partner['coop_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Value (kg)</label>
                        <input type="number" step="0.01" name="quantity" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (Peso)</label>
                        <input type="number" step="0.01" name="volume" class="form-input" required>
                    </div>
                   
                    <button type="submit" name="add_milk" class="submit-btn">Submit Entry</button>
                </form>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="reports-section" class="content-section">
            <div class="dashboard-card">
                <h2>Production Reports</h2>
                <table class="milk-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Cooperative</th>
                            <th>Quantity (kg)</th>
                            <th>Volume (L)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="report-data">
                        <?php foreach ($milkEntries as $entry): ?>
                            <tr>
                                <td><?= htmlspecialchars($entry['entry_date']) ?></td>
                                <td><?= htmlspecialchars($entry['partner_name']) ?></td>
                                <td><?= number_format($entry['quantity'], 2) ?></td>
                                <td><?= number_format($entry['volume'], 2) ?></td>
                                <td>
                                    <span class="status-badge <?= $entry['status'] ?>">
                                        <?= $entry['status'] ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
// Navigation
document.addEventListener('DOMContentLoaded', () => {
    const navLinks = document.querySelectorAll('.nav-link');

    // Section toggle handler
    navLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            const sectionId = link.dataset.section;
            if (!sectionId) return;

            e.preventDefault();

            document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));

            link.classList.add('active');
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });

    // Show section from URL query (e.g., ?section=dashboard-section)
    const urlParams = new URLSearchParams(window.location.search);
    const initialSection = urlParams.get('section');

    if (initialSection) {
        const targetLink = document.querySelector(`.nav-link[data-section="${initialSection}"]`);
        const targetSection = document.getElementById(initialSection);

        document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
        document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));

        if (targetLink) targetLink.classList.add('active');
        if (targetSection) targetSection.classList.add('active');
    }
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

// Report filtering
document.getElementById('reportFilter').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('filter_entries.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(html => {
        document.getElementById('report-data').innerHTML = html;
    })
    .catch(error => console.error('Error:', error));
});

// CSV Export
function exportToCSV() {
    const table = document.querySelector('.milk-table');
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    for (const row of rows) {
        const rowData = [];
        const cols = row.querySelectorAll('td, th');
        
        for (const col of cols) {
            rowData.push(col.innerText);
        }
        
        csv.push(rowData.join(','));
    }

    const csvString = csv.join('\n');
    const blob = new Blob([csvString], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'milk_entries.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

    </script>
</body>
</html>