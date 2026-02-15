<?php
// Use admin config for consistent session, connection and auth
require_once 'config.php';

// Ensure admin is logged in
check_login();

// ==========================================
// 1. SAFETY CHECK: DO TABLES EXIST?
// ==========================================
$tables_check = mysqli_query($conn, "SHOW TABLES LIKE 'bot_health_status'");
$tables_exist = ($tables_check && mysqli_num_rows($tables_check) > 0);

if (!$tables_exist) {
    // Tables don't exist - Show Setup Required Page
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Setup Required - WhatsApp Bot Tracking</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background: #f3f4f6; padding: 50px; font-family: sans-serif; }
            .setup-box { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
            .setup-box h1 { color: #667eea; margin-bottom: 20px; }
            .setup-box .alert { margin: 20px 0; }
            code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="setup-box">
            <h1>⚙️ Database Setup Required</h1>
            <div class="alert alert-warning">
                <strong>⚠️ The Ultimate Multi-Bot System tables haven't been created yet!</strong>
            </div>
            
            <h3>📋 Setup Instructions:</h3>
            <ol>
                <li>Login to <strong>Namecheap cPanel</strong></li>
                <li>Open <strong>phpMyAdmin</strong></li>
                <li>Select database: <code>zinexxio_cinedrive</code></li>
                <li>Click the <strong>"Import"</strong> tab</li>
                <li>Upload file: <code>migration_ultimate_system_FIXED.sql</code></li>
                <li>Click <strong>"Go"</strong> button</li>
                <li>Refresh this page</li>
            </ol>
            
            <div class="alert alert-info">
                <strong>📁 File Location:</strong><br>
                <code>d:/001/whatapp bot/database/migration_ultimate_system_FIXED.sql</code>
            </div>
            
            <a href="whatsapp-bot-tracking.php" class="btn btn-primary btn-lg mt-3">🔄 Refresh Page</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// ==========================================
// 2. FETCH DATA SAFELY
// ==========================================

// Get bot health status
// Get bot health status with server-calculated heartbeat delta
$bot_health_query = "SELECT *, TIMESTAMPDIFF(SECOND, last_heartbeat, NOW()) as seconds_since_heartbeat FROM bot_health_status ORDER BY bot_id";
$bot_health_result = mysqli_query($conn, $bot_health_query);

// Get overall statistics
$stats_query = "SELECT 
    SUM(total_forwards) as total_forwards,
    SUM(cache_hits) as total_cache_hits,
    SUM(cache_misses) as total_cache_misses,
    SUM(total_data_saved_mb) / 1024 as total_data_saved_gb,
    ROUND(AVG(avg_forward_time_sec), 2) as avg_forward_time
FROM bot_statistics";
$stats_result = mysqli_query($conn, $stats_query);
$overall_stats = ($stats_result) ? mysqli_fetch_assoc($stats_result) : [];

// Get popular content (Safely - View might be missing or broken)
$popular_result = false;
try {
    $popular_query = "SELECT * FROM v_popular_content LIMIT 20";
    $popular_result = mysqli_query($conn, $popular_query);
} catch (Exception $e) { /* Ignore view errors */ }

// Get recent alerts
$alerts_query = "SELECT * FROM system_alerts WHERE is_resolved = 0 ORDER BY created_at DESC LIMIT 10";
$alerts_result = mysqli_query($conn, $alerts_query);

// Count active tokens per bot (safely - handle missing columns/tables)
$token_distribution = [];
try {
    $token_dist_query = "SELECT assigned_bot_id, COUNT(*) as count 
                         FROM whatsapp_tokens 
                         WHERE status = 'active' 
                         GROUP BY assigned_bot_id";
    $token_dist_result = mysqli_query($conn, $token_dist_query);
    if ($token_dist_result) {
        while($row = mysqli_fetch_assoc($token_dist_result)) {
            $token_distribution[$row['assigned_bot_id']] = $row['count'];
        }
    }
} catch (Exception $e) {
    // Ignore missing column errors, just leave distribution empty
    error_log("Token distribution query failed: " . $e->getMessage());
}

// Get cache hit rate
$cache_hit_rate = 0;
if (!empty($overall_stats) && ($overall_stats['total_cache_hits'] + $overall_stats['total_cache_misses']) > 0) {
    $cache_hit_rate = round(($overall_stats['total_cache_hits'] / 
                            ($overall_stats['total_cache_hits'] + $overall_stats['total_cache_misses'])) * 100, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>WhatsApp Bot Tracking - Zinema.lk Admin</title>
    
    <!-- Standard Admin CSS -->
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Inherit admin panel layout but restore VIBRANT dashboard styles */
        
        .tracking-dashboard {
            padding: 20px 0;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            margin-bottom: 25px;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        /* RESTORED: Gradient Stat Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            border-radius: 15px;
            padding: 25px;
            color: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            /* Reset typical admin card styles if any */
            background: white; 
            border: none;
        }
        
        .stat-card.green { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .stat-card.blue { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.purple { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #333; }
        .stat-card.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        
        .stat-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: inherit; /* Inherit white or dark based on card */
        }
        
        .stat-card .value {
            font-size: 36px;
            font-weight: bold;
            margin-bottom: 5px;
            color: inherit;
        }
        
        .stat-card .subtitle {
            font-size: 12px;
            opacity: 0.8;
            color: inherit;
        }
        
        /* RESTORED: Bot Cards with Status Borders */
        .bots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .bot-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
            border-left: 5px solid #ddd; /* Default border */
        }
        
        .bot-card.online { border-left-color: #10b981; }
        .bot-card.offline { border-left-color: #ef4444; }
        
        .bot-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .bot-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-badge.online { background: #d1fae5; color: #065f46; }
        .status-badge.offline { background: #fee2e2; color: #991b1b; }
        
        .bot-info { display: grid; gap: 10px; }
        .bot-info-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .bot-info-label { color: #6b7280; }
        .bot-info-value { font-weight: 600; color: #1f2937; }
        
        /* Section Titles */
        .section-title {
            font-size: 24px;
            font-weight: bold;
            margin: 40px 0 20px 0;
            color: #1f2937;
        }

        /* RESTORED: Table Styles */
        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table thead { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
        }
        .data-table th { 
            padding: 15px; 
            text-align: left; 
            font-weight: 600; 
            font-size: 14px;
            background: transparent; /* Override any admin.css specific background */
            color: white;
            text-transform: none;
        }
        .data-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid #f3f4f6; 
            font-size: 14px; 
            color: #4b5563;
        }
        .data-table tr:hover { background: #f9fafb; }
        
        /* Alerts */
        .alert-item {
            padding: 15px;
            background: white;
            border-left: 4px solid #f59e0b;
            margin-bottom: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        /* Progress Bar */
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
            display: block;
            margin-left: 0;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #059669);
            border-radius: 10px;
            transition: width 0.3s ease;
        }
        
        .cache-status {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        .cache-status.hot { background: #d1fae5; color: #065f46; }
        .cache-status.warm { background: #fef3c7; color: #92400e; }
        .cache-status.cold { background: #dbeafe; color: #1e40af; }
    </style>
</head>
<body>

    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <header>
            <h1>WhatsApp Bot Tracking</h1>
        </header>

        <div class="tracking-dashboard">
            <button class="refresh-btn" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i> Refresh Data
            </button>
            
            <!-- Overall Statistics -->
            <div class="stats-grid">
                <div class="stat-card green">
                    <h3>Total Forwards</h3>
                    <div class="value"><?php echo number_format($overall_stats['total_forwards'] ?? 0); ?></div>
                    <div class="subtitle">All-time deliveries</div>
                </div>
                
                <div class="stat-card blue">
                    <h3>Cache Hit Rate</h3>
                    <div class="value"><?php echo $cache_hit_rate; ?>%</div>
                    <div class="subtitle"><?php echo number_format($overall_stats['total_cache_hits'] ?? 0); ?> hot / <?php echo number_format($overall_stats['total_cache_misses'] ?? 0); ?> cold</div>
                </div>
                
                <div class="stat-card purple">
                    <h3>Data Saved</h3>
                    <div class="value"><?php echo number_format($overall_stats['total_data_saved_gb'] ?? 0, 1); ?> GB</div>
                    <div class="subtitle">Through smart caching</div>
                </div>
                
                <div class="stat-card orange">
                    <h3>Avg Response Time</h3>
                    <div class="value"><?php echo $overall_stats['avg_forward_time'] ?? 0; ?>s</div>
                    <div class="subtitle">Average forward time</div>
                </div>
            </div>
            
            <!-- Bot Health Status -->
            <h2 class="section-title">🟢 Bot Instance Health</h2>
            <div class="bots-grid">
                <?php 
                if ($bot_health_result && mysqli_num_rows($bot_health_result) > 0):
                    while($bot = mysqli_fetch_assoc($bot_health_result)): 
                        $status_class = strtolower($bot['status'] ?? 'offline');
                        $status_class = strtolower($bot['status'] ?? 'offline');
                        
                        // Use SQL calculated seconds difference to handle timezone mismatches
                        // If seconds_since_heartbeat is NULL or > 120 (2 mins), consider offline
                        $seconds_since = isset($bot['seconds_since_heartbeat']) ? (int)$bot['seconds_since_heartbeat'] : 9999;
                        $is_really_online = ($status_class === 'online' && $seconds_since < 120);
                        
                        // For display text "Last Seen"
                        $last_seen_mins = floor($seconds_since / 60);
                ?>
                <div class="bot-card <?php echo $is_really_online ? 'online' : 'offline'; ?>">
                    <div class="bot-header">
                        <div class="bot-title">Bot #<?php echo $bot['bot_id']; ?></div>
                        <span class="status-badge <?php echo $is_really_online ? 'online' : 'offline'; ?>">
                            <?php echo $is_really_online ? '🟢 Online' : '🔴 Offline'; ?>
                        </span>
                    </div>
                    
                    <div class="bot-info">
                        <div class="bot-info-item">
                            <span class="bot-info-label">📱 Phone</span>
                            <span class="bot-info-value"><?php echo $bot['bot_phone'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">📬 Queue Size</span>
                            <span class="bot-info-value"><?php echo $bot['queue_size'] ?? 0; ?> requests</span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">📊 Today's Requests</span>
                            <span class="bot-info-value"><?php echo $bot['total_requests_today'] ?? 0; ?></span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">✅ Success / ❌ Failed</span>
                            <span class="bot-info-value">
                                <?php echo $bot['successful_forwards_today'] ?? 0; ?> / <?php echo $bot['failed_forwards_today'] ?? 0; ?>
                            </span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">💾 Disk Usage</span>
                            <span class="bot-info-value"><?php echo round(($bot['disk_usage_mb'] ?? 0) / 1024, 1); ?> GB</span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">⏱️ Uptime</span>
                            <span class="bot-info-value">
                                <?php echo round(($bot['uptime_seconds'] ?? 0) / 3600, 1); ?> hours
                            </span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">🕐 Last Seen</span>
                            <span class="bot-info-value">
                                <?php echo $seconds_since < 300 ? ($seconds_since < 60 ? $seconds_since . ' sec ago' : $last_seen_mins . ' min ago') : 'Long time'; ?>
                            </span>
                        </div>
                        <div class="bot-info-item">
                            <span class="bot-info-label">🎯 Active Tokens</span>
                            <span class="bot-info-value">
                                <?php echo $token_distribution[$bot['bot_id']] ?? 0; ?>
                            </span>
                        </div>
                    </div>
                    
                    <?php if(!empty($bot['error_message'])): ?>
                    <div style="margin-top: 10px; padding: 10px; background: #fee2e2; border-radius: 8px; font-size: 12px; color: #991b1b;">
                        ⚠️ <?php echo htmlspecialchars($bot['error_message']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endwhile; 
                else: ?>
                    <div class="alert alert-warning w-100">No bot instances found in database. Please run the migration script.</div>
                <?php endif; ?>
            </div>
            
            <!-- Active Alerts -->
            <?php if($alerts_result && mysqli_num_rows($alerts_result) > 0): ?>
            <h2 class="section-title">⚠️ Active Alerts</h2>
            <div style="margin-bottom: 30px;">
                <?php while($alert = mysqli_fetch_assoc($alerts_result)): ?>
                <div class="alert-item <?php echo $alert['severity']; ?>">
                    <strong><?php echo strtoupper($alert['alert_type']); ?></strong> - 
                    <?php echo htmlspecialchars($alert['message']); ?>
                    <?php if($alert['bot_id']): ?>(Bot #<?php echo $alert['bot_id']; ?>)<?php endif; ?>
                    <span style="float: right; color: #6b7280; font-size: 12px;">
                        <?php echo date('M j, g:i A', strtotime($alert['created_at'])); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            </div>
            <?php endif; ?>
            
            <!-- Popular Content -->
            <h2 class="section-title">🔥 Popular Content (Top 20)</h2>
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>ID</th>
                            <th>Part</th>
                            <th>Total Forwards</th>
                            <th>Cache Hit Rate</th>
                            <th>Data Saved</th>
                            <th>Cache Status</th>
                            <th>Last Forwarded</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($popular_result):
                            while($content = mysqli_fetch_assoc($popular_result)): 
                            $hit_rate = round($content['cache_hit_rate'], 1);
                        ?>
                        <tr>
                            <td><?php echo ucfirst($content['content_type']); ?></td>
                            <td><?php echo $content['content_id']; ?></td>
                            <td><?php echo $content['part_number'] ?? '-'; ?></td>
                            <td><strong><?php echo number_format($content['total_forwards']); ?></strong></td>
                            <td>
                                <?php echo $hit_rate; ?>%
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo min($hit_rate, 100); ?>%"></div>
                                </div>
                            </td>
                            <td><?php echo number_format(($content['total_data_saved_mb'] ?? 0) / 1024, 2); ?> GB</td>
                            <td>
                                <span class="cache-status <?php echo strtolower($content['cache_status'] ?? 'unknown'); ?>">
                                    <?php echo strtoupper($content['cache_status'] ?? 'UNKNOWN'); ?>
                                </span>
                            </td>
                            <td><?php echo !empty($content['last_forwarded_at']) ? date('M j, g:i A', strtotime($content['last_forwarded_at'])) : 'Never'; ?></td>
                        </tr>
                        <?php endwhile; 
                        else: ?>
                        <tr><td colspan="8" style="text-align:center;">No popular content data available.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
