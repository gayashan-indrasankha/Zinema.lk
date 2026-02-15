<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

// Get shot_id from GET request
$shot_id = isset($_GET['shot_id']) ? intval($_GET['shot_id']) : 0;

// Validate shot_id
if ($shot_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid shot ID']);
    exit;
}

// Query to get comments with usernames
$stmt = $conn->prepare("
    SELECT c.*, u.username 
    FROM shot_comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.shot_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $shot_id);
$stmt->execute();
$result = $stmt->get_result();

$comments = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
}

$stmt->close();
$conn->close();

echo json_encode([
    'status' => 'success',
    'comments' => $comments
]);
