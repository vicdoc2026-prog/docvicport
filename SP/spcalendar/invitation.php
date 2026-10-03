<?php
// Google Calendar Integration with Database
require_once '../config/check-session.php';
require_once '../config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Google Calendar Configuration
$clientID = '679990911962-qtfcc63ceuoajsa219mfc64fqrpucl73.apps.googleusercontent.com';
$clientSecret = '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';
$redirectURI = 'http://localhost/doc_vic/SP/spcalendar/invitation.php';
$scope = 'https://www.googleapis.com/auth/calendar';

// Initialize Google session variable
if (!isset($_SESSION['google_access_token'])) {
    $_SESSION['google_access_token'] = null;
}

// Handle OAuth Callback
if (isset($_GET['code']) && !isset($_SESSION['google_access_token'])) {
    $tokenUrl = 'https://oauth2.googleapis.com/token';
    $postData = [
        'code' => $_GET['code'],
        'client_id' => $clientID,
        'client_secret' => $clientSecret,
        'redirect_uri' => $redirectURI,
        'grant_type' => 'authorization_code'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 200) {
        $token = json_decode($response, true);
        if (isset($token['access_token'])) {
            $_SESSION['google_access_token'] = $token['access_token'];
            if (isset($token['refresh_token'])) {
                $_SESSION['google_refresh_token'] = $token['refresh_token'];
            }
            header('Location: ' . $redirectURI);
            exit;
        }
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['google_access_token']);
    unset($_SESSION['google_refresh_token']);
    header('Location: ' . $redirectURI);
    exit;
}

$message = '';
$error = '';

