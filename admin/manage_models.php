<?php
// =================================================================
// Admin Models Management - /admin/manage_models.php
// =================================================================
require_once '../config.php';
require_once 'includes/archive_functions.php';

$csrf_token = generateCSRFToken();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$models = [];
$error = '';
$success = '';
$conn = null;

try {
    $conn = getDBConnection();

    // Handle form submission (Add/Edit)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // CSRF Token Check
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid security token. Please try again.';
        } elseif (isset($_POST['action'])) {
            $action = $_POST['action'];

            // Input Sanitization and Validation
            $model_id = isset($_POST['model_id']) ? filter_var($_POST['model_id'], FILTER_VALIDATE_INT) : null;
            $category = trim($_POST['category']);
            $brand = trim($_POST['brand'] ?? '');
            $model_name = trim($_POST['model_name']);
            $event_name = trim($_POST['event_name'] ?? '');
            $class = trim($_POST['class'] ?? '');
            $agency = trim($_POST['agency'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $event_date = !empty($_POST['event_date']) ? $_POST['event_date'] : null;

            // Handle file upload
            $image_path = '';
            $upload_dir = '../images/models/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            if (isset($_FILES['model_image']) && $_FILES['model_image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['model_image']['tmp_name'];
                $file_name = $_FILES['model_image']['name'];
                $file_size = $_FILES['model_image']['size'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $max_file_size = 5 * 1024 * 1024; // 5MB

                if (!in_array($file_ext, $allowed_extensions)) {
                    $error = 'Invalid file type. Only JPG, JPEG, PNG, GIF, and WEBP are allowed.';
                } elseif ($file_size > $max_file_size) {
                    $error = 'File size too large. Maximum 5MB allowed.';
                } else {
                    // Generate unique filename
                    $new_filename = 'model_' . time() . '_' . uniqid() . '.' . $file_ext;
                    $target_file = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($file_tmp, $target_file)) {
                        $image_path = 'images/models/' . $new_filename;
                    } else {
                        $error = 'Failed to upload file. Please check directory permissions.';
                    }
                }
            } elseif ($action === 'edit') {
                // Keep existing image if no new file uploaded
                $image_path = trim($_POST['existing_image_path'] ?? '');
            }

            // Basic required fields check
            if (empty($category) || empty($model_name)) {
                $error = 'Category and Model Name are required fields.';
            }

            // Additional check for 'edit' action
            if ($action === 'edit' && !$model_id) {
                $error = 'Invalid model ID for editing.';
            }
            
            // If no error, proceed with DB operation
            if (!$error) {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO models (category, brand, model_name, event_name, class, agency, image_path, description, event_date, is_archived, created_by, created_date) VALUES (:category, :brand, :model_name, :event_name, :class, :agency, :image_path, :description, :event_date, 0, :user, NOW())");
                    $stmt->execute([
                        ':category' => $category,
                        ':brand' => $brand,
                        ':model_name' => $model_name,
                        ':event_name' => $event_name,
                        ':class' => $class,
                        ':agency' => $agency,
                        ':image_path' => $image_path,
                        ':description' => $description,
                        ':event_date' => $event_date,
                        ':user' => $_SESSION['user_id'] ?? 0
                    ]);
                    $success = 'Model added successfully! 🎉';
                } elseif ($action === 'edit') {
                    $stmt = $conn->prepare("UPDATE models SET category = :category, brand = :brand, model_name = :model_name, event_name = :event_name, class = :class, agency = :agency, image_path = :image_path, description = :description, event_date = :event_date WHERE model_id = :id");
                    $stmt->execute([
                        ':category' => $category,
                        ':brand' => $brand,
                        ':model_name' => $model_name,
                        ':event_name' => $event_name,
                        ':class' => $class,
                        ':agency' => $agency,
                        ':image_path' => $image_path,
                        ':description' => $description,
                        ':event_date' => $event_date,
                        ':id' => $model_id
                    ]);
                    $success = 'Model updated successfully! ✨';
                }
                // POST/REDIRECT/GET Pattern
                header('Location: manage_models.php?success=' . urlencode($success));
                exit;
            }
        }
    }

    // Handle archive
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $model_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($model_id !== false) {
            if (archiveRecord($conn, 'models', 'model_id', $model_id, $_SESSION['user_id'] ?? null)) {
                $success = 'Model moved to archive! 🗄️';
            } else {
                $error = 'Failed to archive model.';
            }
            header('Location: manage_models.php?success=' . urlencode($success));
            exit;
        } else {
            $error = 'Invalid model ID for archiving.';
        }
    }

    // Check for success messages after redirect
    if (isset($_GET['success'])) {
        $success = h($_GET['success']);
    }

    // Fetch only non-archived models
    $stmt = $conn->query("SELECT * FROM models WHERE is_archived = 0 ORDER BY created_date DESC");
    $models = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Models management error: " . $e->getMessage());
    $error = 'Database error occurred. Please check logs.';
} finally {
    if ($conn) {
        $conn = null;
    }
    $csrf_token = generateCSRFToken();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Models & Brand Ambassadors</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
    <style>
        .model-thumbnail {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .category-badge {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .badge-talent { background: linear-gradient(135deg, #ff6b9d, #c86dd7); }
        .badge-brand-ambassador { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .badge-usherette { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .badge-virtual-assistant { background: linear-gradient(135deg, #667eea, #764ba2); }
        .badge-other { background: linear-gradient(135deg, #a8edea, #fed6e3); color: #333; }

        /* Image Preview */
        .image-preview-container {
            position: relative;
            width: 150px;
            height: 200px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f9fa;
            margin-top: 10px;
        }

        .image-preview-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-preview-container.empty {
            color: #999;
            font-size: 0.9rem;
        }

        .remove-image-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(220, 53, 69, 0.9);
            border: none;
            border-radius: 50%;
            width: 28px;
            height: 28px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: white;
            z-index: 10;
            transition: background 0.2s;
        }

        .remove-image-btn:hover {
            background: rgba(220, 53, 69, 1);
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-name-display {
            display: inline-block;
            margin-left: 10px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .custom-file-upload {
            display: inline-block;
            padding: 8px 16px;
            background: #0d6efd;
            color: white;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .custom-file-upload:hover {
            background: #0b5ed7;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php $currentPage = 'manage_models.php'; ?>
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">
                            <i class="bi bi-people me-2" style="color: var(--jade-primary);"></i>Manage Models & Brand Ambassadors
                        </h1>
                        <p class="text-muted mb-0">Add and manage your portfolio showcase</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modelModal" onclick="resetForm()">
                        <i class="bi bi-plus-lg me-2"></i>Add New Model
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
                        <i class="bi bi-check-circle me-2"></i><?php echo h($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Stats Cards -->
                <div class="row g-3 mb-4">
                    <?php
                    $talent_count = count(array_filter($models, fn($m) => $m['category'] === 'talent'));
                    $ba_count = count(array_filter($models, fn($m) => $m['category'] === 'brand-ambassador'));
                    $usherette_count = count(array_filter($models, fn($m) => $m['category'] === 'usherette'));
                    $va_count = count(array_filter($models, fn($m) => $m['category'] === 'virtual-assistant'));
                    ?>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Total Models</h6>
                                        <h3 class="mb-0"><?php echo count($models); ?></h3>
                                    </div>
                                    <i class="bi bi-people-fill" style="font-size: 2rem; color: #cd919e; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Talents</h6>
                                        <h3 class="mb-0"><?php echo $talent_count; ?></h3>
                                    </div>
                                    <i class="bi bi-star-fill" style="font-size: 2rem; color: #ff6b9d; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Brand Ambassadors</h6>
                                        <h3 class="mb-0"><?php echo $ba_count; ?></h3>
                                    </div>
                                    <i class="bi bi-megaphone-fill" style="font-size: 2rem; color: #f5576c; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-muted mb-1">Usherettes</h6>
                                        <h3 class="mb-0"><?php echo $usherette_count; ?></h3>
                                    </div>
                                    <i class="bi bi-person-badge-fill" style="font-size: 2rem; color: #4facfe; opacity: 0.3;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 80px;">Image</th>
                                        <th>Model Name</th>
                                        <th>Category</th>
                                        <th>Brand / Event</th>
                                        <th>Class</th>
                                        <th>Agency</th>
                                        <th>Event Date</th>
                                        <th class="text-center" style="width: 150px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($models)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mb-0 mt-2">No models added yet. Click "Add New Model" to get started.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($models as $model): ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($model['image_path'])): ?>
                                                        <img src="../<?php echo h($model['image_path']); ?>" 
                                                             alt="<?php echo h($model['model_name']); ?>" 
                                                             class="model-thumbnail">
                                                    <?php else: ?>
                                                        <div class="model-thumbnail bg-light d-flex align-items-center justify-content-center">
                                                            <i class="bi bi-person text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo h($model['model_name']); ?></strong>
                                                    <?php if (!empty($model['description'])): ?>
                                                        <br><small class="text-muted"><?php echo h(substr($model['description'], 0, 50)); ?>...</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="category-badge badge-<?php echo h(str_replace(' ', '-', strtolower($model['category']))); ?> text-white">
                                                        <?php echo h(ucwords(str_replace('-', ' ', $model['category']))); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($model['brand'])): ?>
                                                        <strong><?php echo h($model['brand']); ?></strong><br>
                                                    <?php endif; ?>
                                                    <?php if (!empty($model['event_name'])): ?>
                                                        <small class="text-muted"><?php echo h($model['event_name']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted">N/A</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($model['class'])): ?>
                                                        <span class="badge bg-secondary">Class <?php echo h($model['class']); ?></span>
                                                    <?php else: ?>
                                                        <small class="text-muted">N/A</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo h($model['agency'] ?: 'N/A'); ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($model['event_date'])) {
                                                        echo date('M d, Y', strtotime($model['event_date']));
                                                    } else {
                                                        echo '<small class="text-muted">N/A</small>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button class="btn btn-sm btn-warning" 
                                                                onclick='editModel(<?php echo json_encode($model); ?>)' 
                                                                title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="?action=archive&id=<?php echo $model['model_id']; ?>" 
                                                           onclick="return confirm('Archive <?php echo h($model['model_name']); ?>?\n\nThis will remove them from the public showcase.');" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Archive Model">
                                                            <i class="bi bi-archive"></i>
                                                        </a>
                                                    </div>
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

    <!-- Add/Edit Model Modal -->
    <div class="modal fade" id="modelModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add New Model</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="model_id" id="model_id">
                        <input type="hidden" name="action" id="action" value="add">
                        <input type="hidden" name="existing_image_path" id="existing_image_path">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="model_name" class="form-label">Model Name *</label>
                                <input type="text" class="form-control" id="model_name" name="model_name" required 
                                       placeholder="e.g., Jane Doe">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="category" class="form-label">Category *</label>
                                <select class="form-select" id="category" name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="talent">Talent</option>
                                    <option value="brand-ambassador">Brand Ambassador</option>
                                    <option value="usherette">Usherette</option>
                                    <option value="virtual-assistant">Virtual Assistant</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="brand" class="form-label">Brand</label>
                                <input type="text" class="form-control" id="brand" name="brand" 
                                       placeholder="e.g., Coca-Cola">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="event_name" class="form-label">Event Name</label>
                                <input type="text" class="form-control" id="event_name" name="event_name" 
                                       placeholder="e.g., Summer Fashion Show 2025">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="class" class="form-label">Class</label>
                                <select class="form-select" id="class" name="class">
                                    <option value="">Select Class</option>
                                    <option value="A">Class A</option>
                                    <option value="B">Class B</option>
                                    <option value="C">Class C</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="agency" class="form-label">Agency</label>
                                <input type="text" class="form-control" id="agency" name="agency" 
                                       placeholder="e.g., Elite Model Management">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="event_date" class="form-label">Event Date</label>
                                <input type="date" class="form-control" id="event_date" name="event_date">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="model_image" class="form-label">Model Image (3:4 ratio recommended)</label>
                            <div class="file-input-wrapper">
                                <input type="file" class="form-control" id="model_image" name="model_image" 
                                       accept="image/jpeg,image/jpg,image/png,image/gif,image/webp" 
                                       onchange="previewImage(event)">
                                <label for="model_image" class="file-input-label">
                                    <i class="bi bi-upload me-2"></i>Choose Image
                                </label>
                                <span class="file-name-display" id="file_name_display">No file chosen</span>
                            </div>
                            <small class="text-muted d-block mt-1">
                                <i class="bi bi-info-circle me-1"></i>
                                Accepted formats: JPG, PNG, GIF, WEBP (Max 5MB). Ideal ratio: 3:4 (e.g., 600x800px)
                            </small>
                            
                            <!-- Image Preview -->
                            <div class="image-preview-container empty mt-3" id="image_preview_container">
                                <div id="preview_placeholder">
                                    <i class="bi bi-image" style="font-size: 2rem;"></i>
                                    <div>Image Preview</div>
                                </div>
                                <img id="image_preview" style="display: none;">
                                <button type="button" class="remove-image-btn" id="remove_image_btn" 
                                        style="display: none;" onclick="removeImage()">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control" id="description" name="description" rows="3" 
                                      placeholder="Brief description about this model or collaboration..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Model</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Add New Model';
            document.getElementById('action').value = 'add';
            document.getElementById('model_id').value = '';
            document.getElementById('model_name').value = '';
            document.getElementById('category').value = '';
            document.getElementById('brand').value = '';
            document.getElementById('event_name').value = '';
            document.getElementById('class').value = '';
            document.getElementById('agency').value = '';
            document.getElementById('event_date').value = '';
            document.getElementById('model_image').value = '';
            document.getElementById('existing_image_path').value = '';
            document.getElementById('description').value = '';
            document.getElementById('submitBtn').textContent = 'Add Model';
            document.getElementById('file_name_display').textContent = 'No file chosen';
            
            // Reset preview
            const preview = document.getElementById('image_preview');
            const placeholder = document.getElementById('preview_placeholder');
            const container = document.getElementById('image_preview_container');
            const removeBtn = document.getElementById('remove_image_btn');
            
            preview.style.display = 'none';
            preview.src = '';
            placeholder.style.display = 'block';
            container.classList.add('empty');
            removeBtn.style.display = 'none';
        }

        function previewImage(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('image_preview');
            const placeholder = document.getElementById('preview_placeholder');
            const container = document.getElementById('image_preview_container');
            const removeBtn = document.getElementById('remove_image_btn');
            const fileNameDisplay = document.getElementById('file_name_display');
            
            if (file) {
                // Display file name
                fileNameDisplay.textContent = file.name;
                
                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size exceeds 5MB. Please choose a smaller file.');
                    event.target.value = '';
                    fileNameDisplay.textContent = 'No file chosen';
                    return;
                }
                
                // Check file type
                const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Invalid file type. Please choose JPG, PNG, GIF, or WEBP.');
                    event.target.value = '';
                    fileNameDisplay.textContent = 'No file chosen';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    placeholder.style.display = 'none';
                    container.classList.remove('empty');
                    removeBtn.style.display = 'flex';
                }
                reader.readAsDataURL(file);
            }
        }

        function removeImage() {
            const fileInput = document.getElementById('model_image');
            const preview = document.getElementById('image_preview');
            const placeholder = document.getElementById('preview_placeholder');
            const container = document.getElementById('image_preview_container');
            const removeBtn = document.getElementById('remove_image_btn');
            const fileNameDisplay = document.getElementById('file_name_display');
            
            fileInput.value = '';
            preview.style.display = 'none';
            preview.src = '';
            placeholder.style.display = 'block';
            container.classList.add('empty');
            removeBtn.style.display = 'none';
            fileNameDisplay.textContent = 'No file chosen';
            
            // Clear existing image path when removing in edit mode
            document.getElementById('existing_image_path').value = '';
        }

        function editModel(model) {
            document.getElementById('modalTitle').textContent = 'Edit Model';
            document.getElementById('action').value = 'edit';
            document.getElementById('model_id').value = model.model_id;
            document.getElementById('model_name').value = model.model_name;
            document.getElementById('category').value = model.category || '';
            document.getElementById('brand').value = model.brand || '';
            document.getElementById('event_name').value = model.event_name || '';
            document.getElementById('class').value = model.class || '';
            document.getElementById('agency').value = model.agency || '';
            document.getElementById('event_date').value = model.event_date || '';
            document.getElementById('existing_image_path').value = model.image_path || '';
            document.getElementById('description').value = model.description || '';
            document.getElementById('submitBtn').textContent = 'Save Changes';
            
            // Show existing image preview
            const preview = document.getElementById('image_preview');
            const placeholder = document.getElementById('preview_placeholder');
            const container = document.getElementById('image_preview_container');
            const removeBtn = document.getElementById('remove_image_btn');
            
            if (model.image_path) {
                preview.src = '../' + model.image_path;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                container.classList.remove('empty');
                removeBtn.style.display = 'flex';
                document.getElementById('file_name_display').textContent = 'Current image: ' + model.image_path.split('/').pop();
            } else {
                preview.style.display = 'none';
                placeholder.style.display = 'block';
                container.classList.add('empty');
                removeBtn.style.display = 'none';
                document.getElementById('file_name_display').textContent = 'No file chosen';
            }
            
            var modal = new bootstrap.Modal(document.getElementById('modelModal'));
            modal.show();
        }
    </script>
</body>
</html>