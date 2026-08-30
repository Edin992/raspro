<?php
/**
 * pages/ads/create.php - Stranica za kreiranje oglasa
 */

// Proveri da li je korisnik ulogovan
if (!isLoggedIn()) {
    $_SESSION['redirect_url'] = '/create-ad';
    redirect('/login');
}

// Učitaj podatke korisnika
$userData = getUserData($_SESSION['user_id']);
$userPackage = $userData['package'] ?? 'free';

// Postavi title
$pageTitle = 'Postavi novi oglas - Rasprodaja.rs';
$pageDescription = 'Postavite besplatni oglas na Rasprodaja.rs';
$pageSpecificJS = ['upload.js']; // VAŽNO: ovaj fajl mora postojati
$MainCategories = getMainCategories();
$userCity = '';
if (isset($_SESSION['user_id'])) {
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT city FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $userCity = $user['city'] ?? '';
}

// Inline JavaScript
$inlineScripts = <<<JS
window.pageInit = function() {
    console.log('Create ad page initialized');
    
    
    // Inicijalizuj kategorije
    initCategorySelector();
};

function checkUserLimits() {
    const userPackage = '{$userPackage}';
    const limits = {
        'free': { ads: 10, images: 10 },
        'silver': { ads: 25, images: 15 },
        'gold': { ads: 9999, images: 20 }
    };
    
    const limit = limits[userPackage] || limits.free;
    
    // Ažuriraj tekst na stranici
    const limitText = document.getElementById('package-limit-text');
    if (limitText) {
        limitText.textContent = `Vaš paket "\${userPackage}" dozvoljava \${limit.ads} oglasa i \${limit.images} slika po oglasu.`;
    }
    
    // Ograniči broj slika
    const imageInput = document.getElementById('image-upload');
    if (imageInput) {
        imageInput.setAttribute('data-max-files', limit.images);
    }
}

// Funkcija za učitavanje podkategorija
function loadSubcategories(categoryId) {
    const subcategoryContainer = document.getElementById('subcategory-container');
    const subcategorySelect = document.getElementById('subcategory_id');
    
    if (!categoryId || categoryId === '') {
        subcategoryContainer.classList.add('d-none');
        subcategorySelect.innerHTML = '<option value="">Izaberite podkategoriju</option>';
        return;
    }
    
    // Prikaži loading
    subcategorySelect.innerHTML = '<option value="">Učitavanje...</option>';
    subcategoryContainer.classList.remove('d-none');
    
    fetch(`\${SITE_CONFIG.url}/api/categories/children.php?parent_id=\${categoryId}`)
        .then(response => response.json())
        .then(data => {
            subcategorySelect.innerHTML = '<option value="">Izaberite podkategoriju </option>';
            
            if (data.success && data.categories && data.categories.length > 0) {
                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.name;
                    if (cat.ad_count > 0) {
                        option.textContent += ` (\${cat.ad_count} oglasa)`;
                    }
                    subcategorySelect.appendChild(option);
                });
            } else {
                // Nema podkategorija, možemo sakriti select
                subcategoryContainer.classList.add('d-none');
            }
        })
        .catch(error => {
            console.error('Error loading subcategories:', error);
            subcategorySelect.innerHTML = '<option value="">Greška pri učitavanju</option>';
        });
}

// Inicijalizacija category selektora
function initCategorySelector() {
    const categorySelect = document.getElementById('category_id');
    if (categorySelect) {
        categorySelect.addEventListener('change', function() {
            loadSubcategories(this.value);
        });
        
        // Ako je već izabrana kategorija (npr. nakon validacije), učitaj podkategorije
        if (categorySelect.value) {
            loadSubcategories(categorySelect.value);
        }
    }
}

