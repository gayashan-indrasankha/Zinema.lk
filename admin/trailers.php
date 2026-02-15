<?php
require_once 'config.php';
check_login();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                if (isset($_FILES['trailer_video'])) {
                    $movie_id = $_POST['movie_id'];
                    $title = $_POST['title'];
                    $description = $_POST['description'];
                    
                    // Handle file upload
                    $target_dir = "../uploads/trailers/";
                    $file_extension = pathinfo($_FILES["trailer_video"]["name"], PATHINFO_EXTENSION);
                    $video_filename = uniqid() . '.' . $file_extension;
                    $target_file = $target_dir . $video_filename;
                    
                    if (move_uploaded_file($_FILES["trailer_video"]["tmp_name"], $target_file)) {
                        $video_url = '/uploads/trailers/' . $video_filename;
                        
                        // Create thumbnail using ffmpeg if available
                        $thumbnail_url = null;
                        if (function_exists('exec')) {
                            $thumb_filename = uniqid() . '.jpg';
                            $thumb_path = $target_dir . $thumb_filename;
                            exec("ffmpeg -i $target_file -ss 00:00:01 -vframes 1 $thumb_path");
                            if (file_exists($thumb_path)) {
                                $thumbnail_url = '/uploads/trailers/' . $thumb_filename;
                            }
                        }
                        
                        // Insert into database
                        $stmt = $conn->prepare("INSERT INTO trailers (movie_id, title, description, video_url, thumbnail_url) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issss", $movie_id, $title, $description, $video_url, $thumbnail_url);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success'] = "Trailer added successfully!";
                        } else {
                            $_SESSION['error'] = "Error adding trailer: " . $stmt->error;
                        }
                        $stmt->close();
                    } else {
                        $_SESSION['error'] = "Error uploading file.";
                    }
                }
                break;
                
            case 'update':
                $id = $_POST['id'];
                $title = $_POST['title'];
                $description = $_POST['description'];
                $status = $_POST['status'];
                
                $stmt = $conn->prepare("UPDATE trailers SET title=?, description=?, status=? WHERE id=?");
                $stmt->bind_param("sssi", $title, $description, $status, $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Trailer updated successfully!";
                } else {
                    $_SESSION['error'] = "Error updating trailer: " . $stmt->error;
                }
                $stmt->close();
                break;
                
            case 'delete':
                $id = $_POST['id'];
                
                // Get video path before deleting
                $stmt = $conn->prepare("SELECT video_url, thumbnail_url FROM trailers WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($file_data = $result->fetch_assoc()) {
                    // Delete files
                    if (file_exists("../" . $file_data['video_url'])) {
                        unlink("../" . $file_data['video_url']);
                    }
                    if ($file_data['thumbnail_url'] && file_exists("../" . $file_data['thumbnail_url'])) {
                        unlink("../" . $file_data['thumbnail_url']);
                    }
                }
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM trailers WHERE id=?");
                $stmt->bind_param("i", $id);
                
                if ($stmt->execute()) {
                    $_SESSION['success'] = "Trailer deleted successfully!";
                } else {
                    $_SESSION['error'] = "Error deleting trailer: " . $stmt->error;
                }
                $stmt->close();
                break;
        }
    }
    header("Location: trailers.php");
    exit();
}

// Get all trailers with movie information
$query = "SELECT t.*, m.title as movie_title 
          FROM trailers t 
          JOIN movies m ON t.movie_id = m.id 
          ORDER BY t.created_at DESC";
$trailers = $conn->query($query);

// Get all movies for the add form
$movies = $conn->query("SELECT id, title FROM movies ORDER BY title");
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
        .trailer-preview {
            width: 200px;
            height: 356px; /* 16:9 aspect ratio */
            object-fit: cover;
            border-radius: 8px;
        }
        .trailer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .trailer-card {
            background: #f5f5f5;
            border-radius: 8px;
            padding: 10px;
            position: relative;
        }
        .trailer-actions {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .upload-form {
            max-width: 500px;
            margin: 20px 0;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <header>
            <h1>Manage Trailers</h1>
        </header>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert success">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Add Trailer Form -->
        <div class="upload-form">
            <h2>Add New Trailer</h2>
            <form action="trailers.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="movie_id">Movie:</label>
                    <select name="movie_id" id="movie_id" required>
                        <option value="">Select Movie</option>
                        <?php while($movie = $movies->fetch_assoc()): ?>
                            <option value="<?php echo $movie['id']; ?>">
                                <?php echo htmlspecialchars($movie['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Trailer Title:</label>
                    <input type="text" name="title" id="title" required>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" id="description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="trailer_video">Video File:</label>
                    <input type="file" name="trailer_video" id="trailer_video" 
                           accept="video/mp4,video/quicktime,video/webm" required>
                    <small>Recommended: Vertical format (9:16 aspect ratio), max 100MB</small>
                </div>

                <button type="submit" class="btn primary">Upload Trailer</button>
            </form>
        </div>

        <!-- Trailers Grid -->
        <div class="trailer-grid">
            <?php while($trailer = $trailers->fetch_assoc()): ?>
                <div class="trailer-card">
                    <video class="trailer-preview" 
                           src="<?php echo htmlspecialchars($trailer['video_url']); ?>"
                           poster="<?php echo htmlspecialchars($trailer['thumbnail_url']); ?>"
                           preload="metadata"
                           muted></video>
                    <h3><?php echo htmlspecialchars($trailer['title']); ?></h3>
                    <p>Movie: <?php echo htmlspecialchars($trailer['movie_title']); ?></p>
                    <p>Views: <?php echo number_format($trailer['views']); ?></p>
                    <p>Likes: <?php echo number_format($trailer['likes']); ?></p>
                    
                    <div class="trailer-actions">
                        <button class="btn" onclick="editTrailer(<?php echo $trailer['id']; ?>)">Edit</button>
                        <form action="trailers.php" method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $trailer['id']; ?>">
                            <button type="submit" class="btn danger" 
                                    onclick="return confirm('Are you sure you want to delete this trailer?')">
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <h2>Edit Trailer</h2>
            <form action="trailers.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label for="edit_title">Title:</label>
                    <input type="text" name="title" id="edit_title" required>
                </div>

                <div class="form-group">
                    <label for="edit_description">Description:</label>
                    <textarea name="description" id="edit_description" rows="3"></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select name="status" id="edit_status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <button type="submit" class="btn primary">Update</button>
                <button type="button" class="btn" onclick="closeEditModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        // Preview video on hover
        document.querySelectorAll('.trailer-preview').forEach(video => {
            video.addEventListener('mouseover', function() {
                this.play();
            });
            video.addEventListener('mouseout', function() {
                this.pause();
                this.currentTime = 0;
            });
        });

        // Edit modal functions
        function editTrailer(id) {
            // Fetch trailer data and populate form
            fetch(`/api/trailers.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_title').value = data.title;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_status').value = data.status;
                    document.getElementById('editModal').style.display = 'block';
                });
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal if clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>