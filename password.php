<?php
session_start();
$db = new mysqli('localhost', 'root', '', 'pcc_auth_system');

// I-check kung naka-login ang user
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$current_password = $_POST['current_password'];
$new_password = $_POST['new_password'];
$confirm_password = $_POST['confirm_password'];

// Kunin ang current hashed password mula sa database
$stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($hashed_password);
$stmt->fetch();
$stmt->close();

// I-verify ang current password
if (password_verify($current_password, $hashed_password)) {
    // I-check kung match ang new at confirm password
    if ($new_password === $confirm_password) {
        // I-validate ang strength ng new password (halimbawa: 8 characters)
        if (strlen($new_password) >= 8) {
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // I-update ang password sa database
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hashed_password, $user_id);
            if ($stmt->execute()) {
                echo "Password successfully changed!";
            } else {
                echo "Error updating password.";
            }
        } else {
            echo "New password must be at least 8 characters.";
        }
    } else {
        echo "New passwords do not match.";
    }
} else {
    echo "Current password is incorrect.";
}

$db->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - PCC</title>
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
        input[type="password"] {
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
        .password-strength {
            margin-top: 5px;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <a href="admin.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <div class="govt-container">
        <div class="form-header">
            <h2><i class="fas fa-key"></i> Change Password</h2>
            <p>Update your account password securely</p>
        </div>
        
        <div id="notification" class="notification" style="display: none;"></div>
        
        <form id="passwordForm" method="POST">
            <div class="form-group">
                <label for="current_password"><i class="fas fa-lock"></i> Current Password</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>

            <div class="form-group">
                <label for="new_password"><i class="fas fa-key"></i> New Password</label>
                <input type="password" id="new_password" name="new_password" required>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-key"></i> Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" class="btn-update" id="submitBtn">Update Password</button>
        </form>
    </div>

    <script>
        // Password strength indicator
        document.getElementById('new_password').addEventListener('input', function() {
            const password = this.value;
            const strengthText = document.getElementById('passwordStrength');
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            const strengthLabels = ['Very Weak', 'Weak', 'Medium', 'Strong', 'Very Strong'];
            const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#218838'];
            
            strengthText.textContent = strength > 0 ? `Strength: ${strengthLabels[strength-1]}` : '';
            strengthText.style.color = strength > 0 ? strengthColors[strength-1] : '';
        });

        // Form submission handler
        document.getElementById('passwordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            const notification = document.getElementById('notification');
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            btn.disabled = true;
            notification.style.display = 'none';
            
            const formData = new FormData(form);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                notification.className = `notification ${data.success ? 'success' : 'error'}`;
                notification.innerHTML = `<i class="fas fa-${data.success ? 'check' : 'exclamation'}-circle"></i> ${data.message}`;
                notification.style.display = 'block';
                
                if(data.success) {
                    form.reset();
                }
            })
            .catch(error => {
                notification.className = 'notification error';
                notification.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred';
                notification.style.display = 'block';
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                notification.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>