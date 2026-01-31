<?php
// admin/cpri/list.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

// Filters
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build query
$query = "SELECT c.*, p.product_name, u.full_name as submitted_by_name
          FROM cpri_submissions c
          JOIN products p ON c.product_id = p.product_id
          JOIN users u ON c.submitted_by = u.user_id
          WHERE 1=1";

$params = [];
$types = [];

if (!empty($status_filter)) {
    $query .= " AND c.approval_status = ?";
    $params[] = $status_filter;
    $types[] = 's';
}

if (!empty($search_term)) {
    $query .= " AND (c.product_id LIKE ? OR p.product_name LIKE ? OR c.cpri_reference_number LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types[] = 's';
    $types[] = 's';
    $types[] = 's';
}

$query .= " ORDER BY c.submission_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle delete action (mark as Deleted)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $del_q = "UPDATE cpri_submissions SET approval_status = 'Deleted' WHERE cpri_id = ?";
    $stmt_del = $conn->prepare($del_q);
    $stmt_del->bind_param("i", $delete_id);
    $stmt_del->execute();
    logActivity($conn, $_SESSION['user_id'], "Deleted CPRI submission", "cpri_submissions", $delete_id);
    header("Location: list.php?msg=deleted");
    exit;
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPRI Submissions - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">
<style>
    .btn-view,.btn-edit,.btn-delete {
margin:2px;
font-size:.69rem!important;
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
                <li><a href="list.php" class="active">CPRI Submissions</a></li>
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
                <h1>CPRI Submissions</h1>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="margin:12px 0;padding:12px;background:#dff0d8;color:#3c763d;border-radius:6px;">CPRI submission deleted successfully.</div>
            <?php endif; ?>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Submissions</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Product ID, Name, or CPRI Reference">
                        </div>
                        <div class="filter-group">
                            <label>Approval Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="Submitted" <?php echo $status_filter == 'Submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="Under Review" <?php echo $status_filter == 'Under Review' ? 'selected' : ''; ?>>Under Review</option>
                                <option value="Approved" <?php echo $status_filter == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $status_filter == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Resubmission Required" <?php echo $status_filter == 'Resubmission Required' ? 'selected' : ''; ?>>Resubmission Required</option>
                            </select>
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Submissions List (<?php echo count($submissions); ?>)</h2>
                <div class="table-responsive">
                    <?php if (count($submissions) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>CPRI ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Submission Date</th>
                                <th>CPRI Reference</th>
                                <th>Status</th>
                                <th>Submitted By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                            
                            <tr>
                                <td><?php echo $sub['cpri_id']; ?></td>
                                <td><?php echo htmlspecialchars($sub['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($sub['product_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($sub['submission_date'])); ?></td>
                                <td><?php echo htmlspecialchars($sub['cpri_reference_number'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-info';
                                    if ($sub['approval_status'] == 'Approved') $badge_class = 'badge-success';
                                    elseif ($sub['approval_status'] == 'Rejected') $badge_class = 'badge-danger';
                                    elseif ($sub['approval_status'] == 'Resubmission Required') $badge_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $sub['approval_status']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($sub['submitted_by_name']); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="view.php?id=<?php echo $sub['cpri_id']; ?>" class="btn btn-view">View</a>
                                        <a href="edit.php?id=<?php echo $sub['cpri_id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="list.php?delete=<?php echo $sub['cpri_id']; ?>" class="btn btn-delete confirm-delete" data-msg="Are you sure you want to delete this CPRI submission?">Delete</a>
                                    </div>
                                </td>"
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No CPRI submissions found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
