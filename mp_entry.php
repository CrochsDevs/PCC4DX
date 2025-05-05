<?php
include('db_config.php');
session_start();

class MilkEntryManager {
    private $conn;
    private $centerCode;
    
    public function __construct($conn) {
        $this->conn = $conn;
        $this->centerCode = $_SESSION['center_code'] ?? '';
    }
    
    public function handleSubmission() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
            $this->validateEntry();
            $this->saveEntry();
        }
        return $this->getActivePartners();
    }

    private function validateEntry() {
        // Validate entry date is Monday
        $entryDate = new DateTime($_POST['entry_date']);
        $today = new DateTime();
        $today->setTime(0, 0, 0);
        
        // Get the Monday of the current week
        $currentMonday = clone $today;
        $currentMonday->modify('Monday this week');
        
        if ($entryDate->format('N') != 1) {
            $this->setMessage("Start date must be a Monday", "danger");
            $this->redirect();
        }
        
        // Validate date constraints
        if ($entryDate < $currentMonday) {
            $this->setMessage("Cannot enter data for past weeks", "danger");
            $this->redirect();
        }
        
        if ($entryDate > $currentMonday) {
            $this->setMessage("Cannot enter data for future weeks", "danger");
            $this->redirect();
        }
        
        if ($_POST['volume'] <= 0 || $_POST['quantity'] <= 0) {
            $this->setMessage("volume and quantity must be positive values", "danger");
            $this->redirect();
        }
    }

    private function saveEntry() {
        try {
            // Calculate dates
            $startDate = new DateTime($_POST['entry_date']);
            $endDate = clone $startDate;
            $endDate->modify('+6 days');
            
            $data = [
                'entry_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'partner_id' => $_POST['cooperative'],
                'milk_produce' => $_POST['milk_produce'],
                'quantity' => $_POST['quantity'],
                'volume' => $_POST['volume'], // Corrected from 'volume' to 'volume'
                'total' => $_POST['quantity'] * $_POST['volume'],
                'status' => 'Pending',
                'center_code' => $this->centerCode
            ];

            // Updated SQL with correct column name
            $stmt = $this->conn->prepare("INSERT INTO milk_production
                (entry_date, end_date, partner_id, milk_produce, quantity, volume, total, status, center_code)
                VALUES (:entry_date, :end_date, :partner_id, :milk_produce, :quantity, :volume, :total, :status, :center_code)");

            $stmt->execute($data);
            
            $this->setMessage("Entry saved successfully!", "success");
        } catch (PDOException $e) {
            $this->setMessage("Error: " . $e->getMessage(), "danger");
        }
        $this->redirect();
    }

    private function getActivePartners() {
        try {
            $stmt = $this->conn->prepare("SELECT id, partner_name, coop_type 
                FROM partners 
                WHERE center_code = :center_code AND is_active = 1
                ORDER BY partner_name");
            $stmt->execute([':center_code' => $this->centerCode]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    private function setMessage($message, $type) {
        $_SESSION['message'] = $message;
        $_SESSION['message_type'] = $type;
    }

    private function redirect() {
        header("Location: ".$_SERVER['PHP_SELF']);
        exit;
    }
}

// Initialize and process form
$entryManager = new MilkEntryManager($conn);
$partners = $entryManager->handleSubmission();

// Get current week's Monday
$today = new DateTime();
$today->setTime(0, 0, 0);
$currentMonday = clone $today;
$currentMonday->modify('Monday this week');
$currentMondayStr = $currentMonday->format('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooperative Partners Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="css/center.css">
    <link rel="stylesheet" href="css/partners.css"> 
    <style>
<<<<<<< HEAD:new_entry.php
        :root {
            --primary: #0056b3;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --border: #dee2e6;
            --focus-border: #0056b3;
            --alert-success: #d4edda;
            --alert-danger: #f8d7da;
            --success-green: #28a745;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--background);
            padding: 20px;
        }

        .entry-form {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: box-shadow 0.3s ease;
        }

        .entry-form:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--focus-border);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);
        }

        .btn-submit {
            background-color: var(--success-green);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            font-weight: 600;
        }

        .btn-submit:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--alert-success);
            color: #155724;
        }

        .alert-danger {
            background-color: var(--alert-danger);
            color: #721c24;
        }

        .week-info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .entry-form {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-input {
                padding: 10px;
            }
=======
      
      :root {

        --background: #f8f9fa; /* Background color */
        --card-bg: #ffffff; /* Card background */
        --text: #212529; /* Text color */
        --border: #dee2e6; /* Border color */
        --focus-border: #0056b3; /* Focus border color */
        --alert-success: #d4edda; /* Success alert */
        --alert-danger: #f8d7da; /* Danger alert */
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background);
            color: var(--text);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: var(--background);
            padding: 20px;
        }

        .entry-form {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: box-shadow 0.3s ease;
        }

        .entry-form:hover {
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--primary);
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid var(--border);
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--focus-border);  /* Focus border color */
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.2);  /* Light shadow around the focused input */
        }

        .entries-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .entries-table th,
        .entries-table td {
            padding: 14px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 16px;
        }

        .entries-table th {
            background-color: var(--primary);
            color: white;
            font-weight: bold;
        }

        .entries-table tr:hover {
            background-color: #f1f1f1;
        }

        .status-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .status-approved {
            color: #28a745;
            font-weight: bold;
        }

        .status-rejected {
            color: #dc3545;
            font-weight: bold;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: 500;
        }

        .alert-success {
            background-color: var(--alert-success);
            color: #155724;
        }

        .alert-danger {
            background-color: var(--alert-danger);
            color: #721c24;
        }

        /* Media Queries */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .entry-form {
                padding: 20px;
            }

            .form-group {
                margin-bottom: 15px;
            }

            .form-input {
                padding: 10px;
            }

            .entries-table th, .entries-table td {
                padding: 10px;
            }

            .status-pending, .status-approved, .status-rejected {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .entry-form {
                padding: 15px;
            }

            .form-input {
                padding: 8px;
                font-size: 14px;
            }

            .entries-table th, .entries-table td {
                padding: 8px;
            }
>>>>>>> 1b2875462e4a0a475a35d9c746aee1d51dc2790d:mp_entry.php
        }

        @media (max-width: 480px) {
            .entry-form {
                padding: 15px;
            }

            .form-input {
                padding: 8px;
                font-size: 14px;
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

<<<<<<< HEAD:new_entry.php
        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fas fa-dashboard"></i> Back to quickfacts</a></li>
                <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="partners.php" class="nav-link"><i class="fas fa-users"></i> Partners</a></li>
                <li><a href="new_entry.php" class="nav-link active"><i class="fas fa-users"></i> New Entry</a></li>
                <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
=======
    <nav>
        <ul>
             <li><a href="services.php" class="nav-link"><i class="fas fa-dashboard"></i> Back to quickfacts</a></li>
            <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="partners.php" class="nav-link "><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="mp_entry.php" class="nav-link active "><i class="fas fa-users"></i> New Entry</a></li>
            <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
>>>>>>> 1b2875462e4a0a475a35d9c746aee1d51dc2790d:mp_entry.php
    </div>
    
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
            </div>
            
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

        <div class="container">
            <?php if(isset($_SESSION['message'])): ?>
                <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                    <?= $_SESSION['message'] ?>
                    <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
                </div>
            <?php endif; ?>

            <div class="week-info">
                <i class="fas fa-info-circle"></i> You can only enter data for the current week (<?= $currentMonday->format('F j, Y') ?> - <?= $currentMonday->modify('+6 days')->format('F j, Y') ?>)
            </div>

            <div class="entry-form">
                <h2>New Milk Entry</h2>
                <br>
                <form method="POST" id="entryForm">
                    <div class="form-group">
                        <label class="form-label">Start Date (Monday)</label>
                        <input type="date" name="entry_date" class="form-input" value="<?= $currentMondayStr ?>" required readonly>
                    </div>

                    <div class="form-group">    
                        <label class="form-label">Partners</label>
                        <select class="form-input" name="cooperative" required>
                            <option value="">Select Partners</option>
                            <?php foreach ($partners as $partner): ?>
                                <option value="<?= $partner['id'] ?>">
                                    <?= htmlspecialchars($partner['partner_name']) ?> 
                                    (<?= htmlspecialchars($partner['coop_type']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Milk Produce (kg)</label>
                        <input type="number" step="0.01" name="milk_produce" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Milk Traded (kg)</label>
                        <input type="number" step="0.01" name="quantity" class="form-input" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Price per kg (₱)</label>
                        <input type="number" step="0.01" name="volume" class="form-input" required>
                    </div>

<<<<<<< HEAD:new_entry.php
                    <button type="submit" name="submit_entry" id="submitBtn" class="btn-submit">
                        <i class="fas fa-save"></i> Submit Entry
                    </button>
                </form>
            </div>
        </div>
    </div>
=======
                <button type="submit" name="submit_entry" class="form-input" style="background-color: var(--primary); color: white; cursor: pointer;">
                    Submit Entry
                </button>
            </form>
    </div>

     
>>>>>>> 1b2875462e4a0a475a35d9c746aee1d51dc2790d:mp_entry.php

    <script>
   document.getElementById('entryForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const form = this;
        
        // Validate inputs
        const volume = parseFloat(form.volume.value);
        const quantity = parseFloat(form.quantity.value);
        
        if (volume <= 0 || quantity <= 0) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid Input',
                text: 'Price and quantity must be positive values',
                confirmButtonColor: '#28a745'
            });
            return;
        }

        // Get form data for confirmation
        const entryData = {
            entry_date: form.entry_date.value,
            partner: form.cooperative.options[form.cooperative.selectedIndex].text,
            milk_produce: form.milk_produce.value,
            quantity: quantity,
            price: volume,
            total: (quantity * volume).toFixed(2)
        };

        // Show confirmation dialog
        Swal.fire({
            title: 'Confirm Milk Entry',
            html: `
                <div class="confirmation-details">
                    <p><strong>Entry Date:</strong> ${entryData.entry_date}</p>
                    <p><strong>Partner:</strong> ${entryData.partner}</p>
                    <p><strong>Milk Produce:</strong> ${entryData.milk_produce} kg</p>
                    <p><strong>Milk Traded:</strong> ${entryData.quantity} kg</p>
                    <p><strong>Price per kg:</strong> ₱${entryData.price.toFixed(2)}</p>
                    <p><strong>Total Amount:</strong> ₱${entryData.total}</p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm Entry',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            reverseButtons: true,
            allowOutsideClick: false,
        }).then((result) => {
            if (result.isConfirmed) {
                // Create hidden submit button
                const hiddenSubmit = document.createElement('input');
                hiddenSubmit.type = 'hidden';
                hiddenSubmit.name = 'submit_entry';
                hiddenSubmit.value = '1';
                form.appendChild(hiddenSubmit);
                
                // Submit form normally
                form.submit();
            }
        });
    });
    </script>
    </script>
  
</body>
</html>