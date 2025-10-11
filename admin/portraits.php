<?php
require_once '../config.php';

// Helper function for security: HTML-escape data
if (!function_exists('h')) {
    /**
     * Escape HTML entities in a string for output.
     * @param string|null $string The string to escape.
     * @return string The escaped string.
     */
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// --- Authentication Check ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$portraits = [];
$portraitCategories = []; // Array to hold categories from portrait_categories table
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
                // Ensure only non-empty, trimmed values are included in the string
                $categories_string = implode(' ', array_filter(array_map('trim', $selected_categories))); 
                
                $is_setcard = isset($_POST['is_setcard']) ? 1 : 0;
                $sort_order = (int) $_POST['sort_order'];
                
                $db_image_filename = $current_filename; // Start with the existing filename (or empty for add)

                // --- FILE UPLOAD LOGIC ---
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/portraits/';
                    
                    // Create upload directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    $file_info = pathinfo($_FILES['image_file']['name']);
                    
                    $original_name = basename($file_info['basename']);
                    $extension = strtolower($file_info['extension'] ?? '');
                    
                    // Create a safe, unique filename
                    $safe_filename = md5(time() . $original_name) . '.' . $extension;
                    $target_file = $upload_dir . $safe_filename;
                    
                    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    if (!in_array($extension, $allowed_types)) {
                        $error = "Only JPG, JPEG, PNG, GIF, & WEBP files are allowed.";
                    } elseif (!move_uploaded_file($_FILES['image_file']['tmp_name'], $target_file)) {
                        $error = "Error moving uploaded file. Check directory permissions (should be 777).";
                    } else {
                        // Store the path relative to the site root for display (e.g., 'images/portraits/...')
                        $db_image_filename = 'images/portraits/' . $safe_filename;
                        
                        // Delete old file if editing and a new file was uploaded
                        if ($_POST['action'] === 'edit' && $current_filename && $current_filename !== $db_image_filename) {
                            $old_file_path = '../' . $current_filename;
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                            }
                        }
                    }
                }
                // --- END FILE UPLOAD LOGIC ---
                
                // Final validation before database operation
                if (empty($db_image_filename)) {
                    $error = "Image file is required for new portraits and cannot be empty on update.";
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
                        // Update query should handle cases where image_filename might not change
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

    // Handle delete - redirected to from the JS confirmation modal
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --color-primary-elegant: #8B5CF6; /* Soft Lavender */
            --color-secondary-dark: #6B7280;
            --color-text-dark: #333333;
            --bg-light: #f4f6f9;
            --card-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            --color-delete: #EF4444;
            --color-edit: #F59E0B;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--color-text-dark);
        }
        
        .main-content {
            padding-left: 2rem;
            padding-right: 2rem;
            padding-top: 2.5rem; 
        }

        /* Header and Button Styling */
        .page-header h1 {
            font-weight: 800;
        }
        
        .btn-primary {
            background-color: var(--color-primary-elegant);
            border-color: var(--color-primary-elegant);
            font-weight: 600;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s ease;
        }
        .btn-primary:hover {
            background-color: #6D3FE6;
            border-color: #6D3FE6;
            transform: translateY(-1px);
        }

        /* Card and Table Styling */
        .card {
            border-radius: 18px;
            box-shadow: var(--card-shadow);
            border: none;
        }
        
        .table thead th {
            font-weight: 700;
            color: var(--color-secondary-dark);
            border-bottom-width: 2px;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f0f2f5;
        }
        
        /* Image Thumbnail Styling */
        .portrait-thumbnail {
            width: 70px; 
            height: 70px; 
            object-fit: cover; 
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        /* Category and Status Badges */
        .category-badge {
            display: inline-block;
            padding: 0.3em 0.7em;
            margin-right: 0.3em;
            margin-bottom: 0.3em;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: #EDE9FE; /* Lightest Lavender */
            color: var(--color-primary-elegant);
        }

        /* Action Buttons in Table */
        .btn-edit {
            background-color: var(--color-edit);
            border-color: var(--color-edit);
            color: #fff;
            border-radius: 8px;
            font-weight: 500;
            margin-right: 5px;
        }
        .btn-delete {
            background-color: var(--color-delete);
            border-color: var(--color-delete);
            color: #fff;
            border-radius: 8px;
            font-weight: 500;
        }
        
        /* Modal Styling */
        .modal-content {
            border-radius: 18px;
        }
        
        .modal-header {
            background-color: var(--color-primary-elegant);
            color: white;
            border-top-left-radius: 18px;
            border-top-right-radius: 18px;
            border-bottom: none;
        }
        
        .modal-header .btn-close {
            filter: invert(1);
        }
        
        .modal-footer .btn-primary {
             background-color: var(--color-primary-elegant);
             border-color: var(--color-primary-elegant);
        }
        .modal-footer .btn-danger {
            background-color: var(--color-delete);
            border-color: var(--color-delete);
        }

        /* Form styling adjustments */
        .form-control, .form-select {
            border-radius: 8px;
        }
        .rounded-3 {
            border-radius: 10px !important;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar placeholder -->
            <div class="col-md-3 col-lg-2 sidebar-wrapper">
                <?php include 'admin_sidebar.php'; ?>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center page-header">
                    <h1 class="h2 fw-bolder text-dark m-0">Manage Portfolio Portraits</h1>
                    <button class="btn btn-success btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#portraitModal"
                        onclick="resetForm()">
                        <i class="bi bi-play-circle-fill me-2"></i> Add New Portrait
                    </button>
                </div> 

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3"><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3"><?php echo h($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Order</th>
                                        <th style="width: 100px;">Image</th>
                                        <th>Title</th>
                                        <th>Categories</th>
                                        <th style="width: 100px;">Setcard</th>
                                        <th style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($portraits)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-folder-open me-2"></i> No portraits found. Use the button above to add one!
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($portraits as $p): ?>
                                            <tr>
                                                <td class="align-middle fw-bold"><?php echo h($p['sort_order']); ?></td>
                                                <td class="align-middle">
                                                    <!-- Note: The path starts with '.. /' because this file is in 'admin/' and images are in 'images/portraits/' -->
                                                    <img src="../<?php echo h($p['image_filename']); ?>"
                                                         alt="<?php echo h($p['title']); ?>"
                                                         class="portrait-thumbnail"
                                                         onerror="this.onerror=null;this.src='https://placehold.co/70x70/6B7280/FFFFFF?text=File+Missing';"
                                                    >
                                                </td>
                                                <td class="align-middle fw-medium"><?php echo h($p['title']); ?></td>
                                                <td class="align-middle">
                                                    <?php 
                                                    // Split the space-separated string back into individual categories for display
                                                    $cats = explode(' ', $p['categories']);
                                                    foreach ($cats as $cat) {
                                                         $cat = trim($cat); // Trim whitespace around category names
                                                         if (!empty($cat)) {
                                                            echo '<span class="category-badge">' . h($cat) . '</span>';
                                                         }
                                                    }
                                                    ?>
                                                </td>
                                                <td class="align-middle">
                                                    <span
                                                         class="badge rounded-pill bg-<?php echo $p['is_setcard'] ? 'info' : 'secondary'; ?> fw-semibold">
                                                         <?php echo $p['is_setcard'] ? 'Yes' : 'No'; ?>
                                                    </span>
                                                </td>
                                                <td class="align-middle">
                                                    <!-- Edit Button -->
                                                    <button class="btn btn-sm btn-edit"
                                                         onclick='editPortrait(<?php echo json_encode($p); ?>)'>
                                                         <i class="bi bi-pencil-square"></i> Edit
                                                    </button>
                                                    <!-- Delete Button using custom modal -->
                                                    <button class="btn btn-sm btn-delete" 
                                                         onclick="showDeleteModal(<?php echo h($p['portrait_id']); ?>)">
                                                         <i class="bi bi-trash"></i> Delete
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
            </main>
        </div>
    </div>


    <!-- Add/Edit Portrait Modal -->
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
                            <label for="image_file" class="form-label fw-bold">Upload Image File</label>
                            <input type="file" class="form-control" id="image_file" name="image_file" accept="image/*">
                            <div id="image_filename_display" class="mt-2 text-muted small" style="display: none;">
                                Current File: <span></span> (Upload new file to replace)
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Title</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-bold">Categories</label>
                            <div class="p-3 border rounded bg-light">
                                <?php if (empty($portraitCategories)): ?>
                                    <small class="text-danger">No categories found. Please add them manually if needed (in the `portrait_categories` table).</small>
                                <?php endif; ?>
                                <div class="row row-cols-3 g-2">
                                    <?php foreach($portraitCategories as $cat): ?>
                                        <div class="col">
                                            <div class="form-check">
                                                <input class="form-check-input portrait-category-checkbox" 
                                                    type="checkbox" 
                                                    name="categories[]" 
                                                    id="cat_<?php echo h($cat['name']); ?>" 
                                                    value="<?php echo h($cat['name']); ?>"
                                                >
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
                            <input type="number" class="form-control" id="sort_order" name="sort_order" value="0">
                            <div class="form-text">Lower numbers appear first. Default is 0.</div>
                        </div>

                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" id="is_setcard" name="is_setcard" role="switch">
                            <label class="form-check-label fw-bold" for="is_setcard">Mark as Set Card</label>
                            <div class="form-text">Display as a primary 'set card' image on the landing page.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-3">Save Portrait</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Custom Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-danger text-white border-0">
                    <h5 class="modal-title" id="deleteConfirmLabel"><i class="bi bi-exclamation-triangle-fill me-2"></i> Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <p class="mb-0 fw-medium">Are you sure you want to permanently delete this portrait?</p>
                    <small class="text-danger">This action cannot be undone. The file on the server will also be deleted.</small>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmDeleteButton" href="#" class="btn btn-danger rounded-3 fw-bold">Delete</a>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        /**
         * Resets the modal form for adding a new portrait.
         */
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Portrait';
            document.getElementById('action').value = 'add';
            document.getElementById('portrait_id').value = '';
            document.getElementById('current_filename').value = ''; 
            document.getElementById('image_file').required = true; 
            document.getElementById('image_filename_display').style.display = 'none';
            document.getElementById('image_file').value = ''; 
            document.getElementById('title').value = '';
            
            // Uncheck all categories
            document.querySelectorAll('.portrait-category-checkbox').forEach(cb => {
                cb.checked = false;
            });

            document.getElementById('sort_order').value = '0';
            document.getElementById('is_setcard').checked = false;
        }

        /**
         * Populates the modal form with data for editing an existing portrait.
         * @param {object} portrait - The portrait data object.
         */
        function editPortrait(portrait) {
            document.getElementById('modalTitle').textContent = 'Edit Portrait #' + portrait.portrait_id;
            document.getElementById('action').value = 'edit';
            document.getElementById('portrait_id').value = portrait.portrait_id;
            
            // Set the existing filename
            document.getElementById('current_filename').value = portrait.image_filename; 
            
            // Display the current filename info
            document.getElementById('image_filename_display').style.display = 'block';
            document.querySelector('#image_filename_display span').textContent = portrait.image_filename;
            
            // File input is not required for edits, clear value to prevent accidental resubmission
            document.getElementById('image_file').required = false; 
            document.getElementById('image_file').value = ''; 
            
            document.getElementById('title').value = portrait.title;
            document.getElementById('sort_order').value = portrait.sort_order;
            document.getElementById('is_setcard').checked = portrait.is_setcard == 1;

            // Handle Category Checkboxes
            
            // 1. Uncheck all first
            document.querySelectorAll('.portrait-category-checkbox').forEach(cb => {
                cb.checked = false; 
            });

            // 2. Check the ones that are in the portrait's category string
            if (portrait.categories) {
                // Split the space-separated string into an array
                const activeCategories = portrait.categories.split(/\s+/).filter(c => c.length > 0);
                
                activeCategories.forEach(catName => {
                    // Check the checkbox using the category name as the unique ID part
                    const checkbox = document.getElementById('cat_' + catName);
                    if (checkbox) {
                        checkbox.checked = true;
                    }
                });
            }

            var modal = new bootstrap.Modal(document.getElementById('portraitModal'));
            modal.show();
        }
        
        /**
         * Shows the custom delete confirmation modal.
         * @param {number} id - The ID of the portrait to delete.
         */
        function showDeleteModal(id) {
            // Set the href of the "Delete" button in the modal
            const deleteUrl = `?action=delete&id=${id}`;
            document.getElementById('confirmDeleteButton').setAttribute('href', deleteUrl);
            
            // Show the modal
            var deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            deleteModal.show();
        }
    </script>
</body>

</html>
