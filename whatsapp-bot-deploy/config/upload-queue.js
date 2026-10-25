/**
 * Upload Queue with Rate Limiting and Concurrency Control
 * Prevents flooding WhatsApp servers when many files need uploading
 */

const EventEmitter = require('events');

class UploadQueue extends EventEmitter {
    constructor(options = {}) {
        super();
        this.maxConcurrent = options.maxConcurrent || 2;      // Max simultaneous uploads
        this.delayBetweenUploads = options.delayBetweenUploads || 5000;  // 5 second gap
        this.maxQueueSize = options.maxQueueSize || 500;      // Max pending items

        this.queue = [];                  // Pending uploads
        this.activeCount = 0;             // Currently uploading
        this.processing = false;          // Is queue running
        this.stats = {
            processed: 0,
            failed: 0,
            skipped: 0
        };

        // Track what's already in queue to prevent duplicates
        this.inQueue = new Set();
    }

    /**
     * Add an upload task to the queue
     * @param {Object} task - { contentType, contentId, partNumber, chatId, priority }
     * @returns {Promise} - Resolves when upload completes
     */
    enqueue(task) {
        return new Promise((resolve, reject) => {
            const key = `${task.contentType}_${task.contentId}_${task.partNumber || 0}`;

            // Skip if already in queue
            if (this.inQueue.has(key)) {
                console.log(`📋 Already queued: ${key}`);
                this.stats.skipped++;
                resolve({ skipped: true, reason: 'Already in queue' });
                return;
            }

            // Reject if queue is full
            if (this.queue.length >= this.maxQueueSize) {
                console.log(`⚠️ Queue full, rejecting: ${key}`);
                reject(new Error('Upload queue is full'));
                return;
            }

            // Add to queue
            this.inQueue.add(key);
            this.queue.push({
                ...task,
                key,
                priority: task.priority || 0,  // Higher = more important
                addedAt: Date.now(),
                resolve,
                reject
            });

            console.log(`📥 Queued: ${key} (position ${this.queue.length}, priority ${task.priority || 0})`);

            // Sort by priority (higher first), then by addedAt (older first)
            this.queue.sort((a, b) => {
                if (b.priority !== a.priority) return b.priority - a.priority;
                return a.addedAt - b.addedAt;
            });

            // Start processing if not already
            this.processQueue();
        });
    }

    /**
     * Process the queue with rate limiting
     */
    async processQueue() {
        if (this.processing) return;
        this.processing = true;

        console.log(`🔄 Upload queue processing started (${this.queue.length} items)`);

        while (this.queue.length > 0) {
            // Wait if at max concurrency
            while (this.activeCount >= this.maxConcurrent) {
                await this.sleep(500);
            }

            // Get next task
            const task = this.queue.shift();
            if (!task) break;

            // Process in background (don't await, allows concurrency)
            this.processTask(task);

            // Delay before starting next
            if (this.queue.length > 0) {
                await this.sleep(this.delayBetweenUploads);
            }
        }

        this.processing = false;
        console.log(`✅ Upload queue empty. Stats: ${this.stats.processed} processed, ${this.stats.failed} failed, ${this.stats.skipped} skipped`);
    }

    /**
     * Process a single upload task
     */
    async processTask(task) {
        this.activeCount++;
        console.log(`📤 Processing: ${task.key} (${this.activeCount}/${this.maxConcurrent} active)`);

        try {
            // Call the upload function (set via setUploadHandler)
            if (!this.uploadHandler) {
                throw new Error('No upload handler set');
            }

            const result = await this.uploadHandler(task);
            this.stats.processed++;
            task.resolve(result);

            console.log(`✅ Completed: ${task.key}`);
            this.emit('completed', task, result);

        } catch (error) {
            this.stats.failed++;
            console.error(`❌ Failed: ${task.key} - ${error.message}`);
            task.reject(error);
            this.emit('failed', task, error);
        } finally {
            this.activeCount--;
            this.inQueue.delete(task.key);
        }
    }

    /**
     * Set the function that performs the actual upload
     * @param {Function} handler - async (task) => result
     */
    setUploadHandler(handler) {
        this.uploadHandler = handler;
    }

    /**
     * Get current queue status
     */
    getStatus() {
        return {
            queueLength: this.queue.length,
            activeCount: this.activeCount,
            maxConcurrent: this.maxConcurrent,
            processing: this.processing,
            stats: { ...this.stats }
        };
    }

    /**
     * Clear the entire queue
     */
    clear() {
        const cleared = this.queue.length;
        this.queue.forEach(task => {
            task.reject(new Error('Queue cleared'));
        });
        this.queue = [];
        this.inQueue.clear();
        console.log(`🧹 Cleared ${cleared} items from upload queue`);
        return cleared;
    }

    /**
     * Boost priority for a specific content
     */
    boostPriority(contentType, contentId, partNumber) {
        const key = `${contentType}_${contentId}_${partNumber || 0}`;
        const task = this.queue.find(t => t.key === key);
        if (task) {
            task.priority += 10;  // Boost priority
            this.queue.sort((a, b) => {
                if (b.priority !== a.priority) return b.priority - a.priority;
                return a.addedAt - b.addedAt;
            });
            console.log(`⬆️ Boosted priority for ${key} to ${task.priority}`);
            return true;
        }
        return false;
    }

    sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = UploadQueue;
