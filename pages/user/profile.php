<?php
/**
 * pages/user/profile.php - Public korisnički profil (Instagram-like)
 */

// Dohvati ID korisnika čiji profil se gleda
$viewedUserId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Ako nema ID u URL-u, gledamo svoj profil (ako smo logovani)
if ($viewedUserId === 0 && isLoggedIn()) {
    $viewedUserId = $_SESSION['user_id'];
} elseif ($viewedUserId === 0 && !isLoggedIn()) {
    // Ne možemo videti ničiji profil
    redirect('/login');
}

// Da li je trenutni korisnik vlasnik profila?
$isOwner = isLoggedIn() && ($_SESSION['user_id'] == $viewedUserId);

// Dohvati podatke o korisniku čiji se profil gleda
$db = getDatabaseConnection();
$stmt = $db->prepare("
    SELECT 
        u.*,
        COUNT(DISTINCT a.id) as total_ads,
        COUNT(DISTINCT CASE WHEN a.status = 'active' THEN a.id END) as active_ads,
        COUNT(DISTINCT CASE WHEN a.status = 'sold' THEN a.id END) as sold_ads,
        COUNT(DISTINCT CASE WHEN a.status = 'deleted' THEN a.id END) as deleted_ads,
        (SELECT COUNT(DISTINCT f1.id) FROM followers f1 WHERE f1.following_id = u.id) as follower_count,
        (SELECT COUNT(DISTINCT f2.id) FROM followers f2 WHERE f2.follower_id = u.id) as following_count,
        DATE_FORMAT(u.created_at, '%d.%m.%Y.') as member_since,
        CASE 
            WHEN u.package = 'Gold' THEN 'bg-warning text-dark'
            WHEN u.package = 'Silver' THEN 'bg-secondary'
            ELSE 'bg-light text-dark'
        END as package_badge_class
    FROM users u
    LEFT JOIN ads a ON u.id = a.user_id AND a.status != 'deleted'
    WHERE u.id = ?
    GROUP BY u.id
");
$stmt->execute([$viewedUserId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/404');
}

// CSRF token za formu
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Proveri da li trenutni korisnik prati ovog korisnika
$isFollowing = false;
if (isLoggedIn()) {
    $stmt = $db->prepare("
        SELECT id FROM followers 
        WHERE follower_id = ? AND following_id = ?
    ");
    $stmt->execute([$_SESSION['user_id'], $viewedUserId]);
    $isFollowing = $stmt->fetch() ? true : false;
}

// Dohvati recenzije korisnika
$stmt = $db->prepare("
    SELECT 
        ur.*,
        u.username as reviewer_username,
        u.avatar as reviewer_avatar,
        a.title as ad_title
    FROM user_reviews ur
    LEFT JOIN users u ON ur.reviewer_id = u.id
    LEFT JOIN ads a ON ur.ad_id = a.id
    WHERE ur.user_id = ? AND ur.is_approved = 1
    ORDER BY ur.created_at DESC 
    LIMIT 5
");
$stmt->execute([$viewedUserId]);
$reviews = $stmt->fetchAll();

// Izračunaj prosečnu ocenu
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_reviews,
        AVG(rating) as avg_rating,
        COUNT(CASE WHEN rating = 5 THEN 1 END) as five_stars,
        COUNT(CASE WHEN rating = 4 THEN 1 END) as four_stars,
        COUNT(CASE WHEN rating = 3 THEN 1 END) as three_stars,
        COUNT(CASE WHEN rating = 2 THEN 1 END) as two_stars,
        COUNT(CASE WHEN rating = 1 THEN 1 END) as one_stars
    FROM user_reviews 
    WHERE user_id = ? AND is_approved = 1
");
$stmt->execute([$viewedUserId]);
$reviewStats = $stmt->fetch();

// Dohvati AKTIVNE oglase korisnika za galeriju
$stmt = $db->prepare("
    SELECT a.*,
           c.name as category_name,
           (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as thumbnail
    FROM ads a
    LEFT JOIN categories c ON a.category_id = c.id
    WHERE a.user_id = ? AND a.status = 'active'
    ORDER BY a.is_premium DESC, a.created_at DESC
");
$stmt->execute([$viewedUserId]);
$activeAds = $stmt->fetchAll();

// Dohvati PRODATE oglase (samo za vlasnika)
$soldAds = [];
if ($isOwner) {
    $stmt = $db->prepare("
        SELECT a.*,
               c.name as category_name,
               (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as thumbnail,
               a.deleted_at as sold_date
        FROM ads a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.user_id = ? AND a.status = 'sold'
        ORDER BY a.deleted_at DESC
    ");
    $stmt->execute([$viewedUserId]);
    $soldAds = $stmt->fetchAll();
}

// Dohvati OBRISANE oglase (samo za vlasnika)
$deletedAds = [];
if ($isOwner) {
    $stmt = $db->prepare("
        SELECT a.*,
               c.name as category_name,
               (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as thumbnail,
               a.deleted_at,
               a.delete_reason
        FROM ads a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.user_id = ? AND a.status = 'deleted'
        ORDER BY a.deleted_at DESC
    ");
    $stmt->execute([$viewedUserId]);
    $deletedAds = $stmt->fetchAll();
}

// Dohvati ISTEKLE oglase (samo za vlasnika)
$expiredAds = [];
if ($isOwner) {
    $stmt = $db->prepare("
        SELECT a.*,
               c.name as category_name,
               (SELECT thumbnail_path FROM ad_images WHERE ad_id = a.id AND is_main = 1 LIMIT 1) as thumbnail,
               a.expires_at
        FROM ads a
        LEFT JOIN categories c ON a.category_id = c.id
        WHERE a.user_id = ? AND a.status = 'expired'
        ORDER BY a.expires_at DESC
    ");
    $stmt->execute([$viewedUserId]);
    $expiredAds = $stmt->fetchAll();
}

// Postavi title
$pageTitle = htmlspecialchars($user['username']) . ' - Profil na Rasprodaja.rs';
$pageDescription = 'Profil korisnika ' . htmlspecialchars($user['username']) . ' sa oglasima i recenzijama.';
$pageSpecificCSS = ['profile.css'];
$pageSpecificJS = ['profile.js'];

$inlineStyles = "
    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    .profile-stats {
        border-top: 1px solid rgba(255,255,255,0.2);
    }
    .profile-tab {
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
    }
    .profile-tab.active {
        border-bottom-color: var(--bs-primary);
        color: var(--bs-primary) !important;
    }
    .ad-grid-item {
        position: relative;
        overflow: hidden;
        border-radius: 8px;
        transition: transform 0.3s;
    }
    .ad-grid-item:hover {
        transform: scale(1.02);
    }
    .ad-grid-item.premium::before {
        content: '⭐ PREMIUM';
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(255,193,7,0.9);
        color: #000;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
    .ad-grid-item.sold::before {
        content: '✅ PRODAT';
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(40,167,69,0.9);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
    .ad-grid-item.deleted {
        opacity: 0.7;
        filter: grayscale(0.3);
    }
    .ad-grid-item.deleted::before {
        content: '🗑️ OBRISAN';
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(108,117,125,0.9);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
    .follow-btn {
        min-width: 120px;
    }
    .profile-package-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        z-index: 10;
    }
    .delete-ad-btn {
        cursor: pointer;
        transition: all 0.2s;
    }
    .delete-ad-btn:hover {
        transform: scale(1.1);
    }
    
    .ad-grid-item.expired {
    opacity: 0.8;
    filter: grayscale(0.2);
    }
    .ad-grid-item.expired::before {
        content: '⏰ ISTEKAO';
        position: absolute;
        top: 10px;
        left: 10px;
        background: rgba(108,117,125,0.9);
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.7rem;
        font-weight: bold;
        z-index: 10;
    }
";
?>
<div class="container-fluid px-0">
    <!-- PROFILE HEADER (Instagram style) -->
    <div class="profile-header py-4">
        <div class="container">
            <div class="row align-items-center">
                <!-- AVATAR -->
                <div class="col-md-3 col-lg-2 text-center">
                    <div class="position-relative d-inline-block">
                        <img src="<?php echo !empty($user['avatar']) ? htmlspecialchars($user['avatar']) : SITE_URL . '/assets/images/defaults/avatar.svg'; ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>"
                             class="profile-avatar rounded-circle border border-4 border-white shadow-lg"
                             width="150" height="150"
                             id="profile-avatar"
                             loading="lazy">
                             <?php if ($isOwner): ?>
                            <button class="btn btn-sm btn-primary position-absolute bottom-0 end-0 rounded-circle"
                                    data-bs-toggle="modal" data-bs-target="#avatarModal"
                                    style="width: 40px; height: 40px;">
                                <i class="fas fa-camera"></i>
                            </button>
                            <?php endif; ?>
                        
                        <!-- PAKET BADGE -->
                        <?php if ($user['package'] !== 'Free'): ?>
                        <span class="profile-package-badge badge <?php echo $user['package_badge_class']; ?> rounded-pill">
                            <?php echo $user['package']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- INFO -->
                <div class="col-md-9 col-lg-7">
                    <div class="d-flex align-items-center mb-3">
                        <h2 class="mb-0 me-3 text-white"><?php echo htmlspecialchars($user['username']); ?></h2>
                        
                        <?php if ($user['is_verified']): ?>
                        <span class="badge bg-success me-2">
                            <i class="fas fa-check-circle"></i> Verifikovan
                        </span>
                        <?php endif; ?>
                        
                        <!-- AKTIVNOSTI ZA VLASNIKA -->
                        <?php if ($isOwner): ?>
                        <button class="btn btn-sm btn-outline-light me-2"
                                data-bs-toggle="modal" data-bs-target="#editProfileModal">
                            <i class="fas fa-edit me-1"></i> Izmeni profil
                        </button>
                        <a href="/dashboard" class="btn btn-sm btn-light">
                            <i class="fas fa-chart-line me-1"></i> Dashboard
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- STATISTIKA -->
                    <div class="row profile-stats pt-3">
                        <div class="col-4 text-center">
                            <div>
                                <strong class="fs-4"><?php echo $user['active_ads']; ?></strong>
                                <div class="small opacity-75">Oglasa</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div>
                                <strong class="fs-4 follower-count"><?php echo $user['follower_count']; ?></strong>
                                <div class="small opacity-75">Pratioca</div>
                            </div>
                        </div>
                        <div class="col-4 text-center">
                            <div>
                                <strong class="fs-4"><?php echo $user['following_count']; ?></strong>
                                <div class="small opacity-75">Prati</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BIO -->
                    <?php if (!empty($user['bio'])): ?>
                    <div class="mt-3">
                        <p class="mb-0 text-white opacity-90"><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- KONTAKT INFO -->
                    <div class="mt-2">
                        <?php if (!empty($user['city'])): ?>
                        <span class="me-3">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($user['city']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (!empty($user['website'])): ?>
                        <a href="<?php echo htmlspecialchars($user['website']); ?>" 
                           class="text-white me-3" target="_blank">
                            <i class="fas fa-link me-1"></i> Website
                        </a>
                        <?php endif; ?>
                        
                        <span>
                            <i class="fas fa-calendar-alt me-1"></i>
                            Član od <?php echo $user['member_since']; ?>
                        </span>
                    </div>
                </div>
                
                <!-- ACTION BUTTONS (za posetioce) -->
                <div class="col-lg-3 mt-3 mt-lg-0">
                    <?php if (!$isOwner && isLoggedIn()): ?>
                    <div class="d-flex flex-column gap-2">
                        <!-- FOLLOW/UNFOLLOW DUGME -->
                        <button class="btn <?php echo $isFollowing ? 'btn-secondary' : 'btn-primary'; ?> follow-btn"
                                data-user-id="<?php echo $viewedUserId; ?>"
                                data-is-following="<?php echo $isFollowing ? '1' : '0'; ?>"
                                id="followButton">
                            <i class="fas fa-<?php echo $isFollowing ? 'user-check' : 'user-plus'; ?> me-1"></i>
                            <?php echo $isFollowing ? 'Pratim' : 'Prati'; ?>
                        </button>
                        
                       
                        
                        <!-- REPORT DUGME -->
                        <button class="btn btn-outline-light btn-sm" id="reportButton">
                            <i class="fas fa-flag me-1"></i> Prijavi profil
                        </button>
                    </div>
                    
                    <?php elseif (!$isOwner && !isLoggedIn()): ?>
                    <div class="d-flex flex-column gap-2">
                        <a href="/login" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Ulogujte se da pratite
                        </a>
                        <a href="/register" class="btn btn-outline-light">
                            <i class="fas fa-user-plus me-1"></i> Registrujte se
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- TABS NAVIGACIJA -->
    <div class="container py-3">
        <div class="row">
            <div class="col-12">
                <nav>
                    <div class="nav nav-tabs border-0" id="profileTabs">
                        <button class="nav-link profile-tab active" 
                                data-bs-toggle="tab" 
                                data-bs-target="#adsTab">
                            <i class="fas fa-tags me-1"></i> Oglasi
                            <span class="badge bg-primary ms-1"><?php echo count($activeAds); ?></span>
                        </button>
                        
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#reviewsTab">
                            <i class="fas fa-star me-1"></i> Recenzije
                            <span class="badge bg-primary ms-1"><?php echo $reviewStats['total_reviews'] ?? 0; ?></span>
                        </button>
                        
                        <?php if ($isOwner): ?>
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#soldTab">
                            <i class="fas fa-check-circle me-1"></i> Prodato
                            <span class="badge bg-success ms-1"><?php echo count($soldAds); ?></span>
                        </button>
                        
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#deletedTab">
                            <i class="fas fa-trash-alt me-1"></i> Obrisano
                            <span class="badge bg-primary ms-1"><?php echo count($deletedAds); ?></span>
                        </button>
                        
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#expiredTab">
                            <i class="fas fa-clock me-1"></i> Istekli
                            <span class="badge bg-secondary ms-1"><?php echo count($expiredAds); ?></span>
                        </button>
                        <?php endif; ?>
                        
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#infoTab">
                            <i class="fas fa-info-circle me-1"></i> Informacije
                        </button>
                        
                        <?php if ($isOwner): ?>
                        <button class="nav-link profile-tab" 
                                data-bs-toggle="tab" 
                                data-bs-target="#settingsTab">
                            <i class="fas fa-cog me-1"></i> Podešavanja
                        </button>
                        <?php endif; ?>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    
    <!-- TAB CONTENT -->
    <div class="container py-4">
        <div class="tab-content">
            <!-- TAB 1: AKTIVNI OGLASI -->
            <div class="tab-pane fade show active" id="adsTab">
                <?php if (empty($activeAds)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nema aktivnih oglasa</h4>
                    <?php if ($isOwner): ?>
                    <p class="text-muted">Još nemate oglasa. Postavite prvi!</p>
                    <a href="/create-ad" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-1"></i> Postavi oglas
                    </a>
                    <?php else: ?>
                    <p class="text-muted">Ovaj korisnik još nema aktivnih oglasa.</p>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($activeAds as $ad): ?>
                    <div class="col">
                        <a href="/ad/<?php echo $ad['slug']; ?>" 
                           class="text-decoration-none text-dark ad-grid-link">
                            <div class="card ad-grid-item h-100 <?php echo $ad['is_premium'] ? 'premium' : ''; ?>">
                                <?php if (!empty($ad['thumbnail'])): ?>
                                <img src="<?php echo htmlspecialchars($ad['thumbnail']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                     style="height: 200px; object-fit: cover;">
                                <?php else: ?>
                                <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                     style="height: 200px;">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($ad['title']); ?>">
                                        <?php echo htmlspecialchars($ad['title']); ?>
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong class="text-primary">
                                            <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
                                        </strong>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($ad['city']); ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($ad['category_name'])): ?>
                                    <div class="mt-2">
                                        <small class="badge bg-light text-dark">
                                            <?php echo htmlspecialchars($ad['category_name']); ?>
                                        </small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($isOwner): ?>
                                <div class="card-footer bg-transparent border-top-0 pt-0">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- DUGME ZA PREMIUM -->
                                        <?php if ($ad['is_premium'] != 1): ?>
                                        <button class="btn btn-sm btn-warning make-premium-btn"
                                                data-ad-id="<?php echo $ad['id']; ?>"
                                                data-ad-title="<?php echo htmlspecialchars($ad['title']); ?>"
                                                title="Označi kao premium">
                                            <i class="fas fa-crown"></i> Premium
                                        </button>
                                        <?php else: ?>
                                        <span class="badge bg-warning text-dark p-2">
                                            <i class="fas fa-crown me-1"></i> PREMIUM
                                        </span>
                                        <?php endif; ?>
                                        <a href="/edit-ad/<?php echo $ad['id']; ?>" 
                                           class="btn btn-sm btn-outline-secondary"
                                           title="Izmeni oglas">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-danger delete-ad-btn"
                                                data-ad-id="<?php echo $ad['id']; ?>"
                                                data-ad-title="<?php echo htmlspecialchars($ad['title']); ?>"
                                                title="Obriši oglas">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 2: RECENZIJE -->
            <div class="tab-pane fade" id="reviewsTab">
                <?php if (empty($reviews) && empty($reviewStats['total_reviews'])): ?>
                <div class="text-center py-5">
                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nema recenzija</h4>
                    <p class="text-muted">Ovaj korisnik još nema recenzija.</p>
                </div>
                <?php else: ?>
                <!-- OCENA SAZVECE -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body text-center">
                                <div class="display-4 text-warning mb-2">
                                    <?php echo number_format($reviewStats['avg_rating'] ?? 0, 1); ?>
                                </div>
                                <div class="text-warning mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= round($reviewStats['avg_rating'] ?? 0)): ?>
                                            <i class="fas fa-star"></i>
                                        <?php else: ?>
                                            <i class="far fa-star"></i>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </div>
                                <p class="text-muted mb-0">
                                    <?php echo $reviewStats['total_reviews'] ?? 0; ?> recenzija
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                <?php 
                                $count = $reviewStats['five_stars'] ?? 0;
                                if ($rating == 4) $count = $reviewStats['four_stars'] ?? 0;
                                if ($rating == 3) $count = $reviewStats['three_stars'] ?? 0;
                                if ($rating == 2) $count = $reviewStats['two_stars'] ?? 0;
                                if ($rating == 1) $count = $reviewStats['one_stars'] ?? 0;
                                
                                $percentage = $reviewStats['total_reviews'] > 0 
                                    ? ($count / $reviewStats['total_reviews']) * 100 
                                    : 0;
                                ?>
                                <div class="row align-items-center mb-2">
                                    <div class="col-2">
                                        <span class="text-warning">
                                            <?php for ($i = 1; $i <= $rating; $i++): ?>★<?php endfor; ?>
                                        </span>
                                    </div>
                                    <div class="col-8">
                                        <div class="progress" style="height: 10px;">
                                            <div class="progress-bar bg-warning" 
                                                 style="width: <?php echo $percentage; ?>%"></div>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end">
                                        <small class="text-muted"><?php echo $count; ?></small>
                                    </div>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- LISTA RECENZIJA -->
                <div class="row">
                    <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-start mb-3">
                                    <img src="<?php echo !empty($review['reviewer_avatar']) ? htmlspecialchars($review['reviewer_avatar']) : SITE_URL . '/assets/images/defaults/avatar.svg'; ?>" 
                                         class="rounded-circle me-3" 
                                         width="50" height="50"
                                         alt="<?php echo htmlspecialchars($review['reviewer_username']); ?>">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($review['reviewer_username']); ?></h6>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= $review['rating']): ?>
                                                    <i class="fas fa-star"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star"></i>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if (!empty($review['title'])): ?>
                                <h6 class="mb-2"><?php echo htmlspecialchars($review['title']); ?></h6>
                                <?php endif; ?>
                                
                                <?php if (!empty($review['COMMENT'])): ?>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($review['COMMENT'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($review['ad_title'])): ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        Oglas: <?php echo htmlspecialchars($review['ad_title']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                                
                                <div class="text-end mt-3">
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y.', strtotime($review['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 3: PRODATI OGLASI (samo za vlasnika) -->
            <?php if ($isOwner): ?>
            <div class="tab-pane fade" id="soldTab">
                <?php if (empty($soldAds)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h4 class="text-muted">Nema prodatih oglasa</h4>
                    <p class="text-muted">Kada prodate neki oglas, on će se ovde pojaviti.</p>
                </div>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($soldAds as $ad): ?>
                    <div class="col">
                        <div class="card ad-grid-item sold h-100">
                            <?php if (!empty($ad['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['thumbnail']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($ad['title']); ?>">
                                    <?php echo htmlspecialchars($ad['title']); ?>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-success">
                                        <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
                                    </strong>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar"></i>
                                        <?php echo date('d.m.Y.', strtotime($ad['deleted_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- TAB 4: OBRISANI OGLASI (samo za vlasnika) -->
            <div class="tab-pane fade" id="deletedTab">
                <?php if (empty($deletedAds)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-trash-alt fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nema obrisanih oglasa</h4>
                    <p class="text-muted">Obrisani oglasi će se ovde pojaviti.</p>
                </div>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($deletedAds as $ad): ?>
                    <div class="col">
                        <div class="card ad-grid-item deleted h-100">
                            <?php if (!empty($ad['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['thumbnail']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height: 200px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($ad['title']); ?>">
                                    <?php echo htmlspecialchars($ad['title']); ?>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-muted text-decoration-line-through">
                                        <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
                                    </strong>
                                    <small class="text-muted">
                                        <i class="fas fa-trash"></i>
                                        <?php echo date('d.m.Y.', strtotime($ad['deleted_at'])); ?>
                                    </small>
                                </div>
                                <?php if (!empty($ad['delete_reason'])): ?>
                                <div class="mt-2">
                                    <small class="badge bg-secondary">
                                        Razlog: <?php 
                                            $reasons = [
                                                'sold' => 'Prodat',
                                                'not_available' => 'Nije dostupno',
                                                'wrong_info' => 'Pogrešne info',
                                                'other' => 'Drugi razlog'
                                            ];
                                            echo $reasons[$ad['delete_reason']] ?? $ad['delete_reason'];
                                        ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- TAB: ISTEKLI OGLASI (samo za vlasnika) -->
            <?php if ($isOwner): ?>
            <div class="tab-pane fade" id="expiredTab">
                <?php if (empty($expiredAds)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">Nema isteklih oglasa</h4>
                    <p class="text-muted">Oglasi kojima je istekao rok trajanja će se ovde pojaviti.</p>
                </div>
                <?php else: ?>
                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                    <?php foreach ($expiredAds as $ad): ?>
                    <div class="col">
                        <div class="card ad-grid-item expired h-100">
                            <?php if (!empty($ad['thumbnail'])): ?>
                            <img src="<?php echo htmlspecialchars($ad['thumbnail']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($ad['title']); ?>"
                                 style="height: 200px; object-fit: cover; opacity: 0.7;">
                            <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                                 style="height: 200px; opacity: 0.7;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                            </div>
                            <?php endif; ?>
                            
                            <div class="card-body">
                                <h6 class="card-title text-truncate" title="<?php echo htmlspecialchars($ad['title']); ?>">
                                    <?php echo htmlspecialchars($ad['title']); ?>
                                </h6>
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-muted text-decoration-line-through">
                                        <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
                                    </strong>
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-times"></i>
                                        Istekao: <?php echo date('d.m.Y.', strtotime($ad['expires_at'])); ?>
                                    </small>
                                </div>
                                <?php if (!empty($ad['category_name'])): ?>
                                <div class="mt-2">
                                    <small class="badge bg-secondary">
                                        <?php echo htmlspecialchars($ad['category_name']); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="card-footer bg-transparent border-top-0 pt-0">
                                <div class="d-flex justify-content-end gap-2">
                                    <button class="btn btn-sm btn-outline-primary renew-ad-btn"
                                            data-ad-id="<?php echo $ad['id']; ?>"
                                            data-ad-title="<?php echo htmlspecialchars($ad['title']); ?>"
                                            title="Obnovi oglas">
                                        <i class="fas fa-sync-alt"></i> Obnovi
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-ad-btn"
                                            data-ad-id="<?php echo $ad['id']; ?>"
                                            data-ad-title="<?php echo htmlspecialchars($ad['title']); ?>"
                                            title="Obriši oglas">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- TAB 5: INFORMACIJE -->
            <div class="tab-pane fade" id="infoTab">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-user-circle me-2"></i> O korisniku</h6>
                            </div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Ime i prezime</dt>
                                    <dd class="col-sm-8">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Korisničko ime</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($user['username']); ?></dd>
                                    
                                    <?php if (!empty($user['city'])): ?>
                                    <dt class="col-sm-4">Lokacija</dt>
                                    <dd class="col-sm-8">
                                        <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                        <?php echo htmlspecialchars($user['city']); ?>
                                    </dd>
                                    <?php endif; ?>
                                    
                                    <dt class="col-sm-4">Član od</dt>
                                    <dd class="col-sm-8"><?php echo $user['member_since']; ?></dd>
                                    
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        <?php if ($user['is_verified']): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i> Verifikovan
                                        </span>
                                        <?php else: ?>
                                        <span class="badge bg-secondary">Nije verifikovan</span>
                                        <?php endif; ?>
                                    </dd>
                                    
                                    <dt class="col-sm-4">Paket</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge <?php echo $user['package_badge_class']; ?>">
                                            <?php echo $user['package']; ?>
                                            <?php if ($user['package_expires_at']): ?>
                                            (do <?php echo date('d.m.Y.', strtotime($user['package_expires_at'])); ?>)
                                            <?php endif; ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i> Statistika</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-6 mb-3">
                                        <div class="display-6 text-primary"><?php echo $user['total_ads']; ?></div>
                                        <div class="text-muted">Ukupno oglasa</div>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <div class="display-6 text-success"><?php echo $user['active_ads']; ?></div>
                                        <div class="text-muted">Aktivni oglasi</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="display-6 text-info follower-count"><?php echo $user['follower_count']; ?></div>
                                        <div class="text-muted">Pratioca</div>
                                    </div>
                                    <div class="col-6">
                                        <div class="display-6 text-warning"><?php echo $user['following_count']; ?></div>
                                        <div class="text-muted">Prati</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- KONTAKT -->
                        <?php if (!empty($user['phone']) || !empty($user['email']) && $user['show_email']): ?>
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="fas fa-address-book me-2"></i> Kontakt</h6>
                            </div>
                            <div class="card-body">
                                <div class="list-group list-group-flush">
                                    <?php if (!empty($user['phone'])): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <i class="fas fa-phone text-primary me-2"></i>
                                        <?php echo htmlspecialchars($user['phone']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['email']) && $user['show_email']): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <i class="fas fa-envelope text-primary me-2"></i>
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($user['website'])): ?>
                                    <div class="list-group-item border-0 px-0">
                                        <a href="<?php echo htmlspecialchars($user['website']); ?>" 
                                           class="text-decoration-none" target="_blank">
                                            <i class="fas fa-globe text-primary me-2"></i>
                                            <?php echo htmlspecialchars($user['website']); ?>
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- TAB 6: PODEŠAVANJA (samo za vlasnika) -->
            <?php if ($isOwner): ?>
            <div class="tab-pane fade" id="settingsTab">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i> Postavke profila</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-3">
                                    <button class="btn btn-outline-primary text-start" 
                                            data-bs-toggle="modal" data-bs-target="#editProfileModal">
                                        <i class="fas fa-edit me-2"></i> Izmeni profil
                                    </button>
                                    <button class="btn btn-outline-secondary text-start" 
                                            data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                        <i class="fas fa-key me-2"></i> Promeni lozinku
                                    </button>
                                    <button class="btn btn-outline-info text-start" 
                                            data-bs-toggle="modal" data-bs-target="#privacySettingsModal">
                                        <i class="fas fa-shield-alt me-2"></i> Privatnost
                                    </button>
                                    <button class="btn btn-outline-warning text-start" 
                                            data-bs-toggle="modal" data-bs-target="#notificationSettingsModal">
                                        <i class="fas fa-bell me-2"></i> Notifikacije
                                    </button>
                                    <a href="/dashboard/" 
                                       class="btn btn-outline-success text-start">
                                        <i class="fas fa-chart-line me-2"></i> Dashboard
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-share-alt me-2"></i> Podeli profil</h5>
                            </div>
                            <div class="card-body text-center">
                                <p class="text-muted mb-3">Podelite svoj profil sa drugima</p>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-outline-primary btn-sm" onclick="shareProfile('facebook')">
                                        <i class="fab fa-facebook"></i>
                                    </button>
                                    <button class="btn btn-outline-info btn-sm" onclick="shareProfile('twitter')">
                                        <i class="fab fa-twitter"></i>
                                    </button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="shareProfile('pinterest')">
                                        <i class="fab fa-pinterest"></i>
                                    </button>
                                    <button class="btn btn-outline-success btn-sm" onclick="copyProfileLink()">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                                <div class="mt-3">
                                    <small class="text-muted">Link profila:</small>
                                    <div class="input-group input-group-sm mt-1">
                                        <input type="text" class="form-control" 
                                               value="<?php echo SITE_URL; ?>/profile/<?php echo $viewedUserId; ?>"
                                               id="profileLink" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="copyProfileLink()">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- MODAL ZA BRISANJE OGLASA -->
<div class="modal fade" id="deleteAdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-trash-alt text-danger me-2"></i>
                    Brisanje oglasa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Da li ste sigurni da želite da obrišete oglas:
                    <strong id="delete-ad-title"></strong>
                </p>
                <p>Molimo vas da navedete razlog brisanja oglasa:</p>
                
                <form id="delete-ad-form">
                    <input type="hidden" name="ad_id" id="delete-ad-id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reason" id="reason-sold" value="sold">
                        <label class="form-check-label" for="reason-sold">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Prodat</strong>
                            <div class="small text-muted">Oglas je uspešno prodat</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reason" id="reason-not-available" value="not_available">
                        <label class="form-check-label" for="reason-not-available">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <strong>Više nije dostupno</strong>
                            <div class="small text-muted">Oglas više nije aktuelan</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reason" id="reason-wrong-info" value="wrong_info">
                        <label class="form-check-label" for="reason-wrong-info">
                            <i class="fas fa-edit text-info me-2"></i>
                            <strong>Pogrešne informacije</strong>
                            <div class="small text-muted">Oglas sadrži netačne podatke</div>
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reason" id="reason-other" value="other" checked>
                        <label class="form-check-label" for="reason-other">
                            <i class="fas fa-question-circle text-secondary me-2"></i>
                            <strong>Drugi razlog</strong>
                            <div class="small text-muted">Želim trajno da obrišem oglas</div>
                        </label>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Napomena:</strong> Ova akcija se ne može poništiti.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Otkaži
                </button>
                <button type="button" class="btn btn-danger" id="confirm-delete-btn">
                    <i class="fas fa-trash-alt me-2"></i>Obriši oglas
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: Izmena profila -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i> Izmena profila
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" action="<?php echo SITE_URL; ?>/api/user/update-profile.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Ime *</label>
                                <input type="text" class="form-control" id="first_name" 
                                       name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                       required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="last_name" class="form-label">Prezime *</label>
                                <input type="text" class="form-control" id="last_name" 
                                       name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                       required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label">Korisničko ime</label>
                        <input type="text" class="form-control" id="username" 
                               name="username" value="<?php echo htmlspecialchars($user['username']); ?>"
                               readonly disabled>
                        <div class="form-text text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Korisničko ime se ne može menjati.
                        </div>
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="bio" class="form-label">Biografija</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"
                                  placeholder="Opisite sebe..."><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="city" class="form-label">Grad</label>
                                <input type="text" class="form-control" id="city" 
                                       name="city" value="<?php echo htmlspecialchars($user['city']); ?>"
                                       placeholder="Npr. Beograd">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telefon</label>
                                <input type="tel" class="form-control" id="phone" 
                                       name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"
                                       placeholder="+381 64 1234567">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="show_email" 
                               name="show_email" value="1" <?php echo $user['show_email'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="show_email">
                            Prikaži moj email u profilu
                        </label>
                    </div>
                    
                    <!-- DUGME UNUTAR FORME -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Odustani
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Sačuvaj promene
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- MODAL: Promena lozinke -->
<div class="modal fade" id="changePasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-key me-2"></i> Promena lozinke
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" action="<?php echo SITE_URL; ?>/api/user/change-password.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Trenutna lozinka *</label>
                        <input type="password" class="form-control" id="current_password" 
                               name="current_password" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Nova lozinka *</label>
                        <input type="password" class="form-control" id="new_password" 
                               name="new_password" required minlength="8">
                        <div class="form-text">Minimalno 8 karaktera.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Potvrdite novu lozinku *</label>
                        <input type="password" class="form-control" id="confirm_password" 
                               name="confirm_password" required>
                    </div>
                    
                    <!-- DUGMAD UNUTAR FORME -->
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Odustani
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Promeni lozinku
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ZA PREMIUM OGLAS -->
<div class="modal fade" id="premiumAdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">
                    <i class="fas fa-crown me-2"></i>
                    Označi oglas kao Premium
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Da li želite da označite oglas kao premium?
                    <strong id="premium-ad-title"></strong>
                </p>
                
                <!-- INFORMACIJE O PREMIUM PAKETU -->
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Premium oglas dobija:</strong>
                    <ul class="mb-0 mt-2">
                        <li><i class="fas fa-arrow-up me-1"></i> Prikaz na vrhu liste</li>
                        <li><i class="fas fa-eye me-1"></i> 5x više pregleda</li>
                        <li><i class="fas fa-crown me-1"></i> Zlatna ikona pored oglasa</li>
                        <li><i class="fas fa-clock me-1"></i> Premium status 30 dana</li>
                    </ul>
                </div>
                
                <!-- STATISTIKA PREMIUM OGLASA -->
                <div class="card mb-3" id="premium-stats-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-crown me-1 text-warning"></i> Premium oglasi:</span>
                            <strong id="current-premium-count">0</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2">
                            <span><i class="fas fa-chart-line me-1"></i> Maksimalno:</span>
                            <strong id="max-premium-count">0</strong>
                        </div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div id="premium-progress-bar" class="progress-bar bg-warning" style="width: 0%"></div>
                        </div>
                        <div class="mt-2 text-center">
                            <small id="premium-remaining-text" class="text-muted"></small>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Napomena:</strong>
                    <p class="mb-0 mt-1 small">
                        Premium status važi 30 dana. Nakon isteka, oglas se vraća na običan prikaz.
                        Ovu akciju možete uraditi samo jednom po oglasu dok je aktivan.
                    </p>
                </div>
                
                <form id="premium-ad-form">
                    <input type="hidden" name="ad_id" id="premium-ad-id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Otkaži
                </button>
                <button type="button" class="btn btn-warning" id="confirm-premium-btn">
                    <i class="fas fa-crown me-2"></i>Označi kao Premium
                </button>
            </div>
        </div>
    </div>
</div>


<!-- MODAL ZA OBNAVLJANJE OGLASA -->
<div class="modal fade" id="renewAdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-sync-alt text-primary me-2"></i>
                    Obnavljanje oglasa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">
                    Da li želite da obnovite oglas:
                    <strong id="renew-ad-title"></strong>
                </p>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Informacije o obnovi:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Oglas će biti aktivan narednih 30 dana</li>
                        <li>Svi pregledi i statistika ostaju sačuvani</li>
                        <li>Premium status ostaje nepromenjen</li>
                        <li>Oglas će biti prikazan na vrhu liste</li>
                    </ul>
                </div>
                
                <?php if ($userPackage === 'free'): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Napomena za FREE paket:</strong>
                    <p class="mb-0 mt-1 small">
                        Obnavljanjem oglasa, on će zauzeti jedan od vaših 10 besplatnih oglasa.
                        Ako ste dostigli limit, nećete moći da obnovite oglas.
                    </p>
                </div>
                <?php endif; ?>
                
                <form id="renew-ad-form">
                    <input type="hidden" name="ad_id" id="renew-ad-id" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Otkaži
                </button>
                <button type="button" class="btn btn-primary" id="confirm-renew-btn">
                    <i class="fas fa-sync-alt me-2"></i>Obnovi oglas
                </button>
            </div>
        </div>
    </div>
</div>



<!-- JavaScript za brisanje oglasa -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dugmad za brisanje oglasa
    const deleteBtns = document.querySelectorAll('.delete-ad-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAdModal'));
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    const deleteAdId = document.getElementById('delete-ad-id');
    const deleteAdTitle = document.getElementById('delete-ad-title');
    
    if (deleteBtns) {
        deleteBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const adId = this.getAttribute('data-ad-id');
                const adTitle = this.getAttribute('data-ad-title');
                
                deleteAdId.value = adId;
                deleteAdTitle.textContent = adTitle;
                
                deleteModal.show();
            });
        });
    }
    
    // Potvrda brisanja
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async function() {
            const adId = deleteAdId.value;
            const csrfToken = document.querySelector('#delete-ad-form input[name="csrf_token"]')?.value;
            const selectedReason = document.querySelector('#delete-ad-form input[name="reason"]:checked')?.value;
            
            if (!adId || !selectedReason) {
                showAlert('danger', 'Greška: Nedostaju podaci za brisanje');
                return;
            }
            
            confirmDeleteBtn.disabled = true;
            confirmDeleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Brisanje...';
            
            try {
                const response = await fetch('/api/ads/delete.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        ad_id: adId,
                        reason: selectedReason,
                        csrf_token: csrfToken
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    showAlert('success', result.message);
                    deleteModal.hide();
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', result.message);
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Obriši oglas';
                }
                
            } catch (error) {
                console.error('Greška:', error);
                showAlert('danger', 'Greška pri brisanju oglasa. Pokušajte ponovo.');
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Obriši oglas';
            }
        });
    }
    
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'danger' ? 'danger' : 'success'} alert-dismissible fade show`;
        alertDiv.style.cssText = 'position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 9999; min-width: 300px; max-width: 500px;';
        alertDiv.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'danger' ? 'exclamation-circle' : 'check-circle'} me-2"></i>
                <div class="flex-grow-1">${message}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        document.body.appendChild(alertDiv);
        
        setTimeout(() => {
            if (alertDiv.parentNode) alertDiv.remove();
        }, 3000);
    }
    
    
    // ============================================
// OBNAVLJANJE OGLASA
// ============================================
const renewModal = new bootstrap.Modal(document.getElementById('renewAdModal'));
const renewAdId = document.getElementById('renew-ad-id');
const renewAdTitle = document.getElementById('renew-ad-title');
const confirmRenewBtn = document.getElementById('confirm-renew-btn');

// Dugmad za obnavljanje
document.querySelectorAll('.renew-ad-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const adId = this.getAttribute('data-ad-id');
        const adTitle = this.getAttribute('data-ad-title');
        
        renewAdId.value = adId;
        renewAdTitle.textContent = adTitle;
        
        renewModal.show();
    });
});

// Potvrda obnavljanja
if (confirmRenewBtn) {
    confirmRenewBtn.addEventListener('click', async function() {
        const adId = renewAdId.value;
        const csrfToken = document.querySelector('#renew-ad-form input[name="csrf_token"]')?.value;
        
        if (!adId) {
            showAlert('danger', 'Greška: Nedostaje ID oglasa');
            return;
        }
        
        confirmRenewBtn.disabled = true;
        confirmRenewBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Obnavljanje...';
        
        try {
            const response = await fetch('/api/ads/renew.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ad_id: adId,
                    csrf_token: csrfToken
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                renewModal.hide();
                
                // Osveži stranicu nakon 1.5 sekundi
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('danger', result.message);
                confirmRenewBtn.disabled = false;
                confirmRenewBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Obnovi oglas';
            }
            
        } catch (error) {
            console.error('Greška:', error);
            showAlert('danger', 'Greška pri obnavljanju oglasa. Pokušajte ponovo.');
            confirmRenewBtn.disabled = false;
            confirmRenewBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Obnovi oglas';
        }
    });
}

// ============================================
// PREMIUM OGLAS - KORISTI package-limits.php
// ============================================
const premiumModal = new bootstrap.Modal(document.getElementById('premiumAdModal'));
const premiumAdId = document.getElementById('premium-ad-id');
const premiumAdTitle = document.getElementById('premium-ad-title');
const confirmPremiumBtn = document.getElementById('confirm-premium-btn');
const currentPremiumCountSpan = document.getElementById('current-premium-count');
const maxPremiumCountSpan = document.getElementById('max-premium-count');
const premiumProgressBar = document.getElementById('premium-progress-bar');
const premiumRemainingText = document.getElementById('premium-remaining-text');

// Dohvati premium statistiku iz package-limits.php
async function loadPremiumStats() {
    try {
        const response = await fetch('/api/user/package-limits.php');
        const data = await response.json();
        
        if (data.success && data.premium_stats) {
            const currentPremium = data.premium_stats.current;
            const maxPremium = data.premium_stats.max;
            const remaining = data.premium_stats.remaining;
            const percentage = maxPremium > 0 ? (currentPremium / maxPremium) * 100 : 0;
            
            if (currentPremiumCountSpan) currentPremiumCountSpan.textContent = currentPremium;
            if (maxPremiumCountSpan) maxPremiumCountSpan.textContent = maxPremium;
            if (premiumProgressBar) premiumProgressBar.style.width = percentage + '%';
            
            if (premiumRemainingText) {
                if (remaining > 0) {
                    premiumRemainingText.textContent = `Preostalo vam je još ${remaining} premium oglasa`;
                    premiumRemainingText.className = 'text-muted';
                } else {
                    premiumRemainingText.textContent = 'Dostigli ste maksimalan broj premium oglasa';
                    premiumRemainingText.className = 'text-danger';
                }
            }
            
            // Onemogući dugme ako nema preostalih premium oglasa
            if (confirmPremiumBtn) {
                confirmPremiumBtn.disabled = remaining <= 0;
            }
        }
    } catch (error) {
        console.error('Error loading premium stats:', error);
    }
}

// Dugmad za premium
document.querySelectorAll('.make-premium-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        const adId = this.getAttribute('data-ad-id');
        const adTitle = this.getAttribute('data-ad-title');
        
        premiumAdId.value = adId;
        premiumAdTitle.textContent = adTitle;
        
        // Učitaj statistike pre prikaza modala
        loadPremiumStats();
        
        premiumModal.show();
    });
});

// Potvrda premium
if (confirmPremiumBtn) {
    confirmPremiumBtn.addEventListener('click', async function() {
        const adId = premiumAdId.value;
        const csrfToken = document.querySelector('#premium-ad-form input[name="csrf_token"]')?.value;
        
        if (!adId) {
            showAlert('danger', 'Greška: Nedostaje ID oglasa');
            return;
        }
        
        confirmPremiumBtn.disabled = true;
        confirmPremiumBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Postavljanje...';
        
        try {
            const response = await fetch('/api/ads/make-premium.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    ad_id: adId,
                    csrf_token: csrfToken
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                premiumModal.hide();
                
                // Osveži stranicu nakon 1.5 sekundi
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showAlert('danger', result.message);
                confirmPremiumBtn.disabled = false;
                confirmPremiumBtn.innerHTML = '<i class="fas fa-crown me-2"></i>Označi kao Premium';
            }
            
        } catch (error) {
            console.error('Greška:', error);
            showAlert('danger', 'Greška pri postavljanju premium oglasa. Pokušajte ponovo.');
            confirmPremiumBtn.disabled = false;
            confirmPremiumBtn.innerHTML = '<i class="fas fa-crown me-2"></i>Označi kao Premium';
        }
    });
}
    
});



</script>