    async refresh(manualTrigger = false, notifyChat = null) {
    if (this.isRefreshing) {
        if (notifyChat) await notifyChat.sendMessage('⚠️ Refresh already in progress!');
        return;
    }

    // Time window safety check (only for automatic runs)
    if (!manualTrigger) {
        const now = new Date();
        const currentHour = now.getHours();
        const currentMinute = now.getMinutes();

        // Check if we're in the window: 00:30 to 06:00
        const isInWindow = (currentHour === 0 && currentMinute >= 30) || (currentHour > 0 && currentHour < 6);

        if (!isInWindow) {
            console.log(`⚠️ AutoRefresher: Outside time window (${currentHour}:${currentMinute}). Rescheduling...`);
            this.scheduleNextRun();
            return;
        }

        console.log(`✅ In time window (${currentHour}:${currentMinute}). Starting refresh...`);
    }

    this.isRefreshing = true;
    console.log('🔄 AutoRefresher: Starting cache refresh cycle...');
    const startTime = Date.now();

    if (notifyChat) await notifyChat.sendMessage('🔄 Starting cache refresh cycle...\nFetching ALL media files from storage...');

    try {
        const storageGroupId = process.env.STORAGE_GROUP_ID;
        const storageChat = await client.getChatById(storageGroupId);
        const cacheChat = await client.getChatById(this.cacheGroupId);

        // Import database methods for tracking
        const { needsRefresh, logRefresh, getRefreshStats } = require('./config/database');

        // Fetch ALL messages with pagination
        console.log('📥 Fetching ALL media messages from Storage Group (pagination enabled)...');
        let allMediaMessages = [];
        let batchNumber = 0;
        let hasMore = true;

        while (hasMore) {
            batchNumber++;
            const batchSize = 100;
            const messages = await storageChat.fetchMessages({ limit: batchSize });

            if (messages.length === 0) {
                hasMore = false;
                break;
            }

            const mediaInBatch = messages.filter(m => m.hasMedia && (m.type === 'video' || m.type === 'document' || m.type === 'image'));
            allMediaMessages.push(...mediaInBatch);

            console.log(`📦 Batch ${batchNumber}: Found ${mediaInBatch.length} media files (${allMediaMessages.length} total)`);

            // If we got fewer messages than the batch size, we've reached the end
            if (messages.length < batchSize) {
                hasMore = false;
            } else {
                // Small delay to avoid rate limits
                await new Promise(resolve => setTimeout(resolve, 2000));
            }
        }

        console.log(`✅ Completed fetching. Total batches: ${batchNumber}`);
        console.log(`✅ Total media files found: ${allMediaMessages.length}`);

        if (notifyChat) await notifyChat.sendMessage(`Found ${allMediaMessages.length} media files. Starting refresh...`);

        let refreshedCount = 0;
        let skippedCount = 0;
        let failedCount = 0;

        for (let i = 0; i < allMediaMessages.length; i++) {
            const msg = allMediaMessages[i];
            const messageId = msg.id._serialized;
            const fileName = msg._data?.filename || msg._data?.caption || `file_${i + 1}`;

            try {
                // Check if this file needs refresh
                const shouldRefresh = await needsRefresh(messageId);

                if (!shouldRefresh) {
                    console.log(`⏭️  Skipped [${i + 1}/${allMediaMessages.length}]: ${fileName} (refreshed recently)`);
                    skippedCount++;
                    continue;
                }

                // Forward to cache group
                await msg.forward(this.cacheGroupId);

                // Log the refresh in database
                await logRefresh(messageId, fileName, msg.type);

                refreshedCount++;
                console.log(`✅ Refreshed [${refreshedCount}/${allMediaMessages.length}]: ${fileName}`);

                // Random delay between 3-5 seconds to avoid spam detection
                const delay = 3000 + Math.floor(Math.random() * 2000);
                await new Promise(resolve => setTimeout(resolve, delay));

            } catch (err) {
                console.error(`❌ Failed [${i + 1}/${allMediaMessages.length}]: ${fileName} - ${err.message}`);
                failedCount++;
            }
        }

        // Get database statistics
        const stats = await getRefreshStats();
        const duration = ((Date.now() - startTime) / 1000 / 60).toFixed(2);

        // Print summary report
        console.log(`\n╔════════════════════════════════════════╗`);
        console.log(`║      REFRESH CYCLE COMPLETED           ║`);
        console.log(`╠════════════════════════════════════════╣`);
        console.log(`║ Total Files Checked: ${allMediaMessages.length.toString().padEnd(18)}║`);
        console.log(`║ Refreshed Now: ${refreshedCount.toString().padEnd(23)}║`);
        console.log(`║ Skipped (Recent): ${skippedCount.toString().padEnd(20)}║`);
        console.log(`║ Failed: ${failedCount.toString().padEnd(30)}║`);
        console.log(`║ Duration: ${duration} minutes${' '.repeat(22 - duration.length)}║`);
        console.log(`╠════════════════════════════════════════╣`);
        console.log(`║ Database Total: ${stats.total_files.toString().padEnd(22)}║`);
        console.log(`║ All-Time Refreshes: ${stats.total_refreshes.toString().padEnd(16)}║`);
        console.log(`╚════════════════════════════════════════╝\n`);

        if (notifyChat) {
            await notifyChat.sendMessage(
                `✅ *Refresh Completed*\n\n` +
                `📊 Total Files: ${allMediaMessages.length}\n` +
                `🔄 Refreshed: ${refreshedCount}\n` +
                `⏭️ Skipped: ${skippedCount}\n` +
                `❌ Failed: ${failedCount}\n` +
                `⏱️ Duration: ${duration} min\n\n` +
                `📅 Next cycle in ${process.env.REFRESH_INTERVAL_HOURS || 72} hours`
            );
        }

    } catch (error) {
        console.error('❌ AutoRefresher Error:', error.message);
        if (notifyChat) await notifyChat.sendMessage(`❌ Error: ${error.message}`);
    } finally {
        this.isRefreshing = false;

        // If this was an automatic run (not manual), schedule the next one
        if (!manualTrigger) {
            // Calculate how many days we need to wait to satisfy the interval
            const daysToAdd = Math.round(this.refreshInterval / (24 * 60 * 60 * 1000));

            // Reschedule manually to ensure alignment
            const nextTarget = new Date();
            nextTarget.setDate(nextTarget.getDate() + daysToAdd);
            nextTarget.setHours(0, 30, 0, 0);

            const delay = nextTarget.getTime() - Date.now();
            console.log(`📅 Cycle complete. Sleeping for ${daysToAdd} days.`);
            console.log(`📅 Next Auto-Refresh: ${nextTarget.toLocaleString()}`);

            this.timerId = setTimeout(() => this.refresh(), delay);
        }
    }
}
