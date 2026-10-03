<?php
include '../STII_operation/conn.php';

// Include session check - this will redirect if not logged in
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Fetch totals from courses table (College)
$sql_college = "SELECT 
        SUM(first_year_male + second_year_male + third_year_male + fourth_year_male) AS total_male,
        SUM(first_year_female + second_year_female + third_year_female + fourth_year_female) AS total_female,
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
$total_male = $data_college['total_male'] ?? 0;
$total_female = $data_college['total_female'] ?? 0;

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>STII Services Portal | Essentiel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">

  <style>
    :root{
      /* ✅ One place to control all card size */
      --card-h: 330px;
      --card-w: 230px; 
      --gap: 1.25rem;

      /* ✅ space so cards won't go behind arrows */
      --rail-pad: 52px;

      /* ✅ feather control (smaller = less feather) */
      --fade-edge: 3%;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
    }

    .brand-title {
      font-family: 'Orbitron', sans-serif;
      font-weight: 900;
      font-size: 3rem;
      background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #10b981 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
      text-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
      letter-spacing: 2px;
    }

    .card-hover {
      position: relative;
      transition: transform .25s cubic-bezier(0.4,0,0.2,1),
                  box-shadow .25s ease,
                  border-color .25s ease;
      will-change: transform;
      border: 1px solid #e5e7eb;
      border-radius: 0.5rem;
      background: #fff;
    }

    .card-hover:hover {
      transform: translateY(-6px);
      z-index: 20;
      box-shadow:
        0 8px 14px -4px rgba(0,0,0,.08),
        0 0 0 2px rgba(var(--glow-r,16), var(--glow-g,185), var(--glow-b,129), .25);
      border-color: rgba(var(--glow-r,16), var(--glow-g,185), var(--glow-b,129), .5);
    }

    .glow-emerald { --glow-r:16;  --glow-g:185; --glow-b:129; }
    .glow-lime    { --glow-r:101; --glow-g:163; --glow-b:13;  }
    .glow-rose    { --glow-r:225; --glow-g:29;  --glow-b:72;  }
    .glow-indigo  { --glow-r:79;  --glow-g:70;  --glow-b:229; }
    .glow-sky     { --glow-r:2;   --glow-g:132; --glow-b:199; }
    .glow-amber   { --glow-r:245; --glow-g:158; --glow-b:11;  }
    .glow-teal    { --glow-r:13;  --glow-g:148; --glow-b:139; }

    /* ✅ wrapper that includes arrows */
    .services-wrap{
      position: relative;
      overflow: visible;
    }

    /* ✅ scroll container */
    .services-scroll{
      overflow-x: auto;
      overflow-y: visible;
      padding: 10px var(--rail-pad) 14px;
      -webkit-overflow-scrolling: touch;
      scroll-behavior: smooth;
      cursor: grab;

      /* ✅ reduced feather boundary */
      border-radius: 20px;
      -webkit-mask-image: linear-gradient(
        to right,
        transparent 0%,
        black var(--fade-edge),
        black calc(100% - var(--fade-edge)),
        transparent 100%
      );
      mask-image: linear-gradient(
        to right,
        transparent 0%,
        black var(--fade-edge),
        black calc(100% - var(--fade-edge)),
        transparent 100%
      );
    }
    .services-scroll:active{ cursor: grabbing; }

    .services-scroll::-webkit-scrollbar{ height: 10px; }
    .services-scroll::-webkit-scrollbar-thumb{ background: rgba(100,116,139,.22); border-radius: 999px; }
    .services-scroll::-webkit-scrollbar-track{ background: transparent; }

    .services-row {
      display: grid;
      grid-template-columns: repeat(7, var(--card-w));
      gap: var(--gap);
      align-items: stretch;
      width: max-content;
      overflow: visible;
      margin-top: 4rem;
    }

    .service-card{
      width: var(--card-w);
      height: var(--card-h);
      min-width: var(--card-w);
      min-height: var(--card-h);
      overflow: hidden;
      border-radius: 0.5rem;
      display: flex;
      flex-direction: column;
    }

    .card-title { font-size: 1rem; font-weight: 600; }
    .stat-label { font-size: 0.75rem; color: #6b7280; }
    .stat-value { font-size: 0.875rem; font-weight: 600; color: #374151; }
    .btn-small { font-size: 0.75rem; padding: 0.4rem 0.75rem; }

    /* ✅ nav arrows */
    .nav-btn{
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 38px;
      height: 38px;
      border-radius: 999px;
      border: 1px solid rgba(148,163,184,.45);
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(6px);
      box-shadow: 0 6px 14px rgba(0,0,0,.10);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: transform 200ms ease, background 200ms ease, box-shadow 200ms ease, opacity 200ms ease;
      z-index: 30;
    }
    .nav-btn:hover{
      transform: translateY(-50%) scale(1.06);
      background: rgba(255,255,255,.98);
      box-shadow: 0 10px 18px rgba(0,0,0,.12);
    }
    .nav-btn:active{ transform: translateY(-50%) scale(.97); }

    .nav-left{ left: 8px; }
    .nav-right{ right: 8px; }

    /* hide arrows if not scrollable */
    .nav-btn.is-hidden{ opacity: 0; pointer-events: none; }
  </style>
</head>

<body class="min-h-screen p-4 md:p-8">
  <div class="mb-6">
    <a href="../categories.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200 text-sm">
      <i class="fas fa-arrow-left mr-2"></i>
      <span>Back</span>
    </a>
  </div>

  <div class="max-w-screen-2xl mx-auto" style="overflow:visible;">
    <header class="mb-8 text-center" style="margin-bottom: 90px;">
      <h1 class="brand-title glitch-effect" data-text="STII Services Portal">
        STII Services Portal
      </h1>
    </header>

    <div class="services-wrap mx-6 md:mx-12" style="margin-top: -50px;">
      <button type="button" id="btnLeft" class="nav-btn nav-left" aria-label="Scroll left">
        <i class="fas fa-chevron-left text-gray-700"></i>
      </button>

      <button type="button" id="btnRight" class="nav-btn nav-right" aria-label="Scroll right">
        <i class="fas fa-chevron-right text-gray-700"></i>
      </button>

      <div id="servicesScroll" class="services-scroll">
        <div class="services-row">

          <!-- STII Operation -->
          <a href="../STII_operation/portal.php" class="service-card shadow-sm card-hover flex flex-col glow-emerald">
            <div class="bg-gradient-to-r from-green-600 to-emerald-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-user-graduate text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">STII Operation</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center">
                <span class="stat-label">College Students</span>
                <span class="stat-value"><?php echo number_format($total_college_students); ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="stat-label">SHS & JHS Students</span>
                <span class="stat-value"><?php echo number_format($total_shs_jhs_students); ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="stat-label">Strands</span>
                <span class="stat-value text-indigo-600"><?php echo number_format($total_strands); ?></span>
              </div>
              <div class="flex justify-between items-center">
                <span class="stat-label">Courses</span>
                <span class="stat-value text-purple-600"><?php echo number_format($total_courses); ?></span>
              </div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-green-600 to-emerald-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- STII Farm / Agri -->
          <a href="Farm/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-lime">
            <div class="bg-gradient-to-r from-lime-600 to-green-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-seedling text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">STII Farm / Agri</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Active Projects</span><span class="stat-value">25</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Completed</span><span class="stat-value">25</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Ongoing</span><span class="stat-value">6</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-lime-600 to-green-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- Royal Raya Hotel -->
          <a href="Farm_house/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-rose">
            <div class="bg-gradient-to-r from-rose-600 to-pink-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-house-chimney text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">Royal Raya Hotel</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Guests</span><span class="stat-value">0</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Booked Dates</span><span class="stat-value">0</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Maintenance</span><span class="stat-value">0</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-rose-600 to-pink-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- Land Title -->
          <a href="Land_title/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-indigo">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-file-alt text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">Land Title</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Secured Titles</span><span class="stat-value">45</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Pending Apps</span><span class="stat-value">3</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-indigo-600 to-purple-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- Electric Bill -->
          <a href="Electric/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-sky">
            <div class="bg-gradient-to-r from-sky-600 to-cyan-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-bolt text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">Electric Bill</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Month (₱)</span><span class="stat-value">0</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Outstanding</span><span class="stat-value">0</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Meters</span><span class="stat-value">0</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-sky-600 to-cyan-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- Bus -->
          <a href="Bus/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-amber">
            <div class="bg-gradient-to-r from-amber-600 to-yellow-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-bus text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">Bus</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Active Buses</span><span class="stat-value">3</span></div>
              <div class="flex justify-between items-center"><span class="stat-label">Total Buses</span><span class="stat-value">3</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-amber-600 to-yellow-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

          <!-- Elevator -->
          <a href="elevator/dashboard.php" class="service-card shadow-sm card-hover flex flex-col glow-teal">
            <div class="bg-gradient-to-r from-teal-600 to-cyan-500 p-4 flex justify-center items-center">
              <div class="text-center">
                <div class="bg-white/20 p-2 rounded-full inline-flex mb-2">
                  <i class="fas fa-building text-white text-lg"></i>
                </div>
                <h3 class="card-title text-white">Elevator</h3>
              </div>
            </div>
            <div class="p-4 flex-grow bg-white space-y-2">
              <div class="flex justify-between items-center"><span class="stat-label">Operational</span><span class="stat-value">1</span></div>
            </div>
            <div class="px-4 pb-4 bg-white">
              <button class="w-full btn-small bg-gradient-to-r from-teal-600 to-cyan-500 text-white rounded-md transition">
                View Dashboard <i class="fas fa-arrow-right ml-1 text-xs"></i>
              </button>
            </div>
          </a>

        </div>
      </div>
    </div>
  </div>

  <script>
    (function () {
      const scroller = document.getElementById('servicesScroll');
      const btnLeft  = document.getElementById('btnLeft');
      const btnRight = document.getElementById('btnRight');

      function stepPx(){
        const styles = getComputedStyle(document.documentElement);
        const w = parseFloat(styles.getPropertyValue('--card-w')) || 240;
        const gap = parseFloat(styles.getPropertyValue('--gap')) || 20;
        return w + gap;
      }

      function updateArrows(){
        const max = scroller.scrollWidth - scroller.clientWidth - 1;
        btnLeft.classList.toggle('is-hidden', scroller.scrollLeft <= 1);
        btnRight.classList.toggle('is-hidden', scroller.scrollLeft >= max);
      }

      btnLeft.addEventListener('click', () => scroller.scrollBy({ left: -stepPx(), behavior: 'smooth' }));
      btnRight.addEventListener('click', () => scroller.scrollBy({ left: stepPx(), behavior: 'smooth' }));

      // drag to scroll
      let isDown = false;
      let startX = 0;
      let startLeft = 0;

      scroller.addEventListener('mousedown', (e) => {
        isDown = true;
        startX = e.pageX;
        startLeft = scroller.scrollLeft;
      });
      window.addEventListener('mouseup', () => { isDown = false; });
      window.addEventListener('mousemove', (e) => {
        if (!isDown) return;
        e.preventDefault();
        const dx = e.pageX - startX;
        scroller.scrollLeft = startLeft - dx;
      });

      scroller.addEventListener('scroll', updateArrows);
      window.addEventListener('resize', updateArrows);
      updateArrows();
    })();
  </script>
</body>
</html>
