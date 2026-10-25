<?php
// Phase 2: quarantined before any bootstrap, SQL, logging or network activity.
http_response_code(403);
exit('Disabled during recovery.');


/**
 * Find Valid Movie IDs
 * Shows you which movie IDs exist in your database
 */

require_once 'admin/config.php';

echo "<h1>Available Movies for Testing</h1>";
echo "<hr>";

// Get all movies
$sql = "SELECT id, title, video_url FROM movies ORDER BY id ASC LIMIT 20";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<p style='color:green'>✅ Found " . $result->num_rows . " movies</p>";
    echo "<table border='1' cellpadding='10' style='border-collapse:collapse; width:100%'>";
    echo "<tr style='background:#333; color:white'>";
    echo "<th>ID</th>";
    echo "<th>Title</th>";
    echo "<th>Has Video URL?</th>";
    echo "<th>Actions</th>";
    echo "</tr>";
    
    while ($row = $result->fetch_assoc()) {
        $hasUrl = !empty($row['video_url']) ? '✅ Yes' : '❌ No';
        $urlColor = !empty($row['video_url']) ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td><strong>" . $row['id'] . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td style='color:$urlColor'>" . $hasUrl . "</td>";
        echo "<td>";
        
        if (!empty($row['video_url'])) {
            echo "<a href='test_video_url.php?id=" . $row['id'] . "' style='margin-right:10px'>🧪 Test</a>";
            echo "<a href='download.php?id=" . $row['id'] . "'>⬇️ Download</a>";
        } else {
            echo "<span style='color:#999'>No video URL</span>";
        }
        
        echo "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<h3>Quick Links:</h3>";
    $firstMovie = $conn->query("SELECT id FROM movies WHERE video_url != '' LIMIT 1")->fetch_assoc();
    if ($firstMovie) {
        $testId = $firstMovie['id'];
        echo "<p>✅ <a href='test_video_url.php?id=$testId'>Test with Movie ID $testId</a></p>";
        echo "<p>⬇️ <a href='download.php?id=$testId'>Try Download with Movie ID $testId</a></p>";
    }
    
} else {
    echo "<p style='color:red'>❌ No movies found in database!</p>";
    echo "<p>You need to import your database first.</p>";
}
?>
