<?php
// tester/update_test.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$test_id = isset($_GET['id']) ? $_GET['id'] : '';

$success_message = '';
$error_message = '';

// Get test details
$test = getTestById($conn, $test_id);

if (!$test) {
    header("Location: my_tests.php");
    exit();
}

// Check if this test belongs to the current tester
if ($test['tester_id'] != $user_id) {
    $error_message = "You don't have permission to edit this test.";
}

// Get test parameters
$test_parameters = getTestParameters($conn, $test_id);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$error_message) {
    $test_status = $_POST['test_status'];
    $observed_results = $_POST['observed_results'];
    $test_remarks = $_POST['test_remarks'];
    $test_criteria_met = isset($_POST['test_criteria_met']) ? 1 : 0;
    $failure_reason = $_POST['failure_reason'];
    
    // Update test record
    $update_query = "UPDATE tests SET 
                     test_status = ?,
                     observed_results = ?,
                     test_criteria_met = ?,
                     test_remarks = ?,
                     failure_reason = ?
                     WHERE test_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssisss", $test_status, $observed_results, $test_criteria_met, 
                      $test_remarks, $failure_reason, $test_id);
    
    if ($stmt->execute()) {
        // Delete existing parameters
        $delete_params = "DELETE FROM test_parameters WHERE test_id = ?";
        $stmt = $conn->prepare($delete_params);
        $stmt->bind_param("s", $test_id);
        $stmt->execute();
        
        // Add updated test parameters
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
        updateProductStatus($conn, $test['product_id']);
        
        // Log activity
        logActivity($conn, $user_id, "Updated test: $test_id", "tests", $test_id);
        
        $success_message = "Test updated successfully!";
        
        // Refresh test data
        $test = getTestById($conn, $test_id);
        $test_parameters = getTestParameters($conn, $test_id);
    } else {
        $error_message = "Error updating test: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Test - Lab Automation System</title>
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
            <h1>Update Test: <?php echo htmlspecialchars($test_id); ?></h1>
            <a href="my_tests.php" class="btn-back">← Back to My Tests</a>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-container">
            <!-- Test Information -->
            <div class="info-box">
                <h3 style="margin-bottom: 15px;">Test Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Test ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($test['test_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Product ID:</span>
                        <span class="info-value"><?php echo htmlspecialchars($test['product_id']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Product Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($test['product_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Test Type:</span>
                        <span class="info-value"><?php echo htmlspecialchars($test['test_type_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Test Date:</span>
                        <span class="info-value"><?php echo date('M d, Y', strtotime($test['test_date'])); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Tester:</span>
                        <span class="info-value"><?php echo htmlspecialchars($test['tester_name']); ?></span>
                    </div>
                </div>
            </div>

            <?php if (!$error_message || $error_message != "You don't have permission to edit this test."): ?>
            <form method="POST" action="">
                <!-- Test Status -->
                <div class="form-section">
                    <h2>Test Status & Results</h2>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Test Status</label>
                            <select name="test_status" required>
                                <option value="Pending" <?php echo $test['test_status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $test['test_status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Passed" <?php echo $test['test_status'] == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                                <option value="Failed" <?php echo $test['test_status'] == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="test_criteria_met" id="test_criteria_met" 
                                    <?php echo $test['test_criteria_met'] ? 'checked' : ''; ?>>
                                <label for="test_criteria_met">Test Criteria Met</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Observed Results</label>
                            <textarea name="observed_results"><?php echo htmlspecialchars($test['observed_results']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>Test Remarks</label>
                            <textarea name="test_remarks"><?php echo htmlspecialchars($test['test_remarks']); ?></textarea>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Failure Reason (if applicable)</label>
                            <textarea name="failure_reason"><?php echo htmlspecialchars($test['failure_reason']); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Test Parameters -->
                <div class="form-section">
                    <h2>Test Parameters</h2>
                    <div class="parameters-section">
                        <div id="parametersContainer">
                            <?php if (count($test_parameters) > 0): ?>
                                <?php foreach ($test_parameters as $param): ?>
                                <div class="parameter-row">
                                    <input type="text" name="param_name[]" value="<?php echo htmlspecialchars($param['parameter_name']); ?>" placeholder="Parameter Name">
                                    <input type="text" name="param_expected[]" value="<?php echo htmlspecialchars($param['expected_value']); ?>" placeholder="Expected Value">
                                    <input type="text" name="param_actual[]" value="<?php echo htmlspecialchars($param['actual_value']); ?>" placeholder="Actual Value">
                                    <input type="text" name="param_unit[]" value="<?php echo htmlspecialchars($param['unit']); ?>" placeholder="Unit">
                                    <input type="checkbox" name="param_within_range[]" <?php echo $param['is_within_range'] ? 'checked' : ''; ?> title="Within Range">
                                    <input type="text" name="param_remarks[]" value="<?php echo htmlspecialchars($param['remarks']); ?>" placeholder="Remarks">
                                    <button type="button" class="btn-remove" onclick="removeParameter(this)">×</button>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                            <div class="parameter-row">
                                <input type="text" name="param_name[]" placeholder="Parameter Name">
                                <input type="text" name="param_expected[]" placeholder="Expected Value">
                                <input type="text" name="param_actual[]" placeholder="Actual Value">
                                <input type="text" name="param_unit[]" placeholder="Unit">
                                <input type="checkbox" name="param_within_range[]" title="Within Range">
                                <input type="text" name="param_remarks[]" placeholder="Remarks">
                                <button type="button" class="btn-remove" onclick="removeParameter(this)">×</button>
                            </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="btn-add" onclick="addParameter()">+ Add Parameter</button>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='my_tests.php'">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Test</button>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
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