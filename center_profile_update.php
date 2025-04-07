<?php
session_start();

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Debug session data (remove in production)
// echo '<pre>Session data: '; print_r($_SESSION); echo '</pre>';

// Get user ID from session - checking common key names
$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? null;

if (!$userId) {
    die("Error: User ID not found in session. Please check your login system.");
}

// Database configuration
$host = 'localhost';
$dbname = 'pcc_auth_system';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get current user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $userId]);
    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$userData) {
        die("Error: User not found in database.");
    }
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    $profileImage = $userData['profile_image'] ?? null;
    
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/profile_images/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileName = uniqid() . '_' . basename($_FILES['profile_image']['name']);
        $targetFile = $uploadDir . $fileName;
        
        $check = getimagesize($_FILES['profile_image']['tmp_name']);
        if ($check !== false) {
            if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
                if (!empty($userData['profile_image']) && file_exists($uploadDir . $userData['profile_image'])) {
                    @unlink($uploadDir . $userData['profile_image']);
                }
                $profileImage = $fileName;
            }
        }
    }
    
    // Get form data
    $full_name = $_POST['full_name'] ?? null;
    $email = $_POST['email'] ?? null;
    $position = $_POST['position'] ?? null;
    $contact = $_POST['contact'] ?? null;

    if (!$full_name || !$email) {
        echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
        exit;
    }

    try {
        // Update user data
        $stmt = $pdo->prepare("UPDATE users SET 
            full_name = :full_name,
            email = :email,
            position = :position,
            contact = :contact,
            profile_image = :profile_image
            WHERE user_id = :user_id");
        
        $stmt->execute([
            ':full_name' => $full_name,
            ':email' => $email,
            ':position' => $position,
            ':contact' => $contact,
            ':profile_image' => $profileImage,
            ':user_id' => $userId
        ]);
        
        // Update session data
        $_SESSION['user'] = array_merge($_SESSION['user'], [
            'full_name' => $full_name,
            'email' => $email,  
            'position' => $position,
            'contact' => $contact,
            'profile_image' => $profileImage
        ]);
        
        echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCC - Profile Update</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
            line-height: 1.6;
        }
        .govt-container {
            max-width: 600px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .form-header h2 {
            color: #003366;
            margin: 0 0 5px 0;
            font-size: 22px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #003366;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link i {
            margin-right: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #003366;
            font-size: 14px;
        }
        input[type="text"], 
        input[type="email"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        .btn-update {
            background-color: #003366;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            margin-top: 20px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-update:hover {
            background-color: #002244;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #003366;
            display: block;
        }
        .image-preview-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            display: none;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <a href="center_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <div class="govt-container">
        <div class="form-header">
            <h2><i class="fas fa-user-cog"></i> Update Profile Information</h2>
            <p>Please update your personal details below</p>
        </div>
        
        <div id="notification" class="notification" style="display: none;"></div>
        
        <form id="profileForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="user_id" value="<?= htmlspecialchars($userData['user_id']) ?>">
            
            <div class="image-preview-container">
                <?php if (!empty($userData['profile_image'])): ?>
                    <img src="uploads/profile_images/<?= htmlspecialchars($userData['profile_image']) ?>" class="profile-picture" id="profilePreview">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($userData['full_name']) ?>&size=150" class="profile-picture" id="profilePreview">
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="profile_image">Profile Picture:</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
            </div>
            
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" 
                     value="<?= htmlspecialchars($userData['full_name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" 
                     value="<?= htmlspecialchars($userData['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="position">Position/Title:</label>
                <input type="text" id="position" name="position" 
                     value="<?= htmlspecialchars($userData['position'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="contact">Contact Number:</label>
                <input type="text" id="contact" name="contact" 
                     value="<?= htmlspecialchars($userData['contact'] ?? '') ?>">
            </div>
            
            <button type="submit" class="btn-update" id="submitBtn">Update Profile</button>
        </form>
    </div>

    <script>
        // Preview image before upload
        document.getElementById('profile_image').addEventListener('change', function(e) {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profilePreview').src = e.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        // Form submission with AJAX
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const btn = document.getElementById('submitBtn');
            const originalText = btn.textContent;
            const notification = document.getElementById('notification');
            
            btn.textContent = 'Processing...';
            btn.disabled = true;
            notification.style.display = 'none';
            
            const formData = new FormData(form);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    notification.className = 'notification success';
                    notification.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                    notification.style.display = 'block';
                    
                    // Update profile picture in case it was changed
                    if (document.getElementById('profile_image').files.length > 0) {
                        document.getElementById('profilePreview').src = URL.createObjectURL(
                            document.getElementById('profile_image').files[0]
                        );
                    }
                } else {
                    notification.className = 'notification error';
                    notification.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                    notification.style.display = 'block';
                }
            })
            .catch(error => {
                notification.className = 'notification error';
                notification.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
                notification.style.display = 'block';
                console.error('Error:', error);
            })
            .finally(() => {
                btn.textContent = originalText;
                btn.disabled = false;
                
                // Scroll to notification
                notification.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>