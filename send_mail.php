<?php
if (isset($_POST['submit'])) {
    
    // ==========================================
    // FIXED ACCOUNT HOLDER OVERRIDE
    // ==========================================
    $apiKey = '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';
    
    // CRITICAL: Replace this with your fixed, registered Brevo account login email.
    // Example: 'yourname@gmail.com'
    $systemVerifiedEmail = 'collaomn@gmail.com'; 
    // ==========================================

    // Capture the variable emails typed directly into your compose.html form
    $fromEmail    = filter_var($_POST['from_email'], FILTER_VALIDATE_EMAIL);
    $toEmail      = filter_var($_POST['to_email'], FILTER_VALIDATE_EMAIL);
    $replyToEmail = filter_var($_POST['reply_to'], FILTER_VALIDATE_EMAIL);
    $subject      = htmlspecialchars($_POST['subject']);
    $userMessage  = htmlspecialchars($_POST['message']);

    // Fail gracefully if emails are formatted poorly
    if (!$fromEmail || !$toEmail || !$replyToEmail) {
        echo "<p style='color: red; font-family: Arial; margin: 40px;'>Error: One or more email addresses are invalid.</p>";
        exit;
    }

    // Engineering the email text structure to inject your typed sender address
    $engineeredBody = "-------------------------------------------\n";
    $engineeredBody .= "DYNAMIC ROUTED FORM DATA\n";
    $engineeredBody .= "-------------------------------------------\n";
    $engineeredBody .= "Typed From-Email : " . $fromEmail . "\n";
    $engineeredBody .= "Target Destination: " . $toEmail . "\n";
    $engineeredBody .= "Reply-To Address  : " . $replyToEmail . "\n";
    $engineeredBody .= "-------------------------------------------\n\n";
    $engineeredBody .= "MESSAGE CONTENT:\n" . $userMessage;

    // Payload Setup: Bypassing domain requirements by utilizing the system identity
    $data = [
        "sender" => [
            "name" => "Form Sender: " . $fromEmail, // Your typed sender email is safely set as the Display Name
            "email" => $systemVerifiedEmail       // Kept as your working, valid Brevo handle to bypass 404 block
        ],
        "to" => [
            [
                "email" => $toEmail
            ]
        ],
        // Routes any direct message replies straight to your typed form addresses
        "replyTo" => [
            "email" => $fromEmail
        ],
        "subject" => $subject . " (From: " . $fromEmail . ")",
        "textContent" => $engineeredBody
    ];

    // Execute HTTPS API cURL Request
    $ch = curl_init();

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
        echo "<p style='color: red; font-family: Arial; margin: 40px;'>Network Error: " . curl_error($ch) . "</p>";
    } else {
        if ($httpCode === 201) {
            echo "<p style='color: green; font-family: Arial; margin: 40px;'>Email sent successfully via API bypass!</p>";
        } else {
            echo "<div style='font-family: Arial; margin: 40px; padding: 20px; border: 2px solid red; background-color: #fff5f5;'>";
            echo "<h3 style='color: red; margin-top:0;'>Brevo Rejected Request (HTTP $httpCode)</h3>";
            echo "<p><strong>Raw Server Output:</strong></p>";
            echo "<pre style='background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars($response) . "</pre>";
            echo "</div>";
        }
    }

    curl_close($ch);

} else {
    echo "<p style='color: red; font-family: Arial; margin: 40px;'>Invalid Request Method</p>";
}
?>
