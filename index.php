<?php
require_once 'config.php';

if (!function_exists('h')) {
    function h($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

try {
    $conn = getDBConnection();

    // Fetch partners
    $stmt = $conn->prepare("SELECT * FROM partners ORDER BY sort_order ASC");
    $stmt->execute();
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch approved testimonials (limit 3 for homepage)
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY testimonial_id DESC LIMIT 3");
    $stmt->execute();
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch gallery portraits (limit 6 for homepage)
    $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC LIMIT 6");
    $stmt->execute();
    $portraits = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch professional experiences
    $stmt = $conn->prepare("SELECT * FROM experiences ORDER BY sort_order ASC, exp_id DESC");
    $stmt->execute();
    $experiences = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database Error on Homepage: " . $e->getMessage());
    $partners = [];
    $testimonials = [];
    $portraits = [];
    $experiences = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Jade S. | Executive VA • Model • Actress</title>

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

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">


  <link rel="stylesheet" href="css/vendor.css">

  <link rel="stylesheet" href="css/style.css">

  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"  
    rel="stylesheet">

  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
  <?php include 'navbar.php'; ?>

  <style>
    /* banner section */
    .intro-box {
      position: absolute;
      bottom: -100px;
      z-index: 9;
      left: 50%;
      transform: translateX(-50%);
    }

    .quote-box {
      position: absolute;
      width: 100%;
      left: 0;
      bottom: 0;
      z-index: 9;
    }

    .highlights-box {
      position: absolute;
      width: 100%;
      left: 0;
      bottom: 0;
      z-index: 9;
    }

    @media only screen and (max-width: 991px) {
      .banner-section {
        transform: translateY(100px);
      }

      .intro-box {
        position: relative;
        bottom: 0;
      }

      .quote-box {
        position: relative;
        bottom: 0;
      }

      .highlights-box {
        position: relative;
        bottom: 0;
      }
    }

    @media only screen and (max-width: 991px) {
      .course-content {
        flex-direction: column-reverse;
      }
    }

    .card img {
      transition: transform 0.3s ease;
    }

    .card:hover img {
      transform: scale(1.05);
    }

    /* Add padding to the column items (the cards/containers for each video) */
    /* This creates space around the video and its title within each column */
    .vtr-item {
      padding: 15px;
      /* Adjust this value as needed for desired spacing */
    }

    .ratio-9x16 {
      position: relative;
      width: 100%;
      padding-top: 177.77%;
      /* This correctly maintains the 9:16 aspect ratio */

      /* Add margin to the bottom to create space between the video and its title */
      margin-bottom: 15px;
      /* Adjust this value as needed */
    }

    .ratio-9x16 iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    /* Desktop (default) */
    .fixed-slide {
      width: 3in;
      height: 5in;
      overflow: hidden;
      position: relative;
      margin: auto;
    }

    .fixed-slide img,
    .fixed-slide video {
      object-fit: cover;
      width: 100%;
      height: 100%;
      display: block;
    }

    .swiper {
      padding-top: 20px;
      padding-bottom: 40px;
    }

    /* 📱 Mobile Layout Refined */
    @media (max-width: 768px) {
      .swiper-wrapper {
        flex-wrap: nowrap !important;
      }

      .swiper-slide {
        display: flex !important;
        justify-content: center;
        align-items: center;
        flex: 0 0 auto;
        width: 90vw;
      }

      .fixed-slide {
        max-width: 90vw;
        height: auto;
        aspect-ratio: 3 / 5;
        overflow: hidden;
        margin: 10px auto;
        border-radius: 12px;
      }

      .fixed-slide img,
      .fixed-slide video {
        object-fit: cover;
        width: 100%;
        height: 100%;
        display: block;
      }

      .swiper {
        padding: 20px 15px 40px;
      }
    }

    /* === FIXED MODAL COLORS === */
    .modal-content {
      background-color: #fff !important;
      color: #333 !important;
      border-radius: 16px;
      border: none;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
    }

    .modal-header {
      background-color: #ffe4ec;
      border-bottom: 3px solid #cd919e;
      border-top-left-radius: 16px;
      border-top-right-radius: 16px;
    }

    .modal-title {
      font-weight: 700;
      color: #333;
    }

    .modal-body {
      background-color: #fff !important;
    }

    .modal-footer {
      background-color: #fff !important;
      border-top: 1px solid #eee;
    }

    .form-label {
      color: #333 !important;
      font-weight: 600;
    }

    .form-control,
    .form-select {
      background-color: #fff;
      color: #333;
      border: 1px solid #ccc;
      border-radius: 8px;
    }

    .btn-submit-inquiry {
      background-color: #cd919e;
      color: white;
      border: none;
      font-weight: 600;
      border-radius: 30px;
      transition: 0.2s;
    }

    .btn-submit-inquiry:hover {
      background-color: #b87c88;
    }

    .btn-close {
      filter: invert(0);
    }

    /* Optional: fade overlay to be slightly transparent black */
    .modal-backdrop.show {
      opacity: 0.6 !important;
      background-color: rgba(0, 0, 0, 0.6);
    }
  </style>
  <section class="banner-section position-relative text-center py-5" style="background-color: #ffe4ec;">
    <div class="main-banner swiper">
      <div class="swiper-wrapper">

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/3.1.png" alt="Slide 8">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>

              <source src="images/SWIPE/member-02.mp4" type="video/mp4">
              Your browser does not support the video tag.

            </video>
          </div>
          <div class="video-overlay"></div>
        </div>

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

        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>

              <source src="images/SWIPE/747.mp4" type="video/mp4">
              Your browser does not support the video tag.

            </video>
          </div>
          <div class="video-overlay"></div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/missu.png" alt="Slide 1">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/1st.png" alt="Slide 5">
          </div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <video autoplay muted loop playsinline>

              <source src="images/SWIPE/litolsweets.mp4" type="video/mp4">
              Your browser does not support the video tag.

            </video>
          </div>
          <div class="video-overlay"></div>
        </div>

        <div class="swiper-slide">
          <div class="fixed-slide">
            <img src="images/SWIPE/3rd.png" alt="Slide 5">
          </div>

        </div>

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
          <div class="video-overlay"></div>
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
          <div class="video-overlay"></div>
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
          <div class="video-overlay"></div>
        </div>

      </div>
    </div>

    <div class="intro-box col-lg-5 p-5 bg-black bg-opacity-75 text-white rounded-4 shadow-sm"      
      style="transition: all 0.3s ease;">
      <h3 class="display-4 mb-3 text-white" style="font-size: 2rem;">
        Executive Virtual Assistant <br>Freelance Model <br> Actress
      </h3>
      <p class="fs-6 text-white">
        Behind the camera organization to striking visuals on stage and ramp, I bring your vision come alive
        with style
        and energy that elevate every project. Aiming to blend professionalism with creativity aiding fashion
        brands and
        events bring their creativity to reality.
      </p>
      <button type="button" class="btn btn-primary p-3 mt-2 w-100 rounded-2"        
        style="background-color: #cd919e; border: none;" data-bs-toggle="modal" data-bs-target="#inquiryModal">
        Let's collaborate!
      </button>
    </div>
  </section>
  <br><br>
 <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="inquiryModalLabel">Send Us Your Inquiry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="inquiryForm">
        <div class="modal-body p-4">
          <p class="text-muted mb-4">Tell us about your project or collaboration idea! We'll get back to you within 24–48 hours.</p>

          <div class="row">
            <div class="col-md-6 mb-3">
              <label for="inquiryName" class="form-label">Your Full Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="inquiryName" name="full_name" required>
            </div>
            <div class="col-md-6 mb-3">
              <label for="inquiryEmail" class="form-label">Email Address <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="inquiryEmail" name="email" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="inquiryPhone" class="form-label">Phone Number (Optional)</label>
            <input type="tel" class="form-control" id="inquiryPhone" name="phone_number">
          </div>

          <div class="mb-3">
            <label for="inquiryType" class="form-label">Type of Inquiry <span class="text-danger">*</span></label>
            <select class="form-select" id="inquiryType" name="inquiry_type" required>
              <option value="" selected disabled>Select...</option>
              <option value="Executive Virtual Assistant">Executive Virtual Assistant</option>
              <option value="Modeling/Acting Booking">Modeling / Acting Booking</option>
              <option value="Collaboration">Collaboration / Project Idea</option>
              <option value="General Question">General Question</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="inquiryMessage" class="form-label">Message / Project Details <span class="text-danger">*</span></label>
            <textarea class="form-control" id="inquiryMessage" name="message" rows="4" required></textarea>
          </div>
        </div>
        <div class="modal-footer d-flex justify-content-between">
          <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-submit-inquiry px-5" id="submitBtn">Send Inquiry</button>
        </div>
      </form>
    </div>
  </div>
</div>

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
                  <img src="images/partners/<?php echo h($partner['logo_image_file']); ?>"              
                    alt="<?php echo h($partner['name']); ?>">
                  <div><?php echo h($partner['name']); ?></div>
                </div>
              <?php endforeach; ?>

              <?php foreach ($partners as $partner): ?>
                <div class="client-logo">
                  <img src="images/partners/<?php echo h($partner['logo_image_file']); ?>"              
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

  <section class="my-5 py-5 bg-text" style="background-color: #ffe4ec;">
    <div class="container">
      <div class="text-center pt-4 mt-4">
        <span class="text-muted text-uppercase">Keeping you on the loop</span>
        <h4 class="display-5 fw-normal mt-2">Gallery Overview</h4>
        <p class="text-muted">A curated selection of my latest work</p>
      </div>

      <div class="swiper mySwiper mt-4">
        <div class="swiper-wrapper">
          <?php foreach ($portraits as $portrait): ?>
            <div class="swiper-slide">
              <div class="card border-0 shadow-lg">
                <div class="card-img-container">
                  <img src="images/portraits/<?php echo h($portrait['image_filename']); ?>"            
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
<!-- ===== Experiences Section (Dynamic, Updated for 2-Column CV Layout) ===== -->
<section id="experiences" class="py-5 bg-light">
  <div class="container">
    <!-- Main Title -->
    <div class="text-center mb-5">
      <h2 class="fw-bold fs-1" style="color: #494949;">Experiences</h2>
      <p class="text-muted fs-5">
        A glimpse into my professional journey, collaborations, and creative ventures.
      </p>
    </div>

    <div class="row">
      <!-- LEFT COLUMN -->
      <div class="col-md-6 border-end border-muted pe-md-5 mb-5 mb-md-0">
        <?php
        // Define left-side categories
        $left_categories = [
          'Professional Experience',
          'Events & Modeling - Modeled & Ushered For',
          'Events & Modeling - Runway',
          'Events & Modeling - Advertised Condominiums'
        ];

        $left_experiences = array_filter($experiences, function ($exp) use ($left_categories) {
          return in_array($exp['category'], $left_categories);
        });

        if (!empty($left_experiences)):
          $grouped_left = [];
          foreach ($left_experiences as $exp) {
            $grouped_left[$exp['category']][] = $exp;
          }

          foreach ($grouped_left as $category => $items): ?>
            <h4 class="fw-bold mb-3 pb-2 border-bottom border-secondary" style="color: #494949;">
              <?php echo h($category); ?>
            </h4>

            <?php foreach ($items as $item): ?>
              <div class="mb-4">
                <p class="fw-semibold mb-1"><?php echo h($item['title']); ?></p>
                <?php if (!empty($item['subtitle'])): ?>
                  <p class="small text-muted mb-1"><?php echo h($item['subtitle']); ?></p>
                <?php endif; ?>
                <?php if (!empty($item['date_range'])): ?>
                  <p class="small fst-italic text-secondary mb-1"><?php echo h($item['date_range']); ?></p>
                <?php endif; ?>
                <?php if (!empty($item['details'])): ?>
                  <p class="small text-muted mb-0"><?php echo nl2br(h($item['details'])); ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach;
          endforeach;
        else: ?>
          <p class="small text-muted">No experiences found on this side.</p>
        <?php endif; ?>
      </div>

      <!-- RIGHT COLUMN -->
      <div class="col-md-6 ps-md-5">
        <?php
        // Define right-side categories
        $right_categories = [
          'Brand Ambassadress Currently For',
          'TV & Commercials',
          'Hosted For'
        ];

        $right_experiences = array_filter($experiences, function ($exp) use ($right_categories) {
          return in_array($exp['category'], $right_categories);
        });

        if (!empty($right_experiences)):
          $grouped_right = [];
          foreach ($right_experiences as $exp) {
            $grouped_right[$exp['category']][] = $exp;
          }

          foreach ($grouped_right as $category => $items): ?>
            <h4 class="fw-bold mb-3 pb-2 border-bottom border-secondary" style="color: #494949;">
              <?php echo h($category); ?>
            </h4>

            <?php foreach ($items as $item): ?>
              <div class="mb-4">
                <p class="fw-semibold mb-1"><?php echo h($item['title']); ?></p>
                <?php if (!empty($item['subtitle'])): ?>
                  <p class="small text-muted mb-1"><?php echo h($item['subtitle']); ?></p>
                <?php endif; ?>
                <?php if (!empty($item['date_range'])): ?>
                  <p class="small fst-italic text-secondary mb-1"><?php echo h($item['date_range']); ?></p>
                <?php endif; ?>
                <?php if (!empty($item['details'])): ?>
                  <p class="small text-muted mb-0"><?php echo nl2br(h($item['details'])); ?></p>
                <?php endif; ?>
              </div>
            <?php endforeach;
          endforeach;
        else: ?>
          <p class="small text-muted">No experiences found on this side.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>



  <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-pink': '#cd919e',
                        'soft-pink': '#fef7f9',
                        'dark-text': '#1f2937',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom styles for Swiper pagination and card aesthetics */
        body { font-family: 'Inter', sans-serif; background-color: #f7f7f7; }
        .form-control { border-radius: 0.5rem; padding: 0.75rem 1rem; border: 1px solid #e5e7eb; transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out; }
        .form-control:focus { border-color: #cd919e; box-shadow: 0 0 0 3px rgba(205, 145, 158, 0.25); outline: none; }
        .swiper-pagination-bullet { width: 10px; height: 10px; opacity: 1; background: #d1d5db; transition: background-color 0.3s; }
        .swiper-pagination-bullet-active { background: #cd919e; width: 12px; height: 12px; }
        .testimonial-card { border-radius: 1rem; background-color: white; transition: transform 0.3s ease-in-out; border: 1px solid #f3f4f6; }
        .testimonial-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
        .quote-icon svg { color: #d1d5db; opacity: 0.7; }
        .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); display: none; align-items: center; justify-content: center; z-index: 1000; transition: opacity 0.3s ease; opacity: 0; }
        .modal.open { display: flex; opacity: 1; }
        .modal-content { background: white; border-radius: 1rem; max-width: 90%; width: 450px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <!-- Load Swiper CSS and JS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

</head>
<body class="bg-soft-pink text-dark-text">

    <?php
        // --- 1. Database Connection and Testimonial Fetch Logic ---
        $testimonials = [];
        $db_error = null;

        // Ensure the helper function exists, as it's used in the display loop
        if (!function_exists('h')) {
            function h($text) {
                return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            }
        }
        
        // This file must exist and contain the getDBConnection() function
        require_once 'config.php'; 

        try {
             // Establish connection using the function from config.php
             $conn = getDBConnection(); 

             // Query to fetch ONLY APPROVED testimonials (is_approved = 1)
             $sql = "SELECT quote_text, client_name, client_title 
                     FROM testimonials 
                     WHERE is_approved = 1 
                     ORDER BY testimonial_id DESC"; // Show newest first
                     
             $stmt = $conn->query($sql);
             $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
             // Log error for internal debugging
             error_log("Testimonial Fetch Error: " . $e->getMessage());
             
             // Set a user-friendly error message
             $db_error = "We are currently unable to load testimonials due to a server issue.";
        }
        
        $has_testimonials = !empty($testimonials);
    ?>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-12 md:py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h4 class="text-sm font-semibold uppercase tracking-wider text-primary-pink">Testimonials</h4>
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Feedbacks</h2>
                <p class="mt-4 text-xl text-gray-500">Real words from people I've worked with</p>
                <?php if (isset($db_error)): ?>
                    <!-- Display DB connection/query error if it occurred -->
                    <p class="mt-4 text-red-600 font-medium bg-red-50 p-3 rounded-lg border border-red-200"><?php echo h($db_error); ?></p>
                <?php endif; ?>
            </div>

            <div class="flex justify-center mb-8">
                <button id="openModalBtn" class="px-6 py-3 text-lg font-medium rounded-full text-white bg-primary-pink hover:bg-pink-700 transition duration-300 shadow-md hover:shadow-lg" onclick="openModal('testimonialModal')">
                    Submit Your Feedback
                </button>
            </div>
            
            <div class="swiper testimonialSwiper relative">
                <div id="swiperWrapper" class="swiper-wrapper">
                    <!-- Testimonial slides are rendered here by PHP from MySQL -->
                    <?php if ($has_testimonials): ?>
                        <?php foreach ($testimonials as $testimonial): ?>
                            <div class="swiper-slide !h-auto">
                                <div class="testimonial-card h-full shadow-xl p-8 lg:p-12 text-center flex flex-col justify-between">
                                    <div class="quote-content">
                                        <div class="quote-icon mb-4 flex justify-center">
                                            <!-- Quote icon SVG for aesthetics -->
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 10H5a2 2 0 00-2 2v4a2 2 0 002 2h4V10zm5-4h5a2 2 0 012 2v4a2 2 0 01-2 2h-5V6z"/>
                                            </svg>
                                        </div>
                                        <p class="text-xl italic mb-6 text-gray-700">
                                            "<?php echo h($testimonial['quote_text']); ?>"
                                        </p>
                                    </div>
                                    <div class="client-info mt-auto">
                                        <h5 class="text-lg font-semibold mb-0"><?php echo h($testimonial['client_name']); ?></h5>
                                        <?php if (!empty($testimonial['client_title'])): ?>
                                            <span class="text-sm text-gray-500"><?php echo h($testimonial['client_title']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Fallback content if no approved testimonials are loaded -->
                        <div class="swiper-slide p-6 flex justify-center items-center w-full">
                            <div class="text-center text-gray-500 p-10 bg-white rounded-xl shadow-lg w-full max-w-lg">
                                <p class="text-lg">No approved testimonials found yet. Be the first to share your feedback!</p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
                <!-- Add Pagination -->
                <div class="swiper-pagination mt-8"></div>
            </div>
        </div>
    </section>

    <!-- Modal for Testimonial Submission -->
    <div id="testimonialModal" class="modal" tabindex="-1" aria-labelledby="testimonialModalLabel" aria-hidden="true" role="dialog">
        <div class="modal-content p-6">
            <div class="flex justify-between items-center pb-4 border-b">
                <h5 class="text-2xl font-bold" id="testimonialModalLabel">Share Your Feedback</h5>
                <button type="button" class="text-gray-400 hover:text-gray-600 transition" onclick="closeModal('testimonialModal')" aria-label="Close">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="py-6">
                <p id="submissionMessage" class="hidden text-center p-3 rounded-lg"></p>
                <p class="text-sm text-gray-500 mb-4">Your submission will be reviewed and approved before being displayed on the site.</p>

                <form id="testimonialForm">
                    <div class="mb-4">
                        <label for="modal_client_name" class="block text-sm font-medium text-gray-700 mb-1">Your Name <span class="text-red-500">*</span></label>
                        <input type="text" class="form-control w-full" id="modal_client_name" name="client_name" required placeholder="E.g., Alex Johnson">
                    </div>

                    <div class="mb-4">
                        <label for="modal_client_title" class="block text-sm font-medium text-gray-700 mb-1">Your Role/Company (Optional)</label>
                        <input type="text" class="form-control w-full" id="modal_client_title" name="client_title" placeholder="E.g., CEO, Acme Corp.">
                    </div>

                    <div class="mb-6">
                        <label for="modal_quote_text" class="block text-sm font-medium text-gray-700 mb-1">Your Testimonial <span class="text-red-500">*</span></label>
                        <textarea class="form-control w-full" id="modal_quote_text" name="quote_text" rows="4" required placeholder="Write your feedback here..."></textarea>
                    </div>

                    <div class="flex justify-end pt-4 border-t">
                        <button type="button" class="px-5 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition duration-150 mr-3" onclick="closeModal('testimonialModal')">Close</button>
                        <button type="submit" class="px-5 py-2 font-medium rounded-lg text-white bg-primary-pink hover:bg-pink-700 transition duration-150" id="submitTestimonialBtn">
                            Submit Feedback
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- JavaScript Setup for Swiper and AJAX Submission -->
    <script>
        let swiperInstance = null;
        const form = document.getElementById('testimonialForm');
        const submitBtn = document.getElementById('submitTestimonialBtn');
        const messageBox = document.getElementById('submissionMessage');
        // Ensure this matches the name of your PHP script
        const submissionEndpoint = 'submit_testimonial.php'; 

        // Initialize Swiper after the window loads and PHP has rendered the content
        window.onload = function() {
            // Count the slides rendered by PHP
            const slides = document.querySelectorAll('#swiperWrapper .swiper-slide');
            const numTestimonials = slides.length;
            
            // Only initialize Swiper if there are slides
            if (numTestimonials > 0) {
                swiperInstance = new Swiper(".testimonialSwiper", {
                    slidesPerView: 1,
                    spaceBetween: 30,
                    // Enable looping only if there's more than one testimonial
                    loop: numTestimonials > 1, 
                    centeredSlides: true,
                    pagination: {
                        el: ".swiper-pagination",
                        clickable: true,
                    },
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                    breakpoints: {
                        640: { slidesPerView: 1.2, spaceBetween: 20, },
                        1024: { slidesPerView: 2.2, spaceBetween: 30, },
                    }
                });
            }
        };

        // Handles the AJAX form submission to your PHP backend
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            submitBtn.disabled = true;
            submitBtn.textContent = 'Submitting...';
            
            const formData = new FormData(form);

            try {
                const response = await fetch(submissionEndpoint, {
                    method: 'POST',
                    body: formData, 
                });
                
                // Expecting a JSON response from submit_testimonial.php
                const result = await response.json();

                if (response.ok && result.success) {
                    showMessage("success", result.message, 5000);
                    form.reset();
                    // Close modal and remind user about the manual approval step
                    setTimeout(() => closeModal('testimonialModal'), 1500);
                    setTimeout(() => showMessage("info", "Remember to refresh to see the change after approval.", 7000, 'bg-blue-100 text-blue-800'), 2000);
                } else {
                    // Handle validation errors or database insertion errors from the PHP script
                    throw new Error(result.message || "Server error occurred.");
                }

            } catch (error) {
                console.error("Error adding testimonial:", error);
                showMessage("error", `Submission failed: ${error.message}`, 5000);
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Submit Feedback';
            }
        });

        // Helper function for custom modal messages
        function showMessage(type, text, duration = 3000, customClasses = null) {
            messageBox.textContent = text;
            messageBox.className = 'text-center p-3 rounded-lg';

            if (customClasses) {
                messageBox.classList.add(...customClasses.split(' '));
            } else if (type === "success") {
                messageBox.classList.add('bg-green-100', 'text-green-800');
            } else if (type === "error") {
                messageBox.classList.add('bg-red-100', 'text-red-800');
            } else {
                 // info/default
                 messageBox.classList.add('bg-gray-100', 'text-gray-700');
            }
            
            messageBox.classList.remove('hidden');

            clearTimeout(window.messageTimeout);
            window.messageTimeout = setTimeout(() => {
                messageBox.classList.add('hidden');
            }, duration);
        }

        // Modal control functions
        window.openModal = function(id) {
            document.getElementById(id).classList.add('open');
            document.body.style.overflow = 'hidden';
            messageBox.classList.add('hidden');
            form.reset();
        }

        window.closeModal = function(id) {
            document.getElementById(id).classList.remove('open');
            document.body.style.overflow = '';
        }
    </script>

  <?php include 'footer.php'; ?>


  <script src="js/jquery-1.11.0.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script src="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js"></script>

  <script src="js/plugins.js"></script>

  <script src="js/script.js"></script>


  <script>
    // Helper function to get the Bootstrap Modal instance
    const getBsModal = (id) => {
      const el = document.getElementById(id);
      if (!el) return null;
      return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    };

    // --- 1. Inquiry Form SweetAlert Logic (Updated for stability) ---
    const inquiryForm = document.getElementById('inquiryForm');

    if (inquiryForm) {
      inquiryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Send Inquiry';

        // Show loading state
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        }

        fetch('submit_inquiry.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Close the Bootstrap modal using the helper function
            const modal = getBsModal('inquiryModal');
            if (modal) modal.hide();

            Swal.fire({
              icon: data.success ? 'success' : 'error',
              title: data.success ? 'Success!' : 'Submission Failed',
              text: data.message,
              confirmButtonColor: '#cd919e'
            });

            if (data.success) form.reset();
          })
          .catch(error => {
            console.error('Inquiry Submission Error:', error);
            const modal = getBsModal('inquiryModal');
            if (modal) modal.hide();

            Swal.fire({
              icon: 'error',
              title: 'Network Error',
              text: 'Could not connect to the server. Please check your connection.',
              confirmButtonColor: '#cd919e'
            });
          })
          .finally(() => {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            }
          });
      });
    } else {
      console.warn("Inquiry form with ID 'inquiryForm' not found. SweetAlert will not work for it.");
    }


    // --- 2. Testimonial Form SweetAlert Logic (NEW) ---
    const testimonialForm = document.getElementById('testimonialForm');

    if (testimonialForm) {
      testimonialForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitTestimonialBtn');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit Feedback';

        // Show loading state
        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        }

        // NOTE: This assumes you have a separate backend file named submit_testimonial.php 
        // that returns a JSON response like: {success: true, message: "..."}
        fetch('submit_testimonial.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Close the Bootstrap modal using the helper function
            const modal = getBsModal('testimonialModal');
            if (modal) modal.hide();

            Swal.fire({
              icon: data.success ? 'success' : 'error',
              title: data.success ? 'Thank You!' : 'Submission Failed',
              text: data.message || (data.success ? 'Your testimonial has been submitted for review.' : 'An unknown error occurred.'),
              confirmButtonColor: '#cd919e'
            });

            if (data.success) form.reset();
          })
          .catch(error => {
            console.error('Testimonial Submission Error:', error);
            const modal = getBsModal('testimonialModal');
            if (modal) modal.hide();

            Swal.fire({
              icon: 'error',
              title: 'Network Error',
              text: 'Could not submit feedback. Please check your connection.',
              confirmButtonColor: '#cd919e'
            });
          })
          .finally(() => {
            if (submitBtn) {
              submitBtn.disabled = false;
              submitBtn.innerHTML = originalBtnText;
            }
          });
      });
    }

    var mainBannerSwiper = new Swiper(".main-banner", {
      loop: true,
      autoplay: {
        delay: 1000, //  (1 second)
        disableOnInteraction: false,
      },
      speed: 2000,
      slidesPerView: 1, // Base setting, overridden by breakpoints
      spaceBetween: 10,
      breakpoints: {
        // Mobile (0px and up)
        0: { slidesPerView: 3, spaceBetween: 20, centeredSlides: true },
        // Larger Tablet/Small Desktop (980px and up)
        980: { slidesPerView: 4, spaceBetween: 20, centeredSlides: true },
        // Large Desktop (1200px and up)
        1200: { slidesPerView: 5, spaceBetween: 20, centeredSlides: true }
      },
      pagination: false // disables pagination safely
    });

    // Initialization for the testimonialSwiper
    var testimonialSwiper = new Swiper(".testimonialSwiper", {
      // Basic settings
      loop: true,
      grabCursor: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },

      // Pagination (dots at the bottom)
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },

      // Key fix: Define different layouts for different screen sizes
      breakpoints: {
        // 0px and up (Mobile)
        0: {
          slidesPerView: 1, // Only show 1 testimonial
          spaceBetween: 20
        },
        // 768px and up (Tablet)
        768: {
          slidesPerView: 2, // Show 2 testimonials
          spaceBetween: 30
        },
        // 992px and up (Desktop)
        992: {
          slidesPerView: 5, // Show 3 testimonials side-by-side (The main goal)
          spaceBetween: 10
        }
      }
    });
  </script>
</body>

</html>