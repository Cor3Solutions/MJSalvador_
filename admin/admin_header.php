<?php
// Ensure this file is only included after config.php is loaded in the calling file
// The `h()` function (for escaping) and $_SESSION variables are assumed to be available.
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jade Salvador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/admin-styles.css" rel="stylesheet">

    <style>
        :root[data-theme="light"] {
            /* Light Mode Colors */
            --jade-primary: #cd919e;
            --jade-primary-hover: #b77f8b;
            --jade-dark: #3a2c38;
            --bg-primary: #ffffff;
            --bg-secondary: #f8f9fa;
            --bg-tertiary: #f1f3f5;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            --border-color: #dee2e6;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.12);
            --navbar-bg: var(--jade-dark);
            --sidebar-bg: #ffffff;
            --card-bg: #ffffff;
            --input-bg: #ffffff;
            --input-border: #ced4da;
            --table-hover: #f8f9fa;
        }

        :root[data-theme="dark"] {
            /* Dark Mode Colors */
            --jade-primary: #e5a4b4;
            --jade-primary-hover: #f0b8c5;
            --jade-dark: #1a1625;
            --bg-primary: #1e1b2e;
            --bg-secondary: #161425;
            --bg-tertiary: #252238;
            --text-primary: #e9ecef;
            --text-secondary: #adb5bd;
            --text-muted: #6c757d;
            --border-color: #3a3550;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.5);
            --navbar-bg: #1a1625;
            --sidebar-bg: #1e1b2e;
            --card-bg: #252238;
            --input-bg: #2d2a3d;
            --input-border: #3a3550;
            --table-hover: #2d2a3d;
        }

        :root[data-theme="pink"] {
            /* Pink Mode Colors */
            --jade-primary: #ff6b9d;
            --jade-primary-hover: #ff8fb8;
            --jade-dark: #2d1b2e;
            --bg-primary: #fff0f5;
            --bg-secondary: #ffe4ec;
            --bg-tertiary: #ffd1dc;
            --text-primary: #2d1b2e;
            --text-secondary: #6d4c5a;
            --text-muted: #b89aa8;
            --border-color: #ffb3d9;
            --shadow-sm: 0 2px 4px rgba(255, 105, 180, 0.1);
            --shadow-md: 0 4px 12px rgba(255, 105, 180, 0.15);
            --shadow-lg: 0 8px 24px rgba(255, 105, 180, 0.2);
            --navbar-bg: linear-gradient(135deg, #ff6b9d 0%, #c44569 100%);
            --sidebar-bg: #fff5f8;
            --card-bg: #ffffff;
            --input-bg: #ffffff;
            --input-border: #ffb3d9;
            --table-hover: #fff0f5;
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            padding-top: 70px;
        }

        /* Navbar Styling */
        .navbar {
            background: var(--navbar-bg) !important;
            box-shadow: var(--shadow-md);
            border-bottom: 1px solid var(--border-color);
            height: 70px;
            z-index: 1030;
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 700;
            color: #ffffff !important;
            font-size: 1.25rem;
        }

        .navbar-brand img {
            height: 45px;
            width: auto;
            max-width: 150px;
            filter: brightness(0) invert(1);
        }

        .logo-text {
            font-family: 'Times New Roman', Times, serif;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        /* Theme Switcher */
        .theme-switcher {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-right: 1rem;
        }

        .theme-btn {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            background: rgba(255, 255, 255, 0.1);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .theme-btn:hover {
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.5);
            background: rgba(255, 255, 255, 0.2);
        }

        .theme-btn.active {
            background: var(--jade-primary);
            border-color: var(--jade-primary);
            box-shadow: 0 4px 12px rgba(205, 145, 158, 0.4);
        }

        /* Sidebar Styling */
        .sidebar {
            width: 280px;
            background-color: var(--sidebar-bg);
            border-right: 1px solid var(--border-color);
            height: calc(100vh - 70px);
            position: fixed;
            z-index: 1000;
            top: 70px;
            left: 0;
            overflow-x: hidden;
            overflow-y: auto;
            padding-top: 1.5rem;
            box-shadow: var(--shadow-md);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--jade-primary);
            border-radius: 3px;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }

        /* Responsive Design */
        @media (max-width: 767.98px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
                padding-top: 0;
                border-right: none;
                border-bottom: 1px solid var(--border-color);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            body {
                padding-top: 70px;
            }
        }

        /* Sidebar Navigation */
        .nav-link {
            color: var(--text-primary);
            padding: 12px 20px;
            margin: 4px 12px;
            font-weight: 500;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }

        .nav-link:hover {
            color: var(--jade-primary);
            background-color: var(--bg-tertiary);
            transform: translateX(4px);
        }

        .nav-link.active {
            color: var(--jade-primary);
            background-color: var(--bg-tertiary);
            font-weight: 600;
            box-shadow: var(--shadow-sm);
        }

        .sidebar-heading {
            padding: 1.5rem 1.5rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--text-muted);
        }

        /* Badge Styling */
        .badge {
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.75rem;
        }

        /* Welcome Text */
        .navbar-text {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
        }

        /* Logout Button */
        .btn-logout {
            color: var(--jade-primary);
            border: 2px solid var(--jade-primary);
            background: transparent;
            border-radius: 8px;
            padding: 6px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-logout:hover {
            background: var(--jade-primary);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(205, 145, 158, 0.3);
        }

        /* Card Styling */
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
        }

        /* Form Controls */
        .form-control, .form-select {
            background-color: var(--input-bg);
            border-color: var(--input-border);
            color: var(--text-primary);
            border-radius: 10px;
            padding: 10px 14px;
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--input-bg);
            border-color: var(--jade-primary);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(205, 145, 158, 0.15);
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        /* Tables */
        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background-color: var(--bg-tertiary);
            color: var(--text-secondary);
            border-color: var(--border-color);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table-hover tbody tr:hover {
            background-color: var(--table-hover);
        }

        .table tbody tr {
            border-color: var(--border-color);
        }

        .table tbody td {
            border-color: var(--border-color);
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-md navbar-dark fixed-top">
        <div class="container-fluid">
            <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu"
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand" href="dashboard.php">
                <img src="logo.png" alt="Jade Salvador Admin Logo">
                <span class="logo-text">Jade Salvador</span>
            </a>
            <div class="d-flex align-items-center">
                <div class="theme-switcher d-none d-md-flex">
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
                <span class="navbar-text me-3 d-none d-md-inline">
                    Welcome, <?php echo h($_SESSION['full_name'] ?? 'Admin'); ?>
                </span>
                <a class="btn btn-logout" href="logout.php">
                    <i class="bi bi-box-arrow-right me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>
    <script src="js/admin-theme.js"></script>

    <script>
        // Theme Switcher Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const html = document.documentElement;
            const themeButtons = document.querySelectorAll('.theme-btn');
            
            // Load saved theme or default to light
            const savedTheme = localStorage.getItem('adminTheme') || 'light';
            html.setAttribute('data-theme', savedTheme);
            updateActiveButton(savedTheme);

            // Theme switcher click handlers
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