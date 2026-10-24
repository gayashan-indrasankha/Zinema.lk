// ZL-18: quarantine before dependency imports, sessions, timers or configuration.
require('../../shared/integration-gate.cjs').blockEntry('whatsapp', module);
/**
 * Health Monitor Module
 * Reports bot health status to database for admin dashboard
 */

const os = require('os');

class HealthMonitor {
    constructor(botInstanceId, apiClient, client) {
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
        this.botInstanceId = botInstanceId;
        this.apiClient = apiClient;
        this.client = client;
        this.startTime = Date.now();
        this.interval = null;
    }

    /**
     * Start sending heartbeats
     */
    start(intervalSeconds = 30) {
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
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
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
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
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
        try {
            const healthData = await this.collectHealthData();

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
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
        const uptime = Math.floor((Date.now() - this.startTime) / 1000);
        const memoryUsage = process.memoryUsage();
        const diskUsage = await this.getLocalDiskUsage();

        return {
            queue_size: global.messageQueue ? global.messageQueue.getStatus().size : 0,
            disk_usage_mb: diskUsage,
            memory_usage_mb: (memoryUsage.heapUsed / (1024 * 1024)).toFixed(2),
            uptime_seconds: uptime,
            status: 'online',
            bot_phone: this.client && this.client.info ? this.client.info.wid.user : null
        };
    }

    /**
     * Get local file storage disk usage
     */
    async getLocalDiskUsage() {
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
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
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
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
        require('../../shared/integration-gate.cjs').assertLive('whatsapp');
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
