<?php
/**
 * Subscription Status Check API
 * Mobile app calls this to decide whether to show ads
 * 
 * Returns subscription status for the authenticated user
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';
require_once __DIR__ . '/../includes/jwt-helper.php';
require_once __DIR__ . '/../includes/mobile_detect.php';

// Authenticate user (JWT or session)
$user_id = null;
$user_data = authenticate_mobile_request();
if ($user_data) {
    $user_id = $user_data['user_id'];
} elseif (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

if (!$user_id) {
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required.',
        'is_subscribed' => false
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT username, email, msisdn, subscription_expiry_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($user = $result->fetch_assoc()) {
        $is_subscribed = false;
        $days_remaining = 0;
        
        if ($user['subscription_expiry_date']) {
            $expiry = new DateTime($user['subscription_expiry_date']);
            $now = new DateTime();
            
            if ($expiry > $now) {
                $is_subscribed = true;
                $days_remaining = $now->diff($expiry)->days;
            }
        }
        
        echo json_encode([
            'success' => true,
            'is_subscribed' => $is_subscribed,
            'subscription_expiry' => $user['subscription_expiry_date'],
            'days_remaining' => $days_remaining,
            'has_phone' => !empty($user['msisdn']),
            'show_ads' => !$is_subscribed
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found.',
            'is_subscribed' => false
        ]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error.',
        'is_subscribed' => false
    ]);
}

$conn->close();
?>
