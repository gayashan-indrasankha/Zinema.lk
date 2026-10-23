/**
 * VideoPlayer Component
 * A responsive video player for Doodstream embeds
 */
class VideoPlayer {
    constructor(container, videoId, options = {}) {
        this.container = typeof container === 'string' ? document.querySelector(container) : container;
        this.videoId = videoId;
        this.options = {
            autoplay: false,
            muted: false,
            ...options
        };
        
        this.isLoading = true;
        this.hasError = false;
        
        this.init();
    }

    init() {
        // Create player structure
        this.container.classList.add('video-player');
        this.container.innerHTML = `
            <div class="video-container">
                <div class="video-overlay loading">
                    <div class="loading-spinner"></div>
                </div>
            </div>
        `;

        // Load the video
        this.loadVideo();
    }

    loadVideo() {
        // Create iframe
        const iframe = document.createElement('iframe');
        iframe.allow = 'autoplay; fullscreen; encrypted-media';
        iframe.allowFullscreen = true;

        // Format Doodstream URL
        const videoUrl = this.formatDoodstreamUrl(this.videoId);
        
        // Handle load events
        iframe.onload = () => {
            this.hideLoading();
            this.iframe = iframe;
        };

        iframe.onerror = () => {
            this.showError('Failed to load video. Please try again.');
        };

        // Set source and append iframe
        iframe.src = videoUrl;
        this.container.querySelector('.video-container').appendChild(iframe);

        // Set timeout for slow connections
        this.loadTimeout = setTimeout(() => {
            if (this.isLoading) {
                this.showError('Video is taking too long to load. Please try again.');
            }
        }, 20000);
    }

    formatDoodstreamUrl(videoId) {
        // Remove any existing URL formatting and extract video ID
        const cleanId = videoId.replace(/^(https?:\/\/)?(.*?\/)?([a-zA-Z0-9]+)$/, '$3');
        return `https://dood.wf/e/${cleanId}`;
    }

    hideLoading() {
        this.isLoading = false;
        clearTimeout(this.loadTimeout);
        const overlay = this.container.querySelector('.video-overlay');
        if (overlay) {
            overlay.remove();
        }
    }

    showError(message) {
        this.hasError = true;
        this.isLoading = false;
        clearTimeout(this.loadTimeout);

        const overlay = this.container.querySelector('.video-overlay') || document.createElement('div');
        overlay.className = 'video-overlay error';
        overlay.innerHTML = `
            <div>
                <p>${message}</p>
                <button class="retry-button" onclick="this.closest('.video-player').__player.retry()">
                    Try Again
                </button>
            </div>
        `;

        if (!overlay.parentNode) {
            this.container.querySelector('.video-container').appendChild(overlay);
        }
    }

    retry() {
        const container = this.container.querySelector('.video-container');
        const iframe = container.querySelector('iframe');
        if (iframe) {
            iframe.remove();
        }

        this.isLoading = true;
        this.hasError = false;

        container.innerHTML = `
            <div class="video-overlay loading">
                <div class="loading-spinner"></div>
            </div>
        `;

        this.loadVideo();
    }

    // Clean up method
    destroy() {
        clearTimeout(this.loadTimeout);
        this.container.innerHTML = '';
        this.container.classList.remove('video-player');
        delete this.container.__player;
    }
}

// Add to window for access from HTML
window.VideoPlayer = VideoPlayer;