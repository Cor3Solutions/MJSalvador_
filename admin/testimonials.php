<?php
require_once '../config.php';

// Helper function for HTML escaping (assumed to be available in config.php or similar)
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Start session if not started (important for flash messages)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$testimonials = [];
$error = '';
// Retrieve and consume the flash message
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

try {
    $conn = getDBConnection();
    $message = '';

    // --- Action Handling (Uses POST for security) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
        $testimonial_id = (int) $_POST['id'];
        $action = $_POST['action'];

        $success = false;

        if ($action == 'approve') {
            $stmt = $conn->prepare("UPDATE testimonials SET is_approved = 1 WHERE testimonial_id = :id");
            $success = $stmt->execute([':id' => $testimonial_id]);
            $message = $success ? "Testimonial #{$testimonial_id} successfully **approved**." : "Failed to approve testimonial.";
        } elseif ($action == 'reject') {
            $stmt = $conn->prepare("UPDATE testimonials SET is_approved = 0 WHERE testimonial_id = :id");
            $success = $stmt->execute([':id' => $testimonial_id]);
            $message = $success ? "Testimonial #{$testimonial_id} successfully **rejected** (marked as pending)." : "Failed to reject testimonial.";
        } elseif ($action == 'delete') {
            $stmt = $conn->prepare("DELETE FROM testimonials WHERE testimonial_id = :id");
            $success = $stmt->execute([':id' => $testimonial_id]);
            $message = $success ? "Testimonial #{$testimonial_id} permanently **deleted**." : "Failed to delete testimonial.";
        }

        // Set flash message before redirecting
        if ($success) {
             $_SESSION['flash_message'] = ['type' => 'success', 'content' => $message];
        } else {
             $_SESSION['flash_message'] = ['type' => 'danger', 'content' => $message];
        }

        // Post/Redirect/Get pattern to prevent re-submission
        header('Location: testimonials.php');
        exit;
    }

    // --- Data Fetching ---
    // Fetch all testimonials, ordering unapproved (0) first, then by ID
    $stmt = $conn->query("SELECT * FROM testimonials ORDER BY is_approved ASC, testimonial_id DESC");
    $testimonials = $stmt->fetchAll();

} catch (PDOException $e) {
    // Log the error for internal debugging
    error_log("Testimonials error: " . $e->getMessage());
    $error = 'Database error: Could not load testimonials.';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS added here -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table-quote-preview {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        /* Style for pending rows */
        .table-warning td {
            font-weight: 500;
        }
        /* Custom sidebar/main-content layout */
        .main-content {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">

            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2"><i class="bi bi-chat-quote-fill me-2"></i>Manage Testimonials</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo h($error); ?></div>
                <?php endif; ?>

                <?php if ($flash_message): ?>
                    <div class="alert alert-<?php echo h($flash_message['type']); ?> alert-dismissible fade show" role="alert">
                        <?php echo $flash_message['content']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th>Client Name</th>
                                        <th>Client Title</th>
                                        <th>Quote Preview</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($testimonials)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="bi bi-info-circle me-1"></i> No testimonials found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($testimonials as $t): ?>
                                            <tr class="<?php echo $t['is_approved'] ? '' : 'table-warning'; ?>">
                                                <td class="text-center"><?php echo $t['testimonial_id']; ?></td>
                                                <td><?php echo h($t['client_name']); ?></td>
                                                <td><?php echo h($t['client_title']); ?></td>
                                                <td class="table-quote-preview" title="<?php echo h($t['quote_text']); ?>">
                                                    <?php echo h(substr($t['quote_text'], 0, 80)) . (strlen($t['quote_text']) > 80 ? '...' : ''); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-<?php echo $t['is_approved'] ? 'success' : 'secondary'; ?> rounded-pill">
                                                        <?php echo $t['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <!-- Approve/Reject Button -->
                                                    <?php if (!$t['is_approved']): ?>
                                                        <button type="button"
                                                            class="btn btn-sm btn-success me-1 action-btn"
                                                            data-action="approve"
                                                            data-id="<?php echo $t['testimonial_id']; ?>"
                                                            title="Approve Testimonial">
                                                            <i class="bi bi-check-lg"></i> Approve
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="button"
                                                            class="btn btn-sm btn-secondary me-1 action-btn"
                                                            data-action="reject"
                                                            data-id="<?php echo $t['testimonial_id']; ?>"
                                                            title="Reject (Mark as Pending)">
                                                            <i class="bi bi-x-lg"></i> Reject
                                                        </button>
                                                    <?php endif; ?>

                                                    <!-- Delete Button (Triggers SweetAlert) -->
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?php echo $t['testimonial_id']; ?>"
                                                        data-name="<?php echo h($t['client_name']); ?>"
                                                        title="Delete Testimonial">
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

    <!-- 1. Hidden POST Form for Actions (Approve/Reject/Delete) -->
    <!-- This form is submitted via JavaScript to ensure POST method is used -->
    <form id="actionForm" method="POST" action="testimonials.php" style="display: none;">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
    </form>

    <!-- The custom Bootstrap delete modal HTML was removed here -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 JS added here -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const actionForm = document.getElementById('actionForm');
            const formAction = document.getElementById('formAction');
            const formId = document.getElementById('formId');

            // --- 1. Handle Approve/Reject Actions via POST ---
            document.querySelectorAll('.action-btn').forEach(button => {
                button.addEventListener('click', function() {
                    formAction.value = this.getAttribute('data-action');
                    formId.value = this.getAttribute('data-id');
                    actionForm.submit();
                });
            });

            // --- 2. Handle SweetAlert Delete Confirmation and Submission ---
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Are you sure?',
                        html: `You are about to permanently delete the testimonial from <strong>${name}</strong> (ID: ${id}).`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#dc3545', // Bootstrap Danger
                        cancelButtonColor: '#6c757d', // Bootstrap Secondary
                        confirmButtonText: '<i class="bi bi-trash"></i> Yes, delete it!',
                        cancelButtonText: '<i class="bi bi-x-lg"></i> Cancel'
                    }).then((result) => {
                        // Check if the user confirmed the action
                        if (result.isConfirmed) {
                            formAction.value = 'delete';
                            formId.value = id;
                            actionForm.submit();
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>
