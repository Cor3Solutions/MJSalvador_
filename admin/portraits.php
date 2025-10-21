<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';

// Helper function for security: HTML-escape data
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentPage = 'portraits.php';
$portraits = [];
$portraitCategories = [];
$error = '';
$success = '';

// Data for pre-filling the side-form
$form_data = [
    'portrait_id' => null,
    'action' => 'add',
    'title' => '',
    'current_filename' => '',
    'categories' => [],
    'is_setcard' => 0,
    'sort_order' => 0,
];

try {
    $conn = getDBConnection();

    // Fetch Categories dynamically
    $stmt_cat = $conn->query("SELECT name, display_name FROM portrait_categories ORDER BY display_name ASC");
    $portraitCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Handle ARCHIVE action (replaces delete)
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $portrait_id = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'portraits', 'portrait_id', $portrait_id)) {
            header('Location: portraits.php?status=success&msg=' . urlencode('Portrait archived successfully!'));
            exit;
        } else {
            header('Location: portraits.php?status=error&msg=' . urlencode('Failed to archive portrait.'));
            exit;
        }
    }

    // Handle form submission (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            $form_data['portrait_id'] = isset($_POST['portrait_id']) ? (int) $_POST['portrait_id'] : null;
            $form_data['action'] = $_POST['action'];
            $form_data['current_filename'] = trim($_POST['current_filename'] ?? '');
            $form_data['title'] = trim($_POST['title'] ?? '');
            $form_data['categories'] = $_POST['categories'] ?? [];
            $form_data['is_setcard'] = isset($_POST['is_setcard']) ? 1 : 0;
            $form_data['sort_order'] = (int) ($_POST['sort_order'] ?? 0);

            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                $portrait_id = $form_data['portrait_id'];
                $current_filename = $form_data['current_filename'];
                $title = $form_data['title'];
                $categories_string = implode(' ', array_filter(array_map('trim', $form_data['categories']))); 
                $is_setcard = $form_data['is_setcard'];
                $sort_order = $form_data['sort_order'];
                $db_image_filename = $current_filename;

                // FILE UPLOAD LOGIC
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/portraits/';
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_info = pathinfo($_FILES['image_file']['name']);
                    $original_name = basename($file_info['basename']);
                    $extension = strtolower($file_info['extension'] ?? '');
                    $safe_filename = md5(time() . $original_name) . '.' . $extension;
                    $target_file = $upload_dir . $safe_filename;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($extension, $allowed_types)) {
                        $error = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
                    } elseif (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                        $error = "Error moving uploaded file. Check directory permissions.";
                    } else {
                        $db_image_filename = 'images/portraits/' . $safe_filename;
                        
                        if ($_POST['action'] === 'edit' && $current_filename && $current_filename !== $db_image_filename) {
                            $old_file_path = '../' . $current_filename;
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                            }
                        }
                    }
                }
                
                if (empty($db_image_filename)) {
                    $error = "Image file is required.";
                }

                if (!$error) {
                    if ($_POST['action'] === 'add') {
                        $stmt = $conn->prepare("INSERT INTO portraits (image_filename, title, categories, is_setcard, sort_order) VALUES (:img, :title, :cat, :setcard, :sort)");
                        $stmt->execute([
                            ':img' => $db_image_filename,
                            ':title' => $title,
                            ':cat' => $categories_string,
                            ':setcard' => $is_setcard,
                            ':sort' => $sort_order
                        ]);
                        $form_data = ['portrait_id' => null, 'action' => 'add', 'title' => '', 'current_filename' => '', 'categories' => [], 'is_setcard' => 0, 'sort_order' => 0];
                        header('Location: portraits.php?status=success&msg=' . urlencode('Portrait added successfully!'));
                        exit;
                    } else {
                        $stmt = $conn->prepare("UPDATE portraits SET image_filename = :img, title = :title, categories = :cat, is_setcard = :setcard, sort_order = :sort WHERE portrait_id = :id");
                        $stmt->execute([
                            ':img' => $db_image_filename,
                            ':title' => $title,
                            ':cat' => $categories_string,
                            ':setcard' => $is_setcard,
                            ':sort' => $sort_order,
                            ':id' => $portrait_id
                        ]);
                        header('Location: portraits.php?status=success&msg=' . urlencode('Portrait updated successfully!'));
                        exit;
                    }
                }
            }
        }
    }

    // Fetch only NON-archived portraits
    $stmt = $conn->query("SELECT * FROM portraits WHERE is_archived = 0 ORDER BY sort_order ASC, portrait_id DESC");
    $portraits = $stmt->fetchAll();

    // Get archived count
    $archived_count = getArchivedCount($conn, 'portraits');

} catch (PDOException $e) {
    error_log("Portraits error: " . $e->getMessage());
    $error = 'Database error occurred: ' . h($e->getMessage());
}

