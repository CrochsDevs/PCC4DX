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
                'volume' => $_POST['price'],
                'total' => $_POST['quantity'] * $_POST['price'],
                'status' => 'Pending',
                'center_code' => $this->centerCode
            ];

            $stmt = $this->conn->prepare("INSERT INTO milk_production 
                (entry_date, end_date, partner_id, quantity, volume, total, status, center_code)
                VALUES (:entry_date, :end_date, :partner_id, :quantity, :volume, :total, :status, :center_code)");

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

// Calculate totals
$totalQuantity = 0;
$totalTotal = 0;
$totalPrice = 0;
$count = 0;
foreach ($entries as $entry) {
    $totalQuantity += $entry['quantity'];
    $totalTotal += $entry['total'];
    $totalPrice += $entry['volume']; 
    $count ++; 
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
    <link rel="stylesheet" href="css/milk_report.css"> 
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
                    <th>Week</th>
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
                <tr style="background-color:rgb(198, 198, 198); font-weight: bold;">
                    <td colspan="4"> Total: <?= number_format($count) ?> </td>
                    <td><?= number_format($totalQuantity, 2) ?></td>
                    <td>₱<?= number_format($totalPrice, 2) ?></td> 
                    <td>₱<?= number_format($totalTotal, 2) ?></td>
                    <td colspan="2"></td>
                </tr>
                <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><?= date('o-\WW', strtotime($entry['entry_date'])) ?></td>
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
                        <a href="edit_entry.php?id=<?= $entry['id'] ?>" class="btn btn-edit">
                            <i class="fas fa-edit"></i>
                        </a>
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