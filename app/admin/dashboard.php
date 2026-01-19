<?php
require_once '/../../config/config.php';
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

$title = 'Admin Dashboard';
renderHeader($title);
?>

<div class="card">
    <h2>Welcome, Administrator!</h2>
    <p style="color: #666; margin-top: 10px;">You have full system control and access to all features.</p>
</div>

<h2>System Statistics</h2>

<div class="stat-cards">
    <div class="stat-card" style="border-left: 4px solid #1976d2;">
        <h3><?php echo $totalUsers; ?></h3>
        <p>Total Users</p>
    </div>
    <div class="stat-card" style="border-left: 4px solid #ef5350;">
        <h3><?php echo $totalAdmins; ?></h3>
        <p>Admins</p>
    </div>
    <div class="stat-card" style="border-left: 4px solid #ffa726;">
        <h3><?php echo $totalManagers; ?></h3>
        <p>Managers</p>
    </div>
    <div class="stat-card" style="border-left: 4px solid #66bb6a;">
        <h3><?php echo $totalRegularUsers; ?></h3>
        <p>Regular Users</p>
    </div>
    <div class="stat-card" style="border-left: 4px solid #bdbdbd;">
        <h3><?php echo $unverifiedUsers; ?></h3>
        <p>Unverified</p>
    </div>
</div>

<div class="card">
    <h2>Admin Capabilities</h2>
    <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
        <li><strong>Full System Control:</strong> Manage all users, roles, and permissions</li>
        <li><strong>Create/Edit/Delete:</strong> All user types (Admin, Manager, User)</li>
        <li><strong>View Everything:</strong> Access to all dashboards and data</li>
        <li><strong>System Configuration:</strong> Manage application settings</li>
        <li><strong>Restriction:</strong> Cannot delete own account (safety measure)</li>
    </ul>
</div>

<div class="card">
    <h2>Access Hierarchy</h2>
    <div style="padding: 15px; background: #f5f5f5; border-radius: 4px;">
        <strong style="color: #ef5350;">Admin (You) → Full Control</strong><br>
        <span style="margin-left: 20px;">↓ Can manage Managers</span><br>
        <strong style="color: #ffa726;">Manager → Limited Control</strong><br>
        <span style="margin-left: 20px;">↓ Can manage Users only</span><br>
        <strong style="color: #66bb6a;">User → No Management Rights</strong><br>
        <span style="margin-left: 20px;">↓ Can only view own profile</span>
    </div>
</div>

<?php renderFooter(); ?>