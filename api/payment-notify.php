<?php
// This script is called by IDEAMART, not the user.
// It does not use sessions.
require_once __DIR__ . '/../admin/config.php';

// 1. Get the raw JSON notification from Ideamart
$input = file_get_contents('php://input');

// 2. Decode the JSON
$notification = json_decode($input, true);

// 3. For safety, log the request to a file to debug
file_put_contents('ideamart_log.txt', $input . "\n", FILE_APPEND);

if (isset($notification['subscriberId']) && isset($notification['status'])) {
    
    $msisdn = $notification['subscriberId']; // e.g., "tel:94771234567"
    $status = $notification['status'];       // e.g., "REGISTERED" or "UNREGISTERED"
    $app_id = $notification['applicationId'];
    
    // --- ⚠️ Add a check for your App ID for security ---
    // if ($app_id !== "APP_000000") { exit; }

    try {
        if ($status === 'REGISTERED') {
            // User has successfully subscribed or renewed!
            // Set their expiry date to 30 days from now.
            $stmt = $conn->prepare("UPDATE users SET subscription_expiry_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE msisdn = ?");
            $stmt->bind_param("s", $msisdn);
            $stmt->execute();
        
        } elseif ($status === 'UNREGISTERED') {
            // User has cancelled their subscription.
            // Set their expiry date to null (or to yesterday to be safe).
            $stmt = $conn->prepare("UPDATE users SET subscription_expiry_date = DATE_SUB(CURDATE(), INTERVAL 1 DAY) WHERE msisdn = ?");
            $stmt->bind_param("s", $msisdn);
            $stmt->execute();
        }
        
    } catch (Exception $e) {
        // Log database errors
        file_put_contents('ideamart_log.txt', 'DB Error: ' . $e->getMessage() . "\n", FILE_APPEND);
    }
}

// Send a 200 OK response to Ideamart so it knows we received it.
http_response_code(200);
echo json_encode(['status' => 'success']);

$conn->close();
?>