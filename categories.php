<?php
include 'STII_operation/conn.php';

// Include session check - this will redirect if not logged in
require_once 'config/check-session.php';
require_once 'config/conn.php';
require_once 'config/role-access.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$profile_image = $_SESSION['profile_image'] ?? '';
$role = $_SESSION['role'];
$name_parts = preg_split('/\s+/', trim($full_name));
$avatar_initials = strtoupper(substr($name_parts[0] ?? '', 0, 1));
if (count($name_parts) > 1) {
    $avatar_initials .= strtoupper(substr(end($name_parts), 0, 1));
}
$avatar_role_class = [
    'stii_admin' => 'stii-admin',
    'belvic_admin' => 'belvic-admin',
    'admin' => 'system-admin',
][$role] ?? 'user';

// Fetch course data (College)
$sql_college = "SELECT 
        SUM(first_year_male + first_year_female + 
            second_year_male + second_year_female + 
            third_year_male + third_year_female + 
            fourth_year_male + fourth_year_female) AS total_students,
        COUNT(*) AS total_courses
        FROM courses";
$result_college = $conn->query($sql_college);
$data_college = $result_college->fetch_assoc();
$total_college_students = $data_college['total_students'] ?? 0;
$total_courses = $data_college['total_courses'] ?? 0;

// Fetch totals from shs_courses and jhs_students (SHS and JHS)
$shs_sql = "SELECT 
        SUM(grade_11_male + grade_11_female + 
            grade_12_male + grade_12_female) AS total_shs_students,
        COUNT(*) AS total_strands
        FROM shs_courses";
$shs_result = $conn->query($shs_sql);
$data_shs = $shs_result->fetch_assoc();
$total_shs_students = $data_shs['total_shs_students'] ?? 0;
$total_strands = $data_shs['total_strands'] ?? 0;

$jhs_sql = "SELECT 
        SUM(grade_7_male + grade_7_female + 
            grade_8_male + grade_8_female + 
            grade_9_male + grade_9_female + 
            grade_10_male + grade_10_male) AS total_jhs_students
        FROM jhs_students WHERE id = 1";
$jhs_result = $conn->query($jhs_sql);
$data_jhs = $jhs_result->fetch_assoc();
$total_jhs_students = $data_jhs['total_jhs_students'] ?? 0;

$total_shs_jhs_students = $total_shs_students + $total_jhs_students;


