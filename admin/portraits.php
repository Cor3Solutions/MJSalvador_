<?php
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$portraits = [];
$error = '';
$success = '';

try {
    $conn = getDBConnection();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                $portrait_id = isset($_POST['portrait_id']) ? (int) $_POST['portrait_id'] : null;
                $image_filename = trim($_POST['image_filename']);
                $title = trim($_POST['title']);
                $categories = trim($_POST['categories']);
                $is_setcard = isset($_POST['is_setcard']) ? 1 : 0;
                $sort_order = (int) $_POST['sort_order'];

                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("INSERT INTO portraits (image_filename, title, categories, is_setcard, sort_order) VALUES (:img, :title, :cat, :setcard, :sort)");
                    $stmt->execute([
                        ':img' => $image_filename,
                        ':title' => $title,
                        ':cat' => $categories,
                        ':setcard' => $is_setcard,
                        ':sort' => $sort_order
                    ]);
                    $success = 'Portrait added successfully!';
                } else {
                    $stmt = $conn->prepare("UPDATE portraits SET image_filename = :img, title = :title, categories = :cat, is_setcard = :setcard, sort_order = :sort WHERE portrait_id = :id");
                    $stmt->execute([
                        ':img' => $image_filename,
                        ':title' => $title,
                        ':cat' => $categories,
                        ':setcard' => $is_setcard,
                        ':sort' => $sort_order,
                        ':id' => $portrait_id
                    ]);
                    $success = 'Portrait updated successfully!';
                }
            }
        }
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $portrait_id = (int) $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM portraits WHERE portrait_id = :id");
        $stmt->execute([':id' => $portrait_id]);
        $success = 'Portrait deleted successfully!';
    }

    // Fetch all portraits
    $stmt = $conn->query("SELECT * FROM portraits ORDER BY sort_order ASC, portrait_id DESC");
    $portraits = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Portraits error: " . $e->getMessage());
    $error = 'Database error occurred.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Portraits</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>
    <?php include 'admin_header.php'; ?>
    <?php include 'admin_sidebar.php'; ?>

    <main class="col-md-10 ms-sm-auto px-md-4">
        <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
            <h1 class="h2">Manage Portraits</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#portraitModal"
                onclick="resetForm()">
                <i class="bi bi-plus-lg"></i> Add New Portrait
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

        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Categories</th>
                                <th>Setcard</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($portraits)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No portraits found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($portraits as $p): ?>
                                    <tr>
                                        <td><?php echo $p['sort_order']; ?></td>
                                        <td><img src="../<?php echo h($p['image_filename']); ?>"
                                                alt="<?php echo h($p['title']); ?>"
                                                style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;"></td>
                                        <td><?php echo h($p['title']); ?></td>
                                        <td><small><?php echo h($p['categories']); ?></small></td>
                                        <td><span
                                                class="badge bg-<?php echo $p['is_setcard'] ? 'info' : 'secondary'; ?>"><?php echo $p['is_setcard'] ? 'Yes' : 'No'; ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning"
                                                onclick='editPortrait(<?php echo json_encode($p); ?>)'>
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <a href="?action=delete&id=<?php echo $p['portrait_id']; ?>"
                                                onclick="return confirm('Delete this portrait?');"
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

    <!-- Portrait Modal -->
    <div class="modal fade" id="portraitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="admin_upload_portrait.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Portrait</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="portrait_id" id="portrait_id">
                        <input type="hidden" name="action" id="action" value="add">

                        <div class="mb-3">
                            <label for="image_file" class="form-label">Upload Image File</label>
                            <!-- The input field is now type="file" -->
                            <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*"
                                required>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="categories" class="form-label">Categories <small
                                    class="text-muted">(space-separated: events headshots setcard)</small></label>
                            <input type="text" class="form-control" id="categories" name="categories"
                                placeholder="events headshots">
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_setcard" name="is_setcard">
                            <label class="form-check-label" for="is_setcard">Is Set Card</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Portrait</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Portrait';
            document.getElementById('action').value = 'add';
            document.getElementById('portrait_id').value = '';
            document.getElementById('image_filename').value = '';
            document.getElementById('title').value = '';
            document.getElementById('categories').value = '';
            document.getElementById('sort_order').value = '0';
            document.getElementById('is_setcard').checked = false;
        }

        function editPortrait(portrait) {
            document.getElementById('modalTitle').textContent = 'Edit Portrait';
            document.getElementById('action').value = 'edit';
            document.getElementById('portrait_id').value = portrait.portrait_id;
            document.getElementById('image_filename').value = portrait.image_filename;
            document.getElementById('title').value = portrait.title;
            document.getElementById('categories').value = portrait.categories;
            document.getElementById('sort_order').value = portrait.sort_order;
            document.getElementById('is_setcard').checked = portrait.is_setcard == 1;

            var modal = new bootstrap.Modal(document.getElementById('portraitModal'));
            modal.show();
        }
    </script>
</body>

</html>