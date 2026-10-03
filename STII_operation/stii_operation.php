<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get available academic years
$years_result = $conn->query("SELECT DISTINCT academic_year FROM courses ORDER BY academic_year DESC");
$years = [];
while ($y = $years_result->fetch_assoc()) {
    $years[] = $y['academic_year'];
}

// Get selected filters
$selected_year = $_GET['ay'] ?? ($years[0] ?? '2025-2026');
$selected_sem = $_GET['sem'] ?? '1st Semester';

// Fetch course data based on filters
$stmt = $conn->prepare("SELECT * FROM courses WHERE academic_year = ? AND semester = ?");
$stmt->bind_param("ss", $selected_year, $selected_sem);
$stmt->execute();
$result = $stmt->get_result();

// Calculate totals
$courses_data = [];
$total_students = 0;
$total_male = 0;
$total_female = 0;
$year_totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

while ($row = $result->fetch_assoc()) {
    $course_male = $row['first_year_male'] + $row['second_year_male'] + $row['third_year_male'] + $row['fourth_year_male'];
    $course_female = $row['first_year_female'] + $row['second_year_female'] + $row['third_year_female'] + $row['fourth_year_female'];
    $course_total = $course_male + $course_female;

    $row['male_students'] = $course_male;
    $row['female_students'] = $course_female;
    $row['total_students'] = $course_total;

    $courses_data[] = $row;

    $total_students += $course_total;
    $total_male += $course_male;
    $total_female += $course_female;

    $year_totals[1] += $row['first_year_male'] + $row['first_year_female'];
    $year_totals[2] += $row['second_year_male'] + $row['second_year_female'];
    $year_totals[3] += $row['third_year_male'] + $row['third_year_female'];
    $year_totals[4] += $row['fourth_year_male'] + $row['fourth_year_female'];
}

$total_courses = count($courses_data);

// Function to get year totals for a specific semester
function get_year_totals($conn, $year, $sem) {
    $totals = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
    $stmt = $conn->prepare("SELECT first_year_male, first_year_female, second_year_male, second_year_female, third_year_male, third_year_female, fourth_year_male, fourth_year_female FROM courses WHERE academic_year = ? AND semester = ?");
    $stmt->bind_param("ss", $year, $sem);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $totals[1] += $row['first_year_male'] + $row['first_year_female'];
        $totals[2] += $row['second_year_male'] + $row['second_year_female'];
        $totals[3] += $row['third_year_male'] + $row['third_year_female'];
        $totals[4] += $row['fourth_year_male'] + $row['fourth_year_female'];
    }
    $stmt->close();
    return $totals;
}

$year_totals_1st = get_year_totals($conn, $selected_year, '1st Semester');
$year_totals_2nd = get_year_totals($conn, $selected_year, '2nd Semester');

// Calculate percentage changes
$percent_changes = [];
for ($i = 1; $i <= 4; $i++) {
    $first = $year_totals_1st[$i];
    $second = $year_totals_2nd[$i];
    if ($first > 0) {
        $change = (($second - $first) / $first) * 100;
        $percent_changes[$i] = round($change, 1);
    } else {
        $percent_changes[$i] = $second > 0 ? 100 : 0;
    }
}

function ordinal($number) {
    $ends = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
    if ((($number % 100) >= 11) && (($number % 100) <= 13)) {
        return $number . 'th';
    } else {
        return $number . $ends[$number % 10];
    }
}

// Fetch enrollment history data
$history_sql = "SELECT school_year, semester, total_enrollees FROM enrollment_history ORDER BY id ASC";
$history_result = $conn->query($history_sql);

