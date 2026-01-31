<?php
// admin/products/edit.php
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

$product = getProductById($conn, $product_id);

if (!$product) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_status = $_POST['current_status'];
    $product_name = $_POST['product_name'];
    $batch_number = $_POST['batch_number'] ?? null;
    $quantity = $_POST['quantity'] ?? 1;
    $specifications = $_POST['specifications'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    $cpri_submission_date = $_POST['cpri_submission_date'] ?? null;
    
    $update_query = "UPDATE products SET product_name = ?, batch_number = ?, quantity = ?, 
                     specifications = ?, remarks = ?, current_status = ?, cpri_submission_date = ? 
                     WHERE product_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssisssss", $product_name, $batch_number, $quantity, $specifications, 
                      $remarks, $current_status, $cpri_submission_date, $product_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], "Edited product", "products", $product_id);
        $success = "Product updated successfully!";
        // Refresh product data
        $product = getProductById($conn, $product_id);
    } else {
        $error = "Error updating product: " . $conn->error;
    }
}

$product_types = $conn->query("SELECT * FROM product_types WHERE is_active = 1 ORDER BY product_type_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product - Admin Panel</title>
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
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
            transition: background 0.3s;
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
                <h1>Edit Product</h1>
                <div>
                    <a href="view.php?id=<?php echo $product_id; ?>" class="btn btn-secondary">View Product</a>
                    <a href="list.php" class="btn btn-secondary">Back to List</a>
                </div>
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
                        <label>Product ID</label>
                        <input type="text" value="<?php echo htmlspecialchars($product['product_id']); ?>" readonly class="readonly-field">
                    </div>

                    <div class="form-group">
                        <label>Product Type</label>
                        <input type="text" value="<?php echo htmlspecialchars($product['product_type_name']); ?>" readonly class="readonly-field">
                    </div>

                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Code</label>
                            <input type="text" value="<?php echo htmlspecialchars($product['product_code']); ?>" readonly class="readonly-field">
                        </div>

                        <div class="form-group">
                            <label>Revision Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($product['revision_number']); ?>" readonly class="readonly-field">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Manufacturing Number</label>
                            <input type="text" value="<?php echo htmlspecialchars($product['manufacturing_number']); ?>" readonly class="readonly-field">
                        </div>

                        <div class="form-group">
                            <label>Manufacturing Date</label>
                            <input type="text" value="<?php echo date('M d, Y', strtotime($product['manufacturing_date'])); ?>" readonly class="readonly-field">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Batch Number</label>
                            <input type="text" name="batch_number" value="<?php echo htmlspecialchars($product['batch_number'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Current Status *</label>
                        <select name="current_status" required>
                            <option value="In Testing" <?php echo $product['current_status'] == 'In Testing' ? 'selected' : ''; ?>>In Testing</option>
                            <option value="Passed" <?php echo $product['current_status'] == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                            <option value="Failed" <?php echo $product['current_status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="Sent to CPRI" <?php echo $product['current_status'] == 'Sent to CPRI' ? 'selected' : ''; ?>>Sent to CPRI</option>
                            <option value="Re-Manufacturing" <?php echo $product['current_status'] == 'Re-Manufacturing' ? 'selected' : ''; ?>>Re-Manufacturing</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>CPRI Submission Date</label>
                        <input type="date" name="cpri_submission_date" value="<?php echo $product['cpri_submission_date'] ? date('Y-m-d', strtotime($product['cpri_submission_date'])) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Specifications</label>
                        <textarea name="specifications" rows="4"><?php echo htmlspecialchars($product['specifications'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"><?php echo htmlspecialchars($product['remarks'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update Product</button>
                        <a href="view.php?id=<?php echo $product_id; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
