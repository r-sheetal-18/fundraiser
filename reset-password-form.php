<?php
// Include database connection
require_once 'config.php';

// Initialize variables
$token = '';
$tokenValid = false;
$errorMessage = '';

// Check if token is provided in the URL
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $stmt = $conn->prepare("SELECT pr.user_id, pr.expiry, u.email 
                           FROM password_resets pr 
                           JOIN users u ON pr.user_id = u.id 
                           WHERE pr.token = ? AND pr.expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $tokenValid = true;
        $userData = $result->fetch_assoc();
    } else {
        $errorMessage = 'Invalid or expired token. Please request a new password reset link.';
    }
} else {
    $errorMessage = 'Token is missing. Please request a new password reset link.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Correctly load Bootstrap Icons as CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="forgot-password-card">
                    <?php if ($tokenValid): ?>
                        <div class="text-center mb-4">
                            <h2>Reset Password</h2>
                            <p class="text-muted">Create a new password for <?php echo htmlspecialchars($userData['email']); ?></p>
                        </div>
                        
                        <form id="resetPasswordForm">
                            <input type="hidden" id="token" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="password-requirements mt-2">
                                    <small class="d-block mb-1">Password must contain:</small>
                                    <small class="d-block" id="length-check">✓ At least 8 characters</small>
                                    <small class="d-block" id="uppercase-check">✓ At least one uppercase letter</small>
                                    <small class="d-block" id="lowercase-check">✓ At least one lowercase letter</small>
                                    <small class="d-block" id="number-check">✓ At least one number</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                                </div>
                                <div class="form-text" id="passwordMatch">Both passwords must match</div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Change Password</button>
                            </div>
                        </form>
                        
                        <div id="messageBox" class="alert mt-4 d-none">
                            <span id="messageText"></span>
                        </div>
                    <?php else: ?>
                        <div class="text-center">
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $errorMessage; ?>
                            </div>
                            <a href="forgot-password.html" class="btn btn-primary mt-3">Request New Reset Link</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if ($tokenValid): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const resetPasswordForm = document.getElementById('resetPasswordForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const messageBox = document.getElementById('messageBox');
            const messageText = document.getElementById('messageText');
            const passwordMatch = document.getElementById('passwordMatch');
            
            // Password strength indicators
            const lengthCheck = document.getElementById('length-check');
            const uppercaseCheck = document.getElementById('uppercase-check');
            const lowercaseCheck = document.getElementById('lowercase-check');
            const numberCheck = document.getElementById('number-check');
            
            // Password validation
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                
                // Check length
                if (password.length >= 8) {
                    lengthCheck.classList.add('text-success');
                    lengthCheck.innerHTML = '✓ At least 8 characters';
                } else {
                    lengthCheck.classList.remove('text-success');
                    lengthCheck.innerHTML = '✗ At least 8 characters';
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    uppercaseCheck.classList.add('text-success');
                    uppercaseCheck.innerHTML = '✓ At least one uppercase letter';
                } else {
                    uppercaseCheck.classList.remove('text-success');
                    uppercaseCheck.innerHTML = '✗ At least one uppercase letter';
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    lowercaseCheck.classList.add('text-success');
                    lowercaseCheck.innerHTML = '✓ At least one lowercase letter';
                } else {
                    lowercaseCheck.classList.remove('text-success');
                    lowercaseCheck.innerHTML = '✗ At least one lowercase letter';
                }
                
                // Check number
                if (/[0-9]/.test(password)) {
                    numberCheck.classList.add('text-success');
                    numberCheck.innerHTML = '✓ At least one number';
                } else {
                    numberCheck.classList.remove('text-success');
                    numberCheck.innerHTML = '✗ At least one number';
                }
                
                // Check password match
                checkPasswordMatch();
            });
            
            // Check if passwords match
            confirmPasswordInput.addEventListener('input', checkPasswordMatch);
            
            function checkPasswordMatch() {
                if (passwordInput.value === confirmPasswordInput.value && confirmPasswordInput.value !== '') {
                    passwordMatch.className = 'form-text text-success';
                    passwordMatch.innerHTML = '✓ Passwords match';
                } else if (confirmPasswordInput.value !== '') {
                    passwordMatch.className = 'form-text text-danger';
                    passwordMatch.innerHTML = '✗ Passwords do not match';
                } else {
                    passwordMatch.className = 'form-text';
                    passwordMatch.innerHTML = 'Both passwords must match';
                }
            }
            
            // Form submission
            resetPasswordForm.addEventListener('submit', function(event) {
                event.preventDefault();
                
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const token = document.getElementById('token').value;
                
                // Validate password
                if (password.length < 8) {
                    showMessage('Password must be at least 8 characters long.', 'danger');
                    return;
                }
                
                if (!/[A-Z]/.test(password)) {
                    showMessage('Password must contain at least one uppercase letter.', 'danger');
                    return;
                }
                
                if (!/[a-z]/.test(password)) {
                    showMessage('Password must contain at least one lowercase letter.', 'danger');
                    return;
                }
                
                if (!/[0-9]/.test(password)) {
                    showMessage('Password must contain at least one number.', 'danger');
                    return;
                }
                
                // Check if passwords match
                if (password !== confirmPassword) {
                    showMessage('Passwords do not match.', 'danger');
                    return;
                }
                
                // Show loading state
                const submitButton = resetPasswordForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
                submitButton.disabled = true;
                
                // Send AJAX request to update password
                fetch('update-password.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `token=${encodeURIComponent(token)}&password=${encodeURIComponent(password)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showMessage(data.message, 'success');
                        resetPasswordForm.innerHTML = `
                            <div class="text-center mt-4">
                                <div class="mb-4">
                                    <i class="bi bi-check-circle text-success" style="font-size: 3rem;"></i>
                                </div>
                                <h3>Password Updated Successfully!</h3>
                                <p class="mb-4">Your password has been changed. You can now log in with your new password.</p>
                                <div class="d-grid">
                                    <a href="login.html" class="btn btn-primary btn-lg">Go to Login</a>
                                </div>
                            </div>
                        `;
                    } else {
                        showMessage(data.message, 'danger');
                        submitButton.innerHTML = originalButtonText;
                        submitButton.disabled = false;
                    }
                })
                .catch(error => {
                    showMessage('An error occurred. Please try again later.', 'danger');
                    console.error('Error:', error);
                    submitButton.innerHTML = originalButtonText;
                    submitButton.disabled = false;
                });
            });
            
            function showMessage(message, type) {
                messageText.textContent = message;
                messageBox.className = `alert alert-${type} mt-4`;
                messageBox.classList.remove('d-none');
                
                // Scroll to message
                messageBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>