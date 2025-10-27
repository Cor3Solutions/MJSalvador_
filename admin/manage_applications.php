<?php
// =================================================================
// NOTE: Assuming this file is at /admin/manage_applications.php
// and config.php is at /config.php (path: ../config.php)
// =================================================================
require_once '../config.php';
require_once 'includes/archive_functions.php'; // ADDED: Required for archiving

// The session is already started conditionally in config.php.

// Use the CSRF function from config.php
$csrf_token = generateCSRFToken();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$opportunities = [];
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
            $opp_id = isset($_POST['opportunity_id']) ? filter_var($_POST['opportunity_id'], FILTER_VALIDATE_INT) : null;
            $title = trim($_POST['title']);
            $description = trim($_POST['description']);
            $requirements = trim($_POST['requirements'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $job_type = trim($_POST['job_type']);
            $net_rate = trim($_POST['net_rate'] ?? '');
            $age_requirement = trim($_POST['age_requirement'] ?? '');
            $height_requirement = trim($_POST['height_requirement'] ?? '');
            $gender_requirement = trim($_POST['gender_requirement'] ?? 'any');
            $model_class = trim($_POST['model_class'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

            // Basic required fields check
            if (empty($title) || empty($description) || empty($job_type)) {
                $error = 'Title, Description, and Job Type are required fields.';
            }

            // Additional check for 'edit' action
            if ($action === 'edit' && !$opp_id) {
                $error = 'Invalid opportunity ID for editing.';
            }
            
            // If no error, proceed with DB operation
            if (!$error) {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO opportunities (title, description, requirements, location, job_type, net_rate, age_requirement, height_requirement, gender_requirement, model_class, is_active, is_archived, deadline, created_by, created_date) VALUES (:title, :desc, :req, :loc, :type, :rate, :age, :height, :gender, :class, :active, 0, :deadline, :user, NOW())");
                    $stmt->execute([
                        ':title' => $title,
                        ':desc' => $description,
                        ':req' => $requirements,
                        ':loc' => $location,
                        ':type' => $job_type,
                        ':rate' => $net_rate,
                        ':age' => $age_requirement,
                        ':height' => $height_requirement,
                        ':gender' => $gender_requirement,
                        ':class' => $model_class,
                        ':active' => $is_active,
                        ':deadline' => $deadline,
                        ':user' => $_SESSION['user_id'] ?? 0
                    ]);
                    $success = 'Opportunity posted successfully! 🚀';
                } elseif ($action === 'edit') {
                    $stmt = $conn->prepare("UPDATE opportunities SET title = :title, description = :desc, requirements = :req, location = :loc, job_type = :type, net_rate = :rate, age_requirement = :age, height_requirement = :height, gender_requirement = :gender, model_class = :class, is_active = :active, deadline = :deadline WHERE opportunity_id = :id");
                    $stmt->execute([
                        ':title' => $title,
                        ':desc' => $description,
                        ':req' => $requirements,
                        ':loc' => $location,
                        ':type' => $job_type,
                        ':rate' => $net_rate,
                        ':age' => $age_requirement,
                        ':height' => $height_requirement,
                        ':gender' => $gender_requirement,
                        ':class' => $model_class,
                        ':active' => $is_active,
                        ':deadline' => $deadline,
                        ':id' => $opp_id
                    ]);
                    $success = 'Opportunity updated successfully! ✨';
                }
                // POST/REDIRECT/GET Pattern to prevent form resubmission
                header('Location: manage_applications.php?success=' . urlencode($success));
                exit;
            }
        }
    }

    // IMPROVED: Use archiveRecord function for proper archiving
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $opp_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($opp_id !== false) {
            if (archiveRecord($conn, 'opportunities', 'opportunity_id', $opp_id, $_SESSION['user_id'] ?? null)) {
                $success = 'Opportunity moved to archive! 🗄️';
            } else {
                $error = 'Failed to archive opportunity.';
            }
            header('Location: manage_applications.php?success=' . urlencode($success));
            exit;
        } else {
            $error = 'Invalid opportunity ID for archiving.';
        }
    }

    // Handle toggle active
    if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
        $opp_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($opp_id !== false) {
            $stmt = $conn->prepare("UPDATE opportunities SET is_active = NOT is_active WHERE opportunity_id = :id AND is_archived = 0");
            $stmt->execute([':id' => $opp_id]);
            $success = 'Status updated! 🔄';
            header('Location: manage_applications.php?success=' . urlencode($success));
            exit;
        } else {
            $error = 'Invalid opportunity ID for status toggle.';
        }
    }

    // Check for success messages after redirect
    if (isset($_GET['success'])) {
        $success = h($_GET['success']);
    }

    // FIXED: Fetch only non-archived opportunities
    $stmt = $conn->query("SELECT o.*, COUNT(a.application_id) as app_count 
                          FROM opportunities o 
                          LEFT JOIN applications a ON o.opportunity_id = a.opportunity_id AND a.is_archived = 0 
                          WHERE o.is_archived = 0 
                          GROUP BY o.opportunity_id 
                          ORDER BY o.created_date DESC");
    $opportunities = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Opportunities error: " . $e->getMessage());
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
    <title>Manage Opportunities</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php $currentPage = 'manage_applications.php'; ?>
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">
                            <i class="bi bi-briefcase me-2" style="color: var(--jade-primary);"></i>Manage Opportunities
                        </h1>
                        <p class="text-muted mb-0">Post and manage job opportunities</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#opportunityModal" onclick="resetForm()">
                        <i class="bi bi-plus-lg me-2"></i>Post New Opportunity
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

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type / Requirements</th>
                                        <th>Location</th>
                                        <th>Deadline</th>
                                        <th>Applications</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width: 200px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($opportunities)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mb-0 mt-2">No active opportunities posted yet.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($opportunities as $opp): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo h($opp['title']); ?></strong><br>
                                                    <small class="text-muted"><?php echo h(substr($opp['description'], 0, 60)); ?>...</small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo h($opp['job_type']); ?></span>
                                                    <small class="d-block mt-1">
                                                        <?php if ($opp['net_rate']): ?>
                                                            <strong>Rate:</strong> <?php echo h($opp['net_rate']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['age_requirement']): ?>
                                                            <strong>Age:</strong> <?php echo h($opp['age_requirement']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['height_requirement']): ?>
                                                            <strong>Height:</strong> <?php echo h($opp['height_requirement']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['gender_requirement'] && $opp['gender_requirement'] != 'any'): ?>
                                                            <strong>Gender:</strong> <?php echo ucfirst(h($opp['gender_requirement'])); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['model_class']): ?>
                                                            <span class="badge bg-secondary">Class <?php echo h($opp['model_class']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><?php echo h($opp['location'] ?: 'N/A'); ?></td>
                                                <td><?php echo $opp['deadline'] ? date('M d, Y', strtotime($opp['deadline'])) : 'N/A'; ?></td>
                                                <td>
                                                    <a href="view_applications.php?opportunity_id=<?php echo $opp['opportunity_id']; ?>" class="badge bg-primary text-decoration-none">
                                                        <i class="bi bi-file-earmark-text me-1"></i><?php echo $opp['app_count']; ?> Applications
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="?action=toggle&id=<?php echo $opp['opportunity_id']; ?>" 
                                                       class="badge bg-<?php echo $opp['is_active'] ? 'success' : 'secondary'; ?> text-decoration-none"
                                                       title="Click to toggle status">
                                                        <?php echo $opp['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button class="btn btn-sm btn-warning" onclick='editOpportunity(<?php echo json_encode($opp); ?>)' title="Edit">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <a href="?action=archive&id=<?php echo $opp['opportunity_id']; ?>" 
                                                           onclick="return confirm('Archive this opportunity: <?php echo h($opp['title']); ?>?\n\nIt will be moved to the archives and hidden from applicants.');" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Archive Opportunity">
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

    <!-- Modal remains the same -->
    <div class="modal fade" id="opportunityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Post New Opportunity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="opportunity_id" id="opportunity_id">
                        <input type="hidden" name="action" id="action" value="add">

                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="e.g., Fashion Model for Summer Campaign">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="job_type" class="form-label">Job Type *</label>
                                <select class="form-select" id="job_type" name="job_type" required onchange="toggleFields()">
                                    <option value="">Select Type</option>
                                    <option value="talent">Talent</option>
                                    <option value="usherette">Usherette</option>
                                    <option value="virtual-assistant">Virtual Assistant</option>
                                    <option value="brand-ambassador">Brand Ambassador</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="e.g., Manila, Philippines">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="net_rate" class="form-label">Net Rate (Talent Fee)</label>
                                <input type="text" class="form-control" id="net_rate" name="net_rate" placeholder="e.g., ₱5,000/day">
                            </div>
                        </div>

                        <div id="talentFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="age_requirement" class="form-label">Age Requirement</label>
                                    <input type="text" class="form-control" id="age_requirement" name="age_requirement" placeholder="e.g., 18-25">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="height_requirement" class="form-label">Height Requirement</label>
                                    <input type="text" class="form-control" id="height_requirement" name="height_requirement" placeholder="e.g., 5'5\" above">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="gender_requirement" class="form-label">Gender</label>
                                    <select class="form-select" id="gender_requirement" name="gender_requirement">
                                        <option value="any">Any</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="model_class" class="form-label">Model Class</label>
                                    <select class="form-select" id="model_class" name="model_class">
                                        <option value="">Select Class</option>
                                        <option value="A">Class A</option>
                                        <option value="B">Class B</option>
                                        <option value="C">Class C</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="4" required placeholder="Describe the opportunity..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="3" placeholder="List the requirements (one per line)"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="deadline" class="form-label">Application Deadline</label>
                                <input type="date" class="form-control" id="deadline" name="deadline">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" checked>
                                    <label class="form-check-label" for="is_active">Active (visible to public)</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Opportunity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', toggleFields);

        function toggleFields() {
            const jobType = document.getElementById('job_type').value;
            const talentFields = document.getElementById('talentFields');
            const talentRelatedTypes = ['talent', 'brand-ambassador', 'usherette'];
            talentFields.style.display = talentRelatedTypes.includes(jobType) ? 'block' : 'none';
        }

        function resetForm() {
            document.getElementById('modalTitle').textContent = 'Post New Opportunity';
            document.getElementById('action').value = 'add';
            document.getElementById('opportunity_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('job_type').value = '';
            document.getElementById('location').value = '';
            document.getElementById('net_rate').value = '';
            document.getElementById('age_requirement').value = '';
            document.getElementById('height_requirement').value = '';
            document.getElementById('gender_requirement').value = 'any';
            document.getElementById('model_class').value = '';
            document.getElementById('description').value = '';
            document.getElementById('requirements').value = '';
            document.getElementById('deadline').value = '';
            document.getElementById('is_active').checked = true;
            document.querySelector('#opportunityModal .modal-footer .btn-primary').textContent = 'Post Opportunity';
            toggleFields();
        }

        function editOpportunity(opp) {
            document.getElementById('modalTitle').textContent = 'Edit Opportunity';
            document.getElementById('action').value = 'edit';
            document.getElementById('opportunity_id').value = opp.opportunity_id;
            document.getElementById('title').value = opp.title;
            document.getElementById('job_type').value = opp.job_type;
            document.getElementById('location').value = opp.location || '';
            document.getElementById('net_rate').value = opp.net_rate || '';
            document.getElementById('age_requirement').value = opp.age_requirement || '';
            document.getElementById('height_requirement').value = opp.height_requirement || '';
            document.getElementById('gender_requirement').value = opp.gender_requirement || 'any';
            document.getElementById('model_class').value = opp.model_class || '';
            document.getElementById('description').value = opp.description;
            document.getElementById('requirements').value = opp.requirements || '';
            document.getElementById('deadline').value = opp.deadline || '';
            document.getElementById('is_active').checked = opp.is_active == 1;
            document.querySelector('#opportunityModal .modal-footer .btn-primary').textContent = 'Save Changes';
            toggleFields();
            var modal = new bootstrap.Modal(document.getElementById('opportunityModal'));
            modal.show();
        }
    </script>
</body>
</html>