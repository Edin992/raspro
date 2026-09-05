<?php
/**
 * pages/ads/detail.php - Detaljan prikaz oglasa
 */

// Ako imamo ad_slug (SEO URL)
if (isset($_GET['ad_slug']) && !isset($_GET['id'])) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT id FROM ads WHERE slug = ? LIMIT 1");
    $stmt->execute([$_GET['ad_slug']]);
    $ad = $stmt->fetch();
    if ($ad) {
        $_GET['id'] = $ad['id'];
    } else {
        // Oglas nije pronadjen - 404
        header('HTTP/1.0 404 Not Found');
        include 'pages/404.php';
        exit;
    }
}

// Vaš postojeći kod za dohvatanje oglasa po ID...

if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('/ads');
}

$adId = $_GET['id'];


// Dohvati podatke o oglasu
$ad = getAdById($adId);
if (!$ad) {
    include 'pages/errors/404.php';
    exit();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Povećaj broj pregleda
incrementAdViews($adId);
$adslug=$ad['slug'];
// Dohvati slike oglasa
$images = getAdImages($adId);

// Dohvati podatke korisnika koji je postavio oglas
$adOwner = getUserById($ad['user_id']);

// ============================================
// OCENA PRODAVCA (prosek recenzija) - za "Prodavac" karticu
// ============================================
$sellerReviewStats = ['total_reviews' => 0, 'avg_rating' => 0];
if (!empty($adOwner['id'])) {
    try {
        $db2 = getDatabaseConnection();
        $stmtSR = $db2->prepare("
            SELECT COUNT(*) as total_reviews, ROUND(AVG(rating), 2) as avg_rating
            FROM user_reviews
            WHERE user_id = ? AND is_approved = 1
        ");
        $stmtSR->execute([(int) $adOwner['id']]);
        $sr = $stmtSR->fetch();
        if ($sr) {
            $sellerReviewStats['total_reviews'] = (int) $sr['total_reviews'];
            $sellerReviewStats['avg_rating'] = (float) $sr['avg_rating'];
        }
    } catch (Throwable $e) {
        error_log('seller rating query: ' . $e->getMessage());
    }
}

// ============================================
// PROVERA ZA OBNOVU OGLASA (samo za vlasnika)
// ============================================
$showRenewButton = false;
$renewDaysLeft = null;
$isExpired = false;

if (isLoggedIn() && $_SESSION['user_id'] == $ad['user_id']) {
    $today = new DateTime();
    $expiresAt = new DateTime($ad['expires_at']);
    $daysLeft = $today->diff($expiresAt)->days;
    
    // Proveri da li je oglas istekao ili ističe za 7 ili manje dana
    $isExpired = ($ad['status'] == 'expired');
    $showRenewButton = ($isExpired || ($daysLeft <= 7 && $daysLeft >= 0));
    $renewDaysLeft = $daysLeft;
}

// ============================================
// DOHVATANJE KATEGORIJE I PODKATEGORIJE
// ============================================
$category = null;
$subcategory = null;

// Prvo dohvati kategoriju preko category_id
if (!empty($ad['category_id'])) {
    $category = getCategoryById($ad['category_id']);
}

// Ako postoji subcategory_id u oglasu, dohvati podkategoriju
if (!empty($ad['subcategory_id'])) {
    $subcategory = getCategoryById($ad['subcategory_id']);
} 
// Ako nema subcategory_id, ali kategorija ima parent_id (dakle sama je podkategorija)
elseif ($category && !empty($category['parent_id']) && $category['parent_id'] != 0) {
    $subcategory = $category;
    $category = getCategoryById($subcategory['parent_id']);
}

$pageTitle = $ad['title'] . ' - Rasprodaja.rs';
$pageDescription = substr(strip_tags($ad['description']), 0, 150);
$pageSpecificCSS = ['ad-detail.css'];
$pageSpecificJS = ['ad-detail.js'];

?>

<div class="container py-4">
   <?php
// Proveri status oglasa (dodaj ovo na vrh fajla posle dohvatanja podataka)
$isActive = ($ad['status'] === 'active');
$isSold = ($ad['status'] === 'sold');
$isExpired = ($ad['status'] === 'expired');
$isDeleted = ($ad['status'] === 'deleted');
$isOwner = isLoggedIn() && $_SESSION['user_id'] == $ad['user_id'];
?>
    <!-- ============================================ -->
    <!-- STATUS BANNER - AKO OGLAS NIJE AKTIVAN -->
    <!-- ============================================ -->
    <?php if (!$isActive): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert <?php 
                echo $isSold ? 'alert-success' : ($isExpired ? 'alert-warning' : 'alert-danger'); 
            ?> border-0 shadow-lg">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <?php if ($isSold): ?>
                            <div class="bg-white rounded-circle p-2 me-3">
                                <i class="fas fa-check-circle text-success fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">✅ OGLAS JE PRODAT</h4>
                                <p class="mb-0 small opacity-75">Ovaj oglas je uspešno prodat i više nije dostupan.</p>
                                <?php if ($isOwner): ?>
                                    <p class="mb-0 small mt-1">
                                        <a href="/create-ad" class="alert-link">
                                            <i class="fas fa-plus me-1"></i>Postavite novi oglas
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($isExpired): ?>
                            <div class="bg-white rounded-circle p-2 me-3">
                                <i class="fas fa-clock text-warning fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">⏰ OGLAS JE ISTEKAO</h4>
                                <p class="mb-0 small opacity-75">
                                    Rok za ovaj oglas je istekao.
                                    <?php if ($isOwner): ?>
                                        <button id="renew-ad-from-banner" class="btn btn-link btn-sm p-0 ms-1 alert-link">
                                            Kliknite ovde da ga obnovite.
                                        </button>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="bg-white rounded-circle p-2 me-3">
                                <i class="fas fa-trash-alt text-danger fa-2x"></i>
                            </div>
                            <div>
                                <h4 class="mb-0 fw-bold">🗑️ OGLAS JE OBRISAN</h4>
                                <p class="mb-0 small opacity-75">Ovaj oglas je trajno obrisan i više nije dostupan.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($isOwner && $isExpired): ?>
                    <button class="btn btn-warning btn-lg" id="renew-ad-btn-banner" data-ad-id="<?php echo $adId; ?>">
                        <i class="fas fa-sync-alt me-2"></i>Obnovi oglas
                    </button>
                    <?php elseif ($isOwner && $isSold): ?>
                    <a href="/create-ad" class="btn btn-primary btn-lg">
                        <i class="fas fa-plus me-2"></i>Postavi novi oglas
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    
    <!-- NASLOV OGLASA - VELIKI SA STATUS BADGE -->
    <div class="ad-title-header mb-4">
        <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
            <h1 class="display-5 fw-bold text-dark mb-0">
                <?php echo htmlspecialchars($ad['title']); ?>
            </h1>
            
            <!-- STATUS BADGE POKRAJ NASLOVA -->
            <?php if ($ad['status'] === 'active'): ?>
                <span class="badge bg-success fs-6 px-3 py-2">
                    <i class="fas fa-check-circle me-1"></i> Aktivan
                </span>
            <?php elseif ($ad['status'] === 'sold'): ?>
                <span class="badge bg-secondary fs-6 px-3 py-2">
                    <i class="fas fa-check-circle me-1"></i> Prodat
                </span>
            <?php elseif ($ad['status'] === 'expired'): ?>
                <span class="badge bg-warning text-dark fs-6 px-3 py-2">
                    <i class="fas fa-clock me-1"></i> Istekao
                </span>
            <?php elseif ($ad['status'] === 'deleted'): ?>
                <span class="badge bg-danger fs-6 px-3 py-2">
                    <i class="fas fa-trash-alt me-1"></i> Obrisan
                </span>
            <?php endif; ?>
        </div>
        
        <div class="d-flex flex-wrap align-items-center gap-3">
            <span class="h3 text-success mb-0">
                <?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?>
            </span>
            <?php if ($ad['price_negotiable'] == 1): ?>
                <span class="badge bg-warning fs-6">Po dogovoru</span>
            <?php endif; ?>
            <?php if ($ad['is_premium'] == 1): ?>
                <span class="badge bg-warning fs-6">
                    <i class="fas fa-crown me-1"></i> Premium
                </span>
            <?php endif; ?>
            <span class="text-muted">
                <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($ad['city']); ?>
            </span>
        </div>
    </div>

    <div class="row">
        <!-- Leva kolona - Slike -->
        <div class="col-lg-8">
            <div class="card mb-4 overflow-hidden">
                <div class="card-body p-0 position-relative">
                    <?php if (!empty($images)): ?>
                        <!-- Glavni prikaz slike sa swipe podrškom -->
                        <div class="image-display-container swipe-container" 
                             id="image-display-container"
                             style="position: relative; height: 500px; overflow: hidden; 
                                    touch-action: pan-y pinch-zoom;">
                            
                            <!-- Slike container za swipe efekat -->
                            <div class="images-wrapper" 
                                 id="images-wrapper"
                                 style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
                                        display: flex; transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);">
                                
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="image-slide" 
                                         data-image-index="<?php echo $index; ?>"
                                         style="flex: 0 0 100%; height: 100%; position: relative;">
                                        
                                        <!-- Blur pozadina za svaku sliku -->
                                        <div class="image-blur-background" 
                                             style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; 
                                                    background-image: url('<?php echo SITE_URL . $image['image_path']; ?>');
                                                    background-size: cover; background-position: center; 
                                                    filter: blur(20px) brightness(0.7); opacity: 0.6;">
                                        </div>
                                        
                                        <!-- Glavna slika -->
                                        <div class="main-image-wrapper" 
                                             style="position: relative; z-index: 2; height: 100%; 
                                                    display: flex; align-items: center; justify-content: center; 
                                                    padding: 20px;">
                                            <img src="<?php echo SITE_URL . $image['image_path']; ?>" 
                                                 alt="Slika <?php echo $index + 1; ?>" 
                                                 class="main-image-display"
                                                 data-image-id="<?php echo $index; ?>"
                                                 style="max-height: 100%; max-width: 100%; object-fit: contain; 
                                                        cursor: zoom-in; border-radius: 8px; 
                                                        box-shadow: 0 15px 40px rgba(0,0,0,0.25);">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Navigacione strelice -->
                            <?php if (count($images) > 1): ?>
                                <button class="image-nav-btn image-nav-prev" 
                                        style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); 
                                               z-index: 30; background: rgba(255,255,255,0.9); border: none; 
                                               width: 50px; height: 50px; border-radius: 50%; font-size: 1.5rem;
                                               box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                    <i class="fas fa-chevron-left"></i>
                                </button>
                                <button class="image-nav-btn image-nav-next"
                                        style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); 
                                               z-index: 30; background: rgba(255,255,255,0.9); border: none; 
                                               width: 50px; height: 50px; border-radius: 50%; font-size: 1.5rem;
                                               box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                                    <i class="fas fa-chevron-right"></i>
                                </button>
                                
                                <!-- Indikatori slika -->
                                <div class="image-indicators" 
                                     style="position: absolute; bottom: 20px; left: 0; right: 0; 
                                            z-index: 30; display: flex; justify-content: center; gap: 8px;">
                                    <?php foreach ($images as $index => $image): ?>
                                        <button class="image-indicator <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                data-image-index="<?php echo $index; ?>"
                                                style="width: 12px; height: 12px; border-radius: 50%; 
                                                       border: none; padding: 0; background: rgba(255,255,255,0.5); 
                                                       transition: all 0.3s ease; cursor: pointer;">
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Broj slike -->
                            <div class="image-counter">
                                <span id="current-image">1</span> / <span id="total-images"><?php echo count($images); ?></span>
                            </div>
                        </div>
                        
                        <!-- Thumbnail galerija -->
                        <div class="image-thumbnails-container p-3" 
                             style="border-top: 1px solid rgba(0,0,0,0.1); background: #f8f9fa;">
                            <div class="row g-2 justify-content-center" id="image-gallery">
                                <?php foreach ($images as $index => $image): ?>
                                    <div class="col-auto">
                                        <div class="thumbnail-wrapper position-relative <?php echo $index === 0 ? 'active' : ''; ?>" 
                                             style="width: 80px; height: 80px; border-radius: 8px; overflow: hidden; 
                                                    cursor: pointer; border: 3px solid transparent;"
                                             data-full-image="<?php echo SITE_URL . $image['image_path']; ?>"
                                             data-image-index="<?php echo $index; ?>">
                                            
                                            <img src="<?php echo SITE_URL . ($image['thumbnail_path'] ?? $image['image_path']); ?>" 
                                                 alt="Slika <?php echo $index + 1; ?>" 
                                                 class="thumbnail-image"
                                                 style="width: 100%; height: 100%; object-fit: cover;">
                                            
                                            <!-- Aktivni indikator -->
                                            <div class="thumbnail-active-indicator" 
                                                 style="position: absolute; bottom: 5px; right: 5px; 
                                                        width: 12px; height: 12px; background: #0d6efd; 
                                                        border-radius: 50%; border: 2px solid white; 
                                                        display: <?php echo $index === 0 ? 'block' : 'none'; ?>;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Bez slika placeholder -->
                        <div class="text-center py-5">
                            <div class="image-placeholder" 
                                 style="height: 400px; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
                                        display: flex; align-items: center; justify-content: center; border-radius: 12px;">
                                <i class="fas fa-image fa-5x text-muted"></i>
                            </div>
                            <p class="text-muted mt-3">Nema dostupnih slika</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Opis oglasa -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Opis</h5>
                </div>
                <div class="card-body">
                    <div class="ad-description">
                        <?php echo nl2br(htmlspecialchars($ad['description'])); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Desna kolona - Informacije -->
        <div class="col-lg-4">
            <!-- Osnovne informacije -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Osnovne informacije</h5>
                </div>
                <div class="card-body">
                    <h3 class="h4 text-primary mb-3"><?php echo htmlspecialchars($ad['title']); ?></h3>
                    
                    <div class="mb-3">
                        <span class="h3 text-success"><?php echo formatPriceWithCurrency($ad['price'], $ad['currency'] ?? 'RSD'); ?></span>
                        <?php if ($ad['price_negotiable'] == 1): ?>
                            <span class="badge bg-warning ms-2">Po dogovoru</span>
                        <?php endif; ?>
                    </div>
                    <!-- DUGMAD ZA DELJENJE -->
                    <div class="share-container mt-3">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="text-muted me-2">
                                <i class="fas fa-share-alt"></i> Podeli:
                            </span>
                            
                            <!-- WhatsApp -->
                            <a href="#" class="share-btn" data-platform="whatsapp" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na WhatsApp">
                                <i class="fab fa-whatsapp fa-xl" style="color: #25D366;"></i>
                            </a>
                            
                            <!-- Facebook -->
                            <a href="#" class="share-btn" data-platform="facebook" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na Facebook">
                                <i class="fab fa-facebook fa-xl" style="color: #1877F2;"></i>
                            </a>
                            
                            <!-- Twitter/X -->
                            <a href="#" class="share-btn" data-platform="twitter" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na Twitter">
                                <i class="fab fa-twitter fa-xl" style="color: #1DA1F2;"></i>
                            </a>
                            
                            <!-- LinkedIn -->
                            <a href="#" class="share-btn" data-platform="linkedin" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na LinkedIn">
                                <i class="fab fa-linkedin fa-xl" style="color: #0077B5;"></i>
                            </a>
                            
                            <!-- Pinterest -->
                            <a href="#" class="share-btn" data-platform="pinterest" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na Pinterest">
                                <i class="fab fa-pinterest fa-xl" style="color: #E60023;"></i>
                            </a>
                            
                            <!-- Email -->
                            <a href="#" class="share-btn" data-platform="email" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Pošalji Email">
                                <i class="fas fa-envelope fa-xl" style="color: #6c757d;"></i>
                            </a>
                            
                            <!-- Telegram -->
                            <a href="#" class="share-btn" data-platform="telegram" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na Telegram">
                                <i class="fab fa-telegram fa-xl" style="color: #0088cc;"></i>
                            </a>
                            
                            <!-- Viber -->
                            <a href="#" class="share-btn" data-platform="viber" 
                               data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>" 
                               data-title="<?php echo htmlspecialchars($ad['title']); ?>"
                               title="Podeli na Viber">
                                <i class="fab fa-viber fa-xl" style="color: #7360F2;"></i>
                            </a>
                            
                            <!-- Kopiraj link -->
                            <button class="btn btn-sm btn-outline-secondary" id="copy-link-btn" 
                                    data-url="<?php echo SITE_URL; ?>/ad/<?php echo $ad['slug']; ?>">
                                <i class="fas fa-link me-1"></i> Kopiraj link
                            </button>
                        </div>
                    </div>


                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <strong><i class="fas fa-tag me-2"></i>Kategorija:</strong>
                            <?php if ($category): ?>
                                <a href="/ads/category/<?php echo $category['slug']; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($subcategory): ?>
                                &nbsp;>&nbsp;
                                <a href="/ads/category/<?php echo $category['slug']; ?>/<?php echo $subcategory['slug']; ?>">
                                    <?php echo htmlspecialchars($subcategory['name']); ?>
                                </a>
                            <?php endif; ?>
                        </li>
                        <li class="mb-2">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>Lokacija:</strong>
                            <?php echo htmlspecialchars($ad['city']); ?>
                            <?php if (!empty($ad['address'])): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($ad['address']); ?></small>
                            <?php endif; ?>
                        </li>
                        <li class="mb-2">
                            <strong><i class="fas fa-box me-2"></i>Stanje:</strong>
                            <?php 
                            $conditionText = [
                                'new' => 'Novo',
                                'used' => 'Korišćeno', 
                                'broken' => 'Oštećeno'
                            ];
                            $conditionClass = [
                                'new' => 'success',
                                'used' => 'info',
                                'broken' => 'warning'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $conditionClass[$ad['item_condition']] ?? 'info'; ?>">
                                <?php echo $conditionText[$ad['item_condition']] ?? 'Korišćeno'; ?>
                            </span>
                        </li>
                        <li class="mb-2">
                            <strong><i class="fas fa-eye me-2"></i>Pregleda:</strong>
                            <?php echo number_format($ad['views'], 0, ',', '.'); ?>
                        </li>
                        <li class="mb-2">
                            <strong><i class="fas fa-calendar me-2"></i>Postavljeno:</strong>
                            <?php echo date('d.m.Y. H:i', strtotime($ad['created_at'])); ?>
                        </li>
                        <li>
                            <strong><i class="fas fa-clock me-2"></i>Važi do:</strong>
                            <?php echo date('d.m.Y.', strtotime($ad['expires_at'])); ?>
                        </li>
                    </ul>
                    
                    <?php if ($ad['is_premium'] == 1): ?>
                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-crown me-2"></i>
                            <strong>Premium oglas</strong>
                            <div class="small">Ističe: <?php echo date('d.m.Y.', strtotime($ad['premium_until'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            
            <!-- Kontakt korisnika - PRIKAZUJE SE SAMO AKO JE OGLAS AKTIVAN ILI SI VLASNIK -->
            <?php if ($isActive || $isOwner): ?>
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="fas fa-user-circle me-2"></i>
                        <?php echo $isActive ? 'Prodavac' : 'Vaš oglas'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($adOwner['avatar'])): ?>
                            <img src="<?php echo SITE_URL . $adOwner['avatar']; ?>" 
                                 alt="<?php echo htmlspecialchars($adOwner['username']); ?>" 
                                 class="rounded-circle me-3" 
                                 style="width: 60px; height: 60px; object-fit: cover;"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center me-3" 
                                 style="width: 60px; height: 60px;">
                                <i class="fas fa-user text-white fa-2x"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div>
                            <h6 class="mb-1">
                                <?php if ($isActive): ?>
                                    <a href="/profile/<?php echo $adOwner['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($adOwner['first_name'] . ' ' . $adOwner['last_name']); ?>
                                    </a>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($adOwner['first_name'] . ' ' . $adOwner['last_name']); ?>
                                <?php endif; ?>
                            </h6>
                            <small class="text-muted">
                                <i class="fas fa-calendar-alt me-1"></i>
                                Član od: <?php echo date('m/Y', strtotime($adOwner['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">
                            <i class="fas fa-star text-warning me-1"></i>
                            <?php if ($sellerReviewStats['total_reviews'] > 0): ?>
                                <strong><?php echo number_format($sellerReviewStats['avg_rating'], 1); ?>/5</strong>
                                <span class="review-stars ms-1" title="<?php echo $sellerReviewStats['total_reviews']; ?> recenzija"><?php for ($si = 1; $si <= 5; $si++): ?><?php echo $si <= round($sellerReviewStats['avg_rating']) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?><?php endfor; ?></span>
                                (<?php echo $sellerReviewStats['total_reviews']; ?> ocena<?php echo $sellerReviewStats['total_reviews'] > 1 ? '' : ''; ?>)
                            <?php else: ?>
                                Nema recenzija
                            <?php endif; ?>
                            |
                            <i class="fas fa-bullhorn me-1 ms-2"></i>
                            Oglasa: <?php echo $adOwner['ads_count'] ?? 0; ?>
                        </small>
                    </div>
                    
                    <!-- PRIKAZ TELEFONA - SAMO ZA AKTIVAN OGLAS ILI VLASNIKA -->
                    <?php if (!empty($adOwner['phone'])): ?>
                    <div class="mb-3">
                        <div class="d-grid">
                            <button class="btn btn-outline-success" id="show-phone-btn" 
                                    data-phone="<?php echo htmlspecialchars($adOwner['phone']); ?>">
                                <i class="fas fa-phone me-2"></i>
                                <?php echo $isOwner ? 'Vidi svoj broj telefona' : 'Vidi broj telefona'; ?>
                            </button>
                        </div>
                        <div id="phone-display" class="alert alert-success mt-2 text-center" style="display: none;">
                            <strong><i class="fas fa-phone me-2"></i>Broj telefona:</strong><br>
                            <span id="phone-number" class="fs-5 fw-bold"></span>
                            <button class="btn btn-sm btn-secondary mt-2" id="copy-phone-btn">
                                <i class="fas fa-copy me-1"></i>Kopiraj
                            </button>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <div class="alert alert-secondary text-center mb-0">
                            <i class="fas fa-phone me-2"></i>
                            <?php echo $isOwner ? 'Niste uneli broj telefona' : 'Prodavac nije uneo broj telefona'; ?>
                            <?php if ($isOwner): ?>
                                <a href="/profile" class="alert-link d-block mt-1">
                                    <i class="fas fa-edit me-1"></i>Dodajte broj telefona u profil
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- DUGMAD ZA AKCIJE -->
                    <div class="d-grid gap-2">
                        <?php if ($isActive && isLoggedIn() && $_SESSION['user_id'] != $ad['user_id']): ?>
                            <button class="btn btn-primary" id="send-message-btn" data-user-id="<?php echo $ad['user_id']; ?>">
                                <i class="fas fa-envelope me-2"></i>Pošalji poruku
                            </button>
                            
                        <?php elseif ($isActive && !isLoggedIn()): ?>
                            <a href="/login" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Prijavite se da kontaktirate
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($isOwner): ?>
                            <div class="btn-group-vertical w-100 gap-2">
                                <div class="btn-group w-100">
                                    <a href="/edit/<?php echo $adslug; ?>" class="btn btn-warning">
                                        <i class="fas fa-edit me-2"></i>Izmeni oglas
                                    </a>
                                    <button class="btn btn-danger" id="delete-ad-btn" data-ad-id="<?php echo $adId; ?>">
                                        <i class="fas fa-trash me-2"></i>Obriši oglas
                                    </button>
                                </div>
                                
                                <?php if ($showRenewButton): ?>
                                <button class="btn btn-success" id="renew-ad-btn" data-ad-id="<?php echo $adId; ?>">
                                    <i class="fas fa-sync-alt me-2"></i>
                                    <?php if ($isExpired): ?>
                                        Obnovi istekli oglas
                                    <?php else: ?>
                                        Obnovi oglas (ističe za <?php echo $renewDaysLeft; ?> dana)
                                    <?php endif; ?>
                                </button>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- PORUKA ZA POSETIOCE KADA OGLAS NIJE AKTIVAN -->
                    <?php if (!$isActive && !$isOwner): ?>
                    <div class="alert alert-secondary text-center mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        <?php if ($isSold): ?>
                            Ovaj oglas je prodat i više nije dostupan za kontakt.
                        <?php elseif ($isExpired): ?>
                            Ovaj oglas je istekao i više nije aktivan.
                        <?php else: ?>
                            Ovaj oglas je obrisan i više nije dostupan.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal za slanje poruke -->
<?php if (isLoggedIn() && $_SESSION['user_id'] != $ad['user_id']): ?>
<div class="modal fade" id="messageModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pošalji poruku</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="send-message-form">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $ad['user_id']; ?>">
                    <input type="hidden" name="ad_id" value="<?php echo $adId; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Poruka</label>
                        <textarea class="form-control" name="message" rows="4" required 
                                  placeholder="Pozdrav, interesuje me vaš oglas..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Otkaži</button>
                <button type="button" class="btn btn-primary" id="send-message-submit">Pošalji</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL ZA OBNOVU OGLASA -->
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
                    <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                </p>
                
                <?php if ($isExpired): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Vaš oglas je istekao!</strong>
                    <p class="mb-0 mt-1">Obnovite ga da bi se ponovo pojavio u pretrazi.</p>
                </div>
                <?php elseif ($renewDaysLeft !== null && $renewDaysLeft <= 7): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Oglas ističe za <?php echo $renewDaysLeft; ?> dana!</strong>
                    <p class="mb-0 mt-1">Obnovite ga sada da biste produžili važenje.</p>
                </div>
                <?php endif; ?>
                
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Šta dobijate obnovom:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Oglas će biti aktivan narednih 30 dana</li>
                        <li>Svi pregledi i statistika ostaju sačuvani</li>
                        <li>Premium status ostaje nepromenjen</li>
                        <li>Oglas će biti prikazan na vrhu liste</li>
                    </ul>
                </div>
                
                <?php 
                $userPackage = getUserPackageName($_SESSION['user_id'] ?? 0);
                if ($userPackage === 'free'): 
                ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Napomena za FREE paket:</strong>
                    <p class="mb-0 mt-1 small">
                        Obnavljanjem oglasa, on će zauzeti jedan od vaših 10 besplatnih oglasa.
                    </p>
                </div>
                <?php endif; ?>
                
                <form id="renew-ad-form">
                    <input type="hidden" name="ad_id" id="renew-ad-id" value="<?php echo $adId; ?>">
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
                    <strong><?php echo htmlspecialchars($ad['title']); ?></strong>
                </p>
                <p>Molimo vas da navedete razlog brisanja oglasa:</p>
                
                <form id="delete-ad-form">
                    <input type="hidden" name="ad_id" value="<?php echo $adId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="reason" id="reason-sold" value="sold" checked>
                        <label class="form-check-label" for="reason-sold">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Stvar ili usluga je prodata</strong>
                            <div class="small text-muted">Oglas će biti označen kao prodat i arhiviran</div>
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
                        <input class="form-check-input" type="radio" name="reason" id="reason-other" value="other">
                        <label class="form-check-label" for="reason-other">
                            <i class="fas fa-question-circle text-secondary me-2"></i>
                            <strong>Drugi razlog</strong>
                            <div class="small text-muted">Želim trajno da obrišem oglas</div>
                        </label>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Napomena:</strong> Ova akcija se ne može poništiti. Oglas će biti trajno uklonjen.
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

<!-- JavaScript za prikaz broja telefona -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Prikaz broja telefona
    const showPhoneBtn = document.getElementById('show-phone-btn');
    const phoneDisplay = document.getElementById('phone-display');
    const phoneNumberSpan = document.getElementById('phone-number');
    
    if (showPhoneBtn && phoneDisplay && phoneNumberSpan) {
        showPhoneBtn.addEventListener('click', function() {
            const phone = this.getAttribute('data-phone');
            phoneNumberSpan.textContent = phone;
            phoneDisplay.style.display = 'block';
            showPhoneBtn.style.display = 'none';
        });
    }
    
    // Kopiranje broja telefona
    const copyPhoneBtn = document.getElementById('copy-phone-btn');
    if (copyPhoneBtn) {
        copyPhoneBtn.addEventListener('click', function() {
            const phoneNumber = document.getElementById('phone-number').textContent;
            navigator.clipboard.writeText(phoneNumber).then(function() {
                const originalText = copyPhoneBtn.innerHTML;
                copyPhoneBtn.innerHTML = '<i class="fas fa-check me-1"></i>Kopirano!';
                setTimeout(function() {
                    copyPhoneBtn.innerHTML = originalText;
                }, 2000);
            });
        });
    }
});
// Obnavljanje oglasa

document.addEventListener('DOMContentLoaded', function() {
    // Dugme za obnavljanje
    const renewBtn = document.getElementById('renew-ad-btn');
    const renewModal = new bootstrap.Modal(document.getElementById('renewAdModal'));
    const confirmRenewBtn = document.getElementById('confirm-renew-btn');
    const renewAdId = document.getElementById('renew-ad-id');
    
    if (renewBtn) {
        renewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            renewModal.show();
        });
    }
    
    // Potvrda obnavljanja
    if (confirmRenewBtn) {
        confirmRenewBtn.addEventListener('click', async function() {
            const adId = renewAdId?.value;
            const csrfToken = document.querySelector('#renew-ad-form input[name="csrf_token"]')?.value;
            
            if (!adId) {
                showRenewAlert('danger', 'Greška: Nedostaje ID oglasa');
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
                    showRenewAlert('success', result.message);
                    renewModal.hide();
                    
                    // Osveži stranicu nakon 2 sekunde
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showRenewAlert('danger', result.message);
                    confirmRenewBtn.disabled = false;
                    confirmRenewBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Obnovi oglas';
                }
                
            } catch (error) {
                console.error('Greška:', error);
                showRenewAlert('danger', 'Greška pri obnavljanju oglasa. Pokušajte ponovo.');
                confirmRenewBtn.disabled = false;
                confirmRenewBtn.innerHTML = '<i class="fas fa-sync-alt me-2"></i>Obnovi oglas';
            }
        });
    }
    
    function showRenewAlert(type, message) {
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
});


document.addEventListener('DOMContentLoaded', function() {
    // Dugme za brisanje
    const deleteBtn = document.getElementById('delete-ad-btn');
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteAdModal'));
    const confirmDeleteBtn = document.getElementById('confirm-delete-btn');
    
    if (deleteBtn) {
        deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deleteModal.show();
        });
    }
    
    // Potvrda brisanja
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async function() {
            const adId = document.querySelector('input[name="ad_id"]')?.value;
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value;
            const selectedReason = document.querySelector('input[name="reason"]:checked')?.value;
            
            if (!adId || !selectedReason) {
                showDeleteAlert('danger', 'Greška: Nedostaju podaci za brisanje');
                return;
            }
            
            // Onemogući dugme da ne bi duplo kliknuli
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
                    // Prikaži uspešnu poruku
                    showDeleteAlert('success', result.message);
                    
                    // Zatvori modal
                    deleteModal.hide();
                    
                    // Preusmeri nakon 2 sekunde
                    setTimeout(() => {
                        window.location.href = '/profile';
                    }, 2000);
                } else {
                    showDeleteAlert('danger', result.message);
                    confirmDeleteBtn.disabled = false;
                    confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Obriši oglas';
                }
                
            } catch (error) {
                console.error('Greška:', error);
                showDeleteAlert('danger', 'Greška pri brisanju oglasa. Pokušajte ponovo.');
                confirmDeleteBtn.disabled = false;
                confirmDeleteBtn.innerHTML = '<i class="fas fa-trash-alt me-2"></i>Obriši oglas';
            }
        });
    }
    
    // Funkcija za prikazivanje alerta
    function showDeleteAlert(type, message) {
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
});


