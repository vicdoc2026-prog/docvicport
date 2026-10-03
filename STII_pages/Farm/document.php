<?php
require_once '../config/conn.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Document Management</title>
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
    
    .table-row { 
        @apply hover:bg-gradient-to-r hover:from-gray-50 hover:to-transparent transition-all duration-200 border-b border-gray-100;
    }
    
    .badge { 
        @apply inline-flex items-center px-3 py-1.5 rounded-full text-xs font-semibold shadow-sm;
    }
    .badge-blue { @apply bg-gradient-to-r from-blue-100 to-blue-50 text-blue-800 border border-blue-200; }
    .badge-green { @apply bg-gradient-to-r from-emerald-100 to-emerald-50 text-emerald-800 border border-emerald-200; }
    .badge-red { @apply bg-gradient-to-r from-red-100 to-red-50 text-red-800 border border-red-200; }
    .badge-purple { @apply bg-gradient-to-r from-purple-100 to-purple-50 text-purple-800 border border-purple-200; }
    
    .action-btn {
        @apply w-9 h-9 rounded-lg flex items-center justify-center transition-all duration-300 transform hover:scale-110;
    }
    .action-btn-view {
        @apply action-btn bg-indigo-50 text-indigo-600 hover:bg-indigo-100;
    }
    .action-btn-download {
        @apply action-btn bg-green-50 text-green-600 hover:bg-green-100;
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

    .file-upload-area {
        @apply border-2 border-dashed border-gray-300 rounded-xl p-8 text-center transition-all duration-300;
    }
    .file-upload-area.dragover {
        @apply border-indigo-500 bg-indigo-50;
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
                            <i class="fas fa-file-alt text-white text-xl"></i>
                        </div>
                        Document Management
                    </h1>
                    <p class="text-gray-600 mt-2 ml-1">Upload, manage, and organize your documents</p>
                </div>
                <button 
                  onclick="openUploadModal()" 
                  class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2.5 rounded-xl shadow-sm hover:shadow-md transition-all duration-200 whitespace-nowrap"
                >
                  <i class="fas fa-upload text-lg"></i>
                  Upload Document
                </button>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Documents</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="totalDocuments">0</h3>
                    <p class="text-xs text-gray-500 mt-1">All files</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-file-alt text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Total Size</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="totalSize">0 MB</h3>
                    <p class="text-xs text-gray-500 mt-1">Storage used</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-hdd text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">PDF Files</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="pdfCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Documents</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-red-500 to-red-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-file-pdf text-white text-2xl"></i>
                  </div>
                </div>
              </div>

              <div class="group bg-white/80 backdrop-blur-sm border border-indigo-100 rounded-2xl p-6 shadow-sm hover:shadow-md hover:-translate-y-1 transition-all duration-300">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-semibold text-gray-500 uppercase tracking-wide">Recent Uploads</p>
                    <h3 class="text-3xl font-bold text-gray-900 mt-2" id="recentCount">0</h3>
                    <p class="text-xs text-gray-500 mt-1">Last 7 days</p>
                  </div>
                  <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center shadow-inner group-hover:scale-110 transition-transform duration-300">
                    <i class="fas fa-clock text-white text-2xl"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Search and Filter -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow duration-300">
              <div class="flex flex-col lg:flex-row justify-between gap-6">
                <div class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <div>
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
                      <i class="fas fa-file-alt text-indigo-500"></i> File Type
                    </label>
                    <select id="filterType" class="w-full px-3 py-2 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition">
                      <option value="">All Types</option>
                      <option value="pdf">PDF</option>
                      <option value="doc">Word</option>
                      <option value="xls">Excel</option>
                      <option value="img">Images</option>
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
                      placeholder="Search by title or description..." 
                      class="w-full pl-11 pr-4 py-2.5 rounded-xl border border-gray-300 text-gray-700 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                    >
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Documents Table -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-all duration-300 hover:shadow-md">
              <div class="overflow-x-auto">
                <table class="min-w-full text-sm text-gray-700">
                  <thead class="bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 sticky top-0 z-10">
                    <tr>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-hashtag mr-2 text-indigo-500"></i>ID
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-file mr-2 text-indigo-500"></i>Document
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-tag mr-2 text-indigo-500"></i>Type
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-weight mr-2 text-indigo-500"></i>Size
                      </th>
                      <th class="px-6 py-4 text-left font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-calendar mr-2 text-indigo-500"></i>Uploaded
                      </th>
                      <th class="px-6 py-4 text-center font-bold text-gray-600 uppercase text-xs tracking-wider">
                        <i class="fas fa-cog mr-2 text-indigo-500"></i>Actions
                      </th>
                    </tr>
                  </thead>

                  <tbody id="documentsTableBody" class="divide-y divide-gray-100 bg-white">
                    <tr>
                      <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                          <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-spinner animate-spin text-indigo-600 text-2xl"></i>
                          </div>
                          <p class="text-gray-500 font-medium">Loading documents...</p>
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

<!-- Upload Document Modal -->
<div id="uploadModal" class="fixed inset-0 bg-black/40 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-8 py-5 flex items-center justify-between">
      <h3 class="text-2xl font-bold text-white flex items-center gap-3">
        <i class="fas fa-upload text-white"></i> Upload Document
      </h3>
      <button type="button" onclick="closeUploadModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="uploadForm" class="p-8 space-y-6" enctype="multipart/form-data">
      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-heading text-indigo-500"></i>
          Document Title <span class="text-red-500">*</span>
        </label>
        <input type="text" id="documentTitle" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Enter document title">
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-align-left text-indigo-500"></i>
          Description <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        <textarea id="documentDescription" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Add document description..."></textarea>
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-file-upload text-indigo-500"></i>
          File <span class="text-red-500">*</span>
        </label>
        <div id="dropArea" class="file-upload-area">
          <input type="file" id="documentFile" required class="hidden" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
          <div class="space-y-2">
            <i class="fas fa-cloud-upload-alt text-5xl text-gray-400"></i>
            <p class="text-gray-700 font-semibold">Drag & drop your file here or <span class="text-indigo-600 cursor-pointer" onclick="document.getElementById('documentFile').click()">browse</span></p>
            <p class="text-gray-500 text-xs">Supported formats: PDF, Word, Excel, Images (Max 10MB)</p>
          </div>
          <div id="fileInfo" class="hidden mt-4 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-3">
              <i class="fas fa-file text-indigo-600 text-2xl"></i>
              <div class="flex-1">
                <p class="font-semibold text-gray-800" id="fileName"></p>
                <p class="text-sm text-gray-500" id="fileSize"></p>
              </div>
              <button type="button" onclick="clearFile()" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <button type="button" onclick="closeUploadModal()" 
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" id="uploadBtn"
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-medium flex items-center gap-2 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-upload"></i> Upload Document
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Document Modal -->
<div id="editModal" class="fixed inset-0 bg-black/40 hidden backdrop-blur-sm z-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl mx-auto overflow-hidden animate-fadeIn">
    <div class="bg-gradient-to-r from-indigo-500 to-indigo-600 px-8 py-5 flex items-center justify-between">
      <h3 class="text-2xl font-bold text-white flex items-center gap-3">
        <i class="fas fa-edit text-white"></i> Edit Document
      </h3>
      <button type="button" onclick="closeEditModal()" class="w-10 h-10 flex items-center justify-center rounded-xl hover:bg-white/20 transition">
        <i class="fas fa-times text-white text-lg"></i>
      </button>
    </div>

    <form id="editForm" class="p-8 space-y-6">
      <input type="hidden" id="editDocumentId">

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-heading text-indigo-500"></i>
          Document Title <span class="text-red-500">*</span>
        </label>
        <input type="text" id="editDocumentTitle" required
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Enter document title">
      </div>

      <div>
        <label class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-2">
          <i class="fas fa-align-left text-indigo-500"></i>
          Description <span class="text-gray-400 text-xs font-normal">(Optional)</span>
        </label>
        <textarea id="editDocumentDescription" rows="3"
          class="w-full border border-gray-300 rounded-xl px-4 py-2.5 text-sm resize-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
          placeholder="Add document description..."></textarea>
      </div>

      <div class="flex justify-end gap-4 pt-6 border-t border-gray-100">
        <button type="button" onclick="closeEditModal()" 
          class="px-6 py-2.5 rounded-xl border border-gray-300 text-gray-700 hover:bg-gray-100 flex items-center gap-2 transition">
          <i class="fas fa-times"></i> Cancel
        </button>
        <button type="submit" 
          class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-500 to-indigo-600 text-white font-medium flex items-center gap-2 hover:from-indigo-600 hover:to-indigo-700 shadow-sm hover:shadow-md transition">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
let currentPage = 1;
const itemsPerPage = 10;
let allDocuments = [];
let filteredDocuments = [];

document.addEventListener('DOMContentLoaded', () => {
    console.log('Page loaded, initializing...');
    loadDocuments();
    setupEventListeners();
    setupFileUpload();
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
        if (currentPage < Math.ceil(filteredDocuments.length / itemsPerPage)) {
            currentPage++;
            renderTable();
        }
    });
}

function setupFileUpload() {
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('documentFile');

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => {
            dropArea.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropArea.addEventListener(eventName, () => {
            dropArea.classList.remove('dragover');
        }, false);
    });

    dropArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        fileInput.files = files;
        handleFileSelect();
    }, false);

    fileInput.addEventListener('change', handleFileSelect);
}

