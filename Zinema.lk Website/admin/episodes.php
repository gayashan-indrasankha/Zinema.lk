<?php
require_once 'config.php';
check_login();

$admin_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

// Handle Delete Episode
if (isset($_GET['delete_episode'])) {
    $id = intval($_GET['delete_episode']);
    $stmt = $conn->prepare("SELECT thumb_image FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Delete the thumbnail file if it exists
    if ($result && !empty($result['thumb_image'])) {
        $filename = basename($result['thumb_image']);
        if (file_exists('../uploads/' . $filename)) {
            unlink('../uploads/' . $filename);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Episode deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting episode: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Add Episode
if (isset($_POST['add_episode'])) {
    $series_id = intval($_POST['series_id']);
    $season_number = intval($_POST['season_number']);
    $episode_number = intval($_POST['episode_number']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $video_url = trim($_POST['video_url']);
    $air_date = !empty($_POST['air_date']) ? $_POST['air_date'] : null;
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : null;
    
    $thumb_image_path = '';

    // Handle thumbnail upload
    if (isset($_FILES['thumb_image']) && $_FILES['thumb_image']['error'] == 0) {
        $target_dir = "../uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }
        
        $image_name = uniqid() . '_thumb_' . basename($_FILES["thumb_image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["thumb_image"]["tmp_name"], $target_file)) {
            $thumb_image_path = 'https://sinhalamovies.web.lk/uploads/' . $image_name;
        }
    }

    // Insert episode
    $stmt = $conn->prepare("INSERT INTO episodes (series_id, season_number, episode_number, title, description, video_url, thumb_image, air_date, duration) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisssssi", $series_id, $season_number, $episode_number, $title, $description, $video_url, $thumb_image_path, $air_date, $duration);
    
    if ($stmt->execute()) {
        $message = "Episode added successfully!";
        $message_type = "success";
    } else {
        $message = "Error adding episode: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Edit Episode
if (isset($_POST['edit_episode'])) {
    $id = intval($_POST['episode_id']);
    $series_id = intval($_POST['series_id']);
    $season_number = intval($_POST['season_number']);
    $episode_number = intval($_POST['episode_number']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $video_url = trim($_POST['video_url']);
    $air_date = !empty($_POST['air_date']) ? $_POST['air_date'] : null;
    $duration = !empty($_POST['duration']) ? intval($_POST['duration']) : null;
    
    // Get current thumbnail
    $stmt = $conn->prepare("SELECT thumb_image FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();
    $thumb_image_path = $current['thumb_image'];

    // Handle new thumbnail upload
    if (isset($_FILES['thumb_image']) && $_FILES['thumb_image']['error'] == 0) {
        $target_dir = "../uploads/";
        
        // Delete old thumbnail
        if (!empty($thumb_image_path)) {
            $old_filename = basename($thumb_image_path);
            if (file_exists($target_dir . $old_filename)) {
                unlink($target_dir . $old_filename);
            }
        }
        
        $image_name = uniqid() . '_thumb_' . basename($_FILES["thumb_image"]["name"]);
        $target_file = $target_dir . $image_name;
        
        if (move_uploaded_file($_FILES["thumb_image"]["tmp_name"], $target_file)) {
            $thumb_image_path = 'https://sinhalamovies.web.lk/uploads/' . $image_name;
        }
    }

    // Update episode
    $stmt = $conn->prepare("UPDATE episodes SET series_id = ?, season_number = ?, episode_number = ?, title = ?, description = ?, video_url = ?, thumb_image = ?, air_date = ?, duration = ? WHERE id = ?");
    $stmt->bind_param("iiisssssii", $series_id, $season_number, $episode_number, $title, $description, $video_url, $thumb_image_path, $air_date, $duration, $id);
    
    if ($stmt->execute()) {
        $message = "Episode updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating episode: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all series for dropdown
$series_result = $conn->query("SELECT id, title FROM series ORDER BY title ASC");

// Fetch all episodes with series info
$episodes_result = $conn->query("
    SELECT e.*, s.title as series_title 
    FROM episodes e 
    LEFT JOIN series s ON e.series_id = s.id 
    ORDER BY s.title ASC, e.season_number ASC, e.episode_number ASC
");

// Get episode for editing if requested
$edit_episode = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM episodes WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_episode = $stmt->get_result()->fetch_assoc();
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
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .episode-thumbnail {
            width: 80px;
            height: 45px;
            object-fit: cover;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <header>
            <h1><?php echo $edit_episode ? 'Edit Episode' : 'Manage Episodes'; ?></h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="form-container">
            <h2><?php echo $edit_episode ? 'Edit Episode' : 'Add New Episode'; ?></h2>
            <form method="POST" action="episodes.php" enctype="multipart/form-data">
                <?php if ($edit_episode): ?>
                    <input type="hidden" name="episode_id" value="<?php echo $edit_episode['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="series_id">Series *</label>
                    <select name="series_id" id="series_id" required>
                        <option value="">Select Series</option>
                        <?php 
                        $series_result->data_seek(0);
                        while($series = $series_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $series['id']; ?>" 
                                <?php echo ($edit_episode && $edit_episode['series_id'] == $series['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($series['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="season_number">Season Number *</label>
                        <input type="number" name="season_number" id="season_number" min="1" 
                            value="<?php echo $edit_episode ? $edit_episode['season_number'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="episode_number">Episode Number *</label>
                        <input type="number" name="episode_number" id="episode_number" min="1" 
                            value="<?php echo $edit_episode ? $edit_episode['episode_number'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="title">Episode Title *</label>
                    <input type="text" name="title" id="title" 
                        value="<?php echo $edit_episode ? htmlspecialchars($edit_episode['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="3" placeholder="Enter episode description..."><?php echo $edit_episode ? htmlspecialchars($edit_episode['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="video_url">Streaming Video URL *</label>
                    <textarea name="video_url" id="video_url" rows="4" 
                        placeholder="Paste the streaming URL here (e.g., https://my-drive-streamer.indrasankag.workers.dev/FILE_ID)"
                        style="font-family: monospace; font-size: 0.85em;" required><?php echo $edit_episode ? htmlspecialchars($edit_episode['video_url']) : ''; ?></textarea>
                    <small style="color: #888; display: block; margin-top: 5px;">
                        ℹ️ Paste the complete streaming URL for the episode.
                    </small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="air_date">Air Date</label>
                        <input type="text" name="air_date" id="air_date" 
                            placeholder="e.g., 2024-01-15"
                            value="<?php echo $edit_episode ? $edit_episode['air_date'] : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="duration">Duration (minutes)</label>
                        <input type="number" name="duration" id="duration" min="1" 
                            placeholder="e.g., 59" 
                            value="<?php echo $edit_episode ? $edit_episode['duration'] : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="thumb_image">Thumbnail Image</label>
                    <input type="file" name="thumb_image" id="thumb_image" accept="image/*">
                    <?php if ($edit_episode && !empty($edit_episode['thumb_image'])): ?>
                        <div style="margin-top: 10px;">
                            <small style="color: #888;">Current thumbnail:</small><br>
                            <img src="<?php echo htmlspecialchars($edit_episode['thumb_image']); ?>" 
                                alt="Current thumbnail" class="episode-thumbnail">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="<?php echo $edit_episode ? 'edit_episode' : 'add_episode'; ?>">
                    <?php echo $edit_episode ? 'Update Episode' : 'Add Episode'; ?>
                </button>
                
                <?php if ($edit_episode): ?>
                    <a href="episodes.php" style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-container">
            <h2>Existing Episodes</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Thumbnail</th>
                        <th>Series</th>
                        <th>Season</th>
                        <th>Episode</th>
                        <th>Title</th>
                        <th>Video URL</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($episodes_result && $episodes_result->num_rows > 0): ?>
                        <?php while($episode = $episodes_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $episode['id']; ?></td>
                            <td>
                                <?php if (!empty($episode['thumb_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($episode['thumb_image']); ?>" 
                                        alt="Thumbnail" class="episode-thumbnail">
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($episode['series_title']); ?></td>
                            <td><?php echo $episode['season_number']; ?></td>
                            <td><?php echo $episode['episode_number']; ?></td>
                            <td><?php echo htmlspecialchars($episode['title']); ?></td>
                            <td style="font-family: monospace; font-size: 0.85em;">
                                <?php echo htmlspecialchars(substr($episode['video_url'], 0, 15)) . '...'; ?>
                            </td>
                            <td><?php echo $episode['duration'] ? $episode['duration'] . 'm' : '-'; ?></td>
                            <td>
                                <a href="episodes.php?edit=<?php echo $episode['id']; ?>">Edit</a> | 
                                <a href="episodes.php?delete_episode=<?php echo $episode['id']; ?>" 
                                    onclick="return confirm('Are you sure you want to delete this episode?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 20px; color: #999;">
                                No episodes found. Add your first episode above!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
