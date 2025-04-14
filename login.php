<?php
require 'db_config.php';  // This includes your new database configuration file

// Fetch active centers from the database
try {
    $stmt = $conn->prepare("SELECT * FROM centers WHERE is_active = TRUE ORDER BY 
                            CASE WHEN center_type = 'Headquarters' THEN 0 ELSE 1 END, 
                            center_name");
    $stmt->execute();
    $centers = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Database query failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Carabao Center - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
    <style>
        /* Smooth Transition */
        body {
            transition: background-color 0.5s ease-in-out, color 0.5s ease-in-out;
        }

        .login-container, 
        .form-group input, 
        .form-group select, 
        .login-button {
            transition: background-color 0.5s ease-in-out, color 0.5s ease-in-out, border-color 0.5s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo-preview">
                <img id="center-logo" src="images/pccnewlogo.png" alt="Philippine Carabao Center Logo">
            </div>
            <h1>PCC-4DX</h1>
            <p>Fill up your details to login</p>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="error-message">
                <?php
                $errors = [
                    'invalid' => 'Invalid username or password',
                    'inactive' => 'Account is inactive',
                    'center' => 'Selected center is unavailable',
                    'required' => 'Please fill all fields',
                    'unauthorized' => 'You are not authorized to access this center'
                ];
                echo $errors[$_GET['error']] ?? 'Login error occurred';
                ?>
            </div>
        <?php endif; ?>

        <form action="authenticate.php" method="post">
            <!-- Location dropdown -->
            <div class="form-group location-group">
                <label for="center_code">Select Location</label>
                <select id="center_code" name="center_code" required>
                    <option value="" disabled selected>-- Select Location --</option>
                    <?php
                    foreach ($centers as $center):
                    ?>
                        <option value="<?= htmlspecialchars($center['center_code']) ?>" 
                                data-image="<?= htmlspecialchars($center['logo_path']) ?>"
                                data-type="<?= htmlspecialchars($center['center_type']) ?>">
                            <?= htmlspecialchars($center['center_name']) ?> (<?= htmlspecialchars($center['center_type']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Login form fields -->
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <input type="hidden" id="center_type" name="center_type" value="">

            <button type="submit" class="login-button">Login</button>
        </form>

        <div class="login-help">
            <a href="forgetpassword.php">Forgot password?</a> | 
            <a href="help.php">Help</a>
        

</body>
</html>
