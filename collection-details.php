<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/ads.php';
$current_nav_page = 'collections'; // For bottom nav highlighting

// Get collection ID from URL
$collection_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($collection_id <= 0) {
    header('Location: collections.php');
    exit();
}

// First Query: Fetch collection details
$sql_collection = "SELECT * FROM collections WHERE id = ?";
$stmt = $conn->prepare($sql_collection);
$stmt->bind_param("i", $collection_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if collection exists
if ($result->num_rows === 0) {
    header('Location: collections.php');
    exit();
}

$collection = $result->fetch_assoc();
$stmt->close();

// Second Query: Fetch all movies in this collection
$movies_in_collection = [];
$sql_movies = "SELECT id, title, cover_image, release_date, genre, rating FROM movies WHERE collection_id = ? ORDER BY id ASC";
$stmt_movies = $conn->prepare($sql_movies);

if ($stmt_movies) {
    $stmt_movies->bind_param("i", $collection_id);
    $stmt_movies->execute();
    $result_movies = $stmt_movies->get_result();
    
    if ($result_movies && $result_movies->num_rows > 0) {
        while ($row = $result_movies->fetch_assoc()) {
            $movies_in_collection[] = $row;
        }
    }
    $stmt_movies->close();
}

// ==========================================
// SEO LOGIC & CLEAN URL GENERATION
// ==========================================

function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'collection';
}

$clean_slug = createSlug($collection['title']);
$clean_link = "collection/" . $collection['id'] . "/" . $clean_slug;
$clean_base = defined('BASE_URL') ? BASE_URL : 'http://localhost/cinedrive';
$canonical_url = $clean_base . "/" . $clean_link;

// 1. Dynamic Page Title
$page_title = $collection['title'] . " - Complete Movie Collection / Box Set HD - Zinema.lk";

// 2. Rich Meta Description
$meta_description = "";
if (!empty($collection['description'])) {
    $clean_desc = strip_tags($collection['description']);
    if (strlen($clean_desc) > 150) {
        $meta_description = substr($clean_desc, 0, 150) . "...";
    } else {
        $meta_description = $clean_desc;
    }
    $meta_description .= " Watch all movies in this collection in HD.";
} else {
    $meta_description = "Stream the complete " . $collection['title'] . " series in Sinhala subtitles. All movies available in Full HD on Zinema.lk.";
}

// 3. Open Graph & Twitter Cards
$og_title = $collection['title'] . " - Complete Set";
$og_desc = $meta_description;
$og_url = $canonical_url;
$og_image = $collection['cover_image'];

// Ensure absolute image path and fix legacy domains
if (strpos($og_image, 'sinhalamovies.web.lk/uploads/') !== false) {
    $og_image = 'uploads/' . basename($og_image);
}
if (!preg_match('/^http/', $og_image)) {
    $local_check_path = __DIR__ . '/' . ltrim($og_image, '/');
    if (file_exists($local_check_path)) {
        $og_image = $clean_base . '/' . ltrim($og_image, '/');
    } else {
         // If file missing, maybe use a default or keep relative (browser might 404 but better than crashing)
         // For now, let's just make it absolute if it exists, otherwise keep it
         $og_image = $clean_base . '/' . ltrim($og_image, '/'); 
    }
}

// 4. JSON-LD Schema (ItemList)
$itemListElement = [];
foreach ($movies_in_collection as $index => $movie) {
    $movie_slug = createSlug($movie['title']);
    $movie_url = $clean_base . "/movie/" . $movie['id'] . "/" . $movie_slug;
    
    $itemListElement[] = [
        "@type" => "ListItem",
        "position" => $index + 1,
        "url" => $movie_url,
        "name" => $movie['title']
    ];
}

