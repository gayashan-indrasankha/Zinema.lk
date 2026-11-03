<?php
// Admin navigation: highlights the current page; on small screens it slides in from the left.
$admin_page = basename($_SERVER['PHP_SELF']);
$admin_nav = [
    'Overview' => [
        'index.php' => ['fa-gauge-high', 'Dashboard'],
        'analytics.php' => ['fa-chart-line', 'Analytics'],
        'whatsapp-bot-tracking.php' => ['fa-robot', 'WhatsApp Bots'],
    ],
    'Content' => [
        'movies.php' => ['fa-film', 'Movies'],
        'series.php' => ['fa-tv', 'TV Series'],
        'episodes.php' => ['fa-list-ol', 'Episodes'],
        'collections.php' => ['fa-layer-group', 'Collections'],
        'manage_shots.php' => ['fa-bolt', 'Shots'],
        'trailers.php' => ['fa-clapperboard', 'Trailers'],
    ],
    'System' => [
        'settings.php' => ['fa-gear', 'Settings'],
    ],
];
?>
<div class="admin-topbar">
    <button type="button" aria-label="Open menu" onclick="document.body.classList.add('sidebar-open')"><i class="fas fa-bars"></i></button>
    <img src="../assets/images/logo.png" alt="Zinema.lk">
</div>
<div class="sidebar-backdrop" onclick="document.body.classList.remove('sidebar-open')"></div>
<nav class="sidebar">
    <div class="sidebar-header">
        <img src="../assets/images/logo.png" alt="Zinema.lk">
        <h3>Admin</h3>
    </div>
    <?php foreach ($admin_nav as $section => $items): ?>
        <div class="nav-section"><?php echo $section; ?></div>
        <ul>
            <?php foreach ($items as $file => [$icon, $label]): ?>
                <li><a href="<?php echo $file; ?>"<?php echo $file === $admin_page ? ' class="active"' : ''; ?>><i class="fas <?php echo $icon; ?>"></i> <?php echo $label; ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
    <div class="sidebar-footer">
        <a href="../index.php" target="_blank" rel="noopener"><i class="fas fa-arrow-up-right-from-square"></i> View site</a>
        <a href="logout.php"><i class="fas fa-right-from-bracket"></i> Logout</a>
    </div>
</nav>
