<?php
/**
 * navbar.php - Glavna navigaciona traka (FIXED + BOTTOM MOBILE)
 * Sa admin panel linkom za administratore
 */





// Proveri da li je korisnik admin
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM admins WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $isAdmin = $stmt->fetchColumn() > 0;
    } catch (Exception $e) {
        $isAdmin = false;
    }
}
?>

<!-- DESKTOP NAVIGATION (vidljivo samo na desktop) -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top d-none d-lg-block">
    <div class="container">
        <!-- LOGO -->
        <a class="navbar-brand" href="/home/">
            <?php 
            $logoPath = (defined('SITE_URL') ? SITE_URL : '') . '/assets/images/logo/logo.png';
            $logoFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/logo/logo.png';
            if (file_exists($logoFile)):
            ?>
                <img src="<?php echo $logoPath; ?>" 
                     alt="Rasprodaja.rs" 
                     style="height: 60px; width: auto;"
                     class="img-fluid">
            <?php else: ?>
                <span class="fs-2 fw-bold">
                    <span class="text-dark">Rasprodaja</span>
                    <span class="text-primary">.rs</span>
                </span>
            <?php endif; ?>
        </a>
        
        <!-- DESKTOP MENU -->
        <div class="d-flex align-items-center">
            <!-- NAVIGACIJA -->
            <ul class="navbar-nav me-4">
                <li class="nav-item mx-1">
                    <a class="nav-link px-3 py-2 <?php echo (($page ?? 'home') == 'home') ? 'active fw-bold' : ''; ?>" 
                       href="/home/">
                        Početna
                    </a>
                </li>
                
                <li class="nav-item dropdown mx-1">
                    <a class="nav-link px-3 py-2 dropdown-toggle <?php echo (strpos($page ?? '', 'ads') !== false) ? 'active fw-bold' : ''; ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        Oglasi
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/ads/">Svi oglasi</a></li>
                        <li><a class="dropdown-item" href="/ads/premium/">Premium oglasi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/categories/">Kategorije</a></li>
                    </ul>
                </li>
                
                <li class="nav-item mx-1">
                    <a class="nav-link px-3 py-2 <?php echo (($page ?? '') == 'how-it-works') ? 'active fw-bold' : ''; ?>" 
                       href="/how-it-works/">
                        Kako radi?
                    </a>
                </li>
                
                <li class="nav-item mx-1">
                    <a class="nav-link px-3 py-2 <?php echo (($page ?? '') == 'contact') ? 'active fw-bold' : ''; ?>" 
                       href="/contact/">
                        Kontakt
                    </a>
                </li>
            </ul>
            
            <!-- PRETRAGA -->
            <form class="d-flex me-3" action="/ads" method="GET" style="width: 300px;">
                <input type="hidden" name="page" value="ads">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" 
                           placeholder="Pretraži oglase..."
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-outline-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- ZVONCE NOTIFIKACIJA (desktop) -->
            <button class="btn btn-sm btn-outline-secondary ms-2 position-relative" id="notif-bell-desktop"
                    type="button" title="Obaveštenja" aria-label="Obaveštenja">
                <i class="far fa-bell fs-6"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none notif-badge"
                      style="font-size:0.6rem;">0</span>
            </button>

            <!-- DARK MODE TOGGLE -->
            <button class="btn btn-sm btn-outline-secondary ms-2" id="theme-toggle" title="Promeni temu">
                <i class="fas fa-moon"></i>
            </button>
            
            <!-- DESKTOP AKCIJE -->
            <div class="d-flex align-items-center ms-2">
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): 
                    $unreadMessages = function_exists('getUnreadMessageCount') ? getUnreadMessageCount($_SESSION['user_id']) : 0;
                ?>
                    <a href="/create-ad/" class="btn btn-success me-2" 
                       style="min-width: 100px; border-radius: 8px;">
                        + Oglas
                    </a>
                    
                    <a href="/messages/" class="btn btn-outline-primary position-relative me-2" 
                       style="border-radius: 8px; min-width: 90px;">
                        Poruke
                        <?php if ($unreadMessages > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-pill"
                                  style="font-size: 0.7rem; padding: 0.2em 0.5em;">
                                <?php echo $unreadMessages; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" 
                                data-bs-toggle="dropdown" style="border-radius: 8px; min-width: 90px;">
                            Profil
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="/dashboard/"><i class="fas fa-tachometer-alt me-2"></i> Kontrolna tabla</a></li>
                            <li><a class="dropdown-item" href="/profile/<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user me-2"></i> Moj profil</a></li>
                            <li><a class="dropdown-item" href="/packages/"><i class="fas fa-crown me-2"></i> Paketi</a></li>
                            
                            <!-- ADMIN PANEL LINK - VIDLJIV SAMO ADMINIMA -->
                            <?php if ($isAdmin): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-warning fw-semibold" href="/admin/dashboard.php">
                                    <i class="fas fa-shield-alt me-2"></i> Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Odjavi se</a></li>
                        </ul>
                    </div>
                    
                <?php else: ?>
                    <a href="/login/" class="btn btn-outline-primary me-2" 
                       style="min-width: 90px; border-radius: 8px;">
                        Prijava
                    </a>
                    
                    <a href="/register/" class="btn btn-primary" 
                       style="min-width: 90px; border-radius: 8px;">
                        Registracija
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE BOTTOM NAVIGATION (vidljivo samo na mobilnim) -->
<nav class="navbar navbar-light bg-white fixed-bottom d-lg-none shadow-lg" 
     style="height: 70px; border-top: 1px solid rgba(0,0,0,0.1);">
    <div class="container-fluid px-0">
        <div class="d-flex justify-content-around align-items-center h-100 w-100">
            <!-- POČETNA -->
            <a href="/home/" 
               class="nav-link d-flex flex-column align-items-center justify-content-center text-center 
                      <?php echo (($page ?? 'home') == 'home') ? 'active text-primary' : 'text-secondary'; ?>"
               style="flex: 1; min-height: 70px; text-decoration: none;">
                <i class="fas fa-home fs-5 mb-1"></i>
                <span class="small">Početna</span>
            </a>
            
            <!-- OGLASI -->
            <a href="/ads/" 
               class="nav-link d-flex flex-column align-items-center justify-content-center text-center 
                      <?php echo (strpos($page ?? '', 'ads') !== false) ? 'active text-primary' : 'text-secondary'; ?>"
               style="flex: 1; min-height: 70px; text-decoration: none;">
                <i class="fas fa-th-list fs-5 mb-1"></i>
                <span class="small">Oglasi</span>
            </a>
            
            <!-- DODAJ OGLAS (CENTRALNO DUGME) -->
            <div class="position-relative" style="flex: 0 0 auto; margin-top: -25px;">
                <a href="/create-ad/" 
                   class="btn btn-success rounded-circle d-flex align-items-center justify-content-center"
                   style="width: 60px; height: 60px; box-shadow: 0 6px 20px rgba(var(--bs-success-rgb), 0.4);">
                    <i class="fas fa-plus fs-4"></i>
                </a>
            </div>
            
            <!-- PORUKE -->
            <?php $unreadCount = (isset($_SESSION['user_id']) && function_exists('getUnreadMessageCount')) ? getUnreadMessageCount($_SESSION['user_id']) : 0; ?>
            <a href="/messages/" 
               class="nav-link d-flex flex-column align-items-center justify-content-center text-center position-relative 
                      <?php echo (($page ?? '') == 'messages') ? 'active text-primary' : 'text-secondary'; ?>"
               style="flex: 1; min-height: 70px; text-decoration: none;">
                <i class="fas fa-envelope fs-5 mb-1"></i>
                <span class="small">Poruke</span>
                <?php if ($unreadCount > 0): ?>
                    <span class="position-absolute top-0 start-75 translate-middle badge bg-danger rounded-pill"
                          style="font-size: 0.6rem; padding: 0.15em 0.4em;">
                        <?php echo $unreadCount; ?>
                    </span>
                <?php endif; ?>
            </a>
            
            <!-- PROFIL SA DROPDOWN-OM (za mobilne) -->
            <div class="dropdown" style="flex: 1;">
                <a href="#" 
                   class="nav-link d-flex flex-column align-items-center justify-content-center text-center text-secondary dropdown-toggle"
                   data-bs-toggle="dropdown"
                   style="flex: 1; min-height: 70px; text-decoration: none;"
                   role="button">
                    <i class="fas fa-user fs-5 mb-1"></i>
                    <span class="small">
                        <?php echo isset($_SESSION['user_id']) ? 'Profil' : 'Prijava'; ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-center mb-2 w-100" 
                    style="bottom: 100%; top: auto; left: 0; right: 0; transform: none !important;">
                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                        <li><a class="dropdown-item" href="/dashboard/"><i class="fas fa-tachometer-alt me-2"></i> Kontrolna tabla</a></li>
                        <li><a class="dropdown-item" href="/profile/<?php echo $_SESSION['user_id']; ?>"><i class="fas fa-user me-2"></i> Moj profil</a></li>
                        <li><a class="dropdown-item" href="/packages/"><i class="fas fa-crown me-2"></i> Paketi</a></li>
                        
                        <!-- ADMIN PANEL LINK - VIDLJIV SAMO ADMINIMA (MOBILNI) -->
                        <?php if ($isAdmin): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-warning fw-semibold" href="/admin/dashboard.php">
                                <i class="fas fa-shield-alt me-2"></i> Admin Panel
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Odjavi se</a></li>
                    <?php else: ?>
                        <li><a class="dropdown-item" href="/login/"><i class="fas fa-sign-in-alt me-2"></i> Prijava</a></li>
                        <li><a class="dropdown-item" href="/register/"><i class="fas fa-user-plus me-2"></i> Registracija</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE SEARCH BAR + HAMBURGER (na vrhu ispod status bara) -->
<div class="d-lg-none bg-white border-bottom" style="padding-top: env(safe-area-inset-top);">
    <div class="container py-2">
        <div class="d-flex align-items-center gap-2">
            <!-- HAMBURGER - otvara side meni -->
            <button class="btn btn-outline-secondary flex-shrink-0" type="button"
                    data-bs-toggle="offcanvas" data-bs-target="#mobileSideMenu"
                    aria-controls="mobileSideMenu" aria-label="Otvori meni"
                    style="min-width:42px;">
                <i class="fas fa-bars"></i>
            </button>
            <form action="/ads/" method="GET" class="d-flex flex-grow-1">
                <input type="hidden" name="page" value="ads">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" 
                           placeholder="Pretraži oglase..."
                           value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
            <!-- ZVONCE NOTIFIKACIJA (mobilno) -->
            <a href="#" class="btn btn-outline-secondary flex-shrink-0 position-relative" 
               id="notif-bell-mobile" title="Obaveštenja" aria-label="Obaveštenja" style="min-width:42px;">
                <i class="far fa-bell"></i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none notif-badge">0</span>
            </a>
        </div>
    </div>
</div>

<!-- ============ MOBILE SIDE MENU (offcanvas) ============ -->
<?php
    $infoPages = [
        'how-it-works' => ['Kako radi?', 'fa-question-circle', '/how-it-works/'],
        'about'        => ['O nama', 'fa-info-circle', '/about/'],
        'contact'      => ['Kontakt', 'fa-envelope', '/contact/'],
        'faq'          => ['Pitanja i odgovori (FAQ)', 'fa-comments', '/faq/'],
        'safety'       => ['Bezbedna kupovina', 'fa-shield-alt', '/safety/'],
        'packages'     => ['Premium paketi', 'fa-crown', '/packages/'],
    ];
    $legalPages = [
        'terms'   => ['Uslovi korišćenja', 'fa-file-contract', '/terms/'],
        'privacy' => ['Politika privatnosti', 'fa-user-secret', '/privacy/'],
        'cookies' => ['Politika kolačića', 'fa-cookie-bite', '/cookies/'],
    ];
?>
<div class="offcanvas offcanvas-start" tabindex="-1" id="mobileSideMenu" aria-labelledby="mobileSideMenuLabel"
     style="width: 300px; max-width: 85vw;">
    <div class="offcanvas-header bg-primary text-white">
        <h5 class="offcanvas-title mb-0 text-white" id="mobileSideMenuLabel">
            <i class="fas fa-compass me-2"></i> Meni
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Zatvori"></button>
    </div>
    <div class="offcanvas-body p-0 d-flex flex-column">
        <!-- KORISNIK -->
        <div class="p-3 border-bottom">
            <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                <div class="d-flex align-items-center">
                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                         style="width:44px;height:44px;">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <strong class="d-block"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Korisnik'); ?></strong>
                        <a href="/logout" class="small text-danger text-decoration-none">
                            <i class="fas fa-sign-out-alt me-1"></i>Odjavi se
                        </a>
                    </div>
                </div>
                <div class="d-grid gap-2 mt-3">
                    <a class="btn btn-sm btn-outline-primary" href="/dashboard/"><i class="fas fa-tachometer-alt me-2"></i>Kontrolna tabla</a>
                    <a class="btn btn-sm btn-outline-primary" href="/messages/"><i class="fas fa-envelope me-2"></i>Poruke</a>
                    <a class="btn btn-sm btn-outline-primary" href="/profile/<?php echo (int)$_SESSION['user_id']; ?>"><i class="fas fa-user me-2"></i>Moj profil</a>
                </div>
            <?php else: ?>
                <p class="small text-muted mb-2">Prijavite se za slanje poruka i upravljanje oglasima</p>
                <div class="d-grid gap-2">
                    <a class="btn btn-sm btn-primary" href="/login/"><i class="fas fa-sign-in-alt me-2"></i>Prijava</a>
                    <a class="btn btn-sm btn-outline-primary" href="/register/"><i class="fas fa-user-plus me-2"></i>Registracija</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- GLAVNE STRANICE -->
        <nav class="nav flex-column p-2">
            <a class="nav-link py-2 <?php echo (($page ?? 'home') == 'home') ? 'active fw-bold text-primary' : ''; ?>" href="/home/">
                <i class="fas fa-home me-3 text-primary"></i> Početna
            </a>
            <a class="nav-link py-2 <?php echo (strpos($page ?? '', 'ads') !== false) ? 'active fw-bold text-primary' : ''; ?>" href="/ads/">
                <i class="fas fa-th-list me-3 text-primary"></i> Svi oglasi
            </a>
            <a class="nav-link py-2" href="/ads/premium/">
                <i class="fas fa-crown me-3 text-warning"></i> Premium oglasi
            </a>
            <a class="nav-link py-2" href="/categories/">
                <i class="fas fa-sitemap me-3 text-primary"></i> Kategorije
            </a>
            <a class="nav-link py-2 text-success fw-semibold" href="/create-ad/">
                <i class="fas fa-plus-circle me-3"></i> Postavi oglas
            </a>
        </nav>

        <!-- INFORMATIVNE -->
        <div class="px-3 pt-2 pb-1 small text-uppercase text-muted fw-bold">Informacije</div>
        <nav class="nav flex-column px-2 pb-2">
            <?php foreach ($infoPages as $key => $info): ?>
            <a class="nav-link py-2 <?php echo (($page ?? '') === $key) ? 'active fw-bold text-primary' : ''; ?>" href="<?php echo $info[2]; ?>">
                <i class="fas <?php echo $info[1]; ?> me-3 text-secondary"></i> <?php echo $info[0]; ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- PRAVNO + KOLACICI -->
        <div class="px-3 pt-1 pb-1 small text-uppercase text-muted fw-bold">Pravno i postavke</div>
        <nav class="nav flex-column px-2 pb-2">
            <?php foreach ($legalPages as $key => $info): ?>
            <a class="nav-link py-2 <?php echo (($page ?? '') === $key) ? 'active fw-bold text-primary' : ''; ?>" href="<?php echo $info[2]; ?>">
                <i class="fas <?php echo $info[1]; ?> me-3 text-secondary"></i> <?php echo $info[0]; ?>
            </a>
            <?php endforeach; ?>
            <a class="nav-link py-2" href="#" data-open-cookie-settings>
                <i class="fas fa-sliders-h me-3 text-secondary"></i> Podešavanja kolačića
            </a>
            <button class="nav-link py-2 text-start btn btn-link border-0" type="button" id="mobile-theme-toggle-side">
                <i class="fas fa-moon me-3 text-secondary"></i> <span class="mobile-theme-label">Tamna tema</span>
            </button>
        </nav>

        <div class="mt-auto p-3 border-top text-center">
            <small class="text-muted">
                Podrška: <a href="tel:+381601234567" class="text-decoration-none">060 123 4567</a>
            </small>
        </div>
    </div>
</div>

<!-- PRAZAN PROSTOR ZA BOTTOM NAV NA MOBILNIM -->
<div class="d-lg-none" style="height: 70px;"></div>

<!-- Theme toggle logika je centralizovana u layout/scripts.php -> initThemeSwitcher()
     (Fiksira bag: dva takmičarska handlera na istom dugmetu)

<style>
/* Popravka za mobilni dropdown meni - prikazuje se lepo na malim ekranima */
@media (max-width: 768px) {
    /* Dropdown meni za profil na mobilnom */
    .dropdown .dropdown-menu {
        position: fixed !important;
        bottom: 70px !important;
        top: auto !important;
        left: 10px !important;
        right: 10px !important;
        width: calc(100% - 20px) !important;
        min-width: unset !important;
        max-width: none !important;
        transform: none !important;
        border-radius: 16px !important;
        box-shadow: 0 -5px 25px rgba(0,0,0,0.15) !important;
        padding: 8px 0 !important;
    }
    
    /* Stavke menija */
    .dropdown .dropdown-menu .dropdown-item {
        padding: 12px 16px !important;
        font-size: 1rem !important;
        text-align: center !important;
        border-bottom: 1px solid rgba(0,0,0,0.05);
    }
    
    .dropdown .dropdown-menu .dropdown-item:last-child {
        border-bottom: none;
    }
    
    /* Ikonice u meniju */
    .dropdown .dropdown-menu .dropdown-item i {
        width: 24px;
        text-align: center;
        margin-right: 12px;
    }
    
    /* Dark mode podrška */
    body.dark-mode .dropdown .dropdown-menu {
        background-color: #16213e;
        border: 1px solid #0f3460;
    }
    
    body.dark-mode .dropdown .dropdown-menu .dropdown-item {
        color: #eee;
        border-bottom-color: #0f3460;
    }
}
/* Dark mode stilovi */
body.dark-mode {
    background-color: #1a1a2e;
    color: #eee;
}

body.dark-mode .navbar,
body.dark-mode .bg-white {
    background-color: #16213e !important;
}

body.dark-mode .navbar-light .navbar-nav .nav-link,
body.dark-mode .btn-outline-secondary {
    color: #eee;
}

body.dark-mode .border-bottom,
body.dark-mode .border-top {
    border-color: #0f3460 !important;
}

body.dark-mode .dropdown-menu {
    background-color: #16213e;
    border-color: #0f3460;
}

body.dark-mode .dropdown-item {
    color: #eee;
}

body.dark-mode .dropdown-item:hover {
    background-color: #0f3460;
}

body.dark-mode .form-control {
    background-color: #0f3460;
    border-color: #1a1a2e;
    color: #eee;
}

body.dark-mode .form-control::placeholder {
    color: #aaa;
}
</style>