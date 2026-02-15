<?php
require_once 'config.php';
check_login();
$admin_id = $_SESSION['admin_id'];
$message = '';
$error = '';

// ============================================
// API CONFIGURATION
// ============================================
// Set your Render.com API URL here after deployment
$api_url = defined('FB_VIDEO_API_URL') ? FB_VIDEO_API_URL : 'https://fb-video-api-2nte.onrender.com';

// Get shot for editing if requested
$edit_shot = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM shots WHERE id = ?");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $edit_shot = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// Handle form submission (both add and edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_shot'])) {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content_type = $_POST['content_type'];
    $linked_content_id = intval($_POST['linked_content_id']);
    $shot_id = isset($_POST['shot_id']) ? intval($_POST['shot_id']) : null;
    $is_edit_mode = !empty($shot_id);
    
    // Get Facebook share URL from form
    $fb_share_url = trim($_POST['fb_share_url']);
    
    // Validate basic inputs
    if (empty($title) || empty($content_type) || $linked_content_id <= 0) {
        $error = 'Please fill in all required fields and select a movie/series.';
    } elseif (empty($fb_share_url)) {
        $error = 'Please provide a Facebook Share URL.';
    } elseif (!preg_match('/facebook\.com|fb\.com/i', $fb_share_url)) {
        $error = 'Please provide a valid Facebook URL.';
    } else {
        // Try to extract CDN URL for initial cache (optional, non-blocking)
        $cached_cdn_url = '';
        $extract_url = $api_url . '/api/extract?url=' . urlencode($fb_share_url);
        
        $ch = curl_init($extract_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['success']) && $data['success'] && !empty($data['url'])) {
                $cached_cdn_url = $data['url'];
            }
        }
        
        // Proceed with database operations
        if ($is_edit_mode) {
            // Update existing shot
            $sql = "UPDATE shots SET title = ?, description = ?, fb_share_url = ?, shot_video_file = ?, linked_content_type = ?, linked_content_id = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssii", $title, $description, $fb_share_url, $cached_cdn_url, $content_type, $linked_content_id, $shot_id);
            
            if ($stmt->execute()) {
                $message = "✅ Success! Shot updated successfully.";
                header("Location: manage_shots.php");
                exit;
            } else {
                $error = 'Error updating shot: ' . $conn->error;
            }
            $stmt->close();
        } else {
            // Verify admin_id exists and is valid
            if (empty($admin_id)) {
                $error = 'Error: Admin session not found. Please log out and log in again.';
            } else {
                // Verify admin exists in database
                $check_admin = $conn->prepare("SELECT id FROM admins WHERE id = ?");
                $check_admin->bind_param("i", $admin_id);
                $check_admin->execute();
                $admin_result = $check_admin->get_result();
                
                if ($admin_result->num_rows === 0) {
                    $error = 'Error: Invalid admin account. Please contact administrator.';
                    $check_admin->close();
                } else {
                    $check_admin->close();
                    
                    // Insert new shot
                    $sql = "INSERT INTO shots (title, description, fb_share_url, shot_video_file, linked_content_type, linked_content_id, admin_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sssssii", $title, $description, $fb_share_url, $cached_cdn_url, $content_type, $linked_content_id, $admin_id);
                    
                    if ($stmt->execute()) {
                        $message = "✅ Success! Shot added successfully.";
                    } else {
                        $error = 'Error adding shot to database: ' . $conn->error;
                    }
                    $stmt->close();
                }
            }
        }
    }
}

// Handle delete request
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);
    
    $delete_sql = "DELETE FROM shots WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $delete_id);
    
    if ($delete_stmt->execute()) {
        $message = 'Shot deleted successfully!';
    } else {
        $error = 'Error deleting shot: ' . $conn->error;
    }
    $delete_stmt->close();
}

