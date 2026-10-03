<?php
/**
 * Infrastructure API
 * Handles all CRUD operations for infrastructure management
 * Actions: list, add, update, delete
 */

session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login.'
    ]);
    exit();
}

// Include database connection
require_once '../config/conn.php';

// Get the action from query parameter
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'list':
            listInfrastructure($conn);
            break;
            
        case 'add':
            addInfrastructure($conn);
            break;
            
        case 'update':
            updateInfrastructure($conn);
            break;
            
        case 'delete':
            deleteInfrastructure($conn);
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
    // Close database connection
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * List all infrastructure projects
 */
function listInfrastructure($conn) {
    try {
        $query = "SELECT 
                    id,
                    infrastructure_name,
                    infrastructure_type,
                    status,
                    progress_percent,
                    details,
                    created_at,
                    updated_at
                  FROM infrastructure 
                  ORDER BY created_at DESC";
        
        $result = $conn->query($query);
        
        if (!$result) {
            throw new Exception("Database query failed: " . $conn->error);
        }
        
        $infrastructure = [];
        
        while ($row = $result->fetch_assoc()) {
            // Ensure progress_percent is an integer
            $row['progress_percent'] = (int)$row['progress_percent'];
            $infrastructure[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Infrastructure data retrieved successfully',
            'data' => $infrastructure,
            'count' => count($infrastructure)
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error listing infrastructure: " . $e->getMessage());
    }
}

/**
 * Add new infrastructure project
 */
function addInfrastructure($conn) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['infrastructure_name', 'infrastructure_type', 'status', 'progress_percent'];
        foreach ($required_fields as $field) {
            if (!isset($input[$field]) || trim($input[$field]) === '') {
                echo json_encode([
                    'success' => false,
                    'message' => "Field '{$field}' is required"
                ]);
                return;
            }
        }
        
        // Sanitize and validate input
        $infrastructure_name = trim($input['infrastructure_name']);
        $infrastructure_type = trim($input['infrastructure_type']);
        $status = trim($input['status']);
        $progress_percent = (int)$input['progress_percent'];
        $details = isset($input['details']) ? trim($input['details']) : '';
        
        // Validate progress percentage
        if ($progress_percent < 0 || $progress_percent > 100) {
            echo json_encode([
                'success' => false,
                'message' => 'Progress percentage must be between 0 and 100'
            ]);
            return;
        }
        
        // Validate status - UPDATED WITH NEW STATUSES
        $valid_statuses = ['Planning', 'In Progress', 'Completed', 'On Hold', 'Operational', 'Need Repair'];
        if (!in_array($status, $valid_statuses)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid status value. Allowed values: ' . implode(', ', $valid_statuses)
            ]);
            return;
        }
        
        // Prepare insert statement
        $query = "INSERT INTO infrastructure 
                  (infrastructure_name, infrastructure_type, status, progress_percent, details, created_at, updated_at) 
                  VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("sssis", 
            $infrastructure_name, 
            $infrastructure_type, 
            $status, 
            $progress_percent, 
            $details
        );
        
        if ($stmt->execute()) {
            $new_id = $conn->insert_id;
            
            echo json_encode([
                'success' => true,
                'message' => 'Infrastructure project added successfully',
                'data' => [
                    'id' => $new_id,
                    'infrastructure_name' => $infrastructure_name,
                    'infrastructure_type' => $infrastructure_type,
                    'status' => $status,
                    'progress_percent' => $progress_percent,
                    'details' => $details
                ]
            ]);
        } else {
            throw new Exception("Failed to insert data: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error adding infrastructure: " . $e->getMessage());
    }
}

/**
 * Update existing infrastructure project
 */
function updateInfrastructure($conn) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        if (!isset($input['id']) || empty($input['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Infrastructure ID is required'
            ]);
            return;
        }
        
        $id = (int)$input['id'];
        
        // Check if infrastructure exists
        $check_query = "SELECT id FROM infrastructure WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Infrastructure project not found'
            ]);
            $check_stmt->close();
            return;
        }
        $check_stmt->close();
        
        // Validate and sanitize input
        $infrastructure_name = isset($input['infrastructure_name']) ? trim($input['infrastructure_name']) : null;
        $infrastructure_type = isset($input['infrastructure_type']) ? trim($input['infrastructure_type']) : null;
        $status = isset($input['status']) ? trim($input['status']) : null;
        $progress_percent = isset($input['progress_percent']) ? (int)$input['progress_percent'] : null;
        $details = isset($input['details']) ? trim($input['details']) : null;
        
        // Validate progress percentage if provided
        if ($progress_percent !== null && ($progress_percent < 0 || $progress_percent > 100)) {
            echo json_encode([
                'success' => false,
                'message' => 'Progress percentage must be between 0 and 100'
            ]);
            return;
        }
        
        // Validate status if provided - UPDATED WITH NEW STATUSES
        if ($status !== null) {
            $valid_statuses = ['Planning', 'In Progress', 'Completed', 'On Hold', 'Operational', 'Need Repair'];
            if (!in_array($status, $valid_statuses)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid status value. Allowed values: ' . implode(', ', $valid_statuses)
                ]);
                return;
            }
        }
        
        // Prepare update statement
        $query = "UPDATE infrastructure 
                  SET infrastructure_name = ?, 
                      infrastructure_type = ?, 
                      status = ?, 
                      progress_percent = ?, 
                      details = ?,
                      updated_at = NOW()
                  WHERE id = ?";
        
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("sssisi", 
            $infrastructure_name, 
            $infrastructure_type, 
            $status, 
            $progress_percent, 
            $details,
            $id
        );
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Infrastructure project updated successfully',
                'data' => [
                    'id' => $id,
                    'infrastructure_name' => $infrastructure_name,
                    'infrastructure_type' => $infrastructure_type,
                    'status' => $status,
                    'progress_percent' => $progress_percent,
                    'details' => $details
                ]
            ]);
        } else {
            throw new Exception("Failed to update data: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error updating infrastructure: " . $e->getMessage());
    }
}

/**
 * Delete infrastructure project
 */
function deleteInfrastructure($conn) {
    try {
        // Get ID from query parameter
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Infrastructure ID is required'
            ]);
            return;
        }
        
        $id = (int)$_GET['id'];
        
        // Check if infrastructure exists
        $check_query = "SELECT infrastructure_name FROM infrastructure WHERE id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Infrastructure project not found'
            ]);
            $check_stmt->close();
            return;
        }
        
        $infrastructure_data = $check_result->fetch_assoc();
        $check_stmt->close();
        
        // Prepare delete statement
        $query = "DELETE FROM infrastructure WHERE id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'Infrastructure project deleted successfully',
                'data' => [
                    'id' => $id,
                    'infrastructure_name' => $infrastructure_data['infrastructure_name']
                ]
            ]);
        } else {
            throw new Exception("Failed to delete data: " . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        throw new Exception("Error deleting infrastructure: " . $e->getMessage());
    }
}
?>