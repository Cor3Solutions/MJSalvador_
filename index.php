<?php
require_once 'config.php';

if (!function_exists('h')) {
  function h($text)
  {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}
if (!function_exists('array_chunk_preserve_keys')) {
  function array_chunk_preserve_keys(array $array, int $size, bool $preserve_keys = true): array
  {
    $chunks = [];
    if ($size <= 0) {
      return $chunks;
    }
    $count = count($array);
    for ($i = 0; $i < $count; $i += $size) {
      $chunks[] = array_slice($array, $i, $size, $preserve_keys);
    }
    return $chunks;
  }
}
try {
  $conn = getDBConnection();

  // Fetch only NON-ARCHIVED partners
  $stmt = $conn->prepare("SELECT * FROM partners WHERE is_archived = 0 ORDER BY sort_order ASC");
  $stmt->execute();
  $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch only NON-ARCHIVED and approved testimonials (limit 3 for homepage)
  $stmt = $conn->prepare("SELECT * FROM testimonials WHERE is_approved = 1 AND is_archived = 0 ORDER BY testimonial_id DESC LIMIT 3");
  $stmt->execute();
  $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch only NON-ARCHIVED gallery portraits (limit 6 for homepage)
  $stmt = $conn->prepare("SELECT * FROM portraits WHERE is_archived = 0 ORDER BY sort_order ASC LIMIT 6");
  $stmt->execute();
  $portraits = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch only NON-ARCHIVED professional experiences
  $stmt = $conn->prepare("SELECT * FROM opportunities WHERE is_active = 1 ORDER BY deadline ASC, opportunity_id DESC");
  $stmt->execute();
  $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log("Database Error on Homepage: " . $e->getMessage());
  $partners = [];
  $testimonials = [];
  $portraits = [];
  $opportunities = [];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Jade S. | Executive VA • Model • Actress</title>

  <meta charset="UTF-8">

  <meta http-equiv="X-UA-Compatible" content="IE=edge">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

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
    /* --- General Colors (Adjust to your brand palette if needed) --- */
    :root {
      --jade-primary: #4CAF50;
      /* A pleasant green/jade */
      --jade-primary-hover: #45a049;
      --text-primary: #333;
      --bg-light: #f8f9fa;
      --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
      --border-color: #eee;
    }

    /* --- Opportunities Section Styling --- */

    /* Ensure the cards look nice and elevated */
    .card {
      border: none;
      border-radius: 12px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .card-title {
      color: var(--text-primary);
      font-weight: 700;
    }

    /* Badge styling for job type */
    .badge.bg-primary {
      background-color: var(--jade-primary) !important;
      font-weight: 500;
      padding: 0.4em 0.8em;
      border-radius: 6px;
    }

    /* Button styling */
    .btn-outline-primary {
      color: var(--jade-primary);
      border-color: var(--jade-primary);
      transition: all 0.2s;
    }

    .btn-outline-primary:hover {
      background-color: var(--jade-primary);
      color: white;
    }

    /* --- Swiper Navigation Styling (Crucial for Design) --- */

   /* --- Swiper Navigation Styling (Fix for Overflow) --- */

.swiper-opportunities {
    /* Set a max-width to match the content above (Bootstrap's LG or XL container size) */
    /* This ensures it aligns with the pink section's content width */
    max-width: 1140px; /* Example: Matches Bootstrap's .container-xl */
    
    /* Center the block element */
    margin: 0 auto; 
    
    /* Crucial: Padding removed from the container to prevent visual overflow */
    padding: 0; 
    overflow: hidden; 
    
    padding-top: 20px;
    padding-bottom: 40px;
}

/* Style the Swiper custom navigation arrows */
.swiper-button-next,
.swiper-button-prev {
    /* Set the buttons to position themselves relative to the container, not the viewport */
    top: 50%;
    transform: translateY(-50%);
    
    /* Position them slightly outside the wrapper for a clean look, but the overall element is constrained by max-width */
    /* Use 'calc' to push the buttons 15px from the edge of the max-width */
    z-index: 10;
}

.swiper-button-next {
    right: 5px; /* Adjust as needed */
}
.swiper-button-prev {
    left: 5px; /* Adjust as needed */
}

/* Responsive adjustment for phones */
@media (max-width: 1200px) {
    /* On medium screens, adjust max-width to match that container size */
    .swiper-opportunities {
        max-width: 960px; /* Example: Matches Bootstrap's .container-lg */
    }
}
@media (max-width: 767.98px) {
    .swiper-opportunities {
        /* On mobile, use the full viewport width minus container padding */
        max-width: 100%; 
        padding: 0 15px; /* Add slight padding for mobile swipe edge */
    }
    
    /* Hide the arrows on small screens for a cleaner, touch-focused experience */
    .swiper-button-next,
    .swiper-button-prev {
        display: none; 
    }
}

/* Sets a reasonable minimum height for the card itself */
.swiper-opportunities .card {
    /* If your cards are getting too tall, uncomment this line */
    /* max-height: 500px; */ 
    
    /* Ensure all cards have at least enough space for content + button */
    min-height: 350px; 
}

/* Ensure the card body expands to fill available space */
.swiper-opportunities .card .card-body {
    flex-grow: 1;
}

/* Fix for buttons being pushed too low */
.swiper-opportunities .card .card-body .btn-outline-primary {
    /* Revert this line if you use the recommended HTML structure with mt-auto */
    /* margin-top: auto; */ 
}

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

    .vtr-item {
      padding: 15px;
    }

    .ratio-9x16 {
      position: relative;
      width: 100%;
      padding-top: 177.77%;
      margin-bottom: 15px;
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
              Your browser does not support the video tag">
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

  <!-- Inquiry Modal with CSRF Token -->
  <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inquiryModalLabel">Send Us Your Inquiry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="inquiryForm">
          <!-- CSRF Token Hidden Field -->
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div class="modal-body p-4">
            <p class="text-muted mb-4">Tell us about your project or collaboration idea! We'll get back to you within
              24–48 hours.</p>

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
              <label for="inquiryMessage" class="form-label">Message / Project Details <span
                  class="text-danger">*</span></label>
              <textarea class="form-control" id="inquiryMessage" name="message" rows="4" required></textarea>
            </div>
          </div>
          <div class="modal-footer d-flex justify-content-between">
            <button type="button" class="btn btn-outline-secondary rounded-pill px-4"
              data-bs-dismiss="modal">Cancel</button>
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
        <p class="text-muted">A curated selection of my latest events</p>
      </div>

      <div class="swiper mySwiper mt-4">
        <div class="swiper-wrapper">
          <?php foreach ($portraits as $portrait): ?>
            <div class="swiper-slide">
              <div class="card border-0 shadow-lg">
                <div class="card-img-container">
                  <img src="<?php echo h($portrait['image_filename']); ?>" alt="<?php echo h($portrait['title']); ?>"
                    class="img-fluid rounded">
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

  <?php if (!empty($opportunities)): ?>
    <section id="opportunities" class="py-5" style="background-color: var(--bg-light);">
      
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold display-5" style="color: var(--text-primary);">Current Opportunities</h2>
          <p class="text-muted fs-5">Open positions and collaboration opportunities</p>
        </div>
      </div>
      <div class="container-fluid">
          <div class="row justify-content-center">
              <div class="col-12"> 
                  <div class="swiper-container swiper-opportunities">
                    <div class="swiper-wrapper">
                      <?php foreach ($opportunities as $opp): ?>
                        <div class="swiper-slide">
                          <div class="card h-100 shadow-sm hover-card">
                            <div class="card-body">
                              <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-dark"><?php echo h($opp['job_type']); ?></span>
                                <?php if ($opp['deadline']): ?>
                                  <small class="text-muted"><i class="bi bi-clock"></i>
                                    <?php echo date('M d', strtotime($opp['deadline'])); ?></small>
                                <?php endif; ?>
                              </div>
                              <h5 class="card-title"><?php echo h($opp['title']); ?></h5>
                              <?php if ($opp['location']): ?>
                                <p class="text-muted small"><i class="bi bi-geo-alt"></i> <?php echo h($opp['location']); ?></p>
                              <?php endif; ?>
                              <p class="card-text"><?php echo h(substr($opp['description'], 0, 120)); ?>...</p>
                              <button class="btn btn-outline-primary btn-sm w-100 mt-2"
                                onclick="showApplicationForm(<?php echo $opp['opportunity_id']; ?>, '<?php echo h($opp['title']); ?>', '<?php echo h($opp['job_type']); ?>')">
                                Apply Now
                              </button>
                            </div>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>

                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-pagination"></div>

                  </div>
              </div>
          </div>
      </div>
      </section>
<?php endif; ?>

  <div class="modal fade" id="applicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="applicationForm" action="submit_application.php" method="POST">
          <div class="modal-header">
            <h5 class="modal-title">Apply for: <span id="jobTitle"></span></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="opportunity_id" id="opportunity_id">
            <input type="hidden" name="job_type" id="application_job_type">

            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="full_name" class="form-label">Full Name *</label>
                <input type="text" class="form-control" id="full_name" name="full_name" required>
              </div>
              <div class="col-md-6 mb-3">
                <label for="email" class="form-label">Email *</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
            </div>

            <div class="mb-3">
              <label for="phone_number" class="form-label">Phone Number *</label>
              <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
            </div>

            <div id="talentApplicationFields" style="display: none;">
              <h6 class="mt-4 mb-3">Talent/Modeling Links</h6>
              <div class="mb-3">
                <label for="setcard_link" class="form-label">Set Card Link *</label>
                <input type="url" class="form-control" id="setcard_link" name="setcard_link"
                  placeholder="https://your-setcard-url.com">
                <small class="text-muted">Provide a link to your set card or portfolio</small>
              </div>
              <div class="mb-3">
                <label for="vtr_link" class="form-label">VTR/Demo Reel Link</label>
                <input type="url" class="form-control" id="vtr_link" name="vtr_link"
                  placeholder="https://youtube.com/watch?v=...">
                <small class="text-muted">Link to your video tape recording or demo reel (optional)</small>
              </div>
            </div>

            <div id="vaApplicationFields" style="display: none;">
              <h6 class="mt-4 mb-3">Resume/Portfolio Links</h6>
              <div class="mb-3">
                <label for="resume_cv_link" class="form-label">Resume/CV Link *</label>
                <input type="url" class="form-control" id="resume_cv_link" name="resume_cv_link"
                  placeholder="https://your-resume-url.com">
                <small class="text-muted">Link to your resume (Google Drive, Dropbox, etc.)</small>
              </div>
              <div class="mb-3">
                <label for="portfolio_link" class="form-label">Portfolio Link (Optional)</label>
                <input type="url" class="form-control" id="portfolio_link" name="portfolio_link"
                  placeholder="https://your-portfolio-url.com">
              </div>
            </div>

            <div class="mb-3">
              <label for="cover_letter" class="form-label">Cover Letter *</label>
              <textarea class="form-control" id="cover_letter" name="cover_letter" rows="4" required></textarea>
            </div>


            <div id="formMessage"></div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">Submit Application</button>
          </div>
        </form>
      </div>
    </div>
  </div>

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
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f7f7f7;
    }

    .form-control {
      border-radius: 0.5rem;
      padding: 0.75rem 1rem;
      border: 1px solid #e5e7eb;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
      border-color: #cd919e;
      box-shadow: 0 0 0 3px rgba(205, 145, 158, 0.25);
      outline: none;
    }

    .swiper-pagination-bullet {
      width: 10px;
      height: 10px;
      opacity: 1;
      background: #d1d5db;
      transition: background-color 0.3s;
    }

    .swiper-pagination-bullet-active {
      background: #cd919e;
      width: 12px;
      height: 12px;
    }

    .testimonial-card {
      border-radius: 1rem;
      background-color: white;
      transition: transform 0.3s ease-in-out;
      border: 1px solid #f3f4f6;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .quote-icon svg {
      color: #d1d5db;
      opacity: 0.7;
    }

    .custom-testimonial-modal {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1050;
      transition: opacity 0.3s ease;
      opacity: 0;
    }

    .custom-testimonial-modal.open {
      display: flex;
      opacity: 1;
    }

    .custom-testimonial-modal .modal-content {
      background: white;
      border-radius: 1rem;
      max-width: 90%;
      width: 450px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
      animation: fadeIn 0.3s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
  </style>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <?php
  // Fetch only NON-ARCHIVED and approved testimonials for display
  $testimonials = [];
  $db_error = null;

  try {
    $conn = getDBConnection();

    $sql = "SELECT quote_text, client_name, client_title 
              FROM testimonials 
              WHERE is_approved = 1 AND is_archived = 0 
              ORDER BY testimonial_id DESC";

    $stmt = $conn->query($sql);
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

  } catch (PDOException $e) {
    error_log("Testimonial Fetch Error: " . $e->getMessage());
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
          <p class="mt-4 text-red-600 font-medium bg-red-50 p-3 rounded-lg border border-red-200">
            <?php echo h($db_error); ?>
          </p>
        <?php endif; ?>
      </div>

      <div class="flex justify-center mb-8">
        <button id="openModalBtn"
          class="px-6 py-3 text-lg font-medium rounded-full text-white bg-primary-pink hover:bg-pink-700 transition duration-300 shadow-md hover:shadow-lg"
          onclick="openModal('testimonialModal')">
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
                      <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 opacity-50" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 10H5a2 2 0 00-2 2v4a2 2 0 002 2h4V10zm5-4h5a2 2 0 012 2v4a2 2 0 01-2 2h-5V6z" />
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

  <!-- REPLACE THE TESTIMONIAL MODAL SECTION IN index.php WITH THIS CODE -->

  <!-- Modal for Testimonial Submission with CSRF Token -->
  <div id="testimonialModal" class="custom-testimonial-modal" tabindex="-1" aria-labelledby="testimonialModalLabel"
    aria-hidden="true" role="dialog">
    <div class="modal-content p-6">
      <div class="flex justify-between items-center pb-4 border-b">
        <h5 class="text-2xl font-bold" id="testimonialModalLabel">Share Your Feedback</h5>
        <button type="button" class="text-gray-400 hover:text-gray-600 transition"
          onclick="closeModal('testimonialModal')" aria-label="Close">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="py-6">
        <p id="submissionMessage" class="hidden text-center p-3 rounded-lg"></p>
        <p class="text-sm text-gray-500 mb-4">Your submission will be reviewed and approved before being displayed on
          the site.</p>

        <form id="testimonialForm">
          <!-- CSRF Token Hidden Field -->
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div class="mb-4">
            <label for="modal_client_name" class="block text-sm font-medium text-gray-700 mb-1">Your Name <span
                class="text-red-500">*</span></label>
            <input type="text" class="form-control w-full" id="modal_client_name" name="client_name" required
              placeholder="E.g., Alex Johnson">
          </div>

          <div class="mb-4">
            <label for="modal_client_title" class="block text-sm font-medium text-gray-700 mb-1">Your Role/Company
              (Optional)</label>
            <input type="text" class="form-control w-full" id="modal_client_title" name="client_title"
              placeholder="E.g., CEO, Acme Corp.">
          </div>

          <div class="mb-6">
            <label for="modal_quote_text" class="block text-sm font-medium text-gray-700 mb-1">Your Testimonial <span
                class="text-red-500">*</span></label>
            <textarea class="form-control w-full" id="modal_quote_text" name="quote_text" rows="4" required
              placeholder="Write your feedback here..."></textarea>
          </div>

          <div class="flex justify-end pt-4 border-t">
            <button type="button"
              class="px-5 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition duration-150 mr-3"
              onclick="closeModal('testimonialModal')">Close</button>
            <button type="submit"
              class="px-5 py-2 font-medium rounded-lg text-white bg-primary-pink hover:bg-pink-700 transition duration-150"
              id="submitTestimonialBtn">
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
    window.onload = function () {
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
    window.openModal = function (id) {
      document.getElementById(id).classList.add('open');
      document.body.style.overflow = 'hidden';
      messageBox.classList.add('hidden');
      form.reset();
    }

    window.closeModal = function (id) {
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

  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script>
    // Replace the entire script section at the bottom of your index.php with this:

    // Helper function to get the Bootstrap Modal instance
    const getBsModal = (id) => {
      const el = document.getElementById(id);
      if (!el) return null;
      return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    };

    // --- 1. Inquiry Form SweetAlert Logic ---
    const inquiryForm = document.getElementById('inquiryForm');

    if (inquiryForm) {
      inquiryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Send Inquiry';

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
    }

    // --- 2. Testimonial Modal Functions (Custom, NOT Bootstrap) ---
    // These are ONLY for the testimonialModal which uses custom styling
    window.openModal = function (id) {
      // Only handle the custom testimonialModal
      if (id === 'testimonialModal') {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
        const messageBox = document.getElementById('submissionMessage');
        if (messageBox) messageBox.classList.add('hidden');
        const form = document.getElementById('testimonialForm');
        if (form) form.reset();
      }
    }

    window.closeModal = function (id) {
      // Only handle the custom testimonialModal
      if (id === 'testimonialModal') {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
      }
    }

    // --- 3. Testimonial Form Submission (Separate from inquiry) ---
    const testimonialForm = document.getElementById('testimonialForm');

    if (testimonialForm) {
      testimonialForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const submitBtn = document.getElementById('submitTestimonialBtn');
        const originalBtnText = submitBtn ? submitBtn.innerHTML : 'Submit Feedback';

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
        }

        fetch('submit_testimonial.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            closeModal('testimonialModal');

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
            closeModal('testimonialModal');

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

    // --- 4. Swiper Initializations ---
    var mainBannerSwiper = new Swiper(".main-banner", {
      loop: true,
      autoplay: {
        delay: 1000,
        disableOnInteraction: false,
      },
      speed: 2000,
      slidesPerView: 1,
      spaceBetween: 10,
      breakpoints: {
        0: { slidesPerView: 3, spaceBetween: 20, centeredSlides: true },
        980: { slidesPerView: 4, spaceBetween: 20, centeredSlides: true },
        1200: { slidesPerView: 5, spaceBetween: 20, centeredSlides: true }
      },
      pagination: false
    });

    var testimonialSwiper = new Swiper(".testimonialSwiper", {
      loop: true,
      grabCursor: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        clickable: true,
      },
      breakpoints: {
        0: {
          slidesPerView: 1,
          spaceBetween: 20
        },
        768: {
          slidesPerView: 2,
          spaceBetween: 30
        },
        992: {
          slidesPerView: 5,
          spaceBetween: 10
        }
      }
    });
    // Initialize Swiper after the DOM is loaded
    // Initialize Swiper after the DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
      const swiper = new Swiper('.swiper-opportunities', {
        // Core Settings
        direction: 'horizontal',
        loop: false,
        // Enable touch swipe on all devices
        simulateTouch: true,
        grabCursor: true,

        // Default: 1 slide per view on mobile
        slidesPerView: 1,
        spaceBetween: 20,

        // Pagination (dots)
        pagination: {
          el: '.swiper-pagination',
          clickable: true,
        },

        // Navigation arrows
        navigation: {
          nextEl: '.swiper-button-next',
          prevEl: '.swiper-button-prev',
        },

        // Responsive breakpoints
        breakpoints: {
          // Tablet/Small Desktop
          768: {
            slidesPerView: 2,
            spaceBetween: 30
          },
          // Large Desktop
          992: {
            slidesPerView: 3,
            spaceBetween: 40
          }
        },
      });
    });

    function showApplicationForm(opportunityId, jobTitle, jobType) {
      // 1. Set general data
      document.getElementById('opportunity_id').value = opportunityId;
      document.getElementById('jobTitle').textContent = jobTitle;
      document.getElementById('application_job_type').value = jobType;

      // 2. Reset form and messages
      document.getElementById('applicationForm').reset();
      document.getElementById('formMessage').innerHTML = '';

      // 3. Select field containers and required inputs
      const talentFields = document.getElementById('talentApplicationFields');
      const vaFields = document.getElementById('vaApplicationFields');
      const setcardLink = document.getElementById('setcard_link');
      const resumeLink = document.getElementById('resume_cv_link');

      // 4. Reset display and 'required' state for all custom fields
      talentFields.style.display = 'none';
      vaFields.style.display = 'none';
      setcardLink.required = false;
      resumeLink.required = false;

      // 5. Define talent job types and check condition
      const talentJobTypes = ['talent', 'brand-ambassador', 'usherette']; // Array for robust checking
      const isTalent = talentJobTypes.includes(jobType.toLowerCase());

      // 6. Conditionally show the correct fields and set their 'required' attribute
      if (isTalent) {
        // Show talent fields and require setcard
        talentFields.style.display = 'block';
        setcardLink.required = true;
      } else {
        // Show VA fields (for VA, Virtual Assistant, or any other type) and require resume
        vaFields.style.display = 'block';
        resumeLink.required = true;
      }

      // 7. Show the modal
      var modal = new bootstrap.Modal(document.getElementById('applicationModal'));
      modal.show();
    }

    // Handle form submission (keep this as is)
    document.getElementById('applicationForm').addEventListener('submit', function (e) {
      e.preventDefault();

      const formData = new FormData(this);
      const messageDiv = document.getElementById('formMessage');

      fetch('submit_application.php', {
        method: 'POST',
        body: formData
      })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            messageDiv.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            this.reset();
            setTimeout(() => {
              // Correct way to get and hide the modal instance
              const modalElement = document.getElementById('applicationModal');
              const modalInstance = bootstrap.Modal.getInstance(modalElement);
              if (modalInstance) {
                modalInstance.hide();
              }
            }, 2000);
          } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
          }
        })
        .catch(error => {
          messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        });
    });
  </script>
</body>

</html>