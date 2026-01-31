<?php
// includes/functions.php

// Get tester statistics
function getTesterStats($conn, $user_id) {
    $stats = array();
    
    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN test_status = 'Passed' THEN 1 ELSE 0 END) as passed,
                SUM(CASE WHEN test_status = 'Failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN test_status = 'Pending' THEN 1 ELSE 0 END) as pending
              FROM tests WHERE tester_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    
    return $stats;
}

// Get recent tests
function getRecentTests($conn, $user_id, $limit = 10) {
    $query = "SELECT t.*, p.product_name, tt.test_type_name 
              FROM tests t
              JOIN products p ON t.product_id = p.product_id
              JOIN test_types tt ON t.test_type_id = tt.test_type_id
              WHERE t.tester_id = ?
              ORDER BY t.created_at DESC
              LIMIT ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tests = array();
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    
    return $tests;
}

// Get pending tests
function getPendingTests($conn, $user_id) {
    $query = "SELECT t.*, p.product_name, tt.test_type_name 
              FROM tests t
              JOIN products p ON t.product_id = p.product_id
              JOIN test_types tt ON t.test_type_id = tt.test_type_id
              WHERE t.tester_id = ? AND t.test_status IN ('Pending', 'In Progress')
              ORDER BY t.test_date DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tests = array();
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    
    return $tests;
}

// Generate Test ID
function generateTestID($conn, $product_code, $revision, $testing_code) {
    // Get the next roll number
    $query = "SELECT MAX(CAST(roll_number AS UNSIGNED)) as max_roll 
              FROM tests 
              WHERE product_code = ? AND revision_number = ? AND testing_code = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $product_code, $revision, $testing_code);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    $next_roll = $result['max_roll'] ? $result['max_roll'] + 1 : 1;
    $roll_number = str_pad($next_roll, 2, '0', STR_PAD_LEFT);
    
    // Format: ProductCode(6) + Revision(2) + TestingCode(2) + RollNumber(2) = 12 digits
    $test_id = $product_code . $revision . $testing_code . $roll_number;
    
    return $test_id;
}

// Get all products
function getAllProducts($conn) {
    $query = "SELECT p.*, pt.product_type_name 
              FROM products p
              JOIN product_types pt ON p.product_type_id = pt.product_type_id
              ORDER BY p.created_at DESC";
    
    $result = $conn->query($query);
    $products = array();
    
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Get all test types
function getAllTestTypes($conn) {
    $query = "SELECT * FROM test_types WHERE is_active = 1 ORDER BY test_type_name";
    $result = $conn->query($query);
    $test_types = array();
    
    while ($row = $result->fetch_assoc()) {
        $test_types[] = $row;
    }
    
    return $test_types;
}

// Get test by ID
function getTestById($conn, $test_id) {
    $query = "SELECT t.*, p.product_name, p.specifications, tt.test_type_name, tt.test_criteria, u.full_name as tester_name
              FROM tests t
              JOIN products p ON t.product_id = p.product_id
              JOIN test_types tt ON t.test_type_id = tt.test_type_id
              JOIN users u ON t.tester_id = u.user_id
              WHERE t.test_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Get test parameters
function getTestParameters($conn, $test_id) {
    $query = "SELECT * FROM test_parameters WHERE test_id = ? ORDER BY parameter_id";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $test_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $parameters = array();
    while ($row = $result->fetch_assoc()) {
        $parameters[] = $row;
    }
    
    return $parameters;
}

// Log user activity
function logActivity($conn, $user_id, $action, $table_affected = null, $record_id = null) {
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    $query = "INSERT INTO user_logs (user_id, action, table_affected, record_id, ip_address) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("issss", $user_id, $action, $table_affected, $record_id, $ip_address);
    $stmt->execute();
}

// Get product by ID
function getProductById($conn, $product_id) {
    $query = "SELECT p.*, pt.product_type_name, pt.product_type_code
              FROM products p
              JOIN product_types pt ON p.product_type_id = pt.product_type_id
              WHERE p.product_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

// Search products
function searchProducts($conn, $search_term, $product_type = null, $status = null) {
    $query = "SELECT p.*, pt.product_type_name 
              FROM products p
              JOIN product_types pt ON p.product_type_id = pt.product_type_id
              WHERE (p.product_id LIKE ? OR p.product_name LIKE ? OR p.batch_number LIKE ?)";
    
    if ($product_type) {
        $query .= " AND p.product_type_id = " . intval($product_type);
    }
    
    if ($status) {
        $query .= " AND p.current_status = '" . $conn->real_escape_string($status) . "'";
    }
    
    $query .= " ORDER BY p.created_at DESC";
    
    $search_param = "%$search_term%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $products = array();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    
    return $products;
}

// Search tests
function searchTests($conn, $search_term, $test_type = null, $status = null, $date_from = null, $date_to = null) {
    $query = "SELECT t.*, p.product_name, tt.test_type_name, u.full_name as tester_name
              FROM tests t
              JOIN products p ON t.product_id = p.product_id
              JOIN test_types tt ON t.test_type_id = tt.test_type_id
              JOIN users u ON t.tester_id = u.user_id
              WHERE (t.test_id LIKE ? OR t.product_id LIKE ? OR p.product_name LIKE ?)";
    
    if ($test_type) {
        $query .= " AND t.test_type_id = " . intval($test_type);
    }
    
    if ($status) {
        $query .= " AND t.test_status = '" . $conn->real_escape_string($status) . "'";
    }
    
    if ($date_from) {
        $query .= " AND t.test_date >= '" . $conn->real_escape_string($date_from) . "'";
    }
    
    if ($date_to) {
        $query .= " AND t.test_date <= '" . $conn->real_escape_string($date_to) . "'";
    }
    
    $query .= " ORDER BY t.test_date DESC";
    
    $search_param = "%$search_term%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tests = array();
    while ($row = $result->fetch_assoc()) {
        $tests[] = $row;
    }
    
    return $tests;
}

// Update product status based on test results
function updateProductStatus($conn, $product_id) {
    // Get all tests for this product
    $query = "SELECT test_status FROM tests WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $has_failed = false;
    $has_pending = false;
    $all_passed = true;
    
    while ($row = $result->fetch_assoc()) {
        if ($row['test_status'] == 'Failed') {
            $has_failed = true;
            $all_passed = false;
        }
        if ($row['test_status'] == 'Pending' || $row['test_status'] == 'In Progress') {
            $has_pending = true;
            $all_passed = false;
        }
    }
    
    // Determine new status
    $new_status = 'In Testing';
    if ($has_failed) {
        $new_status = 'Failed';
    } elseif ($all_passed && !$has_pending) {
        $new_status = 'Passed';
    }
    
    // Update product status
    $update_query = "UPDATE products SET current_status = ? WHERE product_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ss", $new_status, $product_id);
    $stmt->execute();
    
    return $new_status;
}
?>