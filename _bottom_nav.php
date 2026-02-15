<?php
// Get current nav page from parent page, if set
$active_nav = isset($current_nav_page) ? $current_nav_page : '';
?>
<!-- ===== Bottom Navigation Bar (Netflix/Spotify Style) ===== -->
<nav class="bottom-nav" data-active-page="<?php echo htmlspecialchars($active_nav); ?>">
    <a href="<?php echo BASE_URL; ?>/index.php" class="nav-item<?php echo ($active_nav === 'shots') ? ' active' : ''; ?>" data-page="shots">
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
    
    <a href="<?php echo BASE_URL; ?>/movies.php" class="nav-item<?php echo ($active_nav === 'movies') ? ' active' : ''; ?>" data-page="movies">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M19.82 2H4.18C2.97602 2 2 2.97602 2 4.18V19.82C2 21.024 2.97602 22 4.18 22H19.82C21.024 22 22 21.024 22 19.82V4.18C22 2.97602 21.024 2 19.82 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
            <path d="M2 9H22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            <path d="M2 15H22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="nav-label">Movies</span>
    </a>
    
    <a href="<?php echo BASE_URL; ?>/tv-series.php" class="nav-item<?php echo ($active_nav === 'tv-series') ? ' active' : ''; ?>" data-page="tv-series">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="2" y="7" width="20" height="13" rx="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M17 2L12 7L7 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="nav-label">Series</span>
    </a>
    
    <a href="<?php echo BASE_URL; ?>/collections.php" class="nav-item<?php echo ($active_nav === 'collections') ? ' active' : ''; ?>" data-page="collections">
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
    
    <a href="<?php echo BASE_URL; ?>/profile.php" class="nav-item<?php echo ($active_nav === 'profile') ? ' active' : ''; ?>" data-page="profile">
        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="2"/>
            <path d="M6 21C6 17.6863 8.68629 15 12 15C15.3137 15 18 17.6863 18 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        </svg>
        <span class="nav-label">Profile</span>
    </a>
</nav>

<style>
/* ===== Bottom Navigation Bar (Netflix/Spotify Premium Style) ===== */
.bottom-nav {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-around;
    align-items: center;
    background: rgba(10, 10, 10, 0.85);
    border-top: 1px solid rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    box-shadow: 0 -2px 16px rgba(0, 0, 0, 0.4);
    padding: 8px 0 calc(8px + env(safe-area-inset-bottom));
    z-index: 200;
}

.bottom-nav .nav-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 16px;
    border-radius: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    min-width: 64px;
    text-align: center;
    position: relative;
    text-decoration: none;
}

.bottom-nav .nav-icon {
    width: 26px;
    height: 26px;
    color: rgba(255, 255, 255, 0.5);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    stroke-width: 1.8;
}

.bottom-nav .nav-label {
    font-size: 11px;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.5);
    letter-spacing: 0.2px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Active State - Minimalist Netflix/Spotify Style */
.bottom-nav .nav-item.active .nav-icon {
    color: #fff;
    transform: translateY(-2px);
}

.bottom-nav .nav-item.active .nav-label {
    color: #fff;
    font-weight: 600;
}

/* Simple active indicator line (like Spotify) */
.bottom-nav .nav-item.active::before {
    content: '';
    position: absolute;
    top: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 32px;
    height: 3px;
    background: linear-gradient(90deg, rgba(42, 108, 255, 1), rgba(255, 51, 102, 1));
    border-radius: 0 0 3px 3px;
    animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateX(-50%) translateY(-3px);
    }
    to {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
}

/* Hover Effects (Desktop Only) */
@media (hover: hover) {
    .bottom-nav .nav-item:hover .nav-icon {
        color: rgba(255, 255, 255, 0.8);
        transform: translateY(-1px);
    }
    
    .bottom-nav .nav-item:hover .nav-label {
        color: rgba(255, 255, 255, 0.8);
    }
    
    .bottom-nav .nav-item.active:hover .nav-icon {
        transform: translateY(-2px) scale(1.05);
    }
}

/* Tap Feedback (Mobile) */
.bottom-nav .nav-item:active {
    transform: scale(0.95);
}

/* Add bottom padding to body to prevent content from being hidden */
body {
    padding-bottom: 72px;
}

/* Responsive adjustments */
@media (min-width: 768px) {
    .bottom-nav {
        display: none;
    }
    
    body {
        padding-bottom: 0;
    }
}

@media (max-width: 767px) {
    .bottom-nav {
        padding: 6px 0 calc(6px + env(safe-area-inset-bottom));
    }
    
    .bottom-nav .nav-item {
        padding: 8px 12px;
        min-width: 56px;
        gap: 4px;
    }
    
    .bottom-nav .nav-icon {
        width: 24px;
        height: 24px;
    }
    
    .bottom-nav .nav-label {
        font-size: 10px;
    }
    
    .bottom-nav .nav-item.active::before {
        width: 28px;
        height: 2.5px;
    }
}

/* Extra small devices */
@media (max-width: 360px) {
    .bottom-nav .nav-item {
        padding: 6px 8px;
        min-width: 48px;
        gap: 3px;
    }
    
    .bottom-nav .nav-icon {
        width: 22px;
        height: 22px;
    }
    
    .bottom-nav .nav-label {
        font-size: 9px;
    }
    
    .bottom-nav .nav-item.active::before {
        width: 24px;
    }
}
</style>

<script>
// ===== Bottom Navigation Active State Controller =====
(() => {
    document.addEventListener("DOMContentLoaded", () => {
        const navItems = document.querySelectorAll(".bottom-nav .nav-item");
        const path = window.location.pathname;
        const currentPage = path.substring(path.lastIndexOf('/') + 1) || "index.php";
        const urlParams = new URLSearchParams(window.location.search);
        const contentType = urlParams.get('type') || 'movie';
        
        // Debug logging
        console.log('Nav Debug - Current Page:', currentPage, 'Content Type:', contentType);
        
        navItems.forEach(item => {
            const page = item.getAttribute("data-page");
            let isActive = false;
            
            // Match current page to nav item
            switch(page) {
                case "shots":
                    isActive = (currentPage === "index.php" || currentPage === "" || currentPage === "shots.php");
                    break;
                case "movies":
                    isActive = (
                        currentPage === "movies.php" || 
                        currentPage === "movie-details.php" ||
                        ((currentPage === "download.php" || currentPage === "whatsapp-watch.php") && contentType !== "series" && contentType !== "episode")
                    );
                    break;
                case "tv-series":
                    isActive = (
                        currentPage === "tv-series.php" || 
                        currentPage === "series-details.php" ||
                        ((currentPage === "download.php" || currentPage === "whatsapp-watch.php") && (contentType === "series" || contentType === "episode"))
                    );
                    break;
                case "collections":
                    isActive = (currentPage === "collections.php" || currentPage === "collection-details.php");
                    break;
                case "profile":
                    isActive = (currentPage === "profile.php");
                    break;
            }
            
            console.log('Nav Debug - Checking:', page, 'isActive:', isActive);
            
            if (isActive) {
                item.classList.add("active");
            }
        });
    });
})();
</script>

<!-- Analytics Tracker -->
<script src="<?php echo BASE_URL; ?>/js/tracker.js"></script>

<!-- Social Bar Ad (Global - Non-intrusive floating notification) -->
<?php 
if (function_exists('renderSocialBar')) {
    renderSocialBar();
}
?>
