<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Fetch event portraits - filter by categories containing 'events' or 'ritemed'
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
        @media (max-width: 768px) {
            .page-header {
                background: url('images/cover-mobile.png') center center/cover no-repeat;
                height: 250px;
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
                    <button class="btn btn-outline-dark filter-button active" data-filter="*">All Events</button>
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
                        <div class="item <?php echo h($portrait['categories']); ?> col-md-4">
                            <div class="card border-0 shadow-lg">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#imageModal" 
                                   data-img-src="<?php echo h($portrait['image_filename']); ?>" 
                                   data-img-alt="<?php echo h($portrait['title']); ?>">
                                    <div class="card-img-container">
                                        <img src="<?php echo h($portrait['image_filename']); ?>" 
                                             alt="<?php echo h($portrait['title']); ?>" 
                                             class="img-fluid rounded">
                                    </div>
                                    <div class="card-body text-center">
                                        <h5 class="card-title"><?php echo h($portrait['title']); ?></h5>
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
    
    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
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
    });
    </script>
</body>
</html>