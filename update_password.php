<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

require_once 'db_config.php'; // Ensure this returns a PDO connection ($conn)

$userId = $_SESSION['user']['user_id'] ?? $_SESSION['user']['id'] ?? null;
$notification = '';
$notificationType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!$currentPassword || !$newPassword || !$confirmPassword) {
        $notification = 'All fields are required.';
        $notificationType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $notification = 'New password and confirmation do not match.';
        $notificationType = 'error';
    } else {
        // Fetch user info using PDO
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($currentPassword, $user['password_hash'])) {
            $newHashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            
            if ($updateStmt->execute([$newHashedPassword, $userId])) {
                $notification = 'Password updated successfully.';
                $notificationType = 'success';
            } else {
                $notification = 'Failed to update password. Please try again.';
                $notificationType = 'error';
            }
        } else {
            $notification = 'Current password is incorrect.';
            $notificationType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PCC - Update Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .govt-container {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header h2 {
            color: #003366;
            margin-bottom: 10px;
            font-size: 22px;
        }
        .form-header p {
            font-size: 14px;
            color: #666;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #003366;
            text-decoration: none;
            font-weight: 500;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            color: #003366;
            margin-bottom: 8px;
        }
        input[type="password"] {
            width: 100%;
            padding: 12px;
            font-size: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-family: 'Poppins', sans-serif;
        }
        .btn-update {
            background-color: #003366;
            color: white;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-weight: 500;
            font-size: 15px;
            cursor: pointer;
        }
        .btn-update:hover {
            background-color: #002244;
        }
        .notification {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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

<a href="admin.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

<div class="govt-container">
    <div class="form-header">
        <h2><i class="fas fa-lock"></i> Update Password</h2>
        <p>Change your current password to a new one</p>
    </div>

    <?php if ($notification): ?>
        <div class="notification <?= $notificationType ?>">
            <?= htmlspecialchars($notification) ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required>
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm New Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
        </div>

        <button type="submit" class="btn-update">Update Password</button>
    </form>
</div>

</body>
</html>
