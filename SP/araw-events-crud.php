<?php
// araw-events-crud.php
require_once 'config/conn.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'create':
        createEvent($conn);
        break;
    case 'read':
        readEvents($conn);
        break;
    case 'update':
        updateEvent($conn);
        break;
    case 'delete':
        deleteEvent($conn);
        break;
    case 'get_single':
        getSingleEvent($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function createEvent($conn) {
    $municipality = mysqli_real_escape_string($conn, $_POST['municipality']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $fiesta = mysqli_real_escape_string($conn, $_POST['fiesta']);
    $action = mysqli_real_escape_string($conn, $_POST['event_action']);
    
    // Allow fiesta to be NULL if empty
    $fiesta_value = empty($fiesta) ? 'NULL' : "'$fiesta'";
    
    $sql = "INSERT INTO araw_events (municipality, barangay, event_date, fiesta, action) 
            VALUES ('$municipality', '$barangay', '$event_date', $fiesta_value, '$action')";
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Event added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
}

function readEvents($conn) {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;
    $search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
    $month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
    
    // Build WHERE clause
    $where = "WHERE 1=1";
    
    if (!empty($search)) {
        $where .= " AND (municipality LIKE '%$search%' OR barangay LIKE '%$search%' OR action LIKE '%$search%')";
    }
    
    if ($month > 0) {
        $where .= " AND MONTH(event_date) = $month";
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM araw_events $where";
    $countResult = mysqli_query($conn, $countSql);
    $total = mysqli_fetch_assoc($countResult)['total'];
    
    // Get paginated data
    $sql = "SELECT * FROM araw_events $where ORDER BY event_date ASC LIMIT $limit OFFSET $offset";
    $result = mysqli_query($conn, $sql);
    
    $events = [];
    while($row = mysqli_fetch_assoc($result)) {
        $events[] = $row;
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $events,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

function updateEvent($conn) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $municipality = mysqli_real_escape_string($conn, $_POST['municipality']);
    $barangay = mysqli_real_escape_string($conn, $_POST['barangay']);
    $event_date = mysqli_real_escape_string($conn, $_POST['event_date']);
    $fiesta = mysqli_real_escape_string($conn, $_POST['fiesta']);
    $action = mysqli_real_escape_string($conn, $_POST['event_action']);
    
    // Allow fiesta to be NULL if empty
    $fiesta_value = empty($fiesta) ? 'NULL' : "'$fiesta'";
    
    $sql = "UPDATE araw_events 
            SET municipality='$municipality', barangay='$barangay', 
                event_date='$event_date', fiesta=$fiesta_value, action='$action' 
            WHERE id='$id'";
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Event updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
}

function deleteEvent($conn) {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    
    $sql = "DELETE FROM araw_events WHERE id='$id'";
    
    if(mysqli_query($conn, $sql)) {
        echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . mysqli_error($conn)]);
    }
}

function getSingleEvent($conn) {
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    $sql = "SELECT * FROM araw_events WHERE id='$id'";
    $result = mysqli_query($conn, $sql);
    
    if($row = mysqli_fetch_assoc($result)) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Event not found']);
    }
}
?>