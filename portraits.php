<?php
require_once 'config.php';

// Helper function for security: HTML-escape data
if (!function_exists('h')) {
    function h($string)
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

$portraits = [];
$portraitCategories = []; // Array to hold dynamically fetched categories

try {
    $conn = getDBConnection();

    // Fetch all portraits ordered by sort_order
    $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC, portrait_id DESC");
    $stmt->execute();
    $portraits = $stmt->fetchAll();

    // Fetch all available categories from the portrait_categories table
    $stmt_cat = $conn->prepare("SELECT name, display_name FROM portrait_categories ORDER BY display_name ASC");
    $stmt_cat->execute();
    $portraitCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
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
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
</head>
<style>
    .page-header {
        background: url('images/cover.png') center center/cover no-repeat;
        height: 400px;
    }
</style>

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

            <!-- Filter buttons - DYNAMICALLY GENERATED -->
            <div class="text-center my-4">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <!-- Default 'All' button - active state uses btn-dark -->
                    <button class="btn btn-dark filter-button active" data-filter="*">All</button>

                    <?php foreach ($portraitCategories as $category): ?>
                        <button class="filter-button btn btn-outline-dark"
                            data-filter=".<?php echo h($category['name']); ?>">
                            <?php echo h($category['display_name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Portrait grid -->
            <div class="portrait-scrollbox">
                <div class="isotope-container row g-4">
                    <?php foreach ($portraits as $portrait): ?>
                        <?php
                        // Determine the CSS classes needed for filtering
                        // IMPORTANT: The 'categories' column contains the space-separated list of categories.
                        $filter_classes = h($portrait['categories']);
                        if ($portrait['is_setcard']) {
                            $filter_classes .= ' setcard';
                        }
                        ?>
                        <div class="item <?php echo trim($filter_classes); ?> col-md-4">
                            <div class="card border-0 shadow-lg">
                                <div class="card-img-container">
                                    <img src="<?php echo h($portrait['image_filename']); ?>"
                                        alt="<?php echo h($portrait['title']); ?>" class="img-fluid rounded">
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
    <!-- Isotope required for filtering -->
    <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
    <!-- CRITICAL FIX: ImagesLoaded ensures Isotope calculates positions AFTER all images are fully loaded -->
    <script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>

    <script>
        $(document).ready(function () {
            // Get the grid container
            var $grid = $('.isotope-container');

            // --- CRITICAL STEP FOR RELIABLE FILTERING ---
            // Wait until all images within the container are loaded before initializing Isotope
            $grid.imagesLoaded(function () {
                // Initialize Isotope
                $grid.isotope({
                    itemSelector: '.item',
                    layoutMode: 'fitRows'
                });
            });

            $('.filter-button').on('click', function () {
                var filterValue = $(this).attr('data-filter');

                // Check if Isotope has been initialized before filtering
                if ($grid.data('isotope')) {
                    $grid.isotope({ filter: filterValue });
                }

                // Update button styles
                $('.filter-button').removeClass('active btn-dark').addClass('btn-outline-dark');
                $(this).addClass('active btn-dark').removeClass('btn-outline-dark');
            });
        });
    </script>
</body>

</html>