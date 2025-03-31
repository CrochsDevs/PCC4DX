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

        /* Dark Mode Styles */
        .dark-mode {
            background-color: #121212;
            color: #f8f9fa;
        }

        .dark-mode .login-container {
            background: #1e1e1e;
            color: white;
            border-top: 4px solid rgb(255, 221, 0);
        }

        .dark-mode input,
        .dark-mode select {
            background-color: #333;
            color: white;
            border: 1px solid #777;
        }

        .dark-mode input:focus,
        .dark-mode select:focus {
            border-color: rgb(255, 221, 0);
            box-shadow: 0 0 0 3px rgba(255, 152, 0, 0.3);
        }

        .dark-mode .login-button {
            background-color: rgb(255, 221, 0);
            color: #121212;
        }

        .dark-mode .login-button:hover {
            background-color: rgb(255, 196, 0);
        }

        .dark-mode .login-help a {
            color: rgb(255, 221, 0);
        }

        /* Theme Toggle Button */
        .theme-toggle {
            position: absolute;
            top: 20px;
            right: 20px;
            background: #005ea2;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .dark-mode .theme-toggle {
            background: rgb(255, 221, 0);
            color: #121212;
        }

        .theme-toggle:hover {
            background: #004b87;
        }

        .dark-mode .theme-toggle:hover {
            background: rgb(255, 196, 0);
        }

        /* Logo Preview */
        .logo-preview {
            text-align: center;
            margin-bottom: 15px;
        }
        .logo-preview img {
            max-height: 80px;
            transition: filter 0.5s ease-in-out;
        }
        .dark-mode .logo-preview img {
            filter: brightness(0.8);
        }

        /* Error Message */
        .error-message {
            color: #dc3545;
            text-align: center;
            margin-bottom: 15px;
            font-size: 0.9rem;
        }
        .dark-mode .error-message {
            color: #ff6b6b;
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
                    // Database connection
                    require 'db_config.php';
                    
                    // Fetch active centers
                    $centers = $pdo->query("SELECT * FROM centers WHERE is_active = TRUE ORDER BY 
                                          CASE WHEN center_type = 'Headquarters' THEN 0 ELSE 1 END, 
                                          center_name")->fetchAll();
                    
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
        </div>
        <button id="theme-toggle" class="theme-toggle">🌙 Dark Mode</button>
    </div>

    <script>
        // Theme Toggle Functionality
        document.addEventListener("DOMContentLoaded", function () {
            const themeToggle = document.getElementById("theme-toggle");
            const body = document.body;

            // Check for saved user preference
            if (localStorage.getItem("theme") === "dark") {
                body.classList.add("dark-mode");
                themeToggle.textContent = "☀️ Light Mode";
            }

            themeToggle.addEventListener("click", function () {
                body.classList.toggle("dark-mode");

                if (body.classList.contains("dark-mode")) {
                    localStorage.setItem("theme", "dark");
                    themeToggle.textContent = "☀️ Light Mode";
                } else {
                    localStorage.setItem("theme", "light");
                    themeToggle.textContent = "🌙 Dark Mode";
                }
            });

            // Dynamic logo display and center type handling
            const centerSelect = document.getElementById("center_code");
            const centerLogo = document.getElementById("center-logo");
            const centerTypeInput = document.getElementById("center_type");
            
            centerSelect.addEventListener("change", function() {
                const selectedOption = this.options[this.selectedIndex];
                const logoPath = selectedOption.getAttribute("data-image");
                const centerType = selectedOption.getAttribute("data-type");
                
                if (logoPath) {
                    centerLogo.src = logoPath;
                }
                
                if (centerType) {
                    centerTypeInput.value = centerType;
                }
            });
        });
    </script>
</body>
</html>