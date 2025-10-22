<?php
require_once '../config.php';

// Add the required utility function h() which was present in dashboard.php
if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch user name if not in session (assuming this logic exists in your setup)
if (!isset($_SESSION['full_name']) && isset($_SESSION['user_id'])) {
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = :id");
        $stmt->execute([':id' => $_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
        }
    } catch (PDOException $e) {
        error_log("View Applications session load error: " . $e->getMessage());
    }
}

$applications = [];
$error = '';
$success = '';
$filter_opportunity = isset($_GET['opportunity_id']) ? (int)$_GET['opportunity_id'] : 0;

try {
    $conn = getDBConnection();

    // Handle status update
    if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $app_id = (int)$_GET['id'];
        $status = $_GET['status'];
        $allowed_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected'];
        
        if (in_array($status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE applications SET status = :status, is_reviewed = 1 WHERE application_id = :id");
            $stmt->execute([':status' => $status, ':id' => $app_id]);
            // Redirect to remove query parameters and prevent re-submission
            header('Location: view_applications.php' . ($filter_opportunity > 0 ? '?opportunity_id=' . $filter_opportunity : ''));
            exit;
        }
    }

    // Fetch applications with opportunity details
    // Note: The SQL SELECT now includes all necessary application fields (including the link fields)
    $sql = "
        SELECT a.*, o.title as job_title, o.job_type 
        FROM applications a 
        JOIN opportunities o ON a.opportunity_id = o.opportunity_id 
    ";
    $params = [];

    if ($filter_opportunity > 0) {
        $sql .= " WHERE a.opportunity_id = :opp_id";
        $params[':opp_id'] = $filter_opportunity;
    }

    $sql .= " ORDER BY a.submission_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // Get opportunities for filter dropdown
    $stmt = $conn->query("SELECT opportunity_id, title FROM opportunities ORDER BY created_date DESC");
    $opportunities = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Applications error: " . $e->getMessage());
    $error = 'Database error occurred.';
}

