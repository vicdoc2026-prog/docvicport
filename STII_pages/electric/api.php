<?php
// Debug (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// JSON + CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

require_once 'config.php'; // must define $conn = new mysqli(...)

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($action) {
        case 'get_bills':       getBills($conn); break;
        case 'get_locations':   getLocations($conn); break;
        case 'get_summary':     getSummary($conn); break;
        case 'get_chart_data':  getChartData($conn); break;
        case 'add_bill':        addBill($conn, $method); break;
        default:
            echo json_encode(['success'=>false,'message'=>'Invalid action. Available: get_bills, get_locations, get_summary, get_chart_data, add_bill']);
    }
} catch (Throwable $e) {
    echo json_encode(['success'=>false,'message'=>'Error: '.$e->getMessage()]);
}
if (isset($conn) && $conn instanceof mysqli) { $conn->close(); }
exit;

// ==================== FUNCTIONS ====================

function requireJsonBody() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) { throw new Exception('Invalid JSON body'); }
    return $data;
}

// ---------- ADD BILL ----------
function addBill($conn, $method) {
    if ($method !== 'POST') { echo json_encode(['success'=>false,'message'=>'add_bill requires POST']); return; }
    $data = requireJsonBody();

    $location = trim($data['location'] ?? '');
    $dueDate  = trim($data['dueDate']  ?? '');
    $kwh      = $data['kwh']   ?? null;
    $amount   = $data['amount']?? null;

    if ($location === '' || $dueDate === '' || !is_numeric($kwh) || !is_numeric($amount)) {
        echo json_encode(['success'=>false,'message'=>'Missing/invalid fields: location, dueDate, kwh, amount are required']); return;
    }

    // Validate date
    $d = DateTime::createFromFormat('Y-m-d', $dueDate);
    if (!$d || $d->format('Y-m-d') !== $dueDate) {
        echo json_encode(['success'=>false,'message'=>'Invalid dueDate format, expected YYYY-MM-DD']); return;
    }

    // Find location_id
    $locStmt = $conn->prepare("SELECT id FROM locations WHERE location_name = ? LIMIT 1");
    if (!$locStmt) throw new Exception('Prepare failed: '.$conn->error);
    $locStmt->bind_param("s", $location);
    $locStmt->execute();
    $res = $locStmt->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid location']); return;
    }
    $row = $res->fetch_assoc();
    $location_id = (int)$row['id'];
    $locStmt->close();

    // Optional: prevent duplicate (location_id + due_date)
    $chk = $conn->prepare("SELECT 1 FROM bills WHERE location_id=? AND due_date=? LIMIT 1");
    if (!$chk) throw new Exception('Prepare failed: '.$conn->error);
    $chk->bind_param("is", $location_id, $dueDate);
    $chk->execute();
    $dup = $chk->get_result()->num_rows > 0;
    $chk->close();
    if ($dup) {
        echo json_encode(['success'=>false,'message'=>'Bill for this location and due date already exists']); return;
    }

    // Insert
    $stmt = $conn->prepare("INSERT INTO bills (location_id, due_date, kwh, amount) VALUES (?,?,?,?)");
    if (!$stmt) throw new Exception('Prepare failed: '.$conn->error);
    $kwhF = floatval($kwh);
    $amtF = floatval($amount);
    $stmt->bind_param("isdd", $location_id, $dueDate, $kwhF, $amtF);
    if (!$stmt->execute()) {
        echo json_encode(['success'=>false,'message'=>'Insert failed: '.$stmt->error]); return;
    }
    $newId = $stmt->insert_id;
    $stmt->close();

    echo json_encode(['success'=>true,'message'=>'Bill added successfully','bill_id'=>$newId]);
}

// ---------- LIST BILLS ----------
function getBills($conn) {
    $location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';
    $year     = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';
    $month    = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : '';
    $page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $limit    = isset($_GET['limit']) ? intval($_GET['limit']) : 15;
    $offset   = ($page - 1) * $limit;
    $where = []; $countWhere = [];

    if (!empty($location) && $location !== 'All Locations') { $where[]="l.location_name='$location'"; $countWhere[]="l.location_name='$location'"; }
    if (!empty($year) && $year !== 'All Years') { $where[]="YEAR(b.due_date)=$year"; $countWhere[]="YEAR(b.due_date)=$year"; }
    if (!empty($month) && $month !== 'All Months') { $monthNum = date('m', strtotime($month . ' 1')); $where[]="MONTH(b.due_date)=$monthNum"; $countWhere[]="MONTH(b.due_date)=$monthNum"; }

    $whereClause = count($where)>0 ? 'WHERE '.implode(' AND ',$where) : '';
    $countWhereClause = count($countWhere)>0 ? 'WHERE '.implode(' AND ',$countWhere) : '';

    $countQuery = "SELECT COUNT(*) as total FROM bills b INNER JOIN locations l ON b.location_id = l.id $countWhereClause";
    $countResult = $conn->query($countQuery);
    if (!$countResult) throw new Exception('Count query failed: '.$conn->error);
    $total = (int)$countResult->fetch_assoc()['total'];

    $query = "SELECT b.*, l.location_name, l.location_class
              FROM bills b
              INNER JOIN locations l ON b.location_id = l.id
              $whereClause
              ORDER BY b.due_date DESC
              LIMIT $limit OFFSET $offset";
    $result = $conn->query($query);
    if (!$result) throw new Exception('Bills query failed: '.$conn->error);

    $bills = [];
    while ($row = $result->fetch_assoc()) {
        $bills[] = [
            'id' => (int)$row['id'],
            'location' => $row['location_name'],
            'locationClass' => $row['location_class'],
            'dueDate' => $row['due_date'],
            'kwh' => (float)$row['kwh'],
            'amount' => (float)$row['amount']
        ];
    }
    echo json_encode(['success'=>true,'data'=>$bills,'total'=>$total,'page'=>$page,'limit'=>$limit,'totalPages'=>ceil($total/$limit)]);
}

