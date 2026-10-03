<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username  = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];

// Get school year from GET parameter, default to '2025-2026'
$school_year = isset($_GET['school_year']) ? $_GET['school_year'] : '2025-2026';
$school_year_esc = mysqli_real_escape_string($conn, $school_year);

// Get semester from GET parameter, default to '1st'
$semester = isset($_GET['semester']) ? $_GET['semester'] : '1st';
$semester_esc = mysqli_real_escape_string($conn, $semester);

// Fetch available school years for dropdown
$years_sql = "SELECT DISTINCT school_year FROM shs_courses ORDER BY school_year DESC";
$years_result = $conn->query($years_sql);

// ==========================
// Fetch SHS data (filtered by selected semester)
// ==========================
$shs_sql = "SELECT strand_name, grade_11_male, grade_11_female, grade_12_male, grade_12_female
            FROM shs_courses
            WHERE school_year = '$school_year_esc' AND semester = '$semester_esc'";
$shs_result = $conn->query($shs_sql);

// ==========================
// Fetch JHS data (filtered by selected semester)
// ==========================
$jhs_sql = "SELECT * FROM jhs_students WHERE school_year = '$school_year_esc' AND semester = '$semester_esc'";
$jhs_result = $conn->query($jhs_sql);
$jhs_data = $jhs_result ? $jhs_result->fetch_assoc() : null;

// ==========================
// Semester totals for comparison chart (SHS only)
// ==========================
function get_shs_total_by_semester(mysqli $conn, string $schoolYear, string $sem): int {
    $sy = mysqli_real_escape_string($conn, $schoolYear);
    $s  = mysqli_real_escape_string($conn, $sem);

    $sql = "SELECT COALESCE(SUM(grade_11_male + grade_11_female + grade_12_male + grade_12_female), 0) AS total
            FROM shs_courses
            WHERE school_year = '$sy' AND semester = '$s'";
    $res = $conn->query($sql);
    if (!$res) return 0;
    $row = $res->fetch_assoc();
    return (int)($row['total'] ?? 0);
}

$shs_sem1_total = get_shs_total_by_semester($conn, $school_year, '1st');
$shs_sem2_total = get_shs_total_by_semester($conn, $school_year, '2nd');

// ==========================
// % Change (1st -> 2nd) for SHS
// ==========================
function percent_change(int $from, int $to): ?float {
    if ($from <= 0) return null; // avoid divide by zero / no baseline
    return (($to - $from) / $from) * 100;
}

function change_label(?float $pct): string {
    if ($pct === null) return "N/A";
    if ($pct > 0) return "+" . number_format($pct, 1) . "%";
    if ($pct < 0) return number_format($pct, 1) . "%";
    return "0.0%";
}

function change_badge_class(?float $pct): string {
    if ($pct === null) return "bg-slate-100 text-slate-700 border-slate-200";
    if ($pct > 0) return "bg-emerald-50 text-emerald-700 border-emerald-200";
    if ($pct < 0) return "bg-rose-50 text-rose-700 border-rose-200";
    return "bg-slate-100 text-slate-700 border-slate-200";
}

$shs_change_pct   = percent_change((int)$shs_sem1_total, (int)$shs_sem2_total);
$shs_change_label = change_label($shs_change_pct);
$shs_badge_class  = change_badge_class($shs_change_pct);

// ==========================
// Calculate totals (for selected semester page view)
// ==========================
$shs_data = [];
$total_shs_students = $total_shs_male = $total_shs_female = 0;
$shs_grade_totals = [11 => 0, 12 => 0];

if ($shs_result) {
    while ($row = $shs_result->fetch_assoc()) {
        $strand_male   = (int)$row['grade_11_male'] + (int)$row['grade_12_male'];
        $strand_female = (int)$row['grade_11_female'] + (int)$row['grade_12_female'];
        $strand_total  = $strand_male + $strand_female;

        $row['male_students']   = $strand_male;
        $row['female_students'] = $strand_female;
        $row['total_students']  = $strand_total;

        $shs_data[] = $row;

        $total_shs_students += $strand_total;
        $total_shs_male     += $strand_male;
        $total_shs_female   += $strand_female;

        $shs_grade_totals[11] += (int)$row['grade_11_male'] + (int)$row['grade_11_female'];
        $shs_grade_totals[12] += (int)$row['grade_12_male'] + (int)$row['grade_12_female'];
    }
}

