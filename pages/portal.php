<?php
require_once '../config/check-session.php';
require_once '../config/conn.php';

// Get user info from session
$username  = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];

// ✅ Income Statement placeholders (replace with DB queries later)
$total_income   = 0;
$total_expenses = 0;
$net_income     = $total_income - $total_expenses;

$totalProjects = 0;
$activeProjects = 0;
try {
  $projectStatsResult = $conn->query("SELECT COUNT(*) AS total_projects, COALESCE(SUM(status = 'Active'), 0) AS active_projects FROM management_projects");
  $projectStats = $projectStatsResult->fetch_assoc();
  $projectStatsResult->free();
  $totalProjects = (int)$projectStats['total_projects'];
  $activeProjects = (int)$projectStats['active_projects'];
} catch (mysqli_sql_exception $exception) {
  error_log('Project Management portal stats query failed: ' . $exception->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Belvic Operation Portal</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root { color-scheme: light; }
    body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }

    /* Background */
    .bg-grid {
      background-image: radial-gradient(circle at 1px 1px, rgba(15, 23, 42, .06) 1px, transparent 0);
      background-size: 22px 22px;
    }
    .bg-gradient-soft {
      background:
        radial-gradient(900px circle at 10% 10%, rgba(59,130,246,.18), transparent 40%),
        radial-gradient(900px circle at 90% 30%, rgba(16,185,129,.14), transparent 45%),
        radial-gradient(900px circle at 40% 90%, rgba(14,165,233,.16), transparent 42%),
        linear-gradient(135deg, #f8fafc, #eff6ff, #f8fafc);
    }

    /* Shared surfaces */
    .chip {
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.78);
    }

    /* Cards */
    .card {
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.86);
      backdrop-filter: blur(12px);
      -webkit-backdrop-filter: blur(12px);
      box-shadow: 0 16px 40px rgba(2, 6, 23, 0.08);
    }
    .lift {
      transition: transform .22s ease, box-shadow .22s ease, border-color .22s ease;
      transform: translateY(0);
    }
    .lift:hover {
      transform: translateY(-6px);
      box-shadow: 0 22px 55px rgba(2, 6, 23, 0.12);
      border-color: rgba(59,130,246,.25);
    }

    /* Make all cards same height + align internal layout */
    .module-card{
      display:flex;
      flex-direction:column;
      height:100%;
    }
    .module-content{
      display:flex;
      flex-direction:column;
      gap:14px;
      padding:22px;
      flex:1; /* ✅ fills the vacant space so the footer aligns */
    }
    .module-footer{
      padding: 0 22px 22px 22px;
    }
    .module-bar{
      height: 5px;
      width: 100%;
    }

    /* Buttons (uniform) */
    .cta-btn{
      width:100%;
      display:inline-flex;
      align-items:center;
      justify-content:center;
      gap:.6rem;
      padding: 14px 16px;
      border-radius: 14px;
      font-weight: 900;
      letter-spacing: .2px;
      background: #0f172a;
      color:#fff;
      transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
      box-shadow: 0 14px 30px rgba(2,6,23,.15);
      user-select:none;
    }
    .cta-btn:hover{
      transform: translateY(-2px);
      background: #0b1224;
      box-shadow: 0 18px 40px rgba(2,6,23,.22);
    }

    /* Small stat boxes consistent sizing */
    .stat-box{
      border: 1px solid rgba(148,163,184,.35);
      background: rgba(255,255,255,.72);
      border-radius: 16px;
      padding: 16px;
      min-height: 92px; /* ✅ keeps same vertical rhythm */
      display:flex;
      flex-direction:column;
      justify-content:space-between;
    }

    /* Header divider */
    .accent-line {
      height: 1px;
      background: linear-gradient(90deg, rgba(59,130,246,.0), rgba(59,130,246,.45), rgba(16,185,129,.35), rgba(59,130,246,.0));
    }
  </style>
</head>