JS;
?>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-12">
            <!-- HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-plus-circle me-2 text-primary"></i> Postavi novi oglas
                </h1>
                <a href="/ads" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i> Nazad na oglase
                </a>
            </div>
            
            <!-- PAKET INFO -->
            <div class="alert alert-info mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-info-circle fa-2x me-3"></i>
                    <div>
                        <h5 class="alert-heading mb-1">Trenutni paket: 
                            <span class="text-uppercase fw-bold"><?php echo $userPackage; ?></span>
                        </h5>
                        <p class="mb-0" id="package-limit-text">
                            <!-- JavaScript će popuniti -->
                        </p>
                        <!-- Dodato za prikaz feature-a -->
                        <div id="package-features" class="mt-2">
                            <!-- JavaScript će popuniti feature-e -->
                        </div>
                    </div>
                </div>
            </div>
            
                        <!-- PROGRESS BAR -->
            <div class="progress mb-4" style="height: 8px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                     id="form-progress" 
                     style="width: 25%;"></div>
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
            
            <!-- FORMA ZA OGLAS -->
            <div class="card">
                <div class="card-body">
                    <form id="create-ad-form" enctype="multipart/form-data" action="/api/ads/create.php">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <!-- KORAK 1: OSNOVNE INFORMACIJE -->
                        <div id="step-1" class="form-step">
                            <h5 class="border-bottom pb-2 mb-4">1. Osnovne informacije</h5>
                            
                            <!-- NASLOV -->
                            <div class="mb-3">
                                <label for="title" class="form-label">Naslov oglasa *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       required minlength="10" maxlength="200"
                                       placeholder="npr. iPhone 17 Pro Max 512GB">
                                <div class="form-text">
                                    Maksimalno 200 karaktera. Budite jasni i precizni.
                                </div>
                            </div>
                            
                           <!-- KATEGORIJA -->
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Kategorija *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Sve kategorije</option>
                                    <?php foreach ($MainCategories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>">
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                        <?php if ($cat['ad_count'] > 0): ?>
                                            <span class="text-muted">(<?php echo $cat['ad_count']; ?>)</span>
                                        <?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- SUBKATEGORIJA (sakrivena dok se ne izabere kategorija) -->
                            <div class="mb-3 d-none" id="subcategory-container">
                                <label for="subcategory_id" class="form-label">Podkategorija *</label>
                                <select class="form-select" id="subcategory_id" name="subcategory_id" required>
                                    <option value="">Izaberite podkategoriju</option>
                                </select>
                                <div class="form-text">Specifikujte tačnije kategoriju za bolju pretragu.</div>
                            </div>
                            
                            <!-- CENA I VALUTA -->
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="price" class="form-label">Cena *</label>
                                    <input type="number" class="form-control" id="price" name="price" 
                                           required min="1" max="99999999" step="0.01"
                                           placeholder="npr. 120000">
                                </div>
                                <div class="col-md-2">
                                    <label for="currency" class="form-label">Valuta *</label>
                                    <select class="form-select" id="currency" name="currency" required>
                                        <option value="RSD">RSD (DIN)</option>
                                        <option value="EUR">EUR (€)</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="condition" class="form-label">Stanje *</label>
                                    <select class="form-select" id="condition" name="item_condition" required>
                                        <option value="new">Novo</option>
                                        <option value="used" selected>Korišćeno</option>
                                        <option value="broken">Oštećeno</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- POGODBA -->
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" 
                                       id="price_negotiable" name="price_negotiable" >
                                <label class="form-check-label" for="price_negotiable">
                                    Po dogovoru
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
                                          rows="6" required minlength="20"
                                          placeholder="Detaljno opišite šta prodajete..."></textarea>
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
                                           required readonly disabled>
                                    <small class="text-muted">Grad je preuzet iz vašeg profila i ne može se menjati.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="address" class="form-label">Adresa (opciono)</label>
                                    <input type="text" class="form-control" id="address" name="address" 
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
                            
                            <!-- UPLOAD AREA -->
                            <div class="upload-area mb-4" id="upload-area" style="
                                border: 3px dashed #ccc;
                                border-radius: 10px;
                                padding: 40px;
                                text-align: center;
                                background-color: #f9f9f9;
                                cursor: pointer;
                            ">
                                <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                <h5>Prevucite fotografije ovde</h5>
                                <p class="text-muted">ili kliknite za odabir fajlova</p>
                                <small class="text-muted d-block mb-2">
                                    Dozvoljeni formati: JPG, PNG, WebP | Maks. veličina: 5MB
                                </small>
                                <input type="file" id="image-upload" name="images[]" 
                                       multiple accept="image/*" style="display: none;">
                            </div>
                            
                            <!-- PREVIEW -->
                            <div class="mb-4" id="image-preview">
                                <div class="text-center text-muted py-4" id="no-images-message">
                                    <i class="fas fa-image fa-3x mb-3"></i>
                                    <p>Još niste dodali fotografije</p>
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
                            <h5 class="border-bottom pb-2 mb-4">4. Pregled i objava</h5>
                            
                            <!-- PREVIEW -->
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h3 id="preview-title" class="mb-3">Naslov oglasa</h3>
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <strong>Cena:</strong> 
                                                    <span id="preview-price" class="text-success fs-4">0 RSD</span>
                                                    <span id="preview-negotiable" class="badge bg-warning ms-2">Pogodba</span>
                                                </div>
                                                <div class="col-6">
                                                    <strong>Lokacija:</strong> 
                                                    <span id="preview-location">Nepoznato</span>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <strong>Opis:</strong>
                                                <p id="preview-description" class="mt-2">Nema opisa</p>
                                            </div>
                                            <div>
                                                <strong>Stanje:</strong> 
                                                <span id="preview-condition" class="badge bg-info">Korišćeno</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="card">
                                        <div class="card-header bg-light">
                                            <h6 class="mb-0">Sažetak</h6>
                                        </div>
                                        <div class="card-body">
                                            <ul class="list-unstyled">
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Naslov: <span id="summary-title-short"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Kategorija: <span id="summary-category"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Podategorija: <span id="summary-subcategory"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Cena: <span id="summary-price"></span>
                                                </li>
                                                <li class="mb-2">
                                                    <i class="fas fa-check-circle text-success me-2"></i>
                                                    Fotografije: <span id="summary-images">0</span>
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
                                    <i class="fas fa-paper-plane me-2"></i> Objavi oglas
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>