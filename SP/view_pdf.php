<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get session ID from URL
if (!isset($_GET['id'])) {
    die("No session ID provided.");
}

$id = intval($_GET['id']);

// Fetch PDF file info from database
$stmt = $conn->prepare("SELECT session_number, pdf_file FROM sessions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Session not found.");
}

$session = $result->fetch_assoc();
$pdf_file = $session['pdf_file'];
$session_number = htmlspecialchars($session['session_number']);
$stmt->close();

// Check if file exists
$pdf_path = "uploads/sessions/" . $pdf_file;
if (empty($pdf_file) || !file_exists($pdf_path)) {
    die("PDF file not found.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View PDF - Session <?php echo $session_number; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            background-color: #f9fafb;
        }
        .pdf-container {
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .pdf-viewer {
            flex-grow: 1;
            width: 100%;
            border: none;
        }
        .top-bar {
            background: #1a3a6c;
            color: white;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .top-bar h1 {
            font-size: 1rem;
            font-weight: 600;
        }
        .top-bar a {
            background: #e63946;
            padding: 0.4rem 0.9rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="pdf-container">
        <div class="top-bar" style="padding: 0.4rem 1rem;">
            <h1 style="font-size: 0.9rem;">Calendar <?php echo $session_number; ?> - PDF Viewer</h1>
            <a href="calendar.php" class="text-white hover:bg-red-700 transition" style="padding: 0.25rem 0.6rem; font-size: 0.8rem; border-radius: 4px;">Back</a>
        </div>
        <iframe src="<?php echo $pdf_path; ?>#zoom=page-width" class="pdf-viewer"></iframe>
    </div>
</body>
</html>
