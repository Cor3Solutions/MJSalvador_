<?php
require_once '../config.php';
// Define current page for sidebar activation
$currentPage = 'partners.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$partners = [];
$error = '';
$success_message = '';

// ===============================================
// New function for Uniqueness Check (Unchanged)
// ===============================================
/**
 * Checks if a partner name is unique (excluding the current partner ID).
 * @param PDO $conn Database connection.
 * @param string $name Partner name to check.
 * @param int $partner_id ID of the partner being edited (0 for new partner).
 * @return bool True if unique, false otherwise.
 */
function is_partner_name_unique($conn, $name, $partner_id = 0) {
    $sql = "SELECT COUNT(*) FROM partners WHERE name = :name AND partner_id != :id";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':name' => $name, ':id' => $partner_id]);
    return $stmt->fetchColumn() == 0;
}

try {
    $conn = getDBConnection();
    // ... (rest of PHP logic is unchanged) ...
    // ===============================================
    // 1. HANDLE POST SUBMISSION (ADD/EDIT/SAVE)
    // ===============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'save_partner') {
        
        $partner_id = (int)($_POST['partner_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        $current_logo = $_POST['current_logo'] ?? '';
        $new_logo_file = $current_logo;
        
        // --- DUPLICATE CHECK ---
        if (!is_partner_name_unique($conn, $name, $partner_id)) {
            $error = "A partner with the name '{$name}' already exists. Please choose a unique name.";
        } else {
            // --- PROCEED WITH SAVE ---
            $conn->beginTransaction();

            // Handle file upload
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
                    
                    // If editing, delete the old file if it exists
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
                // Update Partner
                $stmt = $conn->prepare("UPDATE partners SET name = :name, logo_image_file = :logo, sort_order = :sort WHERE partner_id = :id");
                $stmt->execute([':name' => $name, ':logo' => $new_logo_file, ':sort' => $sort_order, ':id' => $partner_id]);
                $success_message = 'Partner updated successfully!';
            } else {
                // Insert New Partner
                $stmt = $conn->prepare("INSERT INTO partners (name, logo_image_file, sort_order) VALUES (:name, :logo, :sort)");
                $stmt->execute([':name' => $name, ':logo' => $new_logo_file, ':sort' => $sort_order]);
                $success_message = 'New partner added successfully!';
            }
            
            $conn->commit();
            // Redirect/Reload on success
            header('Location: partners.php?status=' . urlencode($success_message));
            exit;
        } // End of unique check else block
    }
    
    // ===============================================
    // 2. HANDLE DELETE ACTION (GET) - Unchanged
    // ===============================================
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $partner_id = (int) $_GET['id'];
        
        $stmt = $conn->prepare("SELECT logo_image_file FROM partners WHERE partner_id = :id");
        $stmt->execute([':id' => $partner_id]);
        $partner_to_delete = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $conn->prepare("DELETE FROM partners WHERE partner_id = :id");
        $stmt->execute([':id' => $partner_id]);
        
        if ($partner_to_delete && $partner_to_delete['logo_image_file']) {
            $file_path = '../images/partners/' . $partner_to_delete['logo_image_file'];
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }

        header('Location: partners.php?status=' . urlencode('Partner deleted successfully!'));
        exit;
    }
    
    // ===============================================
    // 3. FETCH ALL PARTNERS (READ) - Unchanged
    // ===============================================
    $stmt = $conn->query("SELECT * FROM partners ORDER BY sort_order ASC, name ASC");
    $partners = $stmt->fetchAll();

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Partners error: " . $e->getMessage());
    $error = 'Operation failed: ' . $e->getMessage();
}

if (isset($_GET['status'])) {
    $success_message = h($_GET['status']);
}

// ===============================================
// PHP FUNCTION TO RENDER THE FORM (For AJAX call) - Unchanged
// ===============================================
function render_partner_form($conn, $partner_id = 0) {
    $partner = null;
    
    if ($partner_id > 0) {
        try {
            $stmt = $conn->prepare("SELECT * FROM partners WHERE partner_id = :id");
            $stmt->execute([':id' => $partner_id]);
            $partner = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            return '<div class="alert alert-danger">Error loading data: ' . $e->getMessage() . '</div>';
        }
    }
    
    ob_start();
    ?>
    <div id="form-message"></div>

    <form id="partnerForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="form_action" value="save_partner">
        <input type="hidden" name="partner_id" value="<?php echo $partner_id; ?>">
        <input type="hidden" name="current_logo" value="<?php echo h($partner['logo_image_file'] ?? ''); ?>">

        <div class="mb-3">
            <label for="name" class="form-label">Partner/Brand Name</label>
            <input type="text" class="form-control" id="name" name="name" 
                   value="<?php echo h($partner['name'] ?? ''); ?>" required>
        </div>

        <div class="mb-3">
            <label for="sort_order" class="form-label">Display Order</label>
            <input type="number" class="form-control" id="sort_order" name="sort_order" 
                   value="<?php echo h($partner['sort_order'] ?? 0); ?>">
            <small class="text-muted">Lower numbers appear first.</small>
        </div>

        <div class="mb-3">
            <label for="logo_image_file" class="form-label">Logo Image (JPG/PNG)</label>
            <input class="form-control" type="file" id="logo_image_file" name="logo_image_file" 
                   accept="image/png, image/jpeg">
            <?php if ($partner && $partner['logo_image_file']): ?>
                <div class="mt-2">
                    <p class="mb-1">Current Logo:</p>
                    <img src="../images/partners/<?php echo h($partner['logo_image_file']); ?>" 
                         alt="Current Logo" style="max-width: 150px; height: auto; border: 1px solid #ddd;">
                    <small class="d-block text-muted">Upload a new image to replace the current one.</small>
                </div>
            <?php endif; ?>
        </div>

        <div class="modal-footer px-0 pb-0">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary">Save Partner</button>
        </div>
    </form>
    <?php
    $html = ob_get_clean();
    return $html;
}

