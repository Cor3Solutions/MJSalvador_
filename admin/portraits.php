<?php
require_once '../config.php';

// Helper function for security: HTML-escape data
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$portraits = [];
$portraitCategories = []; // New array to hold categories from portrait_categories table
$error = '';
$success = '';

try {
    $conn = getDBConnection();

    // 1. Fetch Categories dynamically for form display
    $stmt_cat = $conn->query("SELECT name, display_name FROM portrait_categories ORDER BY display_name ASC");
    $portraitCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

    // Handle form submission (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                
                $portrait_id = isset($_POST['portrait_id']) ? (int) $_POST['portrait_id'] : null;
                $current_filename = trim($_POST['current_filename'] ?? ''); // Used for 'edit' when no new file is uploaded
                $title = trim($_POST['title']);
                
                // IMPORTANT: Convert selected category names (an array) back to a space-separated string
                $selected_categories = $_POST['categories'] ?? []; 
                $categories_string = implode(' ', array_map('trim', $selected_categories)); 
                
                $is_setcard = isset($_POST['is_setcard']) ? 1 : 0;
                $sort_order = (int) $_POST['sort_order'];
                
                $db_image_filename = $current_filename; // Start with the existing filename (or empty for add)

                // --- FILE UPLOAD LOGIC ---
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/portraits/';
                    $file_info = pathinfo($_FILES['image_file']['name']);
                    
                    $original_name = basename($file_info['basename']);
                    $extension = strtolower($file_info['extension']);
                    
                    // Create a safe, unique filename
                    $safe_filename = md5(time() . $original_name) . '.' . $extension;
                    $target_file = $upload_dir . $safe_filename;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($extension, $allowed_types)) {
                        $error = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
                    } elseif (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                        $error = "Error moving uploaded file to the destination directory.";
                    } else {
                        // Store the path relative to the site root for display
                        $db_image_filename = 'images/portraits/' . $safe_filename;
                        
                        // Optional: Delete old file if editing and a new file was uploaded
                        if ($portrait_id && $current_filename && file_exists('../' . $current_filename)) {
                            unlink('../' . $current_filename);
                        }
                    }
                }
                // --- END FILE UPLOAD LOGIC ---
                
                // Final validation before database operation
                if (empty($db_image_filename) && $_POST['action'] === 'add') {
                    $error = "Image file is required for new portraits.";
                }

                if (!$error) {
                    if ($_POST['action'] === 'add') {
                        $stmt = $conn->prepare("INSERT INTO portraits (image_filename, title, categories, is_setcard, sort_order) VALUES (:img, :title, :cat, :setcard, :sort)");
                        $stmt->execute([
                            ':img' => $db_image_filename,
                            ':title' => $title,
                            ':cat' => $categories_string, // Use the imploded string
                            ':setcard' => $is_setcard,
                            ':sort' => $sort_order
                        ]);
                        $success = 'Portrait added successfully!';
                    } else { // 'edit'
                        $stmt = $conn->prepare("UPDATE portraits SET image_filename = :img, title = :title, categories = :cat, is_setcard = :setcard, sort_order = :sort WHERE portrait_id = :id");
                        $stmt->execute([
                            ':img' => $db_image_filename,
                            ':title' => $title,
                            ':cat' => $categories_string, // Use the imploded string
                            ':setcard' => $is_setcard,
                            ':sort' => $sort_order,
                            ':id' => $portrait_id
                        ]);
                        $success = 'Portrait updated successfully!';
                    }
                }
            }
        }
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $portrait_id = (int) $_GET['id'];
        
        // Fetch filename to delete the file
        $stmt_fetch = $conn->prepare("SELECT image_filename FROM portraits WHERE portrait_id = :id");
        $stmt_fetch->execute([':id' => $portrait_id]);
        $to_delete = $stmt_fetch->fetchColumn();

        // Delete the database record
        $stmt = $conn->prepare("DELETE FROM portraits WHERE portrait_id = :id");
        $stmt->execute([':id' => $portrait_id]);
        
        // Delete the actual file
        if ($to_delete && file_exists('../' . $to_delete)) {
            unlink('../' . $to_delete);
        }

        // Redirect to clear GET parameters
        header('Location: portraits.php?status=success&msg=' . urlencode('Portrait deleted successfully!'));
        exit;
    }

    // Fetch all portraits
    $stmt = $conn->query("SELECT * FROM portraits ORDER BY sort_order ASC, portrait_id DESC");
    $portraits = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Portraits error: " . $e->getMessage());
    $error = 'Database error occurred: ' . h($e->getMessage());
}

