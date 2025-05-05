<?php
session_start();
require_once 'db_config.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid program ID.');
}

$id = intval($_GET['id']);

try {
    $stmt = $conn->prepare("SELECT profile_image FROM programs WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $program = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($program) {

        if (!empty($program['profile_image']) && file_exists('uploads/programs/' . $program['profile_image'])) {
            unlink('uploads/programs/' . $program['profile_image']);
        }

        $stmt = $conn->prepare("DELETE FROM programs WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        header('Location: admin.php#programs-section');
        exit();
    } else {
        die('Program profile not found.');
    }
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
?>
