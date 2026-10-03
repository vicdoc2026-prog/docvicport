<?php
session_start();
require_once '../electric/config/check-session.php';
require_once '../electric/config/conn_pdo.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $upload_dir = 'uploads/documents/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file = $_FILES['document'];
    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_size = $file['size'];
    $file_error = $file['error'];
    
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed_ext = ['pdf'];
    
    if (in_array($file_ext, $allowed_ext)) {
        if ($file_error === 0) {
            if ($file_size < 10000000) { // 10MB limit
                $file_name_new = uniqid('doc_', true) . '.' . $file_ext;
                $file_destination = $upload_dir . $file_name_new;
                
                if (move_uploaded_file($file_tmp, $file_destination)) {
                    $document_title = $_POST['document_title'] ?? $file_name;
                    $document_description = $_POST['document_description'] ?? '';
                    
                    $stmt = $pdo->prepare("INSERT INTO documents (user_id, title, description, file_name, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$user_id, $document_title, $document_description, $file_name, $file_destination, $file_size]);
                    
                    // REDIRECT after successful upload to prevent resubmission
                    $_SESSION['success_message'] = "Document uploaded successfully!";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $_SESSION['error_message'] = "Failed to upload document.";
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                }
            } else {
                $_SESSION['error_message'] = "File size is too large. Maximum 10MB allowed.";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Error uploading file.";
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Only PDF files are allowed.";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Handle document deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $doc_id = $_GET['delete'];
    
    // Get file path before deleting
    $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ? AND user_id = ?");
    $stmt->execute([$doc_id, $user_id]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        // Delete file from server
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$doc_id, $user_id]);
        
        // REDIRECT after successful deletion
        $_SESSION['success_message'] = "Document deleted successfully!";
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Retrieve and clear session messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all documents
$stmt = $pdo->prepare("SELECT * FROM documents WHERE user_id = ? ORDER BY uploaded_at DESC");
$stmt->execute([$user_id]);
$documents = $stmt->fetchAll();

$total_documents = count($documents);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Documents - Document Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .card-hover {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 50;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
        <div class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-800">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <h1 class="text-2xl font-bold text-gray-800 ml-0">
                            <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                            Farm Documents
                        </h1>
                    </div>
                    <button onclick="openUploadModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition">
                        <i class="fas fa-upload mr-2"></i>Upload Document
                    </button>
                </div>
            </div>
        </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Alert Messages -->
        <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Statistics Card -->
        <div class="bg-white rounded-xl shadow-sm p-6 card-hover mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Documents <span class="text-xs text-gray-400">(as of <?php echo date('M d, Y'); ?>)</span></p>
                    <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo $total_documents; ?></h3>
                    <p class="text-blue-600 text-xs font-medium mt-2">
                        <i class="fas fa-file-alt mr-1"></i> PDF documents stored
                    </p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Documents Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if (empty($documents)): ?>
                <div class="col-span-full text-center py-12">
                    <i class="fas fa-file-alt text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500 text-lg">No documents uploaded yet</p>
                    <button onclick="openUploadModal()" class="mt-4 text-blue-600 hover:text-blue-700 font-medium">
                        Upload your first document <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($documents as $doc): ?>
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-start justify-between mb-4">
                        <div class="bg-red-100 p-3 rounded-lg">
                            <i class="fas fa-file-pdf text-red-600 text-2xl"></i>
                        </div>
                        <div class="flex space-x-2">
                            <button onclick="viewDocument('<?php echo htmlspecialchars($doc['file_path']); ?>', '<?php echo htmlspecialchars($doc['title']); ?>')" 
                                    class="text-blue-600 hover:text-blue-700">
                                <i class="fas fa-eye"></i>
                            </button>
                            <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" download 
                               class="text-green-600 hover:text-green-700">
                                <i class="fas fa-download"></i>
                            </a>
                            <button onclick="confirmDelete(<?php echo $doc['id']; ?>)" 
                                    class="text-red-600 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-800 mb-2 truncate"><?php echo htmlspecialchars($doc['title']); ?></h3>
                    <p class="text-gray-600 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars($doc['description'] ?: 'No description'); ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-500">
                        <span><i class="fas fa-calendar mr-1"></i><?php echo date('M d, Y', strtotime($doc['uploaded_at'])); ?></span>
                        <span><i class="fas fa-file mr-1"></i><?php echo round($doc['file_size'] / 1024, 2); ?> KB</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="bg-white rounded-xl shadow-xl p-8 max-w-md w-full mx-4">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Upload Document</h2>
                <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Document Title</label>
                    <input type="text" name="document_title" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">Description (Optional)</label>
                    <textarea name="document_description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-semibold mb-2">PDF File</label>
                    <input type="file" name="document" accept=".pdf" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <p class="text-gray-500 text-xs mt-1">Maximum file size: 10MB</p>
                </div>
                
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg font-medium transition">
                        <i class="fas fa-upload mr-2"></i>Upload
                    </button>
                    <button type="button" onclick="closeUploadModal()" class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-4 rounded-lg font-medium transition">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Document Modal -->
    <div id="viewModal" class="modal">
        <div class="bg-white rounded-xl shadow-xl p-4 max-w-5xl w-full mx-4" style="height: 90vh;">
            <div class="flex justify-between items-center mb-4">
                <h2 id="viewDocTitle" class="text-xl font-bold text-gray-800"></h2>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <iframe id="pdfViewer" class="w-full rounded-lg" style="height: calc(100% - 60px);"></iframe>
        </div>
    </div>

    <script>
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }

        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
        }

        function viewDocument(path, title) {
            document.getElementById('viewDocTitle').textContent = title;
            document.getElementById('pdfViewer').src = path;
            document.getElementById('viewModal').classList.add('active');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('active');
            document.getElementById('pdfViewer').src = '';
        }

        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this document?')) {
                window.location.href = '?delete=' + id;
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const uploadModal = document.getElementById('uploadModal');
            const viewModal = document.getElementById('viewModal');
            
            if (event.target == uploadModal) {
                closeUploadModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>

</body>
</html>