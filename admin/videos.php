<?php
require_once '../config.php';

// Helper function for security: HTML-escape data
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$videoCategories = []; // Will be populated from the database
$error = '';
$success = '';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

try {
    $conn = getDBConnection();

    // 1. Fetch Categories dynamically from the new table
    $stmt_cat = $conn->query("SELECT category_id, display_name FROM video_categories ORDER BY sort_order ASC, display_name ASC");
    $videoCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);


    // --- Handle Form Submission (Add/Edit) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim($_POST['action'] ?? '');
        $title = trim($_POST['title'] ?? '');
        // We now expect category_id (INT) from the form
        $category_id = (int) ($_POST['category_id'] ?? 0); 
        $youtube_url = trim($_POST['youtube_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $video_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : null;

        if (empty($title) || empty($youtube_url) || $category_id <= 0) {
            $error = 'Title, Category, and YouTube URL are required.';
        } else {
            // Check if the submitted category_id is actually valid
            $valid_category = array_filter($videoCategories, function($cat) use ($category_id) {
                return $cat['category_id'] == $category_id;
            });

            if (empty($valid_category)) {
                $error = 'Invalid category selected.';
            } else {
                if ($action === 'add') {
                    // Use category_id for INSERT
                    $stmt = $conn->prepare("INSERT INTO videos (title, category_id, youtube_embed_url, display_order) VALUES (:title, :cat_id, :url, :order)");
                    $stmt->execute([
                        ':title' => $title,
                        ':cat_id' => $category_id,
                        ':url' => $youtube_url,
                        ':order' => $display_order
                    ]);
                    $success = 'Video added successfully!';
                } elseif ($action === 'edit' && $video_id) {
                    // Use category_id for UPDATE
                    $stmt = $conn->prepare("UPDATE videos SET title = :title, category_id = :cat_id, youtube_embed_url = :url, display_order = :order WHERE video_id = :id");
                    $stmt->execute([
                        ':title' => $title,
                        ':cat_id' => $category_id,
                        ':url' => $youtube_url,
                        ':order' => $display_order,
                        ':id' => $video_id
                    ]);
                    $success = 'Video updated successfully!';
                }
            }
        }
    }
    // --- End Form Submission Handler ---


    // Basic CRUD: Delete Action
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $video_id = (int) $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM videos WHERE video_id = :id");
        $stmt->execute([':id' => $video_id]);

        // Clear the GET parameters that triggered the action for a clean URL
        header('Location: videos.php?status=deleted');
        exit;
    }
    
    // Check for success status from redirect
    if (isset($_GET['status']) && $_GET['status'] == 'deleted') {
           $success = 'Video deleted successfully!';
    }


    // 2. Fetch all videos, joining the new category table to get the display name
    // We select v.category_id as category_id_fk for use in the JS edit function
    $stmt = $conn->query("
        SELECT 
            v.*, 
            vc.display_name AS category_display_name,
            vc.category_id AS category_id_fk 
        FROM videos v
        JOIN video_categories vc ON v.category_id = vc.category_id
        ORDER BY v.display_order ASC, v.video_id DESC
    ");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Videos error: " . $e->getMessage());
    $error = 'Database error: Could not load videos or process request.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa; /* Lighter, cleaner background */
        }
        .main-content {
            padding: 0 1rem; /* Adjust padding for better look */
        }
        .content-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,.04);
        }
        .card {
            border: none;
            border-radius: 12px; 
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); /* Deeper shadow */
        }
        .table thead th {
            background-color: #f0f2f5; 
            border-bottom: 2px solid #dee2e6;
            font-weight: 700;
            color: #495057;
        }
        .modal-content {
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .table-hover tbody tr:hover {
            background-color: #e9f0ff; /* Subtle blue highlight on hover */
            cursor: default;
        }
        /* Custom badge colors for categories */
        .badge-video {
            background-color: #007bff;
            color: white;
            padding: 0.5em 0.75em;
            border-radius: 6px;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                
                <!-- Enhanced Content Header -->
                <div class="content-header d-flex justify-content-between align-items-center">
                    <h1 class="h2 fw-bolder text-dark m-0">Video Management</h1>
                    <button class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#videoModal"
                        onclick="resetForm()">
                        <i class="bi bi-play-circle-fill me-2"></i> Add New Video
                    </button>
                </div>

                <!-- Alerts Section -->
                <div class="row px-3">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert"><?php echo h($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert"><?php echo h($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Video List Card -->
                <div class="card mb-5 shadow-lg">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-borderless table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="py-3 ps-4">Order</th>
                                        <th class="py-3">Title</th>
                                        <th class="py-3">Category</th>
                                        <th class="py-3">YouTube URL (Snippet)</th>
                                        <th class="py-3 text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($videos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="bi bi-film me-2"></i> No videos found. Start adding content!
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($videos as $v): ?>
                                            <tr>
                                                <td class="ps-4"><span class="badge bg-secondary"><?php echo h($v['display_order']); ?></span></td>
                                                <td class="fw-semibold text-break"><?php echo h($v['title']); ?></td>
                                                <td><span class="badge badge-video"><?php echo h($v['category_display_name']); ?></span></td>
                                                <td><small class="text-muted"><?php echo h(substr($v['youtube_embed_url'], 0, 35)) . '...'; ?></small></td>
                                                <td class="text-end pe-4">
                                                    <!-- Pass the full video object including category_id_fk to the JS function -->
                                                    <button class="btn btn-sm btn-warning me-2 text-white"
                                                        onclick='editVideo(<?php echo json_encode($v); ?>)'
                                                        data-bs-toggle="modal" data-bs-target="#videoModal">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo h($v['video_id']); ?>"
                                                        onclick="return confirm('Are you sure you want to permanently delete the video: <?php echo h(addslashes($v['title'])); ?>?');"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>


    <!-- Video Modal (Add/Edit Form) -->
    <div class="modal fade" id="videoModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="videos.php" method="POST">
                    <div class="modal-header bg-dark text-white rounded-top-2">
                        <h5 class="modal-title" id="modalTitle">Add New Video</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden fields for action and ID -->
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="video_id" id="video_id">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Video Title</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Enter video title">
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label fw-bold">Category</label>
                            <!-- Category is a SELECT dropdown using category_id -->
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="" disabled selected>Select a category</option>
                                <?php foreach ($videoCategories as $cat): ?>
                                    <option value="<?php echo h($cat['category_id']); ?>">
                                        <?php echo h($cat['display_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="youtube_url" class="form-label fw-bold">YouTube URL</label>
                            <input type="url" class="form-control" id="youtube_url" name="youtube_url" required placeholder="Paste YouTube watch or share URL here">
                            <small class="form-text text-muted">Must be a complete link (e.g., https://youtu.be/ID)</small>
                        </div>

                        <div class="mb-3">
                            <label for="display_order" class="form-label fw-bold">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" value="0" min="0">
                            <small class="form-text text-muted">Videos are sorted ascending by this number.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-success shadow-sm">Save Video</button>
                    </div>
                </form>
            </div>
            
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Resets the modal form to its "Add New Video" state.
         */
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Video';
            document.getElementById('action').value = 'add';
            document.getElementById('video_id').value = '';
            document.getElementById('title').value = '';
            
            // Reset select to default/placeholder
            document.getElementById('category_id').value = ''; 

            document.getElementById('youtube_url').value = '';
            document.getElementById('display_order').value = '0';
        }

        /**
         * Populates the modal form with data for editing an existing video.
         * @param {object} video - The video object retrieved from the database.
         */
        function editVideo(video) {
            document.getElementById('modalTitle').textContent = 'Edit Video: ' + video.title;
            document.getElementById('action').value = 'edit';
            
            // Populate hidden ID and visible fields
            document.getElementById('video_id').value = video.video_id;
            document.getElementById('title').value = video.title;
            
            // Set the dropdown value using the foreign key
            document.getElementById('category_id').value = video.category_id_fk; 
            
            document.getElementById('youtube_url').value = video.youtube_embed_url; 
            document.getElementById('display_order').value = video.display_order;
        }
    </script>
</body>

</html>
