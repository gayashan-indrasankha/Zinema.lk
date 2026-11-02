<?php
/**
 * Password Reset Page
 * Validates token and allows user to set new password
 */

require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;
$tokenValid = false;

// Validate token if provided
if (!empty($token)) {
    // Check if token exists and is valid
    $stmt = $conn->prepare("
        SELECT pr.id, pr.user_id, pr.expires_at, pr.used, u.email, u.username 
        FROM password_resets pr 
        JOIN users u ON pr.user_id = u.id 
        WHERE pr.token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $resetData = $result->fetch_assoc();
        
        if ($resetData['used']) {
            $error = 'This reset link has already been used.';
        } elseif (strtotime($resetData['expires_at']) < time()) {
            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $tokenValid = true;
        }
    } else {
        $error = 'Invalid reset link.';
    }
    $stmt->close();
} else {
    $error = 'No reset token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Reset Password - Zinema.lk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #000;
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .reset-container {
            background: linear-gradient(145deg, rgba(20, 22, 30, 1), rgba(15, 17, 25, 1));
            border-radius: 12px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .logo {
            text-align: center;
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        .subtitle {
            text-align: center;
            color: rgba(255,255,255,0.7);
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
        }
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            background: rgba(20, 22, 30, 0.7);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #8a2be2;
            background: rgba(25, 28, 38, 0.9);
        }
        .form-group input.invalid { border-color: #ff6b6b; }
        .form-group input.valid { border-color: #51cf66; }
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, rgba(138, 43, 226, 0.8), rgba(255, 51, 102, 0.6));
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #8a2be2, rgba(255, 51, 102, 0.7));
            transform: translateY(-2px);
        }
        .btn-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        .error-message, .success-message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .error-message {
            background: rgba(220, 38, 38, 0.15);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #fca5a5;
        }
        .success-message {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.5);
            color: #86efac;
        }
        .password-strength {
            height: 4px;
            background: rgba(255,255,255,0.1);
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
        }
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: all 0.3s;
            border-radius: 2px;
        }
        .strength-weak { width: 33%; background: #ff6b6b; }
        .strength-medium { width: 66%; background: #ffd43b; }
        .strength-strong { width: 100%; background: #51cf66; }
        .password-hint {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.5);
            margin-top: 5px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #8a2be2;
            text-decoration: none;
        }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="logo">🎬 Zinema.lk</div>
        <p class="subtitle">Reset Your Password</p>
        
        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
            <div class="back-link">
                <a href="profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            </div>
        <?php elseif ($tokenValid): ?>
            <div id="form-error" class="error-message" style="display: none;"></div>
            <div id="form-success" class="success-message" style="display: none;"></div>
            
            <form id="reset-form">
                <?php csrf_token_field(); ?>
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter new password" required minlength="6">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strength-bar"></div>
                    </div>
                    <p class="password-hint">Minimum 6 characters</p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                </div>
                
                <button type="submit" class="btn-submit" id="submit-btn">
                    <span class="btn-text">Reset Password</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
            </form>
            
            <div class="back-link">
                <a href="profile.php"><i class="fas fa-arrow-left"></i> Back to Profile</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.getElementById('strength-bar');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength++;
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                strengthBar.className = 'password-strength-bar';
                if (strength <= 2) {
                    strengthBar.classList.add('strength-weak');
                } else if (strength <= 3) {
                    strengthBar.classList.add('strength-medium');
                } else {
                    strengthBar.classList.add('strength-strong');
                }
                
                // Validate match
                validateMatch();
            });
            
            confirmInput.addEventListener('input', validateMatch);
        }
        
        function validateMatch() {
            const password = passwordInput.value;
            const confirm = confirmInput.value;
            
            if (confirm.length > 0) {
                if (password === confirm) {
                    confirmInput.classList.remove('invalid');
                    confirmInput.classList.add('valid');
                } else {
                    confirmInput.classList.remove('valid');
                    confirmInput.classList.add('invalid');
                }
            } else {
                confirmInput.classList.remove('valid', 'invalid');
            }
        }
        
        // Form submission
        const resetForm = document.getElementById('reset-form');
        if (resetForm) {
            resetForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const password = passwordInput.value;
                const confirm = confirmInput.value;
                const errorDiv = document.getElementById('form-error');
                const successDiv = document.getElementById('form-success');
                const submitBtn = document.getElementById('submit-btn');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoader = submitBtn.querySelector('.btn-loader');
                
                // Hide previous messages
                errorDiv.style.display = 'none';
                successDiv.style.display = 'none';
                
                // Validate
                if (password.length < 6) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Password must be at least 6 characters';
                    errorDiv.style.display = 'block';
                    return;
                }
                
                if (password !== confirm) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> Passwords do not match';
                    errorDiv.style.display = 'block';
                    return;
                }
                
                // Show loading
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline';
                
                try {
                    const formData = new FormData(this);
                    
                    const response = await fetch('ajax_reset_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                        successDiv.style.display = 'block';
                        resetForm.style.display = 'none';
                        
                        // Redirect after 2 seconds
                        setTimeout(() => {
                            window.location.href = 'profile.php';
                        }, 2000);
                    } else {
                        errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + data.message;
                        errorDiv.style.display = 'block';
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                    }
                } catch (error) {
                    errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> An error occurred. Please try again.';
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoader.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
