<?php
require_once 'config.php';
check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Dashboard</h1>
        </header>
        <section class="dashboard-widgets">
            <div class="widget">
                <h2>Total Movies</h2>
                <p><?php echo $conn->query("SELECT COUNT(*) FROM movies")->fetch_row()[0]; ?></p>
            </div>
            <div class="widget">
                <h2>Total TV Series</h2>
                <p><?php echo $conn->query("SELECT COUNT(*) FROM series")->fetch_row()[0]; ?></p>
            </div>
             <div class="widget">
                <h2>Total Collections</h2>
                <p><?php echo $conn->query("SELECT COUNT(*) FROM collections")->fetch_row()[0]; ?></p>
            </div>
        </section>
    </div>
</body>
</html>