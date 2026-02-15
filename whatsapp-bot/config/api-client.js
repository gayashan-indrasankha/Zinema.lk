/**
 * WhatsApp Bot - API Client
 * Handles communication with the API Bridge on Namecheap
 */

const https = require('https');
const http = require('http');
require('dotenv').config();

const API_URL = process.env.API_URL || 'https://zinema.lk/api/whatsapp';
const API_KEY = process.env.API_KEY || '';

/**
 * Make an API request to the bridge
 * @param {string} endpoint - API endpoint (e.g., 'validate-token.php')
 * @param {object} data - Request body data
 * @returns {Promise<object>} - API response
 */
async function apiRequest(endpoint, data = {}) {
    return new Promise((resolve, reject) => {
        const url = new URL(`${API_URL}/${endpoint}`);
        const isHttps = url.protocol === 'https:';
        const client = isHttps ? https : http;

        const postData = JSON.stringify(data);

        const options = {
            hostname: url.hostname,
            port: url.port || (isHttps ? 443 : 80),
            path: url.pathname,
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Content-Length': Buffer.byteLength(postData),
                'X-Api-Key': API_KEY
            },
            timeout: 30000
        };

        const req = client.request(options, (res) => {
            let responseData = '';

            res.on('data', (chunk) => {
                responseData += chunk;
            });

            res.on('end', () => {
                try {
                    const parsed = JSON.parse(responseData);
                    if (res.statusCode >= 400) {
                        reject(new Error(parsed.error || `HTTP ${res.statusCode}`));
                    } else {
                        resolve(parsed);
                    }
                } catch (e) {
                    reject(new Error(`Invalid JSON response: ${responseData.substring(0, 100)}`));
                }
            });
        });

        req.on('error', (e) => {
            reject(new Error(`API request failed: ${e.message}`));
        });

        req.on('timeout', () => {
            req.destroy();
            reject(new Error('API request timed out'));
        });

        req.write(postData);
        req.end();
    });
}

/**
 * Validate a token via API
 * @param {string} token - The token to validate
 * @returns {Promise<object>} - Validation result
 */
async function getTokenData(token) {
    try {
        const response = await apiRequest('validate-token.php', { token });
        console.log('🎫 Token Validation Result:', JSON.stringify(response, null, 2));
        return response;
    } catch (error) {
        console.error('API Error (getTokenData):', error.message);
        return { success: false, error: 'api_error', message: error.message };
    }
}

/**
 * Mark a token as used via API
 * @param {number} tokenId - Token ID to mark as used
 * @returns {Promise<boolean>} - Success status
 */
async function markTokenAsUsed(tokenId) {
    try {
        const response = await apiRequest('mark-used.php', { token_id: tokenId });
        return response.success === true;
    } catch (error) {
        console.error('API Error (markTokenAsUsed):', error.message);
        return false;
    }
}

/**
 * Log forwarding activity via API
 * @param {number|null} tokenId - Token ID
 * @param {string} userPhone - User's phone number
 * @param {string} userChatId - User's chat ID
 * @param {string} status - 'success', 'failed', or 'pending'
 * @param {string|null} errorMessage - Error message if failed
 */
async function logForward(tokenId, userPhone, userChatId, status, errorMessage = null) {
    try {
        await apiRequest('log-forward.php', {
            token_id: tokenId,
            user_phone: userPhone,
            user_chat_id: userChatId,
            status: status,
            error_message: errorMessage
        });
    } catch (error) {
        console.error('API Error (logForward):', error.message);
        // Don't throw - logging failure shouldn't break the flow
    }
}

/**
 * Get WhatsApp message ID for content via API
 * @param {string} contentType - 'movie' or 'episode'
 * @param {number} contentId - Content ID
 * @returns {Promise<object|null>} - Message ID data
 */
async function getMessageId(contentType, contentId) {
    try {
        const response = await apiRequest('get-message-id.php', {
            content_type: contentType,
            content_id: contentId
        });
        return response.found ? response : null;
    } catch (error) {
        console.error('API Error (getMessageId):', error.message);
        return null;
    }
}

/**
 * Add or update WhatsApp message ID for content via API
 * @param {string} contentType - 'movie' or 'episode'
 * @param {number} contentId - Content ID
 * @param {string} messageId - WhatsApp message ID
 * @param {string|null} fileName - File name for reference
 * @param {number|null} partNumber - Part number for multi-part movies
 * @returns {Promise<object>} - Result
 */
async function addMessageId(contentType, contentId, messageId, fileName = null, partNumber = null) {
    try {
        const requestData = {
            content_type: contentType,
            content_id: contentId,
            message_id: messageId,
            file_name: fileName
        };

        // Add part_number if provided (for movie parts)
        if (partNumber && partNumber > 0) {
            requestData.part_number = partNumber;
        }

        const response = await apiRequest('add-message-id.php', requestData);
        return response;
    } catch (error) {
        console.error('API Error (addMessageId):', error.message);
        return { success: false, error: error.message };
    }
}

/**
 * Check if a media file needs to be refreshed (> 48 hours since last refresh)
 * @param {string} messageId - WhatsApp message ID
 * @returns {Promise<boolean>} - True if needs refresh
 */
async function needsRefresh(messageId) {
    try {
        const response = await apiRequest('needs-refresh.php', { message_id: messageId });
        return response.needs_refresh !== false;
    } catch (error) {
        console.error('API Error (needsRefresh):', error.message);
        return true; // On error, assume it needs refresh
    }
}

