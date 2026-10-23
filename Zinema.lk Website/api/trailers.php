<?php
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/TrailerManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();
$userId = $_SESSION['user_id'] ?? null;

$tm = new TrailerManager($pdo);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // If id provided, return single trailer
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        $page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
        $limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;
        $offset = ($page - 1) * $limit;

        if ($id) {
            $trailer = $tm->getTrailer($id, $userId);
            if (!$trailer) {
                http_response_code(404);
                echo json_encode(['error' => 'Trailer not found']);
                exit;
            }
            echo json_encode(['trailer' => $trailer]);
            exit;
        }

        // List trailers
        $trailers = $tm->getAllTrailers($userId, $limit, $offset);
        echo json_encode(['trailers' => $trailers]);
        exit;
    }

    // POST actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?: [];
        // Accept action in query or body
        $action = $_GET['action'] ?? $body['action'] ?? null;

        if (!$action) {
            http_response_code(400);
            echo json_encode(['error' => 'Action required']);
            exit;
        }

        switch ($action) {
            case 'like':
                if (!$userId) { http_response_code(401); echo json_encode(['error'=>'Authentication required']); exit; }
                $trailerId = filter_var($body['trailer_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$trailerId) { http_response_code(400); echo json_encode(['error'=>'Invalid trailer_id']); exit; }
                $result = $tm->toggleLike($trailerId, $userId);
                echo json_encode(['liked' => $result['liked'], 'count' => $result['count']]);
                exit;

            case 'favorite':
                if (!$userId) { http_response_code(401); echo json_encode(['error'=>'Authentication required']); exit; }
                $trailerId = filter_var($body['trailer_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$trailerId) { http_response_code(400); echo json_encode(['error'=>'Invalid trailer_id']); exit; }
                $result = $tm->toggleFavorite($trailerId, $userId);
                echo json_encode(['favorited' => $result['favorited'], 'count' => $result['count']]);
                exit;

            case 'view':
                // Views can be anonymous
                $trailerId = filter_var($body['trailer_id'] ?? null, FILTER_VALIDATE_INT);
                if (!$trailerId) { http_response_code(400); echo json_encode(['error'=>'Invalid trailer_id']); exit; }
                $ip = $_SERVER['REMOTE_ADDR'] ?? null;
                $tm->incrementViewCount($trailerId, $userId, $ip);
                echo json_encode(['ok' => true]);
                exit;

            case 'share':
                if (!$userId) { http_response_code(401); echo json_encode(['error'=>'Authentication required']); exit; }
                $trailerId = filter_var($body['trailer_id'] ?? null, FILTER_VALIDATE_INT);
                $platform = filter_var($body['platform'] ?? 'unknown', FILTER_SANITIZE_STRING);
                if (!$trailerId) { http_response_code(400); echo json_encode(['error'=>'Invalid trailer_id']); exit; }
                $tm->recordShare($trailerId, $userId, $platform);
                echo json_encode(['ok' => true]);
                exit;

            default:
                http_response_code(400);
                echo json_encode(['error' => 'Unknown action']);
                exit;
        }
    }

    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
