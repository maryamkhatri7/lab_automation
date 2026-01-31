<?php
// admin/config.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Admin') {
    header("Location: ../login.php");
    exit();
}

$success = '';
$error = '';

// Get system config
$config_query = "SELECT * FROM system_config ORDER BY config_key";
$configs = $conn->query($config_query)->fetch_all(MYSQLI_ASSOC);

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST['config'] as $config_id => $config_value) {
        $update_query = "UPDATE system_config SET config_value = ?, updated_by = ? WHERE config_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $config_value, $_SESSION['user_id'], $config_id);
        $stmt->execute();
    }
    logActivity($conn, $_SESSION['user_id'], "Updated system configuration", "system_config", null);
    $success = "System configuration updated successfully!";
    // Refresh configs
    $configs = $conn->query($config_query)->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Configuration - Admin Panel</title>
    <link rel="stylesheet" href="../supervisor/public/style.css">
<style>
    /* ================= SYSTEM CONFIG FORM ================= */
/* ================= SIDEBAR SCROLL FIX ================= */

/* Sidebar layout */
.sidebar{
    width:260px;
    background:var(--navy);
    color:#fff;
    height:100vh;
    top:0;
    flex-shrink:0;

    display:flex;
    flex-direction:column;
}

/* Header stays fixed */
.sidebar-header{
    padding:0 20px 20px;
    border-bottom:1px solid rgba(255,255,255,0.1);
    flex-shrink:0;
}

/* MENU SCROLL AREA */
.sidebar-menu{
    list-style:none;
    padding:20px 0;

    flex:1;
    overflow-y:auto;
    overflow-x:hidden;

    scrollbar-width: thin; /* Firefox */
    scrollbar-color: var(--accent) rgba(255,255,255,0.1);
}

/* ===== Cute scrollbar (Chrome / Edge / Safari) ===== */
.sidebar-menu::-webkit-scrollbar{
    width:6px;
}

.sidebar-menu::-webkit-scrollbar-track{
    background: rgba(255,255,255,0.05);
    border-radius:10px;
}

.sidebar-menu::-webkit-scrollbar-thumb{
    background: var(--accent);
    border-radius:10px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover{
    background:#58a0e3;
}

/* Menu links */
.sidebar-menu a{
    display:block;
    padding:12px 20px;
    text-decoration:none;
    color:#ecf0f1;
    border-left:3px solid transparent;
    border-radius:6px;
    transition:0.3s;
}

.sidebar-menu a:hover,
.sidebar-menu a.active{
    background: rgba(255,255,255,0.05);
    border-left:3px solid var(--accent);
}
.content-section form{
    display:flex;
    flex-direction:column;
    gap:16px;
}

/* Each config row */
.content-section .form-group{
    display:flex;
    flex-direction:column;
    gap:6px;
}

/* Label */
.content-section .form-group label{
    font-size:0.85rem;
    font-weight:600;
    color:var(--text);
}

/* Input */
.content-section .form-group input[type="text"]{
    padding:8px 12px;
    font-size:0.85rem;
    border-radius:8px;
    border:1px solid #d1d5db;
    width:100%;
    transition:0.2s ease;
}

.content-section .form-group input:focus{
    outline:none;
    border-color:var(--accent);
    box-shadow:0 0 0 2px rgba(109,188,246,.25);
}

/* Description text */
.content-section .form-group .description{
    font-size:0.75rem;
    color:#64748b;
    line-height:1.4;
}

/* Form actions */
.form-actions{
    margin-top:20px;
    display:flex;
    justify-content:flex-end;
}

/* Save button */
.form-actions .btn{
    background:var(--accent);
    color:var(--navy);
    border:none;
    padding:10px 22px;
    font-size:0.9rem;
    font-weight:600;
    border-radius:10px;
    cursor:pointer;
    transition:0.3s ease;
}

.form-actions .btn:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 16px rgba(109,188,246,.35);
}

/* Alerts */
.alert{
    padding:12px 16px;
    border-radius:10px;
    font-size:0.85rem;
    margin-bottom:16px;
}

.alert-success{
    background:var(--success);
    color:var(--success-text);
}

.alert-error{
    background:var(--error);
    color:var(--error-text);
}

/* ================= RESPONSIVE ================= */

/* Tablet */
@media(max-width:991px){
    .content-section{
        padding:16px;
    }

    .form-actions{
        justify-content:center;
    }

    .form-actions .btn{
        width:120px;
    }
}

/* Mobile */
@media(max-width:576px){
    .content-section{
        padding:14px;
    }

    .content-section .form-group label{
        font-size:0.8rem;
    }

    .content-section .form-group input{
        font-size:0.8rem;
        padding:7px 10px;
    }

    .content-section .form-group .description{
        font-size:0.72rem;
    }

    .form-actions .btn{
        font-size:0.85rem;
        padding:10px;
    }
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
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products/list.php">Products</a></li>
                <li><a href="products/add.php">Add Product</a></li>
                <li><a href="tests/list.php">Tests</a></li>
                <li><a href="users/list.php">Users</a></li>
                <li><a href="users/add.php">Add User</a></li>
                <li><a href="cpri/list.php">CPRI Submissions</a></li>
                <li><a href="remanufacturing/list.php">Re-Manufacturing</a></li>
                <li><a href="reports/index.php">Reports</a></li>
                <li><a href="search.php">Advanced Search</a></li>
                <li><a href="config.php" class="active">System Config</a></li>
                <li><a href="logs.php">Activity Logs</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h1>System Configuration</h1>
            </div>

            <div class="content-section">
                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php foreach ($configs as $config): ?>
                    <div class="form-group">
                        <label><?php echo htmlspecialchars($config['config_key']); ?></label>
                        <input type="text" name="config[<?php echo $config['config_id']; ?>]" 
                               value="<?php echo htmlspecialchars($config['config_value']); ?>" required>
                        <?php if ($config['description']): ?>
                        <div class="description"><?php echo htmlspecialchars($config['description']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
