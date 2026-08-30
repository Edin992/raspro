<?php
/**
 * scripts.php - Svi JavaScript fajlovi i zatvaranje body/html
 * ISPRAVLJENA VERZIJA - bez chat widget-a
 */
?>

    <!-- JAVASCRIPT FILES -->
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Fallback lokalni Bootstrap -->
    <script src="<?php echo SITE_URL; ?>/assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery (opcionalno, ako ga koristite) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/jquery.min.js"></script> <!-- Fallback -->
    
    <!-- COOKIE CONSENT -->
    <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js"></script>
    
    <!-- CUSTOM JS FILES -->
    <script src="<?php echo SITE_URL; ?>/assets/js/main.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/theme-switcher.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/notifications.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/navbar.js"></script>
    <!-- PAGE SPECIFIC JS -->
    <?php if (isset($pageSpecificJS) && is_array($pageSpecificJS)): ?>
        <?php foreach ($pageSpecificJS as $jsFile): ?>
            <script src="<?php echo SITE_URL; ?>/assets/js/<?php echo $jsFile; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- INLINE JAVASCRIPT (ako postoji) -->
    <?php if (isset($inlineScripts) && !empty($inlineScripts)): ?>
        <script><?php echo $inlineScripts; ?></script>
    <?php endif; ?>
    
    <!-- GLOBAL INITIALIZATION -->
    <script>
        // Globalne varijable dostupne svim JS fajlovima
        
        
        // Inicijalizacija kada se stranica učita
        document.addEventListener('DOMContentLoaded', function() {
            // Inicijalizuj Bootstrap komponente
            initBootstrapComponents();
            
            // Back to top dugme
            initBackToTop();
            
            // Theme switcher
            initThemeSwitcher();
            
            // Cookie consent
            initCookieConsent();
            
            // Inicijalizuj notifikacije ako je korisnik ulogovan
            
            
            // Stranica specifična inicijalizacija
            if (typeof window.pageInit === 'function') {
                window.pageInit();
            }
        });
        
        // Globalne helper funkcije
        function showGlobalLoading() {
            const loadingEl = document.getElementById('global-loading');
            if (loadingEl) loadingEl.classList.remove('d-none');
        }
        
        function hideGlobalLoading() {
            const loadingEl = document.getElementById('global-loading');
            if (loadingEl) loadingEl.classList.add('d-none');
        }
        
        // Bootstrap komponente
        function initBootstrapComponents() {
            // Tooltip-ovi
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Popover-i
            var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
            popoverTriggerList.map(function (popoverTriggerEl) {
                return new bootstrap.Popover(popoverTriggerEl);
            });
            
            // Toast-ovi
            var toastElList = [].slice.call(document.querySelectorAll('.toast'));
            toastElList.map(function (toastEl) {
                return new bootstrap.Toast(toastEl);
            });
        }
        
        // Back to top - POBOLJŠANA VERZIJA
        function initBackToTop() {
            const backToTopButton = document.getElementById('back-to-top');
            if (!backToTopButton) return;
            
            // Pokaži/sakrij dugme na skrolovanje
            function toggleBackToTop() {
                if (window.pageYOffset > 300) {
                    backToTopButton.classList.add('show');
                } else {
                    backToTopButton.classList.remove('show');
                }
            }
            
            // Proveri da li treba prikazati dugme na početku
            toggleBackToTop();
            
            // Dodaj event listener za skrolovanje
            window.addEventListener('scroll', toggleBackToTop);
            
            // Smooth scroll na vrh
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Keyboard accessibility
            backToTopButton.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    window.scrollTo({
                        top: 0,
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        // Theme switcher
        function initThemeSwitcher() {
            const themeToggle = document.getElementById('theme-toggle');
            if (!themeToggle) return;
            
            // Proveri sačuvanu temu
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', savedTheme);
            
            // Ažuriraj ikonu
            updateThemeIcon(savedTheme);
            
            themeToggle.addEventListener('click', function() {
                const currentTheme = document.documentElement.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                document.documentElement.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                
                updateThemeIcon(newTheme);
            });
            
            function updateThemeIcon(theme) {
                const icon = themeToggle.querySelector('i');
                if (!icon) return;
                
                if (theme === 'dark') {
                    icon.className = 'fas fa-sun';
                    themeToggle.setAttribute('title', 'Prebaci na svetli režim');
                } else {
                    icon.className = 'fas fa-moon';
                    themeToggle.setAttribute('title', 'Prebaci na tamni režim');
                }
            }
        }
        
        // Cookie consent
        function initCookieConsent() {
            if (typeof window.cookieconsent === 'undefined') return;
            
            window.cookieconsent.initialise({
                palette: {
                    popup: {
                        background: "#252e39"
                    },
                    button: {
                        background: "#14a7d0"
                    }
                },
                theme: "classic",
                content: {
                    message: "Ovaj sajt koristi kolačiće za poboljšanje korisničkog iskustva.",
                    dismiss: "Razumem",
                    link: "Saznaj više",
                    href: "<?php echo SITE_URL; ?>/?page=cookies"
                }
            });
        }
    </script>
    
</body>
</html>