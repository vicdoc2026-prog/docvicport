<!--- Navbar for Belvic Construction Page --->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Belvic</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1e40af',
            secondary: '#3b82f6',
            accent: '#10b981',
          }
        }
      }
    }
  </script>
  <style>
    .nav-item {
      position: relative;
      transition: color 0.3s ease;
    }
    .nav-item::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 0;
      width: 0;
      height: 2px;
      background-color: #3b82f6;
      transition: width 0.3s ease;
    }
    .nav-item:hover::after,
    .nav-item.active::after {
      width: 100%;
    }
    .mobile-menu {
      max-height: 0;
      overflow: hidden;
      transition: max-height 0.3s ease;
    }
    .mobile-menu.open {
      max-height: calc(100dvh - 68px);
      overflow-y: auto;
    }
    .dropdown-menu {
      display: none;
      position: absolute;
    }
    .dropdown-menu.open {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-50 pt-[68px]">

<!-- Navbar -->
<nav class="bg-white shadow navbar fixed top-0 left-0 w-full z-50">
  <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center w-full">
    <!-- Back Button + Logo -->
    <div class="flex items-center space-x-4">
      <!-- Back Button -->
      <button onclick="window.location='portal.php'" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left text-sm"></i>
      </button>

      <!-- Logo -->
      <a href="belvic_construction.php" class="flex items-center">
        <i class="fas fa-car text-primary text-lg mr-2"></i>
        <span class="text-sm font-bold text-gray-900">Belvic</span>
      </a>
    </div>

    <!-- Navigation Links -->
    <div class="hidden md:flex space-x-3 items-center">
      <a href="belvic_construction.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-tachometer-alt mr-1 text-sm"></i> Dashboard
      </a>
      <!-- <a href="general_information.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-id-card mr-1 text-sm"></i> General Information
      </a> -->
      <a href="assets.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-boxes mr-1 text-sm"></i> Assets
      </a>
      <a href="tools.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-tools mr-1 text-sm"></i> Tools
      </a>
      <a href="mileage.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-road mr-1 text-sm"></i> Mileage
      </a>
      <a href="solar.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-id-card mr-1 text-sm"></i> Solar Lights
      </a>
      <a href="lto.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-id-card mr-1 text-sm"></i> LTO
      </a>
      
      <!-- Fuel Dropdown -->
      <a href="fuel.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-gas-pump mr-1 text-sm"></i> Fuel
      </a>

      <a href="insurance.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-shield-alt mr-1 text-sm"></i> Insurance
      </a>
      <a href="lubricant.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-oil-can mr-1 text-sm"></i> Lubricant
      </a>
      <a href="tires.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-dot-circle mr-1 text-sm"></i> Tires
      </a>

      <!-- SAG Operations Dropdown -->
      <div class="relative">
        <button id="sagDropdown" class="nav-item px-2 py-1 text-gray-700 hover:text-primary flex items-center text-sm">
          <i class="fas fa-cogs mr-1 text-sm"></i> SAG Operations <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="sagDropdownMenu" class="dropdown-menu left-0 mt-2 w-56 bg-white rounded shadow-lg z-10 border border-gray-200">
          <a href="damit.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm"><i class="fas fa-cogs mr-2 text-sm"></i> Damit Quary/Bayog</a>
          <a href="palomoc.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm"><i class="fas fa-cogs mr-2 text-sm"></i> Palomoc Quary/Titay</a>
          <a href="sanghanan.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm"><i class="fas fa-cogs mr-2 text-sm"></i> Sanghanan Quary/Kabasalan</a>
        </div>
      </div>
    </div>

    <!-- Right Icons -->
    <div class="flex items-center space-x-3">
      <!-- Search -->
      <div class="hidden md:block relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400 text-sm"></i>
        </div>
        <input type="text" placeholder="Search..." class="pl-8 pr-3 py-1 w-40 border rounded-lg focus:ring-2 focus:ring-primary text-sm">
      </div>

      <!-- Search Button -->
      <button class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-search text-sm"></i>
      </button>

      <!-- Notification Bell -->
      <div class="relative">
        <button class="text-gray-600 hover:text-gray-900">
          <i class="fas fa-bell text-sm"></i>
        </button>
        <span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">3</span>
      </div>
    </div>
  </div>

  <!-- Mobile Menu -->
  <div id="mobileMenu" class="mobile-menu md:hidden bg-white px-4 py-2 space-y-2 fixed top-[68px] left-0 w-full z-40">
    <a href="belvic_construction.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-tachometer-alt mr-1 text-sm"></i> Dashboard</a>
    <a href="general_information.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-id-card mr-1 text-sm"></i> General Information</a>
    <a href="assets.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-boxes mr-1 text-sm"></i> Assets</a>
    <a href="vehicles.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-truck mr-1 text-sm"></i> Vehicles</a>
    <a href="tools.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-tools mr-1 text-sm"></i> Tools</a>
    <a href="mileage.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-road mr-1 text-sm"></i> Mileage</a>
    
    <!-- Fuel Mobile Dropdown -->
    <a href="fuel.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-gas-pump mr-1 text-sm"></i> Fuel</a>

    <a href="lto.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-id-card mr-1 text-sm"></i> LTO</a>
    <a href="insurance.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-shield-alt mr-1 text-sm"></i> Insurance</a>
    <a href="lubricant.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-oil-can mr-1 text-sm"></i> Lubricant</a>
    <a href="tires.php" class="block nav-item text-gray-700 hover:text-primary text-sm"><i class="fas fa-dot-circle mr-1 text-sm"></i> Tires</a>

    <!-- SAG Operations Mobile Dropdown -->
    <div class="relative">
      <button id="sagMobileDropdown" class="block nav-item text-gray-700 hover:text-primary w-full text-left text-sm">
        <i class="fas fa-cogs mr-1 text-sm"></i> SAG Operations <i class="fas fa-chevron-down ml-1 text-xs"></i>
      </button>
      <div id="sagMobileDropdownMenu" class="dropdown-menu pl-4 space-y-2 pt-2">
        <a href="damit.php" class="block text-gray-700 hover:text-primary text-sm"><i class="fas fa-cogs mr-1 text-sm"></i> Damit Quary/Bayog</a>
        <a href="palomoc.php" class="block text-gray-700 hover:text-primary text-sm"><i class="fas fa-cogs mr-1 text-sm"></i> Palomoc Quary/Titay</a>
        <a href="sanghanan.php" class="block text-gray-700 hover:text-primary text-sm"><i class="fas fa-cogs mr-1 text-sm"></i> Sanghanan Quary/Kabasalan</a>
      </div>
    </div>
  </div>
</nav>

<!-- Scripts -->
<script>
  // Function to set active state based on current URL
  function setActiveNav() {
    const currentPath = window.location.pathname.split('/').pop();
    const navItems = document.querySelectorAll('.nav-item');
    
    navItems.forEach(item => {
      const href = item.getAttribute('href');
      if (href && (href === currentPath || (currentPath === '' && href === 'belvic_construction.php'))) {
        item.classList.add('active', 'text-primary', 'font-medium');
      } else {
        item.classList.remove('active', 'text-primary', 'font-medium');
      }
    });
  }

  // Toggle dropdown menu
  function toggleDropdown(buttonId, menuId) {
    const button = document.getElementById(buttonId);
    const menu = document.getElementById(menuId);
    
    if (!button || !menu) return;
    
    button.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      
      // Close all other dropdowns
      document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
        if (dropdown.id !== menuId) {
          dropdown.classList.remove('open');
        }
      });
      
      // Toggle current dropdown
      menu.classList.toggle('open');
    });
  }

  // Close dropdown when clicking outside
  function closeDropdownOnClickOutside() {
    document.addEventListener('click', (e) => {
      // Check if click is outside any dropdown
      const isDropdownButton = e.target.closest('button[id$="Dropdown"]');
      const isDropdownMenu = e.target.closest('.dropdown-menu');
      
      if (!isDropdownButton && !isDropdownMenu) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
          menu.classList.remove('open');
        });
      }
    });
  }

  // Initialize dropdowns
  document.addEventListener('DOMContentLoaded', () => {
    setActiveNav();
    
    // Initialize all dropdowns
    toggleDropdown('sagDropdown', 'sagDropdownMenu');
    toggleDropdown('sagMobileDropdown', 'sagMobileDropdownMenu');
    toggleDropdown('fuelDropdown', 'fuelDropdownMenu');
    toggleDropdown('fuelMobileDropdown', 'fuelMobileDropdownMenu');
    
    // Close dropdowns when clicking outside
    closeDropdownOnClickOutside();
  });

  // Handle nav item clicks
  document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', function(e) {
      if (!this.getAttribute('href')) return; // Skip if not a link
      document.querySelectorAll('.nav-item').forEach(nav => {
        nav.classList.remove('active', 'text-primary', 'font-medium');
      });
      this.classList.add('active', 'text-primary', 'font-medium');
    });
  });
</script>