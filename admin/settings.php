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
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .settings-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        .settings-card h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 1.3rem;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .setting-row {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 20px;
            margin-bottom: 20px;
            align-items: start;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .setting-label {
            color: #333;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .setting-label small {
            display: block;
            color: #666;
            font-weight: 400;
            font-size: 0.8rem;
            margin-top: 4px;
        }
        .setting-input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.3s;
        }
        .setting-input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .btn-save {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .add-setting-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        .add-form-row {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .add-form-row input {
            flex: 1;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
        }
        .add-form-row input:focus {
            border-color: #667eea;
            outline: none;
        }
        .btn-add {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }
        .btn-add:hover {
            background: #218838;
        }
        @media (max-width: 768px) {
            .setting-row {
                grid-template-columns: 1fr;
            }
            .add-form-row {
                flex-direction: column;
            }
        }
    </style>
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
