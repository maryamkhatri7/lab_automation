<?php
// admin/logs.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

// Filters
$user_filter = $_GET['user'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$limit = intval($_GET['limit'] ?? 100);

// Build query
$query = "SELECT ul.*, u.username, u.full_name
          FROM user_logs ul
          JOIN users u ON ul.user_id = u.user_id
          WHERE 1=1";

$params = [];
$types = [];

if (!empty($user_filter)) {
    $query .= " AND ul.user_id = ?";
    $params[] = $user_filter;
    $types[] = 'i';
}

if (!empty($action_filter)) {
    $query .= " AND ul.action LIKE ?";
    $params[] = "%$action_filter%";
    $types[] = 's';
}

if (!empty($date_from)) {
    $query .= " AND DATE(ul.timestamp) >= ?";
    $params[] = $date_from;
    $types[] = 's';
}

if (!empty($date_to)) {
    $query .= " AND DATE(ul.timestamp) <= ?";
    $params[] = $date_to;
    $types[] = 's';
}

$query .= " ORDER BY ul.timestamp DESC LIMIT ?";
$params[] = $limit;
$types[] = 'i';

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param(implode('', $types), ...$params);
}
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get users for filter
$users = $conn->query("SELECT user_id, username, full_name FROM users ORDER BY username")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs - Admin Panel</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">
<style>
    /* ================= GLOBAL STYLES ================= */
body {
    font-family: "Segoe UI", sans-serif;
    color: var(--text, #1e293b);
    background: #f9f9f9;
    margin: 0;
    padding: 0;
}

.main-content {
    padding: 20px;
    width: calc(100% - 250px); /* assuming sidebar width is 250px */
    box-sizing: border-box;
    min-height: 100vh;
}

.top-bar h1 {
    font-size: 1.5rem;
    margin-bottom: 20px;
}
/* ================= FILTERS HEIGHT FIX ================= */
.filters .filter-group input,
.filters .filter-group select {
    padding: 4px 8px;       /* reduce padding to make inputs shorter */
    font-size: 0.85rem;
    height: 32px;           /* fixed height */
    line-height: 1.2;
    border-radius: 5px;
    border: 1px solid #ccc;
    box-sizing: border-box;
    margin-bottom: 0;       /* remove extra vertical margin */
}

.filters .filter-group button.btn {
    height: 32px;           /* match input height */
    padding: 0 12px;        /* horizontal padding only */
    font-size: 0.85rem;
    line-height: 32px;      /* vertically center text */
    border-radius: 5px;
}

/* Align button with inputs */
.filters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center; /* vertically center button with inputs */
}

/* Responsive adjustments */
@media (max-width: 991px) {
    .filters {
        flex-direction: column;
        align-items: stretch;
    }
    .filters .filter-group button.btn {
        width: 120px;
    }
}

@media (max-width: 576px) {
    .filters .filter-group input,
    .filters .filter-group select,
    .filters .filter-group button.btn {
        height: 28px;
        font-size: 0.8rem;
        line-height: 28px;
    }
}

/* ================= TABLE ================= */
.table-responsive {
    overflow-x: auto;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    padding: 10px;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.85rem;
    min-width: 600px;
}

