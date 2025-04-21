<?php
include 'db_config.php'; 

try {
    $stmt = $conn->prepare("SELECT announcement_id, title, image, created_at FROM announcement ORDER BY created_at DESC LIMIT 3");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($announcements) {
        foreach ($announcements as $row) {
            $date = date("F j, Y", strtotime($row['created_at']));
            $image = 'uploads/'.htmlspecialchars($row['image']); 
            $title = htmlspecialchars($row['title']);
            $id = $row['announcement_id'];

            echo '
            <article class="news-card">
                <img src="' . $image . '" alt="News">
                <div class="news-content">
                    <span class="news-date">' . $date . '</span>
                    <h3>' . $title . '</h3>
                </div>
            </article>';
        }
    } else {
        echo '<p>No announcements yet.</p>';
    }

} catch (PDOException $e) {
    echo '<p>Error fetching announcements: ' . $e->getMessage() . '</p>';
}
?>