// Check if the script is being called via AJAX to load the form content - Unchanged
if (isset($_GET['ajax_action']) && $_GET['ajax_action'] === 'load_form') {
    header('Content-Type: text/html');
    echo render_partner_form($conn, (int)$_GET['id']);
    exit;
}
?>

<?php include 'admin_header.php'; ?>
<div class="container-fluid">
    <div class="row">

        <?php include 'admin_sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4"> 

            <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
                <h1 class="h2">Manage Partners</h1>
                
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#partnerModal" 
                        data-id="0">
                    <i class="bi bi-plus-lg"></i> Add New Partner
                </button>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo h($error); ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Order</th>
                                    <th>Logo</th>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($partners)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No partners found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($partners as $p): ?>
                                        <tr>
                                            <td><?php echo $p['sort_order']; ?></td>
                                            <td>
                                                <?php if ($p['logo_image_file']): ?>
                                                    <img src="../images/partners/<?php echo h($p['logo_image_file']); ?>"
                                                        alt="<?php echo h($p['name']); ?>"
                                                        style="width: 60px; height: auto; object-fit: contain;">
                                                <?php else: ?>
                                                    N/A
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo h($p['name']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-warning me-1 edit-btn" data-bs-toggle="modal" 
                                                        data-bs-target="#partnerModal" data-id="<?php echo $p['partner_id']; ?>">
                                                    Edit
                                                </button>
                                                <a href="?action=delete&id=<?php echo $p['partner_id']; ?>"
                                                    onclick="return confirm('Are you sure you want to delete this partner?');"
                                                    class="btn btn-sm btn-danger">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="partnerModal" tabindex="-1" aria-labelledby="partnerModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="partnerModalLabel">Add/Edit Partner</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="partnerModalBody">
                            <div class="text-center py-5">Loading form...</div>
                        </div>
                    </div>
                </div>
            </div>

        </main> </div> </div> <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var partnerModal = $('#partnerModal');
    var modalBody = $('#partnerModalBody');
    var modalTitle = $('#partnerModalLabel');
    var pageUrl = 'partners.php'; // The current file

    // Function to load the form content into the modal via AJAX
    function loadFormContent(partnerId) {
        modalBody.html('<div class="text-center py-5"><i class="bi bi-hourglass-split"></i> Loading form...</div>');
        
        // AJAX call to get ONLY the form HTML from the PHP file
        $.ajax({
            url: pageUrl + '?ajax_action=load_form&id=' + partnerId,
            type: 'GET',
            dataType: 'html',
            success: function(data) {
                modalBody.html(data);
                // Update modal title based on ID
                if (partnerId > 0) {
                    modalTitle.text('Edit Partner (ID: ' + partnerId + ')');
                } else {
                    modalTitle.text('Add New Partner');
                }
            },
            error: function() {
                modalBody.html('<div class="alert alert-danger">Error loading form content.</div>');
            }
        });
    }

    // Listener for when the modal is triggered (Add/Edit buttons)
    partnerModal.on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var partnerId = button.data('id');
        loadFormContent(partnerId);
    });

    // Handle AJAX Form Submission (Delegated event handling)
    modalBody.on('submit', '#partnerForm', function(e) {
        e.preventDefault();
        var formData = new FormData(this); // Captures file uploads

        // Display a loading message
        $('#form-message').html('<div class="alert alert-info">Saving... please wait.</div>');

        // Note: The success case relies on the PHP redirect causing a full page reload.
        // The error handler below will catch non-redirect responses (including the duplicate error text).
        $.ajax({
            url: pageUrl,
            type: 'POST',
            data: formData,
            processData: false, 
            contentType: false, 
            success: function(response) {
                 // If the response contains the duplicate error, display it in the form.
                 if (response.includes("A partner with the name")) {
                     $('#form-message').html('<div class="alert alert-danger">' + response + '</div>');
                 }
                 // Otherwise, expect a redirect/reload from the server.
            },
            error: function(xhr) {
                // If a server-side PHP error occurs, display it.
                $('#form-message').html('<div class="alert alert-danger">Error: ' + (xhr.responseText.substring(0, 200) || 'An unknown error occurred.') + '</div>');
            }
        });
    });
});
</script>

<?php 
/*
 * We remove the unnecessary closing tags at the end of the file, as they are now correctly placed.
 */
?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>