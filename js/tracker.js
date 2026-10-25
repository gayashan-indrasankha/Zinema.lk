// =================== CineDrive Analytics Tracker ===================
(() => {
    function sendEvent(event, value) {
        const data = new URLSearchParams({event, csrf_token: document.querySelector('meta[name="csrf-token"]')?.content || ''});
        if (value !== undefined) data.set('value', value);
        navigator.sendBeacon(new URL('/api/track.php', location.origin), data);
    }
    document.addEventListener("DOMContentLoaded", () => {
        // 1. Log a page view on every page load
        sendEvent('page_view');

        // 2. Track watch time
        const playerDialog = document.getElementById("player");
        if (playerDialog) {
            let watchStartTime = null;

            // Use MutationObserver to detect when the dialog is opened/closed
            const observer = new MutationObserver((mutationsList) => {
                for (const mutation of mutationsList) {
                    if (mutation.attributeName === 'open') {
                        const is_open = playerDialog.hasAttribute('open');
                        if (is_open) {
                            // Modal opened: record start time
                            watchStartTime = Date.now();
                        } else if (watchStartTime) {
                            // Modal closed: calculate duration and send to server
                            const watchEndTime = Date.now();
                            const durationInSeconds = Math.round((watchEndTime - watchStartTime) / 1000);

                            // Only log if watch time is meaningful (e.g., > 5 seconds)
                            if (durationInSeconds > 5) {
                                sendEvent('watch_time', durationInSeconds);
                            }
                            watchStartTime = null; // Reset timer
                        }
                    }
                }
            });

            observer.observe(playerDialog, { attributes: true });
        }
    });
})();