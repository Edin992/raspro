<?php
/**
 * pages/contact.php - Kontakt stranica
 */

$pageTitle = 'Kontaktirajte nas - Rasprodaja.rs';
$pageDescription = 'Kontaktirajte nas za sva pitanja, sugestije ili podršku. Naš tim će vam odgovoriti u najkraćem mogućem roku.';
$pageSpecificCSS = ['contact.css'];
$showBreadcrumbs = true;
$currentPage = 'contact';

// Obrada kontakt forme ako je poslat POST zahtev
$formSubmitted = false;
$formError = '';
$formSuccess = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validacija
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Ime i prezime je obavezno';
    }
    
    if (empty($email)) {
        $errors[] = 'Email adresa je obavezna';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Unesite validnu email adresu';
    }
    
    if (empty($subject)) {
        $errors[] = 'Naslov poruke je obavezan';
    }
    
    if (empty($message)) {
        $errors[] = 'Poruka je obavezna';
    } elseif (strlen($message) < 10) {
        $errors[] = 'Poruka mora imati najmanje 10 karaktera';
    }
    
    if (empty($errors)) {
        // ============================================
        // SLANJE EMAIL-a ADMINU
        // ============================================
        
        $adminEmail = 'kontakt@rasprodaja.rs';
        $siteName = 'Rasprodaja.rs';
        
        // HTML sadržaj email-a za admina
        $htmlContent = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>Kontakt poruka sa sajta</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #0d6efd; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .field { margin-bottom: 15px; }
                .label { font-weight: bold; color: #0d6efd; }
                .footer { text-align: center; padding: 15px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>📧 Nova kontakt poruka</h2>
                </div>
                <div class='content'>
                    <div class='field'>
                        <div class='label'>👤 Ime i prezime:</div>
                        <div>" . htmlspecialchars($name) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>📧 Email adresa:</div>
                        <div>" . htmlspecialchars($email) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>📞 Telefon:</div>
                        <div>" . (empty($phone) ? 'Nije uneto' : htmlspecialchars($phone)) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>🏷️ Naslov:</div>
                        <div>" . htmlspecialchars($subject) . "</div>
                    </div>
                    <div class='field'>
                        <div class='label'>💬 Poruka:</div>
                        <div style='white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message)) . "</div>
                    </div>
                    <hr>
                    <div class='field'>
                        <div class='label'>🌐 IP adresa posetioca:</div>
                        <div>" . ($_SERVER['REMOTE_ADDR'] ?? 'Nepoznato') . "</div>
                    </div>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " " . $siteName . " | Kontakt forma
                </div>
            </div>
        </body>
        </html>
        ";
        
        // Plain text verzija za admina
        $plainText = "
        NOVA KONTAKT PORUKA SA SAJTA
        ================================
        
        Ime i prezime: $name
        Email: $email
        Telefon: " . (empty($phone) ? 'Nije uneto' : $phone) . "
        Naslov: $subject
        
        Poruka:
        $message
        
        --------------------------------
        IP adresa: " . ($_SERVER['REMOTE_ADDR'] ?? 'Nepoznato') . "
        Datum: " . date('d.m.Y H:i:s') . "
        ";
        
        // Pošalji email adminu
        $emailSent = sendEmail($adminEmail, "Kontakt: $subject od $name", $htmlContent, $plainText);
        
        if ($emailSent) {
            // ============================================
            // LEP HTML TEMPLATE ZA POTVRDNU PORUKU KORISNIKU
            // ============================================
            
            $userHtmlContent = "
            <!DOCTYPE html>
            <html lang='sr'>
            <head>
                <meta charset='UTF-8'>
                <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                <title>Potvrda kontakta - Rasprodaja.rs</title>
                <style>
                    body {
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        margin: 0;
                        padding: 0;
                        background-color: #f8f9fa;
                    }
                    .email-container {
                        max-width: 600px;
                        margin: 0 auto;
                        background-color: #ffffff;
                        border-radius: 10px;
                        overflow: hidden;
                        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                    }
                    .email-header {
                        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                        color: white;
                        padding: 30px 20px;
                        text-align: center;
                    }
                    .email-header h1 {
                        margin: 0;
                        font-size: 24px;
                        font-weight: 600;
                    }
                    .email-logo {
                        font-size: 28px;
                        font-weight: bold;
                        margin-bottom: 10px;
                    }
                    .email-body {
                        padding: 30px;
                    }
                    .welcome-text {
                        font-size: 18px;
                        margin-bottom: 20px;
                        color: #2d3748;
                    }
                    .message-box {
                        background-color: #f7fafc;
                        border-left: 4px solid #0d6efd;
                        padding: 20px;
                        margin: 25px 0;
                        border-radius: 4px;
                    }
                    .message-content {
                        background-color: #e9ecef;
                        padding: 15px;
                        border-radius: 8px;
                        margin: 15px 0;
                        font-style: italic;
                    }
                    .info-box {
                        background-color: #f8f9fa;
                        border: 1px solid #dee2e6;
                        padding: 15px;
                        margin: 20px 0;
                        border-radius: 8px;
                    }
                    .button {
                        display: inline-block;
                        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
                        color: white;
                        text-decoration: none;
                        padding: 12px 25px;
                        border-radius: 5px;
                        font-weight: bold;
                        margin: 20px 0;
                    }
                    .footer {
                        background-color: #f8f9fa;
                        padding: 20px;
                        text-align: center;
                        color: #718096;
                        font-size: 14px;
                        border-top: 1px solid #e2e8f0;
                    }
                    .social-links {
                        margin: 15px 0;
                    }
                    .social-link {
                        display: inline-block;
                        margin: 0 10px;
                        color: #4a5568;
                        text-decoration: none;
                    }
                    @media (max-width: 600px) {
                        .email-body {
                            padding: 20px;
                        }
                        .button {
                            display: block;
                            text-align: center;
                        }
                    }
                </style>
            </head>
            <body>
                <div class='email-container'>
                    <div class='email-header'>
                        <div class='email-logo'>📧 Rasprodaja.rs</div>
                        <h1>Potvrda kontakta</h1>
                    </div>
                    
                    <div class='email-body'>
                        <p class='welcome-text'>Poštovani/a <strong>" . htmlspecialchars($name) . "</strong>,</p>
                        
                        <p>Hvala vam što ste nas kontaktirali putem kontakt forme na sajtu <strong>Rasprodaja.rs</strong>.</p>
                        
                        <div class='message-box'>
                            <h3 style='margin-top: 0; color: #2d3748;'>Vaša poruka je uspešno primljena!</h3>
                            <p>Naš tim će vam odgovoriti u najkraćem mogućem roku (obično u roku od 24 časa radnim danima).</p>
                        </div>
                        
                        <div class='info-box'>
                            <h4 style='margin-top: 0; color: #0d6efd;'>
                                <i class='fas fa-info-circle'></i> Podaci o vašoj poruci:
                            </h4>
                            <table style='width: 100%; border-collapse: collapse;'>
                                <tr>
                                    <td style='padding: 5px 0; font-weight: bold;'>📅 Datum slanja:</td>
                                    <td style='padding: 5px 0;'>" . date('d.m.Y. H:i:s') . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; font-weight: bold;'>🏷️ Naslov:</td>
                                    <td style='padding: 5px 0;'>" . htmlspecialchars($subject) . "</td>
                                </tr>
                                <tr>
                                    <td style='padding: 5px 0; font-weight: bold;'>✉️ Vaš email:</td>
                                    <td style='padding: 5px 0;'>" . htmlspecialchars($email) . "</td>
                                </tr>
                                " . (!empty($phone) ? "
                                <tr>
                                    <td style='padding: 5px 0; font-weight: bold;'>📞 Vaš telefon:</td>
                                    <td style='padding: 5px 0;'>" . htmlspecialchars($phone) . "</td>
                                </tr>" : "") . "
                            </table>
                        </div>
                        
                        <div class='message-content'>
                            <strong style='color: #0d6efd;'>📝 Sadržaj vaše poruke:</strong><br>
                            <div style='margin-top: 10px; white-space: pre-wrap;'>" . nl2br(htmlspecialchars($message)) . "</div>
                        </div>
                        
                        <div style='margin: 25px 0; padding: 15px; background-color: #e7f1ff; border-radius: 8px; text-align: center;'>
                            <strong>📌 Šta sledi?</strong><br>
                            Naš tim pregleda vašu poruku i odgovoriće vam u najkraćem roku na email adresu: <strong>" . htmlspecialchars($email) . "</strong>
                        </div>
                        
                        <p style='margin-top: 20px;'>
                            Ukoliko imate dodatnih pitanja, slobodno odgovorite na ovaj email ili nas kontaktirajte putem telefona.
                        </p>
                        
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='" . SITE_URL . "' class='button' style='color: white; text-decoration: none;'>
                                🌐 Posetite naš sajt
                            </a>
                        </div>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>Rasprodaja.rs</strong> - Najveća oglasna tabla u Srbiji</p>
                        <p>Kupujte i prodajte brzo, lako i sigurno</p>
                        
                        <div class='social-links'>
                            <a href='" . SITE_URL . "/contact' class='social-link'>📧 Kontaktirajte nas</a><br>
                            <a href='" . SITE_URL . "/faq' class='social-link'>❔ Pomoć i podrška</a>
                        </div>
                        
                        <p style='font-size: 12px; color: #a0aec0; margin-top: 20px;'>
                            &copy; " . date('Y') . " Rasprodaja.rs. Sva prava zadržana.<br>
                            Ovaj email je automatski generisan kao potvrda vaše kontakt poruke.
                        </p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // Plain text verzija za korisnika
            $userPlainText = "
POŠTOVANI/A " . strtoupper($name) . ",
================================

Hvala vam što ste nas kontaktirali putem kontakt forme na sajtu Rasprodaja.rs.

VAŠA PORUKA JE USPEŠNO PRIMLJENA!

Naš tim će vam odgovoriti u najkraćem mogućem roku (obično u roku od 24 časa radnim danima).

--- PODACI O VAŠOJ PORUCI ---
Datum slanja: " . date('d.m.Y. H:i:s') . "
Naslov: " . $subject . "
Vaš email: " . $email . (!empty($phone) ? "
Vaš telefon: " . $phone : "") . "

--- SADRŽAJ VAŠE PORUKE ---
" . $message . "
-----------------------------

ŠTA SLEDI?
Naš tim pregleda vašu poruku i odgovoriće vam na email: " . $email . "

Ukoliko imate dodatnih pitanja, slobodno odgovorite na ovaj email.

Posetite naš sajt: " . SITE_URL . "

© " . date('Y') . " Rasprodaja.rs - Sva prava zadržana.
";
            
            // Pošalji lep email korisniku
            sendEmail($email, "Potvrda: Vaša poruka je primljena - Rasprodaja.rs", $userHtmlContent, $userPlainText);
            
            $formSubmitted = true;
            $formSuccess = 'Hvala vam što ste nas kontaktirali! Vaša poruka je uspešno poslata. Odgovorićemo vam u najkraćem mogućem roku.';
            
            // Reset forme
            $name = $email = $phone = $subject = $message = '';
        } else {
            $formError = 'Došlo je do greške prilikom slanja poruke. Molimo pokušajte ponovo ili nas kontaktirajte direktno putem email-a.';
            error_log("Contact form: Failed to send email to $adminEmail");
        }
        
        // Log-uj kontakt (ako imate funkciju logUserActivity)
        if (function_exists('logUserActivity') && isset($_SESSION['user_id'])) {
            logUserActivity($_SESSION['user_id'], 'contact_form', [
                'subject' => $subject,
                'char_count' => strlen($message),
                'email_sent' => $emailSent ?? false
            ]);
        }
    } else {
        $formError = implode('<br>', $errors);
    }
}
?>

<!-- HERO SECTION -->
<section class="contact-hero py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1 class="display-5 fw-bold text-white mb-3">
                    Kontaktirajte nas
                </h1>
                <p class="lead text-white mb-0">
                    Imate pitanja? Tu smo da pomognemo! Kontaktirajte nas za sve informacije.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="contact-hero-icon">
                    <i class="fas fa-headset fa-6x text-white opacity-75"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- KONTAKT INFORMACIJE I FORMA -->
<section class="mb-5">
    <div class="container">
        <div class="row">
            <!-- LEVA KOLONA - INFORMACIJE -->
            <div class="col-lg-4 mb-5 mb-lg-0" style="z-index: 10;">
                <div class="sticky-top" style="top: 100px;">
                    <div class="contact-info-card mb-4">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start mb-3">
                                    <div class="contact-icon me-3">
                                        <i class="fas fa-envelope fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <h5 class="card-title mb-1">Email</h5>
                                        <p class="card-text text-muted mb-0">
                                            kontakt@rasprodaja.rs<br>
                                            info@rasprodaja.rs
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- RADNO VREME -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3">
                                <i class="far fa-clock text-primary me-2"></i> Radno vreme
                            </h5>
                            <table class="table table-borderless mb-0">
                                <tbody>
                                    <tr><td>Ponedeljak - Petak</td><td class="text-end">09:00 - 17:00</td></tr>
                                    <tr><td>Subota</td><td class="text-end">10:00 - 14:00</td></tr>
                                    <tr><td>Nedelja</td><td class="text-end">Zatvoreno</td></tr>
                                </tbody>
                             </table>
                            <div class="alert alert-light mt-3 mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                Za hitne slučajeve vikendom, kontaktirajte nas putem email-a.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- DESNA KOLONA - FORMA -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-lg-5">
                        <!-- USPESNA PORUKA -->
                        <?php if ($formSuccess): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $formSuccess; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <!-- GREŠKA -->
                        <?php if ($formError): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $formError; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>
                        
                        <h2 class="h3 mb-4">
                            <i class="fas fa-paper-plane text-primary me-2"></i> Pošaljite nam poruku
                        </h2>
                        
                        <form method="POST" action="" id="contactForm" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">
                                        <i class="fas fa-user me-1"></i> Ime i prezime <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                    <div class="invalid-feedback">Molimo unesite vaše ime i prezime.</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-1"></i> Email adresa <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                    <div class="invalid-feedback">Molimo unesite validnu email adresu.</div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">
                                        <i class="fas fa-phone me-1"></i> Telefon
                                    </label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone ?? ''); ?>" placeholder="+381 11 123 4567">
                                    <small class="text-muted">Opcionalno - za brži kontakt</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="subject" class="form-label">
                                        <i class="fas fa-tag me-1"></i> Naslov poruke <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select" id="subject" name="subject" required>
                                        <option value="" disabled <?php echo empty($subject) ? 'selected' : ''; ?>>Izaberite naslov</option>
                                        <option value="Pitanje o oglasima" <?php echo ($subject ?? '') == 'Pitanje o oglasima' ? 'selected' : ''; ?>>Pitanje o oglasima</option>
                                        <option value="Tehnička podrška" <?php echo ($subject ?? '') == 'Tehnička podrška' ? 'selected' : ''; ?>>Tehnička podrška</option>
                                        <option value="Premium paketi" <?php echo ($subject ?? '') == 'Premium paketi' ? 'selected' : ''; ?>>Premium paketi</option>
                                        <option value="Saradnja" <?php echo ($subject ?? '') == 'Saradnja' ? 'selected' : ''; ?>>Saradnja</option>
                                        <option value="Prijava problema" <?php echo ($subject ?? '') == 'Prijava problema' ? 'selected' : ''; ?>>Prijava problema</option>
                                        <option value="Ostalo" <?php echo ($subject ?? '') == 'Ostalo' ? 'selected' : ''; ?>>Ostalo</option>
                                    </select>
                                    <div class="invalid-feedback">Molimo izaberite naslov poruke.</div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">
                                    <i class="fas fa-comment-dots me-1"></i> Poruka <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="message" name="message" rows="6" required placeholder="Opišite nam šta vas interesuje..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                                <div class="invalid-feedback">Poruka mora imati najmanje 10 karaktera.</div>
                                <div class="mt-2"><small class="text-muted"><span id="charCount">0</span> / 2000 karaktera</small></div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="privacyPolicy" name="privacyPolicy" required>
                                    <label class="form-check-label" for="privacyPolicy">
                                        Prihvatam <a href="/privacy" class="text-decoration-none">Politiku privatnosti</a> i saglasan/saglasna sam da moji podaci budu korišćeni u svrhu kontakta.
                                    </label>
                                    <div class="invalid-feedback">Morate prihvatiti Politiku privatnosti.</div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-outline-secondary"><i class="fas fa-redo me-2"></i> Poništi</button>
                                <button type="submit" class="btn btn-primary px-4"><i class="fas fa-paper-plane me-2"></i> Pošaljite poruku</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- FAQ SEKCIJA -->
                <div class="card border-0 shadow-sm mt-4">
                    <div class="card-body p-4">
                        <h3 class="h4 mb-4"><i class="fas fa-question-circle text-primary me-2"></i> Često postavljana pitanja</h3>
                        <div class="accordion" id="contactFAQ">
                            <div class="accordion-item">
                                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">Koliko brzo dobijam odgovor?</button></h2>
                                <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#contactFAQ"><div class="accordion-body"><strong>Radnim danima odgovaramo u roku od 24 časa.</strong> Vikendom odgovori mogu da kasne do 48 sati. Za hitne slučajeve koristite telefonski kontakt.</div></div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">Kako da prijavim neprikladan oglas?</button></h2>
                                <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#contactFAQ"><div class="accordion-body">Na stranici svakog oglasa postoji dugme "Prijavi oglas". Takođe možete poslati email na <strong>info@rasprodaja.rs</strong> sa linkom oglasa i opisom problema.</div></div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">Da li nudite tehničku podršku za mobilnu aplikaciju?</button></h2>
                                <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#contactFAQ"><div class="accordion-body">Da, za sva pitanja vezana za mobilnu aplikaciju kontaktirajte nas putem ove forme ili na <strong>info@rasprodaja.rs</strong>. Molimo navedite verziju aplikacije i operativni sistem.</div></div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">Kako da postanem premium korisnik?</button></h2>
                                <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#contactFAQ"><div class="accordion-body">Premium pakete možete aktivirati u svom nalogu u sekciji "Moj profil" → "Premium paketi". Za poslovne pakete kontaktirajte nas na <strong>info@rasprodaja.rs</strong>.</div></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MAPA (OPCIONO) -->
