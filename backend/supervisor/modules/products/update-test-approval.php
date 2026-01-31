<?php
// supervisor/modules/products/update-test-approval.php
require_once "../../auth/check-login.php";
require_once "../../auth/check-role.php";
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";

$test_id = $_POST['test_id'] ?? null;
$decision = $_POST['decision'] ?? null;

// Validate input
if (!$test_id || !$decision) {
    http_response_code(400);
    echo "Error: Missing test_id or decision";
    exit;
}

// Make decision safe
$decision = ucfirst(strtolower($decision));
if (!in_array($decision, ['Approved','Rejected'])) {
    http_response_code(400);
    echo "Error: Invalid decision value";
    exit;
}

// Map decision to test_status
$new_status = ($decision === 'Approved') ? 'Passed' : 'Failed';

$stmt = $conn->prepare("UPDATE tests SET test_status = ?, approved_by = ?, approval_date = NOW() WHERE test_id = ?");
$stmt->bind_param("sis", $new_status, $_SESSION['user_id'], $test_id);

if ($stmt->execute()) {
    logActivity($conn, $_SESSION['user_id'], "Updated test approval: $decision", "tests", $test_id);
    echo "success";
} else {
    http_response_code(500);
    echo "Database error: " . $conn->error;
}
