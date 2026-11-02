<?php
require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/includes/comment_helpers.php';

// Set CORS headers if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGetComments($pdo);
            break;
            
        case 'POST':
            handlePostComment($pdo);
            break;
            
        case 'DELETE':
            handleDeleteComment($pdo);
            break;
            
        default:
            http_response_code(405);
            sendJsonResponse(['error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    http_response_code(500);
    sendJsonResponse(['error' => 'Internal server error']);
}

function handleGetComments($pdo) {
    // Validate movie_id parameter
    $movieId = filter_input(INPUT_GET, 'movie_id', FILTER_VALIDATE_INT);
    
    if (!$movieId) {
        http_response_code(400);
        sendJsonResponse(['error' => 'Invalid movie ID']);
    }
    
    // Verify movie exists
    if (!validateMovieId($pdo, $movieId)) {
        http_response_code(404);
        sendJsonResponse(['error' => 'Movie not found']);
    }
    
    // Get top-level comments
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.avatar_url,
            (SELECT COUNT(*) FROM comments r WHERE r.parent_id = c.id) as reply_count
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.movie_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$movieId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get replies for each comment
    foreach ($comments as &$comment) {
        $comment['replies'] = getCommentReplies($pdo, $comment['id']);
    }
    
    sendJsonResponse(['comments' => $comments]);
}

function handlePostComment($pdo) {
    // Validate user authentication
    $user = validateUser($pdo);
    if (isset($user['error'])) {
        sendJsonResponse($user);
    }
    
    // Get and validate POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        sendJsonResponse(['error' => 'Invalid request data']);
    }
    
    $movieId = filter_var($data['movie_id'] ?? null, FILTER_VALIDATE_INT);
    $content = sanitizeInput($data['content'] ?? '');
    $parentId = filter_var($data['parent_id'] ?? null, FILTER_VALIDATE_INT);
    
    // Validate comment data
    $validationError = validateCommentData($content);
    if ($validationError) {
        http_response_code(400);
        sendJsonResponse($validationError);
    }
    
    // Verify movie exists
    if (!validateMovieId($pdo, $movieId)) {
        http_response_code(404);
        sendJsonResponse(['error' => 'Movie not found']);
    }
    
    // If this is a reply, verify parent comment exists
    if ($parentId) {
        $stmt = $pdo->prepare("SELECT id FROM comments WHERE id = ? AND movie_id = ?");
        $stmt->execute([$parentId, $movieId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            sendJsonResponse(['error' => 'Parent comment not found']);
        }
    }
    
    // Insert comment
    $stmt = $pdo->prepare("
        INSERT INTO comments (user_id, movie_id, parent_id, content, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt->execute([$user['id'], $movieId, $parentId, $content])) {
        http_response_code(500);
        sendJsonResponse(['error' => 'Failed to add comment']);
    }
    
    // Get the inserted comment with user details
    $commentId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        SELECT 
            c.*,
            u.username,
            u.avatar_url
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    sendJsonResponse([
        'message' => 'Comment added successfully',
        'comment' => $comment
    ]);
}

function handleDeleteComment($pdo) {
    // Validate user authentication
    $user = validateUser($pdo);
    if (isset($user['error'])) {
        sendJsonResponse($user);
    }
    
    // Get and validate comment ID
    $commentId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if (!$commentId) {
        http_response_code(400);
        sendJsonResponse(['error' => 'Invalid comment ID']);
    }
    
    // Check if user has permission to delete
    if (!canDeleteComment($pdo, $commentId, $user['id'], $user['is_admin'])) {
        http_response_code(403);
        sendJsonResponse(['error' => 'Permission denied']);
    }
    
    // Begin transaction to delete comment and its replies
    $pdo->beginTransaction();
    
    try {
        // Delete replies first
        $stmt = $pdo->prepare("DELETE FROM comments WHERE parent_id = ?");
        $stmt->execute([$commentId]);
        
        // Delete the main comment
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->execute([$commentId]);
        
        $pdo->commit();
        
        sendJsonResponse(['message' => 'Comment deleted successfully']);
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log($e->getMessage());
        http_response_code(500);
        sendJsonResponse(['error' => 'Failed to delete comment']);
    }
}