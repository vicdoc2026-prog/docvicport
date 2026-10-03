<?php
/**
 * campus_view.php - Individual Campus Page
 * Shows all locations and assets for a specific campus
 */

require_once '../conn.php';
session_start();
require_once '../config/check-session.php';
require_once '../config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Database constants
const TBL_CAMPUS = 'campus';
const COL_CAMPUS_ID = 'id_campus';
const COL_CAMPUS_NAME = 'campus';

const TBL_LOCATION = 'location';
const COL_LOCATION_ID = 'location_id';
const COL_LOCATION_NAME = 'location';
const COL_LOCATION_CAMPUS = 'campus';

const TBL_ASSETS = 'assets';
const COL_ASSET_ID = 'assets_id';
const COL_ASSET_NAME = 'assets_name';
const COL_ASSET_LOCATION = 'location';

// Get campus ID from URL
$campusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 0;

if ($campusId <= 0) {
    header('Location: campus.php');
    exit;
}

// Fetch campus details
function getCampusDetails($conn, $campusId) {
    $stmt = $conn->prepare("SELECT " . COL_CAMPUS_ID . " AS id, " . COL_CAMPUS_NAME . " AS name FROM " . TBL_CAMPUS . " WHERE " . COL_CAMPUS_ID . " = ? LIMIT 1");
    $stmt->bind_param('i', $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    $campus = $result->fetch_assoc();
    $stmt->close();
    return $campus;
}

// Fetch locations for campus
function getLocationsForCampus($conn, $campusId) {
    $stmt = $conn->prepare("
        SELECT 
            l." . COL_LOCATION_ID . " AS id,
            l." . COL_LOCATION_NAME . " AS name,
            COUNT(a." . COL_ASSET_ID . ") AS asset_count
        FROM " . TBL_LOCATION . " l
        LEFT JOIN " . TBL_ASSETS . " a ON a." . COL_ASSET_LOCATION . " = l." . COL_LOCATION_ID . "
        WHERE l." . COL_LOCATION_CAMPUS . " = ?
        GROUP BY l." . COL_LOCATION_ID . ", l." . COL_LOCATION_NAME . "
        ORDER BY l." . COL_LOCATION_NAME . " ASC
    ");
    $stmt->bind_param('i', $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
    $stmt->close();
    return $locations;
}

$campus = getCampusDetails($conn, $campusId);

if (!$campus) {
    header('Location: campus.php');
    exit;
}

$locations = getLocationsForCampus($conn, $campusId);
$totalLocations = count($locations);
$totalAssets = array_sum(array_column($locations, 'asset_count'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campus['name']); ?> - Locations</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --danger: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --gray: #95a5a6;
            --border: #bdc3c7;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--light);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        /* Breadcrumb */
        .breadcrumb {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .breadcrumb a {
            color: var(--secondary);
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .breadcrumb a:hover {
            color: var(--primary);
        }
        
        .breadcrumb span {
            margin: 0 10px;
            color: var(--gray);
        }
        
        /* Header */
        .header {
            background: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .header-title i {
            font-size: 32px;
            color: var(--secondary);
        }
        
        .header-title h1 {
            font-size: 26px;
            color: var(--dark);
        }
        
        .header-stats {
            display: flex;
            gap: 15px;
        }
        
        .stat-badge {
            background: var(--light);
            padding: 10px 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-badge .value {
            font-size: 24px;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .stat-badge .label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
        }
        
        /* Search Bar */
        .search-bar {
            position: relative;
            max-width: 400px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.2s;
        }
        
        .search-bar input:focus {
            outline: none;
            border-color: var(--secondary);
        }
        
        .search-bar i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }
        
        /* Locations Grid */
        .locations-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-title {
            font-size: 20px;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--secondary);
        }
        
        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .location-card {
            background: white;
            border: 2px solid var(--border);
            border-radius: 12px;
            padding: 20px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .location-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
            border-color: var(--secondary);
        }
        
        .location-icon {
            width: 50px;
            height: 50px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 15px;
            font-size: 24px;
            color: var(--secondary);
        }
        
        .location-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .location-info {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
            font-size: 14px;
        }
        
        .location-info i {
            color: var(--secondary);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px; }
            .header-top { flex-direction: column; align-items: flex-start; }
            .locations-grid { grid-template-columns: 1fr; }
            .search-bar { max-width: 100%; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="campus.php"><i class="fas fa-chevron-left"></i> Back</a>
            <span>/</span>
            <a href="campus.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <strong><?php echo htmlspecialchars($campus['name']); ?></strong>
        </div>
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-title">
                    <i class="fas fa-building"></i>
                    <h1><?php echo htmlspecialchars($campus['name']); ?> Campus</h1>
                </div>
                <div class="header-stats">
                    <div class="stat-badge">
                        <div class="value"><?php echo $totalLocations; ?></div>
                        <div class="label">Locations</div>
                    </div>
                    <div class="stat-badge">
                        <div class="value"><?php echo $totalAssets; ?></div>
                        <div class="label">Assets</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Locations Section -->
        <div class="locations-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-map-marker-alt"></i>
                    Locations
                </h2>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search locations...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <?php if (empty($locations)): ?>
                <div class="empty-state">
                    <i class="fas fa-map-marker-alt"></i>
                    <p>No locations found for this campus.</p>
                </div>
            <?php else: ?>
                <div class="locations-grid" id="locationsGrid">
                    <?php foreach ($locations as $location): ?>
                        <a href="asset_details.php?campus_id=<?php echo $campusId; ?>&location_id=<?php echo (int)$location['id']; ?>" 
                           class="location-card" 
                           data-name="<?php echo htmlspecialchars($location['name']); ?>">
                            <div class="location-icon">
                                <i class="fas fa-warehouse"></i>
                            </div>
                            <div class="location-name"><?php echo htmlspecialchars($location['name']); ?></div>
                            <div class="location-info">
                                <i class="fas fa-box"></i>
                                <span><strong><?php echo (int)$location['asset_count']; ?></strong> Assets</span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const locationsGrid = document.getElementById('locationsGrid');
        const locationCards = document.querySelectorAll('.location-card');
        
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                
                locationCards.forEach(card => {
                    const name = card.dataset.name.toLowerCase();
                    if (name.includes(searchTerm)) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>