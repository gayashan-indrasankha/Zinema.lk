<?php
require_once 'admin/config.php';

// Set headers to XML
header('Content-Type: application/xml; charset=utf-8');

// Helper function to create SEO-friendly slugs
function slugify($text)
{
    // Replace non-letter or digits by -
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    // Transliterate
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    // Remove unwanted characters
    $text = preg_replace('~[^-\w]+~', '', $text);
    // Trim
    $text = trim($text, '-');
    // Remove duplicate -
    $text = preg_replace('~-+~', '-', $text);
    // Lowercase
    $text = strtolower($text);

    if (empty($text)) {
        return 'n-a';
    }

    return $text;
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">

    <!-- Static Pages -->
    <url>
        <loc><?php echo BASE_URL; ?>/</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>/movies.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>/tv-series.php</loc>
        <changefreq>daily</changefreq>
        <priority>0.9</priority>
    </url>
    <url>
        <loc><?php echo BASE_URL; ?>/collections.php</loc>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>

    <!-- Movies -->
    <?php
    $movies_sql = "SELECT id, title, created_at FROM movies ORDER BY created_at DESC";
    $movies_result = $conn->query($movies_sql);

    if ($movies_result && $movies_result->num_rows > 0) {
        while ($movie = $movies_result->fetch_assoc()) {
            $slug = slugify($movie['title']);
            $url = BASE_URL . "/movie/" . $movie['id'] . "/" . $slug;
            $date = date('Y-m-d', strtotime($movie['created_at']));
            ?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
        }
    }
    ?>

    <!-- TV Series -->
    <?php
    $series_sql = "SELECT id, title, created_at FROM series ORDER BY created_at DESC";
    $series_result = $conn->query($series_sql);

    if ($series_result && $series_result->num_rows > 0) {
        while ($series = $series_result->fetch_assoc()) {
            $slug = slugify($series['title']);
            $url = BASE_URL . "/series/" . $series['id'] . "/" . $slug;
            $date = date('Y-m-d', strtotime($series['created_at']));
            ?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
    </url>
    <?php
        }
    }
    ?>

    <!-- Collections -->
    <?php
    $collections_sql = "SELECT id, title, created_at FROM collections ORDER BY created_at DESC";
    $collections_result = $conn->query($collections_sql);

    if ($collections_result && $collections_result->num_rows > 0) {
        while ($collection = $collections_result->fetch_assoc()) {
            $slug = slugify($collection['title']);
            $url = BASE_URL . "/collection/" . $collection['id'] . "/" . $slug;
            $date = date('Y-m-d', strtotime($collection['created_at']));
            ?>
    <url>
        <loc><?php echo htmlspecialchars($url); ?></loc>
        <lastmod><?php echo $date; ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
    </url>
    <?php
        }
    }
    ?>

</urlset>
