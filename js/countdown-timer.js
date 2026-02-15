class CountdownTimer {
    constructor(options = {}) {
        this.duration = options.duration || 10; // Default 10 seconds
        this.onTick = options.onTick || (() => {});
        this.onComplete = options.onComplete || (() => {});
        this.onStart = options.onStart || (() => {});
        
        this.timeLeft = this.duration;
        this.isRunning = false;
        this.interval = null;
        
        // Elements
        this.progressBar = document.createElement('div');
        this.progressBar.className = 'countdown-progress';
        this.timerDisplay = document.createElement('div');
        this.timerDisplay.className = 'countdown-display';
        
        // Container
        this.container = document.createElement('div');
        this.container.className = 'countdown-container';
        this.container.appendChild(this.progressBar);
        this.container.appendChild(this.timerDisplay);
        
        // Add styles
        this.addStyles();
    }

    addStyles() {
        // Only add styles if they don't exist
        if (!document.getElementById('countdown-styles')) {
            const styles = document.createElement('style');
            styles.id = 'countdown-styles';
            styles.textContent = `
                .countdown-container {
                    position: relative;
                    width: 100%;
                    height: 60px;
                    background: #f0f0f0;
                    border-radius: 30px;
                    overflow: hidden;
                    margin: 20px 0;
                }

                .countdown-progress {
                    position: absolute;
                    left: 0;
                    top: 0;
                    height: 100%;
                    width: 100%;
                    background: linear-gradient(to right, #4CAF50, #8BC34A);
                    transform-origin: left;
                    transition: transform 0.95s linear;
                }

                .countdown-display {
                    position: absolute;
                    width: 100%;
                    height: 100%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #fff;
                    font-size: 24px;
                    font-weight: bold;
                    text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
                    z-index: 1;
                }

                @media (max-width: 768px) {
                    .countdown-container {
                        height: 50px;
                    }
                    .countdown-display {
                        font-size: 20px;
                    }
                }

                @media (max-width: 480px) {
                    .countdown-container {
                        height: 40px;
                    }
                    .countdown-display {
                        font-size: 18px;
                    }
                }
            `;
            document.head.appendChild(styles);
        }
    }

    mount(targetElement) {
        if (typeof targetElement === 'string') {
            targetElement = document.querySelector(targetElement);
        }
        if (!targetElement) {
            throw new Error('Target element not found');
        }
        targetElement.appendChild(this.container);
    }

    updateDisplay() {
        const progress = (this.timeLeft / this.duration) * 100;
        this.progressBar.style.transform = `scaleX(${progress / 100})`;
        this.timerDisplay.textContent = `${this.timeLeft} seconds remaining`;
    }

    start() {
        if (this.isRunning) return;

        this.isRunning = true;
        this.timeLeft = this.duration;
        this.onStart();
        this.updateDisplay();

        // Use requestAnimationFrame for smoother progress bar animation
        let lastTime = Date.now();
        const animate = () => {
            if (!this.isRunning) return;

            const currentTime = Date.now();
            const deltaTime = (currentTime - lastTime) / 1000;
            lastTime = currentTime;

            this.timeLeft = Math.max(0, this.timeLeft - deltaTime);
            this.updateDisplay();
            this.onTick(Math.ceil(this.timeLeft));

            if (this.timeLeft <= 0) {
                this.complete();
            } else {
                requestAnimationFrame(animate);
            }
        };

        requestAnimationFrame(animate);

        // Also use setInterval as a backup for precise second updates
        this.interval = setInterval(() => {
            if (this.timeLeft <= 0) {
                clearInterval(this.interval);
            }
        }, 1000);
    }

    stop() {
        this.isRunning = false;
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }

    reset() {
        this.stop();
        this.timeLeft = this.duration;
        this.updateDisplay();
    }

    complete() {
        this.stop();
        this.timeLeft = 0;
        this.updateDisplay();
        this.onComplete();
    }

    destroy() {
        this.stop();
        if (this.container.parentNode) {
            this.container.parentNode.removeChild(this.container);
        }
    }
}

// Usage example:
/*
const timer = new CountdownTimer({
    duration: 10, // 10 seconds
    onTick: (seconds) => {
        console.log(`${seconds} seconds remaining`);
    },
    onComplete: () => {
        console.log('Countdown complete!');
        // Enable download button here
    },
    onStart: () => {
        console.log('Countdown started');
        // Disable download button here
    }
});

// Mount timer to an element
timer.mount('#timer-container');

// Start the countdown
timer.start();
*/