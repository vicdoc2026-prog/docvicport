<?php 

// Include session check - this will redirect if not logged in
require_once '../electric/config/check-session.php';
require_once '../electric/config/conn.php';
require_once '../electric/config/conn_pdo.php';

// Get user info from session
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
    <title>Farm/Agri Dashboard | Essentiel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); }
        .horizontal-bar-chart { max-height: 300px; }
        .status-completed { background-color: #dcfce7; color: #166534; }
        .status-inprogress { background-color: #dbeafe; color: #1e40af; }
        .status-almost { background-color: #fef3c7; color: #92400e; }
        .status-needsrepair { background-color: #fee2e2; color: #b91c1c; }
        .status-pending { background-color: #e5e7eb; color: #374151; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">

<!-- Main content area -->
<div class="flex-1 overflow-auto w-full">
    <div class="p-6">
        <!-- Mobile header -->
        <div class="flex items-center justify-between mb-6">
            <button onclick="window.location.href='../portal.php';" 
                    class="text-gray-600 hover:text-gray-900 p-2 rounded-lg bg-gray-100">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <h1 class="text-xl font-bold text-gray-800">
                <i class="fas fa-tractor text-lime-600 mr-2"></i>
                Farm/Agri Dashboard
            </h1>
            <!-- Month/Year selector that shows month as a word -->
            <div class="flex items-center gap-2">
                <label class="text-sm text-gray-600 hidden sm:block">Month</label>
                <select id="monthName" class="text-sm border rounded-lg px-2 py-1 bg-white focus:ring-lime-500 focus:border-lime-500">
                    <option value="01">January</option>
                    <option value="02">February</option>
                    <option value="03">March</option>
                    <option value="04">April</option>
                    <option value="05">May</option>
                    <option value="06">June</option>
                    <option value="07">July</option>
                    <option value="08">August</option>
                    <option value="09">September</option>
                    <option value="10">October</option>
                    <option value="11">November</option>
                    <option value="12">December</option>
                </select>
                <select id="yearSelect" class="text-sm border rounded-lg px-2 py-1 bg-white focus:ring-lime-500 focus:border-lime-500">
                    <option value="2025">2025</option>
                </select>
            </div>
        </div>

        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
                <!-- Total Animals -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Total Animals <span id="asOfAnimals" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiTotalAnimals" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="animalBreak1" class="text-green-600 text-xs font-medium mt-2"></p>
                            <p id="animalBreak2" class="text-green-600 text-xs font-medium mt-1"></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-paw text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Machineries Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Machineries <span id="asOfMach" class="text-xs text-gray-400"></span></p>
                            <h3 class="text-2xl font-bold text-gray-800 mt-1">10</h3>
                            <p class="text-green-600 text-xs font-medium mt-2">
                                Hand Tractor: 1, Rotary Composter: 1
                            </p>
                            <p class="text-green-600 text-xs font-medium mt-1">
                                Corn Sealer: 1, Pelletizer: 1, Strauder: 1
                            </p>
                        </div>
                        <div class="bg-purple-100 p-3 rounded-full">
                            <i class="fas fa-cogs text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Projects Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Active Projects <span id="asOfProj" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiActiveProjects" class="text-2xl font-bold text-gray-800 mt-1">5</h3>
                            <p class="text-amber-600 text-xs font-medium mt-2">
                                <i class="fas fa-tasks mr-1"></i> View projects
                            </p>
                        </div>
                        <div class="bg-amber-100 p-3 rounded-full">
                            <i class="fas fa-tasks text-amber-600 text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Trees Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-500 text-sm font-medium">Trees <span id="asOfTrees" class="text-xs text-gray-400"></span></p>
                            <h3 id="kpiTrees" class="text-2xl font-bold text-gray-800 mt-1">—</h3>
                            <p id="treeBreak" class="text-green-600 text-xs font-medium mt-2"></p>
                        </div>
                        <div class="bg-green-100 p-3 rounded-full">
                            <i class="fas fa-tree text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Documents Card -->
                <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-500 text-sm font-medium">
                Documents 
                <span class="text-xs text-gray-400">(as of <?php echo date('M d, Y'); ?>)</span>
            </p>
            <h3 class="text-2xl font-bold text-gray-800 mt-1">
                <?php echo number_format($totalDocuments); ?>
            </h3>
            <p class="text-blue-600 text-xs font-medium mt-2">
                <i class="fas fa-file-alt mr-1"></i> 
                <?php echo $totalDocuments === 1 ? 'PDF document stored' : 'PDF documents stored'; ?>
            </p>
            <a href="farm_document.php" class="text-blue-500 text-sm font-medium mt-2 inline-flex items-center hover:text-blue-600 transition" style="margin-top: 70px;">
                View All Documents <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="bg-blue-100 p-3 rounded-full">
            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
        </div>
    </div>
</div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Animal Population Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Animal Population</h3>
                        <span id="asOfChartAnimals" class="text-xs text-gray-500"></span>
                    </div>
                    <div class="h-80">
                        <canvas id="animalChart"></canvas>
                    </div>
                </div>

                <!-- Project Status Chart -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Project Status</h3>
                        <span id="asOfChartProj" class="text-xs text-gray-500"></span>
                    </div>
                    <div class="h-80">
                        <canvas id="projectStatusChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Projects Status -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Infrastructure Projects Status</h3>
                    <div class="flex items-center gap-2">
                        <span id="asOfTableProjects" class="text-xs text-gray-500"></span>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">31 items</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">%</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <!-- (your rows unchanged) -->
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-shield-alt text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Guard House</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-inprogress">In Progress</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 65%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">65%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">On going operation</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-tint text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Refilling Station</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <!-- (remaining rows unchanged from your original) -->
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-door-open text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Main Gate</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-home text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Kubo</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">6 units, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-dumbbell text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">STII Fits Center</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-tshirt text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Laundry Shop</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-seedling text-yellow-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">New Green House</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-almost">Mixed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 80%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">80%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 units: 1 on going operation, 1 almost to operate</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-seedling text-yellow-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Old Green House</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-almost">Mixed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 85%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">85%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">3 units: 2 on going operation, 1 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-building text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">HRM Building</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-clipboard-check text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Assessment Center</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-chalkboard-teacher text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Training Center</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">4 rooms, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-bed text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Old Dormitory Building</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-restroom text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">CR</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-box text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Stock Room</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-users text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Faculty Room</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Total Room: 1 unit, Convention Hall: 1 unit, Tesda Office: 1 unit</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-door-closed text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Total Room</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-microphone text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Convention Hall</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-briefcase text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Tesda Office</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-home text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Royal Raya Hotel</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-inprogress">In Progress</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 40%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">40%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">On going construction</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-egg text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Poultry</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Stock Room: 1 unit, Tool Room: 1 unit, Storage: 1 unit</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-box text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Stock room</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-tools text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Tool Room</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-warehouse text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Storage</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-kiwi-bird text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Native Poultry</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 units, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-yellow-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-egg text-yellow-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Brooding Facility</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-almost">Needs Repair</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-yellow-500 h-2 rounded-full" style="width: 30%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">30%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Subject to repair</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-arrow-up text-blue-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Growing Facility</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-inprogress">In Progress</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 65%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">65%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">On growing repair</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-layer-group text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">New Layer Building</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-piggy-bank text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Piggery Building</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items center justify-center">
                                            <i class="fas fa-bed text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">New Dormitory</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items center justify-center">
                                            <i class="fas fa-graduation-cap text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Agri Scholar Quarter</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10 bg-green-100 rounded-full flex items center justify-center">
                                            <i class="fas fa-paw text-green-600"></i>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Ruminants Building</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Completed</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-green-600 h-2 rounded-full" style="width: 100%"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">100%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 unit, 100%</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- New Machineries Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Machineries Table -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Machineries</h3>
                        <span class="bg-purple-100 text-purple-800 text-xs font-medium px-2.5 py-0.5 rounded-full">10 items</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Machinery</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Hand Tractor</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rotary Composter</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rice/ Corn Mill</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Corn Sealer</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Pelletizer</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Strauder</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Shredder</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Pulverizer</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Litsunan w/ litsunan stick</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Old Incubator</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- 4 Wheel Tractor Table -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">4 Wheel Tractor</h3>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">2 items</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipment</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rovator</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Displate</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Feed Production Facilities Table -->
                <div class="bg-white rounded-xl shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Feed Production Facilities</h3>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">3 items</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Corn Hummer and Pulverizer</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mixer w/ Electric Motor</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Pulverizer</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-almost">Needs Repair</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mixer w/ Electric Motor</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                                <tr><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mixer w/ Electric Motor</td><td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1</td><td class="px-6 py-4 whitespace-nowrap"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full status-completed">Operational</span></td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div> <!-- end grid -->
        </div>
    </div>


                <!-- Animal Inventory Section -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-paw text-lime-600 mr-2"></i>
                        Animal <span id="asOfAnimalInventory" class="text-xs text-gray-500"></span>
                    </h3>
                    <span class="bg-lime-100 text-lime-800 text-xs font-medium px-2.5 py-0.5 rounded-full">10 species</span>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
                    <!-- Layer Chicken -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="https://images.unsplash.com/photo-1548550023-2bdb3c5beed7?w=400&h=300&fit=crop" alt="Layer Chicken" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Layer</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-lime-600" id="animalLayer">78</span>
                                <div class="bg-orange-100 p-2 rounded-full">
                                    <i class="fas fa-egg text-orange-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Duck -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/duck.jpg" alt="Duck" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Duck</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-blue-600" id="animalDuck">69</span>
                                <div class="bg-blue-100 p-2 rounded-full">
                                    <i class="fas fa-water text-blue-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chicken -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="https://images.unsplash.com/photo-1612170153139-6f881ff067e0?w=400&h=300&fit=crop" alt="Chicken" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Chicken</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-yellow-600" id="animalChicken">32</span>
                                <div class="bg-yellow-100 p-2 rounded-full">
                                    <i class="fas fa-drumstick-bite text-yellow-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Turkey -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="https://images.unsplash.com/photo-1574629810360-7efbbe195018?w=400&h=300&fit=crop" alt="Turkey" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Turkey</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-red-600" id="animalTurkey">12</span>
                                <div class="bg-red-100 p-2 rounded-full">
                                    <i class="fas fa-feather text-red-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pigs -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="https://images.unsplash.com/photo-1516467508483-a7212febe31a?w=400&h=300&fit=crop" alt="Pigs" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Pigs</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-pink-600" id="animalPigs">12</span>
                                <div class="bg-pink-100 p-2 rounded-full">
                                    <i class="fas fa-piggy-bank text-pink-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cattle -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="https://images.unsplash.com/photo-1560493676-04071c5f467b?w=400&h=300&fit=crop" alt="Cattle" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Cattle</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-amber-700" id="animalCattle">9</span>
                                <div class="bg-amber-100 p-2 rounded-full">
                                    <i class="fas fa-cow text-amber-700"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Cornero -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/sheep.jpg" alt="Water Buffalo" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Cornero</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-slate-700" id="animalCornero">9</span>
                                <div class="bg-slate-200 p-2 rounded-full">
                                    <i class="fas fa-horse text-slate-700"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goat -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/goat.jpg" alt="Goat" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Goat</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-teal-600" id="animalGoat">9</span>
                                <div class="bg-teal-100 p-2 rounded-full">
                                    <i class="fas fa-horse-head text-teal-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Horse -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/horse.jpg" alt="Horse" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Horse</h4>
                          
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-indigo-600" id="animalHorse">3</span>
                                <div class="bg-indigo-100 p-2 rounded-full">
                                    <i class="fas fa-horse text-indigo-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Goose -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/goose.jpg" alt="Goose" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Goose</h4>
                          
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-gray-600" id="animalGoose">2</span>
                                <div class="bg-gray-200 p-2 rounded-full">
                                    <i class="fas fa-kiwi-bird text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                                        <!-- Cow -->
                    <div class="bg-white rounded-xl shadow-md animal-card overflow-hidden border border-gray-200">
                        <img src="image/cow.jpg" alt="Cow" class="animal-image">
                        <div class="p-4">
                            <h4 class="text-lg font-semibold text-gray-800 mb-1">Cow</h4>
                            <div class="flex items-center justify-between">
                                <span class="text-2xl font-bold text-gray-600" id="animalCow">2</span>
                                <div class="bg-gray-200 p-2 rounded-full">
                                    <i class="fas fa-cow text-gray-600"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
</div>





<script>
/* ========= COMPLETE MONTHLY DATA (2025) =========
   Key: 'YYYY-MM' (internal), but UI shows "MonthName YYYY"
*/
const MONTHLY_DATA = {
  "2025-01": { animals:{Layer:72,Duck:60,Chicken:28,Turkey:10,Pigs:10,Cattle:8,Cornero:8,Goat:8,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1080,coconut:130}, activeProjects:25 },
  "2025-02": { animals:{Layer:74,Duck:62,Chicken:29,Turkey:10,Pigs:11,Cattle:8,Cornero:8,Goat:8,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1085,coconut:132}, activeProjects:25 },
  "2025-03": { animals:{Layer:75,Duck:63,Chicken:30,Turkey:11,Pigs:11,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1090,coconut:135}, activeProjects:25 },
  "2025-04": { animals:{Layer:76,Duck:65,Chicken:30,Turkey:11,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1100,coconut:136}, activeProjects:25 },
  "2025-05": { animals:{Layer:76,Duck:66,Chicken:31,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1105,coconut:138}, activeProjects:25 },
  "2025-06": { animals:{Layer:77,Duck:67,Chicken:31,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1110,coconut:139}, activeProjects:25 },
  "2025-07": { animals:{Layer:77,Duck:67,Chicken:31,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1120,coconut:140}, activeProjects:25 },
  "2025-08": { animals:{Layer:77,Duck:68,Chicken:31,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1125,coconut:140}, activeProjects:25 },
  "2025-09": { animals:{Layer:77,Duck:68,Chicken:31,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1100,coconut:140}, activeProjects:25 },
  "2025-10": { animals:{Layer:78,Duck:69,Chicken:32,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1130,coconut:142}, activeProjects:25 },
  "2025-11": { animals:{Layer:78,Duck:70,Chicken:32,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1135,coconut:143}, activeProjects:25 },
  "2025-12": { animals:{Layer:79,Duck:71,Chicken:33,Turkey:12,Pigs:12,Cattle:9,Cornero:9,Goat:9,Horse:3,Gansa:2,Cow:2}, projects:{completed:6,inProgress:2,pending:0}, trees:{rubber:1140,coconut:145}, activeProjects:25 }
};

const MONTH_NAMES = ["January","February","March","April","May","June","July","August","September","October","November","December"];
function toMonthWord(key){ // 'YYYY-MM' -> 'Month YYYY'
  const [y,m] = key.split("-");
  const idx = parseInt(m,10)-1;
  return `${MONTH_NAMES[idx]} ${y}`;
}
function keyFrom(y, m2){ return `${y}-${m2}`; }
function sum(obj){ return Object.values(obj).reduce((a,b)=>a+b,0); }

Chart.register(ChartDataLabels);

let animalChart, projectStatusChart;

document.addEventListener('DOMContentLoaded', () => {
  // default to current month if present, else first available
  const now = new Date();
  const currY = String(now.getFullYear());
  const currM = String(now.getMonth()+1).padStart(2,'0');
  const defaultKey = MONTHLY_DATA[keyFrom(currY,currM)] ? keyFrom(currY,currM) : Object.keys(MONTHLY_DATA)[0];

  // init selectors to default
  const monthSel = document.getElementById('monthName');
  const yearSel = document.getElementById('yearSelect');
  yearSel.value = defaultKey.slice(0,4);
  monthSel.value = defaultKey.slice(5,7);

  // refs
  const kpiTotalAnimals = document.getElementById('kpiTotalAnimals');
  const kpiActiveProjects = document.getElementById('kpiActiveProjects');
  const kpiTrees = document.getElementById('kpiTrees');
  const animalBreak1 = document.getElementById('animalBreak1');
  const animalBreak2 = document.getElementById('animalBreak2');
  const treeBreak = document.getElementById('treeBreak');

  const asOfAnimals = document.getElementById('asOfAnimals');
  const asOfMach = document.getElementById('asOfMach');
  const asOfProj = document.getElementById('asOfProj');
  const asOfTrees = document.getElementById('asOfTrees');
  const asOfChartAnimals = document.getElementById('asOfChartAnimals');
  const asOfChartProj = document.getElementById('asOfChartProj');
  const asOfTableProjects = document.getElementById('asOfTableProjects');

  // charts
  const animalCtx = document.getElementById('animalChart').getContext('2d');
  const projCtx = document.getElementById('projectStatusChart').getContext('2d');

  const labelsOrder = ['Layer','Duck','Chicken','Turkey','Pigs','Cattle','Cornero','Goat','Horse','Gansa', 'Cow'];

  function render(key){
    const data = MONTHLY_DATA[key];
    if(!data) return;

    // KPIs
    const animals = data.animals;
    const totalAnimals = sum(animals);
    kpiTotalAnimals.textContent = totalAnimals.toString();
    kpiActiveProjects.textContent = data.activeProjects.toString();

    const treesTotal = (data.trees.rubber||0) + (data.trees.coconut||0);
    kpiTrees.textContent = treesTotal.toLocaleString();
    treeBreak.textContent = `Rubber: ${data.trees.rubber.toLocaleString()}, Coconut: ${data.trees.coconut.toLocaleString()}`;

    // Animal breakdown lines
    const vals = labelsOrder.map(k => animals[k]||0);
    animalBreak1.textContent = `Layer: ${vals[0]}, Duck: ${vals[1]}, Chicken: ${vals[2]}, Turkey: ${vals[3]}, Pigs: ${vals[4]}, Cattle: ${vals[5]}`;
    animalBreak2.textContent = `Cornero: ${vals[6]}, Goat: ${vals[7]}, Horse: ${vals[8]}, Cow ${vals[9]}, Gansa: ${vals[10]}`;

    // "as of" month in WORDS
    const stamp = `(as of ${toMonthWord(key)})`;
    asOfAnimals.textContent = stamp;
    asOfMach.textContent = stamp;
    asOfProj.textContent = stamp;
    asOfTrees.textContent = stamp;
    asOfChartAnimals.textContent = stamp;
    asOfChartProj.textContent = stamp;
    asOfTableProjects.textContent = stamp;

    // Animal chart
    const datasetAnimal = {
      label: 'Population',
      data: vals,
      backgroundColor: [
        'rgba(255, 159, 64, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 205, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(201, 203, 207, 0.7)',
        'rgba(255, 99, 132, 0.7)',
        'rgba(101, 163, 13, 0.7)',
        'rgba(120, 113, 108, 0.7)',
        'rgba(22, 163, 74, 0.7)'
      ],
      borderColor: [
        'rgba(255, 159, 64, 1)',
        'rgba(54, 162, 235, 1)',
        'rgba(255, 205, 86, 1)',
        'rgba(75, 192, 192, 1)',
        'rgba(153, 102, 255, 1)',
        'rgba(201, 203, 207, 1)',
        'rgba(255, 99, 132, 1)',
        'rgba(101, 163, 13, 1)',
        'rgba(120, 113, 108, 1)',
        'rgba(22, 163, 74, 1)'
      ],
      borderWidth: 1
    };

    if(!animalChart){
      animalChart = new Chart(animalCtx, {
        type: 'bar',
        data: { labels: labelsOrder, datasets: [datasetAnimal] },
        options: {
          indexAxis: 'y',
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            x: { beginAtZero: true, grid: { display: true } },
            y: { grid: { display: false } }
          },
          plugins: { legend: { display:false }, tooltip:{ enabled:true } }
        }
      });
    } else {
      animalChart.data.datasets[0].data = vals;
      animalChart.update();
    }

    // Project status chart
    const projVals = [data.projects.completed, data.projects.inProgress, data.projects.pending];
    const datasetProj = {
      label: 'Project Status',
      data: projVals,
      backgroundColor: [
        'rgba(16, 185, 129, 0.7)',
        'rgba(59, 130, 246, 0.7)',
        'rgba(245, 158, 11, 0.7)'
      ],
      borderColor: [
        'rgba(16, 185, 129, 1)',
        'rgba(59, 130, 246, 1)',
        'rgba(245, 158, 11, 1)'
      ],
      borderWidth: 1
    };

    if(!projectStatusChart){
      projectStatusChart = new Chart(projCtx, {
        type: 'doughnut',
        data: { labels: ['Completed','In Progress','Pending'], datasets: [datasetProj] },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position:'bottom' },
            datalabels: {
              color:'#fff',
              formatter: (value, ctx) => {
                const total = ctx.dataset.data.reduce((s,v)=>s+v,0);
                if(total===0) return '0%';
                const pct = ((value/total)*100).toFixed(1);
                return value>0 ? `${pct}%` : '';
              },
              font: { weight:'bold', size:12 },
              textAlign:'center'
            }
          }
        }
      });
    } else {
      projectStatusChart.data.datasets[0].data = projVals;
      projectStatusChart.update();
    }
  }

  // initial render
  render(defaultKey);

  // change handlers (month name & year)
  function onChange(){
    const key = keyFrom(yearSel.value, monthSel.value);
    render(key);
  }
  monthSel.addEventListener('change', onChange);
  yearSel.addEventListener('change', onChange);
});
</script>

</body>
</html>
