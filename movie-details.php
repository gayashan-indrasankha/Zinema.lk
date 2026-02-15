<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/ads.php';
$current_nav_page = 'movies'; // For bottom nav highlighting

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($movie_id <= 0) {
    header('Location: movies.php');
    exit();
}

// Fetch movie details from database
$sql = "SELECT * FROM movies WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $movie_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if movie exists
if ($result->num_rows === 0) {
    header('Location: movies.php');
    exit();
}

$movie = $result->fetch_assoc();
$stmt->close();

// Increment view count
if ($movie_id > 0) {
    $update_sql = "UPDATE movies SET views = views + 1 WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("i", $movie_id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Update the movie array with new view count for display
    $movie['views'] = ($movie['views'] ?? 0) + 1;
}

// ==========================================
// DYNAMIC SEO TAGS GENERATION
// ==========================================

// 1. Dynamic Page Title
$release_year = '';
if (!empty($movie['release_date'])) {
    preg_match('/\d{4}/', $movie['release_date'], $matches);
    $release_year = isset($matches[0]) ? " (" . $matches[0] . ")" : "";
}
$page_title = "Watch " . htmlspecialchars($movie['title']) . $release_year . " Online Full HD - Zinema.lk";

// 2. Rich Meta Description
$meta_description = "";
if (!empty($movie['description'])) {
    // Truncate to 150 characters
    $clean_desc = strip_tags($movie['description']);
    if (strlen($clean_desc) > 150) {
        $meta_description = substr($clean_desc, 0, 150) . "...";
    } else {
        $meta_description = $clean_desc;
    }
    $meta_description .= " Watch " . htmlspecialchars($movie['title']) . " in HD on Zinema.lk.";
} else {
    // Fallback
    $meta_description = "Watch " . htmlspecialchars($movie['title']) . " online in full HD. Stream the best movies and TV series on Zinema.lk.";
}

// Helper to create slug
function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'movie';
}

// 3. Open Graph & Twitter Variables
$og_title = $page_title;
$og_description = $meta_description;
$og_image = $movie['cover_image']; 

// SEO Friendly URL (Canonical)
// Format: https://domain.com/movie/123/movie-title-slug
$clean_slug = createSlug($movie['title']);
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
// Ensure base_url ends with slash if needed, but here we append path
// Check if running in subdir (e.g. localhost/cinedrive)
$script_dir = dirname($_SERVER['SCRIPT_NAME']);
$clean_base = rtrim($base_url . $script_dir, '/\\');

