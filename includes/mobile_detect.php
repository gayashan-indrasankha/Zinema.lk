<?php
/**
 * Mobile App Detection Helper
 * Detects if request is from the Zinema.lk Capacitor app vs web browser
 */

/**
 * Check if request is from the mobile app
 * The Capacitor app sends a custom header: X-Zinema-App: 1
 * 
 * @return bool
 */
function isMobileApp() {
    // Check custom header set by Capacitor app
    if (isset($_SERVER['HTTP_X_ZINEMA_APP']) && $_SERVER['HTTP_X_ZINEMA_APP'] === '1') {
        return true;
    }
    
    // Check query parameter fallback
    if (isset($_GET['app']) && $_GET['app'] === '1') {
        return true;
    }
    
    // Check cookie (set by app on first load)
    if (isset($_COOKIE['zinema_app']) && $_COOKIE['zinema_app'] === '1') {
        return true;
    }
    
    return false;
}

/**
 * Check if user has active IdeaMart subscription
 * 
 * @param mysqli $conn Database connection
 * @param int $user_id User ID
 * @return bool
 */
function isSubscribed($conn, $user_id) {
    if (!$user_id) return false;
    
    $stmt = $conn->prepare("SELECT subscription_expiry_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if ($row['subscription_expiry_date'] && $row['subscription_expiry_date'] >= date('Y-m-d')) {
            return true;
        }
    }
    
    $stmt->close();
    return false;
}

/**
 * Decide which ad system to use
 * 
 * @param mysqli $conn Database connection
 * @param int|null $user_id User ID (null if not logged in)
 * @return string 'none' | 'startio' | 'adsterra'
 */
function getAdSystem($conn, $user_id = null) {
    // Subscribers get no ads
    if ($user_id && isSubscribed($conn, $user_id)) {
        return 'none';
    }
    
    // Mobile app uses Start.io + Unity Ads
    if (isMobileApp()) {
        return 'startio';
    }
    
    // Web browser uses Adsterra
    return 'adsterra';
}
?>