/**
 * Log a media file refresh
 * @param {string} messageId - WhatsApp message ID
 * @param {string} fileName - File name
 * @param {string} fileType - video, document, image
 */
async function logRefresh(messageId, fileName = '', fileType = 'video') {
    try {
        await apiRequest('log-refresh.php', {
            message_id: messageId,
            file_name: fileName,
            file_type: fileType
        });
    } catch (error) {
        console.error('API Error (logRefresh):', error.message);
    }
}

/**
 * Get refresh statistics
 * @returns {Promise<object>} - Stats object with total_files and total_refreshes
 */
async function getRefreshStats() {
    try {
        const response = await apiRequest('refresh-stats.php', {});
        return {
            total_files: response.total_files || 0,
            total_refreshes: response.total_refreshes || 0
        };
    } catch (error) {
        console.error('API Error (getRefreshStats):', error.message);
        return { total_files: 0, total_refreshes: 0 };
    }
}

/**
 * Test API connection
 * @returns {Promise<boolean>} - Connection status
 */
async function testConnection() {
    try {
        // Try a simple validation request with dummy token
        await apiRequest('validate-token.php', { token: 'TEST_CONNECTION' });
        console.log('✅ API Bridge connected successfully!');
        console.log(`   🌐 API URL: ${API_URL}`);
        return true;
    } catch (error) {
        if (error.message.includes('Invalid API key')) {
            console.error('❌ API Connection Failed: Invalid API key');
        } else {
            console.error('❌ API Connection Failed:', error.message);
        }
        return false;
    }
}

module.exports = {
    apiRequest,
    getTokenData,
    markTokenAsUsed,
    logForward,
    getMessageId,
    addMessageId,
    needsRefresh,
    logRefresh,
    getRefreshStats,
    testConnection
};

// ===== NEW METHODS FOR ULTIMATE SYSTEM =====

/**
 * Update cache message ID via API
 */
async function updateCacheMessageId(contentType, contentId, partNumber, cacheMessageId) {
    try {
        await apiRequest('update-cache-message-id.php', {
            content_type: contentType,
            content_id: contentId,
            part_number: partNumber,
            cache_message_id: cacheMessageId
        });
        return true;
    } catch (error) {
        console.error('API Error (updateCacheMessageId):', error.message);
        return false;
    }
}

/**
 * Update forward statistics
 */
async function updateForwardStats(tokenId, hitType) {
    try {
        await apiRequest('update-forward-stats.php', {
            token_id: tokenId,
            hit_type: hitType
        });
    } catch (error) {
        console.error('API Error (updateForwardStats):', error.message);
    }
}

/**
 * Get popular files for cache warming
 */
async function getPopularFiles(limit, botId) {
    try {
        const response = await apiRequest('get-popular-files.php', {
            limit: limit || 50,
            bot_id: botId
        });
        return response;
    } catch (error) {
        console.error('API Error (getPopularFiles):', error.message);
        return { success: false, files: [] };
    }
}

/**
 * Send bot heartbeat
 */
async function sendHeartbeat(botId, queueSize, diskUsageMb, memoryUsageMb) {
    try {
        await apiRequest('bot-heartbeat.php', {
            bot_id: botId,
            queue_size: queueSize,
            disk_usage_mb: diskUsageMb,
            memory_usage_mb: memoryUsageMb
        });
        return true;
    } catch (error) {
        console.error('API Error (sendHeartbeat):', error.message);
        return false;
    }
}

/**
 * Report bot error
 */
async function reportError(botId, errorMessage) {
    try {
        await apiRequest('bot-error.php', {
            bot_id: botId,
            error_message: errorMessage
        });
    } catch (error) {
        console.error('API Error (reportError):', error.message);
    }
}

/**
 * Update daily stats
 */
async function updateDailyStats(botId, successful, failed) {
    try {
        await apiRequest('update-daily-stats.php', {
            bot_id: botId,
            successful: successful,
            failed: failed
        });
    } catch (error) {
        console.error('API Error (updateDailyStats):', error.message);
    }
}

/**
 * Log cache warming operation
 */
async function logCacheWarming(botId, contentType, contentId, partNumber, success, errorMessage = null) {
    try {
        await apiRequest('log-cache-warming.php', {
            bot_id: botId,
            content_type: contentType,
            content_id: contentId,
            part_number: partNumber,
            success: success,
            error_message: errorMessage
        });
    } catch (error) {
        console.error('API Error (logCacheWarming):', error.message);
    }
}

/**
 * Get messages older than X days that need refreshing
 * @param {number} daysOld - Age threshold in days
 * @returns {Promise<object>} - { success, messages: [{content_type, content_id, part_number}] }
 */
async function getAgingMessages(daysOld = 10) {
    try {
        const response = await apiRequest('get-aging-messages.php', {
            days_old: daysOld
        });
        return response;
    } catch (error) {
        console.error('API Error (getAgingMessages):', error.message);
        return { success: false, messages: [] };
    }
}

// Create a client object with request method for HealthMonitor
const apiClient = {
    request: apiRequest,
    getTokenData,
    markTokenAsUsed,
    logForward,
    getMessageId,
    addMessageId,
    needsRefresh,
    logRefresh,
    getRefreshStats,
    testConnection,
    updateCacheMessageId,
    updateForwardStats,
    getPopularFiles,
    sendHeartbeat,
    reportError,
    updateDailyStats,
    logCacheWarming,
    getAgingMessages
};

module.exports = apiClient;

