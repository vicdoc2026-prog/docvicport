<?php
require_once '../config/check-session.php';
require_once '../config/conn.php';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// -----------------------------
// Helpers
// -----------------------------
$flash = $_GET['msg'] ?? '';

function redirectWithMsg($msg) {
  $url = strtok($_SERVER["REQUEST_URI"], '?');
  header("Location: {$url}?msg=" . urlencode($msg));
  exit;
}

function buildQueryUrl($overrides = []) {
  $params = $_GET;
  foreach ($overrides as $k => $v) {
    if ($v === null) unset($params[$k]);
    else $params[$k] = $v;
  }
  $base = strtok($_SERVER["REQUEST_URI"], '?');
  $qs = http_build_query($params);
  return $base . ($qs ? ('?' . $qs) : '');
}

// -----------------------------
// CRUD handlers (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
  $purchase_date = trim($_POST['purchase_date'] ?? '');
  $supplier = trim($_POST['supplier'] ?? '');
  $cost_per_liter = trim($_POST['cost_per_liter'] ?? '');
  $quantity = trim($_POST['quantity'] ?? '');

  $cost_f = is_numeric($cost_per_liter) ? (float)$cost_per_liter : null;
  $qty_f  = is_numeric($quantity) ? (float)$quantity : null;

  if ($action === 'create' || $action === 'update') {
    if ($purchase_date === '' || $supplier === '' || $cost_f === null || $qty_f === null) {
      redirectWithMsg('Please complete all fields correctly.');
    }
    if ($cost_f < 0 || $qty_f < 0) {
      redirectWithMsg('Cost and quantity must be non-negative.');
    }

    if ($action === 'create') {
      $stmt = $conn->prepare("INSERT INTO fuel_purchases (purchase_date, supplier, cost_per_liter, quantity) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("ssdd", $purchase_date, $supplier, $cost_f, $qty_f);
      if ($stmt->execute()) { $stmt->close(); redirectWithMsg('Purchase added successfully.'); }
      $err = $stmt->error; $stmt->close();
      redirectWithMsg('Create failed: ' . $err);
    }

    if ($action === 'update') {
      if ($id <= 0) redirectWithMsg('Invalid record.');
      $stmt = $conn->prepare("UPDATE fuel_purchases SET purchase_date = ?, supplier = ?, cost_per_liter = ?, quantity = ? WHERE id = ?");
      $stmt->bind_param("ssddi", $purchase_date, $supplier, $cost_f, $qty_f, $id);
      if ($stmt->execute()) { $stmt->close(); redirectWithMsg('Purchase updated successfully.'); }
      $err = $stmt->error; $stmt->close();
      redirectWithMsg('Update failed: ' . $err);
    }
  }

  if ($action === 'delete') {
    if ($id <= 0) redirectWithMsg('Invalid record.');
    $stmt = $conn->prepare("DELETE FROM fuel_purchases WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) { $stmt->close(); redirectWithMsg('Purchase deleted.'); }
    $err = $stmt->error; $stmt->close();
    redirectWithMsg('Delete failed: ' . $err);
  }

  redirectWithMsg('Unknown action.');
}

// -----------------------------
// Totals (all records)
// -----------------------------
$totalsSql = "SELECT purchase_date, (cost_per_liter * quantity) AS amount
             FROM fuel_purchases
             ORDER BY purchase_date ASC";
$totalsRes = $conn->query($totalsSql);

$monthlyTotals = [];
$yearlyTotals  = [];

if ($totalsRes) {
  while ($r = $totalsRes->fetch_assoc()) {
    $monthKey = date('Y-m', strtotime($r['purchase_date']));
    $yearKey  = date('Y', strtotime($r['purchase_date']));
    $amount   = (float)$r['amount'];

    if (!isset($monthlyTotals[$monthKey])) $monthlyTotals[$monthKey] = 0;
    if (!isset($yearlyTotals[$yearKey]))  $yearlyTotals[$yearKey]  = 0;

    $monthlyTotals[$monthKey] += $amount;
    $yearlyTotals[$yearKey]  += $amount;
  }
}
$grandTotal = array_sum($yearlyTotals);

// -----------------------------
// Pagination (Purchases table)
// -----------------------------
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
$allowedPerPage = [5, 10, 20, 50];
if (!in_array($perPage, $allowedPerPage, true)) $perPage = 10;

$countRes = $conn->query("SELECT COUNT(*) AS c FROM fuel_purchases");
$totalRows = 0;
if ($countRes) {
  $totalRows = (int)($countRes->fetch_assoc()['c'] ?? 0);
}
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;

// Fetch only current page
$listSql = "SELECT id, purchase_date, supplier, cost_per_liter, quantity, (cost_per_liter * quantity) AS amount
            FROM fuel_purchases
            ORDER BY purchase_date ASC
            LIMIT ? OFFSET ?";
$stmt = $conn->prepare($listSql);
$stmt->bind_param("ii", $perPage, $offset);
$stmt->execute();
$listRes = $stmt->get_result();

$purchases = [];
while ($row = $listRes->fetch_assoc()) $purchases[] = $row;
$stmt->close();

$prevUrl = ($page > 1) ? buildQueryUrl(['page' => $page - 1]) : null;
$nextUrl = ($page < $totalPages) ? buildQueryUrl(['page' => $page + 1]) : null;

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Fuel Purchases</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
  :root { color-scheme: light; }
  body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
  .bg-grid{
    background-image: radial-gradient(circle at 1px 1px, rgba(15,23,42,.06) 1px, transparent 0);
    background-size: 22px 22px;
  }
  .card{
    background: rgba(255,255,255,.92);
    border: 1px solid rgba(15,23,42,.10);
    border-radius: 10px;
    box-shadow: 0 12px 30px rgba(2, 6, 23, 0.06);
  }
  .soft{ border-radius: 10px; }
  .chip{
    border: 1px solid rgba(15,23,42,.10);
    background: rgba(255,255,255,.90);
    border-radius: 999px;
  }
  .nice-scroll::-webkit-scrollbar { height: 10px; width: 10px; }
  .nice-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,.15); border-radius: 999px; }
  .nice-scroll::-webkit-scrollbar-track { background: rgba(15,23,42,.05); }
  .table-sticky thead th{
    position: sticky;
    top: 0;
    z-index: 5;
    background: rgba(248,250,252,.95);
    backdrop-filter: blur(8px);
  }
