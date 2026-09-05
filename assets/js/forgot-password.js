/**
 * assets/js/forgot-password.js - JavaScript za forgot-password stranicu
 */

class ForgotPasswordPage {
    constructor() {
        this.form = document.getElementById('forgot-password-form');
        this.emailInput = document.getElementById('email');
        this.submitBtn = document.getElementById('submit-btn');
        this.spinner = this.submitBtn.querySelector('.spinner-border');
        this.successMessage = document.getElementById('success-message');
        this.errorMessage = document.getElementById('error-message');
        this.verificationMessage = document.getElementById('verification-message');
        this.rateLimitMessage = document.getElementById('rate-limit-message');
        this.rateInfo = document.getElementById('rate-info');
        this.attemptsCount = document.getElementById('attempts-count');
        
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Real-time email validation
        this.emailInput.addEventListener('blur', () => this.validateEmail());
        
        // Rate limit check on email change
        this.emailInput.addEventListener('input', () => this.checkRateLimit());
        
        console.log('ForgotPasswordPage initialized');
    }
    
    validateEmail() {
        const email = this.emailInput.value.trim();
        const isValid = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        
        if (email && !isValid) {
            this.emailInput.classList.add('is-invalid');
            return false;
        }
        
        this.emailInput.classList.remove('is-invalid');
        return true;
    }
    
    async checkRateLimit() {
        const email = this.emailInput.value.trim();
        if (!email || !this.validateEmail()) {
            this.rateInfo.style.display = 'none';
            return;
        }
        
        try {
            // Ovo je mock funkcija - u stvarnoj implementaciji 
            // možete da pozovete API za rate limit info
            // Ovde samo prikazujemo UI
            this.rateInfo.style.display = 'block';
            this.attemptsCount.textContent = '0';
        } catch (error) {
            console.error('Rate limit check error:', error);
        }
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateEmail()) {
            this.showError('Unesite validnu email adresu.');
            return;
        }
        
        this.showLoading(true);
        this.hideMessages();
        
        // reCAPTCHA v3 token (ako je ukljucen na serveru)
        let recaptchaToken = '';
        const rcCfg = window.RECAPTCHA;
        if (rcCfg && rcCfg.enabled) {
            try {
                recaptchaToken = await new Promise((resolve, reject) => {
                    grecaptcha.ready(() => {
                        grecaptcha.execute(rcCfg.siteKey, { action: 'reset_password' })
                            .then(resolve).catch(reject);
                    });
                    setTimeout(() => reject(new Error('recaptcha-timeout')), 10000);
                });
            } catch (err) {
                console.error('reCAPTCHA error:', err);
                this.showError('reCAPTCHA verifikacija nije uspela. Proverite vezu i pokušajte ponovo.');
                this.showLoading(false);
                return;
            }
        }
        
        const formData = {
            email: this.emailInput.value.trim(),
            recaptcha_token: recaptchaToken
        };
        
        try {
            const response = await fetch('/api/user/request-reset.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (response.ok) {
                if (data.success) {
                    this.showSuccess(data.message);
                    this.form.reset();
                    
                    // Ako ima rate info u odgovoru
                    if (data.rate_info) {
                        this.attemptsCount.textContent = data.rate_info.attempts;
                        this.rateInfo.style.display = 'block';
                    }
                } else {
                    // Handle specific error cases
                    if (data.needs_verification) {
                        this.showVerificationRequired(data.message);
                    } else if (response.status === 429) {
                        this.showRateLimit(data.message);
                    } else {
                        this.showError(data.message);
                    }
                }
            } else {
                this.showError(data.message || 'Došlo je do greške. Pokušajte ponovo.');
            }
            
        } catch (error) {
            console.error('Request error:', error);
            this.showError('Problem sa mrežom. Proverite internet konekciju.');
        } finally {
            this.showLoading(false);
        }
    }
    
    showLoading(show) {
        if (show) {
            this.spinner.classList.remove('d-none');
            this.submitBtn.disabled = true;
            this.submitBtn.querySelector('i').classList.add('d-none');
        } else {
            this.spinner.classList.add('d-none');
            this.submitBtn.disabled = false;
            this.submitBtn.querySelector('i').classList.remove('d-none');
        }
    }
    
    showSuccess(message) {
        this.successMessage.classList.remove('d-none');
        document.getElementById('success-text').textContent = message;
        this.successMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    showError(message) {
        this.errorMessage.classList.remove('d-none');
        document.getElementById('error-text').textContent = message;
        this.errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    showVerificationRequired(message) {
        this.verificationMessage.classList.remove('d-none');
        document.getElementById('verification-text').textContent = message;
        this.verificationMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    showRateLimit(message) {
        this.rateLimitMessage.classList.remove('d-none');
        document.getElementById('rate-limit-text').textContent = message;
        this.rateLimitMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    hideMessages() {
        this.successMessage.classList.add('d-none');
        this.errorMessage.classList.add('d-none');
        this.verificationMessage.classList.add('d-none');
        this.rateLimitMessage.classList.add('d-none');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.forgotPasswordPage = new ForgotPasswordPage();
});