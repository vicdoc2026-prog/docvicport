<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

?>

<?php include '../conn.php'; ?>
<?php include '../bar/navbar.php'; 
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Assets</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">
  <!-- Mobile menu button -->
  <div class="md:hidden">
    <button onclick="toggleMobileMenu()" class="ml-4 mt-4 p-2 rounded-md text-gray-500 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white">
      <i class="fas fa-bars"></i>
    </button>
  </div>

  <!-- Main content wrapper -->
  <div class="flex flex-col flex-1 overflow-hidden">
    <div class="flex-1 overflow-x-hidden overflow-y-auto">
      <div class="container mx-auto px-4 py-6">

        <!-- Page Header -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8">
          <div>
            <h1 class="text-2xl font-bold text-gray-800">Assets</h1>
            <!-- <p class="text-gray-600 mt-2">Municipal construction equipment inventory</p> -->
          </div>
          <div class="mt-4 md:mt-0 flex items-center space-x-4">
            <div class="text-sm bg-gradient-to-r from-blue-500 to-blue-600 text-white px-3 py-1.5 rounded-full flex items-center shadow-sm">
              <i class="fas fa-hard-hat mr-2"></i> Belvic Construction Assets
            </div>
            <form class="flex items-center space-x-2" action="#" method="get">
              <input type="text" name="search" placeholder="Search assets..." class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
              <button type="submit" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center">
                <i class="fas fa-search mr-1"></i> Search
              </button>
            </form>
          </div>
        </div>

        <!-- Category Filter Dropdown -->
        <div class="mb-6">
          <label for="categoryFilter" class="text-sm font-medium text-gray-700 mr-2">Filter by Category:</label>
          <select id="categoryFilter" onchange="filterAssets()" class="px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="all">All Categories</option>
            <option value="Dump Truck">Dump Truck</option>
            <option value="Excavator">Excavator</option>
            <option value="Loader">Loader</option>
            <option value="Transit Mixer">Transit Mixer</option>
            <option value="Road Grader">Road Grader</option>
            <option value="Road Roller">Road Roller</option>
            <option value="Vehicle">Vehicle</option>
            <option value="Special Equipment">Special Equipment</option>
          </select>
        </div>

        <!-- Asset Distribution -->
        <div class="mb-8 bg-white rounded-xl p-6 shadow-sm">
          <h2 class="text-xl font-bold text-gray-800 mb-4">Asset Distribution</h2>
          <div class="flex flex-col md:flex-row items-center">
            <!-- Pie Chart Container -->
            <div class="w-full md:w-1/3 flex justify-center mb-6 md:mb-0">
              <div class="relative w-64 h-64">
                <canvas id="assetChart"></canvas>
              </div>
            </div>
            
            <!-- Legend -->
            <div class="w-full md:w-2/3 grid grid-cols-2 md:grid-cols-3 gap-4">
              <div class="flex items-center">
                <div class="w-4 h-4 bg-blue-500 rounded-full mr-2"></div>
                <span>Dump Trucks (10)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-green-500 rounded-full mr-2"></div>
                <span>Excavators (5)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-yellow-500 rounded-full mr-2"></div>
                <span>Loaders (3)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-purple-500 rounded-full mr-2"></div>
                <span>Transit Mixers (4)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-red-500 rounded-full mr-2"></div>
                <span>Road Rollers (2)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-indigo-500 rounded-full mr-2"></div>
                <span>Vehicles (12  )</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-pink-500 rounded-full mr-2"></div>
                <span>Road Graders (2)</span>
              </div>
              <div class="flex items-center">
                <div class="w-4 h-4 bg-teal-500 rounded-full mr-2"></div>
                <span>Special Equipment (4)</span>
              </div>
              <div class="w-full mt-4">
              <p class="text-right text-gray-700 font-semibold">
              Total Assets: <span class="text-primary">41</span>
             </p>
            </div>
            </div>
          </div>
        </div>

        <!-- Status Filters -->
        <div class="flex flex-wrap gap-2 mb-6">
          <button class="px-3 py-1 rounded-full text-sm bg-blue-600 text-white shadow-sm transition-all duration-300 hover:shadow-md">All Assets</button>
          <button class="px-3 py-1 rounded-full text-sm bg-white border border-gray-300 text-gray-700 shadow-sm transition-all duration-300 hover:shadow-md hover:bg-gray-50">Operational</button>
          <button class="px-3 py-1 rounded-full text-sm bg-white border border-gray-300 text-gray-700 shadow-sm transition-all duration-300 hover:shadow-md hover:bg-gray-50">Maintenance</button>
          <button class="px-3 py-1 rounded-full text-sm bg-white border border-gray-300 text-gray-700 shadow-sm transition-all duration-300 hover:shadow-md hover:bg-gray-50">Out of Service</button>
        </div>

        <!-- Heavy Equipment Grid -->
        <div id="assetGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
          <!-- Asset Card 1 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-5)</h3>
                  <p class="text-sm text-gray-500 mt-1">D8AYJ02614</p>
                </div>
                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span> Active
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">              
                  <img src="image_assets/DT5.jpg" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 2 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-7)</h3>
                  <p class="text-sm text-gray-500 mt-1">8DC9254037</p>
                </div>
                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-yellow-500 rounded-full mr-1 animate-pulse"></span> Maintenance
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/DT7.jpg" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 3 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-8)</h3>
                  <p class="text-sm text-gray-500 mt-1">D8AYM056468</p>
                </div>
                <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-red-500 rounded-full mr-1 animate-pulse"></span> Repair Needed
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/DT8.jpg" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 4 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT9)</h3>
                  <p class="text-sm text-gray-500 mt-1">122602</p>
                </div>
                <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-green-500 rounded-full mr-1 animate-pulse"></span> Active
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1621411017400-5f3a5d5f0e5e?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 5 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-10)</h3>
                  <p class="text-sm text-gray-500 mt-1">90808</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 6 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-11)</h3>
                  <p class="text-sm text-gray-500 mt-1">CAO-7526</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 7 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-12)</h3>
                  <p class="text-sm text-gray-500 mt-1">NFL7842</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/DT12.jpeg" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 8 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-13)</h3>
                  <p class="text-sm text-gray-500 mt-1">NEV4433</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 9 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">10 WHEELER DUMP TRUCK (DT-14)</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/DT14.jpg" alt="Dump Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 10 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">4 WHEELS FUEL TRUCK</h3>
                  <p class="text-sm text-gray-500 mt-1">NHE4920</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/fuel_truck.jpg">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 11 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Transit Mixer">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TRANSIT MIXER 1</h3>
                  <p class="text-sm text-gray-500 mt-1">JDK-147</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/mixer_1.jpg">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 12 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Transit Mixer">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TRANSIT MIXER 2</h3>
                  <p class="text-sm text-gray-500 mt-1">KGT-634</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Transit Mixer" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 13 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Transit Mixer">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TRANSIT MIXER 3</h3>
                  <p class="text-sm text-gray-500 mt-1">NCZ8114</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/TM3.jpg" alt="Transit Mixer" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 14 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Transit Mixer">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TRANSIT MIXER 4</h3>
                  <p class="text-sm text-gray-500 mt-1">NIL4703</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/TM4.jpg" alt="Transit Mixer" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 15 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Road Grader">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">ROAD GRADER 1</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/grader1.jpeg" alt="Road Grader" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 16 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Road Grader">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">ROAD GRADER 2</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Road Grader" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 17 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Excavator">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">BACHOE EXCAVATOR 1</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/bachoe1.jpeg" alt="Excavator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>


          <!-- Asset Card 19 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Excavator">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">BACHOE EXCAVATOR 3</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/bachoe2.jpeg" alt="Excavator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 20 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Excavator">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">BACHOE EXCAVATOR 4</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/bachoe3.jpeg" alt="Excavator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 22 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Excavator">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">WHEELED EXCAVATOR 6</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Excavator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 23 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Excavator">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">HYDRAULIC EXCAVATOR 7</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Excavator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>


          <!-- Asset Card 25 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Road Roller">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">ROAD ROLLER 2</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/roadroller.jpeg" alt="Road Roller" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 26 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Road Roller">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">ROAD ROLLER 3</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Road Roller" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>



          <!-- Asset Card 28 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Loader">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">WHEELED LOADER 2</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/loader2.jpeg" alt="Loader" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 29 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Loader">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">WHEELED LOADER 3</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/loader1.jpeg" alt="Loader" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 30 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Loader">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">LOADER 4</h3>
                  <p class="text-sm text-gray-500 mt-1"></p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/loader4.jpeg" alt="Loader" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 31 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Special Equipment">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">SELF LOADING</h3>
                  <p class="text-sm text-gray-500 mt-1">NOT INDICATED</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Self Loading" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 32 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">BOOM TRUCK</h3>
                  <p class="text-sm text-gray-500 mt-1">BLUE</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Boom Truck" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 33 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">DROP SIDE ELF</h3>
                  <p class="text-sm text-gray-500 mt-1">BLUE</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Drop Side Elf" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 34 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">HONDA XRM MOTORCYCLE</h3>
                  <p class="text-sm text-gray-500 mt-1">BLUE</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Motorcycle" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 35 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">HONDA BEAT MOTORCYCLE</h3>
                  <p class="text-sm text-gray-500 mt-1">BLUE/BLACK</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Motorcycle" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 36 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">MAXIMA CARGO MOTORCYCLE</h3>
                  <p class="text-sm text-gray-500 mt-1">WHITE</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/cargo.jpg" alt="Motorcycle" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 37 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Dump Truck">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">CANTER MINI DUMP</h3>
                  <p class="text-sm text-gray-500 mt-1">RED/SILVER</p>
                </div>
                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
                  <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
                </span>
              </div>
              <div class="mb-4 overflow-hidden rounded-lg">
                <div class="relative">
                  <img src="image_assets/canter.jpg" alt="Canter Mini Dump" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
                  <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
                </div>
              </div>
              <div class="flex justify-between mt-6"></div>
            </div>
          </div>

          <!-- Asset Card 38 -->
          <div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
            <div class="p-5 relative z-10">
              <div class="flex justify-between items-start mb-4">
                <div>
                  <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TOYOTA AVANZA</h3>
                  <p class="text-sm text-gray-500 mt-1">SILVER METALLIC</p>
                </div>
                <!-- Continuing from the last asset card -->
  <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
    <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
  </span>
