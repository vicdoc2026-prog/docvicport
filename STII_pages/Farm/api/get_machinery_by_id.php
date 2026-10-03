<?php
// api/get_machinery_by_id.php - Get single machinery by ID
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/conn.php';
    
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    $id = $_GET['id'] ?? null;
    
    if (empty($id)) {
        throw new Exception("Machinery ID is required");
    }
    
    $sql = "SELECT * FROM farm_machinery WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([intval($id)]);
    
    $machinery = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>