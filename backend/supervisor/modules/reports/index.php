<?php 
// supervisor/modules/reports/index.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";

$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

function safe_count($conn, $sql) {
    $res = $conn->query($sql);
    return $res ? (int)$res->fetch_assoc()['count'] : 0;
}

// Supervisor-relevant counts
$total_products = safe_count($conn, "SELECT COUNT(*) as count FROM products");
$total_tests = safe_count($conn, "SELECT COUNT(*) as count FROM tests");
$pending_approvals = safe_count($conn, "SELECT COUNT(*) as count FROM tests WHERE test_status NOT IN ('Passed','Failed')");
$cpri_pending = safe_count($conn, "SELECT COUNT(*) as count FROM cpri_submissions WHERE approval_status IN ('Submitted','Under Review')");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reports - Supervisor</title>
    <link rel="stylesheet" href="../../public/style.css">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6f9; color: #333; }
       
        .logout-btn { background: #e74c3c; color: white; padding: 8px 16px; border-radius: 6px; text-decoration:none; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.06); border-left: 4px solid #3498db; }
        .stat-card h3 { font-size: 14px; color: #7f8c8d; margin-bottom: 8px; text-transform: uppercase; }
        .stat-card .value { font-size: 28px; font-weight: 700; color: #2c3e50; }
        .content-section { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.03); }
        .reports-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 12px; }
        .report-card { background: #fff; padding: 16px; border-radius: 8px; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
        .report-card h3 { margin: 0 0 6px 0; font-size: 16px; color: #2c3e50; }
        .report-card .muted { color: #7f8c8d; font-size: 13px; }
        .report-card .count { font-weight: 700; font-size: 20px; margin: 10px 0; color: #2c3e50; }
        .report-card .actions { display:flex; gap:8px; align-items:center; }
        .btn { padding: 8px 14px; background: #3498db; color: white; border-radius: 6px; text-decoration: none; display:inline-block; }
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
                <li><a href="../products/list.php">Products</a></li>
                <li><a href="../products/tests.php">Tests</a></li>
                <li><a href="../products/test_approval.php">Test Approval</a></li>
                <li><a href="index.php" class="active">Reports</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Reports</h1>
                <div class="user-info"><span>Welcome, <?php echo htmlspecialchars($user_name); ?></span><a href="../../../logout.php" class="logout-btn">Logout</a></div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Products</h3>
                    <div class="value"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Total Tests</h3>
                    <div class="value"><?php echo $total_tests; ?></div>
                </div>
                <div class="stat-card">
                    <h3>Pending Approvals</h3>
                    <div class="value"><?php echo $pending_approvals; ?></div>
                </div>
                <div class="stat-card">
                    <h3>CPRI Pending</h3>
                    <div class="value"><?php echo $cpri_pending; ?></div>
                </div>
            </div>

            <div class="content-section">
                <h2>Available Reports</h2>
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Summary Report</h3>
                        <div class="muted">Key metrics and trends</div>
                        <div class="count"><?php echo $total_tests; ?> Tests</div>
                        <div class="actions"><a href="../products/tests.php" class="btn">View Tests</a></div>
                    </div>

                    <div class="report-card">
                        <h3>Products Report</h3>
                        <div class="muted">Inventory and status breakdown</div>
                        <div class="count"><?php echo $total_products; ?> Products</div>
                        <div class="actions"><a href="../products/list.php" class="btn">View Products</a></div>
                    </div>

                    <div class="report-card">
                        <h3>CPRI Report</h3>
                        <div class="muted">CPRI submission statuses</div>
                        <div class="count"><?php echo $cpri_pending; ?> Pending</div>
                        <div class="actions"><a href="../cpri/list.php" class="btn">View CPRI</a></div>
                    </div>

                    <div class="report-card">
                        <h3>Approvals</h3>
                        <div class="muted">Tests requiring approval</div>
                        <div class="count"><?php echo $pending_approvals; ?> Pending</div>
                        <div class="actions"><a href="../products/test_approval.php" class="btn">View Approvals</a></div>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <script src="../../../admin/assets/js/confirm.js"></script>
</body>
</html>
