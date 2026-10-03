<?php
require_once '../config/check-session.php';
require_once '../config/conn_pdo.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$username = $_SESSION['username'];

try {
    switch ($action) {
        case 'get_all':
            $stmt = $conn->prepare("SELECT id, start_date, end_date, title, category FROM calendar_events ORDER BY start_date");
            $stmt->execute();
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format for frontend
            $formatted = array_map(function($event) {
                return [
                    'id' => 'ev' . $event['id'],    
                    'start' => $event['start_date'],
                    'end' => $event['end_date'],
                    'title' => $event['title'],
                    'cat' => $event['category']
                ];
            }, $events);
            
            echo json_encode(['success' => true, 'events' => $formatted]);
            break;

        case 'add':
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? null;
            $title = $_POST['title'] ?? '';
            $category = $_POST['category'] ?? '';
            
            if (empty($start) || empty($title) || empty($category)) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $conn->prepare("INSERT INTO calendar_events (start_date, end_date, title, category, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$start, $end ?: null, $title, $category, $username]);
            
            $newId = $conn->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Event added successfully',
                'event' => [
                    'id' => 'ev' . $newId,
                    'start' => $start,
                    'end' => $end ?: null,
                    'title' => $title,
                    'cat' => $category
                ]
            ]);
            break;

        case 'update':
            $id = str_replace('ev', '', $_POST['id'] ?? '');
            $start = $_POST['start_date'] ?? '';
            $end = $_POST['end_date'] ?? null;
            $title = $_POST['title'] ?? '';
            $category = $_POST['category'] ?? '';
            
            if (empty($id) || empty($start) || empty($title) || empty($category)) {
                throw new Exception('Missing required fields');
            }
            
            $stmt = $conn->prepare("UPDATE calendar_events SET start_date = ?, end_date = ?, title = ?, category = ? WHERE id = ?");
            $stmt->execute([$start, $end ?: null, $title, $category, $id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Event updated successfully',
                'event' => [
                    'id' => 'ev' . $id,
                    'start' => $start,
                    'end' => $end ?: null,
                    'title' => $title,
                    'cat' => $category
                ]
            ]);
            break;

        case 'delete':
            $id = str_replace('ev', '', $_POST['id'] ?? '');
            
            if (empty($id)) {
                throw new Exception('Event ID is required');
            }
            
            $stmt = $conn->prepare("DELETE FROM calendar_events WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Event deleted successfully']);
            break;

        case 'get_by_month':
            $month = $_GET['month'] ?? '';
            $year = $_GET['year'] ?? 2025;
            
            if (empty($month)) {
                throw new Exception('Month is required');
            }
            
            $startOfMonth = "$year-$month-01";
            $endOfMonth = date("Y-m-t", strtotime($startOfMonth));
            
            $stmt = $conn->prepare("
                SELECT id, start_date, end_date, title, category 
                FROM calendar_events 
                WHERE (start_date BETWEEN ? AND ?) 
                   OR (end_date BETWEEN ? AND ?)
                   OR (start_date <= ? AND end_date >= ?)
                ORDER BY start_date
            ");
            $stmt->execute([$startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth, $startOfMonth, $endOfMonth]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $formatted = array_map(function($event) {
                return [
                    'id' => 'ev' . $event['id'],
                    'start' => $event['start_date'],
                    'end' => $event['end_date'],
                    'title' => $event['title'],
                    'cat' => $event['category']
                ];
            }, $events);
            
            echo json_encode(['success' => true, 'events' => $formatted]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>