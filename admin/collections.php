<?php
require_once 'config.php';
check_login();

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    // Unset collection_id from movies before deleting collection
    $stmt = $conn->prepare("UPDATE movies SET collection_id = NULL WHERE collection_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT cover_image FROM collections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    if ($result && !empty($result['cover_image'])) {
        $filename = basename($result['cover_image']);
        if (file_exists('../uploads/' . $filename)) {
            unlink('../uploads/' . $filename);
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM collections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: collections.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $genre = $_POST['genre'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $cover_image_path = $_POST['existing_cover_image'] ?? '';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $target_dir = "../uploads/";
        $image_name = uniqid() . '_collection_' . basename($_FILES["cover_image"]["name"]);
        $target_file = $target_dir . $image_name;
        if (move_uploaded_file($_FILES["cover_image"]["tmp_name"], $target_file)) {
            $cover_image_path = BASE_URL . '/uploads/' . $image_name;
        }
    }

    if ($id > 0) { // Update
        $stmt = $conn->prepare("UPDATE collections SET title = ?, description = ?, genre = ?, cover_image = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $description, $genre, $cover_image_path, $id);
    } else { // Insert
        $stmt = $conn->prepare("INSERT INTO collections (title, description, genre, cover_image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $description, $genre, $cover_image_path);
    }
    $stmt->execute();
    header('Location: collections.php');
    exit;
}

$edit_collection = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM collections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $edit_collection = $stmt->get_result()->fetch_assoc();
}

$collections = $conn->query("SELECT * FROM collections ORDER BY created_at DESC");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../assets/images/tab-logo.png">
    <title>Zinema.lk</title>
    <link rel="stylesheet" href="assets/admin.css">
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Manage Collections</h1>
        </header>

        <section class="form-container">
            <h2><?php echo $edit_collection ? 'Edit Collection' : 'Add New Collection'; ?></h2>
            <form method="POST" action="collections.php" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $edit_collection['id'] ?? ''; ?>">
                <input type="hidden" name="existing_cover_image" value="<?php echo htmlspecialchars($edit_collection['cover_image'] ?? ''); ?>">
                
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($edit_collection['title'] ?? ''); ?>" required>
                </div>
                 <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"><?php echo htmlspecialchars($edit_collection['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Genre (comma-separated)</label>
                    <input type="text" name="genre" value="<?php echo htmlspecialchars($edit_collection['genre'] ?? ''); ?>" placeholder="e.g. Action,Adventure" required>
                </div>
                <div class="form-group">
                    <label>Cover Image</label>
                    <input type="file" name="cover_image" accept="image/*">
                     <?php if (!empty($edit_collection['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($edit_collection['cover_image']); ?>" width="100" style="margin-top:10px;">
                    <?php endif; ?>
                </div>

                <button type="submit"><?php echo $edit_collection ? 'Update Collection' : 'Add Collection'; ?></button>
                 <?php if ($edit_collection): ?>
                    <a href="collections.php" class="cancel-btn">Cancel Edit</a>
                <?php endif; ?>
            </form>
        </section>

        <section class="table-container">
            <h2>Existing Collections</h2>
            <table>
                <thead>
                    <tr>
                        <th>Cover</th>
                        <th>Title</th>
                        <th>Genre</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($collections) : while($c = $collections->fetch_assoc()): ?>
                    <tr>
                        <td><img src="<?php echo htmlspecialchars($c['cover_image']); ?>" width="100"></td>
                        <td><?php echo htmlspecialchars($c['title']); ?></td>
                        <td><?php echo htmlspecialchars($c['genre']); ?></td>
                        <td>
                            <a href="collections.php?edit=<?php echo $c['id']; ?>">Edit</a>
                            <a href="collections.php?delete=<?php echo $c['id']; ?>" onclick="return confirm('Are you sure? This will also remove movies from this collection.')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>