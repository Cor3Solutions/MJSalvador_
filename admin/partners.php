<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';

$currentPage = 'partners.php';

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$partners = [];
$error = '';
$success_message = '';

function is_partner_name_unique($conn, $name, $partner_id = 0) {
    $sql = "SELECT COUNT(*) FROM partners WHERE name = :name AND partner_id != :id AND is_archived = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':name' => $name, ':id' => $partner_id]);
    return $stmt->fetchColumn() == 0;
}

try {
    $conn = getDBConnection();

    // Handle ARCHIVE action
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $partner_id = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'partners', 'partner_id', $partner_id)) {
            header('Location: partners.php?status=' . urlencode('Partner archived successfully!'));
            exit;
        } else {
            $error = 'Failed to archive partner.';
        }
    }

    // Handle POST SUBMISSION (ADD/EDIT/SAVE)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'save_partner') {
        
        $partner_id = (int)($_POST['partner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_logo = $_POST['current_logo'] ?? '';
        $new_logo_file = $current_logo;
        
        if (!is_partner_name_unique($conn, $name, $partner_id)) {
            $error = "A partner with the name '{$name}' already exists. Please choose a unique name.";
            $_SESSION['partner_form_data'] = $_POST;
            $_SESSION['partner_form_error'] = $error;
        } else {
            try {
                $conn->beginTransaction();

                if (isset($_FILES['logo_image_file']) && $_FILES['logo_image_file']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = '../images/partners/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = pathinfo($_FILES['logo_image_file']['name'], PATHINFO_EXTENSION);
                    $new_filename = uniqid('partner_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['logo_image_file']['tmp_name'], $upload_path)) {
                        $new_logo_file = $new_filename;
                        
                        if ($partner_id > 0 && $current_logo && $current_logo !== $new_logo_file) {
                            $old_file_path = $upload_dir . $current_logo;
                            if (file_exists($old_file_path)) {
                                unlink($old_file_path);
                            }
                        }
                    } else {
                        throw new Exception('File upload failed. Check folder permissions.');
                    }
                }
                
                if ($partner_id > 0) {
                    $stmt = $conn->prepare("UPDATE partners SET name = :name, logo_image_file = :logo, sort_order = :sort WHERE partner_id = :id");
                    $stmt->execute([':name' => $name, ':logo' => $new_logo_file, ':sort' => $sort_order, ':id' => $partner_id]);
                    $success_message = 'Partner updated successfully!';
                } else {
                    $stmt = $conn->prepare("INSERT INTO partners (name, logo_image_file, sort_order) VALUES (:name, :logo, :sort)");
                    $stmt->execute([':name' => $name, ':logo' => $new_logo_file, ':sort' => $sort_order]);
                    $success_message = 'New partner added successfully!';
                }
                
                $conn->commit();
                unset($_SESSION['partner_form_data']);
                unset($_SESSION['partner_form_error']);
                header('Location: partners.php?status=' . urlencode($success_message));
                exit;
            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Partner save error: " . $e->getMessage());
                $error = 'Operation failed: ' . $e->getMessage();
            }
        }
    }
    
    // Fetch only NON-archived partners
    $stmt = $conn->query("SELECT * FROM partners WHERE is_archived = 0 ORDER BY sort_order ASC, name ASC");
    $partners = $stmt->fetchAll();

    // Get archived count
    $archived_count = getArchivedCount($conn, 'partners');

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Partners fetch error: " . $e->getMessage());
    $error = 'Operation failed: ' . $e->getMessage();
}

if (isset($_GET['status'])) {
    $success_message = h($_GET['status']);
}

if (isset($_SESSION['partner_form_error'])) {
    $error = h($_SESSION['partner_form_error']);
    unset($_SESSION['partner_form_error']);
}

$edit_partner_id = (int)($_GET['id'] ?? 0);
$form_data = [];

if ($edit_partner_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM partners WHERE partner_id = :id");
        $stmt->execute([':id' => $edit_partner_id]);
        $form_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$form_data) {
            $edit_partner_id = 0;
        }
    } catch(PDOException $e) {
        $error = 'Error loading data for edit: ' . $e->getMessage();
        $edit_partner_id = 0;
    }
}

if (isset($_SESSION['partner_form_data'])) {
    $form_data = $_SESSION['partner_form_data'];
    $edit_partner_id = (int)($form_data['partner_id'] ?? 0);
    unset($_SESSION['partner_form_data']);
}

