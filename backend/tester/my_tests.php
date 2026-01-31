<?php
// tester/my_tests.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$offset = ($page - 1) * $records_per_page;

// Filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';

// Build query
$where_conditions = ["tester_id = $user_id"];

if ($status_filter) {
    $where_conditions[] = "test_status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($date_filter) {
    $where_conditions[] = "DATE(test_date) = '" . $conn->real_escape_string($date_filter) . "'";
}

$where_clause = implode(" AND ", $where_conditions);

// Get total count
$count_query = "SELECT COUNT(*) as total FROM tests WHERE $where_clause";
$total_records = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get tests
$query = "SELECT t.*, p.product_name, tt.test_type_name 
          FROM tests t
          JOIN products p ON t.product_id = p.product_id
          JOIN test_types tt ON t.test_type_id = tt.test_type_id
          WHERE $where_clause
          ORDER BY t.test_date DESC, t.test_time DESC
          LIMIT $records_per_page OFFSET $offset";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tests - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
    /* ================= PAGE HEADER ================= */
.page-header{
    display:flex;
    flex-wrap:wrap;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
}
.page-header h1{
    font-size:22px;
    margin-bottom:8px;
    color:var(--text);
}
.header-actions{
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.btn-back{
    background:#64748b;
    color:#fff;
    padding:8px 14px;
    border-radius:8px;
    font-size:.9rem;
    transition: all var(--transition);
}
.btn-back:hover{
    background:#4b5563;
    transform:translateY(-2px);
}

/* ================= FILTERS FORM ================= */
.filters-section{
    margin-bottom:20px;
    background:#fff;
    padding:16px;
    border-radius:var(--border-radius);
    box-shadow:0 2px 8px var(--shadow);
}
.filters-form{
    display:flex;
    flex-wrap:wrap;
    gap:16px;
    align-items:flex-end;
}
.filter-group{
    display:flex;
    flex-direction:column;
    flex:1 1 200px;
}
.filter-group label{
    font-size:.85rem;
    margin-bottom:4px;
    color:var(--text);
}
.filters-form input[type="date"],
.filters-form select{
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:.9rem;
    width:100%;
}
.filters-form button{
    padding:8px 16px;
    border:none;
    border-radius:8px;
    background:var(--accent);
    color:var(--navy);
    font-size:.9rem;
    cursor:pointer;
    transition: all var(--transition);
}
.filters-form button:hover{
    background:#58a0e3;
}

/* ================= STATS ROW ================= */
.stats-row{
    display:flex;
    flex-wrap:wrap;
    gap:16px;
    margin-bottom:20px;
}
.stat-item{
    background:#fff;
    padding:16px;
    border-radius:var(--border-radius);
    flex:1 1 150px;
    box-shadow:0 2px 8px var(--shadow);
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
}
.stat-label{
    font-size:.85rem;
    color:#64748b;
    margin-bottom:6px;
}
.stat-value{
    font-size:1.5rem;
    font-weight:700;
    color:var(--accent);
}

/* ================= TABLE ================= */
.table-responsive{
    width:100%;
    overflow-x:auto;
}
table{
    width:100%;
    min-width:720px;
    border-collapse:collapse;
    background:#fff;
    border-radius:var(--border-radius);
    box-shadow:0 2px 8px var(--shadow);
}
th,td{
    padding:12px;
    text-align:left;
    font-size:.9rem;
}
th{
    background:#f1f5f9;
    font-size:.8rem;
    text-transform:uppercase;
}
tr:hover td{
    background:rgba(109,188,246,.08);
}
.action-btns{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
.btn-sm{
    padding:6px 10px;
    border-radius:6px;
    font-size:.75rem;
    font-weight:600;
    transition: all var(--transition);
}
.btn-view{
    background:var(--accent);
    color:var(--navy);
}
.btn-view:hover{
    background:#58a0e3;
}
.btn-edit{
    background:#fbbf24;
    color:#1e293b;
}
.btn-edit:hover{
    background:#f59e0b;
}

/* ================= PAGINATION ================= */
.pagination{
    margin-top:16px;
    display:flex;
    flex-wrap:wrap;
    gap:6px;
    justify-content:center;
    align-items:center;
}
.pagination a, .pagination span{
    padding:6px 12px;
    border-radius:6px;
    background:#e2e8f0;
    color:var(--text);
    font-size:.85rem;
    font-weight:600;
    transition: all var(--transition);
}
.pagination a:hover{
    background:var(--accent);
    color:var(--navy);
}
.pagination span.active{
    background:var(--accent);
    color:var(--navy);
}

/* ================= EMPTY STATE ================= */
.empty-state{
    text-align:center;
    padding:40px 20px;
    color:#64748b;
}
.empty-state h3{
    font-size:1.2rem;
    margin-bottom:12px;
}
.empty-state p{
    font-size:.95rem;
    margin-bottom:20px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:991px){
    table{min-width:650px;}
    .filters-form{gap:12px;}
}
@media(max-width:767px){
    .filters-form{flex-direction:column; align-items:flex-start;}
    .stats-row{flex-direction:column;}
    .header-actions{flex-direction:column; gap:8px;}
    .table-responsive table{min-width:600px;}
}
.filters-form {
    display: flex;
    flex-wrap: nowrap; /* prevent vertical wrap */
    gap:16px;
    align-items:flex-start; /* align label+input to bottom */
}

.filter-group {
    display: flex;
    flex-direction: column; /* label above input */
    flex: 1 1 auto; /* flexible width */
    min-width:150px; /* prevent too narrow inputs */
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
                <li><a href="my_tests.php" class="active">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>My Tests</h1>
            <div class="header-actions">
                <a href="index.php" class="btn btn-back">← Dashboard</a>
                <a href="new_test.php" class="btn btn-primary">+ New Test</a>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-section">
            <form method="GET" action="" class="filters-form">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Passed" <?php echo $status_filter == 'Passed' ? 'selected' : ''; ?>>Passed</option>
                        <option value="Failed" <?php echo $status_filter == 'Failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>Date</label>
                    <input type="date" name="date" value="<?php echo $date_filter; ?>">
                </div>

                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="my_tests.php" class="btn btn-back">Clear</a>
            </form>
        </div>

        <!-- Tests List -->
        <div class="content-section">
            <div class="stats-row">
                <div class="stat-item">
                    <div class="stat-label">Total Tests</div>
                    <div class="stat-value"><?php echo $total_records; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Showing Page</div>
                    <div class="stat-value"><?php echo $page; ?> / <?php echo max(1, $total_pages); ?></div>
                </div>
            </div>

            <div class="table-responsive">
                <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Test Type</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Criteria Met</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['test_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['test_type_name']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['test_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['test_time'])); ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-pending';
                                if ($row['test_status'] == 'Passed') $badge_class = 'badge-success';
                                elseif ($row['test_status'] == 'Failed') $badge_class = 'badge-danger';
                                elseif ($row['test_status'] == 'In Progress') $badge_class = 'badge-progress';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $row['test_status']; ?></span>
                            </td>
                            <td><?php echo $row['test_criteria_met'] ? '✓' : '✗'; ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="view_test.php?id=<?php echo $row['test_id']; ?>" class="btn btn-sm btn-view">View</a>
                                    <a href="update_test.php?id=<?php echo $row['test_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1<?php echo $status_filter ? '&status='.$status_filter : ''; ?><?php echo $date_filter ? '&date='.$date_filter : ''; ?>">First</a>
                        <a href="?page=<?php echo $page-1; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?><?php echo $date_filter ? '&date='.$date_filter : ''; ?>">Previous</a>
                    <?php endif; ?>

                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php if ($i == $page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?><?php echo $date_filter ? '&date='.$date_filter : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?><?php echo $date_filter ? '&date='.$date_filter : ''; ?>">Next</a>
                        <a href="?page=<?php echo $total_pages; ?><?php echo $status_filter ? '&status='.$status_filter : ''; ?><?php echo $date_filter ? '&date='.$date_filter : ''; ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <?php else: ?>
                <div class="empty-state">
                    <h3>No tests found</h3>
                    <p>You haven't conducted any tests yet or no tests match your filters.</p>
                    <a href="new_test.php" class="btn btn-primary" style="margin-top: 20px;">Create First Test</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        </main>
    </div>
    <script src="../admin/assets/js/confirm.js"></script>
</body>
</html>