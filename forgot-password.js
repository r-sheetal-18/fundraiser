document.addEventListener('DOMContentLoaded', function() {
    const forgotPasswordForm = document.getElementById('forgotPasswordForm');
    const verificationForm = document.getElementById('verificationForm');
    const resetPasswordForm = document.getElementById('resetPasswordForm');
    
    const step1 = document.getElementById('step1');
    const step2 = document.getElementById('step2');
    const step3 = document.getElementById('step3');
    
    const errorMessage = document.getElementById('error-message');
    const successMessage = document.getElementById('success-message');
    
    // Password validation elements
    const newPassword = document.getElementById('newPassword');
    const confirmPassword = document.getElementById('confirmPassword');
    const passwordMatch = document.getElementById('passwordMatch');
    
    const lengthReq = document.getElementById('length');
    const uppercaseReq = document.getElementById('uppercase');
    const lowercaseReq = document.getElementById('lowercase');
    const numberReq = document.getElementById('number');
    const specialReq = document.getElementById('special');
    
    let email = '';
    let token = '';
    
    // Step 1: Submit email for password reset
    forgotPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('email').value;
        email = emailInput;
        
        errorMessage.style.display = 'none';
        successMessage.style.display = 'none';
        
        // Send request to the server
        fetch('forgot_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ email: emailInput })
        })
        .then(response => {
            // Check if response is OK before trying to parse JSON
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                successMessage.textContent = data.message;
                successMessage.style.display = 'block';
                
                // Move to step 2 after short delay
                setTimeout(() => {
                    step1.classList.remove('active');
                    step2.classList.add('active');
                }, 2000);
            } else {
                errorMessage.textContent = data.message;
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorMessage.textContent = 'An error occurred while communicating with the server. Please try again later.';
            errorMessage.style.display = 'block';
        });
    });
    
    // Step 2: Verify token
    verificationForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const code = document.getElementById('verificationCode').value;
        token = code;
        
        // Send request to verify the code
        fetch('verify_code.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                email: email,
                token: code 
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Move to step 3
                step2.classList.remove('active');
                step3.classList.add('active');
            } else {
                alert(data.message || 'Invalid verification code.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while verifying the code. Please try again later.');
        });
    });
    
    // Password validation
    newPassword.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', checkPasswordMatch);
    
    function validatePassword() {
        const password = newPassword.value;
        
        // Check password requirements
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasLowercase = /[a-z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecial = /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Update visual indicators
        lengthReq.classList.toggle('valid', hasLength);
        uppercaseReq.classList.toggle('valid', hasUppercase);
        lowercaseReq.classList.toggle('valid', hasLowercase);
        numberReq.classList.toggle('valid', hasNumber);
        specialReq.classList.toggle('valid', hasSpecial);
        
        // Check match if confirm password has value
        if (confirmPassword.value) {
            checkPasswordMatch();
        }
    }
    
    function checkPasswordMatch() {
        const isMatch = newPassword.value === confirmPassword.value;
        
        if (confirmPassword.value) {
            confirmPassword.classList.toggle('is-invalid', !isMatch);
        } else {
            confirmPassword.classList.remove('is-invalid');
        }
    }
    
    // Step 3: Reset password
    resetPasswordForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const password = newPassword.value;
        const passwordConfirm = confirmPassword.value;
        
        // Validate password format
        const isValid = password.length >= 8 && 
                       /[A-Z]/.test(password) && 
                       /[a-z]/.test(password) && 
                       /\d/.test(password) && 
                       /[!@#$%^&*(),.?":{}|<>]/.test(password);
        
        // Check if passwords match
        if (password !== passwordConfirm) {
            confirmPassword.classList.add('is-invalid');
            return;
        }
        
        if (!isValid) {
            alert('Please meet all password requirements.');
            return;
        }
        
        // Send request to reset password
        fetch('reset_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: email,
                token: token,
                password: password
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Server responded with status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Password reset successfully! Redirecting to login page...');
                window.location.href = 'login.php';
            } else {
                alert(data.message || 'Failed to reset password.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while resetting your password. Please try again later.');
        });
    });
});