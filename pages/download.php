<?php
require_once '../includes/header.php';

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// TODO: Fetch movie details from database
$movie = [
    'title' => 'Sample Movie',
    'download_url' => '#'
];
?>

<div class="container mt-4">
    <div class="card shadow">
        <div class="card-body text-center">
            <h2 class="mb-4">Download <?php echo $movie['title']; ?></h2>
            
            <div id="countdown" class="display-4 mb-4">
                <span id="timer">10</span>
            </div>

            <p class="text-muted mb-4">Your download will begin automatically after the countdown.</p>
            
            <div id="download-button" style="display: none;">
                <a href="<?php echo $movie['download_url']; ?>" class="btn btn-lg btn-success">
                    <i class="fas fa-download"></i> Start Download
                </a>
            </div>
        </div>
    </div>
</div>

<script>
let timeLeft = 10;
const timerElement = document.getElementById('timer');
const downloadButton = document.getElementById('download-button');

const countdown = setInterval(() => {
    timeLeft--;
    timerElement.textContent = timeLeft;
    
    if (timeLeft <= 0) {
        clearInterval(countdown);
        document.getElementById('countdown').style.display = 'none';
        downloadButton.style.display = 'block';
    }
}, 1000);
</script>

<?php require_once '../includes/footer.php'; ?>