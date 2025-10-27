<?php
/**
 * Opportunities Page - Job Openings and Models/BAs Showcase
 */

require_once 'config.php';

if (!function_exists('h')) {
  function h($text) {
    return htmlspecialchars((string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}

$conn = getDBConnection();

// Initialize data arrays
$opportunities = [];
$models = [];
$db_error = null;

try {
  // Fetch active opportunities
  $stmt = $conn->prepare("SELECT * FROM opportunities WHERE is_active = 1 AND is_archived = 0 ORDER BY opportunity_id DESC");
  $stmt->execute();
  $opportunities = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Fetch models (you'll need to create this table)
  // For now, this will return empty array until you create the models table
  try {
    $stmt = $conn->prepare("SELECT * FROM models WHERE is_archived = 0 ORDER BY model_id DESC");
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    // Table doesn't exist yet, that's okay
    $models = [];
  }

} catch (PDOException $e) {
  error_log("Database Error on Opportunities Page: " . $e->getMessage());
  $db_error = "Unable to load content due to a server issue.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Opportunities | Jade S.</title>

  <link rel="icon" type="image/png" href="images/logo.png">
  
  <!-- CSS Libraries -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" href="css/style.css">
  
  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root {
      --primary-color: #2c3e50;
      --secondary-color: #34495e;
      --accent-color: #3498db;
      --accent-hover: #2980b9;
      --text-dark: #2c3e50;
      --text-light: #7f8c8d;
      --border-color: #ecf0f1;
      --success-color: #27ae60;
      --danger-color: #e74c3c;
    }

    body {
      background-color: #f8f9fa;
      color: var(--text-dark);
    }

    /* PAGE HEADER */
    .page-header {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      padding: 80px 0 60px;
      margin-bottom: 60px;
      border-bottom: 3px solid var(--accent-color);
    }

    .page-header h1 {
      font-size: 3rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 1rem;
    }

    .page-header p {
      font-size: 1.2rem;
      color: var(--text-light);
    }

    /* OPPORTUNITIES SECTION */
    .opportunity-card {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid var(--border-color);
      border-radius: 12px;
      overflow: hidden;
      height: 100%;
      background: white;
    }

    .opportunity-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 30px rgba(52, 73, 94, 0.15);
      border-color: var(--accent-color);
    }

    .opportunity-card .card-body {
      padding: 1.5rem;
    }

    .opportunity-badge {
      display: inline-block;
      padding: 6px 14px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .badge-talent {
      background-color: #3498db;
      color: white;
    }

    .badge-virtual-assistant {
      background-color: #9b59b6;
      color: white;
    }

    .badge-brand-ambassador {
      background-color: #e67e22;
      color: white;
    }

    .badge-usherette {
      background-color: #1abc9c;
      color: white;
    }

    .badge-other {
      background-color: #95a5a6;
      color: white;
    }

    /* MODELS SECTION */
    .models-section {
      background-color: white;
      padding: 80px 0;
      border-top: 1px solid var(--border-color);
    }

    .model-card {
      position: relative;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      height: 100%;
      background: white;
    }

    .model-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 35px rgba(0, 0, 0, 0.2);
    }

    .model-image {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      display: block;
    }

    .model-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(44, 62, 80, 0.95), rgba(44, 62, 80, 0.3));
      padding: 20px;
      color: white;
    }

    .model-name {
      font-size: 1.3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .model-details {
      font-size: 0.9rem;
      opacity: 0.95;
    }

    .model-details div {
      margin-bottom: 4px;
    }

    .model-category-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(255, 255, 255, 0.95);
      color: var(--primary-color);
      padding: 8px 15px;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    /* FILTER SECTION */
    .filter-section {
      background: white;
      padding: 20px;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      margin-bottom: 40px;
      border: 1px solid var(--border-color);
    }

    .filter-btn {
      padding: 10px 20px;
      border-radius: 6px;
      border: 2px solid var(--border-color);
      background: white;
      color: var(--text-dark);
      font-weight: 600;
      transition: all 0.3s ease;
      margin: 5px;
      cursor: pointer;
    }

    .filter-btn:hover {
      background-color: var(--accent-color);
      color: white;
      border-color: var(--accent-color);
    }

    .filter-btn.active {
      background-color: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border-color);
    }

    .empty-state i {
      font-size: 4rem;
      color: var(--border-color);
      margin-bottom: 20px;
    }

    /* TOGGLE DETAILS */
    .toggle-details-btn {
      cursor: pointer;
      color: var(--accent-color);
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: color 0.3s ease;
    }

    .toggle-details-btn:hover {
      color: var(--accent-hover);
    }

    .toggle-details-btn .icon-chevron {
      transition: transform 0.3s ease;
    }

    .toggle-details-btn.active .icon-chevron {
      transform: rotate(180deg);
    }

    /* SECTION BADGE */
    .section-badge {
      background-color: var(--primary-color);
      color: white;
      padding: 8px 20px;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }

    /* BUTTONS */
    .btn-primary {
      background-color: var(--accent-color);
      border-color: var(--accent-color);
      font-weight: 600;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: var(--accent-hover);
      border-color: var(--accent-hover);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }

    /* TEXT COLORS */
    .text-success {
      color: var(--success-color) !important;
    }

    .text-danger {
      color: var(--danger-color) !important;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .page-header h1 {
        font-size: 2rem;
      }

      .model-image {
        aspect-ratio: 3 / 4;
      }

      .filter-btn {
        font-size: 0.85rem;
        padding: 8px 16px;
      }
    }
  </style>
</head>
<body>
  <?php include 'navbar.php'; ?>

  <!-- PAGE HEADER -->
  <section class="page-header">
    <div class="container text-center">
      <h1>Opportunities & Portfolio</h1>
      <p>Current job openings and professional collaborations</p>
    </div>
  </section>

  <!-- OPPORTUNITIES SECTION -->
  <section class="container mb-5">
    <div class="text-center mb-5">
      <span class="badge section-badge mb-3">
        CAREER OPPORTUNITIES
      </span>
      <h2 class="display-5 fw-bold">Current Openings</h2>
      <p class="text-muted">Apply now for exciting opportunities</p>
    </div>

    <?php if (!empty($opportunities)): ?>
      <div class="row g-4">
        <?php foreach ($opportunities as $opp): ?>
          <div class="col-md-6 col-lg-4">
            <div class="card opportunity-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                  <span class="opportunity-badge badge-<?php echo strtolower(str_replace(' ', '-', $opp['job_type'])); ?>">
                    <?php echo h($opp['job_type']); ?>
                  </span>
                  <?php if ($opp['deadline']): ?>
                    <small class="text-danger fw-bold">
                      <i class="bi bi-clock"></i> <?php echo date('M d', strtotime($opp['deadline'])); ?>
                    </small>
                  <?php endif; ?>
                </div>

                <h5 class="card-title fw-bold mb-3"><?php echo h($opp['title']); ?></h5>

                <?php if ($opp['location']): ?>
                  <p class="text-muted small mb-2">
                    <i class="bi bi-geo-alt-fill"></i> <?php echo h($opp['location']); ?>
                  </p>
                <?php endif; ?>

                <?php if ($opp['net_rate']): ?>
                  <p class="text-success small mb-3">
                    <i class="bi bi-cash-stack"></i> <strong><?php echo h($opp['net_rate']); ?></strong>
                  </p>
                <?php endif; ?>

                <p class="card-text small short-desc-<?php echo $opp['opportunity_id']; ?>">
                  <?php echo h(substr($opp['description'], 0, 120)); ?>
                  <?php echo strlen($opp['description']) > 120 ? '...' : ''; ?>
                </p>

                <?php if (strlen($opp['description']) > 120 || !empty($opp['requirements'])): ?>
                  <div class="full-details-<?php echo $opp['opportunity_id']; ?>" style="display: none;">
                    <?php if (strlen($opp['description']) > 120): ?>
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

                  <a href="#" class="toggle-details-btn mb-3 d-inline-block" data-opp-id="<?php echo $opp['opportunity_id']; ?>">
                    <i class="bi bi-chevron-down icon-chevron"></i> <span class="btn-text">See More</span>
                  </a>
                <?php endif; ?>

                <button class="btn btn-primary w-100 mt-2" 
                        onclick="showApplicationForm(<?php echo $opp['opportunity_id']; ?>, '<?php echo addslashes(h($opp['title'])); ?>', '<?php echo h($opp['job_type']); ?>')">
                  <i class="bi bi-send-fill"></i> Apply Now
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <i class="bi bi-briefcase"></i>
        <h4>No Current Openings</h4>
        <p class="text-muted">Check back soon for new opportunities!</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- MODELS/BAs SHOWCASE SECTION -->
  <section class="models-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="badge section-badge mb-3">
          PROFESSIONAL PORTFOLIO
        </span>
        <h2 class="display-5 fw-bold">Models & Brand Ambassadors</h2>
        <p class="text-muted">Showcasing our talented team members</p>
      </div>

      <!-- FILTER SECTION -->
      <div class="filter-section text-center">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="talent">Talent</button>
        <button class="filter-btn" data-filter="brand-ambassador">Brand Ambassador</button>
        <button class="filter-btn" data-filter="usherette">Usherette</button>
        <button class="filter-btn" data-filter="virtual-assistant">Virtual Assistant</button>
        <button class="filter-btn" data-filter="other">Other</button>
      </div>

      <?php if (!empty($models)): ?>
        <div class="row g-4" id="modelsGrid">
          <?php foreach ($models as $model): ?>
            <div class="col-md-6 col-lg-4 model-item" data-category="<?php echo h(strtolower(str_replace(' ', '-', $model['category']))); ?>">
              <div class="model-card">
                <?php if (!empty($model['image_path'])): ?>
                  <img src="<?php echo h($model['image_path']); ?>" alt="<?php echo h($model['model_name']); ?>" class="model-image">
                <?php else: ?>
                  <div class="model-image" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-person" style="font-size: 4rem; color: rgba(255,255,255,0.3);"></i>
                  </div>
                <?php endif; ?>
                
                <span class="model-category-badge"><?php echo h(ucwords(str_replace('-', ' ', $model['category']))); ?></span>
                
                <div class="model-overlay">
                  <div class="model-name"><?php echo h($model['model_name']); ?></div>
                  <div class="model-details">
                    <?php if (!empty($model['brand'])): ?>
                      <div><i class="bi bi-building"></i> <?php echo h($model['brand']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($model['event_name'])): ?>
                      <div><i class="bi bi-calendar-event"></i> <?php echo h($model['event_name']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($model['class'])): ?>
                      <div><i class="bi bi-star-fill"></i> Class <?php echo h($model['class']); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($model['agency'])): ?>
                      <div><i class="bi bi-briefcase"></i> <?php echo h($model['agency']); ?></div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-people"></i>
          <h4>Portfolio Coming Soon</h4>
          <p class="text-muted">Our models and brand ambassadors showcase will be available shortly!</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

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
                <input type="url" class="form-control" id="setcard_link" name="setcard_link">
                <small class="text-muted">Provide a link to your set card or portfolio</small>
              </div>
              <div class="mb-3">
                <label for="vtr_link" class="form-label">VTR/Demo Reel Link</label>
                <input type="url" class="form-control" id="vtr_link" name="vtr_link">
              </div>
            </div>

            <div id="vaApplicationFields" style="display: none;">
              <div class="mb-3">
                <label for="resume_cv_link" class="form-label">Resume/CV Link *</label>
                <input type="url" class="form-control" id="resume_cv_link" name="resume_cv_link">
              </div>
              <div class="mb-3">
                <label for="portfolio_link" class="form-label">Portfolio Link</label>
                <input type="url" class="form-control" id="portfolio_link" name="portfolio_link">
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

  <?php include 'footer.php'; ?>

  <!-- JavaScript -->
  <script src="js/jquery-1.11.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // TOGGLE OPPORTUNITY DETAILS
    document.querySelectorAll('.toggle-details-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
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
          btnText.textContent = 'See More';
          this.classList.remove('active');
        }
      });
    });

    // SHOW APPLICATION FORM
    window.showApplicationForm = function(opportunityId, jobTitle, jobType) {
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
      if (talentJobTypes.includes(jobType.toLowerCase())) {
        talentFields.style.display = 'block';
        setcardLink.required = true;
      } else {
        vaFields.style.display = 'block';
        resumeLink.required = true;
      }

      const modal = new bootstrap.Modal(document.getElementById('applicationModal'));
      modal.show();
    };

    // APPLICATION FORM SUBMIT
    document.getElementById('applicationForm').addEventListener('submit', function(e) {
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

    // FILTER MODELS
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function() {
        const filter = this.dataset.filter;
        
        // Update active state
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Filter models
        document.querySelectorAll('.model-item').forEach(item => {
          if (filter === 'all' || item.dataset.category === filter) {
            item.style.display = 'block';
          } else {
            item.style.display = 'none';
          }
        });
      });
    });
  </script>
</body>
</html>