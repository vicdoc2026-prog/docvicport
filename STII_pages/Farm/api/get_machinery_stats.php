<?php
// api/get_machinery_stats.php - Get machinery statistics
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once '../config/conn.php';
    
    if (!isset($pdo)) {
        throw new Exception("Database connection not available");
    }
    
    $sql = "SELECT 
                COUNT(*) as total_records,
                SUM(quantity) as total_machinery,
                COUNT(DISTINCT machinery_type) as machinery_types,
                SUM(CASE WHEN status = 'Operational' THEN quantity ELSE 0 END) as operational,
                SUM(CASE WHEN status = 'Under Repair' THEN quantity ELSE 0 END) as under_repair,
                SUM(CASE WHEN status = 'Out of Service' THEN quantity ELSE 0 END) as out_of_service
            FROM farm_machinery";
    
    $stmt = $pdo->query($sql);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get machinery by type
    $typeStmt = $pdo->query("
        SELECT machinery_type, SUM(quantity) as count 
        FROM farm_machinery 
        GROUP BY machinery_type 
        ORDER BY count DESC
    ");
    $byType = $typeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'overview' => $stats,
            'by_type' => $byType
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>