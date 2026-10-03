
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farm/Agri | Essentiel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
    }
    .sidebar-gradient {
      background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    }
    .nav-item {
      transition: all 0.2s ease;
    }
    .nav-item:hover {
      background-color: rgba(255, 255, 255, 0.1);
      transform: translateX(3px);
    }
    .active-nav {
      background-color: rgba(0, 0, 0, 0.2);
      border-left: 4px solid white;
    }
    .search-input {
      background-color: rgba(255, 255, 255, 0.15);
      transition: all 0.2s ease;
    }
    .search-input:focus {
      background-color: rgba(255, 255, 255, 0.25);
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
    }
    .search-input::placeholder {
      color: rgba(255, 255, 255, 0.7);
    }
  </style>
</head>
<body class="flex h-screen overflow-hidden">

<!-- Sidebar -->
<div class="hidden md:flex md:flex-shrink-0">
  <div class="flex flex-col w-64 sidebar-gradient">
    <!-- Header with back button -->
    <div class="flex items-center justify-between h-16 px-4 bg-black bg-opacity-20">
      <button onclick="window.location.href='../categories.php';" 
              class="text-gray-200 hover:text-white transition-colors duration-200 p-1 rounded-full hover:bg-white hover:bg-opacity-10">
        <i class="fas fa-arrow-left fa-sm"></i>
      </button>
      <div class="flex items-center justify-center flex-1">
        <i class="fas fa-tractor text-white mr-2 text-xl"></i>
        <span class="text-white font-semibold text-lg whitespace-nowrap">Farm/Agri</span>
      </div>
      <div class="w-6"></div>
    </div>
    
    <!-- Navigation content --> 
    <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
      <!-- Search bar -->
      <div class="px-4 mb-6">
        <div class="relative">
          <input type="text" placeholder="Search..." 
                 class="w-full pl-10 pr-4 py-2 rounded-lg search-input text-white focus:outline-none focus:ring-0">
          <i class="fas fa-search absolute left-3 top-3 text-gray-200"></i>
        </div>
      </div>
      
      <!-- Navigation links -->
      <nav class="flex-1 px-2 space-y-1">
        <a href="dashboard.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-white active-nav nav-item">
          <i class="fas fa-tachometer-alt mr-3 text-gray-200"></i>
          Dashboard
        </a>
        <a href="farm_house.php" 
          class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
            <i class="fas fa-hotel mr-3"></i>
            Rest House/Hotel
        </a>
        <a href="gate.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-door-open mr-3"></i>
          Repair Gate
        </a>
        <a href="duck.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-feather mr-3"></i> Duck
        </a>
        <a href="rabbit.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-paw mr-3"></i>
          Rabbit
        </a>
        <a href="egg.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-tachometer-alt mr-3"></i>
          Eggs
        </a>
        <a href="../pages/fuel.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-gas-pump mr-3"></i>
          Quarters/Worker
        </a>
        <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Tire/Foot Bath/ Cover
        </a>
                <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Ceiling Training Farmer Center
        </a>
                <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         New Ruminant Building
        </a>
                <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Mini Zoo
        </a>
                <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Flower (Concrete Based) Entrance
        </a>
        <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Tool Room 2 Repaint Rehab
        </a>
                <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
         Firing Range/Steel Plate
        </a>
                        <a href="../pages/solar.php" 
           class="flex items-center px-3 py-3 text-sm font-medium rounded-md text-gray-200 hover:text-white nav-item">
          <i class="fas fa-solar-panel mr-3"></i>
        Piggery
        </a>
      </nav>
    </div>
    
    <!-- User profile footer -->
    <div class="flex-shrink-0 flex p-4 bg-black bg-opacity-20">
      <div class="flex items-center w-full">
        <div>
          <img class="inline-block h-10 w-10 rounded-full border-2 border-white border-opacity-30" 
               src="https://ui-avatars.com/api/?name=Admin+User&background=059669&color=fff" 
               alt="Admin">
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-white">Admin User</p>
          <p class="text-xs font-medium text-gray-200">Administrator</p>
        </div>
        <div class="ml-auto">
          <button class="text-gray-200 hover:text-white">
            <i class="fas fa-sign-out-alt"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Main content area -->
<div class="flex-1 overflow-auto">
  <div class="p-6">
    <!-- Mobile header -->
    <div class="md:hidden flex items-center justify-between mb-6">
      <button onclick="window.location.href='../categories.php';" 
              class="text-gray-600 hover:text-gray-900 p-2 rounded-lg bg-gray-100">
        <i class="fas fa-arrow-left"></i>
      </button>
      <h1 class="text-xl font-bold text-gray-800">
        <i class="fas fa-building text-lime-600 mr-2"></i>
        Belvic Construction
      </h1>
      <div class="w-8"></div>
    </div>
    
    <!-- Page content would go here -->
    <div class="bg-white rounded-xl shadow-sm p-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-4">Dashboard Overview</h2>
      <p class="text-gray-600">Welcome to Belvic Construction management portal. Select a section from the sidebar to begin.</p>
    </div>
  </div>
</div>

<script>
  // Get current page path
  const currentPath = window.location.pathname.split("/").pop();

  // Loop through all nav links
  document.querySelectorAll("nav a").forEach(link => {
    const href = link.getAttribute("href").split("/").pop();
    if (href === currentPath) {
      link.classList.add("active-nav");
      link.classList.add("text-white");
    } else {
      link.classList.remove("active-nav");
    }
  });
</script>


</body>
</html>