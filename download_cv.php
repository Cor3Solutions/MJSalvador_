<?php
/**
 * CV Download Handler with Password Protection
 * Filename: download_cv.php (Place in ROOT directory)
 */

require_once 'config.php';

// Helper function
if (!function_exists('jsonResponse')) {
    function jsonResponse($success, $message = '', $data = []) {
        header('Content-Type: application/json');
        echo json_encode(array_merge([
            'success' => $success,
            'message' => $message
        ], $data));
        exit;
    }
}

try {
    $conn = getDBConnection();
    
    // Handle POST request (password verification)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $cv_id = isset($_POST['cv_id']) ? (int)$_POST['cv_id'] : 0;
        $password = $_POST['password'] ?? '';
        
        if (!$cv_id || empty($password)) {
            jsonResponse(false, 'Invalid request');
        }
        
        // Get CV info and password
        $stmt = $conn->prepare("
            SELECT r.resume_id, r.filepath, r.original_filename, 
                   cp.password_hash
            FROM resumes r
            LEFT JOIN cv_passwords cp ON r.resume_id = cp.resume_id
            WHERE r.resume_id = :id AND r.is_featured = 1
        ");
        $stmt->execute([':id' => $cv_id]);
        $cv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$cv) {
            jsonResponse(false, 'CV not found');
        }
        
        // Check if password is required
        if (!empty($cv['password_hash'])) {
            // Verify password
            if (!password_verify($password, $cv['password_hash'])) {
                // Log failed attempt (optional)
                error_log("Failed CV download attempt for ID: {$cv_id} from IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
                
                jsonResponse(false, 'Incorrect password. Please try again.');
            }
        }
        
        // Password correct or not required - generate download URL
        // Create a temporary token for download
        $token = bin2hex(random_bytes(32));
        $expiry = time() + 300; // 5 minutes
        
        // Store token in session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['cv_download_tokens'])) {
            $_SESSION['cv_download_tokens'] = [];
        }
        
        $_SESSION['cv_download_tokens'][$token] = [
            'cv_id' => $cv_id,
            'expiry' => $expiry
        ];
        
        jsonResponse(true, 'Password verified', [
            'download_url' => "download_cv.php?token={$token}"
        ]);
    }
    
    // Handle GET request (actual download with token)
    elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check for direct download (no password) or token
        if (isset($_GET['id']) && !isset($_GET['token'])) {
            // Direct download without password
            $cv_id = (int)$_GET['id'];
            
            $stmt = $conn->prepare("
                SELECT r.resume_id, r.filepath, r.original_filename, 
                       cp.id as has_password
                FROM resumes r
                LEFT JOIN cv_passwords cp ON r.resume_id = cp.resume_id
                WHERE r.resume_id = :id AND r.is_featured = 1
            ");
            $stmt->execute([':id' => $cv_id]);
            $cv = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cv) {
                die('CV not found');
            }
            
            if ($cv['has_password']) {
                die('This CV requires a password. Please use the download button on the website.');
            }
            
            $cv_to_download = $cv;
        }
        elseif (isset($_GET['token'])) {
            // Token-based download
            $token = $_GET['token'];
            
            if (!isset($_SESSION['cv_download_tokens'][$token])) {
                die('Invalid or expired download token');
            }
            
            $token_data = $_SESSION['cv_download_tokens'][$token];
            
            // Check expiry
            if (time() > $token_data['expiry']) {
                unset($_SESSION['cv_download_tokens'][$token]);
                die('Download token has expired. Please try again.');
            }
            
            // Get CV
            $stmt = $conn->prepare("
                SELECT resume_id, filepath, original_filename
                FROM resumes
                WHERE resume_id = :id AND is_featured = 1
            ");
            $stmt->execute([':id' => $token_data['cv_id']]);
            $cv_to_download = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Clear token after use
            unset($_SESSION['cv_download_tokens'][$token]);
            
            if (!$cv_to_download) {
                die('CV not found');
            }
        }
        else {
            die('Invalid request');
        }
        
        // Serve the file
        $file_path = $cv_to_download['filepath'];
        $full_path = __DIR__ . '/' . $file_path;
        
        if (!file_exists($full_path)) {
            die('File not found on server: ' . htmlspecialchars($file_path));
        }
        
        // Get file info
        $file_size = filesize($full_path);
        $file_name = $cv_to_download['original_filename'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Set content type
        $content_types = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        ];
        $content_type = $content_types[$file_ext] ?? 'application/octet-stream';
        
        // Clear any output buffers
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set headers for download
        header('Content-Type: ' . $content_type);
        header('Content-Disposition: attachment; filename="' . $file_name . '"');
        header('Content-Length: ' . $file_size);
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: 0');
        header('Pragma: public');
        
        // Read and output file
        readfile($full_path);
        exit;
    }
    
} catch (PDOException $e) {
    error_log("CV Download error: " . $e->getMessage());
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        jsonResponse(false, 'An error occurred. Please try again.');
    } else {
        die('An error occurred. Please try again.');
    }
}
?>