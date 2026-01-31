<?php
// admin/products/list.php
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

// Build query
$query = "SELECT p.*, pt.product_type_name, u.full_name as created_by_name
          FROM products p
          JOIN product_types pt ON p.product_type_id = pt.product_type_id
          JOIN users u ON p.created_by = u.user_id
          WHERE 1=1";

$params = [];
$types = [];

if (!empty($status_filter)) {
    $query .= " AND p.current_status = ?";
    $params[] = $status_filter;
    $types[] = 's';
}

if (!empty($type_filter)) {
    $query .= " AND p.product_type_id = ?";
    $params[] = $type_filter;
    $types[] = 'i';
}

if (!empty($search_term)) {
    $query .= " AND (p.product_id LIKE ? OR p.product_name LIKE ? OR p.batch_number LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types[] = 's';
    $types[] = 's';
    $types[] = 's';
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get product types for filter
$product_types = $conn->query("SELECT * FROM product_types WHERE is_active = 1")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Admin Panel</title>
<link rel="stylesheet" href="../../supervisor/public/style.css">
   
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
                <li><a href="list.php" class="active">Products</a></li>
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
                <li><a href="../../logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Products Management</h1>
                <div class="user-info">
                    <a href="add.php" class="btn btn-success">Add New Product</a>
                </div>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="margin:12px 0;padding:12px;background:#dff0d8;color:#3c763d;border-radius:6px;">Product deleted successfully.</div>
            <?php endif; ?>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Products</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Product ID, Name, or Batch">
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="In Testing" <?php echo $status_filter == 'In Testing' ? 'selected' : ''; ?>>In Testing</option>
                                <option value="Passed" <?php echo $status_filter == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                                <option value="Failed" <?php echo $status_filter == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="Sent to CPRI" <?php echo $status_filter == 'Sent to CPRI' ? 'selected' : ''; ?>>Sent to CPRI</option>
                                <option value="Re-Manufacturing" <?php echo $status_filter == 'Re-Manufacturing' ? 'selected' : ''; ?>>Re-Manufacturing</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Product Type</label>
                            <select name="type">
                                <option value="">All Types</option>
                                <?php foreach ($product_types as $type): ?>
                                <option value="<?php echo $type['product_type_id']; ?>" <?php echo $type_filter == $type['product_type_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['product_type_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Products List (<?php echo count($products); ?>)</h2>
                <div class="table-responsive">
                    <?php if (count($products) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Type</th>
                                <th>Batch Number</th>
                                <th>Manufacturing Date</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_type_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['batch_number'] ?? 'N/A'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($product['manufacturing_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-pending';
                                    if ($product['current_status'] == 'Passed') $badge_class = 'badge-success';
                                    elseif ($product['current_status'] == 'Failed') $badge_class = 'badge-danger';
                                    elseif ($product['current_status'] == 'Sent to CPRI') $badge_class = 'badge-info';
                                    elseif ($product['current_status'] == 'Re-Manufacturing') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($product['created_by_name']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-view">View</a>
                                        <a href="edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                        <a href="list.php?delete=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-delete confirm-delete" data-msg="Are you sure you want to delete this product?">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No products found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
