<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Check if user is logged in
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to comment.']);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit;
}

// Get POST data
$shot_id = isset($_POST['shot_id']) ? intval($_POST['shot_id']) : 0;
$comment_text = isset($_POST['comment_text']) ? trim($_POST['comment_text']) : '';

// Validate inputs
if ($shot_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid shot ID']);
    exit;
}

if (empty($comment_text)) {
    echo json_encode(['status' => 'error', 'message' => 'Comment text cannot be empty']);
    exit;
}

// Insert new comment into database
$stmt = $conn->prepare("INSERT INTO shot_comments (shot_id, user_id, comment_text, created_at) VALUES (?, ?, ?, NOW())");
$stmt->bind_param("iis", $shot_id, $user_id, $comment_text);

if ($stmt->execute()) {
    echo json_encode([
        'status' => 'success',
        'message' => 'Comment added successfully'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed to add comment'
    ]);
}

$stmt->close();
$conn->close();
