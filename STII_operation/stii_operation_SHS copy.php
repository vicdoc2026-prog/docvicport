<?php
include 'conn.php';

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];



// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    // ===== SHS Students =====
    if ($_POST['action'] === 'add_shs_student') {
        $strand_name = $_POST['strand_name'];
        $grade_level = $_POST['grade_level']; // "grade_11" or "grade_12"
        $gender = strtolower($_POST['gender']); // male / female
        $count = intval($_POST['count']);
        $operation = $_POST['operation'] ?? 'add';

        $field = $grade_level . '_' . $gender; // ex: grade_11_male

        $check_sql = "SELECT $field FROM shs_courses WHERE strand_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $strand_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $current_data = $result->fetch_assoc();
        $current_count = $current_data[$field] ?? 0;

        if ($operation === 'add') {
            $sql = "UPDATE shs_courses SET $field = $field + ? WHERE strand_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $count, $strand_name);
            $final_count = $current_count + $count;
        } else {
            $new_count = max(0, $current_count - $count);
            $sql = "UPDATE shs_courses SET $field = ? WHERE strand_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $new_count, $strand_name);
            $final_count = $new_count;
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'new_count' => $final_count]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'update_shs_student') {
        $strand_name = $_POST['strand_name'];
        $grade_level = $_POST['grade_level'];
        $gender = strtolower($_POST['gender']);
        $count = intval($_POST['count']);

        $field = $grade_level . '_' . $gender;

        $sql = "UPDATE shs_courses SET $field = ? WHERE strand_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $count, $strand_name);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }

    // ===== JHS Students =====
    if ($_POST['action'] === 'add_jhs_student') {
        $grade_level = $_POST['grade_level']; // 7-10
        $gender = strtolower($_POST['gender']);
        $count = intval($_POST['count']);
        $operation = $_POST['operation'] ?? 'add';

        $field = "grade_{$grade_level}_{$gender}";

        $check_sql = "SELECT $field FROM jhs_students WHERE id = 1";
        $check_result = $conn->query($check_sql);
        $current_data = $check_result->fetch_assoc();
        $current_count = $current_data[$field] ?? 0;

        if ($operation === 'add') {
            $sql = "UPDATE jhs_students SET $field = $field + ? WHERE id = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $count);
            $final_count = $current_count + $count;
        } else {
            $new_count = max(0, $current_count - $count);
            $sql = "UPDATE jhs_students SET $field = ? WHERE id = 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $new_count);
            $final_count = $new_count;
        }

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'new_count' => $final_count]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }

    if ($_POST['action'] === 'update_jhs_student') {
        $grade_level = $_POST['grade_level'];
        $gender = strtolower($_POST['gender']);
        $count = intval($_POST['count']);

        $field = "grade_{$grade_level}_{$gender}";

        $sql = "UPDATE jhs_students SET $field = ? WHERE id = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $count);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
}

// ===== Fetch SHS data =====
$shs_sql = "SELECT strand_name, grade_11_male, grade_11_female, grade_12_male, grade_12_female FROM shs_courses";
$shs_result = $conn->query($shs_sql);

// ===== Fetch JHS data =====
$jhs_sql = "SELECT * FROM jhs_students WHERE id = 1";
$jhs_result = $conn->query($jhs_sql);
$jhs_data = $jhs_result->fetch_assoc();

// ===== Calculate totals =====
$shs_data = [];
$total_shs_students = $total_shs_male = $total_shs_female = 0;
$shs_grade_totals = [11 => 0, 12 => 0];

while ($row = $shs_result->fetch_assoc()) {
    $strand_male = $row['grade_11_male'] + $row['grade_12_male'];
    $strand_female = $row['grade_11_female'] + $row['grade_12_female'];
    $strand_total = $strand_male + $strand_female;

    $row['male_students'] = $strand_male;
    $row['female_students'] = $strand_female;
    $row['total_students'] = $strand_total;

    $shs_data[] = $row;

    $total_shs_students += $strand_total;
    $total_shs_male += $strand_male;
    $total_shs_female += $strand_female;

    $shs_grade_totals[11] += $row['grade_11_male'] + $row['grade_11_female'];
    $shs_grade_totals[12] += $row['grade_12_male'] + $row['grade_12_female'];
}

$total_jhs_students = $total_jhs_male = $total_jhs_female = 0;
$jhs_grade_totals = [7 => 0, 8 => 0, 9 => 0, 10 => 0];

if ($jhs_data) {
    for ($grade = 7; $grade <= 10; $grade++) {
        $male_field = "grade_{$grade}_male";
        $female_field = "grade_{$grade}_female";

        $grade_male = $jhs_data[$male_field];
        $grade_female = $jhs_data[$female_field];
        $grade_total = $grade_male + $grade_female;

        $jhs_grade_totals[$grade] = $grade_total;
        $total_jhs_male += $grade_male;
        $total_jhs_female += $grade_female;
        $total_jhs_students += $grade_total;
    }
}

