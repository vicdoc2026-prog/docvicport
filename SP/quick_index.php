<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// -----------------------------
// Helpers
// -----------------------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect_back($msgKey = null, $msg = null){
  if ($msgKey && $msg) $_SESSION[$msgKey] = $msg;
  header('Location: quick_index.php');
  exit;
}

function ensure_dir($dir){
  if (!is_dir($dir)) mkdir($dir, 0775, true);
}

function upload_pdf($fileFieldName, $uploadDirAbs, $uploadDirRel){
  if (!isset($_FILES[$fileFieldName]) || !is_array($_FILES[$fileFieldName])) return [null, null, null];
  $f = $_FILES[$fileFieldName];

  if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return [null, null, null];
  if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return [null, null, "Upload error code: " . (int)$f['error']];

  $tmp  = $f['tmp_name'] ?? '';
  $name = $f['name'] ?? '';
  $size = (int)($f['size'] ?? 0);

  // Basic size limit (10MB)
  if ($size > 10 * 1024 * 1024) return [null, null, "PDF is too large. Max 10MB."];

  // Validate extension
  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext !== 'pdf') return [null, null, "Only PDF files are allowed."];

  // Validate mime (best-effort)
  $mime = '';
  if (function_exists('finfo_open')) {
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    if ($fi) {
      $mime = finfo_file($fi, $tmp) ?: '';
      finfo_close($fi);
    }
  }
  if ($mime && $mime !== 'application/pdf') return [null, null, "Invalid file type. Please upload a valid PDF."];

  ensure_dir($uploadDirAbs);

  // Random filename
  $safeBase = bin2hex(random_bytes(16));
  $finalName = $safeBase . '.pdf';

  $destAbs = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $finalName;
  $destRel = rtrim($uploadDirRel, '/\\') . '/' . $finalName;

  if (!move_uploaded_file($tmp, $destAbs)) return [null, null, "Failed to save uploaded file."];

  return [$destRel, $name, null];
}

function delete_file_if_exists($relPath){
  if (!$relPath) return;
  $relPath = str_replace(['..\\','../'], '', $relPath);

  $abs = __DIR__ . DIRECTORY_SEPARATOR . $relPath;
  $abs = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $abs);

  if (is_file($abs)) @unlink($abs);
}

