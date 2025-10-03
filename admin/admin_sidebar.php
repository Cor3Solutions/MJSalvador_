 
<?php
// Define a function to check if the current page matches the link's page
// This function needs to be defined in a globally accessible place like config.php or the main script.
// Assuming $currentPage variable is set in the main script (e.g., $currentPage = 'inquiries.php';)
if (!function_exists('is_active')) {
    function is_active($page) {
        global $currentPage;
        return (isset($currentPage) && $currentPage == $page) ? 'active' : '';
    }
}
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 collapse d-md-block sidebar">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('dashboard.php'); ?>" href="dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo is_active('inquiries.php'); ?>" href="inquiries.php">
                    <i class="bi bi-envelope"></i> Inquiries
                    <?php if(isset($unread_inquiries) && $unread_inquiries > 0): ?>
                    <span class="badge rounded-pill bg-danger ms-auto"><?php echo $unread_inquiries; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted text-uppercase">
                <span>Content Management</span>
            </h6>
            <li class="nav-item"><a class="nav-link <?php echo is_active('portraits.php'); ?>" href="portraits.php"><i class="bi bi-images"></i> Portraits</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('videos.php'); ?>" href="videos.php"><i class="bi bi-play-circle"></i> Videos</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('partners.php'); ?>" href="partners.php"><i class="bi bi-building"></i> Partners</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('testimonials.php'); ?>" href="testimonials.php"><i class="bi bi-chat-quote"></i> Testimonials</a></li>
            <li class="nav-item"><a class="nav-link <?php echo is_active('experiences.php'); ?>" href="experiences.php"><i class="bi bi-briefcase"></i> Experiences</a></li>
        </ul>
    </div>
</nav>

<main class="col-md-9 ms-sm-auto col-lg-10 main-content"></main>