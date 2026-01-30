<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireLogin();

$userId = $_GET['user_id'] ?? 0;
$message = '';
$success = false;

// Get user details BEFORE deletion
$stmt = $pdo->prepare("SELECT id, email, role FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($user) {
        try {
            // Log deletion BEFORE actually deleting (so we have user info)
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_deleted', 'success');
            logActivity($pdo, $userId, $user['email'], 'account_deleted', 'success');
            
            // Now delete the user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            if ($stmt->rowCount() > 0) {
                redirect('/app/users/dashboard.php');
            } else {
                $message = "User not found.";
            }
        } catch(PDOException $e) {
            $message = "Error deleting user: " . $e->getMessage();
            
            // Log failed deletion
            logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_deleted', 'failed');
        }
    }
}

renderHeader('Delete User');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">Back to Users</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>Delete User</h1>

<?php if ($message): ?>
    <div class="error"><?php echo htmlspecialchars($message); ?></div>
<?php endif; ?>

<?php if ($user): ?>
    <p>Are you sure you want to delete this user?</p>
    
    <div class="info-box">
        <strong>User Details:</strong><br>
        Email: <?php echo htmlspecialchars($user['email']); ?><br>
        Role: <?php echo htmlspecialchars($user['role']); ?>
    </div>
    
    <form method="POST">
        <button type="submit" style="background: #dc3545;">Delete User</button>
        <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">
            <button type="button" style="background: #6c757d;">Cancel</button>
        </a>
    </form>
<?php else: ?>
    <p>User not found.</p>
<?php endif; ?>

<?php renderFooter(); ?>