$currentPage = 'view_applications.php'; // Set for sidebar highlighting
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Jade Salvador Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <?php include 'admin_header.php'; ?>
    
    <style> 
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--jade-primary), var(--jade-primary-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }

        .card {
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .table-hover tbody tr:hover {
            background-color: var(--bg-hover) !important;
        }
    </style>    
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content">
                <div class="page-title">
                    <div class="page-title-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <div>Applications Management</div>
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

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label for="opportunity_id" class="form-label">Filter by Opportunity</label>
                                <select class="form-select" name="opportunity_id" id="opportunity_id" onchange="this.form.submit()">
                                    <option value="0">All Opportunities</option>
                                    <?php foreach($opportunities as $opp): ?>
                                        <option value="<?php echo $opp['opportunity_id']; ?>" <?php echo $filter_opportunity == $opp['opportunity_id'] ? 'selected' : ''; ?>>
                                            <?php echo h($opp['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="view_applications.php" class="btn btn-secondary">Clear Filter</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($applications)): ?>
                                        <tr><td colspan="5" class="text-center text-muted py-4">No applications yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($applications as $app): ?>
                                            <tr class="<?php echo $app['is_reviewed'] ? '' : 'table-warning'; ?>">
                                                <td><?php echo date('M d, Y', strtotime($app['submission_date'])); ?></td>
                                                <td>
                                                    <strong><?php echo h($app['full_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo h($app['email']); ?></small>
                                                </td>
                                                <td><?php echo h($app['job_title']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $app['status'] == 'shortlisted' ? 'success' : 
                                                            ($app['status'] == 'rejected' ? 'danger' : 
                                                            ($app['status'] == 'reviewed' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($app['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick='viewApplication(<?php echo json_encode($app); ?>)'>
                                                        <i class="bi bi-eye"></i> View
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

    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Application Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Name:</strong> <span id="detail_name"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong> <span id="detail_email"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Phone:</strong> <span id="detail_phone"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Position Applied:</strong> <span id="detail_position"></span>
                        </div>
                    </div>
                    
                    <hr>

                    <div id="talent_links" style="display: none;">
                        <h6 class="mb-3">Talent/Modeling Links:</h6>
                        <div class="mb-3">
                            <strong>Set Card Link (Required):</strong> <a href="#" id="detail_setcard" target="_blank"></a>
                        </div>
                        <div class="mb-3">
                            <strong>VTR/Demo Reel Link (Optional):</strong> <a href="#" id="detail_vtr" target="_blank"></a>
                        </div>
                    </div>

                    <div id="va_links" style="display: none;">
                        <h6 class="mb-3">VA/Other Links:</h6>
                        <div class="mb-3">
                            <strong>Resume/CV Link (Required):</strong> <a href="#" id="detail_resume" target="_blank"></a>
                        </div>
                        <div class="mb-3">
                            <strong>Portfolio Link (Optional):</strong> <a href="#" id="detail_portfolio" target="_blank"></a>
                        </div>
                    </div>
                    <hr>

                    <div class="mb-3">
                        <strong>Cover Letter:</strong>
                        <p id="detail_cover" class="border p-3 bg-light"></p>
                    </div>

                    <div class="mb-3">
                        <strong>Update Status:</strong>
                        <div class="btn-group" role="group" id="statusButtons">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewApplication(app) {
            document.getElementById('detail_name').textContent = app.full_name;
            document.getElementById('detail_email').textContent = app.email;
            document.getElementById('detail_phone').textContent = app.phone_number || 'N/A';
            document.getElementById('detail_position').textContent = app.job_title;
            document.getElementById('detail_cover').textContent = app.cover_letter;
            
            const talentTypes = ['talent', 'brand-ambassador', 'usherette'];
            const isTalent = talentTypes.includes(app.job_type);

            const talentLinksDiv = document.getElementById('talent_links');
            const vaLinksDiv = document.getElementById('va_links');
            
            // Toggle visibility of link groups
            talentLinksDiv.style.display = isTalent ? 'block' : 'none';
            vaLinksDiv.style.display = isTalent ? 'none' : 'block';

            // Populate specific links based on job type
            if (isTalent) {
                // Set Card Link
                const setcardLink = document.getElementById('detail_setcard');
                setcardLink.href = app.setcard_link || '#';
                setcardLink.textContent = app.setcard_link || 'N/A';
                setcardLink.style.color = app.setcard_link ? 'var(--bs-link-color)' : 'var(--bs-gray-500)';

                // VTR Link
                const vtrLink = document.getElementById('detail_vtr');
                vtrLink.href = app.vtr_link || '#';
                vtrLink.textContent = app.vtr_link || 'N/A';
                vtrLink.style.color = app.vtr_link ? 'var(--bs-link-color)' : 'var(--bs-gray-500)';

            } else {
                // Resume/CV Link (for VA/Other)
                const resumeLink = document.getElementById('detail_resume');
                resumeLink.href = app.resume_cv_link || '#';
                resumeLink.textContent = app.resume_cv_link || 'N/A';
                resumeLink.style.color = app.resume_cv_link ? 'var(--bs-link-color)' : 'var(--bs-gray-500)';

                // Portfolio Link (for VA/Other)
                const portfolioLink = document.getElementById('detail_portfolio');
                portfolioLink.href = app.portfolio_link || '#';
                portfolioLink.textContent = app.portfolio_link || 'N/A';
                portfolioLink.style.color = app.portfolio_link ? 'var(--bs-link-color)' : 'var(--bs-gray-500)';
            }

            // Status buttons
            const statusButtons = document.getElementById('statusButtons');
            const statuses = [
                {value: 'pending', label: 'Pending', class: 'secondary'},
                {value: 'reviewed', label: 'Reviewed', class: 'info'},
                {value: 'shortlisted', label: 'Shortlisted', class: 'success'},
                {value: 'rejected', label: 'Rejected', class: 'danger'}
            ];
            
            statusButtons.innerHTML = '';
            statuses.forEach(status => {
                // Ensure the button links back to the current filter state
                const currentFilter = <?php echo json_encode($filter_opportunity); ?>;
                let href = '?action=update_status&id=' + app.application_id + '&status=' + status.value;
                if (currentFilter > 0) {
                    href += '&opportunity_id=' + currentFilter;
                }

                const btn = document.createElement('a');
                btn.className = 'btn btn-sm btn-' + status.class + (app.status === status.value ? ' active' : '');
                btn.href = href;
                btn.textContent = status.label;
                statusButtons.appendChild(btn);
            });
            
            var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();
        }
    </script>
</body>
</html>