<body class="min-h-screen bg-gradient-soft bg-grid">
  <!-- ✅ Full width container (occupy available width) -->
  <div class="w-full px-4 sm:px-6 lg:px-10 py-6 md:py-10">

    <!-- Top row -->
    <div class="flex items-center justify-between gap-3 mb-8">
      <a href="../categories.php"
         class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 font-extrabold text-slate-800 chip hover:bg-white transition">
        <i class="fa-solid fa-arrow-left text-slate-500"></i>
        Back
      </a>

      <div class="hidden sm:flex items-center gap-2">
        <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">
          <i class="fa-solid fa-user text-slate-500"></i>
          <?php echo htmlspecialchars($full_name); ?>
        </span>
        <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">
          <i class="fa-solid fa-shield-halved text-slate-500"></i>
          <?php echo htmlspecialchars($role); ?>
        </span>
      </div>
    </div>

    <!-- Header -->
    <div class="text-center mb-10">
      <div class="inline-flex items-center gap-2 chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">
        <i class="fa-solid fa-sparkles text-blue-600"></i>
        Belvic Operation Portal
      </div>

      <h1 class="mt-4 text-3xl md:text-5xl font-black tracking-tight text-slate-900">
        Belvic Operation
        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 via-sky-500 to-emerald-500">
          Management
        </span>
      </h1>

      <p class="mt-3 text-slate-600 font-semibold max-w-3xl mx-auto">
        Choose a module below to manage construction operations, projects, and financial reporting.
      </p>

      <div class="mt-6 accent-line"></div>
    </div>

    <!-- ✅ Stretch grid so cards align perfectly + fill width -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-stretch">

      <!-- Belvic Construction -->
      <a href="belvic_construction.php" class="card lift rounded-2xl overflow-hidden module-card group">
        <div class="module-content">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-2xl bg-blue-600/10 flex items-center justify-center">
                <i class="fa-solid fa-helmet-safety text-blue-600 text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-black text-slate-900 leading-tight">Belvic Construction</h3>
                <p class="text-sm font-semibold text-slate-500 leading-tight">Project & Asset Management</p>
              </div>
            </div>
            <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">Module</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="stat-box">
              <p class="text-xs font-extrabold text-slate-500">Registration</p>
              <p class="text-3xl font-black text-slate-900">31</p>
            </div>
            <div class="stat-box">
              <p class="text-xs font-extrabold text-slate-500">Equipment Assets</p>
              <p class="text-3xl font-black text-slate-900">40</p>
            </div>
          </div>

          <!-- spacer auto from flex:1 layout (content fills evenly) -->
        </div>

        <div class="module-footer">
          <div class="cta-btn">
            Open Dashboard <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <div class="module-bar bg-gradient-to-r from-blue-600 via-sky-500 to-emerald-500"></div>
      </a>

      <!-- Project Management -->
      <a href="pm/" class="card lift rounded-2xl overflow-hidden module-card group">
        <div class="module-content">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-2xl bg-emerald-600/10 flex items-center justify-center">
                <i class="fa-solid fa-diagram-project text-emerald-600 text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-black text-slate-900 leading-tight">Project Management</h3>
                <p class="text-sm font-semibold text-slate-500 leading-tight">Operations & Resource Planning</p>
              </div>
            </div>
            <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">Module</span>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div class="stat-box">
              <p class="text-xs font-extrabold text-slate-500">Total Projects</p>
              <p class="text-3xl font-black text-slate-900"><?php echo number_format($totalProjects); ?></p>
            </div>
            <div class="stat-box">
              <p class="text-xs font-extrabold text-slate-500">Active Projects</p>
              <p class="text-3xl font-black text-slate-900"><?php echo number_format($activeProjects); ?></p>
            </div>
          </div>
        </div>

        <div class="module-footer">
          <div class="cta-btn">
            Open Dashboard <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <div class="module-bar bg-gradient-to-r from-emerald-500 via-cyan-500 to-sky-500"></div>
      </a>

      <!-- Income Statement -->
      <a href="financial_statement.php" class="card lift rounded-2xl overflow-hidden module-card group">
        <div class="module-content">
          <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="h-12 w-12 rounded-2xl bg-amber-500/10 flex items-center justify-center">
                <i class="fa-solid fa-file-invoice-dollar text-amber-600 text-xl"></i>
              </div>
              <div>
                <h3 class="text-xl font-black text-slate-900 leading-tight">Income and Financial Statement</h3>
                <p class="text-sm font-semibold text-slate-500 leading-tight">Revenue, Expenses & Net</p>
              </div>
            </div>
            <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">Module</span>
          </div>


        </div>

        <div class="module-footer">
          <div class="cta-btn">
            Open Statement <i class="fa-solid fa-arrow-right"></i>
          </div>
        </div>

        <div class="module-bar bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500"></div>
      </a>

    </div>

    <!-- Footer -->
    <div class="mt-10 pt-6 text-center text-slate-500 text-sm font-semibold">
      <div class="accent-line mb-5"></div>
      &copy; <?php echo date('Y'); ?> Belvic Operation. All rights reserved.
    </div>
  </div>
</body>
</html>

<?php $conn->close(); ?>
