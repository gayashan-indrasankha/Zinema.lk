<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/csrf_helper.php';

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

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : null;

// Fetch shots with TikTok-style smart algorithm
$shots_feed = [];

// Build SQL query based on login status
if ($current_user_id) {
    // User is logged in - fetch unwatched videos with smart algorithm
    // STEP 1: Try to fetch videos the user hasn't watched yet
    $sql = "SELECT 
        s.id, 
        s.title, 
        s.description,
        s.shot_video_file,
        s.fb_share_url,
        s.linked_content_type,
        s.linked_content_id,
        s.created_at,
        COUNT(DISTINCT sl.user_id) as like_count,
        COUNT(DISTINCT sc.id) as comment_count,
        MAX(CASE WHEN user_sl.user_id = $current_user_id THEN 1 ELSE 0 END) as user_has_liked,
        MAX(CASE WHEN user_sf.user_id = $current_user_id THEN 1 ELSE 0 END) as user_has_saved
    FROM shots s
    LEFT JOIN shot_likes sl ON s.id = sl.shot_id
    LEFT JOIN shot_comments sc ON s.id = sc.shot_id
    LEFT JOIN shot_likes user_sl ON s.id = user_sl.shot_id AND user_sl.user_id = $current_user_id
    LEFT JOIN user_favorites user_sf ON s.id = user_sf.shot_id AND user_sf.user_id = $current_user_id
    LEFT JOIN user_views uv ON s.id = uv.shot_id AND uv.user_id = $current_user_id
    WHERE uv.id IS NULL
    GROUP BY s.id
    ORDER BY RAND()";
    
    $result = $conn->query($sql);
    
    // If user has watched all videos, fallback to showing all videos randomly
    if ($result && $result->num_rows == 0) {
        // FALLBACK: User has watched everything, show all videos in random order
        $sql = "SELECT 
            s.id, 
            s.title, 
            s.description,
            s.shot_video_file,
            s.fb_share_url,
            s.linked_content_type,
            s.linked_content_id,
            s.created_at,
            COUNT(DISTINCT sl.user_id) as like_count,
            COUNT(DISTINCT sc.id) as comment_count,
            MAX(CASE WHEN user_sl.user_id = $current_user_id THEN 1 ELSE 0 END) as user_has_liked,
            MAX(CASE WHEN user_sf.user_id = $current_user_id THEN 1 ELSE 0 END) as user_has_saved
        FROM shots s
        LEFT JOIN shot_likes sl ON s.id = sl.shot_id
        LEFT JOIN shot_comments sc ON s.id = sc.shot_id
        LEFT JOIN shot_likes user_sl ON s.id = user_sl.shot_id AND user_sl.user_id = $current_user_id
        LEFT JOIN user_favorites user_sf ON s.id = user_sf.shot_id AND user_sf.user_id = $current_user_id
        GROUP BY s.id
        ORDER BY RAND()";
        
        $result = $conn->query($sql);
    }
} else {
    // User not logged in - simple query with random order
    $sql = "SELECT 
        s.id, 
        s.title, 
        s.description,
        s.shot_video_file,
        s.fb_share_url,
        s.linked_content_type,
        s.linked_content_id,
        s.created_at,
        COUNT(DISTINCT sl.user_id) as like_count,
        COUNT(DISTINCT sc.id) as comment_count,
        0 as user_has_liked,
        0 as user_has_saved
    FROM shots s
    LEFT JOIN shot_likes sl ON s.id = sl.shot_id
    LEFT JOIN shot_comments sc ON s.id = sc.shot_id
    GROUP BY s.id
    ORDER BY RAND()";
    
    $result = $conn->query($sql);
}

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $shots_feed[] = $row;
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="description" content="Zinema.lk - Watch trending movie shots TikTok style">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    <link rel="icon" href="<?php echo BASE_URL; ?>/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>/assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/shots-style.css">
    <link rel="stylesheet" href="css/profile-style.css?v=2.0">
    <link rel="stylesheet" href="css/responsive.css">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include '_top_nav.php'; ?>
    
    <!-- App Container -->
    <div class="app-container">
        <!-- Shots Container -->
        <div class="shots-container">
            <?php if (!empty($shots_feed)): ?>
                <?php foreach ($shots_feed as $shot): ?>
                    <div class="shot-item" data-shot-id="<?php echo $shot['id']; ?>" data-fb-share-url="<?php echo htmlspecialchars($shot['fb_share_url'] ?? ''); ?>" data-cached-url="<?php echo htmlspecialchars($shot['shot_video_file'] ?? ''); ?>">
                        <!-- Shot Video Player -->
                        <div class="shot-video-player">
                            <!-- Loading spinner -->
                            <div class="video-loading-spinner" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 5; display: none;">
                                <div style="width: 40px; height: 40px; border: 3px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: spin 1s linear infinite;"></div>
                            </div>
                            <video 
                                data-src="<?php echo htmlspecialchars($shot['shot_video_file'] ?? ''); ?>" 
                                preload="none"
                                loop
                                muted 
                                playsinline
                                webkit-playsinline
                                poster=""
                            ></video>
                        </div>
                        
                        <!-- Shot Overlay Container -->
                        <div class="shot-overlay-container">
                            <!-- Top Overlay -->
                            <div class="shot-overlay-top">
                                <div class="volume-control-container">
                                    <div class="unmute-label">Unmute</div>
                                    <button class="volume-btn">
                                        <span class="vol-icon">
                                            <!-- Muted Icon by default -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="volume-icon-muted">
                                                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                                                <line x1="23" y1="9" x2="17" y2="15"></line>
                                                <line x1="17" y1="9" x2="23" y2="15"></line>
                                            </svg>
                                            <!-- Unmuted Icon (hidden by default) -->
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="volume-icon-unmuted" style="display: none;">
                                                <polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"></polygon>
                                                <path d="M19.07 4.93a10 10 0 0 1 0 14.14M15.54 8.46a5 5 0 0 1 0 7.07"></path>
                                            </svg>
                                        </span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Bottom Overlay (Details + Actions) -->
                            <div class="shot-overlay-bottom">
                                <!-- Shot Details Overlay -->
                                <div class="shot-details">
                                    <!-- Shot Title -->
                                    <h1><?php echo htmlspecialchars($shot['title']); ?></h1>
                                    
                                    <!-- Shot Description/Caption with Expand/Collapse -->
                                    <p class="shot-description" data-full-text="<?php echo htmlspecialchars($shot['description']); ?>">
                                        <span class="description-text"><?php echo htmlspecialchars($shot['description']); ?></span><button class="description-more-btn" style="display: none;">more</button>
                                    </p>
                                    
                                    <!-- Dynamic Watch Button (Movie or Series) -->
                                    <?php if ($shot['linked_content_type'] === 'movie'): ?>
                                        <a href="movie/<?php echo $shot['linked_content_id']; ?>/watch" class="watch-movie-btn">
                                            <span>▶</span>
                                            Watch Full Movie
                                        </a>
                                    <?php elseif ($shot['linked_content_type'] === 'series'): ?>
                                        <a href="series/<?php echo $shot['linked_content_id']; ?>/watch" class="watch-movie-btn">
                                            <span>▶</span>
                                            Watch Full TV Series
                                        </a>
                                    <?php elseif ($shot['linked_content_type'] === 'collection'): ?>
                                        <a href="collection-details.php?id=<?php echo $shot['linked_content_id']; ?>" class="watch-movie-btn">
                                            <span>▶</span>
                                            View Collection
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <!-- TikTok-Style Sidebar Actions -->
                                <div class="shot-sidebar-actions">
                                    <!-- Like Button -->
                                    <button class="action-btn like-btn<?php echo ($shot['user_has_liked'] ?? 0) ? ' liked' : ''; ?>" data-shot-id="<?php echo $shot['id']; ?>" onclick="toggleLike(<?php echo $shot['id']; ?>, this)">
                                        <span class="action-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>
                                            </svg>
                                        </span>
                                        <span class="action-count like-count"><?php echo $shot['like_count']; ?></span>
                                    </button>

                                    <!-- Comment Button -->
                                    <button class="action-btn comment-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="openComments(<?php echo $shot['id']; ?>)">
                                        <span class="action-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                                            </svg>
                                        </span>
                                        <span class="action-count comment-count"><?php echo $shot['comment_count']; ?></span>
                                    </button>

                                    <!-- Favorite Button -->
                                    <button class="action-btn favorite-btn<?php echo ($shot['user_has_saved'] ?? 0) ? ' favorited' : ''; ?>" data-shot-id="<?php echo $shot['id']; ?>" onclick="toggleFavorite(<?php echo $shot['id']; ?>, this)">
                                        <span class="action-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                                            </svg>
                                        </span>
                                        <span class="action-label">Save</span>
                                    </button>

                                    <!-- Share Button -->
                                    <button class="action-btn share-btn" data-shot-id="<?php echo $shot['id']; ?>" onclick="shareShot(<?php echo $shot['id']; ?>, '<?php echo htmlspecialchars($shot['title']); ?>')">
                                        <span class="action-icon">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                                            </svg>
                                        </span>
                                        <span class="action-label">Share</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-shots">
                    <p>No shots available at the moment. Check back soon! 🎬</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>

    <script>
        // ===== Check if user is logged in (from PHP session) =====
        const isUserLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        // ===== FB Video API Configuration =====
        const FB_VIDEO_API_URL = 'https://fb-video-api-2nte.onrender.com';
        
        // In-memory cache for current session
        const cdnUrlCache = new Map();
        
        // Track which videos are currently loading
        const loadingVideos = new Set();
        
        // CDN Cache expiry: 36 hours in milliseconds
        const CDN_CACHE_EXPIRY_MS = 36 * 60 * 60 * 1000;
        
        // ===== ADAPTIVE QUALITY CONFIGURATION =====
        // Speed test with 5-minute caching + background re-test
        const SPEED_THRESHOLD_MBPS = 5; // < 5 Mbps = SD, >= 5 Mbps = HD (HD video needs ~5 Mbps)
        const SPEED_CACHE_DURATION_MS = 5 * 60 * 1000; // 5 minutes
        let detectedQuality = null;
        let speedTestDone = false;
        let speedTestPromise = null;
        
        // Check if we have a valid cached result
        function getCachedSpeed() {
            try {
                const cached = sessionStorage.getItem('speed_test');
                if (cached) {
                    const { quality, timestamp } = JSON.parse(cached);
                    const age = Date.now() - timestamp;
                    if (age < SPEED_CACHE_DURATION_MS) {
                        console.log(`📶 Using cached speed (${Math.round(age/1000)}s old): ${quality.toUpperCase()}`);
                        return quality;
                    } else {
                        console.log(`📶 Cache expired (${Math.round(age/1000)}s old), will re-test in background`);
                        return { quality, expired: true };
                    }
                }
            } catch (e) {}
            return null;
        }
        
        // Simple and reliable speed detection
        async function runSpeedTest() {
            try {
                // Method 1: Use browser's built-in network detection (INSTANT, no download needed)
                if (navigator.connection && navigator.connection.downlink) {
                    const mbps = navigator.connection.downlink;
                    const quality = mbps >= 4.0 ? 'hd' : 'sd';
                    
                    console.log(`📶 Network speed: ${mbps.toFixed(1)} Mbps → ${quality.toUpperCase()} (instant detection)`);
                    
                    sessionStorage.setItem('speed_test', JSON.stringify({
                        quality,
                        speed: mbps.toFixed(1),
                        timestamp: Date.now()
                    }));
                    
                    return quality;
                }
                
                // Method 2: No network API available - default to HD (most users have good connections)
                console.log(`📶 No network detection available → HD (optimistic default)`);
                
                sessionStorage.setItem('speed_test', JSON.stringify({
                    quality: 'hd',
                    speed: 'unknown',
                    timestamp: Date.now()
                }));
                
                return 'hd';
                
            } catch (error) {
                console.warn('Speed detection failed:', error.message);
                return 'hd'; // Optimistic default
            }
        }
        
        // Main detection function
        async function detectConnectionSpeed() {
            const cached = getCachedSpeed();
            
            // Valid cache - use it immediately
            if (cached && typeof cached === 'string') {
                detectedQuality = cached;
                speedTestDone = true;
                return cached;
            }
            
            // Expired cache - default to HD, re-test in background
            if (cached && cached.expired) {
                detectedQuality = 'hd'; // Optimistic default (don't trust old cache)
                speedTestDone = true;
                // Background re-test (doesn't block)
                setTimeout(() => {
                    console.log('📶 Background speed re-test...');
                    runSpeedTest().then(newQuality => {
                        if (newQuality !== detectedQuality) {
                            console.log(`📶 Quality updated: ${detectedQuality} → ${newQuality} (will apply to new videos)`);
                            detectedQuality = newQuality;
                        }
                    });
                }, 2000); // Wait 2 sec so user sees first video first
                return 'hd'; // Return HD for immediate use
            }
            
            // No cache - must run test now (blocks first video)
            console.log('📶 First visit - running speed test...');
            const quality = await runSpeedTest();
            detectedQuality = quality;
            speedTestDone = true;
            return quality;
        }
        
        // Start speed detection
        speedTestPromise = detectConnectionSpeed();
        
        // ===== CACHE VERSION - Clear old cache when this changes =====
        const CACHE_VERSION = 'v3_downlink'; // Change this to invalidate all old caches
        
        // Clear old cache on version change (one-time migration)
        if (localStorage.getItem('cdn_cache_version') !== CACHE_VERSION) {
            console.log('🔄 Clearing old CDN cache (new version)...');
            // Clear all cdn_ entries
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith('cdn_')) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => localStorage.removeItem(key));
            localStorage.setItem('cdn_cache_version', CACHE_VERSION);
            console.log(`🗑️ Cleared ${keysToRemove.length} cached URLs`);
        }

        // ===== LocalStorage CDN Cache (persists across page loads, expires after 36 hours) =====
        function getCachedCdnUrl(shotId) {
            try {
                const cached = localStorage.getItem(`cdn_${shotId}`);
                if (cached) {
                    const { url, timestamp, quality } = JSON.parse(cached);
                    // Check if cache is still valid (36 hours) AND matches current quality
                    const currentQuality = detectedQuality || 'sd';
                    if (Date.now() - timestamp < CDN_CACHE_EXPIRY_MS && quality === currentQuality) {
                        return url;
                    } else {
                        // Expired or quality mismatch, remove it
                        localStorage.removeItem(`cdn_${shotId}`);
                        if (quality !== currentQuality) {
                            console.log(`🔄 Cache quality mismatch for shot ${shotId}: cached=${quality}, needed=${currentQuality}`);
                        }
                    }
                }
            } catch (e) {
                console.warn('LocalStorage error:', e);
            }
            return null;
        }
        
        function setCachedCdnUrl(shotId, url) {
            try {
                const currentQuality = detectedQuality || 'sd';
                localStorage.setItem(`cdn_${shotId}`, JSON.stringify({
                    url: url,
                    timestamp: Date.now(),
                    quality: currentQuality
                }));
            } catch (e) {
                console.warn('LocalStorage save error:', e);
            }
        }

        // ===== Dynamic CDN URL Fetching with Buffer-Before-Play =====
        async function loadVideoWithFreshCdn(shotItem) {
            const video = shotItem.querySelector('video');
            if (!video) return;
            
            const shotId = shotItem.dataset.shotId;
            const fbShareUrl = shotItem.dataset.fbShareUrl;
            const fallbackUrl = shotItem.dataset.cachedUrl || video.dataset.src;
            const spinner = shotItem.querySelector('.video-loading-spinner');
            
            // Skip if already loading or already has a working src
            if (loadingVideos.has(shotId)) return;
            if (video.src && !video.error) return;
            
            // Check in-memory cache first (fastest)
            if (cdnUrlCache.has(shotId)) {
                applyVideoSource(video, cdnUrlCache.get(shotId), spinner);
                return;
            }
            
            // Check localStorage cache (faster than API call)
            const localCached = getCachedCdnUrl(shotId);
            if (localCached) {
                console.log(`💾 Using localStorage cache for shot ${shotId}`);
                cdnUrlCache.set(shotId, localCached);
                applyVideoSource(video, localCached, spinner);
                return;
            }
            
            loadingVideos.add(shotId);
            if (spinner) spinner.style.display = 'block';
            
            // Prepare video for smooth transition (hidden until ready)
            video.style.opacity = '0';
            
            try {
                // If we have a FB share URL, try to get fresh CDN
                if (fbShareUrl && fbShareUrl.includes('facebook.com')) {
                    console.log(`🔄 Fetching fresh CDN for shot ${shotId}...`);
                    
                    // Add timeout to API call (10 seconds max)
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 10000);
                    
                    const response = await fetch(
                        `${FB_VIDEO_API_URL}/api/extract?url=${encodeURIComponent(fbShareUrl)}`,
                        { 
                            method: 'GET',
                            headers: { 'Accept': 'application/json' },
                            signal: controller.signal
                        }
                    );
                    clearTimeout(timeoutId);
                    
                    const data = await response.json();
                    
                    // Debug: Show full API response
                    console.log(`📡 API Response for shot ${shotId}:`, data);
                    
                    // Check if API was updated (new format has hd_url/sd_url)
                    if (data.success && data.url && !data.hd_url) {
                        console.warn('⚠️ API NOT UPDATED! Still using old format with single URL');
                        console.warn('⚠️ Please redeploy fb-video-api to Render.com');
                    }
                    
                    if (data.success && (data.hd_url || data.sd_url)) {
                        // Select quality based on detected connection speed
                        const quality = detectedQuality || 'sd';
                        const selectedUrl = (quality === 'hd' && data.hd_url) ? data.hd_url : (data.sd_url || data.hd_url);
                        
                        console.log(`✅ CDN obtained for shot ${shotId} [${quality.toUpperCase()}] (${data.duration_ms}ms)`);
                        console.log(`   HD: ${data.hd_url ? 'Yes' : 'No'}, SD: ${data.sd_url ? 'Yes' : 'No'}`);
                        console.log(`   Selected: ${quality === 'hd' ? 'HD' : 'SD'} URL`);
                        
                        // Cache in memory and localStorage
                        cdnUrlCache.set(shotId, selectedUrl);
                        setCachedCdnUrl(shotId, selectedUrl);
                        
                        applyVideoSource(video, selectedUrl, spinner);
                        loadingVideos.delete(shotId);
                        return;
                    }
                    
                    // Fallback for OLD API format (single url field)
                    if (data.success && data.url) {
                        console.log(`✅ CDN obtained (old API format) for shot ${shotId}`);
                        cdnUrlCache.set(shotId, data.url);
                        setCachedCdnUrl(shotId, data.url);
                        applyVideoSource(video, data.url, spinner);
                        loadingVideos.delete(shotId);
                        return;
                    }
                }
                
                // Fallback to cached URL from database
                if (fallbackUrl) {
                    console.log(`⚠️ Using fallback URL for shot ${shotId}`);
                    cdnUrlCache.set(shotId, fallbackUrl);
                    applyVideoSource(video, fallbackUrl, spinner);
                }
                
            } catch (error) {
                console.error(`❌ API Error for shot ${shotId}:`, error.message);
                
                // Fallback to cached URL on error
                if (fallbackUrl) {
                    console.log(`⚠️ Falling back to cached URL for shot ${shotId}`);
                    applyVideoSource(video, fallbackUrl, spinner);
                }
            }
            
            loadingVideos.delete(shotId);
        }
        
        // ===== Apply Video Source with Buffer-Before-Play & Smooth Transition =====
        function applyVideoSource(video, url, spinner) {
            video.src = url;
            video.preload = 'auto'; // Start buffering immediately
            
            // Wait for enough buffer before showing (eliminates stuttering)
            const onCanPlay = () => {
                // Smooth fade-in transition
                video.style.transition = 'opacity 0.3s ease';
                video.style.opacity = '1';
                if (spinner) spinner.style.display = 'none';
            };
            
            // Use canplaythrough for smoother experience (more buffer)
            video.addEventListener('canplaythrough', onCanPlay, { once: true });
            
            // Fallback: Show after 3 seconds even if not fully buffered
            setTimeout(() => {
                if (video.style.opacity === '0') {
                    video.style.transition = 'opacity 0.3s ease';
                    video.style.opacity = '1';
                    if (spinner) spinner.style.display = 'none';
                }
            }, 3000);
        }
        
        // ===== AGGRESSIVE Pre-fetching: 5 ahead + 2 behind =====
        function prefetchNextVideos(currentShotItem, count = 5) {
            // Prefetch NEXT videos (more important)
            let sibling = currentShotItem.nextElementSibling;
            let fetched = 0;
            
            while (sibling && fetched < count) {
                if (sibling.classList.contains('shot-item')) {
                    const video = sibling.querySelector('video');
                    if (video && !video.src) {
                        loadVideoWithFreshCdn(sibling);
                        fetched++;
                    }
                }
                sibling = sibling.nextElementSibling;
            }
            
            // Also prefetch 2 PREVIOUS videos (for scroll-up)
            sibling = currentShotItem.previousElementSibling;
            fetched = 0;
            
            while (sibling && fetched < 2) {
                if (sibling.classList.contains('shot-item')) {
                    const video = sibling.querySelector('video');
                    if (video && !video.src) {
                        loadVideoWithFreshCdn(sibling);
                        fetched++;
                    }
                }
                sibling = sibling.previousElementSibling;
            }
        }

        // ===== Global Mute State (Perfect TikTok Experience) =====
        let isMasterMuted = true;

        // ===== Helper: Unmute Master Audio and Update UI =====
        function unmuteMasterAudio(videoElement) {
            if (isMasterMuted && videoElement) {
                isMasterMuted = false;
                videoElement.muted = false;
                
                // Apply unmute to all videos
                document.querySelectorAll('.shot-item video').forEach(v => {
                    v.muted = false;
                });
                
                // Update UI for all volume controls
                updateVolumeUI();
            }
        }

        // ===== Update Volume UI (Icon + Label) =====
        function updateVolumeUI() {
            document.querySelectorAll('.shot-item').forEach(shotItem => {
                const volIcon = shotItem.querySelector('.vol-icon');
                const unmuteLabel = shotItem.querySelector('.unmute-label');
                
                if (volIcon && unmuteLabel) {
                    const mutedIcon = volIcon.querySelector('.volume-icon-muted');
                    const unmutedIcon = volIcon.querySelector('.volume-icon-unmuted');
                    
                    if (isMasterMuted) {
                        // Muted state
                        if (mutedIcon) mutedIcon.style.display = 'block';
                        if (unmutedIcon) unmutedIcon.style.display = 'none';
                        unmuteLabel.classList.remove('hidden');
                    } else {
                        // Unmuted state
                        if (mutedIcon) mutedIcon.style.display = 'none';
                        if (unmutedIcon) unmutedIcon.style.display = 'block';
                        unmuteLabel.classList.add('hidden');
                    }
                }
            });
        }

        // ===== Toggle Mute/Unmute when Volume Button is Clicked =====
        function toggleVolume(event) {
            event.stopPropagation(); // Prevent video pause/play
            
            isMasterMuted = !isMasterMuted;
            
            // Apply to all videos
            document.querySelectorAll('.shot-item video').forEach(v => {
                v.muted = isMasterMuted;
            });
            
            // Update UI
            updateVolumeUI();
        }


        // ========================================
        // Modal Management (New AJAX Implementation)
        // ========================================
        
        // Login Modal Functions
        function showLoginModal() {
            const modal = document.getElementById('login-modal');
            modal.style.display = 'flex';
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
            document.getElementById('login-form').reset();
        }

        // Signup Modal Functions
        function showSignupModal() {
            const modal = document.getElementById('signup-modal');
            modal.style.display = 'flex';
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
            document.getElementById('signup-form').reset();
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

        // ========================================
        // AJAX Form Submission
        // ========================================
        
        // Login Form AJAX Handler
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            if (loginForm) {
                loginForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const submitBtn = this.querySelector('.btn-login-submit');
                    const btnText = submitBtn.querySelector('.btn-text');
                    const btnLoader = submitBtn.querySelector('.btn-loader');
                    const errorDiv = document.getElementById('login-error');
                    
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
                        
                        
                        if (data.success) {
                            // Success - Reload page to update UI state (preserved scroll handled by browser usually, or we can improve)
                            // We reload so PHP re-renders "Like" buttons with "liked" class correctly.
                            window.location.reload(); 
                        } else {
                            // Reset button state only on error
                            submitBtn.disabled = false;
                            btnText.style.display = 'inline';
                            btnLoader.style.display = 'none';
                            
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
                    const errorDiv = document.getElementById('signup-error');
                    
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
                        
                        if (data.success) {
                            // Success - Reload page
                            window.location.reload();
                        } else {
                            // Reset button state on error
                            submitBtn.disabled = false;
                            btnText.style.display = 'inline';
                            btnLoader.style.display = 'none';
                            
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

        // ========================================
        // Forgot Password WhatsApp Function
        // ========================================
        function openForgotPasswordWhatsApp() {
            // Get email from login form
            const emailInput = document.getElementById('login-email');
            const email = emailInput ? emailInput.value.trim() : '';
            
            // WhatsApp number (94753800728)
            const whatsappNumber = '94753800728';
            
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

        // ========================================
        // Focus Trap for Accessibility
        // ========================================
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


        // ===== Toggle Like/Unlike =====
        function toggleLike(shotId, button) {
            // Check if user is logged in
            if (!isUserLoggedIn) {
                showLoginModal();
                return;
            }

            if (event) {
                event.preventDefault();
            }
            
            const likeCountSpan = button.querySelector('.like-count');
            
            fetch('api/like_shot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `shot_id=${shotId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'liked') {
                        button.classList.add('liked');
                        likeCountSpan.textContent = data.like_count;
                    } else {
                        button.classList.remove('liked');
                        likeCountSpan.textContent = data.like_count;
                    }
                } else {
                    console.error('Error:', data.message);
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // ===== Open Comments =====
        function openComments(shotId) {
            // Check if user is logged in
            if (!isUserLoggedIn) {
                showLoginModal();
                return;
            }

            // Show the comment modal
            const modal = document.getElementById('comment-modal');
            modal.style.display = 'flex';

            // Set the shot_id in the hidden input
            document.getElementById('comment-shot-id').value = shotId;

            // Fetch and display comments
            fetch(`api/get_comments.php?shot_id=${shotId}`)
                .then(response => response.json())
                .then(data => {
                    const commentList = document.getElementById('comment-list');
                    commentList.innerHTML = '';

                    if (data.status === 'success' && data.comments.length > 0) {
                        data.comments.forEach(comment => {
                            const commentItem = document.createElement('div');
                            commentItem.className = 'comment-item';
                            
                            // Calculate time ago
                            const timeAgo = getTimeAgo(comment.created_at);
                            
                            // Get first letter of username for avatar
                            const avatarLetter = comment.username.charAt(0).toUpperCase();
                            
                            commentItem.innerHTML = `
                                <div class="comment-avatar">${avatarLetter}</div>
                                <div class="comment-content">
                                    <div class="comment-header">
                                        <strong class="comment-user">${comment.username}</strong>
                                        <span class="comment-time">${timeAgo}</span>
                                    </div>
                                    <p class="comment-text">${comment.comment_text}</p>
                                </div>
                            `;
                            commentList.appendChild(commentItem);
                        });
                    } else {
                        commentList.innerHTML = `
                            <div class="empty-comments">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>
                                </svg>
                                <h4>No comments yet</h4>
                                <p>Be the first to comment!</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading comments:', error);
                    alert('Failed to load comments.');
                });
        }

        // Helper function to calculate time ago
        function getTimeAgo(timestamp) {
            const now = new Date();
            const commentTime = new Date(timestamp);
            const diffInSeconds = Math.floor((now - commentTime) / 1000);

            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + 'd ago';
            return Math.floor(diffInSeconds / 604800) + 'w ago';
        }

        // ===== Toggle Favorite =====
        function toggleFavorite(shotId, button) {
            // Check if user is logged in
            if (!isUserLoggedIn) {
                showLoginModal();
                return;
            }

            if (event) {
                event.preventDefault();
            }
            
            fetch('api/favorite_shot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `shot_id=${shotId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    if (data.action === 'favorited') {
                        button.classList.add('favorited');
                    } else {
                        button.classList.remove('favorited');
                    }
                } else {
                    console.error('Error:', data.message);
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Failed to update favorite. Please try again.');
            });
        }

        // ===== Share Shot =====
        function shareShot(shotId, title) {
            const url = window.location.origin + '/index.php#shot-' + shotId;
            
            if (navigator.share) {
                navigator.share({
                    title: title + ' - Zinema.lk',
                    text: 'Check out this movie on Zinema.lk!',
                    url: url
                })
                .then(() => console.log('Shared successfully'))
                .catch((error) => console.log('Error sharing:', error));
            } else {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy:', err);
                    prompt('Copy this link:', url);
                });
            }
        }

        // ===== DOM Ready =====
        document.addEventListener('DOMContentLoaded', function() {
            // ===== TikTok-Style View Tracking =====
            const viewedShots = new Set(); // Track which shots have been recorded as viewed
            const VIEW_THRESHOLD = 5; // Seconds of playback required to count as "viewed"
            
            // Function to record a view via API
            function recordView(shotId) {
                // Only record for logged-in users
                if (!isUserLoggedIn) return;
                
                // Prevent duplicate recordings
                if (viewedShots.has(shotId)) return;
                
                // Mark as recorded immediately to prevent race conditions
                viewedShots.add(shotId);
                
                // Send view to backend
                fetch('api/record_view.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `shot_id=${shotId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log('✅ View recorded for shot:', shotId);
                    } else {
                        console.warn('⚠️ Failed to record view:', data.message);
                    }
                })
                .catch(error => {
                    console.error('❌ Error recording view:', error);
                });
            }
            
            // ===== IntersectionObserver for Scroll-to-Play =====

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target.querySelector('video');
                    const shotItem = entry.target;
                    
                    if (!video) return;
                    
                    if (entry.isIntersecting) {
                        // Shot is visible on screen
                        // Step 1: Load fresh CDN URL if needed
                        loadVideoWithFreshCdn(shotItem).then(() => {
                            // Step 2: Play the video
                            video.muted = isMasterMuted;
                            video.play().catch(err => console.log('Autoplay failed:', err));
                            
                            // Step 3: Pre-fetch next videos for smooth scrolling (5 ahead + 2 behind)
                            prefetchNextVideos(shotItem, 5);
                            
                            // Step 4: Track view after threshold (existing logic)
                            const shotId = shotItem.dataset.shotId;
                            if (shotId && !viewedShots.has(shotId)) {
                                // Start tracking playback time
                                video._viewStartTime = Date.now();
                            }
                        });
                    } else {
                        // Shot left the screen - pause
                        video.pause();
                        
                        // Check if we should record view
                        const shotId = shotItem.dataset.shotId;
                        if (video._viewStartTime) {
                            const watchTime = (Date.now() - video._viewStartTime) / 1000;
                            if (watchTime >= VIEW_THRESHOLD && !viewedShots.has(shotId)) {
                                recordView(shotId);
                            }
                            video._viewStartTime = null;
                        }
                    }
                });
            }, {
                threshold: 0.5 // Trigger when 50% of the video is visible
            });

            // ===== Smart Click Handling (Tap-to-Unmute + Video Play/Pause) =====
            document.querySelectorAll('.shot-item').forEach(shotItem => {
                // Observe for scroll-to-play
                observer.observe(shotItem);
                
                // Set up volume button click listener
                const volumeBtn = shotItem.querySelector('.volume-btn');
                if (volumeBtn) {
                    volumeBtn.addEventListener('click', toggleVolume);
                }
                
                // ===== Description Expand/Collapse (TikTok-Style Manual Truncation) =====
                const descPara = shotItem.querySelector('.shot-description');
                if (descPara) {
                    const descText = descPara.querySelector('.description-text');
                    const moreBtn = descPara.querySelector('.description-more-btn');
                    const fullText = descPara.getAttribute('data-full-text');
                    
                    if (descText && moreBtn && fullText) {
                        // Wait for rendering
                        setTimeout(() => {
                            // Calculate if text needs truncation
                            const lineHeight = parseFloat(window.getComputedStyle(descPara).lineHeight);
                            const maxHeight = lineHeight * 2; // 2 lines max
                            
                            descText.textContent = fullText;
                            
                            // Check if text overflows
                            if (descPara.scrollHeight > maxHeight + 5) {
                                // Text needs truncation
                                let text = fullText;
                                
                                // Show button first to measure its width
                                moreBtn.style.display = 'inline';
                                moreBtn.textContent = 'more';
                                
                                // Truncate until text + "..." + button fits in 2 lines
                                while (descPara.scrollHeight > maxHeight + 5 && text.length > 0) {
                                    text = text.substring(0, text.length - 1);
                                    descText.textContent = text.trimEnd() + '...';
                                }
                                
                                // Store truncated text
                                const truncatedText = text.trimEnd();
                                
                                // Toggle on click
                                moreBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    
                                    if (descPara.classList.contains('expanded')) {
                                        // Collapse back
                                        descPara.classList.remove('expanded');
                                        descText.textContent = truncatedText + '...';
                                        moreBtn.textContent = 'more';
                                        moreBtn.style.display = 'inline';
                                    } else {
                                        // Expand
                                        descPara.classList.add('expanded');
                                        descText.textContent = fullText + ' ';
                                        moreBtn.textContent = 'less';
                                    }
                                });
                            }
                        }, 100);
                    }
                }
                
                // ===== View Tracking: Record view after 5 seconds of playback =====
                const video = shotItem.querySelector('video');
                const shotId = shotItem.dataset.shotId;
                
                if (video && shotId) {
                    video.addEventListener('timeupdate', function() {
                        // Check if video has played for VIEW_THRESHOLD seconds
                        if (this.currentTime >= VIEW_THRESHOLD) {
                            recordView(shotId);
                            // Remove listener after recording to save resources
                            this.removeEventListener('timeupdate', arguments.callee);
                        }
                    });
                }
                
                // Add sophisticated click handler for Single Tap (Play/Pause) vs Double Tap (Like)
                let lastClickTime = 0;
                let clickTimeout = null;

                shotItem.addEventListener('click', function(event) {
                    const currentTime = new Date().getTime();
                    const tapLength = currentTime - lastClickTime;
                    const video = this.querySelector('video');
                    
                    // Check what was clicked
                    const clickedElement = event.target;
                    const isButton = clickedElement.closest('.like-btn, .comment-btn, .favorite-btn, .share-btn, .watch-movie-btn, .action-btn, .volume-btn, .volume-control-container, .description-more-btn');
                    
                    if (isButton) {
                        // User clicked a button - immediate action, just unmute master if needed
                        unmuteMasterAudio(video);
                        return; // Let button's own listener handle the rest
                    }

                    // User clicked video area
                    if (tapLength < 300 && tapLength > 0) {
                        // --- DOUBLE TAP DETECTED ---
                        clearTimeout(clickTimeout);
                        
                        // 1. Unmute (just in case)
                        unmuteMasterAudio(video);
                        
                        // 2. Trigger Like
                        const likeBtn = shotItem.querySelector('.like-btn');
                        const shotId = shotItem.dataset.shotId;
                        if (likeBtn && shotId) {
                            toggleLike(shotId, likeBtn);
                        }
                        
                        // 3. Visual "Heart Pop" Animation
                        createHeartAnimation(event.clientX, event.clientY);
                        
                    } else {
                        // --- POTENTIAL SINGLE TAP ---
                        // Wait to see if a second tap comes
                        clickTimeout = setTimeout(function() {
                            // Single Tap confirmed (timeout finished)
                            unmuteMasterAudio(video);
                            if (video) {
                                if (video.paused) {
                                    video.play().catch(err => console.log('Play failed:', err));
                                } else {
                                    video.pause();
                                }
                            }
                        }, 300); // 300ms delay to wait for double tap
                    }
                    
                    lastClickTime = currentTime;
                });

        // Helper Function for Heart Animation
        function createHeartAnimation(x, y) {
            const heart = document.createElement('i');
            heart.className = 'fas fa-heart like-heart-pop';
            heart.style.top = y + 'px';
            heart.style.left = x + 'px';
            document.body.appendChild(heart);
            
            // Remove after animation completes (800ms)
            setTimeout(() => {
                heart.remove();
            }, 800);
        }
            });
            
            // Initialize volume UI on page load
            updateVolumeUI();
            
            // ===== Load first video AFTER speed test completes =====
            const firstShot = document.querySelector('.shot-item');
            if (firstShot) {
                // Wait for speed test to complete first
                speedTestPromise.then(() => {
                    console.log(`🎬 Loading first video with ${detectedQuality?.toUpperCase() || 'SD'} quality`);
                    
                    loadVideoWithFreshCdn(firstShot).then(() => {
                        console.log('🎬 First video loaded');
                        
                        // Auto-play the first video after loading
                        const firstVideo = firstShot.querySelector('video');
                        if (firstVideo && firstVideo.src) {
                            firstVideo.muted = isMasterMuted;
                            firstVideo.play().catch(err => console.log('First video autoplay failed:', err));
                        }
                        
                        // Pre-fetch next few videos (5 ahead for smooth experience)
                        prefetchNextVideos(firstShot, 5);
                    });
                });
            }

            // ===== Comment Modal Event Listeners =====
            // Close button
            document.getElementById('close-comments').addEventListener('click', function() {
                document.getElementById('comment-modal').style.display = 'none';
            });

            // Close modal when clicking outside the drawer
            document.getElementById('comment-modal').addEventListener('click', function(event) {
                if (event.target.id === 'comment-modal') {
                    this.style.display = 'none';
                }
            });

            // Comment form submission
            document.getElementById('comment-form').addEventListener('submit', function(event) {
                event.preventDefault();

                const shotId = document.getElementById('comment-shot-id').value;
                const commentText = document.getElementById('comment-text-input').value.trim();

                if (!commentText) {
                    alert('Please enter a comment.');
                    return;
                }

                // Submit comment to API
                fetch('api/add_comment.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `shot_id=${shotId}&comment_text=${encodeURIComponent(commentText)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // Clear input
                        document.getElementById('comment-text-input').value = '';

                        // Reload comments
                        openComments(shotId);
                    } else {
                        alert(data.message || 'Failed to add comment.');
                    }
                })
                .catch(error => {
                    console.error('Error adding comment:', error);
                    alert('Failed to add comment.');
                });
            });
        });
    </script>

    <!-- Comment Modal/Drawer (TikTok-style) -->
    <div id="comment-modal" class="comment-overlay" style="display:none;">
        <div class="comment-drawer">
            <button id="close-comments" class="close-comments-btn">&times;</button>
            <h3>Comments</h3>
            <div id="comment-list" class="comment-list">
                <!-- Comments will be loaded here dynamically -->
            </div>
            <form id="comment-form" class="comment-form">
                <input type="hidden" id="comment-shot-id" name="shot_id">
                <input type="text" id="comment-text-input" name="comment_text" placeholder="Add a comment..." required autocomplete="off">
                <button type="submit">Post</button>
            </form>
        </div>
    </div>

    <!-- Desktop Navigation Buttons (Up/Down) -->
    <div class="desktop-nav-controls">
        <button id="prev-shot-btn" class="nav-control-btn" title="Previous Video">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"></polyline>
            </svg>
        </button>
        <button id="next-shot-btn" class="nav-control-btn" title="Next Video">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"></polyline>
            </svg>
        </button>
    </div>

    <!-- Login Modal -->
    <div id="login-modal" class="login-overlay" style="display: none;">
        <!-- ... existing login modal content ... -->
        <div class="login-card">
            <button class="close-login-btn" onclick="closeLoginModal()">&times;</button>
            <div class="login-header">
                <h2>🎬 Zinema.lk</h2>
                <p>Welcome back!</p>
            </div>
            
            <!-- Error Message Container -->
            <div id="login-error" class="form-error-message" style="display: none;" role="alert"></div>
            
            <form id="login-form" class="login-modal-form">
                <?php if (function_exists('csrf_token_field')) csrf_token_field(); ?>
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
                <?php if (function_exists('csrf_token_field')) csrf_token_field(); ?>
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
                <?php if (function_exists('csrf_token_field')) csrf_token_field(); ?>
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

    <!-- Desktop Navigation Logic -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const prevBtn = document.getElementById('prev-shot-btn');
            const nextBtn = document.getElementById('next-shot-btn');
            let isScrolling = false;

            // Find the visible shot (closest to center)
            function getCurrentShot() {
                const shots = document.querySelectorAll('.shot-item');
                let closestShot = null;
                let minDiff = Infinity;
                const center = window.innerHeight / 2;

                shots.forEach(shot => {
                    const rect = shot.getBoundingClientRect();
                    const shotCenter = rect.top + rect.height / 2;
                    const diff = Math.abs(shotCenter - center);
                    
                    if (diff < minDiff) {
                        minDiff = diff;
                        closestShot = shot;
                    }
                });
                return closestShot;
            }

            function scrollToShot(direction) {
                if (isScrolling) return;
                
                const currentShot = getCurrentShot();
                if (!currentShot) return;

                let targetShot;
                if (direction === 'next') {
                    targetShot = currentShot.nextElementSibling;
                } else {
                    targetShot = currentShot.previousElementSibling;
                }

                if (targetShot && targetShot.classList.contains('shot-item')) {
                    isScrolling = true;
                    targetShot.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    
                    // Reset scrolling flag after animation
                    setTimeout(() => {
                        isScrolling = false;
                    }, 800);
                }
            }

            if (prevBtn) prevBtn.addEventListener('click', () => scrollToShot('prev'));
            if (nextBtn) nextBtn.addEventListener('click', () => scrollToShot('next'));

            // Keyboard Shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    scrollToShot('prev');
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    scrollToShot('next');
                }
            });

            // Forgot Password Form AJAX Handler
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
