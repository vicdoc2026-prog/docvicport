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
    error_log($logMessage . "\n", 3, __DIR__ . '/animals_debug.log');
}

logDebug("========== ANIMALS API CALLED ==========");
logDebug("Request Method", $_SERVER['REQUEST_METHOD']);
logDebug("Request URI", $_SERVER['REQUEST_URI']);
logDebug("GET Parameters", $_GET);

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
        'error' => $e->getMessage()
    ]);
    exit;
}

// Check if PDO connection exists
if (!isset($pdo)) {
    logDebug("ERROR: PDO object not found after including conn.php");
    echo json_encode([
        'success' => false,
        'message' => 'Database connection object not found'
    ]);
    exit;
}

logDebug("PDO connection exists");

// Create uploads directory if it doesn't exist
$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get action parameter
$action = $_GET['action'] ?? '';
logDebug("Action requested", $action);

// Route to appropriate function
switch ($action) {
    case 'list':
        listAnimals();
        break;
    case 'get':
        getAnimal();
        break;
    case 'add':
    case 'create':
        createAnimal();
        break;
    case 'update':
        updateAnimal();
        break;
    case 'delete':
        deleteAnimal();
        break;
    case 'by_month':
        getAnimalsByMonth();
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
            'valid_actions' => ['list', 'get', 'add', 'update', 'delete', 'by_month', 'test']
        ]);
}

// HANDLE FILE UPLOAD
function handleFileUpload() {
    global $uploadDir;
    
    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    
    if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload error: ' . $_FILES['photo']['error']);
    }
    
    $file = $_FILES['photo'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, PNG, GIF, and WEBP are allowed.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File size exceeds 5MB limit.');
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('animal_') . '_' . time() . '.' . $extension;
    $targetPath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return $filename;
    } else {
        throw new Exception('Failed to save uploaded file.');
    }
}

// DELETE OLD PHOTO
function deleteOldPhoto($filename) {
    global $uploadDir;
    
    if ($filename && file_exists($uploadDir . $filename)) {
        unlink($uploadDir . $filename);
        logDebug("Deleted old photo", $filename);
    }
}

