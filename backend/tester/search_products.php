<?php
// tester/search_products.php
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
if (isset($_GET['search']) || isset($_GET['product_type']) || isset($_GET['status'])) {
    $search_term = isset($_GET['search']) ? $_GET['search'] : '';
    $product_type = isset($_GET['product_type']) ? $_GET['product_type'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    
    $search_results = searchProducts($conn, $search_term, $product_type, $status);
    $search_performed = true;
}

// Get product types for filter
$product_types = $conn->query("SELECT * FROM product_types WHERE is_active = 1 ORDER BY product_type_name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Products - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
<style>
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

/* Search row (stable, no column forcing) */
.search-row{
    display:flex;
    flex-wrap:wrap;
    gap:14px;
    width:100%;
}

.form-group{
    flex:1;
    min-width:220px;
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

.badge-testing{background:var(--info-bg); color:var(--info);}
.badge-passed{background:var(--success-bg); color:var(--success);}
.badge-failed{background:var(--error-bg); color:var(--error);}
.badge-remanufacturing{background:var(--warning-bg); color:#8a6d3b;}
.badge-cpri{background:#e0e7ff; color:#3730a3;}

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
@media (max-width: 992px){
    .main-content{padding:16px;}
    .form-group{min-width:200px;}
}

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
                <li><a href="search_products.php" class="active">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>Search Products</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <!-- Search Form -->
        <div class="search-section">
            <form method="GET" action="" class="search-form">
                <div class="search-row">
                    <div class="form-group">
                        <label>Search Term</label>
                        <input type="text" name="search" placeholder="Product ID, Name, or Batch Number..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label>Product Type</label>
                        <select name="product_type">
                            <option value="">All Types</option>
                            <?php while ($type = $product_types->fetch_assoc()): ?>
                            <option value="<?php echo $type['product_type_id']; ?>" 
                                <?php echo (isset($_GET['product_type']) && $_GET['product_type'] == $type['product_type_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['product_type_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Statuses</option>
                            <option value="In Testing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'In Testing') ? 'selected' : ''; ?>>In Testing</option>
                            <option value="Passed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Passed') ? 'selected' : ''; ?>>Passed</option>
                            <option value="Failed" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Failed') ? 'selected' : ''; ?>>Failed</option>
                            <option value="Re-Manufacturing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Re-Manufacturing') ? 'selected' : ''; ?>>Re-Manufacturing</option>
                            <option value="Sent to CPRI" <?php echo (isset($_GET['status']) && $_GET['status'] == 'Sent to CPRI') ? 'selected' : ''; ?>>Sent to CPRI</option>
                        </select>
                    </div>
                </div>

                <div class="search-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="search_products.php" class="btn btn-secondary">Clear</a>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <?php if ($search_performed): ?>
        <div class="results-section">
            <div class="results-header">
                <h2>Search Results</h2>
                <p class="results-count"><?php echo count($search_results); ?> product(s) found</p>
            </div>

            <?php if (count($search_results) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Type</th>
                            <th>Batch Number</th>
                            <th>Manufacturing Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($search_results as $product): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['product_type_name']); ?></td>
                            <td><?php echo htmlspecialchars($product['batch_number']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($product['manufacturing_date'])); ?></td>
                            <td>
                                <?php
                                $badge_class = 'badge-testing';
                                if ($product['current_status'] == 'Passed') $badge_class = 'badge-passed';
                                elseif ($product['current_status'] == 'Failed') $badge_class = 'badge-failed';
                                elseif ($product['current_status'] == 'Re-Manufacturing') $badge_class = 'badge-remanufacturing';
                                elseif ($product['current_status'] == 'Sent to CPRI') $badge_class = 'badge-cpri';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                            </td>
                            <td>
                                <a href="product_details.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-view">View Details</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <h3>No products found</h3>
                <p>Try adjusting your search criteria.</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>