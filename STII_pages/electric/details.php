<?php 
require_once 'config.php'; // This should define $conn = new mysqli(...)

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get bill ID from URL
$billId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($billId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch bill details with location info
try {
    $stmt = $conn->prepare("
        SELECT b.*, l.location_name, l.location_class 
        FROM bills b 
        INNER JOIN locations l ON b.location_id = l.id 
        WHERE b.id = ?
    ");
    $stmt->bind_param("i", $billId);
    $stmt->execute();
    $result = $stmt->get_result();
    $bill = $result->fetch_assoc();
    $stmt->close();
    
    if (!$bill) {
        header('Location: dashboard.php');
        exit;
    }
    
    // Fetch scanned documents for this bill
    $docStmt = $conn->prepare("SELECT * FROM bill_documents WHERE bill_id = ? ORDER BY uploaded_at DESC");
    $docStmt->bind_param("i", $billId);
    $docStmt->execute();
    $docResult = $docStmt->get_result();
    $documents = [];
    while ($doc = $docResult->fetch_assoc()) {
        $documents[] = $doc;
    }
    $docStmt->close();
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bill Details - <?= htmlspecialchars($bill['location_name']) ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;--secondary:#10b981;--danger:#ef4444;--warning:#f59e0b;--purple:#8b5cf6;--light:#f3f4f6;--dark:#1f2937;--gray:#6b7280;--light-gray:#e5e7eb;
    }
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
    body{background:#f9fafb;color:var(--dark);min-height:100vh}
    
    .top-nav{background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100}
    .logo{display:flex;align-items:center;gap:10px}
    .logo h1{font-family:'Orbitron',sans-serif;font-size:24px;font-weight:700;letter-spacing:1px;color:var(--primary)}
    .back-btn{padding:10px 15px;display:flex;align-items:center;gap:8px;color:var(--gray);text-decoration:none;border-radius:5px;transition:all .3s}
    .back-btn:hover{background:#f1f5f9;color:var(--primary)}
    
    .main-content{max-width:1200px;margin:0 auto;padding:30px}
    .page-header{margin-bottom:30px}
    .page-title{font-size:28px;font-weight:700;color:var(--dark);margin-bottom:8px;display:flex;align-items:center;gap:12px}
    .page-subtitle{color:var(--gray);font-size:14px}
    
    .card{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px;border:1px solid #f0f0f0}
    .card-title{font-size:18px;font-weight:700;color:var(--dark);margin-bottom:20px;padding-bottom:12px;border-bottom:2px solid var(--light-gray);display:flex;align-items:center;gap:10px}
    
    .info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px}
    .info-item{padding:16px;background:#f9fafb;border-radius:8px;border-left:4px solid var(--primary)}
    .info-label{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:var(--gray);font-weight:600;margin-bottom:6px}
    .info-value{font-size:18px;font-weight:700;color:var(--dark)}
    .info-value.large{font-size:24px;color:var(--primary)}
    
    .location-badge{padding:8px 16px;border-radius:20px;font-size:14px;font-weight:600;display:inline-block}
    .location-belvic{background:#dbeafe;color:var(--primary)}
    .location-stii-main{background:#d1fae5;color:var(--secondary)}
    .location-stii-pangi{background:#fef3c7;color:var(--warning)}
    .location-stii-sanito{background:#ede9fe;color:var(--purple)}
    
    .documents-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px}
    .document-card{background:#fff;border:2px solid var(--light-gray);border-radius:10px;overflow:hidden;transition:all .3s;cursor:pointer}
    .document-card:hover{border-color:var(--primary);box-shadow:0 8px 16px rgba(37,99,235,.15);transform:translateY(-4px)}
    .document-preview{width:100%;height:200px;background:#f9fafb;display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
    .document-preview img{width:100%;height:100%;object-fit:cover}
    .document-preview .file-icon{font-size:64px;color:var(--primary);opacity:.3}
    .document-info{padding:16px}
    .document-name{font-weight:600;color:var(--dark);margin-bottom:8px;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .document-meta{font-size:12px;color:var(--gray);display:flex;align-items:center;gap:8px;margin-bottom:4px}
    .document-actions{display:flex;gap:8px;margin-top:12px}
    
    .btn{padding:10px 16px;border:none;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s;display:inline-flex;align-items:center;gap:8px;font-size:14px;text-decoration:none}
    .btn-primary{background:var(--primary);color:#fff}
    .btn-primary:hover{background:var(--primary-dark);transform:translateY(-1px)}
    .btn-success{background:var(--secondary);color:#fff}
    .btn-success:hover{background:#059669}
    .btn-danger{background:var(--danger);color:#fff}
    .btn-danger:hover{background:#dc2626}
    .btn-sm{padding:6px 12px;font-size:13px}
    
    .empty-state{text-align:center;padding:60px 20px;color:var(--gray)}
    .empty-state i{font-size:64px;opacity:.3;margin-bottom:16px;display:block}
    .empty-state h3{font-size:18px;margin-bottom:8px;color:var(--dark)}
    
    .upload-section{background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border:2px dashed var(--primary);border-radius:12px;padding:30px;text-align:center;margin-bottom:20px}
    .upload-section input[type="file"]{display:none}
    .upload-label{display:inline-flex;align-items:center;gap:10px;padding:12px 24px;background:var(--primary);color:#fff;border-radius:8px;font-weight:600;cursor:pointer;transition:all .2s}
    .upload-label:hover{background:var(--primary-dark);transform:translateY(-2px)}
    
    /* Modal for image preview */
    .modal{position:fixed;inset:0;background:rgba(0,0,0,.9);display:none;align-items:center;justify-content:center;z-index:1000;padding:20px}
    .modal.show{display:flex}
    .modal-content{max-width:90vw;max-height:90vh;position:relative}
    .modal-content img{max-width:100%;max-height:90vh;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,.5)}
    .modal-close{position:absolute;top:-40px;right:0;background:none;border:none;color:#fff;font-size:32px;cursor:pointer;padding:8px;transition:all .2s}
    .modal-close:hover{transform:scale(1.2)}
    
    .toast{position:fixed;right:16px;bottom:16px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 8px 24px rgba(0,0,0,.15);padding:12px 16px;border-radius:10px;font-weight:600;z-index:2000;display:none}
    .toast.show{display:block;animation:slideIn .3s ease}
    .toast.success{border-color:#86efac;color:#065f46}
    .toast.error{border-color:#fecaca;color:#991b1b}
    
    @keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}
    
    @media (max-width:768px){
      .main-content{padding:15px}
      .info-grid{grid-template-columns:1fr}
      .documents-grid{grid-template-columns:1fr}
      .page-title{font-size:22px}
    }
  </style>
</head>
<body>
  <nav class="top-nav">
    <a href="dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    <div class="logo">
      <i class="fas fa-bolt" style="color:var(--primary);font-size:28px"></i>
      <h1>STII ELECTRIC BILL</h1>
    </div>
    <div></div>
  </nav>

  <div class="main-content">
    <div class="page-header">
      <div class="page-title">
        <i class="fas fa-file-invoice-dollar" style="color:var(--primary)"></i>
        Bill Details
      </div>
      <div class="page-subtitle">Complete information and scanned documents for this electric bill</div>
    </div>

    <div class="card">
      <div class="card-title">
        <i class="fas fa-info-circle" style="color:var(--primary)"></i>
        Bill Information
      </div>
      <div class="info-grid">
        <div class="info-item">
          <div class="info-label">Location</div>
          <div class="info-value">
            <span class="location-badge <?= htmlspecialchars($bill['location_class']) ?>">
              <?= htmlspecialchars($bill['location_name']) ?>
            </span>
          </div>
        </div>
        <div class="info-item">
          <div class="info-label">Due Date</div>
          <div class="info-value"><?= date('F j, Y', strtotime($bill['due_date'])) ?></div>
        </div>
        <div class="info-item">
          <div class="info-label">kWh Usage</div>
          <div class="info-value large"><?= number_format($bill['kwh'], 2) ?> <span style="font-size:16px;color:var(--gray)">kWh</span></div>
        </div>
        <div class="info-item">
          <div class="info-label">Total Amount</div>
          <div class="info-value large">₱ <?= number_format($bill['amount'], 2) ?></div>
        </div>
      </div>
    </div>

    <div class="upload-section">
      <i class="fas fa-cloud-upload-alt" style="font-size:48px;color:var(--primary);margin-bottom:16px"></i>
      <h3 style="margin-bottom:8px;color:var(--dark)">Upload Scanned Documents</h3>
      <p style="color:var(--gray);margin-bottom:20px;font-size:14px">Upload images or PDFs of the bill (max 10MB per file)</p>
      <form id="uploadForm" enctype="multipart/form-data">
        <input type="file" id="fileInput" name="documents[]" multiple accept="image/*,application/pdf">
        <label for="fileInput" class="upload-label">
          <i class="fas fa-file-upload"></i>
          Choose Files
        </label>
      </form>
      <div id="uploadStatus" style="margin-top:16px;font-size:14px;color:var(--gray)"></div>
    </div>

    <div class="card">
      <div class="card-title">
        <i class="fas fa-folder-open" style="color:var(--primary)"></i>
        Scanned Documents
        <span style="margin-left:auto;font-size:14px;font-weight:600;color:var(--gray)"><?= count($documents) ?> file(s)</span>
      </div>

      <?php if (empty($documents)): ?>
        <div class="empty-state">
          <i class="fas fa-file-alt"></i>
          <h3>No Documents Uploaded Yet</h3>
          <p>Upload scanned copies of this bill using the upload section above</p>
        </div>
      <?php else: ?>
        <div class="documents-grid">
          <?php foreach ($documents as $doc): ?>
            <div class="document-card" onclick="viewDocument(<?= $doc['id'] ?>)">
              <div class="document-preview">
                <?php
                $fileExt = strtolower(pathinfo($doc['file_name'], PATHINFO_EXTENSION));
                if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])):
                ?>
                  <img src="<?= htmlspecialchars($doc['file_path']) ?>" alt="Document preview">
                <?php else: ?>
                  <i class="fas fa-file-pdf file-icon"></i>
                <?php endif; ?>
              </div>
              <div class="document-info">
                <div class="document-name" title="<?= htmlspecialchars($doc['file_name']) ?>">
                  <?= htmlspecialchars($doc['file_name']) ?>
                </div>
                <div class="document-meta">
                  <i class="fas fa-calendar"></i>
                  <?= date('M j, Y', strtotime($doc['uploaded_at'])) ?>
                </div>
                <div class="document-meta">
                  <i class="fas fa-database"></i>
                  <?= number_format($doc['file_size'] / 1024, 1) ?> KB
                </div>
                <div class="document-actions">
                  <a href="<?= htmlspecialchars($doc['file_path']) ?>" download class="btn btn-sm btn-primary" onclick="event.stopPropagation()">
                    <i class="fas fa-download"></i> Download
                  </a>
                  <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); viewDocument(<?= $doc['id'] ?>)">
                    <i class="fas fa-eye"></i> View
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); deleteDocument(<?= $doc['id'] ?>)">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Image Preview Modal -->
  <div class="modal" id="imageModal">
    <div class="modal-content">
      <button class="modal-close" onclick="closeModal()">&times;</button>
      <img id="modalImage" src="" alt="Document preview">
    </div>
  </div>

  <div class="toast" id="toast"></div>

  <script>
    const billId = <?= $billId ?>;
    const documents = <?= json_encode($documents) ?>;

    // File upload handling
    document.getElementById('fileInput').addEventListener('change', async function(e) {
      const files = e.target.files;
      if (!files.length) return;

      const formData = new FormData();
      formData.append('bill_id', billId);
      
      for (let file of files) {
        if (file.size > 10 * 1024 * 1024) {
          showToast(`${file.name} exceeds 10MB limit`, 'error');
          continue;
        }
        formData.append('documents[]', file);
      }

      const statusDiv = document.getElementById('uploadStatus');
      statusDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';

      try {
        const response = await fetch('upload_documents.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();
        
        if (result.success) {
          showToast('Documents uploaded successfully!', 'success');
          setTimeout(() => location.reload(), 1500);
        } else {
          showToast(result.message || 'Upload failed', 'error');
          statusDiv.innerHTML = '';
        }
      } catch (error) {
        showToast('Upload failed: ' + error.message, 'error');
        statusDiv.innerHTML = '';
      }

      e.target.value = '';
    });

    function viewDocument(docId) {
      const doc = documents.find(d => d.id == docId);
      if (!doc) return;

      const fileExt = doc.file_name.split('.').pop().toLowerCase();
      if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(fileExt)) {
        document.getElementById('modalImage').src = doc.file_path;
        document.getElementById('imageModal').classList.add('show');
      } else {
        window.open(doc.file_path, '_blank');
      }
    }

    async function deleteDocument(docId) {
      if (!confirm('Are you sure you want to delete this document?')) return;

      try {
        const response = await fetch('delete_document.php', {
          method: 'POST',
          headers: {'Content-Type': 'application/json'},
          body: JSON.stringify({ document_id: docId })
        });

        const result = await response.json();
        
        if (result.success) {
          showToast('Document deleted successfully!', 'success');
          setTimeout(() => location.reload(), 1000);
        } else {
          showToast(result.message || 'Delete failed', 'error');
        }
      } catch (error) {
        showToast('Delete failed: ' + error.message, 'error');
      }
    }

    function closeModal() {
      document.getElementById('imageModal').classList.remove('show');
    }

    function showToast(message, type = 'success') {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.className = `toast ${type} show`;
      setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // Close modal on backdrop click
    document.getElementById('imageModal').addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });

    // Close modal on Escape key
    window.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeModal();
    });
  </script>
</body>
</html>