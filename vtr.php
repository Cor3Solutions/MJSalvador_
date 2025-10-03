<?php
require_once 'config.php';

try {
    $conn = getDBConnection();
    
    // Fetch all videos ordered by display_order
    $stmt = $conn->prepare("SELECT * FROM videos ORDER BY display_order ASC, video_id DESC");
    $stmt->execute();
    $videos = $stmt->fetchAll();
    
} catch(PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $videos = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Tape Recordings - Jade S.</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    
    <style>
        #vtr { background-color: #ffe4ec; }
        .filter-button.active {
            background-color: #212529;
            color: white;
            border-color: #212529;
        }
        .swiper-slide.hidden-slide { display: none; }
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
        
        <!-- Filter Buttons -->
        <div class="text-center my-4">
            <div class="btn-group flex-wrap justify-content-center">
                <button class="filter-button btn btn-outline-dark me-2 active" data-filter="all">All</button>
                <button class="filter-button btn btn-outline-dark me-2" data-filter="evhi">Event Highlights</button>
                <button class="filter-button btn btn-outline-dark me-2" data-filter="vtr">VTR with YES Acting</button>
                <button class="filter-button btn btn-outline-dark me-2" data-filter="aud">Auditions & Screen Tests</button>
                <button class="filter-button btn btn-outline-dark me-2" data-filter="ads">Advertisements</button>
            </div>
        </div>
        
        <div class="container py-4 px-3 bg-white rounded-4 shadow-sm">
            <div class="swiper vtr-swiper">
                <div class="swiper-wrapper">
                    <?php foreach($videos as $video): ?>
                    <div class="swiper-slide item <?php echo h($video['category']); ?>">
                        <div class="ratio ratio-16x9">
                            <iframe src="<?php echo h($video['youtube_embed_url']); ?>" 
                                    title="<?php echo h($video['title']); ?>" 
                                    allowfullscreen></iframe>
                        </div>
                        <h5 class="mt-2 text-center"><?php echo h($video['title']); ?></h5>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>
    
    <?php include 'footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let mySwiper;
        const allSlides = document.querySelectorAll('.vtr-swiper .swiper-slide');
        
        function initSwiper(filter) {
            if (mySwiper) {
                mySwiper.destroy(true, true);
            }
            
            allSlides.forEach(slide => {
                if (filter === 'all' || slide.classList.contains(filter)) {
                    slide.style.display = '';
                } else {
                    slide.style.display = 'none';
                }
            });
            
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
            });
        }
        
        const buttons = document.querySelectorAll('.filter-button');
        buttons.forEach(btn => {
            btn.addEventListener('click', () => {
                const filter = btn.dataset.filter;
                buttons.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                initSwiper(filter);
            });
        });
        
        initSwiper('all');
    });
    </script>
</body>
</html>