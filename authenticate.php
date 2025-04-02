<?php
session_start();
require 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $center_code = trim($_POST['center_code']);

    if (empty($username) || empty($password) || empty($center_code)) {
        header("Location: login.php?error=required");
        exit();
    }

    try {
        // Fetch user details
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = :username OR email = :username) AND is_active = TRUE");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Verify that user has access to the selected center
            $stmt = $pdo->prepare("SELECT * FROM centers WHERE center_code = :center_code AND is_active = TRUE");
            $stmt->execute([':center_code' => $center_code]);
            $center = $stmt->fetch();

            if (!$center || $user['center_code'] !== $center_code) {
                header("Location: login.php?error=center_access_denied");
                exit();
            }

            // Set session variables
            $_SESSION['user'] = [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'position' => $user['position'],
                'role' => $user['role'],  // Admin, Center_Admin, etc.
                'center_code' => $center['center_code'],
                'center_name' => $center['center_name'],
                'center_type' => $center['center_type'],
                'logo_path' => $center['logo_path']
            ];

            // Redirect based on center type
            if ($center['center_type'] === 'Headquarters') {
                header("Location: admin.php");
            } else {
                header("Location: center_dashboard.php");
            }
            exit();
        } else {
            header("Location: login.php?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        header("Location: login.php?error=db_error");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}
?>
