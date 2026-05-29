<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader (or manually require the files)
require 'vendor/autoload.php';

if (isset($_POST['submit'])) {
    $mail = new PHPMailer(true);

    try {
        // --- Server Configuration ---
        // $mail->SMTPDebug = 2;                        // Enable this to debug connection issues
        $mail->isSMTP();                                // Set mailer to use SMTP
        $mail->Host       = '://yourprovider.com';    // Specify your SMTP server (e.g., ://gmail.com)
        $mail->SMTPAuth   = true;                       // Enable SMTP authentication
        $mail->Username   = 'your_username@domain.com'; // SMTP username
        $mail->Password   = 'your_secure_password';     // SMTP password (or App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Enable TLS encryption
        $mail->Port       = 587;                        // TCP port to connect to

        // --- Recipients ---
        // To bypass spoofing filters, the standard "From" should match your SMTP account.
        $mail->setFrom('your_username@domain.com', 'Mailer System'); 
        $mail->addAddress(filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL));
        $mail->addReplyTo(filter_var($_POST['reply_to'], FILTER_SANITIZE_EMAIL));

        // --- Content ---
        $mail->isHTML(false);                           // Set email format to plain text
        $mail->Subject = htmlspecialchars($_POST['subject']);
        $mail->Body    = htmlspecialchars($_POST['message']);

        $mail->send();
        echo "<h3>Email successfully sent using SMTP!</h3>";
    } catch (Exception $e) {
        echo "<h3>Message could not be sent. Mailer Error: {$mail->ErrorInfo}</h3>";
    }
} else {
    echo "<h3>Invalid Request</h3>";
}
?>
