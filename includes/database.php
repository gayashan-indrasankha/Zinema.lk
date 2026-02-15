<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'cinedrive';
$db_user = 'root';
$db_pass = '';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
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