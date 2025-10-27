<?php
/**
 * Resume Management - Manage CV/Resume uploads with Featured & Password Controls
 * Filename: admin/resume_management.php
 */

require_once '../config.php';

if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$currentPage = 'resume_management.php';
$error = '';
$success = '';

// Upload Configuration
$upload_dir = '../uploads/resumes/';
$max_file_size = 5 * 1024 * 1024; // 5 MB
$allowed_extensions = ['pdf', 'doc', 'docx'];

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Define BASE_URL for viewer
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $base_dir = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\');
    define('BASE_URL', "{$protocol}://{$host}{$base_dir}");
}

$resumes = [];
$conn = null;

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Check if new columns exist, if not create them
    try {
        $conn->query("SELECT is_featured FROM resumes LIMIT 1");
    } catch (PDOException $e) {
        // Columns don't exist, create them
        $conn->exec("ALTER TABLE resumes ADD COLUMN is_featured TINYINT(1) DEFAULT 0 AFTER uploaded_by");
        $conn->exec("ALTER TABLE resumes ADD COLUMN cv_type VARCHAR(50) DEFAULT NULL AFTER is_featured");
        $conn->exec("ALTER TABLE resumes ADD COLUMN display_order INT DEFAULT 0 AFTER cv_type");
    }
    
    // Check if cv_passwords table exists, if not create it
    try {
        $conn->query("SELECT 1 FROM cv_passwords LIMIT 1");
    } catch (PDOException $e) {
        // Table doesn't exist, create it
        $conn->exec("
            CREATE TABLE IF NOT EXISTS cv_passwords (
                id INT PRIMARY KEY AUTO_INCREMENT,
                resume_id INT NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (resume_id) REFERENCES resumes(resume_id) ON DELETE CASCADE,
                UNIQUE KEY unique_resume (resume_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Handle Upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
        if (isset($_FILES['cv_file']) && $_FILES['cv_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['cv_file'];
            
            // Validate file size
            if ($file['size'] > $max_file_size) {
                $error = 'File size exceeds 5MB limit.';
            } else {
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                
                // Validate extension
                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = 'Invalid file type. Only PDF, DOC, and DOCX are allowed.';
                } else {
                    // Generate safe filename
                    $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
                    $safe_filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $original_filename);
                    $unique_filename = time() . '_' . $safe_filename . '.' . $file_ext;
                    $target_path = $upload_dir . $unique_filename;
                    
                    // Move file
                    if (move_uploaded_file($file['tmp_name'], $target_path)) {
                        // Save to database
                        $stmt = $conn->prepare("INSERT INTO resumes (original_filename, filepath, file_size, upload_date, uploaded_by) VALUES (:original, :filepath, :size, NOW(), :user)");
                        if ($stmt->execute([
                            ':original' => $file['name'],
                            ':filepath' => 'uploads/resumes/' . $unique_filename,
                            ':size' => $file['size'],
                            ':user' => $_SESSION['user_id'] ?? null
                        ])) {
                            $success = "Resume '{$file['name']}' uploaded successfully! 💾";
                            header('Location: resume_management.php?success=' . urlencode($success));
                            exit;
                        } else {
                            $error = 'File uploaded but database save failed.';
                            @unlink($target_path); // Clean up file
                        }
                    } else {
                        $error = 'Failed to move uploaded file.';
                    }
                }
            }
        } else {
            $error = 'No file uploaded or upload error occurred.';
        }
    }

    // Handle Delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
        $resume_id = (int)$_POST['resume_id'];
        
        // Get file info
        $stmt = $conn->prepare("SELECT filepath, original_filename FROM resumes WHERE resume_id = :id");
        $stmt->execute([':id' => $resume_id]);
        $resume = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($resume) {
            $file_path = '../' . $resume['filepath'];
            
            // Delete file if exists
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            
            // Delete from database (will cascade to cv_passwords)
            $stmt = $conn->prepare("DELETE FROM resumes WHERE resume_id = :id");
            if ($stmt->execute([':id' => $resume_id])) {
                $success = "Resume '{$resume['original_filename']}' deleted successfully!";
                header('Location: resume_management.php?success=' . urlencode($success));
                exit;
            } else {
                $error = 'Failed to delete resume from database.';
            }
        } else {
            $error = 'Resume not found.';
        }
    }

    // Handle Set Featured
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_featured') {
        $resume_id = (int)$_POST['resume_id'];
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $cv_type = trim($_POST['cv_type'] ?? '');
        $display_order = (int)($_POST['display_order'] ?? 0);
        
        $stmt = $conn->prepare("
            UPDATE resumes 
            SET is_featured = :featured, 
                cv_type = :cv_type,
                display_order = :display_order
            WHERE resume_id = :id
        ");
        
        if ($stmt->execute([
            ':featured' => $is_featured,
            ':cv_type' => $cv_type,
            ':display_order' => $display_order,
            ':id' => $resume_id
        ])) {
            $success = $is_featured ? "CV marked as featured! ⭐" : "CV unmarked as featured.";
            header('Location: resume_management.php?success=' . urlencode($success));
            exit;
        } else {
            $error = 'Failed to update featured status.';
        }
    }

    // Handle Set Password
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'set_password') {
        $resume_id = (int)$_POST['resume_id'];
        $password = $_POST['password'] ?? '';
        
        if (empty($password)) {
            // Remove password
            $stmt = $conn->prepare("DELETE FROM cv_passwords WHERE resume_id = :id");
            if ($stmt->execute([':id' => $resume_id])) {
                $success = "Password removed successfully! 🔓";
                header('Location: resume_management.php?success=' . urlencode($success));
                exit;
            }
        } else {
            // Set or update password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Check if password already exists
            $check_stmt = $conn->prepare("SELECT id FROM cv_passwords WHERE resume_id = :id");
            $check_stmt->execute([':id' => $resume_id]);
            $exists = $check_stmt->fetch();
            
            if ($exists) {
                // Update existing password
                $stmt = $conn->prepare("UPDATE cv_passwords SET password_hash = :hash WHERE resume_id = :id");
                $success_msg = "Password updated successfully! 🔐";
            } else {
                // Insert new password
                $stmt = $conn->prepare("INSERT INTO cv_passwords (resume_id, password_hash) VALUES (:id, :hash)");
                $success_msg = "Password set successfully! 🔐";
            }
            
            if ($stmt->execute([':id' => $resume_id, ':hash' => $password_hash])) {
                $success = $success_msg;
                header('Location: resume_management.php?success=' . urlencode($success));
                exit;
            } else {
                $error = 'Failed to set password.';
            }
        }
    }

    // Get success message from redirect
    if (isset($_GET['success'])) {
        $success = h($_GET['success']);
    }

    // Fetch all resumes with password info
    $stmt = $conn->query("
        SELECT r.*, u.username, cp.id as has_password
        FROM resumes r 
        LEFT JOIN users u ON r.uploaded_by = u.user_id 
        LEFT JOIN cv_passwords cp ON r.resume_id = cp.resume_id
        ORDER BY r.is_featured DESC, r.display_order ASC, r.upload_date DESC
    ");
    $resumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get resume to view
    $view_id = isset($_GET['view']) ? (int)$_GET['view'] : 0;
    $current_resume = null;
    
    if ($view_id > 0) {
        foreach ($resumes as $r) {
            if ((int)$r['resume_id'] === $view_id) {
                $current_resume = $r;
                break;
            }
        }
    } elseif (!empty($resumes)) {
        $current_resume = $resumes[0];
    }

} catch (PDOException $e) {
    error_log("Resume Management error: " . $e->getMessage());
    $error = 'Database error occurred: ' . $e->getMessage();
}

// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' bytes';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Resumes - Jade Salvador Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
        .resume-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }
        
        .resume-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--jade-primary);
        }
        
        .resume-card.active {
            border-color: var(--jade-primary);
            background: linear-gradient(135deg, rgba(205, 145, 158, 0.05), rgba(118, 75, 162, 0.05));
        }
        
        .resume-card .badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        .upload-zone {
            border: 2px dashed var(--border-color);
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            background: var(--bg-secondary);
            transition: all 0.3s ease;
        }
        
        .upload-zone:hover {
            border-color: var(--jade-primary);
            background: linear-gradient(135deg, rgba(205, 145, 158, 0.05), rgba(118, 75, 162, 0.05));
        }
        
        .viewer-container {
            height: calc(100vh - 200px);
            min-height: 600px;
        }
        
        .file-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            font-size: 1.25rem;
        }
        
        .resume-list {
            max-height: calc(100vh - 450px);
            overflow-y: auto;
        }
        
        .btn-group-vertical .btn {
            border-radius: 0;
        }
        
        .btn-group-vertical .btn:first-child {
            border-top-left-radius: 0.25rem;
            border-top-right-radius: 0.25rem;
        }
        
        .btn-group-vertical .btn:last-child {
            border-bottom-left-radius: 0.25rem;
            border-bottom-right-radius: 0.25rem;
        }
        
        .manage-cv-btn:hover {
            background-color: var(--jade-primary);
            color: white;
            border-color: var(--jade-primary);
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
                            <i class="bi bi-file-earmark-person me-2" style="color: var(--jade-primary);"></i>Resume Management
                        </h1>
                        <p class="text-muted mb-0">Upload and manage CV/Resume files</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                        <i class="bi bi-cloud-upload me-2"></i>Upload Resume
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-circle me-2"></i><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Resume List -->
                    <div class="col-lg-4">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-list-ul me-2"></i>Uploaded Resumes
                                </h5>
                                <span class="badge bg-primary"><?php echo count($resumes); ?></span>
                            </div>
                            <div class="card-body p-0">
                                <div class="resume-list">
                                    <?php if (empty($resumes)): ?>
                                        <div class="text-center py-5 text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="mb-0 mt-2">No resumes uploaded yet</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($resumes as $r): ?>
                                                <a href="?view=<?php echo $r['resume_id']; ?>" 
                                                   class="list-group-item list-group-item-action resume-card <?php echo $current_resume && $current_resume['resume_id'] == $r['resume_id'] ? 'active' : ''; ?>">
                                                    <div class="d-flex align-items-start">
                                                        <div class="file-icon bg-primary text-white me-3">
                                                            <i class="bi bi-file-earmark-pdf"></i>
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <!-- Featured Badge -->
                                                            <?php if ($r['is_featured']): ?>
                                                                <span class="badge bg-success mb-1">
                                                                    <i class="bi bi-star-fill"></i> Featured
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($r['has_password']): ?>
                                                                <span class="badge bg-warning text-dark mb-1">
                                                                    <i class="bi bi-lock-fill"></i> Protected
                                                                </span>
                                                            <?php endif; ?>
                                                            
                                                            <h6 class="mb-1 text-truncate" style="max-width: 200px;" title="<?php echo h($r['original_filename']); ?>">
                                                                <?php echo h($r['original_filename']); ?>
                                                            </h6>
                                                            
                                                            <?php if ($r['cv_type']): ?>
                                                                <small class="text-primary d-block mb-1">
                                                                    <i class="bi bi-tag"></i> <?php echo h($r['cv_type']); ?>
                                                                </small>
                                                            <?php endif; ?>
                                                            
                                                            <small class="text-muted">
                                                                <i class="bi bi-calendar me-1"></i><?php echo date('M d, Y', strtotime($r['upload_date'])); ?>
                                                                <br>
                                                                <i class="bi bi-hdd me-1"></i><?php echo formatFileSize($r['file_size']); ?>
                                                                <?php if ($r['username']): ?>
                                                                    <br><i class="bi bi-person me-1"></i><?php echo h($r['username']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        
                                                        <!-- Action Buttons -->
                                                        <div class="btn-group-vertical ms-2">
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-outline-primary manage-cv-btn"
                                                                    data-id="<?php echo $r['resume_id']; ?>"
                                                                    data-featured="<?php echo $r['is_featured']; ?>"
                                                                    data-cv-type="<?php echo h($r['cv_type'] ?? ''); ?>"
                                                                    data-display-order="<?php echo $r['display_order']; ?>"
                                                                    data-has-password="<?php echo $r['has_password'] ? '1' : '0'; ?>"
                                                                    onclick="event.preventDefault();"
                                                                    title="Manage CV">
                                                                <i class="bi bi-gear"></i>
                                                            </button>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger delete-btn"
                                                                    data-id="<?php echo $r['resume_id']; ?>"
                                                                    data-name="<?php echo h($r['original_filename']); ?>"
                                                                    onclick="event.preventDefault();"
                                                                    title="Delete">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- File Viewer -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bi bi-eye me-2"></i>File Preview
                                </h5>
                                <?php if ($current_resume): ?>
                                    <div class="btn-group">
                                        <a href="<?php echo BASE_URL . '/' . $current_resume['filepath']; ?>" 
                                           target="_blank" 
                                           class="btn btn-sm btn-primary">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-0">
                                <div class="viewer-container">
                                    <?php if ($current_resume): ?>
                                        <?php
                                        $viewer_url = BASE_URL . '/' . $current_resume['filepath'];
                                        $file_exists = file_exists('../' . $current_resume['filepath']);
                                        $file_ext = strtolower(pathinfo($current_resume['filepath'], PATHINFO_EXTENSION));
                                        ?>
                                        <?php if ($file_exists): ?>
                                            <?php if ($file_ext === 'pdf'): ?>
                                                <iframe src="<?php echo $viewer_url; ?>" 
                                                        style="width:100%; height:100%;" 
                                                        frameborder="0"
                                                        type="application/pdf">
                                                </iframe>
                                            <?php else: ?>
                                                <!-- For non-PDF files, use Google Docs viewer as fallback -->
                                                <iframe src="https://docs.google.com/gview?url=<?php echo urlencode($viewer_url); ?>&embedded=true" 
                                                        style="width:100%; height:100%;" 
                                                        frameborder="0">
                                                </iframe>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted p-4">
                                                <i class="bi bi-exclamation-triangle display-1 text-warning"></i>
                                                <h4 class="mt-3">File Not Found</h4>
                                                <p class="text-center">The file is registered in the database but missing from the server.</p>
                                                <code class="bg-light p-2 rounded"><?php echo h($current_resume['filepath']); ?></code>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="d-flex flex-column justify-content-center align-items-center h-100 text-muted">
                                            <i class="bi bi-file-earmark display-1"></i>
                                            <p class="mt-3">Select a resume to preview</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-cloud-upload me-2"></i>Upload Resume/CV
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="upload-zone mb-3">
                            <i class="bi bi-cloud-arrow-up display-4 text-muted"></i>
                            <p class="mt-3 mb-2">Click to select or drag and drop</p>
                            <small class="text-muted">PDF, DOC, DOCX (Max 5MB)</small>
                        </div>
                        <input type="file" 
                               class="form-control" 
                               name="cv_file" 
                               accept=".pdf,.doc,.docx" 
                               required>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-upload me-2"></i>Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage CV Modal -->
    <div class="modal fade" id="manageCvModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-gear me-2"></i>Manage CV
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Featured Settings -->
                    <form method="POST" class="mb-4 pb-4 border-bottom">
                        <input type="hidden" name="action" value="set_featured">
                        <input type="hidden" name="resume_id" id="featuredResumeId">
                        
                        <h6 class="mb-3">
                            <i class="bi bi-star me-2"></i>Featured on Homepage
                        </h6>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="isFeatured" 
                                       name="is_featured" 
                                       value="1">
                                <label class="form-check-label" for="isFeatured">
                                    Display on homepage CV section
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cvType" class="form-label">CV Type/Title</label>
                            <input type="text" 
                                   class="form-control" 
                                   id="cvType" 
                                   name="cv_type" 
                                   placeholder="e.g., Executive Virtual Assistant">
                            <small class="text-muted">This will be displayed on the homepage</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="displayOrder" class="form-label">Display Order</label>
                            <input type="number" 
                                   class="form-control" 
                                   id="displayOrder" 
                                   name="display_order" 
                                   min="0" 
                                   value="0">
                            <small class="text-muted">Lower numbers appear first (0 = highest priority)</small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-2"></i>Save Featured Settings
                        </button>
                    </form>
                    
                    <!-- Password Settings -->
                    <form method="POST">
                        <input type="hidden" name="action" value="set_password">
                        <input type="hidden" name="resume_id" id="passwordResumeId">
                        
                        <h6 class="mb-3">
                            <i class="bi bi-lock me-2"></i>Download Password
                        </h6>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <small>Set a password to protect this CV from unauthorized downloads.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="cvPassword" class="form-label">
                                Password
                                <span id="currentPasswordStatus" class="badge bg-secondary ms-2"></span>
                            </label>
                            <input type="password" 
                                   class="form-control" 
                                   id="cvPassword" 
                                   name="password" 
                                   placeholder="Enter new password or leave blank to remove">
                            <small class="text-muted">Leave blank to remove password protection</small>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       id="showPassword">
                                <label class="form-check-label" for="showPassword">
                                    Show password
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-shield-lock me-2"></i>Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Form (Hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="resume_id" id="deleteId">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-theme.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const manageCvModal = new bootstrap.Modal(document.getElementById('manageCvModal'));
            
            // Handle manage CV button
            document.querySelectorAll('.manage-cv-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    const id = this.dataset.id;
                    const featured = this.dataset.featured === '1';
                    const cvType = this.dataset.cvType;
                    const displayOrder = this.dataset.displayOrder || '0';
                    const hasPassword = this.dataset.hasPassword === '1';
                    
                    // Set featured form values
                    document.getElementById('featuredResumeId').value = id;
                    document.getElementById('isFeatured').checked = featured;
                    document.getElementById('cvType').value = cvType;
                    document.getElementById('displayOrder').value = displayOrder;
                    
                    // Set password form values
                    document.getElementById('passwordResumeId').value = id;
                    document.getElementById('cvPassword').value = '';
                    
                    // Update password status badge
                    const statusBadge = document.getElementById('currentPasswordStatus');
                    if (hasPassword) {
                        statusBadge.textContent = 'Protected';
                        statusBadge.className = 'badge bg-warning text-dark ms-2';
                    } else {
                        statusBadge.textContent = 'No Password';
                        statusBadge.className = 'badge bg-secondary ms-2';
                    }
                    
                    manageCvModal.show();
                });
            });
            
            // Show/hide password toggle
            document.getElementById('showPassword').addEventListener('change', function() {
                const passwordInput = document.getElementById('cvPassword');
                passwordInput.type = this.checked ? 'text' : 'password';
            });
            
            // Handle delete buttons
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    
                    if (confirm(`Delete resume "${name}"?\n\nThis will permanently remove the file from the server.`)) {
                        document.getElementById('deleteId').value = id;
                        document.getElementById('deleteForm').submit();
                    }
                });
            });
        });
    </script>
</body>
</html>