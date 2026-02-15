<?php
// Quick database migration execution
require_once 'config.php';

$success = true;
$messages = [];

// Step 1: ALTER TABLE
$sql1 = "ALTER TABLE `shots` MODIFY COLUMN `shot_video_file` LONGTEXT NOT NULL";
if ($conn->query($sql1)) {
    $messages[] = "✅ Column type changed to LONGTEXT";
} else {
    $messages[] = "❌ Column error: " . $conn->error;
    $success = false;
}

// Step 2: TRUNCATE TABLE (with foreign key check disabled)
// Disable foreign key checks temporarily
$conn->query("SET FOREIGN_KEY_CHECKS = 0");

$sql2 = "TRUNCATE TABLE `shots`";
if ($conn->query($sql2)) {
    $messages[] = "✅ Shots table cleared";
} else {
    $messages[] = "❌ Truncate error: " . $conn->error;
    $success = false;
}

// Re-enable foreign key checks
$conn->query("SET FOREIGN_KEY_CHECKS = 1");
$messages[] = "✅ Foreign key checks restored";

// Return JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => $success,
    'messages' => $messages
]);
?>
