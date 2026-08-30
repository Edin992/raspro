/**
 * assets/js/listing.js - JavaScript za listu oglasa
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('listing.js učitan');
    initListingPage();
});

function initListingPage() {
    // Dugmad za popularne gradove
    document.querySelectorAll('.city-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const city = this.getAttribute('data-city');
            document.getElementById('city').value = city;
            document.getElementById('search-form').submit();
        });
    });
    
    // Dinamičko učitavanje podkategorija pri promeni kategorije
    const categorySelect = document.getElementById('category');
    const subcategoryContainer = document.getElementById('subcategory-container');
    const subcategorySelect = document.getElementById('subcategory');
    
    if (categorySelect && subcategoryContainer && subcategorySelect) {
        categorySelect.addEventListener('change', async function() {
            const categoryId = this.value;
            
            if (!categoryId) {
                subcategoryContainer.style.display = 'none';
                subcategorySelect.innerHTML = '<option value="">Sve podkategorije</option>';
                return;
            }
            
            subcategorySelect.innerHTML = '<option value="">Učitavanje...</option>';
            subcategoryContainer.style.display = 'block';
            
            try {
                const response = await fetch(`/api/categories/children.php?parent_id=${categoryId}`);
                const data = await response.json();
                
                if (data.success && data.categories && data.categories.length > 0) {
                    let options = '<option value="">Sve podkategorije</option>';
                    data.categories.forEach(cat => {
                        options += `<option value="${cat.id}">${cat.name}`;
                        if (cat.ad_count > 0) {
                            options += ` (${cat.ad_count})`;
                        }
                        options += `</option>`;
                    });
                    subcategorySelect.innerHTML = options;
                    subcategoryContainer.style.display = 'block';
                } else {
                    subcategorySelect.innerHTML = '<option value="">Nema podkategorija</option>';
                    subcategoryContainer.style.display = 'none';
                }
            } catch (error) {
                console.error('Error loading subcategories:', error);
                subcategorySelect.innerHTML = '<option value="">Greška pri učitavanju</option>';
            }
        });
    }
    
    // Reset filtera
    const resetFiltersBtn = document.getElementById('reset-filters');
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', function() {
            window.location.href = '?page=ads';
        });
    }
    
    // Reset pretrage
    const resetSearchBtn = document.getElementById('reset-search');
    if (resetSearchBtn) {
        resetSearchBtn.addEventListener('click', function() {
            window.location.href = '?page=ads';
        });
    }
    
    // Sortiranje - direktno submit forme
    const sortSelect = document.getElementById('sort');
    if (sortSelect) {
        sortSelect.addEventListener('change', function() {
            document.getElementById('search-form').submit();
        });
    }
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = `
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    
    const icon = type === 'error' ? 'exclamation-triangle' : 
                type === 'warning' ? 'exclamation-circle' : 
                type === 'success' ? 'check-circle' : 'info-circle';
    
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${icon} me-2"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 3000);
}