<?php
// No need for content type headers, as this script doesn't return JSON.
// It should execute as fast as possible.
header("Access-Control-Allow-Origin: *");
require_once __DIR__ . '/../admin/config.php';

// Get the event type from the query parameter
$event_type = $_GET['event'] ?? '';
$value = isset($_GET['value']) ? intval($_GET['value']) : null;

// Only log valid, expected events
$allowed_events = ['page_view', 'watch_time'];

if (in_array($event_type, $allowed_events)) {
    // For 'watch_time', a value is required
    if ($event_type === 'watch_time' && is_null($value)) {
        // Do nothing if watch_time event has no value
        exit;
    }

    try {
        $stmt = $conn->prepare("INSERT INTO analytics (event_type, value) VALUES (?, ?)");
        $stmt->bind_param("si", $event_type, $value);
        $stmt->execute();
        $stmt->close();
    } catch (Exception $e) {
        // Silently fail to not impact user experience.
        // In a production environment, you might log this error to a file.
    }
}

$conn->close();

// Respond immediately with a 204 No Content header, which is best for tracking pixels.
http_response_code(204);

?>