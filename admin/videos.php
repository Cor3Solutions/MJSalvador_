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
    <style>
        .main-content {
            padding-left: 20px;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
                    <h1 class="h2">Manage Videos</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#videoModal"
                        onclick="resetForm()">
                        <i class="bi bi-plus-lg"></i> Add New Video
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo h($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Order</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>YouTube URL (Snippet)</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($videos)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted">No videos found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($videos as $v): ?>
                                            <tr>
                                                <td><?php echo h($v['display_order']); ?></td>
                                                <td><?php echo h($v['title']); ?></td>
                                                <!-- Display the human-readable category name from the JOIN -->
                                                <td><?php echo h($v['category_display_name']); ?></td>
                                                <td><?php echo h(substr($v['youtube_embed_url'], 0, 40)) . '...'; ?></td>
                                                <td>
                                                    <!-- Pass the full video object including category_id_fk to the JS function -->
                                                    <button class="btn btn-sm btn-warning me-1"
                                                        onclick='editVideo(<?php echo json_encode($v); ?>)'>
                                                        Edit
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo h($v['video_id']); ?>"
                                                        onclick="return confirm('Are you sure you want to delete this video?');"
                                                        class="btn btn-sm btn-danger">Delete</a>
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
                <!-- IMPORTANT: Changed form name from category to category_id -->
                <form action="videos.php" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Video</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Hidden fields for action and ID -->
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="video_id" id="video_id">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <!-- Category is now a SELECT dropdown using category_id -->
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
                            <label for="youtube_url" class="form-label">YouTube URL (Embed or Watch Link)</label>
                            <input type="url" class="form-control" id="youtube_url" name="youtube_url" required>
                            <small class="text-muted">Enter the full link (e.g., https://youtu.be/ID or https://www.youtube.com/watch?v=ID)</small>
                        </div>

                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" class="form-control" id="display_order" name="display_order" value="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Video</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Function to reset the form for adding a new video
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Video';
            document.getElementById('action').value = 'add';
            document.getElementById('video_id').value = '';
            document.getElementById('title').value = '';
            
            // Set select to default (empty value)
            document.getElementById('category_id').value = ''; 

            document.getElementById('youtube_url').value = '';
            document.getElementById('display_order').value = '0';
        }

        // Function to populate the form for editing an existing video
        function editVideo(video) {
            document.getElementById('modalTitle').textContent = 'Edit Video';
            document.getElementById('action').value = 'edit';
            
            // Populate hidden ID and visible fields
            document.getElementById('video_id').value = video.video_id;
            document.getElementById('title').value = video.title;
            
            // Use the fetched category_id_fk (which is the actual category_id foreign key)
            document.getElementById('category_id').value = video.category_id_fk; 
            
            document.getElementById('youtube_url').value = video.youtube_embed_url; 
            document.getElementById('display_order').value = video.display_order;

            // Show the modal
            var modal = new bootstrap.Modal(document.getElementById('videoModal'));
            modal.show();
        }
    </script>
</body>

</html>
