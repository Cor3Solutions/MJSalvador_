<?php
require_once 'config.php';

if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }
}

// Function to convert YouTube URL to embed format
function convertToEmbedUrl($url) {
    // Extract video ID from various YouTube URL formats
    $video_id = '';
    
    // Handle youtu.be format: https://youtu.be/VIDEO_ID
    if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Handle youtube.com/watch format: https://www.youtube.com/watch?v=VIDEO_ID
    elseif (preg_match('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    // Handle youtube.com/embed format: https://www.youtube.com/embed/VIDEO_ID
    elseif (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    
    // Return embed URL if video ID found
    if ($video_id) {
        return "https://www.youtube.com/embed/{$video_id}";
    }
    
    // Return original URL if no video ID found
    return $url;
}

try {
    $conn = getDBConnection();
    
    $stmt_cat = $conn->prepare("SELECT name, display_name FROM video_categories ORDER BY sort_order ASC, display_name ASC");
    $stmt_cat->execute();
    $categories = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $conn->prepare("
        SELECT 
            v.*, 
            vc.name AS category_short_name
        FROM videos v
        JOIN video_categories vc ON v.category_id = vc.category_id
        ORDER BY v.display_order ASC, v.video_id DESC
    ");
    $stmt->execute();
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    error_log("Error in vtr.php: " . $e->getMessage());
    $videos = [];
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Tape Recordings - Jade S.</title>
   <link rel="icon" type="image/png" href="images/logo.png">

  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" href="css/style.css">

    
    <style>
        #vtr { background-color: #ffe4ec; }
        .filter-button.active {
            background-color: #212529;
            color: white;
            border-color: #212529;
        }
        .swiper-slide.hidden-slide { display: none; }
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
        
        .swiper-slide {
            display: flex !important;
            flex-direction: column !important;
        }
        
        .swiper-slide .ratio {
            width: 100% !important;
        }
        
        .swiper-slide h5 {
            width: 100% !important;
            margin-top: 1rem !important;
        }
        
        .swiper-slide[style*="display: none"] {
            display: none !important;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>
    
    <br>
    <div class="container-fluid page-header py-5 mb-5 wow fadeIn">
        <div class="container py-5"></div>
    </div>
    
    <section id="vtr" class="my-5 py-5 px-3">
        <div class="pt-4 mt-4 text-center">
            <span class="text-muted text-uppercase" style="font-family: 'Playfair Display', serif;">
                Some of my Recent VTRs
            </span>
            <h4 class="display-5 fw-normal mt-2" style="font-family: 'Playfair Display', serif;">
                Video Tape Recordings
            </h4>
        </div>
        
        <div class="text-center my-4">
            <div class="btn-group flex-wrap justify-content-center">
                <?php if (!empty($categories)): ?>
                    <?php foreach($categories as $index => $cat): ?>
                        <button 
                            class="filter-button btn btn-outline-dark me-2 <?php echo $index === 0 ? 'active' : ''; ?>" 
                            data-filter="<?php echo h($cat['name']); ?>"
                        >
                            <?php echo h($cat['display_name']); ?>
                        </button>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="container py-4 px-3 bg-white rounded-4 shadow-sm">
            <?php if(empty($videos)): ?>
                <div class="text-center py-5">
                    <p class="text-muted">No videos available at the moment.</p>
                </div>
            <?php else: ?>
            <div class="swiper vtr-swiper">
                <div class="swiper-wrapper">
                    <?php foreach($videos as $video): ?>
                    <div class="swiper-slide item <?php echo h($video['category_short_name']); ?>">
                        <div class="ratio ratio-16x9">
                            <iframe src="<?php echo h(convertToEmbedUrl($video['youtube_embed_url'])); ?>" 
                                    title="<?php echo h($video['title']); ?>" 
                                    allowfullscreen
                                    loading="lazy"></iframe>
                        </div>
                        <h5 class="mt-4 text-center"><?php echo h($video['title']); ?></h5>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let mySwiper;
        const allSlides = document.querySelectorAll('.vtr-swiper .swiper-slide');
        
        function initSwiper(filter) {
            if (mySwiper) {
                mySwiper.destroy(true, true);
            }
            
            console.log('Filtering by:', filter);
            let visibleCount = 0;
            
            allSlides.forEach(slide => {
                const hasClass = slide.classList.contains(filter);
                console.log('Slide classes:', slide.className, 'Has filter class:', hasClass);
                
                if (hasClass) {
                    slide.style.display = '';
                    visibleCount++;
                } else {
                    slide.style.display = 'none';
                }
            });
            
            console.log('Visible slides:', visibleCount);
            
            mySwiper = new Swiper('.vtr-swiper', {
                slidesPerView: 1,
                spaceBetween: 20,
                breakpoints: {
                    576: { slidesPerView: 1 },
                    768: { slidesPerView: 2 },
                    992: { slidesPerView: 3 }
                },
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                pagination: {
                    el: '.swiper-pagination',
                    clickable: true,
                },
                lazy: true
            });
        }
        
        const buttons = document.querySelectorAll('.filter-button');
        console.log('Found filter buttons:', buttons.length);
        buttons.forEach(btn => {
            console.log('Button:', btn.textContent, 'Filter:', btn.dataset.filter);
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                console.log('Clicked filter:', filter);
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                initSwiper(filter);
            });
        });
        
        // Initialize with the first category
        const firstButton = document.querySelector('.filter-button.active');
        const firstFilter = firstButton ? firstButton.dataset.filter : '';
        initSwiper(firstFilter);
    });
    </script>
</body>
</html>