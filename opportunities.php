<?php
/**
 * Opportunities Page - Job Openings and Models/BAs Showcase
 */

require_once 'config.php';

if (!function_exists('h')) {
  function h($text)
  {
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

  // Fetch models
  try {
    $stmt = $conn->prepare("SELECT * FROM models WHERE is_archived = 0 ORDER BY model_id DESC");
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
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
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">

  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/vendor.css">
  <link rel="stylesheet" href="css/style.css">

  <!-- SweetAlert2 -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* Import unified design variables */
    :root {
      --jade-primary: #cd919e;
      --jade-primary-hover: #d68a9bff;
      --jade-secondary: #764ba2;
      --text-primary: #2c3e50;
      --text-secondary: #6c757d;
      --bg-light: #f8f9fa;
      --bg-pink: #ffe4ec;
      --border-color: #e8ecef;
      --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
      --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.1);
      --shadow-lg: 0 8px 25px rgba(0, 0, 0, 0.12);
      --shadow-xl: 0 12px 40px rgba(0, 0, 0, 0.15);
      --transition-base: all 0.3s ease;
      --card-radius: 16px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg-light);
    }

    /* PAGE HEADER - Matching other pages */
    .page-header {
      background: url('cover1.png') center center/cover no-repeat;
      height: 400px;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      margin-bottom: 60px;
    }

    .page-header::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(135deg, rgba(204, 157, 167, 0.85), rgba(143, 114, 173, 0.85));
    }

    .page-header .container {
      position: relative;
      z-index: 2;
    }

    .page-header h1 {
      font-size: 3rem;
      font-weight: 700;
      color: #fff;
      margin-bottom: 1rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .page-header p {
      font-size: 1.2rem;
      color: #fff;
      opacity: 0.95;
    }

    @media (max-width: 768px) {
      .page-header {
        height: 250px;
      }
      
      .page-header h1 {
        font-size: 2rem;
      }
    }

    /* SECTION STYLING */
    .section-badge {
      display: inline-block;
      background: linear-gradient(135deg, var(--jade-primary), var(--jade-secondary));
      color: white;
      padding: 8px 20px;
      border-radius: 50px;
      font-weight: 600;
      font-size: 0.85rem;
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 1rem;
    }

    /* OPPORTUNITIES SECTION */
    .opportunities-section {
      background: var(--bg-pink);
      padding: 80px 0;
    }

    .opportunities-swiper {
      padding: 10px 5px 50px 5px;
    }

    .opportunity-card {
      transition: transform 0.4s ease;
      border: 1px solid var(--border-color);
      border-radius: var(--card-radius);
      overflow: hidden;
      height: 100%;
      background: white;
      box-shadow: var(--shadow-sm);
    }

    .opportunity-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-xl);
      border-color: var(--jade-primary);
    }

    .opportunity-card .card-body {
      padding: 2rem;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .opportunity-card .card-title {
      font-size: 1.25rem;
      color: var(--text-primary);
      margin-bottom: 1rem;
      line-height: 1.4;
      min-height: 60px;
    }

    .opportunity-badge {
      display: inline-block;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .badge-talent {
      background-color: rgba(52, 152, 219, 0.1);
      color: #3498db;
    }

    .badge-virtual-assistant {
      background-color: rgba(155, 89, 182, 0.1);
      color: #9b59b6;
    }

    .badge-brand-ambassador {
      background-color: rgba(230, 126, 34, 0.1);
      color: #e67e22;
    }

    .badge-usherette {
      background-color: rgba(26, 188, 156, 0.1);
      color: #1abc9c;
    }

    .badge-other {
      background-color: rgba(149, 165, 166, 0.1);
      color: #95a5a6;
    }

    /* Swiper Navigation */
    .swiper-button-next,
    .swiper-button-prev {
      width: 50px;
      height: 50px;
      background: white;
      border-radius: 12px;
      box-shadow: var(--shadow-md);
      transition: var(--transition-base);
      border: 1px solid var(--border-color);
    }

    .swiper-button-next::after,
    .swiper-button-prev::after {
      font-size: 18px;
      font-weight: 900;
      color: var(--text-primary);
    }

    .swiper-button-next:hover,
    .swiper-button-prev:hover {
      background: var(--jade-primary);
      border-color: var(--jade-primary);
      box-shadow: var(--shadow-lg);
    }

    .swiper-button-next:hover::after,
    .swiper-button-prev:hover::after {
      color: white;
    }

    .swiper-pagination-bullet {
      width: 10px;
      height: 10px;
      background: #d1d5db;
      opacity: 1;
      transition: var(--transition-base);
    }

    .swiper-pagination-bullet-active {
      background: var(--jade-primary);
      width: 32px;
      border-radius: 5px;
    }

    /* Info badges */
    .info-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 12px;
      background: var(--bg-light);
      border-radius: 8px;
      font-size: 0.85rem;
      color: var(--text-secondary);
      margin-right: 8px;
      margin-bottom: 8px;
    }

    .info-badge.location {
      background: #e3f2fd;
      color: #1976d2;
    }

    .info-badge.salary {
      background: #e8f5e9;
      color: #2e7d32;
      font-weight: 600;
    }

    .info-badge.deadline {
      background: #ffebee;
      color: #c62828;
      font-weight: 600;
    }

    /* MODELS SECTION */
    .models-section {
      background: white;
      padding: 80px 0;
    }

    .model-card {
      position: relative;
      border-radius: var(--card-radius);
      overflow: hidden;
      box-shadow: var(--shadow-lg);
      transition: transform 0.4s ease;
      height: 100%;
      background: white;
      border: 3px solid transparent;
    }

    .model-card:hover {
      transform: translateY(-8px);
      box-shadow: var(--shadow-xl);
      border-color: var(--jade-primary);
    }

    .model-image {
      width: 100%;
      aspect-ratio: 3 / 4;
      object-fit: cover;
      display: block;
      transition: transform 0.5s ease;
    }

    .model-card:hover .model-image {
      transform: scale(1.08);
    }

    .model-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top,
          rgba(44, 62, 80, 0.98) 0%,
          rgba(44, 62, 80, 0.85) 50%,
          transparent 100%);
      padding: 25px;
      color: white;
      transition: padding 0.4s ease;
      z-index: 2;
    }

    .model-card:hover .model-overlay {
      padding: 30px 25px;
    }

    .model-name {
      font-size: 1.4rem;
      font-weight: 700;
      margin-bottom: 0.6rem;
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    }

    .model-details {
      font-size: 0.9rem;
      opacity: 0.95;
    }

    .model-details div {
      margin-bottom: 6px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .model-category-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(255, 255, 255, 0.98);
      color: var(--text-primary);
      padding: 10px 18px;
      border-radius: 25px;
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
      z-index: 3;
      transition: var(--transition-base);
    }

    .model-card:hover .model-category-badge {
      background: var(--jade-primary);
      color: white;
      transform: scale(1.05);
    }

    /* FILTER SECTION */
    .filter-section {
      background: white;
      padding: 25px;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow-sm);
      margin-bottom: 50px;
      border: 2px solid var(--border-color);
    }

    .filter-btn {
      padding: 12px 24px;
      border-radius: 50px;
      border: 2px solid var(--border-color);
      background: white;
      color: var(--text-primary);
      font-weight: 600;
      transition: var(--transition-base);
      margin: 5px;
      cursor: pointer;
    }

    .filter-btn:hover {
      color: white;
      background: var(--jade-primary);
      border-color: var(--jade-primary);
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(205, 145, 158, 0.3);
    }

    .filter-btn.active {
      background: linear-gradient(135deg, var(--jade-primary), var(--jade-secondary));
      color: white;
      border-color: transparent;
      transform: scale(1.05);
      box-shadow: 0 5px 15px rgba(205, 145, 158, 0.4);
    }

    /* EMPTY STATE */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background: white;
      border-radius: var(--card-radius);
      box-shadow: var(--shadow-sm);
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
      color: var(--jade-primary);
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: var(--transition-base);
    }

    .toggle-details-btn:hover {
      color: var(--jade-primary-hover);
    }

    .toggle-details-btn .icon-chevron {
      transition: transform 0.3s ease;
    }

    .toggle-details-btn.active .icon-chevron {
      transform: rotate(180deg);
    }

    /* BUTTONS */
    .btn-primary {
      background: linear-gradient(135deg, var(--jade-primary), var(--jade-secondary));
      border: none;
      color: white;
      font-weight: 600;
      padding: 12px 30px;
      border-radius: 50px;
      transition: var(--transition-base);
      box-shadow: 0 4px 15px rgba(205, 145, 158, 0.3);
    }

    .btn-primary:hover {
      background: linear-gradient(135deg, var(--jade-secondary), var(--jade-primary));
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(205, 145, 158, 0.4);
      color: white;
    }

    /* VIEW MORE BUTTON */
    .view-more-btn {
      padding: 12px 40px;
      border-radius: 50px;
      border: 2px solid var(--jade-primary);
      background: white;
      color: var(--jade-primary);
      font-weight: 600;
      font-size: 1rem;
      transition: var(--transition-base);
      cursor: pointer;
    }

    .view-more-btn:hover {
      background-color: var(--jade-primary);
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(205, 145, 158, 0.3);
    }

    /* HIDE CARDS INITIALLY */
    .model-item:nth-child(n+7) {
      display: none;
    }

    .show-all .model-item {
      display: block;
    }

    /* MODAL STYLING - Modern Redesign */
    .modal-backdrop {
      background-color: rgba(44, 62, 80, 0.6);
      backdrop-filter: blur(8px);
    }

    .modal-content {
      background: linear-gradient(135deg, #ffffff 0%, #fef7f9 100%) !important;
      color: var(--text-primary) !important;
      border-radius: 24px;
      border: none;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      overflow: hidden;
      position: relative;
    }

    .modal-content::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--jade-primary), var(--jade-secondary), var(--jade-primary));
      background-size: 200% 100%;
      animation: gradientShift 3s ease infinite;
    }

    @keyframes gradientShift {
      0%, 100% { background-position: 0% 0%; }
      50% { background-position: 100% 0%; }
    }

    .modal-header {
      background: transparent;
      border-bottom: none;
      padding: 2rem 2rem 1rem 2rem;
      position: relative;
    }

    .modal-title {
      color: var(--text-primary);
      font-weight: 700;
      font-size: 1.5rem;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .modal-title::before {
      content: '📋';
      font-size: 1.8rem;
      display: inline-block;
      animation: bounce 2s infinite;
    }

    @keyframes bounce {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-5px); }
    }

    .modal-title #jobTitle {
      background: linear-gradient(135deg, var(--jade-primary), var(--jade-secondary));
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      font-weight: 800;
    }

    .btn-close {
      background: transparent;
      opacity: 0.6;
      transition: all 0.3s ease;
      padding: 0.5rem;
      border-radius: 50%;
      width: 36px;
      height: 36px;
    }

    .btn-close:hover {
      opacity: 1;
      background: rgba(205, 145, 158, 0.1);
      transform: rotate(90deg);
    }

    .modal-body {
      padding: 1.5rem 2rem 2rem 2rem;
      background-color: transparent;
    }

    .modal-footer {
      background: transparent;
      border-top: 2px solid rgba(205, 145, 158, 0.2);
      padding: 1.5rem 2rem;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }

    /* FORM STYLING */
    .form-label {
      color: var(--text-primary);
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .form-label .text-danger {
      color: #e74c3c !important;
      font-size: 1.1rem;
    }

    .form-control,
    .form-select {
      background-color: #fff;
      color: #333;
      border: 2px solid rgba(205, 145, 158, 0.3);
      border-radius: 12px;
      padding: 0.85rem 1.2rem;
      transition: all 0.3s ease;
      font-size: 0.95rem;
    }

    .form-control:hover {
      border-color: rgba(205, 145, 158, 0.5);
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--jade-primary);
      box-shadow: 0 0 0 4px rgba(205, 145, 158, 0.15);
      outline: none;
      background-color: #fff;
      color: #333;
      transform: translateY(-2px);
    }

    .form-control::placeholder {
      color: #aaa;
      font-style: italic;
    }

    .text-muted {
      color: #6c757d !important;
      font-size: 0.85rem;
      display: flex;
      align-items: center;
      gap: 6px;
      margin-top: 0.25rem;
    }

    .text-muted::before {
      content: '💡';
      font-size: 1rem;
    }

    /* BUTTON STYLING */
    .btn-secondary {
      background: linear-gradient(135deg, #95a5a6, #7f8c8d);
      border: none;
      color: white;
      font-weight: 600;
      padding: 12px 32px;
      border-radius: 50px;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(149, 165, 166, 0.3);
    }

    .btn-secondary:hover {
      background: linear-gradient(135deg, #7f8c8d, #6c7a7b);
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(149, 165, 166, 0.4);
      color: white;
    }

    .modal-footer .btn-primary {
      padding: 12px 40px;
      font-size: 1rem;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .modal-footer .btn-primary i {
      font-size: 1.1rem;
    }

    /* ALERT STYLING */
    .alert {
      border-radius: 12px;
      padding: 1rem 1.25rem;
      margin-top: 1rem;
      border: none;
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 500;
      animation: slideIn 0.3s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .alert::before {
      font-size: 1.5rem;
    }

    .alert-success {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      color: #155724;
      box-shadow: 0 4px 15px rgba(21, 87, 36, 0.2);
    }

    .alert-success::before {
      content: '✅';
    }

    .alert-danger {
      background: linear-gradient(135deg, #f8d7da, #f5c6cb);
      color: #721c24;
      box-shadow: 0 4px 15px rgba(114, 28, 36, 0.2);
    }

    .alert-danger::before {
      content: '❌';
    }

    /* INPUT GROUP STYLING */
    .row {
      margin-bottom: 0;
    }

    .mb-3 {
      position: relative;
    }

    /* FIELD GROUPS */
    #talentApplicationFields,
    #vaApplicationFields {
      background: rgba(205, 145, 158, 0.05);
      padding: 1.5rem;
      border-radius: 16px;
      border: 2px dashed rgba(205, 145, 158, 0.3);
      margin-top: 1rem;
    }

    #talentApplicationFields::before {
      content: '🎭 Talent-Specific Fields';
      display: block;
      font-weight: 700;
      color: var(--jade-primary);
      margin-bottom: 1rem;
      font-size: 1rem;
    }

    #vaApplicationFields::before {
      content: '💼 Virtual Assistant Fields';
      display: block;
      font-weight: 700;
      color: var(--jade-primary);
      margin-bottom: 1rem;
      font-size: 1rem;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
      .opportunities-section,
      .models-section {
        padding: 60px 0;
      }

      .filter-btn {
        font-size: 0.85rem;
        padding: 8px 16px;
      }

      .swiper-button-next,
      .swiper-button-prev {
        width: 40px;
        height: 40px;
      }

      .swiper-button-next::after,
      .swiper-button-prev::after {
        font-size: 14px;
      }

      .modal-body {
        padding: 1.5rem;
      }

      .modal-footer {
        padding: 1rem 1.5rem;
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
  <section class="opportunities-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="section-badge">CAREER OPPORTUNITIES</span>
        <h2 class="display-5 fw-bold">Current Openings</h2>
        <p class="text-muted">Apply now for exciting opportunities</p>
      </div>

      <?php if (!empty($opportunities)): ?>
        <div class="swiper opportunities-swiper">
          <div class="swiper-wrapper">
            <?php foreach ($opportunities as $opp): ?>
              <div class="swiper-slide">
                <div class="card opportunity-card">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                      <span class="opportunity-badge badge-<?php echo strtolower(str_replace(' ', '-', $opp['job_type'])); ?>">
                        <?php echo h($opp['job_type']); ?>
                      </span>
                      <?php if ($opp['deadline']): ?>
                        <small class="info-badge deadline">
                          <i class="bi bi-clock"></i> <?php echo date('M d', strtotime($opp['deadline'])); ?>
                        </small>
                      <?php endif; ?>
                    </div>

                    <h5 class="card-title fw-bold mb-3"><?php echo h($opp['title']); ?></h5>

                    <div class="mb-3">
                      <?php if ($opp['location']): ?>
                        <span class="info-badge location">
                          <i class="bi bi-geo-alt-fill"></i> <?php echo h($opp['location']); ?>
                        </span>
                      <?php endif; ?>

                      <?php if ($opp['net_rate']): ?>
                        <span class="info-badge salary">
                          <i class="bi bi-cash-stack"></i> <?php echo h($opp['net_rate']); ?>
                        </span>
                      <?php endif; ?>
                    </div>

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

                      <a href="#" class="toggle-details-btn mb-3 d-inline-block"
                        data-opp-id="<?php echo $opp['opportunity_id']; ?>">
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

          <div class="swiper-button-next"></div>
          <div class="swiper-button-prev"></div>
          <div class="swiper-pagination"></div>
        </div>

      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-briefcase"></i>
          <h4>No Current Openings</h4>
          <p class="text-muted">Check back soon for new opportunities!</p>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- MODELS/BAs SHOWCASE SECTION -->
  <section class="models-section">
    <div class="container">
      <div class="text-center mb-5">
        <span class="section-badge">PROFESSIONAL PORTFOLIO</span>
        <h2 class="display-5 fw-bold mb-3">Models & Brand Ambassadors</h2>
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
            <div class="col-md-6 col-lg-4 model-item"
              data-category="<?php echo h(strtolower(str_replace(' ', '-', $model['category']))); ?>">
              <div class="model-card">
                <?php if (!empty($model['image_path'])): ?>
                  <img src="<?php echo h($model['image_path']); ?>" alt="<?php echo h($model['model_name']); ?>"
                    class="model-image">
                <?php else: ?>
                  <div class="model-image"
                    style="background: linear-gradient(135deg, #cd919e, #764ba2); display: flex; align-items: center; justify-content: center;">
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

        <?php if (count($models) > 6): ?>
          <div class="text-center mt-5">
            <button class="view-more-btn" id="viewMoreModels">
              <span class="btn-text">View More Portfolio</span>
              <i class="bi bi-chevron-down ms-2"></i>
            </button>
          </div>
        <?php endif; ?>

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
  <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

  <script>
    // INITIALIZE OPPORTUNITIES SWIPER
    const opportunitiesSwiper = new Swiper('.opportunities-swiper', {
      slidesPerView: 1,
      spaceBetween: 24,
      loop: false,
      grabCursor: true,
      pagination: {
        el: '.swiper-pagination',
        clickable: true,
        dynamicBullets: true,
      },
      navigation: {
        nextEl: '.swiper-button-next',
        prevEl: '.swiper-button-prev',
      },
      breakpoints: {
        640: {
          slidesPerView: 2,
          spaceBetween: 24,
        },
        1024: {
          slidesPerView: 3,
          spaceBetween: 30,
        },
      },
    });

    // TOGGLE OPPORTUNITY DETAILS
    document.querySelectorAll('.toggle-details-btn').forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const oppId = this.dataset.oppId;
        const fullDetails = document.querySelector('.full-details-' + oppId);
        const shortDesc = document.querySelector('.short-desc-' + oppId);
        const btnText = this.querySelector('.btn-text');

        if (fullDetails.style.display === 'none' || fullDetails.style.display === '') {
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

    // FILTER MODELS
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', function () {
        const filter = this.dataset.filter;

        // Update active state
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        // Reset "show all" state when filtering
        const modelsGrid = document.getElementById('modelsGrid');
        modelsGrid.classList.remove('show-all');

        // Get all model items
        const modelItems = document.querySelectorAll('.model-item');
        let visibleCount = 0;

        // Filter models
        modelItems.forEach((item, index) => {
          if (filter === 'all' || item.dataset.category === filter) {
            // Show only first 6 items of filtered category
            if (visibleCount < 6) {
              item.style.display = 'block';
            } else {
              item.style.display = 'none';
            }
            visibleCount++;
          } else {
            item.style.display = 'none';
          }
        });

        // Show/hide view more button based on filtered results
        const viewMoreBtn = document.getElementById('viewMoreModels');
        if (viewMoreBtn) {
          if (visibleCount > 6) {
            viewMoreBtn.style.display = 'inline-block';
            viewMoreBtn.querySelector('.btn-text').textContent = 'View More Portfolio';
            viewMoreBtn.querySelector('i').className = 'bi bi-chevron-down ms-2';
          } else {
            viewMoreBtn.style.display = 'none';
          }
        }
      });
    });

    // VIEW MORE MODELS
    const viewMoreModelsBtn = document.getElementById('viewMoreModels');
    if (viewMoreModelsBtn) {
      viewMoreModelsBtn.addEventListener('click', function () {
        const grid = document.getElementById('modelsGrid');
        const btnText = this.querySelector('.btn-text');
        const btnIcon = this.querySelector('i');

        if (grid.classList.contains('show-all')) {
          grid.classList.remove('show-all');
          btnText.textContent = 'View More Portfolio';
          btnIcon.className = 'bi bi-chevron-down ms-2';
          // Scroll to models section
          document.querySelector('.models-section').scrollIntoView({ behavior: 'smooth', block: 'start' });
        } else {
          grid.classList.add('show-all');
          btnText.textContent = 'Show Less';
          btnIcon.className = 'bi bi-chevron-up ms-2';
        }
      });
    }
  </script>
</body>

</html>