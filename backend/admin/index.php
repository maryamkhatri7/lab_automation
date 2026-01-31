<?php
// admin/index.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// Check if user is logged in and is an Admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// Fetch dashboard statistics
// Total Products
$total_products_query = "SELECT COUNT(*) as count FROM products";
$total_products = $conn->query($total_products_query)->fetch_assoc()['count'];

// Products by Status
$products_in_testing = $conn->query("SELECT COUNT(*) as count FROM products WHERE current_status = 'In Testing'")->fetch_assoc()['count'];
$products_passed = $conn->query("SELECT COUNT(*) as count FROM products WHERE current_status = 'Passed'")->fetch_assoc()['count'];
$products_failed = $conn->query("SELECT COUNT(*) as count FROM products WHERE current_status = 'Failed'")->fetch_assoc()['count'];
$products_cpri = $conn->query("SELECT COUNT(*) as count FROM products WHERE current_status = 'Sent to CPRI'")->fetch_assoc()['count'];
$products_remanufacturing = $conn->query("SELECT COUNT(*) as count FROM products WHERE current_status = 'Re-Manufacturing'")->fetch_assoc()['count'];

// Total Tests
$total_tests = $conn->query("SELECT COUNT(*) as count FROM tests")->fetch_assoc()['count'];
$tests_today = $conn->query("SELECT COUNT(*) as count FROM tests WHERE DATE(test_date) = CURDATE()")->fetch_assoc()['count'];
$tests_passed = $conn->query("SELECT COUNT(*) as count FROM tests WHERE test_status = 'Passed'")->fetch_assoc()['count'];
$tests_failed = $conn->query("SELECT COUNT(*) as count FROM tests WHERE test_status = 'Failed'")->fetch_assoc()['count'];
$tests_pending = $conn->query("SELECT COUNT(*) as count FROM tests WHERE test_status IN ('Pending', 'In Progress')")->fetch_assoc()['count'];

// Total Users
$total_users = $conn->query("SELECT COUNT(*) as count FROM users WHERE is_active = 1")->fetch_assoc()['count'];
$total_testers = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'Tester' AND is_active = 1")->fetch_assoc()['count'];
$total_supervisors = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'Supervisor' AND is_active = 1")->fetch_assoc()['count'];

// CPRI Submissions
$cpri_pending = $conn->query("SELECT COUNT(*) as count FROM cpri_submissions WHERE approval_status IN ('Submitted', 'Under Review')")->fetch_assoc()['count'];
$cpri_approved = $conn->query("SELECT COUNT(*) as count FROM cpri_submissions WHERE approval_status = 'Approved'")->fetch_assoc()['count'];

// Remanufacturing Records
$remanufacturing_pending = $conn->query("SELECT COUNT(*) as count FROM remanufacturing_records WHERE status IN ('Pending', 'In Progress')")->fetch_assoc()['count'];

// Recent Products
$recent_products_query = "SELECT p.*, pt.product_type_name, u.full_name as created_by_name
                          FROM products p
                          JOIN product_types pt ON p.product_type_id = pt.product_type_id
                          JOIN users u ON p.created_by = u.user_id
                          ORDER BY p.created_at DESC
                          LIMIT 10";
$recent_products = $conn->query($recent_products_query)->fetch_all(MYSQLI_ASSOC);

// Recent Tests
$recent_tests_query = "SELECT t.*, p.product_name, tt.test_type_name, u.full_name as tester_name
                       FROM tests t
                       JOIN products p ON t.product_id = p.product_id
                       JOIN test_types tt ON t.test_type_id = tt.test_type_id
                       JOIN users u ON t.tester_id = u.user_id
                       ORDER BY t.created_at DESC
                       LIMIT 10";
$recent_tests = $conn->query($recent_tests_query)->fetch_all(MYSQLI_ASSOC);

// Products requiring attention
$attention_products_query = "SELECT p.*, pt.product_type_name,
                             (SELECT COUNT(*) FROM tests WHERE product_id = p.product_id AND test_status = 'Failed') as failed_tests_count
                             FROM products p
                             JOIN product_types pt ON p.product_type_id = pt.product_type_id
                             WHERE p.current_status IN ('Failed', 'Re-Manufacturing')
                             ORDER BY p.updated_at DESC
                             LIMIT 5";
$attention_products = $conn->query($attention_products_query)->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lab Automation System</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">