function handleFileSelect() {
    const fileInput = document.getElementById('documentFile');
    const file = fileInput.files[0];
    
    if (file) {
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');
        
        fileName.textContent = file.name;
        fileSize.textContent = formatFileSize(file.size);
        fileInfo.classList.remove('hidden');
    }
}

function clearFile() {
    document.getElementById('documentFile').value = '';
    document.getElementById('fileInfo').classList.add('hidden');
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function getFileIcon(fileName) {
    const ext = fileName.split('.').pop().toLowerCase();
    const icons = {
        'pdf': '<i class="fas fa-file-pdf text-red-500 text-2xl"></i>',
        'doc': '<i class="fas fa-file-word text-blue-500 text-2xl"></i>',
        'docx': '<i class="fas fa-file-word text-blue-500 text-2xl"></i>',
        'xls': '<i class="fas fa-file-excel text-green-500 text-2xl"></i>',
        'xlsx': '<i class="fas fa-file-excel text-green-500 text-2xl"></i>',
        'jpg': '<i class="fas fa-file-image text-purple-500 text-2xl"></i>',
        'jpeg': '<i class="fas fa-file-image text-purple-500 text-2xl"></i>',
        'png': '<i class="fas fa-file-image text-purple-500 text-2xl"></i>'
    };
    return icons[ext] || '<i class="fas fa-file text-gray-500 text-2xl"></i>';
}

function getFileTypeBadge(fileName) {
    const ext = fileName.split('.').pop().toLowerCase();
    const badges = {
        'pdf': '<span class="badge badge-red"><i class="fas fa-file-pdf mr-1"></i>PDF</span>',
        'doc': '<span class="badge badge-blue"><i class="fas fa-file-word mr-1"></i>Word</span>',
        'docx': '<span class="badge badge-blue"><i class="fas fa-file-word mr-1"></i>Word</span>',
        'xls': '<span class="badge badge-green"><i class="fas fa-file-excel mr-1"></i>Excel</span>',
        'xlsx': '<span class="badge badge-green"><i class="fas fa-file-excel mr-1"></i>Excel</span>',
        'jpg': '<span class="badge badge-purple"><i class="fas fa-file-image mr-1"></i>Image</span>',
        'jpeg': '<span class="badge badge-purple"><i class="fas fa-file-image mr-1"></i>Image</span>',
        'png': '<span class="badge badge-purple"><i class="fas fa-file-image mr-1"></i>Image</span>'
    };
    return badges[ext] || '<span class="badge badge-blue">' + ext.toUpperCase() + '</span>';
}

function loadDocuments() {
    console.log('Loading documents...');
    showLoadingSpinner();
    
    fetch('api/documents_api.php?action=list')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Documents API Response:', data);
        
        if (data.success === true) {
            allDocuments = data.data || [];
            console.log('Loaded', allDocuments.length, 'documents');
        } else {
            allDocuments = [];
        }
        
        updateStats();
        applyFilters();
    })
    .catch(error => {
        console.error('Error loading documents:', error);
        showAlert('Error', 'Failed to load documents: ' + error.message, 'error');
        allDocuments = [];
        updateStats();
        renderTable();
    });
}

