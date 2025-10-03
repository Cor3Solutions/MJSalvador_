<?php
// Ensure this file is only included after config.php is loaded in the calling file
// The `h()` function (for escaping) and $_SESSION variables are assumed to be available.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Jade Salvador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
        :root {
            --jade-pink: #cd919e;
            --jade-dark: #3a2c38ff;
            --sidebar-width: 250px;
        }

        body {
            background-color: #f8f9fa;
            padding-top: 56px;
        }

        /* 1. Navbar & Sidebar Styling */
        .navbar {
            background-color: var(--jade-dark) !important;
        }

        .navbar-brand img {
            height: 40px;
            /* Adjust as needed */
            width: auto;
            max-width: 150px;
            /* Prevent logo from getting too wide on smaller screens */
        }

        /* NEW STYLING FOR THE TEXT */
        .logo-text {
            font-family: 'Times New Roman', Times, serif;
            /* Apply Times New Roman font */
            font-size: 1.25rem;
            /* Ensure font size is appropriate */
            color: #ffffff;
            /* Make sure the text is visible against the dark navbar */
        }

        .sidebar {
            width: var(--sidebar-width);
            background-color: #ffffff;
            border-right: 1px solid #dee2e6;
            height: 100vh;
            position: fixed;
            z-index: 1000;
            top: 56px;
            left: 0;
            overflow-x: hidden;
            padding-top: 10px;
        }

        .main-content {
            margin-left: var(--sidebar-width);
            padding: 1.5rem;
        }

        /* Responsive Breakpoint: Mobile */
        @media (max-width: 767.98px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                top: 0;
                padding-top: 0;
                border-right: none;
                border-bottom: 1px solid #dee2e6;
            }

            .main-content {
                margin-left: 0;
            }

            .navbar-toggler {
                display: block;
            }
        }

        .nav-link {
            color: var(--jade-dark);
            padding: 10px 15px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .nav-link.active,
        .nav-link:hover {
            color: var(--jade-pink);
            background-color: #f1f1f1;
        }

        .nav-link i {
            margin-right: 8px;
        }

        .sidebar-heading {
            padding: 0 1rem;
        }

        /* 2. Statistics Cards */
        .stat-card {
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card h2 {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Custom card colors */
        .bg-custom-primary {
            background-color: var(--jade-pink) !important;
        }

        .bg-custom-secondary {
            background-color: #764ba2 !important;
        }

        .bg-custom-info {
            background-color: #4db6ac !important;
        }

        .bg-custom-success {
            background-color: #66bb6a !important;
        }

        .bg-custom-warning {
            background-color: #ffb74d !important;
        }

        /* 3. Table Styling */
        .table-card {
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .table-card .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid #dee2e6;
            font-weight: 600;
        }

        .table-card .table thead tr {
            background-color: #f8f9fa;
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
            <a class="navbar-brand me-auto" href="dashboard.php">
                <img src="../images/logo.png" alt="Jade Salvador Admin Logo">
                <span class="logo-text"> Jade Salvador</span>
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3 text-white-50 d-none d-md-inline">Welcome,
                    <?php echo h($_SESSION['full_name'] ?? 'Admin'); ?></span>
                <a class="btn btn-sm" style="color: var(--jade-pink); border-color: var(--jade-pink);"
                    href="logout.php">Logout</a>
            </div>
        </div>
    </nav> 
