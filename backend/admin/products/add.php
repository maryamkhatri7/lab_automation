<?php
// admin/products/add.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_type_id = $_POST['product_type_id'];
    $product_name = $_POST['product_name'];
    $product_code = $_POST['product_code'];
    $revision_number = $_POST['revision_number'];
    $manufacturing_number = $_POST['manufacturing_number'];
    $batch_number = $_POST['batch_number'] ?? null;
    $manufacturing_date = $_POST['manufacturing_date'];
    $quantity = $_POST['quantity'] ?? 1;
    $specifications = $_POST['specifications'] ?? null;
    $remarks = $_POST['remarks'] ?? null;
    
    // Generate Product ID: product_code(6) + revision(2) + manufacturing_number(2) = 10 digits
    $product_id = str_pad($product_code, 6, '0', STR_PAD_LEFT) . 
                  str_pad($revision_number, 2, '0', STR_PAD_LEFT) . 
                  str_pad($manufacturing_number, 2, '0', STR_PAD_LEFT);
    
    // Check if product ID already exists
    $check_query = "SELECT product_id FROM products WHERE product_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Product ID already exists. Please check your product code, revision, and manufacturing number.";
    } else {
        $insert_query = "INSERT INTO products (product_id, product_type_id, product_name, product_code, revision_number, 
                        manufacturing_number, batch_number, manufacturing_date, quantity, specifications, 
                        current_status, remarks, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'In Testing', ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sissssssissi", $product_id, $product_type_id, $product_name, $product_code, 
                          $revision_number, $manufacturing_number, $batch_number, $manufacturing_date, 
                          $quantity, $specifications, $remarks, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], "Added new product", "products", $product_id);
            $success = "Product added successfully! Product ID: $product_id";
            // Clear form
            $_POST = array();
        } else {
            $error = "Error adding product: " . $conn->error;
        }
    }
}

// Get product types
$product_types = $conn->query("SELECT * FROM product_types WHERE is_active = 1 ORDER BY product_type_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">

   <style>
    /* ================= ROOT VARIABLES ================= */
:root {
    --navy: #0b1c2d;
    --text: #1e293b;
    --accent: #6dbcf6;
    --glass: #ffffff;
    --success: #d4edda;
    --success-text: #155724;
    --error: #f8d7da;
    --error-text: #721c24;
    --info: #d1ecf1;
    --info-text: #0c5460;
    --warning: #fff3cd;
    --warning-text: #856404;
    --purple: #f0e6ff;
    --purple-text: #4b2c6f;
}
.btn {
    display: inline-block;
    text-decoration: none;
    border-radius: 8px;
    font-size: 0.9rem;
    padding: 8px 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
}
.btn-primary {
    background: var(--accent);
    color: var(--navy);
}
.btn-primary:hover {
    background: #58a0e3;
}
.btn-secondary {
    background: #e2e8f0;
    color: var(--navy);
}
.btn-secondary:hover {
    background: #d1d5db;
}

/* ================= CONTENT SECTIONS ================= */
.content-section {
    background: #fff;
    padding: 20px;
    border-radius: 12px;
    width: 100%;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}
.form-group {
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
}
.form-group label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 4px;
}
.form-group input,
.form-group select,
.form-group textarea {
    padding: 8px 12px;
    font-size: 0.9rem;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    width: 100%;
}
textarea {
    resize: vertical;
}
.help-text {
    font-size: 0.75rem;
    color: #6b7280;
    margin-top: 2px;
}

/* ================= FORM ROWS ================= */
.form-row {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.form-row .form-group {
    flex: 1;
}

/* ================= ALERTS ================= */
.alert {
    padding: 10px 16px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 0.85rem;
}
.alert-success {
    background: var(--success);
    color: var(--success-text);
}
.alert-error {
    background: var(--error);
    color: var(--error-text);
}

/* ================= RESPONSIVE ================= */
/* Mobile first */
@media (max-width: 767px) {
    
    .form-row {
        flex-direction: column;
    }
    .btn {
        width: 120px;
        text-align: center;
       
    }
}
/* Tablet */
@media (min-width: 768px) and (max-width: 991px) {
    .sidebar {
        width: 200px;
    }
    .form-row {
        gap: 12px;
    }
}
/* Large screens */
@media (min-width: 1200px) {
    .main-content {
        padding: 32px;
    }
}

/* ================= TABLES ================= */
table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}
th, td {
    padding: 12px;
    font-size: 0.85rem;
    text-align: left;
}
th {
    background: #f1f5f9;
    font-size: 0.75rem;
    text-transform: uppercase;
}
tr:hover td {
    background: rgba(109, 188, 246, 0.08);
}

