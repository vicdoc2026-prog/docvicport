<?php
/**
 * campus_index.php - Main Landing Page
 * Displays all campuses with statistics
 */

require_once '../conn.php';
session_start();
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Database table constants
const TBL_CAMPUS = 'campus';
const COL_CAMPUS_ID = 'id_campus';
const COL_CAMPUS_NAME = 'campus';

const TBL_LOCATION = 'location';
const COL_LOCATION_ID = 'location_id';
const COL_LOCATION_CAMPUS = 'campus';

const TBL_ASSETS = 'assets';
const COL_ASSET_LOCATION = 'location';

const TBL_ASSETS_UNIT = 'assets_unit';

// Array of campus background images (can be stored in database or config file)
$campusImages = [
    1 => 'image/stii.jpg', // Main Campus
    2 => 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', // Sanito
    3 => 'https://images.unsplash.com/photo-1516156008625-3a9d6067fab5?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80', // Farm
    // Default fallback images
    4 => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    5 => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
    6 => 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
];

// Function to get campus image (with fallback)
function getCampusImage($campusId, $campusName) {
    global $campusImages;
    
    if (isset($campusImages[$campusId])) {
        return $campusImages[$campusId];
    }
    
    // Map campus names to default images if ID not in array
    $name = strtolower($campusName);
    if (strpos($name, 'main') !== false) {
        return $campusImages[1];
    } elseif (strpos($name, 'sanito') !== false) {
        return $campusImages[2];
    } elseif (strpos($name, 'farm') !== false) {
        return $campusImages[3];
    }
    
    // Random fallback based on campus ID
    $defaultImages = [
        'https://images.unsplash.com/photo-1497366754035-f200968a6e72?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'https://images.unsplash.com/photo-1497366811353-6870744d04b2?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80',
        'https://images.unsplash.com/photo-1509062522246-3755977927d7?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'
    ];
    
    return $defaultImages[$campusId % count($defaultImages)];
}

// Fetch all campuses with statistics
function fetchCampusesWithStats($conn) {
    $query = "
        SELECT 
            c." . COL_CAMPUS_ID . " AS id,
            c." . COL_CAMPUS_NAME . " AS name,
            COUNT(DISTINCT l." . COL_LOCATION_ID . ") AS location_count,
            COUNT(DISTINCT a." . COL_ASSET_LOCATION . ") AS active_locations
        FROM " . TBL_CAMPUS . " c
        LEFT JOIN " . TBL_LOCATION . " l ON l." . COL_LOCATION_CAMPUS . " = c." . COL_CAMPUS_ID . "
        LEFT JOIN " . TBL_ASSETS . " a ON a." . COL_ASSET_LOCATION . " = l." . COL_LOCATION_ID . "
        GROUP BY c." . COL_CAMPUS_ID . ", c." . COL_CAMPUS_NAME . "
        ORDER BY 
            CASE
                WHEN c." . COL_CAMPUS_NAME . " LIKE 'Main%' THEN 1
                WHEN c." . COL_CAMPUS_NAME . " LIKE 'Sanito%' THEN 2
                WHEN c." . COL_CAMPUS_NAME . " LIKE 'Farm%' THEN 3
                ELSE 4
            END,
            c." . COL_CAMPUS_NAME . " ASC
    ";
    
    $result = $conn->query($query);
    $campuses = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $campuses[] = $row;
        }
    }
    
    return $campuses;
}

// Get total assets count
function getTotalAssets($conn) {
    $query = "SELECT COUNT(DISTINCT assets_name) AS total FROM " . TBL_ASSETS;
    $result = $conn->query($query);
    return $result ? (int)$result->fetch_assoc()['total'] : 0;
}

