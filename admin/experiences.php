<?php
// experiences.php

// --- Configuration and Dependencies ---
require_once '../config.php'; // Assumes this provides getDBConnection()

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

// --- Initialization ---
$experiences = [];
$error = '';
// Retrieve and consume the flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// NEW: Define the list of controlled categories based on user request
// These will populate the dropdown and are used for organizing the public-facing CV
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

    // --- Action Handling (C, U, D via POST) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $success = false;
        $message_content = '';
        $redirect = true;

        // --- DELETE Action ---
        if ($action === 'delete' && !empty($_POST['exp_id'])) {
            $exp_id = (int) $_POST['exp_id'];
            // Added simple check to prevent deleting ID 0 if not necessary
            if ($exp_id > 0) {
                $stmt = $conn->prepare("DELETE FROM experiences WHERE exp_id = :id");
                $success = $stmt->execute([':id' => $exp_id]);
                $message_content = $success ? "Experience #{$exp_id} successfully **deleted**." : "Failed to delete experience.";
            } else {
                $success = false;
                $message_content = "Invalid experience ID for deletion.";
            }

        }
        // --- CREATE or UPDATE Action ---
        elseif (in_array($action, ['create', 'update'])) {
            
            // 1. Input Validation and Sanitization
            $errors = [];
            $exp_id = ($action === 'update' && isset($_POST['exp_id'])) ? (int)$_POST['exp_id'] : null;

            $title       = trim($_POST['title'] ?? '');
            $subtitle    = trim($_POST['subtitle'] ?? '');
            $category    = trim($_POST['category'] ?? '');
            $date_range  = trim($_POST['date_range'] ?? '');
            $details     = trim($_POST['details'] ?? ''); // Details can contain line breaks, don't strip
            $sort_order = filter_var($_POST['sort_order'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

            if (empty($title))       $errors[] = "Title is required.";
            // Validate category: must be selected from the defined list
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
                // If validation fails, set the error and **DO NOT REDIRECT**
                $success = false;
                $message_content = "Validation Error: " . implode(' ', $errors);
                $redirect = false;
                // Preserve POST data in session for re-display in form
                $_SESSION['post_data'] = $_POST;
            }
        }

        // Set flash message
        $flash_message_type = $success ? 'success' : 'danger';
        $_SESSION['flash_message'] = ['type' => $flash_message_type, 'content' => $message_content];

        // Post/Redirect/Get pattern: Only redirect on success or non-validation errors
        if ($redirect) {
            header('Location: experiences.php');
            exit;
        } else {
             // If validation failed, clear the flash message to handle it below,
             // but keep the post data for the form.
             $error = $message_content;
             $flash_message = null; // Prevent showing flash message and error block
        }
    }

    // --- Data Fetching (Read) ---
    // Fetch all experiences, sorted by category and sort_order
    $stmt = $conn->query("SELECT * FROM experiences ORDER BY category ASC, sort_order ASC");
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log the error for internal debugging
    error_log("Experiences error: " . $e->getMessage());
    $error = 'Database error: Could not load experiences. Please check server logs.';
}

