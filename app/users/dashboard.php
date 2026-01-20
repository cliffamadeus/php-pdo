<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
requireLogin();

// Role-based filtering
$currentRole = $_SESSION['role'];
$currentUserId = $_SESSION['user_id'];

// Build query based on role hierarchy
if ($currentRole === 'admin') {
    // Admin can see all users
    $stmt = $pdo->prepare("SELECT id, email, role, is_verified, created_at FROM users ORDER BY 
        CASE role 
            WHEN 'admin' THEN 1 
            WHEN 'manager' THEN 2 
            WHEN 'user' THEN 3 
        END, created_at DESC");
    $stmt->execute();
} elseif ($currentRole === 'manager') {
    // Manager can only see regular users (hide admins and other managers)
    $stmt = $pdo->prepare("SELECT id, email, role, is_verified, created_at FROM users 
        WHERE role = 'user' ORDER BY created_at DESC");
    $stmt->execute();
} else {
    // Regular users can only see themselves
    $stmt = $pdo->prepare("SELECT id, email, role, is_verified, created_at FROM users 
        WHERE id = ? ORDER BY created_at DESC");
    $stmt->execute([$currentUserId]);
}

$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check permissions for actions
function canEdit($targetRole, $targetUserId) {
    global $currentRole, $currentUserId;
    
    // Can't edit yourself (except admin can edit own profile but not delete)
    if ($targetUserId == $currentUserId) {
        return true; // Can edit but delete will be restricted separately
    }
    
    if ($currentRole === 'admin') {
        return true; // Admin can edit anyone
    } elseif ($currentRole === 'manager') {
        return $targetRole === 'user'; // Manager can only edit users
    }
    
    return false;
}

function canDelete($targetRole, $targetUserId) {
    global $currentRole, $currentUserId;
    
    // Nobody can delete their own account
    if ($targetUserId == $currentUserId) {
        return false;
    }
    
    if ($currentRole === 'admin') {
        return true; // Admin can delete anyone except self
    } elseif ($currentRole === 'manager') {
        return $targetRole === 'user'; // Manager can only delete users
    }
    
    return false;
}

renderHeader('User Management');
?>

<div class="nav">
    <a href="<?php echo BASE_URL; ?>/app/<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a>
    <?php if ($currentRole === 'admin' || $currentRole === 'manager'): ?>
        <a href="<?php echo BASE_URL; ?>/app/users/user-create.php">Create User</a>
    <?php endif; ?>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<h1>User Management</h1>

<div class="info-box">
    <strong>Your Role: <span class="badge badge-<?php echo $currentRole; ?>"><?php echo ucfirst($currentRole); ?></span></strong><br>
    <?php if ($currentRole === 'admin'): ?>
        You have full access to manage all users.
    <?php elseif ($currentRole === 'manager'): ?>
        You can manage regular users only. Admin and Manager accounts are hidden from your view.
    <?php else: ?>
        You can only view your own profile.
    <?php endif; ?>
</div>

<p>
    <?php if ($currentRole === 'admin'): ?>
        Showing all users (<?php echo count($users); ?> total)
    <?php elseif ($currentRole === 'manager'): ?>
        Showing users you can manage (<?php echo count($users); ?> regular users)
    <?php else: ?>
        Showing your profile
    <?php endif; ?>
</p>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Email</th>
            <th>Role</th>
            <th>Verified</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?php echo $user['id']; ?></td>
            <td><?php echo htmlspecialchars($user['email']); ?></td>
            <td>
                <span class="badge badge-<?php echo $user['role']; ?>">
                    <?php echo ucfirst($user['role']); ?>
                </span>
            </td>
            <td>
                <span class="badge badge-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                    <?php echo $user['is_verified'] ? 'Yes' : 'No'; ?>
                </span>
            </td>
            <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
            <td>
                <a href="user-view.php?user_id=<?php echo $user['id']; ?>">View</a>
                
                <?php if (canEdit($user['role'], $user['id'])): ?>
                    | <a href="user-update.php?user_id=<?php echo $user['id']; ?>">Edit</a>
                <?php endif; ?>
                
                <?php if (canDelete($user['role'], $user['id'])): ?>
                    | <a href="user-delete.php?user_id=<?php echo $user['id']; ?>" 
                         onclick="return confirm('Are you sure you want to delete this user?')" 
                         style="color: #dc3545;">Delete</a>
                <?php elseif ($user['id'] == $currentUserId): ?>
                    | <span style="color: #6c757d;" title="You cannot delete your own account">Delete</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php if (empty($users)): ?>
    <p style="text-align: center; padding: 40px; color: #6c757d;">
        No users to display based on your role permissions.
    </p>
<?php endif; ?>

<?php renderFooter(); ?>