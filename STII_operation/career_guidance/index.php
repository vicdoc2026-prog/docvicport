<?php
require_once '../config/check-session.php';
require_once '../config/conn.php';

// -----------------------------
// Helpers
// -----------------------------
function e($str) {
  return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function redirect_with_kept_query($extra = []) {
  $keep = [];
  foreach (['q','sy','page'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $keep[$k] = $_GET[$k];
  }
  foreach ($extra as $k => $v) {
    if ($v === null || $v === '') continue;
    $keep[$k] = $v;
  }
  $url = "index.php";
  if (!empty($keep)) $url .= '?' . http_build_query($keep);
  header("Location: $url");
  exit;
}

// mysqli bind_param needs references
function stmt_bind_params(mysqli_stmt $stmt, string $types, array $params) {
  $refs = [];
  $refs[] = $types;
  foreach ($params as $k => $v) $refs[] = &$params[$k];
  call_user_func_array([$stmt, 'bind_param'], $refs);
}

function normalize_school($s) {
  // Basic cleanup to reduce duplicates like extra spaces
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', ' ', $s);
  return $s;
}

// ✅ School year dropdown options
$schoolYearOptions = [
  "2023-2024",
  "2024-2025",
  "2025-2026",
  "2026-2027"
];

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$errors = [];
$success = "";

// Flash message (PRG)
if (!empty($_SESSION['flash_success'])) {
  $success = (string)$_SESSION['flash_success'];
  unset($_SESSION['flash_success']);
}

// -----------------------------
// Load schools list from DB (unique, sorted)
// -----------------------------
$schools = [];
$schoolsRes = $conn->query("
  SELECT DISTINCT TRIM(source_school) AS school
  FROM career_forecasting_enrollees
  WHERE source_school IS NOT NULL AND TRIM(source_school) <> ''
  ORDER BY school ASC
");
if ($schoolsRes) {
  while ($r = $schoolsRes->fetch_assoc()) $schools[] = $r['school'];
  $schoolsRes->close();
}

// -----------------------------
// SEARCH / FILTER (GET)
// -----------------------------
$q  = trim($_GET['q']  ?? '');
$sy = trim($_GET['sy'] ?? '');

// -----------------------------
// CREATE / UPDATE / DELETE (POST) ✅ PRG
// -----------------------------
$reopenModal = false;
$reopenMode  = 'create';
$old = [
  'id' => '',
  'school_year' => '',
  'number_of_students' => '',
  'source_school' => '',         // chosen from dropdown
  'new_source_school' => '',     // typed manually if not in list
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  // DELETE via POST
  if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      $errors[] = "Invalid delete ID.";
    } else {
      $stmt = $conn->prepare("DELETE FROM career_forecasting_enrollees WHERE id = ?");
      $stmt->bind_param("i", $id);
      if ($stmt->execute()) {
        $_SESSION['flash_success'] = "Record deleted successfully.";
        $stmt->close();
        redirect_with_kept_query();
      } else {
        $errors[] = "Delete failed: " . $stmt->error;
        $stmt->close();
      }
    }
  }

  // CREATE / UPDATE
  if ($action === 'create' || $action === 'update') {
    $school_year = trim($_POST['school_year'] ?? '');
    $number_of_students = trim($_POST['number_of_students'] ?? '');

    // NEW: dropdown + optional "add new school" input
    $source_school_choice = trim($_POST['source_school'] ?? '');       // could be "" or "__NEW__" or real school
    $new_source_school    = trim($_POST['new_source_school'] ?? '');   // user typed school

    // resolve final source_school
    if ($source_school_choice === '__NEW__') {
      $source_school = normalize_school($new_source_school);
    } else {
      $source_school = normalize_school($source_school_choice);
    }

    $id = (int)($_POST['id'] ?? 0);

    // keep old values if validation fails
    $old['id'] = $id;
    $old['school_year'] = $school_year;
    $old['number_of_students'] = $number_of_students;
    $old['source_school'] = $source_school_choice;
    $old['new_source_school'] = $new_source_school;

    if ($school_year === '') $errors[] = "School Year is required.";

    if ($source_school_choice === '') {
      $errors[] = "Source School is required.";
    } else {
      if ($source_school_choice === '__NEW__' && $source_school === '') {
        $errors[] = "Please type the new school name.";
      }
      if (mb_strlen($source_school) > 255) {
        $errors[] = "School name is too long (max 255 characters).";
      }
    }

    if ($number_of_students === '' || !ctype_digit($number_of_students) || (int)$number_of_students < 0) {
      $errors[] = "Number of Students must be a valid non-negative whole number.";
    }

    // If they typed a school that already exists, auto-use the existing one (avoid duplicates)
    if (empty($errors) && $source_school_choice === '__NEW__' && $source_school !== '') {
      $stmt = $conn->prepare("
        SELECT source_school
        FROM career_forecasting_enrollees
        WHERE LOWER(TRIM(source_school)) = LOWER(TRIM(?))
        LIMIT 1
      ");
      $stmt->bind_param("s", $source_school);
      $stmt->execute();
      $res = $stmt->get_result();
      if ($row = $res->fetch_assoc()) {
        $source_school = $row['source_school']; // use canonical existing value
      }
      $stmt->close();
    }

    if (empty($errors)) {
      $num = (int)$number_of_students;

      if ($action === 'create') {
        $stmt = $conn->prepare("
          INSERT INTO career_forecasting_enrollees (school_year, number_of_students, source_school)
          VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sis", $school_year, $num, $source_school);

        if ($stmt->execute()) {
          $_SESSION['flash_success'] = "Record added successfully.";
          $stmt->close();
          redirect_with_kept_query();
        } else {
          $errors[] = "Insert failed: " . $stmt->error;
          $stmt->close();
          $reopenModal = true;
          $reopenMode = 'create';
        }
      }

      if ($action === 'update') {
        if ($id <= 0) {
          $errors[] = "Invalid record ID.";
          $reopenModal = true;
          $reopenMode = 'update';
        } else {
          $stmt = $conn->prepare("
            UPDATE career_forecasting_enrollees
            SET school_year = ?, number_of_students = ?, source_school = ?
            WHERE id = ?
          ");
          $stmt->bind_param("sisi", $school_year, $num, $source_school, $id);

          if ($stmt->execute()) {
            $_SESSION['flash_success'] = "Record updated successfully.";
            $stmt->close();
            redirect_with_kept_query();
          } else {
            $errors[] = "Update failed: " . $stmt->error;
            $stmt->close();
            $reopenModal = true;
            $reopenMode = 'update';
          }
        }
      }
    } else {
      $reopenModal = true;
      $reopenMode = ($action === 'update') ? 'update' : 'create';
    }
  }
}

// -----------------------------
// PAGINATION SETUP
// -----------------------------
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;

$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
$types = "";

// Base list query
$baseSql = "FROM career_forecasting_enrollees";

if ($q !== '') {
  $where[] = "(source_school LIKE ?)";
  $params[] = "%" . $q . "%";
  $types .= "s";
}

if ($sy !== '') {
  $where[] = "(school_year = ?)";
  $params[] = $sy;
  $types .= "s";
}

$whereSql = "";
if (!empty($where)) $whereSql = " WHERE " . implode(" AND ", $where);

// Total rows
$countSql = "SELECT COUNT(*) as total " . $baseSql . $whereSql;
$stmt = $conn->prepare($countSql);
if (!empty($params)) stmt_bind_params($stmt, $types, $params);
$stmt->execute();
$totalRows = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

// List query
$listSql = "SELECT id, school_year, number_of_students, source_school, created_at "
  . $baseSql . $whereSql
  . " ORDER BY school_year DESC, id DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($listSql);

$bindParams = $params;
$bindTypes  = $types . "ii";
$bindParams[] = $perPage;
$bindParams[] = $offset;

stmt_bind_params($stmt, $bindTypes, $bindParams);

$stmt->execute();
$list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Distinct school years for filter dropdown
$syRes = $conn->query("SELECT DISTINCT school_year FROM career_forecasting_enrollees ORDER BY school_year DESC");
$schoolYears = [];
while ($row = $syRes->fetch_assoc()) $schoolYears[] = $row['school_year'];
$syRes->close();

// Summary
$summarySql = "SELECT COUNT(*) as total_records, COALESCE(SUM(number_of_students),0) as total_students FROM career_forecasting_enrollees";
$summary = $conn->query($summarySql)->fetch_assoc();

// Keep querystring on pagination links
function buildPageLink($newPage) {
  $keep = [];
  foreach (['q','sy'] as $k) {
    if (isset($_GET[$k]) && $_GET[$k] !== '') $keep[$k] = $_GET[$k];
  }
  $keep['page'] = $newPage;
  return "index.php?" . http_build_query($keep);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Career Guidance | Forecasting Report Enrollees</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gray-50 min-h-screen">
  <div class="flex min-h-screen">
    <?php include 'includes/sidebar.php'; ?>

    <main class="flex-1">
      <div class="bg-white border-b border-gray-200">
        <div class="px-4 md:px-8 py-4 flex items-center justify-between">
          <div>
            <div class="text-base font-bold text-gray-900">Forecasting Report (Enrollees)</div>
            <div class="text-xs text-gray-500">Career Guidance Module</div>
          </div>

          <button
            id="openAddModalBtn"
            class="inline-flex items-center gap-2 rounded-lg bg-gradient-to-r from-purple-600 to-pink-500 text-white text-sm font-semibold px-4 py-2 hover:opacity-95"
            type="button"
          >
            <i class="fas fa-plus"></i>
            Add Record
          </button>
        </div>
      </div>

      <div class="px-4 md:px-8 py-6">

        <?php if (!empty($errors)): ?>
          <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4">
            <div class="font-semibold text-red-700 mb-2">Please fix the following:</div>
            <ul class="list-disc pl-5 text-sm text-red-700">
              <?php foreach ($errors as $err): ?>
                <li><?php echo e($err); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if ($success !== ''): ?>
          <div class="mb-5 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-700">
            <?php echo e($success); ?>
          </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
          <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Records</div>
            <div class="text-2xl font-bold text-gray-900"><?php echo (int)($summary['total_records'] ?? 0); ?></div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Total Students (All Years)</div>
            <div class="text-2xl font-bold text-gray-900"><?php echo number_format((int)($summary['total_students'] ?? 0)); ?></div>
          </div>
          <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="text-xs text-gray-500">Showing</div>
            <div class="text-sm text-gray-700">
              Page <span class="font-semibold"><?php echo $page; ?></span> of <span class="font-semibold"><?php echo $totalPages; ?></span>
              (<?php echo number_format($totalRows); ?> result<?php echo $totalRows === 1 ? '' : 's'; ?>)
            </div>
          </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl p-5 overflow-x-auto">
          <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-4">
            <h2 class="text-sm font-bold text-gray-900">Enrollee Records</h2>

            <form method="GET" class="flex flex-col sm:flex-row gap-2 w-full md:w-auto">
              <input
                type="text"
                name="q"
                value="<?php echo e($q); ?>"
                placeholder="Search source school..."
                class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full sm:w-64"
              />

              <select name="sy" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full sm:w-44">
                <option value="">All Years</option>
                <?php foreach ($schoolYears as $year): ?>
                  <option value="<?php echo e($year); ?>" <?php echo ($sy === $year) ? 'selected' : ''; ?>>
                    <?php echo e($year); ?>
                  </option>
                <?php endforeach; ?>
              </select>

              <div class="flex gap-2">
                <button class="rounded-lg bg-gray-900 text-white px-4 py-2 text-sm">Filter</button>
                <a href="index.php" class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                  Reset
                </a>
              </div>
            </form>
          </div>

          <table class="min-w-full text-sm">
            <thead>
              <tr class="text-left text-xs text-gray-500 border-b">
                <th class="py-2 pr-3">School Year</th>
                <th class="py-2 pr-3"># Students</th>
                <th class="py-2 pr-3">Source School</th>
                <th class="py-2 pr-3">Created</th>
                <th class="py-2 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y">
              <?php if (empty($list)): ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-gray-500 text-sm">No records found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($list as $row): ?>
                  <tr class="hover:bg-gray-50">
                    <td class="py-2 pr-3 font-medium text-gray-900"><?php echo e($row['school_year']); ?></td>
                    <td class="py-2 pr-3"><?php echo number_format((int)$row['number_of_students']); ?></td>
                    <td class="py-2 pr-3"><?php echo e($row['source_school']); ?></td>
                    <td class="py-2 pr-3 text-xs text-gray-500"><?php echo e($row['created_at']); ?></td>
                    <td class="py-2 text-right whitespace-nowrap">
                      <button
                        type="button"
                        class="inline-flex items-center text-xs font-semibold text-purple-700 hover:text-purple-900 mr-3 editBtn"
                        data-id="<?php echo (int)$row['id']; ?>"
                        data-school-year="<?php echo e($row['school_year']); ?>"
                        data-number-of-students="<?php echo (int)$row['number_of_students']; ?>"
                        data-source-school="<?php echo e($row['source_school']); ?>"
                      >
                        <i class="fas fa-pen mr-1"></i> Edit
                      </button>

                      <button
                        type="button"
                        class="inline-flex items-center text-xs font-semibold text-red-600 hover:text-red-800 deleteBtn"
                        data-id="<?php echo (int)$row['id']; ?>"
                        data-school-year="<?php echo e($row['school_year']); ?>"
                        data-source-school="<?php echo e($row['source_school']); ?>"
                      >
                        <i class="fas fa-trash mr-1"></i> Delete
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>

          <!-- Pagination -->
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mt-5">
            <div class="text-xs text-gray-500">
              Showing
              <span class="font-semibold"><?php echo ($totalRows === 0) ? 0 : ($offset + 1); ?></span>
              to
              <span class="font-semibold"><?php echo min($offset + $perPage, $totalRows); ?></span>
              of
              <span class="font-semibold"><?php echo number_format($totalRows); ?></span>
              results
            </div>

            <div class="flex items-center gap-2">
              <a
                class="px-3 py-2 rounded-lg border text-sm <?php echo ($page <= 1) ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-700 border-gray-300 hover:bg-gray-50'; ?>"
                href="<?php echo e(buildPageLink(max(1, $page - 1))); ?>"
              >
                Prev
              </a>

              <?php
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                for ($p = $start; $p <= $end; $p++):
              ?>
                <a
                  class="px-3 py-2 rounded-lg border text-sm <?php echo ($p === $page) ? 'bg-gray-900 text-white border-gray-900' : 'text-gray-700 border-gray-300 hover:bg-gray-50'; ?>"
                  href="<?php echo e(buildPageLink($p)); ?>"
                >
                  <?php echo $p; ?>
                </a>
              <?php endfor; ?>

              <a
                class="px-3 py-2 rounded-lg border text-sm <?php echo ($page >= $totalPages) ? 'text-gray-300 border-gray-200 pointer-events-none' : 'text-gray-700 border-gray-300 hover:bg-gray-50'; ?>"
                href="<?php echo e(buildPageLink(min($totalPages, $page + 1))); ?>"
              >
                Next
              </a>
            </div>
          </div>

        </div>
      </div>
    </main>
  </div>

  <!-- =========================
       ADD/EDIT MODAL
       ========================= -->
  <div id="recordModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" id="modalBackdrop"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-lg bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <div class="font-bold text-gray-900 text-sm" id="modalTitle">Add Record</div>
          <button type="button" class="text-gray-500 hover:text-gray-800" id="closeModalBtn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form method="POST" class="p-5 space-y-3" id="recordForm" autocomplete="off">
          <input type="hidden" name="action" id="formAction" value="create">
          <input type="hidden" name="id" id="formId" value="">

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">School Year</label>
            <select
              name="school_year"
              id="formSchoolYear"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
              required
            >
              <option value="">-- Select School Year --</option>
              <?php foreach ($schoolYearOptions as $opt): ?>
                <option value="<?php echo e($opt); ?>"><?php echo e($opt); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Number of Students</label>
            <input
              type="number"
              name="number_of_students"
              id="formNumber"
              min="0"
              value=""
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
              required
            />
          </div>

          <!-- ✅ NEW: source school dropdown from DB + optional add new -->
          <div>
            <label class="block text-xs font-medium text-gray-700 mb-1">Source School</label>
            <select
              name="source_school"
              id="formSourceSelect"
              class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
              required
            >
              <option value="">-- Select School --</option>
              <?php foreach ($schools as $schoolName): ?>
                <option value="<?php echo e($schoolName); ?>"><?php echo e($schoolName); ?></option>
              <?php endforeach; ?>
              <option value="__NEW__">+ Add new school...</option>
            </select>

            <div id="newSchoolWrap" class="mt-2 hidden">
              <label class="block text-xs font-medium text-gray-700 mb-1">New School Name</label>
              <input
                type="text"
                name="new_source_school"
                id="formNewSchool"
                value=""
                placeholder="Type school name..."
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500"
              />
              <p class="mt-1 text-[11px] text-gray-500">
                If the name already exists, it will use the existing one (no duplicates).
              </p>
            </div>
          </div>

          <div class="flex items-center gap-2 pt-2">
            <button type="submit"
              class="flex-1 rounded-lg bg-gradient-to-r from-purple-600 to-pink-500 text-white text-sm font-semibold py-2 hover:opacity-95"
              id="submitBtn"
            >
              Save
            </button>
            <button type="button"
              class="flex-1 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold py-2 hover:bg-gray-50"
              id="cancelModalBtn"
            >
              Cancel
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- =========================
       DELETE CONFIRM MODAL
       ========================= -->
  <div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/40" id="deleteBackdrop"></div>

    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-md bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
        <div class="px-5 py-4 border-b flex items-center justify-between">
          <div class="font-bold text-gray-900 text-sm">Confirm Delete</div>
          <button type="button" class="text-gray-500 hover:text-gray-800" id="closeDeleteBtn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="p-5">
          <p class="text-sm text-gray-700">Are you sure you want to delete this record?</p>
          <div class="mt-3 text-xs text-gray-500" id="deleteHint"></div>

          <form method="POST" class="mt-5 flex gap-2" id="deleteForm">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteId" value="">

            <button
              type="submit"
              class="flex-1 rounded-lg bg-red-600 text-white text-sm font-semibold py-2 hover:bg-red-700"
            >
              Yes, Delete
            </button>
            <button
              type="button"
              class="flex-1 rounded-lg border border-gray-300 text-gray-700 text-sm font-semibold py-2 hover:bg-gray-50"
              id="cancelDeleteBtn"
            >
              Cancel
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // ---------- ADD/EDIT MODAL ----------
    const modal = document.getElementById('recordModal');
    const openBtn = document.getElementById('openAddModalBtn');
    const closeBtn = document.getElementById('closeModalBtn');
    const cancelBtn = document.getElementById('cancelModalBtn');
    const backdrop = document.getElementById('modalBackdrop');

    const modalTitle = document.getElementById('modalTitle');
    const formAction = document.getElementById('formAction');
    const formId = document.getElementById('formId');
    const formSchoolYear = document.getElementById('formSchoolYear');
    const formNumber = document.getElementById('formNumber');

    // NEW school controls
    const formSourceSelect = document.getElementById('formSourceSelect');
    const newSchoolWrap = document.getElementById('newSchoolWrap');
    const formNewSchool = document.getElementById('formNewSchool');

    const submitBtn = document.getElementById('submitBtn');

    function openRecordModal() {
      modal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
    function closeRecordModal() {
      modal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
    }

    function syncNewSchoolVisibility() {
      const isNew = formSourceSelect.value === '__NEW__';
      newSchoolWrap.classList.toggle('hidden', !isNew);
      formNewSchool.required = isNew;
      if (!isNew) formNewSchool.value = '';
    }

    function setCreateMode() {
      modalTitle.textContent = "Add Record";
      formAction.value = "create";
      formId.value = "";
      formSchoolYear.value = "";
      formNumber.value = "";
      formSourceSelect.value = "";
      formNewSchool.value = "";
      syncNewSchoolVisibility();
      submitBtn.textContent = "Save";
    }

    function setEditMode(row) {
      modalTitle.textContent = "Update Record";
      formAction.value = "update";
      formId.value = row.id || "";
      formSchoolYear.value = row.schoolYear || "";
      formNumber.value = row.number || "";

      // Try to match existing option; if not found, use "__NEW__" + fill textbox
      const target = (row.source || "").trim();
      let found = false;
      [...formSourceSelect.options].forEach(opt => {
        if (opt.value === target) found = true;
      });

      if (found) {
        formSourceSelect.value = target;
        formNewSchool.value = "";
      } else {
        formSourceSelect.value = "__NEW__";
        formNewSchool.value = target;
      }

      syncNewSchoolVisibility();
      submitBtn.textContent = "Update";
    }

    openBtn?.addEventListener('click', () => {
      setCreateMode();
      openRecordModal();
    });

    closeBtn?.addEventListener('click', closeRecordModal);
    cancelBtn?.addEventListener('click', closeRecordModal);
    backdrop?.addEventListener('click', closeRecordModal);

    formSourceSelect?.addEventListener('change', syncNewSchoolVisibility);

    // Edit buttons
    document.querySelectorAll('.editBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        setEditMode({
          id: btn.dataset.id,
          schoolYear: btn.dataset.schoolYear,
          number: btn.dataset.numberOfStudents,
          source: btn.dataset.sourceSchool,
        });
        openRecordModal();
      });
    });

    // ---------- DELETE MODAL ----------
    const deleteModal = document.getElementById('deleteModal');
    const deleteBackdrop = document.getElementById('deleteBackdrop');
    const closeDeleteBtn = document.getElementById('closeDeleteBtn');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const deleteId = document.getElementById('deleteId');
    const deleteHint = document.getElementById('deleteHint');

    function openDeleteModal() {
      deleteModal.classList.remove('hidden');
      document.body.classList.add('overflow-hidden');
    }
    function closeDeleteModal() {
      deleteModal.classList.add('hidden');
      document.body.classList.remove('overflow-hidden');
      deleteId.value = "";
      deleteHint.textContent = "";
    }

    document.querySelectorAll('.deleteBtn').forEach(btn => {
      btn.addEventListener('click', () => {
        deleteId.value = btn.dataset.id;
        deleteHint.textContent = `School Year: ${btn.dataset.schoolYear} • Source: ${btn.dataset.sourceSchool}`;
        openDeleteModal();
      });
    });

    closeDeleteBtn?.addEventListener('click', closeDeleteModal);
    cancelDeleteBtn?.addEventListener('click', closeDeleteModal);
    deleteBackdrop?.addEventListener('click', closeDeleteModal);

    // ---------- Re-open modal on validation errors ----------
    const shouldReopen = <?php echo $reopenModal ? 'true' : 'false'; ?>;
    const reopenMode = <?php echo json_encode($reopenMode); ?>;
    const oldValues = <?php echo json_encode($old); ?>;

    if (shouldReopen) {
      if (reopenMode === 'update') {
        setEditMode({
          id: oldValues.id,
          schoolYear: oldValues.school_year,
          number: oldValues.number_of_students,
          source: (oldValues.source_school === '__NEW__') ? oldValues.new_source_school : oldValues.source_school,
        });

        // preserve selector intent
        if (oldValues.source_school === '__NEW__') {
          formSourceSelect.value = '__NEW__';
          formNewSchool.value = oldValues.new_source_school || '';
        }
        syncNewSchoolVisibility();
      } else {
        setCreateMode();
        formSchoolYear.value = oldValues.school_year || "";
        formNumber.value = oldValues.number_of_students || "";

        if (oldValues.source_school) {
          formSourceSelect.value = oldValues.source_school;
        }
        if (oldValues.source_school === '__NEW__') {
          formNewSchool.value = oldValues.new_source_school || '';
        }
        syncNewSchoolVisibility();
      }
      openRecordModal();
    }
  </script>

</body>
</html>
