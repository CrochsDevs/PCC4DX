<?php
session_start();
require 'auth_check.php';
include('db_config.php');

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

class CalfDropManager {
    private $db;
    private $centerCode;
    
    public function __construct($db, $centerCode) {
        $this->db = $db;
        $this->centerCode = $centerCode;
    }
    
    public function saveRecord($data) {
        if (empty($data['remarks'])) {
            $query = "INSERT INTO calf_drop (ai, bep, ih, private, center, date) 
                      VALUES (:ai, :bep, :ih, :private, :center, :date)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':ai' => $data['ai'],
                ':bep' => $data['bep'],
                ':ih' => $data['ih'],
                ':private' => $data['private'],
                ':center' => $this->centerCode,
                ':date' => $data['date']
            ]);
        } else {
            $query = "INSERT INTO calf_drop (ai, bep, ih, private, center, date, remarks) 
                      VALUES (:ai, :bep, :ih, :private, :center, :date, :remarks)";
            $stmt = $this->db->prepare($query);
            return $stmt->execute([
                ':ai' => $data['ai'],
                ':bep' => $data['bep'],
                ':ih' => $data['ih'],
                ':private' => $data['private'],
                ':center' => $this->centerCode,
                ':date' => $data['date'],
                ':remarks' => $data['remarks']
            ]);
        }
    }
    
public function validateInput($data) {
    $errors = [];
    
    $aiValid = isset($data['ai']) && is_numeric($data['ai']) && $data['ai'] > 0;
    $bepValid = isset($data['bep']) && is_numeric($data['bep']) && $data['bep'] > 0;
    $ihValid = isset($data['ih']) && is_numeric($data['ih']) && $data['ih'] > 0;
    $privateValid = isset($data['private']) && is_numeric($data['private']) && $data['private'] > 0;
    
    if (!($aiValid || $bepValid || $ihValid || $privateValid)) {
        $errors[] = "At least one of AI, BEP, IH, or Private must be a positive number greater than zero.";
    }
    
    return $errors;
}

}

