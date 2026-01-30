<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireLogin();

$userId = $_GET['user_id'] ?? 0;
$message = '';
$success = false;

// Get user details
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    
    $updates = [];
    $params = [];
    $changes = []; // Track what changed for logging
    
    if ($email && $email !== $user['email']) {
        $updates[] = "email = ?";
        $params[] = $email;
        $changes[] = 'email';
    }
    
    if ($password) {
        $updates[] = "password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
        $changes[] = 'password';
    }
    
    if ($role && in_array($role, ['admin', 'manager', 'user']) && $role !== $user['role']) {
        $updates[] = "role = ?";
        $params[] = $role;
        $changes[] = 'role';
    }
    
    if (!empty($updates)) {
        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            $message = "User updated successfully!";
            $success = true;
            
            // Log the update action by the admin/manager
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_updated', 'success');
            
            // Log the change for the affected user
            $changesStr = implode(', ', $changes);
            logActivity($pdo, $userId, $user['email'], 'profile_updated', 'success');
            
            // Refresh user data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch(PDOException $e) {
            $message = "Error updating user: " . $e->getMessage();
            
            // Log failed update
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_updated', 'failed');
        }
    }
}

renderHeader('Update User');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">Back to Users</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>Update User</h1>

<?php if ($message): ?>
    <div class="<?php echo $success ? 'success' : 'error'; ?>">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<?php if ($user): ?>
    <form method="POST">
        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
        </div>
        
        <div class="form-group">
            <label for="password">Password (leave empty to keep current):</label>
            <input type="password" id="password" name="password">
        </div>
        
        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
        </div>
        
        <button type="submit">Update User</button>
    </form>
<?php else: ?>
    <p>User not found.</p>
<?php endif; ?>

<?php renderFooter(); ?>