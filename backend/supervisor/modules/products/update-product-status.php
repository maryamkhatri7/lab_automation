<?php
// supervisor/modules/products/update-product-status.php
require_once "../../auth/check-role.php";  // includes check-login too
require_once "../../../config/database.php";
require_once "../../../includes/functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $new_status = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE products SET current_status = ? WHERE product_id = ?");
    $stmt->bind_param("ss", $new_status, $product_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], "Updated product status", "products", $product_id);
        header("Location: list.php");  // redirect back to list
        exit();
    } else {
        echo "Failed to update product status!";
    }
} else {
    die("Invalid request!");
}
?>
