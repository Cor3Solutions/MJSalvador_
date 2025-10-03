<?php
require_once '../config.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$videos = [];
$error = '';

try {
    $conn = getDBConnection();

    // Basic CRUD: Delete Action
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $video_id = (int) $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM videos WHERE video_id = :id");
        $stmt->execute([':id' => $video_id]);

        header('Location: videos.php?status=deleted');
        exit;
    }

    // Fetch all videos
    $stmt = $conn->query("SELECT * FROM videos ORDER BY display_order ASC, video_id DESC");
    $videos = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Videos error: " . $e->getMessage());
    $error = 'Database error: Could not load videos.';
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
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
                    <h1 class="h2">Manage Videos</h1>
                    <a href="video_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add New Video</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
                <?php endif; ?>
                <?php if (isset($_GET['status']) && $_GET['status'] == 'deleted'): ?>
                    <div class="alert alert-success">Video deleted successfully!</div>
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
                                        <th>YouTube URL</th>
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
                                                <td><?php echo $v['display_order']; ?></td>
                                                <td><?php echo h($v['title']); ?></td>
                                                <td><?php echo h($v['category']); ?></td>
                                                <td><?php echo h(substr($v['youtube_embed_url'], 0, 40)) . '...'; ?></td>
                                                <td>
                                                    <a href="video_form.php?id=<?php echo $v['video_id']; ?>"
                                                        class="btn btn-sm btn-warning me-1">Edit</a>
                                                    <a href="?action=delete&id=<?php echo $v['video_id']; ?>"
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>