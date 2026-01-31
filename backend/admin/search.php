<?php
// admin/search.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$search_term = $_GET['q'] ?? '';
$search_type = $_GET['type'] ?? 'all';
$results = [];

if (!empty($search_term)) {
    if ($search_type == 'all' || $search_type == 'products') {
        $products = searchProducts($conn, $search_term);
        $results['products'] = $products;
    }
    
    if ($search_type == 'all' || $search_type == 'tests') {
        $tests = searchTests($conn, $search_term);
        $results['tests'] = $tests;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Search - Admin Panel</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">

    <style>
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
                <li><a href="search.php" class="active">Advanced Search</a></li>
                <li><a href="config.php">System Config</a></li>
                <li><a href="logs.php">Activity Logs</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Advanced Search</h1>
            </div>

            <div class="content-section">
                <form method="GET" action="" class="search-form">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search products, tests..." required>
                    <select name="type">
                        <option value="all" <?php echo $search_type == 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="products" <?php echo $search_type == 'products' ? 'selected' : ''; ?>>Products</option>
                        <option value="tests" <?php echo $search_type == 'tests' ? 'selected' : ''; ?>>Tests</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <?php if (!empty($search_term)): ?>
                <?php if (isset($results['products']) && count($results['products']) > 0): ?>
                <div class="content-section">
                    <h2 style="margin-bottom: 20px;">Products (<?php echo count($results['products']); ?>)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['products'] as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_type_name']); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-pending';
                                    if ($product['current_status'] == 'Passed') $badge_class = 'badge-success';
                                    elseif ($product['current_status'] == 'Failed') $badge_class = 'badge-danger';
                                    elseif ($product['current_status'] == 'Sent to CPRI') $badge_class = 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                                </td>
                                <td>
                                    <a href="products/view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-view">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (isset($results['tests']) && count($results['tests']) > 0): ?>
                <div class="content-section">
                    <h2 style="margin-bottom: 20px;">Tests (<?php echo count($results['tests']); ?>)</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Test ID</th>
                                <th>Product ID</th>
                                <th>Product Name</th>
                                <th>Test Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['tests'] as $test): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
                                <td>
                                    <?php
                                    $badge_class = 'badge-pending';
                                    if ($test['test_status'] == 'Passed') $badge_class = 'badge-success';
                                    elseif ($test['test_status'] == 'Failed') $badge_class = 'badge-danger';
                                    elseif ($test['test_status'] == 'In Progress') $badge_class = 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo $test['test_status']; ?></span>
                                </td>
                                <td>
                                    <a href="tests/view.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-view">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <?php if (empty($results['products']) && empty($results['tests'])): ?>
                <div class="content-section">
                    <p style="text-align: center; padding: 40px; color: #7f8c8d;">No results found.</p>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
