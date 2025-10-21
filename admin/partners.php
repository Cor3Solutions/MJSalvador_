<?php
// partners.php - Manage brand partners and sponsors

// --- Configuration and Dependencies ---
require_once '../config.php';
// The archive_functions.php should include the archiveRecord and getArchivedCount functions
require_once 'includes/archive_functions.php'; 

// --- Authentication and Setup ---
$currentPage = 'partners.php';
$pageTitle = 'Partner Management';

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Initialize session flash messages
$error = $_SESSION['partner_form_error'] ?? '';
$success_message = $_GET['status'] ?? '';
unset($_SESSION['partner_form_error'], $_GET['status']);

$partners = [];
$archived_count = 0;

// Helper function to check for unique partner name
function is_partner_name_unique($conn, $name, $partner_id = 0) {
    $sql = "SELECT COUNT(*) FROM partners WHERE name = :name AND partner_id != :id AND is_archived = 0";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':name' => $name, ':id' => $partner_id]);
    return $stmt->fetchColumn() == 0;
}

try {
    $conn = getDBConnection();

    // --- Handle ARCHIVE action ---
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $partner_id = (int) $_GET['id'];
        
        if (archiveRecord($conn, 'partners', 'partner_id', $partner_id)) {
            // PRG pattern for success
            $_SESSION['flash_message'] = ['type' => 'success', 'content' => 'Partner archived successfully!'];
        } else {
            $_SESSION['flash_message'] = ['type' => 'danger', 'content' => 'Failed to archive partner.'];
        }
        header('Location: partners.php');
        exit;
    }

    // --- Handle POST SUBMISSION (ADD/EDIT/SAVE) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'save_partner') {
        
        $partner_id = (int)($_POST['partner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_logo = $_POST['current_logo'] ?? '';
        $new_logo_file = $current_logo;
        $error_flag = false;

        if (!is_partner_name_unique($conn, $name, $partner_id)) {
            $error = "A partner with the name '{$name}' already exists. Please choose a unique name.";
            $error_flag = true;
        }

        if (!$error_flag) {
            try {
                $conn->beginTransaction();
                $upload_dir = '../images/partners/';

                // 1. Handle File Upload
                if (isset($_FILES['logo_image_file']) && $_FILES['logo_image_file']['error'] === UPLOAD_ERR_OK) {
                    
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file = $_FILES['logo_image_file'];
                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_types = ['jpg', 'jpeg', 'png'];

                    if (!in_array($file_extension, $allowed_types)) {
                        throw new Exception("Invalid file type. Only JPG and PNG are allowed.");
                    }
                    
                    $new_filename = uniqid('partner_') . '.' . $file_extension;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        
                        // New file successfully uploaded
                        $new_logo_file = $new_filename; 

                        // Delete old file if updating and the old file is different from the new one
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
                // Validation: New record must have a logo
                elseif ($partner_id == 0 && empty($new_logo_file)) {
                     throw new Exception('New partner requires a logo image.');
                }
                
                // 2. Database Operation
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
                
                // Store success message in session for PRG redirect
                $_SESSION['flash_message'] = ['type' => 'success', 'content' => $success_message];
                header('Location: partners.php');
                exit;

            } catch (Exception $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                error_log("Partner save error: " . $e->getMessage());
                // Store error for redisplay
                $error = 'Operation failed: ' . $e->getMessage();
                $_SESSION['partner_form_data'] = $_POST;
            }
        }
    }
    
    // --- Data Fetching ---
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
    $error = 'Database error: Could not load data. ' . $e->getMessage();
}

// Retrieve flash message after all processing
if (isset($_SESSION['flash_message'])) {
    $flash = $_SESSION['flash_message'];
    if ($flash['type'] === 'success') {
        $success_message = $flash['content'];
    } elseif ($flash['type'] === 'danger') {
        $error = $flash['content'];
    }
    unset($_SESSION['flash_message']);
}


// --- Form Data Preparation ---
$edit_partner_id = (int)($_GET['id'] ?? 0);
$form_data = [];

if ($edit_partner_id > 0) {
    try {
        $stmt = $conn->prepare("SELECT * FROM partners WHERE partner_id = :id AND is_archived = 0");
        $stmt->execute([':id' => $edit_partner_id]);
        $form_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$form_data) {
            $edit_partner_id = 0; // Not found or archived
        }
    } catch(PDOException $e) {
        $error = 'Error loading data for edit: ' . $e->getMessage();
        $edit_partner_id = 0;
    }
}

