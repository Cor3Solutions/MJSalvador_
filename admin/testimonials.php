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

    // --- Action Handling (Uses POST for security and PRG pattern) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
        $testimonial_id = (int) $_POST['id'];
        $action = $_POST['action'];

        $success = false;
        $message = '';

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

        // Set flash message before redirecting (PRG pattern)
        $_SESSION['flash_message'] = [
            'type' => $success ? 'success' : 'danger',
            'content' => $message
        ];

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table-quote-preview {
            max-width: 200px;
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
                                        <th class="text-center" style="width: 150px;">Action</th>
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
                                                    <?php echo h(substr($t['quote_text'], 0, 40)) . (strlen($t['quote_text']) > 40 ? '...' : ''); ?>
                                                </td>
                                                <td class="text-center">
                                                    <span
                                                        class="badge bg-<?php echo $t['is_approved'] ? 'success' : 'secondary'; ?> rounded-pill">
                                                        <?php echo $t['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-info text-white review-btn"
                                                        data-id="<?php echo $t['testimonial_id']; ?>"
                                                        data-name="<?php echo h($t['client_name']); ?>"
                                                        data-title="<?php echo h($t['client_title']); ?>"
                                                        data-quote-full="<?php echo h($t['quote_text']); ?>"
                                                        data-is-approved="<?php echo (int)$t['is_approved']; ?>"
                                                        title="Review and Act">
                                                        <i class="bi bi-search"></i> Review
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

    <form id="actionForm" method="POST" action="testimonials.php" style="display: none;">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const actionForm = document.getElementById('actionForm');
            const formAction = document.getElementById('formAction');
            const formId = document.getElementById('formId');

            // Helper to submit the hidden form
            function submitAction(id, action) {
                formAction.value = action;
                formId.value = id;
                actionForm.submit();
            }

            // --- Combined Review and Action SweetAlert (using radio input) ---
            document.querySelectorAll('.review-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const title = this.getAttribute('data-title');
                    const quoteFull = this.getAttribute('data-quote-full');
                    const isApproved = parseInt(this.getAttribute('data-is-approved'));

                    // Escape HTML for display in SweetAlert HTML content
                    const escapedQuote = quoteFull.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    const escapedName = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
                    const escapedTitle = title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');

                    // Define the action options
                    const inputOptions = {
                        'approve': 'Approve (Publish to site)',
                        'reject': 'Reject (Mark as pending)',
                        'delete': 'Permanently Delete'
                    };

                    // Set the default action based on current status
                    const defaultAction = isApproved ? 'reject' : 'approve';

                    Swal.fire({
                        title: `Review Testimonial #${id}`,
                        icon: 'info',
                        html: `
                            <div class="text-start">
                                <p class="text-muted mb-1">Current Status: <strong>${isApproved ? 'Approved' : 'Pending'}</strong></p>
                                <hr>
                                <p class="fs-6 mb-0">"${escapedQuote}"</p>
                                <footer class="blockquote-footer mt-2">
                                    ${escapedName}, <cite>${escapedTitle}</cite>
                                </footer>
                                <hr>
                                <p class="mb-2">Select an action:</p>
                            </div>
                        `,
                        input: 'radio',
                        inputOptions: inputOptions,
                        inputValue: defaultAction, // Pre-select the most likely action
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-lightning-charge"></i> Execute Action',
                        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancel',
                        reverseButtons: true,
                        preConfirm: (selectedAction) => {
                            if (!selectedAction) {
                                Swal.showValidationMessage('Please select an action to proceed.');
                                return false;
                            }
                            return selectedAction;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const action = result.value;
                            
                            // For Delete action, show a FINAL confirmation prompt
                            if (action === 'delete') {
                                Swal.fire({
                                    title: 'Confirm Delete',
                                    text: `Are you absolutely sure you want to permanently delete testimonial #${id} from ${name}? This cannot be undone.`,
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#dc3545',
                                    cancelButtonColor: '#6c757d',
                                    confirmButtonText: '<i class="bi bi-trash"></i> Yes, Delete It!',
                                    cancelButtonText: '<i class="bi bi-x-lg"></i> Cancel'
                                }).then((deleteResult) => {
                                    if (deleteResult.isConfirmed) {
                                        submitAction(id, action);
                                    }
                                });
                            } else {
                                // Approve or Reject action is executed immediately
                                submitAction(id, action);
                            }
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>