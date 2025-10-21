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

$total_inquiries = $unread_inquiries = $total_portraits = $total_videos = $total_partners = $approved_testimonials = $applicationCount = 0;
$recent_inquiries = [];
$read_inquiries = 0;
$inquiry_read_percent = 0;

try {
    $conn = getDBConnection();

    // Fetch counts
    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries");
    $total_inquiries = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE is_read = 0");
    $unread_inquiries = $stmt->fetch()['total'];
    
    // Calculate Read Inquiries
    $read_inquiries = $total_inquiries - $unread_inquiries;
    if ($total_inquiries > 0) {
        $inquiry_read_percent = round(($read_inquiries / $total_inquiries) * 100);
    }

    $stmt = $conn->query("SELECT COUNT(*) as total FROM portraits WHERE is_archived = 0");
    $total_portraits = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM videos WHERE is_archived = 0");
    $total_videos = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM partners WHERE is_archived = 0");
    $total_partners = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM testimonials WHERE is_approved = 1");
    $approved_testimonials = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT * FROM inquiries ORDER BY submission_date DESC LIMIT 5");
    $recent_inquiries = $stmt->fetchAll();

    $stmt = $conn->query("SELECT COUNT(*) as total FROM applications WHERE is_archived = 0");
    $applicationCount = $stmt->fetchColumn(); 

    // Placeholder data for the chart (In a real app, you'd fetch this from the DB, grouped by month)
    $chart_data = [
        'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        'data' => [20, 35, 25, 40, 45, 30, 50, 60, 45, 55, 65, 70]
    ];

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <?php include 'admin_header.php'; ?>

    <style>
        /* ... (Keep existing CSS styles) ... */

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

        .stat-card.card-applications { 
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
        
        /* New styles for analytic summary cards */
        .summary-card {
            background: var(--bg-secondary);
            border-radius: 12px;
            padding: 1.25rem;
            text-align: center;
            box-shadow: var(--shadow-sm);
        }

        .summary-value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .summary-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        .progress-bar-label {
            position: absolute;
            width: 100%;
            text-align: center;
            font-weight: 700;
            color: white;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.9rem;
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
                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-inquiries">
                            <div class="stat-icon"><i class="bi bi-chat-dots-fill"></i></div>
                            <div class="stat-value"><?php echo $total_inquiries; ?></div>
                            <div class="stat-label">Total Inquiries</div>
                            <?php if ($unread_inquiries > 0): ?>
                                <span class="stat-badge">New: <?php echo $unread_inquiries; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-portraits">
                            <div class="stat-icon"><i class="bi bi-person-bounding-box"></i></div>
                            <div class="stat-value"><?php echo $total_portraits; ?></div>
                            <div class="stat-label">Portraits</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-videos">
                            <div class="stat-icon"><i class="bi bi-film"></i></div>
                            <div class="stat-value"><?php echo $total_videos; ?></div>
                            <div class="stat-label">Videos</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-testimonials">
                            <div class="stat-icon"><i class="bi bi-star-fill"></i></div>
                            <div class="stat-value"><?php echo $approved_testimonials; ?></div>
                            <div class="stat-label">Testimonials</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-partners">
                            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                            <div class="stat-value"><?php echo $total_partners; ?></div>
                            <div class="stat-label">Partners</div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-4 col-lg-3 col-xl-2">
                        <div class="stat-card card-applications">
                            <div class="stat-icon"><i class="bi bi-briefcase-fill"></i></div>
                            <div class="stat-value"><?php echo $applicationCount; ?></div>
                            <div class="stat-label">Applications</div>
                        </div>
                    </div>
                </div>

                <h3 class="h5 fw-bold mb-3 mt-4 text-secondary">Inquiry Performance Analytics</h3>
                <div class="row g-4 mb-5">
                    
                    <div class="col-lg-8">
                        <div class="card table-card h-100">
                            <div class="card-header">
                                Monthly Inquiry Volume
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyInquiriesChart" style="height: 300px;"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="d-flex flex-column h-100">
                            <div class="card table-card mb-4 p-3 flex-grow-1" style="background: var(--bg-tertiary);">
                                <div class="card-title h6 fw-bold text-uppercase text-secondary">Response Rate</div>
                                <div class="progress mb-3" style="height: 30px; position: relative;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $inquiry_read_percent; ?>%" 
                                         aria-valuenow="<?php echo $inquiry_read_percent; ?>" aria-valuemin="0" 
                                         aria-valuemax="100">
                                    </div>
                                    <span class="progress-bar-label"><?php echo $inquiry_read_percent; ?>% Read</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="summary-card">
                                        <div class="summary-value text-danger"><?php echo $unread_inquiries; ?></div>
                                        <div class="summary-label">Unread</div>
                                    </div>
                                    <div class="summary-card">
                                        <div class="summary-value text-success"><?php echo $read_inquiries; ?></div>
                                        <div class="summary-label">Read</div>
                                    </div>
                                    <div class="summary-card">
                                        <div class="summary-value text-primary"><?php echo $total_inquiries; ?></div>
                                        <div class="summary-label">Total</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card table-card flex-grow-1 p-3">
                                <div class="card-title h6 fw-bold text-uppercase text-secondary">Placeholder Metric</div>
                                <p class="text-muted small">This space can be used for a pie chart of inquiry types (Portrait vs Video) or a quick link to a full report.</p>
                            </div>
                        </div>
                    </div>
                </div> 
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
        function updateClock() {
            const now = new Date();
            
            // Format Time (12-hour format)
            const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
            const timeString = now.toLocaleTimeString('en-US', timeOptions);
            document.getElementById('adminClockTime').textContent = timeString;

            // Format Date
            const dateOptions = { weekday: 'short', month: 'short', day: 'numeric' };
            const dateString = now.toLocaleDateString('en-US', dateOptions);
            document.getElementById('adminClockDate').textContent = dateString;
        }

        // Run the function immediately, then update every second
        updateClock();
        setInterval(updateClock, 1000);updateClock

        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('monthlyInquiriesChart').getContext('2d');
            const chartData = {
                labels: <?php echo json_encode($chart_data['labels']); ?>,
                datasets: [{
                    label: 'New Inquiries',
                    data: <?php echo json_encode($chart_data['data']); ?>,
                    backgroundColor: 'rgba(78, 205, 196, 0.5)', // Matches the Portrait/Video card color
                    borderColor: '#4ECDC4',
                    borderWidth: 2,
                    borderRadius: 4,
                    tension: 0.3,
                    fill: true
                }]
            };

            new Chart(ctx, {
                type: 'line',
                data: chartData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false,
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 12 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Volume'
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)',
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
                
            });
            
        });
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>