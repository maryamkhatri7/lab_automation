<?php
// tester/search_tests.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$search_results = array();
$search_performed = false;

// Handle search
if (isset($_GET['search']) || isset($_GET['test_type']) || isset($_GET['status']) || isset($_GET['date_from']) || isset($_GET['date_to'])) {
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
    $test_type = isset($_GET['test_type']) ? $_GET['test_type'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;
    
    $search_results = searchTests($conn, $search_term, $test_type, $status, $date_from, $date_to);
    $search_performed = true;
}

// Get test types for filter
$test_types = getAllTestTypes($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Tests - Lab Automation System</title>
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
/* ================= MAIN CONTENT ================= */
.main-content{
    flex:1;
    padding:24px;
    width:100%;
    min-width:0;
}

/* ================= PAGE HEADER ================= */
.page-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:12px;
    margin-bottom:20px;
}

.page-header h1{
    font-size:1.6rem;
    font-weight:700;
}

.btn-back{
    background:var(--accent);
    color:var(--navy);
    padding:6px 14px;
    border-radius:6px;
    text-decoration:none;
    font-weight:600;
    font-size:0.85rem;
}

/* ================= SEARCH SECTION ================= */
.search-section{
    background:var(--glass);
    padding:18px;
    border-radius:12px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
    margin-bottom:26px;
}

.search-form{
    display:flex;
    flex-wrap:wrap;
    gap:14px;
}

/* Row stays horizontal & wraps naturally */
.search-row{
    display:flex;
    flex-wrap:wrap;
    gap:14px;
    width:100%;
}

.form-group{
    flex:1;
    min-width:180px;
}

.form-group label{
    font-size:0.8rem;
    font-weight:600;
    margin-bottom:4px;
    display:block;
}

.form-group input,
.form-group select{
    width:100%;
    height:42px;
    padding:8px 12px;
    border-radius:8px;
    border:1px solid #cbd5e1;
    font-size:0.9rem;
    background:#fff;
    transition:0.25s;
}

.form-group input:focus,
.form-group select:focus{
    outline:none;
    border-color:var(--accent);
    box-shadow:0 0 0 2px rgba(109,188,246,.25);
}

/* ================= SEARCH ACTIONS ================= */
.search-actions{
    display:flex;
    gap:10px;
    align-items:flex-end;
}

.btn{
    border:none;
    border-radius:8px;
    padding:10px 18px;
    font-size:0.85rem;
    font-weight:600;
    cursor:pointer;
    transition:0.25s;
    text-decoration:none;
    text-align:center;
}

.btn-primary{
    background:var(--accent);
    color:var(--navy);
}

.btn-secondary{
    background:#e2e8f0;
    color:var(--navy);
}

.btn:hover{
    opacity:0.9;
}

.btn-sm{
    padding:6px 10px;
    font-size:0.75rem;
}

/* ================= RESULTS SECTION ================= */
.results-section{
    background:var(--glass);
    padding:18px;
    border-radius:12px;
    box-shadow:0 4px 14px rgba(0,0,0,0.08);
}

.results-header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:10px;
    margin-bottom:14px;
}

.results-header h2{
    font-size:1.2rem;
}

.results-count{
    font-size:0.85rem;
    color:#64748b;
}

/* ================= TABLE ================= */
.table-responsive{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:900px;
}

th, td{
    padding:10px 12px;
    font-size:0.85rem;
    text-align:left;
    border-bottom:1px solid #e5e7eb;
}

th{
    background:#f1f5f9;
    font-weight:700;
    font-size:0.8rem;
}

tr:hover td{
    background:rgba(109,188,246,0.08);
}

/* ================= BADGES ================= */
.badge{
    padding:4px 10px;
    border-radius:12px;
    font-size:0.7rem;
    font-weight:600;
    display:inline-block;
}

.badge-pending{background:var(--info-bg); color:var(--info);}
.badge-progress{background:#cce5ff; color:#004085;}
.badge-success{background:var(--success-bg); color:var(--success);}
.badge-danger{background:var(--error-bg); color:var(--error);}

/* ================= EMPTY STATE ================= */
.empty-state{
    text-align:center;
    padding:40px 10px;
}

.empty-state h3{
    font-size:1.1rem;
    margin-bottom:6px;
}

.empty-state p{
    font-size:0.85rem;
    color:#64748b;
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media (max-width: 992px){
    .main-content{padding:16px;}
    .search-actions{width:100%;}
    .btn{width:auto;}
}

/* Mobile */
@media (max-width: 576px){
    .page-header{
        flex-direction:column;
        align-items:flex-start;
    }

    .search-actions{
        width:100%;
    }

    .btn{
        width:100%;
    }

    table{
        min-width:100%;
        font-size:0.8rem;
    }
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
                <li><a href="search_tests.php" class="active">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Search Tests</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <!-- Search Form -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <div class="search-row">
                    <div class="form-group">
                        <label>Search Term</label>
                        <input type="text" name="search" placeholder="Test ID or Product ID..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Test Type</label>
                        <select name="test_type">
                            <option value="">All Types</option>
                            <?php foreach ($test_types as $type): ?>
                            <option value="<?php echo $type['test_type_id']; ?>" 
                                <?php echo (isset($_GET['test_type']) && $_GET['test_type'] == $type['test_type_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['test_type_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="Pending" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="In Progress" <?php echo (isset($_GET['status']) && $_GET['status'] == 'In Progress') ? 'selected' : ''; ?>>In Progress</option>
                            <option value="Passed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Passed') ? 'selected' : ''; ?>>Passed</option>
                            <option value="Failed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Date From</label>
                        <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? $_GET['date_from'] : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Date To</label>
                        <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? $_GET['date_to'] : ''; ?>">
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="search_tests.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <?php if ($search_performed): ?>
        <div class="results-section">
            <div class="results-header">
                <h2>Search Results</h2>
                <p class="results-count"><?php echo count($search_results); ?> test(s) found</p>
            </div>

            <?php if (count($search_results) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Test ID</th>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Test Type</th>
                            <th>Tester</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $test): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                            <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($test['tester_name']); ?></td>
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
                                <a href="view_test.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-view">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>No tests found</h3>
                <p>Try adjusting your search criteria.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>