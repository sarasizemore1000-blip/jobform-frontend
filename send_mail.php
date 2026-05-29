<?php
// Tell the browser to expect JSON data back
header('Content-Type: application/json');

// Mobile Quick-Fix: Automatically download PHPMailer files locally if they don't exist
if (!file_exists(__DIR__ . '/PHPMailer.php')) {
    file_put_contents(__DIR__ . '/Exception.php', file_get_contents('https://githubusercontent.com'));
    file_put_contents(__DIR__ . '/PHPMailer.php', file_get_contents('https://githubusercontent.com'));
    file_put_contents(__DIR__ . '/SMTP.php', file_get_contents('https://githubusercontent.com'));
}

// Load the downloaded local files securely
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mail = new PHPMailer(true);

    try {
        // --- SMTP Settings ---
        $mail->isSMTP();                                
        $mail->Host       = getenv('SMTP_HOST');    
        $mail->SMTPAuth   = true;                       
        $mail->Username   = getenv('SMTP_USER');    
        $mail->Password   = getenv('SMTP_PASS');    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;                        

        // --- Recipients ---
        // Brevo requires the "From" address to match your authorized Brevo account login
        $mail->setFrom(getenv('SMTP_USER'), 'Website Mailer'); 
        $mail->addAddress(filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL));
        $mail->addReplyTo(filter_var($_POST['reply_to'], FILTER_SANITIZE_EMAIL));

        // --- Content ---
        $mail->isHTML(false);                           
        $mail->Subject = htmlspecialchars($_POST['subject']);
        $mail->Body    = htmlspecialchars($_POST['message']);

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully via Brevo!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => "Mailer Error: {$mail->ErrorInfo}"]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request Method']);
}
?>
