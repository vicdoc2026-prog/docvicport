<?php
// Start session to store messages
session_start();

// Database configuration
$host = 'localhost';
$dbname = 'doc_vic';
$username = 'root';
$password = '';

// Create connection
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO maintenance_schedule_pvm (schedule_date, room, action_taken, technician_id, status, notes) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['schedule_date'],
                    $_POST['room'],
                    $_POST['action_taken'],
                    $_POST['technician_id'],
                    $_POST['status'],
                    $_POST['notes']
                ]);
                $_SESSION['message'] = "Maintenance schedule added successfully!";
                break;
            
            case 'update':
                $stmt = $pdo->prepare("UPDATE maintenance_schedule_pvm SET schedule_date=?, room=?, action_taken=?, technician_id=?, status=?, notes=? WHERE id=?");
                $stmt->execute([
                    $_POST['schedule_date'],
                    $_POST['room'],
                    $_POST['action_taken'],
                    $_POST['technician_id'],
                    $_POST['status'],
                    $_POST['notes'],
                    $_POST['id']
                ]);
                $_SESSION['message'] = "Maintenance schedule updated successfully!";
                break;
            
            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM maintenance_schedule_pvm WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['message'] = "Maintenance schedule deleted successfully!";
                break;
            
            case 'complete':
                $stmt = $pdo->prepare("UPDATE maintenance_schedule_pvm SET status='Completed', completion_date=NOW() WHERE id=?");
                $stmt->execute([$_POST['id']]);
                $_SESSION['message'] = "Maintenance marked as completed!";
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Get message from session and clear it
$message = '';
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Fetch all maintenance schedules with technician details
$query = "SELECT ms.*, t.name as technician_name 
          FROM maintenance_schedule_pvm ms
          JOIN technicians_pvm t ON ms.technician_id = t.id
          ORDER BY ms.schedule_date DESC";
$schedules = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch all distinct rooms from assets_unit table for dropdown
$technicians = $pdo->query("SELECT * FROM technicians_pvm ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preventive Maintenance Schedule</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        h1 {
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .message {
            background: #4caf50;
            color: white;
            padding: 15px;
            margin: 20px;
            border-radius: 5px;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .header-actions {
            padding: 20px 30px;
            background: #f8f9fa;
            border-bottom: 2px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .add-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
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
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
            animation: slideDown 0.3s;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 30px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.5em;
        }
        
        .close {
            color: white;
            font-size: 35px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s;
            line-height: 1;
        }
        
        .close:hover {
            transform: scale(1.1);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        label {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        input, select, textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .modal-footer {
            padding: 20px 30px;
            background: #f8f9fa;
            border-radius: 0 0 10px 10px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        button {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .table-container {
            padding: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status.scheduled {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .status.in-progress {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .status.completed {
            background: #e8f5e9;
            color: #388e3c;
        }
        
        .status.cancelled {
            background: #ffebee;
            color: #d32f2f;
        }
        
        .action-btn {
            padding: 5px 12px;
            margin: 2px;
            font-size: 12px;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            color: white;
        }
        
        .edit-btn {
            background: #2196f3;
        }
        
        .complete-btn {
            background: #4caf50;
        }
        
        .delete-btn {
            background: #f44336;
        }
        
        .action-btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Air Conditioning Preventive Maintenance Schedule</h1>
            <p>Manage and track all maintenance activities</p>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="header-actions">
            <h2>Maintenance Schedule</h2>
            <button class="add-btn" onclick="openModal()">+ Add New Schedule</button>
        </div>
        
        <!-- Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Add New Maintenance Schedule</h2>
                    <span class="close" onclick="closeModal()">&times;</span>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Schedule Date *</label>
                                <input type="date" name="schedule_date" required>
                            </div>
                            
<?php
// Fetch unique room names from assets_unit table
$rooms = $pdo->query("
    SELECT DISTINCT room 
    FROM assets_unit 
    WHERE room IS NOT NULL AND room != '' 
    ORDER BY room
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Room Selection Dropdown -->
<select name="room" required>
    <option value="">Select Room</option>
    <?php if (!empty($rooms)): ?>
        <?php foreach ($rooms as $room): ?>
            <option value="<?php echo htmlspecialchars($room['room']); ?>">
                <?php echo htmlspecialchars($room['room']); ?>
            </option>
        <?php endforeach; ?>
    <?php else: ?>
        <option value="">No rooms available</option>
    <?php endif; ?>
</select>    
                            <div class="form-group">
                                <label>Technician *</label>
                                <select name="technician_id" required>
                                    <option value="">Select Technician</option>
                                    <?php foreach ($technicians as $tech): ?>
                                        <option value="<?php echo $tech['id']; ?>">
                                            <?php echo htmlspecialchars($tech['name'] . ' (' . $tech['specialization'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 15px;">
                            <label>Action Taken / Description *</label>
                            <textarea name="action_taken" required placeholder="Describe the maintenance action..."></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes (Optional)</label>
                            <textarea name="notes" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn-primary">Add Schedule</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Room</th>
                        <th>Action Taken</th>
                        <th>Technician</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($schedules) > 0): ?>
                        <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($schedule['room']); ?></strong></td>
                                <td><?php echo htmlspecialchars($schedule['action_taken']); ?></td>
                                <td><?php echo htmlspecialchars($schedule['technician_name']); ?></td>
                                <td>
                                    <span class="status <?php echo strtolower(str_replace(' ', '-', $schedule['status'])); ?>">
                                        <?php echo htmlspecialchars($schedule['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($schedule['status'] != 'Completed'): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="complete">
                                            <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                            <button type="submit" class="action-btn complete-btn">Complete</button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $schedule['id']; ?>">
                                        <button type="submit" class="action-btn delete-btn" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #999;">
                                No maintenance schedules found. Click "Add New Schedule" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function openModal() {
            document.getElementById('addModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('addModal').style.display = 'none';
        }
        
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('addModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>