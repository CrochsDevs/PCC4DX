<?php
session_start();
require 'auth_check.php';

if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require 'db_config.php'; // Include PDO connection
    
    try {
        // Handle profile info update
        if (isset($_POST['update_info'])) {
            // Sanitize inputs
            $full_name = htmlspecialchars(trim($_POST['full_name']));
            $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
            $contact_number = htmlspecialchars(trim($_POST['contact_number']));
            
            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error_message = "Invalid email format!";
            } else {
                // Handle file upload if present
                $image_updated = false;
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
                    $target_dir = "uploads/profile_images/";
                    if (!file_exists($target_dir)) {
                        mkdir($target_dir, 0777, true);
                    }

                    $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
                    $filename = 'profile_' . $_SESSION['user']['user_id'] . '_' . time() . '.' . $file_extension;
                    $target_file = $target_dir . $filename;

                    // Check if valid image
                    $check = getimagesize($_FILES['profile_image']['tmp_name']);
                    if ($check !== false) {
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
                            // Delete old image if exists
                            if (!empty($_SESSION['user']['profile_image'])) {
                                $old_file = $target_dir . $_SESSION['user']['profile_image'];
                                if (file_exists($old_file)) {
                                    unlink($old_file);
                                }
                            }

                            // Update profile image in database
                            $query = "UPDATE users SET profile_image = :profile_image WHERE user_id = :user_id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':profile_image', $filename);
                            $stmt->bindParam(':user_id', $_SESSION['user']['user_id'], PDO::PARAM_INT);
                            if ($stmt->execute()) {
                                $_SESSION['user']['profile_image'] = $filename;
                                $image_updated = true;
                            } else {
                                $error_message = "Error updating profile image: " . implode(":", $stmt->errorInfo());
                            }
                        } else {
                            $error_message = "Failed to upload profile image.";
                        }
                    } else {
                        $error_message = "File is not a valid image.";
                    }
                }
                
                // Only proceed with basic info update if there was no image upload error
                if (empty($error_message)) {
                    $query = "UPDATE users SET full_name = :full_name, email = :email, contact = :contact WHERE user_id = :user_id";
                    $stmt = $conn->prepare($query);
                    $stmt->bindParam(':full_name', $full_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':contact', $contact_number);
                    $stmt->bindParam(':user_id', $_SESSION['user']['user_id'], PDO::PARAM_INT);
                    
                    if ($stmt->execute()) {
                        $_SESSION['user']['full_name'] = $full_name;
                        $_SESSION['user']['email'] = $email;
                        $_SESSION['user']['contact_number'] = $contact_number;
                        $success_message = "Account information updated successfully" . ($image_updated ? " with new profile image" : "") . "!";
                    } else {
                        $error_message = "Error updating account: " . implode(":", $stmt->errorInfo());
                    }
                }
            }
        }
        
        // Handle password change
        if (isset($_POST['update_password'])) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $error_message = "All fields are required!";
            } else {
                $query = "SELECT password_hash FROM users WHERE user_id = :user_id";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':user_id', $_SESSION['user']['user_id'], PDO::PARAM_INT);
                $stmt->execute();
                
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify current password
                if ($user && password_verify($current_password, $user['password_hash'])) {
                    // Check if new password matches confirm password
                    if ($new_password === $confirm_password) {
                        // Validate password strength
                        if (strlen($new_password) < 8) {
                            $error_message = "Password must be at least 8 characters long!";
                        } else {
                            // Hash new password
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                            $update_query = "UPDATE users SET password_hash = :password_hash WHERE user_id = :user_id";
                            $update_stmt = $conn->prepare($update_query);
                            $update_stmt->bindParam(':password_hash', $hashed_password);
                            $update_stmt->bindParam(':user_id', $_SESSION['user']['user_id'], PDO::PARAM_INT);

                            if ($update_stmt->execute()) {
                                $success_message = "Password updated successfully!";
                            } else {
                                $error_message = "Error updating password: " . implode(":", $update_stmt->errorInfo());
                            }
                        }
                    } else {
                        $error_message = "New password and confirm password do not match!";
                    }
                } else {
                    $error_message = "Current password is incorrect!";
                }
            }
        }

    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }

    // Close the connection
    $conn = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - PCC Headquarters Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0056b3;
            --secondary-color: #003d82;
            --success-color: #38a169;
            --danger-color: #e53e3e;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #e2e8f0;
            --text-color: #2d3748;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
            color: var(--text-color);
            line-height: 1.6;
        }
        
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
            transition: margin-left 0.3s;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header h1 {
            color: var(--primary-color);
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: var(--danger-color);
            color: white;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background-color: #c53030;
            transform: translateY(-2px);
        }
        
        .settings-container {
            max-width: 1000px;
            margin: 0 auto;
            width: 100%;
        }
        
        .settings-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
        }
        
        .settings-card h3 {
            margin-bottom: 25px;
            color: var(--primary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 15px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 40px 12px 12px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 86, 179, 0.1);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            font-size: 16px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: rgba(56, 161, 105, 0.1);
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }
        
        .alert-danger {
            background-color: rgba(229, 62, 62, 0.1);
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }
        
        .profile-image-container {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary-color);
        }
        
        .image-upload {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .image-upload label {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            background-color: var(--light-color);
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .image-upload label:hover {
            background-color: #e2e6ea;
        }
        
        .image-preview {
            display: none;
            max-width: 200px;
            margin-top: 10px;
            border-radius: 8px;
        }
        
        .password-toggle {
            position: absolute;
            right: 12px;
            top: 38px;
            cursor: pointer;
            color: #666;
            z-index: 10;
            background: none;
            border: none;
            font-size: 16px;
        }
        
        .password-toggle:hover {
            color: var(--primary-color);
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 14px;
        }
        
        .strength-weak {
            color: var(--danger-color);
        }
        
        .strength-medium {
            color: orange;
        }
        
        .strength-strong {
            color: var(--success-color);
        }
        
        /* Full-screen optimizations */
        @media (min-width: 1200px) {
            .settings-container {
                max-width: 1200px;
            }
            
            .settings-card {
                padding: 40px;
            }
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
            
            .settings-card {
                padding: 20px;
            }
            
            .profile-image-container {
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="header">
            <div class="header-left">
                <h1><i class="fas fa-user-cog"></i> Account Settings</h1>
            </div>
            <div class="header-right">
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="settings-container">
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="settings-card">
                <h3><i class="fas fa-user-edit"></i> Basic Information</h3>
                
                <div class="profile-image-container">
                    <?php 
                    $profile_image = !empty($_SESSION['user']['profile_image']) ? 
                        'uploads/profile_images/' . $_SESSION['user']['profile_image'] : 
                        'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['user']['full_name']) . '&size=100&background=0056b3&color=fff';
                    ?>
                    <img src="<?php echo $profile_image; ?>" alt="Profile Image" class="profile-image" id="profile-image-preview">
                    
                    <div class="image-upload">
                        <label for="profile_image">
                            <i class="fas fa-camera"></i> Change Photo
                            <input type="file" id="profile_image" name="profile_image" accept="image/*" style="display: none;" onchange="previewImage(this)">
                        </label>
                        <small>Max 2MB (JPG, PNG)</small>
                        <img id="image-preview" class="image-preview" alt="Preview">
                    </div>
                </div>
                
                <form method="POST" action="account.php" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($_SESSION['user']['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($_SESSION['user']['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="contact_number">Contact Number</label>
                        <input type="tel" id="contact_number" name="contact_number" class="form-control" 
                               value="<?php echo htmlspecialchars($_SESSION['user']['contact_number'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" name="update_info" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>

            <div class="settings-card">
                <h3><i class="fas fa-lock"></i> Change Password</h3>
                <form method="POST" action="account.php">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('current_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <small class="text-muted">Minimum 8 characters</small>
                        <div id="password-strength" class="password-strength"></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-primary">
                        <i class="fas fa-key"></i> Update Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(input) {
            const preview = document.getElementById('image-preview');
            const profilePreview = document.getElementById('profile-image-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    
                    // Also update the profile preview immediately
                    profilePreview.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            // Toggle password visibility
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthIndicator = document.getElementById('password-strength');
            
            if (password.length === 0) {
                strengthIndicator.textContent = '';
                strengthIndicator.className = 'password-strength';
                return;
            }
            
            // Simple strength check
            let strength = 0;
            let className = '';
            let text = '';
            
            // Length check
            if (password.length < 6) {
                strength = 1;
            } else if (password.length < 10) {
                strength = 2;
            } else {
                strength = 3;
            }
            
            // Character type checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Determine strength level
            if (strength <= 2) {
                text = 'Weak';
                className = 'strength-weak';
            } else if (strength <= 4) {
                text = 'Medium';
                className = 'strength-medium';
            } else {
                text = 'Strong';
                className = 'strength-strong';
            }
            
            strengthIndicator.textContent = `Password strength: ${text}`;
            strengthIndicator.className = `password-strength ${className}`;
        });
    </script>
</body>
</html>