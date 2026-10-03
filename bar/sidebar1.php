<?php
  $current_page = basename($_SERVER['PHP_SELF']);
?>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<!-- Sidebar -->
<div class="hidden md:flex md:flex-shrink-0">
  <div class="flex flex-col w-64 bg-gray-800">
    <!-- Back button -->
    <div class="flex items-center justify-between h-16 px-4 bg-gray-900">
      <button onclick="window.location.href='../categories.php';" class="text-gray-300 hover:text-white mr-2">
        <i class="fas fa-arrow-left"></i>
      </button>
      <div class="flex items-center justify-center flex-1">
        <i class="fas fa-building text-white mr-2 text-xl"></i>
        <span class="text-white font-semibold text-lg whitespace-nowrap">BELVIC</span>
      </div>
      <div class="w-6"></div>
    </div>

    <!-- Search box -->
    <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
      <div class="px-4 mb-4">
        <div class="relative">
          <input type="text" placeholder="Search..." class="w-full pl-10 pr-4 py-2 rounded-lg bg-gray-700 text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 px-2 space-y-1">
        <a href="belvic_construction.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'belvic_construction.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-tachometer-alt mr-3"></i> Dashboard
        </a>
        <a href="assets.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'assets.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-boxes mr-3"></i> All Assets
        </a>
        <a href="vehicles.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'vehicles.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-truck mr-3"></i> Vehicles
        </a>
        <a href="../pages/tools.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'tools.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-tools mr-3"></i> Tools
        </a>
        <!-- <a href="../pages/insurance.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'insurance.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-file-contract mr-3"></i> Vehicle Insurance
        </a> -->
        <a href="../pages/mileage.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'mileage.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-road mr-3"></i> Mileage
        </a>
        <a href="../pages/fuel.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'fuel.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-gas-pump mr-3"></i> Fuel
        </a>
        <a href="../pages/solar.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'solar.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-solar-panel mr-3"></i> Solar Lights
        </a>
        <a href="../pages/lto.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'lto.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-id-card mr-3"></i> LTO
        </a>
        <a href="../pages/insurance.php" class="flex items-center px-2 py-3 text-sm font-medium rounded-md border-l-2 <?= $current_page == 'insurance.php' ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
          <i class="fas fa-shield-alt mr-3"></i> Insurance
        </a>
<div x-data="{ open: <?= in_array($current_page, ['sag.php', 'sag_reports.php']) ? 'true' : 'false' ?> }">
  <button 
    type="button"
    @click="open = !open" 
    class="flex items-center w-full px-2 py-3 text-sm font-medium rounded-md border-l-2 focus:outline-none
      <?= ($current_page == 'sag.php' || $current_page == 'sag_reports.php') ? 'bg-gray-900 text-white border-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white border-transparent hover:border-white' ?>">
    <i class="fas fa-cogs mr-3"></i> SAG Operation
    <svg :class="open ? 'transform rotate-90' : ''" class="ml-auto h-4 w-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
    </svg>
  </button>
  <div x-show="open" x-transition x-cloak class="ml-8 mt-1 space-y-1">
    <a href="../pages/damit.php" class="block px-2 py-2 text-sm rounded-md <?= $current_page == 'sag.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?>">
      Damit Quary/Bayog
    </a>
    <a href="../pages/palomoc.php" class="block px-2 py-2 text-sm rounded-md <?= $current_page == 'sag_reports.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?>">
      Palomoc Quary/Titay
    </a>
    <a href="../pages/sanghanan.php" class="block px-2 py-2 text-sm rounded-md <?= $current_page == 'sag_reports.php' ? 'bg-gray-700 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white' ?>">
      Sanghanan Quary/Kabasalan
    </a>
  </div>
</div>
      </nav>
    </div>

    <!-- Footer (admin user) -->
    <!-- <div class="flex-shrink-0 flex bg-gray-700 p-4">
      <div class="flex items-center">
        <img class="inline-block h-9 w-9 rounded-full" src="https://via.placeholder.com/150?text=AD" alt="Admin">
        <div class="ml-3">
          <p class="text-sm font-medium text-white">Admin User</p>
          <p class="text-xs font-medium text-gray-300">Administrator</p>
        </div>
      </div>
    </div> -->
  </div>
</div>
