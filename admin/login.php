<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    try {
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
                // Set ONLY admin_id in session
                $_SESSION['admin_id'] = $admin['id'];
                header('Location: index.php');
                exit;
            } else {
                $error = 'Invalid username or password!';
            }
        } else {
            $error = 'Invalid username or password!';
        }
        $stmt->close();
    } catch (Exception $e) {
        $error = 'Login system error: ' . $e->getMessage();
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