// TEST CONNECTION FUNCTION
function testConnection() {
    global $pdo;
    logDebug("TEST: Testing database connection");
    
    try {
        $stmt = $pdo->query("SELECT DATABASE() as db_name");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $pdo->query("SHOW TABLES LIKE 'farm_animals'");
        $tableExists = $stmt->fetch();
        
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM farm_animals");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection test successful',
            'database' => $result['db_name'],
            'table_exists' => $tableExists ? true : false,
            'total_records' => $count['total']
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

// LIST ALL ANIMALS
function listAnimals() {
    global $pdo;
    logDebug("LIST: Starting listAnimals function");
    
    try {
        $year = $_GET['year'] ?? null;
        
        if ($year) {
            $sql = "SELECT * FROM farm_animals WHERE year = ? ORDER BY year DESC, month DESC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$year]);
        } else {
            $sql = "SELECT * FROM farm_animals ORDER BY year DESC, month DESC, id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
        }
        
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logDebug("LIST: Records fetched", count($animals));
        
        echo json_encode([
            'success' => true,
            'data' => $animals,
            'count' => count($animals)
        ]);
        
    } catch (PDOException $e) {
        logDebug("LIST ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}

// GET SINGLE ANIMAL BY ID
function getAnimal() {
    global $pdo;
    logDebug("GET: Starting getAnimal function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("GET: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        $stmt = $pdo->prepare("SELECT * FROM farm_animals WHERE id = ?");
        $stmt->execute([$id]);
        $animal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($animal) {
            echo json_encode([
                'success' => true,
                'data' => $animal
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Animal record not found'
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

// CREATE NEW ANIMAL RECORD
function createAnimal() {
    global $pdo;
    logDebug("CREATE: Starting createAnimal function");
    
    try {
        // Handle photo upload
        $photoFilename = handleFileUpload();
        
        // Get form data
        $year = $_POST['year'] ?? null;
        $month = $_POST['month'] ?? null;
        $animalType = $_POST['animal_type'] ?? null;
        $quantity = $_POST['quantity'] ?? null;
        $notes = $_POST['notes'] ?? '';
        
        logDebug("CREATE: Data received", [
            'year' => $year,
            'month' => $month,
            'animal_type' => $animalType,
            'quantity' => $quantity,
            'photo' => $photoFilename
        ]);
        
        // Validate required fields
        if (!$year || !$month || !$animalType || !$quantity) {
            throw new Exception("Missing required fields");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO farm_animals (year, month, animal_type, quantity, notes, photo) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $result = $stmt->execute([
            $year,
            $month,
            $animalType,
            $quantity,
            $notes,
            $photoFilename
        ]);
        
        $newId = $pdo->lastInsertId();
        logDebug("CREATE: New record ID", $newId);
        
        echo json_encode([
            'success' => true,
            'message' => 'Animal record created successfully',
            'id' => $newId,
            'photo' => $photoFilename
        ]);
        
    } catch (Exception $e) {
        logDebug("CREATE ERROR", $e->getMessage());
        
        // Delete uploaded photo if database insert failed
        if (isset($photoFilename) && $photoFilename) {
            deleteOldPhoto($photoFilename);
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// UPDATE ANIMAL RECORD
function updateAnimal() {
    global $pdo;
    logDebug("UPDATE: Starting updateAnimal function");
    
    try {
        $id = $_POST['id'] ?? null;
        
        if (!$id) {
            throw new Exception("Invalid data or missing ID");
        }
        
        // Handle photo upload
        $photoFilename = handleFileUpload();
        
        // Get form data
        $year = $_POST['year'] ?? null;
        $month = $_POST['month'] ?? null;
        $animalType = $_POST['animal_type'] ?? null;
        $quantity = $_POST['quantity'] ?? null;
        $notes = $_POST['notes'] ?? '';
        $existingPhoto = $_POST['existing_photo'] ?? null;
        
        logDebug("UPDATE: Data received", [
            'id' => $id,
            'year' => $year,
            'month' => $month,
            'animal_type' => $animalType,
            'quantity' => $quantity,
            'new_photo' => $photoFilename,
            'existing_photo' => $existingPhoto
        ]);
        
        // Validate required fields
        if (!$year || !$month || !$animalType || !$quantity) {
            throw new Exception("Missing required fields");
        }
        
        // Determine final photo filename
        if ($photoFilename) {
            // New photo uploaded, delete old one if exists
            if ($existingPhoto) {
                deleteOldPhoto($existingPhoto);
            }
            $finalPhoto = $photoFilename;
        } else {
            // No new photo, keep existing
            $finalPhoto = $existingPhoto;
        }
        
        $stmt = $pdo->prepare("
            UPDATE farm_animals 
            SET year = ?, month = ?, animal_type = ?, quantity = ?, notes = ?, photo = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $year,
            $month,
            $animalType,
            $quantity,
            $notes,
            $finalPhoto,
            $id
        ]);
        
        logDebug("UPDATE: Rows affected", $stmt->rowCount());
        
        if ($stmt->rowCount() > 0 || $result) {
            echo json_encode([
                'success' => true,
                'message' => 'Animal record updated successfully',
                'photo' => $finalPhoto
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No changes made or record not found'
            ]);
        }
        
    } catch (Exception $e) {
        logDebug("UPDATE ERROR", $e->getMessage());
        
        // Delete newly uploaded photo if database update failed
        if (isset($photoFilename) && $photoFilename) {
            deleteOldPhoto($photoFilename);
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// DELETE ANIMAL RECORD
function deleteAnimal() {
    global $pdo;
    logDebug("DELETE: Starting deleteAnimal function");
    
    try {
        $id = $_GET['id'] ?? 0;
        logDebug("DELETE: ID parameter", $id);
        
        if ($id == 0) {
            throw new Exception("ID parameter is required");
        }
        
        // Get photo filename before deleting record
        $stmt = $pdo->prepare("SELECT photo FROM farm_animals WHERE id = ?");
        $stmt->execute([$id]);
        $animal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Delete the record
        $stmt = $pdo->prepare("DELETE FROM farm_animals WHERE id = ?");
        $result = $stmt->execute([$id]);
        
        logDebug("DELETE: Rows affected", $stmt->rowCount());
        
        if ($stmt->rowCount() > 0) {
            // Delete associated photo file if exists
            if ($animal && $animal['photo']) {
                deleteOldPhoto($animal['photo']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Animal record deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Record not found'
            ]);
        }
        
    } catch (Exception $e) {
        logDebug("DELETE ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

// GET ANIMALS BY MONTH
function getAnimalsByMonth() {
    global $pdo;
    logDebug("BY_MONTH: Starting getAnimalsByMonth function");
    
    try {
        $year = $_GET['year'] ?? date('Y');
        $month = $_GET['month'] ?? date('n');
        
        logDebug("BY_MONTH: Parameters", ['year' => $year, 'month' => $month]);
        
        if ($month < 1 || $month > 12) {
            throw new Exception("Invalid month: must be between 1-12");
        }
        
        $sql = "SELECT * FROM farm_animals WHERE year = ? AND month = ? ORDER BY animal_type ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$year, $month]);
        
        $animals = $stmt->fetchAll(PDO::FETCH_ASSOC);
        logDebug("BY_MONTH: Records fetched", count($animals));
        
        echo json_encode([
            'success' => true,
            'data' => $animals,
            'count' => count($animals),
            'query_params' => [
                'year' => (int)$year,
                'month' => (int)$month
            ]
        ]);
        
    } catch (Exception $e) {
        logDebug("BY_MONTH ERROR", $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

logDebug("========== ANIMALS API COMPLETED ==========\n");
?>