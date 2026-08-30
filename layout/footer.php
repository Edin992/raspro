<?php
/**
 * footer.php - Podnožje sajta
 * ISPRAVLJENA VERZIJA - optimizovan back-to-top dugme
 */
?>

<!-- FOOTER -->
<footer class="mt-auto site-footer">
    <div class="container py-5">
        <div class="row">
            <!-- LOGO I OPIS -->
            <div class="col-lg-4 mb-4 mb-lg-0">
                <a href="https://www.rasprodaja.rs/"><h3 class="text-white mb-3">
                    <span class="fw-bold">Rasprodaja</span>
                    <span class="text-primary">.rs</span>
                </h3></a>
                <p class="text-light">
                    Najveći online oglasnik u Srbiji. Kupujte i prodajte brzo, lako i bezbedno. 
                    Povezujemo kupce i prodavce već <?php echo date('Y') - 2024; ?> godina.
                </p>
                
                <!-- SOCIAL ICONS -->
                <div class="mt-4">
                    <a href="#" class="text-light me-3 social-icon" title="Facebook">
                        <i class="fab fa-facebook fa-lg"></i>
                    </a>
                    <a href="https://www.instagram.com/rasprodaja.rs_" class="text-light me-3 social-icon" title="Instagram">
                        <i class="fab fa-instagram fa-lg"></i>
                    </a>
                    <a href="#" class="text-light me-3 social-icon" title="Twitter">
                        <i class="fab fa-twitter fa-lg"></i>
                    </a>
                    <a href="#" class="text-light social-icon" title="YouTube">
                        <i class="fab fa-youtube fa-lg"></i>
                    </a>
                </div>
            </div>
            
            <!-- BRZI LINKOVI -->
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="text-white mb-3">Oglasnik</h5>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2">
                        <a href="/ads" class="text-light text-decoration-none">
                            Pretraži oglase
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/ads/premium/" class="text-light text-decoration-none">
                            <i class="fas fa-crown text-warning me-1"></i> Premium oglasi
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/packages" class="text-light text-decoration-none">
                            Paketi
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/create-ad" class="text-light text-decoration-none">
                            Postavi oglas
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/how-it-works" class="text-light text-decoration-none">
                            Kako radi?
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/contact" class="text-light text-decoration-none">
                            Kontakt
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- PRAVNE INFORMACIJE -->
            <div class="col-lg-2 col-md-4 mb-4 mb-md-0">
                <h5 class="text-white mb-3">Informacije</h5>
                <ul class="list-unstyled footer-links">
                    <li class="mb-2">
                        <a href="/terms" class="text-light text-decoration-none">
                            Uslovi korišćenja
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/privacy" class="text-light text-decoration-none">
                            Politika privatnosti
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/cookies" class="text-light text-decoration-none">
                            Politika kolačića
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/faq" class="text-light text-decoration-none">
                            Često postavljana pitanja
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="/safety" class="text-light text-decoration-none">
                            Saveti za bezbednost
                        </a>
                    </li>
                </ul>
            </div>
            
            <!-- KONTAKT I PAKETI -->
            <div class="col-lg-4 col-md-4">
                <h5 class="text-white mb-3">Kontakt</h5>
                <ul class="list-unstyled footer-links">
                    <!-- <li class="mb-2">
                        <i class="fas fa-phone-alt me-2 text-primary"></i>
                        <a href="tel:+381601234567" class="text-light text-decoration-none">
                            060 123 4567
                        </a>
                    </li> -->
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2 text-primary"></i>
                        <a href="mailto:info@rasprodaja.rs" class="text-light text-decoration-none">
                            info@rasprodaja.rs
                        </a>
                    </li>
                    <!-- <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                        <span class="text-light">Bulevar Kralja Aleksandra 123, Beograd</span>
                    </li> -->
                    <li class="mb-2">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        <span class="text-light">Pon-Ned: 08:00 - 20:00</span>
                    </li>
                </ul>
                
                <!-- NEWSLETTER -->
                <div class="mt-4">
                    <h6 class="text-white mb-2">Newsletter</h6>
                    <p class="text-light small">Prijavite se za najnovije oglase i ponude.</p>
                    <form class="input-group" id="newsletter-form">
                        <input type="email" class="form-control" name="newsletter" placeholder="Vaš email" required>
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <hr class="bg-light my-4">
        
        <!-- BOTTOM FOOTER -->
        <div class="row">
            <div class="col-md-6 text-center text-md-start">
                <p class="text-light mb-0">
                    &copy; <?php echo date('Y'); ?> <a href="https://www.rasprodaja.rs/">Rasprodaja.rs</a>. Sva prava zadržana.
                </p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p class="text-light mb-0">
                    <a href="/sitemap.xml" class="text-light text-decoration-none me-3">Mapa sajta</a>
                    <a href="/contact" class="text-light text-decoration-none">Kontaktirajte nas</a>
                </p>
            </div>
        </div>
    </div>
</footer>

<!-- BACK TO TOP BUTTON - OPTIMIZOVANO -->
<button class="btn btn-primary btn-floating back-to-top" id="back-to-top" 
        aria-label="Idi na vrh stranice" title="Idi na vrh">
    <i class="fas fa-arrow-up"></i>
</button>