// -----------------------------
// CRUD (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];

  // Upload settings
  $uploadDirRel = 'uploads/quick_index_pdfs';
  $uploadDirAbs = __DIR__ . DIRECTORY_SEPARATOR . $uploadDirRel;

  // ✅ CREATE
  if ($action === 'add_session') {
    $session_number = trim($_POST['session_number'] ?? '');
    $session_date   = trim($_POST['session_date'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $end_time       = trim($_POST['end_time'] ?? '');
    $venue          = trim($_POST['venue'] ?? '');
    $agenda         = trim($_POST['agenda'] ?? '');
    $status         = trim($_POST['status'] ?? 'scheduled');

    if ($session_number === '' || $session_date === '' || $start_time === '' || $end_time === '' || $venue === '' || $status === '') {
      redirect_back('error_message', 'Please fill in all required fields.');
    }

    // PDF upload (optional)
    [$pdfRel, $pdfOriginal, $pdfErr] = upload_pdf('pdf_file', $uploadDirAbs, $uploadDirRel);
    if ($pdfErr) redirect_back('error_message', $pdfErr);

    $uploadedAt = $pdfRel ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare('
      INSERT INTO quick_index
      (session_number, session_date, start_time, end_time, venue, agenda, status, pdf_file, pdf_original_name, pdf_uploaded_at)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->bind_param(
      'ssssssssss',
      $session_number,
      $session_date,
      $start_time,
      $end_time,
      $venue,
      $agenda,
      $status,
      $pdfRel,
      $pdfOriginal,
      $uploadedAt
    );

    if ($stmt->execute()) {
      $stmt->close();
      redirect_back('success_message', 'Session added successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error adding session: {$err}");
  }

  // ✅ UPDATE
  if ($action === 'update_session') {
    $id             = (int)($_POST['id'] ?? 0);
    $session_number = trim($_POST['session_number'] ?? '');
    $session_date   = trim($_POST['session_date'] ?? '');
    $start_time     = trim($_POST['start_time'] ?? '');
    $end_time       = trim($_POST['end_time'] ?? '');
    $venue          = trim($_POST['venue'] ?? '');
    $agenda         = trim($_POST['agenda'] ?? '');
    $status         = trim($_POST['status'] ?? 'scheduled');

    if ($id <= 0) redirect_back('error_message', 'Invalid session ID.');
    if ($session_number === '' || $session_date === '' || $start_time === '' || $end_time === '' || $venue === '' || $status === '') {
      redirect_back('error_message', 'Please fill in all required fields.');
    }

    // Load old PDF path for replacement
    $oldPdf = null;
    $oldStmt = $conn->prepare("SELECT pdf_file FROM quick_index WHERE id=? LIMIT 1");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldRes = $oldStmt->get_result();
    if ($oldRes && $oldRes->num_rows) $oldPdf = $oldRes->fetch_assoc()['pdf_file'] ?? null;
    $oldStmt->close();

    // New PDF upload (optional)
    [$pdfRel, $pdfOriginal, $pdfErr] = upload_pdf('pdf_file', $uploadDirAbs, $uploadDirRel);
    if ($pdfErr) redirect_back('error_message', $pdfErr);

    if ($pdfRel) {
      if (!empty($oldPdf)) delete_file_if_exists($oldPdf);

      $uploadedAt = date('Y-m-d H:i:s');

      $stmt = $conn->prepare('
        UPDATE quick_index
        SET session_number=?, session_date=?, start_time=?, end_time=?, venue=?, agenda=?, status=?,
            pdf_file=?, pdf_original_name=?, pdf_uploaded_at=?
        WHERE id=?
      ');
      $stmt->bind_param(
        'ssssssssssi',
        $session_number,
        $session_date,
        $start_time,
        $end_time,
        $venue,
        $agenda,
        $status,
        $pdfRel,
        $pdfOriginal,
        $uploadedAt,
        $id
      );
    } else {
      $stmt = $conn->prepare('
        UPDATE quick_index
        SET session_number=?, session_date=?, start_time=?, end_time=?, venue=?, agenda=?, status=?
        WHERE id=?
      ');
      $stmt->bind_param('sssssssi', $session_number, $session_date, $start_time, $end_time, $venue, $agenda, $status, $id);
    }

    if ($stmt->execute()) {
      $stmt->close();
      redirect_back('success_message', 'Session updated successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error updating session: {$err}");
  }

  // ✅ DELETE
  if ($action === 'delete_session') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) redirect_back('error_message', 'Invalid session ID.');

    $oldPdf = null;
    $oldStmt = $conn->prepare("SELECT pdf_file FROM quick_index WHERE id=? LIMIT 1");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldRes = $oldStmt->get_result();
    if ($oldRes && $oldRes->num_rows) $oldPdf = $oldRes->fetch_assoc()['pdf_file'] ?? null;
    $oldStmt->close();

    $stmt = $conn->prepare('DELETE FROM quick_index WHERE id=?');
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
      $stmt->close();
      if (!empty($oldPdf)) delete_file_if_exists($oldPdf);
      redirect_back('success_message', 'Session deleted successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error deleting session: {$err}");
  }
}

// -----------------------------
// Stats
// -----------------------------
$total = 0; $completed = 0; $ongoing = 0; $scheduled = 0; $cancelled = 0;
$res = $conn->query("SELECT status, COUNT(*) c FROM quick_index GROUP BY status");
if ($res) {
  while ($r = $res->fetch_assoc()) {
    $total += (int)$r['c'];
    switch ($r['status']) {
      case 'completed': $completed = (int)$r['c']; break;
      case 'ongoing': $ongoing = (int)$r['c']; break;
      case 'scheduled': $scheduled = (int)$r['c']; break;
      case 'cancelled': $cancelled = (int)$r['c']; break;
    }
  }
}

// Latest sessions
$latest = $conn->query("SELECT id, session_number, session_date, start_time, end_time, venue, agenda, status, pdf_file, pdf_original_name FROM quick_index ORDER BY session_date DESC, start_time DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Quick Index – Legislative Summary</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
  .modal{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.55)}
  .modal.show{display:flex;align-items:center;justify-content:center}
  .card{box-shadow:0 6px 14px rgba(0,0,0,.06)}

  /* ✅ PDF Viewer sizing (works with Tailwind 2.2) */
  .pdf-dialog{
    width:95vw;
    max-width:1400px;
    height:92vh;
    display:flex;
    flex-direction:column;
  }
  .pdf-body{
    flex:1;
    min-height:0; /* IMPORTANT so iframe can stretch */
    background:#f3f4f6;
  }
  .pdf-frame{
    width:100%;
    height:100%;
    border:0;
    display:block;
  }
</style>
</head>

<body class="flex h-screen bg-gray-100">
<?php include 'bar/sidebar.php'; ?>

<div class="flex-1 flex flex-col">
<?php include 'bar/header.php'; ?>

<main class="p-6 overflow-y-auto">

<?php if (isset($_SESSION['success_message'])): ?>
  <div class="mb-4 p-4 rounded-lg border border-green-200 bg-green-50 text-green-800" id="flashMsg">
    <i class="fas fa-check-circle mr-2"></i><?php echo e($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
  </div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
  <div class="mb-4 p-4 rounded-lg border border-red-200 bg-red-50 text-red-800" id="flashMsg">
    <i class="fas fa-exclamation-circle mr-2"></i><?php echo e($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
  </div>
<?php endif; ?>

<div class="mb-6 flex items-start justify-between gap-4">
  <div>
    <h1 class="text-2xl font-bold text-gray-800">Quick Index</h1>
  </div>
  <button onclick="openAddModal()" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">
    <i class="fas fa-plus mr-2"></i>Add Quick Index
  </button>
</div>

<!-- Stats -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
  <div class="bg-white p-4 rounded-lg card"><p class="text-sm text-gray-500">Total</p><p class="text-2xl font-bold"><?php echo (int)$total; ?></p></div>
  <div class="bg-green-50 border border-green-100 p-4 rounded-lg card"><p class="text-sm text-green-700">Completed</p><p class="text-2xl font-bold text-green-800"><?php echo (int)$completed; ?></p></div>
  <div class="bg-blue-50 border border-blue-100 p-4 rounded-lg card"><p class="text-sm text-blue-700">Ongoing</p><p class="text-2xl font-bold text-blue-800"><?php echo (int)$ongoing; ?></p></div>
  <div class="bg-yellow-50 border border-yellow-100 p-4 rounded-lg card"><p class="text-sm text-yellow-700">Scheduled</p><p class="text-2xl font-bold text-yellow-800"><?php echo (int)$scheduled; ?></p></div>
  <div class="bg-red-50 border border-red-100 p-4 rounded-lg card"><p class="text-sm text-red-700">Cancelled</p><p class="text-2xl font-bold text-red-800"><?php echo (int)$cancelled; ?></p></div>
</div>

<!-- Latest sessions + CRUD -->
<div class="bg-white rounded-lg card">
  <div class="p-4 border-b flex items-center justify-between">
    <div class="font-semibold">Latest Sessions</div>
    <a href="session.php" class="text-sm text-blue-700 hover:text-blue-900 font-semibold">
      Open Full Sessions <i class="fas fa-arrow-right ml-1"></i>
    </a>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-3 text-left">Session #</th>
          <th class="p-3 text-left">Date</th>
          <th class="p-3 text-left">Time</th>
          <th class="p-3 text-left">Venue</th>
          <th class="p-3 text-left">Status</th>
          <th class="p-3 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($latest && $latest->num_rows > 0): while($row = $latest->fetch_assoc()): ?>
          <?php
            $status = $row['status'] ?? 'scheduled';
            $badge = 'bg-yellow-100 text-yellow-800';
            if ($status === 'completed') $badge = 'bg-green-100 text-green-800';
            if ($status === 'ongoing') $badge = 'bg-blue-100 text-blue-800';
            if ($status === 'cancelled') $badge = 'bg-red-100 text-red-800';
          ?>
          <tr class="border-t hover:bg-gray-50">
            <td class="p-3 font-semibold text-gray-900"><?php echo e($row['session_number']); ?></td>
            <td class="p-3"><?php echo date('M d, Y', strtotime($row['session_date'])); ?></td>
            <td class="p-3"><?php echo date('g:i A', strtotime($row['start_time'])) . ' - ' . date('g:i A', strtotime($row['end_time'])); ?></td>
            <td class="p-3"><?php echo e($row['venue']); ?></td>
            <td class="p-3"><span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold capitalize <?php echo $badge; ?>"><?php echo e($status); ?></span></td>
            <td class="p-3 whitespace-nowrap">
              <?php if (!empty($row['pdf_file'])): ?>
                <button class="text-blue-700 hover:text-blue-900 mr-3" type="button"
                        title="<?php echo e($row['pdf_original_name'] ?: 'View PDF'); ?>"
                        onclick="openPdfViewer(<?php echo (int)$row['id']; ?>)">
                  <i class="fas fa-file-pdf"></i>
                </button>
              <?php else: ?>
                <span class="text-gray-300 mr-3" title="No PDF"><i class="fas fa-file-pdf"></i></span>
              <?php endif; ?>

              <button class="text-green-700 hover:text-green-900 mr-3" title="Edit" type="button"
                      onclick='openEditModal(<?php echo json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>)'>
                <i class="fas fa-edit"></i>
              </button>

              <button class="text-red-700 hover:text-red-900" title="Delete" type="button"
                      onclick="openDeleteModal(<?php echo (int)$row['id']; ?>, '<?php echo e($row['session_number']); ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="6" class="p-6 text-center text-gray-500">No sessions yet</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</main>
</div>

<!-- =========================
     PDF VIEWER MODAL (same tab)
========================= -->
<div id="pdfModal" class="modal">
  <div class="bg-white rounded-lg pdf-dialog">
    <div class="p-4 border-b flex items-center justify-between">
      <div class="font-bold text-gray-800">
        <i class="fas fa-file-pdf mr-2 text-red-600"></i>PDF Viewer
      </div>
      <button type="button" onclick="closePdfViewer()" class="text-gray-400 hover:text-gray-700">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="pdf-body">
      <iframe id="pdfFrame" class="pdf-frame" src=""></iframe>
    </div>

    <div class="p-4 border-t flex justify-end">
      <button type="button" onclick="closePdfViewer()" class="px-4 py-2 rounded-lg border">Close</button>
    </div>
  </div>
</div>

<!-- =========================
     ADD MODAL
========================= -->
<div id="addModal" class="modal">
  <div class="bg-white rounded-lg w-11/12 max-w-xl">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-gray-800">Add Session</div>
      <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>

    <form class="p-5" method="POST" action="quick_index.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_session">

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Session Number</label>
        <input class="w-full border rounded-lg px-3 py-2" name="session_number" required placeholder="e.g., #14">
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
          <input type="date" class="w-full border rounded-lg px-3 py-2" name="session_date" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <select class="w-full border rounded-lg px-3 py-2" name="status" required>
            <option value="scheduled">Scheduled</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
          <input type="time" class="w-full border rounded-lg px-3 py-2" name="start_time" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
          <input type="time" class="w-full border rounded-lg px-3 py-2" name="end_time" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Venue</label>
        <input class="w-full border rounded-lg px-3 py-2" name="venue" required placeholder="e.g., Session Hall">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Agenda / Description</label>
        <textarea class="w-full border rounded-lg px-3 py-2" name="agenda" rows="4" placeholder="Enter agenda"></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Attach PDF (optional)</label>
        <input type="file" name="pdf_file" accept="application/pdf,.pdf" class="w-full border rounded-lg px-3 py-2 bg-white">
        <p class="text-xs text-gray-500 mt-1">PDF only. Max 10MB.</p>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeAddModal()" class="px-4 py-2 rounded-lg border">Cancel</button>
        <button class="px-4 py-2 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold">
          <i class="fas fa-save mr-2"></i>Save
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================
     EDIT MODAL
========================= -->
<div id="editModal" class="modal">
  <div class="bg-white rounded-lg w-11/12 max-w-xl">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-gray-800">Edit Session</div>
      <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>

    <form class="p-5" method="POST" action="quick_index.php" id="editForm" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_session">
      <input type="hidden" name="id" id="edit_id">

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Session Number</label>
        <input class="w-full border rounded-lg px-3 py-2" name="session_number" id="edit_session_number" required>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
          <input type="date" class="w-full border rounded-lg px-3 py-2" name="session_date" id="edit_session_date" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
          <select class="w-full border rounded-lg px-3 py-2" name="status" id="edit_status" required>
            <option value="scheduled">Scheduled</option>
            <option value="ongoing">Ongoing</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Start Time</label>
          <input type="time" class="w-full border rounded-lg px-3 py-2" name="start_time" id="edit_start_time" required>
        </div>
        <div class="mb-4">
          <label class="block text-sm font-semibold text-gray-700 mb-1">End Time</label>
          <input type="time" class="w-full border rounded-lg px-3 py-2" name="end_time" id="edit_end_time" required>
        </div>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Venue</label>
        <input class="w-full border rounded-lg px-3 py-2" name="venue" id="edit_venue" required>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Agenda / Description</label>
        <textarea class="w-full border rounded-lg px-3 py-2" name="agenda" id="edit_agenda" rows="4"></textarea>
      </div>

      <div class="mb-4">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Replace PDF (optional)</label>
          <span class="text-xs text-gray-500" id="edit_pdf_hint"></span>
        </div>
        <input type="file" name="pdf_file" accept="application/pdf,.pdf" class="w-full border rounded-lg px-3 py-2 bg-white">
        <p class="text-xs text-gray-500 mt-1">Upload a new PDF to replace the current one. Max 10MB.</p>
      </div>

      <div class="flex justify-end gap-2 pt-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-lg border">Cancel</button>
        <button class="px-4 py-2 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold">
          <i class="fas fa-save mr-2"></i>Update
        </button>
      </div>
    </form>
  </div>
</div>

<!-- =========================
     DELETE MODAL
========================= -->
<div id="deleteModal" class="modal">
  <div class="bg-white rounded-lg w-11/12 max-w-md">
    <div class="p-5 border-b flex items-center justify-between">
      <div class="font-bold text-gray-800">Delete Session</div>
      <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5">
      <p class="text-gray-600" id="deleteText">Are you sure?</p>
      <form method="POST" action="quick_index.php" class="mt-5">
        <input type="hidden" name="action" value="delete_session">
        <input type="hidden" name="id" id="delete_id">
        <div class="flex justify-end gap-2">
          <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded-lg border">Cancel</button>
          <button class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white font-semibold">
            <i class="fas fa-trash mr-2"></i>Delete
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Flash auto-hide
  (function(){
    const el = document.getElementById('flashMsg');
    if (!el) return;
    setTimeout(()=>{ el.style.display = 'none'; }, 3500);
  })();

  // Add modal
  function openAddModal(){ document.getElementById('addModal').classList.add('show'); }
  function closeAddModal(){ document.getElementById('addModal').classList.remove('show'); }

  // Edit modal
  function openEditModal(row){
    document.getElementById('edit_id').value = row.id || '';
    document.getElementById('edit_session_number').value = row.session_number || '';
    document.getElementById('edit_session_date').value = (row.session_date || '').slice(0,10);
    document.getElementById('edit_start_time').value = (row.start_time || '').slice(0,5);
    document.getElementById('edit_end_time').value = (row.end_time || '').slice(0,5);
    document.getElementById('edit_venue').value = row.venue || '';
    document.getElementById('edit_agenda').value = row.agenda || '';
    document.getElementById('edit_status').value = row.status || 'scheduled';

    const hint = document.getElementById('edit_pdf_hint');
    if (row.pdf_original_name) hint.textContent = "Current: " + row.pdf_original_name;
    else if (row.pdf_file) hint.textContent = "Current PDF attached";
    else hint.textContent = "No PDF attached";

    document.getElementById('editModal').classList.add('show');
  }
  function closeEditModal(){ document.getElementById('editModal').classList.remove('show'); }

  // Delete modal
  function openDeleteModal(id, sessionNumber){
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteText').textContent = `Are you sure you want to delete Session ${sessionNumber}? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
  }
  function closeDeleteModal(){ document.getElementById('deleteModal').classList.remove('show'); }

  // ✅ PDF Viewer (same tab) — FIXED hash
  function openPdfViewer(id){
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    frame.src = 'view_pdf/view_pdf_quick_index.php?id=' + encodeURIComponent(id) + '#zoom=page-width';
    modal.classList.add('show');
  }
  function closePdfViewer(){
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    frame.src = '';
    modal.classList.remove('show');
  }

  // Click outside to close
  window.addEventListener('click', function(e){
    const addM = document.getElementById('addModal');
    const editM = document.getElementById('editModal');
    const delM = document.getElementById('deleteModal');
    const pdfM = document.getElementById('pdfModal');
    if (e.target === addM) closeAddModal();
    if (e.target === editM) closeEditModal();
    if (e.target === delM) closeDeleteModal();
    if (e.target === pdfM) closePdfViewer();
  });
</script>

</body>
</html>
