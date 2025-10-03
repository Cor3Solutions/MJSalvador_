<?php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Validate and sanitize inputs
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
$inquiry_type = isset($_POST['inquiry_type']) ? trim($_POST['inquiry_type']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validation
$errors = [];

if (empty($full_name)) {
    $errors[] = 'Full name is required';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Valid email is required';
}

if (empty($inquiry_type)) {
    $errors[] = 'Inquiry type is required';
}

if (empty($message)) {
    $errors[] = 'Message is required';
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

try {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO inquiries (full_name, email, phone_number, inquiry_type, message, submission_date, is_read)
        VALUES (:full_name, :email, :phone_number, :inquiry_type, :message, NOW(), 0)
    ");
    
    $stmt->execute([
        ':full_name' => $full_name,
        ':email' => $email,
        ':phone_number' => $phone_number,
        ':inquiry_type' => $inquiry_type,
        ':message' => $message
    ]);
    
    // Optional: Send email notification
    $to = "mareljadesalvador@gmail.com";
    $subject = "New Inquiry from " . $full_name;
    $email_message = "New inquiry received:\n\n";
    $email_message .= "Name: " . $full_name . "\n";
    $email_message .= "Email: " . $email . "\n";
    $email_message .= "Phone: " . $phone_number . "\n";
    $email_message .= "Type: " . $inquiry_type . "\n";
    $email_message .= "Message:\n" . $message;
    
    $headers = "From: noreply@jadesalvador.com\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    
    @mail($to, $subject, $email_message, $headers);
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your inquiry! We will get back to you soon.'
    ]);
    
} catch(PDOException $e) {
    error_log("Contact form error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.'
    ]);
}
?>