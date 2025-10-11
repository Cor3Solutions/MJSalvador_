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
        // Use 0 for 'mark_unread', 1 for 'mark_read'
        $is_read = ($_GET['action'] == 'mark_read') ? 1 : 0; 
        
        $stmt = $conn->prepare("UPDATE inquiries SET is_read = :is_read WHERE inquiry_id = :id");
        $stmt->execute([':is_read' => $is_read, ':id' => $inquiry_id]);
        
        // Redirect to clear GET parameters
        header('Location: inquiries.php');
        exit;
    }
    
    // Fetch all inquiries, newest first
    // Note: In a real-world application, adding pagination here would be essential!
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
    <title>Inbox: Client Inquiries - Admin Panel</title>
    <!-- Use a modern, highly readable font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Using Bootstrap Icons for a modern look -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        :root {
            /* Theme Colors - Sophisticated and Approachable */
            --color-primary-elegant: #8B5CF6; /* Soft Lavender/Purple */
            --color-text-dark: #333333;       /* Charcoal Black for better readability */
            --color-success-vibrant: #4ED460;
            --color-danger-vibrant: #FF6B6B;
            --bg-light: #f4f6f9;              /* Very light grey background */
            --color-unread-soft: rgba(139, 92, 246, 0.08); /* Light tint for unread rows */
            --color-type-badge: #A78BFA;      /* Lighter primary shade for badge */
            --card-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--color-text-dark);
        }

        /* Sidebar (assuming standard structure) */
        .sidebar-wrapper {
            background-color: #ffffff;
            border-right: 1px solid #e9ecef;
            min-height: 100vh;
            padding: 0;
        }
        
        .main-content {
            padding-left: 2rem;
            padding-right: 2rem;
            padding-top: 2.5rem; /* Increased top padding */
        }

        /* Header Styling */
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        .page-header h1 {
            font-weight: 800;
            color: var(--color-text-dark);
        }

        /* Card and Table Styling */
        .card {
            border-radius: 18px; /* Increased rounding */
            box-shadow: var(--card-shadow); /* Subtle, elegant shadow */
            border: none;
        }
        
        .table-responsive {
            border-radius: 18px; 
            overflow: hidden;
        }
        
        .table thead th {
            font-weight: 600;
            color: #6c757d;
            background-color: #ffffff; /* White header row for cleanliness */
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        /* Custom highlight for unread row */
        .table tbody tr.table-warning {
            background-color: var(--color-unread-soft) !important; 
            font-weight: 600;
        }

        .table-hover tbody tr:hover {
            background-color: #eff2f6;
        }
        
        .table tbody tr td {
            vertical-align: middle;
            color: var(--color-text-dark);
        }

        /* Status Badge Styling */
        .badge.bg-success {
            background-color: var(--color-success-vibrant) !important;
            color: #fff;
            font-weight: 600;
        }

        .badge.bg-danger {
            background-color: var(--color-danger-vibrant) !important;
            color: #fff;
            font-weight: 600;
        }
        
        /* Inquiry Type Badge (Primary accent) */
        .badge.bg-secondary {
            background-color: var(--color-type-badge) !important; 
            color: #fff;
            font-weight: 500;
            padding: 0.5em 0.8em; /* Slightly larger padding */
        }

        /* Action Button Styling */
        .btn-action-view {
            background-color: var(--color-primary-elegant); 
            border-color: var(--color-primary-elegant);
            color: #fff;
            border-radius: 8px; /* Rounded buttons */
            transition: all 0.2s ease;
        }
        .btn-action-view:hover {
            background-color: #6D3FE6; /* Slightly darker hover */
            border-color: #6D3FE6;
            color: #fff;
            transform: translateY(-1px);
        }
        
        /* Utility button styling */
        .btn-utility {
            border-radius: 8px;
            color: #6c757d;
            border: 1px solid #ced4da;
            transition: all 0.2s ease;
        }
        .btn-utility:hover {
            color: var(--color-primary-elegant);
            border-color: var(--color-primary-elegant);
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
                <div class="page-header">
                    <!-- Updated title to be more engaging -->
                    <h1 class="h2">
                        <i class="bi bi-inbox me-2" style="color: var(--color-primary-elegant);"></i>
                        Client Inbox: Review & Manage Inquiries
                    </h1>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-3"><?php echo h($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <!-- Adjusted header labels for clarity and conciseness -->
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Sender Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Inquiry Type</th>
                                        <th>Status</th>
                                        <th class="text-center">Quick Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($inquiries)): ?>
                                        <tr><td colspan="8" class="text-center text-muted py-4">🎉 All clear! No new inquiries at the moment.</td></tr>
                                    <?php else: ?>
                                        <?php foreach($inquiries as $i): ?>
                                            <!-- Highlight row visually if unread -->
                                            <tr class="<?php echo $i['is_read'] ? '' : 'table-warning'; ?>">
                                                <td><?php echo $i['inquiry_id']; ?></td>
                                                <td><?php echo date('M j, Y', strtotime($i['submission_date'])); ?></td>
                                                
                                                <!-- Make name bold and clickable to view the full inquiry -->
                                                <td>
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>" class="text-dark fw-bold text-decoration-none">
                                                        <?php echo h($i['full_name']); ?>
                                                    </a>
                                                </td>
                                                
                                                <td><?php echo h($i['email']); ?></td>
                                                <td><?php echo h($i['phone_number'] ?: '-'); ?></td>
                                                
                                                <!-- Type Badge -->
                                                <td><span class="badge rounded-pill bg-secondary"><?php echo h($i['inquiry_type']); ?></span></td>
                                                
                                                <!-- Status Badge -->
                                                <td>
                                                    <span class="badge rounded-pill bg-<?php echo $i['is_read'] ? 'success' : 'danger'; ?>">
                                                        <?php echo $i['is_read'] ? 'Read' : 'Unread'; ?>
                                                    </span>
                                                </td>
                                                
                                                <td class="text-center d-flex justify-content-center align-items-center">
                                                    <!-- View Button (Primary Action) -->
                                                    <a href="view_inquiry.php?id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-action-view me-2" title="View Full Message">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    
                                                    <?php if($i['is_read']): ?>
                                                        <!-- Mark Unread Button (Utility/Subtle) -->
                                                        <a href="?action=mark_unread&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-utility" title="Mark as Unread">
                                                            <i class="bi bi-envelope"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <!-- Mark Read Button (Primary utility for unread row) -->
                                                        <a href="?action=mark_read&id=<?php echo $i['inquiry_id']; ?>" class="btn btn-sm btn-success" title="Mark as Read">
                                                            <i class="bi bi-check-lg"></i>
                                                        </a>
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
