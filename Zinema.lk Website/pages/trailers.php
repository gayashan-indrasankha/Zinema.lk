<?php
require_once '../includes/header.php';

// TODO: Fetch trailers from database
$trailers = [
    [
        'id' => 1,
        'title' => 'Movie 1',
        'trailer_url' => 'https://www.youtube.com/embed/sample1',
        'poster' => '/assets/images/poster1.jpg'
    ],
    [
        'id' => 2,
        'title' => 'Movie 2',
        'trailer_url' => 'https://www.youtube.com/embed/sample2',
        'poster' => '/assets/images/poster2.jpg'
    ]
];
?>

<div class="trailer-container vh-100 bg-dark">
    <div class="trailer-wrapper">
        <?php foreach ($trailers as $trailer): ?>
        <div class="trailer-slide" data-trailer-id="<?php echo $trailer['id']; ?>">
            <div class="ratio ratio-9x16">
                <iframe src="<?php echo $trailer['trailer_url']; ?>" allowfullscreen></iframe>
            </div>
            <div class="trailer-info">
                <h3><?php echo $trailer['title']; ?></h3>
                <a href="movie-detail.php?id=<?php echo $trailer['id']; ?>" class="btn btn-light btn-sm">
                    View Details
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
.trailer-container {
    position: relative;
    overflow: hidden;
}

.trailer-wrapper {
    height: 100%;
    scroll-snap-type: y mandatory;
    overflow-y: scroll;
    scrollbar-width: none;
}

.trailer-wrapper::-webkit-scrollbar {
    display: none;
}

.trailer-slide {
    height: 100vh;
    position: relative;
    scroll-snap-align: start;
}

.trailer-info {
    position: absolute;
    bottom: 20px;
    left: 20px;
    color: white;
    z-index: 100;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const trailerWrapper = document.querySelector('.trailer-wrapper');
    let currentTrailer = 0;
    const trailers = document.querySelectorAll('.trailer-slide');

    // Handle scroll events
    trailerWrapper.addEventListener('scroll', () => {
        const newTrailer = Math.round(trailerWrapper.scrollTop / window.innerHeight);
        if (newTrailer !== currentTrailer) {
            currentTrailer = newTrailer;
            // Pause all videos except current
            trailers.forEach((trailer, index) => {
                const iframe = trailer.querySelector('iframe');
                if (index === currentTrailer) {
                    iframe.contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*');
                } else {
                    iframe.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>