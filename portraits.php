<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Fetch all portraits ordered by sort_order
    $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC, portrait_id DESC");
    $stmt->execute();
    $portraits = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $portraits = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Portraits - Jade S.</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <br>
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5"></div>
    </div>
    
    <section id="portraits-section" class="my-5 py-5 bg-text" style="background-color: #ffe4ec;">
        <div class="container">
            <div class="text-center pt-4 mt-4">
                <span class="text-muted text-uppercase">Keeping you on the loop</span>
                <h4 class="display-5 fw-normal mt-2">Gallery Overview</h4>
            </div>
            
            <!-- Filter buttons -->
            <div class="text-center my-4">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-outline-dark filter-button active" data-filter="*">All</button>
                    <div class="dropdown">
                        <button class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">Gym</button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item filter-button" data-filter=".boxing">MMA</a></li>
                            <li><a class="dropdown-item filter-button" data-filter=".gymoutfits">Gym Outfits</a></li>
                        </ul>
                    </div>
                    <button class="btn btn-outline-dark filter-button" data-filter=".events">Events</button>
                    <button class="filter-button btn btn-outline-dark" data-filter=".setcard">Set Cards</button>
                    <button class="filter-button btn btn-outline-dark" data-filter=".headshots">Head Shots</button>
                </div>
            </div>
            
            <!-- Portrait grid -->
            <div class="portrait-scrollbox">
                <div class="isotope-container row g-4">
                    <?php foreach($portraits as $portrait): ?>
                    <div class="item <?php echo h($portrait['categories']); ?> <?php echo $portrait['is_setcard'] ? 'setcard' : ''; ?> col-md-4">
                        <div class="card border-0 shadow-lg">
                            <div class="card-img-container">
                                <img src="<?php echo h($portrait['image_filename']); ?>" 
                                     alt="<?php echo h($portrait['title']); ?>" 
                                     class="img-fluid rounded">
                            </div>
                            <div class="card-body text-center">
                                <h5 class="card-title"><?php echo h($portrait['title']); ?></h5>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php include 'footer.php'; ?>
    
    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
    
    <script>
    $(document).ready(function() {
        var $grid = $('.isotope-container').isotope({
            itemSelector: '.item',
            layoutMode: 'fitRows'
        });
        
        $('.filter-button').on('click', function() {
            var filterValue = $(this).attr('data-filter');
            $grid.isotope({ filter: filterValue });
            
            $('.filter-button').removeClass('active btn-dark').addClass('btn-outline-dark');
            $(this).addClass('active btn-dark').removeClass('btn-outline-dark');
        });
    });
    </script>
</body>
</html>