function showLoadingSpinner() {
    const tbody = document.getElementById('documentsTableBody');
    tbody.innerHTML = `
        <tr>
            <td colspan="6" class="px-6 py-16 text-center">
                <div class="flex flex-col items-center justify-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-spinner animate-spin text-indigo-600 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 font-medium">Loading documents...</p>
                </div>
            </td>
        </tr>`;
}

function applyFilters() {
    let filtered = [...allDocuments];

    const type = document.getElementById('filterType').value;
    if (type) {
        filtered = filtered.filter(doc => {
            const ext = doc.file_name.split('.').pop().toLowerCase();
            if (type === 'pdf') return ext === 'pdf';
            if (type === 'doc') return ['doc', 'docx'].includes(ext);
            if (type === 'xls') return ['xls', 'xlsx'].includes(ext);
            if (type === 'img') return ['jpg', 'jpeg', 'png'].includes(ext);
            return true;
        });
    }

    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    if (searchTerm) {
        filtered = filtered.filter(doc => 
            doc.title.toLowerCase().includes(searchTerm) ||
            (doc.description && doc.description.toLowerCase().includes(searchTerm)) ||
            doc.file_name.toLowerCase().includes(searchTerm)
        );
    }

    filteredDocuments = filtered;
    currentPage = 1;
    renderTable();
}

