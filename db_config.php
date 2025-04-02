<?php
$host = 'localhost';
$dbname = 'pcc_auth_system';
$username = 'root';
$password = '';

try {
    // Use PDO to connect, but assign to $conn for consistency
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