function render_partner_form_inline($data, $partner_id) {
    $current_logo = $data['logo_image_file'] ?? ($data['current_logo'] ?? '');
    ?>
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 fw-bold"><?php echo ($partner_id > 0 ? 'Edit Partner (ID: ' . $partner_id . ')' : 'Add New Partner'); ?></h5>
        </div>
        <div class="card-body">
            <form id="partnerForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="save_partner">
                <input type="hidden" name="partner_id" value="<?php echo $partner_id; ?>">
                <input type="hidden" name="current_logo" value="<?php echo h($current_logo); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Partner/Brand Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                            value="<?php echo h($data['name'] ?? ''); ?>" required placeholder="e.g., Acme Corp.">
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label fw-bold">Display Order</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                            value="<?php echo h($data['sort_order'] ?? 0); ?>">
                    <small class="text-muted">Lower numbers appear first</small>
                </div>

                <div class="mb-3">
                    <label for="logo_image_file" class="form-label fw-bold">Logo Image (JPG/PNG)</label>
                    <input class="form-control" type="file" id="logo_image_file" name="logo_image_file" 
                            accept="image/png, image/jpeg" <?php echo ($partner_id == 0 && empty($current_logo) ? 'required' : ''); ?>>
                    
                    <?php if ($current_logo): ?>
                        <div class="mt-2 p-2 border rounded bg-light">
                            <p class="mb-1 fw-semibold">Current Logo:</p>
                            <img src="../images/partners/<?php echo h($current_logo); ?>" 
                                    alt="Current Logo" class="img-thumbnail" style="max-width: 150px;">
                            <small class="d-block text-muted mt-1">Upload new image to replace</small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary shadow-sm py-2">
                         <i class="bi bi-floppy me-2"></i> Save Partner
                    </button>
                    <?php if ($partner_id > 0): ?>
                        <a href="partners.php" class="btn btn-secondary py-2">
                            <i class="bi bi-x-circle me-2"></i> Cancel Edit
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
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Partners - Jade Salvador Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
    .logo-container img {
        max-width: 60px;
        height: auto;
        object-fit: contain;
        padding: 4px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
    }

    /* Force the table to fit the container */
    .table-responsive {
        overflow-x: hidden !important;
    }

    table.table {
        width: 100%;
        table-layout: auto;
        word-wrap: break-word;
    }

    /* Compact columns */
    th:nth-child(1), td:nth-child(1) { width: 8%; }
    th:nth-child(2), td:nth-child(2) { width: 12%; }
    th:nth-child(3), td:nth-child(3) { width: 45%; }
    th:nth-child(4), td:nth-child(4) { width: 35%; }

    /* Keep buttons flexible and wrap if needed */
    td.text-end {
        white-space: normal !important;
    }

    td.text-end .btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 4px;
        flex-wrap: nowrap;
    }

    /* Responsive stacking of action buttons on smaller screens */
    @media (max-width: 992px) {
        td.text-end .btn {
            width: 100%;
            justify-content: center;
        }
        td.text-end {
            text-align: center !important;
        }
    }

    /* Prevent padding overflow */
    .table td, .table th {
        vertical-align: middle;
        padding: 0.6rem 0.75rem;
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
                            <i class="bi bi-building me-2" style="color: var(--jade-primary);"></i>Partner Management
                        </h1>
                        <p class="text-muted mb-0">Manage brand partners and sponsors</p>
                    </div>
                    <?php if ($edit_partner_id > 0): ?>
                        <a href="partners.php" class="btn btn-success">
                            <i class="bi bi-plus-lg me-2"></i> Add New Partner
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3">
                        <i class="bi bi-check-circle me-2"></i><?php echo h($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($archived_count > 0): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center rounded-3 shadow-sm">
                        <div>
                            <i class="bi bi-archive me-2"></i>
                            <strong><?php echo $archived_count; ?></strong> partner(s) are currently archived.
                        </div>
                        <a href="archives.php?tab=partners" class="btn btn-sm btn-info">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4 col-md-12">
                        <?php render_partner_form_inline($form_data, $edit_partner_id); ?>
                    </div>

                    <div class="col-lg-8 col-md-12">
                        <div class="card shadow-sm">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="py-3 ps-4">Order</th>
                                                <th class="py-3">Logo</th>
                                                <th class="py-3">Name</th>
                                                <th class="py-3 text-end pe-4">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($partners)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-5">
                                                        <i class="bi bi-link-45deg me-2"></i> No partners found
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($partners as $p): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <span class="badge bg-secondary"><?php echo h($p['sort_order']); ?></span>
                                                        </td>
                                                        <td class="logo-container">
                                                            <?php if ($p['logo_image_file']): ?>
                                                                <img src="../images/partners/<?php echo h($p['logo_image_file']); ?>"
                                                                    alt="<?php echo h($p['name']); ?>">
                                                            <?php else: ?>
                                                                <span class="text-muted small">N/A</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="fw-semibold"><?php echo h($p['name']); ?></td>
                                                        <td class="text-end pe-4">
                                                            <a href="?id=<?php echo $p['partner_id']; ?>" class="btn btn-sm btn-primary me-2"> 
                                                                <i class="bi bi-pencil"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                onclick="showArchiveModal(<?php echo $p['partner_id']; ?>, '<?php echo h(addslashes($p['name'])); ?>')">
                                                                <i class="bi bi-archive"></i> Archive
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
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive" style="font-size: 3rem; color: var(--warning);"></i>
                    <p class="mt-3 mb-2 fw-medium">Archive this partner?</p>
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
        function showArchiveModal(id, name) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = name;
            new bootstrap.Modal(document.getElementById('archiveConfirmModal')).show();
        }
    </script>
</body>
</html>