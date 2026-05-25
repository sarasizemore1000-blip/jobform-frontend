<?php
session_start();

// SIMPLE LOGIN
$admin_user = "admin";
$admin_pass = "ijobapizzle1";

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

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ======================
// HANDLE DELETE REQUEST
// ======================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            $delete_stmt = $pdo->prepare("DELETE FROM job_applications WHERE id = ?");
            $delete_stmt->execute([$delete_id]);
            header("Location: admin.php");
            exit;
        } catch (Exception $e) {
            $delete_error = "Failed to delete: " . $e->getMessage();
        }
    }
}

// ======================
// FETCH DATA
// ======================
$stmt = $pdo->query("SELECT * FROM job_applications ORDER BY id DESC");
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// UI STYLE INJECTIONS & MODAL SCRIPT
// ======================
echo "
<style>
    body { font-family: Arial, sans-serif; background-color: #f8fafc; color: #334155; }
    .app-card { border: 1px solid #e2e8f0; padding: 20px; margin: 15px auto; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: relative; }
    .btn-view { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; display: inline-block; border: none; margin-right: 5px; }
    .btn-download { background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; display: inline-block; }
    .btn-delete { background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; border: none; font-size: 14px; cursor: pointer; font-weight: bold; }
    .btn-delete:hover { background: #dc2626; }
    
    #imageModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.85); align-items: center; justify-content: center; }
    #modalImg { max-width: 90%; max-height: 85%; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    .close-modal { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
</style>

<h1 style='text-align:center; margin-top: 30px; color: #1e293b;'>Job Applications Management</h1>";

if (isset($delete_error)) {
    echo "<p style='color:red; text-align:center;'><strong>Error: $delete_error</strong></p>";
}

echo "<div style='max-width:1000px;margin:auto;'>";

foreach ($data as $row) {
    $safe_name = preg_replace('/[^a-zA-Z0-9]/', '_', $row['first_name'] . '_' . $row['last_name']);
    
    // Format the date nicely (e.g., May 25, 2026, 11:30 PM)
    $submission_date = "N/A";
    if (!empty($row['created_at'])) {
        $submission_date = date("M d, Y, h:i A", strtotime($row['created_at']));
    }
    
    echo "
    <div class='app-card'>
        <h3 style='color: #1e293b; margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;'>
            👤 {$row['first_name']} {$row['middle_name']} {$row['last_name']}
        </h3>
        <p style='color: #64748b; font-size: 14px; margin-top: -5px; margin-bottom: 15px;'><strong>📅 SUBMITTED ON:</strong> {$submission_date}</p>
        
        <p><strong>🧾 SSN:</strong> {$row['ssn']}</p>
        <p><strong>📞 PHONE:</strong> {$row['phone']}</p>
        <p><strong>📧 EMAIL:</strong> {$row['email']}</p>
        <p><strong>🎂 DOB:</strong> {$row['dob']}</p>
        <p><strong>🏠 ADDRESS:</strong> {$row['address']}</p>
        <p><strong>🏙 BIRTH CITY:</strong> {$row['birth_city']}</p>
        <p><strong>👨 FATHER NAME:</strong> {$row['father_name']}</p>
        <p><strong>👩 MOTHER NAME:</strong> {$row['mother_name']}</p>
        
        <div style='margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center;'>
            <div>
                <p style='margin: 8px 0;'>
                    <strong>🪪 Front ID:</strong> 
                    " . (!empty($row['front_id']) ? "
                        <button class='btn-view' onclick='openImageModal(`" . $row['front_id'] . "`)'>View Image</button>
                        <a href='{$row['front_id']}' download='{$safe_name}_front_id.png' class='btn-download'>Download</a>
                    " : "<span style='color: #ef4444;'>No image provided</span>") . "
                </p>

                <p style='margin: 8px 0;'>
                    <strong>🪪 Back ID:</strong> 
                    " . (!empty($row['back_id']) ? "
                        <button class='btn-view' onclick='openImageModal(`" . $row['back_id'] . "`)'>View Image</button>
                        <a href='{$row['back_id']}' download='{$safe_name}_back_id.png' class='btn-download'>Download</a>
                    " : "<span style='color: #ef4444;'>No image provided</span>") . "
                </p>
            </div>
            
            <div>
                <form method='POST' onsubmit='return confirm(\"Are you absolutely sure you want to delete this applicant? This will permanently wipe the record and images from Neon storage.\");'>
                    <input type='hidden' name='action' value='delete'>
                    <input type='hidden' name='id' value='{$row['id']}'>
                    <button type='submit' class='btn-delete'>🗑 Delete Record</button>
                </form>
            </div>
        </div>
    </div>";
}

echo "</div>";

// Lightbox Layout
echo '
<div id="imageModal" onclick="this.style.display=\'none\'">
    <span class="close-modal" onclick="document.getElementById(\'imageModal\').style.display=\'none\'">&times;</span>
    <img id="modalImg" src="" alt="Enlarged view">
</div>

<script>
function openImageModal(base64Data) {
    if(!base64Data || base64Data.trim() === "") {
        alert("No valid image data available.");
        return;
    }
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("modalImg");
    modalImg.src = base64Data;
    modal.style.display = "flex";
}
</script>
';
?>
