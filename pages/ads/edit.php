<?php
/**
 * pages/ads/edit.php - Izmena postojećeg oglasa
 * SEO VERZIJA - Radi sa slug-om umesto ID
 */

// ============================================
// 1. DOHVATI OGLAS PREKO SLUG-A ILI ID
// ============================================

$ad = null;
$adId = 0;

// Ako imamo slug (novi SEO URL)
if (isset($_GET['slug']) && !empty($_GET['slug'])) {
    $slug = trim($_GET['slug']);
    
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT * FROM ads 
            WHERE slug = ? LIMIT 1
        ");
        $stmt->execute([$slug]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ad) {
            $_GET['id'] = $ad['id'];
        }
    } catch (Exception $e) {
        error_log("Edit ad by slug error: " . $e->getMessage());
    }
}
// Ako imamo id (stari URL) - backward compatibility
elseif (isset($_GET['id']) && !empty($_GET['id'])) {
    $adId = intval($_GET['id']);
    $ad = getAdById($adId);
}

// ============================================
// 2. PROVERA DA LI OGLAS POSTOJI
// ============================================

if (!$ad) {
    include 'pages/errors/404.php';
    exit();
}

// ============================================
// 3. PROVERA PRAVA KORISNIKA
// ============================================

if (!isLoggedIn() || $_SESSION['user_id'] != $ad['user_id']) {
    $_SESSION['error_message'] = 'Nemate pravo da menjate ovaj oglas';
    
    // Preusmeri na SEO URL ako postoji slug
    $redirectUrl = !empty($ad['slug']) ? '/ad/' . $ad['slug'] : '?page=ad-detail&id=' . $adId;
    redirect($redirectUrl);
}

// ============================================
// 4. DOHVATI PODATKE ZA FORMULAR
// ============================================

// Dohvati podatke korisnika (za grad)
$userData = getUserData($_SESSION['user_id']);
$userCity = $userData['city'] ?? '';

// Dohvati slike oglasa
$images = getAdImages($adId);

// Dohvati kategorije za dropdown
$mainCategories = getMainCategories();

// Postavi title za SEO
$pageTitle = 'Izmeni oglas - ' . htmlspecialchars($ad['title']);
$pageDescription = 'Izmenite vaš oglas na Rasprodaja.rs';
$pageSpecificJS = ['edit-ad.js'];

