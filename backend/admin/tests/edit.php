<?php
// admin/tests/edit.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$test_id = $_GET['id'] ?? null;
if (!$test_id) {
    header('Location: list.php');
    exit();
}

// Fetch test
$stmt = $conn->prepare("SELECT t.*, p.product_name, tt.test_type_name, u.full_name AS tester_name
    FROM tests t
    JOIN products p ON t.product_id = p.product_id
    JOIN test_types tt ON t.test_type_id = tt.test_type_id
    LEFT JOIN users u ON t.tester_id = u.user_id
    WHERE t.test_id = ?");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();
if (!$test) {
    header('Location: list.php');
    exit();
}

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_status = $_POST['test_status'] ?? $test['test_status'];
    $observed = $_POST['observed_results'] ?? '';
    $remarks = $_POST['test_remarks'] ?? '';

    if ($test_status === 'Passed' || $test_status === 'Failed') {
        $approved_by = $_SESSION['user_id'];
        $approval_date = date('Y-m-d H:i:s');
        $update_sql = "UPDATE tests SET test_status = ?, observed_results = ?, test_remarks = ?, approved_by = ?, approval_date = ? WHERE test_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssssi", $test_status, $observed, $remarks, $approved_by, $approval_date, $test_id);
    } else {
        $update_sql = "UPDATE tests SET test_status = ?, observed_results = ?, test_remarks = ?, approved_by = NULL, approval_date = NULL WHERE test_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssi", $test_status, $observed, $remarks, $test_id);
    }

    $stmt->execute();
    logActivity($conn, $_SESSION['user_id'], "Edited test", "tests", $test_id);
    header("Location: view.php?id=" . urlencode($test_id) . "&msg=updated");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Test - Admin</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
    <style>
        .form-group { margin-bottom: 12px; }
        label { display:block; font-weight:600; margin-bottom:6px; }
        textarea, select, input[type=text] { width:100%; padding:10px; border-radius:6px; border:1px solid #ddd; }
        .btn { padding:8px 14px; border-radius:6px; border:none; cursor:pointer; }
        .btn-save { background:#27ae60; color:white; }
        .btn-back { background:#95a5a6; color:white; margin-right:8px; }
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
                <h1>Edit Test #<?php echo htmlspecialchars($test['test_id']); ?></h1>
                <a href="view.php?id=<?php echo urlencode($test['test_id']); ?>" class="btn btn-back">Back</a>
            </div>

            <div class="content-section">
                <form method="post">
                    <div class="form-group">
                        <label>Product</label>
                        <div><?php echo htmlspecialchars($test['product_id'] . ' - ' . $test['product_name']); ?></div>
                    </div>

                    <div class="form-group">
                        <label>Test Type</label>
                        <div><?php echo htmlspecialchars($test['test_type_name']); ?></div>
                    </div>

                    <div class="form-group">
                        <label for="test_status">Test Status</label>
                        <select name="test_status" id="test_status" required>
                            <?php
                            $statuses = ['Pending','In Progress','Passed','Failed'];
                            foreach ($statuses as $s) {
                                $sel = $test['test_status'] == $s ? 'selected' : '';
                                echo "<option value=\"$s\" $sel>$s</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="observed_results">Observed Results</label>
                        <textarea name="observed_results" id="observed_results" rows="6"><?php echo htmlspecialchars($test['observed_results']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="test_remarks">Test Remarks</label>
                        <textarea name="test_remarks" id="test_remarks" rows="4"><?php echo htmlspecialchars($test['test_remarks']); ?></textarea>
                    </div>

                    <div class="form-group" style="display:flex;justify-content:flex-end;">
                        <button type="button" onclick="location.href='view.php?id=<?php echo urlencode($test['test_id']); ?>'" class="btn btn-back">Cancel</button>
                        <button type="submit" class="btn btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>