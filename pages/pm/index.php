<?php
require_once __DIR__ . '/../config/check-session.php';
require_once __DIR__ . '/../config/conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$allowedStatuses = ['Planning', 'Active', 'On Hold', 'Completed'];
$role = $_SESSION['role'] ?? '';
$canAddProjects = in_array($role, ['admin', 'belvic_admin'], true);
$canViewFinancialRecords = $canAddProjects || $role === 'president';
$errors = [];
$formAction = 'add_project';
$editingProjectId = '';
$formValues = [
  'department' => '',
  'contract_id' => '',
  'contract_description' => '',
  'owner' => '',
  'participation_percentage' => '',
  'contract_date_started' => '',
  'contract_date_completed' => '',
  'major_categories_of_work' => '',
  'dimension_km' => '',
  'total_as_built_cost_per_major_work_category' => '',
  'location' => '',
  'status' => 'Planning',
];

if (!isset($_SESSION['pm_csrf_token'])) {
  $_SESSION['pm_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add_project', 'edit_project'], true)) {
  if (!$canAddProjects) {
    http_response_code(403);
    exit('You do not have permission to manage projects.');
  }

  $formAction = $_POST['action'];
  $submittedProjectId = $_POST['project_id'] ?? '';
  $editingProjectId = is_scalar($submittedProjectId) ? trim((string)$submittedProjectId) : '';
  if ($formAction === 'edit_project' && (!ctype_digit($editingProjectId) || (int)$editingProjectId < 1)) {
    $errors[] = 'The project to edit is invalid.';
  }

  foreach ($formValues as $field => $default) {
    $submittedValue = $_POST[$field] ?? $default;
    $formValues[$field] = is_scalar($submittedValue) ? trim((string)$submittedValue) : '';
  }

  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($submittedToken) || !hash_equals($_SESSION['pm_csrf_token'], $submittedToken)) {
    $errors[] = 'Your session token is invalid. Refresh the page and try again.';
  }

  foreach ([
    'department' => 'Department',
    'contract_id' => 'Contract ID',
    'contract_description' => 'Contract description',
    'owner' => 'Owner',
    'participation_percentage' => 'Participation percentage',
    'contract_date_started' => 'Contract date started',
    'major_categories_of_work' => 'Major categories of work',
    'dimension_km' => 'Dimension',
    'total_as_built_cost_per_major_work_category' => 'Total as-built cost per major work category',
    'location' => 'Location',
  ] as $field => $label) {
    if ($formValues[$field] === '') {
      $errors[] = $label . ' is required.';
    }
  }

  $fieldLimits = [
    'department' => 255,
    'contract_id' => 100,
    'contract_description' => 5000,
    'owner' => 255,
    'major_categories_of_work' => 500,
    'location' => 10000,
  ];
  foreach ($fieldLimits as $field => $limit) {
    $length = function_exists('mb_strlen') ? mb_strlen($formValues[$field], 'UTF-8') : strlen($formValues[$field]);
    if ($length > $limit) {
      $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be ' . $limit . ' characters or fewer.';
    }
  }

  if ($formValues['participation_percentage'] !== '' &&
      (!is_numeric($formValues['participation_percentage']) ||
       (float)$formValues['participation_percentage'] < 0 ||
       (float)$formValues['participation_percentage'] > 100)) {
    $errors[] = 'Participation percentage must be between 0 and 100.';
  }

  foreach ([
    'total_as_built_cost_per_major_work_category' => ['Total as-built cost', 13],
  ] as $field => [$label, $maxWholeDigits]) {
    $decimalValue = $formValues[$field];
    if ($decimalValue !== '') {
      if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $decimalValue)) {
        $errors[] = $label . ' must be a non-negative number with up to two decimal places.';
      } else {
        $wholeValue = ltrim(explode('.', $decimalValue, 2)[0], '0') ?: '0';
        if (strlen($wholeValue) > $maxWholeDigits) {
          $errors[] = $label . ' is larger than the supported maximum.';
        }
      }
    }
  }

  $validDate = static function($value) {
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value;
  };
  if ($formValues['contract_date_started'] !== '' && !$validDate($formValues['contract_date_started'])) {
    $errors[] = 'Contract date started must be a valid date.';
  }
  if ($formValues['contract_date_completed'] !== '' && !$validDate($formValues['contract_date_completed'])) {
    $errors[] = 'Contract date completed must be a valid date.';
  }
  if ($formValues['contract_date_completed'] !== '' &&
      $validDate($formValues['contract_date_completed']) &&
      $validDate($formValues['contract_date_started']) &&
      $formValues['contract_date_completed'] < $formValues['contract_date_started']) {
    $errors[] = 'Contract date completed cannot be before its start date.';
  }

  if (!in_array($formValues['status'], $allowedStatuses, true)) {
    $errors[] = 'Choose a valid project status.';
  }

  if (!$errors) {
    try {
      $participationPercentage = (float)$formValues['participation_percentage'];
      $dimensionKm = $formValues['dimension_km'];
      $totalAsBuiltCost = $formValues['total_as_built_cost_per_major_work_category'];
      $contractDateCompleted = $formValues['contract_date_completed'] !== '' ? $formValues['contract_date_completed'] : null;
      if ($formAction === 'add_project') {
        $stmt = $conn->prepare('INSERT INTO management_projects (department, contract_id, contract_description, owner, participation_percentage, contract_date_started, contract_date_completed, major_categories_of_work, dimension_km, total_as_built_cost_per_major_work_category, location, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param(
          'ssssdsssssss',
          $formValues['department'],
          $formValues['contract_id'],
          $formValues['contract_description'],
          $formValues['owner'],
          $participationPercentage,
          $formValues['contract_date_started'],
          $contractDateCompleted,
          $formValues['major_categories_of_work'],
          $dimensionKm,
          $totalAsBuiltCost,
          $formValues['location'],
          $formValues['status']
        );
      } else {
        $projectId = (int)$editingProjectId;
        $stmt = $conn->prepare('UPDATE management_projects SET department = ?, contract_id = ?, contract_description = ?, owner = ?, participation_percentage = ?, contract_date_started = ?, contract_date_completed = ?, major_categories_of_work = ?, dimension_km = ?, total_as_built_cost_per_major_work_category = ?, location = ?, status = ? WHERE id = ?');
        $stmt->bind_param(
          'ssssdsssssssi',
          $formValues['department'],
          $formValues['contract_id'],
          $formValues['contract_description'],
          $formValues['owner'],
          $participationPercentage,
          $formValues['contract_date_started'],
          $contractDateCompleted,
          $formValues['major_categories_of_work'],
          $dimensionKm,
          $totalAsBuiltCost,
          $formValues['location'],
          $formValues['status'],
          $projectId
        );
      }
      $stmt->execute();
      $stmt->close();

      $redirect = $formAction === 'add_project' ? 'index.php?saved=1' : 'index.php?updated=1';
      $returnQuery = $_POST['return_q'] ?? '';
      $returnStatus = $_POST['return_status'] ?? 'all';
      $redirectParams = array_filter([
        'q' => is_string($returnQuery) ? trim($returnQuery) : '',
        'status' => is_string($returnStatus) ? trim($returnStatus) : 'all',
      ], static fn($value) => $value !== '' && $value !== 'all');
      if ($redirectParams) {
        $redirect .= '&' . http_build_query($redirectParams);
      }
      header('Location: ' . $redirect);
      exit;
    } catch (mysqli_sql_exception $exception) {
      if ((int)$exception->getCode() === 1062) {
        $errors[] = 'That Contract ID is already in use.';
      } else {
        error_log('Project save failed: ' . $exception->getMessage());
        $errors[] = 'The project could not be saved. Please try again.';
      }
    }
  }
}

