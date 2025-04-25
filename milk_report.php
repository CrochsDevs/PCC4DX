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
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['submit_entry'])) {
                $this->validateEntry();
                $this->saveEntry();
            } elseif (isset($_POST['delete_entry'])) {
                $this->deleteEntry($_POST['entry_id']);
            }
        }
        return [
            'partners' => $this->getActivePartners(),
            'entries' => $this->getAllEntries()
        ];
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
            $startDate = new DateTime($_POST['entry_date']);
            $endDate = clone $startDate;
            $endDate->modify('+6 days');
            
            $data = [
                'entry_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'partner_id' => $_POST['cooperative'],
                'quantity' => $_POST['quantity'],
                'price_per_kg' => $_POST['price'],
                'total' => $_POST['quantity'] * $_POST['price'],
                'status' => 'Pending',
                'center_code' => $this->centerCode
            ];

            $stmt = $this->conn->prepare("INSERT INTO milk_production 
                (entry_date, end_date, partner_id, quantity, price_per_kg, total, status, center_code)
                VALUES (:entry_date, :end_date, :partner_id, :quantity, :price_per_kg, :total, :status, :center_code)");

            $stmt->execute($data);
            $this->setMessage("Entry saved successfully!", "success");
        } catch (PDOException $e) {
            $this->setMessage("Error: " . $e->getMessage(), "danger");
        }
        $this->redirect();
    }

    private function deleteEntry($id) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM milk_production WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->setMessage("Entry deleted successfully!", "success");
        } catch (PDOException $e) {
            $this->setMessage("Error deleting entry: " . $e->getMessage(), "danger");
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

    private function getAllEntries() {
        try {
            $stmt = $this->conn->prepare("
                SELECT mp.*, p.partner_name 
                FROM milk_production mp
                JOIN partners p ON mp.partner_id = p.id
                WHERE mp.center_code = :center_code
                ORDER BY mp.entry_date DESC
            ");
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
$data = $entryManager->handleSubmission();
$partners = $data['partners'];
$entries = $data['entries'];
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
            --primary: #0056b3;
            --primary-hover: #004080;
            --background: #f8f9fa;
            --card-bg: #ffffff;
            --text: #212529;
            --border: #dee2e6;
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
        }

        .production-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 30px;
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .entry-form {
            margin-bottom: 40px;
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
            transition: border-color 0.3s ease;
        }

        .form-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }

        .entries-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }

        .entries-table th,
        .entries-table td {
            padding: 14px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .entries-table th {
            background-color: var(--primary);
            color: white;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .status-pending {
            background-color: var(--warning);
            color: #000;
        }

        .status-approved {
            background-color: var(--success);
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-delete {
            background-color: var(--danger);
            color: white;
        }

        @media (max-width: 768px) {
            .production-container {
                padding: 20px;
            }
            
            .entries-table th,
            .entries-table td {
                padding: 10px;
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
            <li><a href="milk_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
            <li><a href="partners.php" class="nav-link "><i class="fas fa-users"></i> Partners</a></li>
            <li><a href="new_entry.php" class="nav-link  "><i class="fas fa-users"></i> New Entry</a></li>
            <li><a href="milk_report.php" class="nav-link active"><i class="fas fa-file-alt"></i> Reports</a></li>
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


            <h2>Production Entries</h2>
            <table class="entries-table">
                <thead>
                    <tr>
                        <th>Week Start</th>
                        <th>Week End</th>
                        <th>Cooperative</th>
                        <th>Quantity (kg)</th>
                        <th>Price/kg</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($entries as $entry): ?>
                    <tr>

                        <td><?= date('M d, Y', strtotime($entry['entry_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($entry['end_date'])) ?></td>
                        <td><?= htmlspecialchars($entry['partner_name']) ?></td>
                        <td><?= number_format($entry['quantity'], 2) ?></td>
                        <td>₱<?= number_format($entry['volume'], 2) ?></td>
                        <td>₱<?= number_format($entry['total'], 2) ?></td>
                        <td>
                            <span class="status-badge <?= $entry['status'] === 'Approved' ? 'status-approved' : 'status-pending' ?>">
                                <?= $entry['status'] ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="entry_id" value="<?= $entry['id'] ?>">
                                <button type="submit" name="delete_entry" class="btn btn-delete" 
                                    onclick="return confirm('Are you sure you want to delete this entry?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
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