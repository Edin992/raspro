document.addEventListener('DOMContentLoaded', function() {
    // ========== FUNKCIONALNOST PRETRAGE ==========
    const searchInput = document.getElementById('faqSearch');
    const clearSearchBtn = document.getElementById('clearSearch');
    const searchResults = document.getElementById('searchResults');
    const searchResultsContent = document.getElementById('searchResultsContent');
    const clearResultsBtn = document.getElementById('clearResults');
    const allFaqItems = document.querySelectorAll('.faq-item');
    const categoryNavLinks = document.querySelectorAll('.faq-category-nav .nav-link');
    const suggestionLinks = document.querySelectorAll('.search-suggestion');
    
    // Funkcija za pretragu
    function performSearch(searchTerm) {
        if (!searchTerm.trim()) {
            hideSearchResults();
            return;
        }
        
        const term = searchTerm.toLowerCase().trim();
        let resultsHtml = '';
        let resultCount = 0;
        
        // Sakrij sve kategorije
        document.querySelectorAll('.faq-category-section').forEach(section => {
            section.style.display = 'none';
        });
        
        // Proveri svako pitanje
        allFaqItems.forEach(item => {
            const searchText = item.getAttribute('data-search-text');
            const question = item.querySelector('.accordion-button').textContent;
            const answer = item.querySelector('.accordion-body').textContent;
            
            if (searchText.includes(term)) {
                resultCount++;
                
                // Pronađi kategoriju
                const categoryId = item.closest('.faq-category-section').id.replace('category-', '');
                const category = Array.from(categoryNavLinks).find(link => 
                    link.getAttribute('href') === `#category-${categoryId}`
                );
                const categoryTitle = category ? category.querySelector('.fw-medium').textContent : '';
                
                resultsHtml += `
                    <div class="search-result-item mb-3 p-3 border rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">
                                    <a href="#${item.querySelector('.accordion-button').getAttribute('data-bs-target').replace('#', '')}" 
                                       class="text-decoration-none search-result-link">
                                        ${question}
                                    </a>
                                </h6>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-folder me-1"></i> ${categoryTitle}
                                </p>
                                <p class="mb-0 small">${answer.substring(0, 150)}...</p>
                            </div>
                            <button class="btn btn-sm btn-outline-primary view-answer-btn" 
                                    data-target="${item.querySelector('.accordion-button').getAttribute('data-bs-target')}">
                                Pregledaj odgovor
                            </button>
                        </div>
                    </div>
                `;
            }
        });
        
        // Prikaži rezultate
        if (resultCount > 0) {
            searchResultsContent.innerHTML = `
                <p class="text-muted">Pronađeno <strong>${resultCount}</strong> rezultata za "<strong>${searchTerm}</strong>"</p>
                ${resultsHtml}
            `;
            searchResults.style.display = 'block';
            clearSearchBtn.style.display = 'block';
        } else {
            searchResultsContent.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-search fa-3x text-muted mb-3 opacity-50"></i>
                    <h5>Nema rezultata za "${searchTerm}"</h5>
                    <p class="text-muted">Pokušajte drugačije reči ili pogledajte kategorije.</p>
                </div>
            `;
            searchResults.style.display = 'block';
            clearSearchBtn.style.display = 'block';
        }
    }
    
    // Funkcija za sakrivanje rezultata pretrage
    function hideSearchResults() {
        searchResults.style.display = 'none';
        searchInput.value = '';
        clearSearchBtn.style.display = 'none';
        
        // Vrati prikaz svih kategorija
        document.querySelectorAll('.faq-category-section').forEach(section => {
            section.style.display = 'block';
        });
        
        // Skupi sve accordion-e
        document.querySelectorAll('.accordion-collapse.show').forEach(collapse => {
            const bsCollapse = bootstrap.Collapse.getInstance(collapse) || new bootstrap.Collapse(collapse);
            bsCollapse.hide();
        });
    }
    
    // Event listener-i za pretragu
    searchInput.addEventListener('input', function() {
        clearSearchBtn.style.display = this.value ? 'block' : 'none';
        performSearch(this.value);
    });
    
    clearSearchBtn.addEventListener('click', hideSearchResults);
    clearResultsBtn.addEventListener('click', hideSearchResults);
    
    // Event listener-i za preporuke pretrage
    suggestionLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = this.textContent;
            searchInput.focus();
            performSearch(this.textContent);
        });
    });
    
    // ========== "KORISNO" DUGME ==========
    document.querySelectorAll('.faq-helpful-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const countSpan = this.querySelector('.faq-helpful-count');
            let count = parseInt(countSpan.textContent) || 0;
            count++;
            countSpan.textContent = count;
            
            // Vizuelni feedback
            this.classList.remove('btn-outline-primary');
            this.classList.add('btn-success');
            this.innerHTML = `<i class="fas fa-check me-1"></i> Hvala! <span class="faq-helpful-count">${count}</span>`;
            
            // Simuliraj čuvanje u localStorage (u praksi bi bilo na serveru)
            const question = this.getAttribute('data-question');
            localStorage.setItem(`faq_helpful_${encodeURIComponent(question)}`, count);
            
            // Vrati na originalno stanje nakon 3 sekunde
            setTimeout(() => {
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-primary');
                this.innerHTML = `<i class="fas fa-thumbs-up me-1"></i> Korisno <span class="faq-helpful-count">${count}</span>`;
            }, 3000);
        });
    });
    
    // ========== LINKOVI ZA REZULTATE PRETRAGE ==========
    document.addEventListener('click', function(e) {
        // Linkovi u rezultatima pretrage
        if (e.target.classList.contains('search-result-link')) {
            e.preventDefault();
            const targetId = e.target.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                // Otvori accordion
                const bsCollapse = new bootstrap.Collapse(targetElement, { toggle: true });
                
                // Scroll do elementa
                setTimeout(() => {
                    targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 300);
                
                // Istakni element
                targetElement.classList.add('highlight-answer');
                setTimeout(() => {
                    targetElement.classList.remove('highlight-answer');
                }, 3000);
            }
        }
        
        // Dugmad za pregled odgovora
        if (e.target.classList.contains('view-answer-btn')) {
            const target = e.target.getAttribute('data-target');
            const targetElement = document.querySelector(target);
            
            if (targetElement) {
                const bsCollapse = new bootstrap.Collapse(targetElement, { toggle: true });
                
                setTimeout(() => {
                    document.querySelector(target).scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 300);
            }
        }
    });
    
    // ========== PROŠIRI/SKUPI SVE U KATEGORIJI ==========
    document.querySelectorAll('.expand-all-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-category');
            const collapses = document.querySelectorAll(`#accordion-${categoryId} .accordion-collapse`);
            
            collapses.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse) || new bootstrap.Collapse(collapse);
                bsCollapse.show();
            });
        });
    });
    
    document.querySelectorAll('.collapse-all-category').forEach(btn => {
        btn.addEventListener('click', function() {
            const categoryId = this.getAttribute('data-category');
            const collapses = document.querySelectorAll(`#accordion-${categoryId} .accordion-collapse`);
            
            collapses.forEach(collapse => {
                const bsCollapse = bootstrap.Collapse.getInstance(collapse);
                if (bsCollapse) bsCollapse.hide();
            });
        });
    });
    
    // ========== HIGHLIGHT KATEGORIJA PRI SCROLL-U ==========
    function highlightCategoryOnScroll() {
        const categorySections = document.querySelectorAll('.faq-category-section');
        const navLinks = document.querySelectorAll('.faq-category-nav .nav-link');
        
        let currentCategory = '';
        
        categorySections.forEach(section => {
            const rect = section.getBoundingClientRect();
            if (rect.top <= 150 && rect.bottom >= 150) {
                currentCategory = section.id;
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === `#${currentCategory}`) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', highlightCategoryOnScroll);
    
    // ========== DODATNI CSS ZA HIGHLIGHT ==========
    const style = document.createElement('style');
    style.textContent = `
        .highlight-answer {
            animation: highlightPulse 2s ease;
            background-color: rgba(var(--bs-warning-rgb), 0.1) !important;
            border-left: 4px solid var(--bs-warning) !important;
        }
        
        @keyframes highlightPulse {
            0%, 100% { background-color: rgba(var(--bs-warning-rgb), 0.1); }
            50% { background-color: rgba(var(--bs-warning-rgb), 0.2); }
        }
        
        .search-result-link:hover {
            color: var(--bs-primary) !important;
            text-decoration: underline !important;
        }
        
        .search-result-item {
            transition: all 0.3s ease;
        }
        
        .search-result-item:hover {
            background-color: rgba(var(--bs-primary-rgb), 0.03);
            transform: translateX(5px);
            border-color: var(--bs-primary) !important;
        }
    `;
    document.head.appendChild(style);
    
    // Učitaj prethodne "Korisno" glasove iz localStorage
    document.querySelectorAll('.faq-helpful-btn').forEach(btn => {
        const question = btn.getAttribute('data-question');
        const savedCount = localStorage.getItem(`faq_helpful_${encodeURIComponent(question)}`);
        
        if (savedCount) {
            const countSpan = btn.querySelector('.faq-helpful-count');
            countSpan.textContent = savedCount;
        }
    });
    
    // Inicijalno pozovi za highlight kategorija
    highlightCategoryOnScroll();
});
