<?php
session_start();
require 'auth_check.php';

// Prevent headquarters users from accessing center dashboard
if ($_SESSION['user']['center_type'] === 'Headquarters') {
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
</head>
<body>
    <h1>Welcome to <?= htmlspecialchars($_SESSION['user']['center_name']) ?></h1>
    <p>You are logged in as: <?= htmlspecialchars($_SESSION['user']['position']) ?></p>
    <a href="logout.php">Logout</a>
</body>
</html>
