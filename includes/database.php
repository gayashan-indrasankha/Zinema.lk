<?php
require_once __DIR__ . '/environment.php';
recovery_browser_policy();
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (Throwable $e) {
    recovery_config_error('database connection');
}

// Function to get movie by ID
function getMovie($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get trailers for a movie
function getMovieTrailers($movieId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM trailers WHERE movie_id = ?");
    $stmt->execute([$movieId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to format duration
function formatDuration($minutes) {
    if (!$minutes) return '';
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    return ($hours ? $hours . 'h ' : '') . ($mins ? $mins . 'm' : '');
}