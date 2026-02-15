<?php
// Helper functions for comments API

function validateUser($pdo) {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        return ['error' => 'Unauthorized access'];
    }
    
    // Get user details
    $stmt = $pdo->prepare("SELECT id, username, is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        http_response_code(401);
        return ['error' => 'Invalid user session'];
    }
    
    return $user;
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function validateCommentData($content) {
    if (empty($content)) {
        return ['error' => 'Comment content is required'];
    }
    
    if (strlen($content) > 1000) {
        return ['error' => 'Comment content exceeds maximum length of 1000 characters'];
    }
    
    return null;
}

function canDeleteComment($pdo, $commentId, $userId, $isAdmin) {
    $stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$comment) {
        return false;
    }
    
    // Allow if user is admin or comment owner
    return $isAdmin || $comment['user_id'] == $userId;
}

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function validateMovieId($pdo, $movieId) {
    $stmt = $pdo->prepare("SELECT id FROM movies WHERE id = ?");
    $stmt->execute([$movieId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ? true : false;
}

function getCommentReplies($pdo, $commentId) {
    $stmt = $pdo->prepare("
        SELECT c.*, u.username, u.avatar_url 
        FROM comments c 
        LEFT JOIN users u ON c.user_id = u.id 
        WHERE c.parent_id = ? 
        ORDER BY c.created_at ASC
    ");
    $stmt->execute([$commentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}