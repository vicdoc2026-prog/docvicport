<?php
// api/update_machinery.php - Update existing machinery record
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
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['id'])) {
        throw new Exception("Machinery ID is required");
    }
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
    
    // Check if record exists
    $checkStmt = $pdo->prepare("SELECT id FROM farm_machinery WHERE id = ?");
    $checkStmt->execute([intval($data['id'])]);
    
    if (!$checkStmt->fetch()) {
        throw new Exception("Machinery record not found");
    }
    
    // Prepare update SQL statement
    $sql = "UPDATE farm_machinery 
            SET machinery_name = ?,
                machinery_type = ?,
                quantity = ?,
                status = ?,
                last_maintenance_date = ?,
                notes = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?";
    
    $stmt = $pdo->prepare($sql);
    
    $result = $stmt->execute([
        trim($data['machinery_name']),
        trim($data['machinery_type']),
        intval($data['quantity']),
        trim($data['status']),
        !empty($data['last_maintenance_date']) ? $data['last_maintenance_date'] : null,
        !empty($data['notes']) ? trim($data['notes']) : null,
        intval($data['id'])
    ]);
    
    if ($result) {
        // Fetch the updated record
        $fetchStmt = $pdo->prepare("SELECT * FROM farm_machinery WHERE id = ?");
        $fetchStmt->execute([intval($data['id'])]);
        $updatedRecord = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => 'Machinery updated successfully',
            'rows_affected' => $stmt->rowCount(),
            'data' => $updatedRecord
        ]);
    } else {
        throw new Exception("Failed to update machinery record");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>