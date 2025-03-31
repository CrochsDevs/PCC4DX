<?php
session_start();
require 'auth_check.php';

// Only allow regional center access
if ($_SESSION['user']['center_type'] !== 'Regional') {
    header('Location: access_denied.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .header { background: #003366; color: white; padding: 1rem; display: flex; justify-content: space-between; }
        .center-logo { height: 50px; margin-right: 1rem; }
        .main-content { padding: 2rem; }
    </style>
</head>
<body>
    <div class="header">
        <div style="display: flex; align-items: center;">
            <img src="<?= htmlspecialchars($_SESSION['user']['logo_path']) ?>" class="center-logo">
            <h1><?= htmlspecialchars($_SESSION['user']['center_name']) ?> Dashboard</h1>
        </div>
        <div>
            Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> | 
            <a href="logout.php" style="color: white;">Logout</a>
        </div>
    </div>
    
    <div class="main-content">
        <h2>Center Operations</h2>
        <p>You are logged in as: <?= htmlspecialchars($_SESSION['user']['position']) ?></p>
    </div>
</body>
</html>