// Re-populate form with POST data if there was an error
if (isset($_SESSION['partner_form_data'])) {
    // Merge existing form data (if editing) with POST data (for error)
    $form_data = array_merge($form_data, $_SESSION['partner_form_data']);
    $edit_partner_id = (int)($form_data['partner_id'] ?? 0);
    unset($_SESSION['partner_form_data']);
}


// ===============================================
// PHP FUNCTION TO RENDER THE PARTNER FORM
// ===============================================
function render_partner_form_inline($data, $partner_id) {
    // Current logo is what's in the DB or POST (if error)
    $current_logo = $data['logo_image_file'] ?? ($data['current_logo'] ?? '');
    $upload_dir_public = '../images/partners/';
    ?>
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-primary text-white rounded-top-3">
            <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge-fill me-2"></i><?php echo ($partner_id > 0 ? 'Edit Partner (ID: ' . $partner_id . ')' : 'Add New Partner'); ?></h5>
        </div>
        <div class="card-body">
            <form id="partnerForm" method="POST" action="partners.php" enctype="multipart/form-data">
                <input type="hidden" name="form_action" value="save_partner">
                <input type="hidden" name="partner_id" value="<?php echo $partner_id; ?>">
                <input type="hidden" name="current_logo" value="<?php echo h($current_logo); ?>">

                <div class="mb-3">
                    <label for="name" class="form-label fw-bold">Partner/Brand Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" 
                            value="<?php echo h($data['name'] ?? ''); ?>" required placeholder="e.g., Acme Corp.">
                </div>

                <div class="mb-3">
                    <label for="sort_order" class="form-label fw-bold">Display Order</label>
                    <input type="number" class="form-control" id="sort_order" name="sort_order" 
                            value="<?php echo h($data['sort_order'] ?? 0); ?>">
                    <small class="text-muted">Lower numbers appear first (e.g., 1 is top priority)</small>
                </div>

                <div class="mb-4">
                    <label for="logo_image_file" class="form-label fw-bold">Logo Image (JPG/PNG) <?php echo ($partner_id == 0 ? '<span class="text-danger">*</span>' : ''); ?></label>
                    <input class="form-control" type="file" id="logo_image_file" name="logo_image_file" 
                            accept="image/png, image/jpeg" <?php echo ($partner_id == 0 && empty($current_logo) ? 'required' : ''); ?>>
                    
                    <?php if ($current_logo): ?>
                        <div class="mt-3 p-3 border rounded bg-light d-flex align-items-center">
                            <img src="<?php echo $upload_dir_public . h($current_logo); ?>" 
                                    alt="Current Logo" class="img-thumbnail me-3" style="max-width: 80px; max-height: 80px; object-fit: contain;">
                            <div>
                                <p class="mb-0 fw-semibold">Current Logo: <?php echo h($current_logo); ?></p>
                                <small class="text-muted">Upload a new image above to replace it.</small>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary shadow-sm py-2">
                          <i class="bi bi-floppy me-2"></i> <?php echo ($partner_id > 0 ? 'Update Partner' : 'Save New Partner'); ?>
                    </button>
                    <?php if ($partner_id > 0): ?>
                        <a href="partners.php" class="btn btn-outline-secondary py-2">
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
    <title><?php echo h($pageTitle); ?> - Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet"> 
    
    <style>
    /* Custom styles for partner table responsiveness and aesthetics */
    .logo-container img {
        max-width: 60px;
        height: auto;
        object-fit: contain;
        padding: 4px;
        border: 1px solid var(--bs-gray-300);
        border-radius: 6px;
    }

    .table td, .table th {
        vertical-align: middle;
        padding: 0.8rem 0.75rem;
    }

    /* Action button grouping and responsiveness */
    td.text-end {
        white-space: nowrap; /* Prevent buttons from wrapping by default */
    }
    td.text-end .btn {
        margin-left: 5px; /* Spacing between buttons */
        min-width: 90px; /* Ensure buttons are readable */
    }

    /* Make buttons stack nicely on small screens */
    @media (max-width: 767.98px) {
        .main-content { padding-left: 15px; padding-right: 15px; } /* Adjust for mobile padding */
        td.text-end {
            white-space: normal !important;
            text-align: left !important;
            display: flex; /* Flex container for action buttons */
            flex-direction: column; /* Stack buttons vertically */
            align-items: flex-end; /* Align to the right in the cell */
        }
        td.text-end .btn {
            width: 100%; /* Full width in column layout */
            margin-left: 0;
            margin-bottom: 5px;
            justify-content: center; /* Center text/icon */
        }
    }
</style>

</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <div>
                        <h1 class="h2 fw-bold mb-1">
                            <i class="bi bi-building me-2 text-primary"></i><?php echo h($pageTitle); ?>
                        </h1>
                        <p class="text-muted mb-0">Manage brand partners and sponsors</p>
                    </div>
                    <?php if ($edit_partner_id > 0): ?>
                        <a href="partners.php" class="btn btn-success rounded-pill px-3">
                            <i class="bi bi-plus-lg me-1"></i> Add New
                        </a>
                    <?php endif; ?>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo h($success_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($archived_count > 0): ?>
                    <div class="alert alert-info d-flex justify-content-between align-items-center rounded-3 shadow-sm mb-4">
                        <div>
                            <i class="bi bi-archive-fill me-2"></i>
                            <strong><?php echo $archived_count; ?></strong> partner(s) are currently archived.
                        </div>
                        <a href="archives.php?tab=partners" class="btn btn-sm btn-info text-white">
                            <i class="bi bi-eye me-1"></i> View Archives
                        </a>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-4 col-md-12 order-lg-1 order-2">
                        <?php render_partner_form_inline($form_data, $edit_partner_id); ?>
                    </div>

                    <div class="col-lg-8 col-md-12 order-lg-2 order-1 mb-4">
                        <div class="card shadow-lg">
                            <div class="card-header bg-light">
                                <h5 class="mb-0 fw-bold text-dark">Active Partners (<?php echo count($partners); ?>)</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="py-3 ps-4" style="width: 10%;">Order</th>
                                                <th class="py-3" style="width: 15%;">Logo</th>
                                                <th class="py-3" style="width: 40%;">Name</th>
                                                <th class="py-3 text-end pe-4" style="width: 35%;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($partners)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-5">
                                                        <i class="bi bi-link-45deg me-2"></i> No active partners found.
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
                                                        <td class="fw-semibold text-break"><?php echo h($p['name']); ?></td>
                                                        <td class="text-end pe-4">
                                                            <a href="?id=<?php echo $p['partner_id']; ?>" class="btn btn-sm btn-primary"> 
                                                                <i class="bi bi-pencil-fill"></i> Edit
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-warning"
                                                                onclick="showArchiveModal(<?php echo $p['partner_id']; ?>, '<?php echo h(addslashes($p['name'])); ?>')">
                                                                <i class="bi bi-archive-fill"></i> Archive
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

    <div class="modal fade" id="archiveConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 shadow-lg">
                <div class="modal-header bg-warning text-dark border-0">
                    <h5 class="modal-title"><i class="bi bi-archive-fill me-2"></i> Archive Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-archive-fill text-warning" style="font-size: 3rem;"></i>
                    <p class="mt-3 mb-2 fw-medium">Are you sure you want to archive this partner?</p>
                    <p class="text-muted"><strong>"<span id="archiveItemTitle"></span>"</strong></p>
                    <div class="alert alert-light border mt-3 mb-0">
                        <small><i class="bi bi-info-circle me-1"></i>The partner will be hidden from the main list but can be restored from the Archives page.</small>
                    </div>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <a id="confirmArchiveButton" href="#" class="btn btn-warning rounded-3 fw-bold">
                        <i class="bi bi-archive me-2"></i>Confirm Archive
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-theme.js"></script>
    <script>
        // JS function to populate and show the archive modal
        function showArchiveModal(id, name) {
            document.getElementById('confirmArchiveButton').setAttribute('href', `?action=archive&id=${id}`);
            document.getElementById('archiveItemTitle').textContent = name;
            // Use Bootstrap 5 API to show modal
            const archiveModal = new bootstrap.Modal(document.getElementById('archiveConfirmModal'));
            archiveModal.show();
        }
    </script>
</body>
</html>