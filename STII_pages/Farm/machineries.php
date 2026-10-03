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
<title>Machinery Management</title>
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
    
    .btn-primary { 
        @apply bg-gradient-to-r from-emerald-500 to-emerald-600 text-white px-6 py-3 rounded-xl font-semibold shadow-lg shadow-emerald-500/30 hover:shadow-xl hover:shadow-emerald-500/40 hover:from-emerald-600 hover:to-emerald-700 transition-all duration-300 transform hover:-translate-y-0.5;
    }
    .btn-secondary { 
        @apply bg-gradient-to-r from-blue-500 to-blue-600 text-white px-5 py-2.5 rounded-xl font-medium shadow-md hover:shadow-lg hover:from-blue-600 hover:to-blue-700 transition-all duration-300;
    }
    .btn-outline { 
        @apply border-2 border-gray-300 text-gray-700 px-5 py-2.5 rounded-xl font-medium hover:bg-gray-50 hover:border-gray-400 transition-all duration-300;
    }
    .btn-danger {
        @apply bg-gradient-to-r from-red-500 to-red-600 text-white px-5 py-2.5 rounded-xl font-medium shadow-md hover:shadow-lg hover:from-red-600 hover:to-red-700 transition-all duration-300;
    }
    
    .card { 
        @apply bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-xl;
    }
    
    .stat-card {
        @apply card p-6 relative overflow-hidden;
    }
    
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: linear-gradient(135deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
        border-radius: 0 0 0 100%;
    }
    
    .table-row { 
        @apply hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent transition-all duration-200 border-b border-gray-100;
    }
    
    .badge { 
        @apply inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm;
    }
    .badge-green { @apply bg-gradient-to-r from-emerald-100 to-emerald-50 text-emerald-800 border border-emerald-200; }
    .badge-blue { @apply bg-gradient-to-r from-blue-100 to-blue-50 text-blue-800 border border-blue-200; }
    .badge-purple { @apply bg-gradient-to-r from-purple-100 to-purple-50 text-purple-800 border border-purple-200; }
    .badge-orange { @apply bg-gradient-to-r from-orange-100 to-orange-50 text-orange-800 border border-orange-200; }
    .badge-yellow { @apply bg-gradient-to-r from-yellow-100 to-yellow-50 text-yellow-800 border border-yellow-200; }
    .badge-red { @apply bg-gradient-to-r from-red-100 to-red-50 text-red-800 border border-red-200; }
    
    .modal-backdrop {
        backdrop-filter: blur(8px);
        background: rgba(0, 0, 0, 0.5);
    }
    
    .input-modern {
        @apply w-full rounded-xl border-2 border-gray-200 px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-300;
    }
    
    .select-modern {
        @apply w-full rounded-xl border-2 border-gray-200 px-4 py-3 focus:ring-4 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all duration-300 bg-white;
    }
    
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
        @apply bg-gradient-to-r from-emerald-500 to-emerald-600 text-white shadow-lg;
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
                        <div class="w-12 h-12 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl flex items-center justify-center shadow-lg">
                            <i class="fas fa-tractor text-white text-xl"></i>
                        </div>
                        Machinery Management
                    </h1>
                    <p class="text-gray-600 mt-2 ml-1">Track and manage your farm's machinery inventory</p>
                </div>
                <button 
                  onclick="openAddModal()" 
                  class="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 whitespace-nowrap"
                >
                  <i class="fas fa-plus-circle text-lg"></i>
                  Add New Machinery
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Machinery</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="totalMachinery">0</h3>
                    <p class="text-xs text-gray-500 mt-1">All equipment</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-tractor text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Operational</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="operationalCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Working condition</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-check-circle text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Under Repair</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="repairCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Maintenance needed</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-wrench text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Machinery Types</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="machineryTypes">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Unique categories</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-layer-group text-white text-2xl"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
              <div class="flex flex-col lg:flex-row justify-between gap-6">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-tools text-teal-500"></i> Machinery Type
                    </label>
                    <select id="filterType" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                      <option value="">All Types</option>
                    </select>
                  </div>

                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-flag text-teal-500"></i> Status
                    </label>
                    <select id="filterStatus" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                      <option value="">All Status</option>
                      <option value="Operational">Operational</option>
                      <option value="Under Repair">Under Repair</option>
                      <option value="Out of Service">Out of Service</option>
                    </select>
                  </div>

                  <div class="flex items-end">
                    <button onclick="applyFilters()" class="w-full bg-gradient-to-r from-teal-500 to-teal-600 text-white font-medium py-2.5 rounded-xl flex items-center justify-center gap-2 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition-all duration-300">
                      <i class="fas fa-filter"></i> Apply Filters
                    </button>
                  </div>
                </div>

                <div class="lg:w-80">
                  <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-search text-teal-500"></i> Search
                  </label>
                  <div class="relative">
                    <input 
                      type="text" 
                      id="searchInput" 
                      placeholder="Search by name, type, or ID..." 
                      class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
                    >
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700">
                  <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 sticky top-0 z-10">
                    <tr>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-hashtag mr-2 text-teal-500"></i>ID
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-tag mr-2 text-teal-500"></i>Name
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-tools mr-2 text-teal-500"></i>Type
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-sort-numeric-up mr-2 text-teal-500"></i>Quantity
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-flag mr-2 text-teal-500"></i>Status
                      </th>
                      <th class="px-6 py-4 text-center font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-cog mr-2 text-teal-500"></i>Actions
                      </th>
                    </tr>
                  </thead>

                  <tbody id="machineryTableBody" class="divide-y divide-gray-100 bg-white">
                    <tr>
                      <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-spinner animate-spin text-teal-600 text-2xl"></i>
                          </div>
                          <p class="text-gray-500 font-medium">Loading machinery...</p>
                        </div>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div class="flex flex-col sm:flex-row items-center justify-between border-t border-gray-100 px-6 py-5 gap-4 bg-gray-50">
                <div class="text-sm text-gray-600 font-medium">
                  Showing <span class="font-bold text-teal-600" id="paginationStart">0</span> to 
                  <span class="font-bold text-teal-600" id="paginationEnd">0</span> of 
                  <span class="font-bold text-teal-600" id="paginationTotal">0</span> results
                </div>

                <div class="flex items-center gap-2">
                  <button id="prevPage" 
                    class="px-4 py-2 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center gap-2 hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed" 
                    disabled>
                    <i class="fas fa-chevron-left text-teal-500"></i> Previous
                  </button>

                  <div id="paginationNumbers" class="flex gap-1"></div>

                  <button id="nextPage" 
                    class="px-4 py-2 rounded-xl border border-gray-300 text-gray-600 text-sm font-medium flex items-center gap-2 hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed" 
                    disabled>
                    Next <i class="fas fa-chevron-right text-teal-500"></i>
                  </button>
                </div>
              </div>
            </div>
        </main>
    </div>
