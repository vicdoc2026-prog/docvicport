<?php
header('Content-Type: application/json');

try {
    require_once '../config/conn.php';
    
    if (!isset($_POST['machinery_type_name']) || empty(trim($_POST['machinery_type_name']))) {
        echo json_encode(['status' => 'error', 'message' => 'Machinery type name is required']);
        exit;
    }
    
    $machineryTypeName = trim($_POST['machinery_type_name']);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM machinery_types WHERE LOWER(machinery_type_name) = LOWER(?)");
    $stmt->execute([$machineryTypeName]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['status' => 'exists', 'message' => 'Machinery type already exists']);
        exit;
    }
    
    $stmt = $pdo->prepare("INSERT INTO machinery_types (machinery_type_name) VALUES (?)");
    $stmt->execute([$machineryTypeName]);
    
    echo json_encode(['status' => 'success', 'message' => 'Machinery type added successfully', 'machinery_type_name' => $machineryTypeName]);
    
} catch (PDOException $e) {
    error_log("Error inserting machinery type: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
?>