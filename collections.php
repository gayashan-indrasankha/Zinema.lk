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

// Initialize collections array
$collections = [];

// Build SQL query with search
$sql = "SELECT id, title, description, cover_image, created_at FROM collections WHERE 1=1";
$params = [];
$types = "";

// Search Logic
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $sql .= " AND title LIKE ?";
    $params[] = '%' . $_GET['search'] . '%';
    $types .= "s";
}

// Genre Filter (supports comma-separated genres)
if (isset($_GET['genre']) && !empty($_GET['genre'])) {
    $sql .= " AND genre LIKE ?";
    $params[] = '%' . $_GET['genre'] . '%';
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

// Pagination for Desktop/Tablet (12 items per page = 2 rows)
$items_per_page = 12;
$current_page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$count_sql = str_replace("SELECT id, title, description, cover_image, created_at", "SELECT COUNT(*) as total", $sql);
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_collections = $count_result->fetch_assoc()['total'];
    $count_stmt->close();
} else {
    $count_result = $conn->query($count_sql);
    $total_collections = $count_result->fetch_assoc()['total'];
}

$total_pages = ceil($total_collections / $items_per_page);

// Add pagination LIMIT/OFFSET
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
        $collections[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse curated movie collections and box sets on Zinema.lk. Watch complete franchises like Marvel, Harry Potter, Fast & Furious and more in Full HD with Sinhala subtitles.">
    <meta name="keywords" content="movie collections, movie box sets, complete series, sinhala movies, movie franchise, Zinema.lk">
    <link rel="canonical" href="<?php echo BASE_URL; ?>/collections.php">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    <title>Movie Collections & Box Sets - Complete Franchises | Zinema.lk</title>
    
    <!-- Open Graph Tags -->
    <meta property="og:title" content="Movie Collections & Box Sets | Zinema.lk">
    <meta property="og:description" content="Browse curated movie collections. Watch complete franchises in Full HD with Sinhala subtitles.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo BASE_URL; ?>/collections.php">
    <meta property="og:site_name" content="Zinema.lk">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile-style.css?v=2.0">
    <link rel="stylesheet" href="css/responsive.css">
    <link rel="stylesheet" href="css/search_popup_styles.css">
    <link rel="stylesheet" href="css/desktop-scroll-fix.css">
    <link rel="stylesheet" href="css/desktop-tablet.css">
    <style>
        /* Fix scroll for Collections page */
        body {
            overflow-y: auto !important;
            overflow-x: hidden !important;
            height: auto !important;
        }
        
        .app-container {
            height: auto !important;
            overflow: visible !important;
            min-height: 100vh;
        }
        
        /* Collections Page Styling */
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

        /* Collection Grid - Force 2 columns on mobile */
        .collection-grid {
            margin-top: 0px;
            display: grid !important;
            gap: 12px;
            grid-template-columns: repeat(2, 1fr) !important;
            padding-bottom: 20px;
            padding-top: 20px;
            padding-left: 15px;
            padding-right: 15px;
        }

        @media (min-width: 768px) {
            .collection-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 20px;
                padding: 0 40px 40px;
                max-width: 1400px;
                margin: 0 auto;
            }
            
            .container {
                max-width: 100% !important;
                width: 100% !important;
            }
            
            .app-container {
                padding-top: 20px;
            }
        }

        @media (min-width: 1200px) {
            .collection-grid {
                grid-template-columns: repeat(5, 1fr) !important;
                gap: 25px;
            }
        }
        
        @media (min-width: 1440px) {
            .collection-grid {
                grid-template-columns: repeat(6, 1fr) !important;
                max-width: 1600px;
            }
        }

        /* Netflix-Style Collection Card */
        .collection-card {
            display: flex;
            flex-direction: column;
            background: rgba(20, 22, 30, 1);
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            cursor: pointer;
        }

        .collection-card:hover {
            transform: scale(1.08) translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
            z-index: 10;
        }

        /* Gradient Overlay on Hover - Subtle */
        .collection-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8) 0%, rgba(0, 0, 0, 0.2) 50%, rgba(0, 0, 0, 0) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 2;
            pointer-events: none;
        }

        .collection-card:hover::before {
            opacity: 1;
        }

        /* Keep text visible on hover */
        .collection-card::after {
            content: '';
            display: none;
        }

        .collection-card img {
            width: 100%;
            aspect-ratio: 2/3;
            object-fit: cover;
            display: block;
        }

        .collection-meta {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px 12px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.8) 50%, transparent 100%);
            z-index: 3;
        }

        .collection-title {
            font-size: 15px !important;
            font-weight: 700 !important;
            color: #fff !important;
            margin: 0 0 6px 0 !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
            display: -webkit-box !important;
            -webkit-line-clamp: 2 !important;
            -webkit-box-orient: vertical !important;
            line-height: 1.3 !important;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.8) !important;
            background: none !important;
            -webkit-background-clip: unset !important;
            -webkit-text-fill-color: #fff !important;
        }

        .collection-description {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin: 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        /* Empty state */
        .no-collections {
            text-align: center;
            padding: 80px 20px;
            color: var(--muted);
        }

        .no-collections h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .no-collections p {
            font-size: 1rem;
        }

        /* Mobile-specific improvements */
        @media (max-width: 768px) {
            .page-title {
                font-size: 2rem;
            }

            .collection-icon {
                width: 50px;
                height: 50px;
            }

            .collection-icon span {
                font-size: 22px;
            }
            
            /* Larger text on mobile for better readability */
            .collection-title {
                font-size: 16px !important;
            }
            
            .collection-description {
                font-size: 13px !important;
            }
            
            .collection-meta {
                padding: 18px 14px !important;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 1.7rem;
            }
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
            border-bottom: none;
        }

        .search-wrapper {
            position: relative;
            width: 90%;
            max-width: 500px;
            margin: 0 auto;
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
                <form action="collections.php" method="GET" class="header-search-form">
                    <input type="text" name="search" placeholder="Search collections..." autocomplete="off" id="header-search-input" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>
                <button type="button" class="clear-btn" onclick="toggleHeaderSearch()"><i class="fas fa-times"></i></button>
            </div>
        </div>
    </div>

    <!-- App Container -->
    <div class="app-container">
        <!-- Main Container -->
        <div class="container">
            <!-- Section Title - Hide when genre filter is active -->
            <?php if (!isset($_GET['genre']) || empty($_GET['genre'])): ?>
            <div class="section-title-row hide-on-desktop">
                <h2>All Collections</h2>
            </div>
            <?php endif; ?>

            <!-- Ad Banner -->
            <?php renderResponsiveBannerAd(); ?>

            <!-- Collections Grid -->
            <?php if (!empty($collections)): ?>
                <div class="collection-grid">
            <?php 
            // Helper function for slugs
            if (!function_exists('createSlug')) {
                function createSlug($string) {
                    $slug = strtolower($string);
                    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
                    $slug = trim($slug, '-');
                    return $slug ?: 'collection';
                }
            }

            foreach ($collections as $collection): 
                $slug = createSlug($collection['title']);
                $clean_url = "collection/" . $collection['id'] . "/" . $slug;

                // Fix Poster Path Logic
                $c_poster = $collection['cover_image'];
                
                // 1. Strip hardcoded legacy domain
                if (strpos($c_poster, 'sinhalamovies.web.lk/uploads/') !== false) {
                    $c_poster = 'uploads/' . basename($c_poster);
                }

                // 2. Resolve to absolute path
                if (!preg_match('/^http/', $c_poster)) {
                    $local_check = __DIR__ . '/' . ltrim($c_poster, '/');
                    if (file_exists($local_check)) {
                        $c_poster = BASE_URL . '/' . ltrim($c_poster, '/');
                    } else {
                        // If file missing locally, try standard uploads path as fallback guess
                        // or just keep original relative path if we can't be sure
                         $c_poster = BASE_URL . '/' . ltrim($c_poster, '/');
                    }
                }
            ?>
                <a href="<?php echo $clean_url; ?>" class="collection-card">
                    <img src="<?php echo htmlspecialchars($c_poster); ?>" 
                         alt="<?php echo htmlspecialchars($collection['title']); ?>"
                         loading="lazy"
                         decoding="async">
                    <div class="collection-meta">
                        <h3 class="collection-title"><?php echo htmlspecialchars($collection['title']); ?></h3>
                        <?php if (!empty($collection['description'])): ?>
                            <p class="collection-description"><?php echo htmlspecialchars($collection['description']); ?></p>
                        <?php endif; ?>
                    </div>
                </a>
            <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-collections">
                <h3>📚 No Collections Found</h3>
                <p>Check back soon for curated movie collections! 🎬</p>
            </div>
        <?php endif; ?>
        </div>

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
                    <a href="collections.php?genre=Action" class="genre-item <?php echo ($current_genre === 'Action') ? 'active' : ''; ?>">Action</a>
                    <a href="collections.php?genre=Comedy" class="genre-item <?php echo ($current_genre === 'Comedy') ? 'active' : ''; ?>">Comedy</a>
                    <a href="collections.php?genre=Drama" class="genre-item <?php echo ($current_genre === 'Drama') ? 'active' : ''; ?>">Drama</a>
                    <a href="collections.php?genre=Horror" class="genre-item <?php echo ($current_genre === 'Horror') ? 'active' : ''; ?>">Horror</a>
                    <a href="collections.php?genre=Romance" class="genre-item <?php echo ($current_genre === 'Romance') ? 'active' : ''; ?>">Romance</a>
                    <a href="collections.php?genre=Sci-Fi" class="genre-item <?php echo ($current_genre === 'Sci-Fi') ? 'active' : ''; ?>">Sci-Fi</a>
                    <a href="collections.php?genre=Thriller" class="genre-item <?php echo ($current_genre === 'Thriller') ? 'active' : ''; ?>">Thriller</a>
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

        // Toggle Header Search
        function toggleHeaderSearch() {
            const headerContent = document.getElementById('header-content');
            const searchBar = document.getElementById('header-search-bar');
            const searchInput = document.getElementById('header-search-input');
            
            if (searchBar.style.display === 'none' || searchBar.style.display === '') {
                headerContent.style.display = 'none';
                searchBar.style.display = 'flex';
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            } else {
                searchBar.style.display = 'none';
                headerContent.style.display = 'flex';
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