</div>

<div id="machineryModal" class="fixed inset-0 bg-black/40 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-8 py-5 flex items-center justify-between">
      <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center gap-3">
        <i class="fas fa-tractor text-white"></i> Add Machinery
      </h3>
      <button type="button" onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="machineryForm" class="p-8 space-y-6">
      <input type="hidden" id="machineryId">

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-tag text-teal-500"></i>
          Machinery Name <span class="text-red-500">*</span>
        </label>
        <input type="text" id="machineryName" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
          placeholder="Enter machinery name">
      </div>

      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
            <i class="fas fa-tools text-teal-500"></i>
            Machinery Type <span class="text-red-500">*</span>
          </label>
          <button type="button" onclick="openAddMachineryTypeModal()"
            class="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Add Custom Type
          </button>
        </div>
        <select id="machineryType" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white text-gray-800 visible-select focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
          <option value="">Select Type</option>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-sort-numeric-up text-teal-500"></i>
          Quantity <span class="text-red-500">*</span>
        </label>
        <input type="number" id="quantity" required min="1"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
          placeholder="Enter quantity">
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-flag text-teal-500"></i>
          Status <span class="text-red-500">*</span>
        </label>
        <select id="status" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white text-gray-800 visible-select focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
          <option value="">Select Status</option>
          <option value="Operational">Operational</option>
          <option value="Under Repair">Under Repair</option>
          <option value="Out of Service">Out of Service</option>
        </select>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-sticky-note text-teal-500"></i>
          Notes <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        <textarea id="notes" rows="4"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
          placeholder="Add any additional notes..."></textarea>
      </div>

      <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <button type="button" onclick="closeModal()" 
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" 
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 text-white font-medium flex items-center gap-2 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Machinery
        </button>
      </div>
    </form>
  </div>
</div>

<div id="addMachineryTypeModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[60] backdrop-blur-sm">
  <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white flex items-center gap-2">
        <i class="fas fa-plus-circle"></i> Add New Machinery Type
      </h2>
      <button type="button" onclick="closeAddMachineryTypeModal()" class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="addMachineryTypeForm" class="p-6 space-y-4">
      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-tools text-teal-500"></i> Machinery Type Name <span class="text-red-500">*</span>
        </label>
        <input type="text" id="newMachineryTypeName" placeholder="Enter machinery type name" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
        <p id="machineryTypeError" class="text-red-500 text-sm mt-1 hidden"></p>
      </div>
      
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <button type="button" onclick="closeAddMachineryTypeModal()" 
          class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" id="saveMachineryTypeBtn"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 text-white font-medium flex items-center gap-2 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Type
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
let allMachinery = [];
let filteredMachinery = [];

