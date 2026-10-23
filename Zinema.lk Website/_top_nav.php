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
<div id="search-popup-overlay" class="search-popup-overlay">
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
        window.location.href = `movies.php?search=${encodeURIComponent(query)}`;
    }
}

// Live Search in Popup
let popupSearchTimeout;

function handlePopupSearch(query) {
    clearTimeout(popupSearchTimeout);
    
    const resultsContainer = document.getElementById('search-popup-results');
    
    if (query.length < 2) {
        resultsContainer.innerHTML = '';
        return;
    }
    
    resultsContainer.innerHTML = '<div class="search-loading">Searching...</div>';
    
    popupSearchTimeout = setTimeout(() => {
        // Fetch search results via AJAX
        fetch(`api/live_search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data) && data.length > 0) {
                    let html = '<div class="search-results-grid">';
                    data.forEach(item => {
                        // Determine URL based on current page (movies or series)
                        // Use Clean URL format: /movie/123/watch or /series/123/watch
                        // Ideally we would slugify the title, but JS slugify matches loose regex in htaccess
                        const isSeries = window.location.pathname.includes('series') || item.type === 'series'; // item.type depends on API
                        // Fallback logic if API doesn't return type
                        const urlPrefix = isSeries ? 'series' : 'movie';
                        const url = `${urlPrefix}/${item.id}/watch`;
                        const year = item.release_date ? new Date(item.release_date).getFullYear() : 'N/A';
                        html += `
                            <a href="${url}" class="search-result-card" onclick="closeSearchPopup()">
                                <img src="${item.cover_image}" alt="${item.title}">
                                <div class="search-result-info">
                                    <div class="search-result-title">${item.title}</div>
                                    <div class="search-result-year">${year}</div>
                                </div>
                            </a>
                        `;
                    });
                    html += '</div>';
                    resultsContainer.innerHTML = html;
                } else {
                    resultsContainer.innerHTML = '<div class="search-no-results">😕 No results found for "' + query + '"</div>';
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                resultsContainer.innerHTML = '<div class="search-error">❌ Search failed. Please try again.</div>';
            });
    }, 300);
}
</script>
