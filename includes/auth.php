<?php
// Password authentication has one verification policy for every browser route.
function authenticate_password($conn, $email, $password) {
    if (!is_string($email) || !is_string($password) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    $stmt = $conn->prepare('SELECT id, username, password_hash, is_verified FROM users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    if ((int)$user['is_verified'] !== 1) {
        return ['success' => false, 'unverified' => true, 'message' => 'Please verify your email before logging in.'];
    }
    establish_user_session($user);
    return ['success' => true, 'message' => 'Login successful!', 'redirect' => 'profile.php', 'username' => $user['username']];
}

function establish_user_session($user) {
    if (!session_regenerate_id(true)) {
        throw new RuntimeException('Unable to establish a new session.');
    }
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['username'] = $user['username'];
}

function remember_cookie_options($expires) {
    return ['expires' => $expires, 'path' => '/', 'secure' => parse_url(BASE_URL, PHP_URL_SCHEME) === 'https', 'httponly' => true, 'samesite' => 'Lax'];
}

function clear_remember_cookies() {
    foreach (['remember_token', 'remember_user'] as $name) {
        setcookie($name, '', remember_cookie_options(time() - 3600));
    }
}
