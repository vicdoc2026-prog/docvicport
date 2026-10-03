<?php 

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];


?>
<?php include '../bar/navbar.php'; ?>
<?php
ob_start(); // Start output buffering to prevent stray output
include '../conn.php';
$vehicles = [];

if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
} else {
    // Fetch vehicle data
    $sql = "SELECT mv_file_no, code, make, type, plate_no, due_date_actual 
            FROM lto 
            WHERE due_date_actual IS NOT NULL";
    $result = $conn->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = [
                'mvFileNo' => $row['mv_file_no'],
                'code' => $row['code'],
                'make' => $row['make'],
                'type' => $row['type'],
                'plateNo' => $row['plate_no'],
                'dueDate' => $row['due_date_actual']
            ];
        }
    } else {
        error_log("Query failed: " . $conn->error);
    }
    $conn->close();
}

// Encode vehicles data as JSON, escaping HTML characters
$vehicles_json = json_encode($vehicles, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
ob_end_clean(); // Clear output buffer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Registration Records</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="bg-gray-100">
<div class="flex-1 h-screen px-6 pt-6 pb-10 space-y-6" style="overflow: auto;">
  <!-- Vehicle Records Section -->
  <div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b flex flex-col md:flex-row justify-between items-start md:items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Vehicle Registration Records</h2>
        <p class="text-gray-600 mt-1">Manage all registered vehicles and their registration details</p>
      </div>
      <div class="mt-4 md:mt-0 flex space-x-3 items-center">
        <span class="text-gray-700 text-base font-medium">Total Vehicles: <span id="totalVehicles"><?php echo count($vehicles); ?></span></span>
        <div class="relative">
          <button id="filterButton" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg text-gray-700 flex items-center">
            <i class="fas fa-filter mr-2"></i>
            Filter
            <i class="fas fa-chevron-down ml-2 text-xs"></i>
          </button>
          <div id="filterDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-md shadow-lg py-1 filter-dropdown hidden z-10">
            <div class="px-4 py-3 border-b">
              <p class="text-sm font-medium text-gray-900">Filter by</p>
            </div>
            <div class="px-4 py-2">
              <label class="flex items-center">
                <input type="checkbox" class="form-checkbox rounded text-blue-600" id="dueThisMonth">
                <span class="ml-2 text-gray-700">Due this month</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Search and Controls -->
    <div class="px-6 py-4 bg-gray-50 flex flex-col md:flex-row justify-between items-center">
      <div class="relative mb-4 md:mb-0 w-full md:w-auto">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400"></i>
        </div>
        <input type="text" id="searchInput" class="pl-10 pr-4 py-2 w-full border rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-transparent" placeholder="Search vehicles...">
      </div>
      <div class="flex space-x-2">
        <button class="p-2 rounded-lg border text-gray-700 hover:bg-gray-100">
          <i class="fas fa-download"></i>
        </button>
        <button class="p-2 rounded-lg border text-gray-700 hover:bg-gray-100">
          <i class="fas fa-print"></i>
        </button>
      </div>
    </div>

    <!-- Table and Pagination -->
    <div class="overflow-x-auto">
      <table class="w-full divide-y divide-gray-200">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MV FILE NO.</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CODE</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MAKE</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TYPE</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PLATE NO.</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DUE DATE</th>
          </tr>
        </thead>
        <tbody id="vehicleTableBody" class="bg-white divide-y divide-gray-200">
          <!-- Table rows will be populated by JavaScript -->
        </tbody>
      </table>
      <!-- Pagination -->
      <div id="paginationContainer" class="px-6 py-4 bg-gray-50 border-t flex flex-col md:flex-row justify-between items-center">
        <div id="paginationInfo" class="mb-4 md:mb-0">
          <p class="text-sm text-gray-700">
            Showing <span id="startIndex">1</span> to <span id="endIndex">10</span> of <span id="totalResults">0</span> results
          </p>
        </div>
        <div id="paginationButtons" class="flex items-center space-x-2">
          <!-- Pagination buttons will be populated by JavaScript -->
        </div>
      </div>
    </div>
  </div>

  <script>
    // Load vehicles from PHP
    const vehicles = <?php echo $vehicles_json; ?>;
    console.log('Vehicles data:', vehicles); // Debug: Verify data

    // Pagination settings
    const itemsPerPage = 10;
    let currentPage = 1;
    let filteredVehicles = [];

    // DOM elements
    const tableBody = document.getElementById('vehicleTableBody');
    const paginationContainer = document.getElementById('paginationContainer');
    const paginationInfo = document.getElementById('paginationInfo');
    const paginationButtons = document.getElementById('paginationButtons');
    const searchInput = document.getElementById('searchInput');
    const filterButton = document.getElementById('filterButton');
    const filterDropdown = document.getElementById('filterDropdown');
    const dueThisMonth = document.getElementById('dueThisMonth');

    // Helper function to adjust past due dates to next year
    function fixPastDueDateToNextYear(dueDateStr) {
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize to start of day
      const dueDate = new Date(dueDateStr);
      dueDate.setHours(0, 0, 0, 0); // Normalize to start of day

      // If due date is in the past, advance to same month next year (5th day)
      if (dueDate < today) {
        const newDate = new Date(today.getFullYear() + 1, dueDate.getMonth(), 5);
        return newDate;
      }
      return dueDate;
    }

    // Format due date function
    function formatDueDate(dueDateStr, isUpcoming, isWithinWeek) {
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize to start of day
      const dueDate = fixPastDueDateToNextYear(dueDateStr);
      dueDate.setHours(0, 0, 0, 0); // Normalize to start of day

      // Format: August 11, 2025
      const options = { year: 'numeric', month: 'long', day: 'numeric' };
      const formattedDate = dueDate.toLocaleDateString('en-US', options);

      // Calculate days remaining
      const timeDiff = dueDate - today;
      const daysRemaining = Math.ceil(timeDiff / (1000 * 60 * 60 * 24));

      // Only show days remaining for upcoming events
      if (isUpcoming) {
        return `Due: ${formattedDate} • ${daysRemaining} day${daysRemaining !== 1 ? 's' : ''} remaining`;
      }
      return `Due: ${formattedDate}`;
    }

    // Sort and prioritize upcoming registrations
    function prioritizeUpcomingVehicles() {
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize to start of day

      // Map vehicles with adjusted due dates
      const allVehicles = vehicles.map(vehicle => ({
        ...vehicle,
        adjustedDueDate: fixPastDueDateToNextYear(vehicle.dueDate)
      }));

      // Get top 4 upcoming vehicles (due date >= today)
      const upcoming = allVehicles
        .filter(v => v.adjustedDueDate >= today)
        .sort((a, b) => a.adjustedDueDate - b.adjustedDueDate)
        .slice(0, 4); // Take top 4 upcoming

      // Get all other vehicles (including any not in top 4)
      const others = allVehicles
        .filter(v => !upcoming.includes(v))
        .sort((a, b) => a.adjustedDueDate - b.adjustedDueDate);

      // Combine: top 4 upcoming first, then others
      filteredVehicles = [...upcoming, ...others].map(v => ({
        mvFileNo: v.mvFileNo,
        code: v.code,
        make: v.make,
        type: v.type,
        plateNo: v.plateNo,
        dueDate: v.adjustedDueDate.toISOString().split('T')[0] // Use adjusted date
      }));
    }

    // Toggle filter dropdown
    filterButton.addEventListener('click', () => {
      filterDropdown.classList.toggle('hidden');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!filterButton.contains(e.target) && !filterDropdown.contains(e.target)) {
        filterDropdown.classList.add('hidden');
      }
    });

    // Render table rows
    function renderTable(data, page) {
      tableBody.innerHTML = '';
      const start = (page - 1) * itemsPerPage;
      const end = start + itemsPerPage;
      const paginatedData = data.slice(start, end);

      const today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize to start of day
      const oneWeekFromNow = new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000);

      paginatedData.forEach((vehicle, index) => {
        const dueDate = new Date(vehicle.dueDate);
        dueDate.setHours(0, 0, 0, 0); // Normalize to start of day
        const isUpcoming = index < 4 && dueDate >= today; // Top 4 are upcoming
        const isWithinWeek = isUpcoming && dueDate <= oneWeekFromNow;
        const rowColor = isUpcoming ? (isWithinWeek ? 'bg-red-100' : 'bg-blue-100') : '';

        const row = document.createElement('tr');
        row.className = `table-row hover:bg-gray-50 ${rowColor}`;
        row.innerHTML = `
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm font-medium text-gray-900">${vehicle.mvFileNo || 'N/A'}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${vehicle.code || 'N/A'}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${vehicle.make || 'N/A'}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${vehicle.type || 'N/A'}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${vehicle.plateNo || 'N/A'}</div>
          </td>
          <td class="px-6 py-4 whitespace-nowrap">
            <div class="text-sm text-gray-900">${formatDueDate(vehicle.dueDate, isUpcoming, isWithinWeek)}</div>
          </td>
        `;
        tableBody.appendChild(row);
      });

      updatePaginationInfo(data.length, start, end);
      renderPagination(data.length);
    }

    // Update pagination info
    function updatePaginationInfo(total, start, end) {
      paginationInfo.innerHTML = `
        <p class="text-sm text-gray-700">
          Showing <span id="startIndex">${start + 1}</span> to <span id="endIndex">${Math.min(end, total)}</span> of <span id="totalResults">${total}</span> results
        </p>
      `;
    }

    // Render pagination buttons
    function renderPagination(totalItems) {
      paginationButtons.innerHTML = '';
      const totalPages = Math.ceil(totalItems / itemsPerPage);

      // Previous button
      const prevButton = document.createElement('button');
      prevButton.className = `pagination-btn px-3 py-1 rounded-lg border bg-white text-gray-700 ${currentPage === 1 ? 'disabled:opacity-50 disabled:cursor-not-allowed' : 'hover:bg-gray-100'}`;
      prevButton.disabled = currentPage === 1;
      prevButton.innerHTML = '<i class="fas fa-chevron-left"></i>';
      prevButton.addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage--;
          renderTable(filteredVehicles, currentPage);
        }
      });
      paginationButtons.appendChild(prevButton);

      // Page numbers
      for (let i = 1; i <= totalPages; i++) {
        const pageButton = document.createElement('button');
        pageButton.className = `pagination-btn px-3 py-1 rounded-lg border ${currentPage === i ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'}`;
        pageButton.textContent = i;
        pageButton.addEventListener('click', () => {
          currentPage = i;
          renderTable(filteredVehicles, currentPage);
        });
        paginationButtons.appendChild(pageButton);
      }

      // Next button
      const nextButton = document.createElement('button');
      nextButton.className = `pagination-btn px-3 py-1 rounded-lg border bg-white text-gray-700 ${currentPage === totalPages ? 'disabled:opacity-50 disabled:cursor-not-allowed' : 'hover:bg-gray-100'}`;
      nextButton.disabled = currentPage === totalPages;
      nextButton.innerHTML = '<i class="fas fa-chevron-right"></i>';
      nextButton.addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage++;
          renderTable(filteredVehicles, currentPage);
        }
      });
      paginationButtons.appendChild(nextButton);
    }

    // Filter and search functionality
    function applyFilters() {
      const searchTerm = searchInput.value.toLowerCase();
      const dueThisMonthChecked = dueThisMonth.checked;
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Normalize to start of day
      const currentMonth = today.toLocaleString('default', { month: 'long' });

      // Map vehicles with adjusted due dates
      let tempFiltered = vehicles.map(vehicle => ({
        ...vehicle,
        adjustedDueDate: fixPastDueDateToNextYear(vehicle.dueDate)
      })).filter(vehicle => {
        const dueDate = vehicle.adjustedDueDate;
        dueDate.setHours(0, 0, 0, 0);
        const dueMonth = dueDate.toLocaleString('default', { month: 'long' });
        const matchesSearch = (
          (vehicle.mvFileNo || '').toLowerCase().includes(searchTerm) ||
          (vehicle.code || '').toLowerCase().includes(searchTerm) ||
          (vehicle.make || '').toLowerCase().includes(searchTerm) ||
          (vehicle.type || '').toLowerCase().includes(searchTerm) ||
          (vehicle.plateNo || '').toLowerCase().includes(searchTerm) ||
          formatDueDate(vehicle.dueDate, dueDate >= today, dueDate <= new Date(today.getTime() + 7 * 24 * 60 * 60 * 1000)).toLowerCase().includes(searchTerm)
        );
        const matchesDueThisMonth = !dueThisMonthChecked || dueMonth.toLowerCase() === currentMonth.toLowerCase();
        return matchesSearch && matchesDueThisMonth;
      });

      // Prioritize top 4 upcoming vehicles within filtered results
      const upcoming = tempFiltered
        .filter(v => {
          const dueDate = v.adjustedDueDate;
          dueDate.setHours(0, 0, 0, 0);
          return dueDate >= today;
        })
        .sort((a, b) => a.adjustedDueDate - b.adjustedDueDate)
        .slice(0, 4); // Take top 4 upcoming
      const others = tempFiltered
        .filter(v => !upcoming.includes(v))
        .sort((a, b) => a.adjustedDueDate - b.adjustedDueDate);

      filteredVehicles = [...upcoming, ...others].map(v => ({
        mvFileNo: v.mvFileNo,
        code: v.code,
        make: v.make,
        type: v.type,
        plateNo: v.plateNo,
        dueDate: v.adjustedDueDate.toISOString().split('T')[0] // Use adjusted date
      }));
      currentPage = 1;
      renderTable(filteredVehicles, currentPage);
    }

    // Event listeners for filters and search
    searchInput.addEventListener('input', applyFilters);
    dueThisMonth.addEventListener('change', applyFilters);

    // Initial render
    try {
      prioritizeUpcomingVehicles();
      renderTable(filteredVehicles, currentPage);
    } catch (e) {
      console.error('Error during initial render:', e);
    }
  </script>
  <!-- Additional Stats -->
  <div class="mt-8 mb-10 grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900">Registration Distribution</h3>
      </div>
      <div class="h-64 flex items-end justify-between">
        <div class="flex flex-col items-center">
          <div class="w-10 bg-blue-500 rounded-t" style="height: 120px;"></div>
          <span class="mt-2 text-sm text-gray-600">Jan</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-green-500 rounded-t" style="height: 90px;"></div>
          <span class="mt-2 text-sm text-gray-600">Feb</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-yellow-500 rounded-t" style="height: 70px;"></div>
          <span class="mt-2 text-sm text-gray-600">Mar</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-blue-500 rounded-t" style="height: 150px;"></div>
          <span class="mt-2 text-sm text-gray-600">Apr</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-green-500 rounded-t" style="height: 100px;"></div>
          <span class="mt-2 text-sm text-gray-600">May</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-red-500 rounded-t" style="height: 60px;"></div>
          <span class="mt-2 text-sm text-gray-600">Jun</span>
        </div>
        <div class="flex flex-col items-center">
          <div class="w-10 bg-blue-500 rounded-t" style="height: 40px;"></div>
          <span class="mt-2 text-sm text-gray-600">Jul</span>
        </div>
      </div>
    </div>
    
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-medium text-gray-900">Vehicle Types</h3>
      </div>
      <div class="flex items-center justify-center">
        <div class="relative w-48 h-48">
          <div class="absolute inset-0 rounded-full bg-blue-500 opacity-20"></div>
          <div class="absolute inset-8 rounded-full bg-green-500 opacity-20"></div>
          <div class="absolute inset-16 rounded-full bg-yellow-500 opacity-20"></div>
          <div class="absolute inset-0 flex items-center justify-center">
            <div class="text-center">
              <p class="text-2xl font-bold" id="totalVehiclesDisplay"><?php echo count($vehicles); ?></p>
              <p class="text-gray-600">Total Vehicles</p>
            </div>
          </div>
        </div>
        <div class="ml-8">
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-blue-500 rounded mr-2"></div>
            <span class="text-sm">Trucks & Buses (11)</span>
          </div>
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-green-500 rounded mr-2"></div>
            <span class="text-sm">Pickups (7)</span>
          </div>
          <div class="flex items-center mb-3">
            <div class="w-4 h-4 bg-yellow-500 rounded mr-2"></div>
            <span class="text-sm">Motorcycles (5)</span>
          </div>
          <div class="flex items-center">
            <div class="w-4 h-4 bg-red-500 rounded mr-2"></div>
            <span class="text-sm">Other (3)</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>