    <?php
    session_start();
    include('db_config.php');

    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit;
    }

    $centerCode = $_SESSION['center_code'];
    $partnerId = filter_input(INPUT_GET, 'partner_id', FILTER_VALIDATE_INT) ?? 0;

    try {
        $stmt = $conn->prepare("SELECT * FROM partners WHERE id = :id AND center_code = :center_code");
        $stmt->execute([':id' => $partnerId, ':center_code' => $centerCode]);
        $partner = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partner) {
            $_SESSION['message'] = "Partner not found or access denied";
            $_SESSION['message_type'] = "danger";
            header("Location: partners.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['message'] = "Error retrieving partner details";
        $_SESSION['message_type'] = "danger";
        header("Location: partners.php");
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Collect form data
        $cbed = $_POST['cbed'];
        $mfp = $_POST['mfp'];
        $farmers = $_POST['farmers'];
        $animals = $_POST['animals'];
        $pregnant = $_POST['pregnant'];
        $cows = $_POST['cows'];
        $milking = $_POST['milking'];
        $raw = $_POST['raw'];
        $processed = $_POST['processed'];
        $issues = $_POST['issues'];
        $solutions = $_POST['solutions'];
        $marketing_outlet = $_POST['marketing-outlet'];
        $total_2024 = $_POST['total-2024'];

        // Here, you can process and save the data to a database
        // Example: Database connection and insert data (assuming you have a database)

        // Example: Connecting to MySQL database (update with your actual database details)
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "your_database_name";

        // Create connection
        $conn = new mysqli($servername, $username, $password, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Insert data into database
        $sql = "INSERT INTO additional_information (cbed, mfp, farmers, animals, pregnant, cows, milking, raw, processed, issues, solutions, marketing_outlet, total_2024)
                VALUES ('$cbed', '$mfp', '$farmers', '$animals', '$pregnant', '$cows', '$milking', '$raw', '$processed', '$issues', '$solutions', '$marketing_outlet', '$total_2024')";

        if ($conn->query($sql) === TRUE) {
            echo "New record created successfully";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }

        // Close connection
        $conn->close();
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
                    <h3 class="user-name"><?= htmlspecialchars($_SESSION['user']['full_name']) ?></h3>
                    <p class="user-email"><?= htmlspecialchars($_SESSION['user']['email']) ?></p>
                </div>
            </div>

            <ul>
                <li><a href="center_dashboard.php" class="nav-link"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                <li><a href="services.php" class="nav-link"><i class="fas fa-concierge-bell"></i> 4DX Report</a></li>
                <li><a href="partners.php" class="nav-link active"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="settings.php" class="nav-link"><i class="fas fa-cogs"></i> Settings</a></li>
                <li><a href="logout.php" class="logout-btn" onclick="confirmLogout(event)"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
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
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="info-table">
                    <thead>
                        <tr>
                            <th>CBED/CBIN/CCDP/EPAHP</th>
                            <th>MFP</th>
                            <th>FARMERS</th>
                            <th>ANIMALS</th>
                            <th>PREGNANT</th>
                            <th>COWS</th>
                            <th>MILKING</th>
                            <th>RAW</th>
                            <th>PROCESSED</th>
                            <th>ISSUES</th>
                            <th>SOLUTIONS/NEWS TO SHARE</th>
                            <th>MARKETING OUTLET</th>
                            <th>TOTAL 2024</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Empty row or you can leave it blank -->
                        <tr>
                            <td colspan="13" class="empty-message">
                                <button class="add-details-btn" onclick="toggleForm()">+ Add Details</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Form to Add Details -->
            <div id="add-details-form" style="display: none;">
                <h3>Fill Out the Details</h3>
                <form action="your-php-file.php" method="POST">
                    <label for="cbed">CBED/CBIN/CCDP/EPAHP:</label>
                    <input type="text" id="cbed" name="cbed"><br>

                    <label for="mfp">MFP:</label>
                    <input type="text" id="mfp" name="mfp"><br>

                    <label for="farmers">FARMERS:</label>
                    <input type="text" id="farmers" name="farmers"><br>

                    <label for="animals">ANIMALS:</label>
                    <input type="text" id="animals" name="animals"><br>

                    <label for="pregnant">PREGNANT:</label>
                    <input type="text" id="pregnant" name="pregnant"><br>

                    <label for="cows">COWS:</label>
                    <input type="text" id="cows" name="cows"><br>

                    <label for="milking">MILKING:</label>
                    <input type="text" id="milking" name="milking"><br>

                    <label for="raw">RAW:</label>
                    <input type="text" id="raw" name="raw"><br>

                    <label for="processed">PROCESSED:</label>
                    <input type="text" id="processed" name="processed"><br>

                    <label for="issues">ISSUES:</label>
                    <input type="text" id="issues" name="issues"><br>

                    <label for="solutions">SOLUTIONS/NEWS TO SHARE:</label>
                    <input type="text" id="solutions" name="solutions"><br>

                    <label for="marketing-outlet">MARKETING OUTLET:</label>
                    <input type="text" id="marketing-outlet" name="marketing-outlet"><br>

                    <label for="total-2024">TOTAL 2024:</label>
                    <input type="text" id="total-2024" name="total-2024"><br>

                    <button type="submit">Submit</button>
                </form>
            </div>
        </div>
    </div>

                <br>
                <br>
                <!-- Action Buttons -->
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openEditModal()">
                        <i class="fas fa-edit"></i> Edit Partner
                    </button>
                    <form method="POST" action="delete_partner.php" class="delete-form">
                        <input type="hidden" name="partner_id" value="<?= $partner['id'] ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Partner
                        </button>
                    </form>
                </div>
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
            
        </script>
    </body>
    </html>