<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/assets/images/tab-logo.png">
    <link rel="icon" href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/favicon.ico" type="image/x-icon">
    <link rel="apple-touch-icon" href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/assets/images/tab-logo.png">
    
    <!-- Dynamic Title -->
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Zinema.lk'; ?></title>
    
    <!-- Dynamic Meta Description -->
    <?php if (isset($meta_description)): ?>
        <meta name="description" content="<?php echo htmlspecialchars($meta_description); ?>">
    <?php endif; ?>
    
    <!-- Dynamic Open Graph Tags -->
    <?php if (isset($og_title)): ?><meta property="og:title" content="<?php echo htmlspecialchars($og_title); ?>"><?php endif; ?>
    <?php if (isset($og_description)): ?><meta property="og:description" content="<?php echo htmlspecialchars($og_description); ?>"><?php endif; ?>
    <?php if (isset($og_image)): ?><meta property="og:image" content="<?php echo htmlspecialchars($og_image); ?>"><?php endif; ?>
    <?php if (isset($og_url)): ?><meta property="og:url" content="<?php echo htmlspecialchars($og_url); ?>"><?php endif; ?>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo defined('BASE_URL') ? BASE_URL : ''; ?>/assets/css/style.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">Zinema.lk</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/"><i class="fas fa-home"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/movies.php"><i class="fas fa-film"></i> Movies</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/pages/trailers.php"><i class="fas fa-play-circle"></i> Trailers</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>