<?php
require_once '../config/conn.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infrastructure Management</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * { font-family: 'Inter', sans-serif; }
    
    .fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .slide-in { animation: slideIn 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }
    
    .card { 
        @apply bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-xl;
    }
    
    .table-row { 
        @apply hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent transition-all duration-200 border-b border-gray-100;
    }
    
    .badge { 
        @apply inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm;
    }
    .badge-green { @apply bg-gradient-to-r from-emerald-100 to-emerald-50 text-emerald-800 border border-emerald-200; }
    .badge-blue { @apply bg-gradient-to-r from-blue-100 to-blue-50 text-blue-800 border border-blue-200; }
    .badge-yellow { @apply bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-800 border border-yellow-200; }
    .badge-orange { @apply bg-gradient-to-r from-orange-100 to-orange-50 text-orange-800 border border-orange-200; }
    .badge-red { @apply bg-gradient-to-r from-red-100 to-red-50 text-red-800 border border-red-200; }
    
    .action-btn {
        @apply w-9 h-9 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110;
    }
    .action-btn-edit {
        @apply action-btn bg-blue-50 text-blue-600 hover:bg-blue-100;
    }
    .action-btn-delete {
        @apply action-btn bg-red-50 text-red-600 hover:bg-red-100;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
    
    .pagination-btn {
        @apply px-4 py-2 rounded-lg font-medium transition-all duration-300;
    }
    .pagination-btn-active {
        @apply bg-gradient-to-r from-indigo-500 to-indigo-600 text-white shadow-lg;
    }
    .pagination-btn-inactive {
        @apply bg-white text-gray-700 hover:bg-gray-50 border-2 border-gray-200;
    }

    .visible-select, 
    .visible-select option {
        color: #1f2937 !important;
        background-color: #ffffff !important;
        -webkit-text-fill-color: #1f2937 !important;
        appearance: auto !important;
    }

    .progress-bar-container {
        @apply w-full bg-gray-200 rounded-full h-3 overflow-hidden;
    }
    
    .progress-bar {
        @apply h-full transition-all duration-500 ease-out rounded-full;
    }
    
</style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">

<div class="flex h-screen overflow-hidden">
    <?php include 'bar/sidebar.php'; ?>

    <div class="flex-1 flex flex-col overflow-auto">
        <?php include 'bar/header.php'; ?>

        <main class="p-8 space-y-8 fade-in">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6">
                <div class="slide-in">
                    <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="w-12 h-12 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-building text-white text-xl"></i>
                        </div>
                        Infrastructure Management
                    </h1>
                    <p class="text-gray-600 mt-2 ml-1">Track and manage your farm's infrastructure projects</p>
                </div>
                <button 
                  onclick="openAddModal()" 
                  class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 whitespace-nowrap"
                >
                  <i class="fas fa-plus-circle text-lg"></i>
                  Add New Project
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Projects</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="totalProjects">0</h3>
                    <p class="text-xs text-gray-500 mt-1">All infrastructure</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-building text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Completed</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="completedCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">100% progress</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">In Progress</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="inProgressCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Active projects</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-tasks text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Categories</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="projectTypes">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Project types</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-layer-group text-white text-2xl"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filters Section -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
              <div class="flex flex-col lg:flex-row justify-between gap-6">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-layer-group text-indigo-500"></i> Category
                    </label>
                    <select id="filterType" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                      <option value="">All Categories</option>
                    </select>
                  </div>

                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-flag text-indigo-500"></i> Status
                    </label>
                    <select id="filterStatus" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                      <option value="">All Status</option>
                      <option value="Planning">Planning</option>
                      <option value="In Progress">In Progress</option>
                      <option value="Completed">Completed</option>
                      <option value="On Hold">On Hold</option>
                    </select>
                  </div>

                  <div class="flex items-end">
                    <button onclick="applyFilters()" class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-medium py-2.5 rounded-xl flex items-center justify-center gap-2 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md transition-all duration-300">
                      <i class="fas fa-filter"></i> Apply Filters
                    </button>
                  </div>
                </div>

                <div class="lg:w-80">
                  <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-search text-indigo-500"></i> Search
                  </label>
                  <div class="relative">
                    <input 
                      type="text" 
                      id="searchInput" 
                      placeholder="Search by name, category, or ID..." 
                      class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    >
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700">
                  <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 sticky top-0 z-10">
                    <tr>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-hashtag mr-2 text-indigo-500"></i>ID
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-tag mr-2 text-indigo-500"></i>Project Name
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-layer-group mr-2 text-indigo-500"></i>Category
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-flag mr-2 text-indigo-500"></i>Status
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-chart-line mr-2 text-indigo-500"></i>Progress
                      </th>
                      <th class="px-6 py-4 text-center font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-cog mr-2 text-indigo-500"></i>Actions
                      </th>
                    </tr>
                  </thead>

                  <tbody id="infrastructureTableBody" class="divide-y divide-gray-100 bg-white">
                    <tr>
                      <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-spinner animate-spin text-indigo-600 text-2xl"></i>
                          </div>
                          <p class="text-gray-500 font-medium">Loading projects...</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Pagination -->
              <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 px-6 py-5 gap-4 bg-gray-50">
                <div class="text-sm text-gray-600 font-medium">
                  Showing <span class="font-bold text-indigo-600" id="paginationStart">0</span> to 
                  <span class="font-bold text-indigo-600" id="paginationEnd">0</span> of 
                  <span class="font-bold text-indigo-600" id="paginationTotal">0</span> results
                </div>

                <div class="flex items-center gap-2">
                  <button id="prevPage" 
                    class="px-4 py-2 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center gap-2 hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed" 
                    disabled>
                    <i class="fas fa-chevron-left text-indigo-500"></i> Previous
                  </button>

                  <div id="paginationNumbers" class="flex gap-1"></div>

                  <button id="nextPage" 
                    class="px-4 py-2 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center gap-2 hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed" 
                    disabled>
                    Next <i class="fas fa-chevron-right text-indigo-500"></i>
                  </button>
                </div>
              </div>
            </div>
        </main>
    </div>
</div>

<!-- Add/Edit Project Modal -->
<div id="infrastructureModal" class="fixed inset-0 bg-black/40 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-8 py-5 flex items-center justify-between">
      <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center gap-3">
        <i class="fas fa-building text-white"></i> Add Infrastructure Project
      </h3>
      <button type="button" onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="infrastructureForm" class="p-8 space-y-6">
      <input type="hidden" id="infrastructureId">

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-tag text-indigo-500"></i>
          Project Name <span class="text-red-500">*</span>
        </label>
        <input type="text" id="infrastructureName" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Enter project name">
      </div>

      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
            <i class="fas fa-layer-group text-indigo-500"></i>
            Category <span class="text-red-500">*</span>
          </label>
          <button type="button" onclick="openAddInfrastructureTypeModal()"
            class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Add New Category
          </button>
        </div>
        <select id="infrastructureType" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white text-gray-800 visible-select focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <option value="">Select Category</option>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-flag text-indigo-500"></i>
          Status <span class="text-red-500">*</span>
        </label>
        <select id="status" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white text-gray-800 visible-select focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
          <option value="">Select Status</option>
          <option value="Planning">Planning</option>
          <option value="In Progress">In Progress</option>
          <option value="Completed">Completed</option>
          <option value="On Hold">On Hold</option>
          <option value="Operational">Operational</option>
          <option value="Need Repair">Need Repair</option>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-chart-line text-indigo-500"></i>
          Progress Percentage <span class="text-red-500">*</span>
        </label>
        <div class="flex items-center gap-4">
          <input type="range" id="progressPercent" min="0" max="100" value="0" required
            class="flex-1 h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer"
            oninput="updateProgressDisplay(this.value)">
          <span id="progressDisplay" class="text-2xl font-bold text-indigo-600 min-w-[4rem] text-right">0%</span>
        </div>
        <input type="number" id="progressPercentInput" min="0" max="100" value="0" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition mt-3"
          placeholder="Or enter exact percentage"
          oninput="updateProgressSlider(this.value)">
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-sticky-note text-indigo-500"></i>
          Details <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        <textarea id="details" rows="4"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Add project details..."></textarea>
      </div>

      <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <button type="button" onclick="closeModal()" 
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" 
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-medium flex items-center gap-2 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Project
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Add Category Modal -->
<div id="addInfrastructureTypeModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[60] backdrop-blur-sm">
  <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-6 py-4 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white flex items-center gap-2">
        <i class="fas fa-plus-circle"></i> Add New Category
      </h2>
      <button type="button" onclick="closeAddInfrastructureTypeModal()" class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="addInfrastructureTypeForm" class="p-6 space-y-4">
      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-layer-group text-indigo-500"></i> Category Name <span class="text-red-500">*</span>
        </label>
        <input type="text" id="newInfrastructureTypeName" placeholder="Enter category name" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
        <p id="infrastructureTypeError" class="text-red-500 text-sm mt-1 hidden"></p>
      </div>
      
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <button type="button" onclick="closeAddInfrastructureTypeModal()" 
          class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" id="saveInfrastructureTypeBtn"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-medium flex items-center gap-2 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Category
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let currentPage = 1;
const itemsPerPage = 10;
let totalItems = 0;
let allInfrastructure = [];
let filteredInfrastructure = [];

document.addEventListener('DOMContentLoaded', () => {
    console.log('Page loaded, initializing...');
    loadInfrastructure();
    setupEventListeners();
    fetchInfrastructureTypes();
});

function setupEventListeners() {
    document.getElementById('searchInput').addEventListener('input', function() {
        applyFilters();
    });
    
    document.getElementById('prevPage').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable();
        }
    });
    
    document.getElementById('nextPage').addEventListener('click', () => {
        if (currentPage < Math.ceil(filteredInfrastructure.length / itemsPerPage)) {
            currentPage++;
            renderTable();
        }
    });
}

