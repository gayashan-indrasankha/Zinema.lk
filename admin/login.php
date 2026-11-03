<?php
require_once 'config.php';
$error = '';

// Brute-force protection: at most 5 failed sign-ins per IP address in 15 minutes
define('ADMIN_LOGIN_MAX_FAILS', 5);
define('ADMIN_LOGIN_WINDOW_MINUTES', 15);
$client_ip = $_SERVER['REMOTE_ADDR'] ?? '';

function admin_login_failures($conn, $ip) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND successful = 0 AND attempt_time > NOW() - INTERVAL " . ADMIN_LOGIN_WINDOW_MINUTES . " MINUTE");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_row()[0];
    $stmt->close();
    return $count;
}

function record_admin_login($conn, $ip, $username, $successful) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username, successful) VALUES (?, ?, ?)");
    $flag = $successful ? 1 : 0;
    $stmt->bind_param("ssi", $ip, $username, $flag);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $sent_token = $_POST['csrf_token'] ?? '';

    try {
        if (!is_string($sent_token) || !hash_equals(admin_csrf_token(), $sent_token)) {
            throw new RuntimeException('Your session expired. Please try again.');
        }
        if (admin_login_failures($conn, $client_ip) >= ADMIN_LOGIN_MAX_FAILS) {
            throw new RuntimeException('Too many failed sign-in attempts. Please wait ' . ADMIN_LOGIN_WINDOW_MINUTES . ' minutes and try again.');
        }
        // Prepare statement to get user (MUST SELECT id column)
        $stmt = $conn->prepare("SELECT id, password_hash FROM admins WHERE username = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();
            
            // Verify password using password_verify (secure)
            if (password_verify($password, $admin['password_hash'])) {
                record_admin_login($conn, $client_ip, $username, true);
                // New session ID on sign-in (prevents session fixation)
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_last_seen'] = time();
                unset($_SESSION['admin_csrf']);
                header('Location: index.php');
                exit;
            } else {
                record_admin_login($conn, $client_ip, $username, false);
                $error = 'Invalid username or password!';
            }
        } else {
            record_admin_login($conn, $client_ip, $username, false);
            $error = 'Invalid username or password!';
        }
        $stmt->close();
    } catch (mysqli_sql_exception $e) {
        error_log('Admin login error: ' . $e->getMessage());
        $error = 'Sign-in is temporarily unavailable. Please try again.';
    } catch (RuntimeException $e) {
        $error = $e->getMessage();
    } catch (Exception $e) {
        error_log('Admin login error: ' . $e->getMessage());
        $error = 'Sign-in is temporarily unavailable. Please try again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <div class="login-container">
        <form method="POST" action="login.php" class="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(admin_csrf_token(), ENT_QUOTES); ?>">
            <h2>Zinema.lk Admin Login</h2>
            <?php if ($error): ?>
                <p class="error"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>