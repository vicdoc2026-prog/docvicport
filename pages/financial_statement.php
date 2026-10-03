<?php 
require_once 'config/check-session.php';
require_once 'config/conn.php';

$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Financial Dashboard - Belvic</title>
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
    .dropdown-menu {
      display: none;
      position: absolute;
    }
    .dropdown-menu.open {
      display: block;
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.6);
      animation: fadeIn 0.3s;
    }
    .modal.show {
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .modal-content {
      background-color: white;
      border-radius: 12px;
      padding: 0;
      max-width: 95%;
      width: 1400px;
      max-height: 95vh;
      overflow-y: auto;
      animation: slideIn 0.3s;
    }
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideIn {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    .financial-table { 
      border-collapse: collapse; 
      width: 100%; 
    }
    .financial-table th, .financial-table td { 
      border: 1px solid #d1d5db; 
      padding: 10px 16px; 
      font-size: 14px; 
    }
    .financial-table th { 
      background-color: #1e40af; 
      color: white;
      font-weight: 600; 
    }
    .section-header {
      background-color: #f3f4f6;
      font-weight: 700;
      color: #1f2937;
    }
    .subsection-header {
      background-color: #f9fafb;
      font-weight: 600;
      color: #374151;
      padding-left: 24px !important;
    }
    .item-row {
      background-color: white;
    }
    .item-row:hover {
      background-color: #f9fafb;
    }
    .total-row {
      background-color: #e5e7eb;
      font-weight: 700;
      color: #1f2937;
    }
    .net-income-row {
      background-color: #dbeafe;
      font-weight: 800;
      color: #1e40af;
      font-size: 16px;
    }
    .indent-1 { padding-left: 32px !important; }
    .indent-2 { padding-left: 48px !important; }
    .btn-add {
      padding: 6px 12px;
      background-color: #3b82f6;
      color: white;
      border-radius: 6px;
      font-size: 12px;
      cursor: pointer;
      transition: background-color 0.2s;
    }
    .btn-add:hover {
      background-color: #2563eb;
    }
    .btn-edit, .btn-remove {
      padding: 4px 8px;
      color: white;
      border-radius: 4px;
      font-size: 11px;
      cursor: pointer;
      margin-left: 4px;
    }
    .btn-edit {
      background-color: #f59e0b;
    }
    .btn-edit:hover {
      background-color: #d97706;
    }
    .btn-remove {
      background-color: #ef4444;
    }
    .btn-remove:hover {
      background-color: #dc2626;
    }
    .stat-card {
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .stat-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .statement-card {
      transition: transform 0.3s, box-shadow 0.3s;
      cursor: pointer;
    }
    .statement-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    }
  </style>
</head>
<body class="bg-gray-50 pt-[68px]">

<!-- Navbar -->
<!-- <nav class="bg-white shadow navbar fixed top-0 left-0 w-full z-50">
  <div class="px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center w-full">
    <div class="flex items-center space-x-4">
      <button onclick="window.location='portal.php'" class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-arrow-left text-sm"></i>
      </button>
      <a href="belvic_construction.php" class="flex items-center">
        <i class="fas fa-car text-primary text-lg mr-2"></i>
        <span class="text-sm font-bold text-gray-900">Belvic</span>
      </a>
    </div>

    <div class="hidden md:flex space-x-3 items-center">
      <a href="belvic_construction.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-tachometer-alt mr-1 text-sm"></i> Dashboard
      </a>
      <a href="assets.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-boxes mr-1 text-sm"></i> Assets
      </a>
      <a href="tools.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-tools mr-1 text-sm"></i> Tools
      </a>
      <a href="mileage.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-road mr-1 text-sm"></i> Mileage
      </a>
      <a href="lto.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-id-card mr-1 text-sm"></i> LTO
      </a>
      
      <div class="relative">
        <button id="fuelDropdown" class="nav-item px-2 py-1 text-gray-700 hover:text-primary flex items-center text-sm">
          <i class="fas fa-gas-pump mr-1 text-sm"></i> Fuel <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="fuelDropdownMenu" class="dropdown-menu left-0 mt-2 w-56 bg-white rounded shadow-lg z-10 border border-gray-200">
          <a href="financial_statement.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
            <i class="fas fa-file-invoice-dollar mr-2 text-sm"></i> Financial Statement
          </a>
          <a href="fuel.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
            <i class="fas fa-gas-pump mr-2 text-sm"></i> Fuel
          </a>
        </div>
      </div>

      <a href="insurance.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-shield-alt mr-1 text-sm"></i> Insurance
      </a>
      <a href="lubricant.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-oil-can mr-1 text-sm"></i> Lubricant
      </a>
      <a href="tires.php" class="nav-item text-gray-700 hover:text-primary px-2 py-1 text-sm">
        <i class="fas fa-dot-circle mr-1 text-sm"></i> Tires
      </a>

      <div class="relative">
        <button id="sagDropdown" class="nav-item px-2 py-1 text-gray-700 hover:text-primary flex items-center text-sm">
          <i class="fas fa-cogs mr-1 text-sm"></i> SAG Operations <i class="fas fa-chevron-down ml-1 text-xs"></i>
        </button>
        <div id="sagDropdownMenu" class="dropdown-menu left-0 mt-2 w-56 bg-white rounded shadow-lg z-10 border border-gray-200">
          <a href="damit.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
            <i class="fas fa-cogs mr-2 text-sm"></i> Damit Quary/Bayog
          </a>
          <a href="palomoc.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
            <i class="fas fa-cogs mr-2 text-sm"></i> Palomoc Quary/Titay
          </a>
          <a href="sanghanan.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm">
            <i class="fas fa-cogs mr-2 text-sm"></i> Sanghanan Quary/Kabasalan
          </a>
        </div>
      </div>
    </div>

    <div class="flex items-center space-x-3">
      <div class="hidden md:block relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <i class="fas fa-search text-gray-400 text-sm"></i>
        </div>
        <input type="text" placeholder="Search..." class="pl-8 pr-3 py-1 w-40 border rounded-lg focus:ring-2 focus:ring-primary text-sm">
      </div>
      <button class="text-gray-600 hover:text-gray-900">
        <i class="fas fa-search text-sm"></i>
      </button>
      <div class="relative">
        <button class="text-gray-600 hover:text-gray-900">
          <i class="fas fa-bell text-sm"></i>
        </button>
        <span class="absolute top-0 right-0 bg-red-500 text-white text-[10px] rounded-full h-4 w-4 flex items-center justify-center">3</span>
      </div>
    </div>
  </div>
</nav> -->

<!-- Main Content -->
<div class="flex-1 h-screen px-6 pt-6 pb-10 space-y-6" style="overflow:auto;">
  <div class="flex justify-start">
    <a href="portal.php" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-300">
      <i class="fas fa-arrow-left" aria-hidden="true"></i>
      Back to Portal
    </a>
  </div>
  
  <!-- Header -->
  <div class="bg-gradient-to-r from-blue-900 to-blue-700 rounded-lg shadow-lg p-8 text-white">
    <div class="flex justify-between items-center">
      <div>
        <h1 class="text-3xl font-bold mb-2">Financial Dashboard</h1>
        <p class="text-blue-200">BELVIC Enterprises & Construction</p>
      </div>
      <button onclick="openIncomeStatementModal()" class="bg-white text-blue-900 px-6 py-3 rounded-lg font-semibold hover:bg-blue-50 transition">
        <i class="fas fa-plus mr-2"></i>New Income Statement
      </button>
    </div>
  </div>

  <!-- Summary Stats -->
  <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
    <div class="stat-card bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-600 text-sm mb-1">Total Revenue</p>
          <p class="text-2xl font-bold text-green-600" id="dashTotalRevenue">₱0.00</p>
        </div>
        <div class="bg-green-100 p-3 rounded-full">
          <i class="fas fa-arrow-up text-green-600 text-xl"></i>
        </div>
      </div>
    </div>

    <div class="stat-card bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-600 text-sm mb-1">Total Expenses</p>
          <p class="text-2xl font-bold text-red-600" id="dashTotalExpenses">₱0.00</p>
        </div>
        <div class="bg-red-100 p-3 rounded-full">
          <i class="fas fa-arrow-down text-red-600 text-xl"></i>
        </div>
      </div>
    </div>

    <div class="stat-card bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-600 text-sm mb-1">Net Income</p>
          <p class="text-2xl font-bold text-blue-600" id="dashNetIncome">₱0.00</p>
        </div>
        <div class="bg-blue-100 p-3 rounded-full">
          <i class="fas fa-chart-line text-blue-600 text-xl"></i>
        </div>
      </div>
    </div>

    <div class="stat-card bg-white rounded-lg shadow p-6">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-gray-600 text-sm mb-1">Statements</p>
          <p class="text-2xl font-bold text-purple-600" id="dashTotalStatements">0</p>
        </div>
        <div class="bg-purple-100 p-3 rounded-full">
          <i class="fas fa-file-invoice text-purple-600 text-xl"></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Income Statements List -->
  <div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b flex justify-between items-center">
      <div>
        <h2 class="text-xl font-bold text-gray-800">Income Statements</h2>
        <p class="text-gray-600 mt-1">View and manage financial statements</p>
      </div>
      <div class="flex space-x-2">
        <input type="month" id="filterMonth" class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" onchange="loadStatements()">
        <div class="relative">
          <button id="titleFilterButton" class="px-4 py-2 border rounded-lg flex items-center focus:ring-2 focus:ring-blue-500 hover:bg-gray-50">
            <span id="currentTitleFilter">All Titles</span>
            <i class="fas fa-chevron-down ml-2 text-xs"></i>
          </button>
          <div id="titleFilterMenu" class="dropdown-menu mt-2 w-full bg-white rounded shadow-lg z-10 border border-gray-200">
          </div>
        </div>
      </div>
    </div>

    <div class="p-6">
      <div id="statementsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Statements will be loaded here -->
      </div>
    </div>
  </div>

</div>

<!-- Income Statement Modal -->
<div id="incomeStatementModal" class="modal">
  <div class="modal-content">
    <div class="bg-gradient-to-r from-blue-900 to-blue-700 text-white px-6 py-4 rounded-t-lg flex justify-between items-center">
      <div>
        <h3 class="text-2xl font-bold" id="modalTitle">Income Statement</h3>
        <p class="text-blue-200 text-sm mt-1" id="modalSubtitle">Create new financial statement</p>
      </div>
      <button onclick="closeIncomeStatementModal()" class="text-white hover:text-gray-200 text-2xl">
        <i class="fas fa-times"></i>
      </button>
    </div>

    <div class="p-6">
      <!-- Date Selection -->
      <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
        <div class="flex items-center space-x-4">
          <div class="flex-1">
            <label class="block text-gray-700 font-semibold mb-2">Statement Title</label>
            <div id="titleSelectContainer" class="flex gap-2">
              <select id="statementTitleSelect" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                <option value="">Select or type new...</option>
              </select>
              <button type="button" onclick="toggleCustomTitle()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                <i class="fas fa-edit"></i>
              </button>
            </div>
            <input type="text" id="statementTitleCustom" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter custom title..." required>
          </div>
          <div class="flex-1">
            <label class="block text-gray-700 font-semibold mb-2">Statement Date</label>
            <input type="date" id="statementDate" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
          </div>
          <div class="flex-1">
            <label class="block text-gray-700 font-semibold mb-2">Period</label>
            <input type="text" id="statementPeriod" class="w-full px-4 py-2 border rounded-lg bg-gray-100" readonly placeholder="Auto-generated">
          </div>
          <input type="hidden" id="statementId" value="">
        </div>
      </div>

      <!-- Financial Statement Table -->
      <div class="overflow-x-auto">
        <table class="financial-table">
          <tbody>
            <!-- REVENUE SECTION -->
            <tr class="section-header">
              <td colspan="2"><strong>REVENUE</strong></td>
            </tr>
            <tr class="subsection-header">
              <td class="indent-1">Gross Revenue</td>
              <td class="text-right">
                <button class="btn-add" onclick="openItemModal('revenue')">
                  <i class="fas fa-plus"></i> Add
                </button>
              </td>
            </tr>
            <tbody id="revenueItems"></tbody>
            <tr class="total-row">
              <td class="indent-1">Total Gross Revenue</td>
              <td class="text-right" id="totalGrossRevenue">₱0.00</td>
            </tr>

            <!-- LESS SECTION -->
            <tr class="subsection-header">
              <td class="indent-1">Less:</td>
              <td class="text-right">
                <button class="btn-add" onclick="openItemModal('less')">
                  <i class="fas fa-plus"></i> Add
                </button>
              </td>
            </tr>
            <tbody id="lessItems"></tbody>
            <tr class="total-row">
              <td class="indent-1">Total Deductions</td>
              <td class="text-right" id="totalLess">₱0.00</td>
            </tr>

            <!-- NET REVENUE -->
            <tr class="net-income-row">
              <td><strong>NET REVENUE</strong></td>
              <td class="text-right" id="netRevenue">₱0.00</td>
            </tr>

            <!-- OTHER INCOME -->
            <tr class="section-header">
              <td colspan="2"><strong>OTHER INCOME</strong></td>
            </tr>
            <tr class="subsection-header">
              <td class="indent-1">Other Income</td>
              <td class="text-right">
                <button class="btn-add" onclick="openItemModal('other_income')">
                  <i class="fas fa-plus"></i> Add
                </button>
              </td>
            </tr>
            <tbody id="otherIncomeItems"></tbody>
            <tr class="total-row">
              <td class="indent-1">Total Other Income</td>
              <td class="text-right" id="totalOtherIncome">₱0.00</td>
            </tr>

            <!-- REPAIR & MAINTENANCE -->
            <tr class="section-header">
              <td colspan="2"><strong>REPAIR & MAINTENANCE - HEAVY EQUIPMENT</strong></td>
            </tr>
            <tr class="subsection-header">
              <td class="indent-1">Equipment Repairs</td>
              <td class="text-right">
                <button class="btn-add" onclick="openItemModal('repair')">
                  <i class="fas fa-plus"></i> Add
                </button>
              </td>
            </tr>
            <tbody id="repairItems"></tbody>
            <tr class="total-row">
              <td class="indent-1">Total Repair & Maintenance</td>
              <td class="text-right" id="totalRepair">₱0.00</td>
            </tr>

            <!-- NET INCOME / LOSS -->
            <tr class="net-income-row" style="background-color: #1e40af; color: white;">
              <td><strong>NET INCOME / (NET LOSS)</strong></td>
              <td class="text-right" id="netIncome">₱0.00</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Action Buttons -->
      <div class="mt-6 flex justify-end space-x-3">
        <button onclick="closeIncomeStatementModal()" class="px-6 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 font-semibold">
          <i class="fas fa-times mr-2"></i>Cancel
        </button>
        <button onclick="saveIncomeStatement()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
          <i class="fas fa-save mr-2"></i>Save Statement
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Item Add/Edit Modal -->
<div id="itemModal" class="modal">
  <div class="modal-content" style="max-width: 500px;">
    <div class="bg-blue-600 text-white px-6 py-4 rounded-t-lg">
      <h3 class="text-xl font-bold" id="itemModalTitle">Add Item</h3>
    </div>
    <form id="itemForm" class="p-6">
      <input type="hidden" id="itemId" value="">
      <input type="hidden" id="itemCategory" value="">
      
      <div class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Item Name</label>
        <div class="flex gap-2">
          <select id="itemNameSelect" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            <option value="">Select or type new...</option>
          </select>
          <button type="button" onclick="toggleCustomInput()" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
            <i class="fas fa-edit"></i>
          </button>
        </div>
        <input type="text" id="itemNameCustom" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 mt-2 hidden" placeholder="Enter custom name...">
      </div>

      <div class="mb-4">
        <label class="block text-gray-700 font-semibold mb-2">Amount</label>
        <input type="number" id="itemAmount" step="0.01" min="0" class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500" required>
      </div>

      <div class="mb-6">
        <div class="flex justify-between items-center mb-2">
          <label class="block text-gray-700 font-semibold">Manage Options</label>
          <button type="button" onclick="showAddOption()" class="text-blue-600 hover:text-blue-800 text-sm">
            <i class="fas fa-plus-circle"></i> Add New Option
          </button>
        </div>
        <div id="addOptionForm" class="hidden mb-3 flex gap-2">
          <input type="text" id="newOptionName" class="flex-1 px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" placeholder="New option name...">
          <button type="button" onclick="saveNewOption()" class="px-3 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Save</button>
          <button type="button" onclick="hideAddOption()" class="px-3 py-2 bg-gray-400 text-white rounded hover:bg-gray-500 text-sm">Cancel</button>
        </div>
        <div id="optionsList" class="max-h-48 overflow-y-auto border rounded-lg p-3 bg-gray-50"></div>
      </div>

      <div class="flex gap-3">
        <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
          <i class="fas fa-save mr-2"></i>Save
        </button>
        <button type="button" onclick="closeItemModal()" class="flex-1 px-4 py-2 bg-gray-400 text-white rounded-lg hover:bg-gray-500 font-semibold">
          <i class="fas fa-times mr-2"></i>Cancel
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// Global variables
let currentStatementId = null;
let currentCategory = '';
let statementItems = {
  revenue: [],
  less: [],
  other_income: [],
  repair: []
};
let storedOptions = JSON.parse(localStorage.getItem('financialOptions')) || {};
let options = {
  revenue: storedOptions.revenue || [],
  less: storedOptions.less || [],
  other_income: storedOptions.other_income || [],
  repair: storedOptions.repair || [],
  titles: storedOptions.titles || []
};
let usingCustom = false;
let usingCustomTitle = false;
let editingOptionIndex = -1;
let currentFilterTitle = '';

// Initialize
document.addEventListener('DOMContentLoaded', () => {
  toggleDropdown('sagDropdown', 'sagDropdownMenu');
  toggleDropdown('fuelDropdown', 'fuelDropdownMenu');
  closeDropdownOnClickOutside();
  
  const titleFilterButton = document.getElementById('titleFilterButton');
  const titleFilterMenu = document.getElementById('titleFilterMenu');
  titleFilterButton.addEventListener('click', (e) => {
    e.stopPropagation();
    titleFilterMenu.classList.toggle('open');
  });
  
  // Set current date
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('statementDate').value = today;
  updatePeriodFromDate();
  
  // Set filter to empty for "view all" by default
  document.getElementById('filterMonth').value = '';
  
  loadFilterTitles();
  loadStatements();
  loadDashboardStats();
});

// Dropdown functionality
function toggleDropdown(buttonId, menuId) {
  const button = document.getElementById(buttonId);
  const menu = document.getElementById(menuId);
  if (!button || !menu) return;
  button.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    document.querySelectorAll('.dropdown-menu').forEach(dropdown => {
      if (dropdown.id !== menuId) dropdown.classList.remove('open');
    });
    menu.classList.toggle('open');
  });
}

