<?php
// resume_management.php - Manage CV/Resume uploads

// --- Configuration and Dependencies ---
require_once '../config.php'; // Assumes this provides getDBConnection()

// Helper function for HTML escaping
if (!function_exists('h')) {
    function h($text) {
        // Use ?? '' for null coalescing to ensure htmlspecialchars doesn't get null
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Authentication Check ---
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Define current page for sidebar activation (Assuming you'll rename the menu item in admin_sidebar.php)
$currentPage = 'resume_management.php';
$pageTitle = 'Manage Resumes & CVs';

// --- Initialization ---
$resumes = [];
$error = '';
$flash_message = $_SESSION['flash_message'] ?? null;
unset($_SESSION['flash_message']);

// Configuration for Uploads
$upload_dir = 'uploads/resumes/'; 
$max_file_size = 5 * 1024 * 1024; // 5 MB
$allowed_mimes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$allowed_extensions = ['pdf', 'doc', 'docx'];

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ===============================================
// FIX: Define BASE_URL for the document viewer
// This ensures the Google Viewer gets a publicly accessible URL.
// ===============================================
if (!defined('BASE_URL')) {
    // Get protocol (http or https)
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Get the base path of the application (e.g., /myproject if running in a subdirectory)
    // dirname(dirname($_SERVER['PHP_SELF'])) takes us from /admin/resume_management.php up to the root project folder path.
    $base_dir = dirname(dirname($_SERVER['PHP_SELF']));
    
    // Ensure the base directory is clean
    $base_dir = rtrim($base_dir, '/\\'); 
    
    define('BASE_URL', "{$protocol}://{$host}{$base_dir}"); 
}
// End of FIX

try {
    $conn = getDBConnection();

    // ===============================================
    // 1. HANDLE POST SUBMISSION (UPLOAD or DELETE)
    // ===============================================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        
        $action = $_POST['action'];
        $success = false;
        $message_content = '';
        $redirect = true; // Default to redirect
        
        // --- Handle UPLOAD ---
        if ($action === 'upload' && isset($_FILES['cv_file'])) {
            $file = $_FILES['cv_file'];

            // 1. Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $error_map = [
                    UPLOAD_ERR_INI_SIZE   => 'The file size exceeds the server limit.',
                    UPLOAD_ERR_FORM_SIZE  => 'The file size exceeds the form limit.',
                    UPLOAD_ERR_PARTIAL    => 'The file was only partially uploaded.',
                    UPLOAD_ERR_NO_FILE    => 'No file was uploaded. Please select a file.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
                ];
                $message_content = $error_map[$file['error']] ?? 'An unknown file upload error occurred.';
            } 
            // 2. Validate file type and size
            elseif ($file['size'] > $max_file_size) {
                $message_content = 'File size is too large (Max: 5MB).';
            } 
            // Check MIME type if function is available
            elseif (function_exists('mime_content_type') && !in_array(mime_content_type($file['tmp_name']), $allowed_mimes)) {
                 $message_content = 'Invalid file type. Only PDF, DOC, and DOCX files are allowed.';
            } 
            else {
                // 3. Generate safe filename
                $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Sanitize filename to prevent issues
                $safe_filename = preg_replace('/[^a-zA-Z0-9-]/', '_', $original_filename);
                $unique_filename = time() . '_' . $safe_filename . '.' . $file_ext;
                $target_path = $upload_dir . $unique_filename;

                // 4. Move file and record in database
                if (move_uploaded_file($file['tmp_name'], $target_path)) {
                    $stmt = $conn->prepare("INSERT INTO resumes (original_filename, filepath, upload_date) VALUES (:original_filename, :filepath, NOW())");
                    $success = $stmt->execute([
                        ':original_filename' => $file['name'],
                        ':filepath' => $target_path
                    ]);
                    $message_content = $success ? "File **{$file['name']}** uploaded successfully! 💾" : "File uploaded, but database record failed.";
                } else {
                    $message_content = 'Failed to move uploaded file to the target directory.';
                }
            }
        }
        
        // --- Handle DELETE ---
        elseif ($action === 'delete' && !empty($_POST['resume_id'])) {
            $resume_id = (int) $_POST['resume_id'];
            
            // 1. Get file path from DB
            $stmt = $conn->prepare("SELECT filepath FROM resumes WHERE resume_id = :id");
            $stmt->execute([':id' => $resume_id]);
            $resume = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($resume) {
                // 2. Delete file from file system
                if (file_exists($resume['filepath']) && unlink($resume['filepath'])) {
                    // 3. Delete database record
                    $stmt = $conn->prepare("DELETE FROM resumes WHERE resume_id = :id");
                    $success = $stmt->execute([':id' => $resume_id]);
                    $message_content = $success ? "Resume (ID: {$resume_id}) successfully **deleted**." : "File deleted, but database record removal failed.";
                } elseif (!file_exists($resume['filepath'])) {
                    // File already gone from disk, delete DB record
                    $stmt = $conn->prepare("DELETE FROM resumes WHERE resume_id = :id");
                    $success = $stmt->execute([':id' => $resume_id]);
                    $message_content = $success ? "Database record (ID: {$resume_id}) successfully deleted. File was already missing." : "Database record removal failed.";
                } else {
                    $success = false;
                    $message_content = "Failed to delete file from the server. Database untouched.";
                }
            } else {
                $success = false;
                $message_content = "Invalid resume ID for deletion or file not found in DB.";
            }
        }

        // Set flash message and redirect
        $flash_message_type = $success ? 'success' : 'danger';
        $_SESSION['flash_message'] = ['type' => $flash_message_type, 'content' => $message_content];

        if ($redirect) {
            header('Location: resume_management.php');
            exit;
        } else {
            // Set $error for immediate display if PRG wasn't followed due to a logic error
            $error = $message_content;
            $flash_message = null; 
        }
    }

    // ===============================================
    // 2. Data Fetching (Read)
    // ===============================================
    $stmt = $conn->query("SELECT * FROM resumes ORDER BY upload_date DESC");
    $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Resume Management error: " . $e->getMessage());
    $error = 'Database error: Could not load data. Please check server logs.';
}

