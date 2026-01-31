<?php
// supervisor/auth/check-login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not logged in (using same session vars as admin/tester)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}
?>
