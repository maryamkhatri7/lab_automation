<?php
// admin/remanufacturing/list.php
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
$query = "SELECT r.*, p.product_name, u.full_name as created_by_name
          FROM remanufacturing_records r
          JOIN products p ON r.product_id = p.product_id
          JOIN users u ON r.created_by = u.user_id
          WHERE 1=1";

$params = [];
$types = [];

if (!empty($status_filter)) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types[] = 's';
}

if (!empty($search_term)) {
    $query .= " AND (r.product_id LIKE ? OR p.product_name LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types[] = 's';
    $types[] = 's';
}

$query .= " ORDER BY r.remanufacturing_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle delete action (mark as Deleted)
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    $del_q = "UPDATE remanufacturing_records SET status = 'Deleted' WHERE remanufacturing_id = ?";
    $stmt_del = $conn->prepare($del_q);
    $stmt_del->bind_param("i", $delete_id);
    $stmt_del->execute();
    logActivity($conn, $_SESSION['user_id'], "Deleted remanufacturing record", "remanufacturing_records", $delete_id);
    header("Location: list.php?msg=deleted");
    exit;
}
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-Manufacturing - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">
<style>
/* ================= ROOT VARIABLES ================= */
:root {
    --navy: #0b1c2d;
    --text: #1e293b;
    --accent: #6dbcf6;
    --success: #d4edda;
    --success-text: #155724;
    --info: #d1ecf1;
    --info-text: #0c5460;
    --warning: #fff3cd;
    --warning-text: #856404;
    --danger: #f8d7da;
    --danger-text: #721c24;
    --light: #f1f5f9;
}


/* ================= MAIN CONTENT ================= */
.main-content {
    flex-grow: 1;
    padding: 20px 30px;
    overflow-x: hidden;
}

.top-bar {
    margin-bottom: 20px;
}

/* ================= TABLE ================= */
.table-responsive {
    width: 100%;
    overflow-x: auto;
    border-radius: 8px;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
    background: #fff;
}

th, td {
    padding: 12px 14px;
    font-size: 0.85rem;
    text-align: left;
    vertical-align: middle;
    border-bottom: 1px solid #e5e7eb;
}

th {
    background: var(--light);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

tr:hover td {
    background: rgba(109,188,246,0.08);
}

/* ================= BADGES ================= */
.badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 14px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.badge-success { background: var(--success); color: var(--success-text); }
.badge-info { background: var(--info); color: var(--info-text); }
.badge-warning { background: var(--warning); color: var(--warning-text); }
.badge-danger { background: var(--danger); color: var(--danger-text); }

/* ================= ACTION BUTTONS ================= */
.action-group {
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.btn {
    padding: 6px 12px;
    font-size: 0.75rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.25s ease;
    text-align: center;
}

.btn-view { background: #e0f2fe; color: #0369a1; }
.btn-edit { background: #ecfeff; color: #0f766e; }
.btn-delete { background: #fee2e2; color: #991b1b; }

.btn:hover {
    transform: translateY(-2px);
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media (max-width: 991px) {
    table { min-width: 750px; }
    .filter-group { flex: 1 1 160px; }
}

/* Mobile */
@media (max-width: 576px) {
    .main-content { padding: 14px; }
    .filters { flex-direction: column; gap: 10px; }
    .filter-group { width: 100%; }
    .filter-group input,
    .filter-group select,
    .filter-group button { font-size: 0.8rem; padding: 8px; height: 34px; }
    th, td { font-size: 0.75rem; padding: 10px; }
    .action-group { flex-direction: column; align-items: flex-start; }
    .btn { width: 100%; }
}

/* Super compact for super small devices */
@media (max-width: 400px) {
    .filters { gap: 6px; }
    .filter-group input,
    .filter-group select,
    .filter-group button { font-size: 0.7rem; padding: 6px; height: 28px; }
}/* ================= CLEAN FILTERS ================= */
.filters {
    display: flex;
    flex-wrap: wrap;      /* wrap on small screens */
    gap: 10px;            /* spacing between fields */
    align-items: flex-end; /* align labels + inputs nicely */
}.filter-group {
    display: flex;
    flex-direction: column; /* label above input/select */
    flex-grow: 0;           /* prevent stretching */
    flex-shrink: 0;         /* prevent shrinking too much */
    flex-basis: auto;       /* auto width based on content or container */
    max-width: 230px;            /* natural width */
    min-width: 0;           /* prevent overflow */
    margin: 0;
        height: 60px;          /* let it size naturally */

}

.filter-group label {
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 2px; /* smaller gap to input */
}

.filter-group input,
.filter-group select {
    max-width: 200px;           /* fill parent width */
    padding: 6px 10px;
    font-size: 0.85rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    box-sizing: border-box;
}


.filter-group button {
    padding: 7px 12px;
    font-size: 0.85rem;
    border-radius: 6px;
    cursor: pointer;
    width: 100%;
    max-width: 150px; /* optional desktop cap */
    margin-top :20px!important;
}

/* ================= MOBILE ================= */
@media(max-width: 576px){
    .filters {
        flex-direction: column;
        gap: 8px;
    }

    .filter-group {
        width: 200px; 
        flex: 1 1 100%;
        min-width: unset;
    }

    .filter-group button {
        width: 120px;
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
                <li><a href="../users/list.php">Users</a></li>
                <li><a href="../users/add.php">Add User</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="list.php" class="active">Re-Manufacturing</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../search.php">Advanced Search</a></li>
                <li><a href="../config.php">System Config</a></li>
                <li><a href="../logs.php">Activity Logs</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Re-Manufacturing Records</h1>
            </div>

            <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
                <div style="margin:12px 0;padding:12px;background:#dff0d8;color:#3c763d;border-radius:6px;">Record deleted successfully.</div>
            <?php endif; ?>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Records</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>Search</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Product ID or Name">
                        </div>
                        <div class="filter-group">
                            <label>Status</label>
                            <select name="status">
                                <option value="">All Statuses</option>
                                <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Records List (<?php echo count($records); ?>)</h2>
                <div class="table-responsive">
                    <?php if (count($records) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Record ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $record): ?>
                            
                            <tr>
                                <td><?php echo $record['remanufacturing_id']; ?></td>
                                <td><?php echo htmlspecialchars($record['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($record['product_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($record['remanufacturing_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-warning';
                                    if ($record['status'] == 'Completed') $badge_class = 'badge-success';
                                    elseif ($record['status'] == 'In Progress') $badge_class = 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $record['status']; ?></span>
                                </td>
                                <td><?php echo $record['cost'] ? '₹' . number_format($record['cost'], 2) : 'N/A'; ?></td>
                                <td><?php echo htmlspecialchars($record['created_by_name']); ?></td>
                                <td>
                                    <div class="action-group">
                                        <a href="view.php?id=<?php echo $record['remanufacturing_id']; ?>" class="btn btn-view">View</a>
                                        <a href="edit.php?id=<?php echo $record['remanufacturing_id']; ?>" class="btn btn-edit">Edit</a>
                                        <a href="list.php?delete=<?php echo $record['remanufacturing_id']; ?>" class="btn btn-delete confirm-delete" data-msg="Are you sure you want to delete this record?">Delete</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No remanufacturing records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../assets/js/confirm.js"></script>
</body>
</html>
