<?php
// admin/cpri/view.php
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

$stmt = $conn->prepare("SELECT c.*, p.product_name, u.full_name as submitted_by_name
    FROM cpri_submissions c
    JOIN products p ON c.product_id = p.product_id
    LEFT JOIN users u ON c.submitted_by = u.user_id
    WHERE c.cpri_id = ?");
$stmt->bind_param("i", $cpri_id);
$stmt->execute();
$sub = $stmt->get_result()->fetch_assoc();
if (!$sub) {
    header('Location: list.php');
    exit();
}

// helper to safely fetch approver
$approver = null;
if (!empty($sub['approved_by'])) {
    $q = $conn->prepare("SELECT full_name FROM users WHERE user_id = ? LIMIT 1");
    $q->bind_param("i", $sub['approved_by']); $q->execute();
    $approver = $q->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>View CPRI - Admin</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
    <style>
        .detail-row { margin-bottom:12px; }
        .label { font-weight:700; color:#333; margin-bottom:6px; display:block; }
        .val { background:#fff; padding:10px; border-radius:6px; border:1px solid #eee; }
        .btn { padding:8px 12px; border-radius:6px; text-decoration:none; display:inline-block; }
        .btn-back { background:#95a5a6; color:#fff; }
        .btn-edit { background:#f39c12; color:#fff; }
        .btn-delete { background:#e74c3c; color:#fff; }

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
                <li><a href="../tests/list.php">Tests</a></li>
                <li><a href="../users/list.php">Users</a></li>
                <li><a href="list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <div class="meta">
                    <div class="breadcrumb"><a href="../index.php">Admin</a> &rsaquo; <a href="list.php">CPRI Submissions</a> &rsaquo; View</div>
                    <h1 style="margin:0;">CPRI Submission #<?php echo htmlspecialchars($sub['cpri_id']); ?></h1>
                    <div class="top-actions">
                        <a href="list.php" class="btn btn-back"><svg class="icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11 3L6 8l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>Back</a>
                        <a href="edit.php?id=<?php echo $sub['cpri_id']; ?>" class="btn btn-edit"><svg class="icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.3 3.3l.7.7L5 12v1h1l8-8-.7-.7-1-1-.7-.7-1 1z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>Edit</a>
                        <a href="list.php?delete=<?php echo $sub['cpri_id']; ?>" class="btn btn-delete confirm-delete" data-msg="Are you sure you want to delete this CPRI submission?"><svg class="icon" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 5h10M6 5v8a1 1 0 001 1h2a1 1 0 001-1V5M5 5L6 2h4l1 3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>Delete</a>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div class="detail-row">
                    <span class="label">Product</span>
                    <div class="val"><?php echo htmlspecialchars($sub['product_id'].' - '.$sub['product_name']); ?></div>
                </div>

                <div class="detail-row">
                    <span class="label">Submitted By</span>
                    <div class="val"><?php echo htmlspecialchars($sub['submitted_by_name']); ?></div>
                </div>

                <div class="detail-row">
                    <span class="label">Submission Date</span>
                    <div class="val"><?php echo date('M d, Y H:i', strtotime($sub['submission_date'])); ?></div>
                </div>

                <div class="detail-row">
                    <span class="label">CPRI Reference Number</span>
                    <div class="val"><?php echo htmlspecialchars($sub['cpri_reference_number'] ?? 'N/A'); ?></div>
                </div>

                <div class="detail-row">
                    <span class="label">Approval Status</span>
                    <div class="val"><?php echo htmlspecialchars($sub['approval_status']); ?><?php echo (!empty($sub['approval_date']) && !empty($approver)) ? ' by '.htmlspecialchars($approver['full_name']).' on '.htmlspecialchars($sub['approval_date']) : ''; ?></div>
                </div>

                <div class="detail-row">
                    <span class="label">Admin Notes</span>
                    <div class="val"><?php echo nl2br(htmlspecialchars($sub['admin_notes'] ?? '')); ?></div>
                </div>

                <?php if (!empty($sub['attachment'])): ?>
                <div class="detail-row">
                    <span class="label">Attachment</span>
                    <div class="val"><a href="../../uploads/<?php echo htmlspecialchars($sub['attachment']); ?>" target="_blank">Download</a></div>
                </div>
                <?php endif; ?>

            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>