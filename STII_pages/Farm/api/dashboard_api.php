<?php
require_once __DIR__ . '/../../electric/config/check-session.php';

/**
 * Dashboard API for Farm/Agri Dashboard
 * Handles Animals, Trees, and Infrastructure data
 */

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ========================================
// DATABASE CONFIGURATION
// ========================================
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';
$db_name = 'doc_vic';
// ========================================

// Create database connection
$conn = new mysqli($db_host, $db_username, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $conn->connect_error
    ]);
    exit;
}

$conn->set_charset("utf8mb4");

// Get parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$month = isset($_GET['month']) && $_GET['month'] !== '' ? intval($_GET['month']) : null;
$filterType = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'month';

try {
    switch ($action) {
        case 'animals':
            getAnimals($conn, $year, $month);
            break;
            
        case 'trees':
            getTrees($conn, $year, $month);
            break;
            
        case 'infrastructure':
            getInfrastructure($conn, $year, $month, $filterType);
            break;
            
        case 'infrastructure_stats':
            getInfrastructureStats($conn, $year, $month, $filterType);
            break;
            
        case 'test':
            testConnection($conn);
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action specified',
                'valid_actions' => ['animals', 'trees', 'infrastructure', 'infrastructure_stats', 'test']
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
    if (isset($conn)) {
        $conn->close();
    }
}

/**
 * Test database connection
 */
function testConnection($conn) {
    try {
        $result = $conn->query("SELECT DATABASE() as db_name");
        $db = $result->fetch_assoc();
        
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        $requiredTables = ['farm_animals', 'farm_trees', 'infrastructure'];
        $missingTables = array_diff($requiredTables, $tables);
        
        echo json_encode([
            'success' => true,
            'message' => 'Connection successful',
            'database' => $db['db_name'],
            'all_tables' => $tables,
            'required_tables' => $requiredTables,
            'missing_tables' => $missingTables,
            'ready' => empty($missingTables)
        ]);
    } catch (Exception $e) {
        throw new Exception("Connection test failed: " . $e->getMessage());
    }
}

/**
 * Get animals data
 */
function getAnimals($conn, $year, $month) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'farm_animals'");
        if ($tableCheck->num_rows === 0) {
            throw new Exception("Table 'farm_animals' does not exist");
        }
        
        if ($month !== null && $month > 0) {
            $query = "SELECT * FROM farm_animals WHERE year = ? AND month = ? ORDER BY quantity DESC, animal_type ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $year, $month);
        } else {
            $query = "SELECT * FROM farm_animals WHERE year = ? ORDER BY animal_type ASC, month ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $year);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $animals = [];
        
        while ($row = $result->fetch_assoc()) {
            $animals[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $animals,
            'count' => count($animals),
            'year' => $year,
            'month' => $month
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error getting animals: " . $e->getMessage());
    }
}

/**
 * Get trees data
 */
function getTrees($conn, $year, $month) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'farm_trees'");
        if ($tableCheck->num_rows === 0) {
            throw new Exception("Table 'farm_trees' does not exist");
        }
        
        if ($month !== null && $month > 0) {
            $query = "SELECT * FROM farm_trees WHERE year = ? AND month = ? ORDER BY quantity DESC, tree_type ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $year, $month);
        } else {
            $query = "SELECT * FROM farm_trees WHERE year = ? ORDER BY tree_type ASC, month ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $year);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $trees = [];
        
        while ($row = $result->fetch_assoc()) {
            $trees[] = $row;
        }
        
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'data' => $trees,
            'count' => count($trees),
            'year' => $year,
            'month' => $month
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error getting trees: " . $e->getMessage());
    }
}

/**
 * Get infrastructure data categorized by type
 */
function getInfrastructure($conn, $year, $month, $filterType) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'infrastructure'");
        if ($tableCheck->num_rows === 0) {
            throw new Exception("Table 'infrastructure' does not exist");
        }
        
        // Build query based on filter type
        if ($filterType === 'year') {
            // Filter by year only
            $query = "SELECT * FROM infrastructure 
                      WHERE YEAR(created_at) = ? 
                      ORDER BY infrastructure_type, infrastructure_name ASC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $year);
        } else {
            // Filter by year and month
            if ($month !== null && $month > 0) {
                $query = "SELECT * FROM infrastructure 
                          WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? 
                          ORDER BY infrastructure_type, infrastructure_name ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $year, $month);
            } else {
                // Default to current month if not specified
                $currentMonth = date('n');
                $query = "SELECT * FROM infrastructure 
                          WHERE YEAR(created_at) = ? AND MONTH(created_at) = ? 
                          ORDER BY infrastructure_type, infrastructure_name ASC";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $year, $currentMonth);
            }
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Categorize data
        $categorized = [
            'Infrastructure Project' => [],
            'Machineries' => [],
            '4 Wheel Tractor' => [],
            'Feed Production Facilities' => []
        ];
        
        $totalCount = 0;
        
        while ($row = $result->fetch_assoc()) {
            $type = $row['infrastructure_type'];
            if (isset($categorized[$type])) {
                $categorized[$type][] = $row;
                $totalCount++;
            }
        }
        
        $stmt->close();
        
        // Get counts per category
        $counts = [
            'infrastructure' => count($categorized['Infrastructure Project']),
            'machineries' => count($categorized['Machineries']),
            '4wheel' => count($categorized['4 Wheel Tractor']),
            'feed' => count($categorized['Feed Production Facilities'])
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $categorized,
            'counts' => $counts,
            'total' => $totalCount,
            'year' => $year,
            'month' => $month,
            'filter_type' => $filterType
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error getting infrastructure: " . $e->getMessage());
    }
}