document.addEventListener('DOMContentLoaded', () => {
    loadMachinery();
    setupEventListeners();
    fetchMachineryTypes();
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
        if (currentPage < Math.ceil(filteredMachinery.length / itemsPerPage)) {
            currentPage++;
            renderTable();
        }
    });
}

function openAddMachineryTypeModal() {
    const modal = document.getElementById('addMachineryTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('newMachineryTypeName').focus();
}

function closeAddMachineryTypeModal() {
    const modal = document.getElementById('addMachineryTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('addMachineryTypeForm').reset();
    document.getElementById('machineryTypeError').classList.add('hidden');
}

function fetchMachineryTypes() {
    fetch('api/fetch_machinery_type.php')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('machineryType');
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Type</option>';

            data.forEach(type => {
                const opt = document.createElement('option');
                opt.value = type;
                opt.textContent = type;
                select.appendChild(opt);
            });
            
            if (currentValue) {
                select.value = currentValue;
            }
        })
        .catch(err => console.error('Error fetching types:', err));
}

document.getElementById('addMachineryTypeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const name = document.getElementById('newMachineryTypeName').value.trim();
    const errorEl = document.getElementById('machineryTypeError');
    const submitBtn = document.getElementById('saveMachineryTypeBtn');
    const originalBtnText = submitBtn.innerHTML;
    
    if (name === '') {
        errorEl.textContent = 'Please enter a machinery type name.';
        errorEl.classList.remove('hidden');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Saving...';
    errorEl.classList.add('hidden');
    
    fetch('api/insert_machinery_type.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'machinery_type_name=' + encodeURIComponent(name)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'exists') {
            errorEl.textContent = 'Machinery type already exists.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        } else if (data.status === 'success') {
            showAlert('Success!', 'Machinery type added successfully!', 'success');
            closeAddMachineryTypeModal();
            
            fetchMachineryTypes();
            
            setTimeout(() => {
                document.getElementById('machineryType').value = name;
            }, 100);
        } else {
            errorEl.textContent = 'Something went wrong. Please try again.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    })
    .catch(() => {
        errorEl.textContent = 'Server error. Please try again.';
        errorEl.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
});

function loadMachinery() {
    showLoadingSpinner();
    
    fetch(`api/machinery_api.php?action=list`)
    .then(r => {
        if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
        return r.json();
    })
    .then(data => {
        console.log('API Response:', data);
        
        if (data.success && data.data?.length) {
            allMachinery = data.data;
        } else {
            allMachinery = [];
        }
        updateStats();
        populateTypeFilter();
        applyFilters();
    })
    .catch(error => {
        console.error('Error loading machinery:', error);
        showAlert('Error', 'Failed to load machinery. Please check your API connection.', 'error');
        renderTable();
    });
}

function showLoadingSpinner() {
    const tbody = document.getElementById('machineryTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-spinner animate-spin text-emerald-600 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Loading machinery...</p>
                </div>
            </td>
        </tr>`;
}

function populateTypeFilter() {
    const typeFilter = document.getElementById('filterType');
    const types = [...new Set(allMachinery.map(m => m.machinery_type))].sort();
    
    typeFilter.innerHTML = '<option value="">All Types</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeFilter.appendChild(option);
    });
}

function applyFilters() {
    let filtered = [...allMachinery];

    const type = document.getElementById('filterType').value;
    if (type) {
        filtered = filtered.filter(m => m.machinery_type === type);
    }

    const status = document.getElementById('filterStatus').value;
    if (status) {
        filtered = filtered.filter(m => m.status === status);
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(machinery=> 
            machinery.machinery_name.toLowerCase().includes(searchTerm) ||
            machinery.machinery_type.toLowerCase().includes(searchTerm) ||
            machinery.id.toString().includes(searchTerm) ||
            (machinery.notes && machinery.notes.toLowerCase().includes(searchTerm))
        );
    }

    filteredMachinery = filtered;
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('machineryTableBody');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredMachinery.length);
    const pageMachinery = filteredMachinery.slice(startIndex, endIndex);
    
    if (pageMachinery.length) {
        tbody.innerHTML = pageMachinery.map(machinery => {
            const statusBadge = getStatusBadge(machinery.status);
            
            return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    <span class="font-bold text-gray-900">#${machinery.id}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold text-gray-800">${machinery.machinery_name}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="badge badge-blue">
                        <i class="fas fa-tools mr-1"></i>${machinery.machinery_type}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-lg font-bold text-emerald-600">${machinery.quantity}</span>
                </td>
                <td class="px-6 py-4">
                    ${statusBadge}
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="editMachinery(${machinery.id})" 
                            class="action-btn-edit" title="Edit Machinery">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteMachinery(${machinery.id})" 
                            class="action-btn-delete" title="Delete Machinery">
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
                        <p class="text-gray-500 font-medium text-lg">No machinery found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or add new machinery</p>
                    </div>
                </td>
            </tr>`;
    }
    
    updatePagination();
}

function getStatusBadge(status) {
    const badges = {
        'Operational': '<span class="badge badge-green"><i class="fas fa-check-circle mr-1"></i>Operational</span>',
        'Under Repair': '<span class="badge badge-orange"><i class="fas fa-wrench mr-1"></i>Under Repair</span>',
        'Out of Service': '<span class="badge badge-red"><i class="fas fa-times-circle mr-1"></i>Out of Service</span>'
    };
    return badges[status] || '<span class="badge badge-purple">' + status + '</span>';
}

function updatePagination() {
    const totalPages = Math.ceil(filteredMachinery.length / itemsPerPage);
    const startIndex = filteredMachinery.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredMachinery.length);
    
    document.getElementById('paginationStart').textContent = startIndex;
    document.getElementById('paginationEnd').textContent = endIndex;
    document.getElementById('paginationTotal').textContent = filteredMachinery.length;
    
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
    document.getElementById('totalMachinery').textContent = allMachinery.reduce((sum, m) => sum + parseInt(m.quantity), 0);
    
    const operational = allMachinery.filter(m => m.status === 'Operational');
    document.getElementById('operationalCount').textContent = operational.reduce((sum, m) => sum + parseInt(m.quantity), 0);
    
    const repair = allMachinery.filter(m => m.status === 'Under Repair');
    document.getElementById('repairCount').textContent = repair.reduce((sum, m) => sum + parseInt(m.quantity), 0);
    
    const types = [...new Set(allMachinery.map(m => m.machinery_type))];
    document.getElementById('machineryTypes').textContent = types.length;
}

function openAddModal() {
    document.getElementById('machineryForm').reset();
    document.getElementById('machineryId').value = '';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Machinery';
    
    document.getElementById('machineryModal').classList.remove('hidden');
    document.getElementById('machineryModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('machineryModal').classList.add('hidden');
    document.getElementById('machineryModal').classList.remove('flex');
}

document.getElementById('machineryForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const machineryId = document.getElementById('machineryId').value;
    
    const data = {
        id: machineryId || undefined,
        machinery_name: document.getElementById('machineryName').value.trim(),
        machinery_type: document.getElementById('machineryType').value,
        quantity: document.getElementById('quantity').value,
        status: document.getElementById('status').value,
        notes: document.getElementById('notes').value || ''
    };
    
    console.log('Submitting data:', data);
    
    const url = `api/machinery_api.php?action=${machineryId ? 'update' : 'add'}`;
    
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
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(result => {
        console.log('API Result:', result);
        
        if (result.success) {
            showAlert('Success!', `Machinery ${machineryId ? 'updated' : 'added'} successfully!`, 'success');
            closeModal();
            loadMachinery();
            fetchMachineryTypes();
        } else {
            showAlert('Error', result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving machinery:', error);
        showAlert('Error', 'Failed to save machinery. Please check your connection and try again.', 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

function editMachinery(id) {
    const machinery = allMachinery.find(m => m.id == id);
    if (!machinery) {
        showAlert('Error', 'Machinery not found', 'error');
        return;
    }
    
    document.getElementById('machineryId').value = machinery.id;
    document.getElementById('machineryName').value = machinery.machinery_name;
    document.getElementById('machineryType').value = machinery.machinery_type;
    document.getElementById('quantity').value = machinery.quantity;
    document.getElementById('status').value = machinery.status;
    document.getElementById('notes').value = machinery.notes || '';
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Machinery';
    document.getElementById('machineryModal').classList.remove('hidden');
    document.getElementById('machineryModal').classList.add('flex');
}

function deleteMachinery(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This machinery record will be permanently deleted!",
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
            fetch(`api/machinery_api.php?action=delete&id=${id}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(result => {
                console.log('Delete result:', result);
                
                if (result.success) {
                    showAlert('Deleted!', 'Machinery record has been deleted successfully.', 'success');
                    loadMachinery();
                } else {
                    showAlert('Error', result.message || 'Failed to delete machinery', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting machinery:', error);
                showAlert('Error', 'Failed to delete machinery. Please try again.', 'error');
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
        closeAddMachineryTypeModal();
    }
});

document.getElementById('machineryModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('addMachineryTypeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddMachineryTypeModal();
    }
});
</script>

</body>
</html>