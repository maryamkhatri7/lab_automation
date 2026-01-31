<?php
// admin/reports/index.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Product Statistics
$product_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN current_status = 'In Testing' THEN 1 ELSE 0 END) as in_testing,
    SUM(CASE WHEN current_status = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN current_status = 'Failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN current_status = 'Sent to CPRI' THEN 1 ELSE 0 END) as cpri,
    SUM(CASE WHEN current_status = 'Re-Manufacturing' THEN 1 ELSE 0 END) as remanufacturing
    FROM products
    WHERE DATE(created_at) BETWEEN ? AND ?";
$stmt = $conn->prepare($product_stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$product_stats = $stmt->get_result()->fetch_assoc();

// Test Statistics
$test_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN test_status = 'Passed' THEN 1 ELSE 0 END) as passed,
    SUM(CASE WHEN test_status = 'Failed' THEN 1 ELSE 0 END) as failed,
    SUM(CASE WHEN test_status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END) as pending
    FROM tests
    WHERE DATE(test_date) BETWEEN ? AND ?";
$stmt = $conn->prepare($test_stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$test_stats = $stmt->get_result()->fetch_assoc();

// Products by Type
$products_by_type_query = "SELECT pt.product_type_name, COUNT(p.product_id) as count
                          FROM product_types pt
                          LEFT JOIN products p ON pt.product_type_id = p.product_type_id 
                          AND DATE(p.created_at) BETWEEN ? AND ?
                          GROUP BY pt.product_type_id
                          ORDER BY count DESC";
$stmt = $conn->prepare($products_by_type_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$products_by_type = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tests by Type
$tests_by_type_query = "SELECT tt.test_type_name, COUNT(t.test_id) as count
                       FROM test_types tt
                       LEFT JOIN tests t ON tt.test_type_id = t.test_type_id 
                       AND DATE(t.test_date) BETWEEN ? AND ?
                       GROUP BY tt.test_type_id
                       ORDER BY count DESC";
$stmt = $conn->prepare($tests_by_type_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$tests_by_type = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// CPRI Statistics
$cpri_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) as rejected,
    SUM(CASE WHEN approval_status IN ('Submitted', 'Under Review') THEN 1 ELSE 0 END) as pending
    FROM cpri_submissions
    WHERE DATE(submission_date) BETWEEN ? AND ?";
$stmt = $conn->prepare($cpri_stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$cpri_stats = $stmt->get_result()->fetch_assoc();

// Remanufacturing Statistics
$remanufacturing_stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END) as pending,
    SUM(COALESCE(cost, 0)) as total_cost
    FROM remanufacturing_records
    WHERE DATE(remanufacturing_date) BETWEEN ? AND ?";
$stmt = $conn->prepare($remanufacturing_stats_query);
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$remanufacturing_stats = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">
<style>
    /* ================= REPORTS PAGE ================= */

/* Date filter */
.date-filter{
    display:flex;
    gap:16px;
    flex-wrap:wrap;
    align-items:flex-end;
}

.date-filter .form-group{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.date-filter label{
    font-size:0.8rem;
    font-weight:600;
    color:var(--text);
}

.date-filter input[type="date"]{
    padding:8px 12px;
    font-size:0.85rem;
    border-radius:8px;
    border:1px solid #d1d5db;
}

.date-filter button{
    background:var(--accent);
    color:var(--navy);
    border:none;
    padding:10px 20px;
    font-size:0.85rem;
    font-weight:600;
    border-radius:10px;
    cursor:pointer;
    transition:.3s;
}

.date-filter button:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(109,188,246,.35);
}

/* ================= STATS GRID ================= */

.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(180px,1fr));
    gap:16px;
}

/* Stat card */
.stat-card{
    background:#fff;
    border-radius:14px;
    padding:18px;
    text-align:center;
    box-shadow:0 4px 12px rgba(0,0,0,.08);
    transition:0.3s;
}

.stat-card:hover{
    transform:translateY(-4px);
}

.stat-card h3{
    font-size:0.8rem;
    font-weight:600;
    color:#64748b;
    margin-bottom:6px;
}

.stat-value{
    font-size:1.5rem;
    font-weight:700;
    color:var(--navy);
}

/* ================= TABLES ================= */

.content-section table{
    width:100%;
    border-collapse:collapse;
    min-width:420px;
}

.content-section th,
.content-section td{
    padding:12px 14px;
    font-size:0.85rem;
    text-align:left;
}

.content-section th{
    background:#f1f5f9;
    font-size:0.75rem;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.content-section tr:hover td{
    background:rgba(109,188,246,.08);
}

/* Horizontal scroll safety */
.content-section{
    overflow-x:auto;
}

/* ================= HEADINGS ================= */

.content-section h2{
    font-size:1.05rem;
    font-weight:600;
    color:var(--text);
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media(max-width:991px){
    .date-filter{
        gap:12px;
    }

    .date-filter input,
    .date-filter button{
        width:100%;
    }

    .stat-value{
        font-size:1.3rem;
    }
}

/* Mobile */
@media(max-width:576px){
    .top-bar h1{
        font-size:1.1rem;
    }

    .content-section{
        padding:14px;
    }

    .content-section h2{
        font-size:0.95rem;
    }

    .date-filter label{
        font-size:0.75rem;
    }

    .date-filter input{
        font-size:0.8rem;
        padding:7px 10px;
    }

    .date-filter button{
        font-size:0.8rem;
        padding:9px;
    }

    .stat-card{
        padding:14px;
    }

    .stat-card h3{
        font-size:0.75rem;
    }

    .stat-value{
        font-size:1.2rem;
    }

    .content-section th,
    .content-section td{
        font-size:0.75rem;
        padding:10px;
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
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="index.php" class="active">Reports</a></li>
                <li><a href="../search.php">Advanced Search</a></li>
                <li><a href="../config.php">System Config</a></li>
                <li><a href="../logs.php">Activity Logs</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Reports & Analytics</h1>
            </div>

            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Date Range Filter</h2>
                <form method="GET" action="">
                    <div class="date-filter">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Product Statistics -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Product Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo $product_stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>In Testing</h3>
                        <div class="stat-value"><?php echo $product_stats['in_testing']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Passed</h3>
                        <div class="stat-value"><?php echo $product_stats['passed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Failed</h3>
                        <div class="stat-value"><?php echo $product_stats['failed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Sent to CPRI</h3>
                        <div class="stat-value"><?php echo $product_stats['cpri']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Re-Manufacturing</h3>
                        <div class="stat-value"><?php echo $product_stats['remanufacturing']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Test Statistics -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Test Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Tests</h3>
                        <div class="stat-value"><?php echo $test_stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Passed Tests</h3>
                        <div class="stat-value"><?php echo $test_stats['passed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Failed Tests</h3>
                        <div class="stat-value"><?php echo $test_stats['failed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Pending Tests</h3>
                        <div class="stat-value"><?php echo $test_stats['pending']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Products by Type -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Products by Type</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products_by_type as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['product_type_name']); ?></td>
                            <td><?php echo $type['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Tests by Type -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Tests by Type</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Test Type</th>
                            <th>Count</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests_by_type as $type): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($type['test_type_name']); ?></td>
                            <td><?php echo $type['count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- CPRI Statistics -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">CPRI Submissions</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Submissions</h3>
                        <div class="stat-value"><?php echo $cpri_stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Approved</h3>
                        <div class="stat-value"><?php echo $cpri_stats['approved']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Rejected</h3>
                        <div class="stat-value"><?php echo $cpri_stats['rejected']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Pending</h3>
                        <div class="stat-value"><?php echo $cpri_stats['pending']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Remanufacturing Statistics -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px;">Re-Manufacturing Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Records</h3>
                        <div class="stat-value"><?php echo $remanufacturing_stats['total']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Completed</h3>
                        <div class="stat-value"><?php echo $remanufacturing_stats['completed']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Pending</h3>
                        <div class="stat-value"><?php echo $remanufacturing_stats['pending']; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>Total Cost</h3>
                        <div class="stat-value">Rs.<?php echo number_format($remanufacturing_stats['total_cost'], 2); ?></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
