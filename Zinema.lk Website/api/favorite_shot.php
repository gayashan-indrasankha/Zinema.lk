<?php
header('Content-Type: application/json');
require_once '../admin/config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Check if user is logged in
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to favorite.']);
    exit;
}

// Get POST data
$shot_id = isset($_POST['shot_id']) ? intval($_POST['shot_id']) : 0;

// Validate shot_id
if ($shot_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid shot ID']);
    exit;
}

// Check if the user has already favorited this shot
$check_stmt = $conn->prepare("SELECT id FROM user_favorites WHERE user_id = ? AND shot_id = ?");
$check_stmt->bind_param("ii", $user_id, $shot_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Already favorited - UNFAVORITE (delete the row)
    $delete_stmt = $conn->prepare("DELETE FROM user_favorites WHERE user_id = ? AND shot_id = ?");
    $delete_stmt->bind_param("ii", $user_id, $shot_id);
    
    if ($delete_stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'action' => 'unfavorited'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to unfavorite'
        ]);
    }
    
    $delete_stmt->close();
} else {
    // Not favorited yet - FAVORITE (insert new row)
    $insert_stmt = $conn->prepare("INSERT INTO user_favorites (user_id, shot_id) VALUES (?, ?)");
    $insert_stmt->bind_param("ii", $user_id, $shot_id);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'status' => 'success',
            'action' => 'favorited'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to favorite'
        ]);
    }
    
    $insert_stmt->close();
}

$check_stmt->close();
$conn->close();
?>