function updateProgressDisplay(value) {
    document.getElementById('progressDisplay').textContent = value + '%';
    document.getElementById('progressPercentInput').value = value;
}

function updateProgressSlider(value) {
    if (value < 0) value = 0;
    if (value > 100) value = 100;
    document.getElementById('progressPercent').value = value;
    document.getElementById('progressPercentInput').value = value;
    document.getElementById('progressDisplay').textContent = value + '%';
}

function openAddInfrastructureTypeModal() {
    const modal = document.getElementById('addInfrastructureTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('newInfrastructureTypeName').focus();
}

function closeAddInfrastructureTypeModal() {
    const modal = document.getElementById('addInfrastructureTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('addInfrastructureTypeForm').reset();
    document.getElementById('infrastructureTypeError').classList.add('hidden');
}

function fetchInfrastructureTypes() {
    console.log('Fetching infrastructure categories...');
    
    fetch('api/fetch_infrastructure_types.php')
        .then(res => {
            console.log('Categories API Response Status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Categories API Data:', data);
            
            const select = document.getElementById('infrastructureType');
            const filterSelect = document.getElementById('filterType');
            const currentValue = select.value;
            
            select.innerHTML = '<option value="">Select Category</option>';
            filterSelect.innerHTML = '<option value="">All Categories</option>';

            // Handle both array and object responses
            const categories = Array.isArray(data) ? data : (data.data || []);
            
            categories.forEach(category => {
                const opt = document.createElement('option');
                opt.value = category;
                opt.textContent = category;
                select.appendChild(opt.cloneNode(true));
                
                const filterOpt = document.createElement('option');
                filterOpt.value = category;
                filterOpt.textContent = category;
                filterSelect.appendChild(filterOpt);
            });
            
            if (currentValue) {
                select.value = currentValue;
            }
            
            console.log('Successfully loaded', categories.length, 'infrastructure categories');
        })
        .catch(err => {
            console.error('Error fetching categories:', err);
            showAlert('Warning', 'Could not load infrastructure categories', 'warning');
        });
}

document.getElementById('addInfrastructureTypeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const name = document.getElementById('newInfrastructureTypeName').value.trim();
    const errorEl = document.getElementById('infrastructureTypeError');
    const submitBtn = document.getElementById('saveInfrastructureTypeBtn');
    const originalBtnText = submitBtn.innerHTML;
    
    if (name === '') {
        errorEl.textContent = 'Please enter a category name.';
        errorEl.classList.remove('hidden');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Saving...';
    errorEl.classList.add('hidden');
    
    fetch('api/insert_infrastructure_type.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'infrastructure_type_name=' + encodeURIComponent(name)
    })
    .then(res => res.json())
    .then(data => {
        console.log('Insert category response:', data);
        
        if (data.status === 'exists') {
            errorEl.textContent = 'Category already exists.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        } else if (data.status === 'success' || data.success) {
            showAlert('Success!', 'Category added successfully!', 'success');
            closeAddInfrastructureTypeModal();
            
            // Reload categories
            fetchInfrastructureTypes();
            
            setTimeout(() => {
                document.getElementById('infrastructureType').value = name;
            }, 100);
        } else {
            errorEl.textContent = data.message || 'Something went wrong. Please try again.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(err => {
        console.error('Error inserting category:', err);
        errorEl.textContent = 'Server error. Please try again.';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function loadInfrastructure() {
    console.log('Loading infrastructure data...');
    showLoadingSpinner();
    
    fetch('api/infrastructure_api.php?action=list')
    .then(response => {
        console.log('Infrastructure API Response Status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw API Response:', text);
        
        try {
            const data = JSON.parse(text);
            console.log('Parsed API Response:', data);
            
            if (data.success === true) {
                if (data.data && Array.isArray(data.data)) {
                    allInfrastructure = data.data;
                    console.log('Loaded', allInfrastructure.length, 'infrastructure projects');
                } else {
                    allInfrastructure = [];
                    console.warn('No data array in response');
                }
            } else {
                console.error('API returned success: false', data);
                allInfrastructure = [];
            }
            
            updateStats();
            populateTypeFilter();
            applyFilters();
            
        } catch (parseError) {
            console.error('JSON Parse Error:', parseError);
            console.error('Response text:', text);
            throw new Error('Invalid JSON response from server');
        }
    })
    .catch(error => {
        console.error('Error loading infrastructure:', error);
        showAlert('Error', 'Failed to load projects: ' + error.message, 'error');
        allInfrastructure = [];
        updateStats();
        renderTable();
    });
}

function showLoadingSpinner() {
    const tbody = document.getElementById('infrastructureTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-spinner animate-spin text-indigo-600 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Loading projects...</p>
                </div>
            </td>
        </tr>`;
}

function populateTypeFilter() {
    const typeFilter = document.getElementById('filterType');
    const types = [...new Set(allInfrastructure.map(i => i.infrastructure_type))].sort();
    
    const currentValue = typeFilter.value;
    typeFilter.innerHTML = '<option value="">All Categories</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeFilter.appendChild(option);
    });
    
    if (currentValue) {
        typeFilter.value = currentValue;
    }
}

function applyFilters() {
    let filtered = [...allInfrastructure];

    const type = document.getElementById('filterType').value;
    if (type) {
        filtered = filtered.filter(i => i.infrastructure_type === type);
    }

    const status = document.getElementById('filterStatus').value;
    if (status) {
        filtered = filtered.filter(i => i.status === status);
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(infrastructure => 
            infrastructure.infrastructure_name.toLowerCase().includes(searchTerm) ||
            infrastructure.infrastructure_type.toLowerCase().includes(searchTerm) ||
            infrastructure.id.toString().includes(searchTerm) ||
            (infrastructure.details && infrastructure.details.toLowerCase().includes(searchTerm))
        );
    }

    filteredInfrastructure = filtered;
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('infrastructureTableBody');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredInfrastructure.length);
    const pageInfrastructure = filteredInfrastructure.slice(startIndex, endIndex);
    
    if (pageInfrastructure.length) {
        tbody.innerHTML = pageInfrastructure.map(infra => {
            const statusBadge = getStatusBadge(infra.status);
            const progressBar = getProgressBar(infra.progress_percent);
            
            const safeName = escapeHtml(infra.infrastructure_name);
            const safeType = escapeHtml(infra.infrastructure_type);
            const safeDetails = infra.details ? escapeHtml(infra.details) : '';
            
            return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    <span class="font-bold text-gray-900">#${infra.id}</span>
                </td>
                <td class="px-6 py-4">
                    <div>
                        <span class="font-semibold text-gray-800">${safeName}</span>
                        ${safeDetails ? `<p class="text-xs text-gray-500 mt-1">${safeDetails.substring(0, 50)}${safeDetails.length > 50 ? '...' : ''}</p>` : ''}
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="badge badge-blue">
                        <i class="fas fa-layer-group mr-1"></i>${safeType}
                    </span>
                </td>
                <td class="px-6 py-4">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4">
                    ${progressBar}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="editInfrastructure(${infra.id})" 
                            class="action-btn-edit" title="Edit Project">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteInfrastructure(${infra.id})" 
                            class="action-btn-delete" title="Delete Project">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `}).join('');
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-16">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium text-lg">No projects found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or add a new project</p>
                    </div>
                </td>
            </tr>`;
    }
    
    updatePagination();
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getStatusBadge(status) {
    const badges = {
        'Planning': '<span class="badge badge-yellow"><i class="fas fa-clipboard-list mr-1"></i>Planning</span>',
        'In Progress': '<span class="badge badge-blue"><i class="fas fa-tasks mr-1"></i>In Progress</span>',
        'Completed': '<span class="badge badge-green"><i class="fas fa-check-circle mr-1"></i>Completed</span>',
        'On Hold': '<span class="badge badge-red"><i class="fas fa-pause-circle mr-1"></i>On Hold</span>',
        'Operational': '<span class="badge badge-green"><i class="fas fa-check-circle mr-1"></i>Operational</span>',
        'Need Repair': '<span class="badge badge-yellow"><i class="fas fa-tools mr-1"></i>Need Repair</span>'
    };
    return badges[status] || '<span class="badge badge-blue">' + escapeHtml(status) + '</span>';
}

function getProgressBar(percent) {
    percent = parseInt(percent) || 0;
    let color = 'bg-red-500';
    if (percent >= 75) color = 'bg-emerald-500';
    else if (percent >= 50) color = 'bg-blue-500';
    else if (percent >= 25) color = 'bg-yellow-500';
    
    return `
        <div class="flex items-center gap-3">
            <div class="progress-bar-container flex-1">
                <div class="progress-bar ${color}" style="width: ${percent}%"></div>
            </div>
            <span class="text-sm font-bold text-gray-700 min-w-[3rem] text-right">${percent}%</span>
        </div>
    `;
}

function updatePagination() {
    const totalPages = Math.ceil(filteredInfrastructure.length / itemsPerPage);
    const startIndex = filteredInfrastructure.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredInfrastructure.length);
    
    document.getElementById('paginationStart').textContent = startIndex;
    document.getElementById('paginationEnd').textContent = endIndex;
    document.getElementById('paginationTotal').textContent = filteredInfrastructure.length;
    
    document.getElementById('prevPage').disabled = currentPage === 1;
    document.getElementById('nextPage').disabled = currentPage === totalPages || totalPages === 0;
    
    const paginationNumbers = document.getElementById('paginationNumbers');
    paginationNumbers.innerHTML = '';
    
    if (totalPages > 0) {
        addPageNumber(1, currentPage === 1);
        
        if (currentPage > 3) {
            paginationNumbers.innerHTML += '<span class="px-3 py-2 text-gray-400">...</span>';
        }
        
        for (let i = Math.max(2, currentPage - 1); i <= Math.min(totalPages - 1, currentPage + 1); i++) {
            if (i !== 1 && i !== totalPages) {
                addPageNumber(i, i === currentPage);
            }
        }
        
        if (currentPage < totalPages - 2) {
            paginationNumbers.innerHTML += '<span class="px-3 py-2 text-gray-400">...</span>';
        }
        
        if (totalPages > 1) {
            addPageNumber(totalPages, currentPage === totalPages);
        }
    }
}

function addPageNumber(page, isActive) {
    const paginationNumbers = document.getElementById('paginationNumbers');
    const button = document.createElement('button');
    button.className = isActive 
        ? 'pagination-btn pagination-btn-active' 
        : 'pagination-btn pagination-btn-inactive';
    button.textContent = page;
    button.addEventListener('click', () => {
        currentPage = page;
        renderTable();
    });
    paginationNumbers.appendChild(button);
}

function updateStats() {
    document.getElementById('totalProjects').textContent = allInfrastructure.length;
    
    const completed = allInfrastructure.filter(i => i.status === 'Completed' || parseInt(i.progress_percent) === 100);
    document.getElementById('completedCount').textContent = completed.length;
    
    const inProgress = allInfrastructure.filter(i => {
        const progress = parseInt(i.progress_percent) || 0;
        return i.status === 'In Progress' || (progress > 0 && progress < 100);
    });
    document.getElementById('inProgressCount').textContent = inProgress.length;
    
    const types = [...new Set(allInfrastructure.map(i => i.infrastructure_type))];
    document.getElementById('projectTypes').textContent = types.length;
}

function openAddModal() {
    document.getElementById('infrastructureForm').reset();
    document.getElementById('infrastructureId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Infrastructure Project';
    updateProgressDisplay(0);
    
    document.getElementById('infrastructureModal').classList.remove('hidden');
    document.getElementById('infrastructureModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('infrastructureModal').classList.add('hidden');
    document.getElementById('infrastructureModal').classList.remove('flex');
}

document.getElementById('infrastructureForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const infrastructureId = document.getElementById('infrastructureId').value;
    
    const data = {
        id: infrastructureId || undefined,
        infrastructure_name: document.getElementById('infrastructureName').value.trim(),
        infrastructure_type: document.getElementById('infrastructureType').value,
        status: document.getElementById('status').value,
        progress_percent: parseInt(document.getElementById('progressPercent').value),
        details: document.getElementById('details').value.trim() || ''
    };
    
    console.log('Submitting data:', data);
    
    const url = `api/infrastructure_api.php?action=${infrastructureId ? 'update' : 'add'}`;
    
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Saving...';
    submitButton.disabled = true;
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => {
        console.log('Save response status:', response.status);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(result => {
        console.log('Save API Result:', result);
        
        if (result.success) {
            showAlert('Success!', `Project ${infrastructureId ? 'updated' : 'added'} successfully!`, 'success');
            closeModal();
            loadInfrastructure();
        } else {
            showAlert('Error', result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving project:', error);
        showAlert('Error', 'Failed to save project: ' + error.message, 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

function editInfrastructure(id) {
    const infrastructure = allInfrastructure.find(i => i.id == id);
    if (!infrastructure) {
        showAlert('Error', 'Project not found', 'error');
        return;
    }
    
    document.getElementById('infrastructureId').value = infrastructure.id;
    document.getElementById('infrastructureName').value = infrastructure.infrastructure_name;
    document.getElementById('infrastructureType').value = infrastructure.infrastructure_type;
    document.getElementById('status').value = infrastructure.status;
    document.getElementById('progressPercent').value = infrastructure.progress_percent;
    document.getElementById('progressPercentInput').value = infrastructure.progress_percent;
    updateProgressDisplay(infrastructure.progress_percent);
    document.getElementById('details').value = infrastructure.details || '';
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Infrastructure Project';
    document.getElementById('infrastructureModal').classList.remove('hidden');
    document.getElementById('infrastructureModal').classList.add('flex');
}

function deleteInfrastructure(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This project will be permanently deleted!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: '<i class="fas fa-trash mr-2"></i>Yes, delete it!',
        cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
        customClass: {
            popup: 'rounded-2xl',
            confirmButton: 'rounded-xl font-semibold',
            cancelButton: 'rounded-xl font-semibold'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`api/infrastructure_api.php?action=delete&id=${id}`, {
                method: 'DELETE'
            })
            .then(response => {
                console.log('Delete response status:', response.status);
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(result => {
                console.log('Delete result:', result);
                
                if (result.success) {
                    showAlert('Deleted!', 'Project has been deleted successfully.', 'success');
                    loadInfrastructure();
                } else {
                    showAlert('Error', result.message || 'Failed to delete project', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting project:', error);
                showAlert('Error', 'Failed to delete project: ' + error.message, 'error');
            });
        }
    });
}

function showAlert(title, text, icon) {
    Swal.fire({
        title,
        text,
        icon,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'rounded-2xl shadow-2xl',
        },
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeModal();
        closeAddInfrastructureTypeModal();
    }
});

document.getElementById('infrastructureModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('addInfrastructureTypeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddInfrastructureTypeModal();
    }
});
</script>
</body>
</html>