<?php
require_once __DIR__ . '/admin/config.php';
require_once __DIR__ . '/includes/TrailerManager.php';

// Start session for user authentication
session_start();

// Get current user data
$current_user = isset($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'avatar_url' => $_SESSION['avatar_url'] ?? null,
    'is_admin' => $_SESSION['is_admin'] ?? false
] : null;

// Initialize trailer manager
$trailerManager = new TrailerManager($pdo);

// Get initial trailers for first load
$initial_trailers = $trailerManager->getAllTrailers(
    $current_user['id'] ?? null,
    10, // Limit
    0   // Offset
);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    
    <!-- Prevent screen dimming -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="mobile-web-app-capable" content="yes">
    
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/trailer-feed.css">
</head>
<body class="trailer-view">
    <!-- Main feed container -->
    <div id="trailer-feed" class="trailer-feed"></div>
    
    <script src="js/trailer-feed.js"></script>
    <script defer src="js/nav.js"></script>
    <script>
        // Initialize the trailer feed when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize with server-side data
            const trailerFeed = new TrailerFeed({
                container: document.getElementById('trailer-feed'),
                userId: <?php echo $current_user ? $current_user['id'] : 'null'; ?>,
                apiEndpoint: '/api/trailers.php',
                initialTrailers: <?php echo json_encode($initial_trailers); ?>
            });
            
            // Prevent screen from sleeping
            wakeLock.acquire();
            
            // Release wake lock when page is hidden
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    wakeLock.release();
                } else {
                    wakeLock.acquire();
                }
            });
            
            // Handle orientation change
            const warningEl = document.querySelector('.landscape-warning');
            if (warningEl) {
                window.addEventListener('orientationchange', () => {
                    if (window.orientation === 90 || window.orientation === -90) {
                        warningEl.style.display = 'flex';
                    } else {
                        warningEl.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>