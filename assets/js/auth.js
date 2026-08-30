/**
 * assets/js/auth.js - Login funkcionalnost
 */

class LoginForm {
    constructor() {
        this.form = document.getElementById('login-form');
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.handleLogin();
        });
    }
    
    async handleLogin() {
        const formData = new FormData(this.form);
        const data = Object.fromEntries(formData.entries());
        
        // Validacija
        if (!data.username || !data.password) {
            this.showError('Unesite korisničko ime i lozinku');
            return;
        }
        
        // Prikaži loading
        this.showLoading(true);
        
        try {
            const response = await fetch(`${SITE_CONFIG.url}/api/user/login.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccess('Uspešno prijavljeni!');
                
                // Preusmeri
                setTimeout(() => {
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    } else {
                        window.location.href = '?page=dashboard';
                    }
                }, 1000);
            } else {
                this.showError(result.message || 'Greška pri prijavi');
            }
            
        } catch (error) {
            console.error('Login error:', error);
            this.showError('Greška pri povezivanju sa serverom');
        } finally {
            this.showLoading(false);
        }
    }
    
    showError(message) {
        const errorDiv = document.getElementById('login-error');
        if (errorDiv) {
            errorDiv.innerHTML = `
                <div class="alert alert-danger alert-dismissible fade show">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
        } else {
            alert(message);
        }
    }
    
    showSuccess(message) {
        const container = this.form.closest('.card-body');
        const successDiv = document.createElement('div');
        successDiv.className = 'alert alert-success alert-dismissible fade show';
        successDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        container.prepend(successDiv);
    }
    
    showLoading(show) {
        const submitBtn = this.form.querySelector('button[type="submit"]');
        if (submitBtn) {
            if (show) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Prijavljujem...';
                submitBtn.disabled = true;
            } else {
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Prijavi se';
                submitBtn.disabled = false;
            }
        }
    }
}

// Inicijalizacija
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('login-form')) {
        new LoginForm();
    }
});