</div>
<div class="mb-4 overflow-hidden rounded-lg">
  <div class="relative">
    <img src="image_assets/avanza.jpg" alt="Toyota Avanza" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
  </div>
</div>
<div class="flex justify-between mt-6"></div>
</div>
</div>

<!-- Asset Card 39 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TOYOTA HILUX</h3>
        <p class="text-sm text-gray-500 mt-1">WHITE</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Toyota Hilux" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>

<!-- Asset Card 40 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">TOYOTA INNOVA</h3>
        <p class="text-sm text-gray-500 mt-1">SILVER</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Toyota Innova" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>

<!-- Asset Card 41 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Special Equipment">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">WATER PUMP</h3>
        <p class="text-sm text-gray-500 mt-1">NOT INDICATED</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Water Pump" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>

<!-- Asset Card 42 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Special Equipment">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">WATER TANK</h3>
        <p class="text-sm text-gray-500 mt-1">NOT INDICATED</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Water Tank" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>

<!-- Asset Card 43 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Special Equipment">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">VIBRATOR</h3>
        <p class="text-sm text-gray-500 mt-1">NOT INDICATED</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="https://images.unsplash.com/photo-1620207418302-439b387441b9?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&h=300&q=80" alt="Vibrator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>

<!-- Asset Card 44 -->
<div class="asset-card bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 transform transition-all duration-300 hover:shadow-lg hover:-translate-y-1 group relative" data-category="Vehicle">
  <div class="absolute inset-0 bg-gradient-to-br from-blue-50 to-white opacity-0 group-hover:opacity-100 transition-opacity duration-500 z-0"></div>
  <div class="p-5 relative z-10">
    <div class="flex justify-between items-start mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-800 group-hover:text-blue-700 transition-colors">Fortuner</h3>
        <p class="text-sm text-gray-500 mt-1">JAD-4460</p>
      </div>
      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full flex items-center">
        <span class="w-2 h-2 bg-blue-500 rounded-full mr-1 animate-pulse"></span> New
      </span>
    </div>
    <div class="mb-4 overflow-hidden rounded-lg">
      <div class="relative">
        <img src="image_assets/fortuner.jpg" alt="Vibrator" class="w-full h-40 object-cover rounded-lg group-hover:scale-105 transition-transform duration-500">
        <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent rounded-lg"></div>
      </div>
    </div>
    <div class="flex justify-between mt-6"></div>
  </div>
