<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

try {
    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Filtering
    $genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
    $language = isset($_GET['language']) ? trim($_GET['language']) : '';
    
    // Build query with optional filters
    $where = [];
    $params = [];
    $types = '';
    
    if (!empty($genre)) {
        $where[] = "genre LIKE ?";
        $params[] = "%$genre%";
        $types .= 's';
    }
    if (!empty($language)) {
        $where[] = "language_type = ?";
        $params[] = $language;
        $types .= 's';
    }
    
    $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) as total FROM movies $where_clause";
    if (!empty($params)) {
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bind_param($types, ...$params);
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'];
    } else {
        $total = $conn->query($count_sql)->fetch_assoc()['total'];
    }
    
    $sql = "SELECT id, title, release_date, genre, rating, cover_image, video_url, collection_id, language_type, views FROM movies $where_clause ORDER BY created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $movies = [];
    $secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
    $base = 'https://sinhalamovies.web.lk';

    while ($row = $result->fetch_assoc()) {
        $row['genre'] = array_map('trim', explode(',', $row['genre']));
        
        // Generate signed streaming URL
        $video_id = (int)$row['video_url']; // video_url now holds the video ID from videos table
        if ($video_id > 0) {
            $exp = time() + 14400; // 4 hours
            $sig = hash_hmac('sha256', $video_id . '.' . $exp, $secret);
            $row['video_url'] = "{$base}/tg/stream.php?id={$video_id}&exp={$exp}&sig={$sig}";
        } else {
            $row['video_url'] = null;
        }
        
        $movies[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'page' => $page,
        'limit' => $limit,
        'total' => (int)$total,
        'has_more' => ($page * $limit) < $total,
        'movies' => $movies
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch movies',
        'message' => $e->getMessage()
    ]);
}

$conn->close();