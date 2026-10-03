<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Fetch faculty data
$sql_teaching = "SELECT * FROM faculty WHERE category = 'teaching' AND status = 'active' ORDER BY full_name ASC";
$sql_non_teaching = "SELECT * FROM faculty WHERE category = 'non-teaching' AND status = 'active' ORDER BY full_name ASC";

$result_teaching = $conn->query($sql_teaching);
$result_non_teaching = $conn->query($sql_non_teaching);

// Count totals
$total_teaching = $result_teaching->num_rows;
$total_non_teaching = $result_non_teaching->num_rows;
$total_faculty = $total_teaching + $total_non_teaching;

// Get department counts for teaching
$sql_dept = "SELECT department, COUNT(*) as count FROM faculty WHERE category = 'teaching' AND status = 'active' GROUP BY department ORDER BY count DESC";
$result_dept = $conn->query($sql_dept);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Members - STII</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #1e40af;
            --primary-light: #3b82f6;
            --secondary: #059669;
            --accent: #dc2626;
            --dark: #1f2937;
            --light: #f3f4f6;
            --border: #e5e7eb;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #f0f9ff, #e6f7ff, #d1eeff);
            font-family: 'Inter', sans-serif;
            color: #111827;
            font-size: 14px;
            min-height: 100vh;
        }
        
        .container-fluid {
            max-width: 1400px;
            padding: 20px;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: #475569;
        }
        
        /* Header */
        .page-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            padding: 20px 25px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .page-header h1 {
            font-size: 26px;
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .page-header p {
            font-size: 13px;
            color: #6b7280;
            margin: 5px 0 0 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.12);
        }
        
        .stats-icon {
            width: 64px;
            height: 64px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
        }
        
        .stats-content h3 {
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            line-height: 1;
        }
        
        .stats-content p {
            color: #6b7280;
            margin: 6px 0 0 0;
            font-size: 14px;
            font-weight: 500;
        }

        /* Tab Navigation */
        .tab-navigation {
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            gap: 8px;
        }

        .tab-btn {
            flex: 1;
            padding: 14px 24px;
            border: none;
            background: transparent;
            color: #6b7280;
            font-weight: 600;
            font-size: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .tab-btn:hover {
            background: var(--light);
            color: var(--primary);
        }

        .tab-btn.active {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            box-shadow: 0 4px 12px rgba(30, 64, 175, 0.3);
        }

        .tab-btn i {
            font-size: 18px;
        }

        /* Faculty Content */
        .faculty-section {
            display: none;
        }

        .faculty-section.active {
            display: block;
        }

        .faculty-table-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .faculty-table {
            width: 100%;
            border-collapse: collapse;
        }

        .faculty-table thead {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
        }

        .faculty-table th {
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .faculty-table td {
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        .faculty-table tbody tr {
            transition: all 0.2s;
        }

        .faculty-table tbody tr:hover {
            background: #f9fafb;
        }

        .faculty-table tbody tr:last-child td {
            border-bottom: none;
        }

        .faculty-avatar-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            font-weight: 700;
            margin-right: 12px;
            vertical-align: middle;
        }

        .faculty-name-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .faculty-name-text {
            font-weight: 600;
            color: var(--dark);
        }

        .faculty-id {
            font-size: 12px;
            color: #9ca3af;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 8px;
        }

        .badge-professor { background: #dbeafe; color: #1e40af; }
        .badge-associate { background: #d1fae5; color: #059669; }
        .badge-assistant { background: #fef3c7; color: #d97706; }
        .badge-instructor { background: #f3e8ff; color: #9333ea; }
        .badge-staff { background: #e0e7ff; color: #4f46e5; }

        /* Search & Filter */
        .search-filter-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 10px 40px 10px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box i {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .filter-select {
            padding: 10px 16px;
            border: 2px solid var(--border);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #374151;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 768px) {

            .btn-secondary {
                width: 100%;
                justify-content: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tab-navigation {
                flex-direction: column;
            }

            .search-filter-bar {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: 100%;
            }

            .faculty-table-container {
                overflow-x: auto;
            }

            .faculty-table {
                min-width: 800px;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-state h3 {
            font-size: 18px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 14px;
        }
            
        :root {
            --primary: #17695d;
            --primary-light: #2b8d7b;
            --secondary: #c17b42;
            --dark: #183a35;
            --light: #edf3ef;
            --border: #dce6df;
            --muted: #64766f;
            --surface: #ffffff;
            --page-bg: #f3f6f2;
        }

        body {
            background: radial-gradient(ellipse at 100% 0%, rgba(204, 226, 215, .38), transparent 34%), var(--page-bg);
            color: #263b36;
            font-size: 14px;
        }

        .main-content-wrapper {
            min-height: 100vh;
        }

        .container-fluid {
            width: 100%;
            max-width: 1540px;
            padding: 28px clamp(18px, 3vw, 44px) 48px;
        }

        .page-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 24px;
            padding: 8px 0 24px;
            margin-bottom: 18px;
            border-bottom: 1px solid var(--border);
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            backdrop-filter: none;
        }

        .page-kicker,
        .table-kicker {
            margin: 0 0 7px;
            color: var(--primary);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .page-header h1 {
            color: var(--dark);
            font-size: clamp(27px, 3vw, 36px);
            line-height: 1.1;
            font-weight: 750;
        }

        .page-header p:last-child {
            max-width: 640px;
            margin-top: 9px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.55;
        }

        .directory-status {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            flex: 0 0 auto;
            padding: 9px 12px;
            border: 1px solid #d4e3da;
            border-radius: 6px;
            background: #f8fbf8;
            color: #395a4e;
            font-size: 12px;
            font-weight: 700;
        }

        .directory-status::before {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #3c9a6d;
            content: '';
        }

        .stats-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0;
            margin-bottom: 24px;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, .56);
        }

        .stats-card {
            min-width: 0;
            padding: 17px 20px;
            gap: 14px;
            border-right: 1px solid var(--border);
            border-radius: 0;
            background: transparent;
            box-shadow: none;
            transition: background .18s ease;
        }

        .stats-card:last-child { border-right: 0; }
        .stats-card:hover { transform: none; background: rgba(255, 255, 255, .8); box-shadow: none; }

        .stats-icon {
            width: 42px;
            height: 42px;
            flex: 0 0 42px;
            border-radius: 8px;
            font-size: 18px;
        }

        .stats-content h3 {
            color: var(--dark);
            font-size: 26px;
            font-variant-numeric: tabular-nums;
        }

        .stats-content p {
            margin-top: 5px;
            color: var(--muted);
            font-size: 12px;
        }

        .tab-navigation {
            gap: 4px;
            padding: 0;
            margin-bottom: 0;
            border-bottom: 1px solid var(--border);
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .tab-btn {
            position: relative;
            flex: 0 1 auto;
            min-height: 50px;
            padding: 12px 18px;
            border-radius: 0;
            color: #65766f;
            font-size: 13px;
            justify-content: flex-start;
        }

        .tab-btn:hover { background: #e9f0eb; color: var(--dark); }

        .tab-btn.active {
            background: transparent;
            color: var(--primary);
            box-shadow: none;
        }

        .tab-btn.active::after {
            position: absolute;
            right: 12px;
            bottom: -1px;
            left: 12px;
            height: 3px;
            background: var(--primary);
            content: '';
        }

        .tab-btn i { font-size: 15px; }

        .search-filter-bar {
            display: flex;
            gap: 12px;
            padding: 16px 0;
            margin-bottom: 16px;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .search-box { min-width: 220px; }

        .search-box input,
        .filter-select {
            min-height: 44px;
            border: 1px solid #cfdcd3;
            border-radius: 6px;
            background-color: #fff;
            color: #263b36;
            font: inherit;
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .search-box input { padding: 10px 42px 10px 14px; }
        .search-box input::placeholder { color: #899991; }
        .search-box input:focus,
        .filter-select:focus {
            border-color: var(--primary-light);
            outline: 0;
            box-shadow: 0 0 0 3px rgba(43, 141, 123, .14);
        }

        .search-box i { right: 15px; color: #72857c; }

        .filter-select {
            min-width: 210px;
            padding: 10px 38px 10px 13px;
            cursor: pointer;
        }

        .result-summary {
            display: flex;
            align-items: center;
            margin-left: auto;
            color: var(--muted);
            font-size: 12px;
            font-weight: 600;
            white-space: nowrap;
        }

        .faculty-table-container {
            overflow: hidden;
            margin-bottom: 22px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--surface);
            box-shadow: 0 5px 18px rgba(34, 61, 49, .045);
        }

        .table-heading {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
        }

        .table-heading h2 {
            margin: 0;
            color: var(--dark);
            font-size: 16px;
            font-weight: 750;
        }

        .table-kicker { margin-bottom: 4px; font-size: 10px; }

        .roster-count {
            flex: 0 0 auto;
            padding: 5px 9px;
            border-radius: 4px;
            background: #edf5f0;
            color: #426657;
            font-size: 11px;
            font-weight: 700;
        }

        .table-scroll { overflow-x: auto; }

        .faculty-table {
            min-width: 980px;
            border-collapse: separate;
            border-spacing: 0;
        }

        .faculty-table thead { background: #f3f7f4; color: #62766d; }

        .faculty-table th {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            color: #62766d;
            font-size: 10px;
            letter-spacing: .08em;
            white-space: nowrap;
        }

        .faculty-table td {
            padding: 13px 16px;
            border-bottom: 1px solid #edf1ee;
            color: #44574f;
            font-size: 12px;
            vertical-align: middle;
        }

        .faculty-table tbody tr:hover { background: #f8fbf8; }
        .faculty-table tbody tr:last-child td { border-bottom: 0; }

        .faculty-avatar-small {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            margin: 0;
            border-radius: 8px;
            background: #dcece4;
            color: var(--primary);
            font-size: 12px;
        }

        .faculty-name-cell { gap: 10px; }
        .faculty-name-text { color: #263b36; font-size: 12px; }
        .faculty-id { color: #819087; }

        .faculty-table .badge {
            margin: 0;
            padding: 5px 8px;
            border: 1px solid rgba(23, 105, 93, .11);
            border-radius: 4px;
            background: #edf5f0;
            color: #426657;
            font-size: 10px;
            white-space: nowrap;
        }

        .badge-professor { background: #e9f1f7 !important; color: #365f78 !important; }
        .badge-associate { background: #e8f3ec !important; color: #38694c !important; }
        .badge-assistant { background: #fbf0e4 !important; color: #9c612a !important; }
        .badge-instructor { background: #f0edf8 !important; color: #66538b !important; }

        .faculty-table a { color: var(--primary); text-decoration: none; white-space: nowrap; }
        .faculty-table a:hover { color: #10483f; text-decoration: underline; }

        .empty-state { padding: 42px 20px; color: #788981; }
        .empty-state i { color: #8ca99a; font-size: 42px; opacity: .65; }
        .empty-state h3 { color: var(--dark); font-size: 16px; }
        .empty-state p { color: var(--muted); font-size: 12px; }

        @media (max-width: 768px) {
            .container-fluid { padding: 20px 16px 32px; }
            .page-header { align-items: flex-start; flex-direction: column; gap: 12px; }
            .directory-status { align-self: flex-start; }
            .stats-grid { grid-template-columns: 1fr; }
            .stats-card { border-right: 0; border-bottom: 1px solid var(--border); }
            .stats-card:last-child { border-bottom: 0; }
            .tab-navigation { display: grid; grid-template-columns: 1fr 1fr; }
            .tab-btn { justify-content: center; padding: 12px 8px; font-size: 12px; }
            .search-filter-bar { align-items: stretch; }
            .search-box { min-width: 100%; }
            .filter-select { width: 100%; min-width: 0; }
            .result-summary { justify-content: flex-start; min-height: 20px; margin-left: 0; }
        }

        @media (max-width: 480px) {
            .tab-navigation { grid-template-columns: 1fr; }
            .tab-btn.active::after { right: 30%; left: 30%; }
            .table-heading { align-items: flex-start; flex-direction: column; }
        }
    </style>
</head>
<body>
    <?php include 'bar/sidebar.php'; ?>
    <main class="main-content-wrapper">
    <div class="container-fluid">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <p class="page-kicker">STII / DIRECTORY</p>
                <h1><i class="fas fa-chalkboard-teacher me-2"></i>Faculty &amp; Staff</h1>
                <p>Sibugay Technical Institute Incorporated faculty and staff directory.</p>
            </div>
            <span class="directory-status">Active directory</span>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stats-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #dbeafe, #bfdbfe);">
                    <i class="fas fa-users text-primary"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_faculty; ?></h3>
                    <p>Total Faculty Members</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #d1fae5, #a7f3d0);">
                    <i class="fas fa-chalkboard-teacher text-success"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_teaching; ?></h3>
                    <p>Teaching Faculty</p>
                </div>
            </div>
            
            <div class="stats-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a);">
                    <i class="fas fa-user-tie text-warning"></i>
                </div>
                <div class="stats-content">
                    <h3><?php echo $total_non_teaching; ?></h3>
                    <p>Non-Teaching Staff</p>
                </div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation" role="tablist" aria-label="Faculty category">
            <button type="button" class="tab-btn active" onclick="switchTab('teaching')" id="tab-teaching" role="tab" aria-selected="true" aria-controls="teaching-section">
                <i class="fas fa-chalkboard-teacher"></i>
                Teaching Faculty (<?php echo $total_teaching; ?>)
            </button>
            <button type="button" class="tab-btn" onclick="switchTab('non-teaching')" id="tab-non-teaching" role="tab" aria-selected="false" aria-controls="non-teaching-section">
                <i class="fas fa-user-tie"></i>
                Non-Teaching Staff (<?php echo $total_non_teaching; ?>)
            </button>
        </div>

        <!-- Search & Filter -->
        <div class="search-filter-bar">
            <div class="search-box">
                <label class="visually-hidden" for="searchInput">Search faculty and staff</label>
                <input type="search" id="searchInput" placeholder="Search name, position, or department" oninput="filterFaculty()">
                <i class="fas fa-search"></i>
            </div>
            <label class="visually-hidden" for="departmentFilter">Filter by department</label>
            <select class="filter-select" id="departmentFilter" onchange="filterFaculty()">
                <option value="">All Departments</option>
                <?php 
                $result_dept->data_seek(0);
                while($dept = $result_dept->fetch_assoc()): 
                ?>
                    <option value="<?php echo htmlspecialchars($dept['department']); ?>">
                        <?php echo htmlspecialchars($dept['department']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <div class="result-summary" id="resultSummary" aria-live="polite">Showing <?php echo $total_teaching; ?> records</div>
        </div>

        <!-- Teaching Faculty Section -->
        <section id="teaching-section" class="faculty-section active" role="tabpanel" aria-labelledby="tab-teaching">
            <div class="faculty-table-container">
                <div class="table-heading">
                    <div><p class="table-kicker">ACADEMIC PERSONNEL</p><h2>Teaching faculty roster</h2></div>
                    <span class="roster-count"><?php echo $total_teaching; ?> members</span>
                </div>
                <div class="table-scroll">
                <table class="faculty-table">
                    <thead>
                        <tr>
                            <th>Faculty ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date Hired</th>
                        </tr>
                    </thead>
                    <tbody id="teaching-tbody">
                        <?php 
                        $result_teaching->data_seek(0);
                        if($result_teaching->num_rows > 0):
                            while($faculty = $result_teaching->fetch_assoc()): 
                                $initials = strtoupper(substr($faculty['full_name'], 0, 1));
                                $position_badge = 'badge-staff';
                                if(strpos(strtolower($faculty['position']), 'professor') !== false) {
                                    $position_badge = 'badge-professor';
                                } elseif(strpos(strtolower($faculty['position']), 'associate') !== false) {
                                    $position_badge = 'badge-associate';
                                } elseif(strpos(strtolower($faculty['position']), 'assistant') !== false) {
                                    $position_badge = 'badge-assistant';
                                } elseif(strpos(strtolower($faculty['position']), 'instructor') !== false) {
                                    $position_badge = 'badge-instructor';
                                }
                        ?>
                        <tr data-name="<?php echo strtolower($faculty['full_name']); ?>" 
                            data-position="<?php echo strtolower($faculty['position']); ?>" 
                            data-department="<?php echo strtolower($faculty['department']); ?>">
                            <td>
                                <span class="badge <?php echo $position_badge; ?>"><?php echo htmlspecialchars($faculty['faculty_id']); ?></span>
                            </td>
                            <td>
                                <div class="faculty-name-cell">
                                    <div class="faculty-avatar-small"><?php echo $initials; ?></div>
                                    <span class="faculty-name-text"><?php echo htmlspecialchars($faculty['full_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($faculty['position']); ?></td>
                            <td><?php echo htmlspecialchars($faculty['department']); ?></td>
                            <td>
                                <?php if($faculty['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($faculty['email']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($faculty['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($faculty['phone']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($faculty['phone']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $faculty['date_hired'] ? date('M d, Y', strtotime($faculty['date_hired'])) : '-'; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h3>No Teaching Faculty Found</h3>
                                    <p>There are currently no teaching faculty members in the system.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>

        <!-- Non-Teaching Faculty Section -->
        <section id="non-teaching-section" class="faculty-section" role="tabpanel" aria-labelledby="tab-non-teaching" hidden>
            <div class="faculty-table-container">
                <div class="table-heading">
                    <div><p class="table-kicker">INSTITUTIONAL PERSONNEL</p><h2>Non-teaching staff roster</h2></div>
                    <span class="roster-count"><?php echo $total_non_teaching; ?> members</span>
                </div>
                <div class="table-scroll">
                <table class="faculty-table">
                    <thead>
                        <tr>
                            <th>Staff ID</th>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Department/Office</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Date Hired</th>
                        </tr>
                    </thead>
                    <tbody id="non-teaching-tbody">
                        <?php 
                        $result_non_teaching->data_seek(0);
                        if($result_non_teaching->num_rows > 0):
                            while($faculty = $result_non_teaching->fetch_assoc()): 
                                $initials = strtoupper(substr($faculty['full_name'], 0, 1));
                        ?>
                        <tr data-name="<?php echo strtolower($faculty['full_name']); ?>" 
                            data-position="<?php echo strtolower($faculty['position']); ?>" 
                            data-department="<?php echo strtolower($faculty['department']); ?>">
                            <td>
                                <span class="badge badge-staff"><?php echo htmlspecialchars($faculty['faculty_id']); ?></span>
                            </td>
                            <td>
                                <div class="faculty-name-cell">
                                    <div class="faculty-avatar-small" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                        <?php echo $initials; ?>
                                    </div>
                                    <span class="faculty-name-text"><?php echo htmlspecialchars($faculty['full_name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($faculty['position']); ?></td>
                            <td><?php echo htmlspecialchars($faculty['department']); ?></td>
                            <td>
                                <?php if($faculty['email']): ?>
                                <a href="mailto:<?php echo htmlspecialchars($faculty['email']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($faculty['email']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($faculty['phone']): ?>
                                <a href="tel:<?php echo htmlspecialchars($faculty['phone']); ?>" style="color: var(--primary); text-decoration: none;">
                                    <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($faculty['phone']); ?>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $faculty['date_hired'] ? date('M d, Y', strtotime($faculty['date_hired'])) : '-'; ?></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="empty-state">
                                    <i class="fas fa-user-slash"></i>
                                    <h3>No Non-Teaching Staff Found</h3>
                                    <p>There are currently no non-teaching staff members in the system.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </section>
    </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Switch between tabs
        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.setAttribute('aria-selected', 'false'));
            const activeTab = document.getElementById('tab-' + tab);
            activeTab.classList.add('active');
            activeTab.setAttribute('aria-selected', 'true');
            
            // Update sections
            document.querySelectorAll('.faculty-section').forEach(section => section.classList.remove('active'));
            document.querySelectorAll('.faculty-section').forEach(section => section.hidden = true);
            const activeSection = document.getElementById(tab + '-section');
            activeSection.classList.add('active');
            activeSection.hidden = false;
            
            // Reset filters
            document.getElementById('searchInput').value = '';
            document.getElementById('departmentFilter').value = '';
            filterFaculty();
        }

        // Filter faculty
        function filterFaculty() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const department = document.getElementById('departmentFilter').value.toLowerCase();
            
            // Determine active section
            const activeSection = document.querySelector('.faculty-section.active');
            const rows = activeSection.querySelectorAll('tbody tr[data-name]');
            
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                const position = row.getAttribute('data-position');
                const dept = row.getAttribute('data-department');
                
                const matchesSearch = name.includes(searchText) || 
                                     position.includes(searchText) || 
                                     dept.includes(searchText);
                                     
                const matchesDepartment = !department || dept.includes(department);
                
                if (matchesSearch && matchesDepartment) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const resultSummary = document.getElementById('resultSummary');
            resultSummary.textContent = `Showing ${visibleCount} ${visibleCount === 1 ? 'record' : 'records'}`;
            
            // Show/hide empty state
            const tbody = activeSection.querySelector('tbody');
            let emptyRow = tbody.querySelector('.empty-search-row');
            
            if (visibleCount === 0 && (searchText || department)) {
                if (!emptyRow) {
                    emptyRow = document.createElement('tr');
                    emptyRow.className = 'empty-search-row';
                    emptyRow.innerHTML = `
                        <td colspan="7" class="text-center py-5">
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h3>No Results Found</h3>
                                <p>Try adjusting your search or filters.</p>
                            </div>
                        </td>
                    `;
                    tbody.appendChild(emptyRow);
                }
                emptyRow.style.display = '';
            } else if (emptyRow) {
                emptyRow.style.display = 'none';
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>