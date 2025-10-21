<?php
require_once '../config.php';

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
        error_log("Dashboard session load error: " . $e->getMessage());
    }
}

$total_inquiries = $unread_inquiries = $total_portraits = $total_videos = $total_partners = $approved_testimonials = 0;
$recent_inquiries = [];

try {
    $conn = getDBConnection();

    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries");
    $total_inquiries = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE is_read = 0");
    $unread_inquiries = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM portraits");
    $total_portraits = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM videos");
    $total_videos = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM partners");
    $total_partners = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM testimonials WHERE is_approved = 1");
    $approved_testimonials = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT * FROM inquiries ORDER BY submission_date DESC LIMIT 5");
    $recent_inquiries = $stmt->fetchAll();

    $stmt = $conn->query("SELECT COUNT(*) as total FROM experiences");
    $experienceCount = $stmt->fetch()['total'];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

$currentPage = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jade Salvador</title>
    <link href="css/admin-styles.css" rel="stylesheet"> 
    <?php include 'admin_header.php'; ?> 

    <style>
        .stat-card {
            border-radius: 16px;
            padding: 1.5rem;
            border: none;
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            background: var(--card-bg);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--stat-color-start), var(--stat-color-end));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.card-inquiries {
            --stat-color-start: #FF6B6B;
            --stat-color-end: #FF8E8E;
        }

        .stat-card.card-portraits {
            --stat-color-start: #4ECDC4;
            --stat-color-end: #6FE3D9;
        }

        .stat-card.card-videos {
            --stat-color-start: #A78BFA;
            --stat-color-end: #C4B5FD;
        }

        .stat-card.card-testimonials {
            --stat-color-start: #10B981;
            --stat-color-end: #34D399;
        }

        .stat-card.card-partners {
            --stat-color-start: #FBBF24;
            --stat-color-end: #FCD34D;
        }

        .stat-card.card-experiences {
            --stat-color-start: #fa13f6ff;
            --stat-color-end: #9e1b79ff;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, var(--stat-color-start), var(--stat-color-end));
            color: white;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin: 0.5rem 0;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--stat-color-start), var(--stat-color-end));
            color: white;
            margin-top: 0.5rem;
        }

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

        .table-card {
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .table-card .card-header {
            background-color: var(--bg-tertiary);
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-badge.status-new {
            background: linear-gradient(135deg, #FF6B6B, #FF8E8E);
            color: white;
        }

        .status-badge.status-read {
            background: linear-gradient(135deg, #10B981, #34D399);
            color: white;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .inquiry-type-badge {
            background: var(--jade-primary);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .stat-value {
                font-size: 2rem;
            }

            .page-title {
                font-size: 1.5rem;
            }
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
                        <i class="bi bi-grid-3x3-gap-fill"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 500;">Welcome back,
                        </div>
                        <div><?php echo h($_SESSION['full_name'] ?? 'Admin'); ?></div>
                    </div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <div class="stat-card card-inquiries">
                            <div class="stat-icon">
                                <i class="bi bi-chat-dots-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_portraits; ?></div>
                            <div class="stat-label">Portraits</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <div class="stat-card card-videos">
                            <div class="stat-icon">
                                <i class="bi bi-film"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_videos; ?></div>
                            <div class="stat-label">Videos</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <div class="stat-card card-testimonials">
                            <div class="stat-icon">
                                <i class="bi bi-star-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $approved_testimonials; ?></div>
                            <div class="stat-label">Testimonials</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <div class="stat-card card-partners">
                            <div class="stat-icon">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $total_partners; ?></div>
                            <div class="stat-label">Partners</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-lg-4 col-xl-2">
                        <div class="stat-card card-experiences">
                            <div class="stat-icon">
                                <i class="bi bi-briefcase-fill"></i>
                            </div>
                            <div class="stat-value"><?php echo $experienceCount; ?></div>
                            <div class="stat-label">Experiences</div>
                        </div>
                    </div>
                </div>

                <div class="card table-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-clock-history me-2"></i>Recent Inquiries
                        </h5>
                        <a href="inquiries.php" class="btn btn-sm btn-action"
                            style="background: var(--jade-primary); color: white;">
                            View All <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_inquiries)): ?>
                                        <tr>
                                            <td colspan="6">
                                                <div class="empty-state">
                                                    <i class="bi bi-inbox"></i>
                                                    <p class="mb-0">No recent inquiries found.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_inquiries as $inquiry): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($inquiry['submission_date'])); ?></td>
                                                <td class="fw-bold"><?php echo h($inquiry['full_name']); ?></td>
                                                <td><?php echo h($inquiry['email']); ?></td>
                                                <td>
                                                    <span
                                                        class="inquiry-type-badge"><?php echo h($inquiry['inquiry_type']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($inquiry['is_read']): ?>
                                                        <span class="status-badge status-read">
                                                            <i class="bi bi-check-circle-fill"></i> Read
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="status-badge status-new">
                                                            <i class="bi bi-exclamation-circle-fill"></i> New
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $inquiry['inquiry_id']; ?>"
                                                        class="btn btn-sm btn-action"
                                                        style="background: var(--jade-primary); color: white;">
                                                        <i class="bi bi-eye"></i> View
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>