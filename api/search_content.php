<?php
// Clean any output buffer and start fresh to prevent HTML output
ob_start();

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once '../admin/config.php';

// Clean output buffer before sending JSON
ob_end_clean();

// Set JSON header
header('Content-Type: application/json');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Get search parameters from URL
$term = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : 'movie';

// Validate inputs
if (empty($term)) {
    echo json_encode(['status' => 'error', 'message' => 'Search term is required', 'results' => []]);
    exit();
}

// Validate type
if (!in_array($type, ['movie', 'series', 'collection'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid content type', 'results' => []]);
    exit();
}

$results = [];

try {
    // Prepare search term for LIKE query
    $search_term = '%' . $term . '%';
    
    if ($type === 'movie') {
        // Search movies table
        $sql = "SELECT id, title, release_date FROM movies WHERE title LIKE ? ORDER BY title ASC LIMIT 20";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $search_term);
    } elseif ($type === 'series') {
        // Search series table
        $sql = "SELECT id, title, release_date FROM series WHERE title LIKE ? ORDER BY title ASC LIMIT 20";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $search_term);
    } else {
        // Search collections table
        $sql = "SELECT id, title, NULL as release_date FROM collections WHERE title LIKE ? ORDER BY title ASC LIMIT 20";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("s", $search_term);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'release_date' => $row['release_date']
            ];
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'status' => 'success',
        'count' => count($results),
        'results' => $results
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'results' => []
    ]);
}

$conn->close();
