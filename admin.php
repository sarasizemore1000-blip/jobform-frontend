<?php
session_start();

// ======================
// SIMPLE LOGIN
// ======================
$admin_user = "admin";
$admin_pass = "ijobapizzle1";

if (!isset($_SESSION['logged_in'])) {

    if (isset($_POST['username']) && isset($_POST['password'])) {

        if (
            $_POST['username'] === $admin_user &&
            $_POST['password'] === $admin_pass
        ) {

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

        <input name="username" placeholder="Username"
        style="width:100%;padding:10px;margin:5px;"><br>

        <input name="password" type="password"
        placeholder="Password"
        style="width:100%;padding:10px;margin:5px;"><br>

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
// HANDLE BULK / SINGLE DELETE REQUEST
// ======================
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['action']) &&
    $_POST['action'] === 'delete'
) {

    $ids_to_delete = [];

    // BULK DELETE
    if (isset($_POST['bulk_ids']) && is_array($_POST['bulk_ids'])) {

        $ids_to_delete = array_filter(
            $_POST['bulk_ids'],
            'is_numeric'
        );

    }

    // SINGLE DELETE
    elseif (isset($_POST['id']) && !empty($_POST['id'])) {

        $single_id = filter_var(
            $_POST['id'],
            FILTER_VALIDATE_INT
        );

        if ($single_id) {
            $ids_to_delete[] = $single_id;
        }
    }

    if (!empty($ids_to_delete)) {

        try {

            $placeholders = implode(
                ',',
                array_fill(0, count($ids_to_delete), '?')
            );

            $delete_stmt = $pdo->prepare(
                "DELETE FROM job_applications WHERE id IN ($placeholders)"
            );

            $delete_stmt->execute(array_values($ids_to_delete));

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
$stmt = $pdo->query(
    "SELECT * FROM job_applications ORDER BY id DESC"
);

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// PAGE STYLE
// ======================
echo "
<style>

body{
    font-family:Arial,sans-serif;
    background:#f8fafc;
    margin:0;
    padding:20px;
    color:#334155;
}

.admin-container{
    max-width:1000px;
    margin:auto;
}

.bulk-bar{
    background:white;
    padding:15px 20px;
    border-radius:12px;
    border:1px solid #e2e8f0;
    margin-bottom:20px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    position:sticky;
    top:10px;
    z-index:100;
}

.app-card{
    background:white;
    border:1px solid #e2e8f0;
    border-radius:12px;
    padding:20px 20px 20px 50px;
    margin-bottom:20px;
    position:relative;
    box-shadow:0 2px 5px rgba(0,0,0,0.05);
}

.card-checkbox{
    position:absolute;
    left:20px;
    top:24px;
    transform:scale(1.4);
    cursor:pointer;
}

.btn-view{
    background:#3b82f6;
    color:white;
    padding:6px 12px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    text-decoration:none;
    display:inline-block;
}

.btn-download{
    background:#10b981;
    color:white;
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    display:inline-block;
}

.btn-delete{
    background:#ef4444;
    color:white;
    padding:8px 16px;
    border:none;
    border-radius:6px;
    cursor:pointer;
    font-weight:bold;
}

.btn-delete:hover{
    background:#dc2626;
}

#imageModal{
    display:none;
    position:fixed;
    z-index:9999;
    left:0;
    top:0;
    width:100%;
    height:100%;
    background:rgba(15,23,42,0.85);
    align-items:center;
    justify-content:center;
}

#modalImg{
    max-width:90%;
    max-height:85%;
    border-radius:8px;
}

.close-modal{
    position:absolute;
    top:20px;
    right:30px;
    color:white;
    font-size:40px;
    cursor:pointer;
}

</style>
";

echo "
<div class='admin-container'>

<h1 style='text-align:center;margin-bottom:25px;'>
Job Applications Management
</h1>
";

if (isset($delete_error)) {

    echo "
    <p style='color:red;text-align:center;'>
    <strong>$delete_error</strong>
    </p>
    ";
}

echo "
<form id='mainForm'
method='POST'
onsubmit='return confirmAction();'>

<input type='hidden' name='action' value='delete'>
<input type='hidden' name='id' id='singleDeleteId'>
";

echo "
<div class='bulk-bar'>

<label style='font-weight:bold;display:flex;gap:10px;align-items:center;'>

<input type='checkbox'
id='selectAll'
style='transform:scale(1.2);'>

Select All Applications

</label>

<button
type='submit'
class='btn-delete'
onclick='setBulkAction()'>
🗑 Delete Selected
</button>

</div>
";

// ======================
// LOOP APPLICATIONS
// ======================
foreach ($data as $row) {

    $safe_name = preg_replace(
        '/[^a-zA-Z0-9]/',
        '_',
        $row['first_name'] . '_' . $row['last_name']
    );

    $submission_date = "N/A";

    if (!empty($row['created_at'])) {

        $submission_date = date(
            "M d, Y h:i A",
            strtotime($row['created_at'])
        );
    }

    echo "
    <div class='app-card'>

    <input
        type='checkbox'
        name='bulk_ids[]'
        value='{$row['id']}'
        class='card-checkbox client-box'>

    <p style='
    color:#64748b;
    font-size:14px;
    margin-top:0;
    margin-bottom:10px;
'>
    <strong>📅 SUBMITTED:</strong>
    $submission_date
</p>

<h3 style='
    margin-top:0;
    color:#1e293b;
    border-bottom:2px solid #f1f5f9;
    padding-bottom:8px;
'>
    👤 {$row['first_name']}
    {$row['middle_name']}
    {$row['last_name']}
</h3>

    <p><strong>🧾 SSN:</strong> {$row['ssn']}</p>
    <p><strong>📞 PHONE:</strong> {$row['phone']}</p>
    <p><strong>📧 EMAIL:</strong> {$row['email']}</p>
    <p><strong>🎂 DOB:</strong> {$row['dob']}</p>
    <p><strong>🏠 ADDRESS:</strong> {$row['address']}</p>
    <p><strong>🏙 BIRTH CITY:</strong> {$row['birth_city']}</p>
    <p><strong>👨 FATHER NAME:</strong> {$row['father_name']}</p>
    <p><strong>👩 MOTHER NAME:</strong> {$row['mother_name']}</p>

    <div style='margin-top:20px;'>

    <p>
    <strong>🪪 Front ID:</strong>
    ";

    if (!empty($row['front_id'])) {

        echo "
        <button
        type='button'
        class='btn-view'
        onclick='openImageModal(\"{$row['front_id']}\")'>
        View Image
        </button>

        <a
        href='{$row['front_id']}'
        download='{$safe_name}_front_id.png'
        class='btn-download'>
        Download
        </a>
        ";

    } else {

        echo "<span style='color:red;'>No image</span>";
    }

    echo "
    </p>

    <p>
    <strong>🪪 Back ID:</strong>
    ";

    if (!empty($row['back_id'])) {

        echo "
        <button
        type='button'
        class='btn-view'
        onclick='openImageModal(\"{$row['back_id']}\")'>
        View Image
        </button>

        <a
        href='{$row['back_id']}'
        download='{$safe_name}_back_id.png'
        class='btn-download'>
        Download
        </a>
        ";

    } else {

        echo "<span style='color:red;'>No image</span>";
    }

    echo "
    </p>

    <button
    type='submit'
    class='btn-delete'
    onclick='setSingleAction({$row['id']})'>
    🗑 Delete Record
    </button>

    </div>

    </div>
    ";
}

echo "</form>";
echo "</div>";

// ======================
// IMAGE MODAL
// ======================
echo '
<div id="imageModal"
onclick="closeModal()">

<span class="close-modal"
onclick="closeModal()">
&times;
</span>

<img id="modalImg" src="" alt="Preview">

</div>

<script>

var isBulkSubmit = false;

// SELECT ALL
document.getElementById("selectAll")
.addEventListener("change", function(){

    var boxes =
    document.getElementsByClassName("client-box");

    for(var i=0;i<boxes.length;i++){

        boxes[i].checked = this.checked;
    }
});

// BULK DELETE
function setBulkAction(){

    isBulkSubmit = true;

    document.getElementById(
        "singleDeleteId"
    ).value = "";
}

// SINGLE DELETE
function setSingleAction(id){

    isBulkSubmit = false;

    document.getElementById(
        "singleDeleteId"
    ).value = id;
}

// CONFIRM DELETE
function confirmAction(){

    if(isBulkSubmit){

        var count = 0;

        var boxes =
        document.getElementsByClassName("client-box");

        for(var i=0;i<boxes.length;i++){

            if(boxes[i].checked){
                count++;
            }
        }

        if(count === 0){

            alert("Please select records.");

            return false;
        }

        return confirm(
            "Delete " + count +
            " selected applications permanently?"
        );

    } else {

        return confirm(
            "Delete this applicant permanently?"
        );
    }
}

// OPEN IMAGE
function openImageModal(image){

    if(!image || image.trim() === ""){

        alert("No image found.");
        return;
    }

    document.getElementById(
        "imageModal"
    ).style.display = "flex";

    document.getElementById(
        "modalImg"
    ).src = image;
}

// CLOSE IMAGE
function closeModal(){

    document.getElementById(
        "imageModal"
    ).style.display = "none";
}

</script>
';
?>
