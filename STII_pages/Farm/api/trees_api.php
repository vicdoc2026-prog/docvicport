<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/api_errors.log');

// Set JSON header
header('Content-Type: application/json');

// Log function for debugging
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " | Data: " . json_encode($data);
    }
    error_log($logMessage . "\n", 3, __DIR__ . '/trees_debug.log');
}

logDebug("========== TREES API CALLED ==========");
logDebug("Request Method", $_SERVER['REQUEST_METHOD']);
logDebug("Request URI", $_SERVER['REQUEST_URI']);
logDebug("GET Parameters", $_GET);
logDebug("POST Parameters", $_POST);

// Include database connection
try {
    logDebug("Attempting to include database connection file");
    require_once '../config/conn.php';
    logDebug("Database connection file included successfully");
} catch (Exception $e) {
    logDebug("ERROR: Failed to include conn.php", $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Database connection file not found',
        'error' => $e->getMessage(),
        'debug' => [
            'file_attempted' => '../config/conn.php',
            'current_dir' => __DIR__
        ]
    ]);
    exit;
}

// Check if PDO connection exists
if (!isset($pdo)) {
    logDebug("ERROR: PDO object not found after including conn.php");
    echo json_encode([
        'success' => false,
        'message' => 'Database connection object not found',
        'debug' => [
            'pdo_exists' => isset($pdo),
            'conn_file_path' => '../config/conn.php'
        ]
    ]);
    exit;
}

logDebug("PDO connection exists", get_class($pdo));

// Get action parameter
$action = $_GET['action'] ?? '';
logDebug("Action requested", $action);

// Route to appropriate function
switch ($action) {
    case 'list':
        listTrees();
        break;
    case 'get':
        getTree();
        break;
    case 'add':
    case 'create':
        createTree();
        break;
    case 'update':
        updateTree();
        break;
    case 'delete':
        deleteTree();
        break;
    case 'by_month':
        getTreesByMonth();
        break;
    case 'test':
        testConnection();
        break;
    default:
        logDebug("ERROR: Invalid or missing action", $action);
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid action',
            'provided_action' => $action,
            'valid_actions' => ['list', 'get', 'add', 'create', 'update', 'delete', 'by_month', 'test']
        ]);
}

// TEST CONNECTION FUNCTION
function testConnection() {
    global $pdo;
    logDebug("TEST: Testing database connection");
    
    try {
        // Test basic query
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        logDebug("TEST: Current database", $result);
        
        // Check if farm_trees table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'farm_trees'");
        $tableExists = $stmt->fetch();
        logDebug("TEST: farm_trees table exists", $tableExists ? 'YES' : 'NO');
        
        // Count total records
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM farm_trees");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        logDebug("TEST: Total records in farm_trees", $count);
        
        // Get sample record
        $stmt = $pdo->query("SELECT * FROM farm_trees LIMIT 1");
        $sample = $stmt->fetch(PDO::FETCH_ASSOC);
        logDebug("TEST: Sample record", $sample);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection test successful',
            'database' => $result['db_name'],
            'table_exists' => $tableExists ? true : false,
            'total_records' => $count['total'],
            'sample_record' => $sample,
            'pdo_driver' => $pdo->getAttribute(PDO::ATTR_DRIVER_NAME)
        ]);
        
    } catch (PDOException $e) {
        logDebug("TEST ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Connection test failed',
            'error' => $e->getMessage()
        ]);
    }
}

// LIST ALL TREES
function listTrees() {
    global $pdo;
    logDebug("LIST: Starting listTrees function");
    
    try {
        // Get all trees without year filter to show all data
        $sql = "SELECT * FROM farm_trees ORDER BY year DESC, month DESC, tree_type ASC";
        logDebug("LIST: SQL Query", $sql);
        
        $stmt = $pdo->prepare($sql);
        logDebug("LIST: Statement prepared");
        
        $stmt->execute();
        logDebug("LIST: Query executed");
        
        $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logDebug("LIST: Records fetched", count($trees));
        
        if (count($trees) > 0) {
            logDebug("LIST: Sample record", $trees[0]);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $trees,
            'count' => count($trees),
            'debug' => [
                'query_executed' => true,
                'records_found' => count($trees)
            ]
        ]);
        
    } catch (PDOException $e) {
        logDebug("LIST ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage(),
            'error_code' => $e->getCode(),
            'debug' => [
                'function' => 'listTrees'
            ]
        ]);
    }
}

