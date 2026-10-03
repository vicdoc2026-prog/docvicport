<?php
require_once 'config/conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm Management System - Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .tab-button.active { background-color: #10b981; color: white; }
        .modal { display: none; }
        .modal.active { display: flex; }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Header -->
    <div class="bg-white shadow">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-between items-center">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-tractor text-green-600 mr-2"></i>
                    Farm Management System
                </h1>
                <div class="flex items-center gap-4">
                    <span class="text-gray-600">Welcome, <?php echo $_SESSION['full_name']; ?></span>
                    <a href="dashboard.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-chart-line mr-2"></i>View Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Tabs -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="border-b border-gray-200">
                <nav class="flex -mb-px">
                    <button onclick="switchTab('animals')" class="tab-button active px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:border-gray-300">
                        <i class="fas fa-paw mr-2"></i>Animals
                    </button>
                    <button onclick="switchTab('machineries')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:border-gray-300">
                        <i class="fas fa-cogs mr-2"></i>Machineries
                    </button>
                    <button onclick="switchTab('trees')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:border-gray-300">
                        <i class="fas fa-tree mr-2"></i>Trees
                    </button>
                    <button onclick="switchTab('projects')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:border-gray-300">
                        <i class="fas fa-tasks mr-2"></i>Projects
                    </button>
                    <button onclick="switchTab('infrastructure')" class="tab-button px-6 py-3 text-sm font-medium border-b-2 border-transparent hover:border-gray-300">
                        <i class="fas fa-building mr-2"></i>Infrastructure
                    </button>
                </nav>
            </div>
        </div>

        <!-- Animals Section -->
        <div id="animals-section" class="tab-content">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Animal Records</h2>
                    <button onclick="openAddModal('animal')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Add Animal
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="mb-4 flex gap-4">
                    <select id="animal-year" class="border rounded-lg px-4 py-2">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                    <select id="animal-month" class="border rounded-lg px-4 py-2">
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
                    <button onclick="loadAnimals()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Animal Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="animals-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Machineries Section -->
        <div id="machineries-section" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Machinery Records</h2>
                    <button onclick="openAddModal('machinery')" class="bg-purple-600 text-white px-4 py-2 rounded-lg hover:bg-purple-700">
                        <i class="fas fa-plus mr-2"></i>Add Machinery
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="mb-4 flex gap-4">
                    <select id="machinery-year" class="border rounded-lg px-4 py-2">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                    <button onclick="loadMachineries()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Machinery Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="machineries-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Trees Section -->
        <div id="trees-section" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Tree Records</h2>
                    <button onclick="openAddModal('tree')" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>Add Tree
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="mb-4 flex gap-4">
                    <select id="tree-year" class="border rounded-lg px-4 py-2">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                    <button onclick="loadTrees()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tree Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Notes</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="trees-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <div id="projects-section" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Project Records</h2>
                    <button onclick="openAddModal('project')" class="bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700">
                        <i class="fas fa-plus mr-2"></i>Add Project
                    </button>
                </div>
                
                <!-- Filters -->
                <div class="mb-4 flex gap-4">
                    <select id="project-year" class="border rounded-lg px-4 py-2">
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                    </select>
                    <button onclick="loadProjects()" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Filter
                    </button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Budget</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="projects-table-body" class="bg-white divide-y divide-gray-200">
                            <!-- Dynamic content -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Infrastructure Section -->
        <div id="infrastructure-section" class="tab-content hidden">
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Infrastructure Management</h2>
                <p class="text-gray-600">Infrastructure CRUD functionality coming soon...</p>
            </div>
        </div>

    </div>

    <!-- Add/Edit Modal -->
    <div id="crud-modal" class="modal fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
            <div class="flex justify-between items-center border-b p-6">
                <h3 id="modal-title" class="text-xl font-bold text-gray-800">Add Record</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="p-6">
                <form id="crud-form">
                    <input type="hidden" id="record-id">
                    <input type="hidden" id="record-type">
                    <div id="form-fields" class="space-y-4">
                        <!-- Dynamic form fields -->
                    </div>
                    <div class="mt-6 flex gap-4">
                        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>Save
                        </button>
                        <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
            
            event.target.classList.add('active');
            document.getElementById(tab + '-section').classList.remove('hidden');
            
            // Load data for the selected tab
            switch(tab) {
                case 'animals': loadAnimals(); break;
                case 'machineries': loadMachineries(); break;
                case 'trees': loadTrees(); break;
                case 'projects': loadProjects(); break;
            }
        }

        // Load Animals
        function loadAnimals() {
            const year = document.getElementById('animal-year').value;
            const month = document.getElementById('animal-month').value;
            
            let url = `api/animals.php?action=list&year=${year}`;
            if (month) {
                url = `api/animals.php?action=by_month&year=${year}&month=${month}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayAnimals(data.data);
                    }
                });
        }

        function displayAnimals(animals) {
            const tbody = document.getElementById('animals-table-body');
            tbody.innerHTML = '';
            
            if (animals.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>';
                return;
            }
            
            animals.forEach(animal => {
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${animal.year}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${getMonthName(animal.month)}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${animal.animal_type}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${animal.quantity}</td>
                        <td class="px-6 py-4">${animal.notes || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="editAnimal(${animal.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteRecord('animal', ${animal.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Load Machineries
        function loadMachineries() {
            const year = document.getElementById('machinery-year').value;
            fetch(`api/machineries.php?action=list&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayMachineries(data.data);
                    }
                });
        }

        function displayMachineries(machineries) {
            const tbody = document.getElementById('machineries-table-body');
            tbody.innerHTML = '';
            
            if (machineries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>';
                return;
            }
            
            machineries.forEach(machinery => {
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${machinery.year}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${getMonthName(machinery.month)}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${machinery.machinery_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${machinery.quantity}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${getStatusColor(machinery.status)}">
                                ${machinery.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="editMachinery(${machinery.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteRecord('machinery', ${machinery.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Load Trees
        function loadTrees() {
            const year = document.getElementById('tree-year').value;
            fetch(`api/trees.php?action=list&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTrees(data.data);
                    }
                });
        }

        function displayTrees(trees) {
            const tbody = document.getElementById('trees-table-body');
            tbody.innerHTML = '';
            
            if (trees.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>';
                return;
            }
            
            trees.forEach(tree => {
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${tree.year}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${getMonthName(tree.month)}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${tree.tree_type}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${tree.quantity}</td>
                        <td class="px-6 py-4">${tree.notes || '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="editTree(${tree.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteRecord('tree', ${tree.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Load Projects
        function loadProjects() {
            const year = document.getElementById('project-year').value;
            fetch(`api/projects.php?action=list&year=${year}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayProjects(data.data);
                    }
                });
        }

        function displayProjects(projects) {
            const tbody = document.getElementById('projects-table-body');
            tbody.innerHTML = '';
            
            if (projects.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-4 text-center text-gray-500">No records found</td></tr>';
                return;
            }
            
            projects.forEach(project => {
                const row = `
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">${project.year}</td>
                        <td class="px-6 py-4 whitespace-nowrap">${getMonthName(project.month)}</td>
                        <td class="px-6 py-4">${project.project_name}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs rounded-full ${getProjectStatusColor(project.status)}">
                                ${project.status}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">${project.budget ? '$' + project.budget : '-'}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <button onclick="editProject(${project.id})" class="text-blue-600 hover:text-blue-800 mr-3">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteRecord('project', ${project.id})" class="text-red-600 hover:text-red-800">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        }

        // Modal functions
        function openAddModal(type) {
            document.getElementById('record-id').value = '';
            document.getElementById('record-type').value = type;
            document.getElementById('modal-title').textContent = 'Add ' + type.charAt(0).toUpperCase() + type.slice(1);
            
            generateFormFields(type);
            document.getElementById('crud-modal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('crud-modal').classList.remove('active');
            document.getElementById('crud-form').reset();
        }

        function generateFormFields(type) {
            const formFields = document.getElementById('form-fields');
            let fields = '';
            
            // Common fields
            fields += `
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Year</label>
                        <input type="number" id="field-year" value="2025" class="w-full border rounded-lg px-3 py-2" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Month</label>
                        <select id="field-month" class="w-full border rounded-lg px-3 py-2" required>
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
                </div>
            `;
            
            switch(type) {
                case 'animal':
                    fields += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Animal Type</label>
                            <select id="field-animal_type" class="w-full border rounded-lg px-3 py-2" required>
                                <option value="Layer">Layer</option>
                                <option value="Duck">Duck</option>
                                <option value="Chicken">Chicken</option>
                                <option value="Turkey">Turkey</option>
                                <option value="Pigs">Pigs</option>
                                <option value="Cattle">Cattle</option>
                                <option value="Cornero">Cornero</option>
                                <option value="Goat">Goat</option>
                                <option value="Horse">Horse</option>
                                <option value="Gansa">Gansa</option>
                                <option value="Cow">Cow</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <input type="number" id="field-quantity" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea id="field-notes" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
                        </div>
                    `;
                    break;
                    
                case 'machinery':
                    fields += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Machinery Name</label>
                            <input type="text" id="field-machinery_name" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                                <input type="number" id="field-quantity" class="w-full border rounded-lg px-3 py-2" required>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                <select id="field-status" class="w-full border rounded-lg px-3 py-2" required>
                                    <option value="Working">Working</option>
                                    <option value="Needs Repair">Needs Repair</option>
                                    <option value="Broken">Broken</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea id="field-notes" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
                        </div>
                    `;
                    break;
                    
                case 'tree':
                    fields += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tree Type</label>
                            <select id="field-tree_type" class="w-full border rounded-lg px-3 py-2" required>
                                <option value="Rubber">Rubber</option>
                                <option value="Coconut">Coconut</option>
                                <option value="Mango">Mango</option>
                                <option value="Banana">Banana</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quantity</label>
                            <input type="number" id="field-quantity" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                            <textarea id="field-notes" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
                        </div>
                    `;
                    break;
                    
                case 'project':
                    fields += `
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Project Name</label>
                            <input type="text" id="field-project_name" class="w-full border rounded-lg px-3 py-2" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                            <select id="field-status" class="w-full border rounded-lg px-3 py-2" required>
                                <option value="Completed">Completed</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Pending">Pending</option>
                                <option value="Almost Done">Almost Done</option>
                                <option value="Needs Repair">Needs Repair</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                                <input type="date" id="field-start_date" class="w-full border rounded-lg px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                                <input type="date" id="field-end_date" class="w-full border rounded-lg px-3 py-2">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Budget</label>
                            <input type="number" step="0.01" id="field-budget" class="w-full border rounded-lg px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea id="field-description" class="w-full border rounded-lg px-3 py-2" rows="3"></textarea>
                        </div>
                    `;
                    break;
            }
            
            formFields.innerHTML = fields;
        }

        // Form submission
        document.getElementById('crud-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const type = document.getElementById('record-type').value;
            const id = document.getElementById('record-id').value;
            
            const formData = {
                id: id,
                year: document.getElementById('field-year').value,
                month: document.getElementById('field-month').value
            };
            
            // Get type-specific fields
            switch(type) {
                case 'animal':
                    formData.animal_type = document.getElementById('field-animal_type').value;
                    formData.quantity = document.getElementById('field-quantity').value;
                    formData.notes = document.getElementById('field-notes').value;
                    break;
                case 'machinery':
                    formData.machinery_name = document.getElementById('field-machinery_name').value;
                    formData.quantity = document.getElementById('field-quantity').value;
                    formData.status = document.getElementById('field-status').value;
                    formData.notes = document.getElementById('field-notes').value;
                    break;
                case 'tree':
                    formData.tree_type = document.getElementById('field-tree_type').value;
                    formData.quantity = document.getElementById('field-quantity').value;
                    formData.notes = document.getElementById('field-notes').value;
                    break;
                case 'project':
                    formData.project_name = document.getElementById('field-project_name').value;
                    formData.status = document.getElementById('field-status').value;
                    formData.start_date = document.getElementById('field-start_date').value;
                    formData.end_date = document.getElementById('field-end_date').value;
                    formData.budget = document.getElementById('field-budget').value;
                    formData.description = document.getElementById('field-description').value;
                    break;
            }
            
            const action = id ? 'update' : 'create';
            const apiUrl = `api/${type}s.php?action=${action}`;
            
            fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    closeModal();
                    
                    // Reload the appropriate list
                    switch(type) {
                        case 'animal': loadAnimals(); break;
                        case 'machinery': loadMachineries(); break;
                        case 'tree': loadTrees(); break;
                        case 'project': loadProjects(); break;
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            });
        });

        // Edit functions
        function editAnimal(id) {
            fetch(`api/animals.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        openEditModal('animal', data.data);
                    }
                });
        }

        function editMachinery(id) {
            fetch(`api/machineries.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        openEditModal('machinery', data.data);
                    }
                });
        }

        function editTree(id) {
            fetch(`api/trees.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        openEditModal('tree', data.data);
                    }
                });
        }

        function editProject(id) {
            fetch(`api/projects.php?action=get&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        openEditModal('project', data.data);
                    }
                });
        }

        function openEditModal(type, data) {
            document.getElementById('record-id').value = data.id;
            document.getElementById('record-type').value = type;
            document.getElementById('modal-title').textContent = 'Edit ' + type.charAt(0).toUpperCase() + type.slice(1);
            
            generateFormFields(type);
            
            // Populate common fields
            document.getElementById('field-year').value = data.year;
            document.getElementById('field-month').value = data.month;
            
            // Populate type-specific fields
            switch(type) {
                case 'animal':
                    document.getElementById('field-animal_type').value = data.animal_type;
                    document.getElementById('field-quantity').value = data.quantity;
                    document.getElementById('field-notes').value = data.notes || '';
                    break;
                case 'machinery':
                    document.getElementById('field-machinery_name').value = data.machinery_name;
                    document.getElementById('field-quantity').value = data.quantity;
                    document.getElementById('field-status').value = data.status;
                    document.getElementById('field-notes').value = data.notes || '';
                    break;
                case 'tree':
                    document.getElementById('field-tree_type').value = data.tree_type;
                    document.getElementById('field-quantity').value = data.quantity;
                    document.getElementById('field-notes').value = data.notes || '';
                    break;
                case 'project':
                    document.getElementById('field-project_name').value = data.project_name;
                    document.getElementById('field-status').value = data.status;
                    document.getElementById('field-start_date').value = data.start_date || '';
                    document.getElementById('field-end_date').value = data.end_date || '';
                    document.getElementById('field-budget').value = data.budget || '';
                    document.getElementById('field-description').value = data.description || '';
                    break;
            }
            
            document.getElementById('crud-modal').classList.add('active');
        }

        // Delete function
        function deleteRecord(type, id) {
            if (!confirm('Are you sure you want to delete this record?')) {
                return;
            }
            
            fetch(`api/${type}s.php?action=delete&id=${id}`, { method: 'GET' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        // Reload the appropriate list
                        switch(type) {
                            case 'animal': loadAnimals(); break;
                            case 'machinery': loadMachineries(); break;
                            case 'tree': loadTrees(); break;
                            case 'project': loadProjects(); break;
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                });
        }

        // Utility functions
        function getMonthName(month) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return months[month - 1];
        }

        function getStatusColor(status) {
            switch(status) {
                case 'Working': return 'bg-green-100 text-green-800';
                case 'Needs Repair': return 'bg-yellow-100 text-yellow-800';
                case 'Broken': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        function getProjectStatusColor(status) {
            switch(status) {
                case 'Completed': return 'bg-green-100 text-green-800';
                case 'In Progress': return 'bg-blue-100 text-blue-800';
                case 'Almost Done': return 'bg-yellow-100 text-yellow-800';
                case 'Pending': return 'bg-gray-100 text-gray-800';
                case 'Needs Repair': return 'bg-red-100 text-red-800';
                default: return 'bg-gray-100 text-gray-800';
            }
        }

        // Load initial data
        document.addEventListener('DOMContentLoaded', function() {
            loadAnimals();
        });
    </script>

</body>
</html>