<?php
/**
 * Archives Page - View and manage all archived items
 * Filename: admin/archives.php
 */

require_once '../config.php';
require_once 'includes/archive_functions.php'; // Make sure this file exists

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

$currentPage = 'archives.php';
$error = '';
$success = '';
$activeTab = $_GET['tab'] ?? 'all';

try {
    $conn = getDBConnection();

    // Handle restore action
    if (isset($_POST['action']) && $_POST['action'] === 'restore') {
        $table = $_POST['table'];
        $id = (int)$_POST['id'];
        $id_column = $_POST['id_column'];
        
        if (restoreRecord($conn, $table, $id_column, $id)) {
            $success = "Item successfully restored from archive!";
        } else {
            $error = "Failed to restore item.";
        }
    }

    // Handle permanent delete action
    if (isset($_POST['action']) && $_POST['action'] === 'permanent_delete') {
        $table = $_POST['table'];
        $id = (int)$_POST['id'];
        $id_column = $_POST['id_column'];
        
        if (permanentlyDeleteRecord($conn, $table, $id_column, $id)) {
            $success = "Item permanently deleted.";
        } else {
            $error = "Failed to delete item.";
        }
    }

    // Get archive counts
    $archiveCounts = [
        'portraits' => getArchivedCount($conn, 'portraits'),
        'videos' => getArchivedCount($conn, 'videos'),
        'partners' => getArchivedCount($conn, 'partners'),
        'testimonials' => getArchivedCount($conn, 'testimonials'),
        'experiences' => getArchivedCount($conn, 'experiences'),
        'inquiries' => getArchivedCount($conn, 'inquiries')
    ];

    $totalArchived = array_sum($archiveCounts);

    // Fetch archived items based on active tab
    $archivedItems = [];
    if ($activeTab !== 'all') {
        $archivedItems = getArchivedItems($conn, $activeTab, 100);
    }

} catch (PDOException $e) {
    error_log("Archives error: " . $e->getMessage());
    $error = 'Database error occurred.';
}

