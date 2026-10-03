<?php
// view_pdf/view_pdf_notes.php
require_once __DIR__ . '/../config/check-session.php';
require_once __DIR__ . '/../config/conn.php';

// -----------------------------
// Helpers
// -----------------------------
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// -----------------------------
// Validate ID
// -----------------------------
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  exit('Invalid ID');
}

// optional: force download
$download = isset($_GET['download']) && (int)$_GET['download'] === 1;

// -----------------------------
// Fetch note pdf info
// -----------------------------
$stmt = $conn->prepare("SELECT pdf_file, pdf_original_name FROM notes WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row || empty($row['pdf_file'])) {
  http_response_code(404);
  exit('PDF not found for this note.');
}

$pdfRel = (string)$row['pdf_file'];
$origName = trim((string)($row['pdf_original_name'] ?? ''));

// -----------------------------
// Security: allow only within uploads/notes_pdfs
// -----------------------------
$pdfRel = str_replace(["\0"], '', $pdfRel);
$pdfRel = str_replace(['\\'], '/', $pdfRel);

// Require it to be in your expected folder
$allowedPrefix = 'uploads/notes_pdfs/';
if (strpos($pdfRel, $allowedPrefix) !== 0) {
  http_response_code(403);
  exit('Access denied.');
}

// Build absolute path safely
$rootDir = realpath(__DIR__ . '/..'); // project root (where notes.php sits)
if ($rootDir === false) {
  http_response_code(500);
  exit('Server path error.');
}

$absPath = $rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $pdfRel);

// Resolve real path and re-check it is inside allowed dir
$realFile = realpath($absPath);
$allowedDir = realpath($rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $allowedPrefix));

if ($realFile === false || $allowedDir === false || strpos($realFile, $allowedDir) !== 0) {
  http_response_code(403);
  exit('Access denied.');
}

if (!is_file($realFile) || !is_readable($realFile)) {
  http_response_code(404);
  exit('File missing or not readable.');
}

// Ensure filename ends with .pdf
if ($origName === '' || strtolower(pathinfo($origName, PATHINFO_EXTENSION)) !== 'pdf') {
  $origName = 'note-' . $id . '.pdf';
}

// -----------------------------
// Output PDF
// -----------------------------
while (ob_get_level()) { ob_end_clean(); }

header('X-Content-Type-Options: nosniff');
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($realFile));

$disposition = $download ? 'attachment' : 'inline';

// RFC 5987 filename* for better unicode support
$filenameFallback = preg_replace('/[^A-Za-z0-9._-]+/', '_', $origName);
$filenameStar = rawurlencode($origName);

header("Content-Disposition: {$disposition}; filename=\"{$filenameFallback}\"; filename*=UTF-8''{$filenameStar}");
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

readfile($realFile);
exit;
