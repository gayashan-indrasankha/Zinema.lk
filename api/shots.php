<?php
/**
 * Shots Feed API for Mobile App
 * Serves TikTok-style video shots as JSON
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/jwt-helper.php';
require_once __DIR__ . '/../includes/mobile_detect.php';

try {
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
    $offset = ($page - 1) * $limit;
    
    // Check if user is authenticated (JWT for mobile)
    $current_user_id = null;
    $user_data = authenticate_mobile_request();
    if ($user_data) {
        $current_user_id = $user_data['user_id'];
    } elseif (isset($_SESSION['user_id'])) {
        $current_user_id = $_SESSION['user_id'];
    }
    
    $shots_feed = [];
    
    if ($current_user_id) {
        // Logged in: fetch unwatched shots first
        $sql = "SELECT 
            s.id, 
            s.title, 
            s.description,
            s.shot_video_file,
            s.fb_share_url,
            s.linked_content_type,
            s.linked_content_id,
            COUNT(DISTINCT sl.id) as like_count,
            COUNT(DISTINCT sc.id) as comment_count,
            MAX(CASE WHEN user_sl.user_id IS NOT NULL THEN 1 ELSE 0 END) as user_liked,
            MAX(CASE WHEN user_sf.user_id IS NOT NULL THEN 1 ELSE 0 END) as user_favorited
        FROM shots s
        LEFT JOIN shot_likes sl ON s.id = sl.shot_id
        LEFT JOIN shot_comments sc ON s.id = sc.shot_id
        LEFT JOIN user_views uv ON s.id = uv.shot_id AND uv.user_id = ?
        LEFT JOIN shot_likes user_sl ON s.id = user_sl.shot_id AND user_sl.user_id = ?
        LEFT JOIN user_favorites user_sf ON s.id = user_sf.shot_id AND user_sf.user_id = ?
        WHERE uv.id IS NULL
        GROUP BY s.id
        ORDER BY RAND()
        LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiii", $current_user_id, $current_user_id, $current_user_id, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // If user has watched all, fallback to all shots
        if ($result->num_rows === 0 && $page === 1) {
            $sql = "SELECT 
                s.id, s.title, s.description, s.shot_video_file, s.fb_share_url,
                s.linked_content_type, s.linked_content_id,
                COUNT(DISTINCT sl.id) as like_count,
                COUNT(DISTINCT sc.id) as comment_count,
                MAX(CASE WHEN user_sl.user_id IS NOT NULL THEN 1 ELSE 0 END) as user_liked,
                MAX(CASE WHEN user_sf.user_id IS NOT NULL THEN 1 ELSE 0 END) as user_favorited
            FROM shots s
            LEFT JOIN shot_likes sl ON s.id = sl.shot_id
            LEFT JOIN shot_comments sc ON s.id = sc.shot_id
            LEFT JOIN shot_likes user_sl ON s.id = user_sl.shot_id AND user_sl.user_id = ?
            LEFT JOIN user_favorites user_sf ON s.id = user_sf.shot_id AND user_sf.user_id = ?
            GROUP BY s.id
            ORDER BY RAND()
            LIMIT ? OFFSET ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiii", $current_user_id, $current_user_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
        }
    } else {
        // Not logged in: simple query
        $sql = "SELECT 
            s.id, s.title, s.description, s.shot_video_file, s.fb_share_url,
            s.linked_content_type, s.linked_content_id,
            COUNT(DISTINCT sl.id) as like_count,
            COUNT(DISTINCT sc.id) as comment_count,
            0 as user_liked,
            0 as user_favorited
        FROM shots s
        LEFT JOIN shot_likes sl ON s.id = sl.shot_id
        LEFT JOIN shot_comments sc ON s.id = sc.shot_id
        GROUP BY s.id
        ORDER BY RAND()
        LIMIT ? OFFSET ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    
    while ($row = $result->fetch_assoc()) {
        $row['user_liked'] = (bool)$row['user_liked'];
        $row['user_favorited'] = (bool)$row['user_favorited'];
        $row['like_count'] = (int)$row['like_count'];
        $row['comment_count'] = (int)$row['comment_count'];
        $shots_feed[] = $row;
    }
    
    echo json_encode([
        'status' => 'success',
        'page' => $page,
        'limit' => $limit,
        'count' => count($shots_feed),
        'shots' => $shots_feed
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
