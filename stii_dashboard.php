<?php
include 'conn.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>STII Enrollment Dashboard</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .card-hover:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
  </style>
</head>
<body class="bg-gray-50">
  <div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
      <div>
        <h1 class="text-2xl font-bold text-gray-800">STII Enrollment Dashboard</h1>
        <p class="text-gray-600">Comprehensive management of school operations and enrollment</p>
      </div>
      <div class="mt-4 md:mt-0 flex items-center space-x-4">
        <div class="relative">
          <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
          <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
        </div>
        <div class="text-sm bg-green-100 text-green-800 px-3 py-1 rounded-full">
          <i class="fas fa-graduation-cap mr-1"></i> Education
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Total Students</p>
            <h3 class="text-2xl font-bold mt-1">856</h3>
          </div>
          <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
            <i class="fas fa-user-graduate text-blue-600"></i>
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Teachers</p>
            <h3 class="text-2xl font-bold mt-1">42</h3>
          </div>
          <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
            <i class="fas fa-chalkboard-teacher text-green-600"></i>
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">School Buses</p>
            <h3 class="text-2xl font-bold mt-1">8</h3>
          </div>
          <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
            <i class="fas fa-bus text-amber-600"></i>
          </div>
        </div>
      </div>
      <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <div class="flex justify-between">
          <div>
            <p class="text-sm font-medium text-gray-500">Campuses</p>
            <h3 class="text-2xl font-bold mt-1">3</h3>
          </div>
          <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
            <i class="fas fa-school text-purple-600"></i>
          </div>
        </div>
      </div>
    </div>

    <!-- Main Content Tabs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
      <div class="border-b border-gray-200">
        <nav class="flex overflow-x-auto">
          <button onclick="openTab(event, 'students')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-green-500 text-green-600 whitespace-nowrap">
            <i class="fas fa-user-graduate mr-2"></i> Students
          </button>
          <button onclick="openTab(event, 'teachers')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
            <i class="fas fa-chalkboard-teacher mr-2"></i> Teachers
          </button>
          <button onclick="openTab(event, 'buses')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
            <i class="fas fa-bus mr-2"></i> School Buses
          </button>
          <button onclick="openTab(event, 'campuses')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
            <i class="fas fa-school mr-2"></i> Campuses
          </button>
          <button onclick="openTab(event, 'land')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
            <i class="fas fa-map-marked-alt mr-2"></i> Land Titles
          </button>
          <button onclick="openTab(event, 'maintenance')" class="tab-button py-3 px-6 text-center border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 whitespace-nowrap">
            <i class="fas fa-tools mr-2"></i> Bus Maintenance
          </button>
        </nav>
      </div>
      
      <!-- Students Tab -->
      <div id="students" class="tab-content p-6 active">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Student Enrollment Records</h3>
          <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Enroll Student
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Full Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Program</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrollment Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">ST-2023-0451</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">John Smith</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Computer Science</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-01-15</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">ST-2023-0452</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Sarah Johnson</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Business Administration</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-02-20</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">On Leave</span>
                </td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Teachers Tab -->
      <div id="teachers" class="tab-content p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Faculty Members</h3>
          <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Teacher
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Teacher ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Joined</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">TC-2020-001</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Dr. Robert Chen</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Computer Science</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2020-08-15</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">TC-2021-012</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Prof. Maria Gonzalez</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Business</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2021-01-10</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">On Leave</span>
                </td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- School Buses Tab -->
      <div id="buses" class="tab-content p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">School Bus Fleet</h3>
          <div>
            <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
              <i class="fas fa-plus mr-2"></i> Add Bus
            </button>
          </div>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bus ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plate Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Capacity</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned Route</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">BUS-001</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">ABC 1234</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">30 students</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Titay</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-06-15</td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">BUS-002</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">XYZ 5678</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">24 students</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Tungawan</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-05-22</td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Campuses Tab -->
      <div id="campuses" class="tab-content p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">School Campuses</h3>
          <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Campus
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Campus ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Buildings</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Students</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CMP-001</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Main Campus, Downtown</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">650</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Operational</span>
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CMP-002</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">North Campus</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">206</td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Operational</span>
                </td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Land Titles Tab -->
      <div id="land" class="tab-content p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Land Title Records</h3>
          <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Record
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title Number</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Area (acres)</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acquisition Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documents</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">LT-2020-001</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Downtown District</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5.2</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2020-01-15</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <a href="#" class="text-green-600 hover:text-green-900">View</a>
                </td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">LT-2021-002</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">North District</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3.8</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2021-03-10</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  <a href="#" class="text-green-600 hover:text-green-900">View</a>
                </td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Bus Maintenance Tab -->
      <div id="maintenance" class="tab-content p-6">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-lg font-semibold text-gray-800">Bus Maintenance Records</h3>
          <button class="text-sm bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg flex items-center">
            <i class="fas fa-plus mr-2"></i> Add Record
          </button>
        </div>
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service ID</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bus</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Service Type</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost</th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">SV-2023-0715</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">BUS-001 (ABC 1234)</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Oil Change</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-07-15</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$120.00</td>
              </tr>
              <tr>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">SV-2023-0622</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">BUS-002 (XYZ 5678)</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Tire Replacement</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2023-06-22</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">$350.00</td>
              </tr>
              <!-- More rows... -->
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script>
    function openTab(evt, tabName) {
      // Hide all tab content
      var tabcontent = document.getElementsByClassName("tab-content");
      for (var i = 0; i < tabcontent.length; i++) {
        tabcontent[i].classList.remove("active");
      }

      // Remove active class from all tab buttons
      var tabbuttons = document.getElementsByClassName("tab-button");
      for (var i = 0; i < tabbuttons.length; i++) {
        tabbuttons[i].classList.remove("border-green-500", "text-green-600");
        tabbuttons[i].classList.add("border-transparent", "text-gray-500");
      }

      // Show the current tab and add active class to button
      document.getElementById(tabName).classList.add("active");
      evt.currentTarget.classList.add("border-green-500", "text-green-600");
      evt.currentTarget.classList.remove("border-transparent", "text-gray-500");
    }
  </script>
</body>
</html>