</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p><?php echo htmlspecialchars($user_name); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">Dashboard</a></li>
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
                <li><a href="logs.php">Activity Logs</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>Admin Dashboard</h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <!-- Statistics Cards - Products -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Product Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Products</h3>
                        <div class="stat-value"><?php echo $total_products; ?></div>
                    </div>
                    <div class="stat-card warning">
                        <h3>In Testing</h3>
                        <div class="stat-value"><?php echo $products_in_testing; ?></div>
                    </div>
                    <div class="stat-card success">
                        <h3>Passed</h3>
                        <div class="stat-value"><?php echo $products_passed; ?></div>
                    </div>
                    <div class="stat-card danger">
                        <h3>Failed</h3>
                        <div class="stat-value"><?php echo $products_failed; ?></div>
                    </div>
                    <div class="stat-card info">
                        <h3>Sent to CPRI</h3>
                        <div class="stat-value"><?php echo $products_cpri; ?></div>
                    </div>
                    <div class="stat-card purple">
                        <h3>Re-Manufacturing</h3>
                        <div class="stat-value"><?php echo $products_remanufacturing; ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards - Tests -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">Test Statistics</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Tests</h3>
                        <div class="stat-value"><?php echo $total_tests; ?></div>
                    </div>
                    <div class="stat-card warning">
                        <h3>Tests Today</h3>
                        <div class="stat-value"><?php echo $tests_today; ?></div>
                    </div>
                    <div class="stat-card success">
                        <h3>Passed Tests</h3>
                        <div class="stat-value"><?php echo $tests_passed; ?></div>
                    </div>
                    <div class="stat-card danger">
                        <h3>Failed Tests</h3>
                        <div class="stat-value"><?php echo $tests_failed; ?></div>
                    </div>
                    <div class="stat-card warning">
                        <h3>Pending Tests</h3>
                        <div class="stat-value"><?php echo $tests_pending; ?></div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards - System -->
            <div class="content-section">
                <h2 style="margin-bottom: 20px; color: #2c3e50;">System Overview</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="stat-value"><?php echo $total_users; ?></div>
                    </div>
                    <div class="stat-card info">
                        <h3>Testers</h3>
                        <div class="stat-value"><?php echo $total_testers; ?></div>
                    </div>
                    <div class="stat-card info">
                        <h3>Supervisors</h3>
                        <div class="stat-value"><?php echo $total_supervisors; ?></div>
                    </div>
                    <div class="stat-card warning">
                        <h3>CPRI Pending</h3>
                        <div class="stat-value"><?php echo $cpri_pending; ?></div>
                    </div>
                    <div class="stat-card success">
                        <h3>CPRI Approved</h3>
                        <div class="stat-value"><?php echo $cpri_approved; ?></div>
                    </div>
                    <div class="stat-card purple">
                        <h3>Re-Manufacturing</h3>
                        <div class="stat-value"><?php echo $remanufacturing_pending; ?></div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="two-column">
                <!-- Recent Products -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Recent Products</h2>
                        <a href="products/list.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <?php if (count($recent_products) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Product ID</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_products, 0, 5) as $product): ?>
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
                                        elseif ($product['current_status'] == 'Re-Manufacturing') $badge_class = 'badge-warning';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo $product['current_status']; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-btns">
                                            <a href="products/view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-view">View</a>
                                            <a href="products/edit.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-edit">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No products found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Tests -->
                <div class="content-section">
                    <div class="section-header">
                        <h2>Recent Tests</h2>
                        <a href="tests/list.php" class="btn btn-primary">View All</a>
                    </div>
                    <div class="table-responsive">
                        <?php if (count($recent_tests) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Test ID</th>
                                    <th>Product</th>
                                    <th>Test Type</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($recent_tests, 0, 5) as $test): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($test['test_id']); ?></td>
                                    <td><?php echo htmlspecialchars($test['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($test['test_type_name']); ?></td>
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
                                            <a href="tests/view.php?id=<?php echo $test['test_id']; ?>" class="btn btn-sm btn-view">View</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <div class="empty-state">
                            <p>No tests found.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Products Requiring Attention -->
            <?php if (count($attention_products) > 0): ?>
            <div class="content-section">
                <div class="section-header">
                    <h2>Products Requiring Attention</h2>
                    <a href="products/list.php?status=Failed" class="btn btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Product ID</th>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Failed Tests</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attention_products as $product): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($product['product_id']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['product_type_name']); ?></td>
                                <td><span class="badge badge-danger"><?php echo $product['current_status']; ?></span></td>
                                <td><?php echo $product['failed_tests_count']; ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="products/view.php?id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-view">View</a>
                                        <a href="remanufacturing/add.php?product_id=<?php echo $product['product_id']; ?>" class="btn btn-sm btn-edit">Re-Manufacture</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
