<?php
// supervisor/modules/products/test_approval.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";
$user_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Supervisor';

// Fetch all tests with a derived approval status
$query = "SELECT 
    t.test_id,
    p.product_name,
    tt.test_type_name,
    t.test_status,
    t.approval_date,
    t.observed_results,
    u.full_name AS tester,
    CASE 
        WHEN t.test_status = 'Passed' THEN 'Approved'
        WHEN t.test_status = 'Failed' THEN 'Rejected'
        ELSE 'Pending'
    END AS approval_status
FROM tests t
JOIN products p ON t.product_id = p.product_id
JOIN test_types tt ON t.test_type_id = tt.test_type_id
LEFT JOIN users u ON t.tester_id = u.user_id
ORDER BY t.created_at DESC";

$result = $conn->query($query);
$tests = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Test Approvals - Supervisor</title>
<link rel="stylesheet" href="../../public/style.css">
<style>
    /* ===== STATUS BADGES FOR APPROVAL ===== */
.status {
    padding: 6px 12px;
    border-radius: 12px;
    font-weight: 600;
    display: inline-block;
    text-align: center;
    min-width: 80px;
}

/* Approved: green background, white text */
.status-approved {
    background-color: #28a745!important; /* green */
    color: #fff;
}
.status-approved:hover{
        background-color: #28a745!important; /* green */
    color: #fff;
}

/* Rejected: red background, white text */
.status-rejected {
    background-color: #dc3545!important; /* red */
    color: #fff;
}
.status-rejected:hover{
background-color: #dc3545!important; /* red */
    color: #fff;
}
/* Pending: optional styling */
.status-pending {
    background-color: #ffc107!important; /* yellow */
    color: #212529;
}
 .status-pending:hover{
   background-color: #ffc107!important; /* yellow */
    color: #212529;
}

/* ===== ACTION BUTTONS COLORS ===== */
 .btn-success {
    background-color: #28a745!important; /* green */
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-success:hover {
    background-color: #218838; /* darker green on hover */
}

.btn-danger{
    background-color: #dc3545!important; /* red */
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 6px 12px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-danger:hover {
    background-color: #c82333; /* darker red on hover */
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
                <li><a href="list.php">Products</a></li>
                <li><a href="tests.php">Tests</a></li>
                <li><a href="test_approval.php" class="active">Test Approval</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../../../logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="main-content">
            <div class="top-bar">
                <h1>Pending Test Approvals</h1>
                <div class="user-info">Welcome, <?php echo htmlspecialchars($user_name); ?> <a href="../../../logout.php" class="logout-btn" style="margin-left:12px;text-decoration:none;padding:8px 12px;background:#e74c3c;color:#fff;border-radius:6px;">Logout</a></div>
            </div>
            <div class="content-section">
                <div style="margin-bottom:12px;"><a href="list.php" class="btn">Products</a> <a href="tests.php" class="btn">Tests</a></div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Test ID</th>
                                <th>Product</th>
                                <th>Test Type</th>
                                <th>Tester</th>
                                <th>Result</th>
                                <th>Approval Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$tests): ?>
                                <tr><td colspan="7">No pending approvals</td></tr>
                            <?php endif; ?>

                            <?php foreach ($tests as $t): ?>
                                <tr id="row-<?php echo htmlspecialchars($t['test_id']); ?>">
                                    <td><?php echo htmlspecialchars($t['test_id']); ?></td>
                                    <td><?php echo htmlspecialchars($t['product_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['test_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($t['tester']); ?></td>
                                    <td><?php echo htmlspecialchars($t['test_status']); ?></td>
                                    <td class="status <?php echo strtolower($t['approval_status'])=='pending'?'status-pending':''; echo strtolower($t['approval_status'])=='approved'?' status-approved':''; echo strtolower($t['approval_status'])=='rejected'?' status-rejected':''; ?>"><?php echo htmlspecialchars($t['approval_status']); ?><?php echo $t['approval_date'] ? ' ('.htmlspecialchars($t['approval_date']).')' : ''; ?></td>
                                    <td>
                                        <button class="btn btn-success" onclick="updateTest('<?php echo addslashes($t['test_id']); ?>','Approved')">Approve</button>
                                        <button class="btn btn-danger" onclick="updateTest('<?php echo addslashes($t['test_id']); ?>','Rejected')">Reject</button>
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

<script>
function updateTest(testId, decision) {
    if (!confirm('Are you sure you want to ' + decision + ' this test?')) return;

    var params = 'test_id=' + encodeURIComponent(testId) + '&decision=' + encodeURIComponent(decision);

    fetch('update-test-approval.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params
    })
    .then(response => response.text())
    .then(data => {
        data = data.trim();
        if(data === "success") {
            const row = document.getElementById('row-' + testId);
            const statusCell = row.querySelector('.status');

            statusCell.innerText = decision;
            statusCell.className = 'status';

            if(decision === 'Approved') statusCell.classList.add('status-approved');
            else if(decision === 'Rejected') statusCell.classList.add('status-rejected');
            else statusCell.classList.add('status-pending');

        } else {
            alert('Failed: ' + data);
        }
    })
    .catch(err => alert('Error: ' + err));
}
</script>

</body>
</html>
