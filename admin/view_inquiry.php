<?php
require_once '../config.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$inquiry = null;
$error = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $error = 'Invalid inquiry ID.';
} else {
    $inquiry_id = (int)$_GET['id'];
    
    try {
        $conn = getDBConnection();
        
        // 1. Fetch the inquiry details
        $stmt = $conn->prepare("SELECT * FROM inquiries WHERE inquiry_id = :id");
        $stmt->execute([':id' => $inquiry_id]);
        $inquiry = $stmt->fetch();
        
        if (!$inquiry) {
            $error = 'Inquiry not found.';
        } else {
            // 2. Automatically mark the inquiry as read if it isn't already
            if ($inquiry['is_read'] == 0) {
                $stmt = $conn->prepare("UPDATE inquiries SET is_read = 1 WHERE inquiry_id = :id");
                $stmt->execute([':id' => $inquiry_id]);
                // Re-fetch the data to update the status on the page
                $inquiry['is_read'] = 1; 
            }
        }
        
    } catch(PDOException $e) {
        error_log("View Inquiry error: " . $e->getMessage());
        $error = 'Database error: Could not load inquiry details.';
    }
}

// Helper function to escape HTML output (assuming it's available globally, 
// if not, you must define it here or in config.php)
if (!function_exists('h')) {
    function h($str) { return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Inquiry #<?php echo h($inquiry_id); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2">View Inquiry #<?php echo h($inquiry_id); ?></h1>
                    <a href="inquiries.php" class="btn btn-secondary btn-sm mt-2"><i class="bi bi-arrow-left"></i> Back to Inquiries</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo h($error); ?></div>
                <?php elseif ($inquiry): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">
                                Inquiry from: **<?php echo h($inquiry['full_name']); ?>** <span class="badge bg-<?php echo $inquiry['is_read'] ? 'success' : 'danger'; ?> float-end">
                                    <?php echo $inquiry['is_read'] ? 'Read' : 'Unread'; ?>
                                </span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-sm-3">Date Submitted</dt>
                                <dd class="col-sm-9"><?php echo date('F j, Y, g:i a', strtotime($inquiry['submission_date'])); ?></dd>
                                
                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9"><a href="mailto:<?php echo h($inquiry['email']); ?>"><?php echo h($inquiry['email']); ?></a></dd>
                                
                                <dt class="col-sm-3">Phone</dt>
                                <dd class="col-sm-9"><?php echo h($inquiry['phone_number'] ?: 'N/A'); ?></dd>
                                
                                <dt class="col-sm-3">Type of Inquiry</dt>
                                <dd class="col-sm-9"><?php echo h($inquiry['inquiry_type']); ?></dd>
                            </dl>
                            
                            <hr>
                            
                            <h6>Message:</h6>
                            <div class="p-3 border rounded" style="white-space: pre-wrap; background-color: #f8f9fa;">
                                <?php echo h($inquiry['message']); ?>
                            </div>
                        </div>
                        <div class="card-footer text-end">
                             <a href="mailto:<?php echo h($inquiry['email']); ?>" class="btn btn-success"><i class="bi bi-reply"></i> Reply to Inquiry</a>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>