$total_jhs_students = $total_jhs_male = $total_jhs_female = 0;
$jhs_grade_totals = [7 => 0, 8 => 0, 9 => 0, 10 => 0];

if ($jhs_data) {
    for ($grade = 7; $grade <= 10; $grade++) {
        $male_field   = "grade_{$grade}_male";
        $female_field = "grade_{$grade}_female";

        $grade_male   = (int)($jhs_data[$male_field] ?? 0);
        $grade_female = (int)($jhs_data[$female_field] ?? 0);
        $grade_total  = $grade_male + $grade_female;

        $jhs_grade_totals[$grade] = $grade_total;
        $total_jhs_male          += $grade_male;
        $total_jhs_female        += $grade_female;
        $total_jhs_students      += $grade_total;
    }
}

$total_students = $total_shs_students + $total_jhs_students;
$total_male     = $total_shs_male + $total_jhs_male;
$total_female   = $total_shs_female + $total_jhs_female;
$total_strands  = count($shs_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SHS & JHS Management Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <style>
    :root { color-scheme: light; }
    body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    .bg-grid {
      background-image: radial-gradient(circle at 1px 1px, rgba(15, 23, 42, .06) 1px, transparent 0);
      background-size: 22px 22px;
    }
    .card {
      border: 1px solid rgba(15,23,42,.08);
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      box-shadow: 0 12px 30px rgba(2, 6, 23, 0.06);
    }
    .chip {
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.85);
    }
    .table-head {
      background: linear-gradient(90deg, rgba(59,130,246,.14), rgba(34,197,94,.12));
    }
    .nice-scroll::-webkit-scrollbar { height: 10px; width: 10px; }
    .nice-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,.15); border-radius: 999px; }
    .nice-scroll::-webkit-scrollbar-track { background: rgba(15,23,42,.05); }

    /* child rows for strand details */
    .child-row { display: none; }
    .child-row.visible { display: table-row; }
  </style>
</head>

