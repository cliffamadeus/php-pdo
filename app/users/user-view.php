<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireLogin();

$userId = $_GET['user_id'] ?? 0;
$message = '';
$success = false;

// Handle resend verification email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_verification'])) {
    $verification_token = bin2hex(random_bytes(32));
    $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    try {
        // Update user with new token and expiry
        $stmt = $pdo->prepare("
            UPDATE users 
            SET verification_token = :token, 
                email_verification_expires = :expires 
            WHERE id = :id
        ");
        $stmt->execute([
            ':token' => $verification_token,
            ':expires' => $verification_expires,
            ':id' => $userId
        ]);
        
        // Get user email
        $stmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userEmail = $stmt->fetchColumn();
        
        if ($userEmail) {
            $verificationLink = BASE_URL . "/app/auth/verify-email.php?token=" . $verification_token;
            
            $email_subject = "Verify Your Email Address";
            
            $email_body = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: #1976d2; color: white; padding: 20px; text-align: center; }
                    .content { background: #f9f9f9; padding: 30px; }
                    .button { display: inline-block; padding: 12px 30px; background: #4caf50; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h2>Email Verification</h2>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>You requested a new verification link. Please verify your email address.</p>
                        <p style='text-align: center;'>
                            <a href='{$verificationLink}' class='button'>Verify Email</a>
                        </p>
                        <p>If the button doesn't work, copy and paste this link:</p>
                        <p style='word-break: break-all; color: #1976d2;'>{$verificationLink}</p>
                        <p>This link expires in 24 hours.</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; " . date('Y') . " User Management System</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            // Send email using PHP mail()
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@ics-dev.io" . "\r\n";

            if (mail($userEmail, $email_subject, $email_body, $headers)) {
                $message = "Verification email has been resent successfully! Link: <a href='$verificationLink' target='_blank'>$verificationLink</a>";
                $success = true;
                
                // Log successful resend
                logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'success');
                logActivity($pdo, $userId, $userEmail, 'verification_email_received', 'success');
            } else {
                $message = "Verification email failed to send. Please try again later.";
                $success = false;
                
                // Log failed resend
                logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'failed');
            }
        }
    } catch(PDOException $e) {
        $message = "Error resending verification: " . $e->getMessage();
        $success = false;
        
        // Log error
        logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'verification_email_resent', 'failed');
    }
}

// Get user details
$stmt = $pdo->prepare("SELECT id, email, role, is_verified, verification_token, email_verification_expires, created_at FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Log that this user profile was viewed
if ($user) {
    logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'user_viewed', 'success');
}

// Check if verification has expired
$verificationExpired = false;
if ($user && !$user['is_verified'] && $user['email_verification_expires']) {
    $verificationExpired = strtotime($user['email_verification_expires']) < time();
}

$title = 'View User';
renderHeader($title);
?>

<div class="card">
    <h2>User Details</h2>

    <?php if ($message): ?>
        <div class="<?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($user): ?>
        <table>
            <tr>
                <th style="width: 200px;">ID</th>
                <td><?php echo $user['id']; ?></td>
            </tr>
            <tr>
                <th>Email</th>
                <td><?php echo htmlspecialchars($user['email']); ?></td>
            </tr>
            <tr>
                <th>Role</th>
                <td>
                    <span class="badge badge-<?php echo $user['role']; ?>">
                        <?php echo ucfirst($user['role']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <th>Verified</th>
                <td>
                    <span class="badge badge-<?php echo $user['is_verified'] ? 'verified' : 'unverified'; ?>">
                        <?php echo $user['is_verified'] ? 'Yes' : 'No'; ?>
                    </span>
                    <?php if (!$user['is_verified'] && $verificationExpired): ?>
                        <span class="badge" style="background: #f44336; margin-left: 10px;">Expired</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!$user['is_verified'] && $user['email_verification_expires']): ?>
            <tr>
                <th>Verification Expires</th>
                <td>
                    <?php 
                    $expiresTime = strtotime($user['email_verification_expires']);
                    echo date('F j, Y, g:i a', $expiresTime);
                    
                    if ($verificationExpired) {
                        echo ' <span style="color: #f44336;">(Expired)</span>';
                    } else {
                        $timeLeft = $expiresTime - time();
                        $hoursLeft = floor($timeLeft / 3600);
                        echo ' <span style="color: #ffa726;">(' . $hoursLeft . ' hours remaining)</span>';
                    }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Created</th>
                <td><?php echo date('F j, Y, g:i a', strtotime($user['created_at'])); ?></td>
            </tr>
        </table>
        
        <?php if (!$user['is_verified']): ?>
            <div class="info-box" style="background: <?php echo $verificationExpired ? '#ffebee' : '#fff9c4'; ?>; border-left-color: <?php echo $verificationExpired ? '#f44336' : '#ffa726'; ?>; margin-top: 20px;">
                <strong><?php echo $verificationExpired ? '⚠️ Verification Link Expired' : '📧 Email Not Verified'; ?></strong><br>
                <?php if ($verificationExpired): ?>
                    This user's verification link has expired. Click the button below to send a new verification email.
                <?php else: ?>
                    This user has not verified their email address yet. You can resend the verification email if needed.
                <?php endif; ?>
                
                <form method="POST" style="margin-top: 15px;">
                    <button type="submit" name="resend_verification" style="background: <?php echo $verificationExpired ? '#f44336' : '#ffa726'; ?>;">
                        <span class="material-icons" style="vertical-align: middle; font-size: 18px;">email</span>
                        Resend Verification Email
                    </button>
                </form>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <a href="user-update.php?user_id=<?php echo $user['id']; ?>">
                <button>
                    <span class="material-icons" style="vertical-align: middle; font-size: 18px;">edit</span>
                    Edit User
                </button>
            </a>
            <a href="dashboard.php">
                <button style="background: #757575;">
                    <span class="material-icons" style="vertical-align: middle; font-size: 18px;">arrow_back</span>
                    Back to List
                </button>
            </a>
        </div>
    <?php else: ?>
        <div class="error">User not found.</div>
    <?php endif; ?>
</div>

<?php renderFooter(); ?>