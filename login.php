<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Carabao Center - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="images/pcclogo.png" alt="Philippine Carabao Center Logo">
            <h1>PCC-4DX</h1>
            <p>Fill up your details to login</p>
        </div>

        <form action="/login" method="post">
            <!-- Location dropdown at the top -->
            <div class="location-group">
                <label for="location">Select Location</label>
                <select id="location" name="location" required>
                    <option value="" disabled selected>-- Select Location --</option>
                    <option value="headquarters">Headquarters</option>
                    <optgroup label="Regional Centers">
                        <option value="center1">Center 1</option>
                        <option value="center2">Center 2</option>
                        <option value="center3">Center 3</option>
                        <option value="center4">Center 4</option>
                        <option value="center5">Center 5</option>
                        <option value="center6">Center 6</option>
                        <option value="center7">Center 7</option>
                        <option value="center8">Center 8</option>
                        <option value="center9">Center 9</option>
                        <option value="center10">Center 10</option>
                        <option value="center11">Center 11</option>
                        <option value="center12">Center 12</option>
                        <option value="center13">Center 13</option>
                    </optgroup>
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

            <button type="submit" class="login-button">Login</button>
        </form>

        <div class="login-help">
            <a href="forgetpassword.php">Forgot password?</a> | 
            <a href="/register">Help</a>
        </div>
    </div>
</body>
</html>