<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'sample_php_pdo');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Email configuration (for verification)
define('MAIL_FROM', 'noreply@yourdomain.com');
define('BASE_URL', 'http://localhost/php-pdo');

function redirect($path) {
    header("Location: " . BASE_URL . $path);
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/index.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        die("Access denied. Required role: $role");
    }
}

function renderHeader($title) {
    $currentRole = $_SESSION['role'] ?? 'guest';
    $currentEmail = $_SESSION['email'] ?? '';
    $isLoggedIn = isset($_SESSION['user_id']);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <!-- Material Icons -->
        <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
        <!-- Google Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Roboto', Arial, sans-serif; 
                background: #f5f5f5;
                display: flex;
                min-height: 100vh;
            }
            
            /* Sidebar */
            .sidebar {
                width: 250px;
                background: #1976d2;
                color: white;
                position: fixed;
                height: 100vh;
                overflow-y: auto;
                box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            }
            .sidebar-header {
                padding: 20px;
                background: #1565c0;
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .sidebar-header h2 {
                font-size: 20px;
                font-weight: 500;
            }
            .sidebar-user {
                padding: 15px 20px;
                background: rgba(0,0,0,0.1);
                border-bottom: 1px solid rgba(255,255,255,0.1);
            }
            .sidebar-user-email {
                font-size: 13px;
                opacity: 0.9;
                margin-bottom: 5px;
            }
            .sidebar-user-role {
                display: inline-block;
                padding: 4px 12px;
                background: rgba(255,255,255,0.2);
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            .sidebar-menu {
                padding: 10px 0;
            }
            .sidebar-menu a {
                display: flex;
                align-items: center;
                padding: 12px 20px;
                color: white;
                text-decoration: none;
                transition: background 0.2s;
            }
            .sidebar-menu a:hover {
                background: rgba(255,255,255,0.1);
            }
            .sidebar-menu a.active {
                background: rgba(255,255,255,0.2);
            }
            .sidebar-menu a .material-icons {
                margin-right: 12px;
                font-size: 20px;
            }
            
            /* Main Content */
            .main-content {
                margin-left: 250px;
                flex: 1;
                display: flex;
                flex-direction: column;
            }
            
            /* Navbar */
            .navbar {
                background: white;
                padding: 15px 30px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .navbar h1 {
                font-size: 24px;
                font-weight: 400;
                color: #333;
            }
            .navbar-actions {
                display: flex;
                gap: 10px;
                align-items: center;
            }
            
            /* Container */
            .container {
                flex: 1;
                padding: 30px;
                max-width: 1400px;
                width: 100%;
            }
            
            /* Footer */
            .footer {
                background: white;
                padding: 20px 30px;
                text-align: center;
                color: #666;
                font-size: 14px;
                box-shadow: 0 -2px 4px rgba(0,0,0,0.05);
            }
            
            /* Material Design Components */
            h1 { color: #333; margin-bottom: 20px; font-weight: 400; }
            h2 { color: #555; margin: 20px 0 10px; font-weight: 400; }
            h3 { color: #666; margin: 15px 0 10px; font-weight: 400; }
            
            .card {
                background: white;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                padding: 20px;
                margin-bottom: 20px;
            }
            
            .form-group { margin-bottom: 20px; }
            label { 
                display: block; 
                margin-bottom: 8px; 
                color: #666; 
                font-weight: 500;
                font-size: 14px;
            }
            input[type="text"], input[type="email"], input[type="password"], select { 
                width: 100%; 
                padding: 12px; 
                border: 1px solid #ddd; 
                border-radius: 4px; 
                font-size: 14px;
                font-family: 'Roboto', Arial, sans-serif;
                transition: border-color 0.2s;
            }
            input:focus, select:focus {
                outline: none;
                border-color: #1976d2;
            }
            
            button { 
                background: #1976d2; 
                color: white; 
                padding: 10px 24px; 
                border: none; 
                border-radius: 4px; 
                cursor: pointer; 
                font-size: 14px;
                font-weight: 500;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                transition: background 0.2s, box-shadow 0.2s;
            }
            button:hover { 
                background: #1565c0;
                box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            }
            
            .error { 
                background: #ffebee; 
                color: #c62828; 
                padding: 12px 16px; 
                border-radius: 4px; 
                margin-bottom: 20px;
                border-left: 4px solid #c62828;
            }
            .success { 
                background: #e8f5e9; 
                color: #2e7d32; 
                padding: 12px 16px; 
                border-radius: 4px; 
                margin-bottom: 20px;
                border-left: 4px solid #2e7d32;
            }
            
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-top: 20px;
                background: white;
                border-radius: 4px;
                overflow: hidden;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            table th, table td { 
                padding: 16px; 
                text-align: left; 
                border-bottom: 1px solid #e0e0e0; 
            }
            table th { 
                background: #f5f5f5; 
                color: #666;
                font-weight: 500;
                font-size: 14px;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            table tr:last-child td {
                border-bottom: none;
            }
            table tr:hover {
                background: #fafafa;
            }
            
            .badge { 
                display: inline-block; 
                padding: 4px 12px; 
                border-radius: 12px; 
                font-size: 12px;
                font-weight: 500;
            }
            .badge-admin { background: #ef5350; color: white; }
            .badge-manager { background: #ffa726; color: white; }
            .badge-user { background: #66bb6a; color: white; }
            .badge-verified { background: #66bb6a; color: white; }
            .badge-unverified { background: #bdbdbd; color: white; }
            
            .info-box { 
                background: #e3f2fd; 
                padding: 16px; 
                border-left: 4px solid #1976d2; 
                margin: 20px 0;
                border-radius: 4px;
            }
            
            .stat-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 20px;
                margin: 20px 0;
            }
            .stat-card {
                background: white;
                padding: 24px;
                border-radius: 4px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .stat-card h3 {
                font-size: 36px;
                margin: 0;
                font-weight: 300;
            }
            .stat-card p {
                margin: 8px 0 0;
                color: #666;
                font-size: 14px;
            }
            
            a {
                color: #1976d2;
                text-decoration: none;
            }
            a:hover {
                text-decoration: underline;
            }
        </style>
    </head>
    <body>
        <?php if ($isLoggedIn): ?>
            <?php include __DIR__ . '/components/sidebar.php'; ?>
            <div class="main-content">
                <?php include __DIR__ . '/components/navbar.php'; ?>
                <div class="container">
        <?php else: ?>
            <div class="container" style="margin: 0 auto; max-width: 500px; padding-top: 100px;">
        <?php endif; ?>
    <?php
}

function renderFooter() {
    $isLoggedIn = isset($_SESSION['user_id']);
    ?>
        <?php if ($isLoggedIn): ?>
                </div>
                <?php include __DIR__ . '/components/footer.php'; ?>
            </div>
        <?php else: ?>
            </div>
        <?php endif; ?>
    </body>
    </html>
    <?php
}
