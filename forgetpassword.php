<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Carabao Center - Password Recovery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/forgetpassword.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="gov-container">
        <div class="gov-header">
            <img src="images/pcclogo.png" alt="Government Seal" class="gov-seal">
            <div class="gov-title">
                <h1>Philippine Carabao Center</h1>
                <p>Password Recovery System</p>
            </div>
        </div>

        <div class="gov-form-container">
            <form id="passwordRecoveryForm" class="gov-form">
                <div class="form-progress">
                    <div class="progress-step active">1. Verify Identity</div>
                    <div class="progress-step">2. Reset Password</div>
                    <div class="progress-step">3. Complete</div>
                </div>

                <div class="form-group">
                    <label for="govEmail">Official Email Address</label>
                    <input type="email" id="govEmail" name="email" required 
                           placeholder="Enter your registered email account">
                    <small class="form-text">Enter the email associated Email account</small>
                </div>

                <div class="form-group">
                    <label for="govEmployeeID">Employee ID Number</label>
                    <input type="text" id="govEmployeeID" name="employee_id" required
                           placeholder="Enter your government employee ID">
                </div>

                <div class="security-check">
                    <div class="captcha-container">                                         
                        <label>Security Verification</label>
                        <div class="captcha-box">PCC-2025</div>
                        <input type="text" placeholder="Enter PCC-2025" required>
                    </div>
                </div>

                <button type="submit" class="gov-button primary">
                    <i class="icon-shield"></i> Verify Identity
                </button>

                <div class="form-footer">
                    <a href="login.php" class="gov-link">
                        <i class="icon-arrow-left"></i> Back to Login
                    </a>
                    <span class="help-text">Need assistance? Contact <a href="pcc@email.com">IT Helpdesk</a></span> <!--email of pcc help desk-->
                </div>
            </form>
        </div>

        <footer class="gov-footer">
            <p>Official Philippine Carabao Center • Security System</p>
            <p>© 2025 Philippine Carabao Center. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>