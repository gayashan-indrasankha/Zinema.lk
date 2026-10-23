<?php
// Include database connection and start session
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

// Initialize error message
$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!validate_csrf_token($csrf_token)) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Validate input
        if (empty($email) || empty($password)) {
            $error = 'Please enter both email and password.';
    } else {
        // Query to get user by email
        $stmt = $conn->prepare("SELECT id, username, email, password_hash FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to profile page
                header('Location: profile.php');
                exit();
            } else {
                $error = 'Invalid email or password.';
            }
        } else {
            $error = 'Invalid email or password.';
        }
        
        $stmt->close();
    }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-container h1 {
            color: #fff;
            text-align: center;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .login-container .subtitle {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #fff;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: #e74c3c;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .error-message {
            background: rgba(231, 76, 60, 0.2);
            border: 1px solid #e74c3c;
            color: #fff;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #e74c3c, #c0392b);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #c0392b, #e74c3c);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(231, 76, 60, 0.6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: rgba(255, 255, 255, 0.7);
        }

        .signup-link a {
            color: #e74c3c;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .signup-link a:hover {
            color: #c0392b;
            text-decoration: underline;
        }

        .back-home {
            text-align: center;
            margin-top: 15px;
        }

        .back-home a {
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-home a:hover {
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>🎬 Zinema.lk</h1>
        <p class="subtitle">Login to your account</p>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?php csrf_token_field(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="Enter your email" 
                    required 
                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="Enter your password" 
                    required
                >
            </div>

            <button type="submit" class="btn-login">Login</button>
        </form>

        <div class="signup-link">
            <a href="signup.php">Don't have an account? Sign Up</a>
        </div>

        <div class="back-home">
            <a href="index.php">← Back to Home</a>
        </div>
    </div>
</body>
</html>
