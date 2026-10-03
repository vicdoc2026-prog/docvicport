<?php
require_once 'config.php'; // This should define $conn = new mysqli(...)

header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get bill ID
$billId = isset($_POST['bill_id']) ? intval($_POST['bill_id']) : 0;

if ($billId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bill ID']);
    exit;
}

// Verify bill exists
try {
    $stmt = $conn->prepare("SELECT id FROM bills WHERE id = ?");
    $stmt->bind_param("i", $billId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Bill not found']);
        exit;
    }
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Check if files were uploaded
if (empty($_FILES['documents']['name'][0])) {
    echo json_encode(['success' => false, 'message' => 'No files uploaded']);
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = 'uploads/bills/' . $billId . '/';
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

$uploadedFiles = [];
$errors = [];

// Process each file
$fileCount = count($_FILES['documents']['name']);
for ($i = 0; $i < $fileCount; $i++) {
    $fileName = $_FILES['documents']['name'][$i];
    $fileTmpName = $_FILES['documents']['tmp_name'][$i];
    $fileSize = $_FILES['documents']['size'][$i];
    $fileError = $_FILES['documents']['error'][$i];
    
    // Skip if no file
    if ($fileError === UPLOAD_ERR_NO_FILE) {
        continue;
    }
    
    // Check for upload errors
    if ($fileError !== UPLOAD_ERR_OK) {
        $errors[] = "Error uploading $fileName";
        continue;
    }
    
    // Validate file size (10MB max)
    if ($fileSize > 10 * 1024 * 1024) {
        $errors[] = "$fileName exceeds 10MB limit";
        continue;
    }
    
    // Validate file type
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    
    if (!in_array($fileExt, $allowedExts)) {
        $errors[] = "$fileName has invalid file type";
        continue;
    }
    
    // Generate unique filename
    $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $uniqueFileName;
    
    // Move uploaded file
    if (move_uploaded_file($fileTmpName, $targetPath)) {
        // Insert into database
        try {
            $stmt = $conn->prepare("
                INSERT INTO bill_documents (bill_id, file_name, file_path, file_size, uploaded_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issi", $billId, $fileName, $targetPath, $fileSize);
            
            if ($stmt->execute()) {
                $uploadedFiles[] = [
                    'id' => $conn->insert_id,
                    'name' => $fileName,
                    'path' => $targetPath,
                    'size' => $fileSize
                ];
            } else {
                $errors[] = "Database error for $fileName: " . $stmt->error;
                // Remove the uploaded file if database insert fails
                unlink($targetPath);
            }
            $stmt->close();
        } catch (Exception $e) {
            $errors[] = "Database error for $fileName: " . $e->getMessage();
            // Remove the uploaded file if database insert fails
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
        }
    } else {
        $errors[] = "Failed to move $fileName to upload directory";
    }
}

// Prepare response
if (!empty($uploadedFiles)) {
    $message = count($uploadedFiles) . ' file(s) uploaded successfully';
    if (!empty($errors)) {
        $message .= '. ' . count($errors) . ' file(s) failed.';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'uploaded' => $uploadedFiles,
        'errors' => $errors
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No files were uploaded successfully',
        'errors' => $errors
    ]);
}

$conn->close();
?>