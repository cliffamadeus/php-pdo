<?php
require_once '../../config/config.php';
require_once '../../config/functions.php';
require_once '../../includes/activity-logger.php';
requireRole('manager');

// Get statistics for manager
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user' AND is_verified = 0");
$unverifiedUsers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Get activity statistics for manager and users only
$stmt = $pdo->query("
    SELECT DATE(al.created_at) as date, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY DATE(al.created_at)
    ORDER BY date ASC
");
$dailyActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get action distribution for manager and users
$stmt = $pdo->query("
    SELECT al.action, COUNT(*) as count 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE al.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND (u.role IN ('manager', 'user') OR u.role IS NULL)
    GROUP BY al.action 
    ORDER BY count DESC 
    LIMIT 10
");
$actionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Manager Dashboard';
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
    background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
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
    <h2>Manager Dashboard</h2>
    <p style="color: #666; margin-top: 10px;">
        <strong>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?></strong><br>
        Role: <span class="badge badge-manager">Manager</span>
    </p>
</div>

<h2>User Statistics</h2>

<div class="stat-grid">
    <div class="stat-card" style="background: linear-gradient(135deg, #66bb6a 0%, #43a047 100%);">
        <h3><?php echo $totalUsers; ?></h3>
        <p>Total Users</p>
    </div>
    <div class="stat-card" style="background: linear-gradient(135deg, #bdbdbd 0%, #9e9e9e 100%);">
        <h3><?php echo $unverifiedUsers; ?></h3>
        <p>Unverified Users</p>
    </div>
</div>

<h2>Activity Analytics (Manager & Users Only)</h2>

<div class="chart-grid">
    <div class="chart-container">
        <h3>Daily Activity Trend (Last 7 Days)</h3>
        <canvas id="dailyActivityChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Top Actions</h3>
        <canvas id="actionChart"></canvas>
    </div>
</div>

<div class="card">
    <h2>Manager Capabilities</h2>
    <ul style="margin: 15px 0 0 20px; line-height: 1.8;">
        <li><strong>Manage Users:</strong> Create, edit, and delete regular users only</li>
        <li><strong>View Reports:</strong> Access user data and analytics</li>
        <li><strong>Team Management:</strong> Oversee user activities</li>
        <li><strong>Restrictions:</strong>
            <ul style="margin-top: 5px;">
                <li>Cannot view Admin activities</li>
                <li>Cannot manage Admins or other Managers</li>
                <li>Cannot delete own account</li>
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
            borderColor: 'rgb(255, 167, 38)',
            backgroundColor: 'rgba(255, 167, 38, 0.1)',
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
    type: 'bar',
    data: {
        labels: actionData.map(d => d.action),
        datasets: [{
            label: 'Count',
            data: actionData.map(d => d.count),
            backgroundColor: [
                'rgba(255, 167, 38, 0.7)',
                'rgba(251, 140, 0, 0.7)',
                'rgba(255, 193, 7, 0.7)',
                'rgba(255, 152, 0, 0.7)',
                'rgba(255, 179, 0, 0.7)',
                'rgba(255, 160, 0, 0.7)',
                'rgba(251, 192, 45, 0.7)',
                'rgba(255, 202, 40, 0.7)',
                'rgba(255, 214, 0, 0.7)',
                'rgba(255, 171, 64, 0.7)'
            ]
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

</script>

<?php renderFooter(); ?>