$history_data = [];
if ($history_result) {
    while ($row = $history_result->fetch_assoc()) {
        $history_data[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School Management Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>

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
    .nice-scroll::-webkit-scrollbar { height: 10px; width: 10px; }
    .nice-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,.15); border-radius: 999px; }
    .nice-scroll::-webkit-scrollbar-track { background: rgba(15,23,42,.05); }

    /* layout (works with your sidebar) */
    .main-content-wrapper { margin-left: 280px; transition: margin-left .25s ease; }
    @media (max-width: 768px) { .main-content-wrapper { margin-left: 0; } }

    /* table */
    .table-head {
      background: linear-gradient(90deg, rgba(59,130,246,.14), rgba(34,197,94,.12));
    }
    .child-row { display: none; }
    .child-row.visible { display: table-row; }
  </style>
</head>

<body class="min-h-screen bg-slate-50 bg-grid text-slate-900">
  <?php include 'bar/sidebar.php'; ?>

  <div class="main-content-wrapper">
    <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-10">

      <!-- Header -->
      <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-6">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 chip rounded-full px-3 py-1">
            <i class="fa-solid fa-chart-line text-blue-600"></i>
            College Enrollment Dashboard
          </div>
          <h1 class="mt-3 text-2xl md:text-3xl font-extrabold tracking-tight text-slate-900">
            Sibugay Technical Institute Incorporated
          </h1>
          <p class="mt-1 text-slate-600 font-medium">
            Track totals, distributions, and historical trends in a single view.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <span class="chip rounded-full px-3 py-1 text-sm font-semibold text-slate-800">
            <?php echo htmlspecialchars($selected_sem); ?>
          </span>
          <span class="chip rounded-full px-3 py-1 text-sm font-semibold text-slate-800">
            AY <?php echo htmlspecialchars($selected_year); ?>
          </span>
        </div>
      </div>

      <!-- Filters (modern / auto apply) -->
      <div class="card rounded-2xl p-4 md:p-5 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
          <div class="flex items-center gap-2 text-slate-800 font-bold">
            <i class="fa-solid fa-filter text-emerald-600"></i> Filters
            <span class="text-xs font-semibold text-slate-500">(auto apply)</span>
          </div>

          <form method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-center">
            <div class="relative">
              <i class="fa-regular fa-calendar absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <select name="ay" onchange="this.form.submit()"
                class="pl-9 pr-10 py-2.5 w-full sm:w-56 rounded-xl border border-slate-200 bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                <?php foreach ($years as $y): ?>
                  <option value="<?php echo htmlspecialchars($y); ?>" <?php if ($y == $selected_year) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($y); ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <div class="relative">
              <i class="fa-solid fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <select name="sem" onchange="this.form.submit()"
                class="pl-9 pr-10 py-2.5 w-full sm:w-56 rounded-xl border border-slate-200 bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-200">
                <option value="1st Semester" <?php if ($selected_sem == '1st Semester') echo 'selected'; ?>>1st Semester</option>
                <option value="2nd Semester" <?php if ($selected_sem == '2nd Semester') echo 'selected'; ?>>2nd Semester</option>
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
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
          <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-blue-600" style="width: 100%"></div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">Courses Offered</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_courses); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center">
              <i class="fa-solid fa-book text-emerald-600"></i>
            </div>
          </div>
          <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-emerald-600" style="width: 100%"></div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-semibold text-slate-500">Male Students</p>
              <p class="mt-1 text-2xl font-extrabold text-slate-900"><?php echo number_format($total_male); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
              <i class="fa-solid fa-mars text-violet-600"></i>
            </div>
          </div>
          <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-violet-600" style="width: <?php echo ($total_male / max($total_students, 1)) * 100; ?>%"></div>
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
          <div class="mt-4 h-2 rounded-full bg-slate-100 overflow-hidden">
            <div class="h-full bg-pink-600" style="width: <?php echo ($total_female / max($total_students, 1)) * 100; ?>%"></div>
          </div>
        </div>
      </div>

      <!-- Year Level Distribution Cards -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-users text-blue-600"></i> Student Distribution by Year Level
          </h2>
          <span class="text-xs font-bold text-slate-500">share of total</span>
        </div>

        <?php
          $total_safe = max($total_students, 1);
          $y1p = ($year_totals[1] / $total_safe) * 100;
          $y2p = ($year_totals[2] / $total_safe) * 100;
          $y3p = ($year_totals[3] / $total_safe) * 100;
          $y4p = ($year_totals[4] / $total_safe) * 100;
        ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between">
              <div class="font-bold text-slate-700">1st Year</div>
              <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($year_totals[1]); ?></div>
            </div>
            <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($y1p, 1); ?>%</div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full bg-blue-600" style="width: <?php echo $y1p; ?>%"></div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between">
              <div class="font-bold text-slate-700">2nd Year</div>
              <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($year_totals[2]); ?></div>
            </div>
            <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($y2p, 1); ?>%</div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full bg-emerald-600" style="width: <?php echo $y2p; ?>%"></div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between">
              <div class="font-bold text-slate-700">3rd Year</div>
              <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($year_totals[3]); ?></div>
            </div>
            <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($y3p, 1); ?>%</div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full bg-amber-500" style="width: <?php echo $y3p; ?>%"></div>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between">
              <div class="font-bold text-slate-700">4th Year</div>
              <div class="text-xl font-extrabold text-slate-900"><?php echo number_format($year_totals[4]); ?></div>
            </div>
            <div class="mt-2 text-sm font-semibold text-slate-500"><?php echo number_format($y4p, 1); ?>%</div>
            <div class="mt-3 h-2 rounded-full bg-slate-100 overflow-hidden">
              <div class="h-full bg-violet-600" style="width: <?php echo $y4p; ?>%"></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Historical Enrollment Trends -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
          <div>
            <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-chart-column text-orange-500"></i> Historical Enrollment Trends (2003–2026)
            </h2>
            <p class="text-slate-600 font-medium">Switch views to compare semesters across years.</p>
          </div>

          <div class="flex flex-wrap gap-2">
            <button onclick="showSemester('first')" id="btnFirstSem"
              class="px-4 py-2 rounded-xl font-extrabold text-sm bg-blue-600 text-white hover:bg-blue-700 transition">
              First Semester
            </button>
            <button onclick="showSemester('second')" id="btnSecondSem"
              class="px-4 py-2 rounded-xl font-extrabold text-sm bg-slate-900 text-white hover:bg-slate-800 transition">
              Second Semester
            </button>
            <button onclick="showSemester('both')" id="btnBothSem"
              class="px-4 py-2 rounded-xl font-extrabold text-sm bg-slate-900 text-white hover:bg-slate-800 transition">
              Both
            </button>
          </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4" style="height: 420px;">
          <canvas id="historyBarChart"></canvas>
        </div>
      </div>

      <!-- Students by Course Distribution -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-chart-pie text-emerald-600"></i> Students by Course Distribution
          </h2>
          <span class="text-xs font-bold text-slate-500">pie + bar</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-extrabold text-slate-900">Pie</h3>
              <span class="text-xs font-bold text-slate-500">share</span>
            </div>
            <div style="height: 420px;">
              <canvas id="pieChart"></canvas>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-extrabold text-slate-900">Bar</h3>
              <span class="text-xs font-bold text-slate-500">counts</span>
            </div>
            <div style="height: 420px;">
              <canvas id="courseBarChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Courses Table -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-list-check text-blue-600"></i> Courses Offered
          </h2>
          <span class="chip rounded-full px-3 py-1 text-xs font-extrabold text-slate-700">
            <?php echo number_format($total_courses); ?> courses
          </span>
        </div>

        <div class="overflow-x-auto nice-scroll rounded-2xl border border-slate-200 bg-white">
          <table class="min-w-full text-sm">
            <thead class="table-head">
              <tr class="text-slate-800">
                <th class="text-left px-4 py-3 font-extrabold rounded-tl-xl">Course Name</th>
                <th class="text-center px-4 py-3 font-extrabold">Male</th>
                <th class="text-center px-4 py-3 font-extrabold">Female</th>
                <th class="text-center px-4 py-3 font-extrabold">Total</th>
                <th class="text-center px-4 py-3 font-extrabold rounded-tr-xl">Action</th>
              </tr>
            </thead>
            <tbody id="coursesTableBody">
              <?php foreach ($courses_data as $row): ?>
                <tr class="border-b border-slate-100 hover:bg-slate-50/60 transition course-row"
                    data-course="<?php echo htmlspecialchars($row['course_name']); ?>"
                    data-years='{
                      "first_year":{"male":<?php echo (int)$row['first_year_male']; ?>,"female":<?php echo (int)$row['first_year_female']; ?>,"total":<?php echo (int)$row['first_year_male'] + (int)$row['first_year_female']; ?>},
                      "second_year":{"male":<?php echo (int)$row['second_year_male']; ?>,"female":<?php echo (int)$row['second_year_female']; ?>,"total":<?php echo (int)$row['second_year_male'] + (int)$row['second_year_female']; ?>},
                      "third_year":{"male":<?php echo (int)$row['third_year_male']; ?>,"female":<?php echo (int)$row['third_year_female']; ?>,"total":<?php echo (int)$row['third_year_male'] + (int)$row['third_year_female']; ?>},
                      "fourth_year":{"male":<?php echo (int)$row['fourth_year_male']; ?>,"female":<?php echo (int)$row['fourth_year_female']; ?>,"total":<?php echo (int)$row['fourth_year_male'] + (int)$row['fourth_year_female']; ?>}
                    }'>
                  <td class="px-4 py-3 font-bold text-slate-800"><?php echo htmlspecialchars($row['course_name']); ?></td>
                  <td class="px-4 py-3 text-center font-semibold"><?php echo (int)$row['male_students']; ?></td>
                  <td class="px-4 py-3 text-center font-semibold"><?php echo (int)$row['female_students']; ?></td>
                  <td class="px-4 py-3 text-center font-extrabold text-slate-900"><?php echo (int)$row['total_students']; ?></td>
                  <td class="px-4 py-3 text-center">
                    <button class="px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-extrabold hover:bg-slate-800 transition"
                            onclick="toggleCourseDetails(this)">
                      View Details
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <p class="mt-3 text-xs text-slate-500 font-semibold">
          Tip: Click “View Details” to show year-level breakdown per course.
        </p>
      </div>

      <!-- Year Level Charts -->
      <div class="card rounded-2xl p-5 md:p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-chart-bar text-blue-600"></i> Students by Year Level (Charts)
          </h2>
          <span class="text-xs font-bold text-slate-500">pie + bar</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-extrabold text-slate-900">Pie</h3>
              <span class="text-xs font-bold text-slate-500">share</span>
            </div>
            <div style="height: 420px;">
              <canvas id="yearPieChart"></canvas>
            </div>
          </div>

          <div class="rounded-2xl border border-slate-200 bg-white p-4">
            <div class="flex items-center justify-between mb-2">
              <h3 class="font-extrabold text-slate-900">Bar</h3>
              <span class="text-xs font-bold text-slate-500">counts</span>
            </div>
            <div style="height: 420px;">
              <canvas id="barChart"></canvas>
            </div>
          </div>
        </div>
      </div>

      <!-- Both Semesters Year Level Comparison -->
      <div class="card rounded-2xl p-5 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h2 class="text-lg md:text-xl font-extrabold text-slate-900 flex items-center gap-2">
              <i class="fa-solid fa-arrows-left-right text-violet-600"></i> Year Level Comparison (Both Semesters)
            </h2>
            <p class="text-slate-600 font-medium">Compare 1st vs 2nd semester by year level.</p>
          </div>
          <div class="text-xs font-bold text-slate-500">AY <?php echo htmlspecialchars($selected_year); ?></div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-4" style="height: 420px;">
          <canvas id="bothSemYearChart"></canvas>
        </div>

        <div class="mt-4 flex flex-wrap gap-2">
          <?php for ($i = 1; $i <= 4; $i++): ?>
            <?php
              $change = $percent_changes[$i];
              $cls = $change > 0 ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : ($change < 0 ? 'bg-rose-50 text-rose-700 border-rose-200' : 'bg-slate-100 text-slate-700 border-slate-200');
              $icon = $change > 0 ? 'fa-arrow-up' : ($change < 0 ? 'fa-arrow-down' : 'fa-minus');
            ?>
            <span class="inline-flex items-center gap-2 border rounded-full px-3 py-1 text-xs font-extrabold <?php echo $cls; ?>">
              <span><?php echo ordinal($i); ?> Year</span>
              <span class="opacity-80">•</span>
              <i class="fa-solid <?php echo $icon; ?>"></i>
              <span><?php echo ($change > 0 ? '+' : ''); ?><?php echo $change; ?>%</span>
            </span>
          <?php endfor; ?>
        </div>
      </div>

    </div>
  </div>

