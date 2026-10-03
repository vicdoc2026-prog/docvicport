<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Unauthorized access']);
    exit();
}

require_once '../config/conn.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

try {
    $category_name = isset($_POST['infrastructure_type_name']) ? trim($_POST['infrastructure_type_name']) : '';
    
    if (empty($category_name)) {
        echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Category name is required']);
        exit();
    }
    
    $category_name = htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8');
    
    // Check if category already exists
    $check_query = "SELECT id FROM infrastructure_categories WHERE LOWER(category_name) = LOWER(?)";
    $check_stmt = $conn->prepare($check_query);
    
    if (!$check_stmt) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }
    
    $check_stmt->bind_param("s", $category_name);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        echo json_encode(['success' => false, 'status' => 'exists', 'message' => 'Category already exists']);
        $check_stmt->close();
        exit();
    }
    $check_stmt->close();
    
    // Insert new category
    $insert_query = "INSERT INTO infrastructure_categories (category_name) VALUES (?)";
    $insert_stmt = $conn->prepare($insert_query);
    
    if (!$insert_stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    
    $insert_stmt->bind_param("s", $category_name);
    
    if ($insert_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'status' => 'success',
            'message' => 'Category added successfully',
            'data' => [
                'id' => $conn->insert_id,
                'category_name' => $category_name
            ]
        ]);
    } else {
        throw new Exception("Failed to insert category: " . $insert_stmt->error);
    }
    
    $insert_stmt->close();
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'status' => 'error',
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>