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
    <title>Forgot Password - Chama Management System</title>
    <link rel="stylesheet" href="style-1.css">
    <link rel="stylesheet" href="responsive.css">
    <style>
        .error { color: red; font-size: 0.9em; margin-top: 10px; text-align: center; }
        .success { color: green; font-size: 0.9em; margin-top: 10px; text-align: center; }
        .input-error { border: 1px solid red; }
    </style>
</head>
<body>
    <div class="login-form">
        <h1>Forgot Password</h1>
        <p>Enter your email address to receive a password reset link.</p>
        <?php if ($error_message): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <p class="success"><?php echo $success_message; ?></p>
        <?php endif; ?>
        <form name="forgot_password" action="signin_processor.php" method="POST">
            <input type="hidden" name="action" value="forgot_password">
            <input type="email" name="email" id="email" placeholder="Your Email" required><br><br>
            <p id="email_error" class="error" style="display: none;">Please enter a valid email address.</p>
            <input type="submit" value="Send Reset Link">
        </form>
        <p>Remembered your password? <a href="signin.php">Sign In</a></p>
    </div>
    <script>
        const form = document.forms['forgot_password'];
        const emailField = {
            id: 'email',
            validate: value => /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(value),
            errorMsg: 'Please enter a valid email address.'
        };
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
        form.email.addEventListener('blur', () => validateField(emailField));
        form.email.addEventListener('input', () => {
            if (document.getElementById('email_error').style.display === 'block') {
                validateField(emailField);
            }
        });
        form.addEventListener('submit', (e) => {
            if (!validateField(emailField)) {
                e.preventDefault();
                document.getElementById('email').focus();
                console.log('Form submission prevented due to validation errors.');
            } else {
                console.log('Form is valid, submitting...');
            }
        });
    </script>
</body>
</html>