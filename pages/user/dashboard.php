<?php
/**
 * pages/user/dashboard.php - Kontrolna tabla korisnika
 */
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Proveri da li je korisnik ulogovan
if (!isLoggedIn()) {
    redirect('/login');
}

$userId = $_SESSION['user_id'];
$userData = getUserData($userId);

// KORISTI ISPRAVNU FUNKCIJU
$userPackage = getUserPackageName($userId);  // Vraća 'free', 'silver', 'gold'

// Dohvati statistiku
$db = getDatabaseConnection();

// Broj aktivnih oglasa
$stmt = $db->prepare("SELECT COUNT(*) as count FROM ads WHERE user_id = ? AND status = 'active'");
$stmt->execute([$userId]);
$activeAdsRow = $stmt->fetch();
$activeAds = $activeAdsRow ? (int)$activeAdsRow['count'] : 0;

// Broj prodatih oglasa
$stmt = $db->prepare("SELECT COUNT(*) as count FROM ads WHERE user_id = ? AND status = 'sold'");
$stmt->execute([$userId]);
$soldAds = $stmt->fetch()['count'] ?? 0;

// Ukupno pregleda
$stmt = $db->prepare("SELECT SUM(views) as total FROM ads WHERE user_id = ?");
$stmt->execute([$userId]);
$totalViews = $stmt->fetch()['total'] ?? 0;

// Broj poruka
$stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unreadMessages = $stmt->fetch()['count'] ?? 0;

// Postavi title
$pageTitle = 'Kontrolna tabla - Rasprodaja.rs';
$pageDescription = 'Pregled vaših aktivnosti na Rasprodaja.rs';
$pageSpecificCSS = ['dashboard.css'];
$pageSpecificJS = ['dashboard.js'];
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <!-- DOBRODOŠLICA -->
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h1 class="h3 mb-2">Dobrodošli, <?php echo htmlspecialchars($userData['first_name'] ?? $userData['username']); ?>!</h1>
                            <p class="mb-0 opacity-75">
                                Vaš paket: <span class="badge bg-light text-dark"><?php echo strtoupper($userPackage); ?></span>
                                | Član od: <?php echo date('d.m.Y', strtotime($userData['created_at'])); ?>
                            </p>
                        </div>
                        <div class="text-end">
                            <a href="/create-ad" class="btn btn-light">
                                <i class="fas fa-plus-circle me-2"></i> Novi oglas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- STATISTIKE -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-bullhorn fa-2x text-primary mb-3"></i>
                            <h3 class="mb-0"><?php echo $activeAds; ?></h3>
                            <p class="text-muted mb-0">Aktivnih oglasa</p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="/ads" class="small">Vidi sve</a>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-eye fa-2x text-success mb-3"></i>
                            <h3 class="mb-0"><?php echo number_format($totalViews, 0, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Ukupno pregleda</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-check-circle fa-2x text-warning mb-3"></i>
                            <h3 class="mb-0"><?php echo $soldAds; ?></h3>
                            <p class="text-muted mb-0">Prodatih oglasa</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center h-100">
                        <div class="card-body">
                            <i class="fas fa-envelope fa-2x text-info mb-3"></i>
                            <h3 class="mb-0"><?php echo $unreadMessages; ?></h3>
                            <p class="text-muted mb-0">Nepročitanih poruka</p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <a href="/messages" class="small">Pročitaj</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AKTIVNOSTI -->
            <div class="row">
                <!-- LEVA KOLONA: Brze akcije -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-bolt me-2"></i> Brze akcije
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="/create-ad" class="btn btn-primary btn-lg">
                                    <i class="fas fa-plus-circle me-2"></i> Postavi novi oglas
                                </a>
                                <a href="/messages" class="btn btn-outline-primary">
                                    <i class="fas fa-envelope me-2"></i> Poruke
                                    <?php if ($unreadMessages > 0): ?>
                                        <span class="badge bg-danger ms-2"><?php echo $unreadMessages; ?> novo</span>
                                    <?php endif; ?>
                                </a>
                                <a href="/profile" class="btn btn-outline-secondary">
                                    <i class="fas fa-user me-2"></i> Izmeni profil
                                </a>
                                <a href="/packages" class="btn btn-outline-warning">
                                    <i class="fas fa-crown me-2"></i> Nadogradi paket
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DESNA KOLONA: Nedavni oglasi -->
                <div class="col-lg-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2"></i> Poslednji oglasi
                            </h5>
                            <a href="/ads" class="btn btn-sm btn-outline-primary">
                                Vidi sve
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php
                            // Dohvati poslednjih 5 oglasa
                            $stmt = $db->prepare("
                                SELECT a.id, a.title, a.slug, a.price, a.views, a.created_at, a.status,
                                       (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as image
                                FROM ads a
                                WHERE a.user_id = ?
                                ORDER BY a.created_at DESC
                                LIMIT 5
                            ");
                            $stmt->execute([$userId]);
                            $recentAds = $stmt->fetchAll();
                            
                            if (empty($recentAds)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Još nemate oglasa</p>
                                    <a href="/create-ad" class="btn btn-primary">
                                        Postavi prvi oglas
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($recentAds as $ad): ?>
                                        <a href="/ad/<?php echo $ad['slug']; ?>" 
                                           class="list-group-item list-group-item-action">
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($ad['image'])): ?>
                                                    <img src="<?php echo SITE_URL . '/' . $ad['image']; ?>" 
                                                         class="rounded me-3" 
                                                         width="60" height="60"
                                                         style="object-fit: cover;"
                                                         onerror="this.src='<?php echo SITE_URL; ?>/assets/images/defaults/no-image.jpg'">
                                                <?php else: ?>
                                                    <div class="rounded bg-light d-flex align-items-center justify-content-center me-3" 
                                                         style="width:60px;height:60px;">
                                                        <i class="fas fa-image text-muted"></i>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($ad['title']); ?></h6>
                                                    <div class="d-flex justify-content-between">
                                                        <small class="text-success fw-bold">
                                                            <?php echo number_format($ad['price'], 0, ',', '.'); ?> RSD
                                                        </small>
                                                        <small class="text-muted">
                                                            <i class="fas fa-eye me-1"></i> <?php echo $ad['views']; ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </div>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- PREPORUKE -->
            <div class="card border-warning">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-lightbulb me-2"></i> Preporuke za bolje rezultate
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-camera text-primary fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Dodajte kvalitetne fotografije</h6>
                                    <p class="text-muted small">Oglasi sa slikama imaju 5x više pregleda.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-edit text-success fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Detaljno opišite proizvod</h6>
                                    <p class="text-muted small">Kupci vole detaljne opise i specifikacije.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="d-flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-crown text-warning fa-2x me-3"></i>
                                </div>
                                <div>
                                    <h6>Nadogradite paket</h6>
                                    <p class="text-muted small">Premium oglasi se prikazuju na početnoj stranici.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($userPackage === 'free' && $activeAds >= 3): ?>
                        <div class="alert alert-info mt-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star fa-2x me-3"></i>
                                <div>
                                    <h6 class="mb-1">Vreme za nadogradnju!</h6>
                                    <p class="mb-0">Imate <?php echo $activeAds; ?> od 5 besplatnih oglasa. 
                                        <a href="/packages" class="alert-link">Nadogradite na Silver</a> za više mogućnosti.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>