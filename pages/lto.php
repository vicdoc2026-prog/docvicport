<?php 

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
$canAddLtoVehicles = $role === 'belvic_admin' && strtolower($username) === 'belvicop@gmail.com';
$vehicleForm = [
  'mv_file_no' => '',
  'code' => '',
  'make' => '',
  'type' => '',
  'plate_no' => '',
  'due_date_actual' => '',
];
$vehicleErrors = [];
$vehicleRecordKey = '';

if (!isset($_SESSION['lto_entry_csrf'])) {
  $_SESSION['lto_entry_csrf'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['add_lto_vehicle', 'update_lto_vehicle', 'delete_lto_vehicle'], true)) {
  if (!$canAddLtoVehicles) {
    http_response_code(403);
    exit('You do not have permission to manage vehicle registrations.');
  }

  $submittedToken = $_POST['csrf_token'] ?? '';
  if (!is_string($submittedToken) || !hash_equals($_SESSION['lto_entry_csrf'], $submittedToken)) {
    $vehicleErrors[] = 'Your session token is invalid. Refresh the page and try again.';
  }

  $action = $_POST['action'];
  $submittedKey = $_POST['firebase_key'] ?? '';
  $vehicleRecordKey = is_scalar($submittedKey) ? trim((string)$submittedKey) : '';

  if (in_array($action, ['update_lto_vehicle', 'delete_lto_vehicle'], true) &&
      ($vehicleRecordKey === '' || strlen($vehicleRecordKey) > 128 || !preg_match('/^[A-Za-z0-9_-]+$/D', $vehicleRecordKey))) {
    $vehicleErrors[] = 'The selected vehicle record is invalid.';
  }

  if ($action === 'delete_lto_vehicle' && !$vehicleErrors) {
    require_once __DIR__ . '/../conn.php';
    if (fb_delete('/lto/' . $vehicleRecordKey)) {
      header('Location: lto.php?deleted=1');
      exit;
    }
    $vehicleErrors[] = 'The vehicle could not be deleted. Check Firebase write permissions and try again.';
  }

  if ($action !== 'delete_lto_vehicle') {
    foreach ($vehicleForm as $field => $default) {
      $value = $_POST[$field] ?? $default;
      $vehicleForm[$field] = is_scalar($value) ? trim((string)$value) : '';
    }

    foreach ([
      'mv_file_no' => ['MV File No.', 25],
      'code' => ['Code', 50],
      'make' => ['Make', 50],
      'type' => ['Type', 100],
      'plate_no' => ['Plate No.', 20],
      'due_date_actual' => ['Due Date', 10],
    ] as $field => [$label, $maxLength]) {
      if ($vehicleForm[$field] === '') {
        $vehicleErrors[] = $label . ' is required.';
      } elseif (strlen($vehicleForm[$field]) > $maxLength) {
        $vehicleErrors[] = $label . ' must be ' . $maxLength . ' characters or fewer.';
      }
    }

    if ($vehicleForm['due_date_actual'] !== '') {
      $dueDate = DateTime::createFromFormat('!Y-m-d', $vehicleForm['due_date_actual']);
      if (!$dueDate || $dueDate->format('Y-m-d') !== $vehicleForm['due_date_actual']) {
        $vehicleErrors[] = 'Due Date must be a valid date.';
      }
    }
  }

  if (!$vehicleErrors) {
    require_once __DIR__ . '/../conn.php';
    $vehicleData = [
      'mv_file_no' => $vehicleForm['mv_file_no'],
      'code' => $vehicleForm['code'],
      'make' => $vehicleForm['make'],
      'type' => $vehicleForm['type'],
      'plate_no' => $vehicleForm['plate_no'],
      'due_date_actual' => $vehicleForm['due_date_actual'],
    ];

    $writeSucceeded = $action === 'add_lto_vehicle'
      ? fb_post('/lto', $vehicleData) !== null
      : fb_patch('/lto/' . $vehicleRecordKey, $vehicleData);

    if ($writeSucceeded) {
      header('Location: lto.php?saved=' . ($action === 'add_lto_vehicle' ? 'added' : 'updated'));
      exit;
    }
    $vehicleErrors[] = 'The vehicle could not be saved. Check Firebase write permissions and try again.';
  }
}

