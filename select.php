<?php
session_start();
include('db_config.php');

// Redirect if not logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$centerCode = $_SESSION['center_code'];
$partnerId = filter_input(INPUT_GET, 'partner_id', FILTER_VALIDATE_INT) ?? 0;

try {
    // Fetch partner details
    $stmt = $conn->prepare("SELECT * FROM partners WHERE id = :id AND center_code = :center_code");
    $stmt->execute([':id' => $partnerId, ':center_code' => $centerCode]);
    $partner = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        $_SESSION['message'] = "Partner not found or access denied.";
        $_SESSION['message_type'] = "danger";
        header("Location: partners.php");
        exit;
    }

    // Fetch existing additional information
    $additionalInfoStmt = $conn->prepare("SELECT * FROM additional_information WHERE partner_id = :partner_id");
    $additionalInfoStmt->execute([':partner_id' => $partnerId]);
    $additionalInfo = $additionalInfoStmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $_SESSION['message'] = "Error retrieving data.";
    $_SESSION['message_type'] = "danger";
    header("Location: partners.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_additional_info'])) {
    $data = [
        ':partner_id'       => $partnerId,
        ':coop'             => in_array($_POST['coop'], ['CBED', 'CBIN', 'CCDP', 'EPAHP']) ? $_POST['coop'] : null,
        ':mfp'              => ($_POST['mfp'] === 'Yes') ? 1 : 0,
        ':farmers'          => (int) ($_POST['farmers'] ?? 0),
        ':animals'          => (int) ($_POST['animals'] ?? 0),
        ':pregnant'         => (int) ($_POST['pregnant'] ?? 0),
        ':cows'             => (int) ($_POST['cows'] ?? 0),
        ':milking'          => (int) ($_POST['milking'] ?? 0),
        ':raw'              => (int) ($_POST['raw'] ?? 0),
        ':processed'        => (int) ($_POST['processed'] ?? 0),
        ':issues'           => trim($_POST['issues'] ?? ''),
        ':solutions'        => trim($_POST['solutions'] ?? ''),
        ':marketing_outlet' => trim($_POST['marketing_outlet'] ?? ''),
        ':total_2024'       => (int) ($_POST['total_2024'] ?? 0)
    ];

    try {
        if ($additionalInfo) {
            // Update existing record
            $sql = "UPDATE additional_information SET
                    coop = :coop,
                    mfp = :mfp,
                    farmers = :farmers,
                    animals = :animals,
                    pregnant = :pregnant,
                    cows = :cows,
                    milking = :milking,
                    raw = :raw,
                    processed = :processed,
                    issues = :issues,
                    solutions = :solutions,
                    marketing_outlet = :marketing_outlet,
                    total_2024 = :total_2024
                    WHERE partner_id = :partner_id";
        } else {
            // Insert new record
            $sql = "INSERT INTO additional_information 
                    (partner_id, coop, mfp, farmers, animals, pregnant, cows, milking, 
                     raw, processed, issues, solutions, marketing_outlet, total_2024)
                    VALUES 
                    (:partner_id, :coop, :mfp, :farmers, :animals, :pregnant, :cows, 
                     :milking, :raw, :processed, :issues, :solutions, :marketing_outlet, :total_2024)";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->execute($data);

        $_SESSION['message'] = "Additional information " . ($additionalInfo ? "updated" : "added") . " successfully!";
        $_SESSION['message_type'] = "success";
        header("Location: " . $_SERVER['PHP_SELF'] . "?partner_id=" . $partnerId);
        exit;

    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['message'] = "Error saving data: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header("Location: " . $_SERVER['PHP_SELF'] . "?partner_id=" . $partnerId);
        exit;
    }
}
?>


    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?= htmlspecialchars($partner['partner_name']) ?> Details</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <link rel="stylesheet" href="css/center.css">
        <link rel="stylesheet" href="css/partners.css"> 
        <style>
            .page-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 2rem;
            }

            .herd-code {
                color: var(--secondary);
                font-size: 1.1rem;
                margin-top: 0.5rem;
            }

            .partner-details {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 2rem;
            }

            .detail-card {
                background: white;
                border-radius: 12px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                padding: 1.5rem;
            }

            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .status-badge {
                padding: 0.3rem 0.8rem;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 500;
            }

            .status-badge.active {
                background: #d1fae5;
                color: #065f46;
            }

            .status-badge.inactive {
                background: #fee2e2;
                color: #991b1b;
            }

            .detail-item {
                margin-bottom: 1rem;
                padding: 0.8rem 0;
                border-bottom: 1px solid #eee;
            }

            .label {
                display: block;
                color: var(--secondary);
                font-size: 0.9rem;
            }

            .value {
                font-weight: 500;
                color: var(--dark);
                font-size: 1.1rem;
            }

            /* Modal Styles */
            .modal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                display: none;
                align-items: center;
                justify-content: center;
                z-index: 1000;
            }

            .modal-content {
                background: white;
                border-radius: 12px;
                width: 90%;
                max-width: 600px;
                max-height: 90vh;
                overflow-y: auto;
                padding: 2rem;
                position: relative;
            }

            .modal-close {
                position: absolute;
                top: 1rem;
                right: 1rem;
                background: none;
                border: none;
                font-size: 1.5rem;
                cursor: pointer;
            }

            /* Form Elements */
            .form-group {
                margin-bottom: 1.5rem;
            }

            .form-label {
                display: block;
                margin-bottom: 0.5rem;
                color: var(--secondary);
            }

            .form-control {
                width: 100%;
                padding: 0.75rem;
                border: 1px solid #ced4da;
                border-radius: 6px;
                font-size: 1rem;
            }

            /* Buttons */
            .btn {
                padding: 0.75rem 1.5rem;
                border-radius: 6px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .btn-primary {
                background: var(--primary);
                color: white;
                border: none;
            }

            .btn-danger {
                background: var(--danger);
                color: white;
                border: none;
            }

            .btn-secondary {
                background: var(--secondary);
                color: white;
                border: none;
            }
            .detail-card {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow-x: auto;
    }

    .card-header {
        background-color: #004080;
        color: #fff;
        padding: 15px 20px;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
    }

    .card-header h2 {
        margin: 0;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-body {
        padding: 20px;
    }

    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }

    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    .info-table th,
    .info-table td {
        padding: 10px 15px;
        text-align: center;
        border: 1px solid #ddd;
        font-size: 0.95rem;
    }

    .info-table th {
        background-color: #f1f1f1;
        font-weight: 600;
        color: #333;
    }

    .empty-message {
        text-align: center;
        padding: 30px 0;
    }

    .add-details-btn {
        padding: 10px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 0.95rem;
        cursor: pointer;
        transition: background-color 0.3s ease;
    }

    .add-details-btn:hover {
        background-color: #0056b3;
    }
     /* Add to existing styles */
     .add-info-modal .form-group {
            margin-bottom: 1rem;
        }

        .add-info-modal label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        .add-info-modal input,
        .add-info-modal textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 0.5rem;
        }

        .grid-columns {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }


            /* Responsive Design */
            @media (max-width: 768px) {
                .sidebar {
                    width: 100%;
                    height: auto;
                    position: relative;
                }

                .main-content {
                    margin-left: 0;
                    padding: 1rem;
                }

                .page-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 1rem;
                }
            }
            /* Dropdown Styles */
