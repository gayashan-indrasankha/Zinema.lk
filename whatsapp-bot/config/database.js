const mysql = require('mysql2/promise');
require('dotenv').config();

// Database connection pool configuration - optimized for high traffic
const pool = mysql.createPool({
    host: process.env.DB_HOST || 'localhost',
    user: process.env.DB_USER || 'root',
    password: process.env.DB_PASSWORD || '',
    database: process.env.DB_NAME || 'cinedrive',
    waitForConnections: true,
    connectionLimit: 20,
    queueLimit: 50,
    enableKeepAlive: true,
    keepAliveInitialDelay: 10000,
    connectTimeout: 10000
});

// Test database connection
async function testConnection() {
    try {
        const connection = await pool.getConnection();
        console.log('✅ MySQL Database connected successfully!');
        console.log(`   📁 Database: ${process.env.DB_NAME || 'cinedrive'}`);
        connection.release();
        return true;
    } catch (error) {
        console.error('❌ MySQL Connection Error:', error.message);
        return false;
    }
}

/**
 * Get token data with full validation
 * Checks: exists, is_active, is_used, not expired
 * 
 * @param {string} token - The token to validate
 * @returns {Object|null} Token data with validation status
 */
async function getTokenData(token) {
    try {
        const [rows] = await pool.execute(
            `SELECT 
                t.id, 
                t.token, 
                t.content_type, 
                t.content_id, 
                t.message_id,
                t.is_used, 
                t.is_active, 
                t.expires_at,
                t.created_at,
                m.message_id as content_message_id,
                m.file_name
            FROM whatsapp_tokens t
            LEFT JOIN whatsapp_message_ids m ON t.content_type = m.content_type AND t.content_id = m.content_id
            WHERE t.token = ?`,
            [token.toUpperCase()]
        );

        if (rows.length === 0) {
            return { valid: false, error: 'not_found', message: 'Token not found' };
        }

        const tokenData = rows[0];
        const now = new Date();
        const expiresAt = new Date(tokenData.expires_at);

        // Check if token is active
        if (!tokenData.is_active) {
            return { valid: false, error: 'inactive', message: 'Token is no longer active', data: tokenData };
        }

        // Check if token has been used
        if (tokenData.is_used) {
            return { valid: false, error: 'already_used', message: 'Token has already been used', data: tokenData };
        }

        // Check if token has expired
        if (now > expiresAt) {
            return { valid: false, error: 'expired', message: 'Token has expired', data: tokenData };
        }

        // Use content_message_id from whatsapp_message_ids if token's message_id is null
        const messageId = tokenData.message_id || tokenData.content_message_id;

        if (!messageId) {
            return { valid: false, error: 'no_message_id', message: 'No video configured for this content', data: tokenData };
        }

        return {
            valid: true,
            data: {
                ...tokenData,
                message_id: messageId
            }
        };
    } catch (error) {
        console.error('Database query error:', error);
        throw error;
    }
}

/**
 * Mark token as used immediately when processing starts
 * This prevents the token from being reused while forwarding is in progress
 * 
 * @param {number} tokenId - Token ID to mark as used
 * @returns {boolean} Success status
 */
async function markTokenAsUsed(tokenId) {
    try {
        const [result] = await pool.execute(
            'UPDATE whatsapp_tokens SET is_used = 1, used_at = NOW() WHERE id = ? AND is_used = 0',
            [tokenId]
        );
        return result.affectedRows > 0;
    } catch (error) {
        console.error('Failed to mark token as used:', error);
        return false;
    }
}

/**
 * Log forward activity
 * 
 * @param {number|null} tokenId - Token ID (can be null for failed lookups)
 * @param {string} userPhone - User's phone number
 * @param {string} userChatId - User's WhatsApp chat ID
 * @param {string} status - 'success', 'failed', or 'pending'
 * @param {string|null} errorMessage - Error message if failed
 */
async function logForward(tokenId, userPhone, userChatId, status, errorMessage = null) {
    try {
        // Skip logging if tokenId is invalid (0 or null) to avoid foreign key error
        if (!tokenId || tokenId === 0) {
            console.log(`📊 Skipped logging (no valid token): ${status} for ${userPhone}`);
            return;
        }
        await pool.execute(
            `INSERT INTO whatsapp_forward_logs (token_id, user_phone, user_chat_id, status, error_message) 
             VALUES (?, ?, ?, ?, ?)`,
            [tokenId, userPhone, userChatId, status, errorMessage]
        );
    } catch (error) {
        console.error('Failed to log forward activity:', error);
    }
}

/**
 * Get content title for display
 * 
 * @param {string} contentType - 'movie' or 'episode'
 * @param {number} contentId - Content ID
 * @returns {string|null} Content title
 */
