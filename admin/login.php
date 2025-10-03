<?php
// Include the database configuration file (session_start() should now be inside config.php)
require_once '../config.php'; 

// 1. Check if the user is already logged in
// ... (rest of the PHP logic is retained and correct) ...
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// 2. Process the login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            // Get the database connection
            $conn = getDBConnection();

            // Prepare statement to prevent SQL injection
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            // Check if user exists and verify password hash
            if ($user && password_verify($password, $user['password'])) {
                // Login successful: Set session variables
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Assuming 'first_name' and 'last_name' columns exist
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Redirect to the dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                // Invalid credentials
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            // Log the error for administrator review
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    } else {
        // Missing fields
        $error = 'Please enter both username and password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Jade Salvador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            /* Pink/Purple gradient background (matching your style) */
            background: linear-gradient(135deg, #cd919e 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 400px;
            width: 100%;
        }
        /* Custom styling to match your navbar color */
        .btn-primary {
            background-color: #cd919e;
            border-color: #cd919e;
        }
        .btn-primary:hover {
            background-color: #b77f8b; /* Slightly darker pink on hover */
            border-color: #b77f8b;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h2 class="text-center mb-4">Admin Login</h2>
        
        <?php if($error): ?>
        <div class="alert alert-danger"><?php echo h($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?php echo h($username ?? ''); ?>" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Login</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="../index.php" class="text-muted">Back to Website</a>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>