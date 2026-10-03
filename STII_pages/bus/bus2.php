<?php
// bus2.php

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

date_default_timezone_set('Asia/Manila');

function format_date($ymd) {
    return date('M j, Y', strtotime($ymd));
}
function days_until($ymd) {
    $today = strtotime(date('Y-m-d'));
    $target = strtotime($ymd);
    return (int) floor(($target - $today) / 86400);
}
function expiry_badge($ymd) {
    $days = days_until($ymd);
    if ($days < 0)  return ['Expired',  'bg-red-100 text-red-700 ring-red-600/20'];
    if ($days <= 14) return ['Expiring', 'bg-amber-100 text-amber-700 ring-amber-600/20'];
    return ['Valid', 'bg-emerald-100 text-emerald-700 ring-emerald-600/20'];
}

$bus = [
    'id' => 'bus2',
    'name' => 'BUS 2',
    'plate' => 'NFZ1415',
    'mileage_km' => 156242,
    'image' => 'https://images.unsplash.com/photo-1477414348463-c0eb7f1359b6?q=80&w=1200&auto=format&fit=crop',
    'registration' => ['cr_no' => '46327243-4', 'expiry' => '2026-06-12'],
    'insurance' => ['provider' => 'Pioneer Insurance', 'policy_no' => 'PI-33-112233', 'expiry' => '2025-12-20'],
    'oil' => ['last_change_date' => '2025-09-01', 'last_change_mileage_km' => 153500],
    'engine_no' => 'YC6G27040GA1QAK00004',
    'chassis_no' => 'LA6A1GBF9KB401958',
    'mv_file_no' => '1301-00001492148',
    'make' => 'KING LONG',
    'type' => 'Truck/Bus',
    'jpeg_file' => '../images/NFZ1415_Truck bus BUS_001.jpg',
    'second_image' => '../images/NFZ1415_Truck bus BUS_000.jpg'
];
[$regLabel, $regBadge] = expiry_badge($bus['registration']['expiry']);
[$insLabel, $insBadge] = expiry_badge($bus['insurance']['expiry']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bus 2 Details</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <style>
    .card {background:#fff;border-radius:1rem;box-shadow:0 4px 6px rgba(0,0,0,.1);overflow:hidden;}
    .chip {display:inline-flex;align-items:center;border-radius:0.375rem;padding:0.25rem 0.75rem;font-size:0.875rem;font-weight:500;border:1px solid rgba(0,0,0,0.05);}
    .label {font-size:.875rem;color:#6b7280;}
    .value {font-weight:500;color:#111827;}
    .section-title {font-size:1rem;font-weight:600;color:#1e40af;}
    .modal-image {max-height:80vh;object-fit:contain;margin:auto;display:none;transition:transform .2s;}
    .modal-image.active {display:block;}
  </style>
</head>
<body class="bg-gray-100 text-gray-900 antialiased">
  <div class="min-h-screen w-full px-4 sm:px-6 lg:px-8 py-8">
    <a href="dashboard.php" class="inline-block text-blue-600 hover:text-blue-800 font-medium mb-6">
      <i class="fa-solid fa-arrow-left mr-1"></i> Back to Dashboard
    </a>
    <h1 class="text-2xl font-bold text-blue-600 mb-6"><?php echo htmlspecialchars($bus['name']); ?> Details</h1>
    <div class="card">
      <div class="relative">
        <img src="<?php echo htmlspecialchars($bus['image']); ?>" alt="<?php echo htmlspecialchars($bus['name']); ?>" class="w-full h-64 object-cover">
        <div class="absolute top-3 left-3">
          <span class="px-3 py-1 rounded-md bg-blue-600/90 text-lg font-bold text-white shadow">
            <?php echo htmlspecialchars($bus['name']); ?>
          </span>
        </div>
      </div>
      <div class="p-6 space-y-6">
        <!-- Bus Basic Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div><div class="label">Plate No.</div><div class="value"><?php echo htmlspecialchars($bus['plate']); ?></div></div>
          <div><div class="label">Mileage</div><div class="value"><?php echo number_format($bus['mileage_km']); ?> km</div></div>
          <div><div class="label">Engine No.</div><div class="value"><?php echo htmlspecialchars($bus['engine_no']); ?></div></div>
          <div><div class="label">Chassis No.</div><div class="value"><?php echo htmlspecialchars($bus['chassis_no']); ?></div></div>
          <div><div class="label">MV File No.</div><div class="value"><?php echo htmlspecialchars($bus['mv_file_no']); ?></div></div>
          <div><div class="label">Make</div><div class="value"><?php echo htmlspecialchars($bus['make']); ?></div></div>
          <div><div class="label">Type</div><div class="value"><?php echo htmlspecialchars($bus['type']); ?></div></div>
        </div>

        <!-- Registration & Insurance & Oil Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-2">
            <div class="section-title"><i class="fa-regular fa-id-card mr-1.5"></i>Registration</div>
            <div class="text-sm text-gray-700">CR No.: <span class="font-medium"><?php echo htmlspecialchars($bus['registration']['cr_no']); ?></span></div>
            <!-- <div class="text-sm text-gray-700">Expiry: <span class="font-medium"><?php echo format_date($bus['registration']['expiry']); ?></span>
              <span class="chip ml-2 <?php echo $regBadge; ?>"><?php echo $regLabel; ?></span>
            </div> -->
          </div>
          <div class="space-y-2">
            <!-- <div class="section-title"><i class="fa-solid fa-shield-halved mr-1.5"></i>Insurance</div>
            <div class="text-sm text-gray-700">Provider: <span class="font-medium"><?php echo htmlspecialchars($bus['insurance']['provider']); ?></span></div>
            <div class="text-sm text-gray-700">Policy: <span class="font-medium"><?php echo htmlspecialchars($bus['insurance']['policy_no']); ?></span></div>
            <div class="text-sm text-gray-700">Expiry: <span class="font-medium"><?php echo format_date($bus['insurance']['expiry']); ?></span>
              <span class="chip ml-2 <?php echo $insBadge; ?>"><?php echo $insLabel; ?></span>
            </div> -->
            <div class="mt-4">
              <div class="section-title"><i class="fa-solid fa-gauge-high mr-1.5"></i>Latest Mileage Record</div>
              <div class="text-sm text-gray-700">Last Mileage: <span class="font-medium"><?php echo number_format($bus['oil']['last_change_mileage_km']); ?> km</span></div>
              <div class="text-sm text-gray-700">Record Date: <span class="font-medium"><?php echo format_date($bus['oil']['last_change_date']); ?></span></div>
              <div class="mt-4">
                <div class="section-title"><i class="fa-solid fa-file-image mr-1.5"></i>Documents</div>
                <button onclick="openModal()" class="inline-block px-4 py-2 bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">
                  <i class="fa-solid fa-eye mr-1"></i> View Documents
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="imageModal" class="fixed inset-0 hidden z-50 bg-black/80 flex justify-center items-center">
    <div class="relative max-w-[90%] max-h-[85vh] bg-black p-4 rounded-lg overflow-auto">
      <img id="modalImage1" src="<?php echo htmlspecialchars($bus['jpeg_file']); ?>" class="modal-image active rounded-lg shadow-lg">
      <img id="modalImage2" src="<?php echo htmlspecialchars($bus['second_image']); ?>" class="modal-image rounded-lg shadow-lg">
    </div>

    <!-- Controls -->
    <div class="absolute top-4 right-4 flex space-x-2 z-50">
      <button onclick="event.stopPropagation(); zoomIn()" class="p-2 bg-white rounded-full shadow hover:bg-gray-100"><i class="fa-solid fa-plus"></i></button>
      <button onclick="event.stopPropagation(); zoomOut()" class="p-2 bg-white rounded-full shadow hover:bg-gray-100"><i class="fa-solid fa-minus"></i></button>
      <button onclick="closeModal(event)" class="p-2 bg-white rounded-full shadow hover:bg-gray-100"><i class="fa-solid fa-times"></i></button>
    </div>
    <div class="absolute inset-y-0 left-4 flex items-center z-50">
      <button onclick="event.stopPropagation(); prevImage()" class="p-2 bg-white rounded-full shadow hover:bg-gray-100"><i class="fa-solid fa-chevron-left"></i></button>
    </div>
    <div class="absolute inset-y-0 right-4 flex items-center z-50">
      <button onclick="event.stopPropagation(); nextImage()" class="p-2 bg-white rounded-full shadow hover:bg-gray-100"><i class="fa-solid fa-chevron-right"></i></button>
    </div>
  </div>

  <script>
    let scale = 1;
    let currentIndex = 1;
    const images = ["modalImage1", "modalImage2"];

    function openModal() {
      const modal = document.getElementById('imageModal');
      modal.classList.remove('hidden');
      modal.classList.add('flex');
    }

    function closeModal(e) {
      if (e) e.stopPropagation();
      const modal = document.getElementById('imageModal');
      modal.classList.add('hidden');
      modal.classList.remove('flex');
      scale = 1;
      updateTransform();
    }

    function zoomIn() { scale = Math.min(scale + 0.2, 3); updateTransform(); }
    function zoomOut() { scale = Math.max(scale - 0.2, 0.5); updateTransform(); }

    function updateTransform() {
      document.querySelectorAll('.modal-image.active').forEach(img => {
        img.style.transform = `scale(${scale})`;
      });
    }

    function showImage(index) {
      images.forEach((id, i) => {
        const img = document.getElementById(id);
        if (i === index - 1) {
          img.classList.add('active');
        } else {
          img.classList.remove('active');
          img.style.transform = 'scale(1)';
        }
      });
      scale = 1;
      currentIndex = index;
    }

    function prevImage() { currentIndex = currentIndex === 1 ? images.length : currentIndex - 1; showImage(currentIndex); }
    function nextImage() { currentIndex = currentIndex === images.length ? 1 : currentIndex + 1; showImage(currentIndex); }

    // Background click closes modal
    document.getElementById('imageModal').addEventListener('click', (e) => {
      if (e.target.id === 'imageModal') closeModal();
    });

    // ESC key closes modal
    document.addEventListener('keydown', (e) => {
      if (e.key === "Escape") closeModal();
    });

    // Mouse wheel zoom support
    document.getElementById('imageModal').addEventListener('wheel', (e) => {
      e.preventDefault();
      if (e.deltaY < 0) zoomIn(); else zoomOut();
    });
  </script>
</body>
</html>