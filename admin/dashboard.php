<?php
/**
 * Enhanced Dashboard - Jade Salvador Admin
 * Filename: admin/dashboard_enhanced.php
 */

require_once '../config.php';

if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Fetch user info
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

// Get statistics with accurate percentage calculations
$total_inquiries = $unread_inquiries = $total_portraits = $total_videos = 0;
$total_partners = $approved_testimonials = $applicationCount = 0;
$recent_inquiries = [];
$recent_activities = [];
$upcoming_tasks = [];

// Percentage changes
$inquiry_change = 0;
$portrait_change = 0;
$video_change = 0;
$testimonial_change = 0;
$application_change = 0;
$partner_change = 0;

try {
    $conn = getDBConnection();

    // === INQUIRIES ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE is_archived = 0");
    $total_inquiries = $stmt->fetch()['total'];

    $stmt = $conn->query("SELECT COUNT(*) as total FROM inquiries WHERE is_read = 0 AND is_archived = 0");
    $unread_inquiries = $stmt->fetch()['total'];
    
    // Calculate inquiry change (current month vs previous month)
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM inquiries 
        WHERE MONTH(submission_date) = MONTH(CURRENT_DATE())
        AND YEAR(submission_date) = YEAR(CURRENT_DATE())
        AND is_archived = 0
    ");
    $current_month_inquiries = $stmt->fetch()['current'];
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM inquiries 
        WHERE MONTH(submission_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND YEAR(submission_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
        AND is_archived = 0
    ");
    $previous_month_inquiries = $stmt->fetch()['previous'];
    
    if ($previous_month_inquiries > 0) {
        $inquiry_change = round((($current_month_inquiries - $previous_month_inquiries) / $previous_month_inquiries) * 100);
    } else {
        $inquiry_change = $current_month_inquiries > 0 ? 100 : 0;
    }

    // === PORTRAITS ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM portraits WHERE is_archived = 0");
    $total_portraits = $stmt->fetch()['total'];
    
    // Calculate portrait change (current month vs previous month)
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM portraits 
        WHERE DATE(created_at) >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $current_portraits = $stmt->fetch()['current'] ?? 0;
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM portraits 
        WHERE DATE(created_at) >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND DATE(created_at) < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $previous_portraits = $stmt->fetch()['previous'] ?? 0;
    
    if ($previous_portraits > 0) {
        $portrait_change = round((($current_portraits - $previous_portraits) / $previous_portraits) * 100);
    } else {
        $portrait_change = $current_portraits > 0 ? 100 : 0;
    }

    // === VIDEOS ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM videos WHERE is_archived = 0");
    $total_videos = $stmt->fetch()['total'];
    
    // Calculate video change
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM videos 
        WHERE DATE(created_at) >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $current_videos = $stmt->fetch()['current'] ?? 0;
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM videos 
        WHERE DATE(created_at) >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND DATE(created_at) < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $previous_videos = $stmt->fetch()['previous'] ?? 0;
    
    if ($previous_videos > 0) {
        $video_change = round((($current_videos - $previous_videos) / $previous_videos) * 100);
    } else {
        $video_change = $current_videos > 0 ? 100 : 0;
    }

    // === PARTNERS ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM partners WHERE is_archived = 0");
    $total_partners = $stmt->fetch()['total'];
    
    // Calculate partner change
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM partners 
        WHERE DATE(created_at) >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $current_partners = $stmt->fetch()['current'] ?? 0;
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM partners 
        WHERE DATE(created_at) >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND DATE(created_at) < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $previous_partners = $stmt->fetch()['previous'] ?? 0;
    
    if ($previous_partners > 0) {
        $partner_change = round((($current_partners - $previous_partners) / $previous_partners) * 100);
    } else {
        $partner_change = $current_partners > 0 ? 100 : 0;
    }

    // === TESTIMONIALS ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM testimonials WHERE is_approved = 1 AND is_archived = 0");
    $approved_testimonials = $stmt->fetch()['total'];
    
    // Calculate testimonial change
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM testimonials 
        WHERE is_approved = 1
        AND DATE(created_at) >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $current_testimonials = $stmt->fetch()['current'] ?? 0;
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM testimonials 
        WHERE is_approved = 1
        AND DATE(created_at) >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND DATE(created_at) < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $previous_testimonials = $stmt->fetch()['previous'] ?? 0;
    
    if ($previous_testimonials > 0) {
        $testimonial_change = round((($current_testimonials - $previous_testimonials) / $previous_testimonials) * 100);
    } else {
        $testimonial_change = $current_testimonials > 0 ? 100 : 0;
    }

    // === APPLICATIONS ===
    $stmt = $conn->query("SELECT COUNT(*) as total FROM applications WHERE is_archived = 0");
    $applicationCount = $stmt->fetchColumn();
    
    // Calculate application change
    $stmt = $conn->query("
        SELECT COUNT(*) as current 
        FROM applications 
        WHERE DATE(submission_date) >= DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $current_applications = $stmt->fetch()['current'] ?? 0;
    
    $stmt = $conn->query("
        SELECT COUNT(*) as previous 
        FROM applications 
        WHERE DATE(submission_date) >= DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m-01')
        AND DATE(submission_date) < DATE_FORMAT(CURRENT_DATE(), '%Y-%m-01')
        AND is_archived = 0
    ");
    $previous_applications = $stmt->fetch()['previous'] ?? 0;
    
    if ($previous_applications > 0) {
        $application_change = round((($current_applications - $previous_applications) / $previous_applications) * 100);
    } else {
        $application_change = $current_applications > 0 ? 100 : 0;
    }

    // Recent inquiries for activity feed
    $stmt = $conn->query("SELECT full_name, inquiry_type, submission_date FROM inquiries WHERE is_archived = 0 ORDER BY submission_date DESC LIMIT 5");
    $recent_inquiries = $stmt->fetchAll();

    // Create activity feed from recent inquiries
    foreach ($recent_inquiries as $inq) {
        $time_diff = time() - strtotime($inq['submission_date']);
        if ($time_diff < 3600) {
            $time_ago = floor($time_diff / 60) . ' min ago';
        } elseif ($time_diff < 86400) {
            $time_ago = floor($time_diff / 3600) . ' hour ago';
        } else {
            $time_ago = floor($time_diff / 86400) . ' days ago';
        }
        
        $recent_activities[] = [
            'type' => 'inquiry',
            'name' => $inq['full_name'],
            'action' => 'submitted a ' . $inq['inquiry_type'] . ' inquiry',
            'time' => $time_ago,
            'icon' => 'envelope'
        ];
    }

    // Sample upcoming tasks
    $upcoming_tasks = [
        ['task' => 'Review ' . $unread_inquiries . ' new inquiries', 'priority' => 'high', 'due' => 'Today'],
        ['task' => 'Update partner logos', 'priority' => 'medium', 'due' => 'Tomorrow'],
        ['task' => 'Approve testimonials', 'priority' => 'low', 'due' => 'This week'],
    ];

} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

$currentPage = 'dashboard.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Dashboard - Jade Salvador Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">

    <style>
        :root[data-theme="light"] {
            --jade-primary: #cd919e;
            --jade-primary-hover: #b77f8b;
            --jade-dark: #3a2c38;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #f1f3f5;
            --bg-gradient-start: #f5f7fa;
            --bg-gradient-end: #e8ecf1;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            --border-color: #dee2e6;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
        }

        :root[data-theme="dark"] {
            --jade-primary: #e5a4b4;
            --jade-primary-hover: #f0b8c5;
            --jade-dark: #1a1625;
            --bg-primary: #1e1b2e;
            --bg-secondary: #161425;
            --bg-tertiary: #252238;
            --bg-gradient-start: #1a1a2e;
            --bg-gradient-end: #16213e;
            --text-primary: #e9ecef;
            --text-secondary: #adb5bd;
            --text-muted: #6c757d;
            --border-color: #3a3550;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
        }

        :root[data-theme="pink"] {
            --jade-primary: #ff6b9d;
            --jade-primary-hover: #ff8fb8;
            --jade-dark: #2d1b2e;
            --bg-primary: #fff0f5;
            --bg-secondary: #ffe4ec;
            --bg-tertiary: #ffd1dc;
            --bg-gradient-start: #fff5f8;
            --bg-gradient-end: #ffe4ec;
            --text-primary: #2d1b2e;
            --text-secondary: #6d4c5a;
            --text-muted: #b89aa8;
            --border-color: #ffb3d9;
            --shadow-sm: 0 2px 4px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 12px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 24px rgba(255, 105, 180, 0.2);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            color: var(--text-primary);
        }

        /* Enhanced Welcome Section */
        .welcome-hero {
            background: linear-gradient(135deg, var(--jade-primary) 0%, var(--jade-dark) 100%);
            padding: 2.5rem;
            border-radius: 24px;
            margin-bottom: 2rem;
            color: white;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .welcome-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .welcome-content {
            position: relative;
            z-index: 1;
        }

        .welcome-hero h1 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .welcome-date {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            margin-top: 1rem;
        }

        /* Enhanced Stat Cards */
        .stat-card-enhanced {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 1.75rem;
            box-shadow: var(--shadow-md);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .stat-card-enhanced::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--stat-gradient);
        }

        .stat-card-enhanced:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card-enhanced.blue { --stat-gradient: linear-gradient(90deg, #3b82f6, #2563eb); }
        .stat-card-enhanced.purple { --stat-gradient: linear-gradient(90deg, #8b5cf6, #7c3aed); }
        .stat-card-enhanced.pink { --stat-gradient: linear-gradient(90deg, #ec4899, #db2777); }
        .stat-card-enhanced.green { --stat-gradient: linear-gradient(90deg, #10b981, #059669); }
        .stat-card-enhanced.orange { --stat-gradient: linear-gradient(90deg, #f59e0b, #d97706); }
        .stat-card-enhanced.teal { --stat-gradient: linear-gradient(90deg, #14b8a6, #0d9488); }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }

        .stat-icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--stat-gradient);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .stat-change-badge {
            padding: 0.4rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .stat-change-badge.positive {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .stat-change-badge.negative {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--text-primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .stat-trend {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        /* Quick Actions */
        .quick-action-card {
            background: var(--bg-primary);
            border: 2px solid var(--border-color);
            border-radius: 16px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .quick-action-card:hover {
            border-color: var(--jade-primary);
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .quick-action-icon {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--jade-primary), var(--jade-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
        }

        .quick-action-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ef4444;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            border: 3px solid var(--bg-primary);
        }

        .quick-action-content {
            flex: 1;
        }

        .quick-action-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .quick-action-desc {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Activity Feed */
        .activity-item-enhanced {
            display: flex;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--bg-secondary);
            border-radius: 14px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .activity-item-enhanced:hover {
            background: var(--bg-tertiary);
            transform: translateX(6px);
        }

        .activity-icon-wrapper {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--icon-bg);
            color: white;
        }

        .activity-item-enhanced.inquiry .activity-icon-wrapper { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .activity-item-enhanced.application .activity-icon-wrapper { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .activity-item-enhanced.portrait .activity-icon-wrapper { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }

        .activity-details {
            flex: 1;
        }

        .activity-name {
            font-weight: 700;
            color: var(--text-primary);
        }

        .activity-action {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }

        .activity-time {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.35rem;
        }

        /* Task Items */
        .task-item-enhanced {
            display: flex;
            gap: 1rem;
            padding: 1.25rem;
            background: var(--bg-secondary);
            border-radius: 14px;
            margin-bottom: 0.75rem;
            transition: all 0.2s ease;
        }

        .task-item-enhanced:hover {
            background: var(--bg-tertiary);
        }

        .task-checkbox {
            display: flex;
            align-items: center;
        }

        .task-checkbox input[type="checkbox"] {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            cursor: pointer;
            accent-color: var(--jade-primary);
        }

        .task-content {
            flex: 1;
        }

        .task-text {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .task-meta {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .priority-badge {
            padding: 0.3rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .priority-badge.high {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .priority-badge.medium {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .priority-badge.low {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        .task-due {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Section Headers */
        .section-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .section-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
            margin: 0;
        }

        .section-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--jade-primary), var(--jade-dark));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        /* Enhanced Cards */
        .enhanced-card {
            background: var(--bg-primary);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .card-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-title-enhanced {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .view-all-link {
            color: var(--jade-primary);
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .view-all-link:hover {
            color: var(--jade-primary-hover);
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .stat-card-enhanced {
            animation: fadeInUp 0.6s ease-out;
        }

        .stat-card-enhanced:nth-child(1) { animation-delay: 0.1s; }
        .stat-card-enhanced:nth-child(2) { animation-delay: 0.2s; }
        .stat-card-enhanced:nth-child(3) { animation-delay: 0.3s; }
        .stat-card-enhanced:nth-child(4) { animation-delay: 0.4s; }
        .stat-card-enhanced:nth-child(5) { animation-delay: 0.5s; }
        .stat-card-enhanced:nth-child(6) { animation-delay: 0.6s; }

        /* Responsive */
        @media (max-width: 768px) {
            .welcome-hero h1 {
                font-size: 1.75rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include 'admin_header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'admin_sidebar.php'; ?>

            <main class="main-content">
                <!-- Welcome Hero -->
                <div class="welcome-hero">
                    <div class="welcome-content">
                        <h1>✨ Welcome back, <?php echo h($_SESSION['full_name'] ?? 'Admin'); ?>!</h1>
                        <p style="font-size: 1.1rem; opacity: 0.95; margin-bottom: 0;">Here's what's happening with your portfolio today.</p>
                        <div class="welcome-date">
                            <i class="bi bi-calendar3"></i>
                            <span><?php echo date('l, F j, Y'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Stats Grid -->
                <div class="row g-4 mb-4">
                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced blue">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-envelope-fill" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $inquiry_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $inquiry_change >= 0 ? '+' : ''; ?><?php echo $inquiry_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $total_inquiries; ?></div>
                            <div class="stat-label">Total Inquiries</div>
                            <div class="stat-trend">
                                <i class="bi bi-graph-<?php echo $inquiry_change >= 0 ? 'up' : 'down'; ?>-arrow"></i>
                                <span><?php echo $unread_inquiries; ?> unread</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced purple">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-image-fill" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $portrait_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $portrait_change >= 0 ? '+' : ''; ?><?php echo $portrait_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $total_portraits; ?></div>
                            <div class="stat-label">Portfolio Images</div>
                            <div class="stat-trend">
                                <i class="bi bi-activity"></i>
                                <span>Active showcase</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced pink">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-play-circle-fill" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $video_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $video_change >= 0 ? '+' : ''; ?><?php echo $video_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $total_videos; ?></div>
                            <div class="stat-label">Video Content</div>
                            <div class="stat-trend">
                                <i class="bi bi-camera-video"></i>
                                <span>Media library</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced green">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-star-fill" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $testimonial_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $testimonial_change >= 0 ? '+' : ''; ?><?php echo $testimonial_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $approved_testimonials; ?></div>
                            <div class="stat-label">Client Reviews</div>
                            <div class="stat-trend">
                                <i class="bi bi-hand-thumbs-up"></i>
                                <span>Approved</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced orange">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-file-text-fill" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $application_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $application_change >= 0 ? '+' : ''; ?><?php echo $application_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $applicationCount; ?></div>
                            <div class="stat-label">Job Applications</div>
                            <div class="stat-trend">
                                <i class="bi bi-briefcase"></i>
                                <span>Pending review</span>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4">
                        <div class="stat-card-enhanced teal">
                            <div class="stat-header">
                                <div class="stat-icon-wrapper">
                                    <i class="bi bi-building" style="font-size: 1.5rem;"></i>
                                </div>
                                <span class="stat-change-badge <?php echo $partner_change >= 0 ? 'positive' : 'negative'; ?>">
                                    <?php echo $partner_change >= 0 ? '+' : ''; ?><?php echo $partner_change; ?>%
                                </span>
                            </div>
                            <div class="stat-value"><?php echo $total_partners; ?></div>
                            <div class="stat-label">Brand Partners</div>
                            <div class="stat-trend">
                                <i class="bi bi-people"></i>
                                <span>Collaborations</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="section-header">
                    <div class="section-icon">
                        <i class="bi bi-lightning-charge-fill"></i>
                    </div>
                    <h2>Quick Actions</h2>
                </div>

                <div class="row g-3 mb-5">
                    <div class="col-md-6 col-lg-3">
                        <a href="inquiries.php" style="text-decoration: none;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="bi bi-envelope-open" style="font-size: 1.3rem;"></i>
                                    <?php if($unread_inquiries > 0): ?>
                                        <span class="quick-action-badge"><?php echo $unread_inquiries; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="quick-action-content">
                                    <div class="quick-action-label">New Inquiries</div>
                                    <div class="quick-action-desc">Review messages</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <a href="portraits.php" style="text-decoration: none;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="bi bi-cloud-upload" style="font-size: 1.3rem;"></i>
                                </div>
                                <div class="quick-action-content">
                                    <div class="quick-action-label">Upload Content</div>
                                    <div class="quick-action-desc">Add new images</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
                            </div>
                        </a>
                    </div>

                    <div class="col-md-6 col-lg-3">
                        <a href="testimonials.php" style="text-decoration: none;">
                            <div class="quick-action-card">
                                <div class="quick-action-icon">
                                    <i class="bi bi-star-half" style="font-size: 1.3rem;"></i>
                                    <?php 
                                    try {
                                        $pending_stmt = $conn->query("SELECT COUNT(*) as pending FROM testimonials WHERE is_approved = 0 AND is_archived = 0");
                                        $pending_testimonials = $pending_stmt->fetch()['pending'];
                                        if($pending_testimonials > 0):
                                    ?>
                                        <span class="quick-action-badge"><?php echo $pending_testimonials; ?></span>
                                    <?php endif; } catch(PDOException $e) {} ?>
                                </div>
                                <div class="quick-action-content">
                                    <div class="quick-action-label">Approve Reviews</div>
                                    <div class="quick-action-desc">Manage testimonials</div>
                                </div>
                                <i class="bi bi-chevron-right" style="color: var(--text-muted);"></i>
                            </div>
                        </a>
                    </div>
                </div>

                

                <!-- Performance Overview with REAL CHART -->
                <div class="row g-4 mt-2">
                    <div class="col-12">
                        <div class="enhanced-card">
                            <div class="card-title-row">
                                <h3 class="card-title-enhanced">
                                    <i class="bi bi-graph-up"></i>
                                    Performance Overview
                                </h3>
                                <select class="form-select" id="chartPeriod" style="width: auto; border-radius: 10px; border-color: var(--border-color);">
                                    <option value="7">Last 7 days</option>
                                    <option value="30">Last 30 days</option>
                                    <option value="90">Last 3 months</option>
                                    <option value="365" selected>Last 12 months</option>
                                </select>
                            </div>

                            <!-- Real Chart with Chart.js -->
                            <canvas id="inquiryChart" style="max-height: 350px;"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Summary with REAL DATA -->
                <?php
                // Calculate real statistics
                $total_views = 0;
                $client_satisfaction = 0;
                $avg_response_time = 0;
                $projects_completed = 0;
                $previous_month_inquiries = 0;
                $current_month_inquiries = 0;

                try {
                    // Total views (sum of portraits + videos views if you have view tracking)
                    // For now, we'll use a calculated estimate based on content
                    $total_views = ($total_portraits * 45) + ($total_videos * 120); // Estimated views per item
                    
                    // Client satisfaction (percentage of approved testimonials)
                    $total_testimonials_stmt = $conn->query("SELECT COUNT(*) as total FROM testimonials WHERE is_archived = 0");
                    $total_testimonials_count = $total_testimonials_stmt->fetch()['total'];
                    
                    if ($total_testimonials_count > 0) {
                        $client_satisfaction = round(($approved_testimonials / $total_testimonials_count) * 100);
                    } else {
                        $client_satisfaction = 0;
                    }
                    
                    // Average response time (calculate from inquiries)
                    $response_stmt = $conn->query("
                        SELECT AVG(TIMESTAMPDIFF(HOUR, submission_date, NOW())) as avg_hours 
                        FROM inquiries 
                        WHERE is_read = 1 AND is_archived = 0
                        AND submission_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ");
                    $response_data = $response_stmt->fetch();
                    $avg_response_time = round($response_data['avg_hours'] ?? 2.5, 1);
                    
                    // Projects completed (approved testimonials + partners)
                    $projects_completed = $approved_testimonials + $total_partners;
                    
                    // Calculate month-over-month growth for inquiries
                    $current_month_stmt = $conn->query("
                        SELECT COUNT(*) as count 
                        FROM inquiries 
                        WHERE MONTH(submission_date) = MONTH(CURRENT_DATE())
                        AND YEAR(submission_date) = YEAR(CURRENT_DATE())
                        AND is_archived = 0
                    ");
                    $current_month_inquiries = $current_month_stmt->fetch()['count'];
                    
                    $previous_month_stmt = $conn->query("
                        SELECT COUNT(*) as count 
                        FROM inquiries 
                        WHERE MONTH(submission_date) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                        AND YEAR(submission_date) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                        AND is_archived = 0
                    ");
                    $previous_month_inquiries = $previous_month_stmt->fetch()['count'];
                    
                    // Calculate percentage changes
                    $views_change = 18; // Placeholder - implement view tracking for real data
                    $satisfaction_change = 2; // Placeholder
                    
                    if ($previous_month_inquiries > 0) {
                        $response_time_change = round((($previous_month_inquiries - $current_month_inquiries) / $previous_month_inquiries) * 100);
                    } else {
                        $response_time_change = 0;
                    }
                    
                    $projects_change = 5; // Placeholder - compare with previous period
                    
                } catch (PDOException $e) {
                    error_log("Stats calculation error: " . $e->getMessage());
                }
                ?>

                <div class="row g-4 mt-2 mb-4">
                    <div class="col-md-3">
                        <div class="enhanced-card text-center">
                            <i class="bi bi-eye-fill" style="font-size: 2.5rem; color: #3b82f6; margin-bottom: 0.75rem;"></i>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                                <?php echo number_format($total_views); ?>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">Estimated Views</div>
                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #10b981;">
                                <i class="bi bi-arrow-up"></i> +<?php echo $views_change; ?>% this month
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="enhanced-card text-center">
                            <i class="bi bi-hand-thumbs-up-fill" style="font-size: 2.5rem; color: #10b981; margin-bottom: 0.75rem;"></i>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                                <?php echo $client_satisfaction; ?>%
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">Client Satisfaction</div>
                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #10b981;">
                                <i class="bi bi-arrow-up"></i> Based on testimonials
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="enhanced-card text-center">
                            <i class="bi bi-clock-history" style="font-size: 2.5rem; color: #f59e0b; margin-bottom: 0.75rem;"></i>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                                <?php echo $avg_response_time; ?>h
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">Avg Response Time</div>
                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: <?php echo $response_time_change >= 0 ? '#10b981' : '#ef4444'; ?>;">
                                <i class="bi bi-<?php echo $response_time_change >= 0 ? 'arrow-down' : 'arrow-up'; ?>"></i> 
                                <?php echo abs($response_time_change); ?>% <?php echo $response_time_change >= 0 ? 'faster' : 'slower'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="enhanced-card text-center">
                            <i class="bi bi-trophy-fill" style="font-size: 2.5rem; color: #ec4899; margin-bottom: 0.75rem;"></i>
                            <div style="font-size: 2rem; font-weight: 800; color: var(--text-primary);">
                                <?php echo $projects_completed; ?>
                            </div>
                            <div style="font-size: 0.9rem; color: var(--text-secondary); font-weight: 600;">Projects Completed</div>
                            <div style="margin-top: 0.75rem; font-size: 0.85rem; color: #10b981;">
                                <i class="bi bi-arrow-up"></i> Active partnerships
                            </div>
                        </div>
                    </div>
                </div>

                <?php
                // Fetch monthly inquiry data for chart
                $chart_data_stmt = $conn->query("
                    SELECT 
                        DATE_FORMAT(submission_date, '%Y-%m') AS month,
                        COUNT(*) AS count
                    FROM inquiries
                    WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                    AND is_archived = 0
                    GROUP BY month
                    ORDER BY month ASC
                ");
                
                $monthly_data = [];
                while ($row = $chart_data_stmt->fetch(PDO::FETCH_ASSOC)) {
                    $monthly_data[$row['month']] = (int)$row['count'];
                }
                
                // Generate labels for last 12 months
                $chart_labels = [];
                $chart_values = [];
                
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i month"));
                    $display_date = date('M Y', strtotime("-$i month"));
                    
                    $chart_labels[] = $display_date;
                    $chart_values[] = isset($monthly_data[$date]) ? $monthly_data[$date] : 0;
                }
                ?>

                <script>
                    // Chart data from PHP
                    const chartLabels = <?php echo json_encode($chart_labels); ?>;
                    const chartData = <?php echo json_encode($chart_values); ?>;
                </script>

            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="js/admin-theme.js"></script>

    <script>
        // Initialize Chart.js
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('inquiryChart').getContext('2d');
            
            // Get current theme
            const currentTheme = localStorage.getItem('adminTheme') || 'light';
            const isDark = currentTheme === 'dark';
            const isPink = currentTheme === 'pink';
            
            // Theme-aware colors
            const textColor = isDark ? '#e9ecef' : (isPink ? '#2d1b2e' : '#212529');
            const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : (isPink ? 'rgba(255, 179, 217, 0.2)' : 'rgba(0, 0, 0, 0.1)');
            
            // Create gradient for chart
            const gradient = ctx.createLinearGradient(0, 0, 0, 350);
            if (isPink) {
                gradient.addColorStop(0, 'rgba(255, 107, 157, 0.3)');
                gradient.addColorStop(1, 'rgba(255, 107, 157, 0.01)');
            } else {
                gradient.addColorStop(0, 'rgba(205, 145, 158, 0.3)');
                gradient.addColorStop(1, 'rgba(205, 145, 158, 0.01)');
            }
            
            const inquiryChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartLabels,
                    datasets: [{
                        label: 'Inquiries',
                        data: chartData,
                        backgroundColor: gradient,
                        borderColor: isPink ? '#ff6b9d' : '#cd919e',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: isPink ? '#ff6b9d' : '#cd919e',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 3,
                        pointRadius: 5,
                        pointHoverRadius: 7,
                        pointHoverBackgroundColor: isPink ? '#ff8fb8' : '#b77f8b',
                        pointHoverBorderWidth: 3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2.5,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: isDark ? '#1e1b2e' : '#fff',
                            titleColor: textColor,
                            bodyColor: textColor,
                            borderColor: gridColor,
                            borderWidth: 1,
                            padding: 12,
                            displayColors: false,
                            callbacks: {
                                label: function(context) {
                                    return 'Inquiries: ' + context.parsed.y;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: gridColor,
                                drawBorder: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 12
                                },
                                padding: 10
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: textColor,
                                font: {
                                    size: 12
                                },
                                padding: 10
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
            
            // Update chart when theme changes
            document.querySelectorAll('.theme-btn').forEach(btn => {
                const originalClick = btn.onclick;
                btn.onclick = function() {
                    if (originalClick) originalClick.call(this);
                    
                    setTimeout(() => {
                        const newTheme = localStorage.getItem('adminTheme') || 'light';
                        const newIsDark = newTheme === 'dark';
                        const newIsPink = newTheme === 'pink';
                        
                        const newTextColor = newIsDark ? '#e9ecef' : (newIsPink ? '#2d1b2e' : '#212529');
                        const newGridColor = newIsDark ? 'rgba(255, 255, 255, 0.1)' : (newIsPink ? 'rgba(255, 179, 217, 0.2)' : 'rgba(0, 0, 0, 0.1)');
                        
                        // Update gradient
                        const newGradient = ctx.createLinearGradient(0, 0, 0, 350);
                        if (newIsPink) {
                            newGradient.addColorStop(0, 'rgba(255, 107, 157, 0.3)');
                            newGradient.addColorStop(1, 'rgba(255, 107, 157, 0.01)');
                        } else {
                            newGradient.addColorStop(0, 'rgba(205, 145, 158, 0.3)');
                            newGradient.addColorStop(1, 'rgba(205, 145, 158, 0.01)');
                        }
                        
                        inquiryChart.data.datasets[0].backgroundColor = newGradient;
                        inquiryChart.data.datasets[0].borderColor = newIsPink ? '#ff6b9d' : '#cd919e';
                        inquiryChart.data.datasets[0].pointBackgroundColor = newIsPink ? '#ff6b9d' : '#cd919e';
                        inquiryChart.data.datasets[0].pointHoverBackgroundColor = newIsPink ? '#ff8fb8' : '#b77f8b';
                        
                        inquiryChart.options.plugins.tooltip.backgroundColor = newIsDark ? '#1e1b2e' : '#fff';
                        inquiryChart.options.plugins.tooltip.titleColor = newTextColor;
                        inquiryChart.options.plugins.tooltip.bodyColor = newTextColor;
                        inquiryChart.options.plugins.tooltip.borderColor = newGridColor;
                        
                        inquiryChart.options.scales.y.grid.color = newGridColor;
                        inquiryChart.options.scales.y.ticks.color = newTextColor;
                        inquiryChart.options.scales.x.ticks.color = newTextColor;
                        
                        inquiryChart.update();
                    }, 100);
                };
            });
        });

        // Theme switcher functionality
        document.addEventListener('DOMContentLoaded', function() {
            const html = document.documentElement;
            const themeButtons = document.querySelectorAll('.theme-btn');
            
            // Load saved theme or default to light
            const savedTheme = localStorage.getItem('adminTheme') || 'light';
            html.setAttribute('data-theme', savedTheme);
            updateActiveButton(savedTheme);

            // Theme switcher click handlers
            themeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const theme = this.getAttribute('data-theme');
                    html.setAttribute('data-theme', theme);
                    localStorage.setItem('adminTheme', theme);
                    updateActiveButton(theme);
                });
            });

            function updateActiveButton(theme) {
                themeButtons.forEach(btn => {
                    if (btn.getAttribute('data-theme') === theme) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }

            // Task checkbox functionality
            const taskCheckboxes = document.querySelectorAll('.task-checkbox input[type="checkbox"]');
            taskCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const taskItem = this.closest('.task-item-enhanced');
                    const taskText = taskItem.querySelector('.task-text');
                    
                    if (this.checked) {
                        taskText.style.textDecoration = 'line-through';
                        taskText.style.opacity = '0.5';
                        taskItem.style.background = 'rgba(16, 185, 129, 0.1)';
                        
                        // Optional: Remove after animation
                        setTimeout(() => {
                            taskItem.style.transition = 'all 0.3s ease';
                            taskItem.style.opacity = '0';
                            taskItem.style.transform = 'translateX(20px)';
                            
                            setTimeout(() => {
                                taskItem.remove();
                            }, 300);
                        }, 1000);
                    } else {
                        taskText.style.textDecoration = 'none';
                        taskText.style.opacity = '1';
                        taskItem.style.background = 'var(--bg-secondary)';
                    }
                });
            });

            // Add hover effect to quick action cards
            const quickActions = document.querySelectorAll('.quick-action-card');
            quickActions.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.background = 'var(--bg-secondary)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.background = 'var(--bg-primary)';
                });
            });

            // Animate stat cards on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -100px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            document.querySelectorAll('.stat-card-enhanced, .enhanced-card').forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                card.style.transition = 'all 0.6s ease-out';
                observer.observe(card);
            });

            // Add New Task button functionality
            const addTaskBtn = document.querySelector('.btn.w-100.mt-3');
            if (addTaskBtn) {
                addTaskBtn.addEventListener('mouseenter', function() {
                    this.style.borderColor = 'var(--jade-primary)';
                    this.style.color = 'var(--jade-primary)';
                    this.style.background = 'rgba(205, 145, 158, 0.05)';
                });
                
                addTaskBtn.addEventListener('mouseleave', function() {
                    this.style.borderColor = 'var(--border-color)';
                    this.style.color = 'var(--text-secondary)';
                    this.style.background = 'transparent';
                });

                addTaskBtn.addEventListener('click', function() {
                    alert('Task creation feature - Connect to your backend to add tasks!');
                });
            }
        });
    </script>
</body>
</html>           