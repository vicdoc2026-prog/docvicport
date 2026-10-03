<?php
include 'conn.php';

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Fetch totals from courses table (College)
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
            grade_10_male + grade_10_female) AS total_jhs_students
        FROM jhs_students WHERE id = 1";
$jhs_result = $conn->query($jhs_sql);
$data_jhs = $jhs_result->fetch_assoc();
$total_jhs_students = $data_jhs['total_jhs_students'] ?? 0;

$total_shs_jhs_students = $total_shs_students + $total_jhs_students;

// Income Statement placeholders
$total_income = 0;
$total_expenses = 0;
$net_income = $total_income - $total_expenses;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STII Services Portal | Essentiel</title>
  <script src="https://cdn.tailwindcss.com"></script> 
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">

  <style>
    :root{
      --glowA-rgb: 99,102,241;
      --glowB-rgb: 99,102,241;
      --hover-dur: 400ms;
      --hover-ease: cubic-bezier(0.25, 0.46, 0.45, 0.94);

      /* ✅ Set a single uniform card height here */
      --card-h: 330px;
      --card-w: 230px; 
    }

    .brand-title {
      font-family: 'Orbitron', sans-serif;
      font-weight: 900;
      font-size: 2.25rem;
      background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #10b981 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
      letter-spacing: 1.5px;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
      min-height: 100vh;
      padding: 1rem;
    }

    .service-card {
      position: relative;
      border-radius: 0.6rem;
      background: white;
      border: 1px solid #e5e7eb;
      overflow: hidden;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -1px rgba(0,0,0,0.06);
      transition: transform var(--hover-dur) var(--hover-ease),
                  box-shadow var(--hover-dur) var(--hover-ease),
                  border-color var(--hover-dur) var(--hover-ease),
                  border-radius var(--hover-dur) var(--hover-ease);
      transform-style: preserve-3d;
      perspective: 1000px;

      /* ✅ enforce same height + structure */
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    .service-card::before {
      content: '';
      position: absolute; top: 0; left: 0; right: 0;
      height: 4px;
      background: linear-gradient(90deg, rgba(var(--glowA-rgb),0.8), rgba(var(--glowB-rgb),0.8));
      transform: scaleX(0);
      transform-origin: left;
      transition: transform var(--hover-dur) var(--hover-ease);
      z-index: 10;
    }

    .service-card:hover {
      transform: translateY(-7px) scale(1.015);
      border-color: rgba(var(--glowA-rgb),0.7);
      border-radius: 0.9rem;
      box-shadow: 0 14px 28px -12px rgba(0,0,0,0.2),
                  0 0 12px 0 rgba(var(--glowA-rgb),0.42),
                  0 0 18px 0 rgba(var(--glowB-rgb),0.28);
    }
    .service-card:hover::before { transform: scaleX(1); }

    .card-content {
      transition: transform var(--hover-dur) var(--hover-ease);

      /* ✅ makes content area expand to equalize heights */
      flex: 1;
    }
    .service-card:hover .card-content { transform: translateY(-4px); }

    .glow-blue-indigo   { --glowA-rgb: 37,99,235;  --glowB-rgb: 99,102,241; }
    .glow-teal-cyan     { --glowA-rgb: 13,148,136; --glowB-rgb: 6,182,212; }
    .glow-green-lime    { --glowA-rgb: 22,163,74;  --glowB-rgb: 132,204,22; }
    .glow-red-rose      { --glowA-rgb: 153,27,27;  --glowB-rgb: 190,18,60; }
    .glow-purple-pink   { --glowA-rgb: 147,51,234; --glowB-rgb: 236,72,153; }
    .glow-amber-orange  { --glowA-rgb: 217,119,6;  --glowB-rgb: 249,115,22; }

    .stat-badge { transition: all var(--hover-dur) var(--hover-ease); }
    .service-card:hover .stat-badge {
      transform: translateY(-2px) scale(1.03);
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
    }

    .card-icon { transition: transform var(--hover-dur) var(--hover-ease); }
    .service-card:hover .card-icon { transform: scale(1.07) rotate(2deg); }

    /* ✅ Carousel wrapper */
    .carousel-wrap{
      position: relative;
      width: 100%;
      max-width: 72rem;
    }

    .carousel{
      display: flex;
      gap: 1.25rem;
      overflow-x: auto;
      overflow-y: hidden;
      scroll-behavior: smooth;
      padding: 0.25rem 0.25rem 0.75rem;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
      cursor: grab;
       padding-left: 54px;
  padding-right: 54px;
    }

    .carousel::-webkit-scrollbar{ height: 10px; }
    .carousel::-webkit-scrollbar-thumb{ background: rgba(100,116,139,.25); border-radius: 999px; }
    .carousel::-webkit-scrollbar-track{ background: transparent; }

    /* ✅ FIX: every item same width AND same height */
    .carousel > a{
      flex: 0 0 var(--card-w);
      height: var(--card-h);
      scroll-snap-align: start;
    }

    /* keep header block consistent */
    .card-head{
      min-height: 108px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.25rem;
    }

.nav-btn{
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  width: 38px;                 /* was 42px */
  height: 38px;
  border-radius: 999px;
  border: 1px solid rgba(148,163,184,.45);
  background: rgba(255,255,255,.92);
  backdrop-filter: blur(6px);
  box-shadow: 0 6px 14px rgba(0,0,0,.10); /* lighter */
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: transform 200ms ease, background 200ms ease, box-shadow 200ms ease, opacity 200ms ease;
  z-index: 25;                 /* above fades */
}
    .nav-btn:hover{
  transform: translateY(-50%) scale(1.05);
  background: rgba(255,255,255,.98);
  box-shadow: 0 10px 18px rgba(0,0,0,.12);
}
    .nav-btn:active{
  transform: translateY(-50%) scale(.97);
}

    .nav-left{ left: 6px; }
    .nav-right{ right: 6px; }

.fade-edge{
  pointer-events: none;
  position: absolute;
  top: 0;
  bottom: 0;
  width: 34px;              /* was 70px */
  z-index: 10;              /* behind arrows */
  opacity: .55;             /* softer overall */
}

.fade-left{
  left: 0;
  background: linear-gradient(90deg,
    rgba(243,244,246,.9) 0%,
    rgba(243,244,246,0) 100%
  );
}
.fade-right{
  right: 0;
  background: linear-gradient(270deg,
    rgba(243,244,246,.9) 0%,
    rgba(243,244,246,0) 100%
  );
}

    .grabbing{ cursor: grabbing !important; }
  </style>
</head>

<body class="min-h-screen p-4 md:p-8">
  <div class="mb-6">
    <a href="../STII_pages/portal.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200 text-sm">
      <i class="fas fa-arrow-left mr-2"></i>
      <span>Back</span>
    </a>
  </div>

  <div class="max-w-6xl mx-auto">
    <header class="mb-8 text-center" style="margin-bottom: 70px;">
      <h1 class="brand-title glitch-effect" data-text="STII Services Portal">
        STII Services Portal
      </h1>
    </header>

    <div class="flex justify-center">
      <div class="carousel-wrap">

        <button type="button" id="btnLeft" class="nav-btn nav-left" aria-label="Scroll left">
          <i class="fas fa-chevron-left text-gray-700"></i>
        </button>

        <button type="button" id="btnRight" class="nav-btn nav-right" aria-label="Scroll right">
          <i class="fas fa-chevron-right text-gray-700"></i>
        </button>

        <div class="fade-edge fade-left"></div>
        <div class="fade-edge fade-right"></div>

        <div id="cardCarousel" class="carousel">

          <!-- STII Operation -->
          <a href="stii_operation.php" class="service-card glow-blue-indigo">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-500 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-user-graduate text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">STII Operation</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-blue-100 text-blue-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">College</span>
                    <span class="text-xs font-medium text-gray-500">Students</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800"><?php echo number_format($total_college_students); ?></span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-indigo-100 text-indigo-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">SHS & JHS</span>
                    <span class="text-xs font-medium text-gray-500">Students</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800"><?php echo number_format($total_shs_jhs_students); ?></span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-purple-100 text-purple-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Strands</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800"><?php echo $total_strands; ?></span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-violet-100 text-violet-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Courses</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800"><?php echo $total_courses; ?></span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-blue-600 to-indigo-500 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

          <!-- Calendar Of Activities -->
          <a href="calendar.php" class="service-card glow-teal-cyan">
            <div class="bg-gradient-to-r from-teal-600 to-cyan-500 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-calendar-alt text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">STII Calendar</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-teal-100 text-teal-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Events</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">0</span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-cyan-100 text-cyan-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Upcoming</span>
                    <span class="text-xs font-medium text-gray-500">Events</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">0</span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-teal-600 to-cyan-500 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

          <!-- STII Campus -->
          <a href="campus/campus.php" class="service-card glow-green-lime">
            <div class="bg-gradient-to-r from-green-600 to-lime-500 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-school text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">STII Campus</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-green-100 text-green-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Campuses</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">3</span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-lime-100 text-lime-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Active</span>
                    <span class="text-xs font-medium text-gray-500">Campuses</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">3</span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-green-600 to-lime-500 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

          <!-- STII Cooperative -->
          <a href="cooperative.php" class="service-card glow-red-rose">
            <div class="bg-gradient-to-r from-red-800 to-rose-700 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-handshake text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">STII Cooperative</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-red-100 text-red-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Members</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">104</span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-rose-100 text-rose-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Active</span>
                    <span class="text-xs font-medium text-gray-500">Members</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">104</span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-red-800 to-rose-700 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

          <!-- Career Guidance -->
          <a href="career_guidance/" class="service-card glow-purple-pink">
            <div class="bg-gradient-to-r from-purple-600 to-pink-500 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-briefcase text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">Career Guidance</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-purple-100 text-purple-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Programs</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">0</span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-pink-100 text-pink-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Counselors</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">0</span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-purple-600 to-pink-500 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

          <!-- Income Statement -->
          <a href="income_statement.php" class="service-card glow-amber-orange">
            <div class="bg-gradient-to-r from-amber-600 to-orange-500 card-head">
              <div class="text-center">
                <div class="bg-white/20 p-2.5 rounded-full inline-flex mb-2 card-icon">
                  <i class="fas fa-file-invoice-dollar text-white text-xl"></i>
                </div>
                <h3 class="text-base font-semibold text-white">Income Statement</h3>
              </div>
            </div>

            <div class="p-4 card-content">
              <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-amber-100 text-amber-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Income</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">₱<?php echo number_format($total_income, 2); ?></span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-orange-100 text-orange-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Expenses</span>
                    <span class="text-xs font-medium text-gray-500">Total</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">₱<?php echo number_format($total_expenses, 2); ?></span>
                </div>

                <div class="flex justify-between items-center">
                  <div class="flex items-center">
                    <span class="bg-yellow-100 text-yellow-800 text-[10px] font-medium mr-2 px-2 py-0.5 rounded-full stat-badge">Net</span>
                    <span class="text-xs font-medium text-gray-500">Income</span>
                  </div>
                  <span class="text-xs font-bold text-gray-800">₱<?php echo number_format($net_income, 2); ?></span>
                </div>
              </div>
            </div>

            <div class="px-4 pb-4">
              <button class="w-full bg-gradient-to-r from-amber-600 to-orange-500 text-white font-medium py-1.5 px-3 rounded-md text-xs">
                View <i class="fas fa-arrow-right ml-2 text-[10px]"></i>
              </button>
            </div>
          </a>

        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const carousel = document.getElementById('cardCarousel');
      const btnLeft  = document.getElementById('btnLeft');
      const btnRight = document.getElementById('btnRight');

      function step() {
        const firstCard = carousel.querySelector('a');
        if (!firstCard) return 300;
        const gap = 20;
        return firstCard.getBoundingClientRect().width + gap;
      }

      btnLeft.addEventListener('click', () => {
        carousel.scrollBy({ left: -step(), behavior: 'smooth' });
      });

      btnRight.addEventListener('click', () => {
        carousel.scrollBy({ left: step(), behavior: 'smooth' });
      });

      // drag-to-scroll
      let isDown = false;
      let startX = 0;
      let startScrollLeft = 0;

      carousel.addEventListener('mousedown', (e) => {
        isDown = true;
        carousel.classList.add('grabbing');
        startX = e.pageX;
        startScrollLeft = carousel.scrollLeft;
      });

      window.addEventListener('mouseup', () => {
        isDown = false;
        carousel.classList.remove('grabbing');
      });

      window.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const dx = e.pageX - startX;
        carousel.scrollLeft = startScrollLeft - dx;
      });

      carousel.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
          e.preventDefault();
          carousel.scrollBy({ left: e.deltaY, behavior: 'auto' });
        }
      }, { passive: false });

    })();
  </script>
</body>
</html>
