<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require_once '../electric/config/conn.php';

$action = $_GET['action'] ?? 'list';

try {
    switch($action) {
        case 'list':
            $search = $_GET['search'] ?? '';
            $area_filter = $_GET['area_filter'] ?? 'all';
            $limit = intval($_GET['limit'] ?? 50);
            $offset = intval($_GET['offset'] ?? 0);
            
            $sql = "SELECT id, folder_no, arp_td_no, area, land_owner, location, 
                    lot_no, ref_no, area_code, area_class, acquisition_cost 
                    FROM land_titles WHERE 1=1";
            
            $params = [];
            $types = '';
            
            if (!empty($search)) {
                $sql .= " AND (land_owner LIKE ? OR location LIKE ? OR ref_no LIKE ? OR lot_no LIKE ?)";
                $searchParam = "%$search%";
                $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
                $types .= 'ssss';
            }
            
            if ($area_filter !== 'all') {
                $sql .= " AND area_class = ?";
                $params[] = $area_filter;
                $types .= 's';
            }
            
            $sql .= " ORDER BY CAST(folder_no AS UNSIGNED) ASC, id ASC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            
            $titles = [];
            while($row = $result->fetch_assoc()) {
                $titles[] = $row;
            }
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM land_titles WHERE 1=1";
            if (!empty($search)) {
                $countSql .= " AND (land_owner LIKE ? OR location LIKE ? OR ref_no LIKE ? OR lot_no LIKE ?)";
            }
            if ($area_filter !== 'all') {
                $countSql .= " AND area_class = ?";
            }
            
            $countStmt = $conn->prepare($countSql);
            if (!empty($params)) {
                $countTypes = $types;
                $countTypes = str_replace('ii', '', $countTypes); // Remove limit/offset types
                $countParams = array_slice($params, 0, -2); // Remove limit/offset values
                if (!empty($countParams)) {
                    $countStmt->bind_param($countTypes, ...$countParams);
                }
            }
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $total = $countResult->fetch_assoc()['total'];
            
            echo json_encode([
                'success' => true,
                'data' => $titles,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ]);
            break;
            
        case 'detail':
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                throw new Exception('Invalid ID');
            }
            
            $sql = "SELECT * FROM land_titles WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('Record not found');
            }
            
            $title = $result->fetch_assoc();
            
            echo json_encode([
                'success' => true,
                'data' => $title
            ]);
            break;
            
        case 'stats':
            $sql = "SELECT 
                    COUNT(*) as total_records,
                    COUNT(DISTINCT land_owner) as total_owners,
                    SUM(CASE WHEN acquisition_cost IS NOT NULL AND acquisition_cost != '' THEN 1 ELSE 0 END) as records_with_cost,
                    area_class,
                    COUNT(*) as area_count
                    FROM land_titles
                    GROUP BY area_class";
            
            $result = $conn->query($sql);
            $stats = [
                'total_records' => 0,
                'total_owners' => 0,
                'records_with_cost' => 0,
                'area_distribution' => []
            ];
            
            while($row = $result->fetch_assoc()) {
                if (isset($row['area_class'])) {
                    $stats['area_distribution'][] = [
                        'area' => $row['area_class'],
                        'count' => $row['area_count']
                    ];
                }
            }
            
            // Get totals separately
            $totalSql = "SELECT 
                        COUNT(*) as total_records,
                        COUNT(DISTINCT land_owner) as total_owners,
                        SUM(CASE WHEN acquisition_cost IS NOT NULL AND acquisition_cost != '' THEN 1 ELSE 0 END) as records_with_cost
                        FROM land_titles";
            $totalResult = $conn->query($totalSql);
            $totals = $totalResult->fetch_assoc();
            
            $stats['total_records'] = $totals['total_records'];
            $stats['total_owners'] = $totals['total_owners'];
            $stats['records_with_cost'] = $totals['records_with_cost'];
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>