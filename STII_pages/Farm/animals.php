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
<title>Animal Management</title>
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
    .action-btn-view {
        @apply action-btn bg-purple-50 text-purple-600 hover:bg-purple-100;
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
    
    .image-preview-container {
        position: relative;
        width: 100%;
        max-width: 200px;
        margin: 0 auto;
    }
    
    .image-preview {
        width: 100%;
        height: 200px;
        object-fit: cover;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
    }
    
    .remove-image-btn {
        position: absolute;
        top: 8px;
        right: 8px;
        width: 32px;
        height: 32px;
        background: rgba(239, 68, 68, 0.9);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .remove-image-btn:hover {
        background: rgb(220, 38, 38);
        transform: scale(1.1);
    }
    
    .animal-photo-thumbnail {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 2px solid #e5e7eb;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .animal-photo-thumbnail:hover {
        transform: scale(1.1);
        border-color: #14b8a6;
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
    }
    
    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        width: 100%;
    }
    
    .file-input-wrapper input[type=file] {
        position: absolute;
        left: -9999px;
    }
    
    .file-input-label {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 12px 20px;
        background: linear-gradient(to right, #14b8a6, #0d9488);
        color: white;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }
    
    .file-input-label:hover {
        background: linear-gradient(to right, #0d9488, #0f766e);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(20, 184, 166, 0.3);
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
                            <i class="fas fa-paw text-white text-xl"></i>
                        </div>
                        Animal Management
                    </h1>
                    <p class="text-gray-600 mt-2 ml-1">Track and manage your farm's livestock efficiently</p>
                </div>
                <button 
                  onclick="openAddModal()" 
                  class="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 whitespace-nowrap"
                >
                  <i class="fas fa-plus-circle text-lg"></i>
                  Add New Animal
                </button>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Animals</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="totalAnimals">0</h3>
                    <p class="text-xs text-gray-500 mt-1">All time count</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-teal-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-paw text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">This Month</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="monthAnimals">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Current month</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-chart-line text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">This Year</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="yearAnimals">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Annual count</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-cyan-500 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-calendar-alt text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-teal-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Animal Types</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="animalTypes">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Unique species</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-orange-400 to-teal-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-layer-group text-white text-2xl"></i>
                  </div>
                </div>
              </div>
            </div>

            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
              <div class="flex flex-col lg:flex-row justify-between gap-6">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-calendar text-teal-500"></i> Year
                    </label>
                    <select id="filterYear" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                      <option value="">All Years</option>
                    </select>
                  </div>

                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-calendar-day text-teal-500"></i> Month
                    </label>
                    <select id="filterMonth" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                      <option value="">All Months</option>
                      <option value="1">January</option>
                      <option value="2">February</option>
                      <option value="3">March</option>
                      <option value="4">April</option>
                      <option value="5">May</option>
                      <option value="6">June</option>
                      <option value="7">July</option>
                      <option value="8">August</option>
                      <option value="9">September</option>
                      <option value="10">October</option>
                      <option value="11">November</option>
                      <option value="12">December</option>
                    </select>
                  </div>

                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-paw text-teal-500"></i> Animal Type
                    </label>
                    <select id="filterType" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
                      <option value="">All Types</option>
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
                      placeholder="Search by type, notes, or ID..." 
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
                        <i class="fas fa-image mr-2 text-teal-500"></i>Photo
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-hashtag mr-2 text-teal-500"></i>ID
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-calendar mr-2 text-teal-500"></i>Year
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-calendar-day mr-2 text-teal-500"></i>Month
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-paw mr-2 text-teal-500"></i>Type
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-sort-numeric-up mr-2 text-teal-500"></i>Quantity
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-sticky-note mr-2 text-teal-500"></i>Notes
                      </th>
                      <th class="px-6 py-4 text-center font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-cog mr-2 text-teal-500"></i>Actions
                      </th>
                    </tr>
                  </thead>

                  <tbody id="animalsTableBody" class="divide-y divide-gray-100 bg-white">
                    <tr>
                      <td colspan="8" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-spinner animate-spin text-teal-600 text-2xl"></i>
                          </div>
                          <p class="text-gray-500 font-medium">Loading animals...</p>
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

<!-- Main Animal Modal -->
<div id="animalModal" class="fixed inset-0 bg-black/40 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden animate-fadeIn max-h-[90vh] overflow-y-auto">
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-8 py-5 flex items-center justify-between sticky top-0 z-10">
      <h3 id="modalTitle" class="text-2xl font-bold text-white flex items-center gap-3">
        <i class="fas fa-paw text-white"></i> Add Animal
      </h3>
      <button type="button" onclick="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="animalForm" class="p-8 space-y-6">
      <input type="hidden" id="animalId">
      <input type="hidden" id="existingPhoto">



      <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
        <div>
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
            <i class="fas fa-calendar text-teal-500"></i>
            Year <span class="text-red-500">*</span>
          </label>
          <input type="number" id="year" required min="2020" max="2050"
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
            placeholder="e.g., 2025">
        </div>

        <div>
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
            <i class="fas fa-calendar-day text-teal-500"></i>
            Month <span class="text-red-500">*</span>
          </label>
          <select id="month" required
            class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition bg-white text-gray-800 visible-select">
            <option value="">Select Month</option>
            <option value="1">January</option><option value="2">February</option><option value="3">March</option>
            <option value="4">April</option><option value="5">May</option><option value="6">June</option>
            <option value="7">July</option><option value="8">August</option><option value="9">September</option>
            <option value="10">October</option><option value="11">November</option><option value="12">December</option>
          </select>
        </div>
      </div>

      <div>
        <div class="flex justify-between items-center mb-2">
          <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
            <i class="fas fa-paw text-teal-500"></i>
            Animal Type <span class="text-red-500">*</span>
          </label>
          <button type="button" onclick="openAddAnimalTypeModal()"
            class="text-xs font-semibold text-teal-600 hover:text-teal-700 flex items-center gap-1 transition-colors">
            <i class="fas fa-plus-circle"></i> Add Custom Type
          </button>
        </div>

        <select
          id="animalType"
          required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm bg-white text-gray-800 visible-select focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
          <option value="">Select Animal Type</option>
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
          <i class="fas fa-sticky-note text-teal-500"></i>
          Notes <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        <textarea id="notes" rows="4"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition"
          placeholder="Add any additional notes..."></textarea>
      </div>

            <!-- Photo Upload Section -->
      <div class="space-y-3">
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700">
          <i class="fas fa-camera text-teal-500"></i>
          Animal Photo <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        
        <div id="imagePreviewContainer" class="hidden">
          <div class="image-preview-container">
            <img id="imagePreview" src="" alt="Preview" class="image-preview">
            <button type="button" onclick="removeImage()" class="remove-image-btn" title="Remove photo">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <div class="file-input-wrapper">
          <input type="file" id="animalPhoto" name="photo" accept="image/*" onchange="previewImage(event)">
          <label for="animalPhoto" class="file-input-label">
            <i class="fas fa-cloud-upload-alt text-lg"></i>
            <span>Choose Photo</span>
          </label>
        </div>
        <p class="text-xs text-gray-500 text-center">Accepted formats: JPG, PNG, GIF (Max 5MB)</p>
      </div>

      <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <button type="button" onclick="closeModal()" 
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" 
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 text-white font-medium flex items-center gap-2 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Animal
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Add Custom Animal Type Modal -->
<div id="addAnimalTypeModal" class="fixed inset-0 hidden items-center justify-center bg-black/50 z-[60] backdrop-blur-sm">
  <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-teal-500 to-teal-600 px-6 py-4 flex items-center justify-between">
      <h2 class="text-xl font-bold text-white flex items-center gap-2">
        <i class="fas fa-plus-circle"></i> Add New Animal Type
      </h2>
      <button type="button" onclick="closeAddAnimalTypeModal()" class="w-9 h-9 flex items-center justify-center rounded-lg hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="addAnimalTypeForm" class="p-6 space-y-4">
      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-paw text-teal-500"></i> Animal Name <span class="text-red-500">*</span>
        </label>
        <input type="text" id="newAnimalName" placeholder="Enter animal type name" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 transition">
        <p id="animalTypeError" class="text-red-500 text-sm mt-1 hidden"></p>
      </div>
      
      <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
        <button type="button" onclick="closeAddAnimalTypeModal()" 
          class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" id="saveAnimalTypeBtn"
          class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-teal-500 to-teal-600 text-white font-medium flex items-center gap-2 hover:from-teal-600 hover:to-teal-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Type
        </button>
      </div>

      
    </form>
  </div>

  
</div>

<!-- Photo View Modal -->
<div id="photoViewModal" class="fixed inset-0 bg-black/80 hidden backdrop-blur-sm z-[70] flex items-center justify-center p-4" onclick="closePhotoModal()">
  <div class="relative max-w-4xl max-h-[90vh]" onclick="event.stopPropagation()">
    <button onclick="closePhotoModal()" class="absolute -top-12 right-0 w-10 h-10 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition">
      <i class="fas fa-times text-xl"></i>
    </button>
    <img id="photoViewImage" src="" alt="Animal Photo" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl">
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPage = 1;
const itemsPerPage = 10;
let totalItems = 0;
let allAnimals = [];
let filteredAnimals = [];
const monthNames = ['', 'January','February','March','April','May','June','July','August','September','October','November','December'];

document.addEventListener('DOMContentLoaded', () => {
    loadAnimals();
    setupEventListeners();
    fetchAnimalTypes();
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
        if (currentPage < Math.ceil(filteredAnimals.length / itemsPerPage)) {
            currentPage++;
            renderTable();
        }
    });
}

function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        // Check file size (5MB = 5 * 1024 * 1024 bytes)
        if (file.size > 5 * 1024 * 1024) {
            showAlert('Error', 'File size must be less than 5MB', 'error');
            event.target.value = '';
            return;
        }

        // Check file type
        if (!file.type.match('image.*')) {
            showAlert('Error', 'Please select a valid image file', 'error');
            event.target.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('imagePreviewContainer').classList.remove('hidden');
        };
        reader.readAsDataURL(file);
    }
}

function removeImage() {
    document.getElementById('animalPhoto').value = '';
    document.getElementById('imagePreview').src = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    document.getElementById('existingPhoto').value = '';
}

function viewPhoto(photoUrl) {
    document.getElementById('photoViewImage').src = photoUrl;
    document.getElementById('photoViewModal').classList.remove('hidden');
    document.getElementById('photoViewModal').classList.add('flex');
}

function closePhotoModal() {
    document.getElementById('photoViewModal').classList.add('hidden');
    document.getElementById('photoViewModal').classList.remove('flex');
}

function openAddAnimalTypeModal() {
    const modal = document.getElementById('addAnimalTypeModal');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.getElementById('newAnimalName').focus();
}

function closeAddAnimalTypeModal() {
    const modal = document.getElementById('addAnimalTypeModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.getElementById('addAnimalTypeForm').reset();
    document.getElementById('animalTypeError').classList.add('hidden');
}

function fetchAnimalTypes() {
    fetch('api/fetch_animal_types.php')
        .then(res => res.json())
        .then(data => {
            const select = document.getElementById('animalType');
            const currentValue = select.value;
            select.innerHTML = '<option value="">Select Animal Type</option>';

            data.forEach(animal => {
                const opt = document.createElement('option');
                opt.value = animal;
                opt.textContent = animal;
                select.appendChild(opt);
            });
            
            if (currentValue) {
                select.value = currentValue;
            }
        })
        .catch(err => console.error('Error fetching types:', err));
}

document.getElementById('addAnimalTypeForm').addEventListener('submit', function (e) {
    e.preventDefault();
    
    const name = document.getElementById('newAnimalName').value.trim();
    const errorEl = document.getElementById('animalTypeError');
    const submitBtn = document.getElementById('saveAnimalTypeBtn');
    const originalBtnText = submitBtn.innerHTML;
    
    if (name === '') {
        errorEl.textContent = 'Please enter an animal name.';
        errorEl.classList.remove('hidden');
        return;
    }
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Saving...';
    errorEl.classList.add('hidden');
    
    fetch('api/insert_animal_type.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'animal_name=' + encodeURIComponent(name)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'exists') {
            errorEl.textContent = 'Animal type already exists.';
            errorEl.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        } else if (data.status === 'success') {
            showAlert('Success!', 'Animal type added successfully!', 'success');
            closeAddAnimalTypeModal();
            fetchAnimalTypes();
            setTimeout(() => {
                document.getElementById('animalType').value = name;
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

function loadAnimals() {
    showLoadingSpinner();
    
    fetch(`api/animals1.php?action=list`)
    .then(r => {
        if (!r.ok) throw new Error(`HTTP error! status: ${r.status}`);
        return r.json();
    })
    .then(data => {
        console.log('API Response:', data);
        
        if (data.success && data.data?.length) {
            allAnimals = data.data;
        } else {
            allAnimals = [];
        }
        updateStats();
        populateYearFilter();
        populateTypeFilter();
        applyFilters();
    })
    .catch(error => {
        console.error('Error loading animals:', error);
        showAlert('Error', 'Failed to load animals. Please check your API connection.', 'error');
        renderTable();
    });
}

function showLoadingSpinner() {
    const tbody = document.getElementById('animalsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="8" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-spinner animate-spin text-emerald-600 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Loading animals...</p>
                </div>
            </td>
        </tr>`;
}

function populateYearFilter() {
    const select = document.getElementById('filterYear');
    const years = [...new Set(allAnimals.map(a => a.year))].sort((a, b) => b - a);
    
    select.innerHTML = '<option value="">All Years</option>';
    
    years.forEach(year => {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        select.appendChild(option);
    });
    
    const currentYear = new Date().getFullYear().toString();
    if (years.includes(currentYear)) {
        select.value = currentYear;
    } else if (years.length > 0) {
        select.value = years[0];
    }
}

function populateTypeFilter() {
    const typeFilter = document.getElementById('filterType');
    const types = [...new Set(allAnimals.map(a => a.animal_type))].sort();
    
    typeFilter.innerHTML = '<option value="">All Types</option>';
    
    types.forEach(type => {
        const option = document.createElement('option');
        option.value = type;
        option.textContent = type;
        typeFilter.appendChild(option);
    });
}

function applyFilters() {
    let filtered = [...allAnimals];

    const year = document.getElementById('filterYear').value;
    if (year) {
        filtered = filtered.filter(a => a.year == year);
    }

    const month = document.getElementById('filterMonth').value;
    if (month) {
        filtered = filtered.filter(a => a.month == month);
    }

    const type = document.getElementById('filterType').value;
    if (type) {
        filtered = filtered.filter(a => a.animal_type === type);
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(animal => 
            animal.animal_type.toLowerCase().includes(searchTerm) ||
            (animal.notes && animal.notes.toLowerCase().includes(searchTerm)) ||
            animal.id.toString().includes(searchTerm) ||
            animal.year.toString().includes(searchTerm) ||
            monthNames[parseInt(animal.month)].toLowerCase().includes(searchTerm)
        );
    }

    filteredAnimals = filtered;
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('animalsTableBody');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredAnimals.length);
    const pageAnimals = filteredAnimals.slice(startIndex, endIndex);
    
    if (pageAnimals.length) {
        tbody.innerHTML = pageAnimals.map(animal => {
            const photoHtml = animal.photo 
                ? `<img src="api/uploads/${animal.photo}" alt="${animal.animal_type}" 
                    class="animal-photo-thumbnail" 
                    onclick="viewPhoto('api/uploads/${animal.photo}')" 
                    title="Click to view full size">`
                : `<div class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                    <i class="fas fa-image text-gray-400"></i>
                   </div>`;
            
            return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    ${photoHtml}
                </td>
                <td class="px-6 py-4">
                    <span class="font-bold text-gray-900">#${animal.id}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="font-semibold text-gray-700">${animal.year}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="badge badge-blue">
                        <i class="fas fa-calendar-day mr-1"></i>${monthNames[parseInt(animal.month)]}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="badge badge-green">
                        <i class="fas fa-paw mr-1"></i>${animal.animal_type}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-lg font-bold text-emerald-600">${animal.quantity}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-gray-600 text-sm">${animal.notes || '-'}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="editAnimal(${animal.id})" 
                            class="action-btn-edit" title="Edit Animal">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteAnimal(${animal.id})" 
                            class="action-btn-delete" title="Delete Animal">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        }).join('');
    } else {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center py-16">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                        </div>
                        <p class="text-gray-500 font-medium text-lg">No animals found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or add a new animal</p>
                    </div>
                </td>
            </tr>`;
    }
    
    updatePagination();
}

function updatePagination() {
    const totalPages = Math.ceil(filteredAnimals.length / itemsPerPage);
    const startIndex = filteredAnimals.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredAnimals.length);
    
    document.getElementById('paginationStart').textContent = startIndex;
    document.getElementById('paginationEnd').textContent = endIndex;
    document.getElementById('paginationTotal').textContent = filteredAnimals.length;
    
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
    const currentYear = new Date().getFullYear();
    const currentMonth = new Date().getMonth() + 1;
    
    document.getElementById('totalAnimals').textContent = allAnimals.reduce((sum, animal) => sum + parseInt(animal.quantity), 0);
    
    const monthAnimals = allAnimals.filter(a => parseInt(a.year) === currentYear && parseInt(a.month) === currentMonth);
    document.getElementById('monthAnimals').textContent = monthAnimals.reduce((sum, animal) => sum + parseInt(animal.quantity), 0);
    
    const yearAnimals = allAnimals.filter(a => parseInt(a.year) === currentYear);
    document.getElementById('yearAnimals').textContent = yearAnimals.reduce((sum, animal) => sum + parseInt(animal.quantity), 0);
    
    const types = [...new Set(allAnimals.map(a => a.animal_type))];
    document.getElementById('animalTypes').textContent = types.length;
}

function openAddModal() {
    document.getElementById('animalForm').reset();
    document.getElementById('animalId').value = '';
    document.getElementById('existingPhoto').value = '';
    document.getElementById('imagePreviewContainer').classList.add('hidden');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Add New Animal';
    document.getElementById('year').value = new Date().getFullYear();
    document.getElementById('month').value = new Date().getMonth() + 1;
    
    document.getElementById('animalModal').classList.remove('hidden');
    document.getElementById('animalModal').classList.add('flex');
}

function closeModal() {
    document.getElementById('animalModal').classList.add('hidden');
    document.getElementById('animalModal').classList.remove('flex');
}

document.getElementById('animalForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const animalId = document.getElementById('animalId').value;
    const animalType = document.getElementById('animalType').value;
    
    if (!animalType || animalType.trim() === '') {
        showAlert('Validation Error', 'Please select an animal type', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('id', animalId || '');
    formData.append('year', document.getElementById('year').value);
    formData.append('month', document.getElementById('month').value);
    formData.append('animal_type', animalType.trim());
    formData.append('quantity', document.getElementById('quantity').value);
    formData.append('notes', document.getElementById('notes').value || '');
    
    const photoInput = document.getElementById('animalPhoto');
    if (photoInput.files.length > 0) {
        formData.append('photo', photoInput.files[0]);
    }
    
    if (animalId && document.getElementById('existingPhoto').value) {
        formData.append('existing_photo', document.getElementById('existingPhoto').value);
    }
    
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Saving...';
    submitButton.disabled = true;
    
    const url = `api/animals1.php?action=${animalId ? 'update' : 'add'}`;
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    })
    .then(result => {
        console.log('API Result:', result);
        
        if (result.success) {
            showAlert('Success!', `Animal ${animalId ? 'updated' : 'added'} successfully!`, 'success');
            closeModal();
            loadAnimals();
            fetchAnimalTypes();
        } else {
            showAlert('Error', result.message || 'Operation failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error saving animal:', error);
        showAlert('Error', 'Failed to save animal. Please check your connection and try again.', 'error');
    })
    .finally(() => {
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});

function editAnimal(id) {
    const animal = allAnimals.find(a => a.id == id);
    if (!animal) {
        showAlert('Error', 'Animal not found', 'error');
        return;
    }
    
    document.getElementById('animalId').value = animal.id;
    document.getElementById('year').value = animal.year;
    document.getElementById('month').value = animal.month;
    document.getElementById('quantity').value = animal.quantity;
    document.getElementById('notes').value = animal.notes || '';
    document.getElementById('animalType').value = animal.animal_type;
    
    if (animal.photo) {
        document.getElementById('existingPhoto').value = animal.photo;
        document.getElementById('imagePreview').src = 'api/uploads/' + animal.photo;
        document.getElementById('imagePreviewContainer').classList.remove('hidden');
    } else {
        document.getElementById('imagePreviewContainer').classList.add('hidden');
        document.getElementById('existingPhoto').value = '';
    }
    
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Animal';
    document.getElementById('animalModal').classList.remove('hidden');
    document.getElementById('animalModal').classList.add('flex');
}

function deleteAnimal(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This animal record will be permanently deleted!",
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
            fetch(`api/animals1.php?action=delete&id=${id}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(result => {
                console.log('Delete result:', result);
                
                if (result.success) {
                    showAlert('Deleted!', 'Animal record has been deleted successfully.', 'success');
                    loadAnimals();
                } else {
                    showAlert('Error', result.message || 'Failed to delete animal', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting animal:', error);
                showAlert('Error', 'Failed to delete animal. Please try again.', 'error');
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
        closeAddAnimalTypeModal();
        closePhotoModal();
    }
});

document.getElementById('animalModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('addAnimalTypeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddAnimalTypeModal();
    }
});
</script>

</body>
</html>