<?php
// The custom function h() definition has been removed from this file 
// to resolve the "Cannot redeclare h()" error, as it appears to be defined
// unconditionally in config.php (or another included file).

require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Fetch event portraits - ONLY filter by categories containing 'events' or 'ritemed'
    // This ensures this page is dedicated only to those specific event types.
    $stmt = $conn->prepare("
        SELECT * FROM portraits 
        WHERE categories LIKE '%events%' OR categories LIKE '%ritemed%'
        ORDER BY sort_order ASC, portrait_id DESC
    ");
    $stmt->execute();
    $event_portraits = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $event_portraits = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Events - Jade S.</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .social-icon {
            color: #808080;
            transition: color 0.3s ease;
        }
        .social-icon:hover {
            color: #ff69b4;
        }
        .page-header {
            background: url('images/cover.png') center center/cover no-repeat;
            height: 400px;
        }
        /* Style for the image container to maintain aspect ratio and fit within the card */
        .card-img-container {
            height: 300px; /* Fixed height for consistency */
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Ensures the image covers the container without distortion */
            transition: transform 0.3s ease;
        }
        .card:hover .card-img-container img {
            transform: scale(1.05);
        }
        .card {
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .card-body {
            padding: 1.5rem 1rem;
        }
        .portrait-scrollbox {
            /* If you want the grid to be scrollable within a fixed height: */
            /* max-height: 80vh; */
            /* overflow-y: auto; */
            /* padding: 10px; */
        }
        @media (max-width: 768px) {
            .page-header {
                background: url('images/cover-mobile.png') center center/cover no-repeat;
                height: 250px;
            }
            .card-img-container {
                height: 200px; 
            }
        }
    </style>
    
    <br>
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
        <div class="container py-5"></div>
    </div>
    
    <!-- Gallery Section -->
    <section id="portraits-section" class="my-5 py-5 bg-text" style="background-color: #ffe4ec;">
        <div class="container">
            <div class="text-center pt-4 mt-4">
                <span class="text-muted text-uppercase">Keeping you on the loop</span>
                <h4 class="display-5 fw-normal mt-2">Events</h4>
            </div>
            
            <!-- Filter buttons -->
            <div class="text-center my-4">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <button class="btn btn-dark filter-button active" data-filter="*">All Events</button>
                    <button class="btn btn-outline-dark filter-button" data-filter=".events">General Events</button>
                    <button class="btn btn-outline-dark filter-button" data-filter=".ritemed">RiteMed</button>
                </div>
            </div>
            
            <!-- Scrollable grid wrapper -->
            <div class="portrait-scrollbox">
                <div class="isotope-container row g-4">
                    <?php if(empty($event_portraits)): ?>
                    <div class="col-12 text-center">
                        <p class="text-muted">No event photos available at the moment.</p>
                    </div>
                    <?php else: ?>
                        <?php foreach($event_portraits as $portrait): ?>
                        <?php 
                        // **FINAL FIX:** Explicitly check for 'events' and 'ritemed' to generate the required CSS classes.
                        // This bypasses any issues with spaces, commas, or casing in the raw database string.
                        $category_classes = '';
                        $categories_lower = strtolower($portrait['categories'] ?? '');

                        // If the category string contains 'events' (or 'general events'), assign the 'events' class
                        if (str_contains($categories_lower, 'events')) {
                            $category_classes .= ' events';
                        }

                        // If the category string contains 'ritemed', assign the 'ritemed' class
                        if (str_contains($categories_lower, 'ritemed')) {
                            $category_classes .= ' ritemed';
                        }
                        
                        // Trim leading/trailing whitespace
                        $category_classes = trim($category_classes);
                        ?>
                        <!-- The generated 'category_classes' are now guaranteed to be 'events' or 'ritemed' (or both) -->
                        <div class="item <?php echo h($category_classes); ?> col-12 col-sm-6 col-md-4">
                            <div class="card border-0 shadow-lg rounded-3">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" 
                                   data-img-src="<?php echo h($portrait['image_filename']); ?>" 
                                   data-img-alt="<?php echo h($portrait['title']); ?>">
                                    <div class="card-img-container">
                                        <img src="<?php echo h($portrait['image_filename']); ?>" 
                                             alt="<?php echo h($portrait['title']); ?>" 
                                             class="img-fluid">
                                    </div>
                                    <div class="card-body text-center">
                                        <h5 class="card-title text-dark fw-bold" style="font-family: 'Playfair Display', serif;"><?php echo h($portrait['title']); ?></h5>
                                    </div>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Image Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" 
                        data-bs-dismiss="modal" aria-label="Close"></button>
                <img src="" alt="" id="modalImage" class="img-fluid rounded shadow">
            </div>
        </div>
    </div>
    
    <?php include 'footer.php'; ?>
    
    <!-- Dependencies (using Bootstrap 5.3.3 and latest jQuery) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawR/U9z9KkLp/hS" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Initialize Isotope
        var $grid = $('.isotope-container').isotope({
            itemSelector: '.item',
            layoutMode: 'fitRows'
        });
        
        // Filter functionality
        $('.filter-button').on('click', function() {
            var filterValue = $(this).attr('data-filter');
            $grid.isotope({ filter: filterValue });
            
            // Handle active state for buttons
            $('.filter-button').removeClass('active btn-dark').addClass('btn-outline-dark');
            $(this).addClass('active btn-dark').removeClass('btn-outline-dark');
        });
        
        // Modal image handler
        const imageModal = document.getElementById('imageModal');
        const modalImage = document.getElementById('modalImage');
        
        imageModal.addEventListener('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            const src = trigger.getAttribute('data-img-src');
            const alt = trigger.getAttribute('data-img-alt');
            
            modalImage.src = src;
            modalImage.alt = alt;
        });

        // Ensure Isotope reflows after images load to prevent overlap
        // This is important for correct layout when images load asynchronously
        $grid.imagesLoaded().progress( function() {
            $grid.isotope('layout');
        });
    });
    </script>
</body>
</html>
