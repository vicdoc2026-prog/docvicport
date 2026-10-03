<?php
/**
 * assets_api.php - Backend API
 * Handles AJAX requests for asset data
 */

require_once '../conn.php';

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Clean output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

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

/**
 * Send JSON response
 */
function sendResponse($success, $data = null, $message = '') {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

/**
 * Validate integer parameter
 */
function getIntParam($key, $default = 0) {
    return isset($_GET[$key]) ? (int)$_GET[$key] : $default;
}

/**
 * Get action from request
 */
$action = $_GET['action'] ?? '';

// Route actions
switch ($action) {
    case 'get_campuses':
        getCampuses($conn);
        break;
        
    case 'get_locations':
        getLocations($conn);
        break;
        
    case 'get_assets':
        getAssets($conn);
        break;
        
    case 'get_asset_details':
        getAssetDetails($conn);
        break;
        
    case 'get_asset_units':
        getAssetUnits($conn);
        break;
        
    case 'get_room_distribution':
        getRoomDistribution($conn);
        break;
        
    case 'search_assets':
        searchAssets($conn);
        break;
        
    default:
        sendResponse(false, null, 'Invalid action specified');
}

/**
 * Get all campuses with statistics
 */
function getCampuses($conn) {
    $query = "
        SELECT 
            c." . COL_CAMPUS_ID . " AS id,
            c." . COL_CAMPUS_NAME . " AS name,
            COUNT(DISTINCT l." . COL_LOCATION_ID . ") AS location_count
        FROM " . TBL_CAMPUS . " c
        LEFT JOIN " . TBL_LOCATION . " l ON l." . COL_LOCATION_CAMPUS . " = c." . COL_CAMPUS_ID . "
        GROUP BY c." . COL_CAMPUS_ID . ", c." . COL_CAMPUS_NAME . "
        ORDER BY c." . COL_CAMPUS_NAME . " ASC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        sendResponse(false, null, 'Database query failed: ' . $conn->error);
    }
    
    $campuses = [];
    while ($row = $result->fetch_assoc()) {
        $campuses[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'location_count' => (int)$row['location_count']
        ];
    }
    
    sendResponse(true, $campuses, 'Campuses retrieved successfully');
}

/**
 * Get locations for a specific campus
 */
function getLocations($conn) {
    $campusId = getIntParam('campus_id');
    
    if ($campusId <= 0) {
        sendResponse(false, null, 'Invalid campus_id parameter');
    }
    
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
    
    if (!$stmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'asset_count' => (int)$row['asset_count']
        ];
    }
    
    $stmt->close();
    
    sendResponse(true, $locations, 'Locations retrieved successfully');
}

/**
 * Get assets for a specific location
 */
