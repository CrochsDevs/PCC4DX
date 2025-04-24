document.addEventListener('DOMContentLoaded', () => {
    const forms = document.querySelectorAll('.gov-form');
    let currentStep = 0;
    let recoveryData = {};

    function showStep(stepIndex) {
        forms.forEach((form, index) => {
            form.classList.toggle('active', index === stepIndex);
        });
        currentStep = stepIndex;
    }

    // Toggle password visibility
    document.querySelectorAll('.toggle-password').forEach(icon => {
        icon.addEventListener('click', (e) => {
            const input = e.target.previousElementSibling;
            input.type = input.type === 'password' ? 'text' : 'password';
            e.target.classList.toggle('fa-eye-slash');
        });
    });

    // Step 1: Verify Identity
    document.getElementById('verifyIdentityForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            email: document.getElementById('govEmail').value,
            employee_id: document.getElementById('govEmployeeID').value,
            captcha: document.querySelector('.captcha-container input').value
        };

        try {
            const response = await fetch('verify_identity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result.success) {
                recoveryData = payload;
                showStep(1);
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });

    // Step 2: Reset Password
    document.getElementById('resetPasswordForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const payload = {
            ...recoveryData,
            verification_code: document.getElementById('verificationCode').value,
            new_password: document.getElementById('newPassword').value,
            confirm_password: document.getElementById('confirmPassword').value
        };

        try {
            const response = await fetch('reset_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            
            const result = await response.json();
            
            if (result.success) {
                showStep(2);
            } else {
                alert(result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        }
    });

    // Resend code functionality
    document.querySelector('.resend-code').addEventListener('click', async (e) => {
        e.preventDefault();
        try {
            const response = await fetch('verify_identity.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(recoveryData)
            });
            
            const result = await response.json();
            alert(result.success ? 'New code sent!' : result.message);
        } catch (error) {
            console.error('Error:', error);
            alert('Failed to resend code');
        }
    });
});