<script>
  // Dashboard Chart Functions
  let historyChart = null;
  let currentView = 'first';

  // Historical enrollment data from PHP
  const historyData = <?php echo json_encode($history_data); ?>;

  function setBtnStyles(active) {
    const btnFirst = document.getElementById('btnFirstSem');
    const btnSecond = document.getElementById('btnSecondSem');
    const btnBoth = document.getElementById('btnBothSem');

    const activeCls = 'px-4 py-2 rounded-xl font-extrabold text-sm bg-blue-600 text-white hover:bg-blue-700 transition';
    const idleCls   = 'px-4 py-2 rounded-xl font-extrabold text-sm bg-slate-900 text-white hover:bg-slate-800 transition';

    btnFirst.className  = active === 'first' ? activeCls : idleCls;
    btnSecond.className = active === 'second' ? activeCls : idleCls;
    btnBoth.className   = active === 'both' ? activeCls : idleCls;
  }

  function showSemester(semester) {
    currentView = semester;
    setBtnStyles(semester);

    let filteredData = historyData;
    if (semester !== 'both') {
      const semesterName = semester === 'first' ? 'First Semester' : 'Second Semester';
      filteredData = historyData.filter(d => d.semester === semesterName);
    }

    const labels = [];
    const datasets = [];

    if (semester === 'both') {
      const firstSemData = historyData.filter(d => d.semester === 'First Semester');
      const secondSemData = historyData.filter(d => d.semester === 'Second Semester');

      firstSemData.forEach(d => labels.push(d.school_year));

      datasets.push({
        label: 'First Semester',
        data: firstSemData.map(d => d.total_enrollees),
        backgroundColor: '#3b82f6',
        borderColor: '#2563eb',
        borderWidth: 2,
        borderRadius: 10
      });

      datasets.push({
        label: 'Second Semester',
        data: secondSemData.map(d => d.total_enrollees),
        backgroundColor: '#10b981',
        borderColor: '#059669',
        borderWidth: 2,
        borderRadius: 10
      });
    } else {
      filteredData.forEach(d => labels.push(d.school_year));
      datasets.push({
        label: semester === 'first' ? 'First Semester' : 'Second Semester',
        data: filteredData.map(d => d.total_enrollees),
        backgroundColor: semester === 'first' ? '#3b82f6' : '#10b981',
        borderColor: semester === 'first' ? '#2563eb' : '#059669',
        borderWidth: 2,
        borderRadius: 10
      });
    }

    if (historyChart) historyChart.destroy();

    const ctx = document.getElementById('historyBarChart').getContext('2d');
    historyChart = new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: semester === 'both', position: 'top' },
          title: {
            display: true,
            text: semester === 'both'
              ? 'Enrollment Comparison by Semester'
              : (semester === 'first' ? 'First Semester Enrollment' : 'Second Semester Enrollment'),
            font: { size: 16, weight: '800' }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.dataset.label + ': ' + Number(context.parsed.y).toLocaleString() + ' students';
              }
            }
          }
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { font: { size: 10 }, maxRotation: 45, minRotation: 45 }
          },
          y: {
            beginAtZero: true,
            grid: { color: "rgba(15,23,42,.06)" },
            ticks: { callback: v => Number(v).toLocaleString() }
          }
        }
      }
    });
  }

  function toggleCourseDetails(button) {
    const courseRow = button.closest('.course-row');
    const yearData = JSON.parse(courseRow.getAttribute('data-years'));

    let nextRow = courseRow.nextElementSibling;
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

    const years = [
      { key: 'first_year', label: '1st Year' },
      { key: 'second_year', label: '2nd Year' },
      { key: 'third_year', label: '3rd Year' },
      { key: 'fourth_year', label: '4th Year' }
    ];

    years.reverse().forEach(year => {
      const childRow = document.createElement('tr');
      childRow.classList.add('child-row', 'visible');
      childRow.innerHTML = `
        <td class="px-4 py-3 text-slate-700 font-bold">${year.label}</td>
        <td class="px-4 py-3 text-center font-semibold">${yearData[year.key].male}</td>
        <td class="px-4 py-3 text-center font-semibold">${yearData[year.key].female}</td>
        <td class="px-4 py-3 text-center font-extrabold text-slate-900">${yearData[year.key].total}</td>
        <td class="px-4 py-3 text-center"></td>
      `;
      childRow.style.background = "rgba(2, 6, 23, 0.02)";
      courseRow.insertAdjacentElement('afterend', childRow);
    });

    button.textContent = 'Hide Details';
  }

  document.addEventListener('DOMContentLoaded', function() {
    // Initialize charts
    showSemester('first');

    // Course Pie Chart
    const pieCtx = document.getElementById('pieChart').getContext('2d');
    const courseLabels = [<?php foreach ($courses_data as $row): ?>'<?php echo addslashes($row['course_name']); ?>',<?php endforeach; ?>];
    const courseData = [<?php foreach ($courses_data as $row): ?><?php echo (int)$row['total_students']; ?>,<?php endforeach; ?>];
    const pieTotalStudents = courseData.reduce((a, b) => a + b, 0);

    new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: courseLabels,
        datasets: [{
          data: courseData,
          backgroundColor: [
            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40',
            '#EF4444', '#10B981', '#F59E0B', '#3B82F6', '#8B5CF6', '#06B6D4'
          ]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            position: 'bottom',
            labels: { boxWidth: 14, padding: 10, font: { size: 11 } }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed;
                const percentage = (value * 100 / Math.max(pieTotalStudents, 1)).toFixed(1) + '%';
                return label + ': ' + Number(value).toLocaleString() + ' (' + percentage + ')';
              }
            }
          }
        }
      }
    });

    // Course Bar Chart
    const courseBarCtx = document.getElementById('courseBarChart').getContext('2d');
    new Chart(courseBarCtx, {
      type: 'bar',
      data: {
        labels: courseLabels,
        datasets: [{
          label: 'Students',
          data: courseData,
          backgroundColor: [
            '#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EF4444', '#06B6D4',
            '#EC4899', '#14B8A6', '#F97316', '#6366F1', '#84CC16', '#F43F5E'
          ],
          borderRadius: 10,
          borderWidth: 0
        }]
      },
      options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        layout: { padding: { right: 30 } },
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: function(context) {
                const value = context.parsed.x;
                const percentage = (value * 100 / Math.max(pieTotalStudents, 1)).toFixed(1) + '%';
                return Number(value).toLocaleString() + ' students (' + percentage + ')';
              }
            }
          }
        },
        scales: {
          x: { beginAtZero: true, grid: { color: "rgba(15,23,42,.06)" } },
          y: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
      }
    });

    // Year Level Pie Chart
    const yearPieCtx = document.getElementById('yearPieChart').getContext('2d');
    const yearLevelData = [<?php echo implode(',', [(int)$year_totals[1], (int)$year_totals[2], (int)$year_totals[3], (int)$year_totals[4]]); ?>];
    const totalYearLevelStudents = yearLevelData.reduce((a, b) => a + b, 0);

    new Chart(yearPieCtx, {
      type: 'pie',
      data: {
        labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
        datasets: [{
          data: yearLevelData,
          backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6']
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: {
            callbacks: {
              label: function(context) {
                const label = context.label || '';
                const value = context.parsed;
                const percentage = (value * 100 / Math.max(totalYearLevelStudents, 1)).toFixed(1) + '%';
                return label + ': ' + Number(value).toLocaleString() + ' (' + percentage + ')';
              }
            }
          }
        }
      }
    });

    // Year Level Bar Chart
    const barCtx = document.getElementById('barChart').getContext('2d');
    new Chart(barCtx, {
      type: 'bar',
      data: {
        labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
        datasets: [{
          label: 'Students',
          data: yearLevelData,
          backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6'],
          borderRadius: 10,
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          y: { beginAtZero: true, grid: { color: "rgba(15,23,42,.06)" } },
          x: { grid: { display: false } }
        }
      }
    });

    // Both Semesters Year Level Chart
    const bothSemCtx = document.getElementById('bothSemYearChart').getContext('2d');
    new Chart(bothSemCtx, {
      type: 'bar',
      data: {
        labels: ['1st Year', '2nd Year', '3rd Year', '4th Year'],
        datasets: [{
          label: '1st Semester',
          data: [<?php echo implode(',', array_map('intval', $year_totals_1st)); ?>],
          backgroundColor: '#3b82f6',
          borderColor: '#2563eb',
          borderWidth: 1,
          borderRadius: 10
        }, {
          label: '2nd Semester',
          data: [<?php echo implode(',', array_map('intval', $year_totals_2nd)); ?>],
          backgroundColor: '#10b981',
          borderColor: '#059669',
          borderWidth: 1,
          borderRadius: 10
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'top' },
          title: { display: true, text: 'Year Level Comparison by Semester', font: { size: 16, weight: '800' } }
        },
        scales: {
          y: { beginAtZero: true, grid: { color: "rgba(15,23,42,.06)" } },
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