// ---------- LOCATIONS ----------
function getLocations($conn) {
    $query = "SELECT * FROM locations ORDER BY location_name";
    $result = $conn->query($query);
    if (!$result) throw new Exception('Locations query failed: '.$conn->error);
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = ['id'=>(int)$row['id'], 'name'=>$row['location_name'], 'class'=>$row['location_class']];
    }
    echo json_encode(['success'=>true,'data'=>$locations]);
}

// ---------- SUMMARY ----------
function getSummary($conn) {
    $location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : '';
    $year     = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';
    $month    = isset($_GET['month']) ? $conn->real_escape_string($_GET['month']) : '';
    $where = [];
    if (!empty($location) && $location !== 'All Locations') $where[] = "l.location_name = '$location'";
    if (!empty($year) && $year !== 'All Years')           $where[] = "YEAR(b.due_date) = $year";
    if (!empty($month) && $month !== 'All Months') {
        $monthNum = date('m', strtotime($month . ' 1'));
        $where[] = "MONTH(b.due_date) = $monthNum";
    }
    $whereClause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    $query = "SELECT COUNT(*) as total_bills, SUM(b.kwh) as total_consumption, SUM(b.amount) as total_amount, AVG(b.amount) as average_amount
              FROM bills b INNER JOIN locations l ON b.location_id = l.id $whereClause";
    $result = $conn->query($query);
    if (!$result) throw new Exception('Summary query failed: '.$conn->error);
    $summary = $result->fetch_assoc();
    echo json_encode(['success'=>true,'data'=>[
        'totalBills'=>(int)$summary['total_bills'],
        'totalConsumption'=>(float)($summary['total_consumption'] ?? 0),
        'totalAmount'=>(float)($summary['total_amount'] ?? 0),
        'averageAmount'=>(float)($summary['average_amount'] ?? 0),
    ]]);
}

// ---------- CHART DATA ----------
function getChartData($conn) {
    $location = isset($_GET['location']) ? $conn->real_escape_string($_GET['location']) : 'all';
    $mode     = isset($_GET['mode']) ? $conn->real_escape_string($_GET['mode']) : 'monthly';
    $year     = isset($_GET['year']) ? $conn->real_escape_string($_GET['year']) : '';

    if ($mode === 'yearly') {
        $where = ($location !== 'all') ? "WHERE l.location_name = '$location'" : '';
        $query = "SELECT YEAR(b.due_date) as year, l.location_name, SUM(b.amount) as total_amount
                  FROM bills b INNER JOIN locations l ON b.location_id = l.id
                  $where GROUP BY YEAR(b.due_date), l.location_name
                  ORDER BY YEAR(b.due_date), l.location_name";
    } else {
        $whereParts = [];
        if ($location !== 'all') $whereParts[] = "l.location_name = '$location'";
        if (!empty($year) && $year !== 'All Years') $whereParts[] = "YEAR(b.due_date) = $year";
        $where = count($whereParts) ? 'WHERE '.implode(' AND ', $whereParts) : '';
        $query = "SELECT MONTH(b.due_date) as month, YEAR(b.due_date) as year, l.location_name, SUM(b.amount) as total_amount
                  FROM bills b INNER JOIN locations l ON b.location_id = l.id
                  $where GROUP BY YEAR(b.due_date), MONTH(b.due_date), l.location_name
                  ORDER BY YEAR(b.due_date), MONTH(b.due_date), l.location_name";
    }
    $result = $conn->query($query);
    if (!$result) throw new Exception('Chart data query failed: '.$conn->error);

    $chartData = [];
    while ($row = $result->fetch_assoc()) {
        $chartData[] = [
            'location' => $row['location_name'],
            'year' => (int)$row['year'],
            'month' => isset($row['month']) ? (int)$row['month'] : null,
            'amount' => (float)$row['total_amount']
        ];
    }
    echo json_encode(['success'=>true,'data'=>$chartData,'mode'=>$mode,'location'=>$location]);
}
