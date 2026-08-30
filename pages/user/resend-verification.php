<?php
/**
 * pages/user/resend-verification.php - Ponovno slanje verifikacionog email-a
 */

$pageTitle = 'Ponovno slanje verifikacionog email-a - Rasprodaja.rs';
$pageDescription = 'Ponovo pošaljite verifikacioni email za Vaš nalog';

$email = $_SESSION['user_email_temp'] ?? $_GET['email'] ?? '';

// Ako je korisnik već prijavljen i verifikovan, preusmeri
if (isLoggedIn() && isUserVerified()) {
    redirect('?page=dashboard');
}

$message = '';
$messageType = '';

// Ako je POST zahtev za ponovno slanje
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $email = trim($_POST['email']);
    
    try {
        $db = getDatabaseConnection();
        $stmt = $db->prepare("SELECT id, first_name, last_name FROM users WHERE email = ? AND is_verified = 0");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $result = resendVerificationEmail($user['id']);
            
            if ($result['success']) {
                $message = 'Novi verifikacioni email je poslat na ' . $email . '.';
                $messageType = 'success';
                
                // Sačuvaj u sesiji za display
                $_SESSION['success_message'] = $message;
            } else {
                $message = $result['message'];
                $messageType = 'danger';
            }
        } else {
            $message = 'Nije pronađen nalog sa ovim email-om koji nije verifikovan.';
            $messageType = 'warning';
        }
        
    } catch (Exception $e) {
        $message = 'Došlo je do greške: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
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
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="text-center mb-4">
                        <i class="fas fa-envelope fa-4x text-warning mb-3"></i>
                        <h2 class="h4 mb-3">Niste dobili verifikacioni email?</h2>
                        <p class="lead">
                            Unesite Vaš email ispod da bismo Vam ponovo poslali link za verifikaciju.
                        </p>
                    </div>
                    
                    <form method="POST" action="/resend-verification">
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
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-paper-plane me-2"></i> Pošalji ponovo
                            </button>
                            
                            <div class="text-center mt-3">
                                <a href="?page=login" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Nazad na prijavu
                                </a>
                                <a href="?page=contact" class="btn btn-outline-primary ms-2">
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