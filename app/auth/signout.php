<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';

// Log logout before destroying session
if (isset($_SESSION['user_id']) && isset($_SESSION['email'])) {
    logActivity($pdo, $_SESSION['user_id'], $_SESSION['email'], 'logout', 'success');
}

session_destroy();
redirect('/index.php');
?>