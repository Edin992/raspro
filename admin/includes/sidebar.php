<?php
/**
 * admin/includes/sidebar.php - Admin sidebar
 */
?>
<aside class="admin-sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="fas fa-shield-alt"></i>
            <span>Admin<span class="text-primary">Panel</span></span>
        </div>
        <div class="sidebar-version">v1.0</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Glavno</div>
            <a href="/admin/dashboard.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'dashboard.php' ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i>
                <span>Dashboard</span>
            </a>
            <a href="/admin/users.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'users.php' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Korisnici</span>
                <?php
                $db = getDatabaseConnection();
                $stmt = $db->query("SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()");
                $newUsers = $stmt->fetchColumn();
                if ($newUsers > 0): ?>
                <span class="badge">+<?php echo $newUsers; ?></span>
                <?php endif; ?>
            </a>
            <a href="/admin/ads.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'ads.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Oglasi</span>
            </a>
            <a href="/admin/categories.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-folder-tree"></i>
                <span>Kategorije</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Sistem</div>
            <a href="/admin/settings.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'settings.php' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i>
                <span>Podešavanja</span>
            </a>
            <a href="/admin/transactions.php" class="nav-link <?php echo basename($_SERVER['SCRIPT_NAME']) == 'transactions.php' ? 'active' : ''; ?>">
                <i class="fas fa-credit-card"></i>
                <span>Transakcije</span>
                <?php
                // Broj pending transakcija
                try {
                    $db = getDatabaseConnection();
                    $stmt = $db->query("SELECT COUNT(*) FROM transactions WHERE status = 'pending'");
                    $pendingCount = $stmt->fetchColumn();
                    if ($pendingCount > 0): ?>
                    <span class="badge bg-warning text-dark"><?php echo $pendingCount; ?></span>
                    <?php endif;
                } catch (Exception $e) {
                    // Ignoriši grešku
                }
                ?>
            </a>
            <a href="/admin/logs.php" class="nav-link">
                <i class="fas fa-history"></i>
                <span>Logovi</span>
            </a>
            <a href="/admin/backup.php" class="nav-link">
                <i class="fas fa-database"></i>
                <span>Backup</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Informacije</div>
            <a href="/" target="_blank" class="nav-link">
                <i class="fas fa-globe"></i>
                <span>Idi na sajt</span>
            </a>
            <a href="/admin/logout.php" class="nav-link text-danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Odjava</span>
            </a>
        </div>
    </nav>
    
    <div class="sidebar-footer">
        <div class="admin-info">
            <img src="<?php echo !empty($_SESSION['admin_avatar']) ? $_SESSION['admin_avatar'] : '/assets/images/defaults/avatar.svg'; ?>" class="admin-avatar" alt="Avatar">
            <div class="admin-details">
                <div class="admin-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></div>
                <div class="admin-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'admin'); ?></div>
            </div>
        </div>
    </div>
</aside>

<main class="admin-main">
    <div class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="topbar-title">
            <h1><?php echo $pageTitle ?? 'Dashboard'; ?></h1>
        </div>
        
        <div class="topbar-actions">
            <div class="notification-dropdown">
                <button class="btn btn-icon" id="notificationBtn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
            </div>
            
            <div class="theme-toggle">
                <button class="btn btn-icon" id="darkModeToggle">
                    <i class="fas fa-moon"></i>
                </button>
            </div>
            
            <div class="admin-dropdown">
                <button class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown">
                    <i class="fas fa-user-circle"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="/admin/profile.php"><i class="fas fa-user me-2"></i>Moj profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Odjava</a></li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="admin-content">