$schema_data = [
    "@context" => "https://schema.org",
    "@type" => "CollectionPage",
    "name" => $collection['title'],
    "description" => $meta_description,
    "image" => $og_image,
    "mainEntity" => [
        "@type" => "ItemList",
        "itemListElement" => $itemListElement
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    
    <!-- Dynamic SEO Tags -->
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url); ?>">

    <!-- Open Graph Tags -->
    <meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($og_desc); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>">
    <meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($og_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">

    <!-- JSON-LD Schema -->
    <script type="application/ld+json">
        <?php echo json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/responsive.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/search_popup_styles.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/css/desktop-scroll-fix.css">
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

        /* Mobile Layout Only */
        @media (max-width: 767px) {
            .collection-page {
                max-width: 500px;
                margin: 0 auto;
                background: #0a0a0a;
                min-height: 100vh;
                padding-bottom: 80px;
            }

            .back-btn {
                position: absolute;
                top: 16px;
                left: 16px;
                z-index: 10;
                background: rgba(0, 0, 0, 0.6);
                border: none;
                color: white;
                width: 36px;
                height: 36px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                backdrop-filter: blur(10px);
                transition: all 0.3s ease;
                text-decoration: none;
                font-size: 1.1rem;
            }

            .movies-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
                padding: 0 20px;
                margin-top: 18px;
            }
        }

        /* Desktop/Tablet Override */
        @media (min-width: 768px) {
             .collection-page {
                min-height: 100vh;
                background: #0a0a0a;
                width: 100%;
            }
        }

        /* Hero Section with Cover */
        .hero-section {
            position: relative;
            width: 100%;
            height: 280px;
            overflow: hidden;
        }

        .hero-bg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.4);
        }

        .hero-gradient {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(to top, #0a0a0a 0%, transparent 100%);
        }

        /* Back Button */
        .back-btn {
            position: absolute;
            top: 16px;
            left: 16px;
            z-index: 10;
            background: rgba(0, 0, 0, 0.6);
            border: none;
            color: white;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.1rem;
        }

        .back-btn:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: scale(1.1);
        }

        /* Collection Info */
        .collection-info {
            padding: 0 20px;
            margin-top: -60px;
            position: relative;
            z-index: 5;
        }

        .collection-header {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
        }

        .collection-icon {
            font-size: 2.2rem;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.5));
        }

        .collection-title {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
        }

        /* Meta Row */
        .meta-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-size: 0.9rem;
            color: #ccc;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .meta-divider {
            color: #555;
        }

        /* Genre Pills */
        .genre-pills {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .genre-pill {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 5px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #e0e0e0;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Description */
        .description {
            color: #b0b0b0;
            line-height: 1.6;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }

        /* Section Title */
        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #fff;
        }

        /* Movies Grid - Zinema Style */
        .movies-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 0 20px;
            margin-top: 18px;
        }

        /* Movie Card - Title Overlay Style */
        .movie-card {
            background: #1a1a1a;
            border-radius: 8px;
            overflow: hidden;
            transition: transform .3s ease, box-shadow .3s ease;
            text-decoration: none;
            color: inherit;
            position: relative;
        }

        .movie-card:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.6);
        }

        .movie-card:active {
            transform: scale(0.98);
        }

        .movie-poster-wrapper {
            position: relative;
            aspect-ratio: 2/3;
            overflow: hidden;
        }

        .movie-poster-wrapper::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.3) 50%, transparent 100%);
            z-index: 1;
        }

        .movie-poster {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .play-overlay {
            display: none;
        }

        /* Movie Info - Overlay on Poster */
        .movie-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 10px;
            z-index: 2;
        }

        .movie-title {
            font-weight: 700;
            font-size: 0.85rem;
            margin-bottom: 3px;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            color: #fff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
        }

        .movie-meta {
            font-size: 0.7rem;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .movie-rating {
            display: flex;
            align-items: center;
            gap: 2px;
            color: #ffa500;
            font-weight: 600;
        }

        .movie-rating i {
            font-size: 0.65rem;
        }

        .meta-divider {
            color: rgba(255, 255, 255, 0.4);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3.5rem;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: #999;
        }

        .empty-state p {
            font-size: 0.9rem;
            color: #666;
        }

        /* Responsive */
        @media (max-width: 380px) {
            .movies-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '_top_nav.php'; ?>
    <div class="collection-page">
        <!-- Hero Section -->
        <div class="hero-section">
            <!-- Back Button -->
            <a href="<?php echo BASE_URL; ?>/collections.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
            </a>

            <?php 
                $cover_img = $collection['cover_image'];
                if (strpos($cover_img, 'sinhalamovies.web.lk/uploads/') !== false) {
                    $cover_img = 'uploads/' . basename($cover_img);
                }
                if (!preg_match('/^http/', $cover_img)) {
                    $local_path = __DIR__ . '/' . ltrim($cover_img, '/');
                    if(file_exists($local_path)) {
                        $cover_img = BASE_URL . '/' . ltrim($cover_img, '/');
                    } else {
                         // Keep relative if we can't verify, or fallback? 
                         // For hero bg, let's just try to be robust
                         $cover_img = BASE_URL . '/' . ltrim($cover_img, '/');
                    }
                }
            ?>

            <?php if (!empty($collection['cover_image'])): ?>
                <img src="<?php echo htmlspecialchars($cover_img); ?>" 
                     alt="<?php echo htmlspecialchars($collection['title']); ?>" 
                     class="hero-bg"
                     loading="lazy"
                     decoding="async">
            <?php else: ?>
                <div class="hero-bg" style="background: linear-gradient(135deg, #1a1a2e, #0a0a0a);"></div>
            <?php endif; ?>
            
            <div class="hero-gradient"></div>
        </div>

        <!-- Collection Info -->
        <div class="collection-info">
            <!-- Desktop Split Layout Wrapper -->
            <div class="collection-hero-content">
                <!-- Desktop Poster (Hidden on Mobile) -->
                <div class="desktop-poster">
                    <?php if (!empty($collection['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($cover_img); ?>" 
                             alt="<?php echo htmlspecialchars($collection['title']); ?>">
                    <?php else: ?>
                        <div class="desktop-poster-placeholder" style="background: linear-gradient(135deg, #1a1a2e, #0a0a0a); width: 100%; height: 100%;"></div>
                    <?php endif; ?>
                </div>

                <div class="collection-details-column">
                    <div class="collection-header">
                        <span class="collection-icon">📚</span>
                        <h1 class="collection-title"><?php echo htmlspecialchars($collection['title']); ?></h1>
                    </div>

            <!-- Meta Info -->
            <div class="meta-row">
                <span class="meta-item">
                    <i class="fas fa-film"></i>
                    <?php echo count($movies_in_collection); ?> Movies
                </span>
                
                <?php if (!empty($collection['created_at'])): ?>
                    <span class="meta-divider">•</span>
                    <span class="meta-item">
                        <i class="far fa-calendar"></i>
                        <?php echo date('M Y', strtotime($collection['created_at'])); ?>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Genre Pills -->
            <?php if (!empty($collection['genre'])): ?>
                <div class="genre-pills">
                    <?php 
                    $genres = explode(',', $collection['genre']);
                    foreach ($genres as $genre): 
                    ?>
                        <span class="genre-pill"><?php echo trim(htmlspecialchars($genre)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Description -->
            <?php if (!empty($collection['description'])): ?>
                <p class="description"><?php echo nl2br(htmlspecialchars($collection['description'])); ?></p>
            <?php endif; ?>
                </div> <!-- End collection-details-column -->
            </div> <!-- End collection-hero-content -->

            <!-- Section Title -->
            <h2 class="section-title">
                <i class="fas fa-video"></i>
                Movies in Collection
            </h2>
        </div>

        <!-- Ad Banner -->
        <?php renderRectangleAd(); ?>

        <!-- Movies Grid -->
        <?php if (!empty($movies_in_collection)): ?>
            <div class="movies-grid">
                <?php foreach ($movies_in_collection as $movie): 
                    $m_slug = createSlug($movie['title']);
                    // Note: Ensure consistent clean URL generation
                    $clean_movie_link = BASE_URL . "/movie/" . $movie['id'] . "/" . $m_slug;
                    
                    // Fix poster path
                    $m_poster = $movie['cover_image'];
                    if (strpos($m_poster, 'sinhalamovies.web.lk/uploads/') !== false) {
                        $m_poster = 'uploads/' . basename($m_poster);
                    }
                    if (!preg_match('/^http/', $m_poster)) {
                        $local_p = __DIR__ . '/' . ltrim($m_poster, '/');
                        if (file_exists($local_p)) {
                            $m_poster = BASE_URL . '/' . ltrim($m_poster, '/');
                        } else {
                            // Fallback logic could go here, but for now strict absolute path
                             $m_poster = BASE_URL . '/' . ltrim($m_poster, '/');
                        }
                    }
                ?>
                    <a href="<?php echo $clean_movie_link; ?>" class="movie-card">
                        <div class="movie-poster-wrapper">
                            <?php if (!empty($movie['cover_image'])): ?>
                                <img src="<?php echo htmlspecialchars($m_poster); ?>" 
                                     alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                     class="movie-poster"
                                     loading="lazy"
                                     decoding="async">
                            <?php else: ?>
                                <div class="movie-poster" style="background: linear-gradient(135deg, #1a1a2e, #0a0a0a);"></div>
                            <?php endif; ?>
                            
                            <div class="movie-info">
                                <div class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></div>
                                <div class="movie-meta">
                                    <?php if (!empty($movie['rating'])): ?>
                                        <span class="movie-rating">
                                            <i class="fas fa-star"></i>
                                            <?php echo number_format($movie['rating'], 1); ?>
                                        </span>
                                        <span class="meta-divider">•</span>
                                    <?php endif; ?>
                                    <span><?php echo htmlspecialchars($movie['release_date'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-film"></i>
                <h3>No Movies Found</h3>
                <p>This collection is currently empty</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bottom Navigation Bar -->
    <?php include '_bottom_nav.php'; ?>
</body>
</html>
