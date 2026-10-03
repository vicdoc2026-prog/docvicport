<?php
require_once __DIR__ . '/../config/check-session.php';
require_once __DIR__ . '/../config/conn.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$role = $_SESSION['role'] ?? '';
$canManageRecords = in_array($role, ['admin', 'belvic_admin'], true);
$canViewRecords = $canManageRecords || $role === 'president';
if (!$canViewRecords) {
  http_response_code(403);
  exit('You do not have permission to view financial records.');
}

$recordFields = [
  'current_assets' => 'Current Assets',
  'total_assets' => 'Total Assets',
  'current_liabilities' => 'Current Liabilities',
  'total_liabilities' => 'Total Liabilities',
  'total_liabilities_and_owners_equity' => "Total Liabilities and Owner's Equity",
  'present_net_worth' => 'Present Net Worth',
  'gross_annual_turnover_construction' => 'Gross Annual Turnover (Construction)',
];
$errors = [];
$formValues = array_fill_keys(array_merge(['record_year'], array_keys($recordFields)), '');
$editingRecordId = '';
$recordsPerPage = 10;
$requestedPage = $_GET['page'] ?? 1;
$page = max(1, is_scalar($requestedPage) ? (int)$requestedPage : 1);

if (!isset($_SESSION['pm_financial_csrf_token'])) {
  $_SESSION['pm_financial_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['save_record', 'edit_record'], true)) {
  if (!$canManageRecords) {
    http_response_code(403);
    exit('You do not have permission to manage financial records.');
  }

  $action = $_POST['action'];
  $submittedId = $_POST['record_id'] ?? '';
  $editingRecordId = is_scalar($submittedId) ? trim((string)$submittedId) : '';
  if ($action === 'edit_record' && (!ctype_digit($editingRecordId) || (int)$editingRecordId < 1)) {
    $errors[] = 'The financial record to edit is invalid.';
  }

  foreach ($formValues as $field => $default) {
    $submittedValue = $_POST[$field] ?? $default;
    $formValues[$field] = is_scalar($submittedValue) ? trim((string)$submittedValue) : '';
  }

  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($submittedToken) || !hash_equals($_SESSION['pm_financial_csrf_token'], $submittedToken)) {
    $errors[] = 'Your session token is invalid. Refresh the page and try again.';
  }

  if (!preg_match('/^\d{4}$/D', $formValues['record_year']) || (int)$formValues['record_year'] < 1900 || (int)$formValues['record_year'] > 9999) {
    $errors[] = 'Record Year must be a four-digit year.';
  }

  foreach ($recordFields as $field => $label) {
    $amount = $formValues[$field];
    if ($amount !== '' && !preg_match('/^\d{1,16}(?:\.\d{1,2})?$/D', $amount)) {
      $errors[] = $label . ' must be a non-negative amount with up to two decimal places.';
    }
  }

  if (!$errors) {
    try {
      $recordYear = (int)$formValues['record_year'];
      $currentAssets = $formValues['current_assets'] !== '' ? $formValues['current_assets'] : null;
      $totalAssets = $formValues['total_assets'] !== '' ? $formValues['total_assets'] : null;
      $currentLiabilities = $formValues['current_liabilities'] !== '' ? $formValues['current_liabilities'] : null;
      $totalLiabilities = $formValues['total_liabilities'] !== '' ? $formValues['total_liabilities'] : null;
      $liabilitiesAndEquity = $formValues['total_liabilities_and_owners_equity'] !== '' ? $formValues['total_liabilities_and_owners_equity'] : null;
      $presentNetWorth = $formValues['present_net_worth'] !== '' ? $formValues['present_net_worth'] : null;
      $grossAnnualTurnover = $formValues['gross_annual_turnover_construction'] !== '' ? $formValues['gross_annual_turnover_construction'] : null;
      if ($action === 'save_record') {
        $stmt = $conn->prepare('INSERT INTO pm_financial_records (record_year, current_assets, total_assets, current_liabilities, total_liabilities, total_liabilities_and_owners_equity, present_net_worth, gross_annual_turnover_construction) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('isssssss', $recordYear, $currentAssets, $totalAssets, $currentLiabilities, $totalLiabilities, $liabilitiesAndEquity, $presentNetWorth, $grossAnnualTurnover);
      } else {
        $recordId = (int)$editingRecordId;
        $stmt = $conn->prepare('UPDATE pm_financial_records SET record_year = ?, current_assets = ?, total_assets = ?, current_liabilities = ?, total_liabilities = ?, total_liabilities_and_owners_equity = ?, present_net_worth = ?, gross_annual_turnover_construction = ? WHERE id = ?');
        $stmt->bind_param('isssssssi', $recordYear, $currentAssets, $totalAssets, $currentLiabilities, $totalLiabilities, $liabilitiesAndEquity, $presentNetWorth, $grossAnnualTurnover, $recordId);
      }
      $stmt->execute();
      $stmt->close();
      $positionStmt = $conn->prepare('SELECT COUNT(*) AS records_before FROM pm_financial_records WHERE record_year > ?');
      $positionStmt->bind_param('i', $recordYear);
      $positionStmt->execute();
      $recordsBefore = (int)$positionStmt->get_result()->fetch_assoc()['records_before'];
      $positionStmt->close();
      $savedPage = (int)floor($recordsBefore / $recordsPerPage) + 1;
      header('Location: financial_records.php?' . ($action === 'save_record' ? 'saved=1' : 'updated=1') . '&page=' . $savedPage . '#records-heading');
      exit;
    } catch (mysqli_sql_exception $exception) {
      if ((int)$exception->getCode() === 1062) {
        $errors[] = 'A financial record already exists for that year.';
      } else {
        error_log('Financial record save failed: ' . $exception->getMessage());
        $errors[] = 'The financial record could not be saved. Please try again.';
      }
    }
  }
}

try {
  $countResult = $conn->query('SELECT COUNT(*) AS total FROM pm_financial_records');
  $totalRecords = (int)$countResult->fetch_assoc()['total'];
  $countResult->free();
  $totalPages = max(1, (int)ceil($totalRecords / $recordsPerPage));
  $page = min($page, $totalPages);
  $offset = ($page - 1) * $recordsPerPage;
  $stmt = $conn->prepare('SELECT id, record_year, current_assets, total_assets, current_liabilities, total_liabilities, total_liabilities_and_owners_equity, present_net_worth, gross_annual_turnover_construction FROM pm_financial_records ORDER BY record_year DESC LIMIT ? OFFSET ?');
  $stmt->bind_param('ii', $recordsPerPage, $offset);
  $stmt->execute();
  $result = $stmt->get_result();
  $records = $result->fetch_all(MYSQLI_ASSOC);
  $result->free();
  $stmt->close();
  $firstRecord = $totalRecords > 0 ? $offset + 1 : 0;
  $lastRecord = $offset + count($records);
} catch (mysqli_sql_exception $exception) {
  error_log('Financial records query failed: ' . $exception->getMessage());
  $records = [];
  $totalRecords = 0;
  $totalPages = 1;
  $page = 1;
  $firstRecord = 0;
  $lastRecord = 0;
  $loadError = 'Financial records are unavailable. Apply the financial records migration and reload this page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Annual Financial Records</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body { font-family: Inter, system-ui, sans-serif; }
    .bg-grid { background-image: radial-gradient(circle at 1px 1px, rgba(15, 23, 42, .06) 1px, transparent 0); background-size: 22px 22px; }
    .card { border: 1px solid rgba(15, 23, 42, .08); background: rgba(255, 255, 255, .92); box-shadow: 0 12px 30px rgba(2, 6, 23, .06); }
    .nice-scroll::-webkit-scrollbar { height: 10px; }
    .nice-scroll::-webkit-scrollbar-thumb { background: rgba(15, 23, 42, .15); border-radius: 999px; }
    .nice-scroll::-webkit-scrollbar-track { background: rgba(15, 23, 42, .05); }
    .sidebar-link { transition: background-color .16s ease, color .16s ease, transform .16s ease; }
    .sidebar-link:hover { transform: translateX(2px); }
    dialog::backdrop { background: rgba(15, 23, 42, .48); backdrop-filter: blur(2px); }
    dialog[open] { animation: dialog-in .16s ease-out; }
    @keyframes dialog-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    th, td { vertical-align: top; }
  </style>
</head>
<body class="min-h-screen bg-slate-50 bg-grid text-slate-900">
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
      <a href="index.php" class="sidebar-link flex items-center gap-3 rounded-xl px-3 py-3 font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-900">
        <i class="fa-solid fa-table-columns w-5 text-center" aria-hidden="true"></i>
        <span>Project Dashboard</span>
      </a>
      <a href="financial_records.php" aria-current="page" class="sidebar-link flex items-center gap-3 rounded-xl bg-slate-900 px-3 py-3 font-bold text-white shadow-sm">
        <i class="fa-solid fa-file-invoice-dollar w-5 text-center" aria-hidden="true"></i>
        <span>Financial Records</span>
      </a>
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
          <p class="font-extrabold text-slate-900">Financial Records</p>
        </div>
      </div>
      <?php if ($canManageRecords): ?>
        <button type="button" id="openRecordDialog" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2.5 font-bold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
          <i class="fa-solid fa-plus" aria-hidden="true"></i> Add Financial Record
        </button>
      <?php endif; ?>
    </div>

    <header class="mb-6 border-b border-slate-200 pb-5">
      <p class="text-sm font-bold text-emerald-700">Financial Aspect</p>
      <h1 class="mt-1 text-2xl font-extrabold">Annual Financial Records</h1>
      <p class="mt-1 text-sm font-medium text-slate-600">Annual assets, liabilities, net worth, and construction turnover.</p>
    </header>

    <?php if (!empty($_GET['saved'])): ?>
      <div role="status" class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-800">Financial record saved.</div>
    <?php elseif (!empty($_GET['updated'])): ?>
      <div role="status" class="mb-4 rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-800">Financial record updated.</div>
    <?php endif; ?>
    <?php if (isset($loadError)): ?>
      <div role="alert" class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 font-semibold text-amber-900"><?php echo e($loadError); ?></div>
    <?php endif; ?>

    <section aria-labelledby="records-heading" class="card mb-6 rounded-2xl p-5 md:p-6">
        
      <div class="mb-4 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 id="records-heading" class="text-lg font-black text-slate-900">Financial Records</h2>
          <p class="font-semibold text-slate-600">Annual financial position and construction turnover.</p>
        </div>
        <span class="text-sm font-semibold text-slate-500">Showing <?php echo number_format($firstRecord); ?>–<?php echo number_format($lastRecord); ?> of <?php echo number_format($totalRecords); ?> records</span>
      </div>
      <div class="overflow-x-auto nice-scroll">
        <table class="min-w-[1650px] text-sm">
          <thead>
            <tr class="text-slate-800">
              <th class="w-[120px] px-3 py-3 text-left font-black">Record Year</th>
              <?php foreach ($recordFields as $label): ?>
                <th class="w-[205px] px-3 py-3 text-right font-black"><?php echo e($label); ?></th>
              <?php endforeach; ?>
              <?php if ($canManageRecords): ?><th class="w-[110px] px-3 py-3 text-center font-black">Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (!$records): ?>
              <tr><td colspan="<?php echo $canManageRecords ? '9' : '8'; ?>" class="border-t border-slate-100 px-4 py-8 text-center font-semibold text-slate-600">No financial records yet.</td></tr>
            <?php endif; ?>
            <?php foreach ($records as $record): ?>
              <tr class="border-t border-slate-100 transition hover:bg-slate-50/70">
                <td class="px-3 py-3 font-extrabold text-slate-900"><?php echo e($record['record_year']); ?></td>
                <?php foreach ($recordFields as $field => $label): ?>
                  <td class="px-3 py-3 text-right font-semibold text-slate-700 tabular-nums"><?php echo $record[$field] === null ? 'Not provided' : number_format((float)$record[$field], 2); ?></td>
                <?php endforeach; ?>
                <?php if ($canManageRecords): ?><td class="px-3 py-3 text-center">
                  <button type="button" data-edit-record aria-label="Edit financial record <?php echo e($record['record_year']); ?>"
                    data-record-id="<?php echo e($record['id']); ?>" data-record-year="<?php echo e($record['record_year']); ?>"
                    <?php foreach ($recordFields as $field => $label): ?>data-<?php echo e(str_replace('_', '-', $field)); ?>="<?php echo e($record[$field]); ?>" <?php endforeach; ?>
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 font-bold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-300" title="Edit record">
                    <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                    Edit
                  </button>
                </td><?php endif; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php if ($totalPages > 1): ?>
        <?php $visiblePages = range(max(1, $page - 2), min($totalPages, $page + 2)); ?>
        <nav aria-label="Financial records pages" class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
          <span class="text-sm font-semibold text-slate-600">Page <?php echo number_format($page); ?> of <?php echo number_format($totalPages); ?></span>
          <div class="flex flex-wrap items-center gap-1">
            <?php if ($page > 1): ?>
              <a href="?page=<?php echo $page - 1; ?>#records-heading" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Previous</a>
            <?php else: ?>
              <span aria-disabled="true" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-400">Previous</span>
            <?php endif; ?>
            <?php if ($visiblePages[0] > 1): ?>
              <a href="?page=1#records-heading" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">1</a>
              <?php if ($visiblePages[0] > 2): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
            <?php endif; ?>
            <?php foreach ($visiblePages as $visiblePage): ?>
              <?php if ($visiblePage === $page): ?>
                <span aria-current="page" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white"><?php echo $visiblePage; ?></span>
              <?php else: ?>
                <a href="?page=<?php echo $visiblePage; ?>#records-heading" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"><?php echo $visiblePage; ?></a>
              <?php endif; ?>
            <?php endforeach; ?>
            <?php if (end($visiblePages) < $totalPages): ?>
              <?php if (end($visiblePages) < $totalPages - 1): ?><span class="px-1 text-slate-400">…</span><?php endif; ?>
              <a href="?page=<?php echo $totalPages; ?>#records-heading" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50"><?php echo $totalPages; ?></a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
              <a href="?page=<?php echo $page + 1; ?>#records-heading" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold text-slate-700 hover:bg-slate-50">Next</a>
            <?php else: ?>
              <span aria-disabled="true" class="rounded-lg border border-slate-200 px-3 py-2 text-sm font-bold text-slate-400">Next</span>
            <?php endif; ?>
          </div>
        </nav>
      <?php endif; ?>
    </section>
  </main>
  </div>

  <script>
    (() => {
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
  </script>

  <?php if ($canManageRecords): ?>
  <dialog id="recordDialog" aria-labelledby="recordDialogTitle" class="m-auto max-h-[calc(100dvh-2rem)] w-[min(94vw,820px)] max-w-none overflow-hidden rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl">
    <form id="recordForm" method="POST" class="flex max-h-[calc(100dvh-2rem)] flex-col">
      <input type="hidden" name="action" id="recordAction" value="save_record">
      <input type="hidden" name="record_id" id="recordId" value="<?php echo e($editingRecordId); ?>">
      <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION['pm_financial_csrf_token']); ?>">
      <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:px-7">
        <div class="flex min-w-0 items-start gap-3">
          <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-800"><i class="fa-solid fa-file-invoice-dollar" aria-hidden="true"></i></span>
          <div class="min-w-0">
            <h2 id="recordDialogTitle" class="text-lg font-extrabold sm:text-xl">Add Financial Record</h2>
            <p class="mt-1 text-sm font-medium text-slate-600">Enter the figures available for one record year.</p>
            <p class="mt-2 text-xs font-semibold text-slate-500"><span id="amountProgress">0 of 7 amounts entered</span><span aria-hidden="true"> · </span>Amounts are optional</p>
          </div>
        </div>
        <button type="button" id="closeRecordDialog" aria-label="Close form" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-200 focus:outline-none focus:ring-2 focus:ring-sky-300"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
      </div>
      <div class="min-h-0 flex-1 overflow-y-auto px-5 py-5 sm:px-7 sm:py-6">
        <?php if ($errors): ?>
        <div role="alert" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
          <ul class="list-disc space-y-1 pl-5"><?php foreach ($errors as $error): ?><li><?php echo e($error); ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <section class="mb-6 rounded-lg border border-slate-200 bg-white p-4 sm:p-5">
          <label for="record_year" class="block text-sm font-extrabold text-slate-800">Record Year <span class="text-red-600">*</span></label>
          <p class="mt-1 text-xs font-medium text-slate-500">One financial record is allowed per year.</p>
          <input id="record_year" name="record_year" type="number" min="1900" max="9999" step="1" inputmode="numeric" required value="<?php echo e($formValues['record_year']); ?>" placeholder="e.g. 2026" class="mt-3 w-full rounded-lg border border-slate-300 px-3 py-3 text-lg font-bold tabular-nums placeholder:font-medium placeholder:text-slate-400 focus:border-sky-600 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:max-w-xs">
        </section>

        <?php
          $amountGroups = [
            'Assets' => ['current_assets', 'total_assets'],
            'Liabilities & Equity' => ['current_liabilities', 'total_liabilities', 'total_liabilities_and_owners_equity'],
            'Net Worth & Turnover' => ['present_net_worth', 'gross_annual_turnover_construction'],
          ];
        ?>
        <?php foreach ($amountGroups as $groupLabel => $groupFields): ?>
          <section class="mb-6 last:mb-0">
            <div class="mb-3 flex items-center gap-2 border-b border-slate-200 pb-2">
              <span class="h-5 w-1 rounded-full bg-sky-700" aria-hidden="true"></span>
              <h3 class="text-sm font-extrabold text-slate-800"><?php echo e($groupLabel); ?></h3>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
              <?php foreach ($groupFields as $field): ?>
                <label for="<?php echo e($field); ?>" class="block min-w-0 text-sm font-bold text-slate-700 <?php echo $field === 'total_liabilities_and_owners_equity' ? 'sm:col-span-2' : ''; ?>">
                  <?php echo e($recordFields[$field]); ?>
                  <span class="mt-1.5 flex overflow-hidden rounded-lg border border-slate-300 bg-white focus-within:border-sky-600 focus-within:ring-2 focus-within:ring-sky-100">
                    <span class="flex items-center border-r border-slate-200 bg-slate-50 px-3 text-xs font-extrabold text-slate-500">PHP</span>
                    <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" type="number" min="0" max="9999999999999999.99" step="0.01" inputmode="decimal" data-amount-field value="<?php echo e($formValues[$field]); ?>" placeholder="0.00" aria-label="<?php echo e($recordFields[$field]); ?> amount in Philippine pesos" class="w-full min-w-0 border-0 px-3 py-3 font-semibold tabular-nums placeholder:font-medium placeholder:text-slate-400 focus:outline-none focus:ring-0">
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endforeach; ?>
      </div>
      <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-end sm:px-7">
        <button type="button" data-close-record-dialog class="rounded-lg border border-slate-300 px-4 py-2.5 font-bold text-slate-700 hover:bg-slate-50">Cancel</button>
        <button type="submit" id="recordSubmitLabel" class="inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-sky-300">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><span>Save Record</span>
        </button>
      </div>
    </form>
  </dialog>

  <script>
    (() => {
      const dialog = document.getElementById('recordDialog');
      const form = document.getElementById('recordForm');
      const action = document.getElementById('recordAction');
      const recordId = document.getElementById('recordId');
      const title = document.getElementById('recordDialogTitle');
      const submit = document.getElementById('recordSubmitLabel');
      const amountProgress = document.getElementById('amountProgress');
      const amountFields = [...dialog.querySelectorAll('[data-amount-field]')];
      const fieldNames = <?php echo json_encode(array_merge(['record_year'], array_keys($recordFields)), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
      const fields = Object.fromEntries(fieldNames.map(name => [name, document.getElementById(name)]));
      const updateAmountProgress = () => {
        const entered = amountFields.filter(field => field.value.trim() !== '').length;
        amountProgress.textContent = `${entered} of ${amountFields.length} amounts entered`;
      };
      amountFields.forEach(field => field.addEventListener('input', updateAmountProgress));

      document.getElementById('openRecordDialog').addEventListener('click', () => {
        form.reset();
        action.value = 'save_record';
        recordId.value = '';
        title.textContent = 'Add Financial Record';
        submit.querySelector('span').textContent = 'Save Record';
        updateAmountProgress();
        dialog.showModal();
      });
      document.getElementById('closeRecordDialog').addEventListener('click', () => dialog.close());
      dialog.querySelectorAll('[data-close-record-dialog]').forEach(button => button.addEventListener('click', () => dialog.close()));
      document.querySelectorAll('[data-edit-record]').forEach(button => button.addEventListener('click', () => {
        fieldNames.forEach(name => {
          const dataName = name.replaceAll('_', '-').replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());
          fields[name].value = button.dataset[dataName] || '';
        });
        recordId.value = button.dataset.recordId;
        action.value = 'edit_record';
        title.textContent = 'Edit Financial Record';
        submit.querySelector('span').textContent = 'Save Changes';
        updateAmountProgress();
        dialog.showModal();
      }));
      <?php if ($errors): ?>
        action.value = '<?php echo $editingRecordId !== '' ? 'edit_record' : 'save_record'; ?>';
        title.textContent = '<?php echo $editingRecordId !== '' ? 'Edit Financial Record' : 'Add Financial Record'; ?>';
        updateAmountProgress();
        dialog.showModal();
      <?php endif; ?>
    })();
  </script>
  <?php endif; ?>
</body>
</html>