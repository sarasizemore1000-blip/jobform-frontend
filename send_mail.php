<?php
if (isset($_POST['submit'])) {
    
    // ==========================================
    // CONFIGURATION BLOCK
    // ==========================================
    $apiKey = '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';
    $systemVerifiedEmail = 'collaomn@gmail.com'; 
    // ==========================================

    $fromEmail    = filter_var($_POST['from_email'], FILTER_VALIDATE_EMAIL);
    $toEmail      = filter_var($_POST['to_email'], FILTER_VALIDATE_EMAIL);
    $replyToEmail = filter_var($_POST['reply_to'], FILTER_VALIDATE_EMAIL);
    $subject      = htmlspecialchars($_POST['subject']);
    $userMessage  = htmlspecialchars($_POST['message']);

    if (!$fromEmail || !$toEmail || !$replyToEmail) {
        echo "<p style='color: red; font-family: Arial; margin: 40px;'>Error: One or more email addresses are invalid.</p>";
        exit;
    }

    $engineeredBody = "-------------------------------------------\n";
    $engineeredBody .= "DYNAMIC ROUTED FORM DATA\n";
    $engineeredBody .= "-------------------------------------------\n";
    $engineeredBody .= "Typed From-Email : " . $fromEmail . "\n";
    $engineeredBody .= "Target Destination: " . $toEmail . "\n";
    $engineeredBody .= "Reply-To Address  : " . $replyToEmail . "\n";
    $engineeredBody .= "-------------------------------------------\n\n";
    $engineeredBody .= "MESSAGE CONTENT:\n" . $userMessage;

    $data = [
        "sender" => [
            "name" => "Form Sender: " . $fromEmail,
            "email" => $systemVerifiedEmail       
        ],
        "to" => [["email" => $toEmail]],
        "replyTo" => ["email" => $fromEmail],
        "subject" => $subject . " (From: " . $fromEmail . ")",
        "textContent" => $engineeredBody
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://brevo.com');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // Safety Layer: Explicitly disables server-level proxy overrides interfering with the endpoint
    curl_setopt($ch, CURLOPT_PROXY, '');
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

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
            echo "<h3 style='color: red; margin-top:0;'>Server Response (HTTP $httpCode)</h3>";
            echo "<pre style='background: #eee; padding: 10px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars($response) . "</pre>";
            echo "</div>";
        }
    }

    curl_close($ch);

} else {
    echo "<p style='color: red; font-family: Arial; margin: 40px;'>Invalid Request Method</p>";
}
?>