function getAssets($conn) {
    $locationId = getIntParam('location_id');
    
    if ($locationId <= 0) {
        sendResponse(false, null, 'Invalid location_id parameter');
    }
    
    // Verify location exists
    $checkStmt = $conn->prepare("
        SELECT " . COL_LOCATION_ID . " AS id, " . COL_LOCATION_NAME . " AS name, " . COL_LOCATION_CAMPUS . " AS campus_id
        FROM " . TBL_LOCATION . "
        WHERE " . COL_LOCATION_ID . " = ?
        LIMIT 1
    ");
    
    if (!$checkStmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $checkStmt->bind_param('i', $locationId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        sendResponse(false, null, 'Location not found');
    }
    
    $locationData = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    // Get assets for this location
    $stmt = $conn->prepare("
        SELECT 
            a." . COL_ASSET_ID . " AS id,
            a." . COL_ASSET_NAME . " AS name,
            COUNT(au.assets) AS unit_count
        FROM " . TBL_ASSETS . " a
        LEFT JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . " AND au.location = ?
        WHERE a." . COL_ASSET_LOCATION . " = ?
        GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . "
        ORDER BY a." . COL_ASSET_NAME . " ASC
    ");
    
    if (!$stmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('ii', $locationId, $locationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $assets = [];
    while ($row = $result->fetch_assoc()) {
        $assets[] = [
            'id' => (int)$row['id'],
            'name' => $row['name'],
            'unit_count' => (int)$row['unit_count']
        ];
    }
    
    $stmt->close();
    
    sendResponse(true, [
        'location' => [
            'id' => (int)$locationData['id'],
            'name' => $locationData['name'],
            'campus_id' => (int)$locationData['campus_id']
        ],
        'assets' => $assets
    ], 'Assets retrieved successfully');
}

/**
 * Get detailed information about a specific asset
 */
function getAssetDetails($conn) {
    $assetId = getIntParam('asset_id');
    $locationId = getIntParam('location_id');
    $campusId = getIntParam('campus_id');
    
    if ($assetId <= 0 || $locationId <= 0 || $campusId <= 0) {
        sendResponse(false, null, 'Invalid parameters');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            a." . COL_ASSET_ID . " AS id,
            a." . COL_ASSET_NAME . " AS name,
            COUNT(au.assets) AS total_units,
            SUM(CASE WHEN au.status = 1 THEN 1 ELSE 0 END) AS functional,
            SUM(CASE WHEN au.status = 2 THEN 1 ELSE 0 END) AS not_functional
        FROM " . TBL_ASSETS . " a
        LEFT JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . "
        WHERE a." . COL_ASSET_ID . " = ? 
            AND a." . COL_ASSET_LOCATION . " = ?
            AND au.campus = ?
        GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . "
    ");
    
    if (!$stmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('iii', $assetId, $locationId, $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        sendResponse(false, null, 'Asset not found');
    }
    
    $asset = $result->fetch_assoc();
    $stmt->close();
    
    sendResponse(true, [
        'id' => (int)$asset['id'],
        'name' => $asset['name'],
        'total_units' => (int)$asset['total_units'],
        'functional' => (int)$asset['functional'],
        'not_functional' => (int)$asset['not_functional']
    ], 'Asset details retrieved successfully');
}

/**
 * Get asset units with full details
 */
function getAssetUnits($conn) {
    $assetId = getIntParam('asset_id');
    $locationId = getIntParam('location_id');
    $campusId = getIntParam('campus_id');
    
    if ($assetId <= 0 || $locationId <= 0 || $campusId <= 0) {
        sendResponse(false, null, 'Invalid parameters');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            room,
            model_no,
            serial_no,
            brand,
            capacity,
            price,
            status
        FROM " . TBL_ASSETS_UNIT . "
        WHERE assets = ? AND location = ? AND campus = ?
        ORDER BY room ASC, model_no ASC
    ");
    
    if (!$stmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('iii', $assetId, $locationId, $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $units = [];
    while ($row = $result->fetch_assoc()) {
        $statusText = 'Unknown';
        if ($row['status'] == 1) {
            $statusText = 'Functional';
        } elseif ($row['status'] == 2) {
            $statusText = 'Not Functional';
        }
        
        $units[] = [
            'room' => $row['room'] ?? '',
            'model_no' => $row['model_no'] ?? '',
            'serial_no' => $row['serial_no'] ?? '',
            'brand' => $row['brand'] ?? '',
            'capacity' => $row['capacity'] ?? '',
            'price' => $row['price'] ?? '',
            'status' => (int)$row['status'],
            'status_text' => $statusText
        ];
    }
    
    $stmt->close();
    
    sendResponse(true, $units, 'Asset units retrieved successfully');
}

/**
 * Get room distribution for assets at a location
 */
function getRoomDistribution($conn) {
    $locationId = getIntParam('location_id');
    $campusId = getIntParam('campus_id');
    
    if ($locationId <= 0 || $campusId <= 0) {
        sendResponse(false, null, 'Invalid parameters');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            a." . COL_ASSET_ID . " AS asset_id,
            a." . COL_ASSET_NAME . " AS asset_name,
            au.room,
            COUNT(*) AS unit_count,
            SUM(CASE WHEN au.status = 1 THEN 1 ELSE 0 END) AS functional,
            SUM(CASE WHEN au.status = 2 THEN 1 ELSE 0 END) AS not_functional
        FROM " . TBL_ASSETS . " a
        INNER JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . "
        WHERE a." . COL_ASSET_LOCATION . " = ? 
            AND au.location = ? 
            AND au.campus = ?
        GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . ", au.room
        ORDER BY a." . COL_ASSET_NAME . " ASC, au.room ASC
    ");
    
    if (!$stmt) {
        sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
    }
    
    $stmt->bind_param('iii', $locationId, $locationId, $campusId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $distribution = [];
    while ($row = $result->fetch_assoc()) {
        $assetId = (int)$row['asset_id'];
        
        if (!isset($distribution[$assetId])) {
            $distribution[$assetId] = [
                'asset_id' => $assetId,
                'asset_name' => $row['asset_name'],
                'total_units' => 0,
                'total_functional' => 0,
                'total_not_functional' => 0,
                'rooms' => []
            ];
        }
        
        $unitCount = (int)$row['unit_count'];
        $functional = (int)$row['functional'];
        $notFunctional = (int)$row['not_functional'];
        
        $distribution[$assetId]['rooms'][] = [
            'room' => $row['room'] ?? '',
            'unit_count' => $unitCount,
            'functional' => $functional,
            'not_functional' => $notFunctional
        ];
        
        $distribution[$assetId]['total_units'] += $unitCount;
        $distribution[$assetId]['total_functional'] += $functional;
        $distribution[$assetId]['total_not_functional'] += $notFunctional;
    }
    
    $stmt->close();
    
    // Convert to indexed array
    $distribution = array_values($distribution);
    
    sendResponse(true, $distribution, 'Room distribution retrieved successfully');
}

/**
 * Search assets across locations
 */
function searchAssets($conn) {
    $searchTerm = $_GET['search'] ?? '';
    $campusId = getIntParam('campus_id', 0);
    
    if (empty($searchTerm)) {
        sendResponse(false, null, 'Search term is required');
    }
    
    $searchPattern = '%' . $searchTerm . '%';
    
    if ($campusId > 0) {
        // Search within specific campus
        $stmt = $conn->prepare("
            SELECT 
                a." . COL_ASSET_ID . " AS asset_id,
                a." . COL_ASSET_NAME . " AS asset_name,
                l." . COL_LOCATION_ID . " AS location_id,
                l." . COL_LOCATION_NAME . " AS location_name,
                c." . COL_CAMPUS_ID . " AS campus_id,
                c." . COL_CAMPUS_NAME . " AS campus_name,
                COUNT(au.assets) AS unit_count
            FROM " . TBL_ASSETS . " a
            INNER JOIN " . TBL_LOCATION . " l ON l." . COL_LOCATION_ID . " = a." . COL_ASSET_LOCATION . "
            INNER JOIN " . TBL_CAMPUS . " c ON c." . COL_CAMPUS_ID . " = l." . COL_LOCATION_CAMPUS . "
            LEFT JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . "
            WHERE (a." . COL_ASSET_NAME . " LIKE ? OR l." . COL_LOCATION_NAME . " LIKE ?)
                AND c." . COL_CAMPUS_ID . " = ?
            GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . ", 
                     l." . COL_LOCATION_ID . ", l." . COL_LOCATION_NAME . ",
                     c." . COL_CAMPUS_ID . ", c." . COL_CAMPUS_NAME . "
            ORDER BY a." . COL_ASSET_NAME . " ASC
            LIMIT 50
        ");
        
        if (!$stmt) {
            sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param('ssi', $searchPattern, $searchPattern, $campusId);
    } else {
        // Search across all campuses
        $stmt = $conn->prepare("
            SELECT 
                a." . COL_ASSET_ID . " AS asset_id,
                a." . COL_ASSET_NAME . " AS asset_name,
                l." . COL_LOCATION_ID . " AS location_id,
                l." . COL_LOCATION_NAME . " AS location_name,
                c." . COL_CAMPUS_ID . " AS campus_id,
                c." . COL_CAMPUS_NAME . " AS campus_name,
                COUNT(au.assets) AS unit_count
            FROM " . TBL_ASSETS . " a
            INNER JOIN " . TBL_LOCATION . " l ON l." . COL_LOCATION_ID . " = a." . COL_ASSET_LOCATION . "
            INNER JOIN " . TBL_CAMPUS . " c ON c." . COL_CAMPUS_ID . " = l." . COL_LOCATION_CAMPUS . "
            LEFT JOIN " . TBL_ASSETS_UNIT . " au ON au.assets = a." . COL_ASSET_ID . "
            WHERE a." . COL_ASSET_NAME . " LIKE ? OR l." . COL_LOCATION_NAME . " LIKE ?
            GROUP BY a." . COL_ASSET_ID . ", a." . COL_ASSET_NAME . ", 
                     l." . COL_LOCATION_ID . ", l." . COL_LOCATION_NAME . ",
                     c." . COL_CAMPUS_ID . ", c." . COL_CAMPUS_NAME . "
            ORDER BY a." . COL_ASSET_NAME . " ASC
            LIMIT 50
        ");
        
        if (!$stmt) {
            sendResponse(false, null, 'Failed to prepare statement: ' . $conn->error);
        }
        
        $stmt->bind_param('ss', $searchPattern, $searchPattern);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $results = [];
    while ($row = $result->fetch_assoc()) {
        $results[] = [
            'asset_id' => (int)$row['asset_id'],
            'asset_name' => $row['asset_name'],
            'location_id' => (int)$row['location_id'],
            'location_name' => $row['location_name'],
            'campus_id' => (int)$row['campus_id'],
            'campus_name' => $row['campus_name'],
            'unit_count' => (int)$row['unit_count']
        ];
    }
    
    $stmt->close();
    
    sendResponse(true, [
        'search_term' => $searchTerm,
        'result_count' => count($results),
        'results' => $results
    ], 'Search completed successfully');
}

// Close database connection
$conn->close();
?>