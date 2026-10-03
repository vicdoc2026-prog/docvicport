<?php
include '../conn.php';
require_once '../config/check-session.php';
require_once '../config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Default school year
$current_year = date('Y');
$default_school_year = ($current_year - 1) . '-' . $current_year; // Assuming school year starts in previous year

// Default semester
$default_semester = '1st';

// Messages
$jhs_message = '';
$shs_message = '';

// Process JHS form
if (isset($_POST['submit_jhs'])) {
    $school_year = mysqli_real_escape_string($conn, $_POST['school_year']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    
    $fields = [];
    for ($g = 7; $g <= 10; $g++) {
        $fields["grade_{$g}_male"] = (int)$_POST["grade_{$g}_male"] ?? 0;
        $fields["grade_{$g}_female"] = (int)$_POST["grade_{$g}_female"] ?? 0;
    }
    
    // Check if exists
    $check_sql = "SELECT COUNT(*) as cnt FROM jhs_students WHERE school_year = '$school_year' AND semester = '$semester'";
    $res = $conn->query($check_sql);
    $row = $res->fetch_assoc();
    
    if ($row['cnt'] > 0) {
        // Update
        $update_parts = [];
        foreach ($fields as $k => $v) {
            $update_parts[] = "$k = $v";
        }
        $update_sql = "UPDATE jhs_students SET " . implode(', ', $update_parts) . " WHERE school_year = '$school_year' AND semester = '$semester'";
        if ($conn->query($update_sql)) {
            $jhs_message = '<p class="text-green-600 font-semibold">JHS data updated successfully for ' . htmlspecialchars($school_year) . ' - ' . htmlspecialchars($semester) . ' Semester.</p>';
        } else {
            $jhs_message = '<p class="text-red-600 font-semibold">Error updating JHS data: ' . $conn->error . '.</p>';
        }
    } else {
        // Insert
        $keys = array_keys($fields);
        $vals = array_values($fields);
        $insert_sql = "INSERT INTO jhs_students (school_year, semester, " . implode(', ', $keys) . ") VALUES ('$school_year', '$semester', " . implode(', ', $vals) . ")";
        if ($conn->query($insert_sql)) {
            $jhs_message = '<p class="text-green-600 font-semibold">JHS data inserted successfully for ' . htmlspecialchars($school_year) . ' - ' . htmlspecialchars($semester) . ' Semester.</p>';
        } else {
            $jhs_message = '<p class="text-red-600 font-semibold">Error inserting JHS data: ' . $conn->error . '.</p>';
        }
    }
}

// Process SHS form
if (isset($_POST['submit_shs'])) {
    $school_year = mysqli_real_escape_string($conn, $_POST['school_year']);
    $semester = mysqli_real_escape_string($conn, $_POST['semester']);
    $strand_name = mysqli_real_escape_string($conn, $_POST['strand_name']);
    $grade_11_male = (int)$_POST['grade_11_male'] ?? 0;
    $grade_11_female = (int)$_POST['grade_11_female'] ?? 0;
    $grade_12_male = (int)$_POST['grade_12_male'] ?? 0;
    $grade_12_female = (int)$_POST['grade_12_female'] ?? 0;
    
    // Check if exists
    $check_sql = "SELECT COUNT(*) as cnt FROM shs_courses WHERE school_year = '$school_year' AND semester = '$semester' AND strand_name = '$strand_name'";
    $res = $conn->query($check_sql);
    $row = $res->fetch_assoc();
    
    if ($row['cnt'] > 0) {
        // Update
        $update_sql = "UPDATE shs_courses SET grade_11_male = $grade_11_male, grade_11_female = $grade_11_female, grade_12_male = $grade_12_male, grade_12_female = $grade_12_female WHERE school_year = '$school_year' AND semester = '$semester' AND strand_name = '$strand_name'";
        if ($conn->query($update_sql)) {
            $shs_message = '<p class="text-green-600 font-semibold">SHS strand updated successfully for ' . htmlspecialchars($school_year) . ' - ' . htmlspecialchars($semester) . ' Semester - ' . htmlspecialchars($strand_name) . '.</p>';
        } else {
            $shs_message = '<p class="text-red-600 font-semibold">Error updating SHS strand: ' . $conn->error . '.</p>';
        }
    } else {
        // Insert
        $insert_sql = "INSERT INTO shs_courses (school_year, semester, strand_name, grade_11_male, grade_11_female, grade_12_male, grade_12_female) VALUES ('$school_year', '$semester', '$strand_name', $grade_11_male, $grade_11_female, $grade_12_male, $grade_12_female)";
        if ($conn->query($insert_sql)) {
            $shs_message = '<p class="text-green-600 font-semibold">SHS strand inserted successfully for ' . htmlspecialchars($school_year) . ' - ' . htmlspecialchars($semester) . ' Semester - ' . htmlspecialchars($strand_name) . '.</p>';
        } else {
            $shs_message = '<p class="text-red-600 font-semibold">Error inserting SHS strand: ' . $conn->error . '.</p>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Insert Enrollment Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
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
            color: #00040bff;
            letter-spacing: 0.02em;
            margin-bottom: 1.5rem;
            margin-top: -1rem;
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

        input[type="number"] {
            appearance: textfield;
        }

        @media (max-width: 768px) {
            .brand-title { font-size: 2rem; }
            .dashboard-text { font-size: 1.5rem; }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="glass-container w-full p-6 md:p-10 relative z-10">
            <!-- Header -->
            <div class="text-center mb-10">
                <h1 class="brand-title glitch-effect" data-text="Sibugay Technical Institute Incorporated">
                    Sibugay Technical Institute Incorporated
                </h1>
                <p class="dashboard-text">Insert Enrollment Data</p>
            </div>

            <!-- JHS Insertion Form -->
            <div class="dashboard-card fade-in card-delay-1 rounded-xl p-6 mb-12">
                <h2 class="section-header flex items-center mb-4">
                    <i class="fas fa-school text-purple-500 mr-3"></i> Insert/Update JHS Data
                </h2>
                <?php echo $jhs_message; ?>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label for="school_year" class="block text-sm font-medium text-gray-700">School Year</label>
                        <input type="text" name="school_year" id="school_year" value="<?php echo htmlspecialchars($default_school_year); ?>" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <label for="semester" class="block text-sm font-medium text-gray-700">Semester</label>
                        <select name="semester" id="semester" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            <option value="1st" <?php echo ($default_semester == '1st') ? 'selected' : ''; ?>>1st Semester</option>
                            <option value="2nd" <?php echo ($default_semester == '2nd') ? 'selected' : ''; ?>>2nd Semester</option>
                        </select>
                    </div>
                    <?php for ($grade = 7; $grade <= 10; $grade++): ?>
                        <div>
                            <h3 class="font-bold mb-2">Grade <?php echo $grade; ?></h3>
                            <div class="flex gap-4">
                                <div class="flex-1">
                                    <label for="grade_<?php echo $grade; ?>_male" class="block text-sm font-medium text-gray-700">Male</label>
                                    <input type="number" name="grade_<?php echo $grade; ?>_male" id="grade_<?php echo $grade; ?>_male" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                                </div>
                                <div class="flex-1">
                                    <label for="grade_<?php echo $grade; ?>_female" class="block text-sm font-medium text-gray-700">Female</label>
                                    <input type="number" name="grade_<?php echo $grade; ?>_female" id="grade_<?php echo $grade; ?>_female" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>
                    <div class="col-span-2">
                        <button type="submit" name="submit_jhs" class="btn-primary w-full">Submit JHS Data</button>
                    </div>
                </form>
            </div>

            <!-- SHS Insertion Form -->
            <div class="dashboard-card fade-in card-delay-2 rounded-xl p-6">
                <h2 class="section-header flex items-center mb-4">
                    <i class="fas fa-graduation-cap text-blue-500 mr-3"></i> Insert/Update SHS Strand Data
                </h2>
                <?php echo $shs_message; ?>
                <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="col-span-2">
                        <label for="school_year_shs" class="block text-sm font-medium text-gray-700">School Year</label>
                        <input type="text" name="school_year" id="school_year_shs" value="<?php echo htmlspecialchars($default_school_year); ?>" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                    </div>
                    <div class="col-span-2">
                        <label for="semester_shs" class="block text-sm font-medium text-gray-700">Semester</label>
                        <select name="semester" id="semester_shs" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            <option value="1st" <?php echo ($default_semester == '1st') ? 'selected' : ''; ?>>1st Semester</option>
                            <option value="2nd" <?php echo ($default_semester == '2nd') ? 'selected' : ''; ?>>2nd Semester</option>
                        </select>
                    </div>
                    <div class="col-span-2">
                        <label for="strand_name" class="block text-sm font-medium text-gray-700">Strand Name</label>
                        <input type="text" name="strand_name" id="strand_name" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                    </div>
                    <div>
                        <h3 class="font-bold mb-2">Grade 11</h3>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label for="grade_11_male" class="block text-sm font-medium text-gray-700">Male</label>
                                <input type="number" name="grade_11_male" id="grade_11_male" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            </div>
                            <div class="flex-1">
                                <label for="grade_11_female" class="block text-sm font-medium text-gray-700">Female</label>
                                <input type="number" name="grade_11_female" id="grade_11_female" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            </div>
                        </div>
                    </div>
                    <div>
                        <h3 class="font-bold mb-2">Grade 12</h3>
                        <div class="flex gap-4">
                            <div class="flex-1">
                                <label for="grade_12_male" class="block text-sm font-medium text-gray-700">Male</label>
                                <input type="number" name="grade_12_male" id="grade_12_male" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            </div>
                            <div class="flex-1">
                                <label for="grade_12_female" class="block text-sm font-medium text-gray-700">Female</label>
                                <input type="number" name="grade_12_female" id="grade_12_female" min="0" class="mt-1 p-2 w-full border border-gray-300 rounded-lg" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-span-2">
                        <button type="submit" name="submit_shs" class="btn-primary w-full">Submit SHS Strand</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>