<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
requireRole('admin');

// Get statistics
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'admin'");
$totalAdmins = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'manager'");
$totalManagers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalRegularUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

renderHeader('Admin Dashboard');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">User Management</a>
    <a href="<?php echo BASE_URL; ?>/app/users/user-create.php">Create User</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>Admin hehe Dashboard</h1>

<div class="info-box">
    <strong>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?></strong><br>
    Role: <span class="badge badge-admin">Admin</span>
</div>

<h2>System Statistics</h2>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0;">
    <div style="background: #007bff; color: white; padding: 20px; border-radius: 8px;">
        <h3 style="margin: 0; font-size: 32px; font-color"><?php echo $totalUsers; ?></h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Total Users</p>
    </div>
    <div style="background: #dc3545; color: white; padding: 20px; border-radius: 8px;">
        <h3 style="margin: 0; font-size: 32px;"><?php echo $totalAdmins; ?></h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Admins</p>
    </div>
    <div style="background: #ffc107; color: #333; padding: 20px; border-radius: 8px;">
        <h3 style="margin: 0; font-size: 32px;"><?php echo $totalManagers; ?></h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Managers</p>
    </div>
    <div style="background: #28a745; color: white; padding: 20px; border-radius: 8px;">
        <h3 style="margin: 0; font-size: 32px;"><?php echo $totalRegularUsers; ?></h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Regular Users</p>
    </div>
    <div style="background: #6c757d; color: white; padding: 20px; border-radius: 8px;">
        <h3 style="margin: 0; font-size: 32px;"><?php echo $unverifiedUsers; ?></h3>
        <p style="margin: 5px 0 0; opacity: 0.9;">Unverified</p>
    </div>
</div>

<h2>Admin Capabilities:</h2>
<ul>
    <li><strong>Full System Control:</strong> Manage all users, roles, and permissions</li>
    <li><strong>Create/Edit/Delete:</strong> All user types (Admin, Manager, User)</li>
    <li><strong>View Everything:</strong> Access to all dashboards and data</li>
    <li><strong>System Configuration:</strong> Manage application settings</li>
    <li><strong>Restriction:</strong> Cannot delete own account (safety measure)</li>
</ul>

<h2>Access Hierarchy</h2>
<div class="info-box">
    <strong>Admin (You) → Full Control</strong><br>
    ↓ Can manage Managers<br>
    <strong>Manager → Limited Control</strong><br>
    ↓ Can manage Users only<br>
    <strong>User → No Management Rights</strong><br>
    ↓ Can only view own profile
</div>

<?php renderFooter(); ?>
