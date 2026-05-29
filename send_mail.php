<?php
// Securely load your local PHPMailer files 
require_once __DIR__ . '/Exception.php';
require_once __DIR__ . '/PHPMailer.php';
require_once __DIR__ . '/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_POST['submit'])) {
    $mail = new PHPMailer(true);

    try {
        // --- SMTP Settings ---
        $mail->isSMTP();                                
        $mail->Host       = getenv('SMTP_HOST') ?: '://brevo.com';    
        $mail->SMTPAuth   = true;                       
        $mail->Username   = getenv('SMTP_USER');    
        $mail->Password   = getenv('SMTP_PASS');    
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587;                        

        // --- Sanitizing Inputs ---
        $fromEmail    = filter_var($_POST['from_email'], FILTER_SANITIZE_EMAIL);
        $toEmail      = filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL);
        $replyToEmail = filter_var($_POST['reply_to'], FILTER_SANITIZE_EMAIL);
        $subject      = htmlspecialchars($_POST['subject']);
        $userMessage  = htmlspecialchars($_POST['message']);

        // --- Recipients Configuration ---
        // Brevo requires the system account email as the outbound sender handle
        $mail->setFrom(getenv('SMTP_USER'), 'Website Mailer'); 
        $mail->addAddress($toEmail);
        $mail->addReplyTo($replyToEmail);

        // --- Content Engineering ---
        $mail->isHTML(false);                           
        $mail->Subject = $subject;
        
        // Appends what the user typed into the "From" input block to the top of the body text
        $mail->Body    = "Sent By: " . $fromEmail . "\n" . "---------------------------\n\n" . $userMessage;

        $mail->send();
        echo "<p style='color: green; font-family: Arial; margin: 40px;'>Email sent successfully via Brevo!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red; font-family: Arial; margin: 40px;'>Mailer Error: {$mail->ErrorInfo}</p>";
    }
} else {
    echo "<p style='color: red; font-family: Arial; margin: 40px;'>Invalid Request Method</p>";
}
?>
