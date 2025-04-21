<?php
include 'db_config.php';

if (isset($_GET['announcement_id']) && is_numeric($_GET['announcement_id'])) {
    $announcement_id = intval($_GET['announcement_id']);

    try {
        $stmt = $conn->prepare("DELETE FROM announcement WHERE announcement_id = :announcement_id");
        $stmt->bindParam(':announcement_id', $announcement_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Announcement deleted successfully!'); window.location.href='admin.php#announcement-section';</script>";
        } else {
            echo "<script>alert('Error: Announcement not found or could not be deleted.'); window.location.href='admin.php#announcement-section';</script>";
        }
    } catch (PDOException $e) {
        echo "<script>alert('Error: " . $e->getMessage() . "'); window.location.href='admin.php#announcement-section';</script>";
    }
}
?>
