class TrailerFeed {
    constructor(options) {
        this.container = options.container;
        this.apiEndpoint = options.apiEndpoint || '/api/trailers.php';
        this.currentUserId = options.userId;
        this.page = 1;
        this.loading = false;
        this.trailers = [];
        this.currentTrailer = null;
        this.hasMore = true;
        this.viewTimers = new Map();
        
        this.init();
    }
    
    async init() {
        await this.loadTrailers();
        const selected = Number(new URLSearchParams(window.location.search).get('id'));
        if (Number.isSafeInteger(selected) && selected > 0) {
            let item = this.container.querySelector(`[data-trailer-id="${selected}"]`);
            if (!item) {
                try {
                    const response = await fetch(`${this.apiEndpoint}?id=${selected}`);
                    const data = await response.json();
                    if (!response.ok || !data.trailer) throw new Error('Unavailable');
                    this.trailers.unshift(data.trailer);this.renderTrailers();
                    item = this.container.querySelector(`[data-trailer-id="${selected}"]`);
                } catch {
                    this.container.querySelector('.trailer-container').textContent = 'Requested trailer is unavailable.';
                    this.hasMore = false;
                }
            }
            item?.scrollIntoView({block:'start'});
        }
        this.setupIntersectionObserver();
        this.attachEventListeners();
        this.showSwipeInstruction();
    }
    
    async loadTrailers(append = false) {
        if (this.loading || (append && !this.hasMore)) return;
        this.loading = true;
        try {
            const response = await fetch(`${this.apiEndpoint}?page=${this.page}`);
            const data = await response.json();
            if (!response.ok || !Array.isArray(data.trailers)) throw new Error('Unavailable');
            const known = new Set(append ? this.trailers.map(item => Number(item.id)) : []);
            const batch = data.trailers.filter(item => {
                const id=Number(item.id);
                if (!Number.isSafeInteger(id) || id<1 || known.has(id)) return false;
                known.add(id);return true;
            });
            this.trailers = append ? [...this.trailers, ...batch] : batch;
            this.hasMore = data.trailers.length > 0;
            if (!append || batch.length) this.renderTrailers(append,batch);
            if (data.trailers.length) this.page++;
        } catch {
            if (!this.trailers.length) {this.hasMore=false;this.renderTrailers(false,[],'Trailers are temporarily unavailable.');}
        } finally {this.loading=false;}
    }

