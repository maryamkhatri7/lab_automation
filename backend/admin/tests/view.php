<?php
// admin/tests/view.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$test_id = $_GET['id'] ?? '';

if (empty($test_id)) {
    header("Location: list.php");
    exit();
}

$test = getTestById($conn, $test_id);

if (!$test) {
    header("Location: list.php");
    exit();
}

$parameters = getTestParameters($conn, $test_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test - Admin Panel</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #333;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: #bdc3c7;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: #34495e;
            border-left: 3px solid #3498db;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-item label {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-item .value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-info {
            background: #cce5ff;
            color: #004085;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
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
                <li><a href="list.php">Tests</a></li>
                <li><a href="../users/list.php">Users</a></li>
                <li><a href="../users/add.php">Add User</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
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
                <h1>Test Details</h1>
                <div>
                    <a href="list.php" class="btn btn-secondary">Back to List</a>
                    <a href="edit.php?id=<?php echo urlencode($test['test_id']); ?>" class="btn" style="background:#f39c12;color:#fff;margin-left:8px;">Edit</a>
                    <a href="list.php?delete=<?php echo urlencode($test['test_id']); ?>" class="btn confirm-delete" data-msg="Are you sure you want to delete this test?" style="background:#e74c3c;color:#fff;margin-left:8px;">Delete</a>
                </div>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Test Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Test ID</label>
                        <div class="value"><?php echo htmlspecialchars($test['test_id']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Product ID</label>
                        <div class="value"><?php echo htmlspecialchars($test['product_id']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Product Name</label>
                        <div class="value"><?php echo htmlspecialchars($test['product_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Test Type</label>
                        <div class="value"><?php echo htmlspecialchars($test['test_type_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Test Status</label>
                        <div class="value">
                            <?php
                            $badge_class = 'badge-pending';
                            if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                            elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                            elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-info';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Test Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($test['test_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Test Time</label>
                        <div class="value"><?php echo date('H:i', strtotime($test['test_time'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Tester</label>
                        <div class="value"><?php echo htmlspecialchars($test['tester_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Criteria Met</label>
                        <div class="value"><?php echo $test['test_criteria_met'] ? 'Yes' : 'No'; ?></div>
                    </div>
                    <div class="info-item">
                        <label>Retest Count</label>
                        <div class="value"><?php echo $test['retest_count']; ?></div>
                    </div>
                </div>

                <?php if ($test['observed_results']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Observed Results</label>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($test['observed_results'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($test['test_remarks']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Test Remarks</label>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($test['test_remarks'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($test['failure_reason']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Failure Reason</label>
                    <div style="padding: 15px; background: #f8d7da; border-radius: 5px; color: #721c24;">
                        <?php echo nl2br(htmlspecialchars($test['failure_reason'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($test['test_criteria']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Test Criteria</label>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($test['test_criteria'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if (count($parameters) > 0): ?>
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Test Parameters</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Parameter Name</th>
                            <th>Expected Value</th>
                            <th>Actual Value</th>
                            <th>Unit</th>
                            <th>Within Range</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($parameters as $param): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($param['parameter_name']); ?></td>
                            <td><?php echo htmlspecialchars($param['expected_value'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($param['actual_value'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($param['unit'] ?? 'N/A'); ?></td>
                            <td><?php echo $param['is_within_range'] !== null ? ($param['is_within_range'] ? 'Yes' : 'No') : 'N/A'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
