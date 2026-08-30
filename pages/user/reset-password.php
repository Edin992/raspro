<?php
/**
 * pages/user/reset-password.php - Stranica za unos nove lozinke
 */

// PROVERI TOKEN U URL-U
$token = $_GET['token'] ?? '';
$validToken = false;
$tokenError = '';

if (empty($token)) {
    $tokenError = 'Token nije pronađen u URL-u.';
} else {
    // Proveri da li token postoji u bazi (pojednostavljeno)
    try {
        require_once __DIR__ . '/../../config/database.php';
        $db = getDatabaseConnection();
        
        $stmt = $db->prepare("
            SELECT id, reset_expires 
            FROM users 
            WHERE reset_token = ? 
            AND reset_expires > NOW()
        ");
        
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if ($user) {
            $validToken = true;
        } else {
            $tokenError = 'Token nije validan ili je istekao. Zatražite novi link.';
        }
    } catch (Exception $e) {
        error_log("Token validation error: " . $e->getMessage());
        $tokenError = 'Došlo je do greške pri proveri tokena.';
    }
}

$pageTitle = 'Nova lozinka - Rasprodaja.rs';
$pageDescription = 'Unesite novu lozinku za svoj nalog';
$pageSpecificCSS = [];
$pageSpecificJS = ['reset-password.js'];
//$showBreadcrumbs = true;
?>

<!-- BREADCRUMBS -->
<?php if ($showBreadcrumbs): ?>
<nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="?page=home">Početna</a></li>
        <li class="breadcrumb-item"><a href="?page=login">Prijava</a></li>
        <li class="breadcrumb-item active" aria-current="page">Nova lozinka</li>
    </ol>
</nav>
<?php endif; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            
            <?php if (!$validToken): ?>
                <!-- TOKEN ERROR CARD -->
                <div class="card border-danger shadow">
                    <div class="card-header bg-danger text-white text-center py-4">
                        <h4 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i> Problem sa tokenom
                        </h4>
                    </div>
                    
                    <div class="card-body p-4 p-md-5 text-center">
                        <div class="mb-4">
                            <i class="fas fa-key fa-3x text-danger mb-3"></i>
                            <h5 class="text-danger"><?php echo htmlspecialchars($tokenError); ?></h5>
                        </div>
                        
                        <p class="text-muted mb-4">
                            Vaš link za resetovanje je istekao ili je nevalidan.
                        </p>
                        
                        <div class="d-grid gap-2">
                            <a href="/forgot-password" class="btn btn-danger">
                                <i class="fas fa-redo me-2"></i> Zatraži novi link
                            </a>
                            <a href="/login" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i> Nazad na prijavu
                            </a>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- RESET PASSWORD CARD -->
                <div class="card shadow border-0">
                    <div class="card-header bg-success text-white text-center py-4">
                        <h4 class="mb-0">
                            <i class="fas fa-lock me-2"></i> Unesite novu lozinku
                        </h4>
                    </div>
                    
                    <div class="card-body p-4 p-md-5">
                        <!-- SUCCESS MESSAGE (hidden initially) -->
                        <div class="alert alert-success d-none" id="success-message">
                            <i class="fas fa-check-circle me-2"></i>
                            <span id="success-text"></span>
                        </div>
                        
                        <!-- ERROR MESSAGE (hidden initially) -->
                        <div class="alert alert-danger d-none" id="error-message">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <span id="error-text"></span>
                        </div>
                        
                        <!-- FORM -->
                        <form id="reset-password-form" novalidate>
                            <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <!-- PASSWORD FIELD -->
                            <div class="mb-4">
                                <label for="password" class="form-label">
                                    <i class="fas fa-key me-1"></i> Nova lozinka
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="password" 
                                           name="password"
                                           placeholder="Najmanje 8 karaktera"
                                           required
                                           minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">
                                    Lozinka mora imati najmanje 8 karaktera.
                                </div>
                                <small class="form-text text-muted">
                                    Koristite kombinaciju slova, brojeva i specijalnih karaktera.
                                </small>
                                
                                <!-- PASSWORD STRENGTH METER -->
                                <div class="mt-2">
                                    <div class="progress" style="height: 5px;">
                                        <div class="progress-bar" id="password-strength" 
                                             role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="text-muted" id="strength-text">Jačina lozinke</small>
                                </div>
                            </div>
                            
                            <!-- CONFIRM PASSWORD -->
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-key me-1"></i> Potvrdi lozinku
                                </label>
                                <div class="input-group">
                                    <input type="password" 
                                           class="form-control form-control-lg" 
                                           id="confirm_password" 
                                           name="confirm_password"
                                           placeholder="Ponovite lozinku"
                                           required
                                           minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-confirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="confirm-feedback">
                                    Lozinke se moraju poklapati.
                                </div>
                            </div>
                            
                            <!-- PASSWORD REQUIREMENTS -->
                            <div class="alert alert-light border mb-4">
                                <h6 class="alert-heading small mb-2">
                                    <i class="fas fa-list-check me-1"></i> Zahtevi za lozinku:
                                </h6>
                                <ul class="small mb-0">
                                    <li id="req-length" class="text-muted">
                                        <i class="fas fa-circle me-1"></i> Najmanje 8 karaktera
                                    </li>
                                    <li id="req-match" class="text-muted">
                                        <i class="fas fa-circle me-1"></i> Lozinke se poklapaju
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg" id="submit-btn">
                                    <span class="spinner-border spinner-border-sm d-none me-2" role="status"></span>
                                    <i class="fas fa-check me-2"></i> Postavi novu lozinku
                                </button>
                                
                                <a href="?page=login" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Odustani
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- CARD FOOTER -->
                    <div class="card-footer bg-light text-center py-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Nakon promene lozinke, bićete preusmereni na stranicu za prijavu.
                        </small>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- SECURITY REMINDER -->
            <div class="mt-4">
                <div class="card border-0 bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-shield-alt fa-2x text-primary mb-3"></i>
                        <p class="small mb-0">
                            Za bolju sigurnost, preporučujemo da koristite jedinstvenu lozinku 
                            koju ne koristite na drugim sajtovima.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>