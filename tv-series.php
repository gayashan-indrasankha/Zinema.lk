<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';
require_once 'includes/ads.php';

/**
 * Generate Google OAuth URL
 */
function getGoogleAuthUrl() {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope' => 'email profile',
        'access_type' => 'online',
        'prompt' => 'select_account'
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

// Initialize series array
$series_list = [];

// Build SQL query based on filters
$sql = "SELECT * FROM series WHERE 1=1";
$params = [];
$types = "";

// Search Logic
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND title LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $types .= "s";
}

// Genre Filter
if (isset($_GET['genre']) && !empty($_GET['genre'])) {
    $sql .= " AND genre LIKE ?";
    $params[] = '%' . $_GET['genre'] . '%';
    $types .= "s";
}

// Language Type Filter (Sinhala Dubbed/Subtitled)
if (isset($_GET['filter']) && in_array($_GET['filter'], ['dubbed', 'subtitled'])) {
    $sql .= " AND language_type = ?";
    $params[] = $_GET['filter'];
    $types .= "s";
}

// Sorting
if (isset($_GET['sort']) && $_GET['sort'] == 'trending') {
    $sql .= " ORDER BY views DESC";
} else {
    $sql .= " ORDER BY created_at DESC";
}


// Pagination for Desktop/Tablet (12 items per page = 2 rows)
$items_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;


// Get total count for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_movies = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_movies = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_movies / $items_per_page);

// Always apply pagination LIMIT/OFFSET
$sql .= " LIMIT ? OFFSET ?";
$params[] = $items_per_page;
$params[] = $offset;
$types .= "ii";

// Execute query
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $series_list[] = $row;
    }
}

