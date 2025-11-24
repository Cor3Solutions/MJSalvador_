<?php
/**
 * Homepage - Main landing page
 * Displays banner, partners, CVs, gallery, opportunities, and testimonials
 */

require_once 'config.php';

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

if (!function_exists('h')) {
  function h($text)
  {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

// ============================================================================
// DATABASE QUERIES
// ============================================================================

$conn = getDBConnection();

// Initialize all data arrays
$partners = [];
$testimonials = [];
$portraits = [];
$opportunities = [];
$featured_cvs = [];
$db_error = null;

try {
  // Fetch non-archived partners
  $stmt = $conn->prepare("SELECT * FROM partners WHERE is_archived = 0 ORDER BY sort_order ASC");
  $stmt->execute();
  $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch approved, non-archived testimonials
  $stmt = $conn->prepare("SELECT * FROM testimonials WHERE is_approved = 1 AND is_archived = 0 ORDER BY testimonial_id DESC LIMIT 3");
  $stmt->execute();
  $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch non-archived gallery portraits
  $stmt = $conn->prepare("SELECT * FROM portraits WHERE is_archived = 0 ORDER BY portrait_id DESC LIMIT 3");
  $stmt->execute();
  $portraits = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch active opportunities
  $stmt = $conn->prepare("SELECT * FROM opportunities WHERE is_active = 1 AND is_archived = 0 ORDER BY opportunity_id DESC LIMIT 3");
  $stmt->execute();
  $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch featured CVs
  $stmt = $conn->query("
        SELECT r.resume_id, r.original_filename, r.filepath, r.cv_type, r.upload_date,
               CASE WHEN cp.id IS NOT NULL THEN 1 ELSE 0 END as has_password
        FROM resumes r
        LEFT JOIN cv_passwords cp ON r.resume_id = cp.resume_id
        WHERE r.is_featured = 1
        ORDER BY r.display_order ASC
        LIMIT 2
    ");
  $featured_cvs = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
  error_log("Database Error on Homepage: " . $e->getMessage());
  $db_error = "Unable to load some content due to a server issue.";
}

$has_testimonials = !empty($testimonials);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jade S. | Executive VA • Model • Actress</title>

  <!-- SEO Meta Tags -->
  <meta property="og:title" content="Jade Salvador | Executive VA • Model • Actress">
  <meta property="og:description"
    content="Explore Jade Salvador's professional portfolio as an Executive Virtual Assistant, freelance model, and actress.">
  <meta property="og:image" content="https://cor3solutions.github.io/MJ-Salvador/images/icon.png">
  <meta property="og:url" content="https://jadesalvador.com">

  <link rel="icon" type="image/png" href="images/logo.png">

  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap"
    rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" href="css/style.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            'primary-pink': '#cd919e',
            'soft-pink': '#fef7f9',
            'dark-text': '#1f2937',
          }
        }
      }
    }
  </script>

  <style>
    /* CSS VARIABLES */
    :root {
      --jade-primary: #cd919e;
      --jade-primary-hover: #d68a9bff;
      --text-primary: #333;
      --bg-light: #f8f9fa;
      --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
      --border-color: #eee;
    }

    /* GENERAL STYLES */
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f7f7f7;
    }

    .card {
      border: none;
      border-radius: 12px;
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .hover-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    /* BANNER SECTION */
    .banner-section {
      background-color: #ffe4ec;
    }

    .intro-box {
      position: absolute;
      bottom: -100px;
      z-index: 9;
      left: 50%;
      transform: translateX(-50%);
      transition: all 0.3s ease;
    }

    @media only screen and (max-width: 991px) {
      .banner-section {
        transform: translateY(100px);
      }

      .intro-box {
        position: relative;
        bottom: 0;
      }
    }

    /* SWIPER STYLES */
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

    @media (max-width: 768px) {
      .swiper-slide {
        display: flex !important;
        justify-content: center;
        align-items: center;
      }

      .fixed-slide {
        max-width: 90vw;
        height: auto;
        aspect-ratio: 3 / 5;
        margin: 10px auto;
        border-radius: 12px;
      }
    }

    /* CV SECTION */
    .cv-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: none;
    }

    .cv-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    }

    .cv-icon {
      width: 80px;
      height: 80px;
      margin: 0 auto;
      background: linear-gradient(135deg, #cd919e, #764ba2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      color: white;
    }

    /* CV SWIPER STYLES */
    .cv-swiper {
      padding-bottom: 40px;
      margin: 0;
    }

    .cv-swiper .swiper-wrapper {
      align-items: stretch;
    }

    .cv-swiper .swiper-slide {
      height: auto;
      display: flex;
      align-items: stretch;
    }

    .cv-swiper .card {
      width: 100%;
      margin: 0;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .cv-swiper .card-body {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .cv-swiper .swiper-pagination {
      bottom: 10px;
    }

    .cv-swiper .swiper-pagination-bullet {
      width: 10px;
      height: 10px;
      opacity: 1;
      background: #d1d5db;
      transition: background-color 0.3s;
    }

    .cv-swiper .swiper-pagination-bullet-active {
      background: #cd919e;
      width: 12px;
      height: 12px;
    }

    /* MODALS */
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

    .form-control,
    .form-select {
      background-color: #fff;
      color: #333;
      border: 1px solid #ccc;
      border-radius: 8px;
      padding: 0.75rem 1rem;
      transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
    }

    .form-control:focus {
      border-color: #cd919e;
      box-shadow: 0 0 0 3px rgba(205, 145, 158, 0.25);
      outline: none;
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

    /* CUSTOM TESTIMONIAL MODAL */
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

    /* TESTIMONIAL STYLES */
    .testimonial-card {
      border-radius: 1rem;
      background-color: white;
      transition: transform 0.3s ease-in-out;
      border: 1px solid #f3f4f6;
    }

    .testimonial-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

    /* 1. Aspect Ratio and Card Styling (Applies to all screens) */
    .event-card {
      position: relative;
      overflow: hidden;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
    }

    .aspect-ratio-box {
      position: relative;
      width: 100%;
      height: 0;
      overflow: hidden;
    }

    /* 9:16 Portrait ratio: (16 / 9) * 100% = 177.777...% */
    .aspect-ratio-16x9 {
      padding-bottom: 177.78%;
      /* Forces 9:16 portrait height */
    }

    .event-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .event-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      /* Ensures image fills the container */
    }

    /* --- MOBILE SWIPER STYLES (Applies only to phones) --- */
    @media (max-width: 767.98px) {

      /* 2. Enable Horizontal Scroll on the Row */
      .mobile-scroll-row {
        flex-wrap: nowrap;
        /* Prevents items from wrapping */
        overflow-x: scroll;
        /* Enables swiping */
        -webkit-overflow-scrolling: touch;
        /* Better iOS scrolling */

        /* Optional: Adjust padding for edge-to-edge swiping effect */
        margin-left: -15px;
        /* Compensate for Bootstrap row padding */
        margin-right: -15px;
        padding-left: 20px;
        padding-right: 20px;
      }

      /* 3. Define Card Width for Mobile */
      .mobile-scroll-row>div[class*="col-"] {
        flex: 0 0 85%;
        /* Card takes up 85% of screen width */
        max-width: 85%;
        margin-right: 15px;
        /* Spacing between cards */

        /* Ensure no extra padding from col classes ruins the swiping */
        padding-left: 0;
        padding-right: 0;
      }

      /* 4. Hide Scrollbar (Optional) */
      .mobile-scroll-row::-webkit-scrollbar {
        display: none;
      }
    }

    .btn-custom {
      display: inline-flex;
      align-items: center;
      gap: 12px;
      background: linear-gradient(135deg, #cd919e, #764ba2);
      color: white;
      padding: 16px 48px;
      border-radius: 50px;
      font-weight: 700;
      font-size: 16px;
      text-decoration: none;
      box-shadow: 0 8px 24px rgba(118, 75, 162, 0.3);
      transition: all 0.3s ease;
      border: none;
    }

    .btn-custom:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 32px rgba(118, 75, 162, 0.4);
      background: linear-gradient(135deg, #764ba2, #cd919e);
      color: white;
    }

    /* OPPORTUNITIES TOGGLE */
    .toggle-details-btn .icon-chevron {
      transition: transform 0.3s ease;
      display: inline-block;
    }

    .toggle-details-btn.active .icon-chevron {
      transform: rotate(180deg);
    }

    /* OPPORTUNITIES SWIPER CARD FIXES */
    .swiper-opportunities {
      padding-bottom: 60px !important;
    }

    .swiper-opportunities .swiper-slide {
      height: auto;
      display: flex;
    }

    .swiper-opportunities .card {
      width: 100%;
      min-height: 450px;
      display: flex;
      flex-direction: column;
    }

    .swiper-opportunities .card-body {
      display: flex;
      flex-direction: column;
      flex: 1;
    }

    .swiper-opportunities .card-text {
      flex-grow: 1;
    }

    .swiper-opportunities .btn.mt-3 {
      margin-top: auto !important;
    }

    /* Fixed heights for content sections */
    .swiper-opportunities .card-title {
      min-height: 3rem;
      display: flex;
      align-items: center;
    }

    .swiper-opportunities .card-text.small {
      min-height: 60px;
    }

    /* 1. Base Event Card Styles */
    .event-card {
      position: relative;
      overflow: hidden;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
    }

    /* 2. Aspect Ratio Box for 9:16 PORTRAIT */
    .aspect-ratio-box {
      position: relative;
      width: 100%;
      height: 0;
      overflow: hidden;
    }

    /* 9:16 ratio: (16 / 9) * 100% = 177.777...% */
    /* *** REVERTED TO 9:16 PORTRAIT ASPECT RATIO *** */
    .aspect-ratio-16x9 {
      padding-bottom: 177.78%;
    }

    /* 3. Event Image Container and Image styles (Keep as is) */
    .event-image {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    .event-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    @media (max-width: 767.98px) {

      /* * TARGET: The row that holds the cards. 
     * This class must be applied to the row div in your PHP.
     */
      .mobile-scroll-row {
        /* This is crucial: prevents wrapping, forces horizontal alignment */
        flex-wrap: nowrap;

        /* Enables the horizontal swiping/scrolling */
        overflow-x: auto;

        /* Hides the scrollbar for a cleaner "swiper" look (optional, but common) */
        -webkit-overflow-scrolling: touch;
        -ms-overflow-style: none;
        /* IE and Edge */
        scrollbar-width: none;
        /* Firefox */
        padding-bottom: 15px;
        /* Ensures space if scrollbar is shown */

        /* To hide scrollbar visually: */
        &::-webkit-scrollbar {
          display: none;
        }
      }

      .mobile-scroll-row {
        /* Remove default row padding on mobile */
        margin-left: 0;
        margin-right: 0;

        /* Add padding to the row to create space on the left and right edges */
        padding-left: 20px;
        padding-right: 20px;

        /* Add this to allow horizontal scrolling on the padding itself */
        overflow-x: auto;
      }

      /* Remove default column padding */
      .mobile-scroll-row>div[class*="col-"] {
        padding-left: 0;
        padding-right: 0;
        /* ... (rest of the mobile card sizing) ... */
      }
    }

    /* ANNOUNCEMENT WIDGET */
    .announcement-widget {
      position: fixed;
      top: 50%;
      right: 20px;
      transform: translateY(-50%);
      z-index: 1000;
      background: linear-gradient(135deg, rgba(205, 145, 158, 0.9), rgba(118, 75, 162, 0.9));
      backdrop-filter: blur(10px);
      color: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      cursor: pointer;
      transition: all 0.3s ease;
      max-width: 200px;
      text-align: center;
      animation: pulse 2s infinite;
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .announcement-widget:hover {
      transform: translateY(-50%) scale(1.05);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.25);
    }

    .announcement-widget .icon {
      font-size: 2rem;
      margin-bottom: 10px;
      display: block;
    }

    .announcement-widget .text {
      font-weight: 600;
      font-size: 14px;
      line-height: 1.3;
      margin-bottom: 8px;
    }

    .announcement-widget .subtext {
      font-size: 11px;
      opacity: 0.9;
      font-weight: 400;
    }

    .announcement-widget .arrow {
      font-size: 1.2rem;
      margin-top: 5px;
      display: block;
      animation: bounce 1.5s infinite;
    }

    .announcement-widget .minimize-btn {
      position: absolute;
      top: 8px;
      right: 8px;
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.2s ease;
      z-index: 10;
    }

    .announcement-widget .minimize-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: scale(1.1);
    }

    .announcement-widget.minimized {
      max-width: 60px;
      padding: 15px 10px;
      cursor: pointer;
    }

    .announcement-widget.minimized .content {
      display: none;
    }

    .announcement-widget.minimized .minimize-btn {
      display: none;
    }

    .announcement-widget.minimized .expand-btn {
      display: block;
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(255, 255, 255, 0.2);
      border: none;
      color: white;
      width: 30px;
      height: 30px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .announcement-widget .expand-btn {
      display: none;
    }

    .announcement-widget.minimized .expand-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translate(-50%, -50%) scale(1.1);
    }

    @keyframes pulse {
      0% {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      }

      50% {
        box-shadow: 0 8px 25px rgba(205, 145, 158, 0.3);
      }

      100% {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      }
    }

    @keyframes bounce {

      0%,
      20%,
      50%,
      80%,
      100% {
        transform: translateY(0);
      }

      40% {
        transform: translateY(-3px);
      }

      60% {
        transform: translateY(-2px);
      }
    }

    /* Mobile responsive adjustments */
    @media (max-width: 768px) {
      .announcement-widget {
        position: fixed;
        bottom: 20px;
        right: 15px;
        top: auto;
        transform: none;
        max-width: 150px;
        padding: 12px;
        font-size: 12px;
        border-radius: 12px;
      }

      .announcement-widget .icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
      }

      .announcement-widget .text {
        font-size: 12px;
        margin-bottom: 6px;
      }

      .announcement-widget .subtext {
        font-size: 10px;
      }

      .announcement-widget .arrow {
        font-size: 1rem;
        margin-top: 4px;
      }
    }

    /* Adjust position for smaller screens */
    @media (max-width: 1200px) and (min-width: 769px) {
      .announcement-widget {
        right: 10px;
        max-width: 180px;
        padding: 15px;
      }
    }
  </style>
</head>

<body>
  <?php include 'navbar.php'; ?>

  <!-- ANNOUNCEMENT WIDGET -->
  <?php if (!empty($opportunities)): ?>
    <div class="announcement-widget" id="announcementWidget" onclick="scrollToOpportunities()">
      <button class="minimize-btn" onclick="minimizeAnnouncement(event)">−</button>
      <button class="expand-btn" onclick="expandAnnouncement(event)">+</button>
      <div class="content">
        <i class="bi bi-briefcase-fill icon"></i>
        <div class="text">View Opportunities</div>
        <div class="subtext">Current openings available</div>
        <i class="bi bi-arrow-right arrow"></i>
      </div>
    </div>
  <?php endif; ?>

  <!-- BANNER SECTION -->
  <section class="banner-section position-relative text-center py-5">
    <div class="main-banner swiper">
      <div class="swiper-wrapper">
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/3.1.png" alt="Slide 1"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/member-02.mp4" type="video/mp4">
            </video></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/member-06.jpg" alt="Slide 3"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/2nd.png" alt="Slide 4"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/747.mp4" type="video/mp4">
            </video></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/missu.png" alt="Slide 6"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/1st.png" alt="Slide 7"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/litolsweets.mp4" type="video/mp4">
            </video></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/3rd.png" alt="Slide 9"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/heera.jpg" alt="Slide 10"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/abc.mp4" type="video/mp4">
            </video></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/1.1.png" alt="Slide 12"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/tg.mp4" type="video/mp4">
            </video></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/littlesweets.png" alt="Slide 14"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><img src="images/SWIPE/blue.png" alt="Slide 15"></div>
        </div>
        <div class="swiper-slide">
          <div class="fixed-slide"><video autoplay muted loop playsinline>
              <source src="images/SWIPE/heeravid.mov" type="video/mp4">
            </video></div>
        </div>
      </div>
    </div>

    <div class="intro-box col-lg-5 p-5 bg-black bg-opacity-75 text-white rounded-4 shadow-sm">
      <h3 class="display-4 mb-3 text-white" style="font-size: 2rem;">
        Executive Virtual Assistant <br>Freelance Model <br> Actress
      </h3>
      <p class="fs-6 text-white">
        Behind the camera organization to striking visuals on stage and ramp, I bring your vision come alive
        with style and energy that elevate every project. Aiming to blend professionalism with creativity aiding fashion
        brands and events bring their creativity to reality.
      </p>
      <button type="button" class="btn btn-primary p-3 mt-2 w-100 rounded-2"
        style="background-color: #cd919e; border: none;" data-bs-toggle="modal" data-bs-target="#inquiryModal">
        Let's collaborate!
      </button>
    </div>
  </section>
  <br><br>

  <!-- INQUIRY MODAL -->
  <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inquiryModalLabel">Send Us Your Inquiry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="inquiryForm">
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

  <!-- PARTNERS SECTION -->
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

  <!-- CV SECTION -->
  <section class="container py-5">
    <div class="text-center mb-5">
      <h2 class="fw-bold mb-3">My Professional CVs</h2>
      <p class="text-muted">Download my resume and experience</p>
    </div>

    <?php if (!empty($featured_cvs)): ?>
      <!-- Desktop Layout -->
      <div class="row justify-content-center g-4 d-none d-md-flex">
        <?php foreach ($featured_cvs as $cv): ?>
          <div class="col-md-6 col-lg-5">
            <div class="card shadow-sm h-100 cv-card">
              <div class="card-body text-center p-4">
                <div class="cv-icon mb-3">
                  <i class="bi bi-file-earmark-pdf-fill"></i>
                </div>

                <h4 class="card-title mb-3"><?php echo h($cv['cv_type'] ?? 'Professional CV'); ?></h4>

                <p class="text-muted small mb-4">
                  <i class="bi bi-calendar3 me-1"></i>
                  Updated <?php echo date('M Y', strtotime($cv['upload_date'])); ?>
                  <?php if ($cv['has_password']): ?>
                    <br><i class="bi bi-lock-fill me-1"></i>
                    <span class="text-warning">Password Protected</span>
                  <?php endif; ?>
                </p>

                <div class="d-grid gap-2">
                  <button class="btn btn-outline-primary preview-cv" data-cv-path="<?php echo h($cv['filepath']); ?>"
                    data-cv-title="<?php echo h($cv['cv_type'] ?? 'CV'); ?>">
                    <i class="bi bi-eye me-2"></i>Preview
                  </button>
                  <button class="btn btn-primary download-cv" data-cv-id="<?php echo $cv['resume_id']; ?>"
                    data-has-password="<?php echo $cv['has_password']; ?>">
                    <i class="bi bi-download me-2"></i>Download
                    <?php if ($cv['has_password']): ?>
                      <i class="bi bi-lock-fill ms-1"></i>
                    <?php endif; ?>
                  </button>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Mobile Swiper Layout -->
      <div class="swiper cv-swiper d-md-none">
        <div class="swiper-wrapper">
          <?php foreach ($featured_cvs as $cv): ?>
            <div class="swiper-slide">
              <div class="card shadow-sm h-100 cv-card">
                <div class="card-body text-center p-4">
                  <div class="cv-icon mb-3">
                    <i class="bi bi-file-earmark-pdf-fill"></i>
                  </div>

                  <h4 class="card-title mb-3"><?php echo h($cv['cv_type'] ?? 'Professional CV'); ?></h4>

                  <p class="text-muted small mb-4">
                    <i class="bi bi-calendar3 me-1"></i>
                    Updated <?php echo date('M Y', strtotime($cv['upload_date'])); ?>
                    <?php if ($cv['has_password']): ?>
                      <br><i class="bi bi-lock-fill me-1"></i>
                      <span class="text-warning">Password Protected</span>
                    <?php endif; ?>
                  </p>

                  <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary preview-cv" data-cv-path="<?php echo h($cv['filepath']); ?>"
                      data-cv-title="<?php echo h($cv['cv_type'] ?? 'CV'); ?>">
                      <i class="bi bi-eye me-2"></i>Preview
                    </button>
                    <button class="btn btn-primary download-cv" data-cv-id="<?php echo $cv['resume_id']; ?>"
                      data-has-password="<?php echo $cv['has_password']; ?>">
                      <i class="bi bi-download me-2"></i>Download
                      <?php if ($cv['has_password']): ?>
                        <i class="bi bi-lock-fill ms-1"></i>
                      <?php endif; ?>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="swiper-pagination"></div>
      </div>

      <div class="text-center mt-4">
        <small class="text-muted">
          <i class="bi bi-info-circle me-1"></i>
          Need the password? <a href="#contact">Contact me</a> for access.
        </small>
      </div>
    <?php else: ?>
      <div class="text-center py-5">
        <div class="alert alert-warning">
          <h5>⚠️ No Featured CVs</h5>
          <p class="mb-0">CVs will appear here once they are uploaded and featured.</p>
        </div>
      </div>
    <?php endif; ?>
  </section>

  <!-- CV PREVIEW MODAL -->
  <div class="modal fade" id="cvPreviewModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-eye me-2"></i><span id="previewTitle">CV Preview</span></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body p-0" style="height: 80vh;">
          <div id="previewContainer" class="h-100"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- CV PASSWORD MODAL -->
  <div class="modal fade" id="cvPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-lock me-2"></i>Password Required</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form id="cvPasswordForm">
          <div class="modal-body">
            <p class="text-muted">This CV is password-protected. Please enter the password to download.</p>
            <input type="hidden" id="passwordCvId" name="cv_id">
            <div class="mb-3">
              <label class="form-label">Password</label>
              <div class="input-group">
                <input type="password" class="form-control" id="cvPasswordInput" name="password" required>
                <button class="btn btn-outline-secondary" type="button" id="togglePwd">
                  <i class="bi bi-eye"></i>
                </button>
              </div>
              <div class="invalid-feedback d-none" id="pwdError">
                Incorrect password. Please try again.
              </div>
            </div>
            <div class="alert alert-info mb-0">
              <small>
                <i class="bi bi-info-circle me-1"></i>
                Need the password? <a href="#contact" data-bs-dismiss="modal">Contact me</a>.
              </small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-unlock me-2"></i>Download
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- GALLERY SECTION -->
  <section class="my-5 py-5" style="background: linear-gradient(135deg, #ffe4ec 0%, #fff5f7 100%);">
    <div class="container">
      <div class="text-center mb-5">
        <span class="badge rounded-pill px-4 py-2 mb-3"
          style="background: linear-gradient(135deg, #cd919e, #764ba2); color: white; font-weight: 600; letter-spacing: 0.5px;">
          KEEPING YOU IN THE LOOP
        </span>
        <h2 class="display-4 fw-bold mb-3" style="color: #2d2d2d;">Recent Events</h2>
        <p class="lead text-muted" style="max-width: 600px; margin: 0 auto;">
          A glimpse into my latest projects, collaborations, and memorable moments
        </p>
      </div>

      <div class="row g-4 d-flex mobile-scroll-row">
        <?php
        // PHP Sorting Logic (Keep this)
        usort($portraits, function ($a, $b) {
          $time_a = isset($a['event_date']) ? strtotime($a['event_date']) : 0;
          $time_b = isset($b['event_date']) ? strtotime($b['event_date']) : 0;
          if ($time_a == $time_b)
            return 0;
          return ($time_a > $time_b) ? -1 : 1;
        });

        $event_count = 0;
        foreach ($portraits as $portrait):
          if ($event_count >= 3)
            break;
          $event_count++;
          ?>
          <div class="col-lg-4 col-md-6">
            <div class="event-card">
              <div class="aspect-ratio-box aspect-ratio-16x9">
                <div class="event-image">

                  <img src="<?php echo h($portrait['image_filename']); ?>" alt="<?php echo h($portrait['title']); ?>">

                  <div
                    style="position: absolute; inset: 0; background: linear-gradient(180deg, transparent 0%, transparent 40%, rgba(0,0,0,0.8) 100%); z-index: 1;">
                  </div>

                  <?php if (isset($portrait['event_date'])): ?>
                    <div
                      style="position: absolute; top: 20px; right: 20px; background: white; padding: 8px 16px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 3;">
                      <div style="text-align: center;">
                        <div style="font-size: 24px; font-weight: 800; line-height: 1; color: #764ba2;">
                          <?php echo date('d', strtotime($portrait['event_date'])); ?>
                        </div>
                        <div style="font-size: 12px; font-weight: 600; color: #666; text-transform: uppercase;">
                          <?php echo date('M', strtotime($portrait['event_date'])); ?>
                        </div>
                      </div>
                    </div>
                  <?php endif; ?>

                  <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 24px; color: white; z-index: 2;">
                    <?php if (isset($portrait['category'])): ?>
                      <span
                        style="display: inline-block; background: rgba(255,255,255,0.2); backdrop-filter: blur(10px); padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                        <?php echo h($portrait['category']); ?>
                      </span>
                    <?php endif; ?>

                    <h5 style="font-size: 20px; font-weight: 700; margin: 0; line-height: 1.3;">
                      <?php echo h($portrait['title']); ?>
                    </h5>

                    <?php if (isset($portrait['location'])): ?>
                      <p
                        style="font-size: 14px; opacity: 0.9; margin: 8px 0 0; display: flex; align-items: center; gap: 6px;">
                        <i class="bi bi-geo-alt-fill"></i>
                        <?php echo h($portrait['location']); ?>
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="text-center mt-5 pt-4">
        <a href="portraits.php" class="btn-custom">
          <i class="bi bi-images" style="font-size: 20px;"></i>
          <span>View Full Gallery</span>
          <i class="bi bi-arrow-right" style="font-size: 20px;"></i>
        </a>
      </div>
    </div>
  </section>

  <!-- OPPORTUNITIES SECTION -->
  <?php if (!empty($opportunities)): ?>
    <section id="opportunities" class="py-5" style="background-color: #f8f9fa;">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold display-5" style="color: #494949;">Current Opportunities</h2>
          <p class="text-muted fs-5">Open positions and collaboration opportunities</p>
        </div>

        <div class="swiper swiper-opportunities">
          <div class="swiper-wrapper">
            <?php foreach ($opportunities as $opp): ?>
              <div class="swiper-slide">
                <div class="card h-100 shadow-sm hover-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <span class="badge bg-primary"><?php echo h($opp['job_type']); ?></span>
                      <?php if ($opp['deadline']): ?>
                        <small class="text-danger fw-bold">
                          <i class="bi bi-clock"></i> Deadline: <?php echo date('M d, Y', strtotime($opp['deadline'])); ?>
                        </small>
                      <?php endif; ?>
                    </div>

                    <h5 class="card-title fw-bold"><?php echo h($opp['title']); ?></h5>

                    <?php if ($opp['location']): ?>
                      <p class="text-muted small mb-2">
                        <i class="bi bi-geo-alt-fill"></i> <?php echo h($opp['location']); ?>
                      </p>
                    <?php endif; ?>

                    <?php if ($opp['net_rate']): ?>
                      <p class="text-success small mb-2">
                        <i class="bi bi-cash-stack"></i> <strong><?php echo h($opp['net_rate']); ?></strong>
                      </p>
                    <?php endif; ?>

                    <?php if ($opp['job_type'] === 'talent'): ?>
                      <div class="mb-3">
                        <small class="d-block text-muted">
                          <?php if ($opp['age_requirement']): ?>
                            <span class="me-2"><i class="bi bi-person"></i> Age:
                              <?php echo h($opp['age_requirement']); ?></span>
                          <?php endif; ?>
                          <?php if ($opp['height_requirement']): ?>
                            <span class="me-2"><i class="bi bi-rulers"></i> Height:
                              <?php echo h($opp['height_requirement']); ?></span>
                          <?php endif; ?>
                        </small>
                        <small class="d-block text-muted">
                          <?php if ($opp['gender_requirement'] && $opp['gender_requirement'] != 'any'): ?>
                            <span class="me-2"><i class="bi bi-gender-ambiguous"></i>
                              <?php echo ucfirst(h($opp['gender_requirement'])); ?></span>
                          <?php endif; ?>
                          <?php if ($opp['model_class']): ?>
                            <span class="badge bg-primary">Class <?php echo h($opp['model_class']); ?></span>
                          <?php endif; ?>
                        </small>
                      </div>
                    <?php endif; ?>

                    <p class="card-text small short-desc-<?php echo $opp['opportunity_id']; ?>">
                      <?php echo h(substr($opp['description'], 0, 100)); ?>
                      <?php echo strlen($opp['description']) > 100 ? '...' : ''; ?>
                    </p>

                    <?php if (strlen($opp['description']) > 100 || !empty($opp['requirements'])): ?>

                      <div class="full-details-<?php echo $opp['opportunity_id']; ?>" style="display: none;">
                        <?php if (strlen($opp['description']) > 100): ?>
                          <div class="small mb-2">
                            <strong>Full Description:</strong><br>
                            <?php echo nl2br(h($opp['description'])); ?>
                          </div>
                        <?php endif; ?>

                        <?php if (!empty($opp['requirements'])): ?>
                          <div class="small mb-2">
                            <strong>Requirements:</strong><br>
                            <?php echo nl2br(h($opp['requirements'])); ?>
                          </div>
                        <?php endif; ?>
                      </div>

                      <button class="btn btn-link btn-sm p-0 mb-2 toggle-details-btn"
                        data-opp-id="<?php echo $opp['opportunity_id']; ?>">
                        <i class="bi bi-chevron-down icon-chevron"></i> <span class="btn-text">See More Details</span>
                      </button>

                    <?php endif; ?>

                    <button class="btn btn-primary btn-sm w-100 mt-3"
                      onclick="showApplicationForm(<?php echo $opp['opportunity_id']; ?>, '<?php echo addslashes(h($opp['title'])); ?>', '<?php echo h($opp['job_type']); ?>')">
                      <i class="bi bi-send-fill"></i> Apply Now
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

         <div class="text-center mt-4">
          <a href="opportunities.php" class="btn btn-outline-primary btn-lg">
            See All Opportunities
          </a>
        </div>
       </div>
    </section>
  <?php endif; ?>

  <!-- APPLICATION MODAL -->
  <div class="modal fade" id="applicationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form id="applicationForm">
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

  <!-- TESTIMONIALS SECTION -->
  <section id="testimonials" class="py-12 md:py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-12">
        <h4 class="text-sm font-semibold uppercase tracking-wider text-primary-pink">Testimonials</h4>
        <h2 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">Feedbacks</h2>
        <p class="mt-4 text-xl text-gray-500">Real words from people I've worked with</p>
        <?php if (isset($db_error)): ?>
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
          <?php if ($has_testimonials): ?>
            <?php foreach ($testimonials as $testimonial): ?>
              <div class="swiper-slide !h-auto">
                <div class="testimonial-card h-full shadow-xl p-8 lg:p-12 text-center flex flex-col justify-between">
                  <div class="quote-content">
                    <div class="quote-icon mb-4 flex justify-center">
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
            <div class="swiper-slide p-6 flex justify-center items-center w-full">
              <div class="text-center text-gray-500 p-10 bg-white rounded-xl shadow-lg w-full max-w-lg">
                <p class="text-lg">No approved testimonials found yet. Be the first to share your feedback!</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <div class="swiper-pagination mt-8"></div>
      </div>
    </div>
  </section>

  <!-- TESTIMONIAL MODAL -->
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
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div class="mb-4">
            <label for="modal_client_name" class="block text-sm font-medium text-gray-700 mb-1">
              Your Name <span class="text-red-500">*</span>
            </label>
            <input type="text" class="form-control w-full" id="modal_client_name" name="client_name" required
              placeholder="E.g., Alex Johnson">
          </div>

          <div class="mb-4">
            <label for="modal_client_title" class="block text-sm font-medium text-gray-700 mb-1">
              Your Role/Company (Optional)
            </label>
            <input type="text" class="form-control w-full" id="modal_client_title" name="client_title"
              placeholder="E.g., CEO, Acme Corp.">
          </div>

          <div class="mb-6">
            <label for="modal_quote_text" class="block text-sm font-medium text-gray-700 mb-1">
              Your Testimonial <span class="text-red-500">*</span>
            </label>
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

  <?php include 'footer.php'; ?>

  <!-- JAVASCRIPT LIBRARIES -->
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
  <script src="js/plugins.js"></script>
  <script src="js/script.js"></script>

  <script>
    // UTILITY FUNCTIONS
    const getBsModal = (id) => {
      const el = document.getElementById(id);
      if (!el) return null;
      return bootstrap.Modal.getInstance(el) || new bootstrap.Modal(el);
    };

    // INQUIRY FORM HANDLER
    const inquiryForm = document.getElementById('inquiryForm');
    if (inquiryForm) {
      inquiryForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitBtn');
        const originalBtnText = submitBtn?.innerHTML || 'Send Inquiry';

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
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

            if (data.success) this.reset();
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

    // TESTIMONIAL MODAL CONTROLS
    window.openModal = function (id) {
      if (id === 'testimonialModal') {
        document.getElementById(id).classList.add('open');
        document.body.style.overflow = 'hidden';
        const messageBox = document.getElementById('submissionMessage');
        if (messageBox) messageBox.classList.add('hidden');
        const form = document.getElementById('testimonialForm');
        if (form) form.reset();
      }
    };

    window.closeModal = function (id) {
      if (id === 'testimonialModal') {
        document.getElementById(id).classList.remove('open');
        document.body.style.overflow = '';
      }
    };

    // TESTIMONIAL FORM HANDLER
    const testimonialForm = document.getElementById('testimonialForm');
    if (testimonialForm) {
      testimonialForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const formData = new FormData(this);
        const submitBtn = document.getElementById('submitTestimonialBtn');
        const originalBtnText = submitBtn?.innerHTML || 'Submit Feedback';

        if (submitBtn) {
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';
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

            if (data.success) this.reset();
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

    // CV FUNCTIONALITY
    document.addEventListener('DOMContentLoaded', function () {
      const previewModal = new bootstrap.Modal(document.getElementById('cvPreviewModal'));
      const passwordModal = new bootstrap.Modal(document.getElementById('cvPasswordModal'));

      // Preview CV - Works for both desktop and mobile swiper
      function attachPreviewListeners() {
        document.querySelectorAll('.preview-cv').forEach(btn => {
          // Remove existing listeners to prevent duplicates
          btn.replaceWith(btn.cloneNode(true));
        });

        document.querySelectorAll('.preview-cv').forEach(btn => {
          btn.addEventListener('click', function () {
            const path = this.dataset.cvPath;
            const title = this.dataset.cvTitle;

            // Construct proper URL for XAMPP/localhost setup
            let url;
            if (path.startsWith('http')) {
              // Already a full URL
              url = path;
            } else {
              // For XAMPP, construct URL relative to the project root
              const currentPath = window.location.pathname;
              const projectRoot = currentPath.substring(0, currentPath.lastIndexOf('/'));
              url = window.location.origin + projectRoot + '/' + path;
            }

            const fileExt = path.split('.').pop().toLowerCase();

            // Debug: Log the constructed URL
            console.log('CV Preview Debug:', {
              originalPath: path,
              constructedUrl: url,
              fileExtension: fileExt
            });

            document.getElementById('previewTitle').textContent = title;

            // Test if file is accessible before showing preview
            fetch(url, { method: 'HEAD' })
              .then(response => {
                if (!response.ok) {
                  throw new Error(`File not accessible: ${response.status}`);
                }
                showPreview();
              })
              .catch(error => {
                console.error('File access error:', error);
                showErrorFallback();
              });

            function showPreview() {
              // Show loading state
              document.getElementById('previewContainer').innerHTML = `
                <div class="d-flex justify-content-center align-items-center h-100">
                  <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                      <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted">Loading CV preview...</p>
                  </div>
                </div>
              `;

              if (fileExt === 'pdf') {
                // Create PDF viewer with fallback options
                const pdfViewer = `
                <div class="h-100 position-relative">
                  <embed id="pdfEmbed" 
                         src="${url}#toolbar=1&navpanes=1&scrollbar=1&view=FitH" 
                         type="application/pdf" 
                         width="100%" 
                         height="100%"
                         onerror="showPdfFallback('${url}')"
                         onload="hideLoading()">
                  <div id="pdfFallback" class="d-none h-100 d-flex flex-column justify-content-center align-items-center">
                    <div class="alert alert-warning mb-3">
                      <i class="bi bi-exclamation-triangle me-2"></i>
                      PDF preview not supported in this browser
                    </div>
                    <div class="d-grid gap-2">
                      <a href="${url}" target="_blank" class="btn btn-primary">
                        <i class="bi bi-box-arrow-up-right me-2"></i>Open PDF in New Tab
                      </a>
                      <a href="${url}" download class="btn btn-outline-primary">
                        <i class="bi bi-download me-2"></i>Download PDF
                      </a>
                    </div>
                  </div>
                </div>
              `;
                document.getElementById('previewContainer').innerHTML = pdfViewer;

                // Set a timeout to show fallback if PDF doesn't load within 5 seconds
                setTimeout(() => {
                  const embed = document.getElementById('pdfEmbed');
                  if (embed && !embed.complete) {
                    showPdfFallback(url);
                  }
                }, 5000);
              } else if (fileExt === 'doc' || fileExt === 'docx') {
                document.getElementById('previewContainer').innerHTML = `
                <div class="h-100 d-flex flex-column justify-content-center align-items-center">
                  <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Word documents cannot be previewed directly in the browser
                  </div>
                  <div class="d-grid gap-2">
                    <a href="${url}" download class="btn btn-primary">
                      <i class="bi bi-download me-2"></i>Download Document
                    </a>
                  </div>
                </div>
              `;
              } else {
                document.getElementById('previewContainer').innerHTML = `
                <div class="h-100 d-flex flex-column justify-content-center align-items-center">
                  <div class="alert alert-warning mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    File type not supported for preview
                  </div>
                  <div class="d-grid gap-2">
                    <a href="${url}" download class="btn btn-primary">
                      <i class="bi bi-download me-2"></i>Download File
                    </a>
                  </div>
                </div>
              `;
              }

              previewModal.show();
            }

            function showErrorFallback() {
              document.getElementById('previewContainer').innerHTML = `
                <div class="h-100 d-flex flex-column justify-content-center align-items-center">
                  <div class="alert alert-danger mb-3">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    File not found or not accessible
                  </div>
                  <div class="text-muted mb-3">
                    <small>Debug info: ${url}</small>
                  </div>
                  <div class="d-grid gap-2">
                    <button class="btn btn-primary" onclick="window.open('${url}', '_blank')">
                      <i class="bi bi-box-arrow-up-right me-2"></i>Try Opening in New Tab
                    </button>
                    <button class="btn btn-outline-secondary" onclick="document.getElementById('cvPreviewModal').querySelector('.btn-close').click()">
                      <i class="bi bi-x-circle me-2"></i>Close
                    </button>
                  </div>
                </div>
              `;
              previewModal.show();
            }
          });
        });
      }

      // Attach listeners initially
      attachPreviewListeners();

      // Helper functions for PDF preview
      window.showPdfFallback = function (url) {
        const fallback = document.getElementById('pdfFallback');
        if (fallback) {
          fallback.classList.remove('d-none');
          fallback.classList.add('d-flex');
          // Hide the embed element
          const embed = document.getElementById('pdfEmbed');
          if (embed) {
            embed.style.display = 'none';
          }
        }
        hideLoading();
      };

      window.hideLoading = function () {
        const loadingElement = document.querySelector('.spinner-border');
        if (loadingElement) {
          loadingElement.parentElement.parentElement.remove();
        }
      };

      // Download CV - Works for both desktop and mobile swiper
      function attachDownloadListeners() {
        document.querySelectorAll('.download-cv').forEach(btn => {
          // Remove existing listeners to prevent duplicates
          btn.replaceWith(btn.cloneNode(true));
        });

        document.querySelectorAll('.download-cv').forEach(btn => {
          btn.addEventListener('click', function () {
            const cvId = this.dataset.cvId;
            const hasPassword = this.dataset.hasPassword == '1';

            if (hasPassword) {
              document.getElementById('passwordCvId').value = cvId;
              document.getElementById('cvPasswordInput').value = '';
              document.getElementById('cvPasswordInput').classList.remove('is-invalid');
              document.getElementById('pwdError').classList.add('d-none');
              passwordModal.show();
            } else {
              window.location.href = `download_cv.php?id=${cvId}`;
            }
          });
        });
      }

      // Attach download listeners initially
      attachDownloadListeners();

      // Password form submit
      document.getElementById('cvPasswordForm').addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';

        try {
          const response = await fetch('download_cv.php', {
            method: 'POST',
            body: formData
          });

          const result = await response.json();

          if (result.success) {
            passwordModal.hide();
            window.location.href = result.download_url;
          } else {
            document.getElementById('cvPasswordInput').classList.add('is-invalid');
            document.getElementById('pwdError').classList.remove('d-none');
          }
        } catch (error) {
          alert('An error occurred. Please try again.');
        }

        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
      });

      // Toggle password visibility
      document.getElementById('togglePwd').addEventListener('click', function () {
        const input = document.getElementById('cvPasswordInput');
        const icon = this.querySelector('i');

        if (input.type === 'password') {
          input.type = 'text';
          icon.className = 'bi bi-eye-slash';
        } else {
          input.type = 'password';
          icon.className = 'bi bi-eye';
        }
      });

      // Clear error on input
      document.getElementById('cvPasswordInput').addEventListener('input', function () {
        this.classList.remove('is-invalid');
        document.getElementById('pwdError').classList.add('d-none');
      });
    });

    // APPLICATION FORM HANDLER
    window.showApplicationForm = function (opportunityId, jobTitle, jobType) {
      document.getElementById('opportunity_id').value = opportunityId;
      document.getElementById('jobTitle').textContent = jobTitle;
      document.getElementById('application_job_type').value = jobType;
      document.getElementById('applicationForm').reset();
      document.getElementById('formMessage').innerHTML = '';

      const talentFields = document.getElementById('talentApplicationFields');
      const vaFields = document.getElementById('vaApplicationFields');
      const setcardLink = document.getElementById('setcard_link');
      const resumeLink = document.getElementById('resume_cv_link');

      talentFields.style.display = 'none';
      vaFields.style.display = 'none';
      setcardLink.required = false;
      resumeLink.required = false;

      const talentJobTypes = ['talent', 'brand-ambassador', 'usherette'];
      const isTalent = talentJobTypes.includes(jobType.toLowerCase());

      if (isTalent) {
        talentFields.style.display = 'block';
        setcardLink.required = true;
      } else {
        vaFields.style.display = 'block';
        resumeLink.required = true;
      }

      const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
      modal.show();
    };

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
              const modalElement = document.getElementById('applicationModal');
              const modalInstance = bootstrap.Modal.getInstance(modalElement);
              if (modalInstance) modalInstance.hide();
            }, 2000);
          } else {
            messageDiv.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
          }
        })
        .catch(error => {
          messageDiv.innerHTML = '<div class="alert alert-danger">An error occurred. Please try again.</div>';
        });
    });

    // SWIPER INITIALIZATIONS
    // Main Banner Swiper
    const mainBannerSwiper = new Swiper(".main-banner", {
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

    // Testimonial Swiper
    const testimonialSwiper = new Swiper(".testimonialSwiper", {
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
        0: { slidesPerView: 1, spaceBetween: 20 },
        768: { slidesPerView: 2, spaceBetween: 30 },
        992: { slidesPerView: 3, spaceBetween: 40 }
      }
    });

    // CV Swiper (Mobile Only)
    const cvSwiper = new Swiper(".cv-swiper", {
      direction: 'horizontal',
      loop: false,
      slidesPerView: 1,
      spaceBetween: 0,
      autoHeight: false,
      centeredSlides: true,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      breakpoints: {
        0: { slidesPerView: 1, spaceBetween: 0 },
        768: { slidesPerView: 1, spaceBetween: 0 }
      },
      on: {
        init: function () {
          // Ensure all slides have same height
          this.updateAutoHeight();
        },
        slideChange: function () {
          this.updateAutoHeight();
        }
      }
    });
    // OPPORTUNITIES TOGGLE DETAILS
    document.querySelectorAll('.toggle-details-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const oppId = this.dataset.oppId;
        const fullDetails = document.querySelector('.full-details-' + oppId);
        const shortDesc = document.querySelector('.short-desc-' + oppId);
        const btnText = this.querySelector('.btn-text');

        if (fullDetails.style.display === 'none') {
          fullDetails.style.display = 'block';
          shortDesc.style.display = 'none';
          btnText.textContent = 'See Less';
          this.classList.add('active');
        } else {
          fullDetails.style.display = 'none';
          shortDesc.style.display = 'block';
          btnText.textContent = 'See More Details';
          this.classList.remove('active');
        }
      });
    });
    // OPPORTUNITIES SWIPER
    const opportunitiesSwiper = new Swiper('.swiper-opportunities', {
      direction: 'horizontal',
      loop: false,
      slidesPerView: 1,
      spaceBetween: 20,
      autoHeight: true,
      observer: true,
      observeParents: true,
      observeSlideChildren: true,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        768: { slidesPerView: 2, spaceBetween: 30 },
        992: { slidesPerView: 3, spaceBetween: 40 }
      },
      on: {
        init: function () {
          // Attach toggle listeners after swiper initializes
          attachToggleListeners();
        },
        slideChange: function () {
          // Re-attach listeners when slide changes
          attachToggleListeners();
        }
      }
    });

    // OPPORTUNITIES TOGGLE DETAILS FUNCTION
    function attachToggleListeners() {
      document.querySelectorAll('.toggle-details-btn').forEach(btn => {
        // Remove old listener first
        btn.replaceWith(btn.cloneNode(true));
      });

      // Add new listeners
      document.querySelectorAll('.toggle-details-btn').forEach(btn => {
        btn.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();

          const oppId = this.dataset.oppId;
          const fullDetails = document.querySelector('.full-details-' + oppId);
          const shortDesc = document.querySelector('.short-desc-' + oppId);
          const btnText = this.querySelector('.btn-text');

          console.log('Toggling opportunity:', oppId); // Debug

          if (fullDetails && shortDesc) {
            if (fullDetails.style.display === 'none' || fullDetails.style.display === '') {
              fullDetails.style.display = 'block';
              shortDesc.style.display = 'none';
              btnText.textContent = 'See Less';
              this.classList.add('active');
            } else {
              fullDetails.style.display = 'none';
              shortDesc.style.display = 'block';
              btnText.textContent = 'See More Details';
              this.classList.remove('active');
            }

            // Update swiper height
            setTimeout(() => {
              opportunitiesSwiper.updateAutoHeight(300);
              opportunitiesSwiper.update();
            }, 50);
          }
        });
      });
    }

    // Initialize toggle listeners on page load
    document.addEventListener('DOMContentLoaded', function () {
      attachToggleListeners();
    });

    // SCROLL TO OPPORTUNITIES FUNCTION
    window.scrollToOpportunities = function () {
      const opportunitiesSection = document.getElementById('opportunities');
      if (opportunitiesSection) {
        opportunitiesSection.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    };

    // MINIMIZE ANNOUNCEMENT FUNCTION
    window.minimizeAnnouncement = function (event) {
      event.stopPropagation(); // Prevent triggering the scroll function
      const widget = document.getElementById('announcementWidget');
      if (widget) {
        widget.classList.add('minimized');
        // Store in localStorage so it stays minimized on page refresh
        localStorage.setItem('announcementMinimized', 'true');
      }
    };

    // EXPAND ANNOUNCEMENT FUNCTION
    window.expandAnnouncement = function (event) {
      event.stopPropagation(); // Prevent triggering the scroll function
      const widget = document.getElementById('announcementWidget');
      if (widget) {
        widget.classList.remove('minimized');
        // Remove from localStorage so it stays expanded
        localStorage.removeItem('announcementMinimized');
      }
    };

    // CHECK IF ANNOUNCEMENT WAS PREVIOUSLY MINIMIZED
    document.addEventListener('DOMContentLoaded', function () {
      const isMinimized = localStorage.getItem('announcementMinimized');
      const widget = document.getElementById('announcementWidget');

      if (isMinimized === 'true' && widget) {
        widget.classList.add('minimized');
      }
    });

    // Event Cards Intersection Observer
    const eventCards = document.querySelectorAll('.event-card');
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -100px 0px'
    });

    eventCards.forEach(card => {
      observer.observe(card);
    });

    // TOGGLE FUNCTION
    function toggleOppDetails(oppId, btn) {
      const fullDetails = document.querySelector('.full-details-' + oppId);
      const shortDesc = document.querySelector('.short-desc-' + oppId);
      const btnText = btn.querySelector('.btn-text');

      if (fullDetails.style.display === 'none' || fullDetails.style.display === '') {
        fullDetails.style.display = 'block';
        shortDesc.style.display = 'none';
        btnText.textContent = 'See Less';
        btn.classList.add('active');
      } else {
        fullDetails.style.display = 'none';
        shortDesc.style.display = 'block';
        btnText.textContent = 'See More Details';
        btn.classList.remove('active');
      }

      // Remove the setTimeout that was updating height
    }
  </script>
</body>

</html>