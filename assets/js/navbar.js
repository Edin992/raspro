// assets/js/navbar.js
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar.bg-white');
    
    // Efekat skrolovanja (samo za desktop)
    if (navbar) {
        window.addEventListener('scroll', function() {
            if (window.scrollY > 20) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
        
        // Aktivni link efekat (opciono) - ISPRAVLJENA VERZIJA
        const currentPath = window.location.pathname + window.location.search;
        const currentUrl = window.location.href;
        
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            
            // Preskoči ako je href null ili prazan
            if (!linkHref) return;
            
            // Proveri da li je link aktivan
            if (linkHref === currentUrl || 
                currentPath.includes(linkHref) ||
                (linkHref !== '#' && currentPath.indexOf(linkHref) !== -1)) {
                link.classList.add('active');
            }
        });
    }
    
    // Onemogući hover za dropdown (samo klik)
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('mouseenter', function(e) {
            // Sprečava hover otvaranje
            e.stopPropagation();
        });
    });
    
    // Mobile search focus
    const mobileSearchBtn = document.querySelector('.dropdown-toggle[data-bs-toggle="dropdown"]');
    if (mobileSearchBtn) {
        mobileSearchBtn.addEventListener('shown.bs.dropdown', function() {
            const searchInput = document.querySelector('.dropdown-menu input[name="q"]');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 100);
            }
        });
    }
});