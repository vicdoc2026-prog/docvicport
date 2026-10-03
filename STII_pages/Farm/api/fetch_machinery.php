<?php
// api/fetch_machinery.php - Fetch all machinery records
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/conn.php';
    
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    $sql = "SELECT 
                id,
                machinery_name,
                machinery_type,
                quantity,
                status,
                last_maintenance_date,
                notes,
                created_at,
                updated_at
            FROM farm_machinery 
            ORDER BY created_at DESC";
    
    $stmt = $pdo->query($sql);
    $machinery = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $machinery,
        'count' => count($machinery),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching machinery: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>