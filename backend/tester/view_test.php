<?php
// tester/view_test.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$test_id = isset($_GET['id']) ? $_GET['id'] : '';
$test = getTestById($conn, $test_id);

if (!$test) {
    header("Location: my_tests.php");
    exit();
}

$test_parameters = getTestParameters($conn, $test_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Test - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p class="user-role">Tester</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="my_tests.php" class="active">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li><a href="profile.php">My Profile</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Test Details</h1>
            <div class="header-actions">
                <a href="my_tests.php" class="btn btn-back">← Back</a>
                <?php if ($test['tester_id'] == $_SESSION['user_id']): ?>
                <a href="update_test.php?id=<?php echo $test_id; ?>" class="btn btn-edit">Edit Test</a>
                <?php endif; ?>
                <button onclick="window.print()" class="btn btn-print">Print</button>
            </div>
        </div>

        <!-- Test Information -->
        <div class="content-section">
            <h2 class="section-title">Test Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Test ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($test['test_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Product ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($test['product_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Product Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($test['product_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Test Type</span>
                    <span class="info-value"><?php echo htmlspecialchars($test['test_type_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Test Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($test['test_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Test Time</span>
                    <span class="info-value"><?php echo date('h:i A', strtotime($test['test_time'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tester Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($test['tester_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Test Status</span>
                    <span class="info-value">
                        <?php
                        $badge_class = 'badge-pending';
                        if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                        elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                        elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-progress';
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Criteria Met</span>
                    <span class="info-value">
                        <?php if ($test['test_criteria_met']): ?>
                            <span class="check-icon">✓ Yes</span>
                        <?php else: ?>
                            <span class="cross-icon">✗ No</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Test Criteria -->
        <?php if ($test['test_criteria']): ?>
        <div class="content-section">
            <h2 class="section-title">Test Criteria</h2>
            <div class="remarks-box">
                <?php echo nl2br(htmlspecialchars($test['test_criteria'])); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Test Results -->
        <div class="content-section">
            <h2 class="section-title">Test Results</h2>
            <?php if ($test['observed_results']): ?>
            <div style="margin-bottom: 20px;">
                <strong>Observed Results:</strong>
                <div class="remarks-box">
                    <?php echo nl2br(htmlspecialchars($test['observed_results'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($test['test_remarks']): ?>
            <div style="margin-bottom: 20px;">
                <strong>Test Remarks:</strong>
                <div class="remarks-box">
                    <?php echo nl2br(htmlspecialchars($test['test_remarks'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($test['failure_reason']): ?>
            <div>
                <strong>Failure Reason:</strong>
                <div class="remarks-box" style="border-left-color: #e74c3c;">
                    <?php echo nl2br(htmlspecialchars($test['failure_reason'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Test Parameters -->
        <?php if (count($test_parameters) > 0): ?>
        <div class="content-section">
            <h2 class="section-title">Test Parameters & Measurements</h2>
            <table>
                <thead>
                    <tr>
                        <th>Parameter</th>
                        <th>Expected Value</th>
                        <th>Actual Value</th>
                        <th>Unit</th>
                        <th>Within Range</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_parameters as $param): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($param['parameter_name']); ?></td>
                        <td><?php echo htmlspecialchars($param['expected_value']); ?></td>
                        <td><?php echo htmlspecialchars($param['actual_value']); ?></td>
                        <td><?php echo htmlspecialchars($param['unit']); ?></td>
                        <td>
                            <?php if ($param['is_within_range']): ?>
                                <span class="check-icon">✓</span>
                            <?php else: ?>
                                <span class="cross-icon">✗</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($param['remarks']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Product Specifications -->
        <?php if ($test['specifications']): ?>
        <div class="content-section">
            <h2 class="section-title">Product Specifications</h2>
            <div class="remarks-box">
                <?php echo nl2br(htmlspecialchars($test['specifications'])); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <script src="../admin/assets/js/confirm.js"></script>
</body>
</html>