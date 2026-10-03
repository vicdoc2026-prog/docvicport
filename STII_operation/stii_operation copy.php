<?php
include 'conn.php';

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];



// Handle AJAX requests for adding/editing students
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'add_student') {
        $course_name = $_POST['course_name'];
        $year_level = $_POST['year_level'];
        $gender = $_POST['gender'];
        $count = intval($_POST['count']);
        $operation = $_POST['operation'] ?? 'add'; // 'add' or 'reduce'
        
        $field = $year_level . '_' . strtolower($gender);
        
        // First get current count to ensure we don't go below 0
        $check_sql = "SELECT $field FROM courses WHERE course_name = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $course_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $current_data = $result->fetch_assoc();
        $current_count = $current_data[$field];
        
        if ($operation === 'add') {
            $sql = "UPDATE courses SET $field = $field + ? WHERE course_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $count, $course_name);
        } else { // reduce
            $new_count = max(0, $current_count - $count); // Ensure we don't go below 0
            $sql = "UPDATE courses SET $field = ? WHERE course_name = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $new_count, $course_name);
        }
        
        if ($stmt->execute()) {
            // Return the actual new count
            $final_count = ($operation === 'add') ? $current_count + $count : $new_count;
            echo json_encode(['success' => true, 'new_count' => $final_count]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
    
    if ($_POST['action'] === 'update_student') {
        $course_name = $_POST['course_name'];
        $year_level = $_POST['year_level'];
        $gender = $_POST['gender'];
        $count = intval($_POST['count']);
        
        $field = $year_level . '_' . strtolower($gender);
        
        $sql = "UPDATE courses SET $field = ? WHERE course_name = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $count, $course_name);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $conn->error]);
        }
        exit();
    }
}

// Fetch course data
$sql = "SELECT course_name, 
        first_year_male, first_year_female, second_year_male, second_year_female, 
        third_year_male, third_year_female, fourth_year_male, fourth_year_female 
        FROM courses";
$result = $conn->query($sql);

// Calculate totals
$courses_data = [];
$total_students = 0;
$total_male = 0;
$total_female = 0;
$year_totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

while ($row = $result->fetch_assoc()) {
    // Calculate totals for this course
    $course_male = $row['first_year_male'] + $row['second_year_male'] + $row['third_year_male'] + $row['fourth_year_male'];
    $course_female = $row['first_year_female'] + $row['second_year_female'] + $row['third_year_female'] + $row['fourth_year_female'];
    $course_total = $course_male + $course_female;
    
    $row['male_students'] = $course_male;
    $row['female_students'] = $course_female;
    $row['total_students'] = $course_total;
    
    $courses_data[] = $row;
    
    // Add to overall totals
    $total_students += $course_total;
    $total_male += $course_male;
    $total_female += $course_female;
    
    // Add to year totals
    $year_totals[1] += $row['first_year_male'] + $row['first_year_female'];
    $year_totals[2] += $row['second_year_male'] + $row['second_year_female'];
    $year_totals[3] += $row['third_year_male'] + $row['third_year_female'];
    $year_totals[4] += $row['fourth_year_male'] + $row['fourth_year_female'];
}

$total_courses = count($courses_data);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School Management Dashboard</title>
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

    .year-level-card { transition: all 0.3s ease; }
    .year-level-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); }

    .course-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .course-table th, .course-table td { padding: 14px; text-align: left; border-bottom: 1px solid #e2e8f0; }
    .course-table th { background-color: #3b82f6; color: white; font-weight: 600; }
    .course-table tr:nth-child(even) { background-color: #f8fafc; }
    .course-table tr:hover { background-color: rgba(59, 130, 246, 0.1); cursor: pointer; }
    .course-table th:first-child, .course-table td:first-child { width: 40%; }
    .course-table th:not(:first-child), .course-table td:not(:first-child) { width: 15%; text-align: center; }
    .course-table th:first-child { border-top-left-radius: 8px; }
    .course-table th:last-child { border-top-right-radius: 8px; }

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
    .child-row:hover .reduce-btn {
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

    .switch-btn {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      font-weight: 600;
      border-radius: 20px;
      padding: 8px 16px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
      border: none;
      cursor: pointer;
    }

    .switch-btn:hover {
      background: #1e40af;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(59,130,246,0.4);
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

    .nav-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1rem;
      gap: 1rem;
    }

    .nav-left {
      display: flex;
      gap: 0.5rem;
    }

    .tesda-btn {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      font-weight: 600;
      border-radius: 20px;
      padding: 8px 16px;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 6px;
      border: none;
      cursor: pointer;
    }

    .tesda-btn:hover {
      background: #047857;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
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
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      border-color: #3b82f6;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .nav-link.tesda {
      color: #059669;
    }

    .nav-link.tesda:hover {
      background: rgba(16, 185, 129, 0.1);
      color: #047857;
    }

    .nav-link.highschool {
      color: #7c3aed;
    }

    .nav-link.highschool:hover {
      background: rgba(124, 58, 237, 0.1);
      color: #6d28d9;
    }

    @media (max-width: 768px) {
      .brand-title { font-size: 2.5rem; }
      .dashboard-text { font-size: 1.25rem; }
      .course-table th, .course-table td { padding: 10px; font-size: 0.85rem; }
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

  <!-- Floating decorative elements -->
  <div class="absolute top-20 right-20 w-16 h-16 rounded-full bg-blue-200 opacity-40 animate-pulse"></div>
  <div class="absolute bottom-40 left-10 w-24 h-24 rounded-full bg-blue-100 opacity-30 animate-ping"></div>

  <!-- Add/Reduce Student Modal -->
  <div id="addStudentModal" class="modal">
    <div class="modal-content">
      <h2 class="text-2xl font-bold text-gray-800 mb-6" id="modalTitle">Add Students</h2>
      <form id="addStudentForm">
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Course</label>
          <input type="text" id="courseName" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100">
        </div>
        <div class="mb-4">
          <label class="block text-gray-700 text-sm font-bold mb-2">Year Level</label>
          <input type="text" id="yearLevel" readonly class="w-full p-3 border border-gray-300 rounded-lg bg-gray-100">
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
        <button onclick="window.location='stii_operation_college.php'" class="nav-link active">
          <i class="fas fa-graduation-cap"></i> College
        </button>
        <button onclick="window.location='stii_operation_SHS.php'" class="nav-link highschool">
          <i class="fas fa-school"></i> High School
        </button>
        <button onclick="window.location='tesda_dashboard.php'" class="nav-link tesda">
          <i class="fas fa-certificate"></i> TESDA
        </button>
      </div>
    </nav>

    <!-- Header -->
    <div class="text-center mb-10">
      <h1 class="brand-title glitch-effect" data-text="Sibugay Technical Institute Incorporated">
        Sibugay Technical Institute Incorporated
      </h1>
      <p class="dashboard-text">College Student Enrollment Dashboard</p>
      <p class="text-slate-600 max-w-2xl mx-auto mt-3 font-semibold">
        1st Semester, School Year 2025 - 2026
      </p>
    </div>

    <!-- Summary Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12 min-h-[200px]">
      <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 text-center">
        <i class="fas fa-graduation-cap dashboard-icon text-blue-500"></i>
        <div class="stat-value" id="totalStudents"><?php echo number_format($total_students); ?></div>
        <div class="stat-label">Total Students</div>
      </div>
      <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 text-center">
        <i class="fas fa-book dashboard-icon text-green-500"></i>
        <div class="stat-value"><?php echo $total_courses; ?></div>
        <div class="stat-label">Courses Offered</div>
      </div>
      <div class="dashboard-card fade-in card-delay-3 rounded-xl p-6 text-center">
        <i class="fas fa-male dashboard-icon text-purple-500"></i>
        <div class="stat-value" id="totalMale"><?php echo number_format($total_male); ?></div>
        <div class="stat-label">Male Students</div>
      </div>
      <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 text-center">
        <i class="fas fa-female dashboard-icon text-pink-500"></i>
        <div class="stat-value" id="totalFemale"><?php echo number_format($total_female); ?></div>
        <div class="stat-label">Female Students</div>
      </div>
    </div>

    <!-- Main Content -->
    <div class="space-y-8">
      <!-- Year Level Distribution -->
      <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6 mb-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center">
          <i class="fas fa-users text-blue-500 mr-3"></i> Student Distribution by Year Level
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div class="year-level-card bg-gradient-to-br from-blue-100 to-blue-50 rounded-xl p-5 text-center">
            <div class="text-4xl font-bold text-blue-700" id="firstYearTotal"><?php echo number_format($year_totals[1]); ?></div>
            <div class="font-medium text-blue-800">1st Year</div>
            <div class="text-sm text-blue-600 mt-2" id="firstYearPercent"><?php echo number_format(($year_totals[1] / max($total_students, 1)) * 100, 1); ?>% of total</div>
            <div class="progress-bar mt-3">
              <div class="progress-fill bg-blue-500" id="firstYearProgress" style="width: <?php echo ($year_totals[1] / max($total_students, 1)) * 100; ?>%"></div>
            </div>
          </div>
          
          <div class="year-level-card bg-gradient-to-br from-green-100 to-green-50 rounded-xl p-5 text-center">
            <div class="text-4xl font-bold text-green-700" id="secondYearTotal"><?php echo number_format($year_totals[2]); ?></div>
            <div class="font-medium text-green-800">2nd Year</div>
            <div class="text-sm text-green-600 mt-2" id="secondYearPercent"><?php echo number_format(($year_totals[2] / max($total_students, 1)) * 100, 1); ?>% of total</div>
            <div class="progress-bar mt-3">
              <div class="progress-fill bg-green-500" id="secondYearProgress" style="width: <?php echo ($year_totals[2] / max($total_students, 1)) * 100; ?>%"></div>
            </div>
          </div>
          
          <div class="year-level-card bg-gradient-to-br from-amber-100 to-amber-50 rounded-xl p-5 text-center">
            <div class="text-4xl font-bold text-amber-700" id="thirdYearTotal"><?php echo number_format($year_totals[3]); ?></div>
            <div class="font-medium text-amber-800">3rd Year</div>
            <div class="text-sm text-amber-600 mt-2" id="thirdYearPercent"><?php echo number_format(($year_totals[3] / max($total_students, 1)) * 100, 1); ?>% of total</div>
            <div class="progress-bar mt-3">
              <div class="progress-fill bg-amber-500" id="thirdYearProgress" style="width: <?php echo ($year_totals[3] / max($total_students, 1)) * 100; ?>%"></div>
            </div>
          </div>
          
          <div class="year-level-card bg-gradient-to-br from-purple-100 to-purple-50 rounded-xl p-5 text-center">
            <div class="text-4xl font-bold text-purple-700" id="fourthYearTotal"><?php echo number_format($year_totals[4]); ?></div>
            <div class="font-medium text-purple-800">4th Year</div>
            <div class="text-sm text-purple-600 mt-2" id="fourthYearPercent"><?php echo number_format(($year_totals[4] / max($total_students, 1)) * 100, 1); ?>% of total</div>
            <div class="progress-bar mt-3">
              <div class="progress-fill bg-purple-500" id="fourthYearProgress" style="width: <?php echo ($year_totals[4] / max($total_students, 1)) * 100; ?>%"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Courses Offered Table (Full Width) -->
    <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6">
      <h2 class="text-xl font-bold text-slate-800 mb-4 flex items-center">
        <i class="fas fa-list-ul text-blue-500 mr-3"></i> Courses Offered (<?php echo $total_courses; ?>)
      </h2>
      <div class="overflow-x-auto">
        <table class="course-table">
          <thead>
            <tr>
              <th>Course Name</th>
              <th>Male</th>
              <th>Female</th>
              <th>Total</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="coursesTableBody">
            <?php foreach ($courses_data as $row): ?>
              <tr class="course-row" data-course="<?php echo htmlspecialchars($row['course_name']); ?>" data-years='{
                "first_year":{"male":<?php echo $row['first_year_male']; ?>,"female":<?php echo $row['first_year_female']; ?>,"total":<?php echo $row['first_year_male'] + $row['first_year_female']; ?>},
                "second_year":{"male":<?php echo $row['second_year_male']; ?>,"female":<?php echo $row['second_year_female']; ?>,"total":<?php echo $row['second_year_male'] + $row['second_year_female']; ?>},
                "third_year":{"male":<?php echo $row['third_year_male']; ?>,"female":<?php echo $row['third_year_female']; ?>,"total":<?php echo $row['third_year_male'] + $row['third_year_female']; ?>},
                "fourth_year":{"male":<?php echo $row['fourth_year_male']; ?>,"female":<?php echo $row['fourth_year_female']; ?>,"total":<?php echo $row['fourth_year_male'] + $row['fourth_year_female']; ?>}
              }'>
                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                <td class="text-center course-male"><?php echo $row['male_students']; ?></td>
                <td class="text-center course-female"><?php echo $row['female_students']; ?></td>
                <td class="text-center course-total"><?php echo $row['total_students']; ?></td>
                <td class="text-center">
                  <button class="btn-primary text-xs px-3 py-1" onclick="toggleCourseDetails(this)">
                    <i class="fas fa-eye mr-1"></i> View Details
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <!-- Footer -->
    <div class="mt-10 pt-6 border-t border-slate-200 text-center text-slate-600 text-sm">
      &copy; 2025 School Management System. All rights reserved.
    </div>
  </div>

  <script>
    function switchDashboard() {
      window.location.href = "stii_operation_SHS.php";
    }

    let currentCourseData = {};

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
      
      const courseName = document.getElementById('courseName').value;
      const yearLevel = document.getElementById('yearLevel').value;
      const gender = document.getElementById('gender').value;
      const count = document.getElementById('studentCount').value;
      
      if (!count || count <= 0) {
        alert('Please enter a valid number of students');
        return;
      }
      
      // Send AJAX request
      const operation = document.getElementById('operation').value;
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=add_student&course_name=${encodeURIComponent(courseName)}&year_level=${yearLevel}&gender=${gender}&count=${count}&operation=${operation}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateCourseDataDirect(courseName, yearLevel, gender, data.new_count);
          modal.style.display = 'none';
          document.getElementById('studentCount').value = '';
          document.getElementById('operation').value = 'add';
          updateModalTitle();
          alert(`Students ${operation === 'add' ? 'added' : 'reduced'} successfully!`);
        } else {
          alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} students: ` + (data.error || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert(`Error ${operation === 'add' ? 'adding' : 'reducing'} students`);
      });
    });

    // Update student count (for editing)
    function updateStudentCount(courseName, yearLevel, gender, newValue) {
      fetch('', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_student&course_name=${encodeURIComponent(courseName)}&year_level=${yearLevel}&gender=${gender}&count=${newValue}`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          updateAllTotals();
        } else {
          alert('Error updating student count: ' + (data.error || 'Unknown error'));
          location.reload();
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error updating student count');
        location.reload();
      });
    }

    // Update course data in UI
    function updateCourseData(courseName, yearLevel, gender, count, action = 'add') {
      const courseRow = document.querySelector(`[data-course="${courseName}"]`);
      if (!courseRow) return;
      
      const yearData = JSON.parse(courseRow.getAttribute('data-years'));
      
      if (action === 'add') {
        yearData[yearLevel][gender] += count;
      } else {
        yearData[yearLevel][gender] = count;
      }
      
      yearData[yearLevel].total = yearData[yearLevel].male + yearData[yearLevel].female;
      courseRow.setAttribute('data-years', JSON.stringify(yearData));
      
      let courseMale = 0, courseFemale = 0, courseTotal = 0;
      Object.keys(yearData).forEach(year => {
        courseMale += yearData[year].male;
        courseFemale += yearData[year].female;
        courseTotal += yearData[year].total;
      });
      
      courseRow.querySelector('.course-male').textContent = courseMale;
      courseRow.querySelector('.course-female').textContent = courseFemale;
      courseRow.querySelector('.course-total').textContent = courseTotal;
      
      const childRows = getChildRowsForCourse(courseRow);
      childRows.forEach(childRow => {
        const yearLevel = childRow.getAttribute('data-year');
        if (yearLevel && yearData[yearLevel]) {
          const maleCell = childRow.querySelector('.child-male');
          const femaleCell = childRow.querySelector('.child-female');
          const totalCell = childRow.querySelector('.child-total');
          
          if (maleCell) maleCell.textContent = yearData[yearLevel].male;
          if (femaleCell) femaleCell.textContent = yearData[yearLevel].female;
          if (totalCell) totalCell.textContent = yearData[yearLevel].total;
        }
      });
      
      updateAllTotals();
    }

    // Get child rows for a course
    function getChildRowsForCourse(courseRow) {
      const childRows = [];
      let nextRow = courseRow.nextElementSibling;
      while (nextRow && nextRow.classList.contains('child-row')) {
        childRows.push(nextRow);
        nextRow = nextRow.nextElementSibling;
      }
      return childRows;
    }

    // Update all totals (dashboard cards and year level distribution)
    function updateAllTotals() {
      let totalStudents = 0;
      let totalMale = 0;
      let totalFemale = 0;
      let yearTotals = { first_year: 0, second_year: 0, third_year: 0, fourth_year: 0 };
      
      document.querySelectorAll('.course-row').forEach(row => {
        const yearData = JSON.parse(row.getAttribute('data-years'));
        
        Object.keys(yearData).forEach(year => {
          totalMale += yearData[year].male;
          totalFemale += yearData[year].female;
          totalStudents += yearData[year].total;
          yearTotals[year] += yearData[year].total;
        });
      });
      
      document.getElementById('totalStudents').textContent = totalStudents.toLocaleString();
      document.getElementById('totalMale').textContent = totalMale.toLocaleString();
      document.getElementById('totalFemale').textContent = totalFemale.toLocaleString();
      
      const yearMapping = {
        'first_year': { total: 'firstYearTotal', percent: 'firstYearPercent', progress: 'firstYearProgress' },
        'second_year': { total: 'secondYearTotal', percent: 'secondYearPercent', progress: 'secondYearProgress' },
        'third_year': { total: 'thirdYearTotal', percent: 'thirdYearPercent', progress: 'thirdYearProgress' },
        'fourth_year': { total: 'fourthYearTotal', percent: 'fourthYearPercent', progress: 'fourthYearProgress' }
      };
      
      Object.keys(yearTotals).forEach(year => {
        const ids = yearMapping[year];
        const percentage = totalStudents > 0 ? (yearTotals[year] / totalStudents * 100) : 0;
        
        document.getElementById(ids.total).textContent = yearTotals[year].toLocaleString();
        document.getElementById(ids.percent).textContent = `${percentage.toFixed(1)}% of total`;
        document.getElementById(ids.progress).style.width = `${percentage}%`;
      });
    }

    // Update course data directly with new value
    function updateCourseDataDirect(courseName, yearLevel, gender, newValue) {
      const courseRow = document.querySelector(`[data-course="${courseName}"]`);
      if (!courseRow) return;
      
      const yearData = JSON.parse(courseRow.getAttribute('data-years'));
      yearData[yearLevel][gender] = newValue;
      yearData[yearLevel].total = yearData[yearLevel].male + yearData[yearLevel].female;
      courseRow.setAttribute('data-years', JSON.stringify(yearData));
      
      let courseMale = 0, courseFemale = 0, courseTotal = 0;
      Object.keys(yearData).forEach(year => {
        courseMale += yearData[year].male;
        courseFemale += yearData[year].female;
        courseTotal += yearData[year].total;
      });
      
      courseRow.querySelector('.course-male').textContent = courseMale;
      courseRow.querySelector('.course-female').textContent = courseFemale;
      courseRow.querySelector('.course-total').textContent = courseTotal;
      
      const childRows = getChildRowsForCourse(courseRow);
      childRows.forEach(childRow => {
        const yearLevel = childRow.getAttribute('data-year');
        if (yearLevel && yearData[yearLevel]) {
          const maleInput = childRow.querySelector('.child-male');
          const femaleInput = childRow.querySelector('.child-female');
          const totalCell = childRow.querySelector('.child-total');
          
          if (maleInput) maleInput.value = yearData[yearLevel].male;
          if (femaleInput) femaleInput.value = yearData[yearLevel].female;
          if (totalCell) totalCell.textContent = yearData[yearLevel].total;
        }
      });
      
      updateAllTotals();
    }

    // Update modal title and button text based on operation
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

    // Show add student modal
    function showAddStudentModal(courseName, yearLevel, operation = 'add') {
      const courseRow = document.querySelector(`[data-course="${courseName}"]`);
      const yearData = JSON.parse(courseRow.getAttribute('data-years'));
      
      document.getElementById('courseName').value = courseName;
      document.getElementById('yearLevel').value = yearLevel;
      document.getElementById('gender').value = 'male';
      document.getElementById('operation').value = operation;
      document.getElementById('studentCount').value = '';
      
      const currentCountInfo = document.getElementById('currentCountInfo');
      const yearKey = yearLevel;
      const currentMale = yearData[yearKey].male;
      const currentFemale = yearData[yearKey].female;
      currentCountInfo.innerHTML = `Current count: <strong>Male: ${currentMale}, Female: ${currentFemale}</strong>`;
      
      updateModalTitle();
      modal.style.display = 'block';
      
      document.getElementById('gender').onchange = function() {
        const selectedGender = this.value;
        const currentCount = yearData[yearKey][selectedGender];
        currentCountInfo.innerHTML = `Current ${selectedGender} count: <strong>${currentCount}</strong>`;
      };
      
      document.getElementById('gender').onchange();
    }

    // Toggle course details
    function toggleCourseDetails(button) {
      const courseRow = button.closest('.course-row');
      const courseName = courseRow.getAttribute('data-course');
      const yearData = JSON.parse(courseRow.getAttribute('data-years'));
      
      let nextRow = courseRow.nextElementSibling;
      const hasChildRows = nextRow && nextRow.classList.contains('child-row');
      
      if (hasChildRows) {
        while (nextRow && nextRow.classList.contains('child-row')) {
          const toRemove = nextRow;
          nextRow = nextRow.nextElementSibling;
          toRemove.remove();
        }
        button.innerHTML = '<i class="fas fa-eye mr-1"></i> View Details';
      } else {
        const years = [
          { key: 'first_year', label: '1st Year' },
          { key: 'second_year', label: '2nd Year' },
          { key: 'third_year', label: '3rd Year' },
          { key: 'fourth_year', label: '4th Year' }
        ];
        
        years.reverse().forEach(year => {
          const childRow = document.createElement('tr');
          childRow.classList.add('child-row');
          childRow.setAttribute('data-year', year.key);
          childRow.innerHTML = `
            <td>${year.label}</td>
            <td class="text-center">
              <input type="number" value="${yearData[year.key].male}" 
                     class="edit-input child-male" min="0" 
                     onchange="updateStudentCount('${courseName}', '${year.key}', 'male', this.value)">
            </td>
            <td class="text-center">
              <input type="number" value="${yearData[year.key].female}" 
                     class="edit-input child-female" min="0"
                     onchange="updateStudentCount('${courseName}', '${year.key}', 'female', this.value)">
            </td>
            <td class="text-center child-total">${yearData[year.key].total}</td>
            <td class="text-center" style="position: relative;">
              <button class="add-btn" onclick="showAddStudentModal('${courseName}', '${year.key}', 'add')" title="Add Students">
                <i class="fas fa-plus"></i>
              </button>
              <button class="reduce-btn" onclick="showAddStudentModal('${courseName}', '${year.key}', 'reduce')" title="Reduce Students">
                <i class="fas fa-minus"></i>
              </button>
            </td>
          `;
          courseRow.insertAdjacentElement('afterend', childRow);
          setTimeout(() => childRow.classList.add('visible'), 10);
        });
        
        button.innerHTML = '<i class="fas fa-eye-slash mr-1"></i> Hide Details';
      }
    }

    // Particle animation and other initialization
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