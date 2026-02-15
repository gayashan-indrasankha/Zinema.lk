<?php
require_once 'config.php';
check_login();

$admin_id = $_SESSION['admin_id'];
$message = '';
$message_type = '';

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("SELECT cover_image FROM series WHERE id = ?");
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
    
    $stmt = $conn->prepare("DELETE FROM series WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $message = "Series deleted successfully!";
        $message_type = "success";
    } else {
        $message = "Error deleting series: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Add Series
if (isset($_POST['add_series'])) {
    $title = trim($_POST['title']);
    $release_date = trim($_POST['release_date']);
    $genre = trim($_POST['genre']);
    $description = trim($_POST['description']);
    $language_type = isset($_POST['language_type']) && !empty($_POST['language_type']) ? trim($_POST['language_type']) : null;
    
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

    // Insert series
    $stmt = $conn->prepare("INSERT INTO series (title, release_date, genre, description, cover_image, language_type) VALUES (?, ?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        $message = "Error preparing statement: " . $conn->error;
        $message_type = "error";
    } else {
        $stmt->bind_param("ssssss", $title, $release_date, $genre, $description, $cover_image_path, $language_type);
    }
    
    if ($stmt && $stmt->execute()) {
        $message = "Series added successfully!";
        $message_type = "success";
    } elseif ($stmt) {
        $message = "Error adding series: " . $conn->error;
        $message_type = "error";
    }
}

// Handle Edit Series
if (isset($_POST['edit_series'])) {
    $id = intval($_POST['series_id']);
    $title = trim($_POST['title']);
    $release_date = trim($_POST['release_date']);
    $genre = trim($_POST['genre']);
    $description = trim($_POST['description']);
    $language_type = isset($_POST['language_type']) && !empty($_POST['language_type']) ? trim($_POST['language_type']) : null;
    
    // Get current cover image
    $stmt = $conn->prepare("SELECT cover_image FROM series WHERE id = ?");
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

    // Update series
    $stmt = $conn->prepare("UPDATE series SET title = ?, release_date = ?, genre = ?, description = ?, cover_image = ?, language_type = ? WHERE id = ?");
    $stmt->bind_param("ssssssi", $title, $release_date, $genre, $description, $cover_image_path, $language_type, $id);
    
    if ($stmt->execute()) {
        $message = "Series updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating series: " . $conn->error;
        $message_type = "error";
    }
}

// Fetch all series
$series_result = $conn->query("SELECT * FROM series ORDER BY created_at DESC");

// Get series for editing if requested
$edit_series = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM series WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_series = $stmt->get_result()->fetch_assoc();
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
            <h1><?php echo $edit_series ? 'Edit Series' : 'Manage TV Series'; ?></h1>
        </header>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <section class="form-container">
            <h2><?php echo $edit_series ? 'Edit Series' : 'Add New Series'; ?></h2>
            <form method="POST" action="series.php" enctype="multipart/form-data">
                <?php if ($edit_series): ?>
                    <input type="hidden" name="series_id" value="<?php echo $edit_series['id']; ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" 
                        value="<?php echo $edit_series ? htmlspecialchars($edit_series['title']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="release_date">Release Date</label>
                    <input type="text" name="release_date" id="release_date" placeholder="e.g. 2020" 
                        value="<?php echo $edit_series ? htmlspecialchars($edit_series['release_date']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="genre">Genre (comma-separated)</label>
                    <input type="text" name="genre" id="genre" placeholder="e.g. Drama, Thriller, Mystery" 
                        value="<?php echo $edit_series ? htmlspecialchars($edit_series['genre']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea name="description" id="description" rows="4" placeholder="Enter series description..." required><?php echo $edit_series ? htmlspecialchars($edit_series['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="language_type">Language Type (Optional)</label>
                    <select name="language_type" id="language_type">
                        <option value="">-- Select Language Type --</option>
                        <option value="dubbed" <?php echo ($edit_series && isset($edit_series['language_type']) && $edit_series['language_type'] == 'dubbed') ? 'selected' : ''; ?>>සිංහල හඬ කැවූ (Dubbed)</option>
                        <option value="subtitled" <?php echo ($edit_series && isset($edit_series['language_type']) && $edit_series['language_type'] == 'subtitled') ? 'selected' : ''; ?>>සිංහල උපසිරැසි සමග (Subtitled)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="cover_image">Cover Image</label>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*">
                    <?php if ($edit_series && !empty($edit_series['cover_image'])): ?>
                        <div style="margin-top: 10px;">
                            <small style="color: #888;">Current cover:</small><br>
                            <img src="<?php echo htmlspecialchars('../' . $edit_series['cover_image']); ?>" 
                                alt="Current cover" width="100" style="border-radius: 5px;">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" name="<?php echo $edit_series ? 'edit_series' : 'add_series'; ?>">
                    <?php echo $edit_series ? 'Update Series' : 'Add Series'; ?>
                </button>
                
                <?php if ($edit_series): ?>
                    <a href="series.php" style="margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-container">
            <h2>Existing TV Series</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Release Date</th>
                        <th>Genre</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($series_result && $series_result->num_rows > 0): ?>
                        <?php while($series = $series_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $series['id']; ?></td>
                            <td>
                                <?php if (!empty($series['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars('../' . $series['cover_image']); ?>" alt="Cover" width="50" style="border-radius: 5px;">
                                <?php else: ?>
                                    <span style="color: #999;">No image</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($series['title']); ?></td>
                            <td><?php echo htmlspecialchars($series['release_date']); ?></td>
                            <td><?php echo htmlspecialchars($series['genre']); ?></td>
                            <td style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo htmlspecialchars($series['description']); ?>
                            </td>
                            <td>
                                <a href="series.php?edit=<?php echo $series['id']; ?>">Edit</a> | 
                                <a href="series.php?delete=<?php echo $series['id']; ?>" onclick="return confirm('Are you sure you want to delete this series?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 20px; color: #999;">No TV series found. Add your first series above!</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>
