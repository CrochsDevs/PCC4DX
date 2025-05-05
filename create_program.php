<?php
session_start();
require_once 'db_config.php'; 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = htmlspecialchars(strip_tags(trim($_POST['name'])));
    $title = htmlspecialchars(strip_tags(trim($_POST['title'])));

    $profileImage = '';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        $uploadDir = 'uploads/programs/';
        $originalName = basename($_FILES['profile_image']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions)) {
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $profileImage = uniqid() . "_" . $originalName;
            $imagePath = $uploadDir . $profileImage;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $imagePath)) {
                $error = 'Failed to upload profile image.';
            }
        } else {
            $error = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        }
    }

    if (empty($error) && !empty($name) && !empty($title)) {
        try {
            $query = "INSERT INTO programs (name, title, profile_image, created_at, updated_at) 
                      VALUES (:name, :title, :profile_image, NOW(), NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':profile_image', $profileImage);
            $stmt->execute();

            $success = 'Program profile created successfully!';
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
    <title>Create Program Profile</title>
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

<a href="admin.php#programs-section" class="back-link">
    <i class="fas fa-arrow-left"></i> Back to Dashboard
</a>

<div class="announcement-container">
    <div class="form-header">
        <h2><i class="fas fa-user-friends"></i> Create New Program Profile</h2>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" id="name" required>
        </div>
        <div class="form-group">
            <label for="title">Position Title</label>
            <input type="text" name="title" id="title" required>
        </div>
        <div class="form-group">
            <label for="profile_image">Upload Profile Image</label>
            <input type="file" name="profile_image" id="profile_image">
        </div>
        <button type="submit" class="btn-submit">Create Profile</button>
    </form>
</div>

</body>
</html>
