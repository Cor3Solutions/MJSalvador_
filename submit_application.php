<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate and sanitize inputs
$opportunity_id = isset($_POST['opportunity_id']) ? (int)$_POST['opportunity_id'] : 0;
$job_type = isset($_POST['job_type']) ? trim($_POST['job_type']) : '';
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$setcard_link = isset($_POST['setcard_link']) ? trim($_POST['setcard_link']) : '';
$vtr_link = isset($_POST['vtr_link']) ? trim($_POST['vtr_link']) : '';
$resume_cv_link = isset($_POST['resume_cv_link']) ? trim($_POST['resume_cv_link']) : '';
$portfolio_link = isset($_POST['portfolio_link']) ? trim($_POST['portfolio_link']) : '';

// Validation
$errors = [];

if ($opportunity_id <= 0) {
    $errors[] = 'Invalid opportunity';
}

if (empty($full_name)) {
    $errors[] = 'Full name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($phone_number)) {
    $errors[] = 'Phone number is required';
}

// Job type specific validation
if ($job_type === 'talent' && empty($setcard_link)) {
    $errors[] = 'Set card link is required for talent applications';
}

if ($job_type === 'virtual-assistant' && empty($resume_cv_link)) {
    $errors[] = 'Resume/CV link is required for virtual assistant applications';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $conn = getDBConnection();
    
    // Check if opportunity exists and is active
    $stmt = $conn->prepare("SELECT title FROM opportunities WHERE opportunity_id = :id AND is_active = 1");
    $stmt->execute([':id' => $opportunity_id]);
    $opportunity = $stmt->fetch();
    
    if (!$opportunity) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'This opportunity is no longer available']);
        exit;
    }
    
    // Insert application
    $stmt = $conn->prepare("
        INSERT INTO applications (opportunity_id, job_type, full_name, email, phone_number, setcard_link, vtr_link, resume_cv_link, portfolio_link, submission_date, is_reviewed, status)
        VALUES (:opp_id, :job_type, :name, :email, :phone, :setcard, :vtr, :resume, :portfolio, NOW(), 0, 'pending')
    ");
    
    $stmt->execute([
        ':opp_id' => $opportunity_id,
        ':job_type' => $job_type,
        ':name' => $full_name,
        ':email' => $email,
        ':phone' => $phone_number,
        ':setcard' => $setcard_link,
        ':vtr' => $vtr_link,
        ':resume' => $resume_cv_link,
        ':portfolio' => $portfolio_link
    ]);
    
    // Send email notification to admin
    $to = "mareljadesalvador@gmail.com";
    $subject = "New Application: " . $opportunity['title'];
    $email_message = "New application received:\n\n";
    $email_message .= "Position: " . $opportunity['title'] . "\n";
    $email_message .= "Job Type: " . $job_type . "\n\n";
    $email_message .= "Name: " . $full_name . "\n";
    $email_message .= "Email: " . $email . "\n";
    $email_message .= "Phone: " . $phone_number . "\n\n";
    
    if ($job_type === 'talent' && !empty($setcard_link)) {
        $email_message .= "Set Card: " . $setcard_link . "\n";
        if (!empty($vtr_link)) {
            $email_message .= "VTR/Demo Reel: " . $vtr_link . "\n";
        }
    }
    
    if ($job_type === 'virtual-assistant') {
        if (!empty($resume_cv_link)) {
            $email_message .= "Resume/CV: " . $resume_cv_link . "\n";
        }
        if (!empty($portfolio_link)) {
            $email_message .= "Portfolio: " . $portfolio_link . "\n";
        }
    }
    
    $headers = "From: mareljadesalvador@gmail.com\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    @mail($to, $subject, $email_message, $headers);
    
    // Send confirmation email to applicant
    $applicant_subject = "Application Received - " . $opportunity['title'];
    $applicant_message = "Dear " . $full_name . ",\n\n";
    $applicant_message .= "Thank you for your application for the position: " . $opportunity['title'] . "\n\n";
    $applicant_message .= "We have received your application and will review it shortly. If your profile matches our requirements, we will contact you soon.\n\n";
    $applicant_message .= "Best regards,\nJade Salvador Team";
    
    $applicant_headers = "From: mareljadesalvador@gmail.com\r\n";
    
    @mail($email, $applicant_subject, $applicant_message, $applicant_headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully! We will contact you soon.'
    ]);
    
} catch(PDOException $e) {
    error_log("Application submission error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>