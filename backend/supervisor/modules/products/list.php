<?php
// supervisor/modules/products/list.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

// Keep original logic for filtering by status
$status = $_GET['status'] ?? null;

$query = "SELECT p.*, pt.product_type_name
    FROM products p
    JOIN product_types pt ON p.product_type_id = pt.product_type_id";

if ($status) {
    $query .= " WHERE p.current_status = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);
} else {
    $stmt = $conn->prepare($query);
}

$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products - Supervisor</title>
<link rel="stylesheet" href="../../public/style.css">
<style>
    /* ================= ROOT ================= */
:root{
    --navy:#0b1c2d;
    --text:#1e293b;
    --accent:#6dbcf6;
    --glass:#ffffff;
    --success:#d4edda;
    --success-text:#155724;
    --error:#f8d7da;
    --error-text:#721c24;
    --info:#d1ecf1;
    --info-text:#0c5460;
    --warning:#fff3cd;
    --warning-text:#856404;
    --purple:#f0e6ff;
    --purple-text:#4b2c6f;
}

/* ================= RESET ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}
body{
    font-family:"Segoe UI",sans-serif;
    background:#f4f6f9;
    color:var(--text);
    overflow-x:hidden;
}

/* ================= LAYOUT ================= */
.dashboard-container{
    display:flex;
    width:100%;
    min-height:100vh;
}

/* ================= SIDEBAR ================= */
.sidebar{
    width:260px;
    background:var(--navy);
    color:#fff;
    padding:20px 0;
    height:100vh;
    position:sticky;
    top:0;
    flex-shrink:0;
}
.sidebar-header{
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
    color:#fff;
    transition:.3s;
}
.sidebar-menu a { display:block; padding:12px 20px; color:#ecf0f1; border-left:3px solid transparent; border-radius:6px; transition:0.3s; }
.sidebar-menu a:hover, .sidebar-menu a.active { background: rgba(255,255,255,0.05); border-left:3px solid var(--accent); }


/* ================= MAIN ================= */
.main-content{
    flex:1;
    padding:24px;
    width:100%;
    min-width:0; /* 🔑 stops overflow */
}

/* ================= TOP BAR ================= */
.top-bar{
    background:#fff;
    border-radius:12px;
    padding:16px 20px;
    margin-bottom:20px;
    display:flex;
    flex-wrap:wrap;
    width:100%;
    box-shadow:0 2px 6px rgba(0,0,0,.08);
}
.top-bar h1{
    width:100%;
    font-size:22px;
    margin-bottom:8px;
}
.user-info{
    width:100%;
    display:flex;
    justify-content:space-between;
    align-items:center;
}
.logout-btn{
    background:#e74c3c;
    color:#fff;
    padding:8px 14px;
    border-radius:6px;
    text-decoration:none;
    font-size:.85rem;
}

/* ================= CONTENT ================= */
.content-section{
    background:#fff;
    padding:20px;
    border-radius:12px;
    width:100%;
}

/* BUTTON ROW */
.content-section > div{
    display:flex;
    gap:12px;
    width:100%;
    flex-wrap:wrap;
}
.content-section .btn{
    background:var(--accent);
    color:var(--navy);
    padding:8px 14px;
    border-radius:8px;
    text-decoration:none;
    font-size:.9rem;
}

/* ================= TABLE ================= */
.table-responsive{
    width:100%;
    overflow-x:auto;
    margin-top:16px;
}

table{
    width:100%;
    min-width:720px; /* tablet safety */
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
tr:hover td{background:rgba(109,188,246,.08);}

/* STATUS */
.status-badge{
    padding:6px 12px;
    border-radius:14px;
    font-size:.8rem;
    font-weight:600;
}
.status-passed{background:var(--success);color:var(--success-text);}
.status-failed{background:var(--error);color:var(--error-text);}
.status-testing{background:var(--info);color:var(--info-text);}
.status-cpri{background:var(--warning);color:var(--warning-text);}
.status-re{background:var(--purple);color:var(--purple-text);}

/* ACTION FORM */
table form{
    display:flex;
    gap:6px;
    flex-wrap:wrap;
}
table select,
table button{
    padding:6px;
    border-radius:6px;
    font-size:.85rem;
}
table button{
    background:var(--navy);
    color:#fff;
    border:none;
}

/* ================= MOBILE ================= */
@media(max-width:767px){
    .dashboard-container{
        flex-direction:column;
    }

    .sidebar{
        width:100%;
        height:auto;
        position:relative;
    }

    .main-content{
        padding:16px;
    }

    .user-info{
        flex-direction:column;
        gap:6px;
    }

    table{
        min-width:620px;
    }

    table select,
    table button{
        width:100%;
    }
}

/* ================= TABLET ================= */
@media(min-width:768px) and (max-width:991px){
    .sidebar{
        width:200px;
    }

    table{
        min-width:760px;
    }

    table form{
        flex-direction:column;
        align-items:flex-start;
    }
}

/* ================= LARGE ================= */
@media(min-width:1200px){
    .main-content{padding:32px;}
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
                <li><a href="list.php" class="active">Products</a></li>
                <li><a href="tests.php">Tests</a></li>
                <li><a href="test_approval.php">Test Approval</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Products <?= $status ? "($status)" : "" ?></h1>
                <div class="user-info">
                    <span>Welcome, <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../../../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>

            <div class="content-section">
                <div style="margin-bottom:10px; display:flex; gap: 10px; width:100%;"><a href="tests.php" class="btn">View Tests</a> <a href="test_approval.php" class="btn">Test Approvals</a></div>
                <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$products): ?>
                        <tr><td colspan="5">No products found</td></tr>
                        <?php endif; ?>

                        <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['product_id']); ?></td>
                            <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['product_type_name']); ?></td>
                            <td><span class="status-badge <?php echo htmlspecialchars($status_class = (isset($p['current_status'])?str_replace(' ','',strtolower($p['current_status'])):'')); ?>"><?php echo htmlspecialchars($p['current_status']); ?></span></td>
                            <td>
                                <form method="post" action="update-product-status.php" style="display:flex;gap:8px;align-items:center;">
                                    <input type="hidden" name="product_id" value="<?php echo htmlspecialchars($p['product_id']); ?>">
                                    <select name="new_status" required style="padding:6px;border-radius:6px;border:1px solid #ddd;">
                                        <option value="">Change status</option>
                                        <option value="In Testing">In Testing</option>
                                        <option value="Passed">Passed</option>
                                        <option value="Failed">Failed</option>
                                        <option value="Sent to CPRI">Sent to CPRI</option>
                                        <option value="Re-Manufacturing">Re-Manufacturing</option>
                                    </select>
                                    <button type="submit" class="btn" style="background:rgb(109,188,246);border:none;">Update</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                        </div>
            </div>
        </main>
    </div>
    <script src="../../../admin/assets/js/confirm.js"></script>
</body>
</html>
