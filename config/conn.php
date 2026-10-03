<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'doc_vic');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getAllOrdinances($conn) {
    $sql = "SELECT id, ordinance_number, title, proponent, date_approved, 
            description, pdf_file, status, created_at, updated_at 
            FROM ordinances 
            ORDER BY date_approved DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $ordinances = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $ordinances[] = $row;
        }
        return $ordinances;
    }
    
    return false;
}


// Function to fetch a single ordinance by ID
function getOrdinanceById($conn, $id) {
    $sql = "SELECT id, ordinance_number, title, proponent, date_approved, 
            description, pdf_file, status, created_at, updated_at 
            FROM ordinances 
            WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $ordinance = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    
    return $ordinance;
}

// Function to fetch ordinances by status
function getOrdinancesByStatus($conn, $status) {
    $sql = "SELECT id, ordinance_number, title, proponent, date_approved, 
            description, pdf_file, status, created_at, updated_at 
            FROM ordinances 
            WHERE status = ? 
            ORDER BY date_approved DESC";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $status);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    $ordinances = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ordinances[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    return $ordinances;
}

// Function to search ordinances by title or ordinance number
function searchOrdinances($conn, $searchTerm) {
    $sql = "SELECT id, ordinance_number, title, proponent, date_approved, 
            description, pdf_file, status, created_at, updated_at 
            FROM ordinances 
            WHERE title LIKE ? OR ordinance_number LIKE ? 
            ORDER BY date_approved DESC";
    
    $searchParam = "%$searchTerm%";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $searchParam, $searchParam);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    
    $ordinances = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $ordinances[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    
    return $ordinances;
}

// Function to get total count of ordinances
function getOrdinanceCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM ordinances";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Function to get total count of resolutions
function getResolutionCount($conn) {
    $sql = "SELECT COUNT(*) as total FROM resolutions";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Function to count rows
function countRegularEvents($conn) {
    $sql = "SELECT COUNT(*) AS total FROM regular_events";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        return $row['total'];
    } else {
        return 0; // return 0 if query fails
    }
}

// Function to fetch all regular events
function fetchAllRegularEvents($conn) {
    $sql = "SELECT * FROM regular_events ORDER BY month, day";
    $result = $conn->query($sql);

    $events = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $events[] = $row;
        }
    }

    return $events;
}
?>