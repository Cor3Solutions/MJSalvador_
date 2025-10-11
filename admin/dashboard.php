<?php
require_once '../config.php';

// FIX: Define the h() helper function for HTML escaping, which is missing.
if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Check for authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Ensure session variables for full_name are set, otherwise load them
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
        // You should not use getDBConnection() inside the try block without knowing if it throws exceptions, 
        // but given the context, we'll assume it's safe.
        error_log("Dashboard session load error: " . $e->getMessage());
    }
}


// Initialize variables
$total_inquiries = $unread_inquiries = $total_portraits = $total_videos = $total_partners = $approved_testimonials = 0;
$recent_inquiries = [];

try {
    $conn = getDBConnection();

    // Get statistics
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

    // Get recent inquiries
    $stmt = $conn->query("SELECT * FROM inquiries ORDER BY submission_date DESC LIMIT 5");
    $recent_inquiries = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jade Salvador</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            /* Define a vibrant color palette */
            --color-red-vibrant: #FF6B6B;
            --color-purple-vibrant: #8338EC;
            --color-blue-vibrant: #3A86FF;
            --color-green-vibrant: #6AFA7F;
            --color-yellow-vibrant: #FFBE0B;
            --color-pink-vibrant: #FB5607;
            --bg-light: #f4f6f9;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--bg-light);
        }

        .main-content {
            padding-left: 1rem;
        }

        /* 🎨 FINAL TOUCH: Making the sidebar visually distinct from the main content */
        .sidebar {
            background-color: #ffffff;
            /* White sidebar */
            border-right: 1px solid #e9ecef;
            min-height: 100vh;
        }

        /* Styling for the colorful stat cards */
        .stat-card {
            color: #fff !important;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            overflow: hidden;
            position: relative;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .stat-card .card-body {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
        }

        .stat-card .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .stat-card h2 {
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card small {
            opacity: 0.8;
            font-size: 0.8rem;
        }

        /* Custom color definitions */
        .bg-custom-red {
            background-color: var(--color-red-vibrant) !important;
        }

        .bg-custom-purple {
            background-color: var(--color-purple-vibrant) !important;
        }

        .bg-custom-blue {
            background-color: var(--color-blue-vibrant) !important;
        }

        .bg-custom-green {
            background-color: var(--color-green-vibrant) !important;
        }

        .bg-custom-yellow {
            background-color: var(--color-yellow-vibrant) !important;
        }

        .bg-custom-pink {
            background-color: var(--color-pink-vibrant) !important;
        }


        /* Icon overlay for visual interest */
        .stat-card .stat-icon {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 3rem;
            opacity: 0.2;
            color: #fff;
        }

        /* Table styling for better visual separation */
        .table-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .table-card .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #343a40;
        }

        /* Highlight unread row more clearly */
        .table-warning.bg-opacity-10 {
            background-color: rgba(255, 193, 7, 0.15) !important;
        }

        /* Badges for status */
        .badge.bg-danger {
            background-color: var(--color-red-vibrant) !important;
        }

        .badge.bg-success {
            background-color: var(--color-green-vibrant) !important;
        }

        .badge.bg-primary {
            background-color: var(--color-blue-vibrant) !important;
        }

        /* 📱 RESPONSIVENESS FIXES */
        /* Sidebar on small screens (Bootstrap 5 already helps, but for clarity) */
        @media (max-width: 768px) {
            .sidebar {
                padding-top: 56px;
                /* Offset for a fixed navbar/header if present */
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 100;
                width: 250px;
                display: none;
                /* Usually shown with a toggle on small screens */
            }

            .main-content {
                margin-left: 0 !important;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 sidebar">
                <?php include 'admin_sidebar.php'; ?>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 d-flex justify-content-between align-items-center">
                    <h1 class="h2 text-dark" style="font-weight: 700;">👋 Welcome,
                        <?php echo h(isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Admin'); ?>!
                    </h1>
                    <button class="btn btn-primary"
                        style="background-color: var(--color-purple-vibrant); border-color: var(--color-purple-vibrant);"><i
                            class="bi bi-plus-circle me-2"></i>New Entry</button>
                </div>

                <div class="row g-4 mb-5">

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-red stat-card">
                            <i class="bi bi-chat-dots stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Inquiries</h5>
                                    <h2 class="mt-1"><?php echo $total_inquiries; ?></h2>
                                </div>
                                <small class="fw-bold"><?php echo $unread_inquiries; ?> NEW unread</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-blue stat-card">
                            <i class="bi bi-camera stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Portraits</h5>
                                    <h2 class="mt-1"><?php echo $total_portraits; ?></h2>
                                </div>
                                <small>Total Photos Listed</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-purple stat-card">
                            <i class="bi bi-film stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Videos</h5>
                                    <h2 class="mt-1"><?php echo $total_videos; ?></h2>
                                </div>
                                <small>Total Media Assets</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-green stat-card">
                            <i class="bi bi-star stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Testimonials</h5>
                                    <h2 class="mt-1"><?php echo $approved_testimonials; ?></h2>
                                </div>
                                <small>Approved Reviews</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-yellow stat-card">
                            <i class="bi bi-people stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Partners</h5>
                                    <h2 class="mt-1"><?php echo $total_partners; ?></h2>
                                </div>
                                <small>Companies Listed</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card bg-custom-pink stat-card">
                            <i class="bi bi-graph-up stat-icon"></i>
                            <div class="card-body">
                                <div>
                                    <h5 class="card-title">Projects</h5>
                                    <h2 class="mt-1">54</h2>
                                </div>
                                <small>Total Completed</small>
                            </div>
                        </div>
                    </div>

                </div>

                <hr>

                <div class="card table-card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Recent Inquiries (Last 5)</h5>
                        <a href="inquiries.php" class="btn btn-sm btn-outline-secondary">View All</a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-secondary" scope="col">Date</th>
                                        <th class="text-secondary" scope="col">Name</th>
                                        <th class="text-secondary" scope="col">Email</th>
                                        <th class="text-secondary" scope="col">Type</th>
                                        <th class="text-secondary" scope="col">Status</th>
                                        <th class="text-secondary" scope="col">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recent_inquiries)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-3">No recent inquiries found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_inquiries as $inquiry): ?>
                                            <tr class="<?php echo $inquiry['is_read'] ? '' : 'table-warning bg-opacity-10'; ?>">
                                                <td><?php echo date('M d, Y', strtotime($inquiry['submission_date'])); ?></td>
                                                <td class="fw-bold"><?php echo h($inquiry['full_name']); ?></td>
                                                <td><?php echo h($inquiry['email']); ?></td>
                                                <td><span
                                                        class="badge rounded-pill bg-primary"><?php echo h($inquiry['inquiry_type']); ?></span>
                                                </td>
                                                <td>
                                                    <?php if ($inquiry['is_read']): ?>
                                                        <span class="badge bg-success"><i
                                                                class="bi bi-check-circle-fill me-1"></i>Read</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><i
                                                                class="bi bi-exclamation-circle-fill me-1"></i>New</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $inquiry['inquiry_id']; ?>"
                                                        class="btn btn-sm btn-outline-dark" title="View Inquiry"><i
                                                            class="bi bi-eye"></i> View</a>
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