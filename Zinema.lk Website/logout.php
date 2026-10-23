<?php
// Start session
session_start();

// Unset all session variables
session_unset();

// Delete the session cookie explicitly
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to home page (Shots feed)
header('Location: index.php');
exit();
