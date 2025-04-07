<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Philippine Carabao Center - Password Recovery</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/forgetpassword.css"> 

</head>
<body>
    <div class="gov-container">
        <div class="gov-header">
            <div class="gov-title">
                <h1>Philippine Carabao Center</h1>
                <p>Password Recovery System</p>
            </div>
        </div>
        
        <div class="gov-form-container">
            <!-- Step 1: Verify Identity -->
            <form id="verifyIdentityForm" class="gov-form active">
                <div class="form-progress">
                    <div class="progress-step active">1. Verify Identity</div>
                    <div class="progress-step">2. Reset Password</div>
                    <div class="progress-step">3. Complete</div>
                </div>

                <div class="form-group">
                    <label for="govEmail">Official Email Address</label>
                    <input type="email" id="govEmail" name="email" required 
                           placeholder="Enter your registered email account">
                    <small class="form-text">Enter the email associated with your account</small>
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
                    <i class="fas fa-shield-alt"></i> Verify Identity
                </button>

                <div class="form-footer">
                    <a href="login.php" class="gov-link">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                    <span class="help-text">Need assistance? Contact <a href="mailto:pcc@email.com">IT Helpdesk</a></span>
                </div>
            </form>

            <!-- Step 2: Reset Password -->
            <form id="resetPasswordForm" class="gov-form">
                <div class="form-progress">
                    <div class="progress-step completed">1. Verify Identity</div>
                    <div class="progress-step active">2. Reset Password</div>
                    <div class="progress-step">3. Complete</div>
                </div>

                <div class="verification-notice">
                    <i class="fas fa-envelope verification-icon"></i>
                    <h3>Verification Code Sent</h3>
                    <p>We've sent a 6-digit verification code to your registered email address.</p>
                </div>

                <div class="form-group">
                    <label for="verificationCode">Verification Code</label>
                    <input type="text" id="verificationCode" name="verification_code" required
                           placeholder="Enter 6-digit code" maxlength="6">
                </div>

                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <div class="password-input">
                        <input type="password" id="newPassword" name="new_password" required
                               placeholder="Create new password">
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                    <small class="form-text">Minimum 8 characters with at least 1 number and 1 special character</small>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <div class="password-input">
                        <input type="password" id="confirmPassword" name="confirm_password" required
                               placeholder="Re-enter new password">
                        <i class="fas fa-eye toggle-password"></i>
                    </div>
                </div>

                <button type="submit" class="gov-button primary">
                    <i class="fas fa-key"></i> Reset Password
                </button>

                <div class="form-footer">
                    <a href="#" class="gov-link resend-code">
                        <i class="fas fa-redo"></i> Resend Verification Code
                    </a>
                </div>
            </form>

            <!-- Step 3: Complete -->
            <form id="completeForm" class="gov-form">
                <div class="form-progress">
                    <div class="progress-step completed">1. Verify Identity</div>
                    <div class="progress-step completed">2. Reset Password</div>
                    <div class="progress-step active">3. Complete</div>
                </div>

                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>Password Reset Successful!</h3>
                    <p>Your password has been successfully updated. You can now login with your new password.</p>
                </div>

                <a href="login.php" class="gov-button success">
                    <i class="fas fa-sign-in-alt"></i> Proceed to Login
                </a>

                <div class="form-footer">
                    <span class="help-text">If you didn't request this change, please contact <a href="lester.s.rodriguez.211@gmail.com">IT Helpdesk</a> immediately</span>
                </div>
            </form>
        </div>

        <footer class="gov-footer">
            <p>Official Philippine Carabao Center • Security System</p>
            <p>© 2025 Philippine Carabao Center. All rights reserved.</p>
        </footer>
    </div>

    <script src="js/forgetpassword.js"></script>
</body>
</html>