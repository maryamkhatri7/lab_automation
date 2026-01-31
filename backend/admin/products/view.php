<?php
// admin/products/view.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$product_id = $_GET['id'] ?? '';

if (empty($product_id)) {
    header("Location: list.php");
    exit();
}

// Get product details
$product = getProductById($conn, $product_id);

if (!$product) {
    header("Location: list.php");
    exit();
}

// Get tests for this product
$tests_query = "SELECT t.*, tt.test_type_name, u.full_name as tester_name
                FROM tests t
                JOIN test_types tt ON t.test_type_id = tt.test_type_id
                JOIN users u ON t.tester_id = u.user_id
                WHERE t.product_id = ?
                ORDER BY t.test_date DESC";
$stmt = $conn->prepare($tests_query);
$stmt->bind_param("s", $product_id);
$stmt->execute();
$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get CPRI submissions
$cpri_query = "SELECT c.*, u.full_name as submitted_by_name
               FROM cpri_submissions c
               JOIN users u ON c.submitted_by = u.user_id
               WHERE c.product_id = ?
               ORDER BY c.submission_date DESC";
$stmt = $conn->prepare($cpri_query);
$stmt->bind_param("s", $product_id);
$stmt->execute();
$cpri_submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get remanufacturing records
$remanufacturing_query = "SELECT r.*, u.full_name as created_by_name
                          FROM remanufacturing_records r
                          JOIN users u ON r.created_by = u.user_id
                          WHERE r.product_id = ?
                          ORDER BY r.remanufacturing_date DESC";
$stmt = $conn->prepare($remanufacturing_query);
$stmt->bind_param("s", $product_id);
$stmt->execute();
$remanufacturing_records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Product - Admin Panel</title>
<link rel="stylesheet" href="../../supervisor/public/style.css">

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
      
        .main-content {
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
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        table th {
            background: #f8f9fa;
            color: #2c3e50;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
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
        
        .btn-primary {
            background: #3498db;
            color: white;
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
                <li><a href="list.php">Products</a></li>
                <li><a href="add.php">Add Product</a></li>
                <li><a href="../tests/list.php">Tests</a></li>
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
                <h1>Product Details</h1>
                <div>
                    <a href="edit.php?id=<?php echo $product_id; ?>" class="btn btn-primary">Edit Product</a>
                    <a href="list.php?delete=<?php echo urlencode($product_id); ?>" class="btn confirm-delete" data-msg="Are you sure you want to delete this product?" style="background:#e74c3c;color:#fff;margin-left:8px;">Delete Product</a>
                    <a href="list.php" class="btn btn-secondary" style="margin-left:8px;">Back to List</a>
                </div>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Product Information</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Product ID</label>
                        <div class="value"><?php echo htmlspecialchars($product['product_id']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Product Name</label>
                        <div class="value"><?php echo htmlspecialchars($product['product_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Product Type</label>
                        <div class="value"><?php echo htmlspecialchars($product['product_type_name']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Current Status</label>
                        <div class="value">
                            <?php
                            $badge_class = 'badge-pending';
                            if ($product['current_status'] == 'Passed') $badge_class = 'badge-success';
                            elseif ($product['current_status'] == 'Failed') $badge_class = 'badge-danger';
                            elseif ($product['current_status'] == 'Sent to CPRI') $badge_class = 'badge-info';
                            elseif ($product['current_status'] == 'Re-Manufacturing') $badge_class = 'badge-warning';
                            ?>
                            <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <label>Product Code</label>
                        <div class="value"><?php echo htmlspecialchars($product['product_code']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Revision Number</label>
                        <div class="value"><?php echo htmlspecialchars($product['revision_number']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Manufacturing Number</label>
                        <div class="value"><?php echo htmlspecialchars($product['manufacturing_number']); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Batch Number</label>
                        <div class="value"><?php echo htmlspecialchars($product['batch_number'] ?? 'N/A'); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Manufacturing Date</label>
                        <div class="value"><?php echo date('M d, Y', strtotime($product['manufacturing_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <label>Quantity</label>
                        <div class="value"><?php echo $product['quantity']; ?></div>
                    </div>
                    <div class="info-item">
                        <label>CPRI Submission Date</label>
                        <div class="value"><?php echo $product['cpri_submission_date'] ? date('M d, Y', strtotime($product['cpri_submission_date'])) : 'N/A'; ?></div>
                    </div>
                    <div class="info-item">
                        <label>Created At</label>
                        <div class="value"><?php echo date('M d, Y H:i', strtotime($product['created_at'])); ?></div>
                    </div>
                </div>

                <?php if ($product['specifications']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Specifications</label>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($product['specifications'])); ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($product['remarks']): ?>
                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 8px; font-weight: 600;">Remarks</label>
                    <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                        <?php echo nl2br(htmlspecialchars($product['remarks'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tests Section -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Tests (<?php echo count($tests); ?>)</h2>
                <?php if (count($tests) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Test Type</th>
                            <th>Test Date</th>
                            <th>Tester</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                            <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                            <td><?php echo htmlspecialchars($test['tester_name']); ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-pending';
                                if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                                elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                                elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-info';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                            </td>
                            <td>
                                <a href="../tests/view.php?id=<?php echo $test['test_id']; ?>" class="btn btn-primary" style="font-size: 12px; padding: 5px 10px;">View</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p style="text-align: center; padding: 20px; color: #7f8c8d;">No tests found for this product.</p>
                <?php endif; ?>
            </div>

            <!-- CPRI Submissions -->
            <?php if (count($cpri_submissions) > 0): ?>
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">CPRI Submissions</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Submission Date</th>
                            <th>CPRI Reference</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cpri_submissions as $cpri): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($cpri['submission_date'])); ?></td>
                            <td><?php echo htmlspecialchars($cpri['cpri_reference_number'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo $cpri['approval_status']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($cpri['submitted_by_name']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

            <!-- Remanufacturing Records -->
            <?php if (count($remanufacturing_records) > 0): ?>
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Re-Manufacturing Records</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Reason</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($remanufacturing_records as $record): ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($record['remanufacturing_date'])); ?></td>
                            <td>
                                <span class="badge badge-warning"><?php echo $record['status']; ?></span>
                            </td>
                            <td><?php echo htmlspecialchars($record['reason']); ?></td>
                            <td><?php echo htmlspecialchars($record['created_by_name']); ?></td>
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
