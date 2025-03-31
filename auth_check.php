<?php
session_start();
require 'db_config.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Verify user still exists in database
$stmt = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND is_active = TRUE");
$stmt->execute([$_SESSION['user']['id']]);
if (!$stmt->fetch()) {
    session_destroy();
    header('Location: login.php?error=inactive');
    exit;
}
?>
