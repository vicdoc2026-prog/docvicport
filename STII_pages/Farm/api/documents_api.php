<?php
/**
 * Documents API
 * Handles all CRUD operations for document management
 * Actions: list, upload, update, delete
 */

require_once __DIR__ . '/../../electric/config/check-session.php';
header('Content-Type: application/json');

// Include database connection
require_once '../../config/conn.php';

$user_id = $_SESSION['user_id'];

// Get the action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            listDocuments($conn, $user_id);
            break;
            
        case 'upload':
            uploadDocument($conn, $user_id);
            break;
            
        case 'update':
            updateDocument($conn, $user_id);
            break;
            
        case 'delete':
            deleteDocument($conn, $user_id);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * List all documents for the user
 */
function listDocuments($conn, $user_id) {
    try {
        $query = "SELECT 
                    id,
                    user_id,
                    title,
                    description,
                    file_name,
                    file_path,
                    file_size,
                    uploaded_at
                  FROM documents 
                  WHERE user_id = ?
                  ORDER BY uploaded_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $documents = [];
        while ($row = $result->fetch_assoc()) {
            $documents[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Documents retrieved successfully',
            'data' => $documents,
            'count' => count($documents)
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error listing documents: " . $e->getMessage());
    }
}

/**
 * Upload new document
 */
function uploadDocument($conn, $user_id) {
    try {
        // Validate inputs
        if (!isset($_POST['title']) || empty(trim($_POST['title']))) {
            echo json_encode([
                'success' => false,
                'message' => 'Document title is required'
            ]);
            return;
        }
        
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => 'File upload failed'
            ]);
            return;
        }
        
        $title = trim($_POST['title']);
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $file = $_FILES['file'];
        
        // Validate file size (10MB max)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $maxSize) {
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds 10MB limit'
            ]);
            return;
        }
        
        // Validate file type
        $allowedTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($fileExt, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Allowed: PDF, Word, Excel, Images'
            ]);
            return;
        }
        
        // Create upload directory if not exists
        $uploadDir = '../uploads/documents/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate unique filename
        $uniqueId = uniqid('doc_', true);
        $newFileName = $uniqueId . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;
        $relativePath = 'uploads/documents/' . $newFileName;
        
        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to save file'
            ]);
            return;
        }
        
        // Insert into database
        $query = "INSERT INTO documents 
                  (user_id, title, description, file_name, file_path, file_size, uploaded_at) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssi", 
            $user_id,
            $title,
            $description,
            $file['name'],
            $relativePath,
            $file['size']
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'data' => [
                    'id' => $new_id,
                    'title' => $title,
                    'file_name' => $file['name'],
                    'file_path' => $relativePath
                ]
            ]);
        } else {
            // Delete uploaded file if database insert fails
            unlink($uploadPath);
            throw new Exception("Failed to insert document record");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error uploading document: " . $e->getMessage());
    }
}

/**
 * Update document details
 */
function updateDocument($conn, $user_id) {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['id']) || empty($input['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Document ID is required'
            ]);
            return;
        }
        
        $id = (int)$input['id'];
        $title = isset($input['title']) ? trim($input['title']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        
        // Check if document exists and belongs to user
        $check_query = "SELECT id FROM documents WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found or access denied'
            ]);
            $check_stmt->close();
            return;
        }
        $check_stmt->close();
        
        // Update document
        $query = "UPDATE documents 
                  SET title = ?, description = ?
                  WHERE id = ? AND user_id = ?";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $title, $description, $id, $user_id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Document updated successfully',
                'data' => [
                    'id' => $id,
                    'title' => $title,
                    'description' => $description
                ]
            ]);
        } else {
            throw new Exception("Failed to update document");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error updating document: " . $e->getMessage());
    }
}

/**
 * Delete document
 */
function deleteDocument($conn, $user_id) {
    try {
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Document ID is required'
            ]);
            return;
        }
        
        $id = (int)$_GET['id'];
        
        // Get document info
        $check_query = "SELECT file_path, title FROM documents WHERE id = ? AND user_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("ii", $id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Document not found or access denied'
            ]);
            $check_stmt->close();
            return;
        }
        
        $document_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Delete document from database
        $query = "DELETE FROM documents WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $id, $user_id);
        
        if ($stmt->execute()) {
            // Delete physical file
            $filePath = '../../' . $document_data['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Document deleted successfully',
                'data' => [
                    'id' => $id,
                    'title' => $document_data['title']
                ]
            ]);
        } else {
            throw new Exception("Failed to delete document");
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error deleting document: " . $e->getMessage());
    }
}
?>