<?php
session_start();

// SIMPLE LOGIN (change this)
$admin_user = "admin";
$admin_pass = "12345";

if (!isset($_SESSION['logged_in'])) {

    if (isset($_POST['username']) && isset($_POST['password'])) {

        if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
            $_SESSION['logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            $error = "Invalid login";
        }
    }

    echo '
    <form method="POST" style="max-width:300px;margin:100px auto;text-align:center;">
        <h2>Admin Login</h2>
        <input name="username" placeholder="Username" style="width:100%;padding:10px;margin:5px;"><br>
        <input name="password" type="password" placeholder="Password" style="width:100%;padding:10px;margin:5px;"><br>
        <button style="padding:10px 20px;">Login</button>
        <p style="color:red;">'.($error ?? "").'</p>
    </form>';
    exit;
}

// ======================
// DATABASE CONNECTION
// ======================
$host = "ep-calm-frog-ahfjj5vo-pooler.c-3.us-east-1.aws.neon.tech";
$db   = "neondb";
$user = "neondb_owner";
$pass = "npg_TQ1gBOwA9rCa";
$port = "5432";

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

$pdo = new PDO($dsn, $user, $pass);

// ======================
// FETCH DATA
// ======================
$stmt = $pdo->query("SELECT * FROM job_applications ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// UI
// ======================
echo "<h1 style='text-align:center;'>Job Applications</h1>";

echo "<div style='max-width:1000px;margin:auto;'>";

foreach ($data as $row) {

    echo "
    <div style='border:1px solid #ccc;padding:15px;margin:10px;border-radius:10px;'>
        <h3>{$row['first_name']} {$row['middle_name']} {$row['last_name']}</h3>
        <p>📞 {$row['phone']}</p>
        <p>📧 {$row['email']}</p>
        <p>🎂 {$row['dob']}</p>
        <p>🏠 {$row['address']}</p>

        <p>
            🪪 Front ID:
            <a href='{$row['front_id']}' target='_blank'>View</a>
        </p>

        <p>
            🪪 Back ID:
            <a href='{$row['back_id']}' target='_blank'>View</a>
        </p>

        <hr>
    </div>";
}

echo "</div>";
?>
