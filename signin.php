<?php
session_start();
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : '';
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : '';
unset($_SESSION['error'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chama Management System</title>
    <link rel="stylesheet" href="style-1.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        .error { color: red; font-size: 0.9em; margin-top: 10px; text-align: center; }
        .success { color: green; font-size: 0.9em; margin-top: 10px; text-align: center; }
        .input-error { border: 1px solid red; }
        .password-container { display: flex; align-items: center; margin-bottom: 10px; }
        .toggle-password { margin-left: 10px; cursor: pointer; }
        .toggle-password-label { font-size: 0.8em; margin-left: 5px; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Login to Your Account</h1>
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form name="signin" action="signin_processor.php" method="POST">
            <input type="email" name="email" id="email" placeholder="Your Email" required><br><br>
            <p id="email_error" class="error" style="display: none;">Please enter a valid email address.</p>
            <div class="password-container">
                <input type="password" name="password" id="password" placeholder="Your Password" required>
                <input type="checkbox" id="togglePassword" class="toggle-password">
                <label for="togglePassword" class="toggle-password-label">Show Password</label>
            </div><br><br>
            <p id="password_error" class="error" style="display: none;">Password is required.</p>
            <input type="submit" value="Login">
        </form>
        <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
        <p><a href="forgot_password.php">Forgot Password?</a></p>
    </div>
    <script>
        const form = document.forms['signin'];
        const fields = [
            {
                id: 'email',
                validate: value => /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value),
                errorMsg: 'Please enter a valid email address.'
            },
            {
                id: 'password',
                validate: value => value.length > 0,
                errorMsg: 'Password is required.'
            }
        ];
        function showError(fieldId, show, message) {
            const errorElement = document.getElementById(`${fieldId}_error`);
            const inputElement = document.getElementById(fieldId);
            errorElement.textContent = message;
            errorElement.style.display = show ? 'block' : 'none';
            inputElement.classList.toggle('input-error', show);
        }
        function validateField(field) {
            const input = form[field.id];
            const value = input.value.trim();
            const isValid = field.validate(value);
            showError(field.id, !isValid, field.errorMsg);
            return isValid;
        }
        fields.forEach(field => {
            const input = form[field.id];
            input.addEventListener('blur', () => validateField(field));
            input.addEventListener('input', () => {
                if (document.getElementById(`${field.id}_error`).style.display === 'block') {
                    validateField(field);
                }
            });
        });
        form.addEventListener('submit', (e) => {
            let isValid = true;
            fields.forEach(field => {
                if (!validateField(field)) {
                    isValid = false;
                    console.log(`Validation failed for ${field.id}: ${field.errorMsg}`);
                }
            });
            if (!isValid) {
                e.preventDefault();
                const firstError = document.querySelector('.error[style*="block"]');
                if (firstError) {
                    document.getElementById(firstError.id.replace('_error', '')).focus();
                }
                console.log('Form submission prevented due to validation errors.');
            } else {
                console.log('Form is valid, submitting...');
            }
        });
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            console.log('Password visibility toggled to:', type); // Debugging
        });
    </script>
</body>
</html>