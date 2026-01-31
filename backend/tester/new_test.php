<?php
// tester/new_test.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $test_type_id = $_POST['test_type_id'];
    $test_date = $_POST['test_date'];
    $test_time = $_POST['test_time'];
    $test_status = $_POST['test_status'];
    $observed_results = $_POST['observed_results'];
    $test_remarks = $_POST['test_remarks'];
    $test_criteria_met = isset($_POST['test_criteria_met']) ? 1 : 0;
    
    // Get product details
    $product = getProductById($conn, $product_id);
    
    // Get test type details
    $test_type_query = "SELECT * FROM test_types WHERE test_type_id = ?";
    $stmt = $conn->prepare($test_type_query);
    $stmt->bind_param("i", $test_type_id);
    $stmt->execute();
    $test_type = $stmt->get_result()->fetch_assoc();
    
    // Generate Test ID
    $testing_code = $test_type['test_type_code'];
    $test_id = generateTestID($conn, $product['product_code'], $product['revision_number'], $testing_code);
    
    // Get roll number from test_id
    $roll_number = substr($test_id, -2);
    
    // Insert test record
    $insert_query = "INSERT INTO tests (test_id, product_id, test_type_id, product_code, revision_number, 
                     testing_code, roll_number, test_date, test_time, tester_id, test_status, 
                     observed_results, test_criteria_met, test_remarks) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("ssissssssissis", 
        $test_id, $product_id, $test_type_id, $product['product_code'], 
        $product['revision_number'], $testing_code, $roll_number, 
        $test_date, $test_time, $user_id, $test_status, 
        $observed_results, $test_criteria_met, $test_remarks
    );
    
    if ($stmt->execute()) {
        // Add test parameters if provided
        if (isset($_POST['param_name']) && is_array($_POST['param_name'])) {
            for ($i = 0; $i < count($_POST['param_name']); $i++) {
                if (!empty($_POST['param_name'][$i])) {
                    $param_query = "INSERT INTO test_parameters (test_id, parameter_name, expected_value, 
                                   actual_value, unit, is_within_range, remarks) 
                                   VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
                    $param_stmt = $conn->prepare($param_query);
                    $is_within_range = isset($_POST['param_within_range'][$i]) ? 1 : 0;
                    
                    $param_stmt->bind_param("sssssss", 
                        $test_id,
                        $_POST['param_name'][$i],
                        $_POST['param_expected'][$i],
                        $_POST['param_actual'][$i],
                        $_POST['param_unit'][$i],
                        $is_within_range,
                        $_POST['param_remarks'][$i]
                    );
                    $param_stmt->execute();
                }
            }
        }
        
        // Update product status
        updateProductStatus($conn, $product_id);
        
        // Log activity
        logActivity($conn, $user_id, "Created new test: $test_id", "tests", $test_id);
        
        $success_message = "Test created successfully! Test ID: $test_id";
    } else {
        $error_message = "Error creating test: " . $conn->error;
    }
}

// Get all products
$products = getAllProducts($conn);
$test_types = getAllTestTypes($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New Test - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
/* ================= GLOBAL ================= */
.main-content {
    padding: 20px;
    font-family: "Segoe UI", sans-serif;
    width: 100%;
    box-sizing: border-box;
}

/* Page Header */
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 16px;
}
.page-header h1 {
    font-size: 1.5rem;
    margin: 0;
}
.page-header .btn-back {
    background: var(--accent);
    color: var(--navy);
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
}
.page-header .btn-back:hover { opacity: 0.9; }

/* Alerts */
.alert {
    padding: 12px;
    border-radius: 8px;
    font-size: 0.9rem;
    margin-bottom: 16px;
}
.alert-success { background: var(--success); color: var(--success-text); }
.alert-error { background: var(--error); color: var(--error-text); }

/* Form Container */
.form-container {
    background: var(--glass);
    padding: 16px;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

/* Form Sections */
.form-section {
    margin-bottom: 18px;
}
.form-section h2 {
    font-size: 1.2rem;
    margin-bottom: 12px;
    border-bottom: 1px solid #eee;
    padding-bottom: 6px;
}

/* Form Rows & Groups */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 8px; /* compact spacing */
    align-items: flex-start; /* aligns checkbox & inputs at top */
}
.form-group {
    flex: 1 1 180px;
    display: flex;
    flex-direction: column;
    margin-bottom: 0;
}
label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 2px;
}
input[type="text"],
input[type="date"],
input[type="time"],
select,
textarea {
    padding: 6px 10px;
    font-size: 0.85rem;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 0;
}
textarea { min-height: 50px; resize: vertical; }