?>

<?php include '../bar/navbar.php'; ?>
<?php
// Optional fallback if you still want a PHP-side default when Firebase fetch fails.
// Keep it as an empty array if you're not reading MySQL anymore.
$vehicles = [];
$vehicles_json = json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Vehicle Registration Records</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    .excel-table { border-collapse: collapse; width: 100%; }
    .excel-table th, .excel-table td { border: 1px solid #d1d5db; padding: 8px 12px; font-size: 14px; }
    .excel-table th { background-color: #f3f4f6; font-weight: 600; position: sticky; top: 0; }
    .excel-table tr:nth-child(even) { background-color: #f9fafb; }
    .excel-table tr:hover { background-color: #f1f5f9; }
    .filter-dropdown { box-shadow: 0 4px 6px -1px rgba(0,0,0,.1), 0 2px 4px -1px rgba(0,0,0,.06); border: 1px solid #e5e7eb; }
    .pagination-btn { min-width: 40px; height: 40px; display:flex; align-items:center; justify-content:center; border:1px solid #d1d5db; }
    .status-upcoming { background-color:#dbeafe; }
    .status-urgent { 
      background-color:#fee2e2; 
      animation: urgentBlink 2s infinite;
      font-weight: 600;
      color: #991b1b;
    }
    .status-normal { background-color:#dcfce7; }
    .lto-dialog::backdrop { background: rgba(15, 23, 42, .55); backdrop-filter: blur(2px); }
    .lto-dialog[open] { animation: lto-dialog-in .16s ease-out; }
    @keyframes lto-dialog-in { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    .chart-container { position: relative; height: 300px; }
    .chart-bar { position: absolute; bottom: 0; width: 40px; background-color:#3b82f6; border-radius:4px 4px 0 0; transition: height .5s ease; }
    .chart-bar:hover { background-color:#2563eb; }

    /* Blinking animation for urgent status */
    @keyframes urgentBlink {
      0% { 
        background-color: #fee2e2;
        box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
      }
      50% { 
        background-color: #fecaca;
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.8);
        transform: scale(1.02);
      }
      100% { 
        background-color: #fee2e2;
        box-shadow: 0 0 0 rgba(239, 68, 68, 0.4);
      }
    }

    /* Enhanced urgent row styling */
    .urgent-row {
      animation: urgentRowPulse 3s infinite;
      border-left: 4px solid #ef4444;
    }

    @keyframes urgentRowPulse {
      0% { background-color: rgba(254, 226, 226, 0.3); }
      50% { background-color: rgba(254, 202, 202, 0.5); }
      100% { background-color: rgba(254, 226, 226, 0.3); }
    }

    /* Urgent indicator icon */
    .urgent-icon {
      animation: urgentIconBounce 1s infinite;
      color: #ef4444;
      margin-right: 5px;
    }

    @keyframes urgentIconBounce {
      0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
      40% { transform: translateY(-3px); }
      60% { transform: translateY(-1px); }
    }

    /* Critical status (1-3 days) gets more intense animation */
    .status-critical {
      background-color: #dc2626;
      color: white;
      animation: criticalBlink 1s infinite;
      font-weight: bold;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
    }

    @keyframes criticalBlink {
      0% { 
        background-color: #dc2626;
        box-shadow: 0 0 5px rgba(220, 38, 38, 0.8);
      }
      50% { 
        background-color: #b91c1c;
        box-shadow: 0 0 15px rgba(220, 38, 38, 1);
        transform: scale(1.05);
      }
      100% { 
        background-color: #dc2626;
        box-shadow: 0 0 5px rgba(220, 38, 38, 0.8);
      }
    }

    .critical-row {
      animation: criticalRowFlash 2s infinite;
      border-left: 6px solid #dc2626;
      background: linear-gradient(90deg, rgba(220, 38, 38, 0.1) 0%, transparent 100%);
    }

    @keyframes criticalRowFlash {
      0% { box-shadow: inset 0 0 0 rgba(220, 38, 38, 0.2); }
      50% { box-shadow: inset 0 0 20px rgba(220, 38, 38, 0.4); }
      100% { box-shadow: inset 0 0 0 rgba(220, 38, 38, 0.2); }
    }
  </style>
</head>
<body class="bg-gray-100">
<div class="flex-1 h-screen px-6 pt-6 pb-10 space-y-6" style="overflow:auto;">

  <?php if (!empty($_GET['saved']) || !empty($_GET['deleted'])): ?>
    <div role="status" class="rounded-lg border border-slate-200 bg-white px-4 py-3 font-semibold text-slate-800">
      <?php echo !empty($_GET['deleted']) ? 'Vehicle registration deleted.' : ($_GET['saved'] === 'updated' ? 'Vehicle registration updated.' : 'Vehicle registration added successfully.'); ?>
    </div>
  <?php endif; ?>
  <?php if ($vehicleErrors): ?>
    <div role="alert" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800">
      <ul class="list-disc space-y-1 pl-5"><?php foreach ($vehicleErrors as $vehicleError): ?><li><?php echo htmlspecialchars($vehicleError, ENT_QUOTES, 'UTF-8'); ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <!-- Vehicle Records Section -->
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex flex-col md:flex-row justify-between items-start md:items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Vehicle Registration Records</h2>
        <p class="text-gray-600 mt-1">Manage all registered vehicles and their registration details</p>
      </div>
      <div class="mt-4 md:mt-0 flex space-x-3 items-center">
        <span class="text-gray-700 text-base font-medium">Total Vehicles: <span id="totalVehicles">0</span></span>
        <?php if ($canAddLtoVehicles): ?>
          <button type="button" id="openVehicleEntry" class="inline-flex items-center gap-2 rounded-lg bg-blue-700 px-4 py-2 text-sm font-bold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300">
            <i class="fas fa-plus" aria-hidden="true"></i> Add Vehicle
          </button>
        <?php endif; ?>
        <div class="relative">
          <button id="filterButton" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded text-gray-700 flex items-center border">
            <i class="fas fa-filter mr-2"></i>
            Filter
            <i class="fas fa-chevron-down ml-2 text-xs"></i>
          </button>
          <div id="filterDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 filter-dropdown hidden z-10">
            <div class="px-4 py-3 border-b">
              <p class="text-sm font-medium text-gray-900">Filter by</p>
            </div>
            <div class="px-4 py-2">
              <label class="flex items-center">
                <input type="checkbox" class="form-checkbox rounded text-blue-600" id="dueThisMonth">
                <span class="ml-2 text-gray-700">Due this month</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Controls -->
    <div class="px-6 py-4 bg-gray-50 flex flex-col md:flex-row justify-between items-center border-b">
      <div class="relative mb-4 md:mb-0 w-full md:w-auto">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="searchInput" class="pl-10 pr-4 py-2 w-full border rounded focus:ring-2 focus:ring-blue-600 focus:border-transparent" placeholder="Search vehicles...">
      </div>
      <div class="flex space-x-2">
        <button class="p-2 rounded border text-gray-700 hover:bg-gray-100" id="downloadBtn" title="Download JSON">
          <i class="fas fa-download"></i>
        </button>
        <button class="p-2 rounded border text-gray-700 hover:bg-gray-100" onclick="window.print()" title="Print">
          <i class="fas fa-print"></i>
        </button>
      </div>
    </div>

    <!-- Table and Pagination -->
    <div class="overflow-x-auto">
      <table class="excel-table">
        <thead>
          <tr>
            <th class="text-left">DUE DATE</th>
            <th class="text-left">MV FILE NO.</th>
            <th class="text-left">CODE</th>
            <th class="text-left">MAKE</th>
            <th class="text-left">TYPE</th>
            <th class="text-left">PLATE NO.</th>
            <th class="text-left">STATUS</th>
            <?php if ($canAddLtoVehicles): ?><th class="text-left">ACTIONS</th><?php endif; ?>
          </tr>
        </thead>
        <tbody id="vehicleTableBody">
          <!-- rows injected by JS -->
        </tbody>
      </table>
      <div id="paginationContainer" class="px-6 py-4 bg-gray-50 border-t flex flex-col md:flex-row justify-between items-center">
        <div id="paginationInfo" class="mb-4 md:mb-0">
          <p class="text-sm text-gray-700">
            Showing <span id="startIndex">1</span> to <span id="endIndex">10</span> of <span id="totalResults">0</span> results
          </p>
        </div>
        <div id="paginationButtons" class="flex items-center space-x-2"></div>
      </div>
    </div>
  </div>

  <!-- Additional Stats -->
  <div class="mt-8 mb-10 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900">Registration Distribution</h3>
      </div>
      <div class="chart-container" id="registrationChart"></div>
    </div>
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900">Vehicle Types</h3>
      </div>
      <div class="flex items-center justify-center">
        <div class="relative w-48 h-48">
          <div class="absolute inset-0 rounded-full bg-blue-500 opacity-20"></div>
          <div class="absolute inset-8 rounded-full bg-green-500 opacity-20"></div>
          <div class="absolute inset-16 rounded-full bg-yellow-500 opacity-20"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <p class="text-2xl font-bold" id="totalVehiclesDisplay">0</p>
              <p class="text-gray-600">Total Vehicles</p>
            </div>
          </div>
        </div>
        <div class="ml-8">
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
            <span class="text-sm">Trucks & Buses (<span id="truckCount">0</span>)</span>
          </div>
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
            <span class="text-sm">Pickups (<span id="pickupCount">0</span>)</span>
          </div>
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
            <span class="text-sm">Motorcycles (<span id="motorcycleCount">0</span>)</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
            <span class="text-sm">Other (<span id="otherCount">0</span>)</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <?php if ($canAddLtoVehicles): ?>
    <dialog id="vehicleEntryDialog" aria-labelledby="vehicleEntryTitle" class="lto-dialog m-auto max-h-[calc(100dvh-2rem)] w-[min(94vw,720px)] max-w-none overflow-y-auto rounded-xl border border-gray-200 bg-white p-0 shadow-2xl">
      <form method="POST" class="p-5 sm:p-7">
        <input type="hidden" name="action" id="vehicleEntryAction" value="add_lto_vehicle">
        <input type="hidden" name="firebase_key" id="vehicleFirebaseKey" value="<?php echo htmlspecialchars($vehicleRecordKey, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['lto_entry_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
        <div class="mb-5 flex items-start justify-between gap-4">
          <div>
            <p class="text-xs font-bold uppercase tracking-wide text-blue-700">Vehicle Registration Records</p>
            <h2 id="vehicleEntryTitle" class="mt-1 text-xl font-extrabold text-gray-900"><?php echo $vehicleRecordKey !== '' ? 'Edit Vehicle' : 'Add Vehicle'; ?></h2>
            <p class="mt-1 text-sm font-medium text-gray-600">Enter the vehicle and registration details.</p>
          </div>
          <button type="button" id="closeVehicleEntry" aria-label="Close form" class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-300"><i class="fas fa-times" aria-hidden="true"></i></button>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <?php
            $vehicleFields = [
              'mv_file_no' => ['MV File No.', 25],
              'code' => ['Code', 50],
              'make' => ['Make', 50],
              'type' => ['Type', 100],
              'plate_no' => ['Plate No.', 20],
            ];
          ?>
          <?php foreach ($vehicleFields as $field => [$label, $maxLength]): ?>
            <label class="block text-sm font-bold text-gray-700"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?> <span class="text-red-600">*</span>
              <input name="<?php echo $field; ?>" type="text" maxlength="<?php echo $maxLength; ?>" required value="<?php echo htmlspecialchars($vehicleForm[$field], ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-medium focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100">
            </label>
          <?php endforeach; ?>
          <label class="block text-sm font-bold text-gray-700 sm:col-span-2">Due Date <span class="text-red-600">*</span>
            <input name="due_date_actual" type="date" required value="<?php echo htmlspecialchars($vehicleForm['due_date_actual'], ENT_QUOTES, 'UTF-8'); ?>" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2.5 font-medium focus:border-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-100 sm:max-w-xs">
          </label>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-2 border-t border-gray-200 pt-4 sm:flex-row sm:justify-end">
          <button type="button" data-close-vehicle-entry class="rounded-lg border border-gray-300 px-4 py-2.5 font-bold text-gray-700 hover:bg-gray-50">Cancel</button>
          <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-700 px-5 py-2.5 font-bold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-300"><i class="fas fa-save" aria-hidden="true"></i> <span id="vehicleEntrySubmitLabel"><?php echo $vehicleRecordKey !== '' ? 'Update Vehicle' : 'Save Vehicle'; ?></span></button>
        </div>
      </form>
    </dialog>
    <form id="deleteVehicleForm" method="POST" class="hidden">
      <input type="hidden" name="action" value="delete_lto_vehicle">
      <input type="hidden" name="firebase_key" id="deleteVehicleFirebaseKey" value="">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['lto_entry_csrf'], ENT_QUOTES, 'UTF-8'); ?>">
    </form>
  <?php endif; ?>

<script>
/* =========================
   Firebase LTO Table Script
   ========================= */

/** CONFIG **/
const FB_URL = "https://belvic-9f87a-default-rtdb.firebaseio.com/lto.json";

/** STATE **/
let firebaseVehicles = [];
let filteredVehicles = [];
let currentPage = 1;
const itemsPerPage = 10;

/** DOM **/
const tableBody = document.getElementById('vehicleTableBody');
const paginationButtons = document.getElementById('paginationButtons');
const searchInput = document.getElementById('searchInput');
const filterButton = document.getElementById('filterButton');
const filterDropdown = document.getElementById('filterDropdown');
const dueThisMonth = document.getElementById('dueThisMonth');
const registrationChart = document.getElementById('registrationChart');
const truckCount = document.getElementById('truckCount');
const pickupCount = document.getElementById('pickupCount');
const motorcycleCount = document.getElementById('motorcycleCount');
const otherCount = document.getElementById('otherCount');
const totalVehicles = document.getElementById('totalVehicles');
const totalVehiclesDisplay = document.getElementById('totalVehiclesDisplay');
const downloadBtn = document.getElementById('downloadBtn'); // optional; ignore if not present
const canManageLto = <?php echo $canAddLtoVehicles ? 'true' : 'false'; ?>;

<?php if ($canAddLtoVehicles): ?>
const vehicleEntryDialog = document.getElementById('vehicleEntryDialog');
const vehicleEntryForm = vehicleEntryDialog.querySelector('form');
const vehicleEntryAction = document.getElementById('vehicleEntryAction');
const vehicleFirebaseKey = document.getElementById('vehicleFirebaseKey');
const vehicleEntryTitle = document.getElementById('vehicleEntryTitle');
const vehicleEntrySubmitLabel = document.getElementById('vehicleEntrySubmitLabel');
const vehicleEntryFields = Object.fromEntries(['mv_file_no', 'code', 'make', 'type', 'plate_no', 'due_date_actual'].map(name => [name, vehicleEntryForm.elements[name]]));
const deleteVehicleForm = document.getElementById('deleteVehicleForm');

function openVehicleEditor(vehicle = null) {
  vehicleEntryForm.reset();
  vehicleFirebaseKey.value = vehicle?.firebaseKey || '';
  vehicleEntryAction.value = vehicle ? 'update_lto_vehicle' : 'add_lto_vehicle';
  vehicleEntryTitle.textContent = vehicle ? 'Edit Vehicle' : 'Add Vehicle';
  vehicleEntrySubmitLabel.textContent = vehicle ? 'Update Vehicle' : 'Save Vehicle';
  if (vehicle) {
    vehicleEntryFields.mv_file_no.value = vehicle.mvFileNo;
    vehicleEntryFields.code.value = vehicle.code;
    vehicleEntryFields.make.value = vehicle.make;
    vehicleEntryFields.type.value = vehicle.type;
    vehicleEntryFields.plate_no.value = vehicle.plateNo;
    vehicleEntryFields.due_date_actual.value = vehicle.originalDueDate;
  }
  vehicleEntryDialog.showModal();
}

document.getElementById('openVehicleEntry')?.addEventListener('click', () => openVehicleEditor());
document.getElementById('closeVehicleEntry')?.addEventListener('click', () => vehicleEntryDialog.close());
vehicleEntryDialog.querySelectorAll('[data-close-vehicle-entry]').forEach(button => button.addEventListener('click', () => vehicleEntryDialog.close()));
vehicleEntryDialog.querySelector('form').addEventListener('submit', () => {
  const submitButton = vehicleEntryDialog.querySelector('button[type="submit"]');
  submitButton.disabled = true;
  submitButton.innerHTML = '<i class="fas fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
});
deleteVehicleForm.addEventListener('submit', event => {
  if (!window.confirm('Delete this vehicle registration? This cannot be undone.')) event.preventDefault();
});
<?php if ($vehicleErrors): ?>
openVehicleEditor();
<?php if ($vehicleRecordKey !== ''): ?>
vehicleEntryFields.mv_file_no.value = <?php echo json_encode($vehicleForm['mv_file_no']); ?>;
vehicleEntryFields.code.value = <?php echo json_encode($vehicleForm['code']); ?>;
vehicleEntryFields.make.value = <?php echo json_encode($vehicleForm['make']); ?>;
vehicleEntryFields.type.value = <?php echo json_encode($vehicleForm['type']); ?>;
vehicleEntryFields.plate_no.value = <?php echo json_encode($vehicleForm['plate_no']); ?>;
vehicleEntryFields.due_date_actual.value = <?php echo json_encode($vehicleForm['due_date_actual']); ?>;
vehicleFirebaseKey.value = <?php echo json_encode($vehicleRecordKey); ?>;
vehicleEntryAction.value = 'update_lto_vehicle';
vehicleEntryTitle.textContent = 'Edit Vehicle';
vehicleEntrySubmitLabel.textContent = 'Update Vehicle';
<?php endif; ?>
<?php endif; ?>
<?php endif; ?>

/** OPTIONAL PHP FALLBACK (leave as [] if not using PHP data) **/
const phpFallback = [];

/* =========================
   Helpers
   ========================= */
function fixPastDueDateToNextYear(dueDateStr) {
  const today = new Date(); today.setHours(0,0,0,0);
  const dueDate = new Date(dueDateStr); dueDate.setHours(0,0,0,0);
  if (isNaN(dueDate.getTime())) return today;
  if (dueDate < today) {
    return new Date(today.getFullYear() + 1, dueDate.getMonth(), 5);
  }
  return dueDate;
}

function formatDueDate(d) {
  const dd = fixPastDueDateToNextYear(d);
  const mm = String(dd.getMonth()+1).padStart(2,'0');
  const day = String(dd.getDate()).padStart(2,'0');
  return `${mm}/${day}/${dd.getFullYear()}`;
}

function getStatus(d) {
  const today = new Date(); today.setHours(0,0,0,0);
  const due = fixPastDueDateToNextYear(d);
  const days = Math.ceil((due - today) / (1000*60*60*24));
  
  if (days <= 3) return { 
    text: `<i class="fas fa-exclamation-triangle urgent-icon"></i>Critical (${days} days)`, 
    class: 'status-critical',
    rowClass: 'critical-row'
  };
  if (days <= 7) return { 
    text: `<i class="fas fa-exclamation-circle urgent-icon"></i>Urgent (${days} days)`, 
    class: 'status-urgent',
    rowClass: 'urgent-row'
  };
  if (days <= 30) return { 
    text: `Upcoming (${days} days)`, 
    class: 'status-upcoming',
    rowClass: ''
  };
  return { 
    text: `On track (${days} days)`, 
    class: 'status-normal',
    rowClass: ''
  };
}

function mapRow(v, firebaseKey) {
  return {
    firebaseKey,
    mvFileNo: v.mv_file_no ?? v.mvFileNo ?? '',
    code:     v.code ?? '',
    make:     v.make ?? '',
    type:     v.type ?? '',
    plateNo:  v.plate_no ?? v.plateNo ?? '',
    dueDate:  v.due_date_actual ?? v.dueDate ?? '',
    originalDueDate: v.due_date_actual ?? v.dueDate ?? ''
  };
}

// Accepts either an object-map or an array (with null holes)
function objectToArray(json) {
  if (!json) return [];
  if (Array.isArray(json)) {
    return json
      .map((value, index) => value && typeof value === 'object' ? mapRow(value, String(index)) : null)
      .filter(Boolean)
      .filter(v => v.dueDate);                   // keep rows with dates
  }
  return Object.entries(json)
    .filter(([, value]) => value && typeof value === 'object')
    .map(([key, value]) => mapRow(value, key))
    .filter(v => v.dueDate);
}

/* =========================
   Summary & Charts
   ========================= */
function updateSummaryCounts() {
  totalVehicles.textContent = firebaseVehicles.length;
  totalVehiclesDisplay.textContent = firebaseVehicles.length;

  let trucks=0, pickups=0, motos=0, others=0;
  firebaseVehicles.forEach(v => {
    const t = (v.type||'').toLowerCase();
    if (t.includes('truck') || t.includes('bus')) trucks++;
    else if (t.includes('pickup')) pickups++;
    else if (t.includes('motorcycle') || t.includes('bike')) motos++;
    else others++;
  });
  truckCount.textContent = trucks;
  pickupCount.textContent = pickups;
  motorcycleCount.textContent = motos;
  otherCount.textContent = others;
}

function createRegistrationChart() {
  registrationChart.innerHTML = '';
  const monthCounts = Array(12).fill(0);
  const names = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  filteredVehicles.forEach(v => {
    const d = fixPastDueDateToNextYear(v.dueDate);
    const m = d.getMonth();
    monthCounts[m]++;
  });

  const max = Math.max(...monthCounts, 1);
  names.forEach((name, i) => {
    const count = monthCounts[i];
    const barHeight = (count / max) * 240 || 5;
    const bar = document.createElement('div');
    bar.className = 'chart-bar';
    bar.style.left = `${i * 50 + 20}px`;
    bar.style.height = `${barHeight}px`;
    bar.style.width = '40px';
    bar.title = `${name}: ${count}`;

    const label = document.createElement('div');
    label.className = 'absolute -bottom-6 left-0 right-0 text-center text-xs';
    label.textContent = name;

    const countLabel = document.createElement('div');
    countLabel.className = 'absolute -top-6 left-0 right-0 text-center text-xs font-bold';
    countLabel.textContent = count;

    bar.appendChild(countLabel);
    bar.appendChild(label);
    registrationChart.appendChild(bar);
  });
}

/* =========================
   Table + Pagination
   ========================= */
function renderTable(data, page) {
  tableBody.innerHTML = '';
  const start = (page - 1) * itemsPerPage;
  const end = start + itemsPerPage;
  const slice = data.slice(start, end);

  if (slice.length === 0) {
    tableBody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-4 text-gray-500">No vehicles found.</td>
      </tr>`;
    updatePaginationInfo(0, 0, 0);
    renderPagination(0);
    createRegistrationChart();
    updateSummaryCounts();
    return;
  }

  slice.forEach(v => {
    const status = getStatus(v.dueDate);
    const tr = document.createElement('tr');
    tr.className = status.rowClass || '';
    tr.innerHTML = `
      <td>${formatDueDate(v.dueDate)}</td>
      <td>${v.mvFileNo || 'N/A'}</td>
      <td>${v.code || 'N/A'}</td>
      <td>${v.make || 'N/A'}</td>
      <td>${v.type || 'N/A'}</td>
      <td>${v.plateNo || 'N/A'}</td>
      <td class="${status.class}">${status.text}</td>
    `;
    tableBody.appendChild(tr);
  });

  updatePaginationInfo(data.length, start, end);
  renderPagination(data.length);
  createRegistrationChart();
  updateSummaryCounts();
}

function updatePaginationInfo(total, start, end) {
  document.getElementById('startIndex').textContent = total ? (start + 1) : 0;
  document.getElementById('endIndex').textContent = Math.min(end, total);
  document.getElementById('totalResults').textContent = total;
}

function renderPagination(totalItems) {
  paginationButtons.innerHTML = '';
  const totalPages = Math.ceil(totalItems / itemsPerPage) || 1;

  const prev = document.createElement('button');
  prev.className = `pagination-btn ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}`;
  prev.disabled = currentPage === 1;
  prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
  prev.onclick = () => { if (currentPage > 1) { currentPage--; renderTable(filteredVehicles, currentPage); } };
  paginationButtons.appendChild(prev);

  const maxVisible = 5;
  let start = Math.max(1, currentPage - Math.floor(maxVisible/2));
  let end = Math.min(totalPages, start + maxVisible - 1);
  if (end - start + 1 < maxVisible) start = Math.max(1, end - maxVisible + 1);

  for (let i = start; i <= end; i++) {
    const btn = document.createElement('button');
    btn.className = `pagination-btn ${currentPage === i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
    btn.textContent = i;
    btn.onclick = () => { currentPage = i; renderTable(filteredVehicles, currentPage); };
    paginationButtons.appendChild(btn);
  }

  const next = document.createElement('button');
  next.className = `pagination-btn ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : 'hover:bg-gray-100'}`;
  next.disabled = currentPage === totalPages;
  next.innerHTML = '<i class="fas fa-chevron-right"></i>';
  next.onclick = () => { if (currentPage < totalPages) { currentPage++; renderTable(filteredVehicles, currentPage); } };
  paginationButtons.appendChild(next);
}

/* =========================
   Filtering / Search
   ========================= */
function applyFilters() {
  const term = (searchInput.value || '').toLowerCase();
  const today = new Date(); today.setHours(0,0,0,0);
  const thisMonth = today.getMonth();

  const base = firebaseVehicles.map(v => ({
    ...v,
    adjustedDueDate: fixPastDueDateToNextYear(v.dueDate)
  })).filter(v => {
    const due = v.adjustedDueDate; due.setHours(0,0,0,0);
    const matchesSearch =
      (v.mvFileNo||'').toLowerCase().includes(term) ||
      (v.code||'').toLowerCase().includes(term) ||
      (v.make||'').toLowerCase().includes(term) ||
      (v.type||'').toLowerCase().includes(term) ||
      (v.plateNo||'').toLowerCase().includes(term) ||
      formatDueDate(v.dueDate).toLowerCase().includes(term);

    const matchesMonth = !dueThisMonth.checked || (due.getMonth() === thisMonth);
    return matchesSearch && matchesMonth;
  });

  const upcoming = base
    .filter(v => v.adjustedDueDate >= today)
    .sort((a,b) => a.adjustedDueDate - b.adjustedDueDate)
    .slice(0,4);

  const others = base
    .filter(v => !upcoming.includes(v))
    .sort((a,b) => a.adjustedDueDate - b.adjustedDueDate);

  filteredVehicles = [...upcoming, ...others].map(v => ({
    mvFileNo: v.mvFileNo,
    code: v.code,
    make: v.make,
    type: v.type,
    plateNo: v.plateNo,
    dueDate: v.adjustedDueDate.toISOString().split('T')[0]
  }));

  currentPage = 1;
  renderTable(filteredVehicles, currentPage);
}

/* =========================
   Events
   ========================= */
filterButton.addEventListener('click', (e) => {
  e.stopPropagation();
  filterDropdown.classList.toggle('hidden');
});

document.addEventListener('click', (e) => {
  if (!filterButton.contains(e.target) && !filterDropdown.contains(e.target)) {
    filterDropdown.classList.add('hidden');
  }
});

searchInput.addEventListener('input', applyFilters);
dueThisMonth.addEventListener('change', applyFilters);

if (downloadBtn) {
  downloadBtn.addEventListener('click', () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(firebaseVehicles, null, 2));
    const a = document.createElement('a');
    a.href = dataStr;
    a.download = "lto_export.json";
    a.click();
  });
}

/* =========================
   Init (Fetch from Firebase)
   ========================= */
async function init() {
  try {
    const res = await fetch(FB_URL);
    if (!res.ok) throw new Error(`HTTP ${res.status}`);
    const json = await res.json();          // array with nulls in your case
    firebaseVehicles = objectToArray(json); // handles arrays or maps
    if (!firebaseVehicles.length) throw new Error("Parsed 0 vehicles from Firebase.");
  } catch (err) {
    console.error('Firebase fetch failed / empty:', err);
    firebaseVehicles = (phpFallback || []);
  }
  applyFilters(); // render everything
}

init();
</script>
</div>
</body>
</html>