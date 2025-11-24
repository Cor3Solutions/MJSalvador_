<?php
require_once '../config.php';
require_once 'includes/archive_functions.php';
require_once 'includes/email_functions.php'; // ADDED: For email notifications

if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch user name if not in session
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

    // Handle status update with email notification
    if (isset($_GET['action']) && $_GET['action'] == 'update_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $app_id = (int)$_GET['id'];
        $new_status = $_GET['status'];
        $allowed_statuses = ['pending', 'reviewed', 'shortlisted', 'rejected'];
        
        if (in_array($new_status, $allowed_statuses)) {
            // Get application details before update
            $stmt = $conn->prepare("
                SELECT a.*, o.title as job_title, o.job_type 
                FROM applications a 
                JOIN opportunities o ON a.opportunity_id = o.opportunity_id 
                WHERE a.application_id = :id
            ");
            $stmt->execute([':id' => $app_id]);
            $app_details = $stmt->fetch();
            
            if ($app_details) {
                $old_status = $app_details['status'];
                
                // Update the status
                $updateStmt = $conn->prepare("UPDATE applications SET status = :status, is_reviewed = 1 WHERE application_id = :id AND is_archived = 0");
                $updateStmt->execute([':status' => $new_status, ':id' => $app_id]);
                
                // Send email notification if status actually changed
                if ($old_status !== $new_status) {
                    $email_sent = sendApplicationStatusEmail(
                        $app_details['email'],
                        $app_details['full_name'],
                        $app_details['job_title'],
                        $new_status,
                        $app_details
                    );
                    
                    if ($email_sent) {
                        $success = 'Application status updated and email notification sent! 📧';
                    } else {
                        $success = 'Application status updated, but email notification failed to send.';
                    }
                } else {
                    $success = 'Application status updated successfully!';
                }
            }
            
            header('Location: view_applications.php' . ($filter_opportunity > 0 ? '?opportunity_id=' . $filter_opportunity : '') . '&success=' . urlencode($success));
            exit;
        }
    }

    // Handle archive action
    if (isset($_GET['action']) && $_GET['action'] == 'archive' && isset($_GET['id'])) {
        $app_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
        if ($app_id !== false) {
            if (archiveRecord($conn, 'applications', 'application_id', $app_id, $_SESSION['user_id'] ?? null)) {
                $success = 'Application moved to archive! 🗄️';
            } else {
                $error = 'Failed to archive application.';
            }
            header('Location: view_applications.php' . ($filter_opportunity > 0 ? '?opportunity_id=' . $filter_opportunity : '') . '&success=' . urlencode($success));
            exit;
        } else {
            $error = 'Invalid application ID for archiving.';
        }
    }

    // Check for success messages after redirect
    if (isset($_GET['success'])) {
        $success = h($_GET['success']);
    }

    // Fetch only non-archived applications
    $sql = "
        SELECT a.*, o.title as job_title, o.job_type 
        FROM applications a 
        JOIN opportunities o ON a.opportunity_id = o.opportunity_id 
        WHERE a.is_archived = 0
    ";
    $params = [];

    if ($filter_opportunity > 0) {
        $sql .= " AND a.opportunity_id = :opp_id";
        $params[':opp_id'] = $filter_opportunity;
    }

    $sql .= " ORDER BY a.submission_date DESC";

    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $applications = $stmt->fetchAll();

    // Get only non-archived opportunities
    $stmt = $conn->query("SELECT opportunity_id, title FROM opportunities WHERE is_archived = 0 ORDER BY created_date DESC");
    $opportunities = $stmt->fetchAll();

    // Get application statistics
    $statsStmt = $conn->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN a.status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
            SUM(CASE WHEN a.status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
            SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) as rejected
        FROM applications a
        JOIN opportunities o ON a.opportunity_id = o.opportunity_id
        WHERE a.is_archived = 0 AND o.is_archived = 0
    ");
    $stats = $statsStmt->fetch();
    
    // If filtering by opportunity, also get filtered stats
    if ($filter_opportunity > 0) {
        $filteredStatsStmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) as reviewed,
                SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM applications 
            WHERE is_archived = 0 AND opportunity_id = :opp_id
        ");
        $filteredStatsStmt->execute([':opp_id' => $filter_opportunity]);
        $filteredStats = $filteredStatsStmt->fetch();
    }

} catch (PDOException $e) {
    error_log("Applications error: " . $e->getMessage());
    $error = 'Database error occurred.';
}

