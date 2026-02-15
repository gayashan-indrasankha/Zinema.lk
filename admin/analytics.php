<?php
require_once 'config.php';
check_login();

// --- FETCH ANALYTICS DATA ---

// 1. Daily Views
$daily_views_result = $conn->query("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'page_view' AND DATE(timestamp) = CURDATE()");
$daily_views = $daily_views_result->fetch_assoc()['count'] ?? 0;

// 2. Monthly Views
$monthly_views_result = $conn->query("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'page_view' AND YEAR(timestamp) = YEAR(CURDATE()) AND MONTH(timestamp) = MONTH(CURDATE())");
$monthly_views = $monthly_views_result->fetch_assoc()['count'] ?? 0;

// 3. Daily Watch Time (in seconds)
$daily_watch_seconds_result = $conn->query("SELECT SUM(value) as total_seconds FROM analytics WHERE event_type = 'watch_time' AND DATE(timestamp) = CURDATE()");
$daily_watch_seconds = $daily_watch_seconds_result->fetch_assoc()['total_seconds'] ?? 0;
$daily_watch_hours = round($daily_watch_seconds / 3600, 2); // Convert to hours

// 4. Monthly Watch Time (in seconds)
$monthly_watch_seconds_result = $conn->query("SELECT SUM(value) as total_seconds FROM analytics WHERE event_type = 'watch_time' AND YEAR(timestamp) = YEAR(CURDATE()) AND MONTH(timestamp) = MONTH(CURDATE())");
$monthly_watch_seconds = $monthly_watch_seconds_result->fetch_assoc()['total_seconds'] ?? 0;
$monthly_watch_hours = round($monthly_watch_seconds / 3600, 2); // Convert to hours

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        /* Add some specific styles for the analytics page */
        .analytics-widgets {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        .widget .label {
            font-size: 1.2rem;
            color: #34495e;
            margin-bottom: 0.5rem;
        }
        .widget .value {
            font-size: 3rem;
            font-weight: 700;
            color: #3498db;
        }
         .widget .unit {
            font-size: 1.5rem;
            font-weight: normal;
            color: #7f8c8d;
            margin-left: 8px;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Website Analytics</h1>
        </header>
        
        <section class="analytics-widgets">
            <div class="widget">
                <h2 class="label">Page Views Today</h2>
                <p class="value"><?php echo number_format($daily_views); ?></p>
            </div>
            <div class="widget">
                <h2 class="label">Page Views This Month</h2>
                <p class="value"><?php echo number_format($monthly_views); ?></p>
            </div>
            <div class="widget">
                <h2 class="label">Watch Hours Today</h2>
                <p class="value"><?php echo $daily_watch_hours; ?><span class="unit">hrs</span></p>
            </div>
            <div class="widget">
                <h2 class="label">Watch Hours This Month</h2>
                <p class="value"><?php echo $monthly_watch_hours; ?><span class="unit">hrs</span></p>
            </div>
        </section>
        
        <div class="info-box" style="margin-top: 2rem; padding: 1.5rem; background: #eaf2f8; border-radius: 8px; border: 1px solid #d4e6f1;">
            <p style="margin: 0; color: #2874a6;">
                <strong>Note:</strong> Data tracking started when this feature was implemented. "Watch Hours" are calculated based on the time the video player is open on the screen. This provides a good estimate of user engagement.
            </p>
        </div>
    </div>
</body>
</html>