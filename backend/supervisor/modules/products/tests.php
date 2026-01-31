<?php
// supervisor/modules/products/tests.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

// FILTERS
$filter_test_status = $_GET['test_status'] ?? 'all';
$filter_product_status = $_GET['product_status'] ?? 'all';
$filter_test_status = trim($filter_test_status);
$filter_product_status = trim($filter_product_status);

$conditions = [];
if($filter_test_status !== 'all') {
    $status_esc = $conn->real_escape_string($filter_test_status);
    $conditions[] = "t.test_status = '$status_esc'";
}
if($filter_product_status !== 'all') {
    $status_esc = $conn->real_escape_string($filter_product_status);
    $conditions[] = "p.current_status = '$status_esc'";
}
$where = count($conditions) ? "WHERE " . implode(" AND ", $conditions) : "";

// MAIN QUERY with derived approval_status
$sql = "
SELECT 
    t.test_id,
    t.test_date,
    t.test_time,
    t.test_status,
    t.observed_results,
    t.test_remarks,
    t.approval_date,
    p.product_name,
    p.current_status AS product_status,
    tt.test_type_name,
    u.full_name AS tester_name,
    CASE 
        WHEN t.test_status = 'Passed' THEN 'Approved'
        WHEN t.test_status = 'Failed' THEN 'Rejected'
        ELSE 'Pending'
    END AS approval_status
FROM tests t
JOIN products p ON t.product_id = p.product_id
JOIN test_types tt ON t.test_type_id = tt.test_type_id
LEFT JOIN users u ON t.tester_id = u.user_id
$where
ORDER BY t.test_date DESC, t.test_time DESC
";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Product Tests - Supervisor</title>
<link rel="stylesheet" href="../../public/style.css">

<style>
    :root {
    --navy:#0b1c2d;
    --text:#1e293b;
    --accent:#6dbcf6;
    --glass:rgba(255,255,255,.55);
    --bg-light: #f4f6f9;
    --card-bg: #fff;
    --shadow-light: rgba(0,0,0,0.08);
}

/* ===================== GLOBAL RESET ===================== */
* { margin:0; padding:0; box-sizing:border-box; font-family:"Segoe UI",sans-serif; }
body { background: var(--bg-light); color: var(--text); line-height:1.5; }
a { text-decoration:none; color:inherit; transition:0.3s; }
a:hover { opacity:0.85; }

