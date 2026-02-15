<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

try {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    $secret = 'SinhalaMoviesCDN_SecretKey_9s8d7f6$%ASD123!@#';
    $base = 'https://sinhalamovies.web.lk';

    if ($id > 0) {
        // Fetch a single series with its episodes
        $stmt = $conn->prepare("SELECT id, title, release_date, genre, description, cover_image FROM series WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $series = $stmt->get_result()->fetch_assoc();

        if ($series) {
            $series['genre'] = array_map('trim', explode(',', $series['genre']));
            
            $ep_stmt = $conn->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY season_number ASC, episode_number ASC");
            $ep_stmt->bind_param("i", $id);
            $ep_stmt->execute();
            $episodes_result = $ep_stmt->get_result();

            $seasons = [];
            while ($ep = $episodes_result->fetch_assoc()) {
                $season_num = $ep['season_number'];
                if (!isset($seasons[$season_num])) {
                    $seasons[$season_num] = [
                        'season' => $season_num,
                        'episodes' => []
                    ];
                }
                
                // Generate signed streaming URL
                $video_id = (int)$ep['video_url']; // video_url holds the video ID
                $streaming_url = null;
                if ($video_id > 0) {
                    $exp = time() + 14400; // 4 hours
                    $sig = hash_hmac('sha256', $video_id . '.' . $exp, $secret);
                    $streaming_url = "{$base}/tg/stream.php?id={$video_id}&exp={$exp}&sig={$sig}";
                }
                
                $seasons[$season_num]['episodes'][] = [
                    'num' => 'S' . str_pad($season_num, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($ep['episode_number'], 2, '0', STR_PAD_LEFT),
                    'title' => $ep['title'],
                    'driveId' => $streaming_url,
                    'thumb' => $ep['thumb_image'],
                    'is_locked' => false
                ];
            }
            $series['seasons'] = array_values($seasons);
        }
        
        echo json_encode($series);

    } else {
        // Fetch all series with pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(50, max(1, intval($_GET['limit']))) : 20;
        $offset = ($page - 1) * $limit;
        
        $total = $conn->query("SELECT COUNT(*) as total FROM series")->fetch_assoc()['total'];
        
        $all_series = [];
        $sql = "SELECT id, title, release_date, genre, description, cover_image, language_type FROM series ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $row['genre'] = array_map('trim', explode(',', $row['genre']));
            $all_series[] = $row;
        }
        
        echo json_encode([
            'status' => 'success',
            'page' => $page,
            'limit' => $limit,
            'total' => (int)$total,
            'has_more' => ($page * $limit) < $total,
            'series' => $all_series
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch series',
        'message' => $e->getMessage()
    ]);
}

$conn->close();