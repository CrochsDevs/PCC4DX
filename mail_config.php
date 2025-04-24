<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function configureMailer() {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'lester.s.rodriguez.211@gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'lester.s.rodriguez.211@gmail.com';
    $mail->Password   = 'password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    
    // Content settings
    $mail->isHTML(true);
    $mail->setFrom('noreply@pcc.gov.ph', 'PCC System');
    
    return $mail;
}
?>