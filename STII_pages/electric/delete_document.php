<?php
require_once 'config.php'; // This should define $conn = new mysqli(...)

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['document_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing document ID']);
    exit;
}

$documentId = intval($data['document_id']);

if ($documentId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid document ID']);
    exit;
}

try {
    // Get document info
    $stmt = $conn->prepare("SELECT * FROM bill_documents WHERE id = ?");
    $stmt->bind_param("i", $documentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Document not found']);
        exit;
    }
    
    $document = $result->fetch_assoc();
    $stmt->close();
    
    // Delete file from filesystem
    if (file_exists($document['file_path'])) {
        if (!unlink($document['file_path'])) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete file from server']);
            exit;
        }
    }
    
    // Delete from database
    $deleteStmt = $conn->prepare("DELETE FROM bill_documents WHERE id = ?");
    $deleteStmt->bind_param("i", $documentId);
    
    if ($deleteStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Document deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete from database: ' . $deleteStmt->error]);
    }
    
    $deleteStmt->close();
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>