    renderTrailers(append = false, batch = this.trailers, emptyMessage = 'No trailers available yet.') {
        const html = batch.map(trailer => this.createTrailerHTML(trailer)).join('');
        
        if (append) {
            this.container.querySelector('.trailer-container').insertAdjacentHTML('beforeend', html);
        } else {
            this.container.innerHTML = `
                <div class="trailer-container">
                    ${html || `<p class="trailer-empty">${this.escapeHtml(emptyMessage)}</p>`}
                    <div class="landscape-warning" style="display: none;">
                        <div>
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="white">
                                <path d="M7.5 3.5h9L15 5v14l1.5 1.5h-9L9 19V5L7.5 3.5zm1.5 2v13h6V5.5H9z"/>
                            </svg>
                            <p>Please rotate your device<br>for the best experience</p>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Appending must not reload/reset previously playing videos.
        this.container.querySelectorAll('.trailer-item').forEach(item => {
            const video=item.querySelector('.trailer-video');
            if (!video.dataset.initialized) {this.setupVideoEvents(video);video.dataset.initialized='1';video.load();}
            if (this.observer && !item.dataset.observed) {this.observer.observe(item);item.dataset.observed='1';}
        });
    }

    createTrailerHTML(trailer) {
        return `
            <div class="trailer-item" data-trailer-id="${trailer.id}">
                <video class="trailer-video" 
                       src="${this.escapeAttribute(trailer.trailer_url)}"
                       poster="${this.escapeAttribute(trailer.thumbnail_url)}"
                       playsinline
                       webkit-playsinline
                       preload="metadata"
                       muted controls loop>
                </video>
                
                <div class="trailer-overlay">
                    <div class="trailer-info">
                        <h2 class="trailer-title">${this.escapeHtml(trailer.movie_title)}</h2>
                        <p class="trailer-description">${this.escapeHtml(trailer.description)}</p>
                        <a href="movie-details.php?id=${trailer.movie_id}" class="watch-button">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                            Watch Full Movie
                        </a>
                    </div>
                    
                    <div class="trailer-actions">
                        <button class="action-button ${trailer.user_liked ? 'active' : ''}"
                                data-action="like">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
                            </svg>
                            <span>${trailer.like_count || 0}</span>
                        </button>
                        
                        <button class="action-button ${trailer.user_favorited ? 'active' : ''}"
                                data-action="favorite">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                            </svg>
                            <span>${trailer.user_favorited ? 'Saved' : 'Save'}</span>
                        </button>
                        
                        <button class="action-button" data-action="share">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11c.54.5 1.25.81 2.04.81 1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81C7.5 9.31 6.79 9 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3c.79 0 1.5-.31 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92 1.61 0 2.92-1.31 2.92-2.92s-1.31-2.92-2.92-2.92z"/>
                            </svg>
                            <span>Share</span>
                        </button>
                    </div>
                </div>
                
                <div class="progress-bar">
                    <div class="progress"></div>
                </div>
                
                <div class="loading-overlay">
                    <div class="spinner"></div>
                </div>
            </div>
        `;
    }
    
    setupIntersectionObserver() {
        const options = {
            root: this.container.querySelector('.trailer-container'),
            threshold: 0.8 // 80% visibility required
        };
        
        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const trailer = entry.target;
                const video = trailer.querySelector('.trailer-video');
                
                if (entry.isIntersecting) {
                    this.playVideo(video);
                    this.currentTrailer = trailer;
                } else {
                    this.pauseVideo(video);
                }
            });
        }, options);
        
        this.container.querySelectorAll('.trailer-item').forEach(trailer => {
            this.observer.observe(trailer);
            trailer.dataset.observed = '1';
        });
    }
    
    setupVideoEvents(video) {
        const loadingOverlay = video.parentElement.querySelector('.loading-overlay');
        const progressBar = video.parentElement.querySelector('.progress');
        const fullyBuffered = () => Number.isFinite(video.duration) && video.buffered.length > 0 &&
            video.buffered.end(video.buffered.length-1) >= video.duration-0.05;
        
        video.addEventListener('waiting', () => {
            loadingOverlay.style.display = fullyBuffered() ? 'none' : 'flex';
        });
        // Loop transitions may emit waiting even when the complete local clip is buffered.
        video.addEventListener('loadeddata', () => {loadingOverlay.style.display='none';});
        video.addEventListener('progress', () => {if (fullyBuffered()) loadingOverlay.style.display='none';});
        
        video.addEventListener('playing', () => {
            loadingOverlay.style.display = 'none';
        });
        
        video.addEventListener('error', () => { loadingOverlay.textContent='Video unavailable.';video.controls=true; });
        video.addEventListener('timeupdate', () => {
            const progress = (video.currentTime / video.duration) * 100;
            progressBar.style.width = `${progress}%`;
        });
    }
    
    async playVideo(video) {
        clearTimeout(this.viewTimers.get(video));
        try {
            await video.play();
            this.viewTimers.set(video,setTimeout(() => {
                this.viewTimers.delete(video);
                if (!video.paused && this.currentTrailer===video.parentElement) this.recordView(video.parentElement.dataset.trailerId);
            },3000));
        } catch {
            video.parentElement.querySelector('.loading-overlay').style.display='none';
            video.controls=true;
        }
    }

    pauseVideo(video) {
        video.pause();clearTimeout(this.viewTimers.get(video));this.viewTimers.delete(video);
    }

    attachEventListeners() {
        // Infinite scroll
        const container = this.container.querySelector('.trailer-container');
        container.addEventListener('scroll', () => {
            const items=container.querySelectorAll('.trailer-item');
            const lastTrailer=items[items.length-1];
            if (!lastTrailer || !this.hasMore) return;
            const lastTrailerOffset = lastTrailer.offsetTop + lastTrailer.clientHeight;
            const pageOffset = container.scrollTop + container.clientHeight;
            
            if (pageOffset > lastTrailerOffset - 1000 && !this.loading) {
                this.loadTrailers(true);
            }
        });
        
        // Action buttons
        this.container.addEventListener('click', async (e) => {
            const button = e.target.closest('.action-button');
            if (!button) return;
            
            const trailerId = button.closest('.trailer-item').dataset.trailerId;
            const action = button.dataset.action;
            
            switch (action) {
                case 'like':
                    await this.handleLike(trailerId, button);
                    break;
                case 'favorite':
                    await this.handleFavorite(trailerId, button);
                    break;
                case 'share':
                    this.handleShare(trailerId);
                    break;
            }
        });

        // Touch swipe smoothing: detect quick vertical swipes and snap to next/prev
        let touchStartY = 0;
        let touchStartTime = 0;
        container.addEventListener('touchstart', (ev) => {
            if (!ev.touches || ev.touches.length === 0) return;
            touchStartY = ev.touches[0].clientY;
            touchStartTime = Date.now();
        }, { passive: true });

        container.addEventListener('touchend', (ev) => {
            const touchEndY = (ev.changedTouches && ev.changedTouches[0]) ? ev.changedTouches[0].clientY : null;
            const dt = Date.now() - touchStartTime;
            if (touchEndY === null) return;
            const dy = touchStartY - touchEndY; // positive = swipe up
            const threshold = 60; // minimum px
            const timeThreshold = 300; // ms for a quick swipe
            if (Math.abs(dy) > threshold && dt < 600) {
                // quick swipe; programmatic smooth scroll
                const containerEl = container;
                if (dy > 0) {
                    // swipe up -> next
                    containerEl.scrollBy({ top: containerEl.clientHeight, left: 0, behavior: 'smooth' });
                } else {
                    // swipe down -> previous
                    containerEl.scrollBy({ top: -containerEl.clientHeight, left: 0, behavior: 'smooth' });
                }
            }
        }, { passive: true });
    }
    
    async handleLike(trailerId, button) {
        if (!this.currentUserId) {window.location.href='/profile.php';return;}
        // Prevent duplicate clicks while request is in flight
        if (button.dataset.loading === '1') return;
        const wasActive = button.classList.contains('active');
        const countEl = button.querySelector('span');
        const prevCount = parseInt(countEl.textContent || '0', 10);
        button.dataset.loading = '1';
        button.disabled = true;
        try {
            // Optimistic toggle UI
            const optimisticCount = wasActive ? Math.max(0, prevCount - 1) : prevCount + 1;
            button.classList.toggle('active', !wasActive);
            countEl.textContent = optimisticCount;

            const response = await fetch(`${this.apiEndpoint}?action=like`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ trailer_id: trailerId })
            });

            const data = await response.json();
            if (!response.ok || data.error) throw new Error('Like unavailable');

            // Update with authoritative server values
            button.classList.toggle('active', !!data.liked);
            countEl.textContent = data.count;

            // Pulse animation when liked
            if (data.liked) {
                const svg = button.querySelector('svg');
                svg.classList.add('pulse');
                setTimeout(() => svg.classList.remove('pulse'), 650);
            }
        } catch (error) {
            console.error('Failed to like trailer:', error);
            // Revert optimistic UI on failure
            button.classList.toggle('active', wasActive);
            countEl.textContent = prevCount;
        } finally {
            delete button.dataset.loading;
            button.disabled = false;
        }
    }
    
    async handleFavorite(trailerId, button) {
        if (!this.currentUserId) {window.location.href='/profile.php';return;}
        if (button.dataset.loading === '1') return;
        const wasActive = button.classList.contains('active');
        const span = button.querySelector('span');
        const previousText = span?.textContent;
        button.dataset.loading = '1';
        button.disabled = true;
        try {
            // Optimistic toggle
            button.classList.toggle('active', !wasActive);

            const response = await fetch(`${this.apiEndpoint}?action=favorite`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ trailer_id: trailerId })
            });
            const data = await response.json();
            if (!response.ok || data.error) throw new Error('Favorite unavailable');

            button.classList.toggle('active', !!data.favorited);
            if (span) span.textContent = data.favorited ? 'Saved' : 'Save';

            // Visual pulse when favorited
            if (data.favorited) {
                const svg = button.querySelector('svg');
                svg.classList.add('pulse');
                setTimeout(() => svg.classList.remove('pulse'), 650);
            }
        } catch (error) {
            console.error('Failed to favorite trailer:', error);
            // Revert optimistic state
            button.classList.toggle('active', wasActive);
            if (span) span.textContent = previousText;
        } finally {
            delete button.dataset.loading;
            button.disabled = false;
        }
    }
    
    handleShare(trailerId) {
        const url = this.shareUrl(trailerId);
        if (navigator.share) {
            navigator.share({ title: 'Check out this movie trailer!', url })
                .then(() => this.recordShare(trailerId, 'native'))
                .catch(err => console.warn('Share cancelled', err));
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(url)
                .then(() => {
                    this.recordShare(trailerId, 'clipboard');
                    // small toast feedback
                    alert('Trailer link copied to clipboard');
                })
                .catch(() => this.showShareDialog(trailerId));
        } else {
            this.showShareDialog(trailerId);
        }
    }
    
    async recordView(trailerId) {
        try {
            await fetch(`${this.apiEndpoint}?action=view`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ trailer_id: trailerId })
            });
        } catch (error) {
            console.error('Failed to record view:', error);
        }
    }
    
    async recordShare(trailerId, platform) {
        try {
            await fetch(`${this.apiEndpoint}?action=share`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    trailer_id: trailerId,
                    platform: platform
                })
            });
        } catch (error) {
            console.error('Failed to record share:', error);
        }
    }
    
    showSwipeInstruction() {
        const instruction = document.createElement('div');
        instruction.className = 'swipe-instruction';
        instruction.innerHTML = `
            <svg viewBox="0 0 24 24" fill="white">
                <path d="M7 14l5-5 5 5z"/>
            </svg>
            <p>Swipe up for next trailer</p>
        `;
        
        this.container.appendChild(instruction);
        
        setTimeout(() => {
            instruction.remove();
        }, 3000);
    }
    
    showShareDialog(trailerId) {
        const url = this.shareUrl(trailerId);
        // Fallback copying method
        const input = document.createElement('input');
        input.value = url;
        document.body.appendChild(input);
        input.select();
        try {
            document.execCommand('copy');
            alert('Trailer link copied to clipboard');
            this.recordShare(trailerId, 'copy-fallback');
        } catch (e) {
            alert('Unable to copy link. URL: ' + url);
        }
        input.remove();
    }
    
    shareUrl(trailerId) {return `${window.location.origin}/trailers.php?id=${Number(trailerId)}`;}

    escapeAttribute(value) {return this.escapeHtml(String(value || '')).replace(/"/g,'&quot;').replace(/'/g,'&#39;');}

    escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
}

// Prevent screen sleep during video playback
const wakeLock = {
    lock: null,
    async acquire() {
        try {
            if ('wakeLock' in navigator) {
                this.lock = await navigator.wakeLock.request('screen');
            }
        } catch (err) {
            console.error('Failed to acquire wake lock:', err);
        }
    },
    release() {
        if (this.lock) {
            this.lock.release();
            this.lock = null;
        }
    }
};
