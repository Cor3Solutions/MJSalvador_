<?php
require_once 'config.php';

// Define HTML escaping helper function (best practice to define this early)
if (!function_exists('h')) {
  function h($text)
  {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

try { // <-- The 'try' keyword was missing here
  $conn = getDBConnection();

  // Fetch partners
  $stmt = $conn->prepare("SELECT * FROM partners ORDER BY sort_order ASC");
  $stmt->execute();
  $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch approved testimonials (Limit 3 for the homepage)
// ASSUMPTION: 'id' is an auto-incrementing column, indicating insertion order.
  $stmt = $conn->prepare("SELECT * FROM testimonials WHERE is_approved = 1 ORDER BY testimonial_id DESC LIMIT 3");
  $stmt->execute();
  $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch gallery portraits (limit 6 for homepage)
  $stmt = $conn->prepare("SELECT * FROM portraits ORDER BY sort_order ASC LIMIT 6");
  $stmt->execute();
  $portraits = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) { // <-- The 'catch' block was waiting for a 'try'
  // Log the actual error to a file
  error_log("Database Error on Homepage: " . $e->getMessage());
  // Fallback to empty arrays so the page still loads
  $partners = [];
  $testimonials = [];
  $portraits = [];
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
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header" style="background-color: #ffe4ec;">
          <h5 class="modal-title" id="inquiryModalLabel">Send Us Your Inquiry</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form id="inquiryForm">
          <div class="modal-body">
            <p>Tell us about your project or collaboration idea!</p>

            <div class="mb-3">
              <label for="inquiryName" class="form-label">Your Full Name</label>
              <input type="text" class="form-control" id="inquiryName" name="full_name" required>
            </div>

            <div class="mb-3">
              <label for="inquiryEmail" class="form-label">Email Address</label>
              <input type="email" class="form-control" id="inquiryEmail" name="email" required>
            </div>

            <div class="mb-3">
              <label for="inquiryPhone" class="form-label">Phone Number (Optional)</label>
              <input type="tel" class="form-control" id="inquiryPhone" name="phone_number">
            </div>

            <div class="mb-3">
              <label for="inquiryType" class="form-label">Type of Inquiry</label>
              <select class="form-select" id="inquiryType" name="inquiry_type" required>
                <option value="" selected disabled>Select...</option>
                <option value="Collaboration">Collaboration / Project</option>
                <option value="Booking">Booking / Rate Inquiry</option>
                <option value="General Question">General Question</option>
                <option value="Other">Other</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="inquiryMessage" class="form-label">Message / Details</label>
              <textarea class="form-control" id="inquiryMessage" name="message" rows="4" required></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-primary" id="submitBtn"
              style="background-color: #cd919e; border: none;">
              Send Inquiry
            </button>
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

  <section id="testimonials" class="py-5 bg-light">
    <div class="container">
      <div class="text-center mb-5">
        <h4 class="section-title text-uppercase">Testimonials</h4>
        <h2 class="display-5 fw-semibold">Feedbacks</h2>
        <p class="text-muted">Real words from people I've worked with</p>
      </div>

      <div class="swiper testimonialSwiper">
        <div class="swiper-wrapper">
          <?php if (!empty($testimonials)): ?>
            <?php foreach ($testimonials as $testimonial): ?>
              <div class="swiper-slide">
                <div class="testimonial-card h-100 shadow-lg p-5 text-center">
                  <div class="quote-icon">
                    <i class="bi bi-quote fs-1 text-secondary opacity-50 d-block mb-3"></i>
                  </div>
                  <p class="fs-5 fst-italic testimonial-quote-text">
                    "<?php echo h($testimonial['quote_text']); ?>"
                  </p>
                  <div class="client-info mt-4">
                    <h5 class="mb-0"><?php echo h($testimonial['client_name']); ?></h5>
                    <?php if (!empty($testimonial['client_title'])): ?>
                      <span class="text-muted small"><?php echo h($testimonial['client_title']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
          <?php endif; ?>
        </div>
        <div class="swiper-pagination mt-4"></div>
      </div>

    </div>
  </section>
  <div class="modal fade" id="testimonialModal" tabindex="-1" aria-labelledby="testimonialModalLabel"    
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title fw-bold" id="testimonialModalLabel">Share Your Feedback</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Your submission will be reviewed and approved before being displayed on
            the site.
          </p>

          <form id="testimonialForm">
            <div class="mb-3">
              <label for="modal_client_name" class="form-label">Your Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="modal_client_name" name="client_name" required  
                placeholder="E.g., Alex Johnson">
            </div>

            <div class="mb-3">
              <label for="modal_client_title" class="form-label">Your Role/Company (Optional)</label>
              <input type="text" class="form-control" id="modal_client_title" name="client_title"        
                placeholder="E.g., CEO, Acme Corp.">
            </div>

            <div class="mb-3">
              <label for="modal_quote_text" class="form-label">Your Testimonial <span                  
                  class="text-danger">*</span></label>
              <textarea class="form-control" id="modal_quote_text" name="quote_text" rows="4" required    
                placeholder="Write your feedback here..."></textarea>
            </div>

            <div class="modal-footer px-0 pb-0 pt-3 border-top-0">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" id="submitTestimonialBtn"
                style="background-color: #cd919e; border: none;">Submit
                Feedback</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

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