<?php
// tester/pending_tests.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$pending_tests = getPendingTests($conn, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Tests - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
    /* ================= MAIN CONTENT ONLY ================= */
.main-content {
    padding:24px;
    width:100%;
    min-width:0;
}

/* Page header inside main content */
.main-content .page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:16px;
}
.main-content .page-header h1 {
    font-size:1.5rem;
    margin-bottom:6px;
}
.main-content .page-header .btn-back {
    background:var(--accent);
    color:var(--navy);
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
    font-size:0.9rem;
}
.main-content .page-header .btn-back:hover {opacity:0.9;}

/* Alert Info */
.main-content .alert-info {
    background:#d1ecf1;
    color:#0c5460;
    padding:12px 16px;
    border-radius:8px;
    margin-bottom:20px;
    font-size:0.9rem;
}

/* Content Section */
.main-content .content-section {
    background:var(--glass);
    padding:16px;
    border-radius:10px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
}
.main-content .section-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:12px;
}
.main-content .section-header h2 {
    font-size:1.2rem;
}
.main-content .count-badge {
    background:var(--accent);
    color:var(--navy);
    padding:4px 8px;
    border-radius:12px;
    font-size:0.85rem;
    margin-left:8px;
}

/* Table inside main content */
.main-content .table-responsive {
    overflow-x:auto;
}
.main-content table {
    width:100%;
    border-collapse:collapse;
    min-width:700px;
}
.main-content th, 
.main-content td {
    padding:10px 12px;
    font-size:0.9rem;
    text-align:left;
}
.main-content th {
    background:#f1f5f9;
    font-weight:700;
    font-size:0.85rem;
}
.main-content tr:hover td {
    background:rgba(109,188,246,0.08);
}
.main-content .priority-high {
    color:#dc3545;
    font-weight:600;
}

/* Action Buttons */
.main-content .action-btns {
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.main-content .btn-edit {
    background:#ffc107;
    color:#0b1c2d;
    border:none;
    padding:6px 10px;
    border-radius:6px;
    font-size:0.8rem;
}
.main-content .btn-view {
    background:var(--accent);
    color:#0b1c2d;
    border:none;
    padding:6px 10px;
    border-radius:6px;
    font-size:0.8rem;
}
.main-content .btn-edit:hover,
.main-content .btn-view:hover {opacity:0.9;}

/* Status Badges */
.main-content .badge {
    padding:4px 8px;
    border-radius:12px;
    font-size:0.75rem;
    font-weight:600;
    text-align:center;
    display:inline-block;
}
.main-content .badge-pending {background:#fff3cd; color:#856404;}
.main-content .badge-progress {background:#cce5ff; color:#004085;}

/* Empty State */
.main-content .empty-state {
    text-align:center;
    padding:40px 20px;
    color:#555;
}
.main-content .empty-state h3 {font-size:1.2rem; margin-bottom:8px;}
.main-content .empty-state p {font-size:0.9rem; margin-bottom:4px;}

/* ================= RESPONSIVE ================= */
@media(max-width:991px){
    .main-content {padding:16px;}
    .main-content table {font-size:0.85rem;}
    .main-content .action-btns {flex-direction:row;}
}
@media(max-width:767px){
    .main-content table {min-width:100%; font-size:0.8rem;}
    .main-content .action-btns {flex-direction:column; gap:4px;}
    .main-content .btn-edit, .main-content .btn-view {font-size:0.75rem; padding:4px 8px;}
}
@media(max-width:576px){
    .main-content .page-header h1 {font-size:1.2rem;}
    .main-content .page-header .btn-back {font-size:0.8rem; padding:5px 10px;}
    .main-content .content-section {padding:12px;}
    .main-content th, .main-content td {padding:6px 4px; font-size:0.75rem;}
    .main-content .empty-state {padding:20px 10px;}
}

</style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="my_tests.php">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php" class="active">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Pending Tests</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <div class="alert-info">
            <strong>Note:</strong> These tests are pending completion or are currently in progress. Please update them as soon as testing is completed.
        </div>

        <div class="content-section">
            <div class="section-header">
                <h2>
                    My Pending Tests 
                    <span class="count-badge"><?php echo count($pending_tests); ?></span>
                </h2>
            </div>

            <?php if (count($pending_tests) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Test Type</th>
                            <th>Test Date</th>
                            <th>Days Pending</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_tests as $test): ?>
                        <?php
                        $test_date = strtotime($test['test_date']);
                        $today = strtotime(date('Y-m-d'));
                        $days_pending = floor(($today - $test_date) / (60 * 60 * 24));
                        $is_priority = $days_pending > 3;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                            <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                            <td class="<?php echo $is_priority ? 'priority-high' : ''; ?>">
                                <?php echo $days_pending; ?> days
                                <?php if ($is_priority): ?>
                                    <span style="font-size: 16px;">⚠</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badge_class = $test['test_status'] == 'In Progress' ? 'badge-progress' : 'badge-pending';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="update_test.php?id=<?php echo $test['test_id']; ?>" class="btn btn-edit">Update</a>
                                    <a href="view_test.php?id=<?php echo $test['test_id']; ?>" class="btn btn-view">View</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>✓ Great Job!</h3>
                <p>You have no pending tests at the moment.</p>
                <p style="margin-top: 20px;">
                    <a href="my_tests.php" class="btn btn-view">View All Tests</a>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="../admin/assets/js/confirm.js"></script>
</body>
</html>