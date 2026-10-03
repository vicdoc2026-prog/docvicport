<?php
session_start();
require_once 'db_config.php';

// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}

header('Content-Type: application/json');

try {
    // Validate required fields
    if (empty($_POST['lot_name']) || empty($_POST['location'])) {
        throw new Exception('Lot name and location are required.');
    }

    // Handle file upload
    $document_path = null;
    
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['document_file'];
        
        // Validate file type
        $allowed_types = ['application/pdf'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Only PDF files are allowed.');
        }
        
        // Validate file size (20MB max)
        $max_size = 20 * 1024 * 1024; // 20MB in bytes
        if ($file['size'] > $max_size) {
            throw new Exception('File size must be less than 20MB.');
        }
        
        // Generate unique filename
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9]/', '_', $_POST['lot_name']) . '.' . $file_extension;
        $upload_path = 'uploads/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $document_path = $upload_path;
        } else {
            throw new Exception('Failed to upload file.');
        }
    } elseif (isset($_FILES['document_file']) && $_FILES['document_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        $error_message = match($_FILES['document_file']['error']) {
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => 'Unknown upload error occurred.'
        };
        throw new Exception($error_message);
    } elseif (!empty($_POST['document_url'])) {
        // Use URL if provided
        $document_path = filter_var($_POST['document_url'], FILTER_VALIDATE_URL);
        if (!$document_path) {
            throw new Exception('Invalid document URL.');
        }
    }

    // Get form data
    $lot_id = $_POST['lot_id'] ?? null;
    $lot_name = $_POST['lot_name'];
    $location = $_POST['location'];
    $coordinates = $_POST['coordinates'];
    $area = $_POST['area'];
    $perimeter = $_POST['perimeter'];
    $owner_name = $_POST['owner_name'] ?? null;
    $reference_number = $_POST['reference_number'] ?? null;
    $description = $_POST['description'] ?? null;

    if ($lot_id) {
        // Update existing lot
        // Get old document path
        $stmt = $pdo->prepare("SELECT document_path FROM land_lots WHERE id = ?");
        $stmt->execute([$lot_id]);
        $old_document = $stmt->fetchColumn();

        // If no new document provided, keep old
        if ($document_path === null) {
            $document_path = $old_document;
        }

        $sql = "UPDATE land_lots SET 
                lot_name = ?, 
                location = ?, 
                coordinates = ?, 
                area = ?, 
                perimeter = ?, 
                owner_name = ?, 
                reference_number = ?, 
                document_path = ?, 
                description = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $lot_name,
            $location,
            $coordinates,
            $area,
            $perimeter,
            $owner_name,
            $reference_number,
            $document_path,
            $description,
            $lot_id
        ]);
        
        // Delete old file if new file was uploaded, old exists, and it's a local file
        if ($success && $document_path !== $old_document && $old_document && file_exists($old_document) && strpos($old_document, 'uploads/') === 0) {
            unlink($old_document);
        }
    } else {
        // Insert new lot
        $sql = "INSERT INTO land_lots 
                (lot_name, location, coordinates, area, perimeter, owner_name, reference_number, document_path, description) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            $lot_name,
            $location,
            $coordinates,
            $area,
            $perimeter,
            $owner_name,
            $reference_number,
            $document_path,
            $description
        ]);
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Lot saved successfully!'
        ]);
    } else {
        throw new Exception('Failed to save lot to database.');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>