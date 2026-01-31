<?php
// login.php
session_start();

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'Tester') {
        header("Location: tester/index.php");
    } elseif ($_SESSION['user_type'] == 'Admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: supervisor/index.php");
    }
    exit();
}

require_once 'config/database.php';
require_once 'includes/functions.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']);
    
    $query = "SELECT * FROM users WHERE username = ? AND password = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        
        // Update last login
        $update_query = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        
        // Log the login
        logActivity($conn, $user['user_id'], "Logged in", null, null);
        
        // Redirect based on user type
        if ($user['user_type'] == 'Tester') {
            header("Location: tester/index.php");
        } elseif ($user['user_type'] == 'Admin') {
            header("Location: admin/index.php");
        } else {
            header("Location: supervisor/index.php");
        }
        exit();
    } else {
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Lab Automation System</title>


<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
    background: #f3f5f9;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    padding: 20px;
}

/* =========================
   THEME VARIABLES
========================= */
:root {
    --accent: #0cb0f8;
    --card-bg: #ffffff;
    --shadow: rgba(0,0,0,0.1);
    --hover-shadow: rgba(0,0,0,0.2);
    --transition: 0.25s ease;
}

/* =========================
   LOGIN CONTAINER
========================= */
.login-container {
    background: var(--card-bg);
    padding: 35px 25px;
    border-radius: 12px;
    box-shadow: 0 10px 30px var(--shadow);
    width: 100%;
    max-width: 380px;
    text-align: center;
    transition: transform var(--transition), box-shadow var(--transition);
}

.login-container:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px var(--hover-shadow);
}

/* =========================
   LOGO
========================= */
.logo {
    width: 70px;
    height: 70px;
    background: var(--accent);
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    font-weight: bold;
    margin: 0 auto 15px;
    transition: transform var(--transition);
}

.logo:hover {
    transform: scale(1.1) rotate(5deg);
}

/* =========================
   SECTION TITLE
========================= */
.section-title {
    font-size: 1.8rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 8px;
    position: relative;
}

.section-title::after {
    content: "";
    display: block;
    width: 40px;
    height: 3px;
    background: var(--accent);
    border-radius: 2px;
    margin-top: 6px;
    transition: width var(--transition);
}

.section-title:hover::after {
    width: 60px;
}

/* =========================
   SUBTITLE
========================= */
.login-header p {
    font-size: 13px;
    color: rgba(30,41,59,0.6);
    margin-bottom: 20px;
}

/* =========================
   FORM
========================= */
.form-group {
    margin-bottom: 18px;
    text-align: left;
}

.form-group label {
    font-size: 13px;
    font-weight: 500;
    margin-bottom: 5px;
    display: block;
    color: #1e293b;
}

.form-group input {
    width: 100%;
    padding: 10px 12px;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    font-size: 14px;
    transition: all var(--transition);
}

.form-group input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 8px rgba(12,176,248,0.3);
    outline: none;
}

/* =========================
   LOGIN BUTTON
========================= */
.btn-login {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 6px;
    background: var(--accent);
    color: #fff;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all var(--transition);
}

.btn-login:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(12,176,248,0.4);
}

/* =========================
   ERROR MESSAGE
========================= */
.alert-error {
    background: #f8d7da;
    color: #721c24;
    padding: 10px 12px;
    border-radius: 6px;
    margin-bottom: 15px;
    font-size: 13px;
}

/* =========================
   FOOTER
========================= */
.login-footer {
    font-size: 12px;
    color: rgba(30,41,59,0.5);
    margin-top: 20px;
}

/* =========================
   TEST CREDENTIALS BOX
========================= */
.test-credentials {
    background: rgba(12,176,248,0.1);
    padding: 12px;
    border-radius: 6px;
    font-size: 12px;
    margin-bottom: 15px;
}

.test-credentials code {
    background: #fff;
    padding: 2px 4px;
    border-radius: 3px;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 500px) {
    .login-container { padding: 25px 15px; }
    .section-title { font-size: 1.5rem; }
    .btn-login { font-size: 14px; padding: 10px; }
    .form-group input { font-size: 13px; padding: 8px 10px; }
    .logo { width: 60px; height: 60px; font-size: 24px; }
}

</style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">L</div>
            <h1>Lab Automation System</h1>
            <p>SRS Electrical Appliances</p>
        </div>

        <?php if ($error_message): ?>
        <div class="alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>


        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="login-footer">
            <p>&copy; 2024 SRS Electrical Appliances. All rights reserved.</p>
        </div>
    </div>
</body>
</html>