function renderTable() {
    const tbody = document.getElementById('documentsTableBody');
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = Math.min(startIndex + itemsPerPage, filteredDocuments.length);
    const pageDocuments = filteredDocuments.slice(startIndex, endIndex);
    
    if (pageDocuments.length) {
        tbody.innerHTML = pageDocuments.map(doc => {
            const safeTitle = escapeHtml(doc.title);
            const safeDescription = doc.description ? escapeHtml(doc.description) : '';
            const fileIcon = getFileIcon(doc.file_name);
            const typeBadge = getFileTypeBadge(doc.file_name);
            const uploadDate = new Date(doc.uploaded_at).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            
            return `
            <tr class="table-row">
                <td class="px-6 py-4">
                    <span class="font-bold text-gray-900">#${doc.id}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        ${fileIcon}
                        <div>
                            <span class="font-semibold text-gray-800 block">${safeTitle}</span>
                            ${safeDescription ? `<p class="text-xs text-gray-500 mt-1">${safeDescription.substring(0, 50)}${safeDescription.length > 50 ? '...' : ''}</p>` : ''}
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    ${typeBadge}
                </td>
                <td class="px-6 py-4">
                    <span class="text-gray-700 font-medium">${formatFileSize(doc.file_size)}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-gray-700">${uploadDate}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center justify-center gap-2">
                        <button onclick="viewDocument('${doc.file_path}')" 
                            class="action-btn-view" title="View Document">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button onclick="downloadDocument('${doc.file_path}', '${doc.file_name}')" 
                            class="action-btn-download" title="Download">
                            <i class="fas fa-download"></i>
                        </button>
                        <button onclick="editDocument(${doc.id})" 
                            class="action-btn-edit" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteDocument(${doc.id})" 
                            class="action-btn-delete" title="Delete">
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
                        <p class="text-gray-500 font-medium text-lg">No documents found</p>
                        <p class="text-gray-400 text-sm mt-1">Try adjusting your filters or upload a new document</p>
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

function updatePagination() {
    const totalPages = Math.ceil(filteredDocuments.length / itemsPerPage);
    const startIndex = filteredDocuments.length === 0 ? 0 : (currentPage - 1) * itemsPerPage + 1;
    const endIndex = Math.min(currentPage * itemsPerPage, filteredDocuments.length);
    
    document.getElementById('paginationStart').textContent = startIndex;
    document.getElementById('paginationEnd').textContent = endIndex;
    document.getElementById('paginationTotal').textContent = filteredDocuments.length;
    
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
    document.getElementById('totalDocuments').textContent = allDocuments.length;
    
    const totalSize = allDocuments.reduce((sum, doc) => sum + parseInt(doc.file_size), 0);
    document.getElementById('totalSize').textContent = formatFileSize(totalSize);
    
    const pdfCount = allDocuments.filter(doc => doc.file_name.toLowerCase().endsWith('.pdf')).length;
    document.getElementById('pdfCount').textContent = pdfCount;
    
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
    const recentCount = allDocuments.filter(doc => new Date(doc.uploaded_at) >= sevenDaysAgo).length;
    document.getElementById('recentCount').textContent = recentCount;
}

function openUploadModal() {
    document.getElementById('uploadForm').reset();
    clearFile();
    document.getElementById('uploadModal').classList.remove('hidden');
    document.getElementById('uploadModal').classList.add('flex');
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.getElementById('uploadModal').classList.remove('flex');
}

document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('title', document.getElementById('documentTitle').value.trim());
    formData.append('description', document.getElementById('documentDescription').value.trim());
    formData.append('file', document.getElementById('documentFile').files[0]);
    
    const uploadBtn = document.getElementById('uploadBtn');
    const originalText = uploadBtn.innerHTML;
    uploadBtn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i> Uploading...';
    uploadBtn.disabled = true;
    
    fetch('api/documents_api.php?action=upload', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        console.log('Upload result:', result);
        
        if (result.success) {
            showAlert('Success!', 'Document uploaded successfully!', 'success');
            closeUploadModal();
            loadDocuments();
        } else {
            showAlert('Error', result.message || 'Upload failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error uploading document:', error);
        showAlert('Error', 'Failed to upload document: ' + error.message, 'error');
    })
    .finally(() => {
        uploadBtn.innerHTML = originalText;
        uploadBtn.disabled = false;
    });
});

function viewDocument(filePath) {
    window.open('./' + filePath, '_blank');
}

function downloadDocument(filePath, fileName) {
    const link = document.createElement('a');
    link.href = '../' + filePath;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function editDocument(id) {
    const document = allDocuments.find(d => d.id == id);
    if (!document) {
        showAlert('Error', 'Document not found', 'error');
        return;
    }
    
    document.getElementById('editDocumentId').value = document.id;
    document.getElementById('editDocumentTitle').value = document.title;
    document.getElementById('editDocumentDescription').value = document.description || '';
    
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

document.getElementById('editForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const data = {
        id: document.getElementById('editDocumentId').value,
        title: document.getElementById('editDocumentTitle').value.trim(),
        description: document.getElementById('editDocumentDescription').value.trim()
    };
    
    fetch('api/documents_api.php?action=update', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showAlert('Success!', 'Document updated successfully!', 'success');
            closeEditModal();
            loadDocuments();
        } else {
            showAlert('Error', result.message || 'Update failed', 'error');
        }
    })
    .catch(error => {
        console.error('Error updating document:', error);
        showAlert('Error', 'Failed to update document: ' + error.message, 'error');
    });
});

function deleteDocument(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This document will be permanently deleted!",
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
            fetch(`api/documents_api.php?action=delete&id=${id}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showAlert('Deleted!', 'Document has been deleted successfully.', 'success');
                    loadDocuments();
                } else {
                    showAlert('Error', result.message || 'Failed to delete document', 'error');
                }
            })
            .catch(error => {
                console.error('Error deleting document:', error);
                showAlert('Error', 'Failed to delete document: ' + error.message, 'error');
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
        closeUploadModal();
        closeEditModal();
    }
});

document.getElementById('uploadModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeUploadModal();
    }
});

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>
</body>
</html>