// Fetch all existing shots
$shots = [];
$shots_sql = "SELECT s.*, 
              CASE 
                  WHEN s.linked_content_type = 'movie' THEN m.title
                  WHEN s.linked_content_type = 'series' THEN ser.title
                  WHEN s.linked_content_type = 'collection' THEN c.title
              END as content_title
              FROM shots s
              LEFT JOIN movies m ON s.linked_content_type = 'movie' AND s.linked_content_id = m.id
              LEFT JOIN series ser ON s.linked_content_type = 'series' AND s.linked_content_id = ser.id
              LEFT JOIN collections c ON s.linked_content_type = 'collection' AND s.linked_content_id = c.id
              ORDER BY s.created_at DESC";
$shots_result = $conn->query($shots_sql);

if ($shots_result && $shots_result->num_rows > 0) {
    while ($row = $shots_result->fetch_assoc()) {
        $shots[] = $row;
    }
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
        .search-results {
            position: absolute;
            background: white;
            border: 1px solid #ddd;
            max-height: 200px;
            overflow-y: auto;
            width: 100%;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .search-results div {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        
        .search-results div:hover {
            background: #f5f5f5;
        }
        
        .selected-content {
            margin-top: 10px;
            padding: 10px;
            background: #e8f5e9;
            border: 1px solid #4caf50;
            border-radius: 4px;
            display: none;
        }
        
        .radio-group {
            display: flex;
            gap: 20px;
            margin: 10px 0;
        }
        
        .radio-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        
        .form-control[type="file"] {
            padding: 8px;
        }

        /* API Health Check Styles */
        .api-status-card {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        
        .api-status-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .api-status-indicator {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ffc107;
            animation: pulse 2s infinite;
        }
        
        .api-status-indicator.online {
            background: #4caf50;
        }
        
        .api-status-indicator.offline {
            background: #f44336;
            animation: none;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .api-status-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .api-stat {
            background: rgba(255,255,255,0.1);
            padding: 12px;
            border-radius: 8px;
        }
        
        .api-stat-label {
            font-size: 0.75em;
            opacity: 0.7;
            text-transform: uppercase;
        }
        
        .api-stat-value {
            font-size: 1.2em;
            font-weight: bold;
            margin-top: 4px;
        }
        
        .api-url-display {
            font-family: monospace;
            font-size: 0.85em;
            background: rgba(255,255,255,0.1);
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 10px;
            word-break: break-all;
        }
        
        .btn-check-api {
            background: #2196f3;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
        }
        
        .btn-check-api:hover {
            background: #1976d2;
        }
        
        .btn-check-api:disabled {
            background: #666;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <?php include '_layout.php'; ?>
    
    <div class="main-content">
        <div class="page-header">
            <h1>🎬 Manage Shots</h1>
            <p>Create and manage TikTok-style movie/series shots</p>
        </div>

        <!-- API Health Check Card -->
        <div class="api-status-card">
            <div class="api-status-header">
                <div class="api-status-indicator" id="api-indicator"></div>
                <div>
                    <h3 style="margin: 0;">🔌 FB Video API Status</h3>
                    <small style="opacity: 0.7;">Video CDN extraction service</small>
                </div>
                <button class="btn-check-api" id="btn-check-api" onclick="checkApiHealth()">
                    🔄 Check Status
                </button>
            </div>
            
            <div class="api-url-display">
                <strong>API URL:</strong> <?php echo htmlspecialchars($api_url); ?>
            </div>
            
            <div class="api-status-details" id="api-details" style="margin-top: 15px; display: none;">
                <div class="api-stat">
                    <div class="api-stat-label">Status</div>
                    <div class="api-stat-value" id="api-status-text">--</div>
                </div>
                <div class="api-stat">
                    <div class="api-stat-label">Uptime</div>
                    <div class="api-stat-value" id="api-uptime">--</div>
                </div>
                <div class="api-stat">
                    <div class="api-stat-label">Response Time</div>
                    <div class="api-stat-value" id="api-response-time">--</div>
                </div>
                <div class="api-stat">
                    <div class="api-stat-label">Version</div>
                    <div class="api-stat-value" id="api-version">--</div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Shot Form -->
        <div class="card">
            <div class="card-header">
                <h2><?php echo $edit_shot ? '✏️ Edit Shot' : '➕ Add New Shot'; ?></h2>
            </div>
            <div class="card-body">
                <form action="manage_shots.php" method="post" enctype="multipart/form-data">
                    <?php if ($edit_shot): ?>
                        <input type="hidden" name="shot_id" value="<?php echo $edit_shot['id']; ?>">
                    <?php endif; ?>
                    
                    <!-- Shot Title -->
                    <div class="form-group">
                        <label for="title">Shot Title *</label>
                        <input type="text" name="title" id="title" class="form-control" required placeholder="Enter shot title..." value="<?php echo $edit_shot ? htmlspecialchars($edit_shot['title']) : ''; ?>">
                    </div>

                    <!-- Shot Description -->
                    <div class="form-group">
                        <label for="description">Shot Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="Enter shot description/caption..."><?php echo $edit_shot ? htmlspecialchars($edit_shot['description']) : ''; ?></textarea>
                    </div>

                    <!-- Facebook Share URL (NEW) -->
                    <div class="form-group">
                        <label for="fb_share_url">📎 Facebook Share URL *</label>
                        <textarea name="fb_share_url" id="fb_share_url" class="form-control" rows="2" 
                            placeholder="Paste the Facebook share URL here (e.g., https://www.facebook.com/share/v/1AKqPLQU7T/)" 
                            style="font-family: monospace; font-size: 0.9em;" required><?php echo $edit_shot && isset($edit_shot['fb_share_url']) ? htmlspecialchars($edit_shot['fb_share_url']) : ''; ?></textarea>
                        <small style="color: #888; display: block; margin-top: 5px;">
                            ℹ️ Paste the Facebook video share URL. The system will automatically extract the CDN link for playback.
                        </small>
                    </div>

                    <!-- Test URL Button -->
                    <div class="form-group">
                        <button type="button" class="btn" style="background: #ff9800; color: white;" onclick="testVideoUrl()">
                            🧪 Test URL Extraction
                        </button>
                        <span id="test-result" style="margin-left: 10px;"></span>
                    </div>

                    <!-- Content Type Radio Buttons -->
                    <div class="form-group">
                        <label>Content Type *</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="content_type" value="movie" <?php echo (!$edit_shot || $edit_shot['linked_content_type'] === 'movie') ? 'checked' : ''; ?> onchange="clearSearch()">
                                <span>🎥 Movie</span>
                            </label>
                            <label>
                                <input type="radio" name="content_type" value="series" <?php echo ($edit_shot && $edit_shot['linked_content_type'] === 'series') ? 'checked' : ''; ?> onchange="clearSearch()">
                                <span>📺 TV Series</span>
                            </label>
                            <label>
                                <input type="radio" name="content_type" value="collection" <?php echo ($edit_shot && $edit_shot['linked_content_type'] === 'collection') ? 'checked' : ''; ?> onchange="clearSearch()">
                                <span>📚 Collection</span>
                            </label>
                        </div>
                    </div>

                    <!-- Search Movie/Series -->
                    <div class="form-group" style="position: relative;">
                        <label for="content-search">Search Movie/Series *</label>
                        <input type="text" id="content-search" class="form-control" placeholder="Type to search..." autocomplete="off">
                        <div id="search-results" class="search-results"></div>
                        
                        <!-- Selected Content Display -->
                        <div id="selected-content" class="selected-content">
                            <strong>Selected:</strong> <span id="selected-title"></span>
                        </div>
                        
                        <!-- Hidden Input for Linked Content ID -->
                        <input type="hidden" name="linked_content_id" id="linked-content-id" required>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" name="submit_shot" class="btn btn-primary">
                            <?php echo $edit_shot ? '💾 Update Shot' : '➕ Add Shot'; ?>
                        </button>
                        <?php if ($edit_shot): ?>
                            <a href="manage_shots.php" class="btn" style="margin-left: 10px; background: #6c757d; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; display: inline-block;">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

        <!-- Existing Shots Table -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h2>📋 Existing Shots</h2>
            </div>
            <div class="card-body">
                <?php if (!empty($shots)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Share URL</th>
                                <th>Type</th>
                                <th>Linked Content</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shots as $shot): ?>
                                <tr>
                                    <td><?php echo $shot['id']; ?></td>
                                    <td><?php echo htmlspecialchars($shot['title']); ?></td>
                                    <td style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: 0.75em;" title="<?php echo htmlspecialchars($shot['fb_share_url'] ?? $shot['shot_video_file']); ?>">
                                        <?php 
                                        $url = $shot['fb_share_url'] ?? $shot['shot_video_file'] ?? '';
                                        echo htmlspecialchars(strlen($url) > 50 ? substr($url, 0, 50) . '...' : $url); 
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $shot['linked_content_type'] === 'movie' ? 'blue' : 
                                                ($shot['linked_content_type'] === 'series' ? 'purple' : 'green'); 
                                        ?>">
                                            <?php echo ucfirst($shot['linked_content_type']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($shot['content_title'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($shot['created_at'])); ?></td>
                                    <td>
                                        <a href="?edit=<?php echo $shot['id']; ?>" class="btn btn-primary btn-sm">
                                            ✏️ Edit
                                        </a>
                                        <a href="?delete=<?php echo $shot['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this shot?')">
                                            🗑️ Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">No shots found. Add your first shot above!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // API HEALTH CHECK
        // ============================================
        const API_URL = '<?php echo $api_url; ?>';
        
        async function checkApiHealth() {
            const btn = document.getElementById('btn-check-api');
            const indicator = document.getElementById('api-indicator');
            const details = document.getElementById('api-details');
            
            btn.disabled = true;
            btn.textContent = '⏳ Checking...';
            indicator.className = 'api-status-indicator';
            
            const startTime = Date.now();
            
            try {
                const response = await fetch(API_URL + '/health', {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                
                const responseTime = Date.now() - startTime;
                const data = await response.json();
                
                // Update UI
                indicator.classList.add('online');
                document.getElementById('api-status-text').textContent = '✅ Online';
                document.getElementById('api-uptime').textContent = formatUptime(data.uptime);
                document.getElementById('api-response-time').textContent = responseTime + 'ms';
                document.getElementById('api-version').textContent = data.version || 'Unknown';
                details.style.display = 'grid';
                
            } catch (error) {
                indicator.classList.add('offline');
                document.getElementById('api-status-text').textContent = '❌ Offline';
                document.getElementById('api-uptime').textContent = '--';
                document.getElementById('api-response-time').textContent = '--';
                document.getElementById('api-version').textContent = '--';
                details.style.display = 'grid';
            }
            
            btn.disabled = false;
            btn.textContent = '🔄 Check Status';
        }
        
        function formatUptime(seconds) {
            if (!seconds) return '--';
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            if (hours > 24) {
                const days = Math.floor(hours / 24);
                return days + 'd ' + (hours % 24) + 'h';
            }
            return hours + 'h ' + minutes + 'm';
        }
        
        // Auto-check on page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(checkApiHealth, 500);
        });

        // ============================================
        // TEST VIDEO URL EXTRACTION
        // ============================================
        async function testVideoUrl() {
            const fbUrl = document.getElementById('fb_share_url').value.trim();
            const resultSpan = document.getElementById('test-result');
            
            if (!fbUrl) {
                resultSpan.innerHTML = '<span style="color: #f44336;">⚠️ Please enter a URL first</span>';
                return;
            }
            
            resultSpan.innerHTML = '<span style="color: #2196f3;">⏳ Testing...</span>';
            
            try {
                const response = await fetch(API_URL + '/api/extract?url=' + encodeURIComponent(fbUrl));
                const data = await response.json();
                
                if (data.success) {
                    resultSpan.innerHTML = `<span style="color: #4caf50;">✅ Success! (${data.method}, ${data.duration_ms}ms)</span>`;
                } else {
                    resultSpan.innerHTML = `<span style="color: #f44336;">❌ ${data.error || 'Extraction failed'}</span>`;
                }
            } catch (error) {
                resultSpan.innerHTML = `<span style="color: #f44336;">❌ API Error: ${error.message}</span>`;
            }
        }

        // ============================================
        // CONTENT SEARCH
        // ============================================
        const searchInput = document.getElementById('content-search');
        const searchResults = document.getElementById('search-results');
        const linkedContentId = document.getElementById('linked-content-id');
        const selectedContent = document.getElementById('selected-content');
        const selectedTitle = document.getElementById('selected-title');
        
        let searchTimeout;
        
        // Initialize edit mode if editing an existing shot
        <?php if ($edit_shot): ?>
        document.addEventListener('DOMContentLoaded', function() {
            linkedContentId.value = <?php echo $edit_shot['linked_content_id']; ?>;
            
            <?php
            $content_title = 'N/A';
            if ($edit_shot['linked_content_type'] === 'movie') {
                $stmt = $conn->prepare("SELECT title FROM movies WHERE id = ?");
            } elseif ($edit_shot['linked_content_type'] === 'series') {
                $stmt = $conn->prepare("SELECT title FROM series WHERE id = ?");
            } else {
                $stmt = $conn->prepare("SELECT title FROM collections WHERE id = ?");
            }
            $stmt->bind_param("i", $edit_shot['linked_content_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $content_title = $row['title'];
            }
            $stmt->close();
            ?>
            selectedTitle.textContent = '<?php echo addslashes($content_title); ?>';
            selectedContent.style.display = 'block';
        });
        <?php endif; ?>

        // Search functionality with debounce
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            const contentType = document.querySelector('input[name="content_type"]:checked').value;

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(() => {
                fetch(`../api/search_content.php?q=${encodeURIComponent(query)}&type=${contentType}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success' && data.results.length > 0) {
                            displayResults(data.results);
                        } else if (data.status === 'success' && data.results.length === 0) {
                            searchResults.innerHTML = '<div style="padding: 10px; color: #999;">No results found</div>';
                            searchResults.style.display = 'block';
                        } else {
                            searchResults.innerHTML = `<div style="padding: 10px; color: #f44336;">Error: ${data.message || 'Unknown error'}</div>`;
                            searchResults.style.display = 'block';
                        }
                    })
                    .catch(error => {
                        searchResults.innerHTML = `<div style="padding: 10px; color: #f44336;">Search failed: ${error.message}</div>`;
                        searchResults.style.display = 'block';
                    });
            }, 300);
        });

        function displayResults(results) {
            searchResults.innerHTML = '';
            results.forEach(item => {
                const div = document.createElement('div');
                div.textContent = item.title + (item.release_date ? ` (${item.release_date})` : '');
                div.onclick = function() {
                    selectContent(item.id, item.title);
                };
                searchResults.appendChild(div);
            });
            searchResults.style.display = 'block';
        }

        function selectContent(id, title) {
            linkedContentId.value = id;
            selectedTitle.textContent = title;
            selectedContent.style.display = 'block';
            searchInput.value = '';
            searchResults.style.display = 'none';
        }

        function clearSearch() {
            searchInput.value = '';
            linkedContentId.value = '';
            selectedContent.style.display = 'none';
            searchResults.style.display = 'none';
        }

        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    </script>
</body>
</html>
