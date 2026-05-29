<?php
if (isset($_POST['submit'])) {
    
    // --- Configuration ---
    // 1. Put your Brevo API key here (NOT your SMTP password)
    $apiKey = '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';
    
    // 2. Put your verified Brevo account login email here
    $senderEmail = 'acedcf001@smtp-brevo.com';

    // --- Sanitizing Form Inputs ---
    $fromEmail    = filter_var($_POST['from_email'], FILTER_SANITIZE_EMAIL);
    $toEmail      = filter_var($_POST['to_email'], FILTER_SANITIZE_EMAIL);
    $replyToEmail = filter_var($_POST['reply_to'], FILTER_SANITIZE_EMAIL);
    $subject      = htmlspecialchars($_POST['subject']);
    $userMessage  = htmlspecialchars($_POST['message']);

    // Constructing the payload body text
    $emailBody = "Sent By: " . $fromEmail . "\n" . "---------------------------\n\n" . $userMessage;

    // --- Brevo API Payload Setup ---
    $data = [
        "sender" => [
            "name" => "Website Mailer",
            "email" => $senderEmail
        ],
        "to" => [
            [
                "email" => $toEmail
            ]
        ],
        "replyTo" => [
            "email" => $replyToEmail
        ],
        "subject" => $subject,
        "textContent" => $emailBody
    ];

    // --- Execute cURL Request ---
    $ch = curl_init();

    // CRITICAL: This exact endpoint is required to process the message
    curl_setopt($ch, CURLOPT_URL, 'https://brevo.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $headers = [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ];
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        $errorMsg = curl_error($ch);
        echo "<p style='color: red; font-family: Arial; margin: 40px;'>Network Error: {$errorMsg}</p>";
    } else {
        $responseData = json_decode($response, true);
        if ($httpCode === 201) {
            echo "<p style='color: green; font-family: Arial; margin: 40px;'>Email sent successfully via Brevo API!</p>";
        } else {
            $apiError = isset($responseData['message']) ? $responseData['message'] : 'Unknown API Error';
            echo "<p style='color: red; font-family: Arial; margin: 40px;'>Brevo API Error ({$httpCode}): {$apiError}</p>";
        }
    }

    curl_close($ch);

} else {
    echo "<p style='color: red; font-family: Arial; margin: 40px;'>Invalid Request Method</p>";
}
?>
