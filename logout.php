<?php
// Load the configured session save path and start the session
require_once 'admin/config.php';

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
