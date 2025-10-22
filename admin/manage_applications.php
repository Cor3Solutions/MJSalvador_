<?php
require_once '../config.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$opportunities = [];
$error = '';
$success = '';

try {
    $conn = getDBConnection();

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
                $opp_id = isset($_POST['opportunity_id']) ? (int) $_POST['opportunity_id'] : null;
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $requirements = trim($_POST['requirements']);
                $location = trim($_POST['location']);
                $job_type = trim($_POST['job_type']);
                $net_rate = trim($_POST['net_rate']);
                $age_requirement = trim($_POST['age_requirement']);
                $height_requirement = trim($_POST['height_requirement']);
                $gender_requirement = trim($_POST['gender_requirement']);
                $model_class = trim($_POST['model_class']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                $deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

                if ($_POST['action'] === 'add') {
                    $stmt = $conn->prepare("INSERT INTO opportunities (title, description, requirements, location, job_type, net_rate, age_requirement, height_requirement, gender_requirement, model_class, is_active, deadline, created_by, created_date) VALUES (:title, :desc, :req, :loc, :type, :rate, :age, :height, :gender, :class, :active, :deadline, :user, NOW())");
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
                        ':user' => $_SESSION['user_id']
                    ]);
                    $success = 'Opportunity posted successfully!';
                } else {
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
                    $success = 'Opportunity updated successfully!';
                }
            }
        }
    }

    // Handle delete
    if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
        $opp_id = (int) $_GET['id'];
        $stmt = $conn->prepare("DELETE FROM opportunities WHERE opportunity_id = :id");
        $stmt->execute([':id' => $opp_id]);
        $success = 'Opportunity deleted successfully!';
    }

    // Handle toggle active
    if (isset($_GET['action']) && $_GET['action'] == 'toggle' && isset($_GET['id'])) {
        $opp_id = (int) $_GET['id'];
        $stmt = $conn->prepare("UPDATE opportunities SET is_active = NOT is_active WHERE opportunity_id = :id");
        $stmt->execute([':id' => $opp_id]);
        $success = 'Status updated!';
    }

    // Fetch all opportunities
    $stmt = $conn->query("SELECT o.*, COUNT(a.application_id) as app_count FROM opportunities o LEFT JOIN applications a ON o.opportunity_id = a.opportunity_id GROUP BY o.opportunity_id ORDER BY o.created_date DESC");
    $opportunities = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Opportunities error: " . $e->getMessage());
    $error = 'Database error occurred.';
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
    <style>
        .sidebar {
            position: fixed;
            top: 56px;
            /* Height of the fixed navbar */
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

        main {
            padding-top: 1rem;
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse show" id="sidebarMenu">
                <?php $currentPage = 'manage_applications.php'; // Ensure this is set for highlighting ?>
                <?php include 'admin_sidebar.php'; ?>
            </div>

            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="pt-3 pb-2 mb-4 border-bottom d-flex justify-content-between align-items-center">
                    <h1 class="h2">Manage Opportunities</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#opportunityModal"
                        onclick="resetForm()">
                        <i class="bi bi-plus-lg"></i> Post New Opportunity
                    </button>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><?php echo h($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo h($success); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Location</th>
                                        <th>Deadline</th>
                                        <th>Applications</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($opportunities)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No opportunities posted yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($opportunities as $opp): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo h($opp['title']); ?></strong><br>
                                                    <small
                                                        class="text-muted"><?php echo h(substr($opp['description'], 0, 60)); ?>...</small>
                                                </td>
                                                <td><span class="badge bg-info"><?php echo h($opp['job_type']); ?></span></td>
                                                <td>
                                                    <small>
                                                        <?php if ($opp['net_rate']): ?>
                                                            <strong>Rate:</strong> <?php echo h($opp['net_rate']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['age_requirement']): ?>
                                                            <strong>Age:</strong> <?php echo h($opp['age_requirement']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['height_requirement']): ?>
                                                            <strong>Height:</strong>
                                                            <?php echo h($opp['height_requirement']); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['gender_requirement'] && $opp['gender_requirement'] != 'any'): ?>
                                                            <strong>Gender:</strong>
                                                            <?php echo ucfirst(h($opp['gender_requirement'])); ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($opp['model_class']): ?>
                                                            <span class="badge bg-secondary">Class
                                                                <?php echo h($opp['model_class']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><?php echo h($opp['location']); ?></td>
                                                <td><?php echo $opp['deadline'] ? date('M d, Y', strtotime($opp['deadline'])) : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <a href="view_applications.php?opportunity_id=<?php echo $opp['opportunity_id']; ?>"
                                                        class="badge bg-primary">
                                                        <?php echo $opp['app_count']; ?> Applications
                                                    </a>
                                                </td>
                                                <td>
                                                    <a href="?action=toggle&id=<?php echo $opp['opportunity_id']; ?>"
                                                        class="badge bg-<?php echo $opp['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $opp['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </a>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning"
                                                        onclick='editOpportunity(<?php echo json_encode($opp); ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <a href="?action=delete&id=<?php echo $opp['opportunity_id']; ?>"
                                                        onclick="return confirm('Delete this opportunity and all its applications?');"
                                                        class="btn btn-sm btn-danger">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
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

    <!-- Opportunity Modal -->
    <div class="modal fade" id="opportunityModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Post New Opportunity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="opportunity_id" id="opportunity_id">
                        <input type="hidden" name="action" id="action" value="add">

                        <div class="mb-3">
                            <label for="title" class="form-label">Job Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required
                                placeholder="e.g., Fashion Model for Summer Campaign">
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="job_type" class="form-label">Job Type *</label>
                                <select class="form-select" id="job_type" name="job_type" required
                                    onchange="toggleFields()">
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
                                <input type="text" class="form-control" id="location" name="location"
                                    placeholder="e.g., Manila, Philippines">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="net_rate" class="form-label">Net Rate (Talent Fee)</label>
                                <input type="text" class="form-control" id="net_rate" name="net_rate"
                                    placeholder="e.g., ₱5,000/day">
                            </div>
                        </div>

                        <!-- Talent-specific fields -->
                        <div id="talentFields" style="display: none;">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label for="age_requirement" class="form-label">Age Requirement</label>
                                    <input type="text" class="form-control" id="age_requirement" name="age_requirement"
                                        placeholder="e.g., 18-25">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label for="height_requirement" class="form-label">Height Requirement</label>
                                    <input type="text" class="form-control" id="height_requirement"
                                        name="height_requirement" placeholder="e.g., 5'5\" above">
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
                            <textarea class="form-control" id="description" name="description" rows="4" required
                                placeholder="Describe the opportunity..."></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="requirements" class="form-label">Requirements</label>
                            <textarea class="form-control" id="requirements" name="requirements" rows="3"
                                placeholder="List the requirements (one per line)"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="deadline" class="form-label">Application Deadline</label>
                                <input type="date" class="form-control" id="deadline" name="deadline">
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                                        checked>
                                    <label class="form-check-label" for="is_active">
                                        Active (visible to public)
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Post Opportunity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleFields() {
            const jobType = document.getElementById('job_type').value;
            const talentFields = document.getElementById('talentFields');

            // Define the job types that require the Talent/Modeling fields
            const talentRelatedTypes = ['talent', 'brand-ambassador', 'usherette'];

            if (talentRelatedTypes.includes(jobType)) {
                talentFields.style.display = 'block';
            } else {
                talentFields.style.display = 'none';
            }
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

            toggleFields();

            var modal = new bootstrap.Modal(document.getElementById('opportunityModal'));
            modal.show();
        }
    </script>
</body>

</html>