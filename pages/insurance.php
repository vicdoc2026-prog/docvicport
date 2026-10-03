<?php 
require_once 'config/check-session.php';
require_once 'config/conn_pdo.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Create uploads directory if it doesn't exist
$upload_dir = 'uploads/insurance_documents/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Database functions
function deleteInsurance($id, $conn, $upload_dir) {
    $stmt = $conn->prepare("SELECT document_path FROM insurances WHERE id = ?");
    $stmt->execute([$id]);
    $insurance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($insurance && !empty($insurance['document_path']) && file_exists($insurance['document_path'])) {
        unlink($insurance['document_path']);
    }
    
    $stmt = $conn->prepare("DELETE FROM insurances WHERE id = ?");
    $stmt->execute([$id]);
}

function uploadDocument($file, $upload_dir) {
    if($file['error'] != 0) return [null, null];
    
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'xls', 'xlsx'];
    
    if(!in_array($file_ext, $allowed)) return [null, null];
    
    $new_file_name = uniqid() . '_' . $file_name;
    $document_path = $upload_dir . $new_file_name;
    
    if(move_uploaded_file($file_tmp, $document_path)) {
        return [$file_name, $document_path];
    }
    
    return [null, null];
}

// Handle Delete
if(isset($_GET['delete'])) {
    deleteInsurance((int)$_GET['delete'], $conn, $upload_dir);
    header("Location: insurance.php?msg=deleted");
    exit;
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['add_insurance'])) {
        list($doc_name, $doc_path) = uploadDocument($_FILES['document'] ?? [], $upload_dir);
        
        $stmt = $conn->prepare("INSERT INTO insurances (category, insurance_name, provider, date_from, date_to, amount, description, document_name, document_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['category'],
            $_POST['insurance_name'],
            $_POST['provider'],
            $_POST['date_from'],
            $_POST['date_to'],
            $_POST['amount'],
            $_POST['description'],
            $doc_name,
            $doc_path
        ]);
        header("Location: insurance.php?msg=added");
        exit;
    }
    
    if(isset($_POST['edit_insurance'])) {
        $id = (int)$_POST['id'];
        
        // Get current document
        $stmt = $conn->prepare("SELECT document_name, document_path FROM insurances WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $doc_name = $current['document_name'] ?? null;
        $doc_path = $current['document_path'] ?? null;
        
        // Handle new upload
        if(isset($_FILES['document']) && $_FILES['document']['error'] == 0) {
            if(!empty($current['document_path']) && file_exists($current['document_path'])) {
                unlink($current['document_path']);
            }
            
            list($doc_name, $doc_path) = uploadDocument($_FILES['document'], $upload_dir);
        }
        
        $stmt = $conn->prepare("UPDATE insurances SET category = ?, insurance_name = ?, provider = ?, date_from = ?, date_to = ?, amount = ?, description = ?, document_name = ?, document_path = ? WHERE id = ?");
        $stmt->execute([
            $_POST['category'],
            $_POST['insurance_name'],
            $_POST['provider'],
            $_POST['date_from'],
            $_POST['date_to'],
            $_POST['amount'],
            $_POST['description'],
            $doc_name,
            $doc_path,
            $id
        ]);
        header("Location: insurance.php?msg=updated");
        exit;
    }
}