// Helper function for SEO slugs
function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug ?: 'series';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Watch the best TV series online in Full HD with Sinhala subtitles and dubbing. Stream K-dramas, Hollywood series, anime and more on Zinema.lk - Sri Lanka's #1 streaming site.">
    <meta name="keywords" content="sinhala tv series, korean drama sinhala sub, watch series online, sinhala subtitles, HD series, Zinema.lk">
    <link rel="canonical" href="<?php echo BASE_URL; ?>/tv-series.php">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    <title>Watch TV Series Online - Sinhala Dubbed & Subtitled | Zinema.lk</title>
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Watch TV Series Online - Sinhala Dubbed & Subtitled | Zinema.lk">
    <meta property="og:description" content="Stream the best TV series in Full HD with Sinhala subtitles. K-dramas, Hollywood series, anime and more!">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL; ?>/tv-series.php">
    <meta property="og:site_name" content="Zinema.lk">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css?v=2.0">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/desktop-scroll-fix.css">
    <style>
        /* Page Header */
        .page-header {
            text-align: center;
            padding: 30px 20px 20px;
            margin-bottom: 20px;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            color: var(--text);
            text-transform: uppercase;
            letter-spacing: 1px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .page-subtitle {
            font-size: 1rem;
            color: var(--muted);
            margin-top: 8px;
        }

        /* Search Bar */
        .search-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(20, 22, 30, 0.7);
            padding: 12px 18px;
            border-radius: 14px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin: 20px auto;
            max-width: 600px;
            transition: all 0.3s;
        }

        .search-bar:hover,
        .search-bar:focus-within {
            border-color: rgba(42, 108, 255, 0.5);
            box-shadow: 0 0 10px rgba(42, 108, 255, 0.25);
            background: rgba(25, 28, 38, 0.9);
        }

        .search-bar input {
            flex: 1;
            background: transparent;
            border: none;
            color: var(--text);
            outline: none;
            font-size: 0.95rem;
        }

        .search-bar input::placeholder {
            color: rgba(200, 210, 230, 0.6);
        }

        .search-bar button {
            background: var(--primary);
            color: white;
            padding: 8px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }

        .search-bar button:hover {
            background: #1e56e0;
        }

        /* Filter Buttons */
        .filter-buttons {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
            margin: 20px auto;
            max-width: 800px;
        }

        .filter-btn {
            background: rgba(42, 108, 255, 0.1);
            color: var(--text);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid rgba(42, 108, 255, 0.3);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }

        .filter-btn:hover {
            background: rgba(42, 108, 255, 0.2);
            border-color: rgba(42, 108, 255, 0.5);
        }

        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Netflix-Style Series Grid */
        .series-grid-2col {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            padding: 0 15px;
        }

        @media (min-width: 768px) {
            .series-grid-2col {
                grid-template-columns: repeat(4, 1fr);
                gap: 15px;
            }
        }

        @media (min-width: 1024px) {
            .series-grid-2col {
                grid-template-columns: repeat(5, 1fr);
                gap: 18px;
            }
        }

        @media (min-width: 1400px) {
            .series-grid-2col {
                grid-template-columns: repeat(6, 1fr);
                gap: 20px;
            }
        }

        /* Netflix-Style Series Card */
        .series-grid-2col .movie-card {
            display: flex;
            flex-direction: column;
            background: rgba(20, 22, 30, 1);
            border-radius: 6px;
            overflow: hidden;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            cursor: pointer;
        }

        .series-grid-2col .movie-card:hover {
            transform: scale(1.08) translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            z-index: 10;
        }

        /* Play Icon Overlay - Netflix Style */
        .series-grid-2col .movie-card::before {
            content: '▶';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) scale(0);
            font-size: 40px;
            color: rgba(255, 255, 255, 0.95);
            opacity: 0;
            transition: all 0.3s ease;
            pointer-events: none;
            text-shadow: 0 4px 15px rgba(0, 0, 0, 0.9);
            z-index: 3;
        }

        .series-grid-2col .movie-card:hover::before {
            opacity: 1;
            transform: translate(-50%, -50%) scale(1);
        }

        /* Dark Overlay on Hover */
        .series-grid-2col .movie-card::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.3) 50%, rgba(0, 0, 0, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }

        .series-grid-2col .movie-card:hover::after {
            opacity: 1;
        }

        .series-grid-2col .movie-card img {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
            display: block;
            background: linear-gradient(110deg, #1a1a2e 8%, #252540 18%, #1a1a2e 33%);
            background-size: 200% 100%;
            animation: shimmer 1.5s linear infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .series-grid-2col .movie-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px 10px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.8) 50%, transparent 100%);
            z-index: 3;
        }

        .series-grid-2col .movie-title {
            font-size: 13px;
            font-weight: 700;
            color: #fff;
            margin: 0 0 4px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            line-height: 1.3;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8);
        }

        /* No Series Message */
        .no-movies {
            text-align: center;
            padding: 60px 20px;
            color: var(--muted);
        }

        .no-movies h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-movies p {
            font-size: 1rem;
        }
        
        /* Language Filter Tabs (Episodes-style) */
        .language-filter-tabs {
            display: flex;
            gap: 0;
            margin: 0 20px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            justify-content: space-evenly;
        }
        
        .filter-tab {
            flex: 1;
            text-align: center;
            padding: 14px 20px;
            font-size: 1.05rem;
            font-weight: 700;
            color: rgba(255, 255, 255, 0.6);
            text-decoration: none;
            border-bottom: 2px solid transparent;
            transition: all 0.3s ease;
            position: relative;
            bottom: -1px;
            white-space: nowrap;
        }
        
        .filter-tab:hover {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .filter-tab.active {
            color: #fff;
        }
        
        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, rgba(42,108,255,1), rgba(255,51,102,0.85));
            border-radius: 2px 2px 0 0;
        }

        /* Header Search Bar Styling */
        .header-search-bar {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 100%;
            background-color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 20px;
            z-index: 10;
            border-bottom: none; /* Remove any bottom border */
        }

        .search-wrapper {
            position: relative;
            width: 90%;
            max-width: 500px;
            margin: 0 auto; /* Center the search wrapper */
        }

        .header-search-form {
            position: relative;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-search-form input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            font-size: 16px;
            color: #fff;
            background: rgba(20, 22, 30, 0.7);
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            outline: none;
            transition: all 0.3s ease;
        }

        .header-search-form input:focus {
            border-color: rgba(42, 108, 255, 0.5);
            box-shadow: 0 0 15px rgba(42, 108, 255, 0.3), 0 0 30px rgba(255, 51, 102, 0.15);
        }

        .header-search-form input::placeholder {
            color: #666;
        }

        .clear-btn {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            z-index: 10;
            transition: color 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .clear-btn:hover {
            color: #fff;
        }
        
        .clear-btn:active {
            transform: translateY(-50%) scale(0.9);
        }

        /* Live Search Dropdown */
        .live-search-dropdown {
            position: absolute;
            top: calc(100% + 5px);
            left: 0;
            right: 0;
            background: rgba(20, 22, 30, 0.95);
            max-height: 400px;
            overflow-y: auto;
            border-radius: 10px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.5);
            z-index: 1000;
            border: none; /* Remove border completely */
            display: none; /* Hidden by default */
        }
        
        .live-search-dropdown:not(:empty) {
            display: block; /* Only show when has content */
            border: 1px solid #333;
        }

        .search-result-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 15px;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background 0.2s;
        }

        .search-result-item:hover {
            background: rgba(231, 76, 60, 0.2);
        }

        .search-result-item:last-child {
            border-bottom: none;
        }

        .search-result-item img {
            width: 40px;
            height: 60px;
            object-fit: cover;
            border-radius: 4px;
        }

        .search-result-item .meta {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .search-result-item .title {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
        }

        .search-result-item .year {
            font-size: 12px;
            color: #888;
        }

        .no-results {
            padding: 20px;
            text-align: center;
            color: #888;
            font-size: 14px;
        }
        
        /* Prevent content from being hidden behind bottom nav */
        .app-container {
            padding-bottom: 100px; /* Increased padding for bottom navigation clearance */
        }
        
        /* Add extra bottom padding to series grid for better spacing */
        .series-grid-2col {
            padding-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include '_top_nav.php'; ?>
    
    <!-- Header with In-Header Search -->
    <div class="cs-header">
        <div class="header-content" id="header-content">
            <div class="header-left">
                <button class="burger-btn" onclick="openDrawer()"><i class="fas fa-bars"></i></button>
                <div class="site-logo"><img src="assets/images/logo.png" alt="Logo" class="header-logo"></div>
            </div>
            <div class="header-right">
                <button class="search-toggle" onclick="toggleHeaderSearch()"><i class="fas fa-search"></i></button>
            </div>
        </div>
        <div class="header-search-bar" id="header-search-bar" style="display: none;">
            <div class="search-wrapper">
                <form action="tv-series.php" method="GET" class="header-search-form">
                    <input type="text" name="search" placeholder="Search..." autocomplete="off" id="header-search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
                <button type="button" class="clear-btn" onclick="toggleHeaderSearch()"><i class="fas fa-times"></i></button>
                <div id="live-search-results" class="live-search-dropdown"></div>
            </div>
        </div>
    </div>

    <!-- App Container -->
    <div class="app-container">
        <div class="main-content">
            
            <!-- Language Filter Tabs -->
            <?php
            // Build query string to preserve genre filter
            $genre_param = isset($_GET['genre']) ? '&genre=' . urlencode($_GET['genre']) : '';
            $genre_param_first = isset($_GET['genre']) ? '?genre=' . urlencode($_GET['genre']) : '';
            ?>
            <div class="language-filter-tabs">
                <a href="tv-series.php<?php echo $genre_param_first; ?>" class="filter-tab <?php echo !isset($_GET['filter']) ? 'active' : ''; ?>">
                    All
                </a>
                <a href="tv-series.php?filter=dubbed<?php echo $genre_param; ?>" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'dubbed') ? 'active' : ''; ?>">
                    Dubbed
                </a>
                <a href="tv-series.php?filter=subtitled<?php echo $genre_param; ?>" class="filter-tab <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'subtitled') ? 'active' : ''; ?>">
                    Subtitled
                </a>
            </div>

            <!-- Ad Banner -->
            <?php renderResponsiveBannerAd(); ?>

            <!-- Series Grid -->
            <div class="container">
                <?php if (!empty($series_list)): ?>
                    <div class="series-grid-2col">
                        <?php foreach ($series_list as $series): 
                            $slug = createSlug($series['title']);
                            $clean_url = "series/" . $series['id'] . "/" . $slug;

                            // Fix Poster Path Logic
                            $s_poster = $series['cover_image'];
                            
                            // 1. Strip hardcoded legacy domain
                            if (strpos($s_poster, 'sinhalamovies.web.lk/uploads/') !== false) {
                                $s_poster = 'uploads/' . basename($s_poster);
                            }

                            // 2. Resolve to absolute path
                            if (!preg_match('/^http/', $s_poster)) {
                                $local_check = __DIR__ . '/' . ltrim($s_poster, '/');
                                if (file_exists($local_check)) {
                                    $s_poster = BASE_URL . '/' . ltrim($s_poster, '/');
                                } else {
                                     $s_poster = BASE_URL . '/' . ltrim($s_poster, '/');
                                }
                            }
                        ?>
                            <a href="<?php echo $clean_url; ?>" class="movie-card">
                                <img src="<?php echo htmlspecialchars($s_poster); ?>" 
                                     alt="<?php echo htmlspecialchars($series['title']); ?>"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.src='assets/images/placeholder.jpg';">
                                <div class="movie-info">
                                    <div class="movie-title"><?php echo htmlspecialchars($series['title']); ?></div>
                                    <div style="font-size: 10px; color: #888;"><?php 
                                        $year = 'N/A';
                                        if (!empty($series['release_date'])) {
                                            preg_match('/\d{4}/', $series['release_date'], $matches);
                                            $year = $matches[0] ?? 'N/A';
                                        }
                                        echo htmlspecialchars($year);
                                    ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-movies">
                        <h3>📺 No TV Series Found</h3>
                        <p>We couldn't find any TV series matching your search. Try a different keyword!</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Native Banner Ad (Blends with content) -->
            <?php renderNativeBanner(); ?>
            
            <!-- Pagination (Desktop/Tablet Only) -->
            <?php include '_pagination.php'; ?>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>

    <!-- Side Drawer -->
    <div id="side-drawer" class="drawer-overlay">
        <div class="drawer-content">
            <div class="drawer-header">
                <button class="close-drawer" onclick="closeDrawer()">&times;</button>
            </div>
            <div class="drawer-auth">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="auth-btn logged-in">
                        <i class="fas fa-user-circle"></i> My Profile
                    </a>
                <?php else: ?>
                    <button onclick="showLoginModal()" class="auth-btn login-gradient">
                        Login
                    </button>
                <?php endif; ?>
            </div>
            <div class="drawer-section">
                <h3>GENRES</h3>
                <div class="genre-list">
                    <?php $current_genre = isset($_GET['genre']) ? $_GET['genre'] : ''; ?>
                    <a href="tv-series.php?genre=Action" class="genre-item <?php echo ($current_genre === 'Action') ? 'active' : ''; ?>">Action</a>
                    <a href="tv-series.php?genre=Comedy" class="genre-item <?php echo ($current_genre === 'Comedy') ? 'active' : ''; ?>">Comedy</a>
                    <a href="tv-series.php?genre=Drama" class="genre-item <?php echo ($current_genre === 'Drama') ? 'active' : ''; ?>">Drama</a>
                    <a href="tv-series.php?genre=Horror" class="genre-item <?php echo ($current_genre === 'Horror') ? 'active' : ''; ?>">Horror</a>
                    <a href="tv-series.php?genre=Romance" class="genre-item <?php echo ($current_genre === 'Romance') ? 'active' : ''; ?>">Romance</a>
                    <a href="tv-series.php?genre=Sci-Fi" class="genre-item <?php echo ($current_genre === 'Sci-Fi') ? 'active' : ''; ?>">Sci-Fi</a>
                    <a href="tv-series.php?genre=Thriller" class="genre-item <?php echo ($current_genre === 'Thriller') ? 'active' : ''; ?>">Thriller</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Open Drawer
        function openDrawer() {
            document.getElementById('side-drawer').classList.add('open');
        }

        // Close Drawer
        function closeDrawer() {
            document.getElementById('side-drawer').classList.remove('open');
        }

        // Close drawer when clicking outside
        document.getElementById('side-drawer').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDrawer();
            }
        });

        // Toggle Header Search (WhatsApp/YouTube style)
        function toggleHeaderSearch() {
            const headerContent = document.getElementById('header-content');
            const searchBar = document.getElementById('header-search-bar');
            const searchInput = document.getElementById('header-search-input');
            
            if (searchBar.style.display === 'none' || searchBar.style.display === '') {
                // Show search bar, hide header content
                headerContent.style.display = 'none';
                searchBar.style.display = 'flex';
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            } else {
                // Hide search bar, show header content
                searchBar.style.display = 'none';
                headerContent.style.display = 'flex';
            }
        }

        // Clear Search Function
        function clearSearch() {
            const searchInput = document.getElementById('header-search-input');
            const resultsContainer = document.getElementById('live-search-results');
            
            if (searchInput) {
                searchInput.value = '';
            }
            
            if (resultsContainer) {
                resultsContainer.innerHTML = '';
            }
            
            // Refocus input
            if (searchInput) {
                searchInput.focus();
            }
        }

        // Close search with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const searchBar = document.getElementById('header-search-bar');
                if (searchBar.style.display === 'flex') {
                    toggleHeaderSearch();
                }
            }
        });

        // ========================================
        // Modal Management & AJAX Form Submission
        // ========================================
        
        // Login Modal Functions
        function showLoginModal() {
            const modal = document.getElementById('login-modal');
            modal.style.display = 'flex';
            closeDrawer();
            trapFocus(modal);
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeLoginModal() {
            document.getElementById('login-modal').style.display = 'none';
            // Clear error message
            hideError('login-error');
            // Reset form
            const form = document.getElementById('login-form');
            if (form) form.reset();
        }

        // Signup Modal Functions
        function showSignupModal() {
            const modal = document.getElementById('signup-modal');
            modal.style.display = 'flex';
            closeDrawer();
            trapFocus(modal);
            // Focus first input
            setTimeout(() => {
                const firstInput = modal.querySelector('input');
                if (firstInput) firstInput.focus();
            }, 100);
        }

        function closeSignupModal() {
            document.getElementById('signup-modal').style.display = 'none';
            // Clear error message
            hideError('signup-error');
            // Reset form
            const form = document.getElementById('signup-form');
            if (form) form.reset();
        }

        // Switch between modals
        function switchToSignup() {
            closeLoginModal();
            showSignupModal();
        }

        function switchToLogin() {
            closeSignupModal();
            showLoginModal();
        }

        // Helper function to show error messages
        function showError(elementId, message) {
            const errorDiv = document.getElementById(elementId);
            if (errorDiv) {
                errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
                errorDiv.style.display = 'block';
                // Scroll error into view
                errorDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
        
        // Helper function to hide error messages
        function hideError(elementId) {
            const errorDiv = document.getElementById(elementId);
            if (errorDiv) {
                errorDiv.style.display = 'none';
                errorDiv.innerHTML = '';
            }
        }

        // Forgot Password WhatsApp Function
        function openForgotPasswordWhatsApp() {
            // Get email from login form
            const emailInput = document.getElementById('login-email');
            const email = emailInput ? emailInput.value.trim() : '';
            
            // WhatsApp number (94766032279)
            const whatsappNumber = '94766032279';
            
            // Pre-filled message
            let message = 'Hello Admin, I forgot my password for Zinema.lk.';
            if (email) {
                message += ` My email is: ${email}`;
            } else {
                message += ' My email is: [Please enter your email]';
            }
            
            // Encode message for URL
            const encodedMessage = encodeURIComponent(message);
            
            // Open WhatsApp
            window.open(`https://wa.me/${whatsappNumber}?text=${encodedMessage}`, '_blank');
        }

        // ========================================
        // Forgot Password Modal Functions
        // ========================================
        function showForgotPasswordModal() {
            closeLoginModal();
            const modal = document.getElementById('forgot-password-modal');
            modal.style.display = 'flex';
            trapFocus(modal);
            // Pre-fill email from login form if available
            const loginEmail = document.getElementById('login-email');
            const forgotEmail = document.getElementById('forgot-email');
            if (loginEmail && forgotEmail && loginEmail.value) {
                forgotEmail.value = loginEmail.value;
            }
            setTimeout(() => {
                document.getElementById('forgot-email').focus();
            }, 100);
        }
        
        function closeForgotPasswordModal() {
            document.getElementById('forgot-password-modal').style.display = 'none';
            hideError('forgot-error');
            const successDiv = document.getElementById('forgot-success');
            if (successDiv) successDiv.style.display = 'none';
            document.getElementById('forgot-password-form').reset();
        }
        
        function switchFromForgotToLogin() {
            closeForgotPasswordModal();
            showLoginModal();
        }

        // Focus Trap for Accessibility
        function trapFocus(modal) {
            const focusableElements = modal.querySelectorAll(
                'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
            );
            const firstElement = focusableElements[0];
            const lastElement = focusableElements[focusableElements.length - 1];

            modal.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstElement) {
                            e.preventDefault();
                            lastElement.focus();
                        }
                    } else {
                        if (document.activeElement === lastElement) {
                            e.preventDefault();
                            firstElement.focus();
                        }
                    }
                }
            });
        }

        // Login Form AJAX Handler
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    
                    // Hide previous errors
                    hideError('login-error');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch('ajax_login.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        if (data.success) {
                            // Success - reload page to update session
                            window.location.reload();
                        } else {
                            // Show error message
                            showError('login-error', data.message);
                        }
                    } catch (error) {
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        // Show error
                        showError('login-error', 'An error occurred. Please try again.');
                        console.error('Login error:', error);
                    }
                });
            }
            
            // Signup Form AJAX Handler
            const signupForm = document.getElementById('signup-form');
            if (signupForm) {
                signupForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    
                    // Hide previous errors
                    hideError('signup-error');
                    
                    // Show loading state
                    submitBtn.disabled = true;
                    btnText.style.display = 'none';
                    btnLoader.style.display = 'inline-block';
                    
                    // Get form data
                    const formData = new FormData(this);
                    
                    try {
                        const response = await fetch('ajax_signup.php', {
                            method: 'POST',
                            body: formData
                        });
                        
                        const data = await response.json();
                        
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        if (data.success) {
                            // Success - reload page to update session
                            window.location.reload();
                        } else {
                            // Show error message
                            showError('signup-error', data.message);
                        }
                    } catch (error) {
                        // Reset button state
                        submitBtn.disabled = false;
                        btnText.style.display = 'inline';
                        btnLoader.style.display = 'none';
                        
                        // Show error
                        showError('signup-error', 'An error occurred. Please try again.');
                        console.error('Signup error:', error);
                    }
                });
            }
        });

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            const loginModal = document.getElementById('login-modal');
            const signupModal = document.getElementById('signup-modal');
            if (e.target === loginModal) {
                closeLoginModal();
            }
            if (e.target === signupModal) {
                closeSignupModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLoginModal();
                closeSignupModal();
                closeForgotPasswordModal();
            }
        });

        // Live Search Functionality
        const searchInput = document.getElementById('header-search-input');
        const resultsContainer = document.getElementById('live-search-results');
        let searchTimeout;

        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // If input is empty, clear results
            if (query === '') {
                resultsContainer.innerHTML = '';
                return;
            }
            
            // Debounce: Wait 300ms after user stops typing
            searchTimeout = setTimeout(() => {
                // Fetch search results (you can create a separate API for series or modify the existing one)
                fetch(`api/live_search.php?q=${encodeURIComponent(query)}&type=series`)
                    .then(response => response.json())
                    .then(series => {
                        if (series.length === 0) {
                            resultsContainer.innerHTML = '<div class="no-results">No series found</div>';
                        } else {
                            resultsContainer.innerHTML = series.map(item => `
                                <a href="series-details.php?id=${item.id}" class="search-result-item">
                                    <img src="${item.cover_image}" alt="${item.title}">
                                    <div class="meta">
                                        <span class="title">${item.title}</span>
                                        <span class="year">${item.release_date.substring(0, 4)}</span>
                                    </div>
                                </a>
                            `).join('');
                        }
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        resultsContainer.innerHTML = '<div class="no-results">Search failed</div>';
                    });
            }, 300);
        });

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsContainer.contains(e.target)) {
                resultsContainer.innerHTML = '';
            }
        });
    </script>

    <!-- Login Modal -->
    <div id="login-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeLoginModal()">&times;</button>
            <div class="login-header">
                <h2>🎬 Zinema.lk</h2>
                <p>Welcome back!</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="login-error" class="form-error-message" style="display: none;" role="alert"></div>
            
            <form id="login-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="email" id="login-email" name="email" placeholder="Email" required aria-label="Email address">
                <input type="password" id="login-password" name="password" placeholder="Password" required aria-label="Password">
                <div class="remember-me-container">
                    <input type="checkbox" id="remember-me" name="remember" checked>
                    <label for="remember-me">Remember me</label>
                </div>
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Login</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                
                <!-- Forgot Password Link -->
                <div class="forgot-password-container">
                    <a href="#" onclick="showForgotPasswordModal(); return false;" class="forgot-password-link">
                        <i class="fas fa-key"></i> Forgot Password?
                    </a>
                </div>
                
                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>
                
                <!-- Google Sign-In Button -->
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google-signin">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign in with Google
                </a>
                
                <div class="login-footer">
                    <p>No account? <a href="#" onclick="switchToSignup(); return false;" aria-label="Switch to sign up form">Sign Up</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Signup Modal -->
    <div id="signup-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeSignupModal()">&times;</button>
            <div class="login-header">
                <h2>🚀 Join Us</h2>
                <p>Create Account</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="signup-error" class="form-error-message" style="display: none;" role="alert"></div>
            
            <form id="signup-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="text" id="signup-username" name="username" placeholder="Username" required aria-label="Username">
                <input type="email" id="signup-email" name="email" placeholder="Email" required aria-label="Email address">
                <input type="password" id="signup-password" name="password" placeholder="Password" minlength="6" required aria-label="Password">
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Sign Up</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                
                <!-- Divider -->
                <div class="auth-divider">
                    <span>or continue with</span>
                </div>
                
                <!-- Google Sign-Up Button -->
                <a href="<?php echo getGoogleAuthUrl(); ?>" class="btn-google-signin">
                    <svg width="18" height="18" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Sign up with Google
                </a>
                
                <div class="login-footer">
                    <p>Have an account? <a href="#" onclick="switchToLogin(); return false;" aria-label="Switch to login form">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgot-password-modal" class="login-overlay" style="display: none;">
        <div class="login-card">
            <button class="close-login-btn" onclick="closeForgotPasswordModal()">&times;</button>
            <div class="login-header">
                <h2>🔑 Reset Password</h2>
                <p>Enter your email to receive a reset link</p>
            </div>
            
            <div id="forgot-error" class="form-error-message" style="display: none;" role="alert"></div>
            <div id="forgot-success" class="form-success-message" style="display: none;"></div>
            
            <form id="forgot-password-form" class="login-modal-form">
                <?php csrf_token_field(); ?>
                <input type="email" id="forgot-email" name="email" placeholder="Enter your email" required aria-label="Email address">
                <button type="submit" class="btn-login-submit">
                    <span class="btn-text">Send Reset Link</span>
                    <span class="btn-loader" style="display: none;">⏳</span>
                </button>
                <div class="login-footer">
                    <p>Remember your password? <a href="#" onclick="switchFromForgotToLogin(); return false;">Login</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Search Overlay -->
    <div id="search-overlay" class="search-overlay">
        <div class="search-box-wrapper">
            <button type="button" class="search-back-btn" onclick="closeSearchOverlay()">
                <i class="fas fa-arrow-left"></i>
            </button>
            
            <form action="tv-series.php" method="GET" class="search-form-overlay">
                <input type="text" name="search" id="search-input" placeholder="Search TV series..." autocomplete="off">
            </form>
        </div>
    </div>

    <script>
    // Forgot Password Form AJAX Handler
    document.addEventListener('DOMContentLoaded', function() {
        const forgotForm = document.getElementById('forgot-password-form');
        if (forgotForm) {
            forgotForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('.btn-login-submit');
                const btnText = submitBtn.querySelector('.btn-text');
                const btnLoader = submitBtn.querySelector('.btn-loader');
                const successDiv = document.getElementById('forgot-success');
                
                hideError('forgot-error');
                successDiv.style.display = 'none';
                
                // Show loading
                submitBtn.disabled = true;
                btnText.style.display = 'none';
                btnLoader.style.display = 'inline-block';
                
                try {
                    const formData = new FormData(this);
                    
                    const response = await fetch('ajax_forgot_password.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoader.style.display = 'none';
                    
                    if (data.success) {
                        successDiv.innerHTML = '<i class="fas fa-check-circle"></i> ' + data.message;
                        successDiv.style.display = 'block';
                        this.reset();
                    } else {
                        showError('forgot-error', data.message);
                    }
                } catch (error) {
                    submitBtn.disabled = false;
                    btnText.style.display = 'inline';
                    btnLoader.style.display = 'none';
                    showError('forgot-error', 'An error occurred. Please try again.');
                    console.error('Forgot password error:', error);
                }
            });
        }
    });
    </script>
</body>
</html>
