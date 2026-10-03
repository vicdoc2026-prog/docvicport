<?php
date_default_timezone_set('Asia/Manila');
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

$buses = [
    [
        'id' => 'bus1',
        'name' => 'BUS 1',
        'image' => 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
        'route' => '',
        'status' => ' ',
        'capacity' => '',
        'eta' => ''
    ],
    [
        'id' => 'bus2',
        'name' => 'BUS 2',
        'image' => 'https://images.unsplash.com/photo-1509281373149-e957c6296406?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
        'route' => '',
        'status' => '',
        'capacity' => '',
        'eta' => ''
    ],
    [
        'id' => 'bus3',
        'name' => 'BUS 3',
        'image' => 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1200&q=80',
        'route' => '',
        'status' => '',
        'capacity' => '',
        'eta' => ''
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>STII School Bus Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        }
        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 3rem;
            background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #10b981 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            letter-spacing: 2px;
        }
        .card {
            background-color: #fff;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05), 0 1px 3px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .card:hover { 
            transform: translateY(-6px); 
            box-shadow: 0 12px 18px rgba(0,0,0,0.1), 0 4px 8px rgba(0,0,0,0.08),
                        0 0 0 2px rgba(245, 158, 11, .25);
            border-color: rgba(245, 158, 11, .5);
        }
        .card-image { 
            height: 200px; 
            overflow: hidden; 
            position: relative; 
        }
        .card-image:after {
            content: ''; 
            position: absolute; 
            bottom: 0; 
            left: 0; 
            width: 100%; 
            height: 40%;
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, transparent 100%);
            opacity: 0.7; 
            transition: opacity 0.3s ease;
        }
        .card:hover .card-image:after { 
            opacity: 0.9; 
        }
        .card img { 
            transition: transform 0.5s ease; 
        }
        .card:hover img { 
            transform: scale(1.04); 
        }
        .status-badge {
            display: inline-flex; 
            align-items: center; 
            padding: 0.25rem 0.6rem;
            border-radius: 9999px; 
            font-size: 0.7rem; 
            font-weight: 500;
        }
        .status-in-transit { 
            background-color: #fef3c7; 
            color: #d97706; 
        }
        .status-boarding { 
            background-color: #dcfce7; 
            color: #16a34a; 
        }
        .status-scheduled { 
            background-color: #e0e7ff; 
            color: #4f46e5; 
        }
    </style>
</head>
<body class="min-h-screen p-4 md:p-8">
  <div class="mb-6">
    <a href="../portal.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200 text-sm">
      <i class="fas fa-arrow-left mr-2"></i>
      <span>Back</span>
    </a>
  </div>

  <div class="max-w-7xl mx-auto">
    <header class="mb-8 text-center" style="margin-bottom: 90px;">
      <h1 class="brand-title" data-text="STII SCHOOL BUS">
        STII SCHOOL BUS
      </h1>
    </header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5" style="margin-top: 50px;">
      <?php foreach ($buses as $bus): ?>
        <a href="<?php echo htmlspecialchars($bus['id']); ?>.php" class="card group">
          <div class="card-image">
            <img src="<?php echo htmlspecialchars($bus['image']); ?>" alt="<?php echo htmlspecialchars($bus['name']); ?>" class="w-full h-full object-cover">
            <div class="absolute bottom-3 left-3 z-10 text-white">
              <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($bus['name']); ?></h3>
              <p class="text-xs opacity-90"><?php echo htmlspecialchars($bus['route']); ?></p>
            </div>
          </div>
          <div class="p-4">
            <div class="flex justify-between items-center mb-3">
              <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', trim($bus['status']))); ?>">
                <?php echo htmlspecialchars($bus['status']); ?>
              </span>
              <span class="text-xs font-medium text-gray-600"><?php echo htmlspecialchars($bus['eta']); ?></span>
            </div>
            <div class="flex justify-between items-center">
              <div>
                <p class="text-[11px] text-gray-500 leading-none mb-1">Capacity</p>
                <p class="font-semibold text-sm"><?php echo htmlspecialchars($bus['capacity']); ?></p>
              </div>
              <div class="text-amber-600 group-hover:text-amber-700 transition-colors duration-300 text-sm">
                <span class="font-medium">View Details</span>
                <span aria-hidden="true">→</span>
              </div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>

  <footer class="mt-8 py-4">
    <div class="max-w-7xl mx-auto px-3 sm:px-4 lg:px-6 text-center text-xs sm:text-sm text-gray-500">
      <p>© <?php echo date('Y'); ?> STII School Bus System. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>