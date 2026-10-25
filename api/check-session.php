<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../admin/config.php';

$response = [
    'logged_in' => false,
    'username' => null,
    'user_id' => null,
    'is_subscribed' => false
];

if (isset($_SESSION['user_id']) && isset($_SESSION['username'])) {
    $response['logged_in'] = true;
    $response['username'] = $_SESSION['username'];
    $response['user_id'] = $_SESSION['user_id'];
    
    // Check subscription status
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT subscription_expiry_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $expiry_date_str = $user['subscription_expiry_date'];
        
        if ($expiry_date_str !== null) {
            $expiry_timestamp = strtotime($expiry_date_str);
            if ($expiry_timestamp > time()) {
                $response['is_subscribed'] = true;
            }
        }
    }
}

echo json_encode($response);
$conn->close();
?>