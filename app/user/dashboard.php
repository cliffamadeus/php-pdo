<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../config/functions.php';

requireRole('user');

$userId = $_SESSION['user_id'];

// Get user's activity statistics
$stmt = $pdo->prepare("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM activity_logs 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
$stmt->execute([$userId]);
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action distribution for user
$stmt = $pdo->prepare("
    SELECT action, COUNT(*) as count 
    FROM activity_logs 
    WHERE user_id = ? 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY action 
    ORDER BY count DESC
");
$stmt->execute([$userId]);
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total activity count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM activity_logs WHERE user_id = ?");
$stmt->execute([$userId]);
$totalActivities = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get last login
$stmt = $pdo->prepare("
    SELECT created_at 
    FROM activity_logs 
    WHERE user_id = ? AND action = 'login' AND status = 'success'
    ORDER BY created_at DESC 
    LIMIT 1, 1
");
$stmt->execute([$userId]);
$lastLogin = $stmt->fetch(PDO::FETCH_ASSOC);

$title = 'User Dashboard';
renderHeader($title);
?>

<!-- DataTables CSS -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.stat-card {
    background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}
.stat-card h3 {
    margin: 0;
    font-size: 32px;
}
.stat-card p {
    margin: 5px 0 0;
    opacity: 0.9;
}
.chart-container {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin: 20px 0;
}
.chart-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 20px;
    margin: 20px 0;
}
table.dataTable {
    width: 100% !important;
}
.badge-success { background: #28a745; }
.badge-failed { background: #dc3545; }
</style>

<div class="nav" style="padding-bottom:15px;">
    <a href="<?php echo BASE_URL; ?>/app/users/dashboard.php">User Management |</a>
    <a href="<?php echo BASE_URL; ?>/app/users/user-create.php">Create User |</a>
    <a href="<?php echo BASE_URL; ?>/app/auth/signout.php">Logout</a>
</div>

<div class="card">
    <h2>User Dashboard</h2>
    <p style="color: #666; margin-top: 10px;">
        <strong>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?></strong><br>
        Role: <span class="badge badge-user">User</span>
    </p>
</div>

<h2>Your Activity Statistics</h2>

<div class="stat-grid">
    <div class="stat-card" style="background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);">
        <h3><?php echo $totalActivities; ?></h3>
        <p>Total Activities</p>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);">
        <h3><?php echo count($actionStats); ?></h3>
        <p>Action Types</p>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #ab47bc 0%, #8e24aa 100%);">
        <h3><?php echo $lastLogin ? date('M d', strtotime($lastLogin['created_at'])) : 'N/A'; ?></h3>
        <p>Last Login</p>
    </div>
</div>

<h2>Your Activity Analytics</h2>

<div class="chart-grid">
    <div class="chart-container">
        <h3>Your Daily Activity (Last 7 Days)</h3>
        <canvas id="dailyActivityChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Your Actions Breakdown</h3>
        <canvas id="actionChart"></canvas>
    </div>
</div>

<div class="card">
    <h2>Account Information</h2>
    <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
        <li><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['email']); ?></li>
        <li><strong>Role:</strong> User</li>
        <li><strong>Access Level:</strong> View own profile and activity only</li>
        <li><strong>Features:</strong>
            <ul style="margin-top: 5px;">
                <li>View your activity history</li>
                <li>Update your profile</li>
                <li>Change your password</li>
            </ul>
        </li>
    </ul>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Daily Activity Chart
const dailyCtx = document.getElementById('dailyActivityChart').getContext('2d');
const dailyData = <?php echo json_encode($dailyActivity); ?>;
new Chart(dailyCtx, {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.date),
        datasets: [{
            label: 'Activities',
            data: dailyData.map(d => d.count),
            borderColor: 'rgb(102, 187, 106)',
            backgroundColor: 'rgba(102, 187, 106, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});

// Action Distribution Chart
const actionCtx = document.getElementById('actionChart').getContext('2d');
const actionData = <?php echo json_encode($actionStats); ?>;
new Chart(actionCtx, {
    type: 'doughnut',
    data: {
        labels: actionData.map(d => d.action),
        datasets: [{
            data: actionData.map(d => d.count),
            backgroundColor: [
                'rgba(102, 187, 106, 0.7)',
                'rgba(66, 165, 245, 0.7)',
                'rgba(171, 71, 188, 0.7)',
                'rgba(255, 167, 38, 0.7)',
                'rgba(239, 83, 80, 0.7)',
                'rgba(38, 198, 218, 0.7)',
                'rgba(255, 202, 40, 0.7)',
                'rgba(156, 39, 176, 0.7)',
                'rgba(0, 150, 136, 0.7)',
                'rgba(121, 134, 203, 0.7)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true
    }
});

</script>

<?php renderFooter(); ?>