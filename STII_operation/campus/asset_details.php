<?php
/**
 * asset_details.php - Asset Room Distribution (Table View)
 * Shows assets grouped by room in a table format
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

const TBL_ASSETS_UNIT = 'assets_unit';

// Get parameters
$campusId = isset($_GET['campus_id']) ? (int)$_GET['campus_id'] : 0;
$locationId = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

if ($campusId <= 0 || $locationId <= 0) {
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

// Fetch location details
function getLocationDetails($conn, $locationId, $campusId) {
    $stmt = $conn->prepare("
        SELECT " . COL_LOCATION_ID . " AS id, " . COL_LOCATION_NAME . " AS name 
        FROM " . TBL_LOCATION . " 
        WHERE " . COL_LOCATION_ID . " = ? AND " . COL_LOCATION_CAMPUS . " = ? 
        LIMIT 1
    ");
    $stmt->bind_param('ii', $locationId, $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    $location = $result->fetch_assoc();
    $stmt->close();
    return $location;
}

// Fetch assets with room distribution
function getAssetsWithRoomDistribution($conn, $locationId, $campusId) {
    $stmt = $conn->prepare("
        SELECT 
            a." . COL_ASSET_ID . " AS asset_id,
            a." . COL_ASSET_NAME . " AS asset_name,
            MAX(au.model_no) AS model_no,
            MAX(au.serial_no) AS serial_no,
            MAX(au.brand) AS brand,
            MAX(au.capacity) AS capacity,
            MAX(au.price) AS price,
            au.room,
            COUNT(*) AS unit_count,
            SUM(CASE WHEN au.status = 1 THEN 1 ELSE 0 END) AS functional_count,
            SUM(CASE WHEN au.status = 2 THEN 1 ELSE 0 END) AS not_functional_count
        FROM " . TBL_ASSETS . " a
        INNER JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . "
        WHERE a." . COL_ASSET_LOCATION . " = ? 
            AND au.location = ? 
            AND au.campus = ?
        GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . ", au.room
        ORDER BY a." . COL_ASSET_NAME . " ASC, au.room ASC
    ");
    $stmt->bind_param('iii', $locationId, $locationId, $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assetsGrouped = [];
    while ($row = $result->fetch_assoc()) {
        $assetId = $row['asset_id'];
        if (!isset($assetsGrouped[$assetId])) {
            $assetsGrouped[$assetId] = [
                'asset_name' => $row['asset_name'],
                'total_units' => 0,
                'total_functional' => 0,
                'total_not_functional' => 0,
                'rooms' => []
            ];
        }
        
        $assetsGrouped[$assetId]['rooms'][] = [
            'room' => $row['room'],
            'unit_count' => (int)$row['unit_count'],
            'functional' => (int)$row['functional_count'],
            'not_functional' => (int)$row['not_functional_count'],
            'model_no' => $row['model_no'],
            'serial_no' => $row['serial_no'],
            'brand' => $row['brand'],
            'capacity' => $row['capacity'],
            'price' => $row['price']
        ];
        
        $assetsGrouped[$assetId]['total_units'] += (int)$row['unit_count'];
        $assetsGrouped[$assetId]['total_functional'] += (int)$row['functional_count'];
        $assetsGrouped[$assetId]['total_not_functional'] += (int)$row['not_functional_count'];
    }
    $stmt->close();
    
    return $assetsGrouped;
}

$campus = getCampusDetails($conn, $campusId);
$location = getLocationDetails($conn, $locationId, $campusId);

if (!$campus || !$location) {
    header('Location: campus.php');
    exit;
}

$assetsData = getAssetsWithRoomDistribution($conn, $locationId, $campusId);
$totalAssets = count($assetsData);
$totalUnits = array_sum(array_column($assetsData, 'total_units'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($location['name']); ?> - Asset Details</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
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
        
        .header-title {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
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
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: var(--light);
            padding: 15px;
            border-radius: 10px;
            text-align: center;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--secondary);
        }
        
        .stat-card .label {
            font-size: 12px;
            color: var(--gray);
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        /* Assets Section */
        .assets-section {
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
        
        .search-bar {
            position: relative;
            max-width: 350px;
        }
        
        .search-bar input {
            width: 100%;
            padding: 10px 40px 10px 15px;
            border: 2px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
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
        
        /* Table Styles */
        .table-responsive {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .assets-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .assets-table thead {
            background: linear-gradient(135deg, var(--secondary), #5dade2);
            color: white;
        }
        
        .assets-table thead th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
        }
        
        .assets-table thead th:first-child {
            border-top-left-radius: 10px;
        }
        
        .assets-table thead th:last-child {
            border-top-right-radius: 10px;
        }
        
        .assets-table tbody tr {
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
        }
        
        .assets-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .assets-table tbody td {
            padding: 12px 15px;
            font-size: 14px;
            color: var(--dark);
            vertical-align: middle;
        }
        
        .asset-group-header {
            background: #f1f3f5;
            font-weight: 700;
        }
        
        .asset-group-header td {
            padding: 15px !important;
            color: var(--primary);
        }
        
        .asset-name-cell {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .asset-icon {
            width: 35px;
            height: 35px;
            background: var(--secondary);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        .room-row td:first-child {
            padding-left: 50px;
        }
        
        .room-name {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--dark);
        }
        
        .room-name i {
            color: var(--gray);
            font-size: 12px;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }
        
        .badge-primary {
            background: #e3f2fd;
            color: #1565c0;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .summary-row {
            background: #f8f9fa;
            font-weight: 700;
        }
        
        .summary-row td {
            padding: 15px !important;
            color: var(--primary);
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
        
        /* View Toggle */
        .view-toggle {
            display: flex;
            gap: 10px;
        }
        
        .view-btn {
            padding: 8px 16px;
            border: 2px solid var(--border);
            background: white;
            color: var(--gray);
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .view-btn:hover {
            border-color: var(--secondary);
            color: var(--secondary);
        }
        
        .view-btn.active {
            background: var(--secondary);
            color: white;
            border-color: var(--secondary);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .header { padding: 20px; }
            .assets-section { padding: 20px; }
            .search-bar { max-width: 100%; }
            
            .assets-table {
                font-size: 12px;
            }
            
            .assets-table thead th,
            .assets-table tbody td {
                padding: 10px;
            }
            
            .room-row td:first-child {
                padding-left: 30px;
            }
            
            .asset-icon {
                width: 30px;
                height: 30px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="campus.php"><i class="fas fa-home"></i> Home</a>
            <span>/</span>
            <a href="campus_view.php?campus_id=<?php echo $campusId; ?>"><?php echo htmlspecialchars($campus['name']); ?></a>
            <span>/</span>
            <strong><?php echo htmlspecialchars($location['name']); ?></strong>
        </div>
        
        <!-- Header -->
        <div class="header">
            <div class="header-title">
                <i class="fas fa-warehouse"></i>
                <h1><?php echo htmlspecialchars($location['name']); ?></h1>
            </div>
            <div class="header-stats">
                <div class="stat-card">
                    <div class="value"><?php echo $totalAssets; ?></div>
                    <div class="label">Asset Types</div>
                </div>
                <div class="stat-card">
                    <div class="value"><?php echo $totalUnits; ?></div>
                    <div class="label">Total Units</div>
                </div>
            </div>
        </div>
        
        <!-- Assets Section -->
        <div class="assets-section">
            <div class="section-header">
                <h2 class="section-title">
                    <i class="fas fa-boxes-stacked"></i>
                    Assets by Room
                </h2>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Search assets or rooms...">
                    <i class="fas fa-search"></i>
                </div>
            </div>
            
            <?php if (empty($assetsData)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>No assets found for this location.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="assets-table" id="assetsTable">
                        <thead>
                            <tr>
                                <th style="width: 35%;">Asset / Room</th>
                                <th class="text-center" style="width: 15%;">Total Units</th>
                                <th class="text-center" style="width: 15%;">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspFunctional</th>
                                <th class="text-center" style="width: 20%;">&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbspStatus</th>
                                <th class="text-center">Model & Serial No.</th>
                                <th class="text-center">Brand</th>
                                <th class="text-center">Capacity</th>
                                <th class="text-center">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assetsData as $assetId => $asset): ?>
                                
                                <tr class="asset-group-header" data-asset-id="<?php echo $assetId; ?>">
                                    <td colspan="10">
                                        <div class="asset-name-cell">
                                            <div class="asset-icon">
                                                <i class="fas fa-box"></i>
                                            </div>
                                            <span><?php echo htmlspecialchars($asset['asset_name']); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                
                                <!-- Room Rows -->
                                <?php foreach ($asset['rooms'] as $room): ?>
                                    <tr class="room-row" data-asset-name="<?php echo htmlspecialchars($asset['asset_name']); ?>" data-room-name="<?php echo htmlspecialchars($room['room']); ?>">
                                        <td>
                                            <div class="room-name">
                                                <i class="fas fa-door-open"></i>
                                                <?php echo htmlspecialchars($room['room']); ?>
                                            </div>
                                        </td>
                                        
                                        
                                        <td class="text-center">
                                            <span class="badge badge-primary"><?php echo $room['unit_count']; ?></span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-success"><?php echo $room['functional']; ?></span>
                                        </td>
                                                                                <td class="text-center">
                                            <?php 
                                                $functionalPercentage = $room['unit_count'] > 0 ? round(($room['functional'] / $room['unit_count']) * 100) : 0;
                                                $statusClass = $functionalPercentage >= 80 ? 'badge-success' : ($functionalPercentage >= 50 ? 'badge-warning' : 'badge-danger');
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>"><?php echo $functionalPercentage; ?>% Functional</span>
                                        </td>
         <td class="text-center"><?php echo htmlspecialchars($room['model_no']); ?></td>
        <td class="text-center"><?php echo htmlspecialchars($room['brand']); ?></td>
        <td class="text-center"><?php echo htmlspecialchars($room['capacity']); ?></td>
       <td class="text-center"><?php echo htmlspecialchars($room['price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <!-- Asset Summary Row -->
                                <tr class="summary-row">
                                    <td class="text-right">
                                        <strong>Total for <?php echo htmlspecialchars($asset['asset_name']); ?>:</strong>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?php echo $asset['total_units']; ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success"><?php echo $asset['total_functional']; ?></span>
                                    </td>
                                    <!-- <td class="text-center">
                                        <?php if ($asset['total_not_functional'] > 0): ?>
                                            <span class="badge badge-danger"><?php echo $asset['total_not_functional']; ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-success">0</span>
                                        <?php endif; ?>
                                    </td> -->
                                    <td class="text-center">
                                        <?php 
                                            $totalFunctionalPercentage = $asset['total_units'] > 0 ? round(($asset['total_functional'] / $asset['total_units']) * 100) : 0;
                                            $totalStatusClass = $totalFunctionalPercentage >= 80 ? 'badge-success' : ($totalFunctionalPercentage >= 50 ? 'badge-warning' : 'badge-danger');
                                        ?>
                                    </td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        const table = document.getElementById('assetsTable');
        
        if (searchInput && table) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase().trim();
                const tbody = table.querySelector('tbody');
                const assetGroups = {};
                
                // Group rows by asset
                const rows = tbody.querySelectorAll('tr');
                let currentAssetId = null;
                
                rows.forEach(row => {
                    if (row.classList.contains('asset-group-header')) {
                        currentAssetId = row.dataset.assetId;
                        assetGroups[currentAssetId] = {
                            header: row,
                            rooms: [],
                            summary: null
                        };
                    } else if (row.classList.contains('room-row')) {
                        if (currentAssetId && assetGroups[currentAssetId]) {
                            assetGroups[currentAssetId].rooms.push(row);
                        }
                    } else if (row.classList.contains('summary-row')) {
                        if (currentAssetId && assetGroups[currentAssetId]) {
                            assetGroups[currentAssetId].summary = row;
                        }
                    }
                });
                
                // Filter each asset group
                Object.values(assetGroups).forEach(group => {
                    const assetName = group.header.querySelector('.asset-name-cell span').textContent.toLowerCase();
                    let hasVisibleRoom = false;
                    
                    // Check each room
                    group.rooms.forEach(roomRow => {
                        const roomName = roomRow.dataset.roomName.toLowerCase();
                        if (assetName.includes(searchTerm) || roomName.includes(searchTerm)) {
                            roomRow.style.display = '';
                            hasVisibleRoom = true;
                        } else {
                            roomRow.style.display = 'none';
                        }
                    });
                    
                    // Show/hide the entire asset group
                    if (hasVisibleRoom || (searchTerm === '' || assetName.includes(searchTerm))) {
                        group.header.style.display = '';
                        if (group.summary) group.summary.style.display = '';
                    } else {
                        group.header.style.display = 'none';
                        if (group.summary) group.summary.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>