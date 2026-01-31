<?php
// tester/test_history.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN test_status = 'Passed' THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN test_status = 'Failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN test_status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN test_status = 'In Progress' THEN 1 ELSE 0 END) as in_progress
                FROM tests 
                WHERE tester_id = ? AND test_date BETWEEN ? AND ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get tests by month
$monthly_query = "SELECT 
                  DATE_FORMAT(test_date, '%Y-%m') as month,
                  COUNT(*) as total,
                  SUM(CASE WHEN test_status = 'Passed' THEN 1 ELSE 0 END) as passed,
                  SUM(CASE WHEN test_status = 'Failed' THEN 1 ELSE 0 END) as failed
                  FROM tests
                  WHERE tester_id = ? AND test_date BETWEEN ? AND ?
                  GROUP BY DATE_FORMAT(test_date, '%Y-%m')
                  ORDER BY month DESC";
$stmt = $conn->prepare($monthly_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$monthly_result = $stmt->get_result();

// Get tests by type
$type_query = "SELECT 
               tt.test_type_name,
               COUNT(*) as total,
               SUM(CASE WHEN t.test_status = 'Passed' THEN 1 ELSE 0 END) as passed,
               SUM(CASE WHEN t.test_status = 'Failed' THEN 1 ELSE 0 END) as failed
               FROM tests t
               JOIN test_types tt ON t.test_type_id = tt.test_type_id
               WHERE t.tester_id = ? AND t.test_date BETWEEN ? AND ?
               GROUP BY tt.test_type_name
               ORDER BY total DESC";
$stmt = $conn->prepare($type_query);
$stmt->bind_param("iss", $user_id, $start_date, $end_date);
$stmt->execute();
$type_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test History - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
 /* ================= ROOT COLORS ================= */
:root{
    --navy:#0b1c2d;
    --text:#1e293b;
    --accent:#6dbcf6;
    --glass:#ffffff;
    --success:#28a745;
    --success-bg:#e6f4ea;
    --error:#dc3545;
    --error-bg:#f9e6e7;
    --warning:#ffc107;
    --warning-bg:#fff6e0;
    --info:#17a2b8;
    --info-bg:#d1f0f5;
}

/* ================= GLOBAL RESET ================= */
* {
    margin:0;
    padding:0;
    box-sizing:border-box;
}
body {
    font-family:"Segoe UI",sans-serif;
    color:var(--text);
    background:#f4f6f9;
    overflow-x:hidden;
}

/* ================= DASHBOARD LAYOUT ================= */
.dashboard-container {
    display:flex;
    width:100%;
    min-height:100vh;
}
.sidebar {
    width:260px;
    background:var(--navy);
    color:#fff;
    padding:20px 0;
    height:100vh;
    position:sticky;
    top:0;
    flex-shrink:0;
}
.sidebar-header {
    padding:0 20px 20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
}
.sidebar-header h2{font-size:18px;}
.sidebar-header p{font-size:13px;color:#cbd5e1;}
.sidebar-menu{list-style:none;padding:20px 0;}
.sidebar-menu a{
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:#ecf0f1;
    border-left:3px solid transparent;
    border-radius:6px;
    transition:0.3s;
}
.sidebar-menu a:hover, .sidebar-menu a.active {
    background: rgba(255,255,255,0.05);
    border-left:3px solid var(--accent);
}

/* ================= MAIN ================= */
.main-content{
    flex:1;
    padding:24px;
    width:100%;
    min-width:0;
}

/* ================= PAGE HEADER ================= */
.page-header {
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    margin-bottom:20px;
}
.page-header h1{font-size:1.5rem; margin-bottom:6px;}
.page-header .btn-back{
    background:var(--accent);
    color:var(--navy);
    padding:6px 12px;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
    font-size:0.9rem;
}
.page-header .btn-back:hover{opacity:0.9;}

/* ================= FILTER SECTION ================= */
.filter-section {
    display: flex;
    flex-wrap: wrap;
    gap:12px;
    margin-bottom:30px;
    padding:16px;
    border-radius:10px;
    background:var(--glass);
    backdrop-filter: blur(12px);
    box-shadow:0 4px 10px rgba(0,0,0,0.05);
}
.filter-form{
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:flex-end;
}
.filter-section .form-group{
    display:flex;
    flex-direction:column;
}
.filter-section label{
    font-size:0.9rem;
    margin-bottom:4px;
    font-weight:600;
    color:var(--text);
}
.filter-section input[type="date"]{
    padding:8px 12px;
    border-radius:6px;
    border:1px solid #ccc;
    font-size:0.9rem;
    min-width:150px;
}
.filter-section button.btn-primary{
    background:var(--accent);
    color:var(--navy);
    padding:8px 16px;
    border:none;
    border-radius:6px;
    font-weight:600;
    cursor:pointer;
    font-size:0.9rem;
    transition:0.3s;
}
.filter-section button.btn-primary:hover{
    background:#58a0e3;
}

/* ================= STATS CARDS ================= */
.stats-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:20px;
    margin-bottom:30px;
}
.stat-card{
    padding:20px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    border-left:6px solid var(--accent);
    border-radius:10px;
    background:var(--glass);
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    transition:0.3s;
}
.stat-card:hover{
    transform:translateY(-3px);
    box-shadow:0 6px 16px rgba(0,0,0,0.1);
}
.stat-card h3{
    font-size:1rem;
    font-weight:700;
    margin-bottom:10px;
}
.stat-card .value{
    font-size:1.8rem;
    font-weight:800;
    color:var(--navy);
    margin-bottom:6px;
}
.stat-card .percentage{
    font-size:0.9rem;
    font-weight:600;
    color:#555;
}

/* LEFT BORDER COLORS */
.stat-card.info{border-left-color:var(--info);}
.stat-card.success{border-left-color:var(--success);}
.stat-card.danger{border-left-color:var(--error);}
.stat-card.warning{border-left-color:var(--warning);}

/* ================= TABLES ================= */
.content-section{
    background:var(--glass);
    padding:20px;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    margin-bottom:30px;
    overflow-x:auto;
}
table{
    width:100%;
    border-collapse:collapse;
    min-width:600px;
}
th, td{
    padding:12px;
    font-size:0.9rem;
    text-align:left;
}
th{
    background:#f1f5f9;
    font-weight:700;
    font-size:0.85rem;
}
tr:hover td{
    background:rgba(109,188,246,0.08);
}
td span.badge{
    padding:4px 8px;
    border-radius:12px;
    font-size:0.75rem;
    font-weight:600;
    text-align:center;
}

/* PROGRESS BAR */
.progress-bar{
    height:8px;
    background:#eee;
    border-radius:4px;
    margin-top:4px;
}
.progress-fill{
    height:100%;
    border-radius:4px;
    background:var(--accent);
    transition:width 0.4s;
}
.progress-fill.danger{background:var(--error);}
.progress-fill.success{background:var(--success);}

/* ================= RESPONSIVE ================= */
@media(max-width:991px){
    .main-content{padding:16px;}
    .filter-form{flex-direction:column;align-items:stretch;}
    .filter-section input[type="date"], .filter-section button.btn-primary{width:100%;}
    table{min-width:100%; font-size:0.85rem;}
    .stat-card{padding:16px;}
    .stat-card .value{font-size:1.4rem;}
    .stat-card h3{font-size:0.95rem;}
    .stat-card .percentage{font-size:0.8rem;}
}
@media(max-width:767px){
    .dashboard-container{flex-direction:column;}
    .sidebar{width:100%;height:auto;position:relative;}
    table{min-width:100%; font-size:0.8rem;}
    .stat-card{padding:14px;}
    .stat-card .value{font-size:1.2rem;}
    .stat-card h3{font-size:0.9rem;}
    .stat-card .percentage{font-size:0.75rem;}
}


</style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="my_tests.php">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php" class="active">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Test History & Analytics</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <!-- Date Filter -->
        <div class="filter-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?php echo $start_date; ?>" required>
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?php echo $end_date; ?>" required>
                </div>
                <button type="submit" class="btn-primary">Apply</button>
            </form>
        </div>

        <!-- Overall Statistics -->
        <div class="stats-grid">
            <div class="stat-card info">
                <h3>Total Tests</h3>
                <div class="value"><?php echo $stats['total']; ?></div>
                <div class="percentage">
                    <?php echo date('M d', strtotime($start_date)); ?> - <?php echo date('M d, Y', strtotime($end_date)); ?>
                </div>
            </div>
            
            <div class="stat-card success">
                <h3>Passed Tests</h3>
                <div class="value"><?php echo $stats['passed']; ?></div>
                <div class="percentage">
                    <?php echo $stats['total'] > 0 ? round(($stats['passed'] / $stats['total']) * 100, 1) : 0; ?>% pass rate
                </div>
            </div>
            
            <div class="stat-card danger">
                <h3>Failed Tests</h3>
                <div class="value"><?php echo $stats['failed']; ?></div>
                <div class="percentage">
                    <?php echo $stats['total'] > 0 ? round(($stats['failed'] / $stats['total']) * 100, 1) : 0; ?>% failure rate
                </div>
            </div>
            
            <div class="stat-card warning">
                <h3>Pending Tests</h3>
                <div class="value"><?php echo $stats['pending'] + $stats['in_progress']; ?></div>
                <div class="percentage">
                    In Progress: <?php echo $stats['in_progress']; ?>
                </div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <div class="content-section">
            <h2 class="section-title">Monthly Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Total Tests</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Pass Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $monthly_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                        <td><?php echo $row['total']; ?></td>
                        <td style="color: #27ae60; font-weight: 600;"><?php echo $row['passed']; ?></td>
                        <td style="color: #e74c3c; font-weight: 600;"><?php echo $row['failed']; ?></td>
                        <td>
                            <?php 
                            $pass_rate = $row['total'] > 0 ? ($row['passed'] / $row['total']) * 100 : 0;
                            echo round($pass_rate, 1) . '%';
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $pass_rate; ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Test Type Breakdown -->
        <div class="content-section">
            <h2 class="section-title">Performance by Test Type</h2>
            <table>
                <thead>
                    <tr>
                        <th>Test Type</th>
                        <th>Total Tests</th>
                        <th>Passed</th>
                        <th>Failed</th>
                        <th>Success Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $type_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['test_type_name']); ?></td>
                        <td><?php echo $row['total']; ?></td>
                        <td style="color: #27ae60; font-weight: 600;"><?php echo $row['passed']; ?></td>
                        <td style="color: #e74c3c; font-weight: 600;"><?php echo $row['failed']; ?></td>
                        <td>
                            <?php 
                            $success_rate = $row['total'] > 0 ? ($row['passed'] / $row['total']) * 100 : 0;
                            echo round($success_rate, 1) . '%';
                            ?>
                            <div class="progress-bar">
                                <div class="progress-fill <?php echo $success_rate < 70 ? 'danger' : ''; ?>" 
                                     style="width: <?php echo $success_rate; ?>%;"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>