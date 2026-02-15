<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/whatsapp_token.php';
require_once 'includes/ads.php';

// Get ID and type from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'movie';

// Redirect if no ID provided
if ($id <= 0) {
    header('Location: ' . ($type === 'episode' ? 'tv-series.php' : 'movies.php'));
    exit();
}

// Fetch content details based on type
if ($type === 'episode') {
    $sql = "SELECT e.*, s.title as series_title FROM episodes e LEFT JOIN series s ON e.series_id = s.id WHERE e.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: tv-series.php');
        exit();
    }
    
    $content = $result->fetch_assoc();
    $content['episode_title'] = $content['title']; // Save original episode title
    $content['title'] = $content['series_title'] . ' - ' . $content['title'];
    $content_type = 'episode';
    $back_url = 'series-details.php?id=' . $content['series_id'];
} else {
    $sql = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: movies.php');
        exit();
    }
    
    $content = $result->fetch_assoc();
    $content_type = 'movie';
    $back_url = 'movie-details.php?id=' . $content['id'];
}

// Set nav page for bottom navigation highlighting
$current_nav_page = ($content_type === 'episode') ? 'tv-series' : 'movies';

$stmt->close();

// Check if group mode is enabled
$group_mode = isGroupModeEnabled($conn);
$distribution_group = null;
if ($group_mode) {
    $distribution_group = getActiveDistributionGroup($conn);
}

// Generate WhatsApp token and link
$whatsapp_number = getSetting($conn, 'default_bot_phone', '94766032279'); // From database

// Rate limit and service availability flags
$rate_limited = false;
$rate_limit_message = '';
$service_unavailable = false;
$service_message = '';

// Single token for full movie/episode
$token_data = createWhatsAppToken($conn, $id, $content_type, 10);

