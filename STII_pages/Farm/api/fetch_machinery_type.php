<?php
header('Content-Type: application/json');

try {
    require_once '../config/conn.php';
    
    $stmt = $pdo->query("SELECT machinery_type_name FROM machinery_types ORDER BY machinery_type_name ASC");
    $machineryTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($machineryTypes);
    
} catch (PDOException $e) {
    error_log("Error fetching machinery types: " . $e->getMessage());
    echo json_encode([]);
}
?>