/* Checkbox Group */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 4px;
    margin-top: 2px;
}

/* Product Info Box */
.product-info {
    font-size: 0.8rem;
    color: #555;
    display: none;
    padding: 6px 10px;
    border-left: 3px solid var(--accent);
    background: #f9f9f9;
    border-radius: 5px;
    margin-top: 4px;
}
.product-info.show { display: block; }
.product-info .info-row { margin-bottom: 3px; }
.product-info .info-label { font-weight: 600; margin-right: 4px; }

/* Test Parameters Section */
.parameters-section {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.parameter-row {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    align-items: center;
}
.parameter-row input[type="text"] { flex: 1 1 100px; font-size: 0.85rem; }
.parameter-row input[type="checkbox"] { width: auto; }
.parameter-row .btn-remove {
    background: #dc3545;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 3px 6px;
    cursor: pointer;
    font-size: 0.75rem;
}
.parameter-row .btn-remove:hover { opacity: 0.9; }
.btn-add {
    background: var(--accent);
    color: var(--navy);
    border: none;
    border-radius: 5px;
    padding: 5px 10px;
    font-size: 0.8rem;
    cursor: pointer;
    align-self: flex-start;
}

/* Form Actions */
.form-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-top: 8px;
}
.form-actions .btn {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
}
.btn-primary { background: var(--accent); color: var(--navy); border: none; }
.btn-primary:hover { opacity: 0.9; }
.btn-secondary { background: #ccc; color: var(--navy); border: none; }
.btn-secondary:hover { opacity: 0.9; }

/* ================= RESPONSIVE ================= */
@media(max-width: 991px) {
    .form-row { flex-direction: column; gap: 6px; }
    .parameter-row { flex-direction: column; align-items: flex-start; gap: 3px; }
    .form-actions { flex-direction: column; }
    .form-actions .btn { width: 100%; }
    input, select, textarea { font-size: 0.83rem; padding: 5px 8px; }
    .product-info { font-size: 0.78rem; padding: 5px 8px; }
}

@media(max-width: 767px) {
    .main-content { padding: 12px; }
    .page-header h1 { font-size: 1.3rem; }
    .page-header .btn-back { font-size: 0.8rem; padding: 4px 8px; }
    .parameter-row input[type="text"] { flex: 1 1 100%; }
    .btn-add { font-size: 0.75rem; padding: 4px 8px; }
}

@media(max-width: 576px) {
    .form-row { gap: 4px; }
    .parameter-row { gap: 3px; }
    .form-actions .btn { font-size: 0.75rem; padding: 5px 8px; }
}
/* ================= FORM ROWS & GROUPS ================= */
.form-row {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;          /* horizontal spacing between columns */
    margin-bottom: 12px; /* vertical spacing between rows */
}

.form-group {
    flex: 1 1 180px;     /* allows shrinking on smaller screens */
    display: flex;
    flex-direction: column;
    margin-bottom: 0;    /* spacing handled by .form-row margin-bottom */
}

label {
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 4px;  /* small spacing between label and input */
}

input[type="text"],
input[type="date"],
input[type="time"],
select,
textarea {
    padding: 6px 10px;
    font-size: 0.85rem;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
    margin: 0; /* no extra margin */
}

/* Checkbox spacing */
.checkbox-group {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 4px;
}

/* ================= RESPONSIVE FIXES ================= */
@media (max-width: 991px) {
    .form-row { flex-direction: column; margin-bottom: 10px; }
    .form-group { flex: 1 1 100%; }
}

@media (max-width: 576px) {
    .form-row { margin-bottom: 8px; }
}

</style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="my_tests.php">My Tests</a></li>
                <li><a href="new_test.php" class="active">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Create New Test</h1>
            <a href="index.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="" id="testForm">
                <!-- Basic Information -->
                <div class="form-section">
                    <h2>Basic Information</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Product <span class="required">*</span></label>
                            <select name="product_id" id="product_id" required onchange="showProductInfo()">
                                <option value="">Select Product</option>
                                <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['product_id']; ?>" 
                                    data-name="<?php echo htmlspecialchars($product['product_name']); ?>"
                                    data-type="<?php echo htmlspecialchars($product['product_type_name']); ?>"
                                    data-batch="<?php echo htmlspecialchars($product['batch_number']); ?>"
                                    data-status="<?php echo $product['current_status']; ?>">
                                    <?php echo $product['product_id'] . ' - ' . htmlspecialchars($product['product_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="productInfo" class="product-info"></div>
                        </div>

                        <div class="form-group">
                            <label>Test Type <span class="required">*</span></label>
                            <select name="test_type_id" required>
                                <option value="">Select Test Type</option>
                                <?php foreach ($test_types as $type): ?>
                                <option value="<?php echo $type['test_type_id']; ?>">
                                    <?php echo htmlspecialchars($type['test_type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Date <span class="required">*</span></label>
                            <input type="date" name="test_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Test Time <span class="required">*</span></label>
                            <input type="time" name="test_time" value="<?php echo date('H:i'); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Status <span class="required">*</span></label>
                            <select name="test_status" required>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Passed">Passed</option>
                                <option value="Failed">Failed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="test_criteria_met" id="test_criteria_met">
                                <label for="test_criteria_met">Test Criteria Met</label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Test Results -->
                <div class="form-section">
                    <h2>Test Results</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Observed Results</label>
                            <textarea name="observed_results" placeholder="Enter observed results..."></textarea>
                        </div>

                        <div class="form-group">
                            <label>Test Remarks</label>
                            <textarea name="test_remarks" placeholder="Enter remarks..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Test Parameters -->
                <div class="form-section">
                    <h2>Test Parameters (Optional)</h2>
                    <div class="parameters-section">
                        <div id="parametersContainer">
                            <div class="parameter-row">
                                <input type="text" name="param_name[]" placeholder="Parameter Name">
                                <input type="text" name="param_expected[]" placeholder="Expected Value">
                                <input type="text" name="param_actual[]" placeholder="Actual Value">
                                <input type="text" name="param_unit[]" placeholder="Unit">
                                <input type="checkbox" name="param_within_range[]" title="Within Range">
                                <input type="text" name="param_remarks[]" placeholder="Remarks">
                                <button type="button" class="btn-remove" onclick="removeParameter(this)">×</button>
                            </div>
                        </div>
                        <button type="button" class="btn-add" onclick="addParameter()">+ Add Parameter</button>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='index.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Test</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showProductInfo() {
            const select = document.getElementById('product_id');
            const option = select.options[select.selectedIndex];
            const infoDiv = document.getElementById('productInfo');
            
            if (option.value) {
                const html = `
                    <div class="info-row">
                        <span class="info-label">Product Name:</span>
                        <span>${option.dataset.name}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Product Type:</span>
                        <span>${option.dataset.type}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Batch Number:</span>
                        <span>${option.dataset.batch}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Current Status:</span>
                        <span>${option.dataset.status}</span>
                    </div>
                `;
                infoDiv.innerHTML = html;
                infoDiv.classList.add('show');
            } else {
                infoDiv.classList.remove('show');
            }
        }

        function addParameter() {
            const container = document.getElementById('parametersContainer');
            const newRow = document.createElement('div');
            newRow.className = 'parameter-row';
            newRow.innerHTML = `
                <input type="text" name="param_name[]" placeholder="Parameter Name">
                <input type="text" name="param_expected[]" placeholder="Expected Value">
                <input type="text" name="param_actual[]" placeholder="Actual Value">
                <input type="text" name="param_unit[]" placeholder="Unit">
                <input type="checkbox" name="param_within_range[]" title="Within Range">
                <input type="text" name="param_remarks[]" placeholder="Remarks">
                <button type="button" class="btn-remove" onclick="removeParameter(this)">×</button>
            `;
            container.appendChild(newRow);
        }

        function removeParameter(button) {
            const container = document.getElementById('parametersContainer');
            if (container.children.length > 1) {
                button.parentElement.remove();
            } else {
                alert('At least one parameter row is required');
            }
        }
    </script>
</body>
</html>