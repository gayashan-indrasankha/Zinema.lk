<?php
require_once 'config.php';
check_login();

$admin_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT cover_image FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Delete the cover image file if it exists
    if ($result && !empty($result['cover_image'])) {
        $filename = basename($result['cover_image']);
        if (file_exists('../uploads/' . $filename)) {
            unlink('../uploads/' . $filename);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Movie deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting movie: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Add Movie
if (isset($_POST['add_movie'])) {
    $title = trim($_POST['title']);
    $release_date = trim($_POST['release_date']);
    $genre = trim($_POST['genre']);
    $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0.0;
    $video_url = trim($_POST['video_url']);

    $language_type = isset($_POST['language_type']) && !empty($_POST['language_type']) ? trim($_POST['language_type']) : null;
    $collection_id = isset($_POST['collection_id']) && !empty($_POST['collection_id']) ? intval($_POST['collection_id']) : null;
    
    $cover_image_path = '';

    // Handle file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $image_name = uniqid() . '_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_path = 'uploads/' . $image_name;
        }
    }

    // Insert movie
    $stmt = $conn->prepare("INSERT INTO movies (title, release_date, genre, rating, cover_image, video_url, language_type, collection_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdsssi", $title, $release_date, $genre, $rating, $cover_image_path, $video_url, $language_type, $collection_id);
    
    if ($stmt->execute()) {
        $message = "Movie added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding movie: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Edit Movie
if (isset($_POST['edit_movie'])) {
    $id = intval($_POST['movie_id']);
    $title = trim($_POST['title']);
    $release_date = trim($_POST['release_date']);
    $genre = trim($_POST['genre']);
    $rating = isset($_POST['rating']) ? floatval($_POST['rating']) : 0.0;
    $video_url = trim($_POST['video_url']);

    $language_type = isset($_POST['language_type']) && !empty($_POST['language_type']) ? trim($_POST['language_type']) : null;
    $collection_id = isset($_POST['collection_id']) && !empty($_POST['collection_id']) ? intval($_POST['collection_id']) : null;
    
    // Get current cover image
    $stmt = $conn->prepare("SELECT cover_image FROM movies WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $cover_image_path = $current['cover_image'];

    // Handle new file upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/";
        
        // Delete old cover image
        if (!empty($cover_image_path)) {
            $old_filename = basename($cover_image_path);
            if (file_exists($target_dir . $old_filename)) {
                unlink($target_dir . $old_filename);
            }
        }
        
        $image_name = uniqid() . '_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_path = 'uploads/' . $image_name;
        }
    }

    // Update movie
    $stmt = $conn->prepare("UPDATE movies SET title = ?, release_date = ?, genre = ?, rating = ?, cover_image = ?, video_url = ?, language_type = ?, collection_id = ? WHERE id = ?");
    $stmt->bind_param("sssdsssii", $title, $release_date, $genre, $rating, $cover_image_path, $video_url, $language_type, $collection_id, $id);
    
    if ($stmt->execute()) {
        $message = "Movie updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating movie: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all movies
$movies_result = $conn->query("SELECT * FROM movies ORDER BY created_at DESC");

// Fetch all collections for dropdown
$collections_result = $conn->query("SELECT id, title FROM collections ORDER BY title ASC");


// Get movie for editing if requested
$edit_movie = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_movie = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/admin.css">
    <style>
        .message {
            padding: 12px 20px;
            margin: 20px 0;
            border-radius: 8px;
            font-weight: 600;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <header>
            <h1><?php echo $edit_movie ? 'Edit Movie' : 'Manage Movies'; ?></h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="form-container">
            <h2><?php echo $edit_movie ? 'Edit Movie' : 'Add New Movie'; ?></h2>
            <form method="POST" action="movies.php" enctype="multipart/form-data">
                <?php if ($edit_movie): ?>
                    <input type="hidden" name="movie_id" value="<?php echo $edit_movie['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" 
                        value="<?php echo $edit_movie ? htmlspecialchars($edit_movie['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="text" name="release_date" id="release_date" placeholder="e.g. Nov. 16, 2001" 
                        value="<?php echo $edit_movie ? htmlspecialchars($edit_movie['release_date']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="genre">Genre (comma-separated)</label>
                    <input type="text" name="genre" id="genre" placeholder="e.g. Action, Adventure, Sci-Fi" 
                        value="<?php echo $edit_movie ? htmlspecialchars($edit_movie['genre']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="rating">Rating (0.0 - 10.0)</label>
                    <input type="number" name="rating" id="rating" step="0.1" min="0" max="10" placeholder="e.g. 7.5" 
                        value="<?php echo $edit_movie ? $edit_movie['rating'] : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="language_type">Language Type (Optional)</label>
                    <select name="language_type" id="language_type">
                        <option value="">-- Select Language Type --</option>
                        <option value="dubbed" <?php echo ($edit_movie && isset($edit_movie['language_type']) && $edit_movie['language_type'] == 'dubbed') ? 'selected' : ''; ?>>සිංහල හඬ කැවූ (Dubbed)</option>
                        <option value="subtitled" <?php echo ($edit_movie && isset($edit_movie['language_type']) && $edit_movie['language_type'] == 'subtitled') ? 'selected' : ''; ?>>සිංහල උපසිරැසි සමග (Subtitled)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="collection_id">Collection (Optional)</label>
                    <select name="collection_id" id="collection_id">
                        <option value="">-- Select Collection --</option>
                        <?php 
                        if ($collections_result && $collections_result->num_rows > 0) {
                            $collections_result->data_seek(0); // Reset pointer
                            while($collection = $collections_result->fetch_assoc()) {
                                $selected = ($edit_movie && isset($edit_movie['collection_id']) && $edit_movie['collection_id'] == $collection['id']) ? 'selected' : '';
                                echo '<option value="' . $collection['id'] . '" ' . $selected . '>' . htmlspecialchars($collection['title']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>

                
                <div class="form-group">
                    <label for="video_url">Streaming Video URL *</label>
                    <textarea name="video_url" id="video_url" rows="4" placeholder="Paste the streaming URL here (e.g., https://my-drive-streamer.indrasankag.workers.dev/FILE_ID)" 
                        style="font-family: monospace; font-size: 0.85em;" required><?php echo $edit_movie ? htmlspecialchars($edit_movie['video_url']) : ''; ?></textarea>
                    <small style="color: #888; display: block; margin-top: 5px;">
                        ℹ️ Paste the complete streaming URL for the movie.
                    </small>
                </div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*">
                    <?php if ($edit_movie && !empty($edit_movie['cover_image'])): ?>
                        <div style="margin-top: 10px;">
                            <small style="color: #888;">Current cover:</small><br>
                            <img src="<?php echo htmlspecialchars('../' . $edit_movie['cover_image']); ?>" 
                                alt="Current cover" width="100" style="border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="<?php echo $edit_movie ? 'edit_movie' : 'add_movie'; ?>">
                    <?php echo $edit_movie ? 'Update Movie' : 'Add Movie'; ?>
                </button>
                
                <?php if ($edit_movie): ?>
                    <a href="movies.php" style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-container">
            <h2>Existing Movies</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Release Date</th>
                        <th>Genre</th>
                        <th>Rating</th>
                        <th>Video URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($movies_result && $movies_result->num_rows > 0): ?>
                        <?php while($movie = $movies_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $movie['id']; ?></td>
                            <td>
                                <?php if (!empty($movie['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $movie['cover_image']); ?>" alt="Cover" width="50" style="border-radius: 5px;">
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                            <td><?php echo htmlspecialchars($movie['release_date']); ?></td>
                            <td><?php echo htmlspecialchars($movie['genre']); ?></td>
                            <td>
                                <span style="color: #ffc107;">⭐ <?php echo number_format($movie['rating'], 1); ?></span>
                            </td>
                            <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: 0.75em;" title="<?php echo htmlspecialchars($movie['video_url']); ?>">
                                <?php echo htmlspecialchars(substr($movie['video_url'], 0, 50)) . '...'; ?>
                            </td>
                            <td>
                                <a href="movies.php?edit=<?php echo $movie['id']; ?>">Edit</a> | 
                                <a href="movies.php?delete=<?php echo $movie['id']; ?>" onclick="return confirm('Are you sure you want to delete this movie?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px; color: #999;">No movies found. Add your first movie above!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
