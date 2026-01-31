<?php
// supervisor/modules/cpri/list.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

// Filters
$status_filter = $_GET['status'] ?? '';
$search_term = $_GET['search'] ?? '';

$query = "SELECT c.cpri_id, c.product_id, c.submission_date, c.cpri_reference_number,
                 c.approval_status, p.product_name, u.full_name AS submitted_by_name
          FROM cpri_submissions c
          JOIN products p ON c.product_id = p.product_id
          JOIN users u ON c.submitted_by = u.user_id
          WHERE 1=1";

$params = [];
$types = "";

if (!empty($status_filter)) {
    $query .= " AND c.approval_status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $query .= " AND (c.product_id LIKE ? OR p.product_name LIKE ? OR c.cpri_reference_number LIKE ?)";
    $like = "%$search_term%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "sss";
}

$query .= " ORDER BY c.submission_date DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$submissions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPRI Submissions - Supervisor</title>
    <link rel="stylesheet" href="../../public/style.css">

    <style>
        :root {
    --navy: #0b1c2d;
    --text: #1e293b;
    --accent: #6dbcf6;
    --glass: rgba(255, 255, 255, 0.85);
    --success: #d4edda;
    --success-text: #155724;
    --error: #f8d7da;
    --error-text: #721c24;
    --info: #d1ecf1;
    --info-text: #0c5460;
    --warning: #fff3cd;
    --warning-text: #856404;
    --purple: #f0e6ff;
    --purple-text: #4b2c6f;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Segoe UI', sans-serif;
    background: #f4f6f9;
    color: var(--text);
    overflow-x: hidden;
}
/* 
.logout-btn {
    background: #e74c3c;
    color: white;
    padding: 8px 14px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.logout-btn:hover {
    background: #c0392b;
    transform: translateY(-2px);
} */

/* ===== CONTENT SECTION ===== */
.content-section {
    background: white;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.content-section .btn {
    background: var(--accent);
    color: var(--navy);
    border-radius: 8px;
    padding: 6px 14px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s;
}
.content-section .btn:hover {
    background: #4aa3f0;
    transform: translateY(-2px);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    font-size: 0.95rem;
}
th, td {
    padding: 12px 15px;
    text-align: left;
}
th {
    background: #f1f5f9;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
}
td {
    background: #fff;
}
tr:hover td {
    background: rgba(109, 188, 246, 0.08);
    transition: 0.3s;
}

/* ===== STATUS BADGES ===== */
.status-badge {
    padding: 6px 12px;
    border-radius: 14px;
    font-weight: 600;
    display: inline-block;
    font-size: 0.85rem;
}
.status-passed { background: var(--success); color: var(--success-text); }
.status-failed { background: var(--error); color: var(--error-text); }
.status-testing { background: var(--info); color: var(--info-text); }
.status-cpri { background: var(--warning); color: var(--warning-text); }
.status-re { background: var(--purple); color: var(--purple-text); }

/* ===== SELECT + BUTTON IN TABLE ===== */
table select {
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ddd;
    font-size: 0.9rem;
}
table button {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    background: #f39c12;
    color: white;
    font-size: 0.9rem;
    cursor: pointer;
    transition: all 0.3s;
}
table button:hover {
    background: #d68910;
    transform: translateY(-1px);
}

/* ===== RESPONSIVE ===== */

/* Mobile: <768px */
@media(max-width:767px) {
    .dashboard-container { flex-direction: column; }
    .sidebar { width: 100%; position: relative; height: auto; }
    .main-content { margin-left: 0; padding: 20px; }
    .top-bar { flex-direction: column; gap: 10px; text-align: center; }
    table th, table td { font-size: 0.8rem; padding: 10px; }
    table select, table button { width: 100%; margin-top: 5px; }
    .user-info { flex-direction: column; gap: 5px; }
}

/* Tablet: 768px – 991px */
@media(max-width:991px) and (min-width:768px) {
    .sidebar { width: 220px; }
    .main-content { margin-left: 220px; padding: 25px; }
    table th, table td { font-size: 0.85rem; }
    table select, table button { font-size: 0.85rem; }
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
                <li><a href="list.php" class="active">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>CPRI Submissions</h1>
                <div class="user-info">Welcome, <?php echo htmlspecialchars($user_name); ?> <a href="../../../logout.php" class="logout-btn" style="margin-left:12px;background:#e74c3c;">Logout</a></div>
            </div>

            <div class="content-section">
                <form method="get" class="filters">
                    <input type="text" name="search" placeholder="Search Product / Ref" value="<?php echo htmlspecialchars($search_term); ?>">
                    <select name="status">
                        <option value="">All Statuses</option>
                        <?php
                        $statuses = ['Submitted','Under Review','Approved','Rejected','Resubmission Required'];
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
                                <th>CPRI ID</th>
                                <th>Product</th>
                                <th>Submission Date</th>
                                <th>Reference</th>
                                <th>Status</th>
                                <th>Submitted By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$submissions): ?>
                                <tr><td colspan="6" style="text-align:center;color:#777;padding:20px;">No submissions found</td></tr>
                            <?php else: ?>
                                <?php foreach ($submissions as $row): ?>
                                    <?php
                                    $badge = 'badge-pending';
                                    if ($row['approval_status'] === 'Approved') $badge = 'badge-approved';
                                    elseif ($row['approval_status'] === 'Rejected' || $row['approval_status'] === 'Resubmission Required') $badge = 'badge-rejected';
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['cpri_id']); ?></td>
                                        <td><?php echo htmlspecialchars($row['product_id'] . ' - ' . $row['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['submission_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['cpri_reference_number'] ?? 'N/A'); ?></td>
                                        <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($row['approval_status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($row['submitted_by_name']); ?></td>
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
