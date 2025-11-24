<?php
/**
 * Email Functions for Application Status Notifications
 * Place this file in: admin/includes/email_functions.php
 */

/**
 * Send application status update email to applicant
 * 
 * @param string $to_email Applicant's email address
 * @param string $applicant_name Full name of the applicant
 * @param string $job_title Title of the position applied for
 * @param string $status New status (pending, reviewed, shortlisted, rejected)
 * @param array $app_details Full application details (optional, for additional context)
 * @return bool True if email was sent successfully, false otherwise
 */
function sendApplicationStatusEmail($to_email, $applicant_name, $job_title, $status, $app_details = []) {
    // Email configuration - Update these with your actual SMTP settings
    $from_email = "mareljadesalvador@gmail.com"; // Change to your domain
    $from_name = "Jade Salvador Talent Management";
    $reply_to = "mareljadesalvador@gmail.com"; // Change to your actual contact email
    
    // Generate subject and message based on status
    switch ($status) {
        case 'reviewed':
            $subject = "Application Update: {$job_title} - Under Review";
            $message = generateReviewedEmail($applicant_name, $job_title);
            break;
            
        case 'shortlisted':
            $subject = "Great News! You've Been Shortlisted - {$job_title}";
            $message = generateShortlistedEmail($applicant_name, $job_title);
            break;
            
        case 'rejected':
            $subject = "Application Update: {$job_title}";
            $message = generateRejectedEmail($applicant_name, $job_title);
            break;
            
        case 'pending':
            $subject = "Application Received: {$job_title}";
            $message = generatePendingEmail($applicant_name, $job_title);
            break;
            
        default:
            error_log("Unknown status for email notification: {$status}");
            return false;
    }
    
    // Build email headers
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "From: {$from_name} <{$from_email}>",
        "Reply-To: {$reply_to}",
        "X-Mailer: PHP/" . phpversion()
    ];
    
    // Try to send email
    try {
        $success = mail($to_email, $subject, $message, implode("\r\n", $headers));
        
        if ($success) {
            error_log("Application status email sent to {$to_email} - Status: {$status}");
        } else {
            error_log("Failed to send application status email to {$to_email} - Status: {$status}");
        }
        
        return $success;
    } catch (Exception $e) {
        error_log("Email sending error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML email for "Reviewed" status
 */
function generateReviewedEmail($name, $job_title) {
    return getEmailTemplate($name, $job_title, 
        "Application Under Review",
        "#3b82f6",
        "
        <p>Thank you for your application for the <strong>{$job_title}</strong> position.</p>
        <p>We wanted to let you know that your application has been reviewed by our team. We appreciate the time and effort you put into your application.</p>
        <p>We are currently in the process of evaluating all applications and will be in touch with you regarding the next steps.</p>
        "
    );
}

/**
 * Generate HTML email for "Shortlisted" status
 */
function generateShortlistedEmail($name, $job_title) {
    return getEmailTemplate($name, $job_title,
        "🎉 You've Been Shortlisted!",
        "#10b981",
        "
        <p>Congratulations! We're pleased to inform you that you've been shortlisted for the <strong>{$job_title}</strong> position.</p>
        <p>Your application stood out among many talented candidates, and we're impressed by your qualifications and experience.</p>
        <p><strong>What's Next?</strong></p>
        <ul style='margin: 15px 0; padding-left: 20px;'>
            <li>Our team will reach out to you soon to discuss the next steps</li>
            <li>Please keep your phone and email accessible</li>
            <li>Prepare any questions you may have about the role</li>
        </ul>
        <p>We look forward to speaking with you soon!</p>
        "
    );
}

/**
 * Generate HTML email for "Rejected" status
 */
function generateRejectedEmail($name, $job_title) {
    return getEmailTemplate($name, $job_title,
        "Application Update",
        "#6b7280",
        "
        <p>Thank you for taking the time to apply for the <strong>{$job_title}</strong> position with Jade Salvador.</p>
        <p>After careful consideration, we regret to inform you that we will not be moving forward with your application at this time.</p>
        <p>This decision was not easy, as we received many applications from qualified candidates. Please know that this does not reflect on your abilities or potential.</p>
        <p>We encourage you to:</p>
        <ul style='margin: 15px 0; padding-left: 20px;'>
            <li>Apply for other positions that match your skills and experience</li>
            <li>Keep an eye on our website for future opportunities</li>
            <li>Continue building your professional portfolio</li>
        </ul>
        <p>We wish you the very best in your career journey and thank you for your interest in Jade Salvador.</p>
        "
    );
}

/**
 * Generate HTML email for "Pending" status
 */
function generatePendingEmail($name, $job_title) {
    return getEmailTemplate($name, $job_title,
        "Application Received",
        "#f59e0b",
        "
        <p>Thank you for applying for the <strong>{$job_title}</strong> position with Jade Salvador!</p>
        <p>We have successfully received your application and wanted to confirm that it's now in our system.</p>
        <p><strong>What happens next?</strong></p>
        <ul style='margin: 15px 0; padding-left: 20px;'>
            <li>Our team will carefully review your application</li>
            <li>We'll notify you of any updates regarding your application status</li>
            <li>The review process typically takes 1-2 weeks</li>
        </ul>
        <p>Thank you for your patience, and we appreciate your interest in joining our team!</p>
        "
    );
}

/**
 * Base email template with responsive HTML design
 */
function getEmailTemplate($name, $job_title, $heading, $color, $content) {
    $current_year = date('Y');
    
    return "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Application Update</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; background-color: #f3f4f6;'>
        <table role='presentation' style='width: 100%; border-collapse: collapse;'>
            <tr>
                <td align='center' style='padding: 40px 0;'>
                    <table role='presentation' style='width: 600px; max-width: 100%; border-collapse: collapse; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);'>
                        <!-- Header -->
                        <tr>
                            <td style='background: linear-gradient(135deg, {$color} 0%, #1f2937 100%); padding: 40px 30px; text-align: center;'>
                                <h1 style='margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;'>
                                    Jade Salvador
                                </h1>
                                <p style='margin: 10px 0 0 0; color: #e5e7eb; font-size: 14px;'>
                                    Talent Management
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Content -->
                        <tr>
                            <td style='padding: 40px 30px;'>
                                <h2 style='margin: 0 0 20px 0; color: {$color}; font-size: 24px; font-weight: bold;'>
                                    {$heading}
                                </h2>
                                
                                <p style='margin: 0 0 15px 0; color: #374151; font-size: 16px; line-height: 1.6;'>
                                    Dear {$name},
                                </p>
                                
                                <div style='color: #374151; font-size: 16px; line-height: 1.6;'>
                                    {$content}
                                </div>
                                
                                <p style='margin: 20px 0 0 0; color: #374151; font-size: 16px; line-height: 1.6;'>
                                    Best regards,<br>
                                    <strong>Jade Salvador Talent Management Team</strong>
                                </p>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style='background-color: #f9fafb; padding: 30px; text-align: center; border-top: 1px solid #e5e7eb;'>
                                <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>
                                    Jade Salvador Talent Management
                                </p>
                                <p style='margin: 0 0 10px 0; color: #6b7280; font-size: 14px;'>
                                    📧 contact@jadesalvador.com | 🌐 www.jadesalvador.com
                                </p>
                                <p style='margin: 0; color: #9ca3af; font-size: 12px;'>
                                    © {$current_year} Jade Salvador. All rights reserved.
                                </p>
                                <p style='margin: 10px 0 0 0; color: #9ca3af; font-size: 12px;'>
                                    This is an automated message. Please do not reply to this email.
                                </p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
    </html>
    ";
}
 