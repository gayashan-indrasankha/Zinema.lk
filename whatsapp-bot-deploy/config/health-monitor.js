/**
 * Health Monitor Module
 * Reports bot health status to database for admin dashboard
 */

const os = require('os');

class HealthMonitor {
    constructor(botInstanceId, apiClient, client,resourceGuard=null) {
        this.botInstanceId = botInstanceId;
        this.apiClient = apiClient;
        this.client = client;
        this.resourceGuard=resourceGuard;
        this.startTime = Date.now();
        this.interval = null;
        this.running=false;this.generation=0;
    }

    /**
     * Start sending heartbeats
     */
    start(intervalSeconds = 30) {
        this.stop();
        this.running=true;
        console.log(`💓 Health monitor started (reporting every ${intervalSeconds}s)`);

        // Send initial heartbeat immediately
        this.sendHeartbeat();

        // Then send periodically
        this.interval = setInterval(() => {
            this.sendHeartbeat();
        }, intervalSeconds * 1000);
    }

    /**
     * Stop sending heartbeats
     */
    stop() {
        this.running=false;this.generation++;
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
            console.log('💓 Health monitor stopped');
        }
    }

    /**
     * Send heartbeat to server
     */
    async sendHeartbeat() {
        const generation=this.generation;
        try {
            const healthData = await this.collectHealthData();
            if(!this.running || generation!==this.generation)return;

            const response = await this.apiClient.request('bot-heartbeat.php', {
                bot_id: this.botInstanceId,
                ...healthData
            });

            if (response.success) {
                console.log(`💓 Heartbeat sent: Queue=${healthData.queue_size}, Disk=${healthData.disk_usage_mb}MB`);
            }
        } catch (error) {
            console.error(`❌ Failed to send heartbeat: ${error.message}`);
        }
    }

    /**
     * Collect current health metrics
     */
    async collectHealthData() {
        const uptime = Math.floor((Date.now() - this.startTime) / 1000);
        const memoryUsage = process.memoryUsage();
        const diskUsage = await this.getLocalDiskUsage();

        return {
            queue_size: global.messageQueue ? global.messageQueue.getStatus().size : 0,
            disk_usage_mb: diskUsage,
            memory_usage_mb: ((memoryUsage.rss ?? memoryUsage.heapUsed) / (1024 * 1024)).toFixed(2),
            uptime_seconds: uptime,
            status: this.resourceGuard && !this.resourceGuard.sample().allowed ? 'offline' : 'online',
            bot_phone: this.client?.user?.id ? this.client.user.id.split('@')[0].split(':')[0] : (this.client?.info?.wid?.user || null)
        };
    }

    /**
     * Get local file storage disk usage
     */
    async getLocalDiskUsage() {
        try {
            if (global.fileManager) {
                return await global.fileManager.getTotalDiskUsage();
            }
            return 0;
        } catch {
            return 0;
        }
    }

    /**
     * Report error to server
     */
    async reportError(errorMessage) {
        try {
            await this.apiClient.request('bot-error.php', {
                bot_id: this.botInstanceId,
                error_message: errorMessage
            });
            console.log(`⚠️ Error reported to server`);
        } catch (error) {
            console.error(`❌ Failed to report error: ${error.message}`);
        }
    }

    /**
     * Update daily statistics
     */
    async updateDailyStats(successful, failed) {
        try {
            await this.apiClient.request('update-daily-stats.php', {
                bot_id: this.botInstanceId,
                successful: successful ? 1 : 0,
                failed: failed ? 1 : 0
            });
        } catch (error) {
            console.error(`❌ Failed to update stats: ${error.message}`);
        }
    }
}

module.exports = HealthMonitor;
