<?php
require_once 'config.php';
check_login();
require_once __DIR__ . '/../includes/uploads.php';

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    $id = intval($_POST['delete']);
    // Unset collection_id from movies before deleting collection
    $stmt = $conn->prepare("UPDATE movies SET collection_id = NULL WHERE collection_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $stmt = $conn->prepare("SELECT cover_image FROM collections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    // Retain media; database paths never authorize filesystem deletion.
    $stmt = $conn->prepare("DELETE FROM collections WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header('Location: collections.php');
    exit;
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $genre = $_POST['genre'];
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    $cover_image_path = '';
    if ($id > 0) {
        $lookup = $conn->prepare("SELECT cover_image FROM collections WHERE id = ?");
        $lookup->bind_param("i", $id);
        $lookup->execute();
        $cover_image_path = $lookup->get_result()->fetch_assoc()['cover_image'] ?? '';
    }
    $cover_image_path = store_catalogue_upload('cover_image', 'image', $cover_image_path);

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
    <link rel="stylesheet" href="assets/admin.css?v=<?php echo substr(hash_file('sha256', __DIR__ . '/assets/admin.css'),0,12); ?>">
<?php if (function_exists('csrf_meta_tags')) csrf_meta_tags(); ?>
</head>
<body>
    <?php include '_layout.php'; ?>
    <div class="main-content">
        <header>
            <h1>Manage Collections</h1>
        </header>

        <section class="form-container">
            <h2><?php echo $edit_collection ? 'Edit Collection' : 'Add New Collection'; ?></h2>
            <form method="POST" action="collections.php" enctype="multipart/form-data"><?php csrf_token_field(); ?>
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
                            <form method="POST" action="collections.php" style="display:inline" onsubmit="return confirm('Delete this record?')"><?php csrf_token_field(); ?><input type="hidden" name="delete" value="<?php echo $c['id']; ?>"><button type="submit" class="btn btn-danger btn-sm">Delete</button></form>
                        </td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </section>
    </div>
</body>
</html>