<body class="min-h-screen bg-slate-50 bg-grid">
  <?php include 'bar/sidebar.php'; ?>

  <div class="main-content-wrapper">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-10">

      <!-- Header -->
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-6">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 chip rounded-full px-3 py-1">
            <i class="fa-solid fa-chart-simple text-blue-600"></i>
            Dashboard
          </div>
          <h1 class="mt-3 text-2xl md:text-3xl font-extrabold text-slate-900 tracking-tight">
            Sibugay Technical Institute Incorporated
          </h1>
          <p class="mt-1 text-slate-600 font-medium">
            Senior High School & Junior High School Summary
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <div class="chip rounded-full px-3 py-1 text-sm font-semibold text-slate-800">
            <?php echo htmlspecialchars($semester); ?> Semester
          </div>
          <div class="chip rounded-full px-3 py-1 text-sm font-semibold text-slate-800">
            SY <?php echo htmlspecialchars($school_year); ?>
          </div>
        </div>
      </div>

      <!-- Auto Filter (NO button) -->
      <div class="card rounded-2xl p-4 md:p-5 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="flex items-center gap-2 text-slate-800 font-bold">
            <i class="fa-solid fa-filter text-emerald-600"></i> Filters
            <span class="text-xs font-semibold text-slate-500">(auto apply)</span>
          </div>

          <form id="filterForm" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="relative">
              <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <select id="schoolYearSelect" name="school_year"
                class="pl-9 pr-10 py-2.5 w-full sm:w-56 rounded-xl border border-slate-200 bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                <?php while ($year = $years_result->fetch_assoc()): ?>
                  <option value="<?php echo htmlspecialchars($year['school_year']); ?>"
                    <?php echo ($year['school_year'] == $school_year) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($year['school_year']); ?>
                  </option>
                <?php endwhile; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="relative">
              <i class="fa-solid fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <select id="semesterSelect" name="semester"
                class="pl-9 pr-10 py-2.5 w-full sm:w-56 rounded-xl border border-slate-200 bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <option value="1st" <?php echo ($semester == '1st') ? 'selected' : ''; ?>>1st Semester</option>
                <option value="2nd" <?php echo ($semester == '2nd') ? 'selected' : ''; ?>>2nd Semester</option>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <noscript>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-bold">Apply</button>
            </noscript>
          </form>
        </div>
      </div>

      <!-- Summary Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">Total Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_students); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-blue-50 flex items-center justify-center">
              <i class="fa-solid fa-user-graduate text-blue-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">SHS Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_shs_students); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center">
              <i class="fa-solid fa-school text-emerald-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">JHS Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_jhs_students); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
              <i class="fa-solid fa-building-columns text-violet-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">Male Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_male); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-sky-50 flex items-center justify-center">
              <i class="fa-solid fa-mars text-sky-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">Female Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_female); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-pink-50 flex items-center justify-center">
              <i class="fa-solid fa-venus text-pink-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Semester Comparison (SHS only) -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-2">
          <div>
            <h2 class="text-lg md:text-xl font-extrabold text-slate-900">
              Semester Comparison (<?php echo htmlspecialchars($school_year); ?>)
            </h2>
            <p class="text-slate-600 font-medium">
              Comparison of total students per semester for <span class="font-bold">SHS</span>.
            </p>
          </div>

          <div class="flex items-center gap-2 text-xs font-bold">
            <span class="inline-flex items-center gap-2 chip rounded-full px-3 py-1">
              <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span> 1st Sem (Blue)
            </span>
            <span class="inline-flex items-center gap-2 chip rounded-full px-3 py-1">
              <span class="h-2.5 w-2.5 rounded-full bg-emerald-600"></span> 2nd Sem (Green)
            </span>
          </div>
        </div>

        <!-- % Increase/Decrease badge (SHS only) -->
        <div class="mt-2 flex flex-wrap gap-2 text-xs font-extrabold">
          <div class="chip rounded-full px-3 py-1">
            SHS: <?php echo number_format((int)$shs_sem1_total); ?> → <?php echo number_format((int)$shs_sem2_total); ?>
            <span class="ml-2 inline-flex items-center gap-1 border rounded-full px-2 py-0.5 <?php echo $shs_badge_class; ?>">
              <i class="fa-solid <?php echo ($shs_change_pct !== null && $shs_change_pct < 0) ? 'fa-arrow-down' : 'fa-arrow-up'; ?>"></i>
              <?php echo htmlspecialchars($shs_change_label); ?>
            </span>
          </div>
        </div>

        <div class="w-full mt-4">
          <canvas id="semesterComparisonChart" height="110"></canvas>
        </div>
      </div>

      <!-- Grade Cards -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <div class="card rounded-2xl p-5 md:p-6">
          <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2 mb-4">
            <i class="fa-solid fa-chart-column text-blue-600"></i> SHS Grade Distribution
          </h3>

          <?php
            $g11 = (int)$shs_grade_totals[11];
            $g12 = (int)$shs_grade_totals[12];
            $shs_total_safe = max($total_shs_students, 1);
            $g11p = ($g11 / $shs_total_safe) * 100;
            $g12p = ($g12 / $shs_total_safe) * 100;
          ?>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="font-bold text-slate-700">Grade 11</div>
                <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($g11); ?></div>
              </div>
              <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($g11p, 1); ?>% of SHS</div>
              <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-blue-600" style="width: <?php echo $g11p; ?>%"></div>
              </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-4">
              <div class="flex items-center justify-between">
                <div class="font-bold text-slate-700">Grade 12</div>
                <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($g12); ?></div>
              </div>
              <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($g12p, 1); ?>% of SHS</div>
              <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                <div class="h-full bg-emerald-600" style="width: <?php echo $g12p; ?>%"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5 md:p-6">
          <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2 mb-4">
            <i class="fa-solid fa-chart-line text-emerald-600"></i> JHS Grade Distribution
          </h3>

          <?php
            $jhs_total_safe = max($total_jhs_students, 1);
            $g7  = (int)$jhs_grade_totals[7];
            $g8  = (int)$jhs_grade_totals[8];
            $g9  = (int)$jhs_grade_totals[9];
            $g10 = (int)$jhs_grade_totals[10];
          ?>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
              $jGrades = [
                ['label'=>'Grade 7', 'val'=>$g7,  'color'=>'bg-blue-600'],
                ['label'=>'Grade 8', 'val'=>$g8,  'color'=>'bg-sky-600'],
                ['label'=>'Grade 9', 'val'=>$g9,  'color'=>'bg-emerald-600'],
                ['label'=>'Grade 10','val'=>$g10, 'color'=>'bg-teal-600'],
              ];
              foreach ($jGrades as $item):
                $pct = ($item['val'] / $jhs_total_safe) * 100;
            ?>
              <div class="rounded-2xl border border-slate-200 bg-white p-4">
                <div class="flex items-center justify-between">
                  <div class="font-bold text-slate-700"><?php echo $item['label']; ?></div>
                  <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($item['val']); ?></div>
                </div>
                <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($pct, 1); ?>% of JHS</div>
                <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
                  <div class="h-full <?php echo $item['color']; ?>" style="width: <?php echo $pct; ?>%"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Tables -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
        <!-- SHS Strands -->
        <div class="card rounded-2xl p-5 md:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-graduation-cap text-blue-600"></i> SHS Strands
            </h3>
            <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700"><?php echo $total_strands; ?> strands</span>
          </div>

          <div class="overflow-x-auto nice-scroll">
            <table class="min-w-full text-sm">
              <thead class="table-head">
                <tr class="text-slate-800">
                  <th class="text-left px-3 py-3 font-extrabold rounded-tl-xl">Strand</th>
                  <th class="text-center px-3 py-3 font-extrabold">Male</th>
                  <th class="text-center px-3 py-3 font-extrabold">Female</th>
                  <th class="text-center px-3 py-3 font-extrabold">Total</th>
                  <th class="text-center px-3 py-3 font-extrabold rounded-tr-xl">Action</th>
                </tr>
              </thead>
              <tbody id="strandsTableBody">
                <?php foreach ($shs_data as $row): ?>
                  <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition strand-row"
                      data-strand="<?php echo htmlspecialchars($row['strand_name']); ?>"
                      data-grades='{
                        "grade_11":{"male":<?php echo (int)$row["grade_11_male"]; ?>,"female":<?php echo (int)$row["grade_11_female"]; ?>,"total":<?php echo (int)$row["grade_11_male"] + (int)$row["grade_11_female"]; ?>},
                        "grade_12":{"male":<?php echo (int)$row["grade_12_male"]; ?>,"female":<?php echo (int)$row["grade_12_female"]; ?>,"total":<?php echo (int)$row["grade_12_male"] + (int)$row["grade_12_female"]; ?>}
                      }'>
                    <td class="px-3 py-3 font-bold text-slate-800"><?php echo htmlspecialchars($row['strand_name']); ?></td>
                    <td class="px-3 py-3 text-center font-semibold"><?php echo (int)$row['male_students']; ?></td>
                    <td class="px-3 py-3 text-center font-semibold"><?php echo (int)$row['female_students']; ?></td>
                    <td class="px-3 py-3 text-center font-extrabold text-slate-900"><?php echo (int)$row['total_students']; ?></td>
                    <td class="px-3 py-3 text-center">
                      <button type="button"
                        class="px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-extrabold hover:bg-slate-800 transition"
                        onclick="toggleStrandDetails(this)">
                        View Details
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <p class="mt-3 text-xs text-slate-500 font-semibold">
            Tip: Click “View Details” to see Grade 11 / Grade 12 breakdown per strand.
          </p>
        </div>

        <!-- JHS Grades -->
        <div class="card rounded-2xl p-5 md:p-6">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-school text-emerald-600"></i> JHS Students
            </h3>
            <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">Grades 7–10</span>
          </div>

          <div class="overflow-x-auto nice-scroll">
            <table class="min-w-full text-sm">
              <thead class="table-head">
                <tr class="text-slate-800">
                  <th class="text-left px-3 py-3 font-extrabold rounded-tl-xl">Grade Level</th>
                  <th class="text-center px-3 py-3 font-extrabold">Male</th>
                  <th class="text-center px-3 py-3 font-extrabold">Female</th>
                  <th class="text-center px-3 py-3 font-extrabold rounded-tr-xl">Total</th>
                </tr>
              </thead>
              <tbody>
                <?php for ($grade = 7; $grade <= 10; $grade++): ?>
                  <?php
                    $male_field   = "grade_{$grade}_male";
                    $female_field = "grade_{$grade}_female";
                    $grade_male   = (int)($jhs_data[$male_field] ?? 0);
                    $grade_female = (int)($jhs_data[$female_field] ?? 0);
                    $grade_total  = $grade_male + $grade_female;
                  ?>
                  <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition">
                    <td class="px-3 py-3 font-bold text-slate-800">Grade <?php echo $grade; ?></td>
                    <td class="px-3 py-3 text-center font-semibold"><?php echo $grade_male; ?></td>
                    <td class="px-3 py-3 text-center font-semibold"><?php echo $grade_female; ?></td>
                    <td class="px-3 py-3 text-center font-extrabold text-slate-900"><?php echo $grade_total; ?></td>
                  </tr>
                <?php endfor; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Student Distribution Charts (NO color duplication) -->
      <div class="card rounded-2xl p-5 md:p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-blue-600"></i> Student Distribution Charts
          </h3>
          <span class="text-xs font-bold text-slate-500">unique colors per chart</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-extrabold text-slate-900">SHS Students by Strand</h4>
              <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($semester); ?></span>
            </div>
            <canvas id="pieChart" height="260"></canvas>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-extrabold text-slate-900">JHS Students by Grade</h4>
              <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($semester); ?></span>
            </div>
            <canvas id="jhsPieChart" height="260"></canvas>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h4 class="font-extrabold text-slate-900">Students by Grade Level</h4>
              <span class="text-xs font-bold text-slate-500"><?php echo htmlspecialchars($semester); ?></span>
            </div>
            <canvas id="barChart" height="260"></canvas>
          </div>
        </div>
      </div>

    </div>
  </div>