// The new clean URL
$og_url = $clean_base . "/movie/" . $movie['id'] . "/" . $clean_slug;
$og_type = "video.movie";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    
    <!-- Dynamic Title -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Dynamic Meta Description -->
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    
    <!-- Canonical URL (Important for avoiding duplicate content) -->
    <link rel="canonical" href="<?php echo htmlspecialchars($og_url); ?>">
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>">
    <meta property="og:type" content="<?php echo htmlspecialchars($og_type); ?>">
    <meta property="og:site_name" content="Zinema.lk">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($og_description); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">
    
    <!-- JSON-LD Schema.org Markup -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Movie",
      "name": "<?php echo addslashes($movie['title']); ?>",
      "image": "<?php echo addslashes($movie['cover_image']); ?>",
      "description": "<?php echo addslashes(strip_tags($movie['description'])); ?>",
      "datePublished": "<?php echo $movie['release_date']; ?>",
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "<?php echo $movie['rating'] ?? 0; ?>",
        "bestRating": "10",
        "ratingCount": "<?php echo $movie['views'] ?? 1; ?>"
      },
      "genre": [
        <?php 
            $genres = explode(',', $movie['genre']);
            $quoted_genres = array_map(function($g) { return '"' . trim(addslashes($g)) . '"'; }, $genres);
            echo implode(',', $quoted_genres);
        ?>
      ]
    }
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/search_popup_styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/desktop-tablet.css">
    

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #0a0a0a;
            color: #fff;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            overflow-x: hidden;
        }

        /* Mobile-only max-width constraint */
        @media (max-width: 767px) {
            .movie-details-page {
                max-width: 500px;
                margin: 0 auto;
                overflow-y: auto;
            }
        }

        .movie-details-page {
            background: #0a0a0a;
            min-height: 100vh;
            padding-bottom: 80px;
        }

        /* Desktop/Tablet - no overflow restriction */
        @media (min-width: 768px) {
            .movie-details-page {
                overflow-y: visible;
            }
        }

        /* Back Button - Mobile only */
        @media (max-width: 767px) {
            .back-nav {
                position: absolute;
                top: 20px;
                left: 20px;
                z-index: 100;
            }

            .back-btn {
                background: rgba(0, 0, 0, 0.5);
                border: none;
                color: white;
                width: 40px;
                height: 40px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                backdrop-filter: blur(10px);
                transition: all 0.3s ease;
                text-decoration: none;
            }

            .back-btn:hover {
                background: rgba(0, 0, 0, 0.8);
                transform: scale(1.05);
            }
        }

        /* Poster Section */
        .poster-section {
            position: relative;
            width: 100%;
            aspect-ratio: 2/3;
            max-height: 70vh;
            overflow: hidden;
        }

        .poster-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .poster-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 150px;
            background: linear-gradient(to top, #0a0a0a, transparent);
        }

        /* Content Section */
        .content-section {
            padding: 20px;
        }

        .movie-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 0 15px 0;
            line-height: 1.2;
        }

        /* Meta Info */
        .meta-info {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .rating {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #ffa500;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .rating i {
            font-size: 1rem;
        }

        .meta-divider {
            color: #666;
        }

        .meta-item {
            color: #ccc;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Genre Tags */
        .genre-tags {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .genre-tag {
            background: #1a1a2e;
            color: #9bb3e0;
            padding: 6px 14px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 500;
            border: 1px solid #2a2a3e;
        }

        /* Description */
        .description {
            color: #b3b3b3;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 30px;
        }

        .btn {
            width: 100%;
            padding: 15px;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-watch {
            background: linear-gradient(135deg, rgba(42,108,255,0.9), rgba(255,51,102,0.7));
            color: white;
        }

        .btn-watch:hover {
            background: linear-gradient(135deg, rgba(42,108,255,1), rgba(255,51,102,0.85));
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(42,108,255,0.4), 0 4px 10px rgba(255,51,102,0.2);
        }

        .btn-whatsapp {
            background: transparent;
            color: white;
            border: 2px solid rgba(37, 211, 102, 0.6);
            white-space: nowrap;
        }

        .btn-whatsapp:hover {
            background: rgba(37, 211, 102, 0.15);
            border-color: rgba(37, 211, 102, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .btn-whatsapp i {
            color: #25D366;
        }

        .btn i {
            font-size: 1.1rem;
        }

        /* Video Container */
        #video-container {
            width: 100%;
        }

        #video-container video {
            width: 100%;
            border-radius: 8px;
            background: #000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
        }

        .video-loader {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background: rgba(20, 22, 30, 0.8);
            border-radius: 8px;
        }

        .video-loader-spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid rgba(42, 108, 255, 1);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .video-loader-text {
            color: #ccc;
            font-size: 0.95rem;
        }

        /* App Container */
        .app-container {
            display: grid;
            grid-template-rows: 1fr auto;
            min-height: 100vh;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .movie-title {
                font-size: 1.5rem;
            }
            
            .content-section {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include '_top_nav.php'; ?>
    <div class="app-container">
        <div class="main-content">
            <div class="movie-details-page">
                <!-- Back Button -->
                <div class="back-nav">
                    <a href="<?php echo BASE_URL; ?>/movies.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>

                <!-- Poster Section -->
                <div class="poster-section">
                    <?php 
                        $poster_url = $movie['cover_image'];
                        if (!preg_match('/^http/', $poster_url)) {
                            $poster_url = BASE_URL . '/' . ltrim($poster_url, '/');
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                         class="poster-img"
                         loading="lazy"
                         decoding="async"
                         onerror="this.onerror=null; this.src='https://placehold.co/400x600/1a1a2e/ffffff?text=No+Poster';">
                    <div class="poster-overlay"></div>
                </div>

                <!-- Content Section -->
                <div class="content-section">
                    <!-- Movie Title -->
                    <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>

                    <!-- Meta Info -->
                    <div class="meta-info">
                        <span class="rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format($movie['rating'] ?? 0, 1); ?></span>
                        </span>
                        <span class="meta-divider">•</span>
                        <span class="meta-item">
                            <i class="far fa-calendar"></i>
                            <?php 
                            // Extract year from release_date (e.g., "Nov. 16, 2001" -> 2001)
                            $year = 'N/A';
                            if (!empty($movie['release_date'])) {
                                preg_match('/\d{4}/', $movie['release_date'], $matches);
                                $year = $matches[0] ?? 'N/A';
                            }
                            echo htmlspecialchars($year);
                            ?>
                        </span>
                        <span class="meta-divider">•</span>
                        <span class="meta-item"><?php echo number_format($movie['views'] ?? 0); ?> views</span>
                    </div>

                    <!-- Genre Tags -->
                    <?php if (!empty($movie['genre'])): ?>
                        <div class="genre-tags">
                            <?php 
                            $genres = explode(',', $movie['genre']);
                            foreach ($genres as $genre): 
                            ?>
                                <span class="genre-tag"><?php echo trim(htmlspecialchars($genre)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if (!empty($movie['description'])): ?>
                        <p class="description">
                            <?php echo nl2br(htmlspecialchars($movie['description'])); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Ad Banner -->
                    <?php renderRectangleAd(); ?>

                    <!-- Action Buttons -->
                    <div class="action-buttons">
                        <?php if (!empty($movie['video_url'])): ?>
                            <div id="video-container">
                                <button class="btn btn-watch" id="watchNowBtn" onclick="openSmartLinkThenPlay(<?php echo $movie['id']; ?>, '<?php echo addslashes($movie['video_url']); ?>')">
                                    <i class="fas fa-play"></i>
                                    <span>Watch Now</span>
                                </button>
                            </div>
                        <?php endif; ?>
                        <a href="#" onclick="openSmartLink('<?php echo BASE_URL; ?>/download.php?id=<?php echo $movie['id']; ?>'); return false;" class="btn btn-whatsapp">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp Packages මගින් නරඹන්න.</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>






    
    <!-- Video.js Player Implementation -->
    <script>
        // ========================================
        // DIRECT VIDEO STREAMING IMPLEMENTATION
        // ========================================
        
        // Global variables
        let videoPlayer = null;
        let modalOpen = false;
        let currentVideoUrl = null;
        
        // ========================================
        // INLINE VIDEO PLAYER FUNCTION
        // ========================================
        
        async function playMovie(movieId, dbVideoUrl) {
            const container = document.getElementById('video-container');
            if (!container) return;

            // Show loader
            container.innerHTML = `
                <div class="video-loader">
                    <div class="video-loader-spinner"></div>
                    <p class="video-loader-text">Loading video...</p>
                </div>
            `;

            try {
                console.log('📡 Playing movie ID:', movieId);
                console.log('🎬 Video URL:', dbVideoUrl);
                
                // Add small delay for smooth UX
                await new Promise(resolve => setTimeout(resolve, 300));
                
                // Replace loader with video player with custom controls
                container.innerHTML = `
                    <div class="video-wrapper" style="position: relative; width: 100%; background: #000; border-radius: 8px; overflow: hidden;">
                        <video 
                            id="moviePlayer"
                            controls 
                            autoplay 
                            playsinline
                            poster="<?php 
                                $v_poster = $movie['cover_image'];
                                if (!preg_match('/^http/', $v_poster)) {
                                    $v_poster = BASE_URL . '/' . ltrim($v_poster, '/');
                                }
                                echo htmlspecialchars($v_poster); 
                            ?>"
                            style="width: 100%; display: block;">
                            <source src="${dbVideoUrl}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                `;
                
                // Get video element and add functionality
                const video = document.getElementById('moviePlayer');
                
                if (video) {
                    // Save progress tracking
                    video.addEventListener('timeupdate', function() {
                        if (this.duration > 0) {
                            localStorage.setItem(`movie_${movieId}_progress`, this.currentTime);
                        }
                    });
                    
                    // Check for saved progress
                    const savedTime = localStorage.getItem(`movie_${movieId}_progress`);
                    if (savedTime && parseFloat(savedTime) > 10) {
                        video.currentTime = parseFloat(savedTime);
                    }
                }
                
                console.log('🎬 Video player loaded successfully');
                
            } catch (error) {
                console.error('❌ Error loading video:', error);
                
                // Show error and restore button
                alert('Failed to load video: ' + error.message);
                container.innerHTML = `
                    <button class="btn btn-watch" onclick="playMovie(${movieId}, '${dbVideoUrl.replace(/'/g, "\\'")}')">
                        <i class="fas fa-play"></i>
                        <span>Watch Now</span>
                    </button>
                `;
            }
        }

        // Smart Link Ad Function - Opens ad then plays video
        const SMART_LINK_URL = '<?php echo getSmartLinkUrl(); ?>';
        
        function openSmartLinkThenPlay(movieId, dbVideoUrl) {
            // Only open smart link if URL is set (ads enabled)
            if (SMART_LINK_URL && SMART_LINK_URL.length > 0) {
                window.open(SMART_LINK_URL, '_blank');
            }
            // Play video after a short delay
            setTimeout(function() {
                playMovie(movieId, dbVideoUrl);
            }, 100);
        }

        // Generic Smart Link - Opens ad then URL
        function openSmartLink(destinationUrl) {
            // Only open smart link if URL is set (ads enabled)
            if (SMART_LINK_URL && SMART_LINK_URL.length > 0) {
                window.open(SMART_LINK_URL, '_blank');
            }
            setTimeout(function() {
                window.location.href = destinationUrl;
            }, 100);
        }
    </script>

    <!-- Popunder Ad (High Revenue - Once per session) -->
    <?php renderPopunder(); ?>

</body>
</html>