$centerCode = $_SESSION['center_code'];
$calfDropManager = new CalfDropManager($conn, $centerCode);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_entry'])) {
    $data = [
        'ai' => $_POST['ai'] ?? 0,
        'bep' => $_POST['bep'] ?? 0,
        'ih' => $_POST['ih'] ?? 0,
        'private' => $_POST['private'] ?? 0,
        'date' => $_POST['date'] ?? date('Y-m-d'),
        'remarks' => $_POST['remarks'] ?? ''
    ];
    
    $errors = $calfDropManager->validateInput($data);
    
    if (empty($errors)) {
        $success = $calfDropManager->saveRecord($data);
        if ($success) {
            $_SESSION['success_message'] = "Calf Drop record saved successfully!";
            echo "<script>sessionStorage.setItem('showSuccess', '1'); window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
            exit;
        } else {
            $errors[] = "Failed to save record. Please try again.";
        }
    }
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="css/milk_report.css">
    <link rel="stylesheet" href="css/calf.css">
    <style>
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .modal-footer {
            border-top: 1px solid #eee;
            padding-top: 15px;
            margin-top: 15px;
            text-align: right;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .summary-total {
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
        }
        .btn-confirm {
            background-color: #28a745;
            color: white;
        }
        .btn-cancel {
            background-color: #dc3545;
            color: white;
            margin-right: 10px;
        }

        .entry-form {
            width: 80%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            height: 80vh; 
        }

        form {
            width: 100%;
            max-width: 600px;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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

        <nav>
            <ul>
                <li><a href="services.php" class="nav-link"><i class="fa-solid fa-arrow-left"></i> Back to quickfacts</a></li>
                <li><a href="cd_dashboard.php" class="nav-link"><i class="fas fa-chart-line"></i> Dashboard</a></li>
                <li><a href="calf_drop.php" class="nav-link active"><i class="fas fa-plus-circle"></i> Calf Drop</a></li>
                <li><a href="cd_report.php" class="nav-link"><i class="fas fa-file-alt"></i> Reports</a></li>
                <li><a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <h1>Daily Calf Drop</h1>
            </div>
            <!-- Notification Section -->   
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
                
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        
        <!-- Dashboard Section -->
        <div class="container">
            <?php if (!empty($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success_message'] ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="entry-form">
                <form id="calfDropForm" method="POST">
                    <div class="form-group">
                        <label class="form-label">AI</label>
                        <input type="number" step="1" name="ai" id="ai" class="form-input" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">BEP</label>
                        <input type="number" step="1" name="bep" id="bep" class="form-input" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">IH</label>
                        <input type="number" step="1" name="ih" id="ih" class="form-input" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Private</label>
                        <input type="number" step="1" name="private" id="private" class="form-input" value="0" min="0">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" id="date" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group" id="remarksGroup" style="display: none;">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" id="remarks" class="form-input" placeholder="Provide remarks for past date..." required></textarea>

                    </div>

                    <button type="button" id="submitBtn" class="form-input" style="background-color: var(--primary); color: white; cursor: pointer;">
                        Submit Entry
                    </button>
                </form>
            </div>      
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Calf Drop Entry</h3>
            </div>
            <div class="modal-body">
                <div id="summaryContent">
                    <!-- Summary will be inserted here by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-cancel" id="cancelBtn">Cancel</button>
                <button type="button" class="btn btn-confirm" id="confirmBtn">Confirm</button>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            // Show confirmation modal when submit button is clicked
            $('#submitBtn').click(function(e) {
                e.preventDefault();
                
                // Get form values
                const ai = parseInt($('#ai').val()) || 0;
                const bep = parseInt($('#bep').val()) || 0;
                const ih = parseInt($('#ih').val()) || 0;
                const privateVal = parseInt($('#private').val()) || 0;
                const date = $('#date').val();
                const total = ai + bep + ih + privateVal;
                
                // Build summary HTML
                let summaryHtml = `
                    <div class="summary-item"><span>AI:</span><span>${ai}</span></div>
                    <div class="summary-item"><span>BEP:</span><span>${bep}</span></div>
                    <div class="summary-item"><span>IH:</span><span>${ih}</span></div>
                    <div class="summary-item"><span>Private:</span><span>${privateVal}</span></div>
                    <div class="summary-item"><span>Date:</span><span>${date}</span></div>
                    <div class="summary-total"><span>Total Calves:</span><span>${total}</span></div>
                `;
                
                // Insert summary into modal
                $('#summaryContent').html(summaryHtml);
                
                // Show modal
                $('#confirmationModal').show();
            });
            
            // Handle cancel button
            $('#cancelBtn').click(function() {
                $('#confirmationModal').hide();
            });
            
            // Handle confirm button
            $('#confirmBtn').click(function() {
                // Submit the form
                $('#calfDropForm').append('<input type="hidden" name="submit_entry" value="1">');
                $('#calfDropForm').submit();
            });
            
            // Close modal when clicking outside
            $(window).click(function(e) {
                if (e.target === $('#confirmationModal')[0]) {
                    $('#confirmationModal').hide();
                }
            });
        });

        // Sweet alert
        document.addEventListener("DOMContentLoaded", function() {
            if (sessionStorage.getItem('showSuccess') === '1') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Calf Drop record saved successfully.',
                    confirmButtonColor: '#28a745'
                });
                sessionStorage.removeItem('showSuccess');
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            const dateInput = document.getElementById("date");
            const remarksGroup = document.getElementById("remarksGroup");
            const today = new Date().toISOString().split("T")[0];

            dateInput.setAttribute("max", today); // Restrict future dates
            dateInput.value = today;

            dateInput.addEventListener("change", function () {
                if (this.value < today) {
                    remarksGroup.style.display = "block";
                } else {
                    remarksGroup.style.display = "none";
                    document.getElementById("remarks").value = '';
                }
            });
        });

        $('#submitBtn').click(function(e) {
            e.preventDefault();
            
            const ai = parseInt($('#ai').val()) || 0;
            const bep = parseInt($('#bep').val()) || 0;
            const ih = parseInt($('#ih').val()) || 0;
            const privateVal = parseInt($('#private').val()) || 0;
            
            if (ai < 0 || bep < 0 || ih < 0 || privateVal < 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Entry',
                    text: 'All values must be positive numbers.',
                    confirmButtonColor: '#dc3545'
                });
                return; // Prevent modal and submission
            }
            
            const date = $('#date').val();
            const total = ai + bep + ih + privateVal;
            
            // Build summary HTML
            let summaryHtml = `
                <div class="summary-item"><span>AI:</span><span>${ai}</span></div>
                <div class="summary-item"><span>BEP:</span><span>${bep}</span></div>
                <div class="summary-item"><span>IH:</span><span>${ih}</span></div>
                <div class="summary-item"><span>Private:</span><span>${privateVal}</span></div>
                <div class="summary-item"><span>Date:</span><span>${date}</span></div>

                <div class="summary-total"><span>Total Calves:</span><span>${total}</span></div>
            `;
            
            $('#summaryContent').html(summaryHtml);
            $('#confirmationModal').show();
        });
    </script>
</body>
</html>