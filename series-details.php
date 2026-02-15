<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/ads.php';
$current_nav_page = 'tv-series'; // For bottom nav highlighting

// Get series ID from URL
$series_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Redirect if no ID provided
if ($series_id <= 0) {
    header('Location: tv-series.php');
    exit();
}

// First Query: Fetch series details
$sql_series = "SELECT * FROM series WHERE id = ?";
$stmt = $conn->prepare($sql_series);
$stmt->bind_param("i", $series_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if series exists
if ($result->num_rows === 0) {
    header('Location: tv-series.php');
    exit();
}

$series = $result->fetch_assoc();
$stmt->close();

// Second Query: Fetch all episodes for this series (ordered by season and episode number)
$episodes = [];
$sql_episodes = "SELECT * FROM episodes WHERE series_id = ? ORDER BY season_number ASC, episode_number ASC";
$stmt_episodes = $conn->prepare($sql_episodes);
$stmt_episodes->bind_param("i", $series_id);
$stmt_episodes->execute();
$result_episodes = $stmt_episodes->get_result();

if ($result_episodes && $result_episodes->num_rows > 0) {
    while ($row = $result_episodes->fetch_assoc()) {
        $episodes[] = $row;
    }
}
$stmt_episodes->close();

// Group episodes by season
$seasons = [];
foreach ($episodes as $episode) {
    $season_num = $episode['season_number'];
    if (!isset($seasons[$season_num])) {
        $seasons[$season_num] = [];
    }
    $seasons[$season_num][] = $episode;
}
ksort($seasons); // Sort seasons by number

// Get list of season numbers for selector
$season_numbers = array_keys($seasons);

// Get first episode for initial state
$first_episode = !empty($episodes) ? $episodes[0] : null;

// ==========================================
// SEO LOGIC & CLEAN URL GENERATION
// ==========================================

function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'series';
}

$clean_slug = createSlug($series['title']);
$clean_link = "series/" . $series['id'] . "/" . $clean_slug;
$clean_base = defined('BASE_URL') ? BASE_URL : 'http://localhost/cinedrive';
$canonical_url = $clean_base . "/" . $clean_link;

// 1. Dynamic Page Title
$release_year = 'N/A';
if (!empty($series['release_date'])) {
    preg_match('/\d{4}/', $series['release_date'], $matches);
    $release_year = $matches[0] ?? 'N/A';
}
$page_title = "Watch " . $series['title'] . " - All Seasons (" . $release_year . ") Online Full HD - Zinema.lk";

// 2. Rich Meta Description
$meta_description = "";
if (!empty($series['description'])) {
    $clean_desc = strip_tags($series['description']);
    if (strlen($clean_desc) > 150) {
        $meta_description = substr($clean_desc, 0, 150) . "...";
    } else {
        $meta_description = $clean_desc;
    }
    $meta_description .= " Stream all episodes in HD.";
} else {
    $meta_description = "Watch " . $series['title'] . " online in Full HD with Sinhala subtitles. Stream all seasons and episodes of this popular TV show on Zinema.lk.";
}

// 3. Open Graph & Twitter Cards
$og_title = $series['title'];
$og_desc = $meta_description;
$og_url = $canonical_url;
$og_image = $series['cover_image'];

// Ensure absolute image path
if (!preg_match('/^http/', $og_image)) {
    $og_image = $clean_base . '/' . ltrim($og_image, '/');
}

