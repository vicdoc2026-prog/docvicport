<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sangguniang Panlalawigan Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <style>
        :root {
            --primary: #1a3a6c;
            --secondary: #e63946;
            --accent: #2a9d8f;
            --light: #f1faee;
            --dark: #1d3557;
            --gray: #8d99ae;
            --light-gray: #edf2f4;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .sidebar-item {
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar-subitem {
            padding-left: 2.5rem;
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .voting-table tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .voting-table tr:last-child {
            border-bottom: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
        }
        
        .tab-btn {
            transition: all 0.2s ease;
        }
        
        .tab-btn:hover {
            background-color: rgba(42, 157, 143, 0.1);
        }
        
        .tab-btn.active {
            border-bottom: 3px solid var(--accent);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
        
        .calendar-filter-btn.active {
            background-color: var(--accent);
            color: white;
        }
        
        .fc-event {
            cursor: pointer;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
<?php include 'bar/sidebar.php';?>
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'bar/header.php';?>
        <!-- Dashboard Content -->
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            
        </main>
    </div>
    </body>