// Check for status messages from redirects
if (isset($_GET['status']) && isset($_GET['msg'])) {
    if ($_GET['status'] == 'success') {
        $success = urldecode($_GET['msg']);
    } elseif ($_GET['status'] == 'error') {
        $error = urldecode($_GET['msg']);
    }
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
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
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
                                                <td><?php echo h($p['sort_order']); ?></td>
                                                <td>
                                                    <img src="../<?php echo h($p['image_filename']); ?>"
                                                        alt="<?php echo h($p['title']); ?>"
                                                        style="width: 60px; height: 60px; object-fit: cover; border-radius: 4px;">
                                                </td>
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
                                                    <a href="?action=delete&id=<?php echo h($p['portrait_id']); ?>"
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


    <div class="modal fade" id="portraitModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- IMPORTANT: enctype="multipart/form-data" is required for file uploads -->
                <form action="portraits.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Portrait</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="portrait_id" id="portrait_id">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="current_filename" id="current_filename"> 

                        <div class="mb-3">
                            <label for="image_file" class="form-label">Upload Image File</label>
                            <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                            <div id="image_filename_display" class="mt-2 text-muted" style="display: none;">
                                Current File: <span></span> (Upload new file to replace)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <!-- REPLACED TEXT INPUT WITH DYNAMIC CHECKBOXES -->
                        <div class="mb-3">
                            <label class="form-label">Categories</label>
                            <div class="p-2 border rounded bg-light">
                                <?php if (empty($portraitCategories)): ?>
                                    <small class="text-danger">No categories found in the database. Please add them manually if needed.</small>
                                <?php endif; ?>
                                <?php foreach($portraitCategories as $cat): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input portrait-category-checkbox" 
                                            type="checkbox" 
                                            name="categories[]" 
                                            id="cat_<?php echo h($cat['name']); ?>" 
                                            value="<?php echo h($cat['name']); ?>"
                                        >
                                        <label class="form-check-label" for="cat_<?php echo h($cat['name']); ?>">
                                            <?php echo h($cat['display_name']); ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
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
            document.getElementById('current_filename').value = ''; 
            document.getElementById('image_file').required = true; 
            document.getElementById('image_filename_display').style.display = 'none';
            document.getElementById('image_file').value = ''; 
            document.getElementById('title').value = '';
            
            // Uncheck all categories for a new entry
            document.querySelectorAll('.portrait-category-checkbox').forEach(cb => {
                cb.checked = false;
            });

            document.getElementById('sort_order').value = '0';
            document.getElementById('is_setcard').checked = false;
        }

        function editPortrait(portrait) {
            document.getElementById('modalTitle').textContent = 'Edit Portrait';
            document.getElementById('action').value = 'edit';
            document.getElementById('portrait_id').value = portrait.portrait_id;
            
            // Set the existing filename
            document.getElementById('current_filename').value = portrait.image_filename; 
            
            // Display the current filename
            document.getElementById('image_filename_display').style.display = 'block';
            document.querySelector('#image_filename_display span').textContent = portrait.image_filename;
            
            // File input is not required for edits
            document.getElementById('image_file').required = false; 
            document.getElementById('image_file').value = ''; 
            
            document.getElementById('title').value = portrait.title;
            document.getElementById('sort_order').value = portrait.sort_order;
            document.getElementById('is_setcard').checked = portrait.is_setcard == 1;

            // NEW LOGIC: Handle Category Checkboxes
            
            // 1. Uncheck all first
            document.querySelectorAll('.portrait-category-checkbox').forEach(cb => {
                cb.checked = false; 
            });

            // 2. Check the ones that are in the portrait's category string
            if (portrait.categories) {
                // Split the space-separated string into an array
                const activeCategories = portrait.categories.split(/\s+/).filter(c => c.length > 0);
                
                activeCategories.forEach(catName => {
                    // Check the checkbox using the category name as the unique ID part (e.g., 'cat_gym')
                    const checkbox = document.getElementById('cat_' + catName);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            var modal = new bootstrap.Modal(document.getElementById('portraitModal'));
            modal.show();
        }
    </script>
</body>

</html>
