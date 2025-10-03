<?php
require_once 'config.php';

try {
    $conn = getDBConnection();

    // Fetch partners
    $stmt = $conn->prepare("SELECT * FROM partners ORDER BY sort_order ASC");
    $stmt->execute();
    $partners = $stmt->fetchAll();

    // Fetch approved testimonials
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE is_approved = 1 LIMIT 3");
    $stmt->execute();
    $testimonials = $stmt->fetchAll();

    // Fetch gallery portraits (limit 6 for homepage)
    $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC LIMIT 6");
    $stmt->execute();
    $portraits = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Error: " . $e->getMessage());
    $partners = [];
    $testimonials = [];
    $portraits = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Jade S.</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <meta property="og:title" content="Jade Salvador | Executive VA • Model • Actress">
    <meta property="og:description"
        content="Explore Jade Salvador's professional portfolio as an Executive Virtual Assistant, freelance model, and actress.">
    <meta property="og:image" content="https://cor3solutions.github.io/MJ-Salvador/images/icon.png">
    <meta property="og:url" content="https://jadesalvador.com">

    <link rel="icon" type="image/png" href="images/logo.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="css/vendor.css">
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
        rel="stylesheet">
</head>

<body>
    <?php include 'navbar.php'; ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

 <!-- Banner Section -->
  <section class="banner-section position-relative text-center py-5" style="background-color: #ffe4ec;">
    <!-- Swiper container for sliding images and videos -->
    <div class="main-banner swiper">
      <div class="swiper-wrapper">

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/3.1.png" alt="Slide 8">
          </div>
        </div>

        <!-- Slide 2: Video -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/member-02.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>

        <!-- Slide 3: Image -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/member-06.jpg" alt="Slide 3">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/2nd.png" alt="Slide 3">
          </div>
        </div>

        <!-- Slide 4: Video -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/747.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>

        <!-- Slide 1: Image -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/missu.png" alt="Slide 1">
          </div>
        </div>

        <!-- Slide 5: Image -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/1st.png" alt="Slide 5">
          </div>
        </div>

        <!-- Slide 6: Video -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/litolsweets.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/3rd.png" alt="Slide 5">
          </div>
        </div>

        <!-- Slide 7: Image -->
        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/heera.jpg" alt="Slide 8">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/abc.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/1.1.png" alt="Slide 5">
          </div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/tg.mp4" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/littlesweets.png" alt="Slide 6">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/blue.png" alt="Slide 6">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>
              <source src="images/SWIPE/heeravid.mov" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
        </div>

      </div>
    </div>

    <!-- Intro box: brief info about yourself -->
    <div class="intro-box col-lg-5 p-5 bg-black bg-opacity-75 text-white rounded-4 shadow-sm"
      style="transition: all 0.3s ease;">
      <h3 class="display-4 mb-3 text-white" style="font-size: 2rem;">
        Executive Virtual Assistant <br>Freelance Model <br> Actress
      </h3>
      <p class="fs-6 text-white">
        Behind the camera organization to striking visuals on stage and ramp, I bring your vision come alive with style
        and energy that elevate every project. Aiming to blend professionalism with creativity aiding fashion brands and
        events bring their creativity to reality.
      </p>
      <a href="https://www.facebook.com/jaddengg" target="_blank" class="btn btn-primary p-3 mt-2 w-100 rounded-2"
        style="background-color: #cd919e; border: none;">
        Let's collaborate!
      </a>
    </div>
  </section>

    <section class="container-xxl py-5 bg-white">
        <div class="container text-center">
            <h4 class="section-title">Partners</h4>
            <h1 class="display-5 mb-5">Valued Collaborations</h1>

            <div class="client-marquee-wrapper">
                <div class="client-marquee marquee-right">
                    <div class="marquee-content">
                        <div class="loop-set">
                            <?php foreach ($partners as $partner): ?>
                                <div class="client-logo">
                                    <img src="/images/partners/<?php echo h($partner['logo_image_file']); ?>"
                                        alt="<?php echo h($partner['name']); ?>">
                                    <div><?php echo h($partner['name']); ?></div>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($partners as $partner): ?>
                                <div class="client-logo">
                                    <img src="/images/partners/<?php echo h($partner['logo_image_file']); ?>"
                                        alt="<?php echo h($partner['name']); ?>">
                                    <div><?php echo h($partner['name']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Gallery Overview -->
    <section class="my-5 py-5 bg-text" style="background-color: #ffe4ec;">
        <div class="container">
            <div class="text-center pt-4 mt-4">
                <span class="text-muted text-uppercase">Keeping you on the loop</span>
                <h4 class="display-5 fw-normal mt-2">Gallery Overview</h4>
            </div>

            <div class="swiper mySwiper mt-4">
                <div class="swiper-wrapper">
                    <?php foreach ($portraits as $portrait): ?>
                        <div class="swiper-slide">
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

                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>

            <div class="text-center mt-5">
                <a href="portraits.php"
                    class="btn btn-dark px-5 py-3 rounded-pill shadow-lg fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="bi bi-images"></i> View Full Gallery
                </a>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-5 bg-light">
        <div class="container">
            <div class="text-center mb-5">
                <h4 class="section-title text-uppercase">Testimonials</h4>
                <h2 class="display-5 fw-semibold">Feedbacks</h2>
                <p class="text-muted">Real words from people I've worked with</p>
            </div>

            <div class="swiper testimonialSwiper">
                <div class="swiper-wrapper">
                    <?php foreach ($testimonials as $testimonial): ?>
                        <div class="swiper-slide">
                            <div class="testimonial-card shadow-lg p-5 text-center">
                                <div class="quote-icon">"</div>
                                <p class="fs-5 fst-italic">
                                    "<?php echo h($testimonial['quote_text']); ?>"
                                </p>
                                <div class="client-info mt-4">
                                    <h5 class="mb-0"><?php echo h($testimonial['client_name']); ?></h5>
                                    <?php if ($testimonial['client_title']): ?>
                                        <span class="text-muted small"><?php echo h($testimonial['client_title']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-pagination mt-4"></div>
            </div>
        </div>
    </section>

    <?php include 'footer.php'; ?>

    <script src="js/jquery-1.11.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>
    <script src="js/plugins.js"></script>
    <script src="js/script.js"></script>
</body>

</html>