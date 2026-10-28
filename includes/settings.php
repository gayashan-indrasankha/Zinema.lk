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
    $limits=['token_expiry_minutes'=>180,'rate_limit_per_minute'=>100,'rate_limit_per_hour'=>5000];
    if (isset($limits[$key])) {
        $number=filter_var($value,FILTER_VALIDATE_INT);
        if ($number===false || $number<1 || $number>$limits[$key]) return false;
    }
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
    
    // Read-only assertion: migrations/bootstrap own schema and initial defaults.
    mysqli_query($conn, "SELECT setting_key FROM site_settings LIMIT 0");

    $tableChecked = true;
}
?>
