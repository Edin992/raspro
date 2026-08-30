<?php
/**
 * pages/user/verify-email.php - Stranica za verifikaciju email adrese
 */

$pageTitle = 'Verifikacija Email Adrese - Rasprodaja.rs';
$pageDescription = 'Verifikujte svoju email adresu za Rasprodaja.rs nalog';

// Ako je korisnik već prijavljen, preusmeri
if (isLoggedIn()) {
    $userData = getUserData($_SESSION['user_id']);
    if ($userData['is_verified']) {
        redirect('/profile');
    }
}

// Dohvati token iz URL-a
$token = $_GET['token'] ?? '';
$verificationResult = null;

if (!empty($token)) {
    // Proveri da li postoji funkcija za verifikaciju
    if (function_exists('verifyUserByToken')) {
        $verificationResult = verifyUserByToken($token);
        
        // Ako je uspešno, automatski prijavi korisnika
        if ($verificationResult['success'] && isset($verificationResult['user'])) {
            $user = $verificationResult['user'];
            
            // Prijavi korisnika
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['username'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_package'] = 'Free';
            $_SESSION['is_verified'] = true;
            
            // Dodaj poruku za uspešnu verifikaciju
            $_SESSION['success_message'] = 'Vaš email je uspešno verifikovan! Sada možete koristiti sve mogućnosti sajta.';
        }
    } else {
        $verificationResult = [
            'success' => false,
            'message' => 'Sistem za verifikaciju nije dostupan.'
        ];
    }
}
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-envelope-open-text me-2"></i> Verifikacija Email Adrese
                    </h1>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <?php if (!empty($token)): ?>
                        <!-- REZULTAT VERIFIKACIJE -->
                        <?php if ($verificationResult): ?>
                            <?php if ($verificationResult['success']): ?>
                                <!-- USPĚSNA VERIFIKACIJA -->
                                <div class="text-center py-4">
                                    <div class="mb-4">
                                        <i class="fas fa-check-circle fa-5x text-success"></i>
                                    </div>
                                    <h2 class="h4 mb-3">Čestitamo!</h2>
                                    <p class="lead mb-4">
                                        Vaš email je uspešno verifikovan. Sada možete koristiti sve mogućnosti našeg sajta.
                                    </p>
                                    
                                    <div class="d-grid gap-3 col-md-8 mx-auto">
                                        <a href="/dashboard" class="btn btn-primary btn-lg">
                                            <i class="fas fa-tachometer-alt me-2"></i> Idi na Kontrolnu Tablu
                                        </a>
                                        <a href="/create-ad" class="btn btn-success">
                                            <i class="fas fa-plus-circle me-2"></i> Postavi Prvi Oglas
                                        </a>
                                        <a href="/profile" class="btn btn-outline-secondary">
                                            <i class="fas fa-user-cog me-2"></i> Podešavanja Profila
                                        </a>
                                    </div>
                                    
                                    <div class="mt-5 pt-4 border-top">
                                        <h5 class="mb-3">Šta možete sada?</h5>
                                        <div class="row text-center">
                                            <div class="col-md-4 mb-3">
                                                <div class="p-3 border rounded">
                                                    <i class="fas fa-bullhorn fa-2x text-primary mb-2"></i>
                                                    <h6>Postavite oglase</h6>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="p-3 border rounded">
                                                    <i class="fas fa-comments fa-2x text-success mb-2"></i>
                                                    <h6>Pišite poruke</h6>
                                                </div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <div class="p-3 border rounded">
                                                    <i class="fas fa-star fa-2x text-warning mb-2"></i>
                                                    <h6>Nadogradite paket</h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- NEUSPĚSNA VERIFIKACIJA -->
                                <div class="text-center py-4">
                                    <div class="mb-4">
                                        <i class="fas fa-exclamation-triangle fa-5x text-danger"></i>
                                    </div>
                                    <h2 class="h4 mb-3">Verifikacija nije uspela</h2>
                                    <p class="lead mb-4">
                                        <?php echo htmlspecialchars($verificationResult['message']); ?>
                                    </p>
                                    
                                    <div class="alert alert-warning">
                                        <h5 class="alert-heading">Mogući razlozi:</h5>
                                        <ul class="mb-0">
                                            <li>Link je istekao (važi 24 sata)</li>
                                            <li>Email je već verifikovan</li>
                                            <li>Pogrešan ili nevalidan link</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-grid gap-3 col-md-8 mx-auto mt-4">
                                        <a href="/login" class="btn btn-primary">
                                            <i class="fas fa-sign-in-alt me-2"></i> Prijavite se
                                        </a>
                                        <a href="/contact" class="btn btn-outline-secondary">
                                            <i class="fas fa-question-circle me-2"></i> Zatražite pomoć
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    
                    <?php else: ?>
                        <!-- BEZ TOKENA - PRIKAŽI INSTRUKCIJE -->
                        <div class="text-center py-4">
                            <div class="mb-4">
                                <i class="fas fa-envelope fa-5x text-primary"></i>
                            </div>
                            <h2 class="h4 mb-3">Verifikacija putem email-a</h2>
                            <p class="lead mb-4">
                                Da biste verifikovali svoj nalog, potrebno je da kliknete na link koji smo Vam poslali na email adresu koju ste uneli prilikom registracije.
                            </p>
                            
                            <div class="card border-info mb-4">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-lightbulb me-2"></i> Šta treba da uradite:
                                </div>
                                <div class="card-body">
                                    <ol class="text-start">
                                        <li class="mb-2">Proverite svoj email (proverite i spam folder)</li>
                                        <li class="mb-2">Pronađite email od <strong>Rasprodaja.rs</strong></li>
                                        <li class="mb-2">Kliknite na dugme <strong>"Verifikuj svoj nalog"</strong></li>
                                        <li>To će Vas odvesti nazad na naš sajt gde će Vaš nalog biti aktiviran</li>
                                    </ol>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <h5 class="alert-heading">Niste dobili email?</h5>
                                <p class="mb-0">
                                    Proverite spam folder ili 
                                    <a href="/contact" class="alert-link">kontaktirajte nas</a> za pomoć.
                                    Link za verifikaciju važi 24 sata.
                                </p>
                            </div>
                            
                            <div class="d-grid gap-3 col-md-8 mx-auto">
                                <a href="/login" class="btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i> Prijavite se
                                </a>
                                <a href="/home" class="btn btn-secondary">
                                    <i class="fas fa-home me-2"></i> Nazad na početnu
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>