<?php
// Get current page for active state
$current_php_file = basename($_SERVER['PHP_SELF']);
?>

<!-- ===== TOP NAVIGATION BAR (Desktop & Tablet Only) ===== -->
<nav class="top-nav">
    <div class="top-nav-container">
        <!-- Logo -->
        <a href="<?php echo BASE_URL; ?>/index.php" class="top-nav-logo">
            <img src="<?php echo BASE_URL; ?>/assets/images/logo.png" alt="Zinema.lk Logo">
        </a>

        <!-- Navigation Links -->
        <div class="top-nav-links">
            <a href="<?php echo BASE_URL; ?>/index.php" class="top-nav-item <?php echo ($current_php_file === 'index.php' || $current_php_file === '') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19 3H5C3.89543 3 3 3.89543 3 5V19C3 20.1046 3.89543 21 5 21H19C20.1046 21 21 20.1046 21 19V5C21 3.89543 20.1046 3 19 3Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 3V21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 12H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 7H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M3 17H7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 17H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 7H21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="nav-label">Shots</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/movies.php" class="top-nav-item <?php echo ($current_php_file === 'movies.php' || $current_php_file === 'movie-details.php') ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M19.82 2H4.18C2.97602 2 2 2.97602 2 4.18V19.82C2 21.024 2.97602 22 4.18 22H19.82C21.024 22 22 21.024 22 19.82V4.18C22 2.97602 21.024 2 19.82 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    <path d="M2 9H22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M2 15H22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <span class="nav-label">Movies</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/tv-series.php" class="top-nav-item <?php echo $current_php_file === 'tv-series.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="2" y="7" width="20" height="13" rx="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M17 2L12 7L7 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="nav-label">Series</span>
            </a>

            <a href="<?php echo BASE_URL; ?>/collections.php" class="top-nav-item <?php echo $current_php_file === 'collections.php' ? 'active' : ''; ?>">
                <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M4 6H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M4 18H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <circle cx="4" cy="6" r="1" fill="currentColor"/>
                    <circle cx="4" cy="12" r="1" fill="currentColor"/>
                    <circle cx="4" cy="18" r="1" fill="currentColor"/>
                </svg>
                <span class="nav-label">Sets</span>
            </a>

            <!-- Genre Dropdown -->
            <div class="genre-dropdown">
                <button class="genre-dropdown-trigger" onclick="toggleGenreDropdown(event)">
                    <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M4 6H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <path d="M4 18H12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <span class="nav-label">Genres</span>
                </button>
                <div class="genre-dropdown-menu" id="genre-dropdown-menu">
                    <?php $current_genre = isset($_GET['genre']) ? $_GET['genre'] : ''; ?>
                    <a href="<?php echo $current_php_file; ?>?genre=Action" class="<?php echo ($current_genre === 'Action') ? 'active-genre' : ''; ?>">Action</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Comedy" class="<?php echo ($current_genre === 'Comedy') ? 'active-genre' : ''; ?>">Comedy</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Drama" class="<?php echo ($current_genre === 'Drama') ? 'active-genre' : ''; ?>">Drama</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Horror" class="<?php echo ($current_genre === 'Horror') ? 'active-genre' : ''; ?>">Horror</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Romance" class="<?php echo ($current_genre === 'Romance') ? 'active-genre' : ''; ?>">Romance</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Sci-Fi" class="<?php echo ($current_genre === 'Sci-Fi') ? 'active-genre' : ''; ?>">Sci-Fi</a>
                    <a href="<?php echo $current_php_file; ?>?genre=Thriller" class="<?php echo ($current_genre === 'Thriller') ? 'active-genre' : ''; ?>">Thriller</a>
                    
                    <?php if ($current_php_file !== 'collections.php'): ?>
                    <?php $current_filter = isset($_GET['filter']) ? $_GET['filter'] : ''; ?>
                    <div class="genre-dropdown-separator"></div>
                    <a href="<?php echo $current_php_file; ?>?filter=dubbed" class="<?php echo ($current_filter === 'dubbed') ? 'active-genre' : ''; ?>">🎬 Dubbed</a>
                    <a href="<?php echo $current_php_file; ?>?filter=subtitled" class="<?php echo ($current_filter === 'subtitled') ? 'active-genre' : ''; ?>">📝 Subtitled</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Search Icon Button -->
        <button class="top-nav-search-icon" onclick="openSearchPopup()" title="Search">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>

        <!-- Profile Link -->
        <a href="<?php echo BASE_URL; ?>/profile.php" class="top-nav-profile">
            <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
                <path d="M6 21C6 17.6863 8.68629 15 12 15C15.3137 15 18 17.6863 18 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </a>
    </div>
</nav>

<!-- Search Popup Overlay (Desktop/Tablet Only) -->
<div id="search-popup-overlay" class="search-popup-overlay" data-base-url="<?php echo htmlspecialchars(BASE_URL . '/', ENT_QUOTES); ?>">
    <div class="search-popup-container">
        <button class="search-popup-close" onclick="closeSearchPopup()">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M6 6L18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </button>
        
        <div class="search-popup-content">
            <h2>Search</h2>
            <form action="movies.php" method="GET" class="search-popup-form" onsubmit="return handleSearchSubmit(event)">
                <div class="search-popup-input-wrapper">
                    <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input 
                        type="text" 
                        name="search" 
                        id="search-popup-input"
                        placeholder="Search for movies, series..." 
                        autocomplete="off"
                        oninput="handlePopupSearch(this.value)"
                    >
                </div>
            </form>
            
            <div id="search-popup-results" class="search-popup-results"></div>
        </div>
    </div>
</div>

<script>
// Toggle Genre Dropdown
function toggleGenreDropdown(event) {
    event.stopPropagation();
    const menu = document.getElementById('genre-dropdown-menu');
    menu.classList.toggle('open');
}

// Close genre dropdown when clicking outside
document.addEventListener('click', function(e) {
    const menu = document.getElementById('genre-dropdown-menu');
    if (menu && !e.target.closest('.genre-dropdown')) {
        menu.classList.remove('open');
    }
});

// Search Popup Functions
function openSearchPopup() {
    const overlay = document.getElementById('search-popup-overlay');
    overlay.classList.add('active');
    setTimeout(() => {
        document.getElementById('search-popup-input').focus();
    }, 100);
}

function closeSearchPopup() {
    const overlay = document.getElementById('search-popup-overlay');
    overlay.classList.remove('active');
    document.getElementById('search-popup-input').value = '';
    document.getElementById('search-popup-results').innerHTML = '';
}

// Close on overlay click
document.getElementById('search-popup-overlay')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeSearchPopup();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSearchPopup();
    }
});

// Handle search submit
function handleSearchSubmit(e) {
    e.preventDefault();
    const query = document.getElementById('search-popup-input').value.trim();
    if (query) {
        window.location.href = new URL(`movies.php?search=${encodeURIComponent(query)}`, popupBase).href;
    }
}

</script>

<script src="<?php echo htmlspecialchars(BASE_URL, ENT_QUOTES); ?>/assets/js/search-popup.js?v=<?php echo substr(hash_file('sha256', __DIR__ . '/assets/js/search-popup.js'),0,12); ?>"></script>
