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
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$portfolio_link = isset($_POST['portfolio_link']) ? trim($_POST['portfolio_link']) : '';
$cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
$experience_years = isset($_POST['experience_years']) ? (int)$_POST['experience_years'] : 0;

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

if (empty($cover_letter)) {
    $errors[] = 'Please tell us why you\'re interested';
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
        INSERT INTO applications (opportunity_id, full_name, email, phone_number, portfolio_link, cover_letter, experience_years, submission_date, is_reviewed, status)
        VALUES (:opp_id, :name, :email, :phone, :portfolio, :cover, :exp, NOW(), 0, 'pending')
    ");
    
    $stmt->execute([
        ':opp_id' => $opportunity_id,
        ':name' => $full_name,
        ':email' => $email,
        ':phone' => $phone_number,
        ':portfolio' => $portfolio_link,
        ':cover' => $cover_letter,
        ':exp' => $experience_years
    ]);
    
    // Send email notification to admin
    $to = "mareljadesalvador@gmail.com";
    $subject = "New Application: " . $opportunity['title'];
    $email_message = "New application received:\n\n";
    $email_message .= "Position: " . $opportunity['title'] . "\n";
    $email_message .= "Name: " . $full_name . "\n";
    $email_message .= "Email: " . $email . "\n";
    $email_message .= "Phone: " . $phone_number . "\n";
    $email_message .= "Experience: " . $experience_years . " years\n";
    $email_message .= "Portfolio: " . $portfolio_link . "\n\n";
    $email_message .= "Message:\n" . $cover_letter;
    
    $headers = "From: noreply@jadesalvador.com\r\n";
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