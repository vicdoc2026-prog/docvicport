<?php
// api/machinery.php - Main CRUD API
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/machinery_errors.log');

header('Content-Type: application/json');

function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    error_log($logMessage . "\n", 3, __DIR__ . '/machinery_debug.log');
}

logDebug("========== MACHINERY API CALLED ==========");
logDebug("Request Method", $_SERVER['REQUEST_METHOD']);
logDebug("Request URI", $_SERVER['REQUEST_URI']);

try {
    require_once '../config/conn.php';
    logDebug("Database connection file included successfully");
} catch (Exception $e) {
    logDebug("ERROR: Failed to include conn.php", $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection file not found',
        'error' => $e->getMessage()
    ]);
    exit;
}

if (!isset($pdo)) {
    logDebug("ERROR: PDO object not found");
    echo json_encode([
        'success' => false,
        'message' => 'Database connection object not found'
    ]);
    exit;
}

$action = $_GET['action'] ?? '';
logDebug("Action requested", $action);

switch ($action) {
    case 'list':
        listMachinery();
        break;
    case 'get':
        getMachinery();
        break;
    case 'add':
        addMachinery();
        break;
    case 'update':
        updateMachinery();
        break;
    case 'delete':
        deleteMachinery();
        break;
    case 'stats':
        getMachineryStats();
        break;
    default:
        logDebug("ERROR: Invalid action", $action);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action',
            'valid_actions' => ['list', 'get', 'add', 'update', 'delete', 'stats']
        ]);
}

function listMachinery() {
    global $pdo;
    logDebug("LIST: Starting listMachinery function");
    
    try {
        $sql = "SELECT * FROM farm_machinery ORDER BY id DESC";
        logDebug("LIST: SQL Query", $sql);
        
        $stmt = $pdo->query($sql);
        $machinery = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        logDebug("LIST: Records fetched", count($machinery));
        
        echo json_encode([
            'success' => true,
            'data' => $machinery,
            'count' => count($machinery)
        ]);
    } catch (PDOException $e) {
        logDebug("LIST ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

function getMachinery() {
    global $pdo;
    logDebug("GET: Starting getMachinery function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("GET: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        $stmt = $pdo->prepare("SELECT * FROM farm_machinery WHERE id = ?");
        $stmt->execute([$id]);
        $machinery = $stmt->fetch(PDO::FETCH_ASSOC);
        
        logDebug("GET: Record found", $machinery ? 'YES' : 'NO');
        
        if ($machinery) {
            echo json_encode([
                'success' => true,
                'data' => $machinery
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Machinery not found',
                'id_searched' => $id
            ]);
        }
    } catch (Exception $e) {
        logDebug("GET ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function addMachinery() {
    global $pdo;
    logDebug("ADD: Starting addMachinery function");
    
    try {
        $input = file_get_contents('php://input');
        logDebug("ADD: Raw input", $input);
        
        $data = json_decode($input, true);
        logDebug("ADD: Decoded data", $data);
        
        if (!$data) {
            throw new Exception("Invalid JSON data");
        }
        
        $required = ['machinery_name', 'machinery_type', 'quantity', 'status'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                throw new Exception("Missing required field: $field");
            }
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO farm_machinery
            (machinery_name, machinery_type, quantity, status,  notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            trim($data['machinery_name']),
            trim($data['machinery_type']),
            intval($data['quantity']),
            trim($data['status']),
            !empty($data['notes']) ? trim($data['notes']) : null
        ]);
        
        $newId = $pdo->lastInsertId();
        logDebug("ADD: New record ID", $newId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Machinery added successfully',
            'id' => $newId,
            'data' => $data
        ]);
    } catch (Exception $e) {
        logDebug("ADD ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function updateMachinery() {
    global $pdo;
    logDebug("UPDATE: Starting updateMachinery function");
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        logDebug("UPDATE: Data received", $data);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception("Invalid data or missing ID");
        }
        
        $stmt = $pdo->prepare("
            UPDATE farm_machinery 
            SET machinery_name = ?, 
                machinery_type = ?, 
                quantity = ?, 
                status = ?, 
                notes = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            trim($data['machinery_name']),
            trim($data['machinery_type']),
            intval($data['quantity']),
            trim($data['status']),
            !empty($data['notes']) ? trim($data['notes']) : null,
            intval($data['id'])
        ]);
        
        logDebug("UPDATE: Rows affected", $stmt->rowCount());
        
        echo json_encode([
            'success' => true,
            'message' => 'Machinery updated successfully',
            'rows_affected' => $stmt->rowCount()
        ]);
    } catch (Exception $e) {
        logDebug("UPDATE ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function deleteMachinery() {
    global $pdo;
    logDebug("DELETE: Starting deleteMachinery function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("DELETE: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        $stmt = $pdo->prepare("DELETE FROM farm_machinery WHERE id = ?");
        $result = $stmt->execute([intval($id)]);
        
        logDebug("DELETE: Rows affected", $stmt->rowCount());
        
        echo json_encode([
            'success' => true,
            'message' => 'Machinery deleted successfully',
            'rows_affected' => $stmt->rowCount()
        ]);
    } catch (Exception $e) {
        logDebug("DELETE ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getMachineryStats() {
    global $pdo;
    logDebug("STATS: Getting machinery statistics");
    
    try {
        $stmt = $pdo->query("
            SELECT 
                COUNT(*) as total_records,
                SUM(quantity) as total_quantity,
                COUNT(DISTINCT machinery_type) as total_types,
                SUM(CASE WHEN status = 'Operational' THEN quantity ELSE 0 END) as operational_count,
                SUM(CASE WHEN status = 'Under Repair' THEN quantity ELSE 0 END) as repair_count,
                SUM(CASE WHEN status = 'Out of Service' THEN quantity ELSE 0 END) as out_of_service_count
            FROM farm_machinery
        ");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $stats
        ]);
    } catch (PDOException $e) {
        logDebug("STATS ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching statistics: ' . $e->getMessage()
        ]);
    }
}

logDebug("========== MACHINERY API COMPLETED ==========\n");
?>