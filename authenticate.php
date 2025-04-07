<?php
session_start();
require 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['username']); // Changed from $username to $login to be more generic
    $password = trim($_POST['password']);
    $center_code = trim($_POST['center_code']);

    if (empty($login) || empty($password) || empty($center_code)) {
        header("Location: login.php?error=required");
        exit();
    }

    try {
        // Modified query to check both username and email fields
        $stmt = $conn->prepare("SELECT * FROM users WHERE (username = :login OR email = :login) AND is_active = TRUE");
        $stmt->execute([':login' => $login]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            // Verify that user has access to the selected center
            $stmt = $conn->prepare("SELECT * FROM centers WHERE center_code = :center_code AND is_active = TRUE");
            $stmt->execute([':center_code' => $center_code]);
            $center = $stmt->fetch();

            if (!$center || $user['center_code'] !== $center_code) {
                header("Location: login.php?error=center_access_denied");
                exit();
            }

            // Set session variables - ensure all necessary fields are included
            $_SESSION['users'] = [
                'id' => $user['user_id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'position' => $user['position'],
                'role' => $user['role'],
                'center_code' => $center['center_code'],
                'center_name' => $center['center_name'],
                'center_type' => $center['center_type'],
                'logo_path' => $center['logo_path'],
                'profile_image' => $user['profile_image'] ?? null // Added profile image if exists
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