thead {
    background: var(--accent, #6dbcf6);
    color: var(--navy, #0b1c2d);
    text-align: left;
}

thead th {
    padding: 10px 12px;
    font-weight: 600;
}

tbody td {
    padding: 10px 12px;
    border-top: 1px solid #eee;
}

tbody tr:nth-child(even) {
    background: #f5f7fa;
}

tbody tr:hover {
    background: rgba(109,188,246,0.1);
}

/* ================= HEADINGS ================= */
h2 {
    font-size: 1.2rem;
    margin-bottom: 12px;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 991px) {
    .main-content {
        width: 100%;
        padding: 16px;
    }
    .filters {
        flex-direction: column;
    }
    .filter-group {
        flex: 1 1 100%;
    }
    table {
        font-size: 0.8rem;
    }
    thead th, tbody td {
        padding: 8px 10px;
    }
}

@media (max-width: 767px) {
    .top-bar h1 {
        font-size: 1.3rem;
    }
    .filters {
        gap: 8px;
    }
    .filter-group input,
    .filter-group select,
    .filter-group button {
        font-size: 0.8rem;
        padding: 6px 8px;
    }
}

@media (max-width: 576px) {
    .filters {
        gap: 6px;
    }
    table {
        min-width: 100%;
        font-size: 0.78rem;
    }
    thead th, tbody td {
        padding: 6px 8px;
    }
}

/* ================= EMPTY STATE ================= */
.table-responsive p {
    text-align: center;
    padding: 40px;
    color: #7f8c8d;
    font-size: 0.9rem;
}
/* ================= SIDEBAR SCROLL FIX ================= */

/* Sidebar layout */
.sidebar{
    width:260px;
    background:var(--navy);
    color:#fff;
    height:100vh;
    top:0;
    flex-shrink:0;

    display:flex;
    flex-direction:column;
}

/* Header stays fixed */
.sidebar-header{
    padding:0 20px 20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    flex-shrink:0;
}

/* MENU SCROLL AREA */
.sidebar-menu{
    list-style:none;
    padding:20px 0;

    flex:1;
    overflow-y:auto;
    overflow-x:hidden;

    scrollbar-width: thin; /* Firefox */
    scrollbar-color: var(--accent) rgba(255,255,255,0.1);
}

/* ===== Cute scrollbar (Chrome / Edge / Safari) ===== */
.sidebar-menu::-webkit-scrollbar{
    width:6px;
}

.sidebar-menu::-webkit-scrollbar-track{
    background: rgba(255,255,255,0.05);
    border-radius:10px;
}

.sidebar-menu::-webkit-scrollbar-thumb{
    background: var(--accent);
    border-radius:10px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover{
    background:#58a0e3;
}

/* Menu links */
.sidebar-menu a{
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:#ecf0f1;
    border-left:3px solid transparent;
    border-radius:6px;
    transition:0.3s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background: rgba(255,255,255,0.05);
    border-left:3px solid var(--accent);
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
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products/list.php">Products</a></li>
                <li><a href="products/add.php">Add Product</a></li>
                <li><a href="tests/list.php">Tests</a></li>
                <li><a href="users/list.php">Users</a></li>
                <li><a href="users/add.php">Add User</a></li>
                <li><a href="cpri/list.php">CPRI Submissions</a></li>
                <li><a href="remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="reports/index.php">Reports</a></li>
                <li><a href="search.php">Advanced Search</a></li>
                <li><a href="config.php">System Config</a></li>
                <li><a href="logs.php" class="active">Activity Logs</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Activity Logs</h1>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Filter Logs</h2>
                <form method="GET" action="">
                    <div class="filters">
                        <div class="filter-group">
                            <label>User</label>
                            <select name="user">
                                <option value="">All Users</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name'] . ' (' . $user['username'] . ')'); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Action</label>
                            <input type="text" name="action" value="<?php echo htmlspecialchars($action_filter); ?>" placeholder="Search action...">
                        </div>
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="filter-group">
                            <label>Limit</label>
                            <input type="number" name="limit" value="<?php echo $limit; ?>" min="10" max="1000" step="10">
                        </div>
                        <div class="filter-group" style="display: flex; align-items: flex-end;">
                            <button type="submit" class="btn btn-primary" style="width: 100%;">Filter</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Activity Logs (<?php echo count($logs); ?>)</h2>
                <div class="table-responsive">
                    <?php if (count($logs) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Action</th>
                                <th>Table</th>
                                <th>Record ID</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i:s', strtotime($log['timestamp'])); ?></td>
                                <td><?php echo htmlspecialchars($log['full_name'] . ' (' . $log['username'] . ')'); ?></td>
                                <td><?php echo htmlspecialchars($log['action']); ?></td>
                                <td><?php echo htmlspecialchars($log['table_affected'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['record_id'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address'] ?? 'N/A'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No logs found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
