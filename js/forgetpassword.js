document.addEventListener('DOMContentLoaded', function() {
    const forms = {
        verifyIdentity: document.getElementById('verifyIdentityForm'),
        resetPassword: document.getElementById('resetPasswordForm'),
        complete: document.getElementById('completeForm')
    };

    // Show password toggle functionality
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.classList.toggle('fa-eye-slash');
        });
    });

    // Form submission handlers
    if (forms.verifyIdentity) {
        forms.verifyIdentity.addEventListener('submit', function(e) {
            e.preventDefault();
            this.classList.remove('active');
            forms.resetPassword.classList.add('active');
            
            // Update progress steps
            document.querySelectorAll('.progress-step')[0].classList.add('completed');
            document.querySelectorAll('.progress-step')[1].classList.add('active');
        });
    }

    if (forms.resetPassword) {
        forms.resetPassword.addEventListener('submit', function(e) {
            e.preventDefault();
            this.classList.remove('active');
            forms.complete.classList.add('active');
            
            // Update progress steps
            document.querySelectorAll('.progress-step')[1].classList.add('completed');
            document.querySelectorAll('.progress-step')[2].classList.add('active');
        });
    }

    // Resend code link
    const resendLink = document.querySelector('.resend-code');
    if (resendLink) {
        resendLink.addEventListener('click', function(e) {
            e.preventDefault();
            alert('Verification code has been resent to your email.');
        });
    }
});