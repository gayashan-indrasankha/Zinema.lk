<?php
/**
 * App Update Checker API
 * Mobile app calls this on launch to check for updates
 * 
 * Returns: {version, download_url, required, changelog}
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
error_reporting(0);
ini_set('display_errors', 0);

// ========================================
// UPDATE THIS WHEN YOU RELEASE A NEW APK
// ========================================
$CURRENT_VERSION = '1.0.0';
$APK_DOWNLOAD_URL = 'https://zinema.lk/app/zinema-latest.apk';
$FORCE_UPDATE = false; // Set to true for critical updates
$CHANGELOG = 'Initial release of Zinema.lk mobile app!';

// Get the version from the app
$app_version = isset($_GET['v']) ? trim($_GET['v']) : '';

// Compare versions
$needs_update = false;
if (!empty($app_version)) {
    $needs_update = version_compare($app_version, $CURRENT_VERSION, '<');
}

echo json_encode([
    'latest_version' => $CURRENT_VERSION,
    'download_url' => $APK_DOWNLOAD_URL,
    'needs_update' => $needs_update,
    'force_update' => $FORCE_UPDATE && $needs_update,
    'changelog' => $CHANGELOG,
    'apk_size_mb' => 15 // Approximate APK size for user info
]);
?>
