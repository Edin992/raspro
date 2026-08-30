<?php
/**
 * admin/dashboard.php - Admin Dashboard
 */

// Ovo MORA biti prvo
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once __DIR__ . '/includes/auth.php';
requireAdmin();


$pageTitle = 'Dashboard';
$stats = getAdminStats();
$chartData = getChartData();
$topCategories = getTopCategories(5);
$topUsers = getTopUsers(5);

include __DIR__ . '/includes/header.php';
include __DIR__ . '/includes/sidebar.php';
?>

<!-- Statistics Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-primary">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($stats['total_users']); ?></h3>
                <p class="stat-card-label">Ukupno korisnika</p>
                <div class="stat-card-change text-success">
                    <i class="fas fa-arrow-up me-1"></i>+<?php echo $stats['new_users_today']; ?> danas
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-success">
                <i class="fas fa-tags"></i>
            </div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($stats['active_ads']); ?></h3>
                <p class="stat-card-label">Aktivni oglasi</p>
                <div class="stat-card-change text-success">
                    <i class="fas fa-arrow-up me-1"></i>+<?php echo $stats['new_ads_today']; ?> danas
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-warning">
                <i class="fas fa-crown"></i>
            </div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($stats['premium_ads']); ?></h3>
                <p class="stat-card-label">Premium oglasi</p>
                <div class="stat-card-change text-muted">
                    <i class="fas fa-chart-line me-1"></i><?php echo round(($stats['premium_ads'] / max($stats['active_ads'], 1)) * 100); ?>% od aktivnih
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-card-icon bg-info">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-card-info">
                <h3 class="stat-card-value"><?php echo number_format($stats['total_views']); ?></h3>
                <p class="stat-card-label">Ukupno pregleda</p>
                <div class="stat-card-change text-muted">
                    <i class="fas fa-chart-line me-1"></i>Svih vremena
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Aktivnosti u poslednjih 30 dana</h5>
                <div class="card-tools">
                    <button class="btn btn-sm btn-outline-secondary" id="refreshChart">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="activityChart" height="300"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Top kategorije</h5>
            </div>
            <div class="card-body">
                <?php foreach ($topCategories as $cat): ?>
                <div class="category-item">
                    <div class="category-info">
                        <i class="fas <?php echo htmlspecialchars($cat['icon'] ?? 'fa-folder'); ?>"></i>
                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                    </div>
                    <div class="category-stats">
                        <span class="badge bg-primary"><?php echo $cat['ads_count']; ?> oglasa</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Revenue and Top Users Row -->
<div class="row g-4 mb-4">
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Prihodi</h5>
            </div>
            <div class="card-body">
                <div class="revenue-stats">
                    <div class="revenue-total">
                        <span class="revenue-label">Ukupni prihodi</span>
                        <span class="revenue-amount"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?> RSD</span>
                    </div>
                    <div class="revenue-monthly">
                        <span class="revenue-label">Ovog meseca</span>
                        <span class="revenue-amount"><?php echo number_format($stats['monthly_revenue'], 0, ',', '.'); ?> RSD</span>
                    </div>
                </div>
                <div class="revenue-chart mt-3">
                    <canvas id="revenueChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Najaktivniji korisnici</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Korisnik</th><th>Oglasi</th><th>Pregledi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topUsers as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : '/assets/images/defaults/avatar.svg'; ?>" class="rounded-circle me-2" width="32" height="32">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </div>
                                </td>
                                <td><span class="badge bg-primary"><?php echo $user['ads_count']; ?></span></td>
                                <td><?php echo number_format($user['total_views']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Nedavne aktivnosti</h5>
                <a href="/admin/logs.php" class="btn btn-sm btn-link">Pregled svih</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr><th>Vreme</th><th>Korisnik</th><th>Akcija</th><th>IP adresa</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $db = getDatabaseConnection();
                            $stmt = $db->query("
                                SELECT ul.*, u.username 
                                FROM user_logs ul
                                LEFT JOIN users u ON ul.user_id = u.id
                                ORDER BY ul.created_at DESC 
                                LIMIT 10
                            ");
                            while ($log = $stmt->fetch()):
                            ?>
                            <tr>
                                <td class="text-muted"><?php echo date('d.m.Y H:i', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['username'] ?? 'Sistem'); ?></td>
                                <td><span class="badge bg-secondary"><?php echo htmlspecialchars($log['action']); ?></span></td>
                                <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Activity Chart
const ctx = document.getElementById('activityChart').getContext('2d');
const chartData = <?php echo json_encode($chartData); ?>;

new Chart(ctx, {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: [
            {
                label: 'Novi korisnici',
                data: chartData.users,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                tension: 0.4,
                fill: true
            },
            {
                label: 'Novi oglasi',
                data: chartData.ads,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, grid: { drawBorder: false } } }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>