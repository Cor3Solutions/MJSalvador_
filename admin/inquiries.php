<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentPage = 'inquiries.php';
$inquiries = [];
$error = '';

try {
    $conn = getDBConnection();
    
    // Handle ARCHIVE action
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $inquiry_id = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'inquiries', 'inquiry_id', $inquiry_id)) {
            header('Location: inquiries.php?status=success');
            exit;
        } else {
            $error = 'Failed to archive inquiry.';
        }
    }
    
    // Logic to handle marking as read/unread
    if (isset($_GET['action'], $_GET['id'])) {
        $inquiry_id = (int)$_GET['id'];
        
        if ($_GET['action'] == 'mark_read') {
            $stmt = $conn->prepare("UPDATE inquiries SET is_read = 1 WHERE inquiry_id = :id");
            $stmt->execute([':id' => $inquiry_id]);
        } elseif ($_GET['action'] == 'mark_unread') {
            $stmt = $conn->prepare("UPDATE inquiries SET is_read = 0 WHERE inquiry_id = :id");
            $stmt->execute([':id' => $inquiry_id]);
        }
        
        header('Location: inquiries.php');
        exit;
    }
    
    // Fetch only NON-archived inquiries
    $stmt = $conn->query("SELECT * FROM inquiries WHERE is_archived = 0 ORDER BY submission_date DESC");
    $inquiries = $stmt->fetchAll();

    // Get archived count
    $archived_count = getArchivedCount($conn, 'inquiries');
    
} catch(PDOException $e) {
    error_log("Inquiries error: " . $e->getMessage());
    $error = 'Database error: Could not load inquiries.';
}

$success = '';
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $success = 'Inquiry archived successfully!';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox: Client Inquiries - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
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
                            <i class="bi bi-inbox me-2" style="color: var(--jade-primary);"></i>
                            Client Inbox
                        </h1>
                        <p class="text-muted mb-0">Review and manage inquiries</p>
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
                            <strong><?php echo $archived_count; ?></strong> inquiry(ies) are currently archived.
                        </div>
                        <a href="archives.php?tab=inquiries" class="btn btn-sm btn-info">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Sender Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Inquiry Type</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($inquiries)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                🎉 All clear! No new inquiries at the moment.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($inquiries as $i): ?>
                                            <tr class="<?php echo $i['is_read'] ? '' : 'table-warning'; ?>">
                                                <td><?php echo $i['inquiry_id']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($i['submission_date'])); ?></td>
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>" class="text-dark fw-bold text-decoration-none">
                                                        <?php echo h($i['full_name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo h($i['email']); ?></td>
                                                <td><?php echo h($i['phone_number'] ?: '-'); ?></td>
                                                <td><span class="badge rounded-pill bg-primary"><?php echo h($i['inquiry_type']); ?></span></td>
                                                <td>
                                                    <span class="badge rounded-pill bg-<?php echo $i['is_read'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $i['is_read'] ? 'Read' : 'Unread'; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-primary me-1">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if($i['is_read']): ?>
                                                        <a href="?action=mark_unread&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-secondary me-1" title="Mark as Unread">
                                                            <i class="bi bi-envelope"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="?action=mark_read&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-success me-1" title="Mark as Read">
                                                            <i class="bi bi-check-lg"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    
                                                    <button type="button" class="btn btn-sm btn-warning"
                                                        onclick="showArchiveModal(<?php echo $i['inquiry_id']; ?>, '<?php echo h(addslashes($i['full_name'])); ?>')">
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
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Inquiry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive" style="font-size: 3rem; color: var(--warning);"></i>
                    <p class="mt-3 mb-2 fw-medium">Archive this inquiry?</p>
                    <p class="text-muted">From: <strong><span id="archiveItemTitle"></span></strong></p>
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
        function showArchiveModal(id, name) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = name;
            new bootstrap.Modal(document.getElementById('archiveConfirmModal')).show();
        }
    </script>
</body>
</html>