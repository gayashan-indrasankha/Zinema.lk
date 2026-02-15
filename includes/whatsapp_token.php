<?php
/**
 * WhatsApp Token Generation Functions
 * Generates unique, single-use tokens for WhatsApp video forwarding
 */

// Include rate limiter and settings
require_once __DIR__ . '/rate_limiter.php';
require_once __DIR__ . '/settings.php';

/**
 * Generate a unique 12-character alphanumeric token
 * 
 * @return string 12-character token
 */
function generateUniqueToken() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Excluding I, O, 0, 1 to avoid confusion
    $token = '';
    $length = 12;
    
    for ($i = 0; $i < $length; $i++) {
        $token .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $token;
}

/**
 * Create a WhatsApp token for content and insert into database
 * 
 * @param mysqli $conn Database connection
 * @param int $contentId ID of the movie or episode
 * @param string $contentType Type of content ('movie' or 'episode')
 * @param int $expiryMinutes Token expiry time in minutes (default: 10)
 * @param int $partNumber Part number for multi-part movies (optional, default: null)
 * @return array|false Token data on success, false on failure, or rate limit error array
 */
function createWhatsAppToken($conn, $contentId, $contentType, $expiryMinutes = 180, $partNumber = null) {
    // Rate limiting check - DISABLED
    // To re-enable, uncomment the code below:
    /*
    $rateLimiter = new RateLimiter($conn);
    $rateCheck = $rateLimiter->checkLimit('token_request');
    
    if (!$rateCheck['allowed']) {
        return [
            'error' => true,
            'rate_limited' => true,
            'message' => $rateCheck['reason'],
            'retry_after' => $rateCheck['retry_after'] ?? 60
        ];
    }
    */
    
    // Check if any bots are online (heartbeat within last 2 minutes)
    $onlineBotCheck = mysqli_query($conn, 
        "SELECT COUNT(*) as online_count FROM bot_health_status 
         WHERE status = 'online' AND last_heartbeat > DATE_SUB(NOW(), INTERVAL 2 MINUTE)"
    );
    $onlineCount = 0;
    if ($onlineBotCheck) {
        $row = mysqli_fetch_assoc($onlineBotCheck);
        $onlineCount = (int)($row['online_count'] ?? 0);
    }
    
    if ($onlineCount === 0) {
        return [
            'error' => true,
            'no_bots_available' => true,
            'message' => 'WhatsApp service is temporarily unavailable. Please try again in a few minutes.',
            'online_count' => 0
        ];
    }
    
    $messageId = null;
    
    // For movie parts, get message_id from movie_parts table
    // For movie/episode parts, get message_id from relevant parts table
    if ($partNumber !== null && $partNumber > 0) {
        if ($contentType === 'movie') {
            $msgStmt = $conn->prepare("SELECT message_id FROM movie_parts WHERE movie_id = ? AND part_number = ?");
            $msgStmt->bind_param("ii", $contentId, $partNumber);
            $msgStmt->execute();
            $msgResult = $msgStmt->get_result();
            
            if ($msgResult->num_rows > 0) {
                $msgRow = $msgResult->fetch_assoc();
                $messageId = $msgRow['message_id'];
            }
            $msgStmt->close();
        } elseif ($contentType === 'episode') {
            $msgStmt = $conn->prepare("SELECT message_id FROM episode_parts WHERE episode_id = ? AND part_number = ?");
            $msgStmt->bind_param("ii", $contentId, $partNumber);
            $msgStmt->execute();
            $msgResult = $msgStmt->get_result();
            
            if ($msgResult->num_rows > 0) {
                $msgRow = $msgResult->fetch_assoc();
                $messageId = $msgRow['message_id'];
            }
            $msgStmt->close();
        }
    } else {
        // Standard: check whatsapp_message_ids table
        $msgStmt = $conn->prepare("SELECT message_id FROM whatsapp_message_ids WHERE content_type = ? AND content_id = ?");
        $msgStmt->bind_param("si", $contentType, $contentId);
        $msgStmt->execute();
        $msgResult = $msgStmt->get_result();
        
        if ($msgResult->num_rows > 0) {
            $msgRow = $msgResult->fetch_assoc();
            $messageId = $msgRow['message_id'];
        }
        $msgStmt->close();
    }
    
    // Generate unique token
    $maxAttempts = 10;
    $token = null;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $candidateToken = generateUniqueToken();
        
        // Check if token already exists
        $checkStmt = $conn->prepare("SELECT id FROM whatsapp_tokens WHERE token = ?");
        $checkStmt->bind_param("s", $candidateToken);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows === 0) {
            $token = $candidateToken;
            $checkStmt->close();
            break;
        }
        $checkStmt->close();
    }
    
    if ($token === null) {
        return false; // Failed to generate unique token
    }
    
    // Get available bot for load balancing
    $assignedBotId = 1;
    $assignedBotPhone = getSetting($conn, 'default_bot_phone', '94766032279'); // From database
    
    try {
        // Query to get the best available bot and its phone number
        // fn_get_available_bot() returns the optimal bot_id
        $botQuery = "SELECT b.bot_id, b.bot_phone 
                     FROM bot_health_status b 
                     WHERE b.bot_id = fn_get_available_bot()";
        $botStmt = $conn->query($botQuery);
        if ($botStmt && $botStmt->num_rows > 0) {
            $botRow = $botStmt->fetch_assoc();
            $assignedBotId = $botRow['bot_id'];
            $assignedBotPhone = $botRow['bot_phone'];
        }
    } catch (Exception $e) {
        // Keep defaults on error
        error_log("Bot assignment failed: " . $e->getMessage());
    }

    // Insert token into database (include part_number if provided)
    if ($partNumber !== null && $partNumber > 0) {
        $insertStmt = $conn->prepare(
            "INSERT INTO whatsapp_tokens (token, content_type, content_id, message_id, part_number, assigned_bot_id, is_used, is_active, expires_at) 
             VALUES (?, ?, ?, ?, ?, ?, 0, 1, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $insertStmt->bind_param("ssisiii", $token, $contentType, $contentId, $messageId, $partNumber, $assignedBotId, $expiryMinutes);
    } else {
        $insertStmt = $conn->prepare(
            "INSERT INTO whatsapp_tokens (token, content_type, content_id, message_id, assigned_bot_id, is_used, is_active, expires_at) 
             VALUES (?, ?, ?, ?, ?, 0, 1, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $insertStmt->bind_param("ssisii", $token, $contentType, $contentId, $messageId, $assignedBotId, $expiryMinutes);
    }
    
    if ($insertStmt->execute()) {
        $tokenId = $insertStmt->insert_id;
        $insertStmt->close();
        
        return [
            'id' => $tokenId,
            'token' => $token,
            'content_type' => $contentType,
            'content_id' => $contentId,
            'message_id' => $messageId,
            'part_number' => $partNumber,
            'expires_at' => date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes")), // For display only
            'assigned_bot_id' => $assignedBotId,
            'bot_phone' => $assignedBotPhone
        ];
    }
    
    $insertStmt->close();
    return false;
}


/**
 * Generate WhatsApp redirect link for a token
 * 
 * @param string $token The generated token
 * @param string $botNumber WhatsApp bot phone number (without +)
 * @return string WhatsApp wa.me URL
 */
function getWhatsAppLink($token, $botNumber) {
    // Remove + if present
    $botNumber = str_replace('+', '', $botNumber);
    $message = urlencode("!get {$token}");
    return "https://wa.me/{$botNumber}?text={$message}";
}

/**
 * Clean up expired tokens (optional utility function)
 * 
 * @param mysqli $conn Database connection
 * @return int Number of deleted tokens
 */
function cleanupExpiredTokens($conn) {
    $stmt = $conn->prepare("DELETE FROM whatsapp_tokens WHERE expires_at < NOW() AND is_used = 0");
    $stmt->execute();
    $deletedCount = $stmt->affected_rows;
    $stmt->close();
    return $deletedCount;
}

/**
 * Get token statistics (for admin dashboard)
 * 
 * @param mysqli $conn Database connection
 * @return array Statistics about tokens
 */
function getTokenStats($conn) {
    $stats = [
        'total' => 0,
        'used' => 0,
        'expired' => 0,
        'active' => 0
    ];
    
    // Total tokens
    $result = $conn->query("SELECT COUNT(*) as count FROM whatsapp_tokens");
    $stats['total'] = $result->fetch_assoc()['count'];
    
    // Used tokens
    $result = $conn->query("SELECT COUNT(*) as count FROM whatsapp_tokens WHERE is_used = 1");
    $stats['used'] = $result->fetch_assoc()['count'];
    
    // Expired (not used)
    $result = $conn->query("SELECT COUNT(*) as count FROM whatsapp_tokens WHERE expires_at < NOW() AND is_used = 0");
    $stats['expired'] = $result->fetch_assoc()['count'];
    
    // Active (not used, not expired)
    $result = $conn->query("SELECT COUNT(*) as count FROM whatsapp_tokens WHERE is_used = 0 AND expires_at > NOW() AND is_active = 1");
    $stats['active'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

/**
 * Check if group mode is enabled
 * 
 * @param mysqli $conn Database connection
 * @return bool True if group mode is enabled
 */
function isGroupModeEnabled($conn) {
    return getSetting($conn, 'group_mode_enabled', '0') === '1';
}

/**
 * Get the currently active distribution group
 * Automatically rotates to next available group if current is full
 * 
 * @param mysqli $conn Database connection
 * @return array|null Group data with invite_link, group_jid, group_name, or null if none available
 */
function getActiveDistributionGroup($conn) {
    // First, check if group mode is enabled
    if (!isGroupModeEnabled($conn)) {
        return null;
    }
    
    // Get active group ID from settings
    $activeGroupId = (int) getSetting($conn, 'active_group_id', '1');
    
    // Check if current active group is still valid (not full, is active)
    $stmt = $conn->prepare(
        "SELECT id, group_name, group_jid, invite_link, member_count, max_members, is_full 
         FROM distribution_groups 
         WHERE id = ? AND is_active = TRUE"
    );
    $stmt->bind_param("i", $activeGroupId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $group = $result->fetch_assoc();
        $stmt->close();
        
        // Check if group is full
        if (!$group['is_full'] && $group['member_count'] < $group['max_members']) {
            return [
                'id' => $group['id'],
                'group_name' => $group['group_name'],
                'group_jid' => $group['group_jid'],
                'invite_link' => $group['invite_link'],
                'member_count' => $group['member_count'],
                'max_members' => $group['max_members']
            ];
        }
    } else {
        $stmt->close();
    }
    
    // Current group is full or invalid - find next available group
    $nextStmt = $conn->prepare(
        "SELECT id, group_name, group_jid, invite_link, member_count, max_members 
         FROM distribution_groups 
         WHERE is_active = TRUE AND is_full = FALSE 
         ORDER BY priority ASC, id ASC 
         LIMIT 1"
    );
    $nextStmt->execute();
    $nextResult = $nextStmt->get_result();
    
    if ($nextResult->num_rows > 0) {
        $nextGroup = $nextResult->fetch_assoc();
        $nextStmt->close();
        
        // Update active_group_id in settings
        updateSetting($conn, 'active_group_id', $nextGroup['id']);
        
        return [
            'id' => $nextGroup['id'],
            'group_name' => $nextGroup['group_name'],
            'group_jid' => $nextGroup['group_jid'],
            'invite_link' => $nextGroup['invite_link'],
            'member_count' => $nextGroup['member_count'],
            'max_members' => $nextGroup['max_members']
        ];
    }
    
    $nextStmt->close();
    return null; // No available groups
}

/**
 * Update a setting value
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param string $value Setting value
 * @return bool Success
 */
function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("UPDATE site_settings SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
    $stmt->bind_param("ss", $value, $key);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}

/**
 * Mark a distribution group as full
 * 
 * @param mysqli $conn Database connection
 * @param int $groupId Group ID
 * @return bool Success
 */
function markGroupAsFull($conn, $groupId) {
    $stmt = $conn->prepare("UPDATE distribution_groups SET is_full = TRUE, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $groupId);
    $result = $stmt->execute();
    $stmt->close();
    return $result;
}
?>