// GET SINGLE TREE BY ID
function getTree() {
    global $pdo;
    logDebug("GET: Starting getTree function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("GET: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        $stmt = $pdo->prepare("SELECT * FROM farm_trees WHERE id = ?");
        $stmt->execute([$id]);
        $tree = $stmt->fetch(PDO::FETCH_ASSOC);
        
        logDebug("GET: Record found", $tree ? 'YES' : 'NO');
        
        if ($tree) {
            echo json_encode([
                'success' => true,
                'data' => $tree
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Tree record not found',
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

// CREATE NEW TREE RECORD
function createTree() {
    global $pdo;
    logDebug("CREATE: Starting createTree function");
    
    try {
        $input = file_get_contents('php://input');
        logDebug("CREATE: Raw input", $input);
        
        $data = json_decode($input, true);
        logDebug("CREATE: Decoded data", $data);
        
        if (!$data) {
            throw new Exception("Invalid JSON data");
        }
        
        // Validate required fields
        $required = ['year', 'month', 'tree_type', 'quantity'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate data types
        if (!is_numeric($data['year']) || $data['year'] < 2000 || $data['year'] > 2100) {
            throw new Exception("Invalid year value");
        }
        
        if (!is_numeric($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            throw new Exception("Invalid month value");
        }
        
        if (!is_numeric($data['quantity']) || $data['quantity'] < 1) {
            throw new Exception("Quantity must be a positive number");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO farm_trees (year, month, tree_type, quantity, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $data['year'],
            $data['month'],
            $data['tree_type'],
            $data['quantity'],
            $data['notes'] ?? null
        ]);
        
        $newId = $pdo->lastInsertId();
        logDebug("CREATE: New record ID", $newId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Tree record created successfully',
            'id' => $newId,
            'data' => $data
        ]);
        
    } catch (Exception $e) {
        logDebug("CREATE ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// UPDATE TREE RECORD
function updateTree() {
    global $pdo;
    logDebug("UPDATE: Starting updateTree function");
    
    try {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        logDebug("UPDATE: Data received", $data);
        
        if (!$data || !isset($data['id'])) {
            throw new Exception("Invalid data or missing ID");
        }
        
        // Validate required fields
        $required = ['year', 'month', 'tree_type', 'quantity'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field: $field");
            }
        }
        
        // Validate data types
        if (!is_numeric($data['year']) || $data['year'] < 2000 || $data['year'] > 2100) {
            throw new Exception("Invalid year value");
        }
        
        if (!is_numeric($data['month']) || $data['month'] < 1 || $data['month'] > 12) {
            throw new Exception("Invalid month value");
        }
        
        if (!is_numeric($data['quantity']) || $data['quantity'] < 1) {
            throw new Exception("Quantity must be a positive number");
        }
        
        $stmt = $pdo->prepare("
            UPDATE farm_trees 
            SET year = ?, month = ?, tree_type = ?, quantity = ?, notes = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $data['year'],
            $data['month'],
            $data['tree_type'],
            $data['quantity'],
            $data['notes'] ?? null,
            $data['id']
        ]);
        
        logDebug("UPDATE: Rows affected", $stmt->rowCount());
        
        echo json_encode([
            'success' => true,
            'message' => 'Tree record updated successfully',
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

// DELETE TREE RECORD
function deleteTree() {
    global $pdo;
    logDebug("DELETE: Starting deleteTree function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("DELETE: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        $stmt = $pdo->prepare("DELETE FROM farm_trees WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        logDebug("DELETE: Rows affected", $stmt->rowCount());
        
        echo json_encode([
            'success' => true,
            'message' => 'Tree record deleted successfully',
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

// GET TREES BY MONTH
function getTreesByMonth() {
    global $pdo;
    logDebug("BY_MONTH: Starting getTreesByMonth function");
    
    try {
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        
        logDebug("BY_MONTH: Parameters", ['year' => $year, 'month' => $month]);
        
        // Validate month
        if ($month < 1 || $month > 12) {
            throw new Exception("Invalid month: must be between 1-12");
        }
        
        $sql = "SELECT * FROM farm_trees WHERE year = ? AND month = ? ORDER BY tree_type ASC";
        logDebug("BY_MONTH: SQL Query", $sql);
        logDebug("BY_MONTH: Parameters to bind", [$year, $month]);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$year, $month]);
        
        $trees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logDebug("BY_MONTH: Records fetched", count($trees));
        
        if (count($trees) > 0) {
            logDebug("BY_MONTH: First record", $trees[0]);
            logDebug("BY_MONTH: All tree types", array_column($trees, 'tree_type'));
        } else {
            logDebug("BY_MONTH: No records found");
            
            // Check if ANY records exist for this year
            $checkStmt = $pdo->prepare("SELECT COUNT(*) as total, MIN(month) as min_month, MAX(month) as max_month FROM farm_trees WHERE year = ?");
            $checkStmt->execute([$year]);
            $yearCheck = $checkStmt->fetch(PDO::FETCH_ASSOC);
            logDebug("BY_MONTH: Year check", $yearCheck);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $trees,
            'count' => count($trees),
            'query_params' => [
                'year' => (int)$year,
                'month' => (int)$month
            ],
            'debug' => [
                'sql_executed' => $sql,
                'records_returned' => count($trees),
                'database_queried' => true
            ]
        ]);
        
    } catch (Exception $e) {
        logDebug("BY_MONTH ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'error_code' => $e->getCode(),
            'query_params' => [
                'year' => $_GET['year'] ?? 'not set',
                'month' => $_GET['month'] ?? 'not set'
            ],
            'debug' => [
                'function' => 'getTreesByMonth',
                'error_type' => get_class($e)
            ]
        ]);
    }
}

logDebug("========== TREES API COMPLETED ==========\n");
?>