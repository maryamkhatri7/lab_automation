<?php
// logout.php
session_start();

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    
    logActivity($conn, $_SESSION['user_id'], "Logged out", null, null);
}

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>