<?php
//get_lot.php
require_once 'db_config.php';

header('Content-Type: application/json');

try {
    $id = $_GET['id'] ?? 0;
    
    if (empty($id)) {
        echo json_encode(['success' => false, 'message' => 'Lot ID is required']);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM land_lots WHERE id = ?");
    $stmt->execute([$id]);
    $lot = $stmt->fetch();
    
    if ($lot) {
        echo json_encode(['success' => true, 'lot' => $lot]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lot not found']);
    }
    
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>