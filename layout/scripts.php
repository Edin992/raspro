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
    
    <!-- GLOBALNI COOKIE CONSENT (banner + modal, izbor se cuva u SQL) -->
<?php include __DIR__ . '/cookie-consent.php'; ?>
    
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
            
            // Cookie consent - SAD GA RADI cookies.js (globalni, ceo sajt)
            
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
        
        // Theme switcher - FIX: jedinstveni handler za obe tipke (desktop dugme
        // + side meni dugme) koji sinhronizuje html[data-bs-theme] (Bootstrap)
        // I body.dark-mode (stari CSS sajta). Ranije su navbar.php i ovaj fajl
        // imali DVA odvojena handlera na istom dugmetu i razilazila se u stanju.
        function initThemeSwitcher() {
            const themeToggle = document.getElementById('theme-toggle');
            const sideToggle = document.getElementById('mobile-theme-toggle-side');
            if (!themeToggle && !sideToggle) return;
            
            const savedTheme = localStorage.getItem('theme') || 'light';
            applyTheme(savedTheme);
            
            (themeToggle ? [themeToggle, sideToggle] : [sideToggle]).forEach(btn => {
                if (btn) btn.addEventListener('click', function() {
                    const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
                    const next = current === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('theme', next);
                    applyTheme(next);
                });
            });
            
            function applyTheme(theme) {
                document.documentElement.setAttribute('data-bs-theme', theme);
                document.body.classList.toggle('dark-mode', theme === 'dark');
                updateThemeIcon(theme);
            }
            
            function updateThemeIcon(theme) {
                if (themeToggle) {
                    const icon = themeToggle.querySelector('i');
                    if (icon) {
                        icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
                        themeToggle.setAttribute('title', theme === 'dark' ? 'Prebaci na svetli režim' : 'Prebaci na tamni režim');
                    }
                }
                if (sideToggle) {
                    const lbl = sideToggle.querySelector('.mobile-theme-label');
                    const ic = sideToggle.querySelector('i');
                    if (lbl) lbl.textContent = theme === 'dark' ? 'Svetla tema' : 'Tamna tema';
                    if (ic) ic.className = theme === 'dark' ? 'fas fa-sun me-3 text-secondary' : 'fas fa-moon me-3 text-secondary';
                }
            }
        }
        
/* Cookie consent (cookieconsent CDN) je ZAMENJEN globalnim sistemom u
         layout/cookie-consent.php + assets/js/cookies.js - izbor se cuva i u SQL. */
    </script>
    
</body>
</html>