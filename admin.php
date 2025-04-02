<?php
session_start();
require 'auth_check.php';

// Ensure only HQ admins can access
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
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar">
        <h2>PCC Admin</h2>
        <ul>
            <li><a href="#">Dashboard</a></li>
            <li><a href="#">Manage Users</a></li>
            <li><a href="#">Reports</a></li>
            <li><a href="#">Settings</a></li>
        </ul>
    </div>

    <!-- Navbar -->
    <div class="navbar">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
        <h1>Welcome, <?= htmlspecialchars($_SESSION['user']['full_name']) ?> (HQ Admin)</h1>
        <a href="logout.php" class="logout">Logout</a>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2>Dashboard</h2>
        <p>Manage PCC Headquarters operations here.</p>
    </div>

    <script>
        function toggleSidebar() {
            document.body.classList.toggle('collapsed');
        }
    </script>

</body>
</html>