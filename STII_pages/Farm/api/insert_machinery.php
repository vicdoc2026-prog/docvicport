<?php
// api/insert_machinery.php - Insert new machinery record
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/conn.php';
    
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        // Try form data if JSON fails
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['machinery_name'])) {
        throw new Exception("Machinery name is required");
    }
    if (empty($data['machinery_type'])) {
        throw new Exception("Machinery type is required");
    }
    if (empty($data['quantity']) || $data['quantity'] < 1) {
        throw new Exception("Valid quantity is required");
    }
    if (empty($data['status'])) {
        throw new Exception("Status is required");
    }
    
    // Prepare SQL statement
    $sql = "INSERT INTO farm_machinery 
            (machinery_name, machinery_type, quantity, status, notes) 
            VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        trim($data['machinery_name']),
        trim($data['machinery_type']),
        intval($data['quantity']),
        trim($data['status']),
        !empty($data['notes']) ? trim($data['notes']) : null
    ]);
    
    if ($result) {
        $newId = $pdo->lastInsertId();
        
        // Fetch the newly created record
        $fetchStmt = $pdo->prepare("SELECT * FROM farm_machinery WHERE id = ?");
        $fetchStmt->execute([$newId]);
        $newRecord = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Machinery added successfully',
            'id' => $newId,
            'data' => $newRecord
        ]);
    } else {
        throw new Exception("Failed to insert machinery record");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>