/**
 * assets/js/login.js - JavaScript za login stranicu

 */

class LoginHandler {
    constructor() {
        this.loginForm = document.getElementById('login-form');
        this.errorDiv = document.getElementById('login-error');
        this.toggleBtn = document.getElementById('toggle-password-login');
        this.submitBtn = null;
        this.originalButtonText = '';
        
        this.init();
    }
    
    init() {
        if (!this.loginForm) return;
        
        // Inicijalizuj toggle password
        this.initPasswordToggle();
        
        // Inicijalizuj form submit
        this.initFormSubmit();
    }
    
    initPasswordToggle() {
        if (!this.toggleBtn) return;
        
        this.toggleBtn.addEventListener('click', () => {
            const passwordInput = document.getElementById('password');
            const icon = this.toggleBtn.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'fas fa-eye';
            }
        });
    }
    
    initFormSubmit() {
        this.loginForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleLogin();
        });
    }
    
    async handleLogin() {
        // Prikaži loading
        this.submitBtn = this.loginForm.querySelector('button[type="submit"]');
        this.originalButtonText = this.submitBtn.innerHTML;
        this.submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Prijavljujem...';
        this.submitBtn.disabled = true;
        
        // Sakupi podatke
        const formData = new FormData(this.loginForm);
        const data = Object.fromEntries(formData.entries());
        
        // Prosta validacija
        if (!data.username || !data.password) {
            this.showError('Unesite korisničko ime i lozinku');
            this.resetButton();
            return;
        }
        
        try {
            const response = await fetch('/api/user/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
                credentials: 'include'
            });
            
            const result = await response.json();
            
            if (result.success) {
                await this.handleLoginSuccess(result);
            } else {
                await this.handleLoginError(response, result);
            }
        } catch (error) {
            console.error('Network error:', error);
            this.showError('Greška pri povezivanju sa serverom');
            this.resetButton();
        }
    }
    
    async handleLoginSuccess(result) {
        // Uspešna prijava
        this.showSuccess('Uspešno ste prijavljeni!');
        console.log('Login successful:', result);
        
        // Ažuriraj topbar (ako postoji globalna funkcija)
        if (typeof window.updateUserInfo === 'function') {
            window.updateUserInfo(result.user);
        }
        
        // Preusmeri nakon 1 sekunde
        setTimeout(() => {
            const redirectUrl = result.redirect || '?page=profile';
            console.log('Redirecting to:', redirectUrl);
            window.location.href = redirectUrl;
        }, 1000);
    }
    
    async handleLoginError(response, result) {
        // Proveri da li je greška zbog neverifikovanog email-a
        if (response.status === 403 && result.needs_verification) {
            this.showVerificationWarning(result);
        } else {
            // Obična greška
            this.showError(result.message || 'Greška pri prijavi');
        }
        
        console.error('Login failed:', result);
        this.resetButton();
    }
    
    showVerificationWarning(result) {
        // Sakrij postojeće greške
        if (this.errorDiv) {
            this.errorDiv.innerHTML = '';
        }
        
        // Prikaži poseban alert za verifikaciju
        const verificationAlert = `
            <div class="alert alert-warning alert-dismissible fade show">
                <h5><i class="fas fa-envelope me-2"></i> Verifikacija Email-a Potrebna</h5>
                <p>Vaš email <strong>${result.email}</strong> nije verifikovan.</p>
                <p>Molimo proverite Vaš inbox za verifikacioni email.</p>
                <div class="mt-3">
                    <a href="${result.redirect || '?page=resend-verification'}" 
                       class="btn btn-warning btn-sm">
                        <i class="fas fa-paper-plane me-1"></i> Pošalji ponovo verifikacioni email
                    </a>
                    ${result.verification_link ? `
                    <a href="${result.verification_link}" 
                       class="btn btn-outline-warning btn-sm ms-2">
                        <i class="fas fa-link me-1"></i> Verifikuj direktno
                    </a>
                    ` : ''}
                </div>
            </div>
        `;
        
        // Ubaci alert ispred forme
        const container = this.loginForm.closest('.card-body');
        if (container) {
            const alertDiv = document.createElement('div');
            alertDiv.innerHTML = verificationAlert;
            container.prepend(alertDiv.firstElementChild);
        }
    }
    
    showError(message) {
        if (this.errorDiv) {
            this.errorDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${this.escapeHtml(message)}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        } else {
            // Fallback
            alert(message);
        }
    }
    
    showSuccess(message) {
        const container = this.loginForm.closest('.card-body');
        if (!container) return;
        
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show';
        successDiv.innerHTML = `
            <i class="fas fa-check-circle me-2"></i>
            ${this.escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.prepend(successDiv);
        
        // Automatski ukloni nakon 5 sekundi
        setTimeout(() => {
            if (successDiv.parentNode) {
                successDiv.remove();
            }
        }, 5000);
    }
    
    resetButton() {
        if (this.submitBtn) {
            this.submitBtn.innerHTML = this.originalButtonText;
            this.submitBtn.disabled = false;
        }
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Inicijalizacija kada se DOM učita
document.addEventListener('DOMContentLoaded', function() {
    // Kreiraj instancu LoginHandler-a
    window.loginHandler = new LoginHandler();
    
    // Globalna funkcija za ažuriranje korisničkih informacija
    window.updateUserInfo = function(userData) {
        // Ova funkcija će biti implementirana u main.js ili auth.js
        console.log('User info updated:', userData);
        
        // Možeš dodati logiku za ažuriranje UI-a
        if (typeof window.updateNavbar === 'function') {
            window.updateNavbar(userData);
        }
    };
});

// Export za module (ako budeš koristio ES6 module)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = LoginHandler;
}