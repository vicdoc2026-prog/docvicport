<?php
header('Content-Type: application/json');

try {
    require_once '../config/conn.php';
    
    if (!isset($_POST['tree_name']) || empty(trim($_POST['tree_name']))) {
        echo json_encode(['status' => 'error', 'message' => 'Tree name is required']);
        exit;
    }
    
    $treeName = trim($_POST['tree_name']);
    
    // Check if tree type already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tree_types WHERE LOWER(tree_name) = LOWER(?)");
    $stmt->execute([$treeName]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo json_encode(['status' => 'exists', 'message' => 'Tree type already exists']);
        exit;
    }
    
    // Insert new tree type
    $stmt = $pdo->prepare("INSERT INTO tree_types (tree_name) VALUES (?)");
    $stmt->execute([$treeName]);
    
    echo json_encode(['status' => 'success', 'message' => 'Tree type added successfully', 'tree_name' => $treeName]);
    
} catch (PDOException $e) {
    error_log("Error inserting tree type: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error occurred']);
}
?>