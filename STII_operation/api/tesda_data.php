<?php
include '../conn.php';
require_once '../config/check-session.php';
require_once '../config/conn.php';

$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Handle form submissions for insertions
$message = ''; // For success/error messages

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['insert_qualification'])) {
        // Insert into tesda_qualifications
        $qualification = mysqli_real_escape_string($conn, $_POST['qualification']);
        $ntr_wtr = mysqli_real_escape_string($conn, $_POST['ntr_wtr']);
        $ctpr_number = mysqli_real_escape_string($conn, $_POST['ctpr_number']);
        $date_issued = mysqli_real_escape_string($conn, $_POST['date_issued']);
        $validity_date = mysqli_real_escape_string($conn, $_POST['validity_date']);
        $no_of_hours = mysqli_real_escape_string($conn, $_POST['no_of_hours']);
        $no_of_days = mysqli_real_escape_string($conn, $_POST['no_of_days']);
        $training_cost = mysqli_real_escape_string($conn, $_POST['training_cost']);

        $sql = "INSERT INTO tesda_qualifications (qualification, ntr_wtr, ctpr_number, date_issued, validity_date, no_of_hours, no_of_days, training_cost)
                VALUES ('$qualification', '$ntr_wtr', '$ctpr_number', '$date_issued', '$validity_date', '$no_of_hours', '$no_of_days', '$training_cost')";

        if ($conn->query($sql) === TRUE) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">Qualification inserted successfully!</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: ' . $conn->error . '</div>';
        }
    } elseif (isset($_POST['insert_training_center'])) {
        // Insert into training_centers
        $center_name = mysqli_real_escape_string($conn, $_POST['center_name']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $accreditation_number = mysqli_real_escape_string($conn, $_POST['accreditation_number']);
        $date_accredited = mysqli_real_escape_string($conn, $_POST['date_accredited']);
        $validity_date = mysqli_real_escape_string($conn, $_POST['validity_date']);

        $sql = "INSERT INTO training_centers (center_name, address, accreditation_number, date_accredited, validity_date)
                VALUES ('$center_name', '$address', '$accreditation_number', '$date_accredited', '$validity_date')";

        if ($conn->query($sql) === TRUE) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">Training Center inserted successfully!</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: ' . $conn->error . '</div>';
        }
    } elseif (isset($_POST['insert_assessment_center'])) {
        // Insert into assessment_centers
        $center_name = mysqli_real_escape_string($conn, $_POST['center_name']);
        $qualifications = mysqli_real_escape_string($conn, $_POST['qualifications']);
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $accreditation_number = mysqli_real_escape_string($conn, $_POST['accreditation_number']);
        $date_accredited = mysqli_real_escape_string($conn, $_POST['date_accredited']);
        $validity_date = mysqli_real_escape_string($conn, $_POST['validity_date']);

        $sql = "INSERT INTO assessment_centers (center_name, qualifications, address, accreditation_number, date_accredited, validity_date)
                VALUES ('$center_name', '$qualifications', '$address', '$accreditation_number', '$date_accredited', '$validity_date')";

        if ($conn->query($sql) === TRUE) {
            $message = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">Assessment Center inserted successfully!</div>';
        } else {
            $message = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">Error: ' . $conn->error . '</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TESDA NC2 Insert Data</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* Reuse styles from the original dashboard */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
       
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff, #e6f7ff, #d1eeff);
            background-size: 400% 400%;
            animation: gradientBG 18s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
       
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .glass-container {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 28px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.1);
            flex: 1;
            width: 100%;
        }
        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 3.2rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #10b981);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .dashboard-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.6rem;
            color: #10b981;
        }
        .tab-btn {
            padding: 1rem 2rem;
            border-bottom: 4px solid transparent;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s;
        }
        .tab-btn:hover {
            color: #10b981;
            border-bottom-color: #10b981;
        }
        .tab-btn.active-tab {
            color: #10b981;
            border-bottom-color: #10b981;
        }
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            width: calc(100% - 280px);
        }
        .sidebar.collapsed ~ .main-content {
            margin-left: 90px;
            width: calc(100% - 90px);
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem;
            }
            .brand-title {
                font-size: 2rem;
            }
            .dashboard-text {
                font-size: 1.5rem;
            }
            .glass-container {
                padding: 1rem !important;
            }
            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Include sidebar -->
    <!-- Main Content -->
    <div class="main-content">
        <div class="glass-container w-full p-8 md:p-12 relative z-10">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="brand-title glitch-effect" data-text="Sibugay Technical Institute Incorporated">
                    Sibugay Technical Institute Incorporated
                </h1>
                <p class="dashboard-text">TESDA Insert Data</p>
            </div>
            <!-- Message -->
            <?php echo $message; ?>
            <!-- Tabs Section -->
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex flex-wrap gap-4 px-8 pt-6">
                        <button class="tab-btn active-tab" data-tab="insert-qualifications">
                            <i class="fas fa-list-check mr-2"></i> Insert NC2 Qualifications
                        </button>
                        <button class="tab-btn" data-tab="insert-training">
                            <i class="fas fa-building mr-2"></i> Insert Training Centers
                        </button>
                        <button class="tab-btn" data-tab="insert-assessment">
                            <i class="fas fa-clipboard-check mr-2"></i> Insert Assessment Centers
                        </button>
                    </nav>
                </div>
                <div class="p-8">
                    <!-- Insert Qualifications Tab -->
                    <div id="insert-qualifications" class="tab-content">
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="qualification" placeholder="Qualification" class="border p-2 rounded" required>
                            <input type="text" name="ntr_wtr" placeholder="NTR/WTR" class="border p-2 rounded" required>
                            <input type="text" name="ctpr_number" placeholder="CTPR Number" class="border p-2 rounded" required>
                            <input type="date" name="date_issued" placeholder="Date Issued" class="border p-2 rounded" required>
                            <input type="date" name="validity_date" placeholder="Validity Date" class="border p-2 rounded" required>
                            <input type="text" name="no_of_hours" placeholder="No. of Hours" class="border p-2 rounded" required>
                            <input type="text" name="no_of_days" placeholder="No. of Days" class="border p-2 rounded" required>
                            <input type="text" name="training_cost" placeholder="Training Cost" class="border p-2 rounded" required>
                            <button type="submit" name="insert_qualification" class="bg-blue-500 text-white p-2 rounded col-span-2">Insert Qualification</button>
                        </form>
                    </div>
                    <!-- Insert Training Centers Tab -->
                    <div id="insert-training" class="tab-content hidden">
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="center_name" placeholder="Center Name" class="border p-2 rounded" required>
                            <input type="text" name="address" placeholder="Address" class="border p-2 rounded" required>
                            <input type="text" name="accreditation_number" placeholder="Accreditation Number" class="border p-2 rounded" required>
                            <input type="date" name="date_accredited" placeholder="Date Accredited" class="border p-2 rounded" required>
                            <input type="date" name="validity_date" placeholder="Validity Date" class="border p-2 rounded" required>
                            <button type="submit" name="insert_training_center" class="bg-blue-500 text-white p-2 rounded col-span-2">Insert Training Center</button>
                        </form>
                    </div>
                    <!-- Insert Assessment Centers Tab -->
                    <div id="insert-assessment" class="tab-content hidden">
                        <form method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <input type="text" name="center_name" placeholder="Center Name" class="border p-2 rounded" required>
                            <input type="text" name="qualifications" placeholder="Qualifications" class="border p-2 rounded" required>
                            <input type="text" name="address" placeholder="Address" class="border p-2 rounded" required>
                            <input type="text" name="accreditation_number" placeholder="Accreditation Number" class="border p-2 rounded" required>
                            <input type="date" name="date_accredited" placeholder="Date Accredited" class="border p-2 rounded" required>
                            <input type="date" name="validity_date" placeholder="Validity Date" class="border p-2 rounded" required>
                            <button type="submit" name="insert_assessment_center" class="bg-blue-500 text-white p-2 rounded col-span-2">Insert Assessment Center</button>
                        </form>
                    </div>
                </div>
            </div>
            <!-- Footer -->
            <div class="text-center mt-12 text-gray-600">
                © 2025 Doc Vic – JIMZ All rights reserved.
            </div>
        </div>
    </div>
    <script>
        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.add('active-tab');
                document.getElementById(btn.dataset.tab).classList.remove('hidden');
            });
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>