/* ===================== DASHBOARD LAYOUT ===================== */
.dashboard-container { display:flex; min-height:100vh; }
.sidebar { width:250px; background:var(--navy); color:#fff; padding:20px 0; position:fixed; height:100vh; overflow-y:auto; transition:0.3s; }
.sidebar-header { padding:0 20px 20px; border-bottom:1px solid rgba(255,255,255,0.1); }
.sidebar-header h2 { font-size:18px; margin-bottom:5px; }
.sidebar-header p { font-size:13px; color:#ccc; }
.sidebar-menu { list-style:none; padding:20px 0; }
.sidebar-menu li { margin-bottom:5px; }
.sidebar-menu a { display:block; padding:12px 20px; color:#ecf0f1; border-left:3px solid transparent; border-radius:6px; transition:0.3s; }
.sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.05); border-left:3px solid var(--accent); }

/* Main content */
.main-content { margin-left:250px; flex:1; padding:30px; transition:0.3s; }

/* Top bar */
.top-bar { background: var(--card-bg); padding:20px; border-radius:12px; margin-bottom:20px; box-shadow:0 6px 18px var(--shadow-light); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; }
.top-bar h1 { font-size:22px; color:var(--navy); }
.user-info { font-size:14px; }
.logout-btn { background:#e74c3c; color:#fff; padding:8px 12px; border-radius:6px; font-weight:600; }
.logout-btn:hover { background:#cf3e2e; transform:translateY(-2px); }

/* Content section */
.content-section { background: var(--card-bg); padding:20px 22px; border-radius:12px; box-shadow:0 2px 8px var(--shadow-light); }

/* ===================== FILTER PILLS ===================== */
.filter-pill { display:inline-block; padding:8px 14px; border-radius:20px; margin-right:8px; margin-bottom:8px; font-weight:600; font-size:13px; border:1px solid transparent; background:#e9f5ff; color:#0b66d0; }
.filter-pill.active { background:var(--accent); color:#fff; border-color:var(--accent); }

/* ===================== TABLE STYLES ===================== */
.table-responsive { overflow-x:auto; }
table { width:100%; border-collapse:collapse; margin-top:15px; font-size:14px; }
th, td { padding:12px 10px; border-bottom:1px solid #ecf0f1; vertical-align:middle; }
th { background:var(--card-bg); border-bottom:2px solid #f0f2f5; text-transform:uppercase; font-size:12px; text-align:center; }
td { text-align:left; }

.product-cell { max-width:340px; word-break:break-word; line-height:1.3; }
.badge { display:inline-block; padding:6px 10px; border-radius:12px; color:white; font-weight:700; font-size:13px; text-align:center; min-width:72px; }

/* ===================== STATUS COLORS (keep as is) ===================== */
.Pending { background:#f0ad4e; }
.InProgress { background:#17a2b8; }
.Passed { background:#27ae60; }
.Failed { background:#e74c3c; }

/* Approval buttons */
.approval-actions { display:flex; gap:8px; justify-content:center; align-items:center; }
.btn { padding:8px 14px; border-radius:6px; font-weight:600; text-decoration:none; display:inline-block; cursor:pointer; border:none; transition:0.3s; }
.btn-sm { padding:4px 8px; font-size:13px; }
.btn-success { background:#28a745; color:#fff; }
.btn-danger { background:#dc3545; color:#fff; }
.btn:hover { opacity:0.9; transform:translateY(-2px); }

/* ===================== RESPONSIVE TABLE ===================== */
@media(max-width:1100px) {
    th.col-product, td.col-product { max-width:240px; }
    .product-cell { max-width:240px; }
}

@media(max-width:992px){
    .main-content { margin-left:0; padding:20px; }
    .sidebar { width:220px; }
}

@media(max-width:768px){
    .top-bar { flex-direction:column; gap:12px; text-align:center; }
    .sidebar { position:relative; width:100%; height:auto; }
    table { font-size:13px; }
    th, td { padding:8px 6px; }
}

@media(max-width:576px){
    table, thead, tbody, th, td, tr { display:block; }
    thead tr { position:absolute; top:-9999px; left:-9999px; }
    tr { margin-bottom:15px; border-bottom:1px solid #ddd; padding-bottom:10px; }
    td { border:none; position:relative; padding-left:50%; text-align:left; }
    td::before { position:absolute; top:12px; left:12px; width:45%; white-space:nowrap; font-weight:600; color:var(--navy); }
    td.col-id::before { content:"Test ID"; }
    td.col-product::before { content:"Product"; }
    td.col-product-status::before { content:"Product Status"; }
    td.col-testtype::before { content:"Test Type"; }
    td.col-date::before { content:"Date"; }
    td.col-tester::before { content:"Tester"; }
    td.col-teststatus::before { content:"Test Status"; }
    td.col-observed::before { content:"Observed"; }
    td.col-remarks::before { content:"Remarks"; }
    td.col-approval::before { content:"Approval"; }
    .approval-actions { flex-direction:column; gap:4px; }
}

/* ===================== GLASSMORPHISM CARDS (if needed) ===================== */
.glass-card { background: var(--glass); backdrop-filter:blur(12px); border-radius:16px; padding:1rem; box-shadow:0 10px 25px rgba(0,0,0,0.08); transition:0.3s; text-align:center; }
.glass-card:hover { transform:translateY(-6px); box-shadow:0 18px 40px rgba(0,0,0,0.12); }

</style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Supervisor Panel</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../../index.php">Dashboard</a></li>
                <li><a href="list.php">Products</a></li>
                <li><a href="tests.php" class="active">Tests</a></li>
                <li><a href="test_approval.php">Test Approval</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <h1>Product Tests</h1>
                <div class="user-info">Welcome, <?php echo htmlspecialchars($user_name); ?> <a href="../../../logout.php" class="logout-btn" style="margin-left:12px;text-decoration:none;padding:8px 12px;background:#e74c3c;color:#fff;border-radius:6px;">Logout</a></div>
            </div>
            <div class="content-section">
                <div style="margin-bottom:12px;">
                    <!-- Filters -->
                    <div style="margin-bottom:10px;">
                        <strong>Test Status: </strong>
                        <?php
                        $test_statuses = ['all','Pending','In Progress','Passed','Failed'];
                        foreach ($test_statuses as $s) {
                            $active = ($filter_test_status === $s) ? 'active' : '';
                            $url = "?test_status=$s&product_status=$filter_product_status";
                            echo "<a class='filter-pill $active' href='$url'>$s</a>";
                        }
                        ?>
                    </div>
                    <div>
                        <strong>Product Status: </strong>
                        <?php
                        $product_statuses = ['all','In Testing','Passed','Failed','Re-Manufacturing','Sent to CPRI'];
                        foreach ($product_statuses as $s) {
                            $active = ($filter_product_status === $s) ? 'active' : '';
                            $url = "?test_status=$filter_test_status&product_status=$s";
                            echo "<a class='filter-pill $active' href='$url'>$s</a>";
                        }
                        ?>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th class="col-id">Test ID</th>
                                <th class="col-product">Product</th>
                                <th class="col-product-status">Product Status</th>
                                <th class="col-testtype">Test Type</th>
                                <th class="col-date">Date</th>
                                <th class="col-tester">Tester</th>
                                <th class="col-teststatus">Test Status</th>
                                <th class="col-observed">Observed</th>
                                <th class="col-remarks">Remarks</th>
                                <th class="col-approval">Approval</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while($r = $result->fetch_assoc()): 
                                    $testStatusClass = str_replace(" ","",$r['test_status']);
                                    $productStatusClass = str_replace(" ","",$r['product_status']);
                                ?>
                                    <tr>
                                        <td class="col-id"><?php echo htmlspecialchars($r['test_id']); ?></td>
                                        <td class="col-product"><div class="product-cell"><?php echo htmlspecialchars($r['product_name']); ?></div></td>
                                        <td class="col-product-status"><span class="badge <?php echo htmlspecialchars($productStatusClass); ?>"><?php echo htmlspecialchars($r['product_status']); ?></span></td>
                                        <td class="col-testtype"><?php echo htmlspecialchars($r['test_type_name']); ?></td>
                                        <td class="col-date"><?php echo htmlspecialchars($r['test_date']); ?></td>
                                        <td class="col-tester"><?php echo htmlspecialchars($r['tester_name'] ?? '-'); ?></td>
                                        <td class="col-teststatus"><span class="badge <?php echo htmlspecialchars($testStatusClass); ?>"><?php echo htmlspecialchars($r['test_status']); ?></span></td>
                                        <td class="col-observed"><?php echo htmlspecialchars($r['observed_results'] ?? '-'); ?></td>
                                        <td class="col-remarks"><?php echo htmlspecialchars($r['test_remarks'] ?? '-'); ?></td>
                                        <td class="col-approval">
                                            <?php if($r['approval_status'] === 'Pending'): ?>
                                                <div class="approval-actions">
                                                    <form method="post" action="update-test-approval.php" style="display:inline;">
                                                        <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($r['test_id']); ?>">
                                                        <button type="submit" name="action" value="Approved" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                    <form method="post" action="update-test-approval.php" style="display:inline;">
                                                        <input type="hidden" name="test_id" value="<?php echo htmlspecialchars($r['test_id']); ?>">
                                                        <button type="submit" name="action" value="Rejected" class="btn btn-sm btn-danger">Reject</button>
                                                    </form>
                                                </div>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($r['approval_status']); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="10">NO TESTS FOUND</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    <script src="../../../admin/assets/js/confirm.js"></script>
</body>
</html>

<?php 
// Connection is managed by config/database.php, no need to close
?>
