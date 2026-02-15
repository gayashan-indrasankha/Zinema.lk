<?php
/**
 * Record View API Endpoint
 * Tracks when a user watches a shot (video) for the smart algorithm
 * 
 * Usage: POST request with shot_id parameter
 * Returns: JSON response with status and message
 */

require_once '../admin/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'User not logged in'
    ]);
    exit;
}

// Get shot_id from POST request
$shot_id = isset($_POST['shot_id']) ? intval($_POST['shot_id']) : 0;

// Validate shot_id
if ($shot_id <= 0) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid shot ID'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Insert view record (INSERT IGNORE prevents errors if record already exists)
$sql = "INSERT IGNORE INTO user_views (user_id, shot_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param('ii', $user_id, $shot_id);

if ($stmt->execute()) {
    // Check if the record was actually inserted (affected_rows will be 1)
    // or if it already existed (affected_rows will be 0)
    $was_inserted = $stmt->affected_rows > 0;
    
    echo json_encode([
        'status' => 'success', 
        'message' => $was_inserted ? 'View recorded' : 'View already recorded',
        'inserted' => $was_inserted
    ]);
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Failed to record view: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