/* ================= BADGES ================= */
.badge {
    display: inline-block;
    padding: 6px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 14px;
    white-space: nowrap;
}
.badge-success {
    background: var(--success);
    color: var(--success-text);
}
.badge-danger {
    background: var(--error);
    color: var(--error-text);
}
.badge-info {
    background: var(--info);
    color: var(--info-text);
}

/* ================= FORM ACTIONS ================= */
.form-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

/* ================= MOBILE TABLE ================= */
@media (max-width: 576px) {
    table {
        min-width: 100%;
        font-size: 0.75rem;
    }
    th, td {
        padding: 10px;
    }
    .badge {
        font-size: 0.7rem;
        padding: 4px 10px;
    }
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
                <li><a href="add.php" class="active">Add Product</a></li>
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
                <h1>Add New Product</h1>
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
                        <label>Product Type *</label>
                        <select name="product_type_id" required>
                            <option value="">Select Product Type</option>
                            <?php foreach ($product_types as $type): ?>
                            <option value="<?php echo $type['product_type_id']; ?>" <?php echo (isset($_POST['product_type_id']) && $_POST['product_type_id'] == $type['product_type_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['product_type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Product Name *</label>
                        <input type="text" name="product_name" value="<?php echo htmlspecialchars($_POST['product_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Product Code (6 digits) *</label>
                            <input type="text" name="product_code" value="<?php echo htmlspecialchars($_POST['product_code'] ?? ''); ?>" 
                                   maxlength="6" pattern="[0-9]{1,6}" required>
                            <div class="help-text">Numeric code, will be padded to 6 digits</div>
                        </div>

                        <div class="form-group">
                            <label>Revision Number (2 digits) *</label>
                            <input type="text" name="revision_number" value="<?php echo htmlspecialchars($_POST['revision_number'] ?? '01'); ?>" 
                                   maxlength="2" pattern="[0-9]{1,2}" required>
                            <div class="help-text">Will be padded to 2 digits</div>
                        </div>

                        <div class="form-group">
                            <label>Manufacturing Number (2 digits) *</label>
                            <input type="text" name="manufacturing_number" value="<?php echo htmlspecialchars($_POST['manufacturing_number'] ?? ''); ?>" 
                                   maxlength="2" pattern="[0-9]{1,2}" required>
                            <div class="help-text">Will be padded to 2 digits</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Generated Product ID</label>
                        <input type="text" value="<?php 
                            if (isset($_POST['product_code']) && isset($_POST['revision_number']) && isset($_POST['manufacturing_number'])) {
                                echo str_pad($_POST['product_code'], 6, '0', STR_PAD_LEFT) . 
                                     str_pad($_POST['revision_number'], 2, '0', STR_PAD_LEFT) . 
                                     str_pad($_POST['manufacturing_number'], 2, '0', STR_PAD_LEFT);
                            } else {
                                echo 'Enter values above to generate';
                            }
                        ?>" readonly style="background: #ecf0f1;">
                        <div class="help-text">Auto-generated: Product Code(6) + Revision(2) + Manufacturing Number(2) = 10 digits</div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Batch Number</label>
                            <input type="text" name="batch_number" value="<?php echo htmlspecialchars($_POST['batch_number'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label>Manufacturing Date *</label>
                            <input type="date" name="manufacturing_date" value="<?php echo htmlspecialchars($_POST['manufacturing_date'] ?? date('Y-m-d')); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Quantity *</label>
                            <input type="number" name="quantity" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '1'); ?>" min="1" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Specifications</label>
                        <textarea name="specifications" rows="4"><?php echo htmlspecialchars($_POST['specifications'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"><?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add Product</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