</style>
</head>

<?php include '../bar/navbar.php'; ?>

<body class="min-h-screen bg-slate-50 bg-grid">
  <div class="w-full px-4 md:px-8 py-6 md:py-8">

    <!-- Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-5">
      <div>
        <div class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-700 chip px-3 py-1">
          <i class="fa-solid fa-gas-pump text-emerald-600"></i>
          Fuel Purchases
        </div>
        <h1 class="mt-3 text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
          Monthly & Yearly Totals + Purchases
        </h1>
        <p class="mt-1 text-slate-600 font-semibold">
          Add, edit, and delete purchases. Totals update automatically.
        </p>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <a href="javascript:history.back()"
           class="inline-flex items-center gap-2 px-3 py-2 soft border border-slate-200 bg-white font-extrabold text-slate-800 hover:bg-slate-50">
          <i class="fa-solid fa-arrow-left text-slate-500"></i> Back
        </a>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-3 py-2 soft bg-slate-900 text-white font-extrabold hover:bg-slate-800"
          onclick="openCreate()"
        >
          <i class="fa-solid fa-plus"></i> Add Purchase
        </button>
      </div>
    </div>

    <!-- TOP: Monthly + Yearly totals -->
    <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 mb-4">

      <!-- Monthly Totals -->
      <div class="xl:col-span-7 card p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-black text-slate-900">Monthly Totals</h2>
          <span class="chip px-3 py-1 text-xs font-extrabold text-slate-700">
            <?php echo number_format(count($monthlyTotals)); ?> months
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
          <?php if(!$monthlyTotals): ?>
            <div class="p-3 soft border border-slate-200 bg-white text-slate-600 font-semibold text-sm">
              No monthly totals yet.
            </div>
          <?php endif; ?>

          <?php foreach($monthlyTotals as $month => $total): ?>
            <div class="p-3 soft border border-slate-200 bg-white flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-extrabold text-slate-800 truncate">
                  <?php echo date('F Y', strtotime($month.'-01')); ?>
                </div>
                <div class="text-xs font-semibold text-slate-500"><?php echo e($month); ?></div>
              </div>
              <div class="text-sm font-black text-slate-900 whitespace-nowrap">
                ₱<?php echo number_format((float)$total, 2); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Yearly Totals -->
      <div class="xl:col-span-5 card p-4 md:p-5">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-black text-slate-900">Yearly Totals</h2>
          <span class="chip px-3 py-1 text-xs font-extrabold text-slate-700">
            <?php echo number_format(count($yearlyTotals)); ?> years
          </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-1 gap-2">
          <?php if(!$yearlyTotals): ?>
            <div class="p-3 soft border border-slate-200 bg-white text-slate-600 font-semibold text-sm">
              No yearly totals yet.
            </div>
          <?php endif; ?>

          <?php foreach($yearlyTotals as $year => $total): ?>
            <div class="p-3 soft border border-slate-200 bg-white flex items-center justify-between">
              <span class="font-extrabold text-slate-800"><?php echo e($year); ?></span>
              <span class="font-black text-slate-900">₱<?php echo number_format((float)$total, 2); ?></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="mt-3 p-3 soft border border-slate-200 bg-slate-50 flex items-center justify-between">
          <span class="font-extrabold text-slate-700">
            <i class="fa-solid fa-sigma text-slate-500"></i> Grand Total
          </span>
          <span class="font-black text-slate-900">₱<?php echo number_format((float)$grandTotal, 2); ?></span>
        </div>
      </div>
    </div>

    <!-- WIDE: Purchases table occupies all width -->
    <div class="card p-4 md:p-5">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
        <div>
          <h2 class="text-lg font-black text-slate-900">Purchases</h2>
          <p class="text-sm font-semibold text-slate-600">
            Amount is automatically computed: cost_per_liter × quantity.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <form method="GET" class="flex items-center gap-2">
            <?php if ($flash !== ''): ?>
              <input type="hidden" name="msg" value="<?php echo e($flash); ?>">
            <?php endif; ?>
            <input type="hidden" name="page" value="<?php echo (int)$page; ?>">
            <label class="text-xs font-extrabold text-slate-600">Rows</label>
            <select name="per_page" onchange="this.form.submit()"
                    class="px-3 py-2 soft border border-slate-200 bg-white font-extrabold text-slate-800">
              <?php foreach ([5,10,20,50] as $n): ?>
                <option value="<?php echo $n; ?>" <?php echo ($perPage===$n)?'selected':''; ?>>
                  <?php echo $n; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </form>

          <span class="chip px-3 py-1 text-xs font-extrabold text-slate-700">
            Total rows: <?php echo number_format($totalRows); ?>
          </span>

          <?php if ($flash !== ''): ?>
            <span class="chip px-3 py-1 text-xs font-extrabold text-emerald-700 border-emerald-200">
              <i class="fa-solid fa-circle-check"></i> <?php echo e($flash); ?>
            </span>
          <?php endif; ?>
        </div>
      </div>

      <div class="overflow-auto nice-scroll table-sticky soft border border-slate-200" style="max-height: 560px;">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-slate-700">
              <th class="px-3 py-3 text-left font-black">Date</th>
              <th class="px-3 py-3 text-left font-black">Supplier</th>
              <th class="px-3 py-3 text-right font-black">Cost/Liter</th>
              <th class="px-3 py-3 text-right font-black">Quantity (L)</th>
              <th class="px-3 py-3 text-right font-black">Amount</th>
              <th class="px-3 py-3 text-center font-black">Actions</th>
            </tr>
          </thead>
          <tbody class="bg-white">
            <?php if(!$purchases): ?>
              <tr>
                <td colspan="6" class="px-3 py-10 text-center text-slate-600 font-semibold">
                  No purchases found. Click <span class="font-black">Add Purchase</span> to create one.
                </td>
              </tr>
            <?php endif; ?>

            <?php foreach($purchases as $p): ?>
              <tr class="border-t border-slate-100 hover:bg-slate-50/70 transition">
                <td class="px-3 py-3 font-semibold text-slate-900 whitespace-nowrap">
                  <?php echo e($p['purchase_date']); ?>
                </td>
                <td class="px-3 py-3 font-semibold text-slate-800">
                  <?php echo e($p['supplier']); ?>
                </td>
                <td class="px-3 py-3 text-right font-semibold text-slate-800 whitespace-nowrap">
                  ₱<?php echo number_format((float)$p['cost_per_liter'], 2); ?>
                </td>
                <td class="px-3 py-3 text-right font-semibold text-slate-800 whitespace-nowrap">
                  <?php echo number_format((float)$p['quantity'], 2); ?>
                </td>
                <td class="px-3 py-3 text-right font-black text-slate-900 whitespace-nowrap">
                  ₱<?php echo number_format((float)$p['amount'], 2); ?>
                </td>
                <td class="px-3 py-3 text-center">
                  <div class="inline-flex items-center gap-2">
                    <button
                      type="button"
                      class="px-3 py-2 soft border border-slate-200 bg-white text-slate-800 font-extrabold hover:bg-slate-50 text-xs"
                      onclick='openEdit(<?php echo (int)$p["id"]; ?>, <?php echo json_encode($p["purchase_date"]); ?>, <?php echo json_encode($p["supplier"]); ?>, <?php echo json_encode((string)$p["cost_per_liter"]); ?>, <?php echo json_encode((string)$p["quantity"]); ?>)'
                    >
                      <i class="fa-solid fa-pen-to-square"></i> Edit
                    </button>

                    <form method="POST" onsubmit="return confirmDelete();" class="inline">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                      <button
                        type="submit"
                        class="px-3 py-2 soft bg-rose-600 text-white font-extrabold hover:bg-rose-700 text-xs"
                      >
                        <i class="fa-solid fa-trash"></i> Delete
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-xs font-semibold text-slate-500">
          Page <span class="font-black text-slate-800"><?php echo (int)$page; ?></span>
          of <span class="font-black text-slate-800"><?php echo (int)$totalPages; ?></span>
          • Showing
          <span class="font-black text-slate-800">
            <?php
              if ($totalRows === 0) echo "0";
              else {
                $from = $offset + 1;
                $to = min($offset + $perPage, $totalRows);
                echo number_format($from) . "–" . number_format($to);
              }
            ?>
          </span>
          of <span class="font-black text-slate-800"><?php echo number_format($totalRows); ?></span>
        </div>

        <div class="flex items-center justify-end gap-2">
          <a
            href="<?php echo $prevUrl ? e($prevUrl) : '#'; ?>"
            class="inline-flex items-center gap-2 px-3 py-2 soft border border-slate-200 font-extrabold
                   <?php echo $prevUrl ? 'bg-white text-slate-800 hover:bg-slate-50' : 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none'; ?>"
          >
            <i class="fa-solid fa-chevron-left"></i> Prev
          </a>

          <a
            href="<?php echo $nextUrl ? e($nextUrl) : '#'; ?>"
            class="inline-flex items-center gap-2 px-3 py-2 soft border border-slate-200 font-extrabold
                   <?php echo $nextUrl ? 'bg-white text-slate-800 hover:bg-slate-50' : 'bg-slate-100 text-slate-400 cursor-not-allowed pointer-events-none'; ?>"
          >
            Next <i class="fa-solid fa-chevron-right"></i>
          </a>
        </div>
      </div>
    </div>

  </div>

  <!-- Modal (Create/Edit) -->
  <div id="modalOverlay" class="fixed inset-0 hidden items-center justify-center bg-slate-900/40 backdrop-blur-sm z-[9999] p-4">
    <div class="card w-full max-w-xl p-4 md:p-5">
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 id="modalTitle" class="text-lg font-black text-slate-900">Add Purchase</h3>
          <p class="text-sm font-semibold text-slate-600">Fill out the details then save.</p>
        </div>
        <button type="button" class="px-3 py-2 soft border border-slate-200 bg-white font-extrabold hover:bg-slate-50"
                onclick="closeModal()">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <form method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" id="purchaseForm">
        <input type="hidden" name="action" id="formAction" value="create">
        <input type="hidden" name="id" id="formId" value="">

        <div class="md:col-span-1">
          <label class="text-xs font-extrabold text-slate-700">Purchase Date</label>
          <input type="date" name="purchase_date" id="formDate"
                 class="mt-1 w-full px-3 py-2 soft border border-slate-200 bg-white font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-200"
                 required>
        </div>

        <div class="md:col-span-1">
          <label class="text-xs font-extrabold text-slate-700">Supplier</label>
          <input type="text" name="supplier" id="formSupplier" placeholder="e.g., Petron / Shell"
                 class="mt-1 w-full px-3 py-2 soft border border-slate-200 bg-white font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-200"
                 required>
        </div>

        <div class="md:col-span-1">
          <label class="text-xs font-extrabold text-slate-700">Cost per Liter</label>
          <input type="number" step="0.01" min="0" name="cost_per_liter" id="formCost" placeholder="0.00"
                 class="mt-1 w-full px-3 py-2 soft border border-slate-200 bg-white font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                 required>
        </div>

        <div class="md:col-span-1">
          <label class="text-xs font-extrabold text-slate-700">Quantity (Liters)</label>
          <input type="number" step="0.01" min="0" name="quantity" id="formQty" placeholder="0.00"
                 class="mt-1 w-full px-3 py-2 soft border border-slate-200 bg-white font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                 required>
        </div>

        <div class="md:col-span-2 flex flex-col-reverse md:flex-row md:items-center md:justify-end gap-2 mt-2">
          <button type="button"
                  class="px-4 py-2 soft border border-slate-200 bg-white font-extrabold text-slate-800 hover:bg-slate-50"
                  onclick="closeModal()">
            Cancel
          </button>
          <button type="submit"
                  class="px-4 py-2 soft bg-slate-900 text-white font-extrabold hover:bg-slate-800">
            <i class="fa-solid fa-floppy-disk"></i> Save
          </button>
        </div>
      </form>
    </div>
  </div>

