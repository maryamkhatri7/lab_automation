<?php
// supervisor/auth/check-role.php
require_once "check-login.php"; // ensures session started and user logged in

// Only allow supervisor (using same session vars as admin/tester)
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Supervisor') {
    session_destroy();
    header("Location: ../../login.php");
    exit;
}
?>
