<?php
// Check if user is logged in
function check_login() {
    if (session_status() == PHP_SESSION_NONE) {
        require_once __DIR__ . '/../includes/session.php';
    }
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        header('Location: login.php');
        exit();
    }
}

// Handle database errors
function handle_db_error($conn) {
    error_log("Database error: " . $conn->error);
    die("A database error occurred. Please check the error logs.");
}
?>