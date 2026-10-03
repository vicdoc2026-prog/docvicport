<?php
require_once '../config/check-session.php';
require_once '../config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote Garnered Dashboard - Zamboanga Sibugay District 2</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3a6c;
            --secondary: #e63946;
            --accent: #2a9d8f;
            --light: #f1faee;
            --dark: #1d3557;
            --gold: #FFD700;
            --silver: #C0C0C0;
            --bronze: #CD7F32;
            --sibugay-blue: #1e3a8a;
            --sibugay-green: #0f766e;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e6f7ff 100%);
        }
        
        .rank-1 { background-color: rgba(255, 215, 0, 0.15); }
        .rank-2 { background-color: rgba(192, 192, 192, 0.15); }
        .rank-3 { background-color: rgba(205, 127, 50, 0.15); }
        .top-rank { background-color: rgba(42, 157, 143, 0.1); }
        .not-top { background-color: rgba(239, 68, 68, 0.05); }
        
        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e2e8f0;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--sibugay-green), var(--sibugay-blue));
        }
        
        .municipality-card {
            transition: all 0.3s ease;
            border-left: 4px solid var(--sibugay-blue);
            background: white;
        }
        
        .municipality-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .barangay-row {
            transition: all 0.2s ease;
            cursor: pointer;
        }
        
        .barangay-row:hover {
            background-color: rgba(241, 245, 249, 0.7) !important;
        }
        
        .medal {
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
        }
        
        .gold { background-color: var(--gold); }
        .silver { background-color: var(--silver); }
        .bronze { background-color: var(--bronze); }
        
        .sibugay-map {
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 120"><path d="M20,20 L180,20 L160,100 L40,100 Z" fill="%231e3a8a" opacity="0.1"/></svg>') no-repeat center;
            background-size: contain;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .sidebar-item {
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar-subitem {
            padding-left: 2.5rem;
        }
        
        .card {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .voting-table tr {
            border-bottom: 1px solid #e2e8f0;
        }
        
        .voting-table tr:last-child {
            border-bottom: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
        }
        
        .tab-btn {
            transition: all 0.2s ease;
        }
        
        .tab-btn:hover {
            background-color: rgba(42, 157, 143, 0.1);
        }
        
        .tab-btn.active {
            border-bottom: 3px solid var(--accent);
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
        
        .calendar-filter-btn.active {
            background-color: var(--accent);
            color: white;
        }
        
        .fc-event {
            cursor: pointer;
        }
        
        .table-container {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
            max-width: 90%;
        }
        
        .modal.show {
            display: flex;
        }
       
  body {
    overflow: hidden; /* Prevent scrolling and hide scrollbar */
  }

    </style>
</head>
<body class="flex min-h-screen bg-gray-50">
    <?php include 'bar/sidebar.php';?>
    <div class="flex-1 flex flex-col">
        <?php include 'bar/header.php';?>
<main class="flex-1 p-6 overflow-y-auto" style="max-height: 100vh;">
    <header class="district-banner py-4 px-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold">Zamboanga Sibugay - District 2</h1>
                <p class="opacity-90">Vote Garnered Dashboard</p>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-right">
                    <p class="text-sm">August 9, 2025</p>
                    <p class="text-sm">Saturday, 10:35 AM</p>
                </div>
            </div>
        </div>
    </header>
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow p-6 flex items-center">
        <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
            <i class="fas fa-city text-xl"></i>
        </div>
        <div>
            <h3 class="text-gray-500 text-sm uppercase">Municipalities</h3>
            <p class="text-2xl font-bold mt-1">7</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 flex items-center">
        <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
            <i class="fas fa-map-marked-alt text-xl"></i>
        </div>
        <div>
            <h3 class="text-gray-500 text-sm uppercase">Barangays</h3>
            <p class="text-2xl font-bold mt-1">168</p>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow p-6 flex items-center">
        <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
            <i class="fas fa-users text-xl"></i>
        </div>
        <div>
            <h3 class="text-gray-500 text-sm uppercase">Total Votes</h3>
            <p class="text-2xl font-bold mt-1">89,231</p>
        </div>
    </div>
</div>
    <div class="lg:col-span-2 bg-white rounded-xl shadow">
        <div class="p-5 border-b">
            <h2 class="text-xl font-bold text-gray-800">Top Performing Municipalities</h2>
            <p class="text-gray-600">Ranked by total votes for Javier, Doc Vic</p>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="municipality-card rounded-lg shadow p-5 rank-1" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="medal gold mr-3">1</div>
                            <h3 class="text-lg font-bold">Ipil</h3>
                        </div>
                        <span class="text-lg font-bold text-green-600">20,951 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 20,951</span>
                        <span>Rank: 4</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 100%"></div>
                    </div>
                </div>
                <div class="municipality-card rounded-lg shadow p-5 rank-2" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="medal silver mr-3">2</div>
                            <h3 class="text-lg font-bold">Siay</h3>
                        </div>
                        <span class="text-lg font-bold text-green-600">12,480 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 12,480</span>
                        <span>Rank: 1</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 57.1%"></div>
                    </div>
                </div>
                <div class="municipality-card rounded-lg shadow p-5 rank-3" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="medal bronze mr-3">3</div>
                            <h3 class="text-lg font-bold">Titay</h3>
                        </div>
                        <span class="text-lg font-bold text-green-600">12,956 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 12,956</span>
                        <span>Rank: 4</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 55.4%"></div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div class="municipality-card rounded-lg shadow p-5" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded-full mr-3">4</div>
                            <h3 class="text-lg font-bold">R.T. Lim</h3>
                        </div>
                        <span class="text-lg font-bold text-blue-600">12,408 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 12,408</span>
                        <span>Rank: 2</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 55.4%"></div>
                    </div>
                </div>
                <div class="municipality-card rounded-lg shadow p-5" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded-full mr-3">5</div>
                            <h3 class="text-lg font-bold">Naga</h3>
                        </div>
                        <span class="text-lg font-bold text-blue-600">10,920votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 10,920</span>
                        <span>Rank: 1</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 49.3%"></div>
                    </div>
                </div>
                <div class="municipality-card rounded-lg shadow p-5" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded-full mr-3">6</div>
                            <h3 class="text-lg font-bold">Tungawan</h3>
                        </div>
                        <span class="text-lg font-bold text-blue-600">10,664 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 10,664</span>
                        <span>Rank: 2</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 45.5%"></div>
                    </div>
                </div>
                <div class="municipality-card rounded-lg shadow p-5 not-top" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center">
                            <div class="w-6 h-6 flex items-center justify-center bg-gray-200 rounded-full mr-3">7</div>
                            <h3 class="text-lg font-bold">Kabasalan</h3>
                        </div>
                        <span class="text-lg font-bold text-red-600">8,852 votes</span>
                    </div>
                    <div class="mb-2 flex justify-between text-sm">
                        <span>Total Votes: 8,852</span>
                        <span>Rank: 4</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 38.6%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow mb-8" style="margin-top: 35px;">
        <div class="p-5 border-b">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-800">Municipality Performance</h2>
                    <p class="text-gray-600">Vote garnered by barangay across municipalities</p>
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-sibugay-blue text-white rounded-lg hover:bg-blue-800 transition">
                        <i class="fas fa-download mr-2"></i>Export Data
                    </button>
                    <button class="px-4 py-2 bg-sibugay-green text-white rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
        <div class="p-4">
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Select Municipality:</label>
                <div class="municipality-selector space-x-2">
                    <button class="px-4 py-2 bg-blue-500 text-white rounded-lg shadow active-btn" data-municipality="Ipil">Ipil</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="Kabasalan">Kabasalan</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="Siay">Siay</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="Naga">Naga</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="Tungawan">Tungawan</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="R.T. Lim">R.T. Lim</button>
                    <button class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition" data-municipality="Titay">Titay</button>
                </div>

            </div>
            <div class="table-container overflow-x-auto">
                <table class="w-full voting-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-4 text-left">Rank</th>
                            <th class="py-3 px-4 text-left">Barangay</th>
                            <th class="py-3 px-4 text-left">Total Votes</th>
                            <th class="py-3 px-4 text-left">Rank in Barangay</th>
                            <th class="py-3 px-4 text-left">Progress</th>
                            <th class="py-3 px-4 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200" id="barangay-table-body">
                        <!-- Initial data for Ipil -->
                        <tr class="barangay-row top-rank" data-barangay="Sanito" data-votes="1939" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">1</td>
                            <td class="py-3 px-4 font-medium">Sanito</td>
                            <td class="py-3 px-4">1,939</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 100%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Veterans Village" data-votes="1731" data-rank="5">
                            <td class="py-3 px-4 font-bold text-green-600">2</td>
                            <td class="py-3 px-4 font-medium">Veterans Village</td>
                            <td class="py-3 px-4">1,731</td>
                            <td class="py-3 px-4 font-semibold text-green-600">5</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 89.3%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Don Andres" data-votes="1432" data-rank="4">
                            <td class="py-3 px-4 font-bold text-green-600">3</td>
                            <td class="py-3 px-4 font-medium">Don Andres</td>
                            <td class="py-3 px-4">1,432</td>
                            <td class="py-3 px-4 font-semibold text-green-600">4</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 73.8%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Poblacion" data-votes="1222" data-rank="2">
                            <td class="py-3 px-4 font-bold text-green-600">4</td>
                            <td class="py-3 px-4 font-medium">Poblacion</td>
                            <td class="py-3 px-4">1,222</td>
                            <td class="py-3 px-4 font-semibold text-green-600">2</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 63.0%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Taway" data-votes="1185" data-rank="4">
                            <td class="py-3 px-4">5</td>
                            <td class="py-3 px-4 font-medium">Taway</td>
                            <td class="py-3 px-4">1,185</td>
                            <td class="py-3 px-4">4</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 61.1%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Tiayon" data-votes="1175" data-rank="5">
                            <td class="py-3 px-4">6</td>
                            <td class="py-3 px-4 font-medium">Tiayon</td>
                            <td class="py-3 px-4">1,175</td>
                            <td class="py-3 px-4">5</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 60.6%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Buluan" data-votes="1070" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">7</td>
                            <td class="py-3 px-4 font-medium">Buluan</td>
                            <td class="py-3 px-4">1,070</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 55.2%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Magdaup" data-votes="1034" data-rank="4">
                            <td class="py-3 px-4">8</td>
                            <td class="py-3 px-4 font-medium">Magdaup</td>
                            <td class="py-3 px-4">1,034</td>
                            <td class="py-3 px-4">4</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 53.3%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Pangi" data-votes="904" data-rank="4">
                            <td class="py-3 px-4">9</td>
                            <td class="py-3 px-4 font-medium">Pangi</td>
                            <td class="py-3 px-4">904</td>
                            <td class="py-3 px-4">4</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 46.6%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Tenan" data-votes="845" data-rank="1">
                            <td class="py-3 px-4 font-bold text-green-600">10</td>
                            <td class="py-3 px-4 font-medium">Tenan</td>
                            <td class="py-3 px-4">845</td>
                            <td class="py-3 px-4 font-semibold text-green-600">1</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 43.6%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row not-top" data-barangay="Bangkerohan" data-votes="772" data-rank="6">
                            <td class="py-3 px-4">11</td>
                            <td class="py-3 px-4 font-medium">Bangkerohan</td>
                            <td class="py-3 px-4">772</td>
                            <td class="py-3 px-4">6</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 39.8%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Makilas" data-votes="745" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">12</td>
                            <td class="py-3 px-4 font-medium">Makilas</td>
                            <td class="py-3 px-4">745</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 38.4%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Ipil Heights" data-votes="724" data-rank="5">
                            <td class="py-3 px-4">13</td>
                            <td class="py-3 px-4 font-medium">Ipil Heights</td>
                            <td class="py-3 px-4">724</td>
                            <td class="py-3 px-4">5</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 37.3%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row" data-barangay="Lower Taway" data-votes="703" data-rank="5">
                            <td class="py-3 px-4">14</td>
                            <td class="py-3 px-4 font-medium">Lower Taway</td>
                            <td class="py-3 px-4">703</td>
                            <td class="py-3 px-4">5</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 36.3%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Guituan" data-votes="637" data-rank="1">
                            <td class="py-3 px-4 font-bold text-green-600">15</td>
                            <td class="py-3 px-4 font-medium">Guituan</td>
                            <td class="py-3 px-4">637</td>
                            <td class="py-3 px-4 font-semibold text-green-600">1</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 32.8%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Upper Pangi" data-votes="619" data-rank="1">
                            <td class="py-3 px-4 font-bold text-green-600">16</td>
                            <td class="py-3 px-4 font-medium">Upper Pangi</td>
                            <td class="py-3 px-4">619</td>
                            <td class="py-3 px-4 font-semibold text-green-600">1</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 31.9%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Timalang" data-votes="555" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">17</td>
                            <td class="py-3 px-4 font-medium">Timalang</td>
                            <td class="py-3 px-4">555</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 28.6%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Lower Ipil Heights" data-votes="516" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">18</td>
                            <td class="py-3 px-4 font-medium">Lower Ipil Heights</td>
                            <td class="py-3 px-4">516</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 26.6%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row not-top" data-barangay="Caparan" data-votes="414" data-rank="6">
                            <td class="py-3 px-4">19</td>
                            <td class="py-3 px-4 font-medium">Caparan</td>
                            <td class="py-3 px-4">414</td>
                            <td class="py-3 px-4">6</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 21.4%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">Good</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Dona Josefa" data-votes="404" data-rank="2">
                            <td class="py-3 px-4 font-bold text-green-600">20</td>
                            <td class="py-3 px-4 font-medium">Dona Josefa</td>
                            <td class="py-3 px-4">404</td>
                            <td class="py-3 px-4 font-semibold text-green-600">2</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 20.8%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Suclema" data-votes="389" data-rank="2">
                            <td class="py-3 px-4 font-bold text-green-600">21</td>
                            <td class="py-3 px-4 font-medium">Suclema</td>
                            <td class="py-3 px-4">389</td>
                            <td class="py-3 px-4 font-semibold text-green-600">2</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 20.1%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Tomitom" data-votes="375" data-rank="2">
                            <td class="py-3 px-4 font-bold text-green-600">22</td>
                            <td class="py-3 px-4 font-medium">Tomitom</td>
                            <td class="py-3 px-4">375</td>
                            <td class="py-3 px-4 font-semibold text-green-600">2</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 19.3%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row not-top" data-barangay="Bacalan" data-votes="372" data-rank="7">
                            <td class="py-3 px-4">23</td>
                            <td class="py-3 px-4 font-medium">Bacalan</td>
                            <td class="py-3 px-4">372</td>
                            <td class="py-3 px-4">7</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 19.2%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Needs Attention</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Lumbia" data-votes="328" data-rank="1">
                            <td class="py-3 px-4 font-bold text-green-600">24</td>
                            <td class="py-3 px-4 font-medium">Lumbia</td>
                            <td class="py-3 px-4">328</td>
                            <td class="py-3 px-4 font-semibold text-green-600">1</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 16.9%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Logan" data-votes="236" data-rank="3">
                            <td class="py-3 px-4 font-bold text-green-600">25</td>
                            <td class="py-3 px-4 font-medium">Logan</td>
                            <td class="py-3 px-4">236</td>
                            <td class="py-3 px-4 font-semibold text-green-600">3</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 12.2%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row not-top" data-barangay="Maasin" data-votes="222" data-rank="7">
                            <td class="py-3 px-4">26</td>
                            <td class="py-3 px-4 font-medium">Maasin</td>
                            <td class="py-3 px-4">222</td>
                            <td class="py-3 px-4">7</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 11.4%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">Needs Attention</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Domandan" data-votes="215" data-rank="1">
                            <td class="py-3 px-4 font-bold text-green-600">27</td>
                            <td class="py-3 px-4 font-medium">Domandan</td>
                            <td class="py-3 px-4">215</td>
                            <td class="py-3 px-4 font-semibold text-green-600">1</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 11.1%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                        <tr class="barangay-row top-rank" data-barangay="Labi" data-votes="188" data-rank="2">
                            <td class="py-3 px-4 font-bold text-green-600">28</td>
                            <td class="py-3 px-4 font-medium">Labi</td>
                            <td class="py-3 px-4">188</td>
                            <td class="py-3 px-4 font-semibold text-green-600">2</td>
                            <td class="py-3 px-4">
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: 9.7%"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">Top Performer</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="mt-6 flex justify-between items-center">
                <div class="text-sm text-gray-600" id="barangay-count">
                    Showing 28 of 28 barangays in Ipil
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="px-4 py-2 bg-gray-200 rounded-lg hover:bg-gray-300">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Top Barangays Card -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Top Barangays in District 2</h3>
            <div class="space-y-4">
                <!-- Top 10 Barangays with Shining Star Icons -->
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Crossing Sta. Clara, Naga</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>930 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Buyugan, Siay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>890 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Balingasan, Siay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>841 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Poblacion, Siay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>701 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Tambanan, Naga</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>622 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Upper Pangi, Ipil</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>619 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Coloran, Siay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>616 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Aguinaldo, Naga</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>594 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Taytay Manubo, Naga</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>530 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-yellow-100 text-yellow-600 rounded-full mr-4">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Logpond, Siay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 1</span>
                            <span>506 votes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Needs Attention Card -->
        <div class="bg-white rounded-xl shadow p-6 mb-8">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Needs Attention</h3>
            <div class="space-y-4">
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Diampak, Kabasalan</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 9</span>
                            <span>71 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Upper Sulitan, Naga</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 8</span>
                            <span>488 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Malagandis, Titay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 8</span>
                            <span>273 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Kipit, Titay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 8</span>
                            <span>247 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Little Margos, Tungawan</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 8</span>
                            <span>95 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Bacalan, Ipil</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 7</span>
                            <span>372 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Maasin, Ipil</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 7</span>
                            <span>222 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Longilog, Titay</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 7</span>
                            <span>193 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Tilasan, R.T. Lim</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 7</span>
                            <span>187 votes</span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center">
                    <div class="w-8 h-8 flex items-center justify-center bg-red-100 text-red-800 rounded-full mr-4">
                        <i class="fas fa-exclamation"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium">Tampilisan, Kabasalan</h4>
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Rank: 7</span>
                            <span>94 votes</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal" id="barangay-modal">
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4" id="modal-title">Barangay Details</h2>
            <p class="mb-2"><strong>Votes:</strong> <span id="modal-votes"></span></p>
            <p class="mb-4"><strong>Rank in Barangay:</strong> <span id="modal-rank"></span></p>
            <button class="px-4 py-2 bg-sibugay-blue text-white rounded-lg hover:bg-blue-800 transition" onclick="closeModal()">Close</button>
        </div>
    </div>
</main>    </div>
    <script>
        // Barangay data from Vote_Garnered.docx
        const barangayData = {
            'Ipil': [
                { name: 'Sanito', votes: 1939, rank: 3, maxVotes: 1939 },
                { name: 'Veterans Village', votes: 1731, rank: 5, maxVotes: 1939 },
                { name: 'Don Andres', votes: 1432, rank: 4, maxVotes: 1939 },
                { name: 'Poblacion', votes: 1222, rank: 2, maxVotes: 1939 },
                { name: 'Taway', votes: 1185, rank: 4, maxVotes: 1939 },
                { name: 'Tiayon', votes: 1175, rank: 5, maxVotes: 1939 },
                { name: 'Buluan', votes: 1070, rank: 3, maxVotes: 1939 },
                { name: 'Magdaup', votes: 1034, rank: 4, maxVotes: 1939 },
                { name: 'Pangi', votes: 904, rank: 4, maxVotes: 1939 },
                { name: 'Tenan', votes: 845, rank: 1, maxVotes: 1939 },
                { name: 'Bangkerohan', votes: 772, rank: 6, maxVotes: 1939 },
                { name: 'Makilas', votes: 745, rank: 3, maxVotes: 1939 },
                { name: 'Ipil Heights', votes: 724, rank: 5, maxVotes: 1939 },
                { name: 'Lower Taway', votes: 703, rank: 5, maxVotes: 1939 },
                { name: 'Guituan', votes: 637, rank: 1, maxVotes: 1939 },
                { name: 'Upper Pangi', votes: 619, rank: 1, maxVotes: 1939 },
                { name: 'Timalang', votes: 555, rank: 3, maxVotes: 1939 },
                { name: 'Lower Ipil Heights', votes: 516, rank: 3, maxVotes: 1939 },
                { name: 'Caparan', votes: 414, rank: 6, maxVotes: 1939 },
                { name: 'Dona Josefa', votes: 404, rank: 2, maxVotes: 1939 },
                { name: 'Suclema', votes: 389, rank: 2, maxVotes: 1939 },
                { name: 'Tomitom', votes: 375, rank: 2, maxVotes: 1939 },
                { name: 'Bacalan', votes: 372, rank: 7, maxVotes: 1939 },
                { name: 'Lumbia', votes: 328, rank: 1, maxVotes: 1939 },
                { name: 'Logan', votes: 236, rank: 3, maxVotes: 1939 },
                { name: 'Maasin', votes: 222, rank: 7, maxVotes: 1939 },
                { name: 'Domandan', votes: 215, rank: 1, maxVotes: 1939 },
                { name: 'Labi', votes: 188, rank: 2, maxVotes: 1939 }
            ],
            'Naga': [
                { name: 'Poblacion', votes: 1010, rank: 3, maxVotes: 1010 },
                { name: 'Crossing Sta. Clara', votes: 930, rank: 1, maxVotes: 1010 },
                { name: 'Sulo', votes: 911, rank: 1, maxVotes: 1010 },
                { name: 'Tambanan', votes: 622, rank: 1, maxVotes: 1010 },
                { name: 'Marsolo', votes: 605, rank: 1, maxVotes: 1010 },
                { name: 'Aguinaldo', votes: 594, rank: 1, maxVotes: 1010 },
                { name: 'Lapaz', votes: 570, rank: 3, maxVotes: 1010 },
                { name: 'Sta. Clara', votes: 552, rank: 4, maxVotes: 1010 },
                { name: 'Taytay Manubo', votes: 530, rank: 1, maxVotes: 1010 },
                { name: 'Baluno', votes: 521, rank: 3, maxVotes: 1010 },
                { name: 'Kaliantana', votes: 489, rank: 3, maxVotes: 1010 },
                { name: 'Upper Sulitan', votes: 488, rank: 8, maxVotes: 1010 },
                { name: 'Baga', votes: 463, rank: 4, maxVotes: 1010 },
                { name: 'San Isidro', votes: 394, rank: 1, maxVotes: 1010 },
                { name: 'Guintoloan', votes: 371, rank: 1, maxVotes: 1010 },
                { name: 'Sandayong', votes: 346, rank: 5, maxVotes: 1010 },
                { name: 'Tipan', votes: 341, rank: 2, maxVotes: 1010 },
                { name: 'Mamagon', votes: 280, rank: 4, maxVotes: 1010 },
                { name: 'Lower Sultan', votes: 236, rank: 3, maxVotes: 1010 },
                { name: 'Bangkaw-bangkaw', votes: 213, rank: 3, maxVotes: 1010 },
                { name: 'Gubawang', votes: 203, rank: 4, maxVotes: 1010 },
                { name: 'Tilubog', votes: 174, rank: 4, maxVotes: 1010 },
                { name: 'Cabong', votes: 77, rank: 6, maxVotes: 1010 }
            ],
            'Kabasalan': [
                    { name: 'Conception', votes: 737, rank: 4, maxVotes: 737 },
                    { name: 'Tigbangagan', votes: 662, rank: 2, maxVotes: 737 },
                    { name: 'Santa Cruz', votes: 549, rank: 3, maxVotes: 737 },
                    { name: 'Calubihan', votes: 516, rank: 4, maxVotes: 737 },
                    { name: 'Riverside', votes: 492, rank: 4, maxVotes: 737 },
                    { name: 'Buayan', votes: 484, rank: 4, maxVotes: 737 },
                    { name: 'Cainglet', votes: 459, rank: 4, maxVotes: 737 },
                    { name: 'Timuay Danda', votes: 404, rank: 4, maxVotes: 737 },
                    { name: 'Poblacion', votes: 382, rank: 4, maxVotes: 737 },
                    { name: 'Goodyear', votes: 374, rank: 4, maxVotes: 737 },
                    { name: 'Sanghanan', votes: 368, rank: 4, maxVotes: 737 },
                    { name: 'Bolo Batallion', votes: 284, rank: 4, maxVotes: 737 },
                    { name: 'Calapan', votes: 274, rank: 3, maxVotes: 737 },
                    { name: 'Banker', votes: 260, rank: 5, maxVotes: 737 },
                    { name: 'Penaranda', votes: 230, rank: 6, maxVotes: 737 },
                    { name: 'Nazareth', votes: 230, rank: 6, maxVotes: 737 },
                    { name: 'Shiolan', votes: 145, rank: 1, maxVotes: 737 },
                    { name: 'Lacnapan', votes: 135, rank: 5, maxVotes: 737 },
                    { name: 'Gacbusan', votes: 134, rank: 6, maxVotes: 737 },
                    { name: 'Little Baguio', votes: 119, rank: 5, maxVotes: 737 },
                    { name: 'Sayao', votes: 110, rank: 5, maxVotes: 737 },
                    { name: 'Tamin', votes: 110, rank: 4, maxVotes: 737 },
                    { name: 'Tampilisan', votes: 94, rank: 7, maxVotes: 737 },
                    { name: 'Sininan', votes: 89, rank: 6, maxVotes: 737 },
                    { name: 'Diampak', votes: 71, rank: 9, maxVotes: 737 },
                    { name: 'Dipala', votes: 124, rank: 6, maxVotes: 737 },
                    { name: 'Palinta', votes: 308, rank: 6, maxVotes: 737 },
                    { name: 'Lumbayao', votes: 520, rank: 4, maxVotes: 737 },
                    { name: 'Simbol', votes: 230, rank: 4, maxVotes: 737 }
                    ],

           'Siay': [
                    { name: 'Bagong Silang', votes: 360, rank: 1, maxVotes: 977 },
                    { name: 'Balagon', votes: 841, rank: 1, maxVotes: 977 },
                    { name: 'Balingasan', votes: 317, rank: 1, maxVotes: 977 },
                    { name: 'Balucanan', votes: 489, rank: 2, maxVotes: 977 },
                    { name: 'Bataan', votes: 565, rank: 2, maxVotes: 977 },
                    { name: 'Batu', votes: 890, rank: 1, maxVotes: 977 },
                    { name: 'Buyugan', votes: 374, rank: 2, maxVotes: 977 },
                    { name: 'Camanga', votes: 444, rank: 4, maxVotes: 977 },
                    { name: 'Coloran', votes: 616, rank: 1, maxVotes: 977 },
                    { name: 'Kimos', votes: 349, rank: 1, maxVotes: 977 },
                    { name: 'Labasan', votes: 311, rank: 1, maxVotes: 977 },
                    { name: 'Lagting', votes: 201, rank: 1, maxVotes: 977 },
                    { name: 'Laih', votes: 343, rank: 1, maxVotes: 977 },
                    { name: 'Logpond', votes: 506, rank: 1, maxVotes: 977 },
                    { name: 'Magsaysay', votes: 151, rank: 1, maxVotes: 977 },
                    { name: 'Mahayahay', votes: 299, rank: 1, maxVotes: 977 },
                    { name: 'Maligaya', votes: 253, rank: 1, maxVotes: 977 },
                    { name: 'Maniha', votes: 261, rank: 5, maxVotes: 977 },
                    { name: 'Minsulao', votes: 346, rank: 1, maxVotes: 977 },
                    { name: 'Mirangan', votes: 445, rank: 1, maxVotes: 977 },
                    { name: 'Monching', votes: 977, rank: 1, maxVotes: 977 },
                    { name: 'Paruk', votes: 400, rank: 1, maxVotes: 977 },
                    { name: 'Poblacion', votes: 701, rank: 1, maxVotes: 977 },
                    { name: 'Princess Sumama', votes: 133, rank: 2, maxVotes: 977 },
                    { name: 'Salinding', votes: 697, rank: 1, maxVotes: 977 },
                    { name: 'San Isidro', votes: 239, rank: 1, maxVotes: 977 },
                    { name: 'Sibuguey', votes: 303, rank: 1, maxVotes: 977 },
                    { name: 'Siloh', votes: 426, rank: 2, maxVotes: 977 },
                    { name: 'Villagracia', votes: 243, rank: 1, maxVotes: 977 }
                    ],

                        'Titay': [
                { name: 'Poblacion', votes: 1850, rank: 4, maxVotes: 1850 },
                { name: 'Kitabog', votes: 1040, rank: 3, maxVotes: 1850 },
                { name: 'Palomoc', votes: 912, rank: 2, maxVotes: 1850 },
                { name: 'Dalangin Muslim', votes: 728, rank: 2, maxVotes: 1850 },
                { name: 'San Antonio', votes: 606, rank: 5, maxVotes: 1850 },
                { name: 'Achasol', votes: 590, rank: 3, maxVotes: 1850 },
                { name: 'Namnama', votes: 416, rank: 6, maxVotes: 1850 },
                { name: 'Camanga', votes: 403, rank: 2, maxVotes: 1850 },
                { name: 'Tugop', votes: 378, rank: 1, maxVotes: 1850 },
                { name: 'Santa Fe', votes: 372, rank: 1, maxVotes: 1850 },
                { name: 'Bangco', votes: 371, rank: 4, maxVotes: 1850 },
                { name: 'La Libertad', votes: 367, rank: 3, maxVotes: 1850 },
                { name: 'Dalisay', votes: 357, rank: 3, maxVotes: 1850 },
                { name: 'San Isidro', votes: 316, rank: 3, maxVotes: 1850 },
                { name: 'Mate', votes: 313, rank: 3, maxVotes: 1850 },
                { name: 'Gomotoc', votes: 325, rank: 5, maxVotes: 1850 },
                { name: 'Moalboal', votes: 274, rank: 2, maxVotes: 1850 },
                { name: 'Malagandis', votes: 273, rank: 8, maxVotes: 1850 },
                { name: 'Azusano', votes: 286, rank: 5, maxVotes: 1850 },
                { name: 'Kipit', votes: 247, rank: 8, maxVotes: 1850 },
                { name: 'Pulidan', votes: 213, rank: 6, maxVotes: 1850 },
                { name: 'Poblacion Muslim', votes: 213, rank: 3, maxVotes: 1850 },
                { name: 'Supit', votes: 215, rank: 5, maxVotes: 1850 },
                { name: 'New Canaan', votes: 194, rank: 3, maxVotes: 1850 },
                { name: 'Longilog', votes: 193, rank: 7, maxVotes: 1850 },
                { name: 'Tugop Muslim', votes: 188, rank: 3, maxVotes: 1850 }
                ],

                'R.T. Lim': [
                { name: 'Katipunan', votes: 1030, rank: 2, maxVotes: 1030 },
                { name: 'Ali Alsree', votes: 927, rank: 2, maxVotes: 1030 },
                { name: 'Don Perfecto', votes: 727, rank: 3, maxVotes: 1030 },
                { name: 'Pres. Roxas', votes: 666, rank: 2, maxVotes: 1030 },
                { name: 'Malubal', votes: 652, rank: 2, maxVotes: 1030 },
                { name: 'Magsaysay', votes: 630, rank: 2, maxVotes: 1030 },
                { name: 'San Fernandino', votes: 570, rank: 3, maxVotes: 1030 },
                { name: 'Surabay', votes: 550, rank: 2, maxVotes: 1030 },
                { name: 'Kulambugan', votes: 520, rank: 2, maxVotes: 1030 },
                { name: 'Gango', votes: 496, rank: 3, maxVotes: 1030 },
                { name: 'Santo Rosario', votes: 489, rank: 3, maxVotes: 1030 },
                { name: 'Tupilac', votes: 432, rank: 1, maxVotes: 1030 },
                { name: 'Siawang', votes: 411, rank: 2, maxVotes: 1030 },
                { name: 'Taruc', votes: 396, rank: 2, maxVotes: 1030 },
                { name: 'Casacon', votes: 378, rank: 2, maxVotes: 1030 },
                { name: 'Calula', votes: 318, rank: 4, maxVotes: 1030 },
                { name: 'Mabini', votes: 318, rank: 4, maxVotes: 1030 },
                { name: 'Palmera', votes: 312, rank: 2, maxVotes: 1030 },
                { name: 'Moalboal', votes: 274, rank: 2, maxVotes: 1030 },
                { name: 'New Antique', votes: 280, rank: 3, maxVotes: 1030 },
                { name: 'Balansag', votes: 268, rank: 2, maxVotes: 1030 },
                { name: 'San Jose', votes: 202, rank: 2, maxVotes: 1030 },
                { name: 'Tilasan', votes: 187, rank: 7, maxVotes: 1030 },
                { name: 'Remedios', votes: 145, rank: 5, maxVotes: 1030 },
                { name: 'San Antonio', votes: 406, rank: 3, maxVotes: 1030 }
                ],
            
            'Tungawan': [
                { name: 'Baluran', votes: 542, rank: 3, maxVotes: 1238 },
                { name: 'Batungan', votes: 286, rank: 6, maxVotes: 1238 },
                { name: 'Cayamcam', votes: 548, rank: 2, maxVotes: 1238 },
                { name: 'Datu Tumanggong', votes: 453, rank: 2, maxVotes: 1238 },
                { name: 'Gaycon', votes: 298, rank: 2, maxVotes: 1238 },
                { name: 'Langon', votes: 478, rank: 4, maxVotes: 1238 },
                { name: 'Libertad', votes: 1238, rank: 4, maxVotes: 1238 },
                { name: 'Linguisan', votes: 262, rank: 3, maxVotes: 1238 },
                { name: 'Little Margos', votes: 95, rank: 8, maxVotes: 1238 },
                { name: 'Loboc', votes: 383, rank: 3, maxVotes: 1238 },
                { name: 'Looc-Labuan', votes: 270, rank: 5, maxVotes: 1238 },
                { name: 'Lower Tungawan', votes: 569, rank: 2, maxVotes: 1238 },
                { name: 'Malungon', votes: 399, rank: 5, maxVotes: 1238 },
                { name: 'Masao', votes: 379, rank: 2, maxVotes: 1238 },
                { name: 'San Isidro', votes: 432, rank: 2, maxVotes: 1238 },
                { name: 'San Pedro', votes: 668, rank: 5, maxVotes: 1238 },
                { name: 'San Vicente', votes: 668, rank: 5, maxVotes: 1238 },
                { name: 'Santo Niño', votes: 444, rank: 2, maxVotes: 1238 },
                { name: 'Sisay', votes: 440, rank: 6, maxVotes: 1238 },
                { name: 'Tagbilas', votes: 290, rank: 3, maxVotes: 1238 },
                { name: 'Tigbanuang', votes: 521, rank: 2, maxVotes: 1238 },
                { name: 'Tigbucay', votes: 272, rank: 2, maxVotes: 1238 },
                { name: 'Tigpalay', votes: 499, rank: 3, maxVotes: 1238 },
                { name: 'Timbabauan', votes: 251, rank: 4, maxVotes: 1238 },
                { name: 'Upper Tungawan', votes: 332, rank: 4, maxVotes: 1238 }
                ],

        };

        // Function to determine status
            function getStatus(municipalityRank, barangayRank, votes) {
                if (municipalityRank <= 5 || barangayRank <= 5) {
                    return 'Top Performer';
                } else {
                    return 'Needs Attention';
                }
            }

        // Function to populate barangay table
        function populateBarangayTable(municipality) {
            const tbody = document.getElementById('barangay-table-body');
            tbody.innerHTML = '';
            const barangays = barangayData[municipality];
            barangays.forEach((barangay, index) => {
                const row = document.createElement('tr');
                const status = getStatus(index + 1, barangay.rank, barangay.votes);
                const isTopRank = status === 'Top Performer';
                const isNotTop = status === 'Needs Attention';
                row.className = `barangay-row ${isTopRank ? 'top-rank' : ''} ${isNotTop ? 'not-top' : ''}`;
                row.dataset.barangay = barangay.name;
                row.dataset.votes = barangay.votes;
                row.dataset.rank = barangay.rank;

                const progressWidth = (barangay.votes / barangay.maxVotes) * 100;
                row.innerHTML = `
                    <td class="py-3 px-4 ${isTopRank ? 'font-bold text-green-600' : ''}">${index + 1}</td>
                    <td class="py-3 px-4 font-medium">${barangay.name}</td>
                    <td class="py-3 px-4">${barangay.votes}</td>
                    <td class="py-3 px-4 ${isTopRank ? 'font-semibold text-green-600' : ''}">${barangay.rank}</td>
                    <td class="py-3 px-4">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${progressWidth}%"></div>
                        </div>
                    </td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 rounded-full text-xs 
                            ${status === 'Top Performer' ? 'bg-green-100 text-green-800' : 
                              status === 'Good' ? 'bg-yellow-100 text-yellow-800' : 
                              'bg-red-100 text-red-800'}">
                            ${status}
                        </span>
                    </td>
                `;
                row.addEventListener('click', () => showModal(barangay));
                tbody.appendChild(row);
            });

            // Update barangay count
            document.getElementById('barangay-count').textContent = 
                `Showing ${barangays.length} of ${barangays.length} barangays in ${municipality}`;
        }

        // Modal functions
        function showModal(barangay) {
            document.getElementById('modal-title').textContent = `${barangay.name} Details`;
            document.getElementById('modal-votes').textContent = barangay.votes;
            document.getElementById('modal-rank').textContent = barangay.rank;
            document.getElementById('barangay-modal').classList.add('show');
        }

        function closeModal() {
            document.getElementById('barangay-modal').classList.remove('show');
        }

// Event listeners for municipality buttons
document.querySelectorAll('.municipality-selector button').forEach(button => {
    button.addEventListener('click', () => {
        // Reset styles for all buttons
        document.querySelectorAll('.municipality-selector button').forEach(btn => {
            btn.classList.remove('bg-blue-200', 'text-gray-800');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        });

        // Set active style for the clicked button
        button.classList.remove('bg-gray-100', 'text-gray-700');
        button.classList.add('bg-blue-200', 'text-gray-800'); // use gray-800 for visibility

        // Populate table
        const municipality = button.dataset.municipality;
        populateBarangayTable(municipality);
    });
});


        // Initialize with Ipil
        populateBarangayTable('Ipil');
    </script>
    <script>
    const buttons = document.querySelectorAll('.municipality-selector button');

    buttons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active styles from all
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-500', 'text-white');
                btn.classList.add('bg-gray-100', 'text-gray-700');
            });

            // Add active styles to clicked
            button.classList.remove('bg-gray-100', 'text-gray-700');
            button.classList.add('bg-blue-500', 'text-white');

            // Populate table
            const municipality = button.dataset.municipality;
            populateBarangayTable(municipality); // ← Ensure this function is defined
        });
    });
</script>

</body>
</html>