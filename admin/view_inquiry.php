<?php
require_once '../config.php';

// Authentication Check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$inquiry = null;
$error = '';
$inquiry_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

if ($inquiry_id === 0) {
    $error = 'Invalid inquiry ID.';
} else {
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

// Helper function to escape HTML output (assuming it's available globally)
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            /* Theme Colors - Sophisticated and Approachable */
            --color-primary-elegant: #8B5CF6; /* Soft Lavender/Purple */
            --color-text-dark: #333333;       /* Charcoal Black for better readability */
            --color-success-vibrant: #4ED460;
            --color-danger-vibrant: #FF6B6B;
            --bg-light: #f4f6f9;              /* Very light grey background */
            --color-type-badge: #A78BFA;      
            --card-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--color-text-dark);
        }
        
        /* Main Content Area */
        .main-content {
            padding-left: 2rem;
            padding-right: 2rem;
            padding-top: 2.5rem; 
        }

        /* Header Styling */
        .page-header {
            margin-bottom: 2rem;
        }
        .page-header h1 {
            font-weight: 800;
            color: var(--color-text-dark);
        }

        /* Card Styling */
        .card {
            border-radius: 18px; /* Increased rounding */
            box-shadow: var(--card-shadow); 
            border: none;
            overflow: hidden; /* Ensures header border radius is respected */
        }
        
        .card-header {
            background-color: #ffffff; /* Clean white header */
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem 2rem;
        }
        
        .card-header h5 {
            font-weight: 700;
            color: var(--color-text-dark);
        }

        /* Data List Styling */
        .dl-horizontal dt {
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .dl-horizontal dd {
            margin-bottom: 1rem;
            font-weight: 500;
        }

        /* Message Block Styling */
        .message-box {
            white-space: pre-wrap;
            background-color: #ffffff; /* White background for the main content block */
            border-radius: 12px;
            border: 1px solid #e9ecef;
            padding: 1.5rem;
            font-size: 0.95rem;
        }
        
        /* Status Badges */
        .badge.bg-success {
            background-color: var(--color-success-vibrant) !important;
            font-weight: 600;
        }

        .badge.bg-danger {
            background-color: var(--color-danger-vibrant) !important;
            font-weight: 600;
        }
        
        /* Action Buttons */
        .btn-reply {
            background-color: var(--color-primary-elegant); 
            border-color: var(--color-primary-elegant);
            color: #fff;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.6rem 1.2rem;
            transition: all 0.2s ease;
        }
        .btn-reply:hover {
            background-color: #6D3FE6; 
            border-color: #6D3FE6;
            transform: translateY(-1px);
        }
        
        /* Back Button - Soft Outline */
        .btn-back {
            color: var(--color-primary-elegant);
            border: 1px solid var(--color-primary-elegant);
            background-color: transparent;
            border-radius: 10px;
            transition: all 0.2s ease;
        }
        .btn-back:hover {
            background-color: var(--color-primary-elegant);
            color: #fff;
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar placeholder -->
            <div class="col-md-3 col-lg-2 sidebar-wrapper">
                <?php include 'admin_sidebar.php'; ?>
            </div>

            <main class="col-md-9 ms-sm-auto col-lg-10 main-content">
                <div class="page-header d-flex justify-content-between align-items-center">
                    <!-- Title using the primary accent color icon -->
                    <h1 class="h2">
                        <i class="bi bi-person-lines-fill me-2" style="color: var(--color-primary-elegant);"></i>
                        Inquiry Detail #<?php echo h($inquiry_id); ?>
                    </h1>
                    <!-- Back Button with new style -->
                    <a href="inquiries.php" class="btn btn-back"><i class="bi bi-arrow-left"></i> Back to Inbox</a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3"><?php echo h($error); ?></div>
                <?php elseif ($inquiry): ?>
                    <div class="card mb-5">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <!-- Header emphasizes sender and status -->
                            <h5 class="mb-0">
                                Message from: **<?php echo h($inquiry['full_name']); ?>**
                            </h5>
                            <span class="badge rounded-pill bg-<?php echo $inquiry['is_read'] ? 'success' : 'danger'; ?>">
                                <?php echo $inquiry['is_read'] ? 'Read' : 'Unread'; ?>
                            </span>
                        </div>
                        
                        <div class="card-body">
                            <!-- Information Block -->
                            <dl class="row dl-horizontal">
                                <dt class="col-sm-3">Date Submitted</dt>
                                <dd class="col-sm-9"><?php echo date('F j, Y, g:i a', strtotime($inquiry['submission_date'])); ?></dd>
                                
                                <dt class="col-sm-3">Email</dt>
                                <dd class="col-sm-9"><a href="mailto:<?php echo h($inquiry['email']); ?>" class="text-decoration-none text-primary"><?php echo h($inquiry['email']); ?></a></dd>
                                
                                <dt class="col-sm-3">Phone</dt>
                                <dd class="col-sm-9"><?php echo h($inquiry['phone_number'] ?: 'N/A'); ?></dd>
                                
                                <dt class="col-sm-3">Type of Inquiry</dt>
                                <dd class="col-sm-9"><span class="badge rounded-pill" style="background-color: var(--color-type-badge);"><?php echo h($inquiry['inquiry_type']); ?></span></dd>
                            </dl>
                            
                            <hr class="my-4">
                            
                            <!-- Message Content -->
                            <h6 class="fw-bold mb-3">Client Message:</h6>
                            <div class="message-box">
                                <?php echo h($inquiry['message']); ?>
                            </div>
                        </div>
                        
                        <div class="card-footer text-end p-4 bg-light">
                            <!-- Primary Action Button -->
                             <a href="mailto:<?php echo h($inquiry['email']); ?>" class="btn btn-reply"><i class="bi bi-reply me-1"></i> Send Reply Now</a>
                        </div>
                    </div>
                <?php endif; ?>

            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