$total_students = $total_shs_students + $total_jhs_students;
$total_male = $total_shs_male + $total_jhs_male;
$total_female = $total_shs_female + $total_jhs_female;
$total_strands = count($shs_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SHS & JHS Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff, #e6f7ff, #d1eeff);
            background-size: 400% 400%;
            animation: gradientBG 18s ease infinite;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .glitch-effect {
            position: relative;
            display: inline-block;
        }

        .glitch-effect::before,
        .glitch-effect::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            overflow: hidden;
            clip: rect(0, 900px, 0, 0);
        }

        .glitch-effect::before {
            left: 2px;
            text-shadow: -2px 0 #ff00c1;
            animation: glitch 2s infinite linear alternate-reverse;
        }

        .glitch-effect::after {
            left: -2px;
            text-shadow: -2px 0 #00fff9;
            animation: glitch 2s infinite linear alternate-reverse;
        }

        @keyframes glitch {
            0% { clip: rect(42px, 9999px, 44px, 0); }
            20% { clip: rect(12px, 9999px, 20px, 0); }
            40% { clip: rect(72px, 9999px, 74px, 0); }
            60% { clip: rect(22px, 9999px, 26px, 0); }
            80% { clip: rect(92px, 9999px, 96px, 0); }
            100% { clip: rect(12px, 9999px, 18px, 0); }
        }

        .glass-container {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 24px;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.08);
        }

        .dashboard-card {
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            overflow: hidden;
            position: relative;
            z-index: 1;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.05);
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }

        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 3rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #10b981 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
            letter-spacing: 2px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .dashboard-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 2.5rem;
            color: #3b82f6;
            letter-spacing: 0.02em;
            margin-bottom: 1.5rem;
            margin-top: -1rem;
        }

        .stat-value { font-size: 1.8rem; font-weight: 700; color: #1e293b; }
        .stat-label { color: #64748b; font-weight: 500; }

        .progress-bar {
            height: 8px; border-radius: 4px; background-color: #e2e8f0; overflow: hidden; margin-top: 8px;
        }
        .progress-fill {
            height: 100%; border-radius: 4px; transition: width 0.5s ease;
        }

        .fade-in { animation: fadeIn 1.2s ease-out forwards; }
        @keyframes fadeIn { from {opacity: 0; transform: translateY(20px);} to {opacity: 1; transform: translateY(0);} }
        .card-delay-1 { animation-delay: 0.1s; } .card-delay-2 { animation-delay: 0.2s; } .card-delay-3 { animation-delay: 0.3s; }

        .dashboard-icon { font-size: 2.5rem; margin-bottom: 1rem; transition: transform 0.3s ease; }
        .dashboard-card:hover .dashboard-icon { transform: scale(1.1); }

        .particles { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 0; }
        .particle { position: absolute; border-radius: 50%; background: rgba(96, 165, 250, 0.3); animation: float linear infinite; }
        @keyframes float { to { transform: translateY(-20px) rotate(360deg); opacity: 0; } }

        .grade-level-card { transition: all 0.3s ease; }
        .grade-level-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }

        .strand-table, .jhs-table { width: 100%; border-collapse: separate; border-spacing: 0; }
        .strand-table th, .strand-table td, .jhs-table th, .jhs-table td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .strand-table th, .jhs-table th { background-color: #3b82f6; color: white; font-weight: 600; }
        .strand-table tr:nth-child(even), .jhs-table tr:nth-child(even) { background-color: #f8fafc; }
        .strand-table tr:hover, .jhs-table tr:hover { background-color: rgba(59, 130, 246, 0.1); cursor: pointer; }
        .strand-table th:first-child, .strand-table td:first-child { width: 40%; }
        .strand-table th:not(:first-child), .strand-table td:not(:first-child) { width: 15%; text-align: center; }
        .strand-table th:first-child, .jhs-table th:first-child { border-top-left-radius: 8px; }
        .strand-table th:last-child, .jhs-table th:last-child { border-top-right-radius: 8px; }

        .child-row { display: none; background-color: #eff6ff; font-size: 0.9rem; }
        .child-row td { padding: 10px 14px; border-bottom: 1px solid #e2e8f0; position: relative; }
        .child-row td:first-child { padding-left: 48px; }
        .child-row.visible { display: table-row; animation: slideDown 0.3s ease-in-out; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            overflow-y: auto;
            padding: 20px;
        }
        
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s ease-out;
        }

        @keyframes modalSlideIn {
            from { opacity: 0; transform: translateY(-20px) scale(0.95); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-secondary {
            background: #64748b;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-left: 8px;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .edit-input {
            background: transparent;
            border: 1px solid transparent;
            padding: 4px 8px;
            border-radius: 4px;
            width: 60px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .edit-input:focus {
            outline: none;
            border-color: #3b82f6;
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .add-btn, .reduce-btn {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            border: none;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            transition: all 0.3s ease;
            opacity: 0;
        }

        .add-btn {
            background: #10b981;
            right: 40px;
        }

        .reduce-btn {
            background: #ef4444;
            right: 10px;
        }

        .child-row:hover .add-btn,
        .child-row:hover .reduce-btn,
        .jhs-table tr:hover .add-btn,
        .jhs-table tr:hover .reduce-btn {
            opacity: 1;
        }

        .add-btn:hover {
            background: #059669;
            transform: translateY(-50%) scale(1.1);
        }

        .reduce-btn:hover {
            background: #dc2626;
            transform: translateY(-50%) scale(1.1);
        }

        .section-header {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .nav-links {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        .nav-link {
            color: #475569;
            font-weight: 600;
            padding: 0.5rem 1.25rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
            border: 2px solid transparent;
            background: transparent;
            cursor: pointer;
        }

        .nav-link:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #3b82f6;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(135deg, #7c3aed, #6d28d9);
            color: white;
            border-color: #7c3aed;
            box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
        }

        .nav-link.college {
            color: #3b82f6;
        }

        .nav-link.college:hover {
            background: rgba(59, 130, 246, 0.1);
            color: #1d4ed8;
        }

        .nav-link.tesda {
            color: #059669;
        }

        .nav-link.tesda:hover {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
        }

        @media (max-width: 768px) {
            .brand-title { font-size: 2.5rem; }
            .dashboard-text { font-size: 1.25rem; }
            .strand-table th, .strand-table td, .jhs-table th, .jhs-table td { padding: 10px; font-size: 0.85rem; }
            .child-row td { padding: 8px 10px; font-size: 0.8rem; }
            .child-row td:first-child { padding-left: 20px; }
            .modal-content { margin: 20% auto; padding: 20px; }
            .navbar {
                flex-direction: column;
                gap: 1rem;
                padding: 1rem;
            }
            .nav-links {
                flex-direction: column;
                width: 100%;
                gap: 0.5rem;
            }
            .nav-link {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="p-4">
    <!-- Animated particles background -->
    <div class="particles" id="particles"></div>

    <!-- Add/Reduce Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <h2 class="text-2xl font-bold text-gray-800 mb-6" id="modalTitle">Add Students</h2>
            <form id="addStudentForm">
                <div class="mb-4" id="strandField">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Strand</label>
                    <input type="text" id="strandName" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Grade Level</label>
                    <input type="text" id="gradeLevel" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100">
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Gender</label>
                    <select id="gender" class="w-full p-3 border border-gray-300 rounded-lg">
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Operation</label>
                    <select id="operation" class="w-full p-3 border border-gray-300 rounded-lg" onchange="updateModalTitle()">
                        <option value="add">Add Students</option>
                        <option value="reduce">Reduce Students</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-bold mb-2">Number of Students</label>
                    <input type="number" id="studentCount" min="1" class="w-full p-3 border border-gray-300 rounded-lg" placeholder="Enter number of students">
                    <div id="currentCountInfo" class="text-sm text-gray-600 mt-2"></div>
                </div>
                <div class="flex justify-end">
                    <button type="button" id="cancelAdd" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary ml-3" id="submitBtn">Add Students</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Main container -->
    <div class="glass-container w-full p-6 md:p-10 relative z-10">
        <!-- Navigation Bar -->
        <nav class="navbar">
            <button onclick="window.location='portal.php'" class="btn-secondary">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </button>
            <div class="nav-links">
                <button onclick="window.location='stii_operation.php'" class="nav-link college">
                    <i class="fas fa-graduation-cap"></i> College
                </button>
                <button onclick="window.location='stii_operation_SHS.php'" class="nav-link active">
                    <i class="fas fa-school"></i> High School
                </button>
                <button onclick="window.location='tesda.php'" class="nav-link tesda">
                    <i class="fas fa-certificate"></i> TESDA
                </button>
            </div>
        </nav>

        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="brand-title glitch-effect" data-text="Sibugay Technical Institute Incorporated">
                Sibugay Technical Institute Incorporated
            </h1>
            <p class="dashboard-text">Senior High School & Junior High School Dashboard</p>
            <p class="text-slate-600 max-w-2xl mx-auto mt-3 font-semibold">
                1st Semester, School Year 2025 - 2026
            </p>
        </div>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-12 min-h-[200px]">
            <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 text-center">
                <i class="fas fa-graduation-cap dashboard-icon text-blue-500"></i>
                <div class="stat-value" id="totalStudents"><?php echo number_format($total_students); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 text-center">
                <i class="fas fa-users dashboard-icon text-green-500"></i>
                <div class="stat-value" id="totalSHS"><?php echo number_format($total_shs_students); ?></div>
                <div class="stat-label">SHS Students</div>
            </div>
            <div class="dashboard-card fade-in card-delay-3 rounded-xl p-6 text-center">
                <i class="fas fa-school dashboard-icon text-purple-500"></i>
                <div class="stat-value" id="totalJHS"><?php echo number_format($total_jhs_students); ?></div>
                <div class="stat-label">JHS Students</div>
            </div>
            <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 text-center">
                <i class="fas fa-male dashboard-icon text-cyan-500"></i>
                <div class="stat-value" id="totalMale"><?php echo number_format($total_male); ?></div>
                <div class="stat-label">Male Students</div>
            </div>
            <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 text-center">
                <i class="fas fa-female dashboard-icon text-pink-500"></i>
                <div class="stat-value" id="totalFemale"><?php echo number_format($total_female); ?></div>
                <div class="stat-label">Female Students</div>
            </div>
        </div>

        <!-- SHS Grade Level Distribution -->
        <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 mb-8">
            <h2 class="section-header flex items-center">
                <i class="fas fa-chart-bar text-blue-500 mr-3"></i> SHS Grade Distribution
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="grade-level-card bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-blue-700" id="grade11Total"><?php echo number_format($shs_grade_totals[11]); ?></div>
                    <div class="font-medium text-blue-800">Grade 11</div>
                    <div class="text-sm text-blue-600 mt-2" id="grade11Percent"><?php echo number_format(($shs_grade_totals[11] / max($total_shs_students, 1)) * 100, 1); ?>% of SHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-blue-500" id="grade11Progress" style="width: <?php echo ($shs_grade_totals[11] / max($total_shs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="grade-level-card bg-gradient-to-br from-green-100 to-green-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-green-700" id="grade12Total"><?php echo number_format($shs_grade_totals[12]); ?></div>
                    <div class="font-medium text-green-800">Grade 12</div>
                    <div class="text-sm text-green-600 mt-2" id="grade12Percent"><?php echo number_format(($shs_grade_totals[12] / max($total_shs_students, 1)) * 100, 1); ?>% of SHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-green-500" id="grade12Progress" style="width: <?php echo ($shs_grade_totals[12] / max($total_shs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- JHS Grade Level Distribution -->
        <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 mb-8">
            <h2 class="section-header flex items-center">
                <i class="fas fa-chart-line text-purple-500 mr-3"></i> JHS Grade Distribution
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="grade-level-card bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-purple-700" id="grade7Total"><?php echo number_format($jhs_grade_totals[7]); ?></div>
                    <div class="font-medium text-purple-800">Grade 7</div>
                    <div class="text-sm text-purple-600 mt-2" id="grade7Percent"><?php echo number_format(($jhs_grade_totals[7] / max($total_jhs_students, 1)) * 100, 1); ?>% of JHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-purple-500" id="grade7Progress" style="width: <?php echo ($jhs_grade_totals[7] / max($total_jhs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="grade-level-card bg-gradient-to-br from-indigo-100 to-indigo-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-indigo-700" id="grade8Total"><?php echo number_format($jhs_grade_totals[8]); ?></div>
                    <div class="font-medium text-indigo-800">Grade 8</div>
                    <div class="text-sm text-indigo-600 mt-2" id="grade8Percent"><?php echo number_format(($jhs_grade_totals[8] / max($total_jhs_students, 1)) * 100, 1); ?>% of JHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-indigo-500" id="grade8Progress" style="width: <?php echo ($jhs_grade_totals[8] / max($total_jhs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="grade-level-card bg-gradient-to-br from-amber-100 to-amber-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-amber-700" id="grade9Total"><?php echo number_format($jhs_grade_totals[9]); ?></div>
                    <div class="font-medium text-amber-800">Grade 9</div>
                    <div class="text-sm text-amber-600 mt-2" id="grade9Percent"><?php echo number_format(($jhs_grade_totals[9] / max($total_jhs_students, 1)) * 100, 1); ?>% of JHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-amber-500" id="grade9Progress" style="width: <?php echo ($jhs_grade_totals[9] / max($total_jhs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
                
                <div class="grade-level-card bg-gradient-to-br from-red-100 to-red-50 rounded-xl p-5 text-center">
                    <div class="text-4xl font-bold text-red-700" id="grade10Total"><?php echo number_format($jhs_grade_totals[10]); ?></div>
                    <div class="font-medium text-red-800">Grade 10</div>
                    <div class="text-sm text-red-600 mt-2" id="grade10Percent"><?php echo number_format(($jhs_grade_totals[10] / max($total_jhs_students, 1)) * 100, 1); ?>% of JHS</div>
                    <div class="progress-bar mt-3">
                        <div class="progress-fill bg-red-500" id="grade10Progress" style="width: <?php echo ($jhs_grade_totals[10] / max($total_jhs_students, 1)) * 100; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SHS Strands Table -->
        <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 mb-8">
            <h2 class="section-header flex items-center mb-4">
                <i class="fas fa-graduation-cap text-blue-500 mr-3"></i> SHS Strands (<?php echo $total_strands; ?>)
            </h2>
            <div class="overflow-x-auto">
                <table class="strand-table">
                    <thead>
                        <tr>
                            <th>Strand Name</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="strandsTableBody">
                        <?php foreach ($shs_data as $row): ?>
                            <tr class="strand-row" data-strand="<?php echo htmlspecialchars($row['strand_name']); ?>" data-grades='{
                                "grade_11":{"male":<?php echo $row['grade_11_male']; ?>,"female":<?php echo $row['grade_11_female']; ?>,"total":<?php echo $row['grade_11_male'] + $row['grade_11_female']; ?>},
                                "grade_12":{"male":<?php echo $row['grade_12_male']; ?>,"female":<?php echo $row['grade_12_female']; ?>,"total":<?php echo $row['grade_12_male'] + $row['grade_12_female']; ?>}
                            }'>
                                <td><?php echo htmlspecialchars($row['strand_name']); ?></td>
                                <td class="text-center strand-male"><?php echo $row['male_students']; ?></td>
                                <td class="text-center strand-female"><?php echo $row['female_students']; ?></td>
                                <td class="text-center strand-total"><?php echo $row['total_students']; ?></td>
                                <td class="text-center">
                                    <button class="btn-primary text-xs px-3 py-1" onclick="toggleStrandDetails(this)">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- JHS Students Table -->
        <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 mb-8">
            <h2 class="section-header flex items-center mb-4">
                <i class="fas fa-school text-purple-500 mr-3"></i> Junior High School Students
            </h2>
            <div class="overflow-x-auto">
                <table class="jhs-table" id="jhsTable">
                    <thead>
                        <tr>
                            <th>Grade Level</th>
                            <th>Male</th>
                            <th>Female</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($grade = 7; $grade <= 10; $grade++): ?>
                            <?php
                                $male_field = "grade_{$grade}_male";
                                $female_field = "grade_{$grade}_female";
                                $grade_male = $jhs_data[$male_field];
                                $grade_female = $jhs_data[$female_field];
                                $grade_total = $grade_male + $grade_female;
                            ?>
                            <tr data-grade="<?php echo $grade; ?>">
                                <td>Grade <?php echo $grade; ?></td>
                                <td class="text-center" style="position: relative;">
                                    <input type="number" value="<?php echo $grade_male; ?>" 
                                           class="edit-input jhs-male" min="0" 
                                           onchange="updateJHSStudentCount('<?php echo $grade; ?>', 'male', this.value)">
                                    <button class="add-btn" onclick="showJHSModal('<?php echo $grade; ?>', 'male', 'add')" title="Add Students">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="reduce-btn" onclick="showJHSModal('<?php echo $grade; ?>', 'male', 'reduce')" title="Reduce Students">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </td>
                                <td class="text-center" style="position: relative;">
                                    <input type="number" value="<?php echo $grade_female; ?>" 
                                           class="edit-input jhs-female" min="0"
                                           onchange="updateJHSStudentCount('<?php echo $grade; ?>', 'female', this.value)">
                                    <button class="add-btn" onclick="showJHSModal('<?php echo $grade; ?>', 'female', 'add')" title="Add Students">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button class="reduce-btn" onclick="showJHSModal('<?php echo $grade; ?>', 'female', 'reduce')" title="Reduce Students">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                </td>
                                <td class="text-center jhs-total"><?php echo $grade_total; ?></td>
                            </tr>
                        <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-10 pt-6 border-t border-slate-200 text-center text-slate-600 text-sm">
            &copy; 2025 SHS & JHS Management System. All rights reserved.
        </div>
    </div>

    <script>
        let currentStrandData = {};
        let isJHSModal = false;

        // Modal functionality
        const modal = document.getElementById('addStudentModal');
        const cancelBtn = document.getElementById('cancelAdd');
        
        cancelBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }

        // Add student form
        document.getElementById('addStudentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const gradeLevel = document.getElementById('gradeLevel').value;
            const gender = document.getElementById('gender').value;
            const count = document.getElementById('studentCount').value;
            const operation = document.getElementById('operation').value;
            
            if (!count || count <= 0) {
                alert('Please enter a valid number of students');
                return;
            }
            
            if (isJHSModal) {
                // Handle JHS student update
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_jhs_student&grade_level=${gradeLevel}&gender=${gender}&count=${count}&operation=${operation}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateJHSDataDirect(gradeLevel, gender, data.new_count);
                        modal.style.display = 'none';
                        document.getElementById('studentCount').value = '';
                        document.getElementById('operation').value = 'add';
                        updateModalTitle();
                        alert(`JHS students ${operation === 'add' ? 'added' : 'reduced'} successfully!`);
                    } else {
                        alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} JHS students: ` + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} JHS students`);
                });
            } else {
                // Handle SHS student update
                const strandName = document.getElementById('strandName').value;
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=add_shs_student&strand_name=${encodeURIComponent(strandName)}&grade_level=${gradeLevel}&gender=${gender}&count=${count}&operation=${operation}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStrandDataDirect(strandName, gradeLevel, gender, data.new_count);
                        modal.style.display = 'none';
                        document.getElementById('studentCount').value = '';
                        document.getElementById('operation').value = 'add';
                        updateModalTitle();
                        alert(`SHS students ${operation === 'add' ? 'added' : 'reduced'} successfully!`);
                    } else {
                        alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} SHS students: ` + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} SHS students`);
                });
            }
        });

        // Update SHS student count
        function updateSHSStudentCount(strandName, gradeLevel, gender, newValue) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_shs_student&strand_name=${encodeURIComponent(strandName)}&grade_level=${gradeLevel}&gender=${gender}&count=${newValue}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAllTotals();
                } else {
                    alert('Error updating SHS student count: ' + (data.error || 'Unknown error'));
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating SHS student count');
                location.reload();
            });
        }

        // Update JHS student count
        function updateJHSStudentCount(gradeLevel, gender, newValue) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update_jhs_student&grade_level=${gradeLevel}&gender=${gender}&count=${newValue}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAllTotals();
                } else {
                    alert('Error updating JHS student count: ' + (data.error || 'Unknown error'));
                    location.reload();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating JHS student count');
                location.reload();
            });
        }

        // Update strand data directly with new value
        function updateStrandDataDirect(strandName, gradeLevel, gender, newValue) {
            const strandRow = document.querySelector(`[data-strand="${strandName}"]`);
            if (!strandRow) return;
            
            const gradeData = JSON.parse(strandRow.getAttribute('data-grades'));
            gradeData[gradeLevel][gender] = newValue;
            gradeData[gradeLevel].total = gradeData[gradeLevel].male + gradeData[gradeLevel].female;
            
            strandRow.setAttribute('data-grades', JSON.stringify(gradeData));
            
            let strandMale = 0, strandFemale = 0, strandTotal = 0;
            Object.keys(gradeData).forEach(grade => {
                strandMale += gradeData[grade].male;
                strandFemale += gradeData[grade].female;
                strandTotal += gradeData[grade].total;
            });
            
            strandRow.querySelector('.strand-male').textContent = strandMale;
            strandRow.querySelector('.strand-female').textContent = strandFemale;
            strandRow.querySelector('.strand-total').textContent = strandTotal;
            
            // Update child rows if visible
            const childRows = getChildRowsForStrand(strandRow);
            childRows.forEach(childRow => {
                const childGradeLevel = childRow.getAttribute('data-grade');
                if (childGradeLevel && gradeData[childGradeLevel]) {
                    const maleInput = childRow.querySelector('.child-male');
                    const femaleInput = childRow.querySelector('.child-female');
                    const totalCell = childRow.querySelector('.child-total');
                    
                    if (maleInput) maleInput.value = gradeData[childGradeLevel].male;
                    if (femaleInput) femaleInput.value = gradeData[childGradeLevel].female;
                    if (totalCell) totalCell.textContent = gradeData[childGradeLevel].total;
                }
            });
            
            updateAllTotals();
        }

        // Update JHS data directly with new value
        function updateJHSDataDirect(gradeLevel, gender, newValue) {
            const jhsRow = document.querySelector(`#jhsTable tr[data-grade="${gradeLevel}"]`);
            if (!jhsRow) return;
            
            const input = jhsRow.querySelector(`.jhs-${gender}`);
            if (input) {
                input.value = newValue;
            }
            
            // Update total for this grade
            const maleValue = parseInt(jhsRow.querySelector('.jhs-male').value) || 0;
            const femaleValue = parseInt(jhsRow.querySelector('.jhs-female').value) || 0;
            const totalCell = jhsRow.querySelector('.jhs-total');
            totalCell.textContent = maleValue + femaleValue;
            
            updateAllTotals();
        }

        // Get child rows for a strand
        function getChildRowsForStrand(strandRow) {
            const childRows = [];
            let nextRow = strandRow.nextElementSibling;
            while (nextRow && nextRow.classList.contains('child-row')) {
                childRows.push(nextRow);
                nextRow = nextRow.nextElementSibling;
            }
            return childRows;
        }

        // Update all totals
        function updateAllTotals() {
            let totalStudents = 0;
            let totalMale = 0;
            let totalFemale = 0;
            let totalSHS = 0;
            let totalJHS = 0;
            let shsGradeTotals = { grade_11: 0, grade_12: 0 };
            let jhsGradeTotals = { 7: 0, 8: 0, 9: 0, 10: 0 };
            
            // Calculate SHS totals
            document.querySelectorAll('.strand-row').forEach(row => {
                const gradeData = JSON.parse(row.getAttribute('data-grades'));
                
                Object.keys(gradeData).forEach(grade => {
                    totalMale += gradeData[grade].male;
                    totalFemale += gradeData[grade].female;
                    totalStudents += gradeData[grade].total;
                    totalSHS += gradeData[grade].total;
                    shsGradeTotals[grade] += gradeData[grade].total;
                });
            });
            
            // Calculate JHS totals
            document.querySelectorAll('#jhsTable tr[data-grade]').forEach(row => {
                const grade = row.getAttribute('data-grade');
                const maleValue = parseInt(row.querySelector('.jhs-male').value) || 0;
                const femaleValue = parseInt(row.querySelector('.jhs-female').value) || 0;
                const gradeTotal = maleValue + femaleValue;
                
                totalMale += maleValue;
                totalFemale += femaleValue;
                totalStudents += gradeTotal;
                totalJHS += gradeTotal;
                jhsGradeTotals[grade] = gradeTotal;
            });
            
            // Update dashboard cards
            document.getElementById('totalStudents').textContent = totalStudents.toLocaleString();
            document.getElementById('totalSHS').textContent = totalSHS.toLocaleString();
            document.getElementById('totalJHS').textContent = totalJHS.toLocaleString();
            document.getElementById('totalMale').textContent = totalMale.toLocaleString();
            document.getElementById('totalFemale').textContent = totalFemale.toLocaleString();
            
            // Update SHS grade cards
            const shsGradeMapping = {
                'grade_11': { total: 'grade11Total', percent: 'grade11Percent', progress: 'grade11Progress' },
                'grade_12': { total: 'grade12Total', percent: 'grade12Percent', progress: 'grade12Progress' }
            };
            
            Object.keys(shsGradeTotals).forEach(grade => {
                const ids = shsGradeMapping[grade];
                const percentage = totalSHS > 0 ? (shsGradeTotals[grade] / totalSHS * 100) : 0;
                
                document.getElementById(ids.total).textContent = shsGradeTotals[grade].toLocaleString();
                document.getElementById(ids.percent).textContent = `${percentage.toFixed(1)}% of SHS`;
                document.getElementById(ids.progress).style.width = `${percentage}%`;
            });
            
            // Update JHS grade cards
            const jhsGradeMapping = {
                7: { total: 'grade7Total', percent: 'grade7Percent', progress: 'grade7Progress' },
                8: { total: 'grade8Total', percent: 'grade8Percent', progress: 'grade8Progress' },
                9: { total: 'grade9Total', percent: 'grade9Percent', progress: 'grade9Progress' },
                10: { total: 'grade10Total', percent: 'grade10Percent', progress: 'grade10Progress' }
            };
            
            Object.keys(jhsGradeTotals).forEach(grade => {
                const ids = jhsGradeMapping[grade];
                const percentage = totalJHS > 0 ? (jhsGradeTotals[grade] / totalJHS * 100) : 0;
                
                document.getElementById(ids.total).textContent = jhsGradeTotals[grade].toLocaleString();
                document.getElementById(ids.percent).textContent = `${percentage.toFixed(1)}% of JHS`;
                document.getElementById(ids.progress).style.width = `${percentage}%`;
            });
        }

        // Show SHS modal
        function showSHSModal(strandName, gradeLevel, operation = 'add') {
            isJHSModal = false;
            const strandRow = document.querySelector(`[data-strand="${strandName}"]`);
            const gradeData = JSON.parse(strandRow.getAttribute('data-grades'));
            
            document.getElementById('strandField').style.display = 'block';
            document.getElementById('strandName').value = strandName;
            document.getElementById('gradeLevel').value = gradeLevel;
            document.getElementById('gender').value = 'male';
            document.getElementById('operation').value = operation;
            document.getElementById('studentCount').value = '';
            
            const currentCountInfo = document.getElementById('currentCountInfo');
            const currentMale = gradeData[gradeLevel].male;
            const currentFemale = gradeData[gradeLevel].female;
            currentCountInfo.innerHTML = `Current count: <strong>Male: ${currentMale}, Female: ${currentFemale}</strong>`;
            
            updateModalTitle();
            modal.style.display = 'block';
            
            document.getElementById('gender').onchange = function() {
                const selectedGender = this.value;
                const currentCount = gradeData[gradeLevel][selectedGender];
                currentCountInfo.innerHTML = `Current ${selectedGender} count: <strong>${currentCount}</strong>`;
            };
            
            document.getElementById('gender').onchange();
        }

        // Show JHS modal
        function showJHSModal(gradeLevel, gender = 'male', operation = 'add') {
            isJHSModal = true;
            const jhsRow = document.querySelector(`#jhsTable tr[data-grade="${gradeLevel}"]`);
            
            document.getElementById('strandField').style.display = 'none';
            document.getElementById('gradeLevel').value = gradeLevel;
            document.getElementById('gender').value = gender;
            document.getElementById('operation').value = operation;
            document.getElementById('studentCount').value = '';
            
            const currentCountInfo = document.getElementById('currentCountInfo');
            const maleValue = parseInt(jhsRow.querySelector('.jhs-male').value) || 0;
            const femaleValue = parseInt(jhsRow.querySelector('.jhs-female').value) || 0;
            currentCountInfo.innerHTML = `Current count: <strong>Male: ${maleValue}, Female: ${femaleValue}</strong>`;
            
            updateModalTitle();
            modal.style.display = 'block';
            
            document.getElementById('gender').onchange = function() {
                const selectedGender = this.value;
                const currentCount = selectedGender === 'male' ? maleValue : femaleValue;
                currentCountInfo.innerHTML = `Current ${selectedGender} count: <strong>${currentCount}</strong>`;
            };
            
            document.getElementById('gender').onchange();
        }

        // Update modal title
        function updateModalTitle() {
            const operation = document.getElementById('operation').value;
            const modalTitle = document.getElementById('modalTitle');
            const submitBtn = document.getElementById('submitBtn');
            
            if (operation === 'add') {
                modalTitle.textContent = 'Add Students';
                submitBtn.textContent = 'Add Students';
                submitBtn.className = 'btn-primary ml-3';
            } else {
                modalTitle.textContent = 'Reduce Students';
                submitBtn.textContent = 'Reduce Students';
                submitBtn.className = 'btn-secondary ml-3';
                submitBtn.style.background = '#ef4444';
            }
        }

        // Toggle strand details
        function toggleStrandDetails(button) {
            const strandRow = button.closest('.strand-row');
            const strandName = strandRow.getAttribute('data-strand');
            const gradeData = JSON.parse(strandRow.getAttribute('data-grades'));
            
            let nextRow = strandRow.nextElementSibling;
            const hasChildRows = nextRow && nextRow.classList.contains('child-row');
            
            if (hasChildRows) {
                while (nextRow && nextRow.classList.contains('child-row')) {
                    const toRemove = nextRow;
                    nextRow = nextRow.nextElementSibling;
                    toRemove.remove();
                }
                button.innerHTML = '<i class="fas fa-eye mr-1"></i> View Details';
            } else {
                const grades = [
                    { key: 'grade_11', label: 'Grade 11' },
                    { key: 'grade_12', label: 'Grade 12' }
                ];
                
                grades.reverse().forEach(grade => {
                    const childRow = document.createElement('tr');
                    childRow.classList.add('child-row');
                    childRow.setAttribute('data-grade', grade.key);
                    childRow.innerHTML = `
                        <td>${grade.label}</td>
                        <td class="text-center" style="position: relative;">
                            <input type="number" value="${gradeData[grade.key].male}" 
                                   class="edit-input child-male" min="0" 
                                   onchange="updateSHSStudentCount('${strandName}', '${grade.key}', 'male', this.value)">
                            <button class="add-btn" onclick="showSHSModal('${strandName}', '${grade.key}', 'add')" title="Add Students">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="reduce-btn" onclick="showSHSModal('${strandName}', '${grade.key}', 'reduce')" title="Reduce Students">
                                <i class="fas fa-minus"></i>
                            </button>
                        </td>
                        <td class="text-center" style="position: relative;">
                            <input type="number" value="${gradeData[grade.key].female}" 
                                   class="edit-input child-female" min="0"
                                   onchange="updateSHSStudentCount('${strandName}', '${grade.key}', 'female', this.value)">
                            <button class="add-btn" onclick="showSHSModal('${strandName}', '${grade.key}', 'add')" title="Add Students">
                                <i class="fas fa-plus"></i>
                            </button>
                            <button class="reduce-btn" onclick="showSHSModal('${strandName}', '${grade.key}', 'reduce')" title="Reduce Students">
                                <i class="fas fa-minus"></i>
                            </button>
                        </td>
                        <td class="text-center child-total">${gradeData[grade.key].total}</td>
                        <td class="text-center"></td>
                    `;
                    strandRow.insertAdjacentElement('afterend', childRow);
                    setTimeout(() => childRow.classList.add('visible'), 10);
                });
                
                button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i> Hide Details';
            }
        }

        // Particle animation and initialization
        document.addEventListener('DOMContentLoaded', function() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 20;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                const size = Math.random() * 8 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                const duration = Math.random() * 10 + 10;
                particle.style.animationDuration = `${duration}s`;
                
                particle.style.background = `rgba(96, 165, 250, ${Math.random() * 0.3 + 0.1})`;
                
                particlesContainer.appendChild(particle);
            }
            
            // Progress bar animation
            setTimeout(() => {
                const progressBars = document.querySelectorAll('.progress-fill');
                progressBars.forEach(bar => {
                    const width = bar.style.width;
                    bar.style.width = '0';
                    setTimeout(() => {
                        bar.style.width = width;
                    }, 300);
                });
            }, 500);
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>