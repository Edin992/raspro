/**
 * assets/js/reset-password.js - JavaScript za reset-password stranicu
 */

class ResetPasswordPage {
    constructor() {
        this.form = document.getElementById('reset-password-form');
        this.tokenInput = document.getElementById('token');
        this.passwordInput = document.getElementById('password');
        this.confirmInput = document.getElementById('confirm_password');
        this.submitBtn = document.getElementById('submit-btn');
        this.spinner = this.submitBtn.querySelector('.spinner-border');
        this.successMessage = document.getElementById('success-message');
        this.errorMessage = document.getElementById('error-message');
        
        this.init();
    }
    
    init() {
        if (!this.form) return;
        
        this.form.addEventListener('submit', (e) => this.handleSubmit(e));
        
        // Password strength meter
        this.passwordInput.addEventListener('input', () => {
            this.validatePassword();
            this.checkPasswordMatch();
        });
        
        this.confirmInput.addEventListener('input', () => this.checkPasswordMatch());
        
        // Toggle password visibility
        document.getElementById('toggle-password').addEventListener('click', () => 
            this.togglePasswordVisibility(this.passwordInput, 'toggle-password'));
        
        document.getElementById('toggle-confirm').addEventListener('click', () => 
            this.togglePasswordVisibility(this.confirmInput, 'toggle-confirm'));
        
        console.log('ResetPasswordPage initialized');
    }
    
    validatePassword() {
        const password = this.passwordInput.value;
        const strengthBar = document.getElementById('password-strength');
        const strengthText = document.getElementById('strength-text');
        const lengthReq = document.getElementById('req-length');
        
        // Reset
        strengthBar.style.width = '0%';
        strengthBar.className = 'progress-bar';
        lengthReq.className = 'text-muted';
        
        if (!password) {
            strengthText.textContent = 'Jačina lozinke';
            return false;
        }
        
        // Check length
        const hasLength = password.length >= 8;
        if (hasLength) {
            lengthReq.classList.remove('text-muted');
            lengthReq.classList.add('text-success');
            lengthReq.innerHTML = '<i class="fas fa-check-circle me-1"></i> Najmanje 8 karaktera';
        }
        
        // Calculate strength
        let strength = 0;
        
        // Length
        if (password.length >= 8) strength += 25;
        if (password.length >= 12) strength += 15;
        
        // Complexity
        if (/[A-Z]/.test(password)) strength += 20;
        if (/[0-9]/.test(password)) strength += 20;
        if (/[^A-Za-z0-9]/.test(password)) strength += 20;
        
        // Update UI
        strengthBar.style.width = strength + '%';
        
        if (strength < 40) {
            strengthBar.classList.add('bg-danger');
            strengthText.textContent = 'Slaba';
            strengthText.className = 'text-danger';
        } else if (strength < 70) {
            strengthBar.classList.add('bg-warning');
            strengthText.textContent = 'Srednja';
            strengthText.className = 'text-warning';
        } else {
            strengthBar.classList.add('bg-success');
            strengthText.textContent = 'Jaka';
            strengthText.className = 'text-success';
        }
        
        return hasLength;
    }
    
    checkPasswordMatch() {
        const password = this.passwordInput.value;
        const confirm = this.confirmInput.value;
        const matchReq = document.getElementById('req-match');
        
        if (!password || !confirm) {
            matchReq.className = 'text-muted';
            matchReq.innerHTML = '<i class="fas fa-circle me-1"></i> Lozinke se poklapaju';
            return false;
        }
        
        if (password === confirm) {
            this.confirmInput.classList.remove('is-invalid');
            matchReq.classList.remove('text-muted');
            matchReq.classList.remove('text-danger');
            matchReq.classList.add('text-success');
            matchReq.innerHTML = '<i class="fas fa-check-circle me-1"></i> Lozinke se poklapaju';
            return true;
        } else {
            this.confirmInput.classList.add('is-invalid');
            matchReq.classList.remove('text-muted');
            matchReq.classList.add('text-danger');
            matchReq.innerHTML = '<i class="fas fa-times-circle me-1"></i> Lozinke se ne poklapaju';
            return false;
        }
    }
    
    togglePasswordVisibility(inputField, buttonId) {
        const button = document.getElementById(buttonId);
        const icon = button.querySelector('i');
        
        if (inputField.type === 'password') {
            inputField.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            inputField.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
    
    validateForm() {
        const isValidPassword = this.validatePassword();
        const passwordsMatch = this.checkPasswordMatch();
        
        if (!isValidPassword) {
            this.passwordInput.classList.add('is-invalid');
            return false;
        }
        
        this.passwordInput.classList.remove('is-invalid');
        return isValidPassword && passwordsMatch;
    }
    
    async handleSubmit(e) {
        e.preventDefault();
        
        if (!this.validateForm()) {
            this.showError('Popravite greške u formi.');
            return;
        }
        
        this.showLoading(true);
        this.hideMessages();
        
        const formData = {
            token: this.tokenInput.value,
            password: this.passwordInput.value,
            confirm_password: this.confirmInput.value
        };
        
        try {
            const response = await fetch('/api/user/confirm-reset.php', {
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
                    
                    // Redirect to login after 3 seconds
                    if (data.redirect) {
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 3000);
                    }
                } else {
                    // Handle specific error cases
                    if (data.needs_verification) {
                        this.showError(data.message + ' Preusmeravamo na verifikaciju...');
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 2000);
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
    
    hideMessages() {
        this.successMessage.classList.add('d-none');
        this.errorMessage.classList.add('d-none');
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    window.resetPasswordPage = new ResetPasswordPage();
});