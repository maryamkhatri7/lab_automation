<?php
// tester/profile.php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'Tester') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get user details
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    $update_query = "UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("sssi", $full_name, $email, $phone, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        logActivity($conn, $user_id, "Updated profile", "users", $user_id);
    } else {
        $error_message = "Error updating profile.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (md5($current_password) == $user['password']) {
        if ($new_password == $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed_password = md5($new_password);
                $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully!";
                    logActivity($conn, $user_id, "Changed password", "users", $user_id);
                } else {
                    $error_message = "Error changing password.";
                }
            } else {
                $error_message = "Password must be at least 6 characters long.";
            }
        } else {
            $error_message = "New passwords do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

// Get activity statistics
$activity_query = "SELECT COUNT(*) as total FROM tests WHERE tester_id = ?";
$stmt = $conn->prepare($activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_tests = $stmt->get_result()->fetch_assoc()['total'];

$recent_activity_query = "SELECT action, timestamp FROM user_logs WHERE user_id = ? ORDER BY timestamp DESC LIMIT 10";
$stmt = $conn->prepare($recent_activity_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_activities = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Lab Automation System</title>
<link rel="stylesheet" href="../supervisor/public/style.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Tester Panel</h2>
                <p class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></p>
                <p class="user-role">Tester</p>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="my_tests.php">My Tests</a></li>
                <li><a href="new_test.php">Create New Test</a></li>
                <li><a href="pending_tests.php">Pending Tests</a></li>
                <li><a href="search_products.php">Search Products</a></li>
                <li><a href="search_tests.php">Search Tests</a></li>
                <li><a href="test_history.php">Test History</a></li>
                <li><a href="profile.php" class="active">My Profile</a></li>
                <li class="logout"><a href="../logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
        <div class="page-header">
            <h1>My Profile</h1>
            <a href="index.php" class="btn-back">← Dashboard</a>
        </div>

        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="profile-grid">
            <!-- Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="profile-role">Tester</div>
                
                <div class="profile-stat">
                    <div class="label">Total Tests</div>
                    <div class="value"><?php echo $total_tests; ?></div>
                </div>
                
                <div class="profile-stat">
                    <div class="label">Member Since</div>
                    <div class="value" style="font-size: 14px;">
                        <?php echo date('M Y', strtotime($user['created_at'])); ?>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div>
                <!-- Profile Information -->
                <div class="content-section">
                    <h2 class="section-title">Profile Information</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Full Name</label>
                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="content-section">
                    <h2 class="section-title">Change Password</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Current Password</label>
                            <input type="password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-section">
            <h2 class="section-title">Recent Activity</h2>
            <ul class="activity-list">
                <?php while ($activity = $recent_activities->fetch_assoc()): ?>
                <li class="activity-item">
                    <span class="activity-text"><?php echo htmlspecialchars($activity['action']); ?></span>
                    <span class="activity-time"><?php echo date('M d, Y h:i A', strtotime($activity['timestamp'])); ?></span>
                </li>
                <?php endwhile; ?>
            </ul>
        </div>        </main>    </div>
</body>
</html>