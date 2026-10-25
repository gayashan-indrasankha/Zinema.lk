<?php
// Include database connection
require_once '../admin/config.php';

// Set JSON header
header('Content-Type: application/json');

// Get search query parameter and type
$q = $_GET['q'] ?? '';
$type = $_GET['type'] ?? 'movies'; // Default to movies
if (!is_string($q) || strlen($q)>200 || !is_string($type) || !in_array($type,['movies','series','collections'],true)) {
    http_response_code(400);echo json_encode(['error'=>'Invalid search']);exit;
}

// If search term is empty, return empty array
if (empty(trim($q))) {
    echo json_encode([]);
    exit;
}

// Prepare search query based on type
if ($type === 'collections') {
    $sql = "SELECT id, title, NULL AS release_date, cover_image FROM collections WHERE title LIKE ? ORDER BY created_at DESC LIMIT 10";
} elseif ($type === 'series') {
    $sql = "SELECT id, title, release_date, cover_image FROM series WHERE title LIKE ? ORDER BY created_at DESC LIMIT 10";
} else {
    $sql = "SELECT id, title, release_date, cover_image FROM movies WHERE title LIKE ? ORDER BY created_at DESC LIMIT 10";
}

try {
    $stmt = $conn->prepare($sql);
    $searchTerm = '%' . $q . '%';
    $stmt->bind_param('s', $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'release_date' => $row['release_date'],
            'cover_image' => $row['cover_image']
        ];
    }
    
    echo json_encode($items);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Search failed']);
}
?>
