<?php
/**
 * Ads Management for Zinema.lk
 * - Web: Adsterra ads (existing)
 * - Mobile App: Start.io + Unity Ads (handled natively in Capacitor)
 * - IdeaMart Subscribers: No ads
 */

require_once __DIR__ . '/mobile_detect.php';

// ========================================
// MASTER SWITCH: Set to false to disable ALL ads
// ========================================
define('ADS_ENABLED', true);  // Ads are now enabled

// Check if running inside mobile app (Start.io/Unity handles ads there)
// If in mobile app, skip all Adsterra ad rendering
$IS_MOBILE_APP = isMobileApp();

// Smart Link URL for click monetization (web only)
define('SMART_LINK_URL', 'https://www.effectivegatecpm.com/a9d5n1rn19?key=11ae796dc3f92c308b494e5436e6345f');

/**
 * Render mobile banner ad (320x50)
 */
function renderMobileBannerAd() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return; // Start.io handles ads in mobile app
    ?>
    <div class="ad-container ad-mobile-banner" style="display: flex; justify-content: center; margin: 15px auto; max-width: 320px;">
        <script>
            atOptions = {
                'key' : '1d0586ec88f8a644bf7216ff1f1ce59d',
                'format' : 'iframe',
                'height' : 50,
                'width' : 320,
                'params' : {}
            };
        </script>
        <script src="https://www.highperformanceformat.com/1d0586ec88f8a644bf7216ff1f1ce59d/invoke.js"></script>
    </div>
    <?php
}

/**
 * Render desktop leaderboard ad (728x90)
 */
function renderDesktopBannerAd() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <div class="ad-container ad-desktop-banner" style="display: flex; justify-content: center; margin: 20px auto; max-width: 728px;">
        <script>
            atOptions = {
                'key' : 'e7efa8d8a6307b7d1498941b1c72d52c',
                'format' : 'iframe',
                'height' : 90,
                'width' : 728,
                'params' : {}
            };
        </script>
        <script src="https://www.highperformanceformat.com/e7efa8d8a6307b7d1498941b1c72d52c/invoke.js"></script>
    </div>
    <?php
}

/**
 * Render medium rectangle ad (300x250)
 */
function renderRectangleAd() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <div class="ad-container ad-rectangle" style="display: flex; justify-content: center; margin: 20px auto; max-width: 300px;">
        <script>
            atOptions = {
                'key' : 'c424e6df9df0318133d943b20b05e15d',
                'format' : 'iframe',
                'height' : 250,
                'width' : 300,
                'params' : {}
            };
        </script>
        <script src="https://www.highperformanceformat.com/c424e6df9df0318133d943b20b05e15d/invoke.js"></script>
    </div>
    <?php
}

/**
 * Render responsive banner ad (desktop on large, mobile on small screens)
 */
function renderResponsiveBannerAd() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <div class="ad-container ad-responsive" style="margin: 20px auto;">
        <!-- Desktop Ad (728x90) - Hidden on mobile -->
        <div class="ad-desktop-only" style="display: none;">
            <script>
                atOptions = {
                    'key' : 'e7efa8d8a6307b7d1498941b1c72d52c',
                    'format' : 'iframe',
                    'height' : 90,
                    'width' : 728,
                    'params' : {}
                };
            </script>
            <script src="https://www.highperformanceformat.com/e7efa8d8a6307b7d1498941b1c72d52c/invoke.js"></script>
        </div>
        <!-- Mobile Ad (320x50) - Hidden on desktop -->
        <div class="ad-mobile-only" style="display: flex; justify-content: center;">
            <script>
                atOptions = {
                    'key' : '1d0586ec88f8a644bf7216ff1f1ce59d',
                    'format' : 'iframe',
                    'height' : 50,
                    'width' : 320,
                    'params' : {}
                };
            </script>
            <script src="https://www.highperformanceformat.com/1d0586ec88f8a644bf7216ff1f1ce59d/invoke.js"></script>
        </div>
    </div>
    <style>
        @media (min-width: 768px) {
            .ad-desktop-only { display: flex !important; justify-content: center; }
            .ad-mobile-only { display: none !important; }
        }
    </style>
    <?php
}

/**
 * Get smart link URL (returns empty if ads disabled)
 */
function getSmartLinkUrl() {
    if (!ADS_ENABLED) return '';
    return SMART_LINK_URL;
}

/**
 * Render Social Bar ad (floating notification-style)
 * Non-intrusive, appears at bottom of screen
 */
function renderSocialBar() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <script src="https://pl28454417.effectivegatecpm.com/4b/df/7a/4bdf7a1c069e928795cb0b2deed4af46.js"></script>
    <?php
}

/**
 * Render Popunder ad script
 * Opens a new tab in background - use once per page/session
 */
function renderPopunder() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <script>
        // Load popunder only once per session to prevent history pollution
        // (Adsterra deduplicates server-side anyway, so revenue is unaffected)
        if (!sessionStorage.getItem('_pu')) {
            sessionStorage.setItem('_pu', '1');
            var s = document.createElement('script');
            s.src = 'https://pl28336857.effectivegatecpm.com/98/16/d8/9816d8032d18812ecbc7cbdeed1e9a12.js';
            document.body.appendChild(s);
        }
    </script>
    <?php
}

/**
 * Render Native Banner ad (blends with content - high CTR)
 * Best placed in content sections like movie lists, episode lists
 */
function renderNativeBanner() {
    if (!ADS_ENABLED) return;
    global $IS_MOBILE_APP;
    if ($IS_MOBILE_APP) return;
    ?>
    <div class="ad-container ad-native" style="margin: 20px auto; max-width: 100%;">
        <script async="async" data-cfasync="false" src="https://pl28458506.effectivegatecpm.com/ec880499ab783df664cf5d4b63662ce3/invoke.js"></script>
        <div id="container-ec880499ab783df664cf5d4b63662ce3"></div>
    </div>
    <?php
}
?>
