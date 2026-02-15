<?php
require_once '../includes/header.php';

// Get movie ID from URL
$movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// TODO: Fetch movie details from database
$movie = [
    'title' => 'Sample Movie',
    'year' => '2023',
    'rating' => '8.5',
    'duration' => '2h 15min',
    'description' => 'This is a sample movie description.',
    'poster' => '/assets/images/sample-poster.jpg',
    'trailer_url' => 'https://www.youtube.com/embed/sample'
];
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>" class="img-fluid rounded shadow">
        </div>
        <div class="col-md-8">
            <h1 class="mb-3"><?php echo $movie['title']; ?> (<?php echo $movie['year']; ?>)</h1>
            <div class="mb-3">
                <span class="badge bg-primary me-2"><?php echo $movie['rating']; ?> / 10</span>
                <span class="badge bg-secondary"><?php echo $movie['duration']; ?></span>
            </div>
            <p class="lead"><?php echo $movie['description']; ?></p>
            
            <div class="mt-4">
                <a href="trailers.php?id=<?php echo $movie_id; ?>" class="btn btn-primary me-2">
                    <i class="fas fa-play-circle"></i> Watch Trailer
                </a>
                <a href="download.php?id=<?php echo $movie_id; ?>" class="btn btn-success">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    </div>

    <!-- Trailer Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h2 class="mb-4">Official Trailer</h2>
            <div class="ratio ratio-16x9">
                <iframe src="<?php echo $movie['trailer_url']; ?>" allowfullscreen></iframe>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>