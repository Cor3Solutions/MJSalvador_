<?php
require_once '../config.php';  

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        try {
            $conn = getDBConnection();
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password';
            }
        } catch(PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred. Please try again.';
        }
    } else {
        $error = 'Please enter both username and password';
    }
}

if (!function_exists('h')) {
    function h($text) { return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8'); }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Jade Salvador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root[data-theme="light"] {
            --bg-gradient-start: #cd919e;
            --bg-gradient-end: #764ba2;
            --card-bg: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --input-bg: #ffffff;
            --input-border: #ced4da;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        :root[data-theme="dark"] {
            --bg-gradient-start: #1a1625;
            --bg-gradient-end: #2d1b2e;
            --card-bg: #252238;
            --text-primary: #e9ecef;
            --text-secondary: #adb5bd;
            --input-bg: #2d2a3d;
            --input-border: #3a3550;
            --shadow: 0 20px 60px rgba(0, 0, 0, 0.6);
        }

        :root[data-theme="pink"] {
            --bg-gradient-start: #ff6b9d;
            --bg-gradient-end: #c44569;
            --card-bg: #ffffff;
            --text-primary: #2d1b2e;
            --text-secondary: #6d4c5a;
            --input-bg: #ffffff;
            --input-border: #ffb3d9;
            --shadow: 0 20px 60px rgba(255, 105, 180, 0.4);
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 480px;
        }

        .login-card {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow);
            padding: 3rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .logo-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .logo-icon i {
            font-size: 2.5rem;
            color: white;
        }

        .login-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: var(--text-secondary);
            font-size: 0.95rem;
            font-weight: 500;
        }

        .theme-switcher {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 2rem;
        }

        .theme-btn {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            border: 2px solid transparent;
            background: rgba(0, 0, 0, 0.1);
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.25rem;
        }

        .theme-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .theme-btn.active {
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            color: white;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .form-floating {
            margin-bottom: 1.25rem;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 12px;
            padding: 1rem 1.25rem;
            font-size: 1rem;
            height: auto;
        }

        .form-control:focus {
            background-color: var(--input-bg);
            border-color: var(--bg-gradient-start);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(205, 145, 158, 0.15);
        }

        .form-floating label {
            color: var(--text-secondary);
            padding: 1rem 1.25rem;
        }

        .btn-login {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            border: none;
            color: white;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-link a:hover {
            color: var(--text-primary);
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 2rem 1.5rem;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h2 class="login-title">Admin Login</h2>
                <p class="login-subtitle">Sign in to access your dashboard</p>
            </div>

            <div class="theme-switcher">
                <button class="theme-btn" data-theme="light" title="Light Mode">
                    <i class="bi bi-sun-fill"></i>
                </button>
                <button class="theme-btn" data-theme="dark" title="Dark Mode">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>
                <button class="theme-btn" data-theme="pink" title="Pink Mode">
                    <i class="bi bi-heart-fill"></i>
                </button>
            </div>
            
            <?php if($error): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo h($error); ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Username" value="<?php echo h($username ?? ''); ?>" required>
                    <label for="username"><i class="bi bi-person me-2"></i>Username</label>
                </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Password" required>
                    <label for="password"><i class="bi bi-lock me-2"></i>Password</label>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </button>
            </form>
            
            <div class="back-link">
                <a href="../index.php">
                    <i class="bi bi-arrow-left me-1"></i> Back to Website
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const html = document.documentElement;
            const themeButtons = document.querySelectorAll('.theme-btn');
            
            const savedTheme = localStorage.getItem('adminTheme') || 'light';
            html.setAttribute('data-theme', savedTheme);
            updateActiveButton(savedTheme);

            themeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const theme = this.getAttribute('data-theme');
                    html.setAttribute('data-theme', theme);
                    localStorage.setItem('adminTheme', theme);
                    updateActiveButton(theme);
                });
            });

            function updateActiveButton(theme) {
                themeButtons.forEach(btn => {
                    if (btn.getAttribute('data-theme') === theme) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
        });
    </script>
</body>
</html>