<?php
// Define a function to check if the current page matches the link's page
if (!function_exists('is_active')) {
    function is_active($page) {
        global $currentPage;
        return (isset($currentPage) && $currentPage == $page) ? 'active' : '';
    }
}

// Get archived items count for badge
$total_archived = 0;
if (isset($conn)) {
    try {
        $stmt = $conn->query("
            SELECT SUM(
                (SELECT COUNT(*) FROM portraits WHERE is_archived = 1) +
                (SELECT COUNT(*) FROM videos WHERE is_archived = 1) +
                (SELECT COUNT(*) FROM partners WHERE is_archived = 1) +
                (SELECT COUNT(*) FROM testimonials WHERE is_archived = 1) +
                (SELECT COUNT(*) FROM experiences WHERE is_archived = 1) +
                (SELECT COUNT(*) FROM inquiries WHERE is_archived = 1)
            ) as total
        ");
        $result = $stmt->fetch();
        $total_archived = (int)($result['total'] ?? 0);
    } catch (PDOException $e) {
        // Silently fail if archive columns don't exist yet
    }
}
?>

<nav id="sidebarMenu" class="collapse d-md-block sidebar">
    <div class="position-sticky">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('dashboard.php'); ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('inquiries.php'); ?>" href="inquiries.php">
                    <i class="bi bi-envelope"></i>
                    <span>Inquiries</span>
                    <?php if(isset($unread_inquiries) && $unread_inquiries > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto"><?php echo $unread_inquiries; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <h6 class="sidebar-heading">
                <span>Content Management</span>
            </h6>
            
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('portraits.php'); ?>" href="portraits.php">
                    <i class="bi bi-images"></i>
                    <span>Portraits</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('videos.php'); ?>" href="videos.php">
                    <i class="bi bi-play-circle"></i>
                    <span>Videos</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('partners.php'); ?>" href="partners.php">
                    <i class="bi bi-building"></i>
                    <span>Partners</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('testimonials.php'); ?>" href="testimonials.php">
                    <i class="bi bi-chat-quote"></i>
                    <span>Testimonials</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('experiences.php'); ?>" href="experiences.php">
                    <i class="bi bi-briefcase"></i>
                    <span>Experiences</span>
                </a>
            </li>
            
            <h6 class="sidebar-heading">
                <span>System</span>
            </h6>
            
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('archives.php'); ?>" href="archives.php">
                    <i class="bi bi-archive"></i>
                    <span>Archives</span>
                    <?php if($total_archived > 0): ?>
                    <span class="badge rounded-pill bg-secondary ms-auto"><?php echo $total_archived; ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </div>
</nav>