// Handle Excel Export
if(isset($_GET['export'])) {
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="insurances_' . date('Y-m-d') . '.xls"');
    
    $stmt = $conn->prepare("SELECT * FROM insurances ORDER BY category, insurance_name");
    $stmt->execute();
    $insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Category</th><th>Insurance Name</th><th>Provider</th><th>Date From</th><th>Date To</th><th>Amount</th><th>Status</th><th>Description</th></tr>";
    
    foreach($insurances as $ins) {
        $status = (strtotime($ins['date_to']) >= strtotime('today')) ? 'Active' : 'Expired';
        echo "<tr>";
        echo "<td>{$ins['id']}</td>";
        echo "<td>{$ins['category']}</td>";
        echo "<td>{$ins['insurance_name']}</td>";
        echo "<td>{$ins['provider']}</td>";
        echo "<td>{$ins['date_from']}</td>";
        echo "<td>{$ins['date_to']}</td>";
        echo "<td>" . number_format($ins['amount'], 2) . "</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$ins['description']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    exit;
}

// Pagination and Search
$limit = 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? 'all';

$where = '';
$params = [];

if(!empty($search)) {
    $where = "WHERE (insurance_name LIKE ? OR provider LIKE ?)";
    $search_param = "%{$search}%";
    $params = [$search_param, $search_param];
}

if($category !== 'all') {
    if($where) {
        $where .= " AND category = ?";
    } else {
        $where = "WHERE category = ?";
    }
    $params[] = $category;
}

// Get total count
$count_sql = "SELECT COUNT(*) FROM insurances {$where}";
$total_stmt = $conn->prepare($count_sql);
$total_stmt->execute($params);
$total_records = $total_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch data
$sql = "SELECT * FROM insurances {$where} ORDER BY created_at DESC";
$sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

$stmt = $conn->prepare($sql);
if (!$stmt->execute($params)) {
    echo "<div class='alert alert-danger'>Database error: " . implode(' ', $stmt->errorInfo()) . "</div>";
    $insurances = [];
} else {
    $insurances = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insurance Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3b82f6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #9ca3af;
            --gray-700: #334155;
            --gray-900: #0f172a;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gray-50);
            color: var(--gray-700);
        }
        
        .main-wrapper {
            padding: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .page-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 4px;
        }
        
        .page-subtitle {
            font-size: 14px;
            color: var(--gray-700);
        }
        
        /* Toolbar */
        .toolbar {
            padding: 16px 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
            background: var(--gray-50);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            max-width: 400px;
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            font-size: 14px;
        }
        
        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        /* Button Styles */
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 13px;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-info {
            background: var(--info-color);
            color: white;
        }
        
        .btn-info:hover {
            background: #0891b2;
        }
        
        /* Table Container */
        .table-container {
            overflow-y: auto;
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .data-table thead {
            position: sticky;
            top: 0;
            background: var(--gray-50);
            z-index: 10;
        }
        
        .data-table th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            color: var(--gray-700);
            border-bottom: 2px solid var(--gray-200);
            white-space: nowrap;
        }
        
        .data-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--gray-200);
            vertical-align: middle;
        }
        
        .data-table tbody tr:hover {
            background: var(--gray-50);
        }
        
        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-expiring {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            overflow: auto;
        }
        
        .modal-header {
            padding: 16px 20px;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
            flex-shrink: 0;
        }
        
        .modal-body {
            padding: 20px;
            overflow-y: auto;
            flex-grow: 1;
        }
        
        .modal-footer {
            padding: 16px 20px;
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            flex-shrink: 0;
        }
        
        /* Form */
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        
        /* Alerts */
        .alert {
            padding: 12px 16px;
            margin: 0 20px 20px;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-danger {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        /* Pagination - Enhanced */
        .pagination {
            padding: 16px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 6px;
            border-top: 1px solid var(--gray-200);
            flex-wrap: wrap;
        }
        
        .page-link {
            padding: 8px 14px;
            border: 1px solid var(--gray-300);
            border-radius: 4px;
            text-decoration: none;
            color: var(--gray-700);
            font-size: 14px;
            background: white;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            min-width: 40px;
            justify-content: center;
        }
        
        .page-link:not(.disabled):not(.active):hover {
            background: var(--gray-100);
            border-color: var(--gray-400);
            transform: translateY(-1px);
        }
        
        .page-link.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
            font-weight: 600;
        }
        
        .page-link.disabled {
            color: var(--gray-400);
            cursor: not-allowed;
            background: var(--gray-50);
            border-color: var(--gray-200);
        }
        
        /* Previous/Next buttons */
        .page-link.page-nav {
            font-weight: 500;
            padding: 8px 16px;
        }
        
        .page-link.page-nav:not(.disabled) {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-link.page-nav:not(.disabled):hover {
            background: #2563eb;
            border-color: #2563eb;
        }
        
        .page-link.page-nav.disabled {
            background: var(--gray-100);
            color: var(--gray-400);
        }
        
        /* Empty state */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--gray-700);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 16px;
            color: var(--gray-300);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .main-wrapper {
                padding: 12px;
            }
            
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                max-width: 100%;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .modal-content {
                width: 95%;
            }
            
            .pagination {
                padding: 12px;
                gap: 4px;
            }
            
            .page-link {
                padding: 6px 10px;
                font-size: 13px;
                min-width: 36px;
            }
            
            .page-link.page-nav {
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <?php 
    include '../bar/header.php';
    include '../bar/navbar.php';
    ?>
    <div class="main-wrapper" style="margin-top: 50px; max-height: calc(100vh - 70px); overflow-y: auto;">
        <div class="card">
            <div class="card-header">
                <h1 class="page-title">Insurance Management</h1>
                <p class="page-subtitle"></p>
            </div>
            
            <?php if(isset($_GET['msg'])): ?>
                <div class="alert alert-success" style="margin-top: 20px;">
                    <?php 
                    $messages = [
                        'added' => 'Insurance policy added successfully!',
                        'updated' => 'Insurance policy updated successfully!',
                        'deleted' => 'Insurance policy deleted successfully!'
                    ];
                    echo $messages[$_GET['msg']] ?? '';
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="toolbar">
                <form method="GET" action="" style="flex: 1; display: flex; gap: 12px; max-width: 600px;">
                    <select name="category" class="form-control" style="width: auto; min-width: 150px;">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="Building" <?php echo $category === 'Building' ? 'selected' : ''; ?>>Building</option>
                        <option value="Lot" <?php echo $category === 'Lot' ? 'selected' : ''; ?>>Lot</option>
                        <option value="Equipment" <?php echo $category === 'Equipment' ? 'selected' : ''; ?>>Equipment</option>
                    </select>
                    <input type="text" name="search" class="search-box" 
                           placeholder="Search by name or provider..." 
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary" style="padding: 8px 12px;"><i class="fas fa-filter"></i> Filter</button>
                </form>
                <div class="action-buttons">
                    <button class="btn btn-primary" onclick="openModal('addModal')">
                        <i class="fas fa-plus"></i> Add Insurance
                    </button>
                    <a href="?export=1" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                </div>
            </div>
            
            <div class="table-container">
                <?php if(empty($insurances)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h3>No insurance policies found</h3>
                        <p>Start by adding your first insurance policy</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Insurance Name</th>
                                <th>Provider</th>
                                <th>Start Date</th>
                                <th>Expiration</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($insurances as $ins): 
                                $date_to = strtotime($ins['date_to']);
                                $today = strtotime('today');
                                $days_diff = ($date_to - $today) / (60 * 60 * 24);
                                
                                if($days_diff < 0) {
                                    $status = 'Expired';
                                    $statusClass = 'status-expired';
                                } elseif($days_diff <= 30) {
                                    $status = 'Expiring Soon';
                                    $statusClass = 'status-expiring';
                                } else {
                                    $status = 'Active';
                                    $statusClass = 'status-active';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div style="font-weight: 600;"><?php echo htmlspecialchars($ins['insurance_name']); ?></div>
                                        <small style="color: #6b7280;"><?php echo htmlspecialchars($ins['category']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($ins['provider']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ins['date_from'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($ins['date_to'])); ?></td>
                                    <td>₱<?php echo number_format($ins['amount'], 2); ?></td>
                                    <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-info btn-sm" onclick="viewRecord(<?php echo htmlspecialchars(json_encode($ins)); ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="editRecord(<?php echo htmlspecialchars(json_encode($ins)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <a href="?delete=<?php echo $ins['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this insurance policy?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <!-- Previous Button -->
                    <?php if($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                           class="page-link page-nav">
                            <i class="fas fa-chevron-left"></i> Previous
                        </a>
                    <?php else: ?>
                        <span class="page-link page-nav disabled">
                            <i class="fas fa-chevron-left"></i> Previous
                        </span>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $range = 2;
                    $start = max(1, $page - $range);
                    $end = min($total_pages, $page + $range);

                    if ($start > 1) {
                        echo '<a href="?page=1&search='.urlencode($search).'&category='.urlencode($category).'" class="page-link">1</a>';
                        if ($start > 2) echo '<span class="page-link disabled">...</span>';
                    }

                    for ($i = $start; $i <= $end; $i++) {
                        echo '<a href="?page='.$i.'&search='.urlencode($search).'&category='.urlencode($category).'" 
                                 class="page-link '.($i == $page ? 'active' : '').'">'.$i.'</a>';
                    }

                    if ($end < $total_pages) {
                        if ($end < $total_pages - 1) echo '<span class="page-link disabled">...</span>';
                        echo '<a href="?page='.$total_pages.'&search='.urlencode($search).'&category='.urlencode($category).'" 
                                 class="page-link">'.$total_pages.'</a>';
                    }
                    ?>

                    <!-- Next Button -->
                    <?php if($page < $total_pages): ?>
                        <a href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>" 
                           class="page-link page-nav">
                            Next <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="page-link page-nav disabled">
                            Next <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Page Info -->
                <div style="padding: 8px 20px; text-align: center; font-size: 13px; color: var(--gray-700); border-top: 1px solid var(--gray-200);">
                    Showing page <?php echo $page; ?> of <?php echo $total_pages; ?> 
                    (<?php echo number_format($total_records); ?> total records)
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Insurance</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="Building">Building</option>
                                <option value="Lot">Lot</option>
                                <option value="Equipment">Equipment</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Insurance Name</label>
                            <input type="text" name="insurance_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="date_from" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="date_to" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Document (Optional)</label>
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_insurance" class="btn btn-primary">Add Insurance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Insurance</h3>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="grid-2">
                        <div class="form-group">
                            <label class="form-label">Category</label>
                            <select name="category" id="edit_category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="Building">Building</option>
                                <option value="Lot">Lot</option>
                                <option value="Equipment">Equipment</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Insurance Name</label>
                            <input type="text" name="insurance_name" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Provider</label>
                            <input type="text" name="provider" id="edit_provider" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="date_from" id="edit_date_from" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Expiration Date</label>
                            <input type="date" name="date_to" id="edit_date_to" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Amount</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Document (Optional)</label>
                        <div id="current_doc" class="mb-2" style="font-size: 14px;"></div>
                        <input type="file" name="document" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.xls,.xlsx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_insurance" class="btn btn-primary">Update Insurance</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Insurance Details</h3>
            </div>
            <div class="modal-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <div class="form-control" style="background: #f9fafb;" id="view_category"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Insurance Name</label>
                        <div class="form-control" style="background: #f9fafb;" id="view_name"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Provider</label>
                        <div class="form-control" style="background: #f9fafb;" id="view_provider"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Start Date</label>
                        <div class="form-control" style="background: #f9fafb;" id="view_date_from"></div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Expiration Date</label>
                        <div class="form-control" style="background: #f9fafb;" id="view_date_to"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Amount</label>
                    <div class="form-control" style="background: #f9fafb; font-weight: bold;" id="view_amount"></div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <div class="form-control" style="background: #f9fafb; min-height: 80px;" id="view_description"></div>
                </div>
                
                <div class="form-group" id="view_document_container" style="display: none;">
                    <label class="form-label">Document</label>
                    <div id="view_document" class="form-control" style="background: #f9fafb;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="closeModal('viewModal')">Close</button>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        // View record
        function viewRecord(data) {
            document.getElementById('view_category').textContent = data.category;
            document.getElementById('view_name').textContent = data.insurance_name;
            document.getElementById('view_provider').textContent = data.provider;
            document.getElementById('view_date_from').textContent = formatDate(data.date_from);
            document.getElementById('view_date_to').textContent = formatDate(data.date_to);
            document.getElementById('view_amount').textContent = '₱' + parseFloat(data.amount).toLocaleString('en-US', {minimumFractionDigits: 2});
            document.getElementById('view_description').textContent = data.description || 'No description';
            
            const docContainer = document.getElementById('view_document_container');
            const docElement = document.getElementById('view_document');
            
            if(data.document_name && data.document_path) {
                docContainer.style.display = 'block';
                docElement.innerHTML = `<a href="${data.document_path}" target="_blank" style="color: #3b82f6;">
                    <i class="fas fa-file"></i> ${data.document_name}
                </a>`;
            } else {
                docContainer.style.display = 'none';
            }
            
            openModal('viewModal');
        }
        
        // Edit record
        function editRecord(data) {
            document.getElementById('edit_id').value = data.id;
            document.getElementById('edit_category').value = data.category;
            document.getElementById('edit_name').value = data.insurance_name;
            document.getElementById('edit_provider').value = data.provider;
            document.getElementById('edit_date_from').value = data.date_from;
            document.getElementById('edit_date_to').value = data.date_to;
            document.getElementById('edit_amount').value = data.amount;
            document.getElementById('edit_description').value = data.description || '';
            
            const currentDoc = document.getElementById('current_doc');
            if(data.document_name && data.document_path) {
                currentDoc.innerHTML = `<span>Current: <a href="${data.document_path}" target="_blank">${data.document_name}</a></span>`;
            } else {
                currentDoc.innerHTML = '<span>No document uploaded</span>';
            }
            
            openModal('editModal');
        }
        
        // Format date
        function formatDate(dateString) {
            if(!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }
        
        // Close modal on outside click
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if(e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    if(modal.style.display === 'flex') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                    }
                });
            }
        });
        
        // Auto-hide alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.3s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 3000);
    </script>
</body>
</html>