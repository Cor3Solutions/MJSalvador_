<?php
require_once '../config.php';

// Define HTML escaping helper function if not already in config.php
// It is crucial for security!
if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$inquiries = [];
$error = '';

try {
    $conn = getDBConnection();
    
    // Logic to handle marking as read/unread (via GET request)
    if (isset($_GET['action'], $_GET['id'])) {
        $inquiry_id = (int)$_GET['id'];
        $is_read = ($_GET['action'] == 'mark_read') ? 1 : 0;
        
        $stmt = $conn->prepare("UPDATE inquiries SET is_read = :is_read WHERE inquiry_id = :id");
        $stmt->execute([':is_read' => $is_read, ':id' => $inquiry_id]);
        
        // Redirect to clear GET parameters
        header('Location: inquiries.php');
        exit;
    }
    
    // Fetch all inquiries, newest first
    // Note: Assuming 'phone_number' column exists in your 'inquiries' table
    $stmt = $conn->query("SELECT * FROM inquiries ORDER BY submission_date DESC");
    $inquiries = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Inquiries error: " . $e->getMessage());
    $error = 'Database error: Could not load inquiries.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Inquiries</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .table-hover a {
            text-decoration: none;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">Manage Inquiries</h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th> <!-- ADDED PHONE COLUMN -->
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($inquiries)): ?>
                                        <!-- Updated colspan to 8 to match the new column count -->
                                        <tr><td colspan="8" class="text-center text-muted">No inquiries found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($inquiries as $i): ?>
                                            <tr class="<?php echo $i['is_read'] ? '' : 'table-warning'; ?>">
                                                <td><?php echo $i['inquiry_id']; ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($i['submission_date'])); ?></td>
                                                
                                                <!-- Make name a clickable link to view the full inquiry -->
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>">
                                                        <?php echo h($i['full_name']); ?>
                                                    </a>
                                                </td>
                                                
                                                <td><?php echo h($i['email']); ?></td>
                                                <td><?php echo h($i['phone_number'] ?: '-'); ?></td> <!-- Display phone number -->
                                                <td><?php echo h($i['inquiry_type']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $i['is_read'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $i['is_read'] ? 'Read' : 'Unread'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-primary text-white me-1" title="View Message">View</a>
                                                    <?php if($i['is_read']): ?>
                                                        <a href="?action=mark_unread&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-outline-secondary" title="Mark Unread"><i class="bi bi-eye-slash"></i></a>
                                                    <?php else: ?>
                                                        <a href="?action=mark_read&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-success" title="Mark Read"><i class="bi bi-check-circle"></i></a>
                                                    <?php endif; ?>
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
