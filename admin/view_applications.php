<?php
require_once '../config.php';

// Helper function for HTML escaping (required before including the header)
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
    }
}

// Define variables required by includes
$pageTitle = 'View Applications';
$currentPage = 'view_applications.php'; // For sidebar highlighting

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$applications = [];
$error = '';
$success = '';
$filter_opportunity = isset($_GET['opportunity_id']) ? (int)$_GET['opportunity_id'] : 0;

try {
    // Assuming getDBConnection() is defined in config.php
    $conn = getDBConnection();

    // Handle status update
    if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $app_id = (int)$_GET['id'];
        $status = $_GET['status'];
        $allowed_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected'];
        
        if (in_array($status, $allowed_statuses)) {
            $stmt = $conn->prepare("UPDATE applications SET status = :status, is_reviewed = 1 WHERE application_id = :id");
            $stmt->execute([':status' => $status, ':id' => $app_id]);
            $success = 'Status updated successfully! ✅';
             // Redirect to clear GET parameters
            header('Location: view_applications.php' . ($filter_opportunity > 0 ? '?opportunity_id=' . $filter_opportunity : ''));
            exit;
        }
    }

    // Fetch applications with opportunity details
    if ($filter_opportunity > 0) {
        $stmt = $conn->prepare("
            SELECT a.*, o.title as job_title, o.job_type 
            FROM applications a 
            JOIN opportunities o ON a.opportunity_id = o.opportunity_id 
            WHERE a.opportunity_id = :opp_id
            ORDER BY a.submission_date DESC
        ");
        $stmt->execute([':opp_id' => $filter_opportunity]);
    } else {
        $stmt = $conn->query("
            SELECT a.*, o.title as job_title, o.job_type 
            FROM applications a 
            JOIN opportunities o ON a.opportunity_id = o.opportunity_id 
            ORDER BY a.submission_date DESC
        ");
    }
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get opportunities for filter dropdown
    $stmt = $conn->query("SELECT opportunity_id, title FROM opportunities ORDER BY created_date DESC");
    $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Applications error: " . $e->getMessage());
    $error = 'Database error occurred.';
}
?>
<?php include 'admin_header.php'; ?>

    <?php include 'admin_sidebar.php'; ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="pt-3 pb-2 mb-4 border-bottom">
            <h1 class="h2">Applications</h1>
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
                                <th>Experience</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applications)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No applications yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($applications as $app): ?>
                                    <tr class="<?php echo $app['is_reviewed'] ? '' : 'table-warning'; ?>">
                                        <td><?php echo date('M d, Y', strtotime($app['submission_date'])); ?></td>
                                        <td>
                                            <strong><?php echo h($app['full_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo h($app['email']); ?></small>
                                        </td>
                                        <td><?php echo h($app['job_title']); ?></td>
                                        <td><?php echo h($app['experience_years']); ?> years</td>
                                        <td>
                                            <?php
                                            $badge_class = 'secondary';
                                            switch ($app['status']) {
                                                case 'shortlisted': $badge_class = 'success'; break;
                                                case 'rejected': $badge_class = 'danger'; break;
                                                case 'reviewed': $badge_class = 'info'; break;
                                            }
                                            ?>
                                            <span class="badge bg-<?php echo $badge_class; ?>">
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
</div> </div> <div class="modal fade" id="applicationModal" tabindex="-1">
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
                            <strong>Experience:</strong> <span id="detail_exp"></span> years
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Position Applied:</strong> <span id="detail_position"></span>
                    </div>
                    <div class="mb-3">
                        <strong>Portfolio/Link:</strong> <a href="#" id="detail_portfolio" target="_blank"></a>
                    </div>
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
            document.getElementById('detail_exp').textContent = app.experience_years;
            document.getElementById('detail_position').textContent = app.job_title;
            
            const portfolioLink = document.getElementById('detail_portfolio');
            if (app.portfolio_link) {
                portfolioLink.href = app.portfolio_link;
                portfolioLink.textContent = app.portfolio_link;
                portfolioLink.style.display = 'inline';
            } else {
                portfolioLink.textContent = 'N/A';
                portfolioLink.removeAttribute('href');
            }
            
            document.getElementById('detail_cover').textContent = app.cover_letter;
            
            // Status buttons
            const statusButtons = document.getElementById('statusButtons');
            // Retain the current opportunity_id filter if it exists
            const currentFilter = <?php echo $filter_opportunity; ?>;
            const filterParam = currentFilter > 0 ? '&opportunity_id=' + currentFilter : '';

            const statuses = [
                {value: 'pending', label: 'Pending', class: 'secondary'},
                {value: 'reviewed', label: 'Reviewed', class: 'info'},
                {value: 'shortlisted', label: 'Shortlisted', class: 'success'},
                {value: 'rejected', label: 'Rejected', class: 'danger'}
            ];
            
            statusButtons.innerHTML = '';
            statuses.forEach(status => {
                const btn = document.createElement('a');
                btn.className = 'btn btn-sm btn-' + status.class + (app.status === status.value ? ' active' : '');
                // Ensure the status update redirects back to the current view (with filter if applicable)
                btn.href = '?action=update_status&id=' + app.application_id + '&status=' + status.value + filterParam;
                btn.textContent = status.label;
                statusButtons.appendChild(btn);
            });
            
            var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();
        }
    </script>
</body>
</html>

tanggal host
modeling->talent

malalagay net rate (talent fee)
qualifications (age height gender)
model class (a,b,c)


set card and vtr