$queryValue = $_GET['q'] ?? '';
$statusValue = $_GET['status'] ?? 'all';
$q = is_string($queryValue) ? trim($queryValue) : '';
$status = is_string($statusValue) ? trim($statusValue) : 'all';
if ($status !== 'all' && !in_array($status, $allowedStatuses, true)) {
  $status = 'all';
}

try {
  $result = $conn->query('SELECT id, department, contract_id, contract_description, owner, participation_percentage, contract_date_started, contract_date_completed, major_categories_of_work, dimension_km, total_as_built_cost_per_major_work_category, location, status FROM management_projects ORDER BY id DESC');
  $projects = $result->fetch_all(MYSQLI_ASSOC);
  $result->free();
} catch (mysqli_sql_exception $exception) {
  error_log('Project list query failed: ' . $exception->getMessage());
  $projects = [];
  $loadError = 'Projects are temporarily unavailable. Confirm that the project management migration has been applied.';
}

$filtered = array_filter($projects, function($p) use ($q, $status) {
  $hay = strtolower($p['department'].' '.$p['contract_id'].' '.$p['contract_description'].' '.$p['owner'].' '.$p['major_categories_of_work'].' '.$p['location'].' '.$p['status']);
  $matchQ = $q === '' || str_contains($hay, strtolower($q));
  $matchStatus = $status === 'all' || strtolower($p['status']) === strtolower($status);
  return $matchQ && $matchStatus;
});

