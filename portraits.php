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
$portraitCategories = [];
$debug_info = [];

try {
    $conn = getDBConnection();

    // DEBUG: Check table structure
    try {
        $stmt = $conn->query("DESCRIBE portraits");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['columns'] = $columns;
    } catch (PDOException $e) {
        $debug_info['columns_error'] = $e->getMessage();
    }

    // DEBUG: Check if is_archived column exists
    $has_archived = in_array('is_archived', $columns ?? []);
    $debug_info['has_archived_column'] = $has_archived;

    // Fetch portraits (with or without archive filter)
    if ($has_archived) {
        $stmt = $conn->prepare("SELECT * FROM portraits WHERE is_archived = 0 ORDER BY sort_order ASC, portrait_id DESC");
    } else {
        $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC, portrait_id DESC");
    }
    $stmt->execute();
    $portraits = $stmt->fetchAll();
    $debug_info['portraits_count'] = count($portraits);

    // DEBUG: Show first portrait data
    if (!empty($portraits)) {
        $debug_info['first_portrait'] = $portraits[0];
    }

    // Fetch categories
    try {
        $stmt_cat = $conn->prepare("SELECT name, display_name, color FROM portrait_categories ORDER BY display_name ASC");
        $stmt_cat->execute();
        $portraitCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
        $debug_info['categories_count'] = count($portraitCategories);
        $debug_info['categories'] = $portraitCategories;
    } catch (PDOException $e) {
        // If color column doesn't exist, fetch without it
        $stmt_cat = $conn->prepare("SELECT name, display_name FROM portrait_categories ORDER BY display_name ASC");
        $stmt_cat->execute();
        $portraitCategories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
        $debug_info['categories_count'] = count($portraitCategories);
        $debug_info['categories'] = $portraitCategories;
        $debug_info['no_color_column'] = true;
    }

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $debug_info['error'] = $e->getMessage();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        .page-header {
            background: url('images/cover.png') center center/cover no-repeat;
            height: 400px;
        }
        
        .filter-button {
            padding: 10px 24px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #ddd;
            margin: 5px;
        }
        
        .filter-button.active {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .portrait-card {
            position: relative;
            overflow: hidden;
            border-radius: 16px;
            transition: all 0.4s ease;
            height: 400px;
            border: 3px solid transparent;
        }
        
        .portrait-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
        }
        
        .card-img-container {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .card-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .setcard-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: gold;
            color: #000;
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.75rem;
            z-index: 10;
        }
        
        .setcard-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(205, 145, 158, 0.95), rgba(118, 75, 162, 0.95));
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.4s ease;
            padding: 20px;
            color: white;
        }
        
        .portrait-card:hover .setcard-overlay {
            opacity: 1;
        }
        
        .stat-item {
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            margin: 8px 0;
            backdrop-filter: blur(10px);
        }
        
        .card-title-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
            padding: 20px;
            color: white;
        }
        
        .item-classes {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0,0,0,0.7);
            color: lime;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.7rem;
            z-index: 5;
            font-family: monospace;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

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
            <div class="text-center my-5">
                <div class="d-flex flex-wrap justify-content-center">
                    <?php foreach ($portraitCategories as $index => $category): ?>
                        <?php
                        $color = $category['color'] ?? '#764ba2';
                        // Count items in this category
                        $count = 0;
                        foreach ($portraits as $p) {
                            if (strpos($p['categories'], $category['name']) !== false) {
                                $count++;
                            }
                        }
                        $isFirst = $index === 0;
                        ?>
                        <button class="filter-button btn <?php echo $isFirst ? 'active' : ''; ?>" 
                                data-filter=".<?php echo h($category['name']); ?>"
                                data-color="<?php echo h($color); ?>"
                                style="background-color: <?php echo $isFirst ? $color : 'white'; ?>; color: <?php echo $isFirst ? 'white' : $color; ?>; border-color: <?php echo h($color); ?>;">
                            <?php echo h($category['display_name']); ?> (<?php echo $count; ?>)
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Portrait grid -->
            <div class="portrait-scrollbox">
                <div class="isotope-container row g-4">
                    <?php if (empty($portraits)): ?>
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <h4>⚠️ No Portraits Found</h4>
                                <p>No portraits in database. Please upload some in the admin panel.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($portraits as $portrait): ?>
                            <?php
                            // Get categories and create filter classes
                            $categories_raw = $portrait['categories'] ?? '';
                            $categories_array = array_filter(array_map('trim', explode(' ', $categories_raw)));
                            $filter_classes = implode(' ', $categories_array);
                            
                            // Debug: show what classes each item has
                            $debug_classes = !empty($filter_classes) ? $filter_classes : 'NO-CATEGORIES';
                            ?>
                            <div class="item <?php echo h($filter_classes); ?> col-lg-4 col-md-6"
                                 data-debug-categories="<?php echo h($categories_raw); ?>">
                                <div class="card border-0 portrait-card">
                                    <!-- Debug label showing classes -->
                                    <div class="item-classes">
                                        <?php echo h($debug_classes); ?>
                                    </div>
                                    
                                    <!-- Set Card Badge -->
                                    <?php if ($portrait['is_setcard']): ?>
                                        <div class="setcard-badge">
                                            ⭐ SET CARD
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="card-img-container">
                                        <img src="<?php echo h($portrait['image_filename']); ?>"
                                            alt="<?php echo h($portrait['title']); ?>" 
                                            class="img-fluid">
                                        
                                        <!-- Title Overlay -->
                                        <div class="card-title-overlay">
                                            <h5><?php echo h($portrait['title']); ?></h5>
                                            <small>ID: <?php echo $portrait['portrait_id']; ?></small>
                                        </div>
                                        
                                        <!-- Set Card Hover Overlay -->
                                        <?php if ($portrait['is_setcard']): ?>
                                            <div class="setcard-overlay">
                                                <div class="text-center">
                                                    <h4>📊 Model Stats</h4>
                                                    <div class="stat-item">
                                                        <strong>Height:</strong> 5'7"
                                                    </div>
                                                    <div class="stat-item">
                                                        <strong>Weight:</strong> 47kg
                                                    </div>
                                                    <div class="stat-item">
                                                        <strong>Vitals:</strong> 34-23-35
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/isotope-layout@3/dist/isotope.pkgd.min.js"></script>
    <script src="https://unpkg.com/imagesloaded@5/imagesloaded.pkgd.min.js"></script>

    <script>
        $(document).ready(function () {
            console.log('=== PORTRAITS FILTER DEBUG ===');
            
            // Simple filter implementation without Isotope dependency
            $('.filter-button').on('click', function (e) {
                e.preventDefault();
                
                var filterValue = $(this).attr('data-filter');
                var buttonColor = $(this).attr('data-color') || '#2c2c2c';
                
                console.log('=== FILTER CLICKED ===');
                console.log('Filter value:', filterValue);
                
                // Remove the dot from filter value for class matching
                var className = filterValue.replace('.', '');
                console.log('Looking for class:', className);
                
                // Hide all items first
                $('.item').hide();
                
                // Show only items with the matching class
                if (className === '') {
                    // Show all items
                    $('.item').show();
                    console.log('Showing all items');
                } else {
                    $('.item.' + className).show();
                    var visibleCount = $('.item.' + className).length;
                    console.log('Showing items with class "' + className + '":', visibleCount);
                }
                
                // Update button styles
                $('.filter-button').removeClass('active').css({
                    'background-color': 'white',
                    'color': function() {
                        return $(this).attr('data-color') || '#2c2c2c';
                    },
                    'border-color': function() {
                        return $(this).attr('data-color') || '#2c2c2c';
                    }
                });
                
                $(this).addClass('active').css({
                    'background-color': buttonColor,
                    'color': 'white',
                    'border-color': buttonColor
                });
                
                console.log('Filter applied successfully');
            });
            
            // Initialize with first category
            var firstButton = $('.filter-button.active');
            if (firstButton.length > 0) {
                firstButton.trigger('click');
            }
            
            console.log('Filter system initialized');
        });
    </script>
</body>
</html>