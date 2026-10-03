<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Handle form submission for adding new session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_session') {
    $session_number = trim($_POST['session_number']);
    $session_date = trim($_POST['session_date']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $venue = trim($_POST['venue']);
    $agenda = trim($_POST['agenda']);
    $status = trim($_POST['status']);
    
    $stmt = $conn->prepare("INSERT INTO sessions (session_number, session_date, start_time, end_time, venue, agenda, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $session_number, $session_date, $start_time, $end_time, $venue, $agenda, $status);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Session added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding session: " . $conn->error;
    }
    $stmt->close();
    header("Location: session.php");
    exit();
}

// Handle delete session
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Session deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting session: " . $conn->error;
    }
    $stmt->close();
    header("Location: session.php");
    exit();
}

// Get statistics
$completed_count = 0;
$ongoing_count = 0;

$result = $conn->query("SELECT status, COUNT(*) as count FROM sessions GROUP BY status");
while ($row = $result->fetch_assoc()) {
    if ($row['status'] === 'completed') {
        $completed_count = $row['count'];
    } elseif ($row['status'] === 'ongoing') {
        $ongoing_count = $row['count'];
    }
}

// Get all sessions
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

$query = "SELECT * FROM sessions WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (session_number LIKE ? OR venue LIKE ? OR agenda LIKE ?)";
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

$query .= " ORDER BY session_date DESC, start_time DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$sessions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions - Sangguniang Panlalawigan</title>
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
        
        .sidebar-subitem {
            padding-left: 2.5rem;
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

        .session-card {
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .session-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(26, 58, 108, 0.15);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-ongoing {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-scheduled {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Modal Styles */
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
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
    <?php include 'bar/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'bar/header.php'; ?>
        
        <!-- Session Content -->
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <!-- Success/Error Messages -->
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

            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Sessions</h1>
                    <p class="text-gray-600 text-sm mt-1">Manage legislative sessions</p>
                </div>
                <button onclick="openAddSessionModal()" class="btn-primary text-white px-5 py-2.5 rounded-lg font-medium text-sm">
                    <i class="fas fa-plus mr-2"></i>Add New Session
                </button>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Completed</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $completed_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-spinner text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Ongoing</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $ongoing_count; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sessions List -->
            <div class="card bg-white">
                <div class="card-header p-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">All Sessions</h2>
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search sessions..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select name="filter_status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">All Status</option>
                            <option value="completed" <?php echo $filter_status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="ongoing" <?php echo $filter_status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                            <option value="scheduled" <?php echo $filter_status === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                            <option value="cancelled" <?php echo $filter_status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session #</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($sessions_result->num_rows > 0): ?>
                                    <?php while ($session = $sessions_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 font-semibold text-gray-900"><?php echo htmlspecialchars($session['session_number']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo date('F d, Y', strtotime($session['session_date'])); ?></td>
                                            <td class="py-3 px-4 text-gray-700">
                                                <?php 
                                                    echo date('g:i A', strtotime($session['start_time'])) . ' - ' . 
                                                         date('g:i A', strtotime($session['end_time'])); 
                                                ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($session['venue']); ?></td>
                                            <td class="py-3 px-4">
                                                <?php
                                                    $status_class = 'status-' . $session['status'];
                                                    $status_icon = '';
                                                    $status_text = ucfirst($session['status']);
                                                    
                                                    switch($session['status']) {
                                                        case 'completed':
                                                            $status_icon = 'fa-check-circle';
                                                            break;
                                                        case 'ongoing':
                                                            $status_icon = 'fa-spinner';
                                                            break;
                                                        case 'scheduled':
                                                            $status_icon = 'fa-clock';
                                                            break;
                                                        case 'cancelled':
                                                            $status_icon = 'fa-times-circle';
                                                            break;
                                                    }
                                                ?>
                                                <span class="status-badge <?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?> mr-1"></i> <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4">
                                                <button onclick="viewSession(<?php echo $session['id']; ?>)" class="text-blue-600 hover:text-blue-800 mr-3" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button onclick="editSession(<?php echo $session['id']; ?>)" class="text-green-600 hover:text-green-800 mr-3" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteSession(<?php echo $session['id']; ?>)" class="text-red-600 hover:text-red-800" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            No sessions found
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

    <!-- Add Session Modal -->
    <div id="addSessionModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">Add New Session</h2>
                    <button onclick="closeAddSessionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                
                <form id="addSessionForm" method="POST" action="session.php">
                    <input type="hidden" name="action" value="add_session">
                    
                    <div class="form-group">
                        <label class="form-label">Session Number</label>
                        <input type="text" name="session_number" class="form-input" placeholder="e.g., #14" required>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Date</label>
                            <input type="date" name="session_date" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="scheduled">Scheduled</option>
                                <option value="ongoing">Ongoing</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Venue</label>
                        <input type="text" name="venue" class="form-input" placeholder="e.g., Session Hall" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Agenda / Description</label>
                        <textarea name="agenda" class="form-textarea" placeholder="Enter session agenda or description"></textarea>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeAddSessionModal()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg font-medium">
                            <i class="fas fa-save mr-2"></i>Save Session
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Modal functions
        function openAddSessionModal() {
            document.getElementById('addSessionModal').classList.add('show');
        }

        function closeAddSessionModal() {
            document.getElementById('addSessionModal').classList.remove('show');
            document.getElementById('addSessionForm').reset();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('addSessionModal');
            if (event.target === modal) {
                closeAddSessionModal();
            }
        }

        // Delete session with confirmation
        function deleteSession(id) {
            if (confirm('Are you sure you want to delete this session?')) {
                window.location.href = 'session.php?delete_id=' + id;
            }
        }

        // View session (placeholder - you can implement this)
        function viewSession(id) {
            alert('View session #' + id + ' - This feature can be implemented');
        }

        // Edit session (placeholder - you can implement this)
        function editSession(id) {
            alert('Edit session #' + id + ' - This feature can be implemented');
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