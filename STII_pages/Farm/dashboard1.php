<?php 
require_once '../electric/config/check-session.php';
require_once '../electric/config/conn.php';
require_once '../electric/config/conn_pdo.php';

$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total_documents FROM documents WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$documentCount = $stmt->fetch();
$totalDocuments = $documentCount['total_documents'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farm/Agri Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-inprogress { background-color: #dbeafe; color: #1e40af; }
        .status-planning { background-color: #f3e8ff; color: #6b21a8; }
        .status-onhold { background-color: #fef3c7; color: #92400e; }
        .status-needrepair { background-color: #fee2e2; color: #b91c1c; }
        .status-operational { background-color: #dcfce7; color: #166534; }
        .animal-image { width: 100%; height: 150px; object-fit: cover; }
        .animal-card { transition: all 0.3s ease; }
        .animal-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
 <?php include 'bar/sidebar.php'; ?>
<div class="flex-1 overflow-auto w-full">
     <?php include 'bar/header.php'; ?>
    <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <button onclick="window.location.href='../portal.php';" class="text-gray-600 hover:text-gray-900 p-2 rounded-lg bg-gray-100">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <h1 class="text-xl font-bold text-gray-800">
                <i class="fas fa-tractor text-lime-600 mr-2"></i>
                Farm/Agri Dashboard
            </h1>
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 hidden sm:block">Filter By</label>
                <select id="filterType" class="text-sm border rounded-lg px-2 py-1 bg-white" onchange="toggleFilterMode()">
                    <option value="month">Month</option>
                    <option value="year">Year Only</option>
                </select>
                
                <div id="monthFilterContainer" class="flex items-center gap-2">
                    <select id="monthSelect" class="text-sm border rounded-lg px-2 py-1 bg-white">
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
                
                <select id="yearSelect" class="text-sm border rounded-lg px-2 py-1 bg-white">
                    <option value="2023">2023</option>
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
        </div>

        <div class="space-y-6">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <!-- Total Animals -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Animals <span id="asOfAnimals" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiTotalAnimals" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="animalBreakdown" class="text-green-600 text-xs font-medium mt-2">Loading...</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-paw text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Infrastructure Projects -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Projects <span id="asOfInfra" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiInfraProjects" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="infraBreakdown" class="text-amber-600 text-xs font-medium mt-2">Loading...</p>
                        </div>
                        <div class="bg-amber-100 p-3 rounded-full">
                            <i class="fas fa-building text-amber-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Machineries -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Machineries <span id="asOfMachinery" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiMachineries" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="machineryBreakdown" class="text-purple-600 text-xs font-medium mt-2">Loading...</p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-cogs text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Trees -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Trees <span id="asOfTrees" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiTrees" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="treeBreakdown" class="text-green-600 text-xs font-medium mt-2">Loading...</p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-tree text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Documents -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Documents</p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1"><?php echo number_format($totalDocuments); ?></h3>
                            <p class="text-blue-600 text-xs font-medium mt-2">
                                <a href="farm_document.php" class="hover:underline">View Documents →</a>
                            </p>
                        </div>
                        <div class="bg-blue-100 p-3 rounded-full">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Animal Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Animal Population</h3>
                        <span id="asOfChartAnimals" class="text-xs text-gray-500"></span>
                    </div>
                    <div class="h-80">
                        <canvas id="animalChart"></canvas>
                    </div>
                </div>

                <!-- Infrastructure Project Completion Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Project Status</h3>
                        <span id="asOfChartInfra" class="text-xs text-gray-500"></span>
                    </div>
                    <div class="h-80">
                        <canvas id="infrastructureChart"></canvas>
                    </div>
                </div>

                <!-- Tree Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Tree Distribution</h3>
                        <span id="asOfChartTrees" class="text-xs text-gray-500"></span>
                    </div>
                    <div class="h-80">
                        <canvas id="treeChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Infrastructure Projects (Dynamic from Database) -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                    <i class="fas fa-building mr-2 text-amber-600"></i>
                    Infrastructure & Equipment <span id="asOfInfraTable" class="text-xs text-gray-500"></span>
                </h3>
                
                <!-- Category Tabs -->
                <div class="flex gap-2 mb-4 border-b overflow-x-auto">
                    <button onclick="showCategory('infrastructure')" class="category-tab active px-4 py-2 font-medium text-sm border-b-2 border-amber-600 text-amber-600 whitespace-nowrap">
                        Infrastructure (<span id="countInfra">0</span>)
                    </button>
                    <button onclick="showCategory('machineries')" class="category-tab px-4 py-2 font-medium text-sm text-gray-600 hover:text-amber-600 whitespace-nowrap">
                        Machineries (<span id="countMachinery">0</span>)
                    </button>
                    <button onclick="showCategory('4wheel')" class="category-tab px-4 py-2 font-medium text-sm text-gray-600 hover:text-amber-600 whitespace-nowrap">
                        4 Wheel Tractor (<span id="count4Wheel">0</span>)
                    </button>
                    <button onclick="showCategory('feed')" class="category-tab px-4 py-2 font-medium text-sm text-gray-600 hover:text-amber-600 whitespace-nowrap">
                        Feed Production (<span id="countFeed">0</span>)
                    </button>
                </div>

                <!-- Infrastructure Category -->
                <div id="category-infrastructure" class="category-content">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Project</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Progress</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Details</th>
                                </tr>
                            </thead>
                            <tbody id="infraTableBody" class="divide-y">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Machineries Category -->
                <div id="category-machineries" class="category-content hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Machinery</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Progress</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Details</th>
                                </tr>
                            </thead>
                            <tbody id="machineryTableBody" class="divide-y">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4 Wheel Tractor Category -->
                <div id="category-4wheel" class="category-content hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Equipment</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Progress</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Details</th>
                                </tr>
                            </thead>
                            <tbody id="4wheelTableBody" class="divide-y">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Feed Production Category -->
                <div id="category-feed" class="category-content hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Facility</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Progress</th>
                                    <th class="px-4 py-3 text-left font-medium text-gray-600">Details</th>
                                </tr>
                            </thead>
                            <tbody id="feedTableBody" class="divide-y">
                                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-500"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Animal Inventory -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-paw text-lime-600 mr-2"></i>
                        Animal Inventory <span id="asOfAnimalInventory" class="text-xs text-gray-500"></span>
                    </h3>
                    <span id="animalTypesCount" class="bg-lime-100 text-lime-800 text-xs font-medium px-2.5 py-0.5 rounded-full">Loading...</span>
                </div>
                <div id="animalInventoryGrid" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <div class="col-span-full text-center py-8">
                        <i class="fas fa-spinner fa-spin text-3xl text-gray-400 mb-2"></i>
                        <p class="text-gray-500">Loading animals...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
Chart.register(ChartDataLabels);

let animalChart, treeChart, infrastructureChart;
let allAnimals = [];
let allTrees = [];
let allInfrastructure = {};

const MONTH_NAMES = ["January","February","March","April","May","June","July","August","September","October","November","December"];

const now = new Date();
document.getElementById('monthSelect').value = now.getMonth() + 1;
document.getElementById('yearSelect').value = now.getFullYear();

document.addEventListener('DOMContentLoaded', () => {
    loadDashboardData();
    document.getElementById('monthSelect').addEventListener('change', loadDashboardData);
    document.getElementById('yearSelect').addEventListener('change', loadDashboardData);
    document.getElementById('filterType').addEventListener('change', loadDashboardData);
});

function toggleFilterMode() {
    const filterType = document.getElementById('filterType').value;
    const monthContainer = document.getElementById('monthFilterContainer');
    
    if (filterType === 'year') {
        monthContainer.style.display = 'none';
    } else {
        monthContainer.style.display = 'flex';
    }
    loadDashboardData();
}

function showCategory(category) {
    document.querySelectorAll('.category-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.category-tab').forEach(el => {
        el.classList.remove('active', 'border-amber-600', 'text-amber-600');
        el.classList.add('text-gray-600');
    });
    
    document.getElementById('category-' + category).classList.remove('hidden');
    event.target.classList.add('active', 'border-amber-600', 'text-amber-600');
    event.target.classList.remove('text-gray-600');
}

async function loadDashboardData() {
    const filterType = document.getElementById('filterType').value;
    const year = parseInt(document.getElementById('yearSelect').value);
    const month = filterType === 'month' ? parseInt(document.getElementById('monthSelect').value) : null;
    
    await Promise.all([
        loadAnimals(year, month),
        loadTrees(year, month),
        loadInfrastructure(year, month, filterType),
        loadInfrastructureStats(year, month, filterType)
    ]);
    
    updateDateStamps(year, month);
}

function updateDateStamps(year, month) {
    const filterType = document.getElementById('filterType').value;
    let stamp;
    
    if (filterType === 'year') {
        stamp = `(Year ${year} Total)`;
    } else {
        stamp = `(as of ${MONTH_NAMES[month-1]} ${year})`;
    }
    
    document.getElementById('asOfAnimals').textContent = stamp;
    document.getElementById('asOfTrees').textContent = stamp;
    document.getElementById('asOfInfra').textContent = stamp;
    document.getElementById('asOfMachinery').textContent = stamp;
    document.getElementById('asOfChartAnimals').textContent = stamp;
    document.getElementById('asOfChartTrees').textContent = stamp;
    document.getElementById('asOfChartInfra').textContent = stamp;
    document.getElementById('asOfAnimalInventory').textContent = stamp;
    document.getElementById('asOfInfraTable').textContent = stamp;
}

async function loadAnimals(year, month) {
    console.log('[loadAnimals] Function called', { year, month });

    try {
        let url = `api/dashboard_api.php?action=animals&year=${year}`;
        if (month !== null) {
            url += `&month=${month}`;
        }

        console.log('[loadAnimals] Fetching URL:', url);

        const response = await fetch(url);

        // If the PHP file is not reached or returns an error status
        if (!response.ok) {
            console.error(
                '[loadAnimals] API not reached or HTTP error',
                response.status,
                response.statusText
            );
            showError('animals');
            return;
        }

        console.log('[loadAnimals] Response received');

        const data = await response.json();
        console.log('[loadAnimals] Response data:', data);

        if (data.success) {
            allAnimals = month === null
                ? aggregateByType(data.data)
                : data.data;

            updateAnimalStats();
            updateAnimalChart();
            updateAnimalInventory();
        } else {
            console.error('[loadAnimals] API responded but failed:', data.message);
            showError('animals');
        }
    } catch (error) {
        // This usually means the PHP file was never reached (network/CORS/404)
        console.error('[loadAnimals] Fetch failed – file not reached:', error);
        showError('animals');
    }
}


async function loadTrees(year, month) {
    try {
        let url = `api/dashboard_api.php?action=trees&year=${year}`;
        if (month !== null) {
            url += `&month=${month}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allTrees = month === null ? aggregateByType(data.data, 'tree_type') : data.data;
            updateTreeStats();
            updateTreeChart();
        } else {
            console.error('Failed to load trees:', data.message);
            showError('trees');
        }
    } catch (error) {
        console.error('Error loading trees:', error);
        showError('trees');
    }
}

async function loadInfrastructure(year, month, filterType) {
    try {
        let url = `api/dashboard_api.php?action=infrastructure&year=${year}&filter_type=${filterType}`;
        if (month !== null && filterType === 'month') {
            url += `&month=${month}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            allInfrastructure = data.data;
            updateInfrastructureStats(data.counts);
            renderInfrastructureTables();
        } else {
            console.error('Failed to load infrastructure:', data.message);
            showError('infrastructure');
        }
    } catch (error) {
        console.error('Error loading infrastructure:', error);
        showError('infrastructure');
    }
}

async function loadInfrastructureStats(year, month, filterType) {
    try {
        let url = `api/dashboard_api.php?action=infrastructure_stats&year=${year}&filter_type=${filterType}`;
        if (month !== null && filterType === 'month') {
            url += `&month=${month}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            updateInfrastructureChart(data.status_distribution);
        } else {
            console.error('Failed to load infrastructure stats:', data.message);
        }
    } catch (error) {
        console.error('Error loading infrastructure stats:', error);
    }
}

function aggregateByType(data, typeField = 'animal_type') {
    const aggregated = {};
    
    data.forEach(item => {
        const type = item[typeField];
        if (!aggregated[type]) {
            aggregated[type] = {
                ...item,
                quantity: 0
            };
        }
        aggregated[type].quantity += parseInt(item.quantity);
    });
    
    return Object.values(aggregated).sort((a, b) => b.quantity - a.quantity);
}

function showError(type) {
    if (type === 'animals') {
        document.getElementById('kpiTotalAnimals').textContent = '0';
        document.getElementById('animalBreakdown').textContent = 'No data available';
        document.getElementById('animalInventoryGrid').innerHTML = '<div class="col-span-full text-center py-8"><p class="text-gray-500">No animals recorded</p></div>';
    } else if (type === 'trees') {
        document.getElementById('kpiTrees').textContent = '0';
        document.getElementById('treeBreakdown').textContent = 'No data available';
    } else if (type === 'infrastructure') {
        document.getElementById('kpiInfraProjects').textContent = '0';
        document.getElementById('infraBreakdown').textContent = 'No data available';
    }
}

function updateAnimalStats() {
    const totalAnimals = allAnimals.reduce((sum, animal) => sum + parseInt(animal.quantity), 0);
    document.getElementById('kpiTotalAnimals').textContent = totalAnimals.toLocaleString();
    
    const breakdown = allAnimals.slice(0, 3).map(a => `${a.animal_type}: ${a.quantity}`).join(', ');
    document.getElementById('animalBreakdown').textContent = breakdown || 'No animals recorded';
}

function updateTreeStats() {
    const totalTrees = allTrees.reduce((sum, tree) => sum + parseInt(tree.quantity), 0);
    document.getElementById('kpiTrees').textContent = totalTrees.toLocaleString();
    
    const breakdown = allTrees.slice(0, 2).map(t => `${t.tree_type}: ${t.quantity}`).join(', ');
    document.getElementById('treeBreakdown').textContent = breakdown || 'No trees recorded';
}

function updateInfrastructureStats(counts) {
    // Update KPI cards
    document.getElementById('kpiInfraProjects').textContent = counts.infrastructure;
    document.getElementById('kpiMachineries').textContent = counts.machineries;
    
    // Update tab counts
    document.getElementById('countInfra').textContent = counts.infrastructure;
    document.getElementById('countMachinery').textContent = counts.machineries;
    document.getElementById('count4Wheel').textContent = counts['4wheel'];
    document.getElementById('countFeed').textContent = counts.feed;
    
    // Update breakdown text
    const completed = allInfrastructure['Infrastructure Project']?.filter(i => i.status === 'Completed').length || 0;
    document.getElementById('infraBreakdown').textContent = `${completed} completed projects`;
    
    const operational = allInfrastructure['Machineries']?.filter(m => m.status === 'Operational').length || 0;
    document.getElementById('machineryBreakdown').textContent = `${operational} operational`;
}

function getStatusClass(status) {
    const statusMap = {
        'Completed': 'status-completed',
        'In Progress': 'status-inprogress',
        'Planning': 'status-planning',
        'On Hold': 'status-onhold',
        'Need Repair': 'status-needrepair',
        'Operational': 'status-operational'
    };
    return statusMap[status] || 'status-inprogress';
}

function renderInfrastructureTables() {
    // Infrastructure Projects
    const infraBody = document.getElementById('infraTableBody');
    const infraData = allInfrastructure['Infrastructure Project'] || [];
    
    if (infraData.length === 0) {
        infraBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No infrastructure projects found</td></tr>';
    } else {
        infraBody.innerHTML = infraData.map(item => `
            <tr>
                <td class="px-4 py-3">${item.infrastructure_name}</td>
                <td class="px-4 py-3">
                    <span class="${getStatusClass(item.status)} px-2 py-1 rounded text-xs">${item.status}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-blue-500 h-2 rounded-full" style="width: ${item.progress_percent}%"></div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">${item.progress_percent}% ${item.details ? '- ' + item.details : ''}</td>
            </tr>
        `).join('');
    }
    
    // Machineries
    const machineryBody = document.getElementById('machineryTableBody');
    const machineryData = allInfrastructure['Machineries'] || [];
    
    if (machineryData.length === 0) {
        machineryBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No machineries found</td></tr>';
    } else {
        machineryBody.innerHTML = machineryData.map(item => `
            <tr>
                <td class="px-4 py-3">${item.infrastructure_name}</td>
                <td class="px-4 py-3">
                    <span class="${getStatusClass(item.status)} px-2 py-1 rounded text-xs">${item.status}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: ${item.progress_percent}%"></div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">${item.progress_percent}%</td>
            </tr>
        `).join('');
    }
    
    // 4 Wheel Tractor
    const wheelBody = document.getElementById('4wheelTableBody');
    const wheelData = allInfrastructure['4 Wheel Tractor'] || [];
    
    if (wheelData.length === 0) {
        wheelBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No 4 wheel tractors found</td></tr>';
    } else {
        wheelBody.innerHTML = wheelData.map(item => `
            <tr>
                <td class="px-4 py-3">${item.infrastructure_name}</td>
                <td class="px-4 py-3">
                    <span class="${getStatusClass(item.status)} px-2 py-1 rounded text-xs">${item.status}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: ${item.progress_percent}%"></div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">${item.progress_percent}%</td>
            </tr>
        `).join('');
    }
    
    // Feed Production
    const feedBody = document.getElementById('feedTableBody');
    const feedData = allInfrastructure['Feed Production Facilities'] || [];
    
    if (feedData.length === 0) {
        feedBody.innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-500">No feed production facilities found</td></tr>';
    } else {
        feedBody.innerHTML = feedData.map(item => `
            <tr>
                <td class="px-4 py-3">${item.infrastructure_name}</td>
                <td class="px-4 py-3">
                    <span class="${getStatusClass(item.status)} px-2 py-1 rounded text-xs">${item.status}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="w-32 bg-gray-200 rounded-full h-2">
                        <div class="bg-green-500 h-2 rounded-full" style="width: ${item.progress_percent}%"></div>
                    </div>
                </td>
                <td class="px-4 py-3 text-gray-500">${item.progress_percent}%</td>
            </tr>
        `).join('');
    }
}

function updateAnimalChart() {
    const ctx = document.getElementById('animalChart').getContext('2d');
    
    const labels = allAnimals.map(a => a.animal_type);
    const quantities = allAnimals.map(a => parseInt(a.quantity));
    
    const colors = ['rgba(255, 159, 64, 0.7)','rgba(54, 162, 235, 0.7)','rgba(255, 205, 86, 0.7)','rgba(75, 192, 192, 0.7)','rgba(153, 102, 255, 0.7)','rgba(201, 203, 207, 0.7)','rgba(255, 99, 132, 0.7)','rgba(101, 163, 13, 0.7)','rgba(120, 113, 108, 0.7)','rgba(22, 163, 74, 0.7)'];
    
    if (animalChart) animalChart.destroy();
    
    animalChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Population',
                data: quantities,
                backgroundColor: colors,
                borderColor: colors.map(c => c.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { beginAtZero: true },
                y: { grid: { display: false } }
            },
            plugins: { 
                legend: { display: false },
                datalabels: { display: false }
            }
        }
    });
}

function updateInfrastructureChart(statusData) {
    const ctx = document.getElementById('infrastructureChart').getContext('2d');
    
    const labels = statusData.map(s => s.status);
    const counts = statusData.map(s => parseInt(s.count));
    
    const colorMap = {
        'Completed': 'rgba(34, 197, 94, 0.7)',
        'In Progress': 'rgba(59, 130, 246, 0.7)',
        'Planning': 'rgba(168, 85, 247, 0.7)',
        'On Hold': 'rgba(234, 179, 8, 0.7)',
        'Need Repair': 'rgba(239, 68, 68, 0.7)'
    };
    
    const colors = labels.map(label => colorMap[label] || 'rgba(156, 163, 175, 0.7)');
    
    if (infrastructureChart) infrastructureChart.destroy();
    
    infrastructureChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: counts,
                backgroundColor: colors,
                borderColor: colors.map(c => c.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
                        return total === 0 ? '0' : value;
                    },
                    font: { weight: 'bold', size: 12 }
                }
            }
        }
    });
}

function updateTreeChart() {
    const ctx = document.getElementById('treeChart').getContext('2d');
    
    const labels = allTrees.map(t => t.tree_type);
    const quantities = allTrees.map(t => parseInt(t.quantity));
    
    const colors = ['rgba(34, 197, 94, 0.7)','rgba(59, 130, 246, 0.7)','rgba(234, 179, 8, 0.7)','rgba(168, 85, 247, 0.7)','rgba(236, 72, 153, 0.7)','rgba(14, 165, 233, 0.7)'];
    
    if (treeChart) treeChart.destroy();
    
    treeChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: quantities,
                backgroundColor: colors,
                borderColor: colors.map(c => c.replace('0.7', '1')),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' },
                datalabels: {
                    color: '#fff',
                    formatter: (value, ctx) => {
                        const total = ctx.dataset.data.reduce((s, v) => s + v, 0);
                        return total === 0 ? '0%' : `${((value / total) * 100).toFixed(1)}%`;
                    },
                    font: { weight: 'bold', size: 12 }
                }
            }
        }
    });
}

function updateAnimalInventory() {
    const grid = document.getElementById('animalInventoryGrid');
    document.getElementById('animalTypesCount').textContent = `${allAnimals.length} types`;
    
    if (allAnimals.length === 0) {
        grid.innerHTML = '<div class="col-span-full text-center py-8"><i class="fas fa-inbox text-3xl text-gray-400 mb-2"></i><p class="text-gray-500">No animals recorded</p></div>';
        return;
    }
    
    grid.innerHTML = allAnimals.map(animal => {
        const photoUrl = animal.photo ? `api/uploads/${animal.photo}` : 'https://images.unsplash.com/photo-1560493676-04071c5f467b?w=400&h=300&fit=crop';
        
        return `
            <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                <img src="${photoUrl}" alt="${animal.animal_type}" class="animal-image" onerror="this.src='https://images.unsplash.com/photo-1560493676-04071c5f467b?w=400&h=300&fit=crop'">
                <div class="p-4">
                    <h4 class="text-lg font-semibold text-gray-800 mb-1">${animal.animal_type}</h4>
                    <div class="flex items-center justify-between">
                        <span class="text-2xl font-bold text-lime-600">${animal.quantity}</span>
                        <div class="bg-lime-100 p-2 rounded-full">
                            <i class="fas fa-paw text-lime-600"></i>
                        </div>
                    </div>
                    ${animal.notes ? `<p class="text-xs text-gray-500 mt-2">${animal.notes}</p>` : ''}
                </div>
            </div>
        `;
    }).join('');
}
</script>

</body>
</html>