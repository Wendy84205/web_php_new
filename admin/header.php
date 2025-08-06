<?php
// admin/header.php
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body>
    <header class="admin-header">
        <div class="container">
            <h1>Food Delivery Admin</h1>
            <nav>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <style>
        /* admin.css hoặc thêm vào phần style trong header.php */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3a0ca3;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --white-color: #ffffff;
            --shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        /* Reset và base styles */
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
        }

        .admin-header {
            background-color: var(--primary-color);
            color: var(--white-color);
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .admin-header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .admin-header h1 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-header nav ul {
            display: flex;
            list-style: none;
            margin: 0;
            padding: 0;
            gap: 1.5rem;
        }

        .admin-header nav a {
            color: var(--white-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            padding: 0.5rem 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .admin-header nav a:hover {
            opacity: 0.8;
        }

        .admin-header nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: var(--white-color);
            transition: width 0.3s ease;
        }

        .admin-header nav a:hover::after {
            width: 100%;
        }

        /* Active link style */
        .admin-header nav a.active {
            font-weight: 600;
        }

        .admin-header nav a.active::after {
            width: 100%;
        }

        /* Container for main content */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .admin-header .container {
                flex-direction: column;
                padding: 1rem;
            }

            .admin-header nav ul {
                margin-top: 1rem;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .admin-header h1 {
                font-size: 1.2rem;
            }

            .admin-header nav a {
                font-size: 0.9rem;
            }
        }
    </style>
    <main class="container"></main>