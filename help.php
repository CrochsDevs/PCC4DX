<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PCC Help Center</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/help.css">
</head>
<body>
    <div class="gov-container">
        <div class="gov-header">
            <div class="gov-title">
                <h1>Philippine Carabao Center</h1>
                <p>Help Center</p>
            </div>
        </div>

        <div class="help-container">
            <div class="help-sidebar">
                <h3>Help Topics</h3>
                <ul>
                    <li><a href="#login-help" class="active"><i class="fas fa-sign-in-alt"></i> How to Login</a></li>
                    <li><a href="#forgotpass-help"><i class="fas fa-key"></i> Password Recovery</a></li>
                    <li><a href="#contact-help"><i class="fas fa-headset"></i> Contact Support</a></li>
                </ul>
            </div>

            <div class="help-content">
                <!-- Login Help Section -->
                <section id="login-help" class="help-section active">
                    <h2><i class="fas fa-sign-in-alt"></i> How to Login to PCC System</h2>
                    <div class="help-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Access the Login Page</h3>
                                <p>Go to the PCC Employee Portal at <a href="https://portal.pcc.gov.ph" target="_blank">4DX.pcc.gov.ph</a></p>
                                <img src="images/login.png" alt="Login Page" class="help-image">
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Select Location</h3>
                                <ul>
                                    <li>Choose Headquarters if your are admin in PCC-Headquarters</li>
                                    <li>Choose center if you are admin center</li>
                                </ul>
                            </div>
                        </div>

                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Enter Your Credentials</h3>
                                <ul>
                                    <li><strong>Username or Email:</strong> Enter your assigned username or email</li>
                                    <li><strong>Password:</strong> Your assigned or personal password</li>
                                </ul>
                            </div>
                        </div>
                  
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h3>Click Login</h3>
                                <p>Press the <button class="demo-button"><i class="fas fa-sign-in-alt"></i> Login</button> button to access your account</p>
                                <div class="help-note">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <p>If you forgot your password, click <a href="forgetpassword.php">"Forgot Password"</a> below the login button</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Forgot Password Help Section -->
                <section id="forgotpass-help" class="help-section">
                    <h2><i class="fas fa-key"></i> Password Recovery Process</h2>
                    <div class="help-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h3>Access Password Recovery</h3>
                                <p>Click on <a href="forgetpassword.php">"Forgot Password"</a> link on the login page</p>
                                <img src="images/forget.png" alt="Forgot Password Page" class="help-image">
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h3>Verify Your Identity</h3>
                                <ul>
                                    <li>Enter your registered PCC email account or username</li>
                                    <li>Provide your employee ID number</li>
                                    <li>Complete the CAPTCHA verification</li>
                                    <li>Enter PCC - 2025 for captcha verification</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h3>Check Your Email</h3>
                                <p>You'll receive a 6-digit verification code at your registered email address</p>
                                <div class="help-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <p>The verification code expires after 5 minutes</p>
                                </div>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h3>Create New Password</h3>
                                <ul>
                                    <li>Enter the verification code received</li>
                                    <li>Create a new strong password (minimum 8 characters)</li>
                                    <li>Confirm your new password</li>
                                </ul>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">5</div>
                            <div class="step-content">
                                <h3>Password Updated</h3>
                                <p>You'll see a confirmation message and can now login with your new password</p>
                                <div class="help-tip">
                                    <i class="fas fa-lightbulb"></i>
                                    <p>Remember to store your password securely and don't share it with anyone</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Contact Help Section -->
                <section id="contact-help" class="help-section">
                    <h2><i class="fas fa-headset"></i> Contact PCC IT Support</h2>
                    <div class="contact-methods">
                        <div class="contact-card">
                            <i class="fas fa-phone-alt"></i>
                            <h3>Phone Support</h3>
                            <p>(049) 123-4567</p>
                            <p>Monday-Friday, 8:00 AM - 5:00 PM</p>
                        </div>
                        <div class="contact-card">
                            <i class="fas fa-envelope"></i>
                            <h3>Email Support</h3>
                            <p><a href="mailto:itsupport@pcc.gov.ph">itsupport@pcc.gov.ph</a></p>
                            <p>Response within 24 hours</p>
                        </div>
                        <div class="contact-card">
                            <i class="fas fa-map-marker-alt"></i>
                            <h3>In-Person Support</h3>
                            <p>PCC Main Office, Science City of Muñoz, Nueva Ecija</p>
                            <p>Building 2, IT Help Desk</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <footer class="gov-footer">
            <p>Philippine Carabao Center Help Center</p>
            <p>© 2025 PCC. All rights reserved. | <a href="https://www.pcc.gov.ph/pcc-privacy-notice/">Privacy Policy</a></p>
        </footer>
    </div>

    <script src="js/help.js"></script>
</body>
</html>