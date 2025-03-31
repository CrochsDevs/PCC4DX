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
                    <option value="center1" data-image="images/headquarters.png">PCC-Headquarters</option>
                    <optgroup label="Regional Centers">
                    <option value="center2" data-image="images/clsu-pic.png">Central Luzon State University</option>
                    <option value="center3" data-image="images/cmu.png">Central Mindanao University</option>
                    <option value="center4" data-image="images/csu.png">Cagayan State University</option>
                    <option value="center5" data-image="images/dmmmsu-pic.png">Don Mariano Marcos Memorial State University</option> 
                    <option value="center6" data-image="images/genepool.jpg"> Gene Pool</option>
                    <option value="center7" data-image="images/lcsf.png">La Carlota Stock Farm</option>
                    <option value="center8" data-image="images/niz.jpg"> National Impact Zone</option>
                    <option value="center9" data-image="images/mlpc-pic.png">Mindanao Livestock Production Center</option>
                    <option value="center10" data-image="images/mmsu2.png"> Mariano Marcos State University</option>
                    <option value="center11" data-image="images/uplb.png"> University of the Philippines Los Baños</option>
                    <option value="center12" data-image="images/usf.jpg"> Ubay Stock Farm</option>
                    <option value="center13" data-image="images/usm.jpg"> University of Southern Mindanao</option>
                    <option value="center14" data-image="images/vsu.jpg"> Visayas State University</option> 
                    <option value="center15" data-image="images/wvsu.jpg"> West Visayas State University</option>
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
            <a href="help.php">Help</a>
        </div>
    </div>
</body>
</html>