<?php
require 'config.php';
require 'mail_config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Rate limiting (5 attempts per hour)
    if (!isset($_SESSION['reset_attempts'])) {
        $_SESSION['reset_attempts'] = 0;
        $_SESSION['first_attempt'] = time();
    }

    if ($_SESSION['reset_attempts'] >= 5) {
        $remaining = 3600 - (time() - $_SESSION['first_attempt']);
        if ($remaining > 0) {
            throw new Exception('Too many attempts. Try again in '.ceil($remaining/60).' minutes');
        }
    }

    $_SESSION['reset_attempts']++;

    // Validate inputs
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $employee_id = htmlspecialchars($data['employee_id']);
    $captcha = htmlspecialchars($data['captcha']);

    if ($captcha !== 'PCC-2025') {
        throw new Exception('Invalid security verification code');
    }

    // Check user exists
    $stmt = $pdo->prepare("SELECT user_id, full_name FROM users 
                          WHERE email = ? AND employee_id = ? AND is_active = 1");
    $stmt->execute([$email, $employee_id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Invalid credentials or inactive account');
    }

    // Generate verification code
    $verification_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Update database
    $updateStmt = $pdo->prepare("UPDATE users 
                                SET reset_token = ?, token_expiry = ?
                                WHERE user_id = ?");
    $updateStmt->execute([$verification_code, $expiry, $user['user_id']]);

    // Send email
    $mail = configureMailer();
    $mail->addAddress($email, $user['full_name']);
    $mail->Subject = 'PCC Password Reset Verification Code';
    $mail->Body = "
        <h2>Password Reset Request</h2>
        <p>Hi {$user['full_name']},</p>
        <p>Your verification code is: <strong>$verification_code</strong></p>
        <p>This code will expire in 10 minutes.</p>
        <p>If you didn't request this, please contact IT Helpdesk immediately.</p>
    ";

    $mail->send();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>