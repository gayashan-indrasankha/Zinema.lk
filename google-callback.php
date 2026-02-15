<?php
/**
 * Google OAuth Callback Handler
 * Handles the OAuth response from Google and logs in/registers the user
 */

require_once 'admin/config.php';

// Check for errors
if (isset($_GET['error'])) {
    header('Location: profile.php?error=google_denied');
    exit;
}

// Check for authorization code
if (!isset($_GET['code'])) {
    header('Location: profile.php?error=no_code');
    exit;
}

$code = $_GET['code'];

// Exchange code for access token
$tokenUrl = 'https://oauth2.googleapis.com/token';
$tokenData = [
    'code' => $code,
    'client_id' => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'grant_type' => 'authorization_code'
];

$ch = curl_init($tokenUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$tokenResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    error_log("Google OAuth token error: " . $tokenResponse);
    header('Location: profile.php?error=token_error');
    exit;
}

$tokenInfo = json_decode($tokenResponse, true);
$accessToken = $tokenInfo['access_token'] ?? null;

if (!$accessToken) {
    header('Location: profile.php?error=no_token');
    exit;
}

// Get user info from Google
$userInfoUrl = 'https://www.googleapis.com/oauth2/v2/userinfo';
$ch = curl_init($userInfoUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$userResponse = curl_exec($ch);
curl_close($ch);

$googleUser = json_decode($userResponse, true);

if (!isset($googleUser['id']) || !isset($googleUser['email'])) {
    error_log("Google OAuth user info error: " . $userResponse);
    header('Location: profile.php?error=user_info_error');
    exit;
}

$googleId = $googleUser['id'];
$email = $googleUser['email'];
$name = $googleUser['name'] ?? explode('@', $email)[0];

// Check if user exists by google_id
$stmt = $conn->prepare("SELECT id, username, email, is_verified FROM users WHERE google_id = ?");
$stmt->bind_param("s", $googleId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Existing Google user - log them in
    $user = $result->fetch_assoc();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $stmt->close();
    header('Location: profile.php');
    exit;
}
$stmt->close();

// Check if user exists by email
$stmt = $conn->prepare("SELECT id, username, google_id, is_verified FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Email exists - link Google account to existing user
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Update user with Google ID
    $updateStmt = $conn->prepare("UPDATE users SET google_id = ?, is_verified = 1 WHERE id = ?");
    $updateStmt->bind_param("si", $googleId, $user['id']);
    $updateStmt->execute();
    $updateStmt->close();
    
    // Log them in
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    header('Location: profile.php');
    exit;
}
$stmt->close();

// New user - create account
// Generate a unique username from name
$baseUsername = preg_replace('/[^a-zA-Z0-9_]/', '', strtolower($name));
$baseUsername = substr($baseUsername, 0, 15) ?: 'user';
$username = $baseUsername;
$counter = 1;

// Ensure unique username
while (true) {
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $checkStmt->close();
    
    if ($checkResult->num_rows === 0) {
        break;
    }
    $username = $baseUsername . $counter;
    $counter++;
}

// Create new user (no password, verified via Google)
$stmt = $conn->prepare("INSERT INTO users (username, email, password_hash, is_verified, google_id, created_at) VALUES (?, ?, '', 1, ?, NOW())");
$stmt->bind_param("sss", $username, $email, $googleId);

if ($stmt->execute()) {
    $userId = $conn->insert_id;
    $stmt->close();
    
    // Log them in
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    header('Location: profile.php?welcome=1');
    exit;
} else {
    $stmt->close();
    error_log("Google OAuth user creation error: " . $conn->error);
    header('Location: profile.php?error=create_error');
    exit;
}