// 4. JSON-LD Schema
$schema_data = [
    "@context" => "https://schema.org",
    "@type" => "TVSeries",
    "name" => $series['title'],
    "image" => $og_image,
    "description" => $meta_description,
    "datePublished" => $series['release_date'] ?? '',
    "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => "8.5", // Using the page's default or $series['rating'] if valid
        "bestRating" => "10",
        "ratingCount" => "150"
    ],
    "numberOfSeasons" => count($seasons),
    "genre" => explode(',', $series['genre'] ?? 'TV Show')
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
    <meta property="og:type" content="video.tv_show">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($og_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($og_desc); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars($og_image); ?>">

    <!-- JSON-LD Schema -->
    <script type="application/ld+json">
        <?php echo json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>

    <!-- CSS Assets (Absolute Paths) -->
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
            .movie-details-page {
                max-width: 500px;
                margin: 0 auto;
                background: #0a0a0a;
                min-height: 100vh;
                overflow-y: auto;
                padding-bottom: 80px;
            }

            .back-nav {
                position: absolute;
                top: 20px;
                left: 20px;
                z-index: 100;
            }
        }
        
        /* Desktop/Tablet Override */
        @media (min-width: 768px) {
             .movie-details-page {
                min-height: 100vh;
                background: #0a0a0a;
            }
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
            margin-bottom: 20px;
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

        /* Tabs Section */
        .tabs-section {
            margin-top: 10px;
        }

        .tabs-header {
            display: flex;
            gap: 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 20px;
        }

        .tab-btn {
            flex: 1;
            padding: 12px 20px;
            background: transparent;
            border: none;
            color: #888;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .tab-btn.active {
            color: #fff;
        }

        .tab-btn.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, rgba(42,108,255,1), rgba(255,51,102,0.85));
            border-radius: 2px 2px 0 0;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* About Tab */
        .about-content {
            color: #b3b3b3;
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Episodes List */
        .episodes-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .episode-item {
            display: flex;
            gap: 15px;
            background: rgba(20, 22, 30, 0.4);
            border-radius: 12px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .episode-item:hover {
            background: rgba(35, 37, 48, 0.9);
            transform: scale(1.02);
            box-shadow: 0 8px 24px rgba(42, 108, 255, 0.2), 0 4px 12px rgba(0, 0, 0, 0.4);
            border-color: rgba(42, 108, 255, 0.4);
        }

        .episode-thumbnail {
            width: 160px;
            min-width: 160px;
            aspect-ratio: 16/9;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
            background-color: #1a1a2e;
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .episode-thumbnail::after {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            font-size: 24px;
            opacity: 0;
            transition: opacity 0.3s ease;
            text-shadow: 0 2px 8px rgba(0,0,0,0.8);
        }

        .episode-item:hover .episode-thumbnail::after {
            opacity: 1;
        }

        .episode-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            padding-top: 0;
            padding-bottom: 0;
        }

        .episode-badge {
            font-size: 0.85rem;
            color: #9bb3e0;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .episode-title {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            line-height: 1.4;
            margin: 0;
        }

        .episode-meta {
            font-size: 0.85rem;
            color: #888;
            display: flex;
            align-items: center;
            gap: 5px;
            flex-wrap: wrap;
            margin-top: 2px;
        }

        .episode-meta span {
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .no-episodes {
            text-align: center;
            padding: 40px 20px;
            color: #888;
        }

        /* Season Dropdown Styled as Tab */
        /* Season Dropdown Styled as Tab */
        .season-dropdown-container {
            position: relative;
            margin-bottom: 0;
            margin-top: 10px;
            display: flex;
            justify-content: center;
            width: 100%;
            z-index: 100;
        }

        .season-dropdown-header {
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 12px 10px;
            margin-bottom: 5px;
            width: 100%;
            cursor: pointer;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        #season-current-text {
            margin-right: 10px;
        }

        .season-dropdown-options {
            display: none;
            position: absolute;
            top: 100%;
            left: 50%;
            transform: translateX(-50%);
            width: 250px;
            background: #1a1a2e;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
        }

        .season-dropdown-options.show {
            display: block;
        }

        .season-option {
            padding: 12px 20px;
            cursor: pointer;
            text-align: center;
            color: #ccc;
            transition: all 0.2s;
        }

        .season-option:hover {
            background: rgba(42, 108, 255, 0.1);
            color: #fff;
        }

        .season-option.active {
            color: #4da6ff;
            background: rgba(42, 108, 255, 0.15);
        }

        .season-dropdown-icon {
            font-size: 0.8rem;
            transition: transform 0.3s;
        }
        
        .season-dropdown-options.show ~ .season-dropdown-header .season-dropdown-icon {
            transform: rotate(180deg);
        }

        /* Active underline for season dropdown */
        .season-dropdown-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(135deg, rgba(42,108,255,1), rgba(255,51,102,0.85));
            border-radius: 2px 2px 0 0;
        }

        .no-episodes i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.3;
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

            .episode-thumbnail {
                width: 120px;
                min-width: 120px;
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
                    <a href="<?php echo BASE_URL; ?>/tv-series.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>

                <!-- Poster Section -->
                <div class="poster-section">
                    <?php 
                        $poster_url = $series['cover_image'];
                        if (!preg_match('/^http/', $poster_url)) {
                            $poster_url = BASE_URL . '/' . ltrim($poster_url, '/');
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($poster_url); ?>" 
                         alt="<?php echo htmlspecialchars($series['title']); ?>" 
                         class="poster-img"
                         loading="lazy"
                         decoding="async">
                    <div class="poster-overlay"></div>
                </div>

                <!-- Content Section -->
                <div class="content-section">
                    <!-- Series Title -->
                    <h1 class="movie-title"><?php echo htmlspecialchars($series['title']); ?></h1>

                    <!-- Meta Info -->
                    <div class="meta-info">
                        <span class="rating">
                            <i class="fas fa-star"></i>
                            <span><?php echo number_format(8.5, 1); ?></span>
                        </span>
                        <span class="meta-divider">•</span>
                        <span class="meta-item">
                            <i class="far fa-calendar"></i>
                            <?php 
                            $year = 'N/A';
                            if (!empty($series['release_date'])) {
                                preg_match('/\d{4}/', $series['release_date'], $matches);
                                $year = $matches[0] ?? 'N/A';
                            }
                            echo htmlspecialchars($year);
                            ?>
                        </span>
                        <span class="meta-divider">•</span>
                        <span class="meta-item"><?php echo count($episodes); ?> Episodes</span>
                    </div>

                    <!-- Genre Tags -->
                    <?php if (!empty($series['genre'])): ?>
                        <div class="genre-tags">
                            <?php 
                            $genres = explode(',', $series['genre']);
                            foreach ($genres as $genre): 
                            ?>
                                <span class="genre-tag"><?php echo trim(htmlspecialchars($genre)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Video Player Container (Hidden Initially) -->
                    <div id="video-container" style="display: none;">
                        <!-- Video player will be inserted here by JavaScript -->
                    </div>

                    <!-- Download Button (Hidden Initially) -->
                    <div class="action-buttons" id="action-buttons" style="display: none;">
                        <a href="#" onclick="openSmartLink(this.href); return false;" class="btn btn-whatsapp" id="download-btn">
                            <i class="fab fa-whatsapp"></i>
                            <span>WhatsApp Packages මගින් නරඹන්න.</span>
                        </a>
                    </div>

                    <!-- Season Selector (always show if episodes exist) -->
                    <?php if (!empty($episodes)): ?>
                        <div class="season-dropdown-container">
                            <div class="season-dropdown-header" onclick="toggleSeasonDropdown()">
                                <span id="season-current-text">Season <?php echo $season_numbers[0]; ?></span>
                                <i class="fas fa-chevron-down season-dropdown-icon"></i>
                            </div>
                            <div class="season-dropdown-options" id="season-dropdown-options">
                                <?php foreach ($season_numbers as $season_num): ?>
                                    <div class="season-option" onclick="selectSeason('<?php echo $season_num; ?>')">
                                        Season <?php echo $season_num; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Ad Banner -->
                    <?php renderRectangleAd(); ?>

                    <!-- Tabs Section -->
                    <div class="tabs-section">
                        <div class="tabs-header">
                            <button class="tab-btn" onclick="switchTab('about')">About</button>
                            <button class="tab-btn active" onclick="switchTab('episodes')">Episodes</button>
                        </div>

                        <!-- About Tab -->
                        <div class="tab-content" id="about-tab">
                            <div class="about-content">
                                <?php if (!empty($series['description'])): ?>
                                    <?php echo nl2br(htmlspecialchars($series['description'])); ?>
                                <?php else: ?>
                                    <p>No description available for this series.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Episodes Tab -->
                        <div class="tab-content active" id="episodes-tab">
                            <?php if (!empty($episodes)): ?>
                                <!-- Episodes List -->
                                <div class="episodes-list" id="episodes-list">
                                    <?php foreach ($episodes as $episode): ?>
                                        <div class="episode-item" 
                                             data-season="<?php echo $episode['season_number']; ?>" 
                                             data-video-url="<?php echo htmlspecialchars($episode['video_url'] ?? ''); ?>"
                                             onclick="playEpisode(<?php echo $episode['id']; ?>, '<?php echo addslashes($episode['video_url'] ?? ''); ?>')">
                                            <?php 
                                                $ep_thumb = !empty($episode['thumb_image']) ? $episode['thumb_image'] : $series['cover_image'];
                                                
                                                // Fix legacy/broken hardcoded domain paths
                                                if (strpos($ep_thumb, 'sinhalamovies.web.lk/uploads/') !== false) {
                                                    $ep_thumb = 'uploads/' . basename($ep_thumb);
                                                }

                                                // If local path, check file existence and make absolute
                                                if (!preg_match('/^http/', $ep_thumb)) {
                                                    $local_check_path = __DIR__ . '/' . ltrim($ep_thumb, '/');
                                                    
                                                    // If file exists locally, use it
                                                    if (file_exists($local_check_path)) {
                                                        $ep_thumb = BASE_URL . '/' . ltrim($ep_thumb, '/');
                                                    } else {
                                                        // File missing? Fallback to Series Cover
                                                        $ep_thumb = $series['cover_image'];
                                                        if (!preg_match('/^http/', $ep_thumb)) {
                                                            $ep_thumb = BASE_URL . '/' . ltrim($ep_thumb, '/');
                                                        }
                                                    }
                                                }
                                            ?>
                                            <div class="episode-thumbnail" style="background-image: url('<?php echo htmlspecialchars($ep_thumb); ?>')"></div>
                                            <div class="episode-info">
                                                <div class="episode-badge">
                                                    EPS <?php echo str_pad($episode['episode_number'], 2, '0', STR_PAD_LEFT); ?>
                                                </div>
                                                <h3 class="episode-title"><?php echo htmlspecialchars($episode['title']); ?></h3>
                                                <div class="episode-meta">
                                                    <?php if (!empty($series['genre'])): ?>
                                                        <span><?php echo htmlspecialchars(explode(',', $series['genre'])[0]); ?></span>
                                                        <span>•</span>
                                                    <?php endif; ?>
                                                    <?php 
                                                        $display_date = !empty($episode['air_date']) ? $episode['air_date'] : $episode['created_at'];
                                                    ?>
                                                    <span><?php echo date('d M Y', strtotime($display_date)); ?></span>
                                                    <span>•</span>
                                                    <span><?php echo !empty($episode['duration']) ? htmlspecialchars($episode['duration']) . 'm' : 'N/A'; ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-episodes">
                                    <i class="fas fa-film"></i>
                                    <p>No episodes available yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>


    
    <script>
        const seriesId = <?php echo $series_id; ?>;
        let currentEpisodeId = null;

        // Tab switching function
        function switchTab(tabName) {
            // Remove active class from all tabs
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });

            // Add active class to selected tab
            event.target.classList.add('active');
            document.getElementById(tabName + '-tab').classList.add('active');
        }

        // Season filtering function
        function filterBySeason(seasonNumber) {
            const allEpisodes = document.querySelectorAll('.episode-item');
            
            allEpisodes.forEach(episode => {
                const episodeSeason = episode.getAttribute('data-season');
                
                if (episodeSeason === seasonNumber) {
                    episode.style.display = 'flex';
                } else {
                    episode.style.display = 'none';
                }
            });
        }

        // Toggle dropdown
        function toggleSeasonDropdown() {
            const dropdown = document.getElementById('season-dropdown-options');
            dropdown.classList.toggle('show');
            const icon = document.querySelector('.season-dropdown-icon');
            if(icon) {
                 icon.style.transform = dropdown.classList.contains('show') ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        }

        // Select season
        function selectSeason(seasonNum) {
            // Update Text
            const textSpan = document.getElementById('season-current-text');
            if(textSpan) textSpan.textContent = 'Season ' + seasonNum;
            
            // Filter
            filterBySeason(seasonNum);
            
            // Close
            const dropdown = document.getElementById('season-dropdown-options');
             if(dropdown) {
                dropdown.classList.remove('show');
                const icon = document.querySelector('.season-dropdown-icon');
                if(icon) icon.style.transform = 'rotate(0deg)';
             }
            
            // Update Active Class
            document.querySelectorAll('.season-option').forEach(opt => {
                opt.classList.remove('active');
                if(opt.textContent.trim() === 'Season ' + seasonNum) {
                    opt.classList.add('active');
                }
            });
        }

        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.closest('.season-dropdown-container')) {
                const dropdown = document.getElementById('season-dropdown-options');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                     const icon = document.querySelector('.season-dropdown-icon');
                     if(icon) icon.style.transform = 'rotate(0deg)';
                }
            }
        }

        // Initialize first season on page load (if multiple seasons exist)
        document.addEventListener('DOMContentLoaded', function() {
            const firstOption = document.querySelector('.season-option');
            if (firstOption) {
                const text = firstOption.textContent.trim();
                const num = text.replace('Season ', '');
                filterBySeason(num); 
                firstOption.classList.add('active');
                
                // Ensure initial text matches
                const textSpan = document.getElementById('season-current-text');
                if(textSpan) textSpan.textContent = text;
            }
        });

        // Play episode function
        async function playEpisode(episodeId, videoUrl) {
            // Open Smart Link when playing episode (ad monetization)
            if (SMART_LINK_URL && SMART_LINK_URL.length > 0) {
                window.open(SMART_LINK_URL, '_blank');
            }
            
            const container = document.getElementById('video-container');
            const actionButtons = document.getElementById('action-buttons');
            const downloadBtn = document.getElementById('download-btn');
            
            if (!container) return;

            // Show container and download button
            container.style.display = 'block';
            actionButtons.style.display = 'flex';

            // If same episode, do nothing
            if (currentEpisodeId === episodeId && container.querySelector('video')) {
                return;
            }

            currentEpisodeId = episodeId;
            
            // Update download button to download current episode
            downloadBtn.href = `<?php echo BASE_URL; ?>/download.php?id=${episodeId}&type=episode`;

            // Show loader
            container.innerHTML = `
                <div class="video-loader">
                    <div class="video-loader-spinner"></div>
                    <p class="video-loader-text">Loading episode...</p>
                </div>
            `;

            try {
                console.log('📡 Using video URL for episode ID:', episodeId);
                console.log('🎬 Video URL:', videoUrl);
                
                // Add small delay for smooth UX
                await new Promise(resolve => setTimeout(resolve, 300));
                
                // Replace loader with video player
                container.innerHTML = `
                    <div class="video-wrapper" style="position: relative; width: 100%; background: #000; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);">
                        <video 
                            id="seriesPlayer"
                            controls 
                            autoplay 
                            playsinline
                            poster="<?php 
                                $v_poster = $series['cover_image'];
                                if (!preg_match('/^http/', $v_poster)) {
                                    $v_poster = BASE_URL . '/' . ltrim($v_poster, '/');
                                }
                                echo htmlspecialchars($v_poster);
                            ?>"
                            style="width: 100%; display: block;">
                            <source src="${videoUrl}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                `;
                
                const video = document.getElementById('seriesPlayer');
                
                if (video) {
                    // Save progress tracking
                    video.addEventListener('timeupdate', () => {
                        if (video.duration > 0) {
                            localStorage.setItem(`episode_${episodeId}_progress`, video.currentTime);
                        }
                    });

                    // Resume Playback (automatic, no popup)
                    const savedTime = localStorage.getItem(`episode_${episodeId}_progress`);
                    if (savedTime && parseFloat(savedTime) > 10) {
                        video.currentTime = parseFloat(savedTime);
                    }
                }
                
                console.log('🎬 Episode player loaded successfully');
                
                // Scroll to video
                container.scrollIntoView({ behavior: 'smooth', block: 'start' });
                
            } catch (error) {
                console.error('❌ Error loading episode:', error);
                alert('Failed to load episode: ' + error.message);
            }
        }

        // Format time helper
        function formatTime(seconds) {
            const h = Math.floor(seconds / 3600);
            const m = Math.floor((seconds % 3600) / 60);
            const s = Math.floor(seconds % 60);
            return h > 0 ? `${h}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}` : `${m}:${s.toString().padStart(2,'0')}`;
        }

        // Generic Smart Link - Opens ad then URL
        const SMART_LINK_URL = '<?php echo getSmartLinkUrl(); ?>';
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