</div>
</div>
<!-- End of Heavy Equipment Grid -->

</div>
</div>
</div>

<!-- JavaScript for Mobile Menu and Filtering -->
<script>
  // Mobile Menu Toggle
  function toggleMobileMenu() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('hidden');
  }

  // Asset Filtering by Category
  function filterAssets() {
    const filter = document.getElementById('categoryFilter').value;
    const assetCards = document.querySelectorAll('.asset-card');

    assetCards.forEach(card => {
      const category = card.getAttribute('data-category');
      if (filter === 'all' || category === filter) {
        card.style.display = 'block';
      } else {
        card.style.display = 'none';
      }
    });
  }

  // Chart.js for Asset Distribution
  const ctx = document.getElementById('assetChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Dump Trucks', 'Excavators', 'Loaders', 'Transit Mixers', 'Road Rollers', 'Vehicles', 'Road Graders', 'Special Equipment'],
      datasets: [{
        data: [10, 5, 3, 4, 2, 12, 2, 4],
        backgroundColor: [
          '#3B82F6', // Blue
          '#10B981', // Green
          '#F59E0B', // Yellow
          '#8B5CF6', // Purple
          '#EF4444', // Red
          '#4F46E5', // Indigo
          '#EC4899', // Pink
          '#14B8A6'  // Teal
        ],
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
</script>

</body>
</html>