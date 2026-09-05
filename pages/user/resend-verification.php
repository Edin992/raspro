<?php
/**
 * pages/user/resend-verification.php - Ponovno slanje verifikacionog email-a
 */

$pageTitle = 'Ponovno slanje verifikacionog email-a - Rasprodaja.rs';
$pageDescription = 'Ponovo pošaljite verifikacioni email za Vaš nalog';

$email = $_SESSION['user_email_temp'] ?? $_GET['email'] ?? '';

// Ako je korisnik već prijavljen i verifikovan, preusmeri
if (isLoggedIn() && isUserVerified()) {
    redirect('/profile');
}

$message = '';
$messageType = '';

// Ako je POST zahtev za ponovno slanje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    // FIX: rate limit - endpoint salje email i mogao je biti zlostavljan
    if (function_exists('apc_fetch')) {
        $rlKey = 'raspro_resend_rl_' . md5((string)($_SERVER['REMOTE_ADDR'] ?? 'na'));
        $ok = false;
        $hits = (int) apc_fetch($rlKey, $ok);
        if ($ok && $hits >= 3) {
            $message = 'Previše zahteva. Sačekajte 10 minuta i pokušajte ponovo.';
            $messageType = 'danger';
            goto after_resend; // preskoci slanje
        }
        if (!@apc_inc($rlKey, 1, $incOk)) { apc_add($rlKey, 1, 600); }
    }
    $email = trim($_POST['email']);
    
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name, company_name, account_type FROM users WHERE email = ? AND is_verified = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Generiši novi token
            $verificationToken = bin2hex(random_bytes(16));
            $verificationExpires = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Ažuriraj token
            $stmt = $db->prepare("UPDATE users SET verification_token = ?, verification_expires = ? WHERE id = ?");
            $stmt->execute([$verificationToken, $verificationExpires, $user['id']]);
            
            // Pripremi ime
            if ($user['account_type'] === 'company' && !empty($user['company_name'])) {
                $userName = $user['company_name'];
            } else {
                $userName = trim($user['first_name'] . ' ' . $user['last_name']);
                if (empty($userName)) {
                    $userName = 'Korisniče';
                }
            }
            
            // Generiši link
            $siteUrl = defined('SITE_URL') ? SITE_URL : 'https://rasprodaja.rs';
            $verificationLink = $siteUrl . '/?page=verify-email&token=' . $verificationToken;
            
            // Generiši email
            $emailContent = generateVerificationEmail($userName, $verificationLink);
            
            // Pošalji email
            $emailSent = sendEmail(
                $email,
                'Verifikujte Vaš nalog na Rasprodaja.rs',
                $emailContent
            );
            
            if ($emailSent) {
                $message = 'Novi verifikacioni email je poslat na ' . $email . '.';
                $messageType = 'success';
                $_SESSION['success_message'] = $message;
            } else {
                $message = 'Greška pri slanju email-a. Molimo pokušajte kasnije.';
                $messageType = 'danger';
            }
        } else {
            $message = 'Nije pronađen nalog sa ovim email-om koji nije verifikovan.';
            $messageType = 'warning';
        }
        
    } catch (Exception $e) {
        $message = 'Došlo je do greške. Pokušajte ponovo kasnije.'; // FIX: interni detalji ne idu korisniku
        $messageType = 'danger';
        error_log("Resend verification error: " . $e->getMessage());
    }
}

after_resend:
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-dark text-center py-4">
                    <h1 class="h3 mb-0">
                        <i class="fas fa-paper-plane me-2"></i> Ponovno pošaljite verifikacioni email
                    </h1>
                </div>
                
                <div class="card-body p-4 p-md-5">
                    <div id="alert-container">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($message); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope fa-4x text-warning mb-3"></i>
                        <h2 class="h4 mb-3">Niste dobili verifikacioni email?</h2>
                        <p class="lead">
                            Unesite Vaš email ispod da bismo Vam ponovo poslali link za verifikaciju.
                        </p>
                    </div>
                    
                    <form id="resend-form" method="POST" action="/resend-verification">
                        <div class="mb-4">
                            <label for="email" class="form-label">Vaša email adresa</label>
                            <input type="email" 
                                   class="form-control form-control-lg" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   required
                                   placeholder="unesite@vas.email">
                            <div class="form-text">
                                Email adresa koju ste koristili prilikom registracije
                            </div>
                        </div>
                        
                        <div class="d-grid gap-3">
                            <button type="submit" id="submit-btn" class="btn btn-warning btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Pošalji ponovo
                            </button>
                            
                            <div class="text-center mt-3">
                                <a href="/login" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Nazad na prijavu
                                </a>
                                <a href="/contact" class="btn btn-outline-primary ms-2">
                                    <i class="fas fa-question-circle me-2"></i> Pomoc
                                </a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="mt-5 pt-4 border-top">
                        <h5 class="mb-3"><i class="fas fa-lightbulb me-2 text-info"></i> Saveti:</h5>
                        <ul class="list-unstyled">
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Proverite spam/junk folder</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Proverite da li ste uneli tačnu email adresu</li>
                            <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Link za verifikaciju važi 24 sata</li>
                            <li><i class="fas fa-check-circle text-success me-2"></i> Ako i dalje imate problema, kontaktirajte nas</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <p class="text-muted">
                    Već imate verifikovan nalog? 
                    <a href="/login" class="text-decoration-none">Prijavite se</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('resend-form');
    const submitBtn = document.getElementById('submit-btn');
    const alertContainer = document.getElementById('alert-container');
    const emailInput = document.getElementById('email');
    
    if (!form) return;
    
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Validacija
        const email = emailInput.value.trim();
        if (!email || !isValidEmail(email)) {
            showAlert('danger', 'Unesite validnu email adresu');
            return;
        }
        
        // Prikaži loading
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Slanje...';
        submitBtn.disabled = true;
        
        try {
            const response = await fetch('/api/user/resend-verification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ email: email })
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert('success', result.message);
                emailInput.value = '';
            } else {
                showAlert('danger', result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('danger', 'Greška pri povezivanju sa serverom');
        } finally {
            // Vrati dugme u prvobitno stanje
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    });
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
    
    function showAlert(type, message) {
        alertContainer.innerHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Auto dismiss after 5 seconds
        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 150);
            }
        }, 5000);
    }
});
</script>
