<?php
session_start();
require 'auth_check.php';
require 'db_config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agency = $_POST['agency'];
    $cluster = $_POST['cluster'];
    $region = $_POST['region'];
    $beneficiaries = $_POST['beneficiaries'];
    $coops = $_POST['coops'];
    $raw_milk = $_POST['raw_milk'];
    $milk_packs = $_POST['milk_packs'];
    $contract_amount = $_POST['contract_amount'];
    $center_code = $_SESSION['user']['center_code'];

    try {
        $stmt = $conn->prepare("INSERT INTO feeding_entries 
            (agency, cluster, region, beneficiaries, coops, raw_milk, milk_packs, contract_amount, center_code)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $agency, $cluster, $region, $beneficiaries, $coops, 
            $raw_milk, $milk_packs, $contract_amount, $center_code
        ]);
        
        $_SESSION['success'] = "New feeding program entry added successfully!";
        header("Location: milkfeeding_dashboard.php");
        exit();
    } catch(PDOException $e) {
        $_SESSION['error'] = "Error adding entry: " . $e->getMessage();
    }
}

// Fetch existing entries
$entries = [];
try {
    $stmt = $conn->prepare("SELECT * FROM feeding_entries WHERE center_code = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user']['center_code']]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $_SESSION['error'] = "Error fetching entries: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Milk Feeding Program | PCC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --pcc-blue: #0056b3;
            --pcc-light-blue: #e1f0ff;
            --pcc-gold: #d4af37;
            --pcc-dark: #343a40;
        }
        body { 
            margin-left: 280px; 
            background-color: #f8fafc; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .sidebar { 
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 280px;
            background: var(--pcc-blue);
            color: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .pcc-header {
            background: var(--pcc-blue);
            color: white;
            padding: 1rem;
            margin-bottom: 2rem;
            border-bottom: 3px solid var(--pcc-gold);
        }
        .pcc-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }
        .pcc-card:hover {
            transform: translateY(-5px);
        }
        .pcc-card-header {
            background: var(--pcc-blue);
            color: white;
            border-radius: 8px 8px 0 0 !important;
            padding: 1rem;
            font-weight: 600;
        }
        .deped-badge {
            background: #2A5C82;
            color: white;
        }
        .dswd-badge {
            background: var(--pcc-gold);
            color: white;
        }
        .btn-pcc {
            background: var(--pcc-blue);
            color: white;
            border: none;
        }
        .btn-pcc:hover {
            background: #004494;
            color: white;
        }
        .btn-pcc-outline {
            border: 1px solid var(--pcc-blue);
            color: var(--pcc-blue);
        }
        .btn-pcc-outline:hover {
            background: var(--pcc-light-blue);
        }
        .table-pcc {
            border-collapse: separate;
            border-spacing: 0;
        }
        .table-pcc thead th {
            background: var(--pcc-blue);
            color: white;
            position: sticky;
            top: 0;
        }
        .table-pcc tbody tr:hover {
            background-color: var(--pcc-light-blue);
        }
        .modal-header {
            background: var(--pcc-blue);
            color: white;
        }
        .agency-badge {
            font-size: 1.1rem;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .agency-badge:hover {
            opacity: 0.9;
            transform: scale(1.02);
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
            padding: 2rem 1.5rem;
            box-shadow: 4px 0 15px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            overflow-y: auto;
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
            padding-left: 0;
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
            background: #ffc107;
            color: var(--primary);
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
            border: 3px solid var(--secondary);
        }

        .profile-picture img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: red;
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            margin-top: 2rem;
        }

        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }

        .chart-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .data-table {
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-completed {
            background-color: #d4edda;
            color: #155724;
        }

        #loadingSpinner {
            display: none;
        }
        /* Add these to your existing CSS */
.profile-info {
    max-width: 200px; /* Adjust based on your sidebar width */
    width: 100%;
}

