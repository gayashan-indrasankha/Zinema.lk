<?php
/**
 * Admin Settings Page
 * Manage site-wide configuration
 */

require_once 'config.php';
require_once '../includes/settings.php';

// Check admin authentication
check_login();

$message = '';
$message_type = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_settings') {
        $updated = 0;
        
        // Update each submitted setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $settingKey = substr($key, 8); // Remove 'setting_' prefix
                if (setSetting($conn, $settingKey, trim($value))) {
                    $updated++;
                }
            }
        }
        
        $message = "Successfully updated $updated setting(s)";
        $message_type = 'success';
    }
    
    if ($action === 'add_setting') {
        $newKey = trim($_POST['new_key'] ?? '');
        $newValue = trim($_POST['new_value'] ?? '');
        $newDesc = trim($_POST['new_description'] ?? '');
        
        if ($newKey && $newValue) {
            if (setSetting($conn, $newKey, $newValue, $newDesc)) {
                $message = "Setting '$newKey' added successfully";
                $message_type = 'success';
            } else {
                $message = "Failed to add setting";
                $message_type = 'error';
            }
        }
    }
}

// Get all settings
$settings = getAllSettings($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Settings - Zinema.lk Admin</title>
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo filemtime(__DIR__ . '/assets/admin.css'); ?>">
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <header>
            <h1>⚙️ Site Settings</h1>
        </header>
        
        <?php if ($message): ?>
        <div class="message <?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>
        
        <div class="settings-card">
            <h2>📝 Configuration</h2>
            <form method="POST">
                <input type="hidden" name="action" value="save_settings">
                
                <?php foreach ($settings as $key => $data): ?>
                <div class="setting-row">
                    <label class="setting-label">
                        <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $key))); ?>
                        <?php if ($data['description']): ?>
                        <small><?php echo htmlspecialchars($data['description']); ?></small>
                        <?php endif; ?>
                    </label>
                    <input type="text" 
                           name="setting_<?php echo htmlspecialchars($key); ?>" 
                           value="<?php echo htmlspecialchars($data['value']); ?>"
                           class="setting-input">
                </div>
                <?php endforeach; ?>
                
                <button type="submit" class="btn-save">
                    💾 Save Settings
                </button>
            </form>
        </div>
        
        <div class="settings-card">
            <h2>➕ Add New Setting</h2>
            <form method="POST" class="add-setting-form">
                <input type="hidden" name="action" value="add_setting">
                <div class="add-form-row">
                    <input type="text" name="new_key" placeholder="Setting Key (e.g. my_setting)" required>
                    <input type="text" name="new_value" placeholder="Value" required>
                </div>
                <div class="add-form-row">
                    <input type="text" name="new_description" placeholder="Description (optional)">
                    <button type="submit" class="btn-add">
                        ➕ Add Setting
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
