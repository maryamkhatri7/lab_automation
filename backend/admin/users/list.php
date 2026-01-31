<?php
// admin/users/list.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

// Handle delete
if (isset($_GET['delete']) && $_GET['delete'] != $_SESSION['user_id']) {
    $delete_id = intval($_GET['delete']);
    $delete_query = "UPDATE users SET is_active = 0 WHERE user_id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], "Deleted user", "users", $delete_id);
        header("Location: list.php?msg=deleted");
        exit();
    }
}

// Filters
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

// Build query
$query = "SELECT * FROM users WHERE 1=1";

$params = [];
$types = [];

if ($status_filter !== '') {
    $query .= " AND is_active = ?";
    $params[] = $status_filter == 'active' ? 1 : 0;
    $types[] = 'i';
} else {
    $query .= " AND is_active = 1";
}

if (!empty($type_filter)) {
    $query .= " AND user_type = ?";
    $params[] = $type_filter;
    $types[] = 's';
}

if (!empty($search_term)) {
    $query .= " AND (username LIKE ? OR full_name LIKE ? OR email LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types[] = 's';
    $types[] = 's';
    $types[] = 's';
}

$query .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">
<style>
    /* ================= USERS LIST SPECIFIC ================= */

/* Filters */
.filters {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end; /* align button with inputs */
    margin-bottom: 16px;
}

.filter-group {
    display: flex;
    flex-direction: column;
    flex: 1 1 200px;   /* flexible width, min 200px */
    min-width: 0;
}

.filter-group label {
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: var(--text);
}

.filter-group input,
.filter-group select {
    width: 100%;
    padding: 8px 12px;
    border-radius: 8px;
    border: 1px solid #d1d5db;
    font-size: 0.85rem;
    box-sizing: border-box;
}

/* Filter button */
.filter-group button {
    background: var(--accent);
    color: var(--navy);
    border: none;
    border-radius: 8px;
    padding: 8px 14px;
    font-weight: 600;
    font-size: 0.85rem;
    cursor: pointer;
    transition: 0.3s;
    margin-top: 0;
}

.filter-group button:hover {
    background: #58a0e3;
}

/* ================= TABLE ================= */
.table-responsive {
    overflow-x: auto;
    margin-top: 16px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 700px; /* tablet safety */
}

table th, table td {
    padding: 10px 12px;
    font-size: 0.85rem;
    text-align: left;
    vertical-align: middle;
}

table th {
    background: #f1f5f9;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.03em;
}

table tr:hover td {
    background: rgba(109, 188, 246, 0.08);
}

/* Badges */
.badge {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 14px;
    font-size: 0.75rem;
    font-weight: 600;
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

.badge-pending {
    background: #e2e8f0;
    color: #475569;
}

/* Action buttons */
.action-btns {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
}

.action-btns a {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    text-align: center;
    transition: 0.3s;
}

.action-btns a.btn-primary {
    background: var(--accent);
    color: var(--navy);
}

.action-btns a.btn-primary:hover {
    background: #58a0e3;
}

.action-btns a.btn-danger {
    background: #e53935;
    color: #fff;
}

.action-btns a.btn-danger:hover {
    background: #e53935;
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media (max-width: 991px) {
    .filters {
        flex-direction: column;
        gap: 10px;
    }

    .filter-group {
        flex: 1 1 100%;
    }

    table {
        min-width: 600px;
    }

    .action-btns {
        flex-direction: column;
        gap: 6px;
    }
}

/* Mobile */
@media (max-width: 576px) {
    .filters input,
    .filters select,
    .filters button {
        font-size: 0.8rem;
        padding: 6px 10px;
    }

    .filters button {
        width: 100%;
        max-width: none;
    }

    table th,
    table td {
        font-size: 0.75rem;
        padding: 8px 10px;
    }

    .badge {
        font-size: 0.7rem;
        padding: 4px 8px;
    }

    .action-btns a {
        width: 100%;
        font-size: 0.7rem;
        padding: 6px 10px;
    }

    table {
        min-width: 500px;
    }
}

/* Extra small */
@media (max-width: 400px) {
    .filters input,
    .filters select,
    .filters button {
        font-size: 0.7rem;
        padding: 5px 8px;
    }

    table th,
    table td {
        font-size: 0.7rem;
        padding: 6px 8px;
    }

    .badge {
        font-size: 0.65rem;
        padding: 3px 6px;
    }

    .action-btns a {
        font-size: 0.65rem;
        padding: 5px 8px;
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
                <li><a href="../products/list.php">Products</a></li>
                <li><a href="../products/add.php">Add Product</a></li>
                <li><a href="../tests/list.php">Tests</a></li>
                <li><a href="list.php" class="active">Users</a></li>
                <li><a href="add.php">Add User</a></li>
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
                <h1>Users Management</h1>
                <a href="add.php" class="btn btn-success">Add New User</a>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
            <div class="content-section">
                <div class="alert alert-success">User deleted successfully!</div>
            </div>
            <?php endif; ?>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Users</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Username, Name, or Email">
                        </div>
                        <div class="filter-group">
                            <label>User Type</label>
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="Admin" <?php echo $type_filter == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Tester" <?php echo $type_filter == 'Tester' ? 'selected' : ''; ?>>Tester</option>
                                <option value="Supervisor" <?php echo $type_filter == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Users List (<?php echo count($users); ?>)</h2>
                <div class="table-responsive">
                    <?php if (count($users) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>User Type</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge badge-info"><?php echo $user['user_type']; ?></span>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                    <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                    <span class="badge badge-danger">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="edit.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <a href="list.php?delete=<?php echo $user['user_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           class="confirm-delete" data-msg="Are you sure you want to delete this user?">Delete</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No users found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
