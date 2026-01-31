<?php
// admin/cpri/edit.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$cpri_id = $_GET['id'] ?? null;
if (!$cpri_id) {
    header('Location: list.php');
    exit();
}

// Fetch submission
$stmt = $conn->prepare("SELECT c.*, p.product_name, u.full_name as submitted_by_name
    FROM cpri_submissions c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN users u ON c.submitted_by = u.user_id
    WHERE c.cpri_id = ?");
$stmt->bind_param("i", $cpri_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission) {
    header('Location: list.php');
    exit();
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $approval_status = $_POST['approval_status'] ?? $submission['approval_status'];
    $cpri_ref = trim($_POST['cpri_reference_number'] ?? '');
    $admin_notes = $_POST['admin_notes'] ?? '';

    // Determine whether table has approval metadata columns
    $has_approved_by = $conn->query("SHOW COLUMNS FROM cpri_submissions LIKE 'approved_by'")->num_rows > 0;
    $has_approval_date = $conn->query("SHOW COLUMNS FROM cpri_submissions LIKE 'approval_date'")->num_rows > 0;

    if (($approval_status === 'Approved' || $approval_status === 'Rejected') && $has_approved_by && $has_approval_date) {
        $approved_by = $_SESSION['user_id'];
        $update_sql = "UPDATE cpri_submissions SET approval_status = ?, cpri_reference_number = ?, admin_notes = ?, approved_by = ?, approval_date = NOW() WHERE cpri_id = ?";
        $stmt_up = $conn->prepare($update_sql);
        $stmt_up->bind_param("ssssi", $approval_status, $cpri_ref, $admin_notes, $approved_by, $cpri_id);
    } else {
        // do not touch approval_by/approval_date if they don't exist; clear if exists and status is not final
        if ($has_approved_by && $has_approval_date) {
            $update_sql = "UPDATE cpri_submissions SET approval_status = ?, cpri_reference_number = ?, admin_notes = ?, approved_by = NULL, approval_date = NULL WHERE cpri_id = ?";
            $stmt_up = $conn->prepare($update_sql);
            $stmt_up->bind_param("sssi", $approval_status, $cpri_ref, $admin_notes, $cpri_id);
        } else {
            $update_sql = "UPDATE cpri_submissions SET approval_status = ?, cpri_reference_number = ?, admin_notes = ? WHERE cpri_id = ?";
            $stmt_up = $conn->prepare($update_sql);
            $stmt_up->bind_param("sssi", $approval_status, $cpri_ref, $admin_notes, $cpri_id);
        }
    }

    $stmt_up->execute();
    logActivity($conn, $_SESSION['user_id'], "Edited CPRI submission", "cpri_submissions", $cpri_id);
    header("Location: list.php?msg=updated");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit CPRI Submission - Admin</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
    <style>
        .form-group { margin-bottom: 12px; }
        label { display:block; font-weight:600; margin-bottom:6px; }
        textarea, select, input[type=text] { width:100%; padding:10px; border-radius:6px; border:1px solid #ddd; }
        .btn { padding:8px 14px; border-radius:6px; border:none; cursor:pointer; }
        .btn-save { background:#27ae60; color:white; }
        .btn-back { background:#95a5a6; color:white; margin-right:8px; }

        /* Topbar and icon styles (standardized) */
        .top-bar { display:flex; flex-direction:column; gap:8px; }
        .top-bar .meta { display:flex; align-items:center; gap:12px; }
        .breadcrumb { font-size:13px; color:#6c7a89; }
        .top-actions { margin-left:auto; display:flex; gap:8px; align-items:center; }
        .icon { width:14px; height:14px; vertical-align:middle; margin-right:6px; display:inline-block; }
        @media(min-width:800px){ .top-bar { flex-direction:row; align-items:center; } }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="../products/list.php">Products</a></li>
                <li><a href="../products/add.php">Add Product</a></li>
                <li><a href="../tests/list.php">Tests</a></li>
                <li><a href="../users/list.php">Users</a></li>
                <li><a href="../users/add.php">Add User</a></li>
                <li><a href="list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../search.php">Advanced Search</a></li>
                <li><a href="../config.php">System Config</a></li>
                <li><a href="../logs.php">Activity Logs</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <div class="meta">
                    <div class="breadcrumb"><a href="../index.php">Admin</a> &rsaquo; <a href="list.php">CPRI Submissions</a> &rsaquo; Edit</div>
                    <h1 style="margin:0;">Edit CPRI #<?php echo htmlspecialchars($submission['cpri_id']); ?></h1>
                    <div class="top-actions">
                        <a href="list.php" class="btn btn-back"><svg class="icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 3L6 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>Back</a>
                        <button type="button" class="btn btn-save" onclick="document.querySelector('form').submit();"><svg class="icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 7v6h10V4l-3-3H3v6z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Save</button>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <form method="post">
                    <div class="form-group">
                        <label>Product</label>
                        <div><?php echo htmlspecialchars($submission['product_id'] . ' - ' . $submission['product_name']); ?></div>
                    </div>

                    <div class="form-group">
                        <label>Submitted By</label>
                        <div><?php echo htmlspecialchars($submission['submitted_by_name']); ?></div>
                    </div>

                    <div class="form-group">
                        <label for="cpri_reference_number">CPRI Reference Number</label>
                        <input type="text" name="cpri_reference_number" id="cpri_reference_number" value="<?php echo htmlspecialchars($submission['cpri_reference_number'] ?? ''); ?>">
                    </div>

                    <div class="form-group">
                        <label for="approval_status">Approval Status</label>
                        <select name="approval_status" id="approval_status" required>
                            <?php
                            $statuses = ['Submitted','Under Review','Approved','Rejected','Resubmission Required'];
                            foreach ($statuses as $s) {
                                $sel = ($submission['approval_status'] == $s) ? 'selected' : '';
                                echo "<option value=\"$s\" $sel>$s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="admin_notes">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" rows="4"><?php echo htmlspecialchars($submission['admin_notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group" style="display:flex;justify-content:flex-end;">
                        <button type="button" onclick="location.href='list.php'" class="btn btn-back">Cancel</button>
                        <button type="submit" class="btn btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>