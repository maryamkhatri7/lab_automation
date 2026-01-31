<?php
// admin/users/add.php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../../login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'] ?? null;
    $user_type = $_POST['user_type'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Check if username exists
    $check_query = "SELECT user_id FROM users WHERE username = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $error = "Username already exists.";
    } else {
        // Check if email exists
        $check_query = "SELECT user_id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = "Email already exists.";
        } else {
            $insert_query = "INSERT INTO users (username, password, full_name, email, phone, user_type, is_active) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ssssssi", $username, $password, $full_name, $email, $phone, $user_type, $is_active);
            
            if ($stmt->execute()) {
                $new_user_id = $conn->insert_id;
                logActivity($conn, $_SESSION['user_id'], "Added user", "users", $username);
                $success = "User added successfully!";
                $_POST = array();
            } else {
                $error = "Error adding user: " . $conn->error;
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
    <title>Add User - Admin Panel</title>
    <link rel="stylesheet" href="../../supervisor/public/style.css">
<style>
    /* ================= ROOT VARIABLES ================= */
:root {
    --navy: #0b1c2d;
    --text: #1e293b;
    --accent: #6dbcf6;
    --success: #d4edda;
    --success-text: #155724;
    --info: #d1ecf1;
    --info-text: #0c5460;
    --warning: #fff3cd;
    --warning-text: #856404;
    --danger: #f8d7da;
    --danger-text: #721c24;
    --light: #f1f5f9;
    --bg: #f4f6f9;
}


/* ================= ALERTS ================= */
.alert {
    padding: 12px 15px;
    border-radius: 6px;
    margin-bottom: 20px;
    font-size: 0.85rem;
}

.alert-success { background: var(--success); color: var(--success-text); }
.alert-error { background: var(--danger); color: var(--danger-text); }
/* ================= FORM FIXES ================= */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-row {
    display: flex;
    gap: 10px;           /* smaller gap */
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
    flex: 1 1 100%;      /* take full width on small screens */
    min-width: 0;        /* prevent overflow */
    margin-bottom: 0;    /* remove extra margin */
}

.form-group label {
    margin-bottom: 4px;   /* small gap above input */
    font-weight: 600;
    font-size: 0.85rem;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px 10px;
    font-size: 0.85rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    box-sizing: border-box;
}

/* Checkbox fix */
.form-group input[type="checkbox"] {
    width: auto;
    margin: 0;
}

/* Form actions buttons */
.form-actions {
    display: flex;
    gap: 10px;        /* smaller gap between buttons */
    flex-wrap: wrap;
}

/* Buttons */
.btn {
    padding: 8px 14px;
    font-size: 0.85rem;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    width:120px;
    text-align:center;
    
}

/* Primary / Secondary */
.btn-primary { background: var(--accent); color: var(--navy); border: none; }
.btn-secondary { background: #e5e7eb; color: var(--text); border: none; }

/* Hover effect */
.btn:hover {
    transform: translateY(-2px);
    transition: 0.3s;
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media(max-width: 991px){
    .form-row { flex-direction: column; gap: 15px; }
    .form-actions { flex-direction: column; }
}

/* Mobile */
@media(max-width: 576px){
    .main-content { padding: 15px; }
    .form-group input,
    .form-group select,
    .form-actions .btn { font-size: 0.8rem; padding: 7px 10px; }
}

/* Super small devices */
@media(max-width: 400px){
    .form-group input,
    .form-group select,
    .form-actions .btn { font-size: 0.75rem; padding: 6px 8px; }
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
                <li><a href="add.php" class="active">Add User</a></li>
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
                <h1>Add New User</h1>
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
                            <input type="text" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Email *</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>User Type *</label>
                            <select name="user_type" required>
                                <option value="">Select User Type</option>
                                <option value="Admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="Tester" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Tester') ? 'selected' : ''; ?>>Tester</option>
                                <option value="Supervisor" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; align-items: center; gap: 10px;">
                                <input type="checkbox" name="is_active" <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                                Active User
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Add User</button>
                        <a href="list.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
