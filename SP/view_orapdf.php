<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Handle form submission for adding new ordinance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_ordinance') {
    $ordinance_number = trim($_POST['ordinance_number']);
    $title = trim($_POST['title']);
    $proponent = trim($_POST['proponent']);
    $date_approved = trim($_POST['date_approved']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    
    // Handle PDF file upload
    $pdf_file = null;
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/ordinances/';
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        if ($file_extension === 'pdf') {
            $new_filename = uniqid('ord_') . '_' . time() . '.pdf';
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                $pdf_file = $upload_path;
            } else {
                $_SESSION['error_message'] = "Error uploading PDF file.";
            }
        } else {
            $_SESSION['error_message'] = "Only PDF files are allowed.";
        }
    }
    
    $stmt = $conn->prepare("INSERT INTO ordinances (ordinance_number, title, proponent, date_approved, description, status, pdf_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $ordinance_number, $title, $proponent, $date_approved, $description, $status, $pdf_file);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Ordinance added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding ordinance: " . $conn->error;
    }
    $stmt->close();
    header("Location: ordinances.php");
    exit();
}

// Handle delete ordinance
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Get PDF file path before deleting
    $stmt = $conn->prepare("SELECT pdf_file FROM ordinances WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete PDF file if exists
        if (!empty($row['pdf_file']) && file_exists($row['pdf_file'])) {
            unlink($row['pdf_file']);
        }
    }
    $stmt->close();
    
    // Delete ordinance record
    $stmt = $conn->prepare("DELETE FROM ordinances WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Ordinance deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting ordinance: " . $conn->error;
    }
    $stmt->close();
    header("Location: ordinances.php");
    exit();
}

// Get statistics
$approved_count = 0;
$pending_count = 0;

$result = $conn->query("SELECT status, COUNT(*) as count FROM ordinances GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'approved') {
        $approved_count = $row['count'];
    } elseif ($row['status'] === 'pending') {
        $pending_count = $row['count'];
    }
}

// Get all ordinances
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

