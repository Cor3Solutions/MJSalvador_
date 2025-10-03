<?php
require_once '../config.php';

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

    // Correct table name 'videos' and column match
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">  
</head>

<body>
    <?php include 'admin_header.php'; ?>
 
    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?> 
            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4">
                    <h1 class="h2">Dashboard Summary</h1>
                </div>

                <div class="row g-4 mb-5">

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card text-white bg-custom-primary stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Inquiries</h5>
                                <h2><?php echo $total_inquiries; ?></h2>
                                <small><?php echo $unread_inquiries; ?> unread</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card text-white bg-custom-secondary stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Portraits</h5>
                                <h2><?php echo $total_portraits; ?></h2>
                                <small>Total photos</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card text-white bg-custom-info stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Videos</h5>
                                <h2><?php echo $total_videos; ?></h2>
                                <small>Total media</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card text-white bg-custom-success stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Testimonials</h5>
                                <h2><?php echo $approved_testimonials; ?></h2>
                                <small>Approved reviews</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-4 col-lg-2">
                        <div class="card text-white bg-custom-warning stat-card">
                            <div class="card-body">
                                <h5 class="card-title">Partners</h5>
                                <h2><?php echo $total_partners; ?></h2>
                                <small>Companies listed</small>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="card table-card mb-4">
                    <div class="card-header">
                        <h5>Recent Inquiries (Last 5)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-borderless">
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
                                            <td colspan="6" class="text-center text-muted py-3">No recent inquiries found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($recent_inquiries as $inquiry): ?>
                                            <tr class="<?php echo $inquiry['is_read'] ? '' : 'table-warning bg-opacity-10'; ?>">
                                                <td><?php echo date('M d, Y', strtotime($inquiry['submission_date'])); ?></td>
                                                <td><?php echo h($inquiry['full_name']); ?></td>
                                                <td><?php echo h($inquiry['email']); ?></td>
                                                <td><?php echo h($inquiry['inquiry_type']); ?></td>
                                                <td>
                                                    <?php if ($inquiry['is_read']): ?>
                                                        <span class="badge bg-success">Read</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">New</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $inquiry['inquiry_id']; ?>"
                                                        class="btn btn-sm btn-outline-secondary">View</a>
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