// Funkcija za deljenje na društvenim mrežama
(function() {
    'use strict';
    
    // Platform-specific share URLs
    const shareUrls = {
        whatsapp: (url, title) => `https://wa.me/?text=${encodeURIComponent(title)}%20-%20${encodeURIComponent(url)}`,
        facebook: (url) => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`,
        twitter: (url, title) => `https://twitter.com/intent/tweet?text=${encodeURIComponent(title)}&url=${encodeURIComponent(url)}`,
        linkedin: (url) => `https://www.linkedin.com/sharing/share-offsite/?u=${encodeURIComponent(url)}`,
        pinterest: (url, title) => `https://pinterest.com/pin/create/button/?url=${encodeURIComponent(url)}&description=${encodeURIComponent(title)}`,
        email: (url, title) => `mailto:?subject=${encodeURIComponent(title)}&body=${encodeURIComponent('Pogledajte ovaj oglas: ' + url)}`,
        telegram: (url, title) => `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`,
        viber: (url, title) => `viber://forward?text=${encodeURIComponent(title + ' - ' + url)}`
    };
    
    // Inicijalizacija dugmadi za deljenje
    function initShareButtons() {
        const shareButtons = document.querySelectorAll('.share-btn');
        
        shareButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const platform = this.dataset.platform;
                const url = this.dataset.url;
                const title = this.dataset.title;
                
                if (!platform || !url) {
                    console.error('Missing platform or URL');
                    return;
                }
                
                const shareUrl = shareUrls[platform] ? shareUrls[platform](url, title) : null;
                
                if (shareUrl) {
                    window.open(shareUrl, '_blank', 'width=600,height=400,resizable=yes,scrollbars=yes');
                } else {
                    console.error('Unknown platform:', platform);
                    showToast('Nije moguće deliti na ovu platformu', 'error');
                }
            });
        });
    }
    
    // Kopiranje linka u clipboard
    function initCopyLink() {
        const copyBtn = document.getElementById('copy-link-btn');
        
        if (copyBtn) {
            copyBtn.addEventListener('click', async function(e) {
                e.preventDefault();
                
                const url = this.dataset.url;
                
                if (!url) {
                    showToast('Greška: Link nije dostupan', 'error');
                    return;
                }
                
                if (navigator.clipboard && window.isSecureContext) {
                    try {
                        await navigator.clipboard.writeText(url);
                        showSuccessMessage(this, url);
                        return;
                    } catch (err) {
                        console.error('Clipboard API failed:', err);
                    }
                }
                
                try {
                    const textArea = document.createElement('textarea');
                    textArea.value = url;
                    textArea.style.position = 'fixed';
                    textArea.style.left = '-999999px';
                    textArea.style.top = '-999999px';
                    document.body.appendChild(textArea);
                    textArea.focus();
                    textArea.select();
                    
                    const successful = document.execCommand('copy');
                    document.body.removeChild(textArea);
                    
                    if (successful) {
                        showSuccessMessage(this, url);
                    } else {
                        throw new Error('execCommand failed');
                    }
                } catch (err) {
                    console.error('Fallback copy failed:', err);
                    showToast('Greška pri kopiranju. Link je: ' + url, 'error');
                }
            });
        }
    }
    
    // Dugme za obnavljanje iz banera
    const renewBannerBtn = document.getElementById('renew-ad-btn-banner');
    if (renewBannerBtn) {
        renewBannerBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const renewModal = new bootstrap.Modal(document.getElementById('renewAdModal'));
            renewModal.show();
        });
    }
    
    // Link za obnavljanje iz banera
    const renewLink = document.getElementById('renew-ad-from-banner');
    if (renewLink) {
        renewLink.addEventListener('click', function(e) {
            e.preventDefault();
            const renewModal = new bootstrap.Modal(document.getElementById('renewAdModal'));
            renewModal.show();
        });
    }
    
    // Prikaz uspešne poruke
    function showSuccessMessage(button, url) {
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-check me-1"></i> Kopirano!';
        button.classList.add('btn-success');
        button.classList.remove('btn-outline-secondary');
        
        showToast('Link je kopiran u clipboard!', 'success');
        
        setTimeout(() => {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    }
    
    // Toast notifikacija
    function showToast(message, type = 'success') {
        let toastContainer = document.getElementById('share-toast-container');
        
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.id = 'share-toast-container';
            toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            toastContainer.style.zIndex = '1060';
            document.body.appendChild(toastContainer);
        }
        
        const toastId = 'toast-' + Date.now() + '-' + Math.random().toString(36).substr(2, 5);
        const bgClass = type === 'success' ? 'bg-success' : 'bg-danger';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        
        const toastHTML = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0 mb-2" role="alert">
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas ${icon} me-2"></i>
                        ${message}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `;
        
        toastContainer.insertAdjacentHTML('beforeend', toastHTML);
        const toastElement = document.getElementById(toastId);
        
        if (typeof bootstrap !== 'undefined' && bootstrap.Toast) {
            const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 3000 });
            toast.show();
            toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
        } else {
            toastElement.classList.add('show');
            setTimeout(() => toastElement.remove(), 3000);
        }
    }
    
    // Pokreni sve kada se DOM učita
    document.addEventListener('DOMContentLoaded', function() {
        initShareButtons();
        initCopyLink();
    });
})();
</script>

<!-- PHP za Open Graph meta tagove - OVO MORA BITI U PHP DELU, NE U JAVASCRIPTU -->
<?php
// Open Graph meta tagovi za društvene mreže - dodaje se u <head>
$ogTitle = htmlspecialchars($ad['title']);
$ogDescription = htmlspecialchars(substr(strip_tags($ad['description']), 0, 200));
$ogUrl = SITE_URL . '/ad/' . $ad['id'] . '/' . $ad['slug'];
$ogImage = !empty($images[0]['image_path']) ? SITE_URL . $images[0]['image_path'] : SITE_URL . '/assets/images/defaults/og-image.jpg';

// Dodaj OG tagove u head preko PHP (OVO JE PRAVI NAČIN)
$GLOBALS['og_tags'] = [
    'og:title' => $ogTitle,
    'og:description' => $ogDescription,
    'og:image' => $ogImage,
    'og:url' => $ogUrl,
    'og:type' => 'product',
    'og:site_name' => 'Rasprodaja.rs',
    'twitter:card' => 'summary_large_image',
    'twitter:title' => $ogTitle,
    'twitter:description' => $ogDescription,
    'twitter:image' => $ogImage
];
?>