function closeDropdownOnClickOutside() {
  document.addEventListener('click', (e) => {
    const isDropdownButton = e.target.closest('button[id$="Dropdown"]') || e.target.closest('#titleFilterButton');
    const isDropdownMenu = e.target.closest('.dropdown-menu');
    if (!isDropdownButton && !isDropdownMenu) {
      document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.classList.remove('open');
      });
    }
  });
}

// Update period based on selected date
document.getElementById('statementDate').addEventListener('change', updatePeriodFromDate);

function updatePeriodFromDate() {
  const dateInput = document.getElementById('statementDate').value;
  if(dateInput) {
    const date = new Date(dateInput);
    const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const period = `${months[date.getMonth()]} ${date.getFullYear()}`;
    document.getElementById('statementPeriod').value = period;
  }
}

// Format currency
function formatCurrency(amount) {
  return '₱' + parseFloat(amount).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// Load dashboard statistics
async function loadDashboardStats() {
  try {
    const response = await fetch('api/financial_statement.php?action=get_dashboard_stats');
    const result = await response.json();
    
    if(result.success) {
      document.getElementById('dashTotalRevenue').textContent = formatCurrency(result.data.total_revenue);
      document.getElementById('dashTotalExpenses').textContent = formatCurrency(result.data.total_expenses);
      document.getElementById('dashNetIncome').textContent = formatCurrency(result.data.net_income);
      document.getElementById('dashTotalStatements').textContent = result.data.total_statements;
    }
  } catch(error) {
    console.error('Error loading dashboard stats:', error);
  }
}

// Load statements list
async function loadStatements() {
  const filterMonth = document.getElementById('filterMonth').value;
  const filterTitle = currentFilterTitle;
  
  try {
    const response = await fetch(`api/financial_statement.php?action=get_statements&filter_month=${filterMonth}&filter_title=${encodeURIComponent(filterTitle)}`);
    const result = await response.json();
    
    if(result.success) {
      renderStatementsList(result.data);
    }
  } catch(error) {
    console.error('Error loading statements:', error);
  }
}

// Render statements list
function renderStatementsList(statements) {
  const container = document.getElementById('statementsList');
  
  if(statements.length === 0) {
    container.innerHTML = '<div class="col-span-3 text-center py-12 text-gray-500"><i class="fas fa-file-invoice text-4xl mb-4"></i><p>No income statements found</p></div>';
    return;
  }
  
  container.innerHTML = '';
  statements.forEach(statement => {
    const netIncome = statement.net_income;
    const incomeClass = netIncome >= 0 ? 'text-green-600' : 'text-red-600';
    const incomeIcon = netIncome >= 0 ? 'fa-arrow-up' : 'fa-arrow-down';
    
    container.innerHTML += `
      <div class="statement-card bg-white border rounded-lg p-6 hover:shadow-lg transition">
        <div class="flex justify-between items-start mb-4">
          <div>
            <h3 class="text-lg font-bold text-gray-900">${statement.title ?? 'Untitled'}</h3>
            <p class="text-sm text-gray-600">${statement.period} - ${statement.statement_date}</p>
          </div>
          <button onclick="deleteStatement(${statement.id})" class="text-red-600 hover:text-red-800">
            <i class="fas fa-trash"></i>
          </button>
        </div>
        <div class="space-y-2 mb-4">
          <div class="flex justify-between">
            <span class="text-gray-600 text-sm">Revenue:</span>
            <span class="font-semibold text-green-600">${formatCurrency(statement.total_revenue)}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-600 text-sm">Expenses:</span>
            <span class="font-semibold text-red-600">${formatCurrency(statement.total_expenses)}</span>
          </div>
          <div class="flex justify-between pt-2 border-t">
            <span class="text-gray-700 font-semibold">Net Income:</span>
            <span class="font-bold ${incomeClass}">
              <i class="fas ${incomeIcon} mr-1"></i>${formatCurrency(netIncome)}
            </span>
          </div>
        </div>
        <div class="flex space-x-2">
          <button onclick="viewStatement(${statement.id})" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
            <i class="fas fa-eye mr-2"></i>View
          </button>
          <button onclick="editStatement(${statement.id})" class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">
            <i class="fas fa-edit mr-2"></i>Edit
          </button>
        </div>
      </div>
    `;
  });
}

// Load titles into select
function loadTitlesSelect() {
  const select = document.getElementById('statementTitleSelect');
  select.innerHTML = '<option value="">Select or type new...</option>';
  options.titles.forEach(opt => {
    select.innerHTML += `<option value="${opt}">${opt}</option>`;
  });
}

// Load filter titles
function loadFilterTitles() {
  const menu = document.getElementById('titleFilterMenu');
  menu.innerHTML = '';
  ['All Titles', ...options.titles].forEach((opt, index) => {
    const value = (index === 0) ? '' : opt;
    const a = document.createElement('a');
    a.href = '#';
    a.classList = 'block px-4 py-2 text-gray-700 hover:bg-gray-100 text-sm';
    a.textContent = opt;
    a.addEventListener('click', (e) => {
      e.preventDefault();
      document.getElementById('currentTitleFilter').textContent = opt;
      currentFilterTitle = value;
      loadStatements();
      menu.classList.remove('open');
    });
    menu.appendChild(a);
  });
}

// Toggle custom title input
function toggleCustomTitle() {
  usingCustomTitle = !usingCustomTitle;
  const customInput = document.getElementById('statementTitleCustom');
  const selectContainer = document.getElementById('titleSelectContainer');
  customInput.classList.toggle('hidden');
  selectContainer.classList.toggle('hidden');
  if (usingCustomTitle) {
    customInput.focus();
  }
}

// Open income statement modal
function openIncomeStatementModal() {
  currentStatementId = null;
  statementItems = { revenue: [], less: [], other_income: [], repair: [] };
  
  const today = new Date().toISOString().split('T')[0];
  document.getElementById('statementDate').value = today;
  updatePeriodFromDate();
  document.getElementById('statementId').value = '';
  
  loadTitlesSelect();
  usingCustomTitle = false;
  document.getElementById('statementTitleSelect').value = '';
  document.getElementById('statementTitleCustom').value = '';
  document.getElementById('statementTitleCustom').classList.add('hidden');
  document.getElementById('titleSelectContainer').classList.remove('hidden');
  
  document.getElementById('modalTitle').textContent = 'New Income Statement';
  document.getElementById('modalSubtitle').textContent = 'Create new financial statement';
  
  renderAllItems();
  document.getElementById('incomeStatementModal').classList.add('show');
}

// Close income statement modal
function closeIncomeStatementModal() {
  document.getElementById('incomeStatementModal').classList.remove('show');
}

// View statement
async function viewStatement(id) {
  await editStatement(id, true);
}

// Edit statement
async function editStatement(id, viewOnly = false) {
  try {
    const response = await fetch(`api/financial_statement.php?action=get_statement_details&id=${id}`);
    const result = await response.json();
    
    if(result.success) {
      currentStatementId = id;
      const data = result.data;
      
      document.getElementById('statementId').value = id;
      document.getElementById('statementDate').value = data.statement_date;
      document.getElementById('statementPeriod').value = data.period;
      
      loadTitlesSelect();
      if (options.titles.includes(data.title)) {
        usingCustomTitle = false;
        document.getElementById('statementTitleSelect').value = data.title;
        document.getElementById('statementTitleCustom').classList.add('hidden');
        document.getElementById('titleSelectContainer').classList.remove('hidden');
      } else {
        usingCustomTitle = true;
        document.getElementById('statementTitleCustom').value = data.title;
        document.getElementById('statementTitleCustom').classList.remove('hidden');
        document.getElementById('titleSelectContainer').classList.add('hidden');
      }
      
      document.getElementById('modalTitle').textContent = viewOnly ? 'View Income Statement' : 'Edit Income Statement';
      document.getElementById('modalSubtitle').textContent = `${data.title} - ${data.period}`;
      
      statementItems = {
        revenue: data.items.revenue || [],
        less: data.items.less || [],
        other_income: data.items.other_income || [],
        repair: data.items.repair || []
      };
      
      renderAllItems();
      document.getElementById('incomeStatementModal').classList.add('show');
    }
  } catch(error) {
    console.error('Error loading statement:', error);
    alert('Error loading statement');
  }
}

// Delete statement
async function deleteStatement(id) {
  if(!confirm('Are you sure you want to delete this income statement?')) return;
  
  const formData = new FormData();
  formData.append('action', 'delete_statement');
  formData.append('id', id);
  
  try {
    const response = await fetch('api/financial_statement.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    
    if(result.success) {
      loadStatements();
      loadDashboardStats();
      alert('Statement deleted successfully!');
    } else {
      alert(result.message || 'Failed to delete statement');
    }
  } catch(error) {
    console.error('Error deleting statement:', error);
    alert('Error deleting statement');
  }
}

// Save income statement
async function saveIncomeStatement() {
  const statementDate = document.getElementById('statementDate').value;
  let statementTitle;
  if (usingCustomTitle) {
    statementTitle = document.getElementById('statementTitleCustom').value.trim();
  } else {
    statementTitle = document.getElementById('statementTitleSelect').value.trim();
  }
  const period = document.getElementById('statementPeriod').value;
  const statementId = document.getElementById('statementId').value;
  
  if(!statementDate) {
    alert('Please select a statement date');
    return;
  }
  
  if(!statementTitle) {
    alert('Please enter a statement title');
    return;
  }
  
  const formData = new FormData();
  formData.append('action', statementId ? 'update_statement' : 'create_statement');
  if(statementId) formData.append('id', statementId);
  formData.append('statement_date', statementDate);
  formData.append('title', statementTitle);
  formData.append('period', period);
  formData.append('items', JSON.stringify(statementItems));
  
  try {
    const response = await fetch('api/financial_statement.php', {
      method: 'POST',
      body: formData
    });
    const result = await response.json();
    
    if(result.success) {
      if (!options.titles.includes(statementTitle)) {
        options.titles.push(statementTitle);
        saveOptions();
        loadFilterTitles();
      }
      closeIncomeStatementModal();
      loadStatements();
      loadDashboardStats();
      alert(statementId ? 'Statement updated successfully!' : 'Statement created successfully!');
    } else {
      alert(result.message || 'Failed to save statement');
    }
  } catch(error) {
    console.error('Error saving statement:', error);
    alert('Error saving statement');
  }
}

// Render all items
function renderAllItems() {
  const categories = {
    revenue: { tbody: 'revenueItems', total: 'totalGrossRevenue' },
    less: { tbody: 'lessItems', total: 'totalLess' },
    other_income: { tbody: 'otherIncomeItems', total: 'totalOtherIncome' },
    repair: { tbody: 'repairItems', total: 'totalRepair' }
  };

  let totals = { revenue: 0, less: 0, other_income: 0, repair: 0 };

  for(let [category, config] of Object.entries(categories)) {
    const tbody = document.getElementById(config.tbody);
    tbody.innerHTML = '';
    
    statementItems[category].forEach((item, index) => {
      totals[category] += item.amount;
      tbody.innerHTML += `
        <tr class="item-row">
          <td class="indent-2">${item.name}</td>
          <td class="text-right">
            ${formatCurrency(item.amount)}
            <button class="btn-edit" onclick="editItem('${category}', ${index})">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn-remove" onclick="removeItem('${category}', ${index})">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
      `;
    });
    
    document.getElementById(config.total).textContent = formatCurrency(totals[category]);
  }

  const netRevenue = totals.revenue - totals.less;
  const netIncome = netRevenue + totals.other_income - totals.repair;

  document.getElementById('netRevenue').textContent = formatCurrency(netRevenue);
  document.getElementById('netIncome').textContent = formatCurrency(netIncome);
}

// Open item modal
function openItemModal(category) {
  currentCategory = category;
  
  const titles = {
    revenue: 'Revenue',
    less: 'Deduction',
    other_income: 'Other Income',
    repair: 'Repair & Maintenance'
  };
  
  document.getElementById('itemModalTitle').textContent = `Add ${titles[category]}`;
  document.getElementById('itemForm').reset();
  document.getElementById('itemId').value = '';
  document.getElementById('itemCategory').value = category;
  
  usingCustom = false;
  document.getElementById('itemNameCustom').classList.add('hidden');
  document.getElementById('itemNameSelect').classList.remove('hidden');
  document.getElementById('itemNameSelect').value = '';

  loadOptionsSelect();
  loadOptionsList();
  hideAddOption();
  editingOptionIndex = -1;

  document.getElementById('itemModal').classList.add('show');
}

// Close item modal
function closeItemModal() {
  document.getElementById('itemModal').classList.remove('show');
}

// Edit item
function editItem(category, index) {
  currentCategory = category;
  const item = statementItems[category][index];
  
  const titles = {
    revenue: 'Revenue',
    less: 'Deduction',
    other_income: 'Other Income',
    repair: 'Repair & Maintenance'
  };
  
  document.getElementById('itemModalTitle').textContent = `Edit ${titles[category]}`;
  document.getElementById('itemId').value = index;
  document.getElementById('itemCategory').value = category;
  document.getElementById('itemAmount').value = item.amount;

  loadOptionsSelect();
  loadOptionsList();
  hideAddOption();
  editingOptionIndex = -1;

  if (options[category].includes(item.name)) {
    usingCustom = false;
    document.getElementById('itemNameSelect').value = item.name;
    document.getElementById('itemNameCustom').classList.add('hidden');
    document.getElementById('itemNameSelect').classList.remove('hidden');
  } else {
    usingCustom = true;
    document.getElementById('itemNameCustom').value = item.name;
    document.getElementById('itemNameCustom').classList.remove('hidden');
    document.getElementById('itemNameSelect').classList.add('hidden');
  }
  
  document.getElementById('itemModal').classList.add('show');
}

// Remove item
function removeItem(category, index) {
  if(!confirm('Are you sure you want to remove this item?')) return;
  
  statementItems[category].splice(index, 1);
  renderAllItems();
}

// Load options into select
function loadOptionsSelect() {
  const select = document.getElementById('itemNameSelect');
  select.innerHTML = '<option value="">Select or type new...</option>';
  options[currentCategory].forEach(opt => {
    select.innerHTML += `<option value="${opt}">${opt}</option>`;
  });
}

// Load options list for management
function loadOptionsList() {
  const list = document.getElementById('optionsList');
  list.innerHTML = '';
  if (options[currentCategory].length === 0) {
    list.innerHTML = '<p class="text-gray-500 text-sm">No options added yet</p>';
    return;
  }
  options[currentCategory].forEach((opt, index) => {
    list.innerHTML += `
      <div class="flex justify-between items-center p-2 border-b last:border-0">
        <span class="text-gray-700">${opt}</span>
        <div>
          <button type="button" onclick="editOption(${index})" class="text-blue-600 mr-2"><i class="fas fa-edit"></i></button>
          <button type="button" onclick="deleteOption(${index})" class="text-red-600"><i class="fas fa-trash"></i></button>
        </div>
      </div>
    `;
  });
}

// Toggle custom input
function toggleCustomInput() {
  usingCustom = !usingCustom;
  const customInput = document.getElementById('itemNameCustom');
  const select = document.getElementById('itemNameSelect');
  customInput.classList.toggle('hidden');
  select.classList.toggle('hidden');
  if (usingCustom) {
    customInput.focus();
  }
}

// Show add option form
function showAddOption() {
  document.getElementById('addOptionForm').classList.remove('hidden');
  document.getElementById('newOptionName').focus();
}

// Hide add option form
function hideAddOption() {
  document.getElementById('addOptionForm').classList.add('hidden');
  document.getElementById('newOptionName').value = '';
}

// Save new/edited option
function saveNewOption() {
  const name = document.getElementById('newOptionName').value.trim();
  if (!name) {
    alert('Please enter an option name');
    return;
  }
  if (editingOptionIndex === -1) {
    options[currentCategory].push(name);
  } else {
    options[currentCategory][editingOptionIndex] = name;
  }
  saveOptions();
  loadOptionsSelect();
  loadOptionsList();
  hideAddOption();
  editingOptionIndex = -1;
}

// Edit option
function editOption(index) {
  editingOptionIndex = index;
  document.getElementById('newOptionName').value = options[currentCategory][index];
  showAddOption();
}

// Delete option
function deleteOption(index) {
  if (!confirm('Are you sure you want to delete this option?')) return;
  options[currentCategory].splice(index, 1);
  saveOptions();
  loadOptionsSelect();
  loadOptionsList();
}

// Item form submit
document.getElementById('itemForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const itemId = document.getElementById('itemId').value;
  const category = document.getElementById('itemCategory').value;
  let itemName;
  if (usingCustom) {
    itemName = document.getElementById('itemNameCustom').value.trim();
  } else {
    itemName = document.getElementById('itemNameSelect').value.trim();
  }
  const itemAmount = parseFloat(document.getElementById('itemAmount').value);
  
  if(!itemName || !itemAmount) {
    alert('Please fill in all fields');
    return;
  }
  
  if(itemId !== '') {
    // Edit existing item
    statementItems[category][parseInt(itemId)] = { name: itemName, amount: itemAmount };
  } else {
    // Add new item
    statementItems[category].push({ name: itemName, amount: itemAmount });
  }
  
  renderAllItems();
  closeItemModal();
});

// Close modals when clicking outside
document.getElementById('incomeStatementModal').addEventListener('click', function(e) {
  if(e.target === this) closeIncomeStatementModal();
});

document.getElementById('itemModal').addEventListener('click', function(e) {
  if(e.target === this) closeItemModal();
});

function saveOptions() {
  localStorage.setItem('financialOptions', JSON.stringify(options));
}
</script>

</body>
</html>