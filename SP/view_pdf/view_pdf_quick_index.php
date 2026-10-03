<?php
require_once __DIR__ . '/../config/check-session.php';
require_once __DIR__ . '/../config/conn.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); exit('Invalid ID'); }

$stmt = $conn->prepare("SELECT pdf_file, pdf_original_name FROM quick_index WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

$pdf = $row['pdf_file'] ?? '';
if (!$pdf) { http_response_code(404); exit('No PDF attached.'); }

// sanitize
$pdf = str_replace(["\0", '..\\', '../'], '', $pdf);
$pdf = ltrim($pdf, "/\\"); // important

// ✅ BASE DIR = parent folder of /view_pdf
$baseDir = realpath(__DIR__ . '/..'); // points to your project root folder
$abs = $baseDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pdf);

if (!is_file($abs)) {
  http_response_code(404);
  exit('File not found.');
}

$filename = $row['pdf_original_name'] ?: 'document.pdf';
$filename = preg_replace('/[^a-zA-Z0-9_\-. ]+/', '', $filename);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: inline; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');

readfile($abs);
exit;