async function getContentTitle(contentType, contentId) {
    try {
        let query;
        if (contentType === 'movie') {
            query = 'SELECT title FROM movies WHERE id = ?';
        } else if (contentType === 'episode') {
            query = `SELECT CONCAT(s.title, ' - ', e.title) as title 
                     FROM episodes e 
                     LEFT JOIN series s ON e.series_id = s.id 
                     WHERE e.id = ?`;
        } else {
            return null;
        }

        const [rows] = await pool.execute(query, [contentId]);
        return rows.length > 0 ? rows[0].title : null;
    } catch (error) {
        console.error('Failed to get content title:', error);
        return null;
    }
}

/**
 * Clean up expired tokens (utility function)
 * 
 * @returns {number} Number of deleted tokens
 */
async function cleanupExpiredTokens() {
    try {
        const [result] = await pool.execute(
            'DELETE FROM whatsapp_tokens WHERE expires_at < NOW() AND is_used = 0'
        );
        return result.affectedRows;
    } catch (error) {
        console.error('Failed to cleanup expired tokens:', error);
        return 0;
    }
}

// Legacy functions for backward compatibility
async function getMessageIdByToken(token) {
    const result = await getTokenData(token);
    if (result.valid) {
        return {
            id: result.data.id,
            message_id: result.data.message_id,
            file_name: result.data.file_name,
            description: result.data.content_type
        };
    }
    return null;
}

async function incrementDownloadCount(tokenId) {
    // No-op for new system (tokens are single-use)
    return;
}

async function addToken(token, messageId, fileName = null, fileSize = null, description = null) {
    // This is now handled by PHP, but keep for admin commands
    try {
        const [result] = await pool.execute(
            `INSERT INTO whatsapp_message_ids (content_type, content_id, message_id, file_name) 
             VALUES ('movie', 0, ?, ?)
             ON DUPLICATE KEY UPDATE message_id = ?, file_name = ?, updated_at = NOW()`,
            [messageId, fileName, messageId, fileName]
        );
        return result;
    } catch (error) {
        console.error('Failed to add token:', error);
        throw error;
    }
}

async function getAllTokens() {
    try {
        const [rows] = await pool.execute(
            'SELECT * FROM whatsapp_message_ids ORDER BY created_at DESC'
        );
        return rows;
    } catch (error) {
        console.error('Failed to get all tokens:', error);
        throw error;
    }
}

/**
 * Check if a media file needs to be refreshed (> 48 hours since last refresh)
 * @param {string} messageId - WhatsApp message ID
 * @returns {boolean} True if needs refresh, false if refreshed recently
 */
async function needsRefresh(messageId) {
    try {
        const [rows] = await pool.execute(
            `SELECT last_refreshed FROM media_refresh_log WHERE message_id = ?`,
            [messageId]
        );

        if (rows.length === 0) {
            return true; // Never refreshed, needs refresh
        }

        const lastRefreshed = new Date(rows[0].last_refreshed);
        const hoursSinceRefresh = (Date.now() - lastRefreshed.getTime()) / (1000 * 60 * 60);

        return hoursSinceRefresh > 48; // Refresh if older than 48 hours
    } catch (error) {
        console.error('Error checking refresh status:', error.message);
        return true; // On error, assume it needs refresh
    }
}

/**
 * Log a media file refresh
 * @param {string} messageId - WhatsApp message ID
 * @param {string} fileName - File name (optional)
 * @param {string} fileType - video, document, image, etc.
 * @param {number} fileSize - File size in bytes (optional)
 */
async function logRefresh(messageId, fileName = null, fileType = 'video', fileSize = null) {
    try {
        await pool.execute(
            `INSERT INTO media_refresh_log (message_id, file_name, file_type, file_size, refresh_count)
             VALUES (?, ?, ?, ?, 1)
             ON DUPLICATE KEY UPDATE 
                last_refreshed = CURRENT_TIMESTAMP,
                refresh_count = refresh_count + 1,
                file_name = COALESCE(?, file_name),
                file_type = COALESCE(?, file_type),
                file_size = COALESCE(?, file_size)`,
            [messageId, fileName, fileType, fileSize, fileName, fileType, fileSize]
        );
    } catch (error) {
        console.error('Error logging refresh:', error.message);
        // Don't throw - logging failure shouldn't stop the refresh
    }
}

/**
 * Get refresh statistics
 * @returns {Object} Statistics about refresh history
 */
async function getRefreshStats() {
    try {
        const [stats] = await pool.execute(
            `SELECT 
                COUNT(*) as total_files,
                SUM(refresh_count) as total_refreshes,
                MAX(last_refreshed) as most_recent_refresh
             FROM media_refresh_log`
        );
        return stats[0];
    } catch (error) {
        console.error('Error getting refresh stats:', error.message);
        return { total_files: 0, total_refreshes: 0, most_recent_refresh: null };
    }
}

module.exports = {
    pool,
    testConnection,
    getTokenData,
    markTokenAsUsed,
    logForward,
    getContentTitle,
    cleanupExpiredTokens,
    // Media refresh tracking
    needsRefresh,
    logRefresh,
    getRefreshStats,
    // Legacy exports
    getMessageIdByToken,
    incrementDownloadCount,
    addToken,
    getAllTokens
};

