<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

try {
    $sql = "SELECT id, title, release_date, genre, cover_image, video_url, collection_id FROM movies ORDER BY created_at DESC";
    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $movies = [];
    $secret = STREAM_SIGNING_SECRET;
    $base = STREAM_BASE_URL;

    while ($row = $result->fetch_assoc()) {
        $row['genre'] = array_map('trim', explode(',', $row['genre']));
        
        // Generate signed streaming URL
        $video_id = (int)$row['video_url']; // video_url now holds the video ID from videos table
        if (MEDIA_MODE !== 'disabled' && $video_id > 0) {
            $exp = time() + 14400; // 4 hours
            $sig = hash_hmac('sha256', $video_id . '.' . $exp, $secret);
            $row['video_url'] = "{$base}/tg/stream.php?id={$video_id}&exp={$exp}&sig={$sig}";
        } else {
            $row['video_url'] = null;
        }
        
        $movies[] = $row;
    }

    echo json_encode($movies);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch movies',
        'message' => $e->getMessage()
    ]);
}

$conn->close();