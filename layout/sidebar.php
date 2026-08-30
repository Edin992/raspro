<?php
/**
 * sidebar.php - Sidebar sa kategorijama i filterima
 * Učitava se samo na stranicama gde je potrebno
 */

// Proveri da li sidebar treba da se prikaže
if (!isset($showSidebar) || $showSidebar !== true) {
    return;
}

// Učitaj kategorije
$categories = getCategoriesWithCounts();
$currentCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;
?>

<!-- SIDEBAR -->
<div class="col-lg-3 mb-4">
    <div class="sticky-top" style="top: 80px;">
        
        <!-- KATEGORIJE -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    <i class="fas fa-list me-2"></i> Kategorije
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <a href="?page=ads" 
                       class="list-group-item list-group-item-action <?php echo ($currentCategory == 0) ? 'active' : ''; ?>">
                        <i class="fas fa-th-large me-2"></i> Sve kategorije
                        <span class="badge bg-secondary float-end"><?php echo getTotalAdCount(); ?></span>
                    </a>
                    
                    <?php foreach ($categories as $category): ?>
                        <?php if ($category['parent_id'] == null): ?>
                            <a href="?page=ads&category=<?php echo $category['id']; ?>" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center 
                                      <?php echo ($currentCategory == $category['id']) ? 'active' : ''; ?>">
                                <div>
                                    <i class="<?php echo $category['icon'] ?? 'fas fa-folder'; ?> me-2"></i>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </div>
                                <span class="badge bg-<?php echo ($currentCategory == $category['id']) ? 'light text-dark' : 'secondary'; ?>">
                                    <?php echo $category['ad_count']; ?>
                                </span>
                            </a>
                            
                            <!-- PODKATEGORIJE -->
                            <?php 
                            $subcategories = array_filter($categories, function($cat) use ($category) {
                                return $cat['parent_id'] == $category['id'];
                            });
                            
                            foreach ($subcategories as $subcat): 
                            ?>
                                <a href="?page=ads&category=<?php echo $subcat['id']; ?>" 
                                   class="list-group-item list-group-item-action ps-5 
                                          <?php echo ($currentCategory == $subcat['id']) ? 'active' : ''; ?>">
                                    <i class="fas fa-angle-right me-2"></i>
                                    <?php echo htmlspecialchars($subcat['name']); ?>
                                    <span class="badge bg-<?php echo ($currentCategory == $subcat['id']) ? 'light text-dark' : 'secondary'; ?> float-end">
                                        <?php echo $subcat['ad_count']; ?>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- FILTERI -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">
                <h6 class="mb-0">
                    <i class="fas fa-filter me-2"></i> Filteri
                </h6>
            </div>
            <div class="card-body">
                <form id="filter-form" method="GET" action="?page=ads">
                    <input type="hidden" name="page" value="ads">
                    
                    <!-- CENA -->
                    <div class="mb-3">
                        <label class="form-label">Cena (RSD)</label>
                        <div class="row g-2">
                            <div class="col">
                                <input type="number" class="form-control form-control-sm" 
                                       name="min_price" placeholder="Min" 
                                       value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>">
                            </div>
                            <div class="col">
                                <input type="number" class="form-control form-control-sm" 
                                       name="max_price" placeholder="Max" 
                                       value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- STANJE -->
                    <div class="mb-3">
                        <label class="form-label">Stanje</label>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="condition[]" 
                                   value="new" id="cond-new" 
                                   <?php echo (isset($_GET['condition']) && in_array('new', $_GET['condition'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cond-new">Novo</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="condition[]" 
                                   value="used" id="cond-used" 
                                   <?php echo (!isset($_GET['condition']) || (isset($_GET['condition']) && in_array('used', $_GET['condition']))) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cond-used">Korišćeno</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="condition[]" 
                                   value="broken" id="cond-broken" 
                                   <?php echo (isset($_GET['condition']) && in_array('broken', $_GET['condition'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="cond-broken">Oštećeno</label>
                        </div>
                    </div>
                    
                    <!-- LOKACIJA -->
                    <div class="mb-3">
                        <label class="form-label">Lokacija</label>
                        <select class="form-select form-select-sm" name="city">
                            <option value="">Sve lokacije</option>
                            <?php
                            $cities = getPopularCities();
                            foreach ($cities as $city):
                            ?>
                                <option value="<?php echo $city['name']; ?>" 
                                        <?php echo (isset($_GET['city']) && $_GET['city'] == $city['name']) ? 'selected' : ''; ?>>
                                    <?php echo $city['name']; ?> (<?php echo $city['count']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- SORTIRANJE -->
                    <div class="mb-3">
                        <label class="form-label">Sortiraj po</label>
                        <select class="form-select form-select-sm" name="sort">
                            <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>
                                Najnovije
                            </option>
                            <option value="price_asc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : ''; ?>>
                                Cena: niža → viša
                            </option>
                            <option value="price_desc" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : ''; ?>>
                                Cena: viša → niža
                            </option>
                            <option value="popular" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'popular') ? 'selected' : ''; ?>>
                                Najpopularnije
                            </option>
                        </select>
                    </div>
                    
                    <!-- SAMO PREMIUM -->
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="premium_only" 
                                   id="premium-only" value="1" 
                                   <?php echo (isset($_GET['premium_only']) && $_GET['premium_only'] == '1') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="premium-only">
                                <i class="fas fa-crown text-warning me-1"></i> Samo premium oglasi
                            </label>
                        </div>
                    </div>
                    
                    <!-- DUGMADI -->
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-1"></i> Primeni filtere
                        </button>
                        <a href="?page=ads" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-1"></i> Poništi filtere
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- PREMIUM PAKETI (reklama) -->
        <div class="card border-warning">
            <div class="card-header bg-warning">
                <h6 class="mb-0">
                    <i class="fas fa-bolt me-2"></i> Ubrzaj prodaju!
                </h6>
            </div>
            <div class="card-body">
                <p class="small mb-3">Postanite premium i povećajte vidljivost vaših oglasa.</p>
                <a href="?page=packages" class="btn btn-warning btn-sm w-100">
                    <i class="fas fa-crown me-1"></i> Vidi pakete
                </a>
            </div>
        </div>
    </div>
</div>