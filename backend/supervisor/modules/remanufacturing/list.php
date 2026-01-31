<?php
// supervisor/modules/remanufacturing/list.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

$query = "SELECT r.remanufacturing_id, r.product_id, r.remanufacturing_date, r.status,
                 r.cost, r.reason, r.completed_date, r.new_product_id,
                 p.product_name, u.full_name AS created_by_name
          FROM remanufacturing_records r
          JOIN products p ON r.product_id = p.product_id
          JOIN users u ON r.created_by = u.user_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND r.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $query .= " AND (r.product_id LIKE ? OR p.product_name LIKE ?)";
    $like = "%$search_term%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$query .= " ORDER BY r.remanufacturing_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$records = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Re-Manufacturing - Supervisor</title>
    <link rel="stylesheet" href="../../public/style.css">

    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background:#f4f6f9; color:#333; }
        
        .content-section { background:white; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.04); }
        .filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px; align-items:center; }
        .filters input, .filters select { padding:10px; border:1px solid #ddd; border-radius:6px; }
        .filter-pill { display:inline-block; padding:8px 12px; background:#e9f5ff; color:#0b66d0; border-radius:12px; margin-right:8px; font-weight:600; }
        .filter-pill.active { background:#0b66d0; color:#fff; }
        .btn { padding:8px 14px; background:#3498db; color:#fff; border-radius:6px; text-decoration:none; display:inline-block; }
        .table-responsive { overflow-x:auto; }
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:12px 10px; border-bottom:1px solid #ecf0f1; text-align:left; vertical-align:middle; }
        th { background:#fff; border-bottom:2px solid #f0f2f5; text-transform:uppercase; font-size:13px; }
        .badge { padding:6px 10px; border-radius:12px; font-size:12px; font-weight:600; white-space:nowrap; }
        .badge-pending { background:#fff3cd; color:#856404; }
        .badge-info { background:#cce5ff; color:#004085; }
        .badge-success { background:#d4edda; color:#155724; }
        @media (max-width:768px){ .top-bar { flex-direction:column; gap:10px } }
        /* ===== FILTER INPUTS RESPONSIVE ===== */
@media (max-width: 767px) {
    .filters {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filters input[type="text"],
    .filters select,
    .filters button {
        font-size: 0.75rem; /* smaller text */
        padding: 6px 8px;   /* slightly smaller padding */
    }

    .filters button {
        width: 100px; /* button full width on mobile */
    }
}

    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Supervisor Panel</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../../index.php">Dashboard</a></li>
                <li><a href="../products/list.php">Products</a></li>
                <li><a href="../products/tests.php">Tests</a></li>
                <li><a href="../products/test_approval.php">Test Approval</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="list.php" class="active">Re-Manufacturing</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Re-Manufacturing</h1>
                <div class="user-info">Welcome, <?php echo htmlspecialchars($user_name); ?> <a href="../../../logout.php" class="btn" style="margin-left:12px;background:#e74c3c;">Logout</a></div>
            </div>

            <div class="content-section">
                <form method="get" class="filters">
                    <input type="text" name="search" placeholder="Search Product" value="<?php echo htmlspecialchars($search_term); ?>">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <?php
                        $statuses = ['Pending','In Progress','Completed'];
                        foreach ($statuses as $s) {
                            $sel = $status_filter === $s ? 'selected' : '';
                            echo "<option value=\"$s\" $sel>$s</option>";
                        }
                        ?>
                    </select>
                    <button class="btn" type="submit">Filter</button>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Cost</th>
                                <th>Reason</th>
                                <th>Created By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$records): ?>
                                <tr><td colspan="7" style="text-align:center;color:#777;padding:20px;">No records found</td></tr>
                            <?php else: ?>
                                <?php foreach ($records as $row): ?>
                                    <?php
                                    $badge = 'badge-pending';
                                    if ($row['status'] === 'Completed') $badge = 'badge-success';
                                    elseif ($row['status'] === 'In Progress') $badge = 'badge-info';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['remanufacturing_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product_id'] . ' - ' . $row['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['remanufacturing_date']); ?></td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><?php echo $row['cost'] ? '₹' . number_format($row['cost'], 2) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($row['reason']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_by_name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
    <script src="../../../admin/assets/js/confirm.js"></script>
</body>
</html>