<script>
  // ✅ Auto-submit filter on change (NO Filter button)
  (function () {
    const form = document.getElementById('filterForm');
    const sy   = document.getElementById('schoolYearSelect');
    const sem  = document.getElementById('semesterSelect');

    function autoSubmit() { form.submit(); }
    sy.addEventListener('change', autoSubmit);
    sem.addEventListener('change', autoSubmit);
  })();

  // Toggle strand details
  function toggleStrandDetails(button) {
    const strandRow = button.closest('.strand-row');
    const gradeData = JSON.parse(strandRow.getAttribute('data-grades'));

    let nextRow = strandRow.nextElementSibling;
    const hasChildRows = nextRow && nextRow.classList.contains('child-row');

    if (hasChildRows) {
      while (nextRow && nextRow.classList.contains('child-row')) {
        const toRemove = nextRow;
        nextRow = nextRow.nextElementSibling;
        toRemove.remove();
      }
      button.textContent = 'View Details';
      return;
    }

    const grades = [
      { key: 'grade_11', label: 'Grade 11' },
      { key: 'grade_12', label: 'Grade 12' }
    ];

    grades.reverse().forEach(grade => {
      const childRow = document.createElement('tr');
      childRow.classList.add('child-row', 'visible');
      childRow.innerHTML = `
        <td class="px-3 py-3 text-slate-700 font-bold">${grade.label}</td>
        <td class="px-3 py-3 text-center font-semibold">${gradeData[grade.key].male}</td>
        <td class="px-3 py-3 text-center font-semibold">${gradeData[grade.key].female}</td>
        <td class="px-3 py-3 text-center font-extrabold text-slate-900">${gradeData[grade.key].total}</td>
        <td class="px-3 py-3 text-center"></td>
      `;
      childRow.style.background = "rgba(2, 6, 23, 0.02)";
      strandRow.insertAdjacentElement('afterend', childRow);
    });

    button.textContent = 'Hide Details';
  }

  // ✅ Unique color generator (NO duplication)
  function hslColor(h, s=72, l=52, a=0.85) {
    return `hsla(${h}, ${s}%, ${l}%, ${a})`;
  }
  function generateHslColors(count, offset=0) {
    const colors = [];
    const step = Math.floor(360 / Math.max(count, 1));
    for (let i = 0; i < count; i++) {
      const hue = (offset + i * step) % 360;
      colors.push(hslColor(hue));
    }
    return colors;
  }

  Chart.defaults.font.family = "Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif";
  Chart.defaults.color = "rgba(15,23,42,.9)";

  document.addEventListener('DOMContentLoaded', function () {

    // ✅ Semester Comparison Chart (SHS only) - 1st = Blue, 2nd = Green
    const semCtx = document.getElementById('semesterComparisonChart').getContext('2d');
    new Chart(semCtx, {
      type: 'bar',
      data: {
        labels: ['1st Semester', '2nd Semester'],
        datasets: [
          {
            label: 'SHS Students',
            data: [<?php echo (int)$shs_sem1_total; ?>, <?php echo (int)$shs_sem2_total; ?>],
            backgroundColor: ['rgba(37,99,235,.85)', 'rgba(16,185,129,.85)'], // Blue, Green
            borderColor: ['rgba(37,99,235,1)', 'rgba(16,185,129,1)'],
            borderWidth: 1,
            borderRadius: 10
          }
        ]
      },
      options: {
        responsive: true,
        plugins: {
          title: {
            display: true,
            text: 'SHS Students (1st vs 2nd Semester)',
            font: { weight: '800' }
          },
          legend: { position: 'top' },
          tooltip: { enabled: true }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: "rgba(15,23,42,.06)" },
            ticks: { precision: 0 }
          },
          x: { grid: { display: false } }
        }
      }
    });

    // SHS Pie (unique palette based on number of strands)
    const shsLabels = [<?php foreach ($shs_data as $row): ?>'<?php echo addslashes($row['strand_name']); ?>',<?php endforeach; ?>];
    const shsTotals = [<?php foreach ($shs_data as $row): ?><?php echo (int)$row['total_students']; ?>,<?php endforeach; ?>];
    const pieColors = generateHslColors(shsLabels.length, 10);

    const pieCtx = document.getElementById('pieChart').getContext('2d');
    new Chart(pieCtx, {
      type: 'doughnut',
      data: {
        labels: shsLabels,
        datasets: [{
          data: shsTotals,
          backgroundColor: pieColors,
          borderColor: "rgba(255,255,255,.9)",
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        cutout: "62%",
        plugins: {
          legend: { position: 'bottom' },
          title: { display: true, text: 'SHS Students Distribution by Strand', font: { weight: '800' } }
        }
      }
    });

    // JHS Pie (different offset)
    const jhsColors = generateHslColors(4, 160);
    const jhsPieCtx = document.getElementById('jhsPieChart').getContext('2d');
    new Chart(jhsPieCtx, {
      type: 'doughnut',
      data: {
        labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
        datasets: [{
          data: [
            <?php echo (int)$jhs_grade_totals[7]; ?>,
            <?php echo (int)$jhs_grade_totals[8]; ?>,
            <?php echo (int)$jhs_grade_totals[9]; ?>,
            <?php echo (int)$jhs_grade_totals[10]; ?>
          ],
          backgroundColor: jhsColors,
          borderColor: "rgba(255,255,255,.9)",
          borderWidth: 2
        }]
      },
      options: {
        responsive: true,
        cutout: "62%",
        plugins: {
          legend: { position: 'bottom' },
          title: { display: true, text: 'JHS Students Distribution by Grade', font: { weight: '800' } }
        }
      }
    });

    // Grade Level Bar (another offset)
    const barColors = generateHslColors(6, 280);
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
        datasets: [{
          label: 'Number of Students',
          data: [
            <?php echo (int)$jhs_grade_totals[7]; ?>,
            <?php echo (int)$jhs_grade_totals[8]; ?>,
            <?php echo (int)$jhs_grade_totals[9]; ?>,
            <?php echo (int)$jhs_grade_totals[10]; ?>,
            <?php echo (int)$shs_grade_totals[11]; ?>,
            <?php echo (int)$shs_grade_totals[12]; ?>
          ],
          backgroundColor: barColors,
          borderWidth: 0,
          borderRadius: 10
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: false },
          title: { display: true, text: 'Students per Grade Level', font: { weight: '800' } }
        },
        scales: {
          y: {
            beginAtZero: true,
            grid: { color: "rgba(15,23,42,.06)" },
            ticks: { precision: 0 }
          },
          x: { grid: { display: false } }
        }
      }
    });
  });
</script>

</body>
</html>

<?php
$conn->close();
?>
