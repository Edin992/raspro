/**
 * assets/js/packages.js - JavaScript za pakete
 * SAMO BANKOVNI RAČUN - SA REFERENCE NUMBER
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('packages.js loaded');
    
    initPackageSelection();
    initPaymentForm();
});

function initPackageSelection() {
    document.querySelectorAll('.select-package').forEach(btn => {
        btn.addEventListener('click', function() {
            const packageId = this.dataset.packageId;
            const packageName = this.dataset.packageName;
            const packagePrice = this.dataset.packagePrice;
            const packageYearly = this.dataset.packageYearly;
            
            // Postavi podatke u modal
            document.getElementById('modalPackageId').value = packageId;
            document.getElementById('modalPackageName').textContent = packageName;
            document.getElementById('modalPackageNameDisplay').textContent = packageName;
            
            // Mesečna cena
            const monthlyPrice = parseInt(packagePrice);
            const yearlyPrice = parseInt(packageYearly);
            
            // Postavi cenu
            updatePriceDisplay(monthlyPrice, yearlyPrice);
            
            // Generiši poziv na broj (timestamp + user_id)
            const userId = window.SITE_CONFIG?.userId || 'XXXX';
            const refNumber = Date.now() + '-' + userId;
            document.getElementById('referenceNumber').textContent = refNumber;
            document.getElementById('modalReferenceNumber').value = refNumber;
            
            // Prikaži modal
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        });
    });
}

function initPaymentForm() {
    // Promena perioda plaćanja
    document.getElementById('paymentPeriod').addEventListener('change', function() {
        const packageId = document.getElementById('modalPackageId').value;
        const btn = document.querySelector(`.select-package[data-package-id="${packageId}"]`);
        
        if (btn) {
            const monthlyPrice = parseInt(btn.dataset.packagePrice);
            const yearlyPrice = parseInt(btn.dataset.packageYearly);
            updatePriceDisplay(monthlyPrice, yearlyPrice, this.value);
        }
        
        // Ažuriraj period u hidden polju
        document.getElementById('modalPeriod').value = this.value;
    });
    
    // Potvrda plaćanja
    document.getElementById('confirmPaymentBtn').addEventListener('click', function() {
        const packageName = document.getElementById('modalPackageNameDisplay').textContent;
        const price = document.getElementById('modalPackagePrice').textContent;
        const period = document.getElementById('paymentPeriod').value;
        const refNumber = document.getElementById('referenceNumber').textContent;
        
        // Potvrda
        const confirmMsg = `Da li ste sigurni da želite da izaberete ${packageName} paket?\n\n` +
                          `Period: ${period === 'monthly' ? 'Mesečno' : 'Godišnje'}\n` +
                          `Cena: ${price}\n` +
                          `Poziv na broj: ${refNumber}\n\n` +
                          `Nakon uplate na račun, paket će biti aktiviran u roku od 24h.`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Prikaži loading
        const btn = this;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Obrada...';
        btn.disabled = true;
        
        // Pripremi podatke za slanje - koristi FormData
        const form = document.getElementById('paymentForm');
        const formData = new FormData(form);
        
        // Dodaj period ako nije u formi
        if (!formData.has('period')) {
            formData.append('period', document.getElementById('paymentPeriod').value);
        }
        
        // Pošalji zahtev
        fetch('/api/package/upgrade.php', {
            method: 'POST',
            body: formData,
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('success', data.message || 'Zahtev za paket je poslat!');
                
                // Zatvori modal
                const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                if (modal) modal.hide();
                
                // Osveži stranicu nakon 2 sekunde
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showNotification('danger', data.message || 'Greška pri aktivaciji paketa');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('danger', 'Greška pri komunikaciji sa serverom');
            btn.innerHTML = originalText;
            btn.disabled = false;
        });
    });
}

function updatePriceDisplay(monthlyPrice, yearlyPrice, period = 'monthly') {
    const priceDisplay = document.getElementById('modalPackagePrice');
    const periodSelect = document.getElementById('paymentPeriod');
    const actualPeriod = period || periodSelect.value;
    
    if (actualPeriod === 'yearly' && yearlyPrice > 0) {
        const yearlyFormatted = yearlyPrice.toLocaleString('sr-RS');
        const monthlySavings = Math.round((monthlyPrice * 12 - yearlyPrice) / 12);
        priceDisplay.innerHTML = `
            ${yearlyFormatted} RSD 
            <small class="text-muted">/ godišnje</small>
            <div class="small text-success">
                <i class="fas fa-calculator me-1"></i>
                = ${Math.round(yearlyPrice / 12).toLocaleString('sr-RS')} RSD/mesečno
                <span class="badge bg-success ms-2">ušteda ${monthlySavings} RSD/mesečno</span>
            </div>
        `;
    } else {
        const monthlyFormatted = monthlyPrice.toLocaleString('sr-RS');
        priceDisplay.innerHTML = `
            ${monthlyFormatted} RSD 
            <small class="text-muted">/ mesečno</small>
        `;
    }
}

function showNotification(type, message) {
    // Ukloni postojeće notifikacije
    document.querySelectorAll('.custom-notification').forEach(el => el.remove());
    
    const colors = {
        success: '#28a745',
        danger: '#dc3545',
        warning: '#ffc107',
        info: '#17a2b8'
    };
    
    const div = document.createElement('div');
    div.className = 'custom-notification';
    div.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${colors[type] || '#007bff'};
        color: #fff;
        padding: 15px 25px;
        border-radius: 8px;
        z-index: 99999;
        font-size: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        max-width: 400px;
        min-width: 300px;
        text-align: center;
        font-weight: 500;
        animation: slideDown 0.3s ease;
    `;
    div.textContent = message;
    document.body.appendChild(div);
    
    setTimeout(() => {
        div.style.opacity = '0';
        div.style.transition = 'opacity 0.3s';
        setTimeout(() => div.remove(), 300);
    }, 5000);
}

// Dodaj CSS za notifikacije
const style = document.createElement('style');
style.textContent = `
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);