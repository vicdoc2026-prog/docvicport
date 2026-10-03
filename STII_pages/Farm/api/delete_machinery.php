<?php
// api/delete_machinery.php - Delete machinery record
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/conn.php';
    
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    // Get ID from query string or POST data
    $id = $_GET['id'] ?? $_POST['id'] ?? null;
    
    if (empty($id)) {
        throw new Exception("Machinery ID is required");
    }
    
    // Check if record exists
    $checkStmt = $pdo->prepare("SELECT * FROM farm_machinery WHERE id = ?");
    $checkStmt->execute([intval($id)]);
    $machinery = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$machinery) {
        throw new Exception("Machinery record not found");
    }
    
    // Delete the record
    $sql = "DELETE FROM farm_machinery WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([intval($id)]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Machinery deleted successfully',
            'deleted_record' => $machinery,
            'rows_affected' => $stmt->rowCount()
        ]);
    } else {
        throw new Exception("Failed to delete machinery record");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>