if (isset($_GET['status']) && isset($_GET['msg'])) {
    if ($_GET['status'] == 'success') {
        $success = urldecode($_GET['msg']);
    } elseif ($_GET['status'] == 'error') {
        $error = urldecode($_GET['msg']);
    }
}

if ($error && $form_data['portrait_id']) {
    $form_data['action'] = 'edit';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Portraits - Jade Salvador Admin</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
        .portrait-thumbnail {
            width: 70px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .category-badge {
            display: inline-block;
            padding: 0.3em 0.7em;
            margin-right: 0.3em;
            margin-bottom: 0.3em;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: rgba(205, 145, 158, 0.2);
            color: var(--jade-primary);
        }
    </style>
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
                            <i class="bi bi-images me-2" style="color: var(--jade-primary);"></i>Manage Portraits
                        </h1>
                        <p class="text-muted mb-0">Add, edit, or archive portfolio images</p>
                    </div>
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
                            <strong><?php echo $archived_count; ?></strong> portrait(s) are currently archived.
                        </div>
                        <a href="archives.php?tab=portraits" class="btn btn-sm btn-info">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-5">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="mb-0" id="formTitle">
                                    <?php echo $form_data['action'] === 'edit' ? 'Edit Portrait #' . h($form_data['portrait_id']) : 'Add New Portrait'; ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="portraits.php" method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="portrait_id" id="portrait_id" value="<?php echo h($form_data['portrait_id']); ?>">
                                    <input type="hidden" name="action" id="action" value="<?php echo h($form_data['action']); ?>">
                                    <input type="hidden" name="current_filename" id="current_filename" value="<?php echo h($form_data['current_filename']); ?>">

                                    <div class="mb-3">
                                        <label for="image_file" class="form-label fw-bold">Upload Image File</label>
                                        <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*" <?php echo $form_data['action'] === 'add' ? 'required' : ''; ?>>
                                        <div id="image_filename_display" class="mt-2 text-muted small" style="display: <?php echo $form_data['current_filename'] ? 'block' : 'none'; ?>;">
                                            Current File: <span><?php echo h($form_data['current_filename']); ?></span>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="title" class="form-label fw-bold">Title</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo h($form_data['title']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Categories</label>
                                        <div class="p-3 border rounded bg-light">
                                            <div class="row row-cols-2 g-2">
                                                <?php 
                                                $active_categories = array_map('trim', $form_data['categories']); 
                                                foreach($portraitCategories as $cat): ?>
                                                    <div class="col">
                                                        <div class="form-check">
                                                            <input class="form-check-input" 
                                                                type="checkbox" 
                                                                name="categories[]" 
                                                                id="cat_<?php echo h($cat['name']); ?>" 
                                                                value="<?php echo h($cat['name']); ?>"
                                                                <?php echo in_array($cat['name'], $active_categories) ? 'checked' : ''; ?>>
                                                            <label class="form-check-label small" for="cat_<?php echo h($cat['name']); ?>">
                                                                <?php echo h($cat['display_name']); ?>
                                                            </label>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="sort_order" class="form-label fw-bold">Sort Order</label>
                                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo h($form_data['sort_order']); ?>">
                                        <div class="form-text">Lower numbers appear first</div>
                                    </div>

                                    <div class="form-check form-switch pt-2 mb-4">
                                        <input class="form-check-input" type="checkbox" id="is_setcard" name="is_setcard" <?php echo $form_data['is_setcard'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="is_setcard">Mark as Set Card</label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <?php if ($form_data['action'] === 'edit'): ?>
                                            <button type="button" class="btn btn-secondary rounded-3" onclick="resetForm()">Cancel</button>
                                        <?php endif; ?>
                                        <button type="submit" class="btn btn-primary rounded-3 flex-grow-1 ms-3">
                                            <?php echo $form_data['action'] === 'edit' ? 'Update Portrait' : 'Add Portrait'; ?>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-7">
                        <div class="card shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Order</th>
                                                <th>Image</th>
                                                <th>Title</th>
                                                <th>Categories</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($portraits)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-folder-open me-2"></i> No portraits found
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($portraits as $p): ?>
                                                    <tr>
                                                        <td class="fw-bold"><?php echo h($p['sort_order']); ?></td>
                                                        <td>
                                                            <img src="../<?php echo h($p['image_filename']); ?>"
                                                                alt="<?php echo h($p['title']); ?>"
                                                                class="portrait-thumbnail">
                                                        </td>
                                                        <td>
                                                            <?php echo h($p['title']); ?>
                                                            <div class="small mt-1">
                                                                <span class="badge bg-<?php echo $p['is_setcard'] ? 'info' : 'secondary'; ?>">
                                                                    Set Card: <?php echo $p['is_setcard'] ? 'Yes' : 'No'; ?>
                                                                </span>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <?php 
                                                            $cats = explode(' ', $p['categories']);
                                                            foreach ($cats as $cat) {
                                                                $cat = trim($cat);
                                                                if (!empty($cat)) {
                                                                    $display_name = $cat;
                                                                    foreach($portraitCategories as $cat_data) {
                                                                        if ($cat_data['name'] === $cat) {
                                                                            $display_name = $cat_data['display_name'];
                                                                            break;
                                                                        }
                                                                    }
                                                                    echo '<span class="category-badge">' . h($display_name) . '</span>';
                                                                }
                                                            }
                                                            ?>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-primary me-1"
                                                                onclick='editPortrait(<?php echo json_encode($p); ?>)'>
                                                                <i class="bi bi-pencil"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="showArchiveModal(<?php echo $p['portrait_id']; ?>, '<?php echo h(addslashes($p['title'])); ?>')">
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
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Portrait</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive" style="font-size: 3rem; color: var(--warning);"></i>
                    <p class="mt-3 mb-2 fw-medium">Archive this portrait?</p>
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
            document.getElementById('formTitle').textContent = 'Add New Portrait';
            document.getElementById('action').value = 'add';
            document.getElementById('portrait_id').value = '';
            document.getElementById('current_filename').value = '';
            document.getElementById('image_file').required = true;
            document.getElementById('image_filename_display').style.display = 'none';
            document.getElementById('image_file').value = '';
            document.getElementById('title').value = '';
            document.querySelectorAll('.portrait-category-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('sort_order').value = '0';
            document.getElementById('is_setcard').checked = false;
        }

        function editPortrait(portrait) {
            document.getElementById('formTitle').textContent = 'Edit Portrait #' + portrait.portrait_id;
            document.getElementById('action').value = 'edit';
            document.getElementById('portrait_id').value = portrait.portrait_id;
            document.getElementById('current_filename').value = portrait.image_filename;
            document.getElementById('image_filename_display').style.display = 'block';
            document.querySelector('#image_filename_display span').textContent = portrait.image_filename;
            document.getElementById('image_file').required = false;
            document.getElementById('image_file').value = '';
            document.getElementById('title').value = portrait.title;
            document.getElementById('sort_order').value = portrait.sort_order;
            document.getElementById('is_setcard').checked = portrait.is_setcard == 1;

            document.querySelectorAll('.form-check-input[name="categories[]"]').forEach(cb => cb.checked = false);
            if (portrait.categories) {
                const activeCategories = portrait.categories.split(/\s+/).filter(c => c.length > 0);
                activeCategories.forEach(catName => {
                    const checkbox = document.getElementById('cat_' + catName);
                    if (checkbox) checkbox.checked = true;
                });
            }
            document.getElementById('formTitle').scrollIntoView({ behavior: 'smooth' });
        }
        
        function showArchiveModal(id, title) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = title;
            new bootstrap.Modal(document.getElementById('archiveConfirmModal')).show();
        }
    </script>
</body>
</html>