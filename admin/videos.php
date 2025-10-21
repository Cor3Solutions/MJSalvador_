<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentPage = 'videos.php';
$videoCategories = [];
$error = '';
$success = '';

// Form initialization
$video_id = null;
$action = 'add';
$title = '';
$category_id = 0;
$youtube_url = '';
$display_order = 0;

try {
    $conn = getDBConnection();

    // Fetch Categories
    $stmt_cat = $conn->query("SELECT category_id, display_name FROM video_categories ORDER BY sort_order ASC, display_name ASC");
    $videoCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Handle ARCHIVE action
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $video_id_archive = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'videos', 'video_id', $video_id_archive)) {
            header('Location: videos.php?status=success&msg=' . urlencode('Video archived successfully!'));
            exit;
        } else {
            header('Location: videos.php?status=error&msg=' . urlencode('Failed to archive video.'));
            exit;
        }
    }

    // Handle Form Submission (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = trim($_POST['action'] ?? 'add');
        $title = trim($_POST['title'] ?? '');
        $category_id = (int) ($_POST['category_id'] ?? 0);
        $youtube_url = trim($_POST['youtube_url'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        $video_id = isset($_POST['video_id']) ? (int) $_POST['video_id'] : null;

        if (empty($title) || empty($youtube_url) || $category_id <= 0) {
            $error = 'Title, Category, and YouTube URL are required.';
        } else {
            $valid_category = array_filter($videoCategories, function($cat) use ($category_id) {
                return $cat['category_id'] == $category_id;
            });

            if (empty($valid_category)) {
                $error = 'Invalid category selected.';
            } else {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO videos (title, category_id, youtube_embed_url, display_order) VALUES (:title, :cat_id, :url, :order)");
                    $stmt->execute([
                        ':title' => $title,
                        ':cat_id' => $category_id,
                        ':url' => $youtube_url,
                        ':order' => $display_order
                    ]);
                    header('Location: videos.php?status=success&msg=' . urlencode('Video added successfully!'));
                    exit;
                } elseif ($action === 'edit' && $video_id) {
                    $stmt = $conn->prepare("UPDATE videos SET title = :title, category_id = :cat_id, youtube_embed_url = :url, display_order = :order WHERE video_id = :id");
                    $stmt->execute([
                        ':title' => $title,
                        ':cat_id' => $category_id,
                        ':url' => $youtube_url,
                        ':order' => $display_order,
                        ':id' => $video_id
                    ]);
                    header('Location: videos.php?status=success&msg=' . urlencode('Video updated successfully!'));
                    exit;
                }
            }
        }
    }

    // Check for status messages
    if (isset($_GET['status']) && isset($_GET['msg'])) {
        if ($_GET['status'] == 'success') {
            $success = urldecode($_GET['msg']);
        } elseif ($_GET['status'] == 'error') {
            $error = urldecode($_GET['msg']);
        }
    }

    // Fetch only NON-archived videos
    $stmt = $conn->query("
        SELECT 
            v.*, 
            vc.display_name AS category_display_name,
            v.category_id AS category_id_fk 
        FROM videos v
        JOIN video_categories vc ON v.category_id = vc.category_id
        WHERE v.is_archived = 0
        ORDER BY v.display_order ASC, v.video_id DESC
    ");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get archived count
    $archived_count = getArchivedCount($conn, 'videos');

} catch (PDOException $e) {
    error_log("Videos error: " . $e->getMessage());
    $error = 'Database error: Could not load videos.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Videos - Jade Salvador Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">
                            <i class="bi bi-play-circle me-2" style="color: var(--jade-primary);"></i>Manage Videos
                        </h1>
                        <p class="text-muted mb-0">Add, edit, or archive video content</p>
                    </div>
                    <button class="btn btn-success" onclick="resetForm()">
                        <i class="bi bi-plus-lg me-2"></i> Add New Video
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3">
                        <i class="bi bi-check-circle me-2"></i><?php echo h($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($archived_count > 0): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center rounded-3 shadow-sm">
                        <div>
                            <i class="bi bi-archive me-2"></i>
                            <strong><?php echo $archived_count; ?></strong> video(s) are currently archived.
                        </div>
                        <a href="archives.php?tab=videos" class="btn btn-sm btn-info">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title m-0" id="formTitle"><?php echo $action === 'edit' ? 'Edit Video' : 'Add New Video'; ?></h5>
                            </div>
                            <div class="card-body">
                                <form action="videos.php" method="POST">
                                    <input type="hidden" name="action" id="action" value="<?php echo h($action); ?>">
                                    <input type="hidden" name="video_id" id="video_id" value="<?php echo h($video_id); ?>">

                                    <div class="mb-3">
                                        <label for="title" class="form-label fw-bold">Video Title</label>
                                        <input type="text" class="form-control" id="title" name="title" required placeholder="Enter video title" 
                                                value="<?php echo h($title); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="category_id" class="form-label fw-bold">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="" disabled <?php echo ($category_id == 0 && empty($title)) ? 'selected' : ''; ?>>Select a category</option>
                                            <?php foreach ($videoCategories as $cat): ?>
                                                <option value="<?php echo h($cat['category_id']); ?>"
                                                        <?php echo $category_id == $cat['category_id'] ? 'selected' : ''; ?>>
                                                    <?php echo h($cat['display_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="youtube_url" class="form-label fw-bold">YouTube URL</label>
                                        <input type="url" class="form-control" id="youtube_url" name="youtube_url" required 
                                               placeholder="https://youtu.be/..." value="<?php echo h($youtube_url); ?>">
                                        <small class="form-text text-muted">Full YouTube link</small>
                                    </div>

                                    <div class="mb-3">
                                        <label for="display_order" class="form-label fw-bold">Display Order</label>
                                        <input type="number" class="form-control" id="display_order" name="display_order" min="0" 
                                                value="<?php echo h($display_order); ?>">
                                        <small class="form-text text-muted">Lower numbers appear first</small>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-secondary" onclick="resetForm()">Cancel</button>
                                        <button type="submit" class="btn btn-primary flex-grow-1 ms-2">Save Video</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Title</th>
                                                <th>Category</th>
                                                <th>URL</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($videos)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-film me-2"></i> No videos found
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($videos as $v): ?>
                                                    <tr>
                                                        <td><span class="badge bg-secondary"><?php echo h($v['display_order']); ?></span></td>
                                                        <td class="fw-semibold"><?php echo h($v['title']); ?></td>
                                                        <td><span class="badge bg-primary"><?php echo h($v['category_display_name']); ?></span></td>
                                                        <td><small class="text-muted"><?php echo h(substr($v['youtube_embed_url'], 0, 35)) . '...'; ?></small></td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-primary me-1"
                                                                 onclick='editVideo(<?php echo json_encode($v); ?>)'>
                                                                 <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="showArchiveModal(<?php echo $v['video_id']; ?>, '<?php echo h(addslashes($v['title'])); ?>')">
                                                                <i class="bi bi-archive"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Archive Modal -->
    <div class="modal fade" id="archiveConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive" style="font-size: 3rem; color: var(--warning);"></i>
                    <p class="mt-3 mb-2 fw-medium">Archive this video?</p>
                    <p class="text-muted"><strong>"<span id="archiveItemTitle"></span>"</strong></p>
                    <div class="alert alert-info mt-3 mb-0">
                        <small><i class="bi bi-info-circle me-1"></i>You can restore it later from Archives</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmArchiveButton" href="#" class="btn btn-warning rounded-3 fw-bold">
                        <i class="bi bi-archive me-2"></i>Archive
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-theme.js"></script>
    <script>
        function resetForm() {
            document.getElementById('formTitle').textContent = 'Add New Video';
            document.getElementById('action').value = 'add';
            document.getElementById('video_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('category_id').value = '';
            document.getElementById('youtube_url').value = '';
            document.getElementById('display_order').value = '0';
        }

        function editVideo(video) {
            document.getElementById('formTitle').textContent = 'Edit Video: ' + video.title;
            document.getElementById('action').value = 'edit';
            document.getElementById('video_id').value = video.video_id;
            document.getElementById('title').value = video.title;
            document.getElementById('category_id').value = video.category_id_fk;
            document.getElementById('youtube_url').value = video.youtube_embed_url;
            document.getElementById('display_order').value = video.display_order;
            document.getElementById('formTitle').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        function showArchiveModal(id, title) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('archiveConfirmModal')).show();
        }
    </script>
</body>
</html>