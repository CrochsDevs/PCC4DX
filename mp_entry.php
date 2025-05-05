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
        if ($entryDate->format('N') != 1) {
            $this->setMessage("Start date must be a Monday", "danger");
            $this->redirect();
        }
        
        // Validate positive values
        if ($_POST['price'] <= 0 || $_POST['quantity'] <= 0) {
            $this->setMessage("Price and quantity must be positive values", "danger");
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
                'volume' => $_POST['price'], 
                'total' => $_POST['quantity'] * $_POST['price'],
                'status' => 'Pending',
                'center_code' => $this->centerCode
            ];

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

// Get existing entries for display
try {
    $stmt = $conn->prepare("
        SELECT mp.*, p.partner_name 
        FROM milk_production mp
        JOIN partners p ON mp.partner_id = p.id
        WHERE mp.center_code = :center_code
        ORDER BY mp.entry_date DESC
    ");
    $stmt->execute([':center_code' => $_SESSION['center_code']]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $entries = [];
    $_SESSION['message'] = "Error fetching entries: " . $e->getMessage();
    $_SESSION['message_type'] = "danger";
}
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
            <li><a href="partners.php" class="nav-link "><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="mp_entry.php" class="nav-link active "><i class="fas fa-users"></i> New Entry</a></li>
            <li><a href="milk_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="logout.php" class="logout-btn" id="logoutLink"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>
    </div>
    <div>
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

        <div class="entry-form">
            <h2>New Milk Entry</h2>
            <br>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Start Date (Monday)</label>
                    <input type="date" name="entry_date" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Partners </label>
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
                    <input type="number" step="0.01" name="price" class="form-input" required>
                </div>

                <button type="submit" name="submit_entry" class="form-input" style="background-color: var(--primary); color: white; cursor: pointer;">
                    Submit Entry
                </button>
            </form>
    </div>

     

    <script>
        // Date Validation
        document.querySelector('input[name="entry_date"]').addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            if (selectedDate.getDay() !== 1) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Date',
                    text: 'Please select a Monday'
                });
                this.value = '';
            }
        });

        // Form Validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const price = document.querySelector('input[name="price"]');
            const quantity = document.querySelector('input[name="quantity"]');
            
            if (price.value <= 0 || quantity.value <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Input',
                    text: 'Price and quantity must be positive values'
                });
            }
        });
    </script>
</body>
</html>