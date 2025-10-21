<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';

if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentPage = 'testimonials.php';
$testimonials = [];
$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

try {
    $conn = getDBConnection();

    // Handle ARCHIVE action
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $testimonial_id = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'testimonials', 'testimonial_id', $testimonial_id)) {
            $_SESSION['flash_message'] = ['type' => 'success', 'content' => 'Testimonial archived successfully!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'content' => 'Failed to archive testimonial.'];
        }
        header('Location: testimonials.php');
        exit;
    }

    // Handle POST actions (approve, reject)
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
        }

        $_SESSION['flash_message'] = [
            'type' => $success ? 'success' : 'danger',
            'content' => $message
        ];

        header('Location: testimonials.php');
        exit;
    }

    // Fetch only NON-archived testimonials
    $stmt = $conn->query("SELECT * FROM testimonials WHERE is_archived = 0 ORDER BY is_approved ASC, testimonial_id DESC");
    $testimonials = $stmt->fetchAll();

    // Get archived count
    $archived_count = getArchivedCount($conn, 'testimonials');

} catch (PDOException $e) {
    error_log("Testimonials error: " . $e->getMessage());
    $error = 'Database error: Could not load testimonials.';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Testimonials - Jade Salvador Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
        .table-quote-preview {
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .table-warning td {
            font-weight: 500;
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
                            <i class="bi bi-chat-quote-fill me-2" style="color: var(--jade-primary);"></i>Manage Testimonials
                        </h1>
                        <p class="text-muted mb-0">Review, approve, or archive client testimonials</p>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($flash_message): ?>
                    <div class="alert alert-<?php echo h($flash_message['type']); ?> alert-dismissible fade show rounded-3">
                        <?php echo $flash_message['content']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($archived_count > 0): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center rounded-3 shadow-sm">
                        <div>
                            <i class="bi bi-archive me-2"></i>
                            <strong><?php echo $archived_count; ?></strong> testimonial(s) are currently archived.
                        </div>
                        <a href="archives.php?tab=testimonials" class="btn btn-sm btn-info">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-center">ID</th>
                                        <th>Client Name</th>
                                        <th>Client Title</th>
                                        <th>Quote Preview</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center" style="width: 200px;">Actions</th>
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
                                                    <span class="badge bg-<?php echo $t['is_approved'] ? 'success' : 'secondary'; ?> rounded-pill">
                                                        <?php echo $t['is_approved'] ? 'Approved' : 'Pending'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <button type="button"
                                                        class="btn btn-sm btn-info text-white me-1 review-btn"
                                                        data-id="<?php echo $t['testimonial_id']; ?>"
                                                        data-name="<?php echo h($t['client_name']); ?>"
                                                        data-title="<?php echo h($t['client_title']); ?>"
                                                        data-quote-full="<?php echo h($t['quote_text']); ?>"
                                                        data-is-approved="<?php echo (int)$t['is_approved']; ?>"
                                                        title="Review">
                                                        <i class="bi bi-search"></i> Review
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="showArchiveModal(<?php echo $t['testimonial_id']; ?>, '<?php echo h(addslashes($t['client_name'])); ?>')">
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
            </main>
        </div>
    </div>

    <!-- Archive Modal -->
    <div class="modal fade" id="archiveConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Testimonial</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive" style="font-size: 3rem; color: var(--warning);"></i>
                    <p class="mt-3 mb-2 fw-medium">Archive this testimonial?</p>
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

    <form id="actionForm" method="POST" action="testimonials.php" style="display: none;">
        <input type="hidden" name="action" id="formAction">
        <input type="hidden" name="id" id="formId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const actionForm = document.getElementById('actionForm');
            const formAction = document.getElementById('formAction');
            const formId = document.getElementById('formId');

            function submitAction(id, action) {
                formAction.value = action;
                formId.value = id;
                actionForm.submit();
            }

            document.querySelectorAll('.review-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const title = this.getAttribute('data-title');
                    const quoteFull = this.getAttribute('data-quote-full');
                    const isApproved = parseInt(this.getAttribute('data-is-approved'));

                    const escapedQuote = quoteFull.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const escapedName = name.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    const escapedTitle = title.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    const inputOptions = {
                        'approve': 'Approve (Publish to site)',
                        'reject': 'Reject (Mark as pending)'
                    };

                    const defaultAction = isApproved ? 'reject' : 'approve';

                    Swal.fire({
                        title: `Review Testimonial #${id}`,
                        icon: 'info',
                        html: `
                            <div class="text-start">
                                <p class="text-muted mb-1">Status: <strong>${isApproved ? 'Approved' : 'Pending'}</strong></p>
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
                        inputValue: defaultAction,
                        showCancelButton: true,
                        confirmButtonText: '<i class="bi bi-lightning-charge"></i> Execute Action',
                        cancelButtonText: '<i class="bi bi-x-circle"></i> Cancel',
                        reverseButtons: true,
                        preConfirm: (selectedAction) => {
                            if (!selectedAction) {
                                Swal.showValidationMessage('Please select an action.');
                                return false;
                            }
                            return selectedAction;
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            submitAction(id, result.value);
                        }
                    });
                });
            });
        });
        
        function showArchiveModal(id, name) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = name;
            new bootstrap.Modal(document.getElementById('archiveConfirmModal')).show();
        }
    </script>
</body>
</html>