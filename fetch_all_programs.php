<?php
require 'db_config.php';

try {
    $stmt = $conn->prepare("SELECT id, name, title, profile_image, created_at FROM programs ORDER BY created_at DESC");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($programs) {
        foreach ($programs as $row) {
            $profileImage = 'uploads/programs/' . htmlspecialchars($row['profile_image']);
            $name = htmlspecialchars($row['name']);
            $title = htmlspecialchars($row['title']);

            echo '
            <article class="news-card">
                <img src="' . $profileImage . '" alt="Profile Image">
                <h4>' . $name . '</h4>
                <p>' . $title . '</p>
            </article>';
        }
    } else {
        echo '<p>No programs available at the moment.</p>';
    }
} catch (PDOException $e) {
    echo '<p>Error: ' . $e->getMessage() . '</p>';
}
?>
