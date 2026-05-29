<?php
// Tell the browser to expect JSON data back
header('Content-Type: application/json');

// On mobile, instead of Composer, we load PHPMailer directly from a secure online mirror
require_once 'https://jsdelivr.net';
require_once 'https://jsdelivr.net';
require_once 'https://jsdelivr.net';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new PHPMailer(true);

    try {
        // --- SMTP Settings ---
        $mail->isSMTP();                                
        $mail->Host       = getenv('SMTP_HOST');    // Read from Render environment settings
        $mail->SMTPAuth   = true;                       
        $mail->Username   = getenv('SMTP_USER');    // Read from Render environment settings
        $mail->Password   = getenv('SMTP_PASS');    // Read from Render environment settings
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;                        

        // --- Recipients ---
        $mail->setFrom(getenv('SMTP_USER'), 'Website Contact'); 
        $mail->addAddress(filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL));
        $mail->addReplyTo(filter_var($_POST['reply_to'], FILTER_SANITIZE_EMAIL));

        // --- Content ---
        $mail->isHTML(false);                           
        $mail->Subject = htmlspecialchars($_POST['subject']);
        $mail->Body    = htmlspecialchars($_POST['message']);

        $mail->send();
        
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Failed to send: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>