$currentPage = 'view_applications.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applications - Jade Salvador Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="css/admin-styles.css" rel="stylesheet">
    
    <style>
        .stat-card {
            border-radius: 12px;
            padding: 1.25rem;
            border: 2px solid var(--border-color);
            background: var(--card-bg);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 600;
        }

        .link-section {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.25rem;
        }

        .link-section h6 {
            color: #0d6efd;
            margin-bottom: 0.75rem;
        }

        .link-item {
            margin-bottom: 0.75rem;
        }

        .link-item:last-child {
            margin-bottom: 0;
        }

        .status-update-info {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 0.25rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
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
                            <i class="bi bi-file-earmark-text me-2" style="color: var(--jade-primary);"></i>Applications Management
                        </h1>
                        <p class="text-muted mb-0">Review and manage job applications</p>
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
                        <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;">
                                <i class="bi bi-inbox"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($filteredStats) ? $filteredStats['total'] : $stats['total']; ?></div>
                            <div class="stat-label"><?php echo $filter_opportunity > 0 ? 'Filtered' : 'Total'; ?> Applications</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316); color: white;">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($filteredStats) ? $filteredStats['pending'] : $stats['pending']; ?></div>
                            <div class="stat-label">Pending Review</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #10b981, #059669); color: white;">
                                <i class="bi bi-star"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($filteredStats) ? $filteredStats['shortlisted'] : $stats['shortlisted']; ?></div>
                            <div class="stat-label">Shortlisted</div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white;">
                                <i class="bi bi-eye"></i>
                            </div>
                            <div class="stat-value"><?php echo isset($filteredStats) ? $filteredStats['reviewed'] : $stats['reviewed']; ?></div>
                            <div class="stat-label">Reviewed</div>
                        </div>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-8">
                                <label for="opportunity_id" class="form-label">
                                    <i class="bi bi-funnel me-1"></i>Filter by Opportunity
                                </label>
                                <select class="form-select" name="opportunity_id" id="opportunity_id" onchange="this.form.submit()">
                                    <option value="0">All Opportunities</option>
                                    <?php foreach($opportunities as $opp): ?>
                                        <option value="<?php echo $opp['opportunity_id']; ?>" <?php echo $filter_opportunity == $opp['opportunity_id'] ? 'selected' : ''; ?>>
                                            <?php echo h($opp['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 text-end">
                                <a href="view_applications.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-1"></i>Clear Filter
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Applications Table -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i>
                            <?php echo $filter_opportunity > 0 ? 'Filtered Applications' : 'All Applications'; ?>
                        </h5>
                        <span class="badge bg-primary"><?php echo count($applications); ?> Results</span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Applicant</th>
                                        <th>Position</th>
                                        <th>Status</th>
                                        <th class="text-center" style="width: 180px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($applications)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-5">
                                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mb-0 mt-2">No applications found.</p>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($applications as $app): ?>
                                            <tr class="<?php echo !$app['is_reviewed'] ? 'table-warning' : ''; ?>">
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, Y', strtotime($app['submission_date'])); ?>
                                                    </small>
                                                    <?php if (!$app['is_reviewed']): ?>
                                                        <br><span class="badge bg-warning text-dark">New</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo h($app['full_name']); ?></strong><br>
                                                    <small class="text-muted">
                                                        <i class="bi bi-envelope me-1"></i><?php echo h($app['email']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo h($app['job_title']); ?></span>
                                                </td>
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
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button class="btn btn-sm btn-primary" 
                                                                onclick='viewApplication(<?php echo json_encode($app); ?>)' 
                                                                title="View Details">
                                                            <i class="bi bi-eye"></i>
                                                        </button>
                                                        <a href="?action=archive&id=<?php echo $app['application_id']; ?><?php echo $filter_opportunity > 0 ? '&opportunity_id=' . $filter_opportunity : ''; ?>" 
                                                           onclick="return confirm('Archive this application from <?php echo h($app['full_name']); ?>?\n\nIt will be moved to the archives.');" 
                                                           class="btn btn-sm btn-danger" 
                                                           title="Archive Application">
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

    <!-- Application Details Modal -->
    <div class="modal fade" id="applicationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-file-earmark-text me-2"></i>Application Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Basic Information -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="bi bi-person me-1"></i>Name:</strong> 
                            <span id="detail_name"></span>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="bi bi-envelope me-1"></i>Email:</strong> 
                            <span id="detail_email"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong><i class="bi bi-telephone me-1"></i>Phone:</strong> 
                            <span id="detail_phone"></span>
                        </div>
                        <div class="col-md-6">
                            <strong><i class="bi bi-briefcase me-1"></i>Position Applied:</strong> 
                            <span id="detail_position"></span>
                        </div>
                    </div>
                    
                    <hr>

                    <!-- Talent/Modeling Links -->
                    <div id="talent_links" style="display: none;">
                        <div class="link-section">
                            <h6><i class="bi bi-camera me-2"></i>Talent/Modeling Materials</h6>
                            <div class="link-item">
                                <strong>Set Card Link:</strong><br>
                                <a href="#" id="detail_setcard" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <i class="bi bi-box-arrow-up-right me-1"></i>View Set Card
                                </a>
                                <span id="no_setcard" class="text-muted" style="display: none;">Not provided</span>
                            </div>
                            <div class="link-item">
                                <strong>VTR/Demo Reel Link:</strong><br>
                                <a href="#" id="detail_vtr" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <i class="bi bi-play-circle me-1"></i>View VTR/Demo Reel
                                </a>
                                <span id="no_vtr" class="text-muted" style="display: none;">Not provided</span>
                            </div>
                        </div>
                    </div>

                    <!-- VA/Other Links -->
                    <div id="va_links" style="display: none;">
                        <div class="link-section">
                            <h6><i class="bi bi-file-earmark-person me-2"></i>Professional Materials</h6>
                            <div class="link-item">
                                <strong>Resume/CV Link:</strong><br>
                                <a href="#" id="detail_resume" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <i class="bi bi-file-earmark-text me-1"></i>View Resume/CV
                                </a>
                                <span id="no_resume" class="text-muted" style="display: none;">Not provided</span>
                            </div>
                            <div class="link-item">
                                <strong>Portfolio Link:</strong><br>
                                <a href="#" id="detail_portfolio" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                    <i class="bi bi-folder2-open me-1"></i>View Portfolio
                                </a>
                                <span id="no_portfolio" class="text-muted" style="display: none;">Not provided</span>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <!-- Cover Letter -->
                    <div class="mb-3">
                        <strong><i class="bi bi-file-text me-1"></i>Cover Letter:</strong>
                        <div id="detail_cover" class="border rounded p-3 bg-light mt-2" style="white-space: pre-wrap;"></div>
                    </div>

                    <hr>

                    <!-- Status Update Section -->
                    <div class="status-update-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Changing the status will automatically send an email notification to the applicant.
                    </div>

                    <div class="mb-3">
                        <strong><i class="bi bi-toggle-on me-1"></i>Update Status:</strong>
                        <div class="btn-group d-block mt-2" role="group" id="statusButtons">
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
            document.getElementById('detail_cover').textContent = app.cover_letter || 'No cover letter provided.';
            
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
                const noSetcard = document.getElementById('no_setcard');
                if (app.setcard_link && app.setcard_link.trim() !== '') {
                    setcardLink.href = app.setcard_link;
                    setcardLink.style.display = 'inline-block';
                    noSetcard.style.display = 'none';
                } else {
                    setcardLink.style.display = 'none';
                    noSetcard.style.display = 'inline';
                }

                // VTR Link
                const vtrLink = document.getElementById('detail_vtr');
                const noVtr = document.getElementById('no_vtr');
                if (app.vtr_link && app.vtr_link.trim() !== '') {
                    vtrLink.href = app.vtr_link;
                    vtrLink.style.display = 'inline-block';
                    noVtr.style.display = 'none';
                } else {
                    vtrLink.style.display = 'none';
                    noVtr.style.display = 'inline';
                }

            } else {
                // Resume/CV Link (for VA/Other)
                const resumeLink = document.getElementById('detail_resume');
                const noResume = document.getElementById('no_resume');
                if (app.resume_cv_link && app.resume_cv_link.trim() !== '') {
                    resumeLink.href = app.resume_cv_link;
                    resumeLink.style.display = 'inline-block';
                    noResume.style.display = 'none';
                } else {
                    resumeLink.style.display = 'none';
                    noResume.style.display = 'inline';
                }

                // Portfolio Link (for VA/Other)
                const portfolioLink = document.getElementById('detail_portfolio');
                const noPortfolio = document.getElementById('no_portfolio');
                if (app.portfolio_link && app.portfolio_link.trim() !== '') {
                    portfolioLink.href = app.portfolio_link;
                    portfolioLink.style.display = 'inline-block';
                    noPortfolio.style.display = 'none';
                } else {
                    portfolioLink.style.display = 'none';
                    noPortfolio.style.display = 'inline';
                }
            }

            // Status buttons
            const statusButtons = document.getElementById('statusButtons');
            const statuses = [
                {value: 'pending', label: 'Pending', class: 'secondary', icon: 'clock-history'},
                {value: 'reviewed', label: 'Reviewed', class: 'info', icon: 'eye'},
                {value: 'shortlisted', label: 'Shortlisted', class: 'success', icon: 'star'},
                {value: 'rejected', label: 'Rejected', class: 'danger', icon: 'x-circle'}
            ];
            
            statusButtons.innerHTML = '';
            statuses.forEach(status => {
                const currentFilter = <?php echo json_encode($filter_opportunity); ?>;
                let href = '?action=update_status&id=' + app.application_id + '&status=' + status.value;
                if (currentFilter > 0) {
                    href += '&opportunity_id=' + currentFilter;
                }

                const btn = document.createElement('a');
                btn.className = 'btn btn-sm btn-' + status.class + (app.status === status.value ? ' active' : '') + ' me-2 mb-2';
                btn.href = href;
                btn.innerHTML = '<i class="bi bi-' + status.icon + ' me-1"></i>' + status.label;
                
                // Add confirmation for status change
                if (app.status !== status.value) {
                    btn.onclick = function(e) {
                        return confirm('Update status to "' + status.label + '"?\n\nAn email notification will be sent to ' + app.email);
                    };
                }
                
                statusButtons.appendChild(btn);
            });
            
            var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
            modal.show();
        }
    </script>
</body>
</html>