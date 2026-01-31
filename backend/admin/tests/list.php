<?php
// admin/tests/list.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

// Filters
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search_term = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$query = "SELECT t.*, p.product_name, p.current_status as product_status, tt.test_type_name, u.full_name as tester_name
          FROM tests t
          JOIN products p ON t.product_id = p.product_id
          JOIN test_types tt ON t.test_type_id = tt.test_type_id
          JOIN users u ON t.tester_id = u.user_id
          WHERE 1=1";

$params = [];
$types = [];

if (!empty($status_filter)) {
    $query .= " AND t.test_status = ?";
    $params[] = $status_filter;
    $types[] = 's';
}

if (!empty($type_filter)) {
    $query .= " AND t.test_type_id = ?";
    $params[] = $type_filter;
    $types[] = 'i';
}

if (!empty($search_term)) {
    $query .= " AND (t.test_id LIKE ? OR t.product_id LIKE ? OR p.product_name LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types[] = 's';
    $types[] = 's';
    $types[] = 's';
}

if (!empty($date_from)) {
    $query .= " AND t.test_date >= ?";
    $params[] = $date_from;
    $types[] = 's';
}

if (!empty($date_to)) {
    $query .= " AND t.test_date <= ?";
    $params[] = $date_to;
    $types[] = 's';
}

$query .= " ORDER BY t.test_date DESC, t.test_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle delete action (mark test status as Deleted)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "UPDATE tests SET test_status = 'Deleted' WHERE test_id = ?";
    $stmt_del = $conn->prepare($delete_query);
    $stmt_del->bind_param("i", $delete_id);
    $stmt_del->execute();
    logActivity($conn, $_SESSION['user_id'], "Deleted test", "tests", $delete_id);
    header("Location: list.php?msg=deleted");
    exit;
}

// Get test types for filter
$test_types = $conn->query("SELECT * FROM test_types WHERE is_active = 1 ORDER BY test_type_name")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tests - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">

<style>
    .view-btn,.delete-btn,.edit-btn{
        font-size:0.65rem!important;
        margin-bottom:3px!important;
    }
     .view-btn,.delete-btn,.edit-btn :hover{
        background-color:rgb(109,188,246)!important;
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
                <li><a href="list.php" class="active">Tests</a></li>
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
                <h1>Tests Management</h1>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="margin:12px 0;padding:12px;background:#dff0d8;color:#3c763d;border-radius:6px;">Test deleted successfully.</div>
            <?php elseif (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                <div style="margin:12px 0;padding:12px;background:#dff0d8;color:#3c763d;border-radius:6px;">Test updated successfully.</div>
            <?php endif; ?>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Tests</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Test ID, Product ID, or Name">
                        </div>
                        <div class="filter-group">
                            <label>Test Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Passed" <?php echo $status_filter == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                                <option value="Failed" <?php echo $status_filter == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Test Type</label>
                            <select name="type">
                                <option value="">All Types</option>
                                <?php foreach ($test_types as $type): ?>
                                <option value="<?php echo $type['test_type_id']; ?>" <?php echo $type_filter == $type['test_type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['test_type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Tests List (<?php echo count($tests); ?>)</h2>
                <div class="table-responsive table-compact">
                    <?php if (count($tests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th class="col-id">Test ID</th>
                                <th class="col-product">Product ID</th>
                                <th class="col-name">Product Name</th>
                                <th class="col-type">Test Type</th>
                                <th class="col-date">Test Date</th>
                                <th class="col-tester">Tester</th>
                                <th class="col-status">Status</th>
                                <th class="col-actions">Actions</th>
                            </tr>
                        </thead> 
                        
                        <tbody>
                            <?php foreach ($tests as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_name']); ?></td>
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
                                    <div class="action-group">
                                        <a href="view.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-view">View</a>
                                        <a href="edit.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                        <a href="list.php?delete=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-delete confirm-delete" data-msg="Are you sure you want to delete this test?">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No tests found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
