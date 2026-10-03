<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// -----------------------------
// Helpers
// -----------------------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect_back($msgKey = null, $msg = null) {
    if ($msgKey && $msg) $_SESSION[$msgKey] = $msg;
    header("Location: session.php");
    exit();
}

// Get user info from session
$username  = $_SESSION['username'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$role      = $_SESSION['role'] ?? '';

// -----------------------------
// CRUD handlers (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ✅ CREATE
    if ($action === 'add_session') {
        $session_number = trim($_POST['session_number'] ?? '');
        $session_date   = trim($_POST['session_date'] ?? '');
        $start_time     = trim($_POST['start_time'] ?? '');
        $end_time       = trim($_POST['end_time'] ?? '');
        $venue          = trim($_POST['venue'] ?? '');
        $agenda         = trim($_POST['agenda'] ?? '');
        $status         = trim($_POST['status'] ?? '');

        if ($session_number === '' || $session_date === '' || $start_time === '' || $end_time === '' || $venue === '' || $status === '') {
            redirect_back('error_message', 'Please fill in all required fields.');
        }

        $stmt = $conn->prepare("INSERT INTO sessions (session_number, session_date, start_time, end_time, venue, agenda, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $session_number, $session_date, $start_time, $end_time, $venue, $agenda, $status);

        if ($stmt->execute()) {
            $stmt->close();
            redirect_back('success_message', 'Session added successfully!');
        } else {
            $err = $conn->error;
            $stmt->close();
            redirect_back('error_message', "Error adding session: {$err}");
        }
    }

    // ✅ UPDATE
    if ($action === 'update_session') {
        $id             = (int)($_POST['id'] ?? 0);
        $session_number = trim($_POST['session_number'] ?? '');
        $session_date   = trim($_POST['session_date'] ?? '');
        $start_time     = trim($_POST['start_time'] ?? '');
        $end_time       = trim($_POST['end_time'] ?? '');
        $venue          = trim($_POST['venue'] ?? '');
        $agenda         = trim($_POST['agenda'] ?? '');
        $status         = trim($_POST['status'] ?? '');

        if ($id <= 0) redirect_back('error_message', 'Invalid session ID.');
        if ($session_number === '' || $session_date === '' || $start_time === '' || $end_time === '' || $venue === '' || $status === '') {
            redirect_back('error_message', 'Please fill in all required fields.');
        }

        $stmt = $conn->prepare("UPDATE sessions SET session_number=?, session_date=?, start_time=?, end_time=?, venue=?, agenda=?, status=? WHERE id=?");
        $stmt->bind_param("sssssssi", $session_number, $session_date, $start_time, $end_time, $venue, $agenda, $status, $id);

        if ($stmt->execute()) {
            $stmt->close();
            redirect_back('success_message', 'Session updated successfully!');
        } else {
            $err = $conn->error;
            $stmt->close();
            redirect_back('error_message', "Error updating session: {$err}");
        }
    }

    // ✅ DELETE (safer than GET)
    if ($action === 'delete_session') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) redirect_back('error_message', 'Invalid session ID.');

        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $stmt->close();
            redirect_back('success_message', 'Session deleted successfully!');
        } else {
            $err = $conn->error;
            $stmt->close();
            redirect_back('error_message', "Error deleting session: {$err}");
        }
    }
}

// (Optional) Backward compatibility: old GET delete
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    if ($delete_id > 0) {
        $stmt = $conn->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();
        redirect_back('success_message', 'Session deleted successfully!');
    }
}

// -----------------------------
// Statistics
// -----------------------------
$completed_count = 0;
$ongoing_count   = 0;

$result = $conn->query("SELECT status, COUNT(*) as count FROM sessions GROUP BY status");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (($row['status'] ?? '') === 'completed') $completed_count = (int)$row['count'];
        if (($row['status'] ?? '') === 'ongoing')   $ongoing_count   = (int)$row['count'];
    }
}

// -----------------------------
// List + Search + Filter
// -----------------------------
$search        = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['filter_status']) ? trim($_GET['filter_status']) : '';

$query  = "SELECT * FROM sessions WHERE 1=1";
$params = [];
$types  = "";

if ($search !== '') {
    $query .= " AND (session_number LIKE ? OR venue LIKE ? OR agenda LIKE ?)";
    $s = "%{$search}%";
    $params[] = $s; $params[] = $s; $params[] = $s;
    $types .= "sss";
}

