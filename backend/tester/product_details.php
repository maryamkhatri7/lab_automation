<?php
// tester/product_details.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$product_id = isset($_GET['id']) ? $_GET['id'] : '';
$product = getProductById($conn, $product_id);

if (!$product) {
    header("Location: search_products.php");
    exit();
}

// Get all tests for this product
$tests_query = "SELECT t.*, tt.test_type_name, u.full_name as tester_name
                FROM tests t
                JOIN test_types tt ON t.test_type_id = tt.test_type_id
                JOIN users u ON t.tester_id = u.user_id
                WHERE t.product_id = ?
                ORDER BY t.test_date DESC, t.test_time DESC";
$stmt = $conn->prepare($tests_query);
$stmt->bind_param("s", $product_id);
$stmt->execute();
$tests_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Details - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
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
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php" class="active">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Product Details</h1>
            <a href="search_products.php" class="btn-back">← Back to Search</a>
        </div>

        <!-- Product Information -->
        <div class="content-section">
            <h2 class="section-title">Product Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Product ID</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['product_id']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Product Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['product_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Product Type</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['product_type_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Product Code</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['product_code']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Revision Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['revision_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Manufacturing Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['manufacturing_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Batch Number</span>
                    <span class="info-value"><?php echo htmlspecialchars($product['batch_number']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Manufacturing Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($product['manufacturing_date'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Quantity</span>
                    <span class="info-value"><?php echo $product['quantity']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Current Status</span>
                    <span class="info-value">
                        <?php
                        $badge_class = 'badge-testing';
                        if ($product['current_status'] == 'Passed') $badge_class = 'badge-passed';
                        elseif ($product['current_status'] == 'Failed') $badge_class = 'badge-failed';
                        elseif ($product['current_status'] == 'Re-Manufacturing') $badge_class = 'badge-remanufacturing';
                        elseif ($product['current_status'] == 'Sent to CPRI') $badge_class = 'badge-cpri';
                        ?>
                        <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                    </span>
                </div>
                <?php if ($product['cpri_submission_date']): ?>
                <div class="info-item">
                    <span class="info-label">CPRI Submission Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($product['cpri_submission_date'])); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($product['specifications']): ?>
            <div style="margin-top: 20px;">
                <strong>Specifications:</strong>
                <div class="specs-box">
                    <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($product['remarks']): ?>
            <div style="margin-top: 20px;">
                <strong>Remarks:</strong>
                <div class="specs-box">
                    <?php echo nl2br(htmlspecialchars($product['remarks'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Test History -->
        <div class="content-section">
            <h2 class="section-title">Test History</h2>
            <?php if ($tests_result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Test ID</th>
                        <th>Test Type</th>
                        <th>Tester</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Criteria Met</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($test = $tests_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                        <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                        <td><?php echo htmlspecialchars($test['tester_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($test['test_time'])); ?></td>
                        <td>
                            <?php
                            $badge_class = 'badge-pending';
                            if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                            elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                            elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-progress';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                        </td>
                        <td><?php echo $test['test_criteria_met'] ? '✓' : '✗'; ?></td>
                        <td>
                            <a href="view_test.php?id=<?php echo $test['test_id']; ?>" class="btn-sm btn-view">View</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <p>No tests have been conducted on this product yet.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>