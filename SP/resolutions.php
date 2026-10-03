<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Handle form submission for adding new resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_resolution') {
    $resolution_number = trim($_POST['resolution_number']);
    $title = trim($_POST['title']);
    $proponent = trim($_POST['proponent']);
    $date_approved = trim($_POST['date_approved']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    
    // Handle multiple file uploads
    $uploaded_files = [];
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $total_files = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['images']['error'][$i] === 0) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                // Check if file is JPG/JPEG
                if (in_array($file_ext, ['jpg', 'jpeg'])) {
                    $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                    $upload_path = 'uploads/resolutions/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $uploaded_files[] = $upload_path;
                    }
                }
            }
        }
    }
    
    $files_json = !empty($uploaded_files) ? json_encode($uploaded_files) : null;
    
    $stmt = $conn->prepare("INSERT INTO resolutions (resolution_number, title, proponent, date_approved, description, status, pdf_file) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $resolution_number, $title, $proponent, $date_approved, $description, $status, $files_json);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Resolution added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding resolution: " . $conn->error;
    }
    $stmt->close();
    header("Location: resolutions.php");
    exit();
}

// Handle update resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_resolution') {
    $id = intval($_POST['id']);
    $resolution_number = trim($_POST['resolution_number']);
    $title = trim($_POST['title']);
    $proponent = trim($_POST['proponent']);
    $date_approved = trim($_POST['date_approved']);
    $description = trim($_POST['description']);
    $status = trim($_POST['status']);
    
    // Get existing files
    $existing_files_query = $conn->prepare("SELECT pdf_file FROM resolutions WHERE id = ?");
    $existing_files_query->bind_param("i", $id);
    $existing_files_query->execute();
    $result = $existing_files_query->get_result();
    $existing_data = $result->fetch_assoc();
    $existing_files = $existing_data['pdf_file'] ? json_decode($existing_data['pdf_file'], true) : [];
    
    // Handle new file uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        $total_files = count($_FILES['images']['name']);
        
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['images']['error'][$i] === 0) {
                $file_name = $_FILES['images']['name'][$i];
                $file_tmp = $_FILES['images']['tmp_name'][$i];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, ['jpg', 'jpeg'])) {
                    $new_file_name = uniqid() . '_' . time() . '.' . $file_ext;
                    $upload_path = 'uploads/resolutions/' . $new_file_name;
                    
                    if (move_uploaded_file($file_tmp, $upload_path)) {
                        $existing_files[] = $upload_path;
                    }
                }
            }
        }
    }
    
    $files_json = !empty($existing_files) ? json_encode($existing_files) : null;
    
    $stmt = $conn->prepare("UPDATE resolutions SET resolution_number = ?, title = ?, proponent = ?, date_approved = ?, description = ?, status = ?, pdf_file = ? WHERE id = ?");
    $stmt->bind_param("sssssssi", $resolution_number, $title, $proponent, $date_approved, $description, $status, $files_json, $id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Resolution updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating resolution: " . $conn->error;
    }
    $stmt->close();
    header("Location: resolutions.php");
    exit();
}

// Handle delete resolution
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    
    // Get files to delete
    $files_query = $conn->prepare("SELECT pdf_file FROM resolutions WHERE id = ?");
    $files_query->bind_param("i", $delete_id);
    $files_query->execute();
    $result = $files_query->get_result();
    $data = $result->fetch_assoc();
    
    if ($data['pdf_file']) {
        $files = json_decode($data['pdf_file'], true);
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
    
    $stmt = $conn->prepare("DELETE FROM resolutions WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Resolution deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting resolution: " . $conn->error;
    }
    $stmt->close();
    header("Location: resolutions.php");
    exit();
}

// Handle delete individual image
if (isset($_GET['delete_image'])) {
    $resolution_id = intval($_GET['resolution_id']);
    $image_path = $_GET['delete_image'];
    
    // Get current files
    $files_query = $conn->prepare("SELECT pdf_file FROM resolutions WHERE id = ?");
    $files_query->bind_param("i", $resolution_id);
    $files_query->execute();
    $result = $files_query->get_result();
    $data = $result->fetch_assoc();
    
    if ($data['pdf_file']) {
        $files = json_decode($data['pdf_file'], true);
        $files = array_filter($files, function($file) use ($image_path) {
            return $file !== $image_path;
        });
        
        // Delete physical file
        if (file_exists($image_path)) {
            unlink($image_path);
        }
        
        $files_json = !empty($files) ? json_encode(array_values($files)) : null;
        
        $stmt = $conn->prepare("UPDATE resolutions SET pdf_file = ? WHERE id = ?");
        $stmt->bind_param("si", $files_json, $resolution_id);
        $stmt->execute();
        $stmt->close();
    }
    
    $_SESSION['success_message'] = "Image deleted successfully!";
    header("Location: resolutions.php");
    exit();
}

