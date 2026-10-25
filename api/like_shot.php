<?php
// Set JSON header
header('Content-Type: application/json');

// Include database connection
require_once '../admin/config.php';

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? 0;

// Check if user is logged in
if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'You must be logged in to like.']);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
    exit();
}

// Get shot_id from POST request
$shot_id = isset($_POST['shot_id']) ? intval($_POST['shot_id']) : 0;

// Validate shot_id
if ($shot_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid shot ID']);
    exit();
}

try {
    // Check if user has already liked this shot
    $check_sql = "SELECT id FROM shot_likes WHERE user_id = ? AND shot_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $user_id, $shot_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // User has already liked - UNLIKE (DELETE)
        $delete_sql = "DELETE FROM shot_likes WHERE user_id = ? AND shot_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("ii", $user_id, $shot_id);
        
        if ($delete_stmt->execute()) {
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as like_count FROM shot_likes WHERE shot_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $shot_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            
            echo json_encode([
                'status' => 'success',
                'action' => 'unliked',
                'like_count' => $count_row['like_count']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to unlike']);
        }
        
        $delete_stmt->close();
    } else {
        // User has not liked - LIKE (INSERT)
        $insert_sql = "INSERT INTO shot_likes (user_id, shot_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ii", $user_id, $shot_id);
        
        if ($insert_stmt->execute()) {
            // Get updated like count
            $count_sql = "SELECT COUNT(*) as like_count FROM shot_likes WHERE shot_id = ?";
            $count_stmt = $conn->prepare($count_sql);
            $count_stmt->bind_param("i", $shot_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            
            echo json_encode([
                'status' => 'success',
                'action' => 'liked',
                'like_count' => $count_row['like_count']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to like']);
        }
        
        $insert_stmt->close();
    }
    
    $check_stmt->close();
    
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}

// Close database connection
$conn->close();
?>