// Check for rate limit error
if (isset($token_data['rate_limited']) && $token_data['rate_limited']) {
    $rate_limited = true;
    $rate_limit_message = $token_data['message'];
    $whatsapp_link = '#';
    $token_expires = null;
// Check for no bots available
} elseif (isset($token_data['no_bots_available']) && $token_data['no_bots_available']) {
    $service_unavailable = true;
    $service_message = $token_data['message'];
    $whatsapp_link = '#';
    $token_expires = null;
} elseif ($token_data && !isset($token_data['error'])) {
    $whatsapp_link = getWhatsAppLink($token_data['token'], $token_data['bot_phone'] ?? $whatsapp_number);
    $token_expires = $token_data['expires_at'];
} else {
    // Fallback if token generation fails
    $whatsapp_message = urlencode("Hi! I want to watch: " . $content['title']);
    $whatsapp_link = "https://wa.me/{$whatsapp_number}?text={$whatsapp_message}";
    $token_expires = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/desktop-tablet.css">
    <style>
        body {
            background-color: #000000 !important;
            background-image: none !important;
        }

        .watch-page {
            max-width: 600px;
            margin: 0 auto;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #000000;
        }

        .watch-container {
            background: #000000;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            width: 100%;
            box-shadow: none;
        }

        /* Movie Info Card */
        .movie-info-card {
            background: #111;
            border: 1px solid #333;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }

        .movie-info-label {
            color: #888;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .movie-info-title {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            line-height: 1.4;
        }

        /* WhatsApp Button */
        .btn-whatsapp-main {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            width: 100%;
            padding: 16px 24px;
            background: transparent;
            border: 2px solid rgba(37, 211, 102, 0.6);
            border-radius: 12px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 15px;
        }

        .btn-whatsapp-main:hover {
            background: rgba(37, 211, 102, 0.15);
            border-color: rgba(37, 211, 102, 0.9);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .btn-whatsapp-main i {
            color: #25D366;
            font-size: 1.3rem;
        }

        /* Contact Admin Button */
        .btn-contact-admin {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px 24px;
            background: transparent;
            border: 2px solid rgba(37, 211, 102, 0.4);
            border-radius: 12px;
            color: #ccc;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn-contact-admin:hover {
            background: rgba(37, 211, 102, 0.1);
            border-color: rgba(37, 211, 102, 0.7);
            color: #fff;
        }

        .btn-contact-admin i {
            color: #25D366;
            font-size: 1.1rem;
        }

        /* Back Link */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            color: #b4bcc9;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .back-link:hover {
            color: #25D366;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 25px;
        }

        .page-header h1 {
            font-size: 1.3rem;
            color: #fff;
            margin-bottom: 8px;
        }

        .page-header p {
            color: #888;
            font-size: 0.9rem;
        }

        /* Info Section */
        .info-section {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 1px solid #333;
            text-align: left;
        }

        .info-title {
            font-size: 1rem;
            color: #fff;
            margin-bottom: 20px;
        }

        .info-title a {
            color: #25D366;
            text-decoration: none;
        }

        .info-title a:hover {
            text-decoration: underline;
        }

        .help-section {
            margin-bottom: 20px;
        }

        .help-section h3 {
            font-size: 1rem;
            color: #fff;
            margin-bottom: 12px;
        }

        .help-section p {
            color: #999;
            font-size: 0.85rem;
            line-height: 1.6;
            margin-bottom: 10px;
        }

        .contact-admin-text {
            color: #999;
            font-size: 0.9rem;
        }

        .contact-admin-text a {
            color: #25D366;
            text-decoration: none;
        }

        .contact-admin-text a:hover {
            text-decoration: underline;
        }

        /* Movie Parts Styles */
        .parts-container {
            margin-bottom: 20px;
        }

        .parts-header {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 12px;
            text-align: center;
        }

        .parts-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-part {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px;
            background: rgba(37, 211, 102, 0.08);
            border: 1px solid rgba(37, 211, 102, 0.3);
            border-radius: 10px;
            color: #fff;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn-part:hover {
            background: rgba(37, 211, 102, 0.18);
            border-color: rgba(37, 211, 102, 0.6);
            transform: translateX(5px);
        }

        .btn-part-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-part-icon {
            color: #25D366;
            font-size: 1.2rem;
        }

        .btn-part-info {
            text-align: left;
        }

        .btn-part-title {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .btn-part-size {
            color: #888;
            font-size: 0.8rem;
            margin-top: 2px;
        }

        .btn-part-arrow {
            color: #25D366;
        }

        /* Bot Assignment Card */
        .bot-assignment {
            background: rgba(37, 211, 102, 0.1);
            border: 1px solid rgba(37, 211, 102, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        .bot-assignment h3 {
            color: #fff;
            margin: 0 0 10px 0;
            font-size: 1.1rem;
        }
        .bot-phone {
            color: #25D366;
            font-size: 1.2rem;
            font-weight: bold;
            margin: 0 0 10px 0;
            letter-spacing: 1px;
        }
        .bot-instruction {
            color: #ccc;
            font-size: 0.9rem;
            margin: 0;
            background: rgba(0,0,0,0.3);
            padding: 8px 12px;
            border-radius: 6px;
            display: inline-block;
        }
        .bot-instruction strong {
            color: #fff;
            font-family: monospace;
            font-size: 1rem;
        }

        /* Rate Limit Error */
        .rate-limit-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.4);
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .rate-limit-error i.fa-exclamation-triangle {
            color: #ef4444;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .rate-limit-error h3 {
            color: #ef4444;
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        .rate-limit-error p {
            color: #ccc;
            margin: 0 0 10px 0;
            font-size: 0.95rem;
        }
        .rate-limit-error .retry-info {
            color: #888;
            font-size: 0.85rem;
        }
        .btn-retry {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.5);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .btn-retry:hover {
            background: rgba(239, 68, 68, 0.3);
            border-color: rgba(239, 68, 68, 0.7);
        }

        /* Service Unavailable */
        .service-unavailable {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.4);
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
        }
        .service-unavailable i.fa-server {
            color: #3b82f6;
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .service-unavailable h3 {
            color: #3b82f6;
            margin: 0 0 10px 0;
            font-size: 1.2rem;
        }
        .service-unavailable p {
            color: #ccc;
            margin: 0 0 10px 0;
            font-size: 0.95rem;
        }
        .service-unavailable .retry-info {
            color: #888;
            font-size: 0.85rem;
        }
        .btn-retry-blue {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.5);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.95rem;
            margin-top: 15px;
            transition: all 0.3s ease;
        }
        .btn-retry-blue:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.7);
        }

        /* Group Mode Styles */
        .group-join-section {
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.15), rgba(37, 211, 102, 0.05));
            border: 2px solid rgba(37, 211, 102, 0.5);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
        }
        .group-join-section h3 {
            color: #25D366;
            margin: 0 0 8px 0;
            font-size: 1.1rem;
        }
        .group-join-section p {
            color: #aaa;
            font-size: 0.85rem;
            margin: 0 0 15px 0;
        }
        .btn-join-group {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 30px;
            background: #25D366;
            border: none;
            border-radius: 12px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .btn-join-group:hover {
            background: #1ebe5a;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.4);
        }
        .token-section {
            margin-top: 20px;
        }
        .token-section h4 {
            color: #fff;
            font-size: 0.95rem;
            margin: 0 0 15px 0;
        }
        .token-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid #333;
            border-radius: 10px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }
        .token-item-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .token-item-icon {
            color: #25D366;
            font-size: 1.1rem;
        }
        .token-item-info {
            text-align: left;
        }
        .token-item-title {
            color: #fff;
            font-weight: 600;
            font-size: 0.9rem;
        }
        .token-item-size {
            color: #888;
            font-size: 0.75rem;
            margin-top: 2px;
        }
        .token-code {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .token-text {
            font-family: 'Courier New', monospace;
            background: rgba(37, 211, 102, 0.15);
            border: 1px solid rgba(37, 211, 102, 0.3);
            padding: 8px 12px;
            border-radius: 6px;
            color: #25D366;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .btn-copy {
            background: rgba(37, 211, 102, 0.2);
            border: 1px solid rgba(37, 211, 102, 0.4);
            color: #25D366;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }
        .btn-copy:hover {
            background: rgba(37, 211, 102, 0.3);
        }
        .btn-copy.copied {
            background: #25D366;
            color: #fff;
        }
        .group-instructions {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 15px;
            margin-top: 20px;
        }
        .group-instructions h4 {
            color: #fff;
            font-size: 0.9rem;
            margin: 0 0 10px 0;
        }
        .group-instructions ol {
            color: #aaa;
            font-size: 0.85rem;
            margin: 0;
            padding-left: 20px;
        }
        .group-instructions li {
            margin-bottom: 6px;
        }
        .group-instructions code {
            background: rgba(37, 211, 102, 0.15);
            color: #25D366;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 480px) {
            .group-join-section {
                padding: 18px 15px;
                margin-bottom: 20px;
            }
            .group-join-section h3 {
                font-size: 1rem;
            }
            .group-join-section p {
                font-size: 0.8rem;
            }
            .btn-join-group {
                padding: 12px 20px;
                font-size: 0.9rem;
                width: 100%;
                box-sizing: border-box;
            }
            .token-section h4 {
                font-size: 0.85rem;
            }
            .token-item {
                flex-direction: column;
                align-items: stretch;
                gap: 12px;
                padding: 12px;
            }
            .token-item-left {
                justify-content: flex-start;
            }
            .token-code {
                width: 100%;
                justify-content: space-between;
            }
            .token-text {
                flex: 1;
                font-size: 0.75rem;
                padding: 10px 12px;
                text-align: center;
                word-break: break-all;
            }
            .btn-copy {
                padding: 10px 15px;
                flex-shrink: 0;
            }
            .group-instructions {
                padding: 12px;
            }
            .group-instructions h4 {
                font-size: 0.85rem;
            }
            .group-instructions ol {
                font-size: 0.8rem;
                padding-left: 18px;
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="main-content">
            <div class="watch-page">
                <div class="watch-container">
                    <!-- Page Header -->
                    <div class="page-header">
                        <h1><i class="fab fa-whatsapp" style="color: #25D366; margin-right: 8px;"></i> WhatsApp මගින් නරඹන්න</h1>
                        <p>Watch via WhatsApp Packages</p>
                    </div>

                    <!-- Movie Info Card -->
                    <div class="movie-info-card">
                        <?php if ($content_type === 'episode'): ?>
                        <div class="movie-info-label">Series Name:</div>
                        <div class="movie-info-title"><?php echo htmlspecialchars($content['series_title']); ?></div>
                        <div class="movie-info-label" style="margin-top: 12px;">Episode:</div>
                        <div class="movie-info-title"><?php echo htmlspecialchars($content['episode_title']); ?></div>
                        <?php else: ?>
                        <div class="movie-info-label">Movie Name:</div>
                        <div class="movie-info-title"><?php echo htmlspecialchars($content['title']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Ad Banner -->
                    <?php renderRectangleAd(); ?>

                    <?php if ($rate_limited): ?>
                    <!-- Rate Limit Error Message -->
                    <div class="rate-limit-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <h3>Too Many Requests</h3>
                        <p><?php echo htmlspecialchars($rate_limit_message); ?></p>
                        <p class="retry-info">Please wait a moment and refresh this page.</p>
                        <button onclick="location.reload()" class="btn-retry">
                            <i class="fas fa-sync-alt"></i> Retry
                        </button>
                    </div>
                    <?php elseif ($service_unavailable): ?>
                    <!-- Service Unavailable Message -->
                    <div class="service-unavailable">
                        <i class="fas fa-server"></i>
                        <h3>Service Temporarily Unavailable</h3>
                        <p><?php echo htmlspecialchars($service_message); ?></p>
                        <p class="retry-info">All bots are currently offline. Please try again shortly.</p>
                        <button onclick="location.reload()" class="btn-retry-blue">
                            <i class="fas fa-sync-alt"></i> Check Again
                        </button>
                    </div>
                    <?php else: ?>
                    <!-- Single Full Video -->

                    <?php if ($group_mode && $distribution_group && $token_data && !isset($token_data['error'])): ?>
                    <!-- GROUP MODE: Join Group + Copy Token for Full Video -->
                    <div class="group-join-section">
                        <h3><i class="fab fa-whatsapp"></i> Step 1: Join Our Group</h3>
                        <p>First, join the WhatsApp group to receive videos</p>
                        <a href="<?php echo htmlspecialchars($distribution_group['invite_link']); ?>" target="_blank" class="btn-join-group">
                            <i class="fas fa-users"></i>
                            Join <?php echo htmlspecialchars($distribution_group['group_name']); ?>
                        </a>
                    </div>
                    
                    <div class="token-section">
                        <h4><i class="fas fa-key"></i> Step 2: Copy & Send Token in Group</h4>
                        <div class="token-item">
                            <div class="token-item-left">
                                <i class="fas fa-film token-item-icon"></i>
                                <div class="token-item-info">
                                    <div class="token-item-title"><?php echo $content_type === 'episode' ? 'Full Episode' : 'Full Movie'; ?></div>
                                </div>
                            </div>
                            <div class="token-code">
                                <span class="token-text">!get <?php echo $token_data['token']; ?></span>
                                <button class="btn-copy" onclick="copyToken(this, '!get <?php echo $token_data['token']; ?>')">
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="group-instructions">
                        <h4>📋 How to Watch:</h4>
                        <ol>
                            <li>Click "Join Group" button above</li>
                            <li>Copy the token (e.g., <code>!get ABC123</code>)</li>
                            <li>Paste & send it in the group</li>
                            <li>Bot will send the video to the group</li>
                        </ol>
                    </div>
                    
                    <?php else: ?>
                    <!-- OLD MODE: Direct WhatsApp Link -->
                    <?php 
                    if ($token_data): 
                        $bId = $token_data['assigned_bot_id'] ?? 1;
                        $bPhone = $token_data['bot_phone'] ?? $whatsapp_number;
                    ?>
                    <div class="bot-assignment">
                        <h3>📱 Send your token to Bot #<?php echo $bId; ?></h3>
                        <p class="bot-phone"><?php echo $bPhone; ?></p>
                        <p class="bot-instruction">Send: <strong>!get <?php echo $token_data['token']; ?></strong></p>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo $whatsapp_link; ?>" target="_blank" class="btn-whatsapp-main">
                        <i class="fab fa-whatsapp"></i>
                        <span>WhatsApp මගින් නරඹන්න</span>
                    </a>
                    <?php endif; ?>
                    <?php endif; ?>

                    <!-- Back Link -->
                    <a href="<?php echo $back_url; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Back to <?php echo $content_type === 'episode' ? 'Series' : 'Movie'; ?> Details
                    </a>

                    <!-- Info Section -->
                    <div class="info-section">
                        <p class="info-title">For More <?php echo $content_type === 'episode' ? 'Tv Series' : 'Movies'; ?> Visit <a href="https://zinema.lk" target="_blank">Zinema.lk</a></p>
                        
                        <div class="help-section">
                            <h3>Help</h3>
                            <p> මේ හරහා ඔබට WhatsApp Packages මගින් නොමිලේ Movies සහ TV Series පහසුවෙන් නැරඹිය හැක.</p>
                            <?php if ($has_parts): ?>
                            <p>ඉහත Part එකක් ක්ලික් කර WhatsApp වෙත යවන්න. එක් Part එක බාගත වූ පසු ඊළඟ Part එක ක්ලික් කරන්න.</p>
                            <?php else: ?>
                            <p>ඉහත "WhatsApp මගින් නරඹන්න"  කියන button එක ක්ලික් කර අදාල Link එක send කර ඔබට අවශ්‍ය Movie එක හෝ Tv Series එක ලැබෙන තුරු රැඳී සිටින්න.</p>
                            <?php endif; ?>
                            <p class="contact-admin-text">කිසියම් ගැටලුවක් ඇත්නම් Admin අමතන්න. - <a href="https://wa.me/94766032279?text=<?php echo urlencode('Hi Admin, I need help'); ?>" target="_blank">Admin</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>

    <?php if (defined('ADS_ENABLED') && ADS_ENABLED): ?>
    <script src="https://pl28336857.effectivegatecpm.com/98/16/d8/9816d8032d18812ecbc7cbdeed1e9a12.js"></script>
    <?php endif; ?>
    
    <!-- Click-based Ad Trigger System -->
    <script>
    (function() {
        // Smart Link URL for redirects
        var smartLinkUrl = 'https://www.effectivegatecpm.com/a9d5n1rn19?key=11ae796dc3f92c308b494e5436e6345f';
        
        // Clicks that should trigger a Pop-up/new tab (1st, 3rd, 5th, 7th, 10th)
        var popupClicks = [1, 3, 5, 7, 10];
        
        // Clicks that should trigger a Smart Link redirect (12th, 15th)
        var redirectClicks = [12, 15];
        
        // Get current click count
        function getClickCount() {
            return parseInt(localStorage.getItem('adClickCount') || '0');
        }
        
        // Save click count
        function saveClickCount(count) {
            localStorage.setItem('adClickCount', count.toString());
        }
        
        // Handle click event
        function handleAdClick(e) {
            var clickCount = getClickCount() + 1;
            
            // Reset if over 15
            if (clickCount > 15) {
                clickCount = 1;
            }
            
            // Save immediately
            saveClickCount(clickCount);
            
            console.log('Ad Click Count:', clickCount); // Debug - remove in production
            
            // Pop-up clicks (1st, 5th, 7th, 10th) - opens in new tab
            if (popupClicks.indexOf(clickCount) !== -1) {
                // Open Smart Link in new tab (user action, so won't be blocked)
                var newTab = window.open(smartLinkUrl, '_blank');
                // Don't prevent default - let user's action continue
            }
            // Redirect clicks (12th, 20th)
            else if (redirectClicks.indexOf(clickCount) !== -1) {
                // Prevent the user's intended action
                e.preventDefault();
                e.stopPropagation();
                
                // Redirect after small delay to ensure localStorage saved
                setTimeout(function() {
                    window.location.href = smartLinkUrl;
                }, 50);
                
                return false;
            }
        }
        
        // Attach click listener to the document (capture phase)
        document.addEventListener('click', handleAdClick, true);
    })();
    </script>

    <!-- Copy Token Function -->
    <script>
    function copyToken(btn, text) {
        navigator.clipboard.writeText(text).then(function() {
            // Visual feedback
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            
            // Reset after 2 seconds
            setTimeout(function() {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        }).catch(function(err) {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            btn.classList.add('copied');
            btn.innerHTML = '<i class="fas fa-check"></i>';
            setTimeout(function() {
                btn.classList.remove('copied');
                btn.innerHTML = '<i class="fas fa-copy"></i>';
            }, 2000);
        });
    }
    </script>
</body>
</html>

<?php
/**
 * Format bytes to human readable format
 */
function formatBytes($bytes, $precision = 2) {
    if (!$bytes) return '';
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
