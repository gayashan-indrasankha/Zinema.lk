<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once 'config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    try {
        require_once __DIR__.'/../includes/password_throttle.php';
        $result=password_attempt($conn,'admin',$username,static function() use($conn,$username,$password) {
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
                return ['success'=>true,'id'=>(int)$admin['id']];
            }
        }
        $stmt->close();
        return ['success'=>false,'message'=>'Invalid username or password!'];
        });
        if ($result['success']) {
            session_regenerate_id(true);$_SESSION['admin_id']=$result['id'];
            header('Location: index.php');exit;
        }
        $error=$result['message'];
    } catch (Exception $e) {
        $error = 'Unable to sign in. Please try again later.';
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
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo substr(hash_file('sha256', __DIR__ . '/assets/admin.css'),0,12); ?>">
<?php if (function_exists('csrf_meta_tags')) csrf_meta_tags(); ?>
</head>
<body>
    <div class="login-container">
        <form method="POST" action="login.php" class="login-form"><?php csrf_token_field(); ?>
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
