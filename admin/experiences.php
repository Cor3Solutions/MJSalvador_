<?php
// experiences.php

// --- Configuration and Dependencies ---
require_once '../config.php'; // Assumes this provides getDBConnection()
require_once 'includes/archive_functions.php';

// Helper function for HTML escaping
if (!function_exists('h')) {
    function h($text) {
        // Use ?? '' for null coalescing to ensure htmlspecialchars doesn't get null
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Start session if not started (important for flash messages and auth)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Define current page for sidebar activation
$currentPage = 'experiences.php';

// --- Initialization ---
$experiences = [];
$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Define the list of controlled categories
$category_options = [
    "Professional Experience",
    "Events & Modeling - Modeled & Ushered For",
    "Events & Modeling - Runway",
    "Events & Modeling - Advertised Condominiums",
    "Brand Ambassadress Currently For",
    "TV & Commercials",
    "Hosted For"
];

try {
    $conn = getDBConnection();

    // ===============================================
    // 1. HANDLE POST SUBMISSION (CREATE or UPDATE)
    // ===============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        $action = $_POST['action'];
        $success = false;
        $message_content = '';
        $redirect = true; // Default to redirect
        
        // Handle CREATE or UPDATE
        if (in_array($action, ['create', 'update'])) {
            
            // 1. Input Validation and Sanitization
            $errors = [];
            $exp_id = ($action === 'update' && isset($_POST['exp_id'])) ? (int)$_POST['exp_id'] : null;

            $title      = trim($_POST['title'] ?? '');
            $subtitle   = trim($_POST['subtitle'] ?? '');
            $category   = trim($_POST['category'] ?? '');
            $date_range = trim($_POST['date_range'] ?? '');
            $details    = trim($_POST['details'] ?? '');
            $sort_order = filter_var($_POST['sort_order'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

            if (empty($title))     $errors[] = "Title is required.";
            if (!in_array($category, $category_options)) $errors[] = "Category is required and must be selected from the predefined list.";
            if ($sort_order === false || $sort_order === null) $errors[] = "Sort Order must be a non-negative integer.";

            if (empty($errors)) {
                $params = compact('title', 'subtitle', 'category', 'date_range', 'details', 'sort_order');
                
                if ($action === 'create') {
                    $stmt = $conn->prepare("INSERT INTO experiences (title, subtitle, category, date_range, details, sort_order) VALUES (:title, :subtitle, :category, :date_range, :details, :sort_order)");
                    $success = $stmt->execute($params);
                    $message_content = $success ? "New experience **created** successfully." : "Failed to create experience.";
                } elseif ($action === 'update' && $exp_id > 0) {
                    $params['exp_id'] = $exp_id; // Add ID to parameters for WHERE clause
                    $stmt = $conn->prepare("UPDATE experiences SET title = :title, subtitle = :subtitle, category = :category, date_range = :date_range, details = :details, sort_order = :sort_order WHERE exp_id = :exp_id");
                    $success = $stmt->execute($params);
                    $message_content = $success ? "Experience #{$exp_id} successfully **updated**." : "Failed to update experience.";
                } else {
                    $success = false;
                    $message_content = "Invalid action or missing experience ID for update.";
                }
            } else {
                // Validation failed: Do not redirect (PRG fail)
                $success = false;
                $message_content = "Validation Error: " . implode(' ', $errors);
                $redirect = false;
                // Preserve POST data in session for re-display in form
                $_SESSION['post_data'] = $_POST;
            }
        }
        
        // Handle DELETE (Moved to POST to follow PRG, though the original was POST-based)
        elseif ($action === 'delete' && !empty($_POST['exp_id'])) {
            $exp_id = (int) $_POST['exp_id'];
            if ($exp_id > 0) {
                $stmt = $conn->prepare("DELETE FROM experiences WHERE exp_id = :id");
                $success = $stmt->execute([':id' => $exp_id]);
                $message_content = $success ? "Experience #{$exp_id} successfully **deleted**." : "Failed to delete experience.";
            } else {
                $success = false;
                $message_content = "Invalid experience ID for deletion.";
            }
        }

        // Set flash message
        $flash_message_type = $success ? 'success' : 'danger';
        $_SESSION['flash_message'] = ['type' => $flash_message_type, 'content' => $message_content];

        // Post/Redirect/Get pattern: Redirect on success or non-validation errors
        if ($redirect) {
            header('Location: experiences.php');
            exit;
        } else {
             // If validation failed, set $error to display it immediately
             $error = $message_content;
             $flash_message = null; 
        }
    }

    // ===============================================
    // 2. Data Fetching (Read)
    // ===============================================
    $stmt = $conn->query("SELECT * FROM experiences ORDER BY category ASC, sort_order ASC");
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Experiences error: " . $e->getMessage());
    $error = 'Database error: Could not load experiences. Please check server logs.';
}

// ===============================================
// 3. Determine Form Data for Edit/New Mode
// ===============================================
// Check for a GET request with an ID (for initial edit load)
$edit_exp_id = (int)($_GET['id'] ?? 0);
$form_data = [];
$form_action = 'create';

// Check for re-posted data from a failed validation (takes precedence over GET ID)
if (isset($_SESSION['post_data'])) {
    $form_data = $_SESSION['post_data'];
    $edit_exp_id = (int)($form_data['exp_id'] ?? 0);
    $form_action = $form_data['action'] ?? 'create';
    unset($_SESSION['post_data']); // Clear session data
} 
// If no reposted data, check for GET ID
elseif ($edit_exp_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM experiences WHERE exp_id = :id");
        $stmt->execute([':id' => $edit_exp_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $form_data = $data;
            $form_action = 'update';
        } else {
            // ID not found, switch to New mode
            $edit_exp_id = 0;
            $form_action = 'create';
        }
    } catch(PDOException $e) {
        $error = 'Error loading data for edit: ' . $e->getMessage();
        $edit_exp_id = 0;
        $form_action = 'create';
    }
}

// ===============================================
// PHP FUNCTION TO RENDER THE FORM (Inline use)
// ===============================================
function render_experience_form_inline($data, $exp_id, $action, $category_options) {
    // Determine title and button text
    $is_edit = $action === 'update' && $exp_id > 0;
    $card_title = $is_edit ? 'Edit Experience (ID: ' . h($exp_id) . ')' : 'Add New Experience';
    $button_text = $is_edit ? '<i class="bi bi-floppy me-2"></i> Save Changes' : '<i class="bi bi-plus-lg me-2"></i> Create Item';
    $button_class = $is_edit ? 'btn-warning' : 'btn-primary';
    
    // Default values
    $data_title = h($data['title'] ?? '');
    $data_subtitle = h($data['subtitle'] ?? '');
    $data_category = h($data['category'] ?? '');
    $data_date_range = h($data['date_range'] ?? '');
    $data_sort_order = h($data['sort_order'] ?? 10);
    $data_details = h($data['details'] ?? '');
    
    ?>
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-dark text-white rounded-top-2">
            <h5 class="mb-0 fw-bold"><?php echo $card_title; ?></h5>
        </div>
        <div class="card-body">
            <form id="experienceForm" method="POST" action="experiences.php">
                <input type="hidden" name="exp_id" value="<?php echo h($exp_id); ?>">
                <input type="hidden" name="action" value="<?php echo h($action); ?>">

                <div class="mb-3">
                    <label for="title" class="form-label fw-bold">Title / Company Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo $data_title; ?>" required maxlength="100">
                </div>

                <div class="mb-3">
                    <label for="subtitle" class="form-label">Subtitle / Position / Degree</label>
                    <input type="text" class="form-control" id="subtitle" name="subtitle" value="<?php echo $data_subtitle; ?>" maxlength="100">
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label fw-bold">Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="category" name="category" required>
                            <option value="" disabled <?php echo ($data_category === '' ? 'selected' : ''); ?>>Select a Category</option>
                            <?php foreach ($category_options as $option): ?>
                                <option value="<?php echo h($option); ?>" <?php echo ($data_category === $option ? 'selected' : ''); ?>>
                                    <?php echo h($option); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="date_range" class="form-label">Date Range</label>
                        <input type="text" class="form-control" id="date_range" name="date_range" value="<?php echo $data_date_range; ?>" maxlength="50"
                            placeholder="e.g., 2018 - Present, Jan 2020">
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label fw-bold">Sort Order (Lower is Higher Priority) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo $data_sort_order; ?>" required min="0">
                    <small class="form-text text-muted">A lower number means it appears higher in the list.</small>
                </div>

                <div class="mb-3">
                    <label for="details" class="form-label">Description / Bullet Points</label>
                    <textarea class="form-control" id="details" name="details" rows="5"><?php echo $data_details; ?></textarea>
                    <small class="form-text text-muted">Use line breaks or markdown for formatting.</small>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn <?php echo $button_class; ?> shadow-sm py-2">
                        <?php echo $button_text; ?>
                    </button>
                    <?php if ($is_edit): ?>
                        <a href="experiences.php" class="btn btn-secondary py-2">
                            <i class="bi bi-x-circle me-2"></i> Cancel Edit / Add New
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Experiences</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body {
            background-color: #f8f9fa; 
        }
        .main-content {
            padding: 0 1rem; 
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
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); 
        }
        .table thead th {
            background-color: #f0f2f5; 
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                
                <div class="content-header d-flex justify-content-between align-items-center">
                    <h1 class="h2 fw-bolder text-dark m-0"><i class="bi bi-briefcase me-2"></i>Experience Management</h1>
                    
                    <?php if ($edit_exp_id > 0): ?>
                        <a href="experiences.php" class="btn btn-success btn-lg shadow-sm">
                            <i class="bi bi-plus-lg me-2"></i> Add New Item
                        </a>
                    <?php endif; ?>
                </div>

                <div class="row px-3">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert"><?php echo h($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($flash_message): ?>
                        <div class="alert alert-<?php echo h($flash_message['type']); ?> alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                            <?php echo $flash_message['content']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row px-3">
                    
                    <div class="col-lg-4 col-md-12">
                        <?php render_experience_form_inline($form_data, $edit_exp_id, $form_action, $category_options); ?>
                    </div>

                    <div class="col-lg-8 col-md-12">
                        <div class="card mb-5 shadow-lg">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-3">Order</th>
                                                <th class="py-3">Category</th>
                                                <th class="py-3">Title</th>
                                                <th class="py-3">Date Range</th>
                                                <th class="text-center py-3">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($experiences)): ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted py-4">
                                                        <i class="bi bi-info-circle me-1"></i> No experiences found.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($experiences as $e): ?>
                                                    <tr>
                                                        <td class="text-center">
                                                             <span class="badge bg-secondary"><?php echo h($e['sort_order']); ?></span>
                                                        </td>
                                                        <td><span class="badge bg-info text-dark"><?php echo h($e['category']); ?></span></td>
                                                        <td class="fw-semibold">
                                                            <?php echo h($e['title']); ?>
                                                            <small class="d-block text-muted"><?php echo h($e['subtitle']); ?></small>
                                                        </td>
                                                        <td><?php echo h($e['date_range']); ?></td>
                                                        <td class="text-center">
                                                            <a href="?id=<?php echo $e['exp_id']; ?>" class="btn btn-sm btn-warning me-1 text-white" title="Edit Experience"> 
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                                data-id="<?php echo h($e['exp_id']); ?>"
                                                                data-title="<?php echo h($e['title']); ?>"
                                                                title="Delete Experience">
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
                    </div></div></main>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="experiences.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="exp_id" id="deleteFormId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const deleteForm = document.getElementById('deleteForm');
            const deleteFormId = document.getElementById('deleteFormId');
            
            // SweetAlert for Delete Confirmation
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const title = this.getAttribute('data-title');

                    Swal.fire({
                        title: 'Confirm Deletion',
                        html: `Are you sure you want to permanently delete **${title}** (ID: ${id})? This cannot be undone.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Bootstrap Danger
                        cancelButtonColor: '#6c757d', // Bootstrap Secondary
                        confirmButtonText: '<i class="bi bi-trash"></i> Yes, Delete it!',
                        cancelButtonText: '<i class="bi bi-x-lg"></i> Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            deleteFormId.value = id;
                            deleteForm.submit();
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>