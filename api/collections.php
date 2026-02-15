<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

try {
    // Check which column exists in movies table: release_date or release_year
    $check_columns = $conn->query("SHOW COLUMNS FROM movies LIKE 'release_%'");
    $date_column = 'release_date'; // default
    
    while ($col = $check_columns->fetch_assoc()) {
        if ($col['Field'] === 'release_year') {
            $date_column = 'release_year';
            break;
        }
    }
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Secret key and base URL for generating signed URLs
    $secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
    $base = 'https://sinhalamovies.web.lk';

    if ($id > 0) {
        // Fetch a single collection with its movies
        $stmt = $conn->prepare("SELECT * FROM collections WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $collection = $stmt->get_result()->fetch_assoc();

        if ($collection) {
            $collection['genre'] = array_map('trim', explode(',', $collection['genre']));
            
            // Fetch movies with proper column selection
            $movie_stmt = $conn->prepare("SELECT id, title, $date_column as release_date, cover_image, video_url FROM movies WHERE collection_id = ? ORDER BY $date_column ASC");
            $movie_stmt->bind_param("i", $id);
            $movie_stmt->execute();
            $movies_result = $movie_stmt->get_result();
            
            $collection['movies'] = [];
            while($movie = $movies_result->fetch_assoc()){
                // Generate signed streaming URL
                $video_id = (int)$movie['video_url']; // video_url holds the video ID
                $streaming_url = null;
                
                if ($video_id > 0) {
                    $exp = time() + 14400; // 4 hours expiration
                    $sig = hash_hmac('sha256', $video_id . '.' . $exp, $secret);
                    $streaming_url = "{$base}/tg/stream.php?id={$video_id}&exp={$exp}&sig={$sig}";
                }
                
                // Add the movie with the signed URL as driveId
                $collection['movies'][] = [
                    'id' => $movie['id'],
                    'title' => $movie['title'],
                    'release_date' => $movie['release_date'],
                    'cover_image' => $movie['cover_image'],
                    'driveId' => $streaming_url,
                    'video_url' => $streaming_url // Provide both for compatibility
                ];
            }
        }
        echo json_encode($collection);

    } else {
        // Fetch all collections
        $sql = "SELECT id, title, description, genre, cover_image FROM collections ORDER BY created_at DESC";
        $result = $conn->query($sql);
        
        if (!$result) {
            throw new Exception("Query failed: " . $conn->error);
        }
        
        $all_collections = [];
        while ($row = $result->fetch_assoc()) {
            $row['genre'] = array_map('trim', explode(',', $row['genre']));
            $all_collections[] = $row;
        }
        echo json_encode($all_collections);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch collections',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>