.dropdown-select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 6px;
    background-color: white;
    font-size: 1rem;
    appearance: none;
    background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23007CB2%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
    background-repeat: no-repeat;
    background-position: right 0.7rem top 50%;
    background-size: 0.65rem auto;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.dropdown-select:focus {
    border-color: #80bdff;
    outline: 0;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Boolean Dropdown Specific */
select[name="mfp"] {
    text-transform: uppercase;
    font-weight: 500;
}

select[name="mfp"] option[value="Yes"] {
    color: #28a745;
}

select[name="mfp"] option[value="No"] {
    color: #dc3545;
}

/* Responsive Dropdowns */
@media (max-width: 768px) {
    .dropdown-select {
        font-size: 0.9rem;
        padding: 0.6rem;
    }
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
                <li><a href="milk_production.php?section=dashboard-section" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link active"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="milk_production.php?section=entry-section" class="nav-link"><i class="fas fa-edit"></i> New Entry</a></li>
                <li><a href="milk_production.php?section=reports-section" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>

    </div>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container">
                <!-- Partner Header -->
                <div class="page-header">
                    <div>
                        <h1><?= htmlspecialchars($partner['partner_name']) ?></h1>
                        <p class="herd-code">Herd Code: <?= htmlspecialchars($partner['herd_code']) ?></p>
                    </div>
                    <a href="partners.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Partners
                    </a>
                </div>

                <!-- Partner Details Sections -->
                <div class="partner-details">
                    <!-- Basic Information Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-info-circle"></i> Basic Information</h2>
                            <span class="status-badge <?= $partner['is_active'] ? 'active' : 'inactive' ?>">
                                <?= $partner['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <span class="label">Partner Type</span>
                                <span class="value"><?= htmlspecialchars($partner['coop_type']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Registration Date</span>
                                <span class="value"><?= date('M d, Y', strtotime($partner['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-address-book"></i> Contact Information</h2>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <span class="label">Contact Person</span>
                                <span class="value"><?= htmlspecialchars($partner['contact_person']) ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="label">Contact Number</span>
                                <a href="tel:<?= htmlspecialchars($partner['contact_number']) ?>" class="value">
                                    <?= htmlspecialchars($partner['contact_number']) ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Location Card -->
                    <div class="detail-card">
                        <div class="card-header">
                            <h2><i class="fas fa-map-marker-alt"></i> Location</h2>
                        </div>
                        <div class="card-body">
                            <div class="detail-item">
                                <span class="label">Address</span>
                                <span class="value">
                                    <?= htmlspecialchars($partner['barangay']) ?>, 
                                    <?= htmlspecialchars($partner['municipality']) ?>, 
                                    <?= htmlspecialchars($partner['province']) ?>
                                </span>
                            </div>
                            <div class="map-placeholder">
                                <i class="fas fa-map-marked-alt"></i>
                                <p>Map integration coming soon</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Additional Information Card -->
                <div class="detail-card">
                    <div class="card-header">
                        <h2><i class="fas fa-plus-circle"></i> Additional Information</h2>
                        <button class="btn <?= $additionalInfo ? 'btn-warning' : 'btn-primary' ?>" onclick="openAddInfoModal()">
                            <i class="fas <?= $additionalInfo ? 'fa-edit' : 'fa-plus' ?>"></i> 
                            <?= $additionalInfo ? 'Edit Information' : 'Add Information' ?>
                        </button>
                    </div>
                    <div class="card-body">
                    <?php if (!empty($additionalInfo)): ?>
                        <div class="table-responsive">
                            <table class="info-table">
                                <thead>
                                    <tr>
                                        <th>COOP</th>
                                        <th>MFP</th>
                                        <th>Farmers</th>
                                        <th>Animals</th>
                                        <th>Cows</th>
                                        <th>Pregnant</th>
                                        <th>Milking</th>
                                        <th>Raw</th>
                                        <th>Processed</th>
                                        <th>Issue</th>
                                        <th>Solution</th>
                                        <th>Marketing outlet</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($additionalInfo as $info): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($info['coop']) ?></td>
                                            <td><?= $info['mfp'] ? 'Yes' : 'No' ?></td>
                                            <td><?= $info['farmers'] ?></td>
                                            <td><?= $info['animals'] ?></td>
                                            <td><?= $info['pregnant'] ?></td>
                                            <td><?= $info['cows'] ?></td>
                                            <td><?= $info['milking'] ?></td>
                                            <td><?= $info['raw'] ?></td>
                                            <td><?= $info['processed'] ?></td>
                                            <td><?= $info['issues'] ?></td>
                                            <td><?= $info['solutions'] ?></td>
                                            <td><?= $info['marketing_outlet'] ?></td>
                                            <td><?= date('M d, Y H:i', strtotime($info['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-message">No additional information found</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add/Edit Modal -->
            <div class="modal-overlay" id="addInfoModal">
                <div class="modal-content add-info-modal">
                    <button class="modal-close" onclick="closeAddInfoModal()">&times;</button>
                    <h2><?= $additionalInfo ? 'Edit' : 'Add' ?> Additional Information</h2>
                    <form method="POST" action="">
                        <div class="grid-columns">
                            <div class="form-group">
                                <label>COOP Type *</label>
                                <select class="dropdown-select" name="coop" required>
                                    <option value="">Select Type</option>
                                    <option value="CBED" <?= isset($additionalInfo[0]['coop']) && $additionalInfo[0]['coop'] === 'CBED' ? 'selected' : '' ?>>CBED</option>
                                    <option value="CBIN" <?= isset($additionalInfo[0]['coop']) && $additionalInfo[0]['coop'] === 'CBIN' ? 'selected' : '' ?>>CBIN</option>
                                    <option value="CCDP" <?= isset($additionalInfo[0]['coop']) && $additionalInfo[0]['coop'] === 'CCDP' ? 'selected' : '' ?>>CCDP</option>
                                    <option value="EPAHP" <?= isset($additionalInfo[0]['coop']) && $additionalInfo[0]['coop'] === 'EPAHP' ? 'selected' : '' ?>>EPAHP</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>MFP Member *</label>
                                <select class="dropdown-select" name="mfp" required>
                                    <option value="Yes" <?= isset($additionalInfo[0]['mfp']) && $additionalInfo[0]['mfp'] ? 'selected' : '' ?>>Yes</option>
                                    <option value="No" <?= isset($additionalInfo[0]['mfp']) && !$additionalInfo[0]['mfp'] ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>

                            <!-- Rest of the form fields remain the same -->
                            <div class="form-group">
                                <label>Farmers</label>
                                <input type="number" name="farmers" required>
                            </div>
                            <div class="form-group">
                                <label>Animals</label>
                                <input type="number" name="animals" required>
                            </div>
                            <div class="form-group">
                                <label>Pregnant</label>
                                <input type="number" name="pregnant" required>
                            </div>
                            <div class="form-group">
                                <label>Cows</label>
                                <input type="number" name="cows" required>
                            </div>
                            <div class="form-group">
                                <label>Milking</label>
                                <input type="number" name="milking" required>
                            </div>
                            <div class="form-group">
                                <label>Raw</label>
                                <input type="number" name="raw" required>
                            </div>
                            <div class="form-group">
                                <label>Processed</label>
                                <input type="number" name="processed" required>
                            </div>

                            <div class="form-group">
                            <label>Issues</label>
                            <textarea name="issues" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Solutions/News</label>
                            <textarea name="solutions" rows="3"></textarea>
                        </div>

                        <div class="form-group">
                            <label>Marketing Outlet</label>
                            <input type="text" name="marketing_outlet" required>
                        </div>

                            <div class="form-group">
                                <label>Total</label>
                                <input type="number" name="total_2024" required>
                            </div>
                        </div>
                        
                        
                        
                        <div class="modal-actions">
                            <button type="button" class="btn btn-secondary" onclick="closeAddInfoModal()">Cancel</button>
                            <button type="submit" name="save_additional_info" class="btn btn-primary">
                                <?= $additionalInfo ? 'Update' : 'Save' ?> Information
                            </button>
                        </div>
                    </form>
                </div>
            </div>


                <br>
                <br>
            
            </div>
        </main>

        <!-- Edit Modal -->
        <div class="modal-overlay" id="editModal">
            <div class="modal-content">
                <button class="modal-close">&times;</button>
                <h2>Edit Partner Details</h2>
                
                <form method="POST" action="update_partner.php" id="editForm">
                    <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Partner Name</label>
                        <input type="text" class="form-control" name="partner_name" 
                            value="<?= htmlspecialchars($partner['partner_name']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Partner Type</label>
                        <select class="form-control" name="coop_type" required>
                            <option value="Cooperatives" <?= $partner['coop_type'] === 'Cooperatives' ? 'selected' : '' ?>>Cooperatives</option>
                            <option value="Associations" <?= $partner['coop_type'] === 'Associations' ? 'selected' : '' ?>>Associations</option>
                            <option value="LGU" <?= $partner['coop_type'] === 'LGU' ? 'selected' : '' ?>>LGU</option>
                            <option value="SCU" <?= $partner['coop_type'] === 'SCU' ? 'selected' : '' ?>>SCU</option>
                            <option value="Family_Module" <?= $partner['coop_type'] === 'Family_Module' ? 'selected' : '' ?>>Family Module</option>
                            <option value="Corporation" <?= $partner['coop_type'] === 'Corporation' ? 'selected' : '' ?>>Corporation</option>
                        </select>
                    </div>

                    <!-- Add other form fields similarly -->

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <div class="status-toggle">
                            <input type="checkbox" name="is_active" id="is_active" 
                                <?= $partner['is_active'] ? 'checked' : '' ?> hidden>
                            <label for="is_active" class="toggle-label"></label>
                        </div>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            // Modal Handling
            function openEditModal() {
                document.getElementById('editModal').style.display = 'flex';
            }

            function closeEditModal() {
                document.getElementById('editModal').style.display = 'none';
            }

            // Close modal when clicking outside
            document.getElementById('editModal').addEventListener('click', (e) => {
                if(e.target === document.getElementById('editModal')) {
                    closeEditModal();
                }
            });

            // Delete Confirmation
            document.querySelectorAll('.delete-form').forEach(form => {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    Swal.fire({
                        title: 'Confirm Deletion',
                        text: "Are you sure you want to delete this partner?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Delete'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
            
            function toggleForm() {
            const form = document.getElementById('add-details-form');
            if (form.style.display === 'none') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }

            // Form Submission Handling
            document.getElementById('editForm').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                
                try {
                    const response = await fetch('update_partner.php', {
                        method: 'POST',
                        body: formData
                    });

                    if (response.ok) {
                        location.reload();
                    } else {
                        Swal.fire('Error', 'Failed to update partner', 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('Error', 'An error occurred', 'error');
                }
                
            });
                // Add Info Modal Functions
        function openAddInfoModal() {
            document.getElementById('addInfoModal').style.display = 'flex';
        }

        function closeAddInfoModal() {
            document.getElementById('addInfoModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('addInfoModal').addEventListener('click', (e) => {
            if(e.target === document.getElementById('addInfoModal')) {
                closeAddInfoModal();
            }
        });

        // Handle form submission
        document.querySelector('#addInfoModal form').addEventListener('submit', function(e) {
            const inputs = this.querySelectorAll('input, textarea');
            let isValid = true;
            
            inputs.forEach(input => {
                if (input.required && !input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                } else {
                    input.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                Swal.fire('Error', 'Please fill all required fields', 'error');
            }
        });

        // navigation.js
document.addEventListener('DOMContentLoaded', () => {
    const currentPage = window.location.pathname.split('/').pop(); // Get current filename (e.g., "partners.php")

    // Highlight active nav-link based on current page or section
    document.querySelectorAll('nav .nav-link, nav a').forEach(link => {
        const href = link.getAttribute('href');
        
        // Highlight if href matches the current page
        if (href && currentPage === href) {
            link.classList.add('active');
        }

        // SPA-style section toggle for links with data-section (if ever needed again)
        if (link.dataset.section) {
            link.addEventListener('click', (e) => {
                e.preventDefault();

                document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));

                link.classList.add('active');
                const targetSection = document.getElementById(link.dataset.section);
                if (targetSection) {
                    targetSection.classList.add('active');
                }
            });
        }
    });
});

            
        </script>
    </body>
    </html>