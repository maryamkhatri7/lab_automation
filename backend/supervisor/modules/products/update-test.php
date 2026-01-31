<?php
// supervisor/modules/products/update-test.php
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";

$test_id = $_POST['test_id'] ?? '';
$supervisor_id = $_SESSION['user_id'];

if (isset($_POST['approve'])) {
    $stmt = $conn->prepare("UPDATE tests SET test_status='Passed', approved_by=?, approval_date=NOW() WHERE test_id=?");
    $stmt->bind_param("is", $supervisor_id, $test_id);
    $stmt->execute();
    logActivity($conn, $supervisor_id, "Approved test", "tests", $test_id);
}

if (isset($_POST['reject'])) {
    $stmt = $conn->prepare("UPDATE tests SET test_status='Failed', approved_by=?, approval_date=NOW() WHERE test_id=?");
    $stmt->bind_param("is", $supervisor_id, $test_id);
    $stmt->execute();
    logActivity($conn, $supervisor_id, "Rejected test", "tests", $test_id);
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'tests.php'));
exit;