$campuses = fetchCampusesWithStats($conn);
$totalCampuses = count($campuses);
$totalLocations = array_sum(array_column($campuses, 'location_count'));
$totalAssets = getTotalAssets($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STII Asset Management - Campus Overview</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary: #1a237e;
            --primary-light: #534bae;
            --primary-dark: #000051;
            --secondary: #2979ff;
            --accent: #00b0ff;
            --success: #00c853;
            --warning: #ff9100;
            --info: #2962ff;
            --light: #f5f7ff;
            --dark: #1a1a2e;
            --gray: #78909c;
            --border: #e0e7ff;
            --card-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            --hover-shadow: 0 10px 40px rgba(41, 121, 255, 0.15);
            --overlay: rgba(0, 0, 0, 0.6);
            --overlay-light: rgba(0, 0, 0, 0.4);
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #f5f7ff 0%, #e3e9ff 100%);
            min-height: 100vh;
            color: var(--dark);
            line-height: 1.6;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Dashboard Header */
        .dashboard-header {
            margin-bottom: 2.5rem;
        }
        
        .dashboard-title {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .dashboard-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .dashboard-title h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.5px;
        }
        
        .dashboard-subtitle {
            color: var(--gray);
            font-size: 1.1rem;
            max-width: 600px;
            line-height: 1.7;
        }
        
        /* Statistics Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, var(--secondary), var(--accent));
        }
        
        .stat-card:nth-child(2)::before {
            background: linear-gradient(to bottom, var(--success), #00e676);
        }
        
        .stat-card:nth-child(3)::before {
            background: linear-gradient(to bottom, var(--warning), #ffab40);
        }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }
        
        .stat-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--light), white);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--secondary);
            font-size: 1.2rem;
        }
        
        .stat-trend {
            font-size: 0.8rem;
            color: var(--success);
            font-weight: 600;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
        }
        
        /* Campus Grid */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .campus-count {
            background: var(--light);
            color: var(--primary);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .campus-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }
        
        .campus-card {
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: white;
            display: block;
            position: relative;
            min-height: 280px;
        }
        
        .campus-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--hover-shadow);
        }
        
        .campus-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            z-index: 1;
            transition: transform 0.5s ease;
        }
        
        .campus-card:hover .campus-bg {
            transform: scale(1.1);
        }
        
        .campus-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(0,0,0,0.7));
            z-index: 2;
        }
        
        .campus-content {
            position: relative;
            z-index: 3;
            height: 100%;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
        }
        
        .campus-header {
            margin-bottom: auto;
        }
        
        .campus-icon {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .campus-name {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .campus-id {
            font-size: 0.85rem;
            opacity: 0.9;
            background: rgba(255, 255, 255, 0.2);
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        
        .campus-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 1rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .stat-icon-small {
            width: 36px;
            height: 36px;
            background: rgba(255, 255, 255, 0.25);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }
        
        .stat-details h4 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-details p {
            font-size: 0.8rem;
            opacity: 0.9;
        }
        
        .campus-footer {
            padding-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .view-btn {
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .campus-card:hover .view-btn {
            background: rgba(255, 255, 255, 0.3);
            transform: translateX(5px);
        }
        
        .progress-container {
            flex: 1;
            margin-right: 1rem;
        }
        
        .progress-label {
            font-size: 0.8rem;
            opacity: 0.9;
            margin-bottom: 0.25rem;
        }
        
        .progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(to right, var(--accent), #18ffff);
            border-radius: 3px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: var(--card-shadow);
            border: 2px dashed var(--border);
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray);
            max-width: 400px;
            margin: 0 auto 1.5rem;
        }
        
        /* Footer */
        .dashboard-footer {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            margin-top: 3rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-container {
                padding: 1.5rem;
            }
            
            .campus-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
                margin-top: 1rem;
            }
            
            .dashboard-title h1 {
                font-size: 1.75rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .campus-grid {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .dashboard-title {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .stat-value {
                font-size: 2rem;
            }
            
            .campus-name {
                font-size: 1.4rem;
            }
        }
        
        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-container">
        <!-- Dashboard Header -->
        <div class="dashboard-header fade-in">
            <div class="dashboard-title">
                <div>
                    <h1>Campus Overview Dashboard</h1>
                </div>
            </div>
        </div>
        
        <!-- Statistics Overview -->
        <div class="stats-grid">
            <div class="stat-card fade-in">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <span class="stat-trend">
                        <i class="fas fa-chart-line"></i> Active
                    </span>
                </div>
                <div class="stat-value"><?php echo $totalCampuses; ?></div>
                <div class="stat-label">Total Campuses</div>
            </div>
            
            <div class="stat-card fade-in delay-1">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <span class="stat-trend">
                        <i class="fas fa-chart-line"></i> Active
                    </span>
                </div>
                <div class="stat-value"><?php echo $totalLocations; ?></div>
                <div class="stat-label">Total Locations</div>
            </div>
            
            <div class="stat-card fade-in delay-2">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-laptop-house"></i>
                    </div>
                    <span class="stat-trend">
                        <i class="fas fa-chart-line"></i> Tracked
                    </span>
                </div>
                <div class="stat-value"><?php echo $totalAssets; ?></div>
                <div class="stat-label">Total Assets</div>
            </div>
        </div>
        
        <!-- Campus Selection -->
        <div class="section-header fade-in delay-3">
            <h2 class="section-title">
                <i class="fas fa-layer-group"></i>
                Campus Locations
                <span class="campus-count"><?php echo $totalCampuses; ?> campuses</span>
            </h2>
            <div class="progress-bar" style="flex: 0 0 200px; background: var(--border);">
                <div class="progress-fill" style="width: <?php echo $totalLocations > 0 ? min(100, ($totalAssets / ($totalLocations * 10) * 100)) : 0; ?>%"></div>
            </div>
        </div>
        
        <?php if (empty($campuses)): ?>
            <div class="empty-state fade-in">
                <div class="empty-icon">
                    <i class="fas fa-building"></i>
                </div>
                <h3>No Campuses Found</h3>
                <p>There are currently no campuses registered in the system. Please contact your administrator to add campus locations.</p>
            </div>
        <?php else: ?>
            <div class="campus-grid">
                <?php foreach ($campuses as $campus): 
                    $activePercentage = $campus['location_count'] > 0 ? 
                        ($campus['active_locations'] / $campus['location_count'] * 100) : 0;
                    $campusImage = getCampusImage($campus['id'], $campus['name']);
                ?>
                    <a href="campus_view.php?campus_id=<?php echo (int)$campus['id']; ?>" class="campus-card fade-in">
                        <div class="campus-bg" style="background-image: url('<?php echo $campusImage; ?>');"></div>
                        <div class="campus-overlay"></div>
                        
                        <div class="campus-content">
                            <div class="campus-header">
                                <!-- <div class="campus-icon">
                                    <i class="fas fa-building"></i>
                                </div> -->
                                <div class="campus-name"><?php echo htmlspecialchars($campus['name']); ?></div>
                                <!-- <div class="campus-id">ID: CAMP-<?php echo str_pad($campus['id'], 3, '0', STR_PAD_LEFT); ?></div> -->
                            </div>
                            
                            <div class="campus-stats">
                                <div class="stat-item">
                                    <div class="stat-icon-small">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h4><?php echo (int)$campus['location_count']; ?></h4>
                                        <p>Locations</p>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon-small">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="stat-details">
                                        <h4><?php echo (int)$campus['active_locations']; ?></h4>
                                        <p>Active</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="campus-footer">
                                <div class="progress-container">
                                    <div class="progress-label">Active Locations</div>
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo min(100, $activePercentage); ?>%"></div>
                                    </div>
                                </div>
                                <span class="view-btn">
                                    View Details
                                    <i class="fas fa-arrow-right"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- <div class="dashboard-footer">
            <p>STII Asset Management System v2.1 • <?php echo date('F Y'); ?> • Last updated: Today</p>
        </div> -->
    </div>
</body>
</html>