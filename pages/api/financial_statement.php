<?php
require_once '../config/conn.php';
require_once __DIR__ . '/../../config/role-access.php';
header('Content-Type: application/json');

if (!docVicCanAccessPortal('belvic')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_dashboard_stats':
        getDashboardStats($conn);
        break;

    case 'get_statements':
        getStatements($conn);
        break;

    case 'get_statement_details':
        getStatementDetails($conn);
        break;

    case 'create_statement':
        createStatement($conn);
        break;

    case 'update_statement':
        updateStatement($conn);
        break;

    case 'delete_statement':
        deleteStatement($conn);
        break;

    case 'get_options':
        getOptions($conn);
        break;

    case 'add_option':
        addOption($conn);
        break;

    case 'delete_option':
        deleteOption($conn);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getDashboardStats($conn) {
    try {
        $currentYear = date('Y');

        $stmt = $conn->prepare(
            "SELECT 
                SUM(total_revenue) AS total_revenue,
                SUM(total_expenses) AS total_expenses,
                COUNT(*) AS total_statements
             FROM income_statements
             WHERE YEAR(statement_date) = ?"
        );
        $stmt->bind_param('i', $currentYear);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();

        $totalRevenue = $row['total_revenue'] ?? 0;
        $totalExpenses = $row['total_expenses'] ?? 0;

        echo json_encode([
            'success' => true,
            'data' => [
                'total_revenue' => $totalRevenue,
                'total_expenses' => $totalExpenses,
                'net_income' => $totalRevenue - $totalExpenses,
                'total_statements' => $row['total_statements'] ?? 0
            ]
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStatements($conn) {
    try {
        $filterMonth = $_GET['filter_month'] ?? '';

        $query = "
            SELECT
                id,
                title,
                statement_date,
                period,
                total_revenue,
                total_expenses,
                net_income,
                created_at
            FROM income_statements
        ";

        if ($filterMonth) {
            $query .= " WHERE DATE_FORMAT(statement_date, '%Y-%m') = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $filterMonth);
        } else {
            $query .= " ORDER BY statement_date DESC";
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $statements = [];
        while ($row = $result->fetch_assoc()) {
            $statements[] = $row;
        }

        echo json_encode([
            'success' => true,
            'data' => $statements
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getStatementDetails($conn) {
    try {
        $id = intval($_GET['id'] ?? 0);

        $stmt = $conn->prepare("SELECT * FROM income_statements WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $statement = $stmt->get_result()->fetch_assoc();

        if (!$statement) {
            echo json_encode(['success' => false, 'message' => 'Statement not found']);
            return;
        }

        $itemsStmt = $conn->prepare(
            "SELECT * FROM income_statement_items
             WHERE statement_id = ?
             ORDER BY category, id"
        );
        $itemsStmt->bind_param('i', $id);
        $itemsStmt->execute();
        $itemsResult = $itemsStmt->get_result();

        $items = [
            'revenue' => [],
            'less' => [],
            'other_income' => [],
            'repair' => []
        ];

        while ($item = $itemsResult->fetch_assoc()) {
            $items[$item['category']][] = [
                'name' => $item['item_name'],
                'amount' => floatval($item['amount'])
            ];
        }

        $statement['items'] = $items;

        echo json_encode([
            'success' => true,
            'data' => $statement
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function createStatement($conn) {
    try {
        $title = trim($_POST['title'] ?? '');
        $statementDate = $_POST['statement_date'] ?? '';
        $period = $_POST['period'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true);

        if (!$title || !$statementDate || !$period) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $totalRevenue = array_sum(array_column($items['revenue'] ?? [], 'amount'));
        $totalLess = array_sum(array_column($items['less'] ?? [], 'amount'));
        $totalOtherIncome = array_sum(array_column($items['other_income'] ?? [], 'amount'));
        $totalRepair = array_sum(array_column($items['repair'] ?? [], 'amount'));

        $netRevenue = $totalRevenue - $totalLess;
        $totalExpenses = $totalRepair + $totalLess;
        $netIncome = $netRevenue + $totalOtherIncome - $totalRepair;

        $stmt = $conn->prepare(
            "INSERT INTO income_statements
            (title, statement_date, period, total_revenue, total_expenses, net_income, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param(
            'sssddd',
            $title,
            $statementDate,
            $period,
            $totalRevenue,
            $totalExpenses,
            $netIncome
        );
        $stmt->execute();

        $statementId = $conn->insert_id;
        insertStatementItems($conn, $statementId, $items);

        echo json_encode([
            'success' => true,
            'message' => 'Statement created successfully',
            'id' => $statementId
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateStatement($conn) {
    try {
        $id = intval($_POST['id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $statementDate = $_POST['statement_date'] ?? '';
        $period = $_POST['period'] ?? '';
        $items = json_decode($_POST['items'] ?? '[]', true);

        if (!$id || !$title || !$statementDate || !$period) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $totalRevenue = array_sum(array_column($items['revenue'] ?? [], 'amount'));
        $totalLess = array_sum(array_column($items['less'] ?? [], 'amount'));
        $totalOtherIncome = array_sum(array_column($items['other_income'] ?? [], 'amount'));
        $totalRepair = array_sum(array_column($items['repair'] ?? [], 'amount'));

        $netRevenue = $totalRevenue - $totalLess;
        $totalExpenses = $totalRepair + $totalLess;
        $netIncome = $netRevenue + $totalOtherIncome - $totalRepair;

        $stmt = $conn->prepare(
            "UPDATE income_statements
             SET title = ?, statement_date = ?, period = ?, total_revenue = ?, total_expenses = ?, net_income = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            'sssdddi',
            $title,
            $statementDate,
            $period,
            $totalRevenue,
            $totalExpenses,
            $netIncome,
            $id
        );
        $stmt->execute();

        $deleteStmt = $conn->prepare("DELETE FROM income_statement_items WHERE statement_id = ?");
        $deleteStmt->bind_param('i', $id);
        $deleteStmt->execute();

        insertStatementItems($conn, $id, $items);

        echo json_encode([
            'success' => true,
            'message' => 'Statement updated successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteStatement($conn) {
    try {
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid statement ID']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM income_statement_items WHERE statement_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM income_statements WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Statement deleted successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function insertStatementItems($conn, $statementId, $items) {
    $stmt = $conn->prepare(
        "INSERT INTO income_statement_items
         (statement_id, category, item_name, amount)
         VALUES (?, ?, ?, ?)"
    );

    foreach (['revenue', 'less', 'other_income', 'repair'] as $category) {
        if (!empty($items[$category])) {
            foreach ($items[$category] as $item) {
                $stmt->bind_param(
                    'issd',
                    $statementId,
                    $category,
                    $item['name'],
                    $item['amount']
                );
                $stmt->execute();
            }
        }
    }
}

function getOptions($conn) {
    try {
        $category = $_GET['category'] ?? '';

        if (!$category) {
            echo json_encode(['success' => false, 'message' => 'Category is required']);
            return;
        }

        $stmt = $conn->prepare(
            "SELECT id, option_name AS name
             FROM item_options
             WHERE category = ?
             ORDER BY option_name"
        );
        $stmt->bind_param('s', $category);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addOption($conn) {
    try {
        $category = $_POST['category'] ?? '';
        $optionName = trim($_POST['option_name'] ?? '');

        if (!$category || !$optionName) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            return;
        }

        $stmt = $conn->prepare(
            "INSERT INTO item_options (category, option_name)
             VALUES (?, ?)"
        );
        $stmt->bind_param('ss', $category, $optionName);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Option added successfully',
            'id' => $conn->insert_id
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteOption($conn) {
    try {
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid option ID']);
            return;
        }

        $stmt = $conn->prepare("DELETE FROM item_options WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'Option deleted successfully'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

$conn->close();
