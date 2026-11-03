<?php
require_once 'config.php';
check_login();

// One number per query; a missing table shows 0 instead of breaking the dashboard
function dashboard_count($conn, $sql) {
    try {
        $result = $conn->query($sql);
        return $result ? (int)$result->fetch_row()[0] : 0;
    } catch (Throwable $e) {
        error_log('Dashboard count failed: ' . $e->getMessage());
        return 0;
    }
}

$widgets = [
    ['Movies', 'fa-film', dashboard_count($conn, "SELECT COUNT(*) FROM movies"), 'movies.php'],
    ['TV Series', 'fa-tv', dashboard_count($conn, "SELECT COUNT(*) FROM series"), 'series.php'],
    ['Episodes', 'fa-list-ol', dashboard_count($conn, "SELECT COUNT(*) FROM episodes"), 'episodes.php'],
    ['Collections', 'fa-layer-group', dashboard_count($conn, "SELECT COUNT(*) FROM collections"), 'collections.php'],
    ['Shots', 'fa-bolt', dashboard_count($conn, "SELECT COUNT(*) FROM shots"), 'manage_shots.php'],
    ['Trailers', 'fa-clapperboard', dashboard_count($conn, "SELECT COUNT(*) FROM trailers"), 'trailers.php'],
    ['Users', 'fa-users', dashboard_count($conn, "SELECT COUNT(*) FROM users"), null],
    ['Tokens today', 'fa-ticket', dashboard_count($conn, "SELECT COUNT(*) FROM whatsapp_tokens WHERE created_at >= CURDATE()"), 'whatsapp-bot-tracking.php'],
    ['Bots online', 'fa-robot', dashboard_count($conn, "SELECT COUNT(*) FROM bot_health_status WHERE status = 'online' AND last_heartbeat > NOW() - INTERVAL 2 MINUTE"), 'whatsapp-bot-tracking.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Dashboard - Zinema.lk Admin</title>
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo filemtime(__DIR__ . '/assets/admin.css'); ?>">
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
        </header>
        <section class="dashboard-widgets">
            <?php foreach ($widgets as [$label, $icon, $count, $link]): ?>
                <div class="widget">
                    <i class="fas <?php echo $icon; ?> widget-icon"></i>
                    <h2><?php echo $label; ?></h2>
                    <p><?php echo number_format($count); ?></p>
                    <?php if ($link): ?>
                        <a class="widget-link" href="<?php echo $link; ?>">Manage <i class="fas fa-arrow-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="form-container">
            <h2><i class="fas fa-plus-circle"></i> Quick actions</h2>
            <div class="quick-actions">
                <a href="movies.php"><i class="fas fa-film"></i> Add movie</a>
                <a href="series.php"><i class="fas fa-tv"></i> Add series</a>
                <a href="episodes.php"><i class="fas fa-list-ol"></i> Add episode</a>
                <a href="manage_shots.php"><i class="fas fa-bolt"></i> Add shot</a>
                <a href="trailers.php"><i class="fas fa-clapperboard"></i> Add trailer</a>
                <a href="settings.php"><i class="fas fa-gear"></i> Settings</a>
            </div>
        </section>
    </div>
</body>
</html>
