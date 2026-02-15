<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to subscribe.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$msisdn = $input['msisdn'] ?? '';

if (empty($msisdn)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required.']);
    exit;
}

// Validate phone number format (must start with tel:94)
if (!preg_match('/^tel:947\d{8}$/', $msisdn)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format. Use: tel:94XXXXXXXXX']);
    exit;
}

// ============================================
// IDEAMART API CONFIGURATION
// ============================================
// Replace these with your actual IdeaMart credentials
$APP_ID = "YOUR_APP_ID";  // e.g., "APP_000000"
$APP_SECRET = "YOUR_APP_SECRET";  // Your app secret key
$IDEAMART_API_URL = "https://api.dialog.lk/subscription/v1/subscribe";

// Prepare the subscription request
$subscription_data = [
    'applicationId' => $APP_ID,
    'subscriberId' => $msisdn,
    'password' => $APP_SECRET,
    'version' => '1.0'
];

// Initialize cURL
$ch = curl_init($IDEAMART_API_URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscription_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Parse IdeaMart response
$ideamart_response = json_decode($response, true);

// Log the subscription attempt
file_put_contents(__DIR__ . '/subscription_log.txt', 
    date('Y-m-d H:i:s') . " - User ID: " . $_SESSION['user_id'] . 
    " - MSISDN: " . $msisdn . 
    " - Response: " . $response . "\n", 
    FILE_APPEND
);

// Check if subscription was successful
if ($http_code === 200 || $http_code === 201) {
    // Update user's MSISDN and set subscription expiry (30 days from now)
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("UPDATE users SET msisdn = ?, subscription_expiry_date = DATE_ADD(CURDATE(), INTERVAL 30 DAY) WHERE id = ?");
    $stmt->bind_param("si", $msisdn, $user_id);
    $stmt->execute();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Subscription successful! You now have access to all content for 30 days.'
    ]);
} else {
    // Subscription failed
    $error_message = $ideamart_response['message'] ?? 'Subscription request failed. Please try again.';
    echo json_encode([
        'success' => false, 
        'message' => $error_message
    ]);
}

$conn->close();
?>