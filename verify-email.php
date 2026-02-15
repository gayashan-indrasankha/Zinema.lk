<?php
/**
 * Email Verification Page
 * Validates token and activates user account
 */

require_once 'admin/config.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = false;

if (!empty($token)) {
    // Validate token
    $stmt = $conn->prepare("
        SELECT id, username, email, is_verified, verification_expires 
        FROM users 
        WHERE verification_token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if ($user['is_verified']) {
            $error = 'Your email is already verified. You can login now.';
        } elseif (strtotime($user['verification_expires']) < time()) {
            $error = 'This verification link has expired. Please request a new one.';
        } else {
            // Verify the user
            $stmt->close();
            $stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = ?");
            $stmt->bind_param("i", $user['id']);
            
            if ($stmt->execute()) {
                $success = true;
                
                // Auto-login the user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
            } else {
                $error = 'Verification failed. Please try again.';
            }
        }
    } else {
        $error = 'Invalid verification link.';
    }
    $stmt->close();
} else {
    $error = 'No verification token provided.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Verify Email - Zinema.lk</title>
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
        .verify-container {
            background: linear-gradient(145deg, rgba(20, 22, 30, 1), rgba(15, 17, 25, 1));
            border-radius: 12px;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        .icon { font-size: 4rem; margin-bottom: 20px; }
        .icon.success { color: #51cf66; }
        .icon.error { color: #ff6b6b; }
        h1 { font-size: 1.8rem; margin-bottom: 15px; }
        p { color: rgba(255,255,255,0.7); font-size: 1rem; line-height: 1.6; margin-bottom: 25px; }
        .btn {
            display: inline-block;
            padding: 14px 30px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, rgba(138, 43, 226, 0.8), rgba(255, 51, 102, 0.6));
            color: #fff;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #8a2be2, rgba(255, 51, 102, 0.7));
            transform: translateY(-2px);
        }
        .countdown {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.5);
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <?php if ($success): ?>
            <div class="icon success"><i class="fas fa-check-circle"></i></div>
            <h1>Email Verified! 🎉</h1>
            <p>Your account is now active. You can start exploring Zinema.lk!</p>
            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
            <p class="countdown">Redirecting in <span id="countdown">5</span> seconds...</p>
            <script>
                let count = 5;
                const countdownEl = document.getElementById('countdown');
                const timer = setInterval(() => {
                    count--;
                    countdownEl.textContent = count;
                    if (count <= 0) {
                        clearInterval(timer);
                        window.location.href = 'profile.php';
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <div class="icon error"><i class="fas fa-times-circle"></i></div>
            <h1>Verification Failed</h1>
            <p><?php echo htmlspecialchars($error); ?></p>
            <a href="profile.php" class="btn btn-primary">Go to Profile</a>
        <?php endif; ?>
    </div>
</body>
</html>
