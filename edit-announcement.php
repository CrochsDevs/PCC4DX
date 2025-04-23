<?php
session_start();
include 'db_config.php';

$error = '';
$success = '';
$announcement = null;

if (isset($_GET['announcement_id'])) {
    $announcement_id = intval($_GET['announcement_id']);

    $stmt = $conn->prepare("SELECT title, content, image FROM announcement WHERE announcement_id = :announcement_id");
    $stmt->bindParam(':announcement_id', $announcement_id);
    $stmt->execute();
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$announcement) {
        $error = 'Announcement not found!';
    }
} else {
    $error = 'Invalid announcement ID!';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $content = htmlspecialchars(strip_tags(trim($_POST['content'])));
    $imageName = $announcement['image']; // Keep current image by default

    // Image upload logic
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadDir = 'uploads/';
        $originalName = basename($_FILES['image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            // Generate unique name & path
            $newImageName = uniqid() . "_" . $originalName;
            $newImagePath = $uploadDir . $newImageName;

            // Move file and delete old image if successful
            if (move_uploaded_file($_FILES['image']['tmp_name'], $newImagePath)) {
                // Delete old image
                if (!empty($announcement['image']) && file_exists($uploadDir . $announcement['image'])) {
                    unlink($uploadDir . $announcement['image']);
                }
                $imageName = $newImageName;
            } else {
                $error = 'Failed to upload new image.';
            }
        } else {
            $error = 'Invalid image type. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $stmt = $conn->prepare("UPDATE announcement SET title = :title, content = :content, image = :image, updated_at = NOW() WHERE announcement_id = :announcement_id");
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':image', $imageName);
            $stmt->bindParam(':announcement_id', $announcement_id);
            $stmt->execute();

            $success = 'Announcement updated successfully!';

            // Refresh $announcement with new data
            $announcement['title'] = $title;
            $announcement['content'] = $content;
            $announcement['image'] = $imageName;
        } catch (PDOException $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    } elseif (empty($error)) {
        $error = 'Please fill in all fields!';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Announcement</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .announcement-container {
            max-width: 700px;
            margin: 20px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-header {
            margin-bottom: 25px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }
        .form-header h2 {
            color: #003366;
            margin: 0;
            font-size: 24px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #003366;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link i {
            margin-right: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #003366;
            font-size: 14px;
        }
        input[type="text"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 15px;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        .btn-submit {
            background-color: #003366;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 15px;
            width: 100%;
            margin-top: 10px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #002244;
        }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .current-image {
            margin-top: 10px;
        }
        .current-image img {
            max-width: 200px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<a href="admin.php" class="back-link">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
</a>

<div class="announcement-container">
    <div class="form-header">
        <h2><i class="fas fa-edit"></i> Edit Announcement</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <?php if ($announcement): ?>
    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" value="<?= htmlspecialchars($announcement['title']) ?>" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea name="content" rows="5" required><?= htmlspecialchars($announcement['content']) ?></textarea>
        </div>
        <div class="form-group">
            <label for="image">Replace Image</label>
            <input type="file" name="image">
            <?php if (!empty($announcement['image'])): ?>
                <div class="current-image">
                    <p>Current Image:</p>
                    <img src="uploads/<?= htmlspecialchars($announcement['image']) ?>" alt="Current Image">
                </div>
            <?php endif; ?>
        </div>
        <button type="submit" class="btn-submit">Update Announcement</button>
    </form>
    <?php endif; ?>
</div>
</body>
</html>