$query = "SELECT * FROM ordinances WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (ordinance_number LIKE ? OR title LIKE ? OR proponent LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($filter_status)) {
    $query .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$query .= " ORDER BY date_approved DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$ordinances_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ordinances - Sangguniang Panlalawigan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3a6c;
            --secondary: #e63946;
            --accent: #2a9d8f;
            --light: #f1faee;
            --dark: #1d3557;
            --gray: #8d99ae;
            --light-gray: #edf2f4;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .sidebar-item {
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-approved {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-draft {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .pdf-modal-content {
            max-width: 95%;
            max-height: 95vh;
            width: 1200px;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.5rem 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 58, 108, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .pdf-viewer-container {
            width: 100%;
            height: 80vh;
            background-color: #525659;
        }

        .pdf-viewer-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
        }

        .file-input-wrapper input[type=file] {
            position: absolute;
            left: -9999px;
        }

        .file-input-label {
            display: flex;
            align-items: center;
            padding: 0.5rem 0.75rem;
            border: 2px dashed #d1d5db;
            border-radius: 0.375rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .file-input-label:hover {
            border-color: var(--primary);
            background-color: #f9fafb;
        }

        .file-input-label i {
            margin-right: 0.5rem;
            color: #6b7280;
        }

        .file-name {
            color: #059669;
            font-weight: 500;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
    <?php include 'bar/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'bar/header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Ordinances</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage legislative ordinances</p>
                </div>
                <button onclick="openAddModal()" class="btn-primary text-white px-5 py-2.5 rounded-lg font-medium text-sm">
                    <i class="fas fa-plus mr-2"></i>Add New Ordinance
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Approved</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $approved_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-clock text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Pending</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $pending_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-white">
                <div class="card-header p-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">All Ordinances</h2>
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ordinances..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select name="filter_status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="approved" <?php echo $filter_status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="draft" <?php echo $filter_status === 'draft' ? 'selected' : ''; ?>>Draft</option>
                            <option value="rejected" <?php echo $filter_status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                        <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordinance No.</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proponent</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Approved</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($ordinances_result->num_rows > 0): ?>
                                    <?php while ($ordinance = $ordinances_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 font-semibold text-gray-900"><?php echo htmlspecialchars($ordinance['ordinance_number']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($ordinance['title']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($ordinance['proponent']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo date('M d, Y', strtotime($ordinance['date_approved'])); ?></td>
                                            <td class="py-3 px-4">
                                                <?php
                                                    $status_class = 'status-' . $ordinance['status'];
                                                    $status_icon = '';
                                                    $status_text = ucfirst($ordinance['status']);
                                                    
                                                    switch($ordinance['status']) {
                                                        case 'approved':
                                                            $status_icon = 'fa-check-circle';
                                                            break;
                                                        case 'pending':
                                                            $status_icon = 'fa-clock';
                                                            break;
                                                        case 'draft':
                                                            $status_icon = 'fa-file-alt';
                                                            break;
                                                        case 'rejected':
                                                            $status_icon = 'fa-times-circle';
                                                            break;
                                                    }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?> mr-1"></i> <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php if (!empty($ordinance['pdf_file']) && file_exists($ordinance['pdf_file'])): ?>
                                                    <button onclick="viewPDF('<?php echo htmlspecialchars($ordinance['pdf_file']); ?>', '<?php echo htmlspecialchars($ordinance['title']); ?>')" class="text-blue-600 hover:text-blue-800 mr-3" title="View PDF">
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button class="text-gray-400 mr-3 cursor-not-allowed" title="No PDF available" disabled>
                                                        <i class="fas fa-file-pdf"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <button onclick="editOrdinance(<?php echo $ordinance['id']; ?>)" class="text-green-600 hover:text-green-800 mr-3" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteOrdinance(<?php echo $ordinance['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            No ordinances found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Ordinance Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Add New Ordinance</h2>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="addForm" method="POST" action="ordinances.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_ordinance">
                    
                    <div class="form-group">
                        <label class="form-label">Ordinance Number</label>
                        <input type="text" name="ordinance_number" class="form-input" placeholder="e.g., 2025-001" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" placeholder="Enter ordinance title" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Proponent</label>
                            <input type="text" name="proponent" class="form-input" placeholder="e.g., Hon. Dela Cruz" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date Approved</label>
                            <input type="date" name="date_approved" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="">Select Status</option>
                            <option value="draft">Draft</option>
                            <option value="pending">Pending</option>
                            <option value="approved" selected>Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">PDF Document</label>
                        <div class="file-input-wrapper">
                            <input type="file" name="pdf_file" id="pdfFile" accept=".pdf" onchange="updateFileName(this)">
                            <label for="pdfFile" class="file-input-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span id="fileNameDisplay">Choose PDF file or drag here</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 10MB. Only PDF files are allowed.</p>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" placeholder="Enter ordinance description"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-save mr-2"></i>Save Ordinance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    <div id="pdfModal" class="modal">
        <div class="modal-content pdf-modal-content">
            <div class="p-4 bg-gray-800 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-white" id="pdfTitle">View Ordinance</h2>
                <button onclick="closePDFModal()" class="text-white hover:text-gray-300">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="pdf-viewer-container">
                <iframe id="pdfViewer" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('show');
        }

        function closeAddModal() {
            document.getElementById('addModal').classList.remove('show');
            document.getElementById('addForm').reset();
            document.getElementById('fileNameDisplay').textContent = 'Choose PDF file or drag here';
        }

        function viewPDF(pdfPath, title) {
            document.getElementById('pdfTitle').textContent = title;
            document.getElementById('pdfViewer').src = pdfPath;
            document.getElementById('pdfModal').classList.add('show');
        }

        function closePDFModal() {
            document.getElementById('pdfModal').classList.remove('show');
            document.getElementById('pdfViewer').src = '';
        }

        function updateFileName(input) {
            const fileName = input.files[0] ? input.files[0].name : 'Choose PDF file or drag here';
            const display = document.getElementById('fileNameDisplay');
            
            if (input.files[0]) {
                display.innerHTML = '<span class="file-name"><i class="fas fa-file-pdf mr-1"></i>' + fileName + '</span>';
            } else {
                display.textContent = 'Choose PDF file or drag here';
            }
        }

        window.onclick = function(event) {
            const addModal = document.getElementById('addModal');
            const pdfModal = document.getElementById('pdfModal');
            
            if (event.target === addModal) {
                closeAddModal();
            }
            if (event.target === pdfModal) {
                closePDFModal();
            }
        }

        function deleteOrdinance(id) {
            if (confirm('Are you sure you want to delete this ordinance? This will also delete the associated PDF file.')) {
                window.location.href = 'ordinances.php?delete_id=' + id;
            }
        }

        function editOrdinance(id) {
            alert('Edit ordinance #' + id + ' - This feature can be implemented');
        }
    </script>
</body>
</html>