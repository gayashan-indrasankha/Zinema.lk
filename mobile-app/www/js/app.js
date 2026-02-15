/**
 * Zinema.lk Mobile App - Main Application Logic
 * Handles: app initialization, update checking, native bridge
 */

const APP_VERSION = '1.0.0';
const API_BASE = 'https://zinema.lk/api';
let updateUrl = '';

/**
 * Initialize app on load
 */
document.addEventListener('DOMContentLoaded', async () => {
    console.log('[Zinema App] Starting v' + APP_VERSION);

    // Set the mobile app cookie/marker so the server knows we're in the app
    document.cookie = 'zinema_app=1; path=/; max-age=31536000';

    // Check for updates
    await checkForUpdate();

    // Initialize ad system
    initAds();
});

/**
 * Check for app updates
 */
async function checkForUpdate() {
    try {
        const response = await fetch(`${API_BASE}/app-update.php?v=${APP_VERSION}`);
        const data = await response.json();

        if (data.needs_update) {
            updateUrl = data.download_url;
            const banner = document.getElementById('updateBanner');
            const message = document.getElementById('updateMessage');

            if (data.force_update) {
                message.textContent = `Critical update required! (v${data.latest_version})`;
                banner.style.display = 'block';
                // Block app usage for forced updates
                document.getElementById('splash').innerHTML = `
                    <h1>Update Required</h1>
                    <p>Please update to continue using Zinema.lk</p>
                    <p style="margin-top: 12px; color: #aaa; font-size: 12px;">${data.changelog}</p>
                `;
            } else {
                message.textContent = `New version ${data.latest_version} available! ${data.changelog}`;
                banner.style.display = 'block';

                // Auto-hide after 10 seconds if not forced
                setTimeout(() => {
                    banner.style.display = 'none';
                }, 10000);
            }
        }
    } catch (e) {
        console.log('[Zinema App] Update check failed:', e.message);
        // Don't block the app if update check fails
    }
}

/**
 * Download the update APK
 */
function downloadUpdate() {
    if (updateUrl) {
        // Use Capacitor Browser plugin to open download URL
        if (window.Capacitor && window.Capacitor.Plugins.Browser) {
            window.Capacitor.Plugins.Browser.open({ url: updateUrl });
        } else {
            window.open(updateUrl, '_system');
        }
    }
}

/**
 * Initialize ad system based on subscription status
 */
function initAds() {
    // Get stored auth token
    const token = localStorage.getItem('zinema_token');

    if (token) {
        // Check subscription status
        checkSubscription(token);
    } else {
        // No token = not logged in = show ads
        enableAds();
    }
}

/**
 * Check if user has active subscription
 */
async function checkSubscription(token) {
    try {
        const response = await fetch(`${API_BASE}/check-subscription.php`, {
            headers: {
                'Authorization': 'Bearer ' + token,
                'X-Zinema-App': '1'
            }
        });
        const data = await response.json();

        if (data.success && data.is_subscribed) {
            console.log('[Zinema App] User is subscribed - no ads');
            disableAds();
        } else {
            console.log('[Zinema App] User is NOT subscribed - showing ads');
            enableAds();
        }
    } catch (e) {
        console.log('[Zinema App] Subscription check failed:', e.message);
        enableAds(); // Default to showing ads
    }
}

/**
 * Enable Start.io + Unity Ads
 */
function enableAds() {
    window.ZINEMA_SHOW_ADS = true;
    console.log('[Zinema App] Ads enabled (Start.io)');

    // Start.io handles all ad types natively from the Android plugin

    // Notify native layer to start showing ads
    if (window.Capacitor && window.Capacitor.Plugins.ZinemaAds) {
        window.Capacitor.Plugins.ZinemaAds.enableAds();
    }
}

/**
 * Disable ads for subscribers
 */
function disableAds() {
    window.ZINEMA_SHOW_ADS = false;
    console.log('[Zinema App] Ads disabled (subscriber)');

    if (window.Capacitor && window.Capacitor.Plugins.ZinemaAds) {
        window.Capacitor.Plugins.ZinemaAds.disableAds();
    }
}

/**
 * Show interstitial ad via Start.io (called from website JS via bridge)
 * Called every 3rd movie click
 */
window.showInterstitialAd = function () {
    if (!window.ZINEMA_SHOW_ADS) return Promise.resolve();

    return new Promise((resolve) => {
        if (window.Capacitor && window.Capacitor.Plugins.ZinemaAds) {
            window.Capacitor.Plugins.ZinemaAds.showInterstitial()
                .then(() => resolve())
                .catch(() => resolve()); // Don't block if ad fails
        } else {
            resolve();
        }
    });
};

/**
 * Show rewarded video ad via Start.io (called before movie playback)
 * Returns true if user watched the full ad
 */
window.showRewardedAd = function () {
    if (!window.ZINEMA_SHOW_ADS) return Promise.resolve(true);

    return new Promise((resolve) => {
        if (window.Capacitor && window.Capacitor.Plugins.ZinemaAds) {
            window.Capacitor.Plugins.ZinemaAds.showRewarded()
                .then((result) => resolve(result.rewarded))
                .catch(() => resolve(true)); // Allow access if ad fails
        } else {
            resolve(true);
        }
    });
};

/**
 * Handle back button (Android)
 */
document.addEventListener('backbutton', (e) => {
    // If we can go back in WebView history, do that
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Otherwise exit app
        if (window.Capacitor && window.Capacitor.Plugins.App) {
            window.Capacitor.Plugins.App.exitApp();
        }
    }
});

/**
 * Store JWT token when user logs in via website
 * The website calls this function after successful login
 */
window.onMobileLogin = function (token, user) {
    localStorage.setItem('zinema_token', token);
    localStorage.setItem('zinema_user', JSON.stringify(user));

    // Re-check subscription to update ad display
    checkSubscription(token);
};

/**
 * Clear stored data on logout
 */
window.onMobileLogout = function () {
    localStorage.removeItem('zinema_token');
    localStorage.removeItem('zinema_user');
    enableAds();
};
