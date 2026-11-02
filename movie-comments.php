<?php
// Get current movie ID from query params
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get current user data from session
session_start();
$currentUser = [
    'id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0,
    'username' => isset($_SESSION['username']) ? $_SESSION['username'] : 'Guest',
    'avatar_url' => isset($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/assets/images/default-avatar.png',
    'is_admin' => isset($_SESSION['is_admin']) ? $_SESSION['is_admin'] : false
];

// Convert to JSON for JavaScript
$userJson = json_encode($currentUser);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movie Comments</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/comments.css">
</head>
<body>
    <!-- Movie details section -->
    <div class="movie-details">
        <!-- Movie content here -->
    </div>

    <!-- Comments section -->
    <div id="comments-container"></div>

    <script src="/js/comments.js"></script>
    <script>
        // Initialize comments system when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Use server-provided user data
            const currentUser = <?php echo $userJson; ?>;

            // Initialize comments system
            const comments = new CommentsSystem({
                container: document.getElementById('comments-container'),
                movieId: <?php echo $movie_id; ?>,
                currentUser: currentUser,
                apiEndpoint: '/api/comments.php',
                maxLength: 500,
                onError: function(error) {
                    console.error('Comments error:', error);
                    // Show user-friendly error message
                    alert('There was an error loading comments. Please try again later.');
                }
            });
        });
    </script>
</body>
</html>