/**
 * Get infrastructure statistics for charts
 */
function getInfrastructureStats($conn, $year, $month, $filterType) {
    try {
        $tableCheck = $conn->query("SHOW TABLES LIKE 'infrastructure'");
        if ($tableCheck->num_rows === 0) {
            throw new Exception("Table 'infrastructure' does not exist");
        }
        
        // Build WHERE clause based on filter type
        if ($filterType === 'year') {
            $whereClause = "WHERE YEAR(created_at) = ?";
            $params = [$year];
            $types = "i";
        } else {
            if ($month !== null && $month > 0) {
                $whereClause = "WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
                $params = [$year, $month];
                $types = "ii";
            } else {
                $currentMonth = date('n');
                $whereClause = "WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?";
                $params = [$year, $currentMonth];
                $types = "ii";
            }
        }
        
        // Get status distribution for Infrastructure Projects only
        $query = "SELECT 
                    status,
                    COUNT(*) as count,
                    AVG(progress_percent) as avg_progress
                  FROM infrastructure 
                  $whereClause
                  AND infrastructure_type = 'Infrastructure Project'
                  GROUP BY status
                  ORDER BY count DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $statusData = [];
        while ($row = $result->fetch_assoc()) {
            $statusData[] = $row;
        }
        $stmt->close();
        
        // Get progress distribution
        $query = "SELECT 
                    CASE 
                        WHEN progress_percent = 100 THEN 'Completed'
                        WHEN progress_percent >= 80 THEN '80-99%'
                        WHEN progress_percent >= 60 THEN '60-79%'
                        WHEN progress_percent >= 40 THEN '40-59%'
                        WHEN progress_percent >= 20 THEN '20-39%'
                        ELSE '0-19%'
                    END as progress_range,
                    COUNT(*) as count
                  FROM infrastructure 
                  $whereClause
                  AND infrastructure_type = 'Infrastructure Project'
                  GROUP BY progress_range
                  ORDER BY MIN(progress_percent) DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $progressData = [];
        while ($row = $result->fetch_assoc()) {
            $progressData[] = $row;
        }
        $stmt->close();
        
        // Get total counts
        $query = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                    AVG(progress_percent) as overall_progress
                  FROM infrastructure 
                  $whereClause
                  AND infrastructure_type = 'Infrastructure Project'";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $totals = $result->fetch_assoc();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'status_distribution' => $statusData,
            'progress_distribution' => $progressData,
            'totals' => $totals,
            'year' => $year,
            'month' => $month,
            'filter_type' => $filterType
        ]);
        
    } catch (Exception $e) {
        throw new Exception("Error getting infrastructure stats: " . $e->getMessage());
    }
}
?>