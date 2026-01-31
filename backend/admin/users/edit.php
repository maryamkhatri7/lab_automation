<?php
// admin/users/edit.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id = $_GET['id'] ?? '';

if (empty($user_id)) {
    header("Location: list.php");
    exit();
}

// Get user
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header("Location: list.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $user_type = $_POST['user_type'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if username exists (excluding current user)
    $check_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Username already exists.";
    } else {
        // Check if email exists (excluding current user)
        $check_query = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            // Update password if provided
            if (!empty($_POST['password'])) {
                $password = md5($_POST['password']);
                $update_query = "UPDATE users SET username = ?, password = ?, full_name = ?, email = ?, phone = ?, user_type = ?, is_active = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("sssssssi", $username, $password, $full_name, $email, $phone, $user_type, $is_active, $user_id);
            } else {
                $update_query = "UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, user_type = ?, is_active = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("ssssssi", $username, $full_name, $email, $phone, $user_type, $is_active, $user_id);
            }
            
            if ($stmt->execute()) {
                logActivity($conn, $_SESSION['user_id'], "Edited user", "users", $username);
                $success = "User updated successfully!";
                // Refresh user data
                $user_query = "SELECT * FROM users WHERE user_id = ?";
                $stmt = $conn->prepare($user_query);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $error = "Error updating user: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - Admin Panel</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f4f6f9;
            color: #333;
        }
        
        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar-header h2 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        
        .sidebar-header p {
            font-size: 13px;
            color: #bdc3c7;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 5px;
        }
        
        .sidebar-menu a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            text-decoration: none;
            transition: background 0.3s;
        }
        
        .sidebar-menu a:hover {
            background: #34495e;
            border-left: 3px solid #3498db;
        }
        
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 30px;
        }
        
        .top-bar {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .content-section {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ecf0f1;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .help-text {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Admin Panel</h2>
                <p><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="../index.php">Dashboard</a></li>
                <li><a href="../products/list.php">Products</a></li>
                <li><a href="../products/add.php">Add Product</a></li>
                <li><a href="../tests/list.php">Tests</a></li>
                <li><a href="list.php">Users</a></li>
                <li><a href="add.php">Add User</a></li>
                <li><a href="../cpri/list.php">CPRI Submissions</a></li>
                <li><a href="../remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="../reports/index.php">Reports</a></li>
                <li><a href="../search.php">Advanced Search</a></li>
                <li><a href="../config.php">System Config</a></li>
                <li><a href="../logs.php">Activity Logs</a></li>
                <li><a href="../../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>Edit User</h1>
                <a href="list.php" class="btn btn-secondary">Back to List</a>
            </div>

            <div class="content-section">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Username *</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current password">
                            <div class="help-text">Leave blank to keep current password</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>User Type *</label>
                            <select name="user_type" required>
                                <option value="Admin" <?php echo $user['user_type'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                <option value="Tester" <?php echo $user['user_type'] == 'Tester' ? 'selected' : ''; ?>>Tester</option>
                                <option value="Supervisor" <?php echo $user['user_type'] == 'Supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="is_active" <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                                Active User
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Update User</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