// ===============================================
// PHP FUNCTION TO RENDER THE UPLOAD FORM
// ===============================================
function render_upload_form($max_file_size) {
    $max_size_mb = number_format($max_file_size / 1024 / 1024, 0);
    ?>
    <div class="card shadow-lg mb-4">
        <div class="card-header bg-dark text-white rounded-top-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cloud-arrow-up-fill me-2"></i>Upload New Resume/CV</h5>
        </div>
        <div class="card-body">
            <form id="uploadForm" method="POST" action="resume_management.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                
                <div class="mb-3">
                    <label for="cv_file" class="form-label fw-bold">Select File (PDF, DOC, DOCX) <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="cv_file" name="cv_file" required accept=".pdf,.doc,.docx">
                    <small class="form-text text-muted">Max file size: <?php echo h($max_size_mb); ?>MB</small>
                </div>
                
                <div class="d-grid gap-2 mt-4">
                    <button type="submit" class="btn btn-primary shadow-sm py-2">
                        <i class="bi bi-upload me-2"></i> Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
}

// ===============================================
// PHP FUNCTION TO RENDER THE VIEWER IFRAME (UPDATED FOR BASE_URL)
// ===============================================
function render_viewer_iframe($resumes, $upload_dir) {
    $view_id_from_url = (int)($_GET['view_id'] ?? 0);
    $resume_to_view = null;

    if ($view_id_from_url > 0) {
        foreach($resumes as $r) {
            if((int)$r['resume_id'] === $view_id_from_url) {
                $resume_to_view = $r;
                break;
            }
        }
    } elseif (!empty($resumes)) {
        $resume_to_view = $resumes[0];
    }
    
    $current_file_path = $resume_to_view ? $resume_to_view['filepath'] : '';
    $current_filename = $resume_to_view ? h($resume_to_view['original_filename']) : 'No file selected';
    $resume_id = $resume_to_view ? (int)$resume_to_view['resume_id'] : 0;
    
    $server_file_path = $current_file_path; 
    $viewer_url = '';
    
    if (!empty($server_file_path)) {
        // Construct the full public URL for the Google Docs Viewer
        $viewer_url = BASE_URL . '/' . $server_file_path;
    }

    // Check if the file actually exists on the server
    $file_exists = !empty($server_file_path) && file_exists($server_file_path);

    ?>
    <div class="card shadow-lg" style="height: 85vh;">
        <div class="card-header bg-secondary text-white rounded-top-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text-fill me-2"></i>File Viewer: <small id="viewer_title"><?php echo $current_filename; ?></small></h5>
        </div>
        <div class="card-body p-0 h-100">
            <?php if ($file_exists && $resume_id > 0): ?>
                <iframe id="resume_iframe" src="https://docs.google.com/gview?url=<?php echo urlencode($viewer_url); ?>&embedded=true" 
                        style="width:100%; height:100%;" frameborder="0">
                </iframe>
            <?php else: ?>
                <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                    <i class="bi bi-file-earmark-excel display-1"></i>
                    <p class="mt-3">
                        <?php 
                        if ($resume_id == 0 && empty($resumes)) {
                            echo "No resumes uploaded yet.";
                        } elseif ($resume_id > 0 && !$file_exists) {
                            echo "File not found on the server for resume ID: " . $resume_id . ". Please ensure the file is present at " . h($server_file_path);
                        } else {
                            echo "No file selected or uploaded yet.";
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>
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
    <title><?php echo h($pageTitle); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { padding-top: 1rem; }
        .content-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 1.5rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,.04);
        }
        .card { border: none; border-radius: 12px; box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08); }
        .table thead th { background-color: #f0f2f5; font-weight: 700; }
        /* Adjusted sidebar styles (copied from previous response for context) */
        .sidebar {
            position: fixed;
            top: 56px; 
            bottom: 0;
            left: 0;
            z-index: 1000;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link.active {
            color: #0d6efd;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto col-lg-10 main-content"> 
                
                <div class="content-header d-flex justify-content-between align-items-center">
                    <h1 class="h2 fw-bolder text-dark m-0"><i class="bi bi-file-earmark-person me-2"></i><?php echo h($pageTitle); ?></h1>
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
                        <?php render_upload_form($max_file_size); ?>

                        <div class="card mb-5 shadow-lg">
                            <div class="card-header bg-secondary text-white rounded-top-2">
                                <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Uploaded Resumes</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="max-height: 50vh; overflow-y: auto;">
                                    <table class="table table-striped table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th class="py-3">Filename</th>
                                                <th class="text-center py-3">Date</th>
                                                <th class="text-center py-3">View/Del</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($resumes)): ?>
                                                <tr>
                                                    <td colspan="3" class="text-center text-muted py-4">
                                                        <i class="bi bi-info-circle me-1"></i> No resumes uploaded.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($resumes as $r): ?>
                                                    <tr>
                                                        <td class="fw-semibold text-truncate" style="max-width: 150px;" title="<?php echo h($r['original_filename']); ?>">
                                                            <?php echo h($r['original_filename']); ?>
                                                        </td>
                                                        <td class="text-center small"><?php echo date('M j, Y', strtotime($r['upload_date'])); ?></td>
                                                        <td class="text-center">
                                                            <a href="?view_id=<?php echo $r['resume_id']; ?>" class="btn btn-sm btn-info text-white me-1" title="View in Iframe"> 
                                                                <i class="bi bi-eye"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger delete-btn"
                                                                data-id="<?php echo h($r['resume_id']); ?>"
                                                                data-title="<?php echo h($r['original_filename']); ?>"
                                                                title="Delete Resume">
                                                                <i class="bi bi-trash"></i>
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

                    <div class="col-lg-8 col-md-12">
                        <?php render_viewer_iframe($resumes, $upload_dir); ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <form id="deleteForm" method="POST" action="resume_management.php" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="resume_id" id="deleteFormId">
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
                        html: `Are you sure you want to permanently delete **${title}** (ID: ${id})? This will remove the file from the server.`,
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