// Table configurations
$tableConfigs = [
    'portraits' => [
        'name' => 'Portraits',
        'id_column' => 'portrait_id',
        'icon' => 'bi-images',
        'columns' => ['portrait_id' => 'ID', 'title' => 'Title', 'image_filename' => 'File', 'archived_at' => 'Archived']
    ],
    'videos' => [
        'name' => 'Videos',
        'id_column' => 'video_id',
        'icon' => 'bi-play-circle',
        'columns' => ['video_id' => 'ID', 'title' => 'Title', 'archived_at' => 'Archived']
    ],
    'partners' => [
        'name' => 'Partners',
        'id_column' => 'partner_id',
        'icon' => 'bi-building',
        'columns' => ['partner_id' => 'ID', 'name' => 'Name', 'archived_at' => 'Archived']
    ],
    'testimonials' => [
        'name' => 'Testimonials',
        'id_column' => 'testimonial_id',
        'icon' => 'bi-chat-quote',
        'columns' => ['testimonial_id' => 'ID', 'client_name' => 'Client', 'archived_at' => 'Archived']
    ],
    'experiences' => [
        'name' => 'Experiences',
        'id_column' => 'exp_id',
        'icon' => 'bi-briefcase',
        'columns' => ['exp_id' => 'ID', 'title' => 'Title', 'archived_at' => 'Archived']
    ],
    'inquiries' => [
        'name' => 'Inquiries',
        'id_column' => 'inquiry_id',
        'icon' => 'bi-envelope',
        'columns' => ['inquiry_id' => 'ID', 'full_name' => 'Name', 'email' => 'Email', 'archived_at' => 'Archived']
    ]
];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives - Jade Salvador Admin</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
        .archive-stat-card {
            border-radius: 12px;
            padding: 1.5rem;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .archive-stat-card:hover {
            border-color: var(--jade-primary);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .archive-stat-card.active {
            border-color: var(--jade-primary);
            background: linear-gradient(135deg, rgba(205, 145, 158, 0.1), rgba(118, 75, 162, 0.1));
        }

        .archive-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: var(--jade-primary);
            color: white;
            margin-bottom: 1rem;
        }

        .archive-count {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .archive-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .empty-archive {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-muted);
        }

        .empty-archive i {
            font-size: 4rem;
            opacity: 0.3;
            margin-bottom: 1rem;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .thumbnail-preview {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
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
                            <i class="bi bi-archive me-2" style="color: var(--jade-primary);"></i>Archives
                        </h1>
                        <p class="text-muted mb-0">View and manage archived items</p>
                    </div>
                    <div class="badge bg-secondary" style="font-size: 1rem; padding: 0.75rem 1.5rem;">
                        <?php echo $totalArchived; ?> Total Archived Items
                    </div>
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

                <!-- Archive Categories -->
                <div class="row g-3 mb-4">
                    <?php foreach ($tableConfigs as $table => $config): ?>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="?tab=<?php echo $table; ?>" class="text-decoration-none">
                                <div class="archive-stat-card <?php echo $activeTab === $table ? 'active' : ''; ?>">
                                    <div class="archive-icon">
                                        <i class="bi <?php echo $config['icon']; ?>"></i>
                                    </div>
                                    <div class="archive-count"><?php echo $archiveCounts[$table]; ?></div>
                                    <div class="archive-label"><?php echo $config['name']; ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Archived Items Table -->
                <?php if ($activeTab !== 'all'): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi <?php echo $tableConfigs[$activeTab]['icon']; ?> me-2"></i>
                                Archived <?php echo $tableConfigs[$activeTab]['name']; ?>
                            </h5>
                            <a href="?tab=all" class="btn btn-sm btn-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Overview
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($archivedItems)): ?>
                                <div class="empty-archive">
                                    <i class="bi bi-inbox"></i>
                                    <h4>No Archived Items</h4>
                                    <p>There are no archived <?php echo strtolower($tableConfigs[$activeTab]['name']); ?> at this time.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <?php foreach ($tableConfigs[$activeTab]['columns'] as $col => $label): ?>
                                                    <th><?php echo $label; ?></th>
                                                <?php endforeach; ?>
                                                <th class="text-center" style="width: 200px;">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($archivedItems as $item): ?>
                                                <tr>
                                                    <?php foreach ($tableConfigs[$activeTab]['columns'] as $col => $label): ?>
                                                        <td>
                                                            <?php 
                                                            if ($col === 'image_filename' || $col === 'logo_image_file') {
                                                                $imgPath = '../' . ($item[$col] ?? '');
                                                                if (!empty($item[$col]) && file_exists($imgPath)) {
                                                                    echo '<img src="' . h($imgPath) . '" class="thumbnail-preview" alt="Preview">';
                                                                } else {
                                                                    echo '<span class="text-muted">No image</span>';
                                                                }
                                                            } elseif ($col === 'archived_at') {
                                                                echo date('M d, Y H:i', strtotime($item[$col]));
                                                            } else {
                                                                echo h($item[$col] ?? '-');
                                                            }
                                                            ?>
                                                        </td>
                                                    <?php endforeach; ?>
                                                    <td>
                                                        <div class="action-buttons justify-content-center">
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="restore">
                                                                <input type="hidden" name="table" value="<?php echo $activeTab; ?>">
                                                                <input type="hidden" name="id" value="<?php echo $item[$tableConfigs[$activeTab]['id_column']]; ?>">
                                                                <input type="hidden" name="id_column" value="<?php echo $tableConfigs[$activeTab]['id_column']; ?>">
                                                                <button type="submit" class="btn btn-sm btn-success" title="Restore">
                                                                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                                                                </button>
                                                            </form>
                                                            <button type="button" 
                                                                    class="btn btn-sm btn-danger delete-permanent-btn"
                                                                    data-table="<?php echo $activeTab; ?>"
                                                                    data-id="<?php echo $item[$tableConfigs[$activeTab]['id_column']]; ?>"
                                                                    data-id-column="<?php echo $tableConfigs[$activeTab]['id_column']; ?>"
                                                                    title="Delete Permanently">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Overview Stats -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-bar-chart me-2"></i>Archive Statistics
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if ($totalArchived > 0): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-3">Archived Items by Category</h6>
                                        <div class="list-group">
                                            <?php foreach ($tableConfigs as $table => $config): ?>
                                                <?php if ($archiveCounts[$table] > 0): ?>
                                                    <a href="?tab=<?php echo $table; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <i class="bi <?php echo $config['icon']; ?> me-2"></i>
                                                            <?php echo $config['name']; ?>
                                                        </div>
                                                        <span class="badge bg-primary rounded-pill"><?php echo $archiveCounts[$table]; ?></span>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 class="text-muted mb-3">Quick Info</h6>
                                        <div class="alert alert-info">
                                            <i class="bi bi-info-circle me-2"></i>
                                            <strong>About Archives:</strong>
                                            <ul class="mb-0 mt-2">
                                                <li>Archived items are hidden from public view</li>
                                                <li>You can restore items at any time</li>
                                                <li>Permanent deletion cannot be undone</li>
                                                <li>Files are preserved when archived</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="empty-archive">
                                    <i class="bi bi-inbox"></i>
                                    <h4>No Archived Items</h4>
                                    <p>You haven't archived any items yet. When you archive content, it will appear here.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>

    <!-- Permanent Delete Confirmation Modal -->
    <div class="modal fade" id="permanentDeleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Permanent Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong>⚠️ Warning: This action cannot be undone!</strong></p>
                    <p>Are you absolutely sure you want to permanently delete this item? All associated data will be lost forever.</p>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" id="confirmPermanentDelete">
                        <label class="form-check-label" for="confirmPermanentDelete">
                            I understand this action is permanent and cannot be reversed
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" id="permanentDeleteForm">
                        <input type="hidden" name="action" value="permanent_delete">
                        <input type="hidden" name="table" id="deleteTable">
                        <input type="hidden" name="id" id="deleteId">
                        <input type="hidden" name="id_column" id="deleteIdColumn">
                        <button type="submit" class="btn btn-danger" id="confirmDeleteBtn" disabled>
                            <i class="bi bi-trash me-2"></i>Permanently Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/admin-theme.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteModal = new bootstrap.Modal(document.getElementById('permanentDeleteModal'));
            const confirmCheckbox = document.getElementById('confirmPermanentDelete');
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            
            // Handle permanent delete button clicks
            document.querySelectorAll('.delete-permanent-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const table = this.getAttribute('data-table');
                    const id = this.getAttribute('data-id');
                    const idColumn = this.getAttribute('data-id-column');
                    
                    document.getElementById('deleteTable').value = table;
                    document.getElementById('deleteId').value = id;
                    document.getElementById('deleteIdColumn').value = idColumn;
                    
                    // Reset checkbox and button
                    confirmCheckbox.checked = false;
                    confirmBtn.disabled = true;
                    
                    deleteModal.show();
                });
            });
            
            // Enable/disable confirm button based on checkbox
            confirmCheckbox.addEventListener('change', function() {
                confirmBtn.disabled = !this.checked;
            });
            
            // Reset checkbox when modal is hidden
            document.getElementById('permanentDeleteModal').addEventListener('hidden.bs.modal', function() {
                confirmCheckbox.checked = false;
                confirmBtn.disabled = true;
            });
        });
    </script>
</body>
</html>