.user-email {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
    display: block;
}
    </style>
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
            <li><a href="services.php" class="nav-link"><i class="fas fa-dashboard"></i> Back to quickfacts</a></li>
            <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="partners.php" class="nav-link active"><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="new_entry.php" class="nav-link "><i class="fas fa-users"></i> New Entry</a></li>
            <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    </div>
    <!-- Main Content -->
    <div class="container-fluid p-4">
        <!-- Header -->
        <div class="pcc-header rounded">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">
                    <i class="fas fa-milk-bottle me-2"></i> Milk Feeding Program
                </h2>
                <button class="btn btn-pcc" data-bs-toggle="modal" data-bs-target="#newEntryModal">
                    <i class="fas fa-plus me-2"></i> New Entry
                </button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card pcc-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Total Beneficiaries</h5>
                        <h2 class="text-primary"><?= number_format(array_sum(array_column($entries, 'beneficiaries'))); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card pcc-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Total Milk Packs</h5>
                        <h2 class="text-primary"><?= number_format(array_sum(array_column($entries, 'milk_packs'))); ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card pcc-card">
                    <div class="card-body text-center">
                        <h5 class="card-title text-muted">Total Contract Amount</h5>
                        <h2 class="text-primary">₱<?= number_format(array_sum(array_column($entries, 'contract_amount')), 2); ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Entries Table -->
        <div class="card pcc-card">
            <div class="card-header pcc-card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-table me-2"></i> Feeding Program Entries</span>
                <div>
                    <button class="btn btn-sm btn-pcc-outline me-2">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                    <button class="btn btn-sm btn-pcc-outline">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-pcc table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Agency</th>
                                <th>Region</th>
                                <th>Beneficiaries</th>
                                <th>Cooperatives</th>
                                <th>Milk Packs</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($entries as $entry): ?>
                            <tr>
                                <td><?= date('M d, Y', strtotime($entry['created_at'])); ?></td>
                                <td>
                                    <span class="badge <?= $entry['agency'] === 'DepEd' ? 'deped-badge' : 'dswd-badge'; ?>">
                                        <?= $entry['agency']; ?>
                                    </span>
                                </td>
                                <td><?= $entry['region']; ?></td>
                                <td><?= number_format($entry['beneficiaries']); ?></td>
                                <td><?= number_format($entry['coops']); ?></td>
                                <td><?= number_format($entry['milk_packs']); ?></td>
                                <td>₱<?= number_format($entry['contract_amount'], 2); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-pcc-outline me-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-pcc-outline">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($entries)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No entries found. Click "New Entry" to add one.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- New Entry Modal -->
    <div class="modal fade" id="newEntryModal" tabindex="-1" aria-labelledby="newEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newEntryModalLabel">
                        <i class="fas fa-milk-bottle me-2"></i> New Feeding Program Entry
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="feedingForm">
                        <div class="mb-4">
                            <label class="form-label">Select Agency</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn agency-btn deped-badge active" data-agency="DepEd">
                                    <i class="fas fa-school me-2"></i> DepEd School-based
                                </button>
                                <button type="button" class="btn agency-btn dswd-badge" data-agency="DSWD">
                                    <i class="fas fa-hands-helping me-2"></i> DSWD Supplementary
                                </button>
                                <input type="hidden" name="agency" id="selectedAgency" value="DepEd">
                            </div>
                        </div>

                        <div class="row g-3">
                            <!-- Cluster Field (Visible for DepEd) -->
                            <div class="col-md-6 deped-field">
                                <label class="form-label">Cluster</label>
                                <select class="form-select" name="cluster" id="clusterSelect">
                                    <option value="Luzon">Luzon</option>
                                    <option value="Visayas">Visayas</option>
                                    <option value="Mindanao">Mindanao</option>
                                </select>
                            </div>

                            <!-- Region Field (Dynamic based on Cluster/Agency) -->
                            <div class="col-md-6">
                                <label class="form-label">Region</label>
                                <select class="form-select" name="region" id="regionSelect" required>
                                    <!-- Options populated by JavaScript -->
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Number of Beneficiaries</label>
                                <input type="number" class="form-control" name="beneficiaries" min="0" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Number of Cooperatives</label>
                                <input type="number" class="form-control" name="coops" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Raw Milk Used (Liters)</label>
                                <input type="number" step="0.01" class="form-control" name="raw_milk" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Milk Packs</label>
                                <input type="number" class="form-control" name="milk_packs" min="0" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Contract Amount (₱)</label>
                                <input type="number" step="0.01" class="form-control" name="contract_amount" min="0" required>
                            </div>
                        </div>

                        <div class="modal-footer mt-4">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-2"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-pcc">
                                <i class="fas fa-save me-2"></i> Submit Entry
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Agency Selection
        document.querySelectorAll('.agency-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.agency-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('selectedAgency').value = this.dataset.agency;
                
                // Toggle DepEd-specific fields
                document.querySelectorAll('.deped-field').forEach(field => {
                    field.style.display = this.dataset.agency === 'DepEd' ? 'block' : 'none';
                });
                
                updateRegions();
            });
        });

        // Region Data
        const regions = {
            DepEd: {
                Luzon: ['I', 'II', 'III', 'IVA', 'IVB', 'V', 'NCR'],
                Visayas: ['VI', 'VII', 'VIII'],
                Mindanao: ['XI', 'XII', 'Caraga']
            },
            DSWD: {
                Luzon: ['III'],
                Visayas: ['VI']
            }
        };

        function updateRegions() {
            const agency = document.getElementById('selectedAgency').value;
            const cluster = document.getElementById('clusterSelect').value;
            const regionSelect = document.getElementById('regionSelect');
            
            regionSelect.innerHTML = '';
            regions[agency][cluster].forEach(region => {
                const option = document.createElement('option');
                option.value = region;
                option.textContent = region;
                regionSelect.appendChild(option);
            });
        }

        // Initial setup
        document.getElementById('clusterSelect').addEventListener('change', updateRegions);
        updateRegions();

        // Clear form when modal is hidden
        document.getElementById('newEntryModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('feedingForm').reset();
            document.getElementById('selectedAgency').value = 'DepEd';
            document.querySelectorAll('.agency-btn').forEach(b => b.classList.remove('active'));
            document.querySelector('.agency-btn[data-agency="DepEd"]').classList.add('active');
            updateRegions();
        });
    </script>
</body>
</html>