<section class="mb-5">
    <div class="container">
        <h2 class="h3 mb-4 text-center"><i class="fas fa-map-marked-alt text-primary me-2"></i> Pronađite nas</h2>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="contact-map-placeholder" style="height: 400px; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                    <div class="d-flex flex-column justify-content-center align-items-center h-100">
                        <i class="fas fa-map fa-4x text-primary mb-3 opacity-50"></i>
                        <h4 class="text-muted mb-2">Naša lokacija</h4>
                        <p class="text-muted text-center px-4"><br><small>Uključite stvarnu Google Maps integraciju kada budete spremni</small></p>
                        <a href="https://maps.google.com/?q=Bulevar+kralja+Aleksandra+123+Beograd" target="_blank" class="btn btn-primary mt-3"><i class="fas fa-directions me-2"></i> Otvori u Google Maps</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- DRUŠTVENE MREŽE -->
<section class="mb-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-5 text-center">
                        <h3 class="h4 mb-4">Pratite nas na društvenim mrežama</h3>
                        <p class="text-muted mb-4">Budite u toku sa najnovijim vestima, akcijama i savetima.</p>
                        <div class="d-flex justify-content-center flex-wrap gap-3">
                            <a href="https://facebook.com/" target="_blank" class="btn btn-outline-primary btn-social"><i class="fab fa-facebook-f fa-lg me-2"></i> Facebook</a>
                            <a href="https://instagram.com/" target="_blank" class="btn btn-outline-danger btn-social"><i class="fab fa-instagram fa-lg me-2"></i> Instagram</a>
                            <a href="https://twitter.com/" target="_blank" class="btn btn-outline-info btn-social"><i class="fab fa-twitter fa-lg me-2"></i> Twitter</a>
                            <a href="https://linkedin.com/company/" target="_blank" class="btn btn-outline-primary btn-social"><i class="fab fa-linkedin-in fa-lg me-2"></i> LinkedIn</a>
                            <a href="https://youtube.com/" target="_blank" class="btn btn-outline-danger btn-social"><i class="fab fa-youtube fa-lg me-2"></i> YouTube</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .contact-hero { background: linear-gradient(135deg, var(--bs-primary) 0%, var(--bs-info) 100%); border-radius: 0 0 20px 20px; margin-top: -1rem; }
    .contact-hero-icon i { filter: drop-shadow(0 4px 6px rgba(0,0,0,0.2)); animation: headsetFloat 3s infinite ease-in-out; }
    @keyframes headsetFloat { 0%, 100% { transform: translateY(0) rotate(0deg); } 50% { transform: translateY(-10px) rotate(5deg); } }
    .contact-info-card { transition: transform 0.3s ease; }
    .contact-info-card:hover { transform: translateY(-5px); }
    .contact-icon { width: 50px; height: 50px; line-height: 50px; text-align: center; background: rgba(var(--bs-primary-rgb), 0.1); border-radius: 12px; flex-shrink: 0; }
    .contact-map-placeholder { border-radius: 8px; overflow: hidden; }
    .btn-social { padding: 10px 20px; border-radius: 25px; transition: all 0.3s ease; min-width: 140px; }
    .btn-social:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
    @media (max-width: 768px) {
        .contact-hero { padding: 3rem 0 !important; }
        .contact-hero .display-5 { font-size: 2rem; }
        .btn-social { min-width: 120px; padding: 8px 16px; font-size: 0.9rem; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const messageTextarea = document.getElementById('message');
        const charCount = document.getElementById('charCount');
        if (messageTextarea && charCount) {
            messageTextarea.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                if (length > 1900) charCount.classList.add('text-danger');
                else charCount.classList.remove('text-danger');
            });
            charCount.textContent = messageTextarea.value.length;
        }
        
        const contactForm = document.getElementById('contactForm');
        if (contactForm) {
            contactForm.addEventListener('submit', function(event) {
                if (!this.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                this.classList.add('was-validated');
            });
            
            const resetBtn = contactForm.querySelector('button[type="reset"]');
            if (resetBtn) {
                resetBtn.addEventListener('click', function() {
                    contactForm.classList.remove('was-validated');
                    if (charCount) charCount.textContent = '0';
                });
            }
        }
    });
</script>