// Inline inicijalizacija
$inlineScripts = '
document.addEventListener("DOMContentLoaded", function() {
    if (typeof window.initEditAd === "function") {
        window.initEditAd();
    } else {
        console.error("initEditAd nije definisan! Proveri da li je edit-ad.js učitan.");
    }
});
';
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i> Izmeni oglas
                </h1>
                <!-- Link nazad koristi SEO URL ako postoji slug -->
                <a href="<?php echo !empty($ad['slug']) ? '/ad/' . $ad['slug'] : '?page=ad-detail&id=' . $adId; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Nazad na oglas
                </a>
            </div>
            
            <!-- PAKET INFO -->
            <div class="alert alert-info mb-4" id="package-info">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Vaš paket: <span id="package-name" class="text-uppercase fw-bold">Free</span></h5>
                        <p class="mb-0" id="package-limit-text">Učitavanje limita...</p>
                    </div>
                </div>
            </div>
            
            <!-- PROGRESS BAR -->
            <div class="progress mb-4" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     id="form-progress" style="width: 25%;"></div>
            </div>
            
            <!-- PROGRESS INDICATORS -->
            <div class="row text-center mb-4">
                <div class="col-3">
                    <div class="form-step-indicator completed">
                        <div class="indicator-circle">1</div>
                        <small class="d-block mt-1">Osnovno</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-step-indicator">
                        <div class="indicator-circle">2</div>
                        <small class="d-block mt-1">Opis</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-step-indicator">
                        <div class="indicator-circle">3</div>
                        <small class="d-block mt-1">Slike</small>
                    </div>
                </div>
                <div class="col-3">
                    <div class="form-step-indicator">
                        <div class="indicator-circle">4</div>
                        <small class="d-block mt-1">Pregled</small>
                    </div>
                </div>
            </div>
            
            <!-- FORMA ZA IZMENU -->
            <div class="card">
                <div class="card-body">
                    <!-- Promenjeno: forma šalje na API sa slug-om -->
                    <form id="edit-ad-form" enctype="multipart/form-data" action="/api/ads/update.php" method="POST">
                        <!-- Šaljemo i slug i id za backward compatibility -->
                        <input type="hidden" name="ad_id" value="<?php echo $adId; ?>">
                        <input type="hidden" name="ad_slug" value="<?php echo htmlspecialchars($ad['slug'] ?? ''); ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <input type="hidden" id="original-category-id" value="<?php echo $ad['category_id']; ?>">
                        <input type="hidden" id="original-subcategory-id" value="<?php echo $ad['subcategory_id'] ?? ''; ?>">
                        
                        <!-- KORAK 1: OSNOVNE INFORMACIJE -->
                        <div id="step-1" class="form-step">
                            <h5 class="border-bottom pb-2 mb-4">1. Osnovne informacije</h5>
                            
                            <!-- NASLOV -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Naslov oglasa *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($ad['title']); ?>"
                                       required minlength="10" maxlength="200">
                                <div class="form-text">
                                    Maksimalno 200 karaktera. Budite jasni i precizni.
                                </div>
                            </div>
                            
                            <!-- KATEGORIJA -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategorija *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Izaberite kategoriju</option>
                                    <?php foreach ($mainCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"
                                        <?php echo $ad['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- SUBKATEGORIJA -->
                            <div class="mb-3" id="subcategory-container" style="<?php echo (!empty($ad['subcategory_id'])) ? 'display: block;' : 'display: none;'; ?>">
                                <label for="subcategory_id" class="form-label">Podkategorija</label>
                                <select class="form-select" id="subcategory_id" name="subcategory_id">
                                    <option value="">Izaberite podkategoriju</option>
                                    <?php if (!empty($ad['subcategory_id'])): ?>
                                        <?php
                                        $subcat = getCategoryById($ad['subcategory_id']);
                                        if ($subcat):
                                        ?>
                                        <option value="<?php echo $subcat['id']; ?>" selected>
                                            <?php echo htmlspecialchars($subcat['name']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </select>
                                <div class="form-text">Specifikujte tačnije kategoriju za bolju pretragu.</div>
                            </div>
                            
                            <!-- CENA I VALUTA -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="price" class="form-label">Cena *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?php echo $ad['price']; ?>"
                                               required min="1" max="99999999" step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label for="currency" class="form-label">Valuta *</label>
                                    <select class="form-select" id="currency" name="currency" required>
                                        <option value="RSD" <?php echo ($ad['currency'] ?? 'RSD') == 'RSD' ? 'selected' : ''; ?>>RSD (DIN)</option>
                                        <option value="EUR" <?php echo ($ad['currency'] ?? 'RSD') == 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="item_condition" class="form-label">Stanje *</label>
                                    <select class="form-select" id="item_condition" name="item_condition" required>
                                        <option value="new" <?php echo $ad['item_condition'] == 'new' ? 'selected' : ''; ?>>Novo</option>
                                        <option value="used" <?php echo $ad['item_condition'] == 'used' ? 'selected' : ''; ?>>Korišćeno</option>
                                        <option value="broken" <?php echo $ad['item_condition'] == 'broken' ? 'selected' : ''; ?>>Oštećeno</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- POGODBA -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" 
                                       id="price_negotiable" name="price_negotiable" 
                                       value="1"
                                       <?php echo $ad['price_negotiable'] == 1 ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="price_negotiable">
                                    Cena je po dogovoru
                                </label>
                            </div>
                            
                            <!-- DUGMAD -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary" disabled>
                                    <i class="fas fa-arrow-left"></i> Nazad
                                </button>
                                <button type="button" class="btn btn-primary next-step" 
                                        data-next="step-2">
                                    Dalje <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- KORAK 2: OPIS I LOKACIJA -->
                        <div id="step-2" class="form-step d-none">
                            <h5 class="border-bottom pb-2 mb-4">2. Opis i lokacija</h5>
                            
                            <!-- OPIS -->
                            <div class="mb-3">
                                <label for="description" class="form-label">Opis *</label>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="6" required minlength="20"><?php echo htmlspecialchars($ad['description']); ?></textarea>
                                <div class="form-text">
                                    Minimum 20 karaktera. Opisujte što detaljnije.
                                </div>
                            </div>
                            
                            <!-- LOKACIJA -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="city" class="form-label">Grad/Mesto *</label>
                                    <input type="text" class="form-control" id="city" name="city" 
                                           value="<?php echo htmlspecialchars($userCity); ?>"
                                           required readonly>
                                    <small class="text-muted">Grad je preuzet iz vašeg profila i ne može se menjati.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">Adresa (opciono)</label>
                                    <input type="text" class="form-control" id="address" name="address" 
                                           value="<?php echo htmlspecialchars($ad['address'] ?? ''); ?>"
                                           placeholder="Ulica i broj">
                                </div>
                            </div>
                            
                            <!-- DUGMAD -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step" 
                                        data-prev="step-1">
                                    <i class="fas fa-arrow-left"></i> Nazad
                                </button>
                                <button type="button" class="btn btn-primary next-step" 
                                        data-next="step-3">
                                    Dalje <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- KORAK 3: SLIKE -->
                        <div id="step-3" class="form-step d-none">
                            <h5 class="border-bottom pb-2 mb-4">3. Fotografije</h5>
                            
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Postojeće fotografije možete videti ispod. Dodajte nove ili uklonite postojeće.
                            </div>
                            
                            <!-- POSTOJEĆE SLIKE -->
                            <?php if (!empty($images)): ?>
                            <div class="mb-4">
                                <h6>Postojeće fotografije:</h6>
                                <div class="row g-2" id="existing-images">
                                    <?php foreach ($images as $index => $image): ?>
                                    <div class="col-6 col-md-3 col-lg-2 existing-image-card" data-image-id="<?php echo $image['id']; ?>">
                                        <div class="card h-100">
                                            <img src="<?php echo SITE_URL . ($image['thumbnail_path'] ?? $image['image_path']); ?>" 
                                                 class="card-img-top" alt="Slika <?php echo $index + 1; ?>"
                                                 style="height: 100px; object-fit: cover;">
                                            <div class="card-body p-2 text-center">
                                                <div class="form-check mb-1">
                                                    <input class="form-check-input delete-image-checkbox" 
                                                           type="checkbox" 
                                                           name="delete_images[]" 
                                                           value="<?php echo $image['id']; ?>"
                                                           id="delete_img_<?php echo $image['id']; ?>">
                                                    <label class="form-check-label small" 
                                                           for="delete_img_<?php echo $image['id']; ?>">
                                                        Obriši
                                                    </label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input main-image-radio" 
                                                           type="radio" 
                                                           name="main_image_id" 
                                                           value="<?php echo $image['id']; ?>"
                                                           id="main_img_<?php echo $image['id']; ?>"
                                                           <?php echo $image['is_main'] == 1 ? 'checked' : ''; ?>>
                                                    <label class="form-check-label small" 
                                                           for="main_img_<?php echo $image['id']; ?>">
                                                        Glavna
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- NOVE SLIKE -->
                            <div class="upload-area mb-4" id="upload-area" style="
                                border: 3px dashed #ccc;
                                border-radius: 10px;
                                padding: 40px;
                                text-align: center;
                                background-color: #f9f9f9;
                                cursor: pointer;
                            ">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Dodajte nove fotografije</h5>
                                <p class="text-muted">ili kliknite za odabir fajlova</p>
                                <small class="text-muted d-block mb-2">
                                    Dozvoljeni formati: JPG, PNG, WebP | Maks. veličina: 5MB
                                </small>
                                <input type="file" id="image-upload" name="new_images[]" 
                                       multiple accept="image/*" style="display: none;">
                            </div>
                            
                            <!-- PREVIEW NOVIH SLIKA -->
                            <div class="mb-4" id="image-preview">
                                <div class="text-center text-muted py-4" id="no-images-message">
                                    <i class="fas fa-image fa-3x mb-3"></i>
                                    <p>Niste dodali nove fotografije</p>
                                </div>
                            </div>
                            
                            <!-- DUGMAD -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step" 
                                        data-prev="step-2">
                                    <i class="fas fa-arrow-left"></i> Nazad
                                </button>
                                <button type="button" class="btn btn-primary next-step" 
                                        data-next="step-4">
                                    Dalje <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                        
                        <!-- KORAK 4: PREGLED -->
                        <div id="step-4" class="form-step d-none">
                            <h5 class="border-bottom pb-2 mb-4">4. Pregled izmena</h5>
                            
                            <!-- PREVIEW -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h3 id="preview-title" class="mb-3"><?php echo htmlspecialchars($ad['title']); ?></h3>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Cena:</strong> 
                                                    <span id="preview-price" class="text-success fs-4">
                                                        <?php echo number_format($ad['price'], 0, ',', '.'); ?> RSD
                                                    </span>
                                                    <span id="preview-negotiable" class="badge bg-warning ms-2" 
                                                          style="display: <?php echo $ad['price_negotiable'] ? 'inline-block' : 'none'; ?>">
                                                        Po dogovoru
                                                    </span>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Lokacija:</strong> 
                                                    <span id="preview-location"><?php echo htmlspecialchars($userCity); ?></span>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Opis:</strong>
                                                <p id="preview-description" class="mt-2"><?php echo nl2br(htmlspecialchars($ad['description'])); ?></p>
                                            </div>
                                            <div>
                                                <strong>Stanje:</strong> 
                                                <span id="preview-condition" class="badge bg-info">
                                                    <?php 
                                                    $conditionMap = ['new' => 'Novo', 'used' => 'Korišćeno', 'broken' => 'Oštećeno'];
                                                    echo $conditionMap[$ad['item_condition']] ?? 'Korišćeno';
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Sažetak izmena</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Naslov: <span id="summary-title-short"><?php echo htmlspecialchars($ad['title']); ?></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Kategorija: <span id="summary-category"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Podkategorija: <span id="summary-subcategory"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Cena: <span id="summary-price"><?php echo number_format($ad['price'], 0, ',', '.'); ?> RSD</span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Fotografije: <span id="summary-images"><?php echo count($images); ?> postojećih</span>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- DUGMAD -->
                            <div class="d-flex justify-content-between">
                                <button type="button" class="btn btn-outline-secondary prev-step" 
                                        data-prev="step-3">
                                    <i class="fas fa-arrow-left"></i> Nazad
                                </button>
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fas fa-save me-2"></i> Sačuvaj izmene
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.form-step-indicator {
    cursor: pointer;
}
.form-step-indicator .indicator-circle {
    width: 40px;
    height: 40px;
    background-color: #e9ecef;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    transition: all 0.3s;
}
.form-step-indicator.completed .indicator-circle {
    background-color: #28a745;
    color: white;
}
.form-step-indicator.active .indicator-circle {
    background-color: #007bff;
    color: white;
    transform: scale(1.1);
}
.upload-area:hover {
    border-color: #007bff !important;
    background-color: #e7f1ff !important;
}
</style>