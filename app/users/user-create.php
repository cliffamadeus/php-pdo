<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
requireLogin();

$currentRole = $_SESSION['role'];

// Only admin and manager can create users
if ($currentRole !== 'admin' && $currentRole !== 'manager') {
    die("Access denied. Only administrators and managers can create users.");
}

$message = '';
$success = false;
$verificationLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'user';
    $send_email = isset($_POST['send_verification_email']) ? true : false;

    // Role restrictions based on current user's role
    if ($currentRole === 'manager') {
        $role = 'user';
    } elseif ($currentRole === 'admin') {
        if (!in_array($role, ['admin', 'manager', 'user'])) {
            $role = 'user';
        }
    }

    try {
        // Email verification defaults
        $email_verified = 1;
        $verification_token = null;
        $verification_expires = null;

        if ($send_email) {
            $email_verified = 0;
            $verification_token = bin2hex(random_bytes(32));
            $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        }

        // Insert user into database
        $stmt = $pdo->prepare("
            INSERT INTO users 
                (email, password, role, verification_token, is_verified, email_verification_expires, created_at) 
            VALUES 
                (:email, :password, :role, :token, :verified, :expires, NOW())
        ");

        $stmt->execute([
            ':email'    => $email,
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':role'     => $role,
            ':token'    => $verification_token,
            ':verified' => $email_verified,
            ':expires'  => $verification_expires
        ]);

        // Send verification email if requested
        if ($send_email) {
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
                        <p>An account was created for this email. Please verify your email address.</p>
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

            // Set headers for HTML email
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@ics-dev.io" . "\r\n";

            if (mail($email, $email_subject, $email_body, $headers)) {
                $message = "User created successfully! Verification email sent.";
            } else {
                $message = "User created successfully! Verification email failed to send.";
            }
        } else {
            $message = "User created successfully! (Email verification skipped)";
        }

        $success = true;

    } catch(PDOException $e) {
        $message = "Error creating user: " . $e->getMessage();
    }
}

$title = 'Create User';
renderHeader($title);
?>


<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Create New User</h2>
        <span class="badge badge-<?php echo $currentRole; ?>"><?php echo ucfirst($currentRole); ?></span>
    </div>

    <?php if ($currentRole === 'manager'): ?>
        <div class="info-box" style="background: #fff9c4; border-left-color: #ffa726;">
            <strong>Manager Access:</strong> You can only create regular users.
        </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="<?php echo $success ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($verificationLink): ?>
        <div class="info-box">
            <strong>📧 Email Verification Link (for testing):</strong><br>
            <a href="<?php echo $verificationLink; ?>" target="_blank"><?php echo $verificationLink; ?></a>
            <p style="margin-top: 10px; font-size: 13px; color: #666;">
                In production, this link will be sent via email. The email has been queued.
            </p>
        </div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="email">Email Address:</label>
            <input type="email" id="email" name="email" required placeholder="user@example.com">
        </div>
        
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required placeholder="Enter password">
        </div>
        
        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role" <?php echo $currentRole === 'manager' ? 'disabled' : ''; ?>>
                <option value="user">User</option>
                <?php if ($currentRole === 'admin'): ?>
                    <option value="manager">Manager</option>
                    <option value="admin">Admin</option>
                <?php endif; ?>
            </select>
            <?php if ($currentRole === 'manager'): ?>
                <input type="hidden" name="role" value="user">
                <small style="color: #666;">Managers can only create regular users</small>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" name="send_verification_email" value="1" checked 
                       style="width: auto; margin-right: 10px;">
                <span>Send email verification (recommended)</span>
            </label>
            <small style="color: #666; margin-left: 30px;">
                If unchecked, user will be verified immediately without email confirmation.
            </small>
        </div>
        
        <button type="submit">
            <span class="material-icons" style="vertical-align: middle; font-size: 18px;">person_add</span>
            Create User
        </button>
    </form>
</div>

<?php renderFooter(); ?>