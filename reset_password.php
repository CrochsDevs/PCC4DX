<?php
require 'config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate inputs
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $code = htmlspecialchars($data['verification_code']);
    $new_password = $data['new_password'];
    $confirm_password = $data['confirm_password'];

    if ($new_password !== $confirm_password) {
        throw new Exception('Passwords do not match');
    }

    if (!preg_match('/^(?=.*\d)(?=.*[!@#$%^&*])(?=.*[a-zA-Z]).{8,}$/', $new_password)) {
        throw new Exception('Password must be at least 8 characters with 1 number and 1 special character');
    }

    // Verify code
    $stmt = $pdo->prepare("SELECT user_id FROM users 
                          WHERE email = ? 
                          AND reset_token = ? 
                          AND token_expiry > NOW()");
    $stmt->execute([$email, $code]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception('Invalid or expired verification code');
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $updateStmt = $pdo->prepare("UPDATE users 
                                SET password_hash = ?, 
                                    reset_token = NULL, 
                                    token_expiry = NULL 
                                WHERE user_id = ?");
    $updateStmt->execute([$hashed_password, $user['user_id']]);

    // Reset rate limiting
    unset($_SESSION['reset_attempts']);
    unset($_SESSION['first_attempt']);

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>