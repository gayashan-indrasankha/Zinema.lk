<?php

// Include database connection
require_once 'admin/config.php';

// Get ID and type from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$type = isset($_GET['type']) ? $_GET['type'] : 'movie'; // Default to movie for backward compatibility

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
    $content['title'] = $content['series_title'] . ' - ' . $content['title']; // Combine series and episode title
    $content_type = 'episode';
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
}

// Set nav page for bottom navigation highlighting
$current_nav_page = ($content_type === 'episode') ? 'tv-series' : 'movies';

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/vendor/fontawesome-6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/desktop-tablet.css">
    <style>
        body {
            background-color: #000000 !important;
            background-image: none !important;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .app-container {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .download-page {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            padding: 40px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background-color: #000000;
        }

        .download-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 16px;
            padding: 40px 30px;
            text-align: center;
            width: 100%;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .movie-info {
            margin-bottom: 30px;
        }

        .movie-info h1 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #fff;
        }

        .movie-info p {
            color: #b4bcc9;
            font-size: 0.9rem;
        }

        /* Status Message */
        .status-message {
            font-size: 1.1rem;
            color: #b4bcc9;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .status-message i {
            color: #25D366;
            animation: pulse 1.5s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Download Button - Premium WhatsApp Theme */
        .download-btn {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: linear-gradient(135deg, rgba(37, 211, 102, 0.9), rgba(18, 140, 126, 0.85));
            color: white;
            border: none;
            padding: 18px 50px;
            border-radius: 10px;
            font-size: 1.15rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            width: 100%;
            max-width: 320px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }

        .download-btn:hover {
            background: linear-gradient(135deg, rgba(37, 211, 102, 1), rgba(18, 140, 126, 1));
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.5);
        }

        .download-btn:active {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 211, 102, 0.4);
        }

        .download-btn i {
            font-size: 1.4rem;
            color: #fff;
        }

        /* Show state */
        .download-btn.show {
            display: flex;
            animation: fadeInUp 0.5s ease, pulse-glow 2s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
            }
            50% {
                box-shadow: 0 4px 25px rgba(37, 211, 102, 0.5);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Back Button */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 40px;
            color: #b4bcc9;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }


        .back-link:hover {
            color: #25D366;
        }

        .back-link i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <div class="main-content">
            <div class="download-page">
                <div class="download-container">
                    <!-- Movie Info -->
                    <div class="movie-info">
                        <h1><?php echo htmlspecialchars($content['title']); ?></h1>
                        <p>Your download is ready</p>
                    </div>

                    <!-- Status Message -->
                    <div class="status-message" id="statusMessage" style="color: #25D366;">
                        <i class="fas fa-check-circle"></i>
                        <span>Ready!</span>
                    </div>

                    <!-- Download Button -->
                    <a href="whatsapp-watch.php?id=<?php echo $id; ?>&type=<?php echo $content_type; ?>" class="download-btn show" id="downloadBtn">
                        <i class="fab fa-whatsapp"></i>
                        <span id="btnText">Click Here</span>
                    </a>

                    <!-- Back Link -->
                    <a href="<?php echo $content_type === 'episode' ? 'series-details.php?id=' . $content['series_id'] : 'movie-details.php?id=' . $content['id']; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        Back to <?php echo $content_type === 'episode' ? 'Series' : 'Movie'; ?> Details
                    </a>
                </div>
            </div>
        </div>

        <!-- Bottom Navigation Bar -->
        <?php include '_bottom_nav.php'; ?>
    </div>
</body>
</html>