if ($filter_status !== '') {
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
        :root{
            --primary:#1a3a6c; --secondary:#e63946; --accent:#2a9d8f;
            --light:#f1faee; --dark:#1d3557; --gray:#8d99ae; --light-gray:#edf2f4;
        }
        body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color:#f8fafc; }
        .sidebar{ background:linear-gradient(180deg,var(--primary),var(--dark)); transition:all .3s ease; }
        .sidebar-item{ transition:all .2s ease; }
        .sidebar-item:hover{ background-color:rgba(255,255,255,.1); transform:translateX(5px); }
        .sidebar-subitem{ padding-left:2.5rem; }
        .card{ box-shadow:0 4px 6px rgba(0,0,0,.05); transition:all .3s ease; border-radius:10px; overflow:hidden; }
        .card:hover{ transform:translateY(-5px); box-shadow:0 10px 15px rgba(0,0,0,.1); }
        .card-header{ border-bottom:1px solid #e2e8f0; }
        .btn-primary{ background:linear-gradient(135deg,var(--primary),var(--dark)); transition:all .3s ease; }
        .btn-primary:hover{ transform:translateY(-2px); box-shadow:0 4px 6px rgba(26,58,108,.3); }
        .status-badge{ display:inline-flex; align-items:center; padding:.25rem .75rem; border-radius:9999px; font-size:.75rem; font-weight:600; }
        .status-completed{ background-color:#d1fae5; color:#065f46; }
        .status-ongoing{ background-color:#dbeafe; color:#1e40af; }
        .status-scheduled{ background-color:#fef3c7; color:#92400e; }
        .status-cancelled{ background-color:#fee2e2; color:#991b1b; }
        .modal{ display:none; position:fixed; z-index:1000; inset:0; background-color:rgba(0,0,0,.5); }
        .modal.show{ display:flex; align-items:center; justify-content:center; }
        .modal-content{ background:#fff; border-radius:.5rem; width:92%; max-width:650px; max-height:90vh; overflow-y:auto; }
        .form-group{ margin-bottom:1rem; }
        .form-label{ display:block; font-size:.875rem; font-weight:500; color:#374151; margin-bottom:.5rem; }
        .form-input,.form-select,.form-textarea{ width:100%; padding:.5rem .75rem; border:1px solid #d1d5db; border-radius:.375rem; font-size:.875rem; }
        .form-input:focus,.form-select:focus,.form-textarea:focus{ outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(26,58,108,.1); }
        .form-textarea{ resize:vertical; min-height:100px; }
        .alert{ padding:1rem; border-radius:.5rem; margin-bottom:1rem; }
        .alert-success{ background-color:#d1fae5; color:#065f46; border:1px solid #6ee7b7; }
        .alert-error{ background-color:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
<?php include 'bar/sidebar.php'; ?>

<div class="flex-1 flex flex-col overflow-hidden">
    <?php include 'bar/header.php'; ?>

    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" id="flashMsg">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo e($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error" id="flashMsg">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo e($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Calendar</h1>
                <p class="text-gray-600 text-sm mt-1">Manage legislative Calendar</p>
            </div>

            <!-- ✅ Enable Create -->
            <button onclick="openAddSessionModal()"
                    class="btn-primary text-white px-5 py-2.5 rounded-lg font-medium text-sm">
                <i class="fas fa-plus mr-2"></i>Add New Calendar
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
                            <h3 class="text-gray-500 text-sm uppercase">Completed</h3>
                            <p class="text-2xl font-bold mt-1"><?php echo (int)$completed_count; ?></p>
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
                            <p class="text-2xl font-bold mt-1"><?php echo (int)$ongoing_count; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-white">
            <div class="card-header p-4 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">All Sessions</h2>

                <form method="GET" class="flex items-center space-x-4">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo e($search); ?>"
                               placeholder="Search sessions..."
                               class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>

                    <select name="filter_status" onchange="this.form.submit()"
                            class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
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
                        <?php if ($sessions_result && $sessions_result->num_rows > 0): ?>
                            <?php while ($session = $sessions_result->fetch_assoc()): ?>
                                <?php
                                $status = $session['status'] ?? 'scheduled';
                                $status_class = 'status-' . $status;
                                $status_text  = ucfirst($status);
                                $status_icon  = 'fa-clock';
                                if ($status === 'completed') $status_icon = 'fa-check-circle';
                                if ($status === 'ongoing')   $status_icon = 'fa-spinner';
                                if ($status === 'cancelled') $status_icon = 'fa-times-circle';
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4 font-semibold text-gray-900"><?php echo e($session['session_number']); ?></td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo date('F d, Y', strtotime($session['session_date'])); ?></td>
                                    <td class="py-3 px-4 text-gray-700">
                                        <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?>
                                    </td>
                                    <td class="py-3 px-4 text-gray-700"><?php echo e($session['venue']); ?></td>
                                    <td class="py-3 px-4">
                                        <span class="status-badge <?php echo e($status_class); ?>">
                                            <i class="fas <?php echo e($status_icon); ?> mr-1"></i> <?php echo e($status_text); ?>
                                        </span>
                                    </td>

                                    <td class="py-3 px-4 whitespace-nowrap">
                                        <?php if (!empty($session['pdf_file'])): ?>
                                            <a href="view_pdf.php?id=<?php echo (int)$session['id']; ?>#view=FitH"
                                               class="text-blue-600 hover:text-blue-800 mr-3" title="View PDF">
                                                <i class="fas fa-file-pdf mr-1"></i> View PDF
                                            </a>
                                        <?php else: ?>
                                            <span class="text-gray-400 mr-3" title="No PDF available">
                                                <i class="fas fa-file-pdf mr-1"></i> No PDF
                                            </span>
                                        <?php endif; ?>

                                        <!-- ✅ UPDATE button -->
                                        <button
                                            class="text-green-600 hover:text-green-800 mr-3"
                                            title="Edit"
                                            onclick='openEditSessionModal(<?php echo json_encode([
                                                "id" => (int)$session["id"],
                                                "session_number" => $session["session_number"],
                                                "session_date" => $session["session_date"],
                                                "start_time" => $session["start_time"],
                                                "end_time" => $session["end_time"],
                                                "venue" => $session["venue"],
                                                "agenda" => $session["agenda"],
                                                "status" => $session["status"],
                                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>)'>
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- ✅ DELETE button -->
                                        <button
                                            class="text-red-600 hover:text-red-800"
                                            title="Delete"
                                            onclick="openDeleteModal(<?php echo (int)$session['id']; ?>, '<?php echo e($session['session_number']); ?>')">
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

<!-- =========================
     CREATE MODAL
========================= -->
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
                    <button type="button" onclick="closeAddSessionModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
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

<!-- =========================
     UPDATE MODAL
========================= -->
<div id="editSessionModal" class="modal">
    <div class="modal-content">
        <div class="p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-800">Edit Session</h2>
                <button onclick="closeEditSessionModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form id="editSessionForm" method="POST" action="session.php">
                <input type="hidden" name="action" value="update_session">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Session Number</label>
                    <input type="text" name="session_number" id="edit_session_number" class="form-input" required>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Date</label>
                        <input type="date" name="session_date" id="edit_session_date" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select" required>
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
                        <input type="time" name="start_time" id="edit_start_time" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" id="edit_end_time" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Venue</label>
                    <input type="text" name="venue" id="edit_venue" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Agenda / Description</label>
                    <textarea name="agenda" id="edit_agenda" class="form-textarea"></textarea>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeEditSessionModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg font-medium">
                        <i class="fas fa-save mr-2"></i>Update Session
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =========================
     DELETE CONFIRM MODAL
========================= -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width:520px;">
        <div class="p-6">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Delete Session</h2>
                    <p class="text-gray-600 text-sm mt-1" id="deleteText">Are you sure?</p>
                </div>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <form method="POST" action="session.php" class="mt-6">
                <input type="hidden" name="action" value="delete_session">
                <input type="hidden" name="id" id="delete_id">

                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 rounded-lg font-medium text-white bg-red-600 hover:bg-red-700">
                        <i class="fas fa-trash mr-2"></i>Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-hide flash messages
    (function(){
        const el = document.getElementById('flashMsg');
        if (!el) return;
        setTimeout(()=>{ el.style.display = 'none'; }, 3500);
    })();

    // ========== CREATE modal ==========
    function openAddSessionModal() {
        document.getElementById('addSessionModal').classList.add('show');
    }
    function closeAddSessionModal() {
        document.getElementById('addSessionModal').classList.remove('show');
        document.getElementById('addSessionForm').reset();
    }

    // ========== UPDATE modal ==========
    function openEditSessionModal(data) {
        document.getElementById('edit_id').value = data.id || '';
        document.getElementById('edit_session_number').value = data.session_number || '';
        document.getElementById('edit_session_date').value = data.session_date || '';
        document.getElementById('edit_start_time').value = data.start_time || '';
        document.getElementById('edit_end_time').value = data.end_time || '';
        document.getElementById('edit_venue').value = data.venue || '';
        document.getElementById('edit_agenda').value = data.agenda || '';
        document.getElementById('edit_status').value = data.status || 'scheduled';

        document.getElementById('editSessionModal').classList.add('show');
    }
    function closeEditSessionModal() {
        document.getElementById('editSessionModal').classList.remove('show');
        document.getElementById('editSessionForm').reset();
    }

    // ========== DELETE modal ==========
    function openDeleteModal(id, sessionNumber) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteText').textContent =
            `Are you sure you want to delete Session ${sessionNumber}? This action cannot be undone.`;
        document.getElementById('deleteModal').classList.add('show');
    }
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.remove('show');
        document.getElementById('delete_id').value = '';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event){
        const addM  = document.getElementById('addSessionModal');
        const editM = document.getElementById('editSessionModal');
        const delM  = document.getElementById('deleteModal');

        if (event.target === addM)  closeAddSessionModal();
        if (event.target === editM) closeEditSessionModal();
        if (event.target === delM)  closeDeleteModal();
    });

    // Mobile menu toggle (if your header has md:hidden button)
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.querySelector('button.md\\:hidden');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                const sb = document.querySelector('.sidebar');
                if (sb) sb.classList.toggle('hidden');
            });
        }
    });
</script>
</body>
</html>
