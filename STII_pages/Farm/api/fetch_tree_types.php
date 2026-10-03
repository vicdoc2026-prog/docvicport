<?php
header('Content-Type: application/json');

try {
    require_once '../config/conn.php';
    
    // Fetch all tree types from the database
    $stmt = $pdo->query("SELECT tree_name FROM tree_types ORDER BY tree_name ASC");
    $treeTypes = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode($treeTypes);
    
} catch (PDOException $e) {
    error_log("Error fetching tree types: " . $e->getMessage());
    echo json_encode([]);
}
?>