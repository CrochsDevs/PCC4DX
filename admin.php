<?php
session_start();
require 'auth_check.php';

// Only allow HQ admin access
if ($_SESSION['user']['center_type'] !== 'Headquarters') {
    header('Location: access_denied.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCC Headquarters Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        .header { background: #003366; color: white; padding: 1rem; display: flex; justify-content: space-between; }
        .sidebar { width: 250px; background: #f5f5f5; height: 100vh; padding: 1rem; }
        .main-content { margin-left: 250px; padding: 2rem; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PCC Headquarters Admin Panel</h1>
        <div>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?></div>
    </div>
    
    <div class="sidebar">
        <h3>Admin Menu</h3>
        <ul>
            <li><a href="#">Manage Centers</a></li>
            <li><a href="#">User Accounts</a></li>
            <li><a href="#">Reports</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>
    
    <div class="main-content">
        <h2>Central Monitoring Dashboard</h2>
        <p>You are logged in as: <?= htmlspecialchars($_SESSION['user']['position']) ?></p>
    </div>
</body>
</html>
