<?php
// supervisor/modules/dashboard.php
require_once __DIR__ . "/../../config/database.php";
require_once __DIR__ . "/../../includes/functions.php";

// Get statistics using mysqli
$total_query = "SELECT COUNT(*) as count FROM products";
$total = $conn->query($total_query)->fetch_assoc()['count'];

$passed_query = "SELECT COUNT(*) as count FROM products WHERE current_status='Passed'";
$passed = $conn->query($passed_query)->fetch_assoc()['count'];

$failed_query = "SELECT COUNT(*) as count FROM products WHERE current_status='Failed'";
$failed = $conn->query($failed_query)->fetch_assoc()['count'];

$testing_query = "SELECT COUNT(*) as count FROM products WHERE current_status='In Testing'";
$testing = $conn->query($testing_query)->fetch_assoc()['count'];

$cpri_query = "SELECT COUNT(*) as count FROM products WHERE current_status='Sent to CPRI'";
$cpri = $conn->query($cpri_query)->fetch_assoc()['count'];
?>

<h1>Supervisor Dashboard</h1>

<div class="cards">

<a href="products/list.php" class="card clickable">
    Total Products
    <b><?= $total ?></b>
</a>

<a href="products/list.php?status=Passed" class="card success clickable">
    Passed
    <b><?= $passed ?></b>
</a>

<a href="products/list.php?status=Failed" class="card danger clickable">
    Failed
    <b><?= $failed ?></b>
</a>

<a href="products/list.php?status=In Testing" class="card info clickable">
    In Testing
    <b><?= $testing ?></b>
</a>

<a href="products/list.php?status=Sent to CPRI" class="card info clickable">
    Sent to CPRI
    <b><?= $cpri ?></b>
</a>

</div>
