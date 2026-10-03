<?php 

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];


?>

<?php include '../bar/navbar.php';?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard Overview</title>

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    body { 
      font-family: 'Inter', sans-serif; 
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background particles */
    .particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
      z-index: 0;
    }

    .particle {
      position: absolute;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.1);
      animation: float linear infinite;
    }

    @keyframes float {
      to {
        transform: translateY(-100vh) rotate(360deg);
        opacity: 0;
      }
    }

    /* Glass morphism effect */
    .glass-card {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(20px);
      -webkit-backdrop-filter: blur(20px);
      border: 1px solid rgba(255, 255, 255, 0.3);
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .glass-card:hover {
      transform: translateY(-8px);
      box-shadow: 0 20px 60px 0 rgba(31, 38, 135, 0.25);
    }

    /* Stat cards with gradient borders */
    .stat-card {
      position: relative;
      overflow: hidden;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--gradient-start), var(--gradient-end));
    }

    .stat-card.vehicle { --gradient-start: #667eea; --gradient-end: #764ba2; }
    .stat-card.assets { --gradient-start: #06b6d4; --gradient-end: #0891b2; }
    .stat-card.upcoming { --gradient-start: #f59e0b; --gradient-end: #d97706; }
    .stat-card.mission { --gradient-start: #8b5cf6; --gradient-end: #7c3aed; }

    /* Icon containers with gradient */
    .icon-gradient {
      background: linear-gradient(135deg, var(--icon-start), var(--icon-end));
      padding: 1rem;
      border-radius: 1rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .icon-gradient.blue { --icon-start: #667eea; --icon-end: #764ba2; }
    .icon-gradient.cyan { --icon-start: #06b6d4; --icon-end: #0891b2; }
    .icon-gradient.amber { --icon-start: #f59e0b; --icon-end: #d97706; }
    .icon-gradient.purple { --icon-start: #8b5cf6; --icon-end: #7c3aed; }

    /* Animated counter */
    .counter {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Upcoming list items */
    .upcoming-item {
      transition: all 0.3s ease;
      border-left: 3px solid transparent;
      padding-left: 1rem;
    }

    .upcoming-item:hover {
      border-left-color: #667eea;
      background: rgba(102, 126, 234, 0.05);
      transform: translateX(5px);
    }

    /* Status badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 600;
    }

    .status-badge.warning {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #92400e;
    }

    .status-badge.danger {
      background: linear-gradient(135deg, #fee2e2, #fecaca);
      color: #991b1b;
    }

    .status-badge.success {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #065f46;
    }

    /* Progress bar animation */
    .progress-bar {
      height: 6px;
      border-radius: 9999px;
      background: rgba(0, 0, 0, 0.05);
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #667eea, #764ba2);
      transition: width 1.5s cubic-bezier(0.4, 0, 0.2, 1);
      border-radius: 9999px;
    }

    /* Mission card styling */
    .mission-card {
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(124, 58, 237, 0.05));
      border: 2px solid rgba(139, 92, 246, 0.2);
    }

    .mission-card h3 {
      background: linear-gradient(135deg, #8b5cf6, #7c3aed);
      -webkit-background-clip: text;
      background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    /* Shimmer effect on load */
    @keyframes shimmer {
      0% { background-position: -1000px 0; }
      100% { background-position: 1000px 0; }
    }

    .shimmer {
      animation: shimmer 2s infinite;
      background: linear-gradient(to right, transparent 0%, rgba(255,255,255,0.3) 50%, transparent 100%);
      background-size: 1000px 100%;
    }

    /* Link hover effects */
    .link-arrow {
      transition: all 0.3s ease;
    }

    .link-arrow:hover {
      gap: 0.5rem;
    }

    .link-arrow i {
      transition: transform 0.3s ease;
    }

    .link-arrow:hover i {
      transform: translateX(4px);
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .stat-card {
        margin-bottom: 1rem;
      }
    }

    /* Number animation */
    .animate-number {
      display: inline-block;
      animation: popIn 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes popIn {
      0% { transform: scale(0); opacity: 0; }
      50% { transform: scale(1.1); }
      100% { transform: scale(1); opacity: 1; }
    }

    /* Calendar icon pulse */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .calendar-pulse {
      animation: pulse 2s ease-in-out infinite;
    }
  </style>
</head>
<body>
  <!-- Animated particles background -->
  <div class="particles" id="particles"></div>

  <!-- Main Content -->
  <div class="py-8 px-4 md:px-6 relative z-10">
    <div class="max-w-7xl mx-auto">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-4xl font-bold text-white mb-2 drop-shadow-lg">Dashboard Overview</h1>
        <!-- <p class="text-white/80 text-lg">Monitor and manage your fleet operations efficiently</p> -->
      </div>

      <a href="general_information.php" class="mb-6 flex flex-col gap-4 rounded-xl border border-white/70 bg-white px-5 py-4 shadow-lg transition hover:shadow-xl sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-800">
            <i class="fas fa-building" aria-hidden="true"></i>
          </span>
          <span class="min-w-0">
            <span class="block text-xs font-bold uppercase text-slate-500">DPWH Registered Contractor</span>
            <span class="mt-1 block break-words text-lg font-extrabold text-slate-900">BELVIC ENTERPRISES &amp; CONSTRUCTION</span>
            <span class="block text-sm font-semibold text-slate-600">Contractor ID 25337 · Sole Proprietorship</span>
          </span>
        </div>
        <span class="inline-flex shrink-0 items-center gap-2 font-bold text-sky-800">General Information <i class="fas fa-arrow-right" aria-hidden="true"></i></span>
      </a>

      <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Mission, Vision, Goals Card -->
        <div class="lg:col-span-1">
          <div class="glass-card stat-card mission rounded-2xl p-6 h-full">
            <div class="flex items-center mb-6">
              <div class="icon-gradient purple">
                <i class="fas fa-bullseye text-white text-2xl"></i>
              </div>
              <h2 class="text-2xl font-bold text-gray-800 ml-4">Our Vision</h2>
            </div>
            
            <div class="space-y-6">
              <div class="mission-card rounded-xl p-4">
                <div class="flex items-center mb-2">
                  <i class="fas fa-flag text-purple-600 mr-2"></i>
                  <h3 class="text-lg font-bold">Mission</h3>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">
                  To be a leading development and construction company committed to countryside and community development using its resources and technology to attain stable growth and development.
                </p>
              </div>
              
              <div class="mission-card rounded-xl p-4">
                <div class="flex items-center mb-2">
                  <i class="fas fa-eye text-purple-600 mr-2"></i>
                  <h3 class="text-lg font-bold">Vision</h3>
                </div>
                <p class="text-sm text-gray-700 leading-relaxed">
                  That BELVIC enterprise will be a vital partner in development using its resources and technology to attain stable growth and development.
                </p>
              </div>
              
              <div class="mission-card rounded-xl p-4">
                <div class="flex items-center mb-2">
                  <i class="fas fa-chart-line text-purple-600 mr-2"></i>
                  <h3 class="text-lg font-bold">Goals</h3>
                </div>
                <ul class="text-sm text-gray-700 space-y-2">
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-purple-600 mr-2 mt-1 flex-shrink-0"></i>
                    <span>Reliable and dependable A-1 condition heavy equipment and trucks</span>
                  </li>
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-purple-600 mr-2 mt-1 flex-shrink-0"></i>
                    <span>Sustain machinery through backup parts and proper maintenance</span>
                  </li>
                  <li class="flex items-start">
                    <i class="fas fa-check-circle text-purple-600 mr-2 mt-1 flex-shrink-0"></i>
                    <span>Vast supply of construction raw materials</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="lg:col-span-3">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Total Vehicle LTO Registration -->
            <div class="glass-card stat-card vehicle rounded-2xl p-6">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <p class="text-gray-600 text-sm font-medium mb-2">Total Vehicle LTO Registration</p>
                  <p id="totalLtoCount" class="text-5xl font-bold counter animate-number">0</p>
                </div>
                <div class="icon-gradient blue">
                  <i class="fas fa-car text-white text-3xl"></i>
                </div>
              </div>
              <div class="progress-bar mb-4">
                <div class="progress-fill" style="width: 0%"></div>
              </div>
              <a href="lto.php" class="link-arrow text-sm font-semibold text-purple-600 hover:text-purple-800 flex items-center gap-2">
                View all registrations
                <i class="fas fa-arrow-right"></i>
              </a>
            </div>

            <!-- Total Assets -->
            <div class="glass-card stat-card assets rounded-2xl p-6">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <p class="text-gray-600 text-sm font-medium mb-2">Total Assets</p>
                  <p class="text-5xl font-bold counter animate-number">40</p>
                </div>
                <div class="icon-gradient cyan">
                  <i class="fas fa-warehouse text-white text-3xl"></i>
                </div>
              </div>
              <div class="progress-bar mb-4">
                <div class="progress-fill" style="width: 100%"></div>
              </div>
              <a href="assets.php" class="link-arrow text-sm font-semibold text-cyan-600 hover:text-cyan-800 flex items-center gap-2">
                View asset inventory
                <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>

          <!-- Upcoming Registrations -->
          <div class="glass-card stat-card upcoming rounded-2xl p-6">
            <div class="flex items-center justify-between mb-6">
              <div>
                <h3 class="text-2xl font-bold text-gray-800 mb-1">Upcoming LTO Registrations</h3>
                <p class="text-gray-600 text-sm">Soonest due dates (top 4)</p>
              </div>
              <div class="icon-gradient amber calendar-pulse">
                <i class="fas fa-calendar-check text-white text-2xl"></i>
              </div>
            </div>

            <div id="upcomingList" class="space-y-3">
              <div class="text-gray-500 text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl mb-3"></i>
                <p>Loading upcoming registrations...</p>
              </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
              <a href="lto.php" class="link-arrow text-sm font-semibold text-amber-600 hover:text-amber-800 flex items-center gap-2">
                View all upcoming registrations
                <i class="fas fa-arrow-right"></i>
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    /* =========================
       Particle Animation
       ========================= */
    document.addEventListener('DOMContentLoaded', function() {
      const particlesContainer = document.getElementById('particles');
      const particleCount = 30;
      
      for (let i = 0; i < particleCount; i++) {
        const particle = document.createElement('div');
        particle.classList.add('particle');
        
        const size = Math.random() * 8 + 2;
        particle.style.width = `${size}px`;
        particle.style.height = `${size}px`;
        
        particle.style.left = `${Math.random() * 100}%`;
        particle.style.bottom = `-${size}px`;
        
        const duration = Math.random() * 20 + 15;
        particle.style.animationDuration = `${duration}s`;
        particle.style.animationDelay = `${Math.random() * 5}s`;
        
        particlesContainer.appendChild(particle);
      }
    });

    /* =========================
       Firebase config
       ========================= */
    const FB_URL = "https://belvic-9f87a-default-rtdb.firebaseio.com/lto.json";

    /* =========================
       Helpers
       ========================= */
    function mapRow(v) {
      return {
        make:    v.make ?? '',
        type:    v.type ?? '',
        plateNo: v.plate_no ?? v.plateNo ?? '',
        dueDate: v.due_date_actual ?? v.dueDate ?? '',
        mvFileNo: v.mv_file_no ?? v.mvFileNo ?? '',
        code:     v.code ?? ''
      };
    }

    function normalize(json) {
      if (!json) return [];
      if (Array.isArray(json)) {
        return json.filter(x => x && typeof x === 'object').map(mapRow);
      }
      return Object.values(json).filter(x => x && typeof x === 'object').map(mapRow);
    }

    function isValidDateStr(s) {
      const d = new Date(s);
      return !isNaN(d.getTime());
    }

    function formatHumanDate(s) {
      const d = new Date(s);
      const opts = { year:'numeric', month:'short', day:'numeric' };
      return d.toLocaleDateString(undefined, opts);
    }

    function daysDiffFromToday(s) {
      const today = new Date(); today.setHours(0,0,0,0);
      const d = new Date(s);    d.setHours(0,0,0,0);
      return Math.round((d - today) / (1000*60*60*24));
    }

    function escapeHtml(s) {
      return String(s ?? '').replace(/[&<>"']/g, c => (
        { '&':'&amp;', '<':'&lt;', '>':'&gt;', '"':'&quot;', "'":'&#039;' }[c]
      ));
    }

    /* =========================
       Render upcoming list
       ========================= */
    function renderUpcoming(items) {
      const container = document.getElementById('upcomingList');
      container.innerHTML = '';

      if (!items.length) {
        container.innerHTML = `
          <div class="text-center py-8">
            <i class="fas fa-calendar-times text-gray-400 text-4xl mb-3"></i>
            <p class="text-gray-500">No upcoming registrations found.</p>
          </div>
        `;
        return;
      }

      items.slice(0, 4).forEach((row, index) => {
        const days = daysDiffFromToday(row.dueDate);
        let badgeClass = 'success';
        let badgeIcon = 'check-circle';
        let statusText = `${days} day${days !== 1 ? 's' : ''} remaining`;
        
        if (days < 0) {
          badgeClass = 'danger';
          badgeIcon = 'exclamation-circle';
          statusText = 'Overdue';
        } else if (days <= 7) {
          badgeClass = 'warning';
          badgeIcon = 'exclamation-triangle';
        }

        const el = document.createElement('div');
        el.className = "upcoming-item glass-card rounded-xl p-4";
        el.style.animationDelay = `${index * 0.1}s`;
        el.innerHTML = `
          <div class="flex items-start justify-between">
            <div class="flex-1">
              <div class="flex items-center mb-2">
                <i class="fas fa-car text-purple-600 mr-2"></i>
                <h4 class="font-bold text-gray-900">${escapeHtml(row.make)} ${escapeHtml(row.type)}</h4>
              </div>
              <div class="text-sm text-gray-600 space-y-1">
                <p><i class="fas fa-hashtag text-gray-400 w-4"></i> <strong>Plate:</strong> ${escapeHtml(row.plateNo || 'N/A')}</p>
                <p><i class="fas fa-calendar text-gray-400 w-4"></i> <strong>Due:</strong> ${formatHumanDate(row.dueDate)}</p>
              </div>
            </div>
            <span class="status-badge ${badgeClass}">
              <i class="fas fa-${badgeIcon} mr-1"></i>
              ${statusText}
            </span>
          </div>
        `;
        container.appendChild(el);
      });
    }

    /* =========================
       Animate counter
       ========================= */
    function animateCounter(element, target) {
      let current = 0;
      const increment = target / 50;
      const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
          element.textContent = target;
          clearInterval(timer);
        } else {
          element.textContent = Math.floor(current);
        }
      }, 20);
    }

    /* =========================
       Init dashboard
       ========================= */
    async function initDashboard() {
      let rows = [];
      try {
        const res = await fetch(FB_URL);
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const json = await res.json();
        rows = normalize(json);
      } catch (e) {
        console.error('Failed to fetch from Firebase:', e);
      }

      // Animate total count
      const totalEl = document.getElementById('totalLtoCount');
      animateCounter(totalEl, rows.length);

      // Animate progress bar
      setTimeout(() => {
        const progressBar = document.querySelector('.vehicle .progress-fill');
        if (progressBar) {
          progressBar.style.width = '75%';
        }
      }, 300);

      // Filter and sort upcoming registrations
      const today = new Date(); today.setHours(0,0,0,0);
      const upcoming = rows
        .filter(r => r.dueDate && isValidDateStr(r.dueDate))
        .filter(r => {
          const d = new Date(r.dueDate);
          d.setHours(0,0,0,0);
          return d >= today;
        })
        .sort((a, b) => new Date(a.dueDate) - new Date(b.dueDate));

      renderUpcoming(upcoming);
    }

    document.addEventListener('DOMContentLoaded', initDashboard);
  </script>
</body>
</html>