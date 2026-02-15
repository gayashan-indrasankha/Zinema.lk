<?php
// Include database connection
require_once 'admin/config.php';
require_once 'includes/ads.php';

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

        /* Ad Space */
        .ad-space {
            background: #111;
            border: 2px dashed #333;
            border-radius: 12px;
            padding: 60px 20px;
            margin: 20px 0;
            color: #666;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ad-space.top {
            margin-bottom: 30px;
        }

        .ad-space.middle {
            margin-top: 30px;
            margin-bottom: 30px;
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

        /* Countdown Timer */
        .countdown-timer {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin: 40px 0;
        }

        .timer-circle {
            position: relative;
            width: 150px;
            height: 150px;
            margin-bottom: 20px;
        }

        .timer-circle svg {
            transform: rotate(-90deg);
        }

        .timer-circle circle {
            fill: none;
            stroke-width: 8;
        }

        .timer-circle .bg-circle {
            stroke: #333;
        }

        .timer-circle .progress-circle {
            stroke: url(#gradient);
            stroke-linecap: round;
            transition: stroke-dashoffset 1s linear;
        }

        .timer-number {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            font-weight: 700;
            color: #fff;
            text-shadow: 0 0 25px rgba(37, 211, 102, 0.8);
        }

        .timer-label {
            color: #b4bcc9;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 2px;
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
                        <p>Preparing your download...</p>
                    </div>

                    <!-- Ad Space 1 (Top) -->
                    <?php renderRectangleAd(); ?>

                    <!-- Status Message -->
                    <div class="status-message" id="statusMessage">
                        <i class="fas fa-spinner fa-spin"></i>
                        <span>Please wait... Generating Link</span>
                    </div>

                    <!-- Countdown Timer -->
                    <div class="countdown-timer" id="countdownTimer">
                        <div class="timer-circle">
                            <svg width="150" height="150">
                                <defs>
                                    <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                        <stop offset="0%" style="stop-color:#25D366;stop-opacity:1" />
                                        <stop offset="100%" style="stop-color:#5FFC7B;stop-opacity:1" />
                                    </linearGradient>
                                </defs>
                                <circle class="bg-circle" cx="75" cy="75" r="70"></circle>
                                <circle class="progress-circle" cx="75" cy="75" r="70" 
                                        id="progressCircle"
                                        stroke-dasharray="440"
                                        stroke-dashoffset="0"></circle>
                            </svg>
                            <div class="timer-number" id="timerNumber">5</div>
                        </div>
                        <div class="timer-label">Seconds Remaining</div>
                    </div>

                    <!-- Ad Space 2 (Middle) -->
                    <?php renderRectangleAd(); ?>

                    <!-- Download Button (Hidden Initially) -->
                    <a href="#" onclick="openSmartLink('whatsapp-watch.php?id=<?php echo $id; ?>&type=<?php echo $content_type; ?>'); return false;" class="download-btn" id="downloadBtn">
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

    <script>
        const COUNTDOWN_DURATION = 8; // seconds
        let countdown = COUNTDOWN_DURATION;
        let countdownInterval = null;

        // Start countdown on page load
        window.addEventListener('DOMContentLoaded', function() {
            startCountdown();
        });

        function startCountdown() {
            const timerNumber = document.getElementById('timerNumber');
            const progressCircle = document.getElementById('progressCircle');
            const countdownTimer = document.getElementById('countdownTimer');
            const downloadBtn = document.getElementById('downloadBtn');
            const statusMessage = document.getElementById('statusMessage');
            
            const circumference = 2 * Math.PI * 70; // radius = 70
            
            countdownInterval = setInterval(function() {
                countdown--;
                
                // Update timer display
                timerNumber.textContent = countdown;
                
                // Update progress circle
                const progress = countdown / COUNTDOWN_DURATION;
                const offset = circumference * (1 - progress);
                progressCircle.style.strokeDashoffset = offset;
                
                // Check if countdown is complete
                if (countdown <= 0) {
                    clearInterval(countdownInterval);
                    
                    // Hide timer and update status message
                    countdownTimer.style.display = 'none';
                    statusMessage.innerHTML = '<i class="fas fa-check-circle"></i><span>Ready!</span>';
                    statusMessage.style.color = '#25D366';
                    
                    // Show download button
                    downloadBtn.classList.add('show');
                }
            }, 1000);
        }


        // Smart Link Ad Function
        const SMART_LINK_URL = '<?php echo getSmartLinkUrl(); ?>';
        
        function openSmartLink(destinationUrl) {
            // Only open smart link if URL is set (ads enabled)
            if (SMART_LINK_URL && SMART_LINK_URL.length > 0) {
                window.open(SMART_LINK_URL, '_blank');
            }
            // Navigate to destination after a short delay
            setTimeout(function() {
                window.location.href = destinationUrl;
            }, 100);
        }
    </script>

    <!-- Popunder Ad (High Revenue - Once per session) -->
    <?php renderPopunder(); ?>

</body>
</html>
