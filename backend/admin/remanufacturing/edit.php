<?php
// admin/remanufacturing/edit.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$rid = $_GET['id'] ?? null;
if (!$rid) {
    header('Location: list.php');
    exit();
}

// Fetch record
$stmt = $conn->prepare("SELECT r.*, p.product_name, u.full_name as created_by_name
    FROM remanufacturing_records r
    JOIN products p ON r.product_id = p.product_id
    LEFT JOIN users u ON r.created_by = u.user_id
    WHERE r.remanufacturing_id = ?");
$stmt->bind_param("i", $rid);
$stmt->execute();
$record = $stmt->get_result()->fetch_assoc();
if (!$record) {
    header('Location: list.php');
    exit();
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'] ?? $record['status'];
    $cost = $_POST['cost'] !== '' ? floatval($_POST['cost']) : null;
    $reason = $_POST['reason'] ?? '';

    if ($status === 'Completed') {
        $update_sql = "UPDATE remanufacturing_records SET status = ?, cost = ?, reason = ?, completed_date = NOW() WHERE remanufacturing_id = ?";
        $stmt_up = $conn->prepare($update_sql);
        $stmt_up->bind_param("sdsi", $status, $cost, $reason, $rid);
    } else {
        $update_sql = "UPDATE remanufacturing_records SET status = ?, cost = ?, reason = ?, completed_date = NULL WHERE remanufacturing_id = ?";
        $stmt_up = $conn->prepare($update_sql);
        $stmt_up->bind_param("sdsi", $status, $cost, $reason, $rid);
    }

    $stmt_up->execute();
    logActivity($conn, $_SESSION['user_id'], "Edited remanufacturing record", "remanufacturing_records", $rid);
    header("Location: list.php?msg=updated");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Re-Manufacturing - Admin</title>
    <link rel="stylesheet" href="../../admin/style.css">
    <link rel="stylesheet" href="../supervisor/public/style.css">

    <style>
        .form-group { margin-bottom: 12px; }
        label { display:block; font-weight:600; margin-bottom:6px; }
        textarea, select, input[type=text], input[type=number] { width:100%; padding:10px; border-radius:6px; border:1px solid #ddd; }
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
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="list.php">Re-Manufacturing</a></li>
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
                    <div class="breadcrumb"><a href="../index.php">Admin</a> &rsaquo; <a href="list.php">Re-Manufacturing</a> &rsaquo; Edit</div>
                    <h1 style="margin:0;">Edit Record #<?php echo htmlspecialchars($record['remanufacturing_id']); ?></h1>
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
                        <div><?php echo htmlspecialchars($record['product_id'] . ' - ' . $record['product_name']); ?></div>
                    </div>

                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" required>
                            <?php
                            $statuses = ['Pending', 'In Progress', 'Completed'];
                            foreach ($statuses as $s) {
                                $sel = ($record['status'] == $s) ? 'selected' : '';
                                echo "<option value=\"$s\" $sel>$s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="cost">Cost</label>
                        <input type="number" step="0.01" name="cost" id="cost" value="<?php echo htmlspecialchars($record['cost']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason / Notes</label>
                        <textarea name="reason" id="reason" rows="4"><?php echo htmlspecialchars($record['reason'] ?? ''); ?></textarea>
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