// Handle Delete Event
if (isset($_POST['delete_event'])) {
    $event_id = intval($_POST['event_id']);
    $google_event_id = $_POST['google_event_id'];
    
    // Delete from Google Calendar
    if (isset($_SESSION['google_access_token']) && !empty($google_event_id)) {
        $ch = curl_init("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$google_event_id}");
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['google_access_token']
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM invitation WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    if ($stmt->execute()) {
        $message = "Event deleted successfully!";
    }
    $stmt->close();
}

// Handle Update Event
if (isset($_POST['update_event'])) {
    $event_id = intval($_POST['event_id']);
    $google_event_id = $_POST['google_event_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start = $_POST['start'];
    $end = $_POST['end'];
    
    if (!empty($title) && !empty($start) && !empty($end)) {
        $startISO = date('c', strtotime($start));
        $endISO = date('c', strtotime($end));
        
        // Update in Google Calendar
        if (isset($_SESSION['google_access_token']) && !empty($google_event_id)) {
            $eventData = [
                'summary' => $title,
                'description' => $description,
                'start' => ['dateTime' => $startISO, 'timeZone' => 'Asia/Manila'],
                'end' => ['dateTime' => $endISO, 'timeZone' => 'Asia/Manila']
            ];
            
            $ch = curl_init("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$google_event_id}");
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($eventData));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $_SESSION['google_access_token'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_exec($ch);
            curl_close($ch);
        }
        
        // Update in database
        $stmt = $conn->prepare("UPDATE invitation SET title = ?, description = ?, start_datetime = ?, end_datetime = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $title, $description, $start, $end, $event_id);
        if ($stmt->execute()) {
            $message = "Event updated successfully!";
        }
        $stmt->close();
    }
}

// Handle Create Event
if (isset($_POST['create_event']) && isset($_SESSION['google_access_token'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $start = $_POST['start'];
    $end = $_POST['end'];

    if (!empty($title) && !empty($start) && !empty($end)) {
        $startISO = date('c', strtotime($start));
        $endISO = date('c', strtotime($end));

        $eventData = [
            'summary' => $title,
            'description' => $description,
            'start' => ['dateTime' => $startISO, 'timeZone' => 'Asia/Manila'],
            'end' => ['dateTime' => $endISO, 'timeZone' => 'Asia/Manila']
        ];

        $ch = curl_init('https://www.googleapis.com/calendar/v3/calendars/primary/events');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($eventData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $_SESSION['google_access_token'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 || $httpCode == 201) {
            $event = json_decode($response, true);
            $google_event_id = $event['id'];
            $event_link = $event['htmlLink'];
            
            // Save to database
            $stmt = $conn->prepare("INSERT INTO invitation (title, description, start_datetime, end_datetime, google_event_id, event_link, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssss", $title, $description, $start, $end, $google_event_id, $event_link, $username);
            
            if ($stmt->execute()) {
                $message = "Event created successfully!";
            } else {
                $error = "Event created in Google Calendar but failed to save in database.";
            }
            $stmt->close();
        } else {
            $error = 'Failed to create event in Google Calendar.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Pagination and Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$records_per_page = 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Count total records
$count_query = "SELECT COUNT(*) as total FROM invitation WHERE 1=1";
if (!empty($search)) {
    $count_query .= " AND (title LIKE ? OR description LIKE ?)";
    $count_stmt = $conn->prepare($count_query);
    $search_param = "%$search%";
    $count_stmt->bind_param("ss", $search_param, $search_param);
} else {
    $count_stmt = $conn->prepare($count_query);
}
$count_stmt->execute();
$total_records = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Get events
$query = "SELECT * FROM invitation WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (title LIKE ? OR description LIKE ?)";
}
$query .= " ORDER BY start_datetime DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bind_param("ssii", $search_param, $search_param, $records_per_page, $offset);
} else {
    $stmt->bind_param("ii", $records_per_page, $offset);
}
$stmt->execute();
$events_result = $stmt->get_result();

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientID,
    'redirect_uri' => $redirectURI,
    'response_type' => 'code',
    'scope' => $scope,
    'access_type' => 'offline',
    'prompt' => 'consent'
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Calendar - Sangguniang Panlalawigan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3a6c;
            --secondary: #e63946;
            --accent: #2a9d8f;
            --light: #f1faee;
            --dark: #1d3557;
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
        }
        
        .card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
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
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(26, 58, 108, 0.1);
        }

        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            text-decoration: none;
            color: #374151;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background-color: var(--primary);
            color: white;
        }

        .pagination .active {
            background-color: var(--primary);
            color: white;
            font-weight: 600;
        }

        .connect-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
    <?php include '../bar/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include '../bar/header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Invitation</h1>
                <p class="text-gray-600 text-sm mt-1">Manage your Google Calendar events</p>
            </div>

            <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($message) ?>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-4">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['google_access_token'])): ?>
                <div class="connect-banner">
                    <i class="fas fa-calendar-alt text-5xl mb-3"></i>
                    <h3 class="text-2xl font-bold mb-2">Connect Google Calendar</h3>
                    <p class="mb-4">Link your Google account to manage events</p>
                    <a href="<?php echo htmlspecialchars($authUrl); ?>" class="inline-block px-6 py-3 bg-white text-gray-800 font-semibold rounded-full hover:bg-gray-100 transition-all shadow-lg">
                        <i class="fab fa-google mr-2"></i> Connect Now
                    </a>
                </div>
            <?php else: ?>
                <div class="card bg-white mb-6">
                    <div class="p-4 border-b flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold">
                                <i class="fas fa-check-circle mr-1"></i> Connected
                            </span>
                            <button onclick="openModal('createModal')" class="btn-primary text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i> New Event
                            </button>
                        </div>
                        <div class="flex items-center space-x-4">
                            <form method="GET" class="flex items-center">
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search events..." class="border border-gray-300 rounded-lg px-4 py-2 text-sm">
                                <button type="submit" class="ml-2 bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                            <a href="?logout=1" class="text-red-600 hover:text-red-700 text-sm font-medium">
                                <i class="fas fa-sign-out-alt mr-1"></i> Disconnect
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Start</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">End</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase">Created By</th>
                                    <th class="py-3 px-4 text-center text-xs font-medium text-gray-500 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($events_result->num_rows > 0): ?>
                                    <?php while ($event = $events_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 font-semibold text-gray-900"><?= htmlspecialchars($event['title']) ?></td>
                                            <td class="py-3 px-4 text-gray-700 text-sm"><?= htmlspecialchars(substr($event['description'], 0, 50)) ?><?= strlen($event['description']) > 50 ? '...' : '' ?></td>
                                            <td class="py-3 px-4 text-gray-700 text-sm"><?= date('M d, Y h:i A', strtotime($event['start_datetime'])) ?></td>
                                            <td class="py-3 px-4 text-gray-700 text-sm"><?= date('M d, Y h:i A', strtotime($event['end_datetime'])) ?></td>
                                            <td class="py-3 px-4 text-gray-700 text-sm"><?= htmlspecialchars($event['created_by']) ?></td>
                                            <td class="py-3 px-4 text-center">
                                                <button onclick='openEditModal(<?= json_encode($event) ?>)' class="text-blue-600 hover:text-blue-800 mx-1">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="confirmDelete(<?= $event['id'] ?>, '<?= htmlspecialchars($event['google_event_id']) ?>', '<?= htmlspecialchars($event['title']) ?>')" class="text-red-600 hover:text-red-800 mx-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                                <?php if ($event['event_link']): ?>
                                                <a href="<?= htmlspecialchars($event['event_link']) ?>" target="_blank" class="text-green-600 hover:text-green-800 mx-1">
                                                    <i class="fas fa-external-link-alt"></i>
                                                </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-gray-500">
                                            <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                                            No events found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                    <div class="p-4 border-t flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            Showing <?= $offset + 1 ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> events
                        </div>
                        <div class="flex gap-2 pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="?page=<?= $current_page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-left"></i></a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="active"><?= $i ?></span>
                                <?php else: ?>
                                    <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="?page=<?= $current_page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"><i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Create Event Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-calendar-plus mr-2 text-blue-600"></i> Create New Event</h2>
                    <button onclick="closeModal('createModal')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST">
                    <div class="form-group mb-4">
                        <label><i class="fas fa-tag mr-1"></i> Event Title *</label>
                        <input type="text" name="title" required placeholder="e.g., Team Meeting">
                    </div>
                    <div class="form-group mb-4">
                        <label><i class="fas fa-align-left mr-1"></i> Description</label>
                        <textarea name="description" rows="3" placeholder="Event details..."></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i> Start *</label>
                            <input type="datetime-local" name="start" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i> End *</label>
                            <input type="datetime-local" name="end" required>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeModal('createModal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" name="create_event" class="btn-primary text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-plus mr-2"></i> Create Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Event Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-edit mr-2 text-blue-600"></i> Edit Event</h2>
                    <button onclick="closeModal('editModal')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <form method="POST" id="editForm">
                    <input type="hidden" name="event_id" id="edit_event_id">
                    <input type="hidden" name="google_event_id" id="edit_google_event_id">
                    <div class="form-group mb-4">
                        <label><i class="fas fa-tag mr-1"></i> Event Title *</label>
                        <input type="text" name="title" id="edit_title" required>
                    </div>
                    <div class="form-group mb-4">
                        <label><i class="fas fa-align-left mr-1"></i> Description</label>
                        <textarea name="description" id="edit_description" rows="3"></textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i> Start *</label>
                            <input type="datetime-local" name="start" id="edit_start" required>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i> End *</label>
                            <input type="datetime-local" name="end" id="edit_end" required>
                        </div>
                    </div>
                    <div class="flex justify-end gap-2">
                        <button type="button" onclick="closeModal('editModal')" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg">Cancel</button>
                        <button type="submit" name="update_event" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                            <i class="fas fa-save mr-2"></i> Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Form -->
    <form method="POST" id="deleteForm" style="display: none;">
        <input type="hidden" name="event_id" id="delete_event_id">
        <input type="hidden" name="google_event_id" id="delete_google_event_id">
        <input type="hidden" name="delete_event" value="1">
    </form>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('active');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }

        function openEditModal(event) {
            document.getElementById('edit_event_id').value = event.id;
            document.getElementById('edit_google_event_id').value = event.google_event_id;
            document.getElementById('edit_title').value = event.title;
            document.getElementById('edit_description').value = event.description;
            document.getElementById('edit_start').value = event.start_datetime.replace(' ', 'T').slice(0, 16);
            document.getElementById('edit_end').value = event.end_datetime.replace(' ', 'T').slice(0, 16);
            openModal('editModal');
        }

        function confirmDelete(id, googleId, title) {
            if (confirm(`Are you sure you want to delete "${title}"?`)) {
                document.getElementById('delete_event_id').value = id;
                document.getElementById('delete_google_event_id').value = googleId;
                document.getElementById('deleteForm').submit();
            }
        }

        // Set default datetime values
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            const dateTimeNow = now.toISOString().slice(0, 16);
            
            const startInput = document.querySelector('#createModal input[name="start"]');
            if (startInput) {
                startInput.value = dateTimeNow;
                startInput.addEventListener('change', function() {
                    const endInput = document.querySelector('#createModal input[name="end"]');
                    const startTime = new Date(this.value);
                    const endTime = new Date(startTime.getTime() + 60 * 60 * 1000);
                    endTime.setMinutes(endTime.getMinutes() - endTime.getTimezoneOffset());
                    endInput.value = endTime.toISOString().slice(0, 16);
                });
            }
            
            const endInput = document.querySelector('#createModal input[name="end"]');
            if (endInput) {
                const oneHourLater = new Date(now.getTime() + 60 * 60 * 1000);
                oneHourLater.setMinutes(oneHourLater.getMinutes() - oneHourLater.getTimezoneOffset());
                endInput.value = oneHourLater.toISOString().slice(0, 16);
            }
        });
    </script>
</body>
</html>