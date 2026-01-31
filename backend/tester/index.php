<?php
// tester/index.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a Tester
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Fetch dashboard statistics
$stats = getTesterStats($conn, $user_id);
$recent_tests = getRecentTests($conn, $user_id, 10);
$pending_tests = getPendingTests($conn, $user_id);

// Get counts
$total_tests_query = "SELECT COUNT(*) as count FROM tests WHERE tester_id = ?";
$stmt = $conn->prepare($total_tests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tests = $stmt->get_result()->fetch_assoc()['count'];

$today_tests_query = "SELECT COUNT(*) as count FROM tests WHERE tester_id = ? AND DATE(test_date) = CURDATE()";
$stmt = $conn->prepare($today_tests_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$today_tests = $stmt->get_result()->fetch_assoc()['count'];

$passed_query = "SELECT COUNT(*) as count FROM tests WHERE tester_id = ? AND test_status = 'Passed'";
$stmt = $conn->prepare($passed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$passed_tests = $stmt->get_result()->fetch_assoc()['count'];

$failed_query = "SELECT COUNT(*) as count FROM tests WHERE tester_id = ? AND test_status = 'Failed'";
$stmt = $conn->prepare($failed_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$failed_tests = $stmt->get_result()->fetch_assoc()['count'];

// include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tester Dashboard - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
    
/* ================= STATS CARDS ================= */
.stats-grid{
    display:grid;
    grid-template-columns: repeat(auto-fit,minmax(180px,1fr));
    gap:16px;
    margin-bottom:20px;
}
.stat-card{
    background:#fff;
    padding:20px;
    border-radius:var(--border-radius);
    box-shadow:0 2px 8px var(--shadow);
    display:flex;
    flex-direction:column;
    transition: all var(--transition);
}
.stat-card h3{
    font-size:1rem;
    margin-bottom:8px;
    color:var(--text);
}
.stat-card .stat-value{
    font-size:1.5rem;
    font-weight:700;
    color:var(--accent);
}
.stat-card.success .stat-value{color:var(--success-text);}
.stat-card.danger .stat-value{color:var(--error-text);}
.stat-card.warning .stat-value{color:var(--warning-text);}

/* ================= CONTENT ================= */
.content-section{
    background:#fff;
    padding:20px;
    border-radius:var(--border-radius);
    margin-bottom:24px;
    box-shadow:0 2px 8px var(--shadow);
}
.content-section .section-header{
    display:flex;
    justify-content:space-between;
    flex-wrap:wrap;
    margin-bottom:16px;
}
.content-section .section-header h2{
    font-size:1.2rem;
}
.content-section .btn{
    background:var(--accent);
    color:var(--navy);
    padding:8px 14px;
    border-radius:8px;
    font-size:.9rem;
    transition: all var(--transition);
}
.content-section .btn:hover{
    background:#58a0e3;
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
}
th,td{
    padding:12px;
    text-align:left;
    font-size:.9rem;
}
th{
    background:#f1f5f9;
    text-transform:uppercase;
    font-size:.8rem;
}
tr:hover td{
    background:rgba(109,188,246,.08);
}
.status-badge{
    padding:6px 12px;
    border-radius:14px;
    font-size:.8rem;
    font-weight:600;
    display:inline-block;
}
.status-passed{background:var(--success); color:var(--success-text);}
.status-failed{background:var(--error); color:var(--error-text);}
.status-testing{background:var(--info); color:var(--info-text);}
.status-cpri{background:var(--warning); color:var(--warning-text);}
.status-re{background:var(--purple); color:var(--purple-text);}

/* ACTION FORM */
table form{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
table select, table button{
    padding:6px;
    border-radius:6px;
    font-size:.85rem;
}
table button{
    background:var(--navy);
    color:#fff;
    border:none;
}

/* ================= FILTERS FORM ================= */
.filters{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:20px;
}
.filters input[type="text"], .filters select{
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:.9rem;
    flex:1 1 200px;
    min-width:120px;
}
.filters button{
    background:var(--accent);
    color:var(--navy);
    padding:8px 16px;
    border:none;
    border-radius:8px;
    font-size:.9rem;
    cursor:pointer;
    transition: all var(--transition);
}
.filters button:hover{background:#58a0e3;}

/* ================= BADGES ================= */
.badge{
    display:inline-block;
    padding:6px 12px;
    border-radius:14px;
    font-size:.8rem;
    font-weight:600;
    text-align:center;
}
.badge-pending{background:var(--info); color:var(--info-text);}
.badge-approved{background:var(--success); color:var(--success-text);}
.badge-rejected{background:var(--error); color:var(--error-text);}
.badge-success{background:var(--success); color:var(--success-text);}
.badge-danger{background:var(--error); color:var(--error-text);}
.badge-progress{background:var(--info); color:var(--info-text);}

/* ================= RESPONSIVE ================= */
@media(max-width:1199px){
    .main-content{padding:24px;}
}
@media(max-width:991px){
    .sidebar{width:220px;}
    table{min-width:700px;}
    table form{flex-direction:column; align-items:flex-start;}
}
@media(max-width:767px){
    .dashboard-container{flex-direction:column;}
    .sidebar{width:100%; height:auto; position:relative;}
    .main-content{padding:16px;}
    .user-info{flex-direction:column; gap:6px;}
    table{min-width:620px;}
    table select, table button{width:100%;}
    .filters{flex-direction:column; gap:8px;}
    .filters input[type="text"], .filters select, .filters button{
        font-size:0.75rem;
        padding:6px 8px;
    }
    .filters button{width:100%;}
    .stats-grid{grid-template-columns: repeat(auto-fit,minmax(150px,1fr));}
}

/* ================= EMPTY STATES ================= */
.empty-state{
    text-align:center;
    padding:20px;
    color:#64748b;
    font-size:.9rem;
}
</style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Dashboard</a></li>
                <li><a href="my_tests.php">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Tester Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>Total Tests Conducted</h3>
                    <div class="stat-value"><?php echo $total_tests; ?></div>
                </div>
                <div class="stat-card warning">
                    <h3>Tests Today</h3>
                    <div class="stat-value"><?php echo $today_tests; ?></div>
                </div>
                <div class="stat-card success">
                    <h3>Passed Tests</h3>
                    <div class="stat-value"><?php echo $passed_tests; ?></div>
                </div>
                <div class="stat-card danger">
                    <h3>Failed Tests</h3>
                    <div class="stat-value"><?php echo $failed_tests; ?></div>
                </div>
            </div>

            <!-- Pending Tests Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Pending Tests</h2>
                    <a href="pending_tests.php" class="btn btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <?php if (count($pending_tests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Test ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Test Type</th>
                                <th>Test Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($pending_tests, 0, 5) as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                                <td><span class="badge badge-pending"><?php echo $test['test_status']; ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="update_test.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-edit">Update</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No pending tests found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Tests Section -->
            <div class="content-section">
                <div class="section-header">
                    <h2>Recent Tests</h2>
                    <a href="my_tests.php" class="btn btn-primary">View All Tests</a>
                </div>
                <div class="table-responsive">
                    <?php if (count($recent_tests) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Test ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Test Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_tests as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($test['test_date'])); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-pending';
                                    if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                                    elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                                    elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-progress';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                                </td>
                                <td>
                                    <div class="action-btns">
                                        <a href="view_test.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-view">View</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <p>No recent tests found.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
    <script src="../admin/assets/js/confirm.js"></script>
</body>
</html>