<?php
header('Content-Type: application/json');
require_once '../config/conn.php';

// Get filter parameters
$position = isset($_GET['position']) ? $_GET['position'] : 'all';
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$loan = isset($_GET['loan']) ? $_GET['loan'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;

$offset = ($page - 1) * $limit;

// Build query
$sql = "SELECT m.*, l.check_number, l.loan_date, l.batch, l.amount 
        FROM members m 
        LEFT JOIN loans l ON m.id = l.member_id 
        WHERE 1=1";

$countSql = "SELECT COUNT(DISTINCT m.id) as total 
             FROM members m 
             LEFT JOIN loans l ON m.id = l.member_id 
             WHERE 1=1";

// Apply filters
if ($position !== 'all') {
    $position = $conn->real_escape_string($position);
    $sql .= " AND m.position = '$position'";
    $countSql .= " AND m.position = '$position'";
}

if ($status !== 'all') {
    $status = $conn->real_escape_string($status);
    $sql .= " AND m.status = '$status'";
    $countSql .= " AND m.status = '$status'";
}

if ($loan === 'loaned') {
    $sql .= " AND l.id IS NOT NULL";
    $countSql .= " AND l.id IS NOT NULL";
} elseif ($loan === 'no-loan') {
    $sql .= " AND l.id IS NULL";
    $countSql .= " AND l.id IS NULL";
}

if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND m.full_name LIKE '%$search%'";
    $countSql .= " AND m.full_name LIKE '%$search%'";
}

// Get total count
$countResult = $conn->query($countSql);
$totalRow = $countResult->fetch_assoc();
$total = $totalRow['total'];

// Add order and limit
$sql .= " ORDER BY m.id ASC LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$members = array();
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $member = array(
            'id' => $row['id'],
            'name' => $row['full_name'],
            'position' => $row['position'],
            'date' => $row['date_of_membership'],
            'status' => $row['status']
        );
        
        if ($row['check_number']) {
            $member['loan'] = array(
                'checkNumber' => $row['check_number'],
                'date' => $row['loan_date'],
                'batch' => $row['batch'],
                'amount' => $row['amount']
            );
        } else {
            $member['loan'] = null;
        }
        
        $members[] = $member;
    }
}

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_members,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_members,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_members,
    SUM(CASE WHEN DATE_FORMAT(STR_TO_DATE(date_of_membership, '%m/%d/%y'), '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN 1 ELSE 0 END) as new_this_month
    FROM members";

$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

$response = array(
    'success' => true,
    'data' => $members,
    'pagination' => array(
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'totalPages' => ceil($total / $limit)
    ),
    'stats' => array(
        'totalMembers' => $stats['total_members'],
        'activeMembers' => $stats['active_members'],
        'pendingMembers' => $stats['pending_members'],
        'newThisMonth' => $stats['new_this_month'],
        'activePercentage' => $stats['total_members'] > 0 ? round(($stats['active_members'] / $stats['total_members']) * 100) : 0
    )
);

echo json_encode($response);

$conn->close();
?>