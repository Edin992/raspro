<?php
/**
 * layout/topbar.php - Gornja traka (FULL RESPONSIVE)
 */

// Proveri da li je korisnik ulogovan
$isLoggedIn = isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);

if ($isLoggedIn) {
    // Koristimo nove funkcije
    $userName = getUserDisplayName($_SESSION['user_id']);
    $userPackage = getUserPackage($_SESSION['user_id']);
    $unreadMessages = getUnreadMessageCount($_SESSION['user_id']);
} else {
    $userName = '';
    $userPackage = 'free';
    $unreadMessages = 0;
}
?>

<!-- TOP BAR - RESPONSIVE -->
<div class="bg-dark text-white py-2">
    <div class="container">
        <div class="row align-items-center">
            <!-- KONTAKT INFO - VIDLJIV NA TABLETIMA I DESKTOPIMA -->
            <div class="col-md-6 d-none d-md-block">
                <div class="d-flex align-items-center">
                    <i class="fas fa-phone-alt me-2"></i>
                    <small>Podrška: <a href="tel:+381601234567" class="text-white text-decoration-none">060 123 4567</a></small>
                    <span class="mx-3">|</span>
                    <i class="fas fa-envelope me-2"></i>
                    <small>Email: <a href="mailto:info@rasprodaja.rs" class="text-white text-decoration-none">info@rasprodaja.rs</a></small>
                </div>
            </div>
            
            <!-- MOBILNI KONTAKT INFO (ikonice bez teksta) -->
            <div class="col-6 d-md-none">
                <div class="d-flex align-items-center">
                    <a href="tel:+381601234567" class="text-white me-3" title="Pozovi podršku">
                        <i class="fas fa-phone-alt"></i>
                    </a>
                    <a href="mailto:info@rasprodaja.rs" class="text-white" title="Pošalji email">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
            
            <!-- KORISNIČKI MENI -->
            <div class="col-md-6 col-6 text-end">
                <div class="d-flex align-items-center justify-content-end">
                    <?php if ($isLoggedIn): ?>
                        <!-- PRIJAVLJEN KORISNIK - MOBILNA VERZIJA -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle" type="button" 
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                <!-- Ikonica za mobilne -->
                                <span class="d-md-none">
                                    <i class="fas fa-user-circle"></i>
                                    <?php if ($unreadMessages > 0): ?>
                                        <span class="position-absolute top-0 start-100 translate-middle badge bg-danger rounded-pill" 
                                              style="font-size: 0.6rem; padding: 0.15em 0.4em;">
                                            <?php echo $unreadMessages; ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                                
                                <!-- Puni tekst za desktop -->
                                <span class="d-none d-md-inline">
                                    <i class="fas fa-user-circle me-1"></i>
                                    <?php echo htmlspecialchars($userName); ?>
                                    <?php if ($userPackage !== 'free'): ?>
                                        <span class="badge bg-<?php echo $userPackage === 'gold' ? 'warning' : 'secondary'; ?> ms-1">
                                            <?php echo strtoupper($userPackage); ?>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </button>
                            
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="?page=dashboard">
                                        <i class="fas fa-tachometer-alt me-2"></i> Kontrolna tabla
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="?page=profile">
                                        <i class="fas fa-user me-2"></i> Moj profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item position-relative" href="?page=messages">
                                        <i class="fas fa-envelope me-2"></i> Poruke
                                        <?php if ($unreadMessages > 0): ?>
                                            <span class="position-absolute top-50 translate-middle-y badge bg-danger rounded-pill" 
                                                  style="right: 10px; font-size: 0.7rem;">
                                                <?php echo $unreadMessages; ?>
                                            </span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="?page=create-ad">
                                        <i class="fas fa-plus-circle me-2"></i> Postavi oglas
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="?page=packages">
                                        <i class="fas fa-crown me-2"></i> Paketi
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item " href="/api/user/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Odjavi se
                                    </a>
                                </li>
                            </ul>
                        </div>
                        
                    <?php else: ?>
                        <!-- NEPRIJAVLJEN KORISNIK - RESPONSIVE -->
                        <!-- Mobilni (ikonice) -->
                        <div class="d-md-none">
                            <a href="?page=login" class="btn btn-sm btn-outline-light me-2" title="Prijavi se">
                                <i class="fas fa-sign-in-alt"></i>
                            </a>
                            <a href="?page=register" class="btn btn-sm btn-primary" title="Registruj se">
                                <i class="fas fa-user-plus"></i>
                            </a>
                        </div>
                        
                        <!-- Desktop (puni tekst) -->
                        <div class="d-none d-md-block">
                            <a href="?page=login" class="btn btn-sm btn-outline-light me-2">
                                <i class="fas fa-sign-in-alt me-1"></i> Prijavi se
                            </a>
                            <a href="?page=register" class="btn btn-sm btn-primary">
                                <i class="fas fa-user-plus me-1"></i> Registruj se
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- DARK MODE TOGGLE -->
                    <button class="btn btn-sm btn-outline-light ms-2" id="theme-toggle" title="Promeni temu">
                        <i class="fas fa-moon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MOBILNI LOGIN/REGISTER BANNER (samo za neprijavljene na malim ekranima) -->
<?php if (!$isLoggedIn): ?>
<div class="bg-primary text-white py-2 d-md-none text-center">
    <div class="container">
        <small>
            <a href="?page=login" class="text-white text-decoration-none fw-bold me-3">
                <i class="fas fa-sign-in-alt me-1"></i> Prijavi se
            </a>
            <span class="text-light">|</span>
            <a href="?page=register" class="text-white text-decoration-none fw-bold ms-3">
                <i class="fas fa-user-plus me-1"></i> Registruj se
            </a>
        </small>
    </div>
</div>
<?php endif; ?>