<script>
  const overlay = document.getElementById('modalOverlay');
  const titleEl = document.getElementById('modalTitle');
  const formAction = document.getElementById('formAction');
  const formId = document.getElementById('formId');
  const formDate = document.getElementById('formDate');
  const formSupplier = document.getElementById('formSupplier');
  const formCost = document.getElementById('formCost');
  const formQty = document.getElementById('formQty');

  function openCreate() {
    titleEl.textContent = 'Add Purchase';
    formAction.value = 'create';
    formId.value = '';
    formDate.value = '';
    formSupplier.value = '';
    formCost.value = '';
    formQty.value = '';
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    setTimeout(() => formDate.focus(), 50);
  }

  function openEdit(id, date, supplier, cost, qty) {
    titleEl.textContent = 'Edit Purchase';
    formAction.value = 'update';
    formId.value = id;
    formDate.value = date || '';
    formSupplier.value = supplier || '';
    formCost.value = cost || '';
    formQty.value = qty || '';
    overlay.classList.remove('hidden');
    overlay.classList.add('flex');
    setTimeout(() => formSupplier.focus(), 50);
  }

  function closeModal() {
    overlay.classList.add('hidden');
    overlay.classList.remove('flex');
  }

  function confirmDelete() {
    return confirm('Delete this purchase? This cannot be undone.');
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !overlay.classList.contains('hidden')) closeModal();
  });
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) closeModal();
  });
</script>

</body>
</html>

<?php $conn->close(); ?>
