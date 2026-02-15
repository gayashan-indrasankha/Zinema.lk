<?php
/**
 * Site Settings Helper
 * Manages system configuration stored in database
 */

/**
 * Get a setting value from database
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $default Default value if not found
 * @return mixed Setting value
 */
function getSetting($conn, $key, $default = null) {
    static $settingsCache = [];
    
    // Return from cache if available
    if (isset($settingsCache[$key])) {
        return $settingsCache[$key];
    }
    
    // Ensure table exists
    ensureSettingsTable($conn);
    
    // Query setting
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM site_settings WHERE setting_key = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 's', $key);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($row = mysqli_fetch_assoc($result)) {
            $settingsCache[$key] = $row['setting_value'];
            mysqli_stmt_close($stmt);
            return $row['setting_value'];
        }
        mysqli_stmt_close($stmt);
    }
    
    return $default;
}

/**
 * Set a setting value in database
 * 
 * @param mysqli $conn Database connection
 * @param string $key Setting key
 * @param mixed $value Setting value
 * @param string $description Optional description
 * @return bool Success
 */
function setSetting($conn, $key, $value, $description = null) {
    ensureSettingsTable($conn);
    
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO site_settings (setting_key, setting_value, description) 
         VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()"
    );
    
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $key, $value, $description, $value);
        $result = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $result;
    }
    
    return false;
}

/**
 * Get all settings
 * 
 * @param mysqli $conn Database connection
 * @return array All settings as key => value pairs
 */
function getAllSettings($conn) {
    ensureSettingsTable($conn);
    
    $settings = [];
    $result = mysqli_query($conn, "SELECT setting_key, setting_value, description FROM site_settings ORDER BY setting_key");
    
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }
    }
    
    return $settings;
}

/**
 * Ensure settings table exists
 */
function ensureSettingsTable($conn) {
    static $tableChecked = false;
    
    if ($tableChecked) return;
    
    $sql = "CREATE TABLE IF NOT EXISTS site_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    mysqli_query($conn, $sql);
    
    // Insert default settings if table is empty
    $check = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM site_settings");
    $row = mysqli_fetch_assoc($check);
    
    if ($row['cnt'] == 0) {
        // Default settings
        $defaults = [
            ['default_bot_phone', '94766032279', 'Default WhatsApp bot phone number (fallback)'],
            ['token_expiry_minutes', '10', 'How long tokens remain valid (minutes)'],
            ['rate_limit_per_minute', '10', 'Max token requests per IP per minute'],
            ['rate_limit_per_hour', '50', 'Max token requests per IP per hour'],
            ['site_name', 'Zinema.lk', 'Website name'],
            ['admin_whatsapp', '94766032279', 'Admin WhatsApp number for support']
        ];
        
        foreach ($defaults as $setting) {
            $stmt = mysqli_prepare($conn, 
                "INSERT IGNORE INTO site_settings (setting_key, setting_value, description) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt, 'sss', $setting[0], $setting[1], $setting[2]);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }
    
    $tableChecked = true;
}
?>
