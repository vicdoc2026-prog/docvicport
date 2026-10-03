<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// -----------------------------
// Helpers
// -----------------------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect_back($msgKey = null, $msg = null){
  if ($msgKey && $msg) $_SESSION[$msgKey] = $msg;
  header('Location: notes.php');
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

// Max 50MB
if ($size > 50 * 1024 * 1024) {
    return [null, null, "PDF is too large. Max 50MB."];
}

  $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
  if ($ext !== 'pdf') return [null, null, "Only PDF files are allowed."];

  // Best-effort MIME check
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

  $safeBase = bin2hex(random_bytes(16));
  $finalName = $safeBase . '.pdf';

  $destAbs = rtrim($uploadDirAbs, '/\\') . DIRECTORY_SEPARATOR . $finalName;
  $destRel = rtrim($uploadDirRel, '/\\') . '/' . $finalName;

  if (!move_uploaded_file($tmp, $destAbs)) return [null, null, "Failed to save uploaded file."];

  return [$destRel, $name, null];
}

function delete_file_if_exists($relPath){
  if (!$relPath) return;
  $relPath = str_replace(["\0", '..\\','../'], '', $relPath);
  $abs = __DIR__ . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relPath);
  if (is_file($abs)) @unlink($abs);
}

// -----------------------------
// CRUD (POST)
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  $action = $_POST['action'];

  // Upload settings
  $uploadDirRel = 'uploads/notes_pdfs';
  $uploadDirAbs = __DIR__ . DIRECTORY_SEPARATOR . $uploadDirRel;

  // ✅ CREATE
  if ($action === 'add_note') {
    $title = trim($_POST['title'] ?? '');
    $note_date = trim($_POST['note_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($title === '' || $note_date === '') {
      redirect_back('error_message', 'Please fill in Title and Date.');
    }

    // PDF upload (optional)
    [$pdfRel, $pdfOriginal, $pdfErr] = upload_pdf('pdf_file', $uploadDirAbs, $uploadDirRel);
    if ($pdfErr) redirect_back('error_message', $pdfErr);

    $uploadedAt = $pdfRel ? date('Y-m-d H:i:s') : null;

    $stmt = $conn->prepare("
      INSERT INTO notes (title, note_date, description, pdf_file, pdf_original_name, pdf_uploaded_at)
      VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('ssssss', $title, $note_date, $description, $pdfRel, $pdfOriginal, $uploadedAt);

    if ($stmt->execute()) {
      $stmt->close();
      redirect_back('success_message', 'Note added successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error adding note: {$err}");
  }

  // ✅ UPDATE
  if ($action === 'update_note') {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $note_date = trim($_POST['note_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($id <= 0) redirect_back('error_message', 'Invalid note ID.');
    if ($title === '' || $note_date === '') redirect_back('error_message', 'Please fill in Title and Date.');

    // old pdf
    $oldPdf = null;
    $oldStmt = $conn->prepare("SELECT pdf_file FROM notes WHERE id=? LIMIT 1");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldRes = $oldStmt->get_result();
    if ($oldRes && $oldRes->num_rows) $oldPdf = $oldRes->fetch_assoc()['pdf_file'] ?? null;
    $oldStmt->close();

    // new pdf (optional)
    [$pdfRel, $pdfOriginal, $pdfErr] = upload_pdf('pdf_file', $uploadDirAbs, $uploadDirRel);
    if ($pdfErr) redirect_back('error_message', $pdfErr);

    $now = date('Y-m-d H:i:s');

    if ($pdfRel) {
      if (!empty($oldPdf)) delete_file_if_exists($oldPdf);
      $uploadedAt = $now;

      $stmt = $conn->prepare("
        UPDATE notes
        SET title=?, note_date=?, description=?,
            pdf_file=?, pdf_original_name=?, pdf_uploaded_at=?,
            updated_at=?
        WHERE id=?
      ");
      $stmt->bind_param('sssssssi', $title, $note_date, $description, $pdfRel, $pdfOriginal, $uploadedAt, $now, $id);
    } else {
      $stmt = $conn->prepare("
        UPDATE notes
        SET title=?, note_date=?, description=?, updated_at=?
        WHERE id=?
      ");
      $stmt->bind_param('ssssi', $title, $note_date, $description, $now, $id);
    }

    if ($stmt->execute()) {
      $stmt->close();
      redirect_back('success_message', 'Note updated successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error updating note: {$err}");
  }

  // ✅ DELETE
  if ($action === 'delete_note') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) redirect_back('error_message', 'Invalid note ID.');

    $oldPdf = null;
    $oldStmt = $conn->prepare("SELECT pdf_file FROM notes WHERE id=? LIMIT 1");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldRes = $oldStmt->get_result();
    if ($oldRes && $oldRes->num_rows) $oldPdf = $oldRes->fetch_assoc()['pdf_file'] ?? null;
    $oldStmt->close();

    $stmt = $conn->prepare("DELETE FROM notes WHERE id=?");
    $stmt->bind_param('i', $id);

    if ($stmt->execute()) {
      $stmt->close();
      if (!empty($oldPdf)) delete_file_if_exists($oldPdf);
      redirect_back('success_message', 'Note deleted successfully!');
    }
    $err = $conn->error;
    $stmt->close();
    redirect_back('error_message', "Error deleting note: {$err}");
  }
}

// -----------------------------
// Fetch notes
// -----------------------------
$notes = $conn->query("SELECT id, title, note_date, description, pdf_file, pdf_original_name FROM notes ORDER BY note_date DESC, id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notes</title>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
  .modal{display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.55)}
  .modal.show{display:flex;align-items:center;justify-content:center}
  .card{box-shadow:0 6px 14px rgba(0,0,0,.06)}

  /* PDF Viewer sizing (Tailwind 2.2 safe) */
  .pdf-dialog{ width:95vw; max-width:1400px; height:92vh; display:flex; flex-direction:column; }
  .pdf-body{ flex:1; min-height:0; background:#f3f4f6; }
  .pdf-frame{ width:100%; height:100%; border:0; display:block; }
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
    <h1 class="text-2xl font-bold text-gray-800">Notes</h1>
    <p class="text-sm text-gray-500 mt-1">Create notes with optional PDF attachments.</p>
  </div>

  <button onclick="openAddModal()" class="bg-blue-700 hover:bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-semibold">
    <i class="fas fa-plus mr-2"></i>Add Note
  </button>
</div>

<div class="bg-white rounded-lg card">
  <div class="p-4 border-b flex items-center justify-between">
    <div class="font-semibold">Latest Notes</div>
  </div>

  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="p-3 text-left">Title</th>
          <th class="p-3 text-left">Date</th>
          <th class="p-3 text-left">Description</th>
          <th class="p-3 text-left">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($notes && $notes->num_rows > 0): while($row = $notes->fetch_assoc()): ?>
          <tr class="border-t hover:bg-gray-50">
            <td class="p-3 font-semibold text-gray-900"><?php echo e($row['title']); ?></td>
            <td class="p-3"><?php echo date('M d, Y', strtotime($row['note_date'])); ?></td>
            <td class="p-3 text-gray-700">
              <?php
                $desc = trim((string)($row['description'] ?? ''));
                echo e(mb_strimwidth($desc, 0, 120, '...'));
              ?>
            </td>
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

              <button class="text-green-700 hover:text-green-900 mr-3" type="button" title="Edit"
                      onclick='openEditModal(<?php echo json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); ?>)'>
                <i class="fas fa-edit"></i>
              </button>

              <button class="text-red-700 hover:text-red-900" type="button" title="Delete"
                      onclick="openDeleteModal(<?php echo (int)$row['id']; ?>, '<?php echo e($row['title']); ?>')">
                <i class="fas fa-trash"></i>
              </button>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="4" class="p-6 text-center text-gray-500">No notes yet</td></tr>
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
      <div class="font-bold text-gray-800">Add Note</div>
      <button onclick="closeAddModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>

    <form class="p-5" method="POST" action="notes.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="add_note">

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
        <input class="w-full border rounded-lg px-3 py-2" name="title" required placeholder="Note title">
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
        <input type="date" class="w-full border rounded-lg px-3 py-2" name="note_date" required>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
        <textarea class="w-full border rounded-lg px-3 py-2" name="description" rows="4" placeholder="Write details..."></textarea>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Attach PDF (optional)</label>
        <input type="file" name="pdf_file" accept="application/pdf,.pdf" class="w-full border rounded-lg px-3 py-2 bg-white">
        <p class="text-xs text-gray-500 mt-1">PDF only. Max 50MB.</p>
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
      <div class="font-bold text-gray-800">Edit Note</div>
      <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>

    <form class="p-5" method="POST" action="notes.php" enctype="multipart/form-data">
      <input type="hidden" name="action" value="update_note">
      <input type="hidden" name="id" id="edit_id">

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Title</label>
        <input class="w-full border rounded-lg px-3 py-2" name="title" id="edit_title" required>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Date</label>
        <input type="date" class="w-full border rounded-lg px-3 py-2" name="note_date" id="edit_note_date" required>
      </div>

      <div class="mb-4">
        <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
        <textarea class="w-full border rounded-lg px-3 py-2" name="description" id="edit_description" rows="4"></textarea>
      </div>

      <div class="mb-4">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-semibold text-gray-700 mb-1">Replace PDF (optional)</label>
          <span class="text-xs text-gray-500" id="edit_pdf_hint"></span>
        </div>
        <input type="file" name="pdf_file" accept="application/pdf,.pdf" class="w-full border rounded-lg px-3 py-2 bg-white">
        <p class="text-xs text-gray-500 mt-1">Upload a new PDF to replace the current one.</p>
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
      <div class="font-bold text-gray-800">Delete Note</div>
      <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-700"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-5">
      <p class="text-gray-600" id="deleteText">Are you sure?</p>
      <form method="POST" action="notes.php" class="mt-5">
        <input type="hidden" name="action" value="delete_note">
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
    document.getElementById('edit_title').value = row.title || '';
    document.getElementById('edit_note_date').value = (row.note_date || '').slice(0,10);
    document.getElementById('edit_description').value = row.description || '';

    const hint = document.getElementById('edit_pdf_hint');
    if (row.pdf_original_name) hint.textContent = "Current: " + row.pdf_original_name;
    else if (row.pdf_file) hint.textContent = "Current PDF attached";
    else hint.textContent = "No PDF attached";

    document.getElementById('editModal').classList.add('show');
  }
  function closeEditModal(){ document.getElementById('editModal').classList.remove('show'); }

  // Delete modal
  function openDeleteModal(id, title){
    document.getElementById('delete_id').value = id;
    document.getElementById('deleteText').textContent = `Are you sure you want to delete "${title}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('show');
  }
  function closeDeleteModal(){ document.getElementById('deleteModal').classList.remove('show'); }

  // PDF viewer
  function openPdfViewer(id){
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    frame.src = 'view_pdf/view_pdf_notes.php?id=' + encodeURIComponent(id) + '#zoom=page-width';
    modal.classList.add('show');
  }
  function closePdfViewer(){
    const modal = document.getElementById('pdfModal');
    const frame = document.getElementById('pdfFrame');
    frame.src = '';
    modal.classList.remove('show');
  }

  // Click outside close
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