// Handle non-redirected POST data for re-populating the form on validation error
$post_data = $_SESSION['post_data'] ?? null;
unset($_SESSION['post_data']);

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
        .main-content {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
        .table td {
            vertical-align: middle;
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
                    <h1 class="h2"><i class="bi bi-briefcase me-2"></i>Manage Experiences / Portfolio Items</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                        data-bs-target="#experienceModal" id="addNewBtn">
                        <i class="bi bi-plus-lg"></i> Add New Item
                    </button>
                </div>

                <?php if ($error || $flash_message): ?>
                    <div class="alert alert-<?php echo h($flash_message ? $flash_message['type'] : 'danger'); ?> alert-dismissible fade show"
                        role="alert">
                        <?php echo h($flash_message ? $flash_message['content'] : $error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card mb-4 shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center">Order</th>
                                        <th>Category</th>
                                        <th>Title</th>
                                        <th>Subtitle</th>
                                        <th>Date Range</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($experiences)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-info-circle me-1"></i> No experiences found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($experiences as $e): ?>
                                            <tr>
                                                <td class="text-center"><?php echo h($e['sort_order']); ?></td>
                                                <td><span
                                                        class="badge bg-secondary"><?php echo h($e['category']); ?></span>
                                                </td>
                                                <td><?php echo h($e['title']); ?></td>
                                                <td><?php echo h($e['subtitle']); ?></td>
                                                <td><?php echo h($e['date_range']); ?></td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-sm btn-warning me-1 edit-btn"
                                                        data-bs-toggle="modal" data-bs-target="#experienceModal"
                                                        data-id="<?php echo h($e['exp_id']); ?>"
                                                        data-title="<?php echo h($e['title']); ?>"
                                                        data-subtitle="<?php echo h($e['subtitle']); ?>"
                                                        data-category="<?php echo h($e['category']); ?>"
                                                        data-daterange="<?php echo h($e['date_range']); ?>"
                                                        data-sortorder="<?php echo h($e['sort_order']); ?>"
                                                        data-details="<?php echo h($e['details']); ?>"
                                                        title="Edit Experience">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
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

            </main>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="experiences.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="exp_id" id="deleteFormId">
    </form>

    <div class="modal fade" id="experienceModal" tabindex="-1" aria-labelledby="experienceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="experienceForm" method="POST" action="experiences.php">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="experienceModalLabel">Add New Experience</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <input type="hidden" name="exp_id" id="exp_id_field">
                        <input type="hidden" name="action" id="action_field">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title / Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" required maxlength="100">
                        </div>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Subtitle / Position / Degree</label>
                            <input type="text" class="form-control" id="subtitle" name="subtitle" maxlength="100">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                                <!-- UPDATED: Replaced text input with select dropdown for controlled categories -->
                                <select class="form-select" id="category" name="category" required>
                                    <option value="" disabled selected>Select a Category</option>
                                    <?php foreach ($category_options as $option): ?>
                                        <option value="<?php echo h($option); ?>"><?php echo h($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="date_range" class="form-label">Date Range</label>
                                <input type="text" class="form-control" id="date_range" name="date_range" maxlength="50"
                                    placeholder="e.g., 2018 - Present, Jan 2020">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="sort_order" class="form-label">Sort Order (Lower is Higher Priority) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" required min="0" value="10">
                            <small class="form-text text-muted">A lower number means it appears higher in the list.</small>
                        </div>

                        <div class="mb-3">
                            <label for="details" class="form-label">Description / Bullet Points</label>
                            <textarea class="form-control" id="details" name="details" rows="5"></textarea>
                            <small class="form-text text-muted">Use line breaks or markdown for formatting.</small>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="modalSaveButton">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('experienceModal');
            const modalTitle = document.getElementById('experienceModalLabel');
            const form = document.getElementById('experienceForm');
            const expIdField = document.getElementById('exp_id_field');
            const actionField = document.getElementById('action_field');
            const modalSaveButton = document.getElementById('modalSaveButton');
            const deleteForm = document.getElementById('deleteForm');
            const deleteFormId = document.getElementById('deleteFormId');
            
            // Bootstrap Modal instance for programatic control
            const experienceModal = new bootstrap.Modal(modal);

            // Function to reset the form for "Add" mode
            function resetForm() {
                form.reset();
                expIdField.value = '';
                actionField.value = 'create';
                modalTitle.textContent = 'Add New Experience';
                modalSaveButton.textContent = 'Create Item';
                modalSaveButton.classList.remove('btn-warning');
                modalSaveButton.classList.add('btn-primary');
                // Ensure the select defaults to the placeholder
                document.getElementById('category').value = "";
            }

            // Function to populate and switch to "Edit" mode
            function populateFormForEdit(data) {
                modalTitle.textContent = `Edit Experience (ID: ${data.id})`;
                modalSaveButton.textContent = 'Save Changes';
                modalSaveButton.classList.remove('btn-primary');
                modalSaveButton.classList.add('btn-warning');

                // Populate form fields
                document.getElementById('title').value = data.title;
                document.getElementById('subtitle').value = data.subtitle;
                // This line now sets the value of the <select>
                document.getElementById('category').value = data.category;
                document.getElementById('date_range').value = data.daterange;
                document.getElementById('sort_order').value = data.sortorder;
                document.getElementById('details').value = data.details;

                // Set hidden fields for update action
                expIdField.value = data.id;
                actionField.value = 'update';
            }

            // 1. Handle "Add New Item" button click
            document.getElementById('addNewBtn').addEventListener('click', resetForm);

            // 2. Handle "Edit" button clicks
            document.querySelectorAll('.edit-btn').forEach(button => {
                button.addEventListener('click', function () {
                    const data = {
                        id: this.getAttribute('data-id'),
                        title: this.getAttribute('data-title'),
                        subtitle: this.getAttribute('data-subtitle'),
                        category: this.getAttribute('data-category'),
                        daterange: this.getAttribute('data-daterange'),
                        sortorder: this.getAttribute('data-sortorder'),
                        details: this.getAttribute('data-details')
                    };
                    populateFormForEdit(data);
                });
            });

            // 3. SweetAlert for Delete Confirmation
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
            
            // 4. Handle failed validation, re-open modal with POST data if available
            <?php if (!empty($post_data) && $error): ?>
                // Data structure mimics the data attributes on the edit button
                const postData = {
                    id: '<?php echo h($post_data['exp_id'] ?? ''); ?>',
                    title: '<?php echo h($post_data['title'] ?? ''); ?>',
                    subtitle: '<?php echo h($post_data['subtitle'] ?? ''); ?>',
                    category: '<?php echo h($post_data['category'] ?? ''); ?>',
                    daterange: '<?php echo h($post_data['date_range'] ?? ''); ?>',
                    sortorder: '<?php echo h($post_data['sort_order'] ?? ''); ?>',
                    details: `<?php echo addslashes(h($post_data['details'] ?? '')); ?>` // Handle new lines and escaping
                };

                // Decide between Create/Update based on the action field
                if ('<?php echo h($post_data['action'] ?? ''); ?>' === 'update' && postData.id) {
                    populateFormForEdit(postData);
                } else {
                    resetForm();
                    // Manually populate fields that resetForm cleared, just to be safe
                    document.getElementById('title').value = postData.title;
                    document.getElementById('subtitle').value = postData.subtitle;
                    document.getElementById('category').value = postData.category;
                    document.getElementById('date_range').value = postData.daterange;
                    document.getElementById('sort_order').value = postData.sortorder;
                    document.getElementById('details').value = postData.details;
                }
                
                // Show the modal
                experienceModal.show();
            <?php endif; ?>
        });
    </script>
</body>

</html>
