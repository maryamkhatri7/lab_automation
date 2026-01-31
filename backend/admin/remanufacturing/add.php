<?php
// admin/remanufacturing/add.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$product_id = $_GET['product_id'] ?? '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $original_test_id = $_POST['original_test_id'] ?? null;
    $remanufacturing_date = $_POST['remanufacturing_date'];
    $reason = $_POST['reason'];
    $cost = !empty($_POST['cost']) ? $_POST['cost'] : null;
    $status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? null;
    
    $insert_query = "INSERT INTO remanufacturing_records (product_id, original_test_id, remanufacturing_date, reason, cost, status, remarks, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssssdssi", $product_id, $original_test_id, $remanufacturing_date, $reason, $cost, $status, $remarks, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        // Update product status
        $update_query = "UPDATE products SET current_status = 'Re-Manufacturing' WHERE product_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("s", $product_id);
        $stmt->execute();
        
        logActivity($conn, $_SESSION['user_id'], "Created remanufacturing record", "remanufacturing_records", $product_id);
        $success = "Re-manufacturing record created successfully!";
        header("Location: list.php");
        exit();
    } else {
        $error = "Error creating record: " . $conn->error;
    }
}

// Get product details if product_id is provided
$product = null;
if (!empty($product_id)) {
    $product = getProductById($conn, $product_id);
}

// Get failed tests for this product
$failed_tests = [];
if ($product) {
    $tests_query = "SELECT test_id, test_type_name, test_date, failure_reason 
                    FROM tests t
                    JOIN test_types tt ON t.test_type_id = tt.test_type_id
                    WHERE product_id = ? AND test_status = 'Failed'
                    ORDER BY test_date DESC";
    $stmt = $conn->prepare($tests_query);
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $failed_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Re-Manufacturing Record - Admin Panel</title>
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
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
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
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            padding: 12px 24px;
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
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .readonly-field {
            background: #ecf0f1;
            color: #7f8c8d;
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
                <h1>Add Re-Manufacturing Record</h1>
                <a href="list.php" class="btn btn-secondary">Back to List</a>
            </div>

            <div class="content-section">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label>Product ID *</label>
                        <input type="text" name="product_id" value="<?php echo htmlspecialchars($product_id); ?>" required>
                        <?php if ($product): ?>
                        <div style="margin-top: 5px; color: #7f8c8d;">
                            Product: <?php echo htmlspecialchars($product['product_name']); ?>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if (count($failed_tests) > 0): ?>
                    <div class="form-group">
                        <label>Original Test ID (Failed Test)</label>
                        <select name="original_test_id">
                            <option value="">Select Failed Test</option>
                            <?php foreach ($failed_tests as $test): ?>
                            <option value="<?php echo htmlspecialchars($test['test_id']); ?>">
                                <?php echo htmlspecialchars($test['test_id'] . ' - ' . $test['test_type_name'] . ' (' . date('M d, Y', strtotime($test['test_date'])) . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Re-Manufacturing Date *</label>
                            <input type="date" name="remanufacturing_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Status *</label>
                            <select name="status" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Reason *</label>
                        <textarea name="reason" rows="4" required><?php echo htmlspecialchars($_POST['reason'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Cost (₹)</label>
                            <input type="number" name="cost" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['cost'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Create Record</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
