<?php
require 'db_config.php';

try {
    $stmt = $conn->prepare("SELECT announcement_id, title, image, created_at FROM announcement ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($announcements) {
        foreach ($announcements as $row) {
            $date = date("F j, Y", strtotime($row['created_at']));
            $imagePath = 'uploads/' . htmlspecialchars($row['image']);
            $title = htmlspecialchars($row['title']);
            $id = $row['announcement_id'];

            echo '
            <article class="news-card">
                <img src="' . $imagePath . '" alt="Announcement Image">
                <div class="news-content">
                    <span class="news-date">' . $date . '</span>
                    <h3>' . $title . '</h3>
                    <a href="announcement_view.php?id=' . $id . '" class="read-more">Read More <i class="fas fa-arrow-right"></i></a>
                </div>
            </article>';
        }
    } else {
        echo '<p>No announcements available at the moment.</p>';
    }
} catch (PDOException $e) {
    echo '<p>Error: ' . $e->getMessage() . '</p>';
}
?>