$total_projects = count($projects);
$visible_projects = count($filtered);

$statusCounts = ['Active'=>0,'Planning'=>0,'On Hold'=>0,'Completed'=>0];
foreach ($projects as $p) {
  $statusCounts[$p['status']]++;
}

function badgeClass($status) {
  $s = strtolower($status);
  if ($s === 'active') return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  if ($s === 'planning') return 'bg-sky-50 text-sky-700 border-sky-200';
  if ($s === 'on hold') return 'bg-amber-50 text-amber-700 border-amber-200';
  if ($s === 'completed') return 'bg-violet-50 text-violet-700 border-violet-200';
  return 'bg-slate-50 text-slate-700 border-slate-200';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Project Management Dashboard</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
    .sidebar-link { transition: background-color .16s ease, color .16s ease, transform .16s ease; }
    .sidebar-link:hover { transform: translateX(2px); }

    /* ✅ Make table consume full available width */
    table { width: 100%; table-layout: fixed; }
    th, td { vertical-align: top; }
    .cell-wrap { word-break: break-word; overflow-wrap: anywhere; }
    dialog::backdrop { background: rgba(15, 23, 42, .45); backdrop-filter: blur(3px); }
    dialog[open] { animation: dialog-in .16s ease-out; }
    @keyframes dialog-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

    /* Sticky header */
    thead th {
      position: sticky;
      top: 0;
      z-index: 1;
      background: rgba(248, 250, 252, 0.98);
      backdrop-filter: blur(6px);
    }
  </style>
</head>

<body class="min-h-screen bg-slate-50 bg-grid">
  <div id="sidebarBackdrop" class="fixed inset-0 z-40 hidden bg-slate-950/40 backdrop-blur-[2px] lg:hidden" data-close-sidebar></div>
  <aside id="projectSidebar" class="fixed inset-y-0 left-0 z-50 flex w-[min(84vw,288px)] -translate-x-full flex-col border-r border-slate-200 bg-white shadow-xl transition-transform duration-200 lg:w-72 lg:translate-x-0 lg:shadow-none">
    <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-6">
      <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
        <i class="fa-solid fa-diagram-project text-lg" aria-hidden="true"></i>
      </div>
      <div class="min-w-0">
        <p class="truncate font-black text-slate-900">Project Management</p>
        <p class="text-xs font-semibold text-slate-500">Operations dashboard</p>
      </div>
      <button type="button" id="closeSidebar" class="ml-auto inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-300 lg:hidden" aria-label="Close navigation">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <nav aria-label="Project Management" class="flex-1 space-y-1 px-3 py-5">
      <p class="px-3 pb-2 text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Workspace</p>
      <a href="index.php" aria-current="page" class="sidebar-link flex items-center gap-3 rounded-xl bg-slate-900 px-3 py-3 font-bold text-white shadow-sm">
        <i class="fa-solid fa-table-columns w-5 text-center" aria-hidden="true"></i>
        <span>Project Dashboard</span>
      </a>
      <?php if ($canViewFinancialRecords): ?>
        <a href="financial_records.php" class="sidebar-link flex items-center gap-3 rounded-xl px-3 py-3 font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-900">
          <i class="fa-solid fa-file-invoice-dollar w-5 text-center" aria-hidden="true"></i>
          <span>Financial Records</span>
        </a>
      <?php endif; ?>
    </nav>
    <div class="border-t border-slate-100 p-3">
      <a href="../portal.php" class="sidebar-link flex items-center gap-3 rounded-xl px-3 py-3 font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-900">
        <i class="fa-solid fa-arrow-left w-5 text-center" aria-hidden="true"></i>
        <span>Back to Portal</span>
      </a>
    </div>
  </aside>

  <div class="min-h-screen lg:pl-72">
    <main class="w-full px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
      <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <button type="button" id="openSidebar" aria-controls="projectSidebar" aria-expanded="false" aria-label="Open navigation" class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-300 lg:hidden">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
          </button>
          <div>
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Project Management</p>
            <p class="font-extrabold text-slate-900">Project Dashboard</p>
          </div>
        </div>
        <div class="flex items-center gap-2 text-xs font-extrabold text-slate-600">
          <span class="chip rounded-full px-3 py-2">Total: <?php echo number_format($total_projects); ?></span>
          <span class="chip hidden rounded-full px-3 py-2 sm:inline-flex">Showing: <?php echo number_format($visible_projects); ?></span>
        </div>
      </div>

      <!-- Header -->
      <div class="mb-7 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
        <div>
          <div class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-700 chip rounded-full px-3 py-1">
            <i class="fa-solid fa-chart-simple text-blue-600" aria-hidden="true"></i>
            Operations overview
          </div>
          <h1 class="mt-3 text-2xl font-black text-slate-900 sm:text-3xl">
            Project Dashboard
          </h1>
          <p class="mt-1 max-w-2xl text-slate-600 font-semibold">
            Monitor project delivery, contract details, and status across the portfolio.
          </p>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-2">
          <?php if ($canAddProjects): ?>
            <button type="button" id="openProjectDialog" class="inline-flex items-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 font-extrabold text-white shadow-sm transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
              <i class="fa-solid fa-plus" aria-hidden="true"></i>
              Add Project
            </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if (!empty($_GET['saved'])): ?>
        <div role="status" class="mb-5 rounded-xl border border-slate-200 bg-white px-4 py-3 font-bold text-slate-800">
          Project added successfully.
        </div>
      <?php endif; ?>
      <?php if (!empty($_GET['updated'])): ?>
        <div role="status" class="mb-5 rounded-xl border border-slate-200 bg-white px-4 py-3 font-bold text-slate-800">
          Project details updated successfully.
        </div>
      <?php endif; ?>
      <?php if (isset($loadError)): ?>
        <div role="alert" class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 font-bold text-amber-900">
          <?php echo e($loadError); ?>
        </div>
      <?php endif; ?>

      <?php if ($canAddProjects): ?>
        <dialog id="projectDialog" aria-labelledby="projectDialogTitle" class="w-[min(92vw,720px)] max-w-none rounded-2xl border border-slate-200 bg-white p-0 shadow-2xl">
          <form id="projectForm" method="POST" class="p-5 md:p-7">
            <input id="projectAction" type="hidden" name="action" value="<?php echo e($formAction); ?>">
            <input id="projectId" type="hidden" name="project_id" value="<?php echo e($editingProjectId); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['pm_csrf_token']); ?>">
            <input type="hidden" name="return_q" value="<?php echo e($q); ?>">
            <input type="hidden" name="return_status" value="<?php echo e($status); ?>">
            <div class="mb-5 flex items-start justify-between gap-4">
              <div>
                <h2 id="projectDialogTitle" class="text-xl font-black text-slate-900"><?php echo $formAction === 'edit_project' ? 'Edit Project' : 'Add Project'; ?></h2>
                <p class="mt-1 text-sm font-semibold text-slate-600">Enter the contract details below.</p>
              </div>
              <button type="button" id="closeProjectDialog" aria-label="Close form" class="inline-flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
              </button>
            </div>

            <?php if ($errors): ?>
              <div role="alert" class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
                <ul class="list-disc space-y-1 pl-5">
                  <?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div>
                <label for="department" class="mb-1 block text-sm font-extrabold text-slate-700">Department <span aria-hidden="true" class="text-red-600">*</span></label>
                <input id="department" name="department" type="text" maxlength="255" required autocomplete="organization" value="<?php echo e($formValues['department']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div>
                <label for="contract_id" class="mb-1 block text-sm font-extrabold text-slate-700">Contract ID <span aria-hidden="true" class="text-red-600">*</span></label>
                <input id="contract_id" name="contract_id" type="text" maxlength="100" required value="<?php echo e($formValues['contract_id']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div class="sm:col-span-2">
                <label for="contract_description" class="mb-1 block text-sm font-extrabold text-slate-700">Contract Description <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="contract_description" name="contract_description" maxlength="5000" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200"><?php echo e($formValues['contract_description']); ?></textarea>
              </div>
              <div>
                <label for="owner" class="mb-1 block text-sm font-extrabold text-slate-700">Owner <span aria-hidden="true" class="text-red-600">*</span></label>
                <input id="owner" name="owner" type="text" maxlength="255" required value="<?php echo e($formValues['owner']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div>
                <label for="participation_percentage" class="mb-1 block text-sm font-extrabold text-slate-700">Participation Percentage <span aria-hidden="true" class="text-red-600">*</span></label>
                <div class="relative">
                  <input id="participation_percentage" name="participation_percentage" type="number" min="0" max="100" step="0.01" required value="<?php echo e($formValues['participation_percentage']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 pr-10 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                  <span class="absolute right-3 top-1/2 -translate-y-1/2 font-bold text-slate-500">%</span>
                </div>
              </div>
              <div>
                <label for="contract_date_started" class="mb-1 block text-sm font-extrabold text-slate-700">Contract Date Started <span aria-hidden="true" class="text-red-600">*</span></label>
                <input id="contract_date_started" name="contract_date_started" type="date" required value="<?php echo e($formValues['contract_date_started']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div>
                <label for="contract_date_completed" class="mb-1 block text-sm font-extrabold text-slate-700">Contract Date Completed</label>
                <input id="contract_date_completed" name="contract_date_completed" type="date" min="<?php echo e($formValues['contract_date_started']); ?>" value="<?php echo e($formValues['contract_date_completed']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div class="sm:col-span-2">
                <label for="major_categories_of_work" class="mb-1 block text-sm font-extrabold text-slate-700">Major Categories of Work <span aria-hidden="true" class="text-red-600">*</span></label>
                <input id="major_categories_of_work" name="major_categories_of_work" type="text" maxlength="500" required value="<?php echo e($formValues['major_categories_of_work']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
              </div>
              <div>
                <label for="dimension_km" class="mb-1 block text-sm font-extrabold text-slate-700">Dimension <span aria-hidden="true" class="text-red-600">*</span></label>
                <div class="relative">
                  <input id="dimension_km" name="dimension_km" type="text" placeholder="e.g. 12.50 or 10 x 5" required value="<?php echo e($formValues['dimension_km']); ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
              </div>
              <div>
                <label for="total_as_built_cost_per_major_work_category" class="mb-1 block text-sm font-extrabold text-slate-700">Total As-Built Cost Per Major Work Category (PHP) <span aria-hidden="true" class="text-red-600">*</span></label>
                <div class="relative">
                  <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-500">PHP</span>
                  <input id="total_as_built_cost_per_major_work_category" name="total_as_built_cost_per_major_work_category" type="number" min="0" max="9999999999999.99" step="0.01" required value="<?php echo e($formValues['total_as_built_cost_per_major_work_category']); ?>" class="w-full rounded-xl border border-slate-300 py-2.5 pl-14 pr-3 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                </div>
              </div>
              <div class="sm:col-span-2">
                <label for="location" class="mb-1 block text-sm font-extrabold text-slate-700">Location <span aria-hidden="true" class="text-red-600">*</span></label>
                <textarea id="location" name="location" maxlength="10000" rows="3" required class="w-full rounded-xl border border-slate-300 px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200"><?php echo e($formValues['location']); ?></textarea>
              </div>
              <div>
                <label for="project_status" class="mb-1 block text-sm font-extrabold text-slate-700">Status <span aria-hidden="true" class="text-red-600">*</span></label>
                <select id="project_status" name="status" required class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200">
                  <?php foreach ($allowedStatuses as $projectStatus): ?>
                    <option value="<?php echo e($projectStatus); ?>" <?php echo $formValues['status'] === $projectStatus ? 'selected' : ''; ?>><?php echo e($projectStatus); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="mt-6 flex flex-col-reverse justify-end gap-2 sm:flex-row">
              <button type="button" data-close-project-dialog class="rounded-xl border border-slate-300 px-4 py-2.5 font-extrabold text-slate-700 hover:bg-slate-50">Cancel</button>
              <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-700 px-4 py-2.5 font-extrabold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
                <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i>
                <span id="projectSubmitLabel"><?php echo $formAction === 'edit_project' ? 'Save Changes' : 'Save Project'; ?></span>
              </button>
            </div>
          </form>
        </dialog>
      <?php endif; ?>

      <!-- Filters -->
      <div class="card rounded-2xl p-4 md:p-5 mb-6">
        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-3">
          <div class="flex items-center gap-2 text-slate-800 font-extrabold">
            <i class="fa-solid fa-filter text-emerald-600"></i> Filters
            <span class="text-xs font-bold text-slate-500">(auto apply)</span>
          </div>

          <form id="filterForm" method="GET" class="flex flex-col sm:flex-row gap-3 sm:items-center w-full xl:w-auto">
            <div class="relative flex-1 min-w-[240px] w-full xl:w-[520px]">
              <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <input
                id="qInput"
                name="q"
                value="<?php echo e($q); ?>"
                placeholder="Search department, contract id, name, location..."
                class="w-full pl-9 pr-3 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-900 font-semibold focus:outline-none focus:ring-2 focus:ring-blue-200"
              />
            </div>

            <div class="relative w-full sm:w-64">
              <i class="fa-solid fa-tag absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
              <select
                id="statusSelect"
                name="status"
                class="pl-9 pr-10 py-2.5 w-full rounded-xl border border-slate-200 bg-white text-slate-900 font-extrabold focus:outline-none focus:ring-2 focus:ring-emerald-200"
              >
                <option value="all" <?php echo ($status==='all')?'selected':''; ?>>All Status</option>
                <option value="Active" <?php echo ($status==='Active')?'selected':''; ?>>Active</option>
                <option value="Planning" <?php echo ($status==='Planning')?'selected':''; ?>>Planning</option>
                <option value="On Hold" <?php echo ($status==='On Hold')?'selected':''; ?>>On Hold</option>
                <option value="Completed" <?php echo ($status==='Completed')?'selected':''; ?>>Completed</option>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
            </div>

            <noscript>
              <button type="submit" class="px-4 py-2 rounded-xl bg-blue-600 text-white font-extrabold">Apply</button>
            </noscript>
          </form>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-bold text-slate-500">Active</p>
              <p class="mt-1 text-2xl font-black text-slate-900"><?php echo number_format($statusCounts['Active']); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center">
              <i class="fa-solid fa-bolt text-emerald-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-bold text-slate-500">Planning</p>
              <p class="mt-1 text-2xl font-black text-slate-900"><?php echo number_format($statusCounts['Planning']); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-sky-50 flex items-center justify-center">
              <i class="fa-solid fa-compass-drafting text-sky-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-bold text-slate-500">On Hold</p>
              <p class="mt-1 text-2xl font-black text-slate-900"><?php echo number_format($statusCounts['On Hold']); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center">
              <i class="fa-solid fa-pause text-amber-600"></i>
            </div>
          </div>
        </div>

        <div class="card rounded-2xl p-5">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-sm font-bold text-slate-500">Completed</p>
              <p class="mt-1 text-2xl font-black text-slate-900"><?php echo number_format($statusCounts['Completed']); ?></p>
            </div>
            <div class="h-10 w-10 rounded-xl bg-violet-50 flex items-center justify-center">
              <i class="fa-solid fa-check text-violet-600"></i>
            </div>
          </div>
        </div>
      </div>

      <!-- Projects List -->
      <div class="card rounded-2xl p-5 md:p-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-4">
          <div>
            <h2 class="text-lg md:text-xl font-black text-slate-900">Projects</h2>
            <p class="text-slate-600 font-semibold">Click a row to copy Contract ID.</p>
          </div>
        </div>

        <div class="overflow-x-auto nice-scroll">
          <table class="min-w-[2140px] text-sm">
            <thead>
              <tr class="text-slate-800">
                <th class="w-[140px] text-left px-3 py-3 font-black">Department</th>
                <th class="w-[110px] text-left px-3 py-3 font-black">Contract ID</th>
                <th class="w-[320px] text-left px-3 py-3 font-black">Contract Description</th>
                <th class="w-[160px] text-left px-3 py-3 font-black">Owner</th>
                <th class="w-[120px] text-right px-3 py-3 font-black">Participation</th>
                <th class="w-[135px] text-left px-3 py-3 font-black">Date Started</th>
                <th class="w-[135px] text-left px-3 py-3 font-black">Date Completed</th>
                <th class="w-[230px] text-left px-3 py-3 font-black">Major Categories of Work</th>
                <th class="w-[110px] text-right px-3 py-3 font-black">Dimension</th>
                <th class="w-[190px] text-right px-3 py-3 font-black">Total As-Built Cost</th>
                <th class="w-[280px] text-left px-3 py-3 font-black">Location</th>
                <th class="w-[110px] text-center px-3 py-3 font-black">Status</th>
                <?php if ($canAddProjects): ?><th class="w-[100px] text-center px-3 py-3 font-black">Actions</th><?php endif; ?>
              </tr>
            </thead>
            <tbody>
              <?php if (count($filtered) === 0): ?>
                <tr>
                  <td colspan="<?php echo $canAddProjects ? '13' : '12'; ?>" class="px-3 py-8 text-center text-slate-600 font-semibold">
                    No projects found for your filters.
                  </td>
                </tr>
              <?php endif; ?>

              <?php foreach ($filtered as $p): ?>
                <tr
                  class="border-t border-slate-100 hover:bg-slate-50/70 transition cursor-pointer project-row"
                  data-copy="<?php echo e($p['contract_id']); ?>"
                  title="Click to copy Contract ID"
                >
                  <td class="px-3 py-3 font-extrabold text-slate-900 cell-wrap"><?php echo e($p['department']); ?></td>

                  <td class="px-3 py-3">
                    <span class="inline-flex items-center gap-2 font-black text-slate-900">
                      <i class="fa-solid fa-hashtag text-slate-400"></i>
                      <?php echo e($p['contract_id']); ?>
                    </span>
                  </td>

                  <td class="px-3 py-3 font-semibold text-slate-800 cell-wrap"><?php echo e($p['contract_description']); ?></td>
                  <td class="px-3 py-3 font-semibold text-slate-800 cell-wrap"><?php echo e($p['owner'] ?: 'Not provided'); ?></td>
                  <td class="px-3 py-3 text-right font-semibold text-slate-800"><?php echo number_format((float)$p['participation_percentage'], 2); ?>%</td>
                  <td class="px-3 py-3 font-semibold text-slate-700"><?php echo e($p['contract_date_started'] ?: 'Not set'); ?></td>
                  <td class="px-3 py-3 font-semibold text-slate-700"><?php echo e($p['contract_date_completed'] ?: 'Not completed'); ?></td>
                  <td class="px-3 py-3 font-semibold text-slate-800 cell-wrap"><?php echo e($p['major_categories_of_work'] ?: 'Not provided'); ?></td>
                  <td class="px-3 py-3 text-right font-semibold text-slate-700"><?php echo $p['dimension_km'] === null || trim((string)$p['dimension_km']) === '' ? 'Not provided' : e($p['dimension_km']); ?></td>
                  <td class="px-3 py-3 text-right font-semibold text-slate-700"><?php echo $p['total_as_built_cost_per_major_work_category'] === null ? 'Not provided' : 'PHP ' . number_format((float)$p['total_as_built_cost_per_major_work_category'], 2); ?></td>

                  <td class="px-3 py-3 font-semibold text-slate-700 cell-wrap">
                    <i class="fa-solid fa-location-dot text-slate-400"></i>
                    <?php echo e($p['location']); ?>
                  </td>

                  <td class="px-3 py-3 text-center">
                    <span class="inline-flex items-center justify-center border rounded-full px-3 py-1 text-xs font-black <?php echo badgeClass($p['status']); ?>">
                      <?php echo e($p['status']); ?>
                    </span>
                  </td>
                  <?php if ($canAddProjects): ?>
                    <td class="px-3 py-3 text-center">
                      <button
                        type="button"
                        data-edit-project
                        data-project-id="<?php echo e($p['id']); ?>"
                        data-department="<?php echo e($p['department']); ?>"
                        data-contract-id="<?php echo e($p['contract_id']); ?>"
                        data-contract-description="<?php echo e($p['contract_description']); ?>"
                        data-owner="<?php echo e($p['owner']); ?>"
                        data-participation-percentage="<?php echo e($p['participation_percentage']); ?>"
                        data-contract-date-started="<?php echo e($p['contract_date_started'] ?? ''); ?>"
                        data-contract-date-completed="<?php echo e($p['contract_date_completed'] ?? ''); ?>"
                        data-major-categories-of-work="<?php echo e($p['major_categories_of_work']); ?>"
                        data-dimension-km="<?php echo e($p['dimension_km']); ?>"
                        data-total-as-built-cost-per-major-work-category="<?php echo e($p['total_as_built_cost_per_major_work_category']); ?>"
                        data-location="<?php echo e($p['location']); ?>"
                        data-status="<?php echo e($p['status']); ?>"
                        aria-label="Edit project <?php echo e($p['contract_id']); ?>"
                        class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-300"
                      >
                        <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                        Edit
                      </button>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Toast -->
        <div id="toast"
             class="fixed bottom-5 right-5 hidden items-center gap-2 rounded-2xl border border-slate-200 bg-white/95 px-4 py-3 shadow-lg">
          <i class="fa-solid fa-check text-emerald-600"></i>
          <span class="text-sm font-extrabold text-slate-800" id="toastText">Copied</span>
        </div>
      </div>
    </main>
  </div>

<script>
  // Responsive project navigation
  (function () {
    const sidebar = document.getElementById('projectSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    const openButton = document.getElementById('openSidebar');
    const closeButton = document.getElementById('closeSidebar');
    if (!sidebar || !backdrop || !openButton) return;

    function setSidebarOpen(isOpen) {
      sidebar.classList.toggle('-translate-x-full', !isOpen);
      sidebar.classList.toggle('translate-x-0', isOpen);
      backdrop.classList.toggle('hidden', !isOpen);
      openButton.setAttribute('aria-expanded', String(isOpen));
      document.body.classList.toggle('overflow-hidden', isOpen);
    }

    openButton.addEventListener('click', () => setSidebarOpen(true));
    closeButton?.addEventListener('click', () => setSidebarOpen(false));
    backdrop.addEventListener('click', () => setSidebarOpen(false));
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => setSidebarOpen(false)));
    document.addEventListener('keydown', event => {
      if (event.key === 'Escape') setSidebarOpen(false);
    });
  })();

  // Auto submit filters with debounce for search
  (function () {
    const form = document.getElementById('filterForm');
    const q = document.getElementById('qInput');
    const status = document.getElementById('statusSelect');

    let t = null;
    function submitSoon() {
      clearTimeout(t);
      t = setTimeout(() => form.submit(), 350);
    }
    q.addEventListener('input', submitSoon);
    status.addEventListener('change', () => form.submit());
  })();

  // Project entry dialog
  (function () {
    const dialog = document.getElementById('projectDialog');
    if (!dialog) return;

    const openButton = document.getElementById('openProjectDialog');
    const closeButton = document.getElementById('closeProjectDialog');
    const form = document.getElementById('projectForm');
    const actionField = document.getElementById('projectAction');
    const projectIdField = document.getElementById('projectId');
    const title = document.getElementById('projectDialogTitle');
    const submitLabel = document.getElementById('projectSubmitLabel');
    const fields = {
      department: document.getElementById('department'),
      contract_id: document.getElementById('contract_id'),
      contract_description: document.getElementById('contract_description'),
      owner: document.getElementById('owner'),
      participation_percentage: document.getElementById('participation_percentage'),
      contract_date_started: document.getElementById('contract_date_started'),
      contract_date_completed: document.getElementById('contract_date_completed'),
      major_categories_of_work: document.getElementById('major_categories_of_work'),
      dimension_km: document.getElementById('dimension_km'),
      total_as_built_cost_per_major_work_category: document.getElementById('total_as_built_cost_per_major_work_category'),
      location: document.getElementById('location'),
      status: document.getElementById('project_status'),
    };
    const startDate = dialog.querySelector('#contract_date_started');
    const completedDate = dialog.querySelector('#contract_date_completed');
    const validateDateRange = () => {
      completedDate.min = startDate.value;
      completedDate.setCustomValidity(
        completedDate.value && startDate.value && completedDate.value < startDate.value
          ? 'Completion date cannot be before the start date.'
          : ''
      );
    };
    openButton?.addEventListener('click', () => {
      form.reset();
      Object.entries(fields).forEach(([name, field]) => {
        field.value = name === 'status' ? 'Planning' : '';
      });
      actionField.value = 'add_project';
      projectIdField.value = '';
      title.textContent = 'Add Project';
      submitLabel.textContent = 'Save Project';
      validateDateRange();
      dialog.showModal();
    });
    closeButton?.addEventListener('click', () => dialog.close());
    document.querySelectorAll('[data-edit-project]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const data = button.dataset;
        projectIdField.value = data.projectId;
        fields.department.value = data.department;
        fields.contract_id.value = data.contractId;
        fields.contract_description.value = data.contractDescription;
        fields.owner.value = data.owner;
        fields.participation_percentage.value = data.participationPercentage;
        fields.contract_date_started.value = data.contractDateStarted;
        fields.contract_date_completed.value = data.contractDateCompleted;
        fields.major_categories_of_work.value = data.majorCategoriesOfWork;
        fields.dimension_km.value = data.dimensionKm;
        fields.total_as_built_cost_per_major_work_category.value = data.totalAsBuiltCostPerMajorWorkCategory;
        fields.location.value = data.location;
        fields.status.value = data.status;
        actionField.value = 'edit_project';
        title.textContent = 'Edit Project';
        submitLabel.textContent = 'Save Changes';
        validateDateRange();
        dialog.showModal();
      });
    });
    startDate.addEventListener('change', validateDateRange);
    completedDate.addEventListener('change', validateDateRange);
    validateDateRange();
    dialog.querySelectorAll('[data-close-project-dialog]').forEach(button => {
      button.addEventListener('click', () => dialog.close());
    });
    <?php if ($errors): ?>
      dialog.showModal();
      dialog.querySelector('#department')?.focus();
    <?php endif; ?>
  })();

  // Click-to-copy Contract ID + toast
  (function () {
    const rows = document.querySelectorAll('.project-row');
    const toast = document.getElementById('toast');
    const toastText = document.getElementById('toastText');
    let toastTimer = null;

    function showToast(msg) {
      toastText.textContent = msg;
      toast.classList.remove('hidden');
      toast.classList.add('flex');
      clearTimeout(toastTimer);
      toastTimer = setTimeout(() => {
        toast.classList.add('hidden');
        toast.classList.remove('flex');
      }, 1600);
    }

    rows.forEach(r => {
      r.addEventListener('click', async event => {
        if (event.target.closest('button')) return;
        const val = r.getAttribute('data-copy') || '';
        if (!val) return;

        try {
          await navigator.clipboard.writeText(val);
          showToast('Contract ID copied: ' + val);
        } catch (e) {
          const ta = document.createElement('textarea');
          ta.value = val;
          document.body.appendChild(ta);
          ta.select();
          document.execCommand('copy');
          ta.remove();
          showToast('Copied: ' + val);
        }
      });
    });
  })();
</script>

</body>
</html>
