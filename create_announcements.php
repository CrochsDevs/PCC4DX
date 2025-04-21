<?php
session_start();
require_once 'db_config.php'; 

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));
    $content = htmlspecialchars(strip_tags(trim($_POST['content'])));

    // Handle image
    $imageName = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadDir = 'uploads/';
        $originalName = basename($_FILES['image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            $imageName = uniqid() . "_" . $originalName;
            $imagePath = $uploadDir . $imageName;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $imagePath)) {
                $error = 'Failed to upload image.';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($error) && !empty($title) && !empty($content)) {
        try {
            $query = "INSERT INTO announcement (title, content, image, created_at, updated_at) 
                      VALUES (:title, :content, :image, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':image', $imageName);
            $stmt->execute();

            $success = 'Announcement created successfully!';
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
    <title>Create Announcement</title>
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
    </style>
</head>
<body>
<a href="admin.php#announcement-section" class="back-link">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
</a>

<div class="announcement-container">
    <div class="form-header">
        <h2><i class="fas fa-bullhorn"></i> Create New Announcement</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Title</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div class="form-group">
            <label for="content">Content</label>
            <textarea name="content" id="content" rows="5" required></textarea>
        </div>
        <div class="form-group">
            <label for="image">Upload Image</label>
            <input type="file" name="image" id="image">
        </div>
        <button type="submit" class="btn-submit">Create Announcement</button>
    </form>
</div>
</body>
</html>