// Get statistics
$approved_count = 0;
$pending_count = 0;

$result = $conn->query("SELECT status, COUNT(*) as count FROM resolutions GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'approved') {
        $approved_count = $row['count'];
    } elseif ($row['status'] === 'pending') {
        $pending_count = $row['count'];
    }
}

// Get all resolutions
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

$query = "SELECT * FROM resolutions WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (resolution_number LIKE ? OR title LIKE ? OR proponent LIKE ?)";
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
$resolutions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolutions - Sangguniang Panlalawigan</title>
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
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
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

        .image-preview {
            position: relative;
            display: inline-block;
            margin: 0.5rem;
        }

        .image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
            border: 2px solid #e5e7eb;
        }

        .image-preview .delete-btn {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ef4444;
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 0.75rem;
        }

        .image-preview .delete-btn:hover {
            background-color: #dc2626;
        }

        .image-gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .image-gallery img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .image-gallery img:hover {
            transform: scale(1.05);
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
                    <h1 class="text-2xl font-bold text-gray-800">Resolutions</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage legislative resolutions</p>
                </div>
                <!-- <button onclick="openAddModal()" class="btn-primary text-white px-5 py-2.5 rounded-lg font-medium text-sm">
                    <i class="fas fa-plus mr-2"></i>Add New Resolution
                </button> -->
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
                    <h2 class="text-lg font-semibold text-gray-800">All Resolutions</h2>
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search resolutions..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
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
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Resolution No.</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Proponent</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Approved</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Images</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($resolutions_result->num_rows > 0): ?>
                                    <?php while ($resolution = $resolutions_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 font-semibold text-gray-900"><?php echo htmlspecialchars($resolution['resolution_number']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($resolution['title']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($resolution['proponent']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo date('M d, Y', strtotime($resolution['date_approved'])); ?></td>
                                            <td class="py-3 px-4 text-gray-700">
                                                <?php 
                                                    $images = $resolution['pdf_file'] ? json_decode($resolution['pdf_file'], true) : [];
                                                    echo count($images) . ' image(s)';
                                                ?>
                                            </td>
                                            <td class="py-3 px-4">
                                                <?php
                                                    $status_class = 'status-' . $resolution['status'];
                                                    $status_icon = '';
                                                    $status_text = ucfirst($resolution['status']);
                                                    
                                                    switch($resolution['status']) {
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
                                                <button onclick='viewResolution(<?php echo json_encode($resolution); ?>)' class="text-blue-600 hover:text-blue-800 mr-3" title="View">
                                                    View
                                                </button>
                                                <!-- <button onclick='editResolution(<?php echo json_encode($resolution); ?>)' class="text-green-600 hover:text-green-800 mr-3" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteResolution(<?php echo $resolution['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button> -->
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="py-8 px-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            No resolutions found
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

    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Add New Resolution</h2>
                    <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="addForm" method="POST" action="resolutions.php" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_resolution">
                    
                    <div class="form-group">
                        <label class="form-label">Resolution Number</label>
                        <input type="text" name="resolution_number" class="form-input" placeholder="e.g., 2025-001" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-input" placeholder="Enter resolution title" required>
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
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" placeholder="Enter resolution description"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload Images (JPG only)</label>
                        <input type="file" name="images[]" class="form-input" accept=".jpg,.jpeg" multiple>
                        <p class="text-xs text-gray-500 mt-1">You can select multiple JPG/JPEG images</p>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-save mr-2"></i>Save Resolution
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">View Resolution</h2>
                    <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <div id="viewContent">
                    <!-- Content will be populated by JavaScript -->
                </div>

                <div class="flex justify-end mt-6">
<button onclick="closeViewModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
Close
</button>
</div>
</div>
</div>
</div>

<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Edit Resolution</h2>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form id="editForm" method="POST" action="resolutions.php" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_resolution">
                <input type="hidden" name="id" id="edit_id">
                
                <div class="form-group">
                    <label class="form-label">Resolution Number</label>
                    <input type="text" name="resolution_number" id="edit_resolution_number" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" id="edit_title" class="form-input" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Proponent</label>
                        <input type="text" name="proponent" id="edit_proponent" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Approved</label>
                        <input type="date" name="date_approved" id="edit_date_approved" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" id="edit_status" class="form-select" required>
                        <option value="draft">Draft</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" id="edit_description" class="form-textarea"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Current Images</label>
                    <div id="current_images" class="flex flex-wrap gap-2">
                        <!-- Images will be populated by JavaScript -->
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Add More Images (JPG only)</label>
                    <input type="file" name="images[]" class="form-input" accept=".jpg,.jpeg" multiple>
                    <p class="text-xs text-gray-500 mt-1">You can add multiple JPG/JPEG images</p>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Update Resolution
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Add Modal Functions
    function openAddModal() {
        document.getElementById('addModal').classList.add('show');
    }

    function closeAddModal() {
        document.getElementById('addModal').classList.remove('show');
        document.getElementById('addForm').reset();
    }

    // View Modal Functions
    function viewResolution(resolution) {
        const images = resolution.pdf_file ? JSON.parse(resolution.pdf_file) : [];
        
        let imagesHTML = '';
        if (images.length > 0) {
            imagesHTML = '<div class="image-gallery">';
            images.forEach(image => {
                imagesHTML += `<img src="${image}" alt="Resolution Image" onclick="window.open('${image}', '_blank')">`;
            });
            imagesHTML += '</div>';
        } else {
            imagesHTML = '<p class="text-gray-500 text-sm">No images uploaded</p>';
        }

        const content = `
            <div class="space-y-4">
                <div>
                    <label class="font-semibold text-gray-700">Resolution Number:</label>
                    <p class="text-gray-900">${resolution.resolution_number}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Title:</label>
                    <p class="text-gray-900">${resolution.title}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Proponent:</label>
                    <p class="text-gray-900">${resolution.proponent}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Date Approved:</label>
                    <p class="text-gray-900">${new Date(resolution.date_approved).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Status:</label>
                    <p class="text-gray-900 capitalize">${resolution.status}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Description:</label>
                    <p class="text-gray-900">${resolution.description || 'No description provided'}</p>
                </div>
                <div>
                    <label class="font-semibold text-gray-700">Images:</label>
                    ${imagesHTML}
                </div>
            </div>
        `;

        document.getElementById('viewContent').innerHTML = content;
        document.getElementById('viewModal').classList.add('show');
    }

    function closeViewModal() {
        document.getElementById('viewModal').classList.remove('show');
    }

    // Edit Modal Functions
    function editResolution(resolution) {
        document.getElementById('edit_id').value = resolution.id;
        document.getElementById('edit_resolution_number').value = resolution.resolution_number;
        document.getElementById('edit_title').value = resolution.title;
        document.getElementById('edit_proponent').value = resolution.proponent;
        document.getElementById('edit_date_approved').value = resolution.date_approved;
        document.getElementById('edit_status').value = resolution.status;
        document.getElementById('edit_description').value = resolution.description || '';

        // Display current images
        const images = resolution.pdf_file ? JSON.parse(resolution.pdf_file) : [];
        const currentImagesDiv = document.getElementById('current_images');
        
        if (images.length > 0) {
            currentImagesDiv.innerHTML = '';
            images.forEach(image => {
                const imagePreview = document.createElement('div');
                imagePreview.className = 'image-preview';
                imagePreview.innerHTML = `
                    <img src="${image}" alt="Resolution Image">
                    <div class="delete-btn" onclick="deleteImage(${resolution.id}, '${image}')">
                        <i class="fas fa-times"></i>
                    </div>
                `;
                currentImagesDiv.appendChild(imagePreview);
            });
        } else {
            currentImagesDiv.innerHTML = '<p class="text-gray-500 text-sm">No images uploaded yet</p>';
        }

        document.getElementById('editModal').classList.add('show');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.remove('show');
        document.getElementById('editForm').reset();
    }

    function deleteImage(resolutionId, imagePath) {
        if (confirm('Are you sure you want to delete this image?')) {
            window.location.href = `resolutions.php?delete_image=${encodeURIComponent(imagePath)}&resolution_id=${resolutionId}`;
        }
    }

    // Delete Resolution
    function deleteResolution(id) {
        if (confirm('Are you sure you want to delete this resolution? All associated images will also be deleted.')) {
            window.location.href = 'resolutions.php?delete_id=' + id;
        }
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const addModal = document.getElementById('addModal');
        const viewModal = document.getElementById('viewModal');
        const editModal = document.getElementById('editModal');
        
        if (event.target === addModal) {
            closeAddModal();
        } else if (event.target === viewModal) {
            closeViewModal();
        } else if (event.target === editModal) {
            closeEditModal();
        }
    }

    // Mobile menu toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('button.md\\:hidden');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                document.querySelector('.sidebar').classList.toggle('hidden');
            });
        }
    });
</script>
</body>
</html>