$ordinanceCount = getOrdinanceCount($conn);
$resolutionCount = getResolutionCount($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Belvic Centerpoint - 2050 Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&family=Rajdhani:wght@600;700&display=swap');
        
        :root {
            --primary-blue: #3b82f6;
            --primary-purple: #8b5cf6;
            --primary-green: #10b981;
            --light-bg: #f8fafc;
            --card-bg: rgba(255, 255, 255, 0.9);
            --glass-border: rgba(255, 255, 255, 0.5);
        }
        
        body { 
            font-family: 'Inter', sans-serif; 
            background: linear-gradient(135deg, #f0f9ff, #e6f7ff, #d1eeff);
            background-size: 400% 400%;
            animation: gradientBG 18s ease infinite;
            min-height: 100vh;
            overflow-x: hidden;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            box-shadow: 
                0 12px 40px rgba(0, 0, 0, 0.08),
                0 0 80px rgba(59, 130, 246, 0.1);
            position: relative;
        }
        
        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* LOGOUT CONFIRMATION MODAL */
        .logout-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .logout-modal-overlay.show {
            display: flex;
            opacity: 1;
        }
        
        .logout-modal {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
            backdrop-filter: blur(20px);
            border: 2px solid rgba(239, 68, 68, 0.3);
            border-radius: 20px;
            box-shadow: 
                0 25px 70px rgba(0, 0, 0, 0.3),
                0 0 50px rgba(239, 68, 68, 0.2),
                inset 0 0 40px rgba(239, 68, 68, 0.05);
            padding: 2.5rem;
            max-width: 450px;
            width: 90%;
            position: relative;
            overflow: hidden;
            transform: scale(0.8);
            opacity: 0;
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        
        .logout-modal-overlay.show .logout-modal {
            transform: scale(1);
            opacity: 1;
        }
        
        .logout-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(239, 68, 68, 0.08), transparent);
            animation: shimmer 3s infinite;
        }
        
        .modal-icon-wrapper {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            position: relative;
            box-shadow: 
                0 10px 30px rgba(239, 68, 68, 0.4),
                0 0 40px rgba(239, 68, 68, 0.3);
            animation: iconPulse 2s infinite;
        }
        
        .modal-icon-wrapper::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 3px solid #ef4444;
            animation: ripple 2s infinite;
        }
        
        .modal-icon-wrapper i {
            color: white;
            font-size: 2.5rem;
            animation: iconShake 1s ease;
        }
        
        @keyframes iconShake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-10deg); }
            75% { transform: rotate(10deg); }
        }
        
        .modal-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            text-align: center;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.8rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }
        
        .modal-message {
            text-align: center;
            color: #475569;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        
        .modal-btn {
            flex: 1;
            padding: 0.9rem 1.5rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            font-family: 'Rajdhani', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .modal-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .modal-btn:hover::before {
            left: 100%;
        }
        
        .btn-confirm {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
        }
        
        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }
        
        .btn-cancel {
            background: linear-gradient(135deg, #64748b, #475569);
            color: white;
            box-shadow: 0 4px 15px rgba(100, 116, 139, 0.3);
        }
        
        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(100, 116, 139, 0.4);
        }
        
        @media (max-width: 768px) {
            .logout-modal {
                padding: 2rem;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
        
        /* CUSTOM SUCCESS ALERT */
        .cyber-success-alert {
            position: sticky;
            top: 30px;
            right: 30px;
            margin-left: auto;
            margin-bottom: 2rem;
            min-width: 380px;
            max-width: 450px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95), rgba(248, 250, 252, 0.95));
            backdrop-filter: blur(20px);
            border: 2px solid rgba(59, 130, 246, 0.3);
            border-radius: 16px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.15),
                0 0 40px rgba(59, 130, 246, 0.2),
                inset 0 0 30px rgba(59, 130, 246, 0.05);
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            z-index: 99999;
            opacity: 0;
            transform: translateX(500px) scale(0.8);
            transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
            overflow: hidden;
        }
        
        .cyber-success-alert.show {
            opacity: 1;
            transform: translateX(0) scale(1);
        }
        
        .cyber-success-alert.hide {
            opacity: 0;
            transform: translateX(500px) scale(0.8);
        }
        
        .cyber-success-alert::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .alert-icon-wrapper {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            box-shadow: 
                0 8px 20px rgba(59, 130, 246, 0.4),
                0 0 30px rgba(139, 92, 246, 0.3);
            animation: iconPulse 2s infinite;
        }
        
        @keyframes iconPulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 
                    0 8px 20px rgba(59, 130, 246, 0.4),
                    0 0 30px rgba(139, 92, 246, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 
                    0 12px 30px rgba(59, 130, 246, 0.5),
                    0 0 40px rgba(139, 92, 246, 0.4);
            }
        }
        
        .alert-icon-wrapper::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px solid var(--primary-blue);
            animation: ripple 2s infinite;
        }
        
        @keyframes ripple {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        
        .alert-icon-wrapper i {
            color: white;
            font-size: 1.8rem;
            animation: checkBounce 0.6s ease;
        }
        
        @keyframes checkBounce {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }
            50% {
                transform: scale(1.2) rotate(5deg);
            }
            100% {
                transform: scale(1) rotate(0deg);
                opacity: 1;
            }
        }
        
        .alert-content {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .alert-title {
            font-family: 'Rajdhani', sans-serif;
            font-size: 1.3rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.3rem;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        
        .alert-message {
            font-size: 0.95rem;
            color: #475569;
            line-height: 1.5;
        }
        
        .alert-message strong {
            color: #1e293b;
            font-weight: 600;
        }
        
        .alert-close-btn {
            background: rgba(59, 130, 246, 0.1);
            border: none;
            color: var(--primary-blue);
            cursor: pointer;
            font-size: 1.5rem;
            padding: 8px;
            line-height: 1;
            border-radius: 8px;
            transition: all 0.3s;
            flex-shrink: 0;
            position: relative;
            z-index: 1;
        }
        
        .alert-close-btn:hover {
            background: rgba(59, 130, 246, 0.2);
            transform: rotate(90deg);
        }
        
        .alert-progress {
            position: absolute;
            bottom: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-blue), var(--primary-purple), var(--primary-green));
            border-radius: 0 0 14px 14px;
            animation: progressBar 5s linear;
        }
        
        @keyframes progressBar {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .cyber-success-alert {
                right: 15px;
                left: 15px;
                min-width: auto;
                max-width: none;
            }
        }
        
        .cyber-card {
            background: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
        }
        
        .cyber-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.05), transparent);
            transition: left 0.6s;
        }
        
        .cyber-card:hover::before {
            left: 100%;
        }
        
        .cyber-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        
        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 3.0rem;
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--primary-purple) 50%, var(--primary-green) 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            margin-bottom: 0.5rem;
            letter-spacing: 2px;
        }
        
        .dashboard-text {
            font-family: 'Rajdhani', sans-serif;
            font-weight: 700;
            font-size: 2rem;
            color: var(--primary-blue);
            margin-bottom: 2rem;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            font-family: 'Rajdhani', sans-serif;
        }
        
        .cyber-btn {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            border: none;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            padding: 12px 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .cyber-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }
        
        .cyber-btn:hover::before {
            left: 100%;
        }
        
        .cyber-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
        }
        
        .pulse-glow {
            animation: pulse-glow 3s infinite alternate;
        }
        
        @keyframes pulse-glow {
            from { 
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.2); 
            }
            to { 
                box-shadow: 0 0 30px rgba(59, 130, 246, 0.4); 
            }
        }
        
        .floating {
            animation: floating 6s ease-in-out infinite;
        }
        
        @keyframes floating {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-blue), var(--primary-purple));
            transition: all 0.4s ease;
        }
        
        .president-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.9));
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            position: relative;
        }
        
        .president-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }
        
        .data-stream {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.03), transparent);
            animation: dataStream 8s linear infinite;
            pointer-events: none;
        }
        
        @keyframes dataStream {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }
        
        .particle {
            position: absolute;
            background: rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }
        
        @keyframes particleFloat {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .rotate-3d {
            animation: rotate3d 20s infinite linear;
            transform-style: preserve-3d;
        }
        
        @keyframes rotate3d {
            0% { transform: perspective(1000px) rotateY(0deg); }
            100% { transform: perspective(1000px) rotateY(360deg); }
        }
        
        .glitch-effect {
            position: relative;
        }
        
        .glitch-effect::before, .glitch-effect::after {
            content: attr(data-text);
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }
        
        .glitch-effect::before {
            animation: glitch-1 5s infinite linear alternate-reverse;
            color: #8b5cf6;
            z-index: -1;
        }
        
        .glitch-effect::after {
            animation: glitch-2 3s infinite linear alternate-reverse;
            color: #10b981;
            z-index: -2;
        }
        
        @keyframes glitch-1 {
            0% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
            100% { transform: translate(0); }
        }
        
        @keyframes glitch-2 {
            0% { transform: translate(0); }
            20% { transform: translate(2px, 2px); }
            40% { transform: translate(2px, -2px); }
            60% { transform: translate(-2px, 2px); }
            80% { transform: translate(-2px, -2px); }
            100% { transform: translate(0); }
        }
        .president-image-small {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            object-fit: cover;
        }

        .profile-initials {
            width: 70px;
            height: 70px;
            border: 3px solid white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #17695d, #2b8d7b);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            color: white;
            font-size: 22px;
            font-weight: 700;
        }

        .profile-initials.belvic-admin { background: linear-gradient(135deg, #b86b36, #d89552); }
        .profile-initials.system-admin { background: linear-gradient(135deg, #365f78, #5288a0); }
    </style>
</head>
<body class="flex flex-col items-center min-h-screen">


    <!-- CUSTOM SUCCESS ALERT -->
    <div class="cyber-success-alert" id="successAlert">
        <div class="alert-icon-wrapper">
            <i class="fas fa-check"></i>
        </div>
        <div class="alert-content">
            <div class="alert-title">Access Granted</div>
            <div class="alert-message">
                Welcome back, <strong><?php echo htmlspecialchars($full_name); ?></strong>!<br>
                You have successfully logged into the portal.
            </div>
        </div>
        <button class="alert-close-btn" onclick="closeSuccessAlert()">×</button>
        <div class="alert-progress"></div>
    </div>

    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Data Stream Animation -->
    <div class="data-stream"></div>

    <div class="w-full max-w-6xl p-8 relative z-10">
        <!-- Header -->
        <div class="text-center mb-12" style="margin-top: -200px;">
            <div class="inline-block pulse-glow rounded-2xl p-2 mb-4">
                <div class="bg-white rounded-lg p-4">
                    <h1 class="brand-title glitch-effect" data-text="BELVIC CENTERPOINT">BELVIC CENTERPOINT</h1>
                </div>
            </div>
        </div>

<!-- President Info Bar -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8 p-4 president-card">
            <div class="flex items-center mb-4 md:mb-0">
                <div class="relative">
                    <?php if ($profile_image !== '' || $role === 'president'): ?>
                    <img src="<?php echo htmlspecialchars($profile_image !== '' ? $profile_image : 'STII_pages/images/ULOL4_40.jpg', ENT_QUOTES, 'UTF-8'); ?>"
                        alt="Profile photo for <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>" class="president-image-small">
                    <?php else: ?>
                    <div class="profile-initials <?php echo htmlspecialchars($avatar_role_class, ENT_QUOTES, 'UTF-8'); ?>" aria-label="Profile initials for <?php echo htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($avatar_initials, ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="ml-4">
                    <p class="stat-label text-xs">Welcome back, <?php echo htmlspecialchars($full_name); ?></p>
                    <p class="text-lg font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right hidden md:block">
                    <p class="stat-label text-xs">System Status</p>
                    <p class="text-green-600 font-bold flex items-center text-sm">
                        <span class="w-2 h-2 bg-green-500 rounded-full mr-2 pulse-glow"></span> ONLINE
                    </p>
                </div>
<button onclick="openLogoutModal()" class="cyber-btn flex items-center text-sm py-2 px-4">
       <i class="fas fa-sign-out-alt mr-2 text-xs"></i> LOGOUT
   </button>
            </div>
        </div>

<!-- Services Grid (reduced spacing) -->
<div class="grid grid-cols-1 <?php echo docVicCanAccessPortal('stii') && docVicCanAccessPortal('belvic') ? 'md:grid-cols-2' : 'md:grid-cols-1 max-w-3xl mx-auto'; ?> gap-4 mt-5">
    <?php if (docVicCanAccessPortal('stii')): ?>
    <!-- STII Operation -->
    <a href="STII_pages/portal.php" class="cyber-card rounded-lg h-full flex flex-col shadow-md hover:shadow-lg transition-all duration-300">
        <div class="card-header p-4 flex justify-center items-center rounded-t-lg">
            <div class="text-center">
                <i class="fas fa-graduation-cap text-white text-3xl mb-2 floating"></i>
                <h3 class="text-lg font-bold text-white font-rajdhani">STII OPERATION</h3>
                <p class="mt-1 text-xs font-semibold text-white/80"><i class="fas fa-shield-halved mr-1"></i><?php echo $role === 'stii_admin' ? 'STII ADMIN' : ($role === 'admin' ? 'SYSTEM ADMIN' : 'PRESIDENT ACCESS'); ?></p>
            </div>
        </div>
        <div class="p-4 flex-grow space-y-2">
            <div class="flex justify-between items-center p-2 bg-blue-50 rounded-md text-sm">
                <span>College Students</span>
                <span class="text-green-600"><?php echo number_format($total_college_students); ?></span>
            </div>
            <div class="flex justify-between items-center p-2 bg-blue-50 rounded-md text-sm">
                <span>SHS & JHS Students</span>
                <span class="text-blue-600"><?php echo number_format($total_shs_jhs_students); ?></span>
            </div>
            <div class="flex justify-between items-center p-2 bg-blue-50 rounded-md text-sm">
                <span>Strands</span>
                <span class="text-indigo-600"><?php echo number_format($total_strands); ?></span>
            </div>
            <div class="flex justify-between items-center p-2 bg-blue-50 rounded-md text-sm">
                <span>Courses</span>
                <span class="text-purple-600"><?php echo $total_courses; ?></span>
            </div>
        </div>
        <div class="px-4 pb-4">
            <button class="w-full cyber-btn flex items-center justify-center py-2 text-sm">
                ACCESS PORTAL <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </button>
        </div>
    </a>
    <?php endif; ?>

    <?php if (docVicCanAccessPortal('belvic')): ?>
    <!-- Belvic Operation -->
    <a href="pages/portal.php" class="cyber-card rounded-lg h-full flex flex-col shadow-md hover:shadow-lg transition-all duration-300">
        <div class="card-header p-4 flex justify-center items-center rounded-t-lg" style="background: linear-gradient(135deg, #10b981, #059669);">
            <div class="text-center">
                <i class="fas fa-building text-white text-3xl mb-2 floating"></i>
                <h3 class="text-lg font-bold text-white font-rajdhani">BELVIC OPERATION</h3>
                <p class="mt-1 text-xs font-semibold text-white/80"><i class="fas fa-shield-halved mr-1"></i><?php echo $role === 'belvic_admin' ? 'BELVIC ADMIN' : ($role === 'admin' ? 'SYSTEM ADMIN' : 'PRESIDENT ACCESS'); ?></p>
            </div>
        </div>
        <div class="p-4 flex-grow space-y-2">
            <div class="flex justify-between items-center p-2 bg-green-50 rounded-md text-sm">
                <span>Assets</span>
                <span class="text-blue-600">40</span>
            </div>
            <div class="flex justify-between items-center p-2 bg-green-50 rounded-md text-sm">
                <span>Registrations</span>
                <span class="text-green-600">31</span>
            </div>
            <div class="flex justify-between items-center p-2 bg-green-50 rounded-md text-sm">
                <span>Insurance</span>
                <span class="text-amber-600">0</span>
            </div>
        </div>
        <div class="px-4 pb-4">
            <button class="w-full cyber-btn flex items-center justify-center py-2 text-sm">
                ACCESS PORTAL <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </button>
        </div>
    </a>
    <?php endif; ?>

    <!-- SP -->
    <!-- <a href="SP/dashboard.php" class="cyber-card rounded-lg h-full flex flex-col shadow-md hover:shadow-lg transition-all duration-300">
        <div class="card-header p-4 flex justify-center items-center rounded-t-lg" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
            <div class="text-center">
                <i class="fas fa-user-circle text-white text-3xl mb-2 floating"></i>
                <h3 class="text-lg font-bold text-white font-rajdhani">SP</h3>
            </div>
        </div>
        <div class="p-4 flex-grow space-y-2">
            <div class="flex justify-between items-center p-2 bg-amber-50 rounded-md text-sm">
                <span>Ordinance</span>
                <span class="text-amber-600"><?php echo $ordinanceCount; ?></span>
            </div>
            <div class="flex justify-between items-center p-2 bg-amber-50 rounded-md text-sm">
                <span>Resolution</span>
                <span class="text-green-600"><?php echo $resolutionCount; ?></span>
            </div>
            <div class="flex justify-between items-center p-2 bg-amber-50 rounded-md text-sm">
                <span>Event</span>
                <span class="text-blue-600">0</span>
            </div>
        </div>
        <div class="px-4 pb-4">
            <button class="w-full cyber-btn flex items-center justify-center py-2 text-sm">
                ACCESS PORTAL <i class="fas fa-arrow-right ml-2 text-xs"></i>
            </button>
        </div>
    </a> -->
</div>

<div class="logout-modal-overlay" id="logoutModal">
       <div class="logout-modal">
           <div class="modal-icon-wrapper">
               <i class="fas fa-sign-out-alt"></i>
           </div>
           <h2 class="modal-title">Confirm Logout</h2>
           <p class="modal-message">
               Are you sure you want to log out from the portal?<br>
               Your session will be ended securely.
           </p>
           <div class="modal-buttons">
               <button class="modal-btn btn-cancel" onclick="closeLogoutModal()">
                   <i class="fas fa-times mr-2"></i> Cancel
               </button>
               <button class="modal-btn btn-confirm" onclick="confirmLogout()">
                   <i class="fas fa-check mr-2"></i> Yes, Logout
               </button>
           </div>
       </div>
   </div>

        <!-- Footer -->
        <div class="text-center mt-8 pt-6 border-t border-blue-200">
            <p class="stat-label">BELVIC CENTERPOINT VENTURES © 2050 • ALL SYSTEMS OPERATIONAL</p>
        </div>
    </div>

    <script>
function openLogoutModal() {
       const modal = document.getElementById('logoutModal');
       modal.classList.add('show');
   }

   function closeLogoutModal() {
       const modal = document.getElementById('logoutModal');
       modal.classList.remove('show');
   }

   function confirmLogout() {
       // Redirect to logout URL
       window.location.href = 'config/logout.php';
   }

        // SUCCESS ALERT FUNCTIONALITY
        window.addEventListener('DOMContentLoaded', function() {
            // Check if login was successful
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('login') === 'success') {
                showSuccessAlert();
                
                // Remove the parameter from URL without reloading
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
            }
            
            // Initialize other functions
            createParticles();
            initializeCardEffects();
        });

        function showSuccessAlert() {
            const alert = document.getElementById('successAlert');
            
            // Show the alert with animation
            setTimeout(() => {
                alert.classList.add('show');
            }, 200);

            // Auto-hide after 5 seconds
            setTimeout(() => {
                closeSuccessAlert();
            }, 5000);
        }

        function closeSuccessAlert() {
            const alert = document.getElementById('successAlert');
            alert.classList.remove('show');
            alert.classList.add('hide');
        }

        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.classList.add('particle');
                
                // Random properties
                const size = Math.random() * 10 + 5;
                const left = Math.random() * 100;
                const animationDuration = Math.random() * 20 + 10;
                const animationDelay = Math.random() * 5;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${left}%`;
                particle.style.animationDuration = `${animationDuration}s`;
                particle.style.animationDelay = `${animationDelay}s`;
                
                particlesContainer.appendChild(particle);
            }
        }
        
        // Initialize card effects
        function initializeCardEffects() {
            const cards = document.querySelectorAll('.cyber-card');
            
            cards.forEach(card => {
                card.addEventListener('mousemove', (e) => {
                    const rect = card.getBoundingClientRect();
                    const x = e.clientX - rect.left;
                    const y = e.clientY - rect.top;
                    
                    card.style.setProperty('--mouse-x', `${x}px`);
                    card.style.setProperty('--mouse-y', `${y}px`);
                });
            });
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>