<?php 
require_once '../electric/config/check-session.php';

include 'db_config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Title</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2563eb;
            --primary-light: #3b82f6;
            --primary-bg: #eff6ff;
            --secondary: #64748b;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
            --border: #e2e8f0;
            --card-bg: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
            --shadow: 0 4px 6px rgba(0,0,0,0.05);
            --shadow-lg: 0 10px 25px rgba(0,0,0,0.05);
            --radius: 12px;
            --radius-lg: 16px;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #ffffff;
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }
        
        /* Top Navigation */
        .top-nav {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: var(--shadow-sm);
        }
        
        .nav-container {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .nav-brand i {
            font-size: 1.5rem;
            color: var(--primary);
        }
        
        .nav-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 1rem;
            background: var(--primary-bg);
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* Main Container */
        .main-container {
            max-width: 1600px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .stat-card:nth-child(1) .stat-icon {
            background: var(--primary);
        }
        
        .stat-card:nth-child(2) .stat-icon {
            background: var(--success);
        }
        
        .stat-card:nth-child(3) .stat-icon {
            background: var(--warning);
        }
        
        .stat-card:nth-child(4) .stat-icon {
            background: var(--danger);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: var(--secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        /* Dashboard Card */
        .dashboard-card {
            background: var(--card-bg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            border: 1px solid var(--border);
        }
        
        .dashboard-header {
            background: var(--card-bg);
            padding: 1.75rem 2rem;
            border-bottom: 1px solid var(--border);
        }
        
        .dashboard-title-section {
            margin-bottom: 1.5rem;
        }
        
        .dashboard-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.375rem;
        }
        
        .dashboard-subtitle {
            color: var(--secondary);
            font-size: 0.95rem;
        }
        
        /* Search and Filter Bar */
        .control-bar {
            background: #fafbfc;
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-wrapper {
            flex: 1;
            min-width: 300px;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--dark);
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary);
            font-size: 1rem;
        }
        
        .filter-group {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .filter-select {
            padding: 0.75rem 2.25rem 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: white;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%2364748b' d='M6 8L2 4h8z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            min-width: 160px;
            color: var(--dark);
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
            border: 1px solid var(--primary);
        }
        
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: white;
            color: var(--dark);
            border: 1px solid var(--border);
        }
        
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: var(--primary);
            color: var(--primary);
        }
        
        .header-actions {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        /* Content Area */
        .content-area {
            padding: 2rem;
            min-height: 600px;
        }
        
        /* Grid View */
        .grid-view {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 1.5rem;
        }
        
        .title-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .title-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }
        
        .title-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .folder-badge {
            background: #f1f5f9;
            color: var(--secondary);
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .area-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        
        /* Area Badge Colors */
        .badge-lt { background: #3b82f6; }
        .badge-t { background: #10b981; }
        .badge-ty { background: #ec4899; }
        .badge-up { background: #f59e0b; }
        .badge-pa { background: #8b5cf6; }
        .badge-go { background: #ef4444; }
        .badge-g { background: #06b6d4; }
        .badge-s { background: #14b8a6; }
        .badge-p { background: #eab308; }
        .badge-m { background: #f97316; }
        .badge-r { background: #a855f7; }
        .badge-sh { background: #0ea5e9; }
        .badge-zc { background: #6366f1; }
        .badge-other { background: #64748b; }
        
        .title-ref {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .title-owner {
            font-size: 1rem;
            font-weight: 500;
            color: var(--primary);
            margin-bottom: 0.75rem;
        }
        
        .title-location {
            color: var(--secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .title-meta {
            display: flex;
            gap: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
        }
        
        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.375rem;
            color: var(--secondary);
        }
        
        .meta-item i {
            font-size: 0.875rem;
        }
        
        /* Detail View */
        .detail-view {
            display: none;
        }
        
        .detail-header {
            background: var(--card-bg);
            padding: 2rem;
            border-bottom: 1px solid var(--border);
            margin: -2rem -2rem 2rem -2rem;
        }
        
        .detail-actions {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .owner-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }
        
        .owner-header {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .owner-avatar-large {
            width: 72px;
            height: 72px;
            border-radius: 12px;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .owner-info h2 {
            font-size: 1.5rem;
            margin-bottom: 0.375rem;
            color: var(--dark);
        }
        
        .owner-info p {
            color: var(--secondary);
            font-size: 1rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 1.25rem;
        }
        
        .detail-item {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 8px;
            border-left: 3px solid var(--primary);
        }
        
        .detail-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0.375rem;
        }
        
        .detail-value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        /* PDF Viewer */
        .pdf-section {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid var(--border);
            margin-top: 2rem;
        }
        
        .pdf-header-bar {
            background: #f8fafc;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--border);
        }
        
        .pdf-header-bar i {
            font-size: 1.25rem;
            color: #ef4444;
        }
        
        .pdf-viewer-container {
            min-height: 600px;
            background: #f8fafc;
        }
        
        .pdf-viewer-container iframe {
            width: 100%;
            height: 600px;
            border: none;
        }
        
        /* Loading State */
        .loading-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            color: var(--secondary);
        }
        
        .loading-state i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: var(--primary);
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            100% { transform: rotate(360deg); }
        }
        
        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 2rem;
            color: var(--secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: #e2e8f0;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
        }
        
        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border);
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .page-btn:hover:not(:disabled) {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .page-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Responsive Design */
        @media (max-width: 1200px) {
            .grid-view {
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .main-container {
                padding: 0 1rem;
            }
            
            .top-nav {
                padding: 1rem;
            }
            
            .nav-brand {
                font-size: 1.1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .dashboard-title {
                font-size: 1.25rem;
            }
            
            .control-bar {
                padding: 1rem;
            }
            
            .search-wrapper {
                min-width: 100%;
            }
            
            .filter-select {
                width: 100%;
            }
            
            .grid-view {
                grid-template-columns: 1fr;
            }
            
            .content-area {
                padding: 1.5rem;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .header-actions {
                width: 100%;
            }
            
            .header-actions .btn {
                flex: 1;
                justify-content: center;
                min-width: 120px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .user-info {
                padding: 0.375rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .owner-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            
            .owner-avatar-large {
                width: 64px;
                height: 64px;
                font-size: 1.25rem;
            }
        }
        
        /* Print Styles */
        @media print {
            .top-nav, .control-bar, .detail-actions, .btn {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .dashboard-card {
                box-shadow: none;
                border: none;
            }
            
            .owner-section {
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-brand">
                <a href="../portal.php" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none; color: inherit;">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <i class="fas fa-landmark"></i>
                <span>Land Title</span>
            </div>
            <div class="nav-actions">
                <div class="user-info">
                    <div class="user-avatar">DV</div>
                    <div>
                        <div style="font-weight: 500;">Doc Vic</div>
                        <div style="font-size: 0.8rem; color: var(--secondary);">President</div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Stats Cards -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-file-contract"></i></div>
                <div class="stat-value" id="totalRecords">0</div>
                <div class="stat-label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="totalOwners">0</div>
                <div class="stat-label">Land Owners</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-map-marked-alt"></i></div>
                <div class="stat-value" id="totalAreas">0</div>
                <div class="stat-label">Areas Covered</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value" id="recordsWithCost">0</div>
                <div class="stat-label">Valued Properties</div>
            </div>
        </div>

        <!-- Dashboard Card -->
        <div class="dashboard-card">
            <!-- List View -->
            <div id="listView">
                <div class="dashboard-header">
                    <div class="dashboard-title-section">
                        <h1 class="dashboard-title">Property Registry</h1>
                        <p class="dashboard-subtitle">Browse and manage land title records</p>
                    </div>
                    <div class="header-actions">
                        <button class="btn btn-secondary" onclick="window.location.href='new.php'">
                            <i class="fas fa-map"></i> View Map
                        </button>
                        <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                            <i class="fas fa-redo-alt"></i> Refresh
                        </button>
                        <button class="btn btn-secondary">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                </div>
                
                <div class="control-bar">
                    <div class="search-wrapper">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" class="search-input" id="searchInput" 
                               placeholder="Search by owner, location, reference number...">
                    </div>
                    <div class="filter-group">
                        <select class="filter-select" id="areaFilter">
                            <option value="all">All Areas</option>
                            <option value="lt">Lower Taway</option>
                            <option value="t">Taway</option>
                            <option value="ty">Tiayon</option>
                            <option value="up">Upper Pangi</option>
                            <option value="pa">Pangi</option>
                            <option value="go">Guito-an</option>
                            <option value="g">Gango</option>
                            <option value="s">Sanito</option>
                            <option value="p">Poblacion</option>
                            <option value="m">Manila</option>
                            <option value="r">Riverside</option>
                            <option value="sh">Sanghanan</option>
                            <option value="zc">Zamboanga City</option>
                            <option value="other">Other Areas</option>
                        </select>
                    </div>
                </div>
                
                <div class="content-area">
                    <div id="titleContainer"></div>
                </div>
            </div>

            <!-- Detail View -->
            <div id="detailView" class="detail-view">
                <div class="content-area">
                    <div class="detail-header">
                        <div class="detail-actions">
                            <button class="btn btn-secondary" onclick="goBackToList()">
                                <i class="fas fa-arrow-left"></i> Back to List
                            </button>
                            <button class="btn btn-secondary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print
                            </button>
                            <button class="btn btn-secondary">
                                <i class="fas fa-share-alt"></i> Share
                            </button>
                        </div>
                        <h1 class="dashboard-title" style="margin: 0;">Property Details</h1>
                    </div>
                    
                    <div class="owner-section">
                        <div class="owner-header">
                            <div class="owner-avatar-large" id="detailAvatar"></div>
                            <div class="owner-info">
                                <h2 id="detailOwner"></h2>
                                <p id="detailRef"></p>
                            </div>
                        </div>
                        
                        <div class="details-grid">
                            <div class="detail-item">
                                <div class="detail-label">Area</div>
                                <div class="detail-value" id="detailArea"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Lot Number</div>
                                <div class="detail-value" id="detailLot"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Reference Number</div>
                                <div class="detail-value" id="detailRefNo"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Folder Number</div>
                                <div class="detail-value" id="detailFolder"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Area Code</div>
                                <div class="detail-value" id="detailAreaCode"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Location</div>
                                <div class="detail-value" id="detailLocation"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Acquisition Cost</div>
                                <div class="detail-value" id="detailCost"></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">ARP/TD Number</div>
                                <div class="detail-value" id="detailArp"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="pdf-section">
                        <div class="pdf-header-bar">
                            <i class="fas fa-file-pdf"></i>
                            <span style="font-weight: 500;">Original Document</span>
                        </div>
                        <div class="pdf-viewer-container" id="pdfContainer"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentPage = 0;
        const limit = 50;
        let searchTimeout;

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadStats();
            loadTitles();
            
            document.getElementById('searchInput').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentPage = 0;
                    loadTitles();
                }, 500);
            });
            
            document.getElementById('areaFilter').addEventListener('change', () => {
                currentPage = 0;
                loadTitles();
            });
        });

        // Load Statistics
        async function loadStats() {
            try {
                const response = await fetch('fetch_data.php?action=stats');
                const result = await response.json();
                
                if (result.success) {
                    document.getElementById('totalRecords').textContent = result.data.total_records;
                    document.getElementById('totalOwners').textContent = result.data.total_owners;
                    document.getElementById('totalAreas').textContent = result.data.area_distribution.length;
                    document.getElementById('recordsWithCost').textContent = result.data.records_with_cost;
                }
            } catch (error) {
                console.error('Error loading stats:', error);
            }
        }

        // Load Titles
        async function loadTitles() {
            const container = document.getElementById('titleContainer');
            container.innerHTML = '<div class="loading-state"><i class="fas fa-spinner fa-spin"></i><p>Loading records...</p></div>';
            
            try {
                const search = document.getElementById('searchInput').value;
                const areaFilter = document.getElementById('areaFilter').value;
                const offset = currentPage * limit;

                const response = await fetch(`fetch_data.php?action=list&search=${encodeURIComponent(search)}&area_filter=${areaFilter}&limit=${limit}&offset=${offset}`);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    renderTitles(result.data);
                } else {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-folder-open"></i><p>No records found</p></div>';
                }
            } catch (error) {
                console.error('Error loading titles:', error);
                container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading records</p></div>';
            }
        }

        // Render Titles
        function renderTitles(titles) {
            const container = document.getElementById('titleContainer');
            let html = '<div class="grid-view">';
            
            titles.forEach(title => {
                const initials = title.land_owner.substring(0, 2).toUpperCase();
                html += `
                    <div class="title-card" onclick="showDetail(${title.id})">
                        <div class="title-card-header">
                            <span class="folder-badge">Folder #${title.folder_no}</span>
                            <span class="area-badge badge-${title.area_class}">${title.area_code}</span>
                        </div>
                        <div class="title-ref">${title.ref_no || 'N/A'}</div>
                        <div class="title-owner">${title.land_owner}</div>
                        <div class="title-location">
                            <i class="fas fa-map-marker-alt"></i>
                            ${title.location}
                        </div>
                        <div class="title-meta">
                            <div class="meta-item">
                                <i class="fas fa-ruler-combined"></i>
                                <span>${title.area || 'N/A'}</span>
                            </div>
                            ${title.acquisition_cost ? `
                            <div class="meta-item">
                                <i class="fas fa-tag"></i>
                                <span>₱${title.acquisition_cost}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.innerHTML = html;
        }

        // Show Detail
        async function showDetail(id) {
            try {
                const response = await fetch(`fetch_data.php?action=detail&id=${id}`);
                const result = await response.json();
                
                if (result.success) {
                    const data = result.data;
                    const initials = data.land_owner.substring(0, 2).toUpperCase();
                    
                    document.getElementById('detailAvatar').textContent = initials;
                    document.getElementById('detailOwner').textContent = data.land_owner;
                    document.getElementById('detailRef').textContent = `Reference: ${data.ref_no || 'N/A'}`;
                    document.getElementById('detailArea').textContent = data.area || 'N/A';
                    document.getElementById('detailLot').textContent = data.lot_no || 'N/A';
                    document.getElementById('detailRefNo').textContent = data.ref_no || 'N/A';
                    document.getElementById('detailFolder').textContent = data.folder_no || 'N/A';
                    document.getElementById('detailAreaCode').textContent = data.area_code || 'N/A';
                    document.getElementById('detailLocation').textContent = data.location;
                    document.getElementById('detailCost').textContent = data.acquisition_cost ? `₱${data.acquisition_cost}` : 'N/A';
                    document.getElementById('detailArp').textContent = data.arp_td_no || 'N/A';
                    
                    const pdfUrl = `Land_title/${data.pdf_filename || data.folder_no + '.pdf'}`;
                    document.getElementById('pdfContainer').innerHTML = `<iframe src="${pdfUrl}"></iframe>`;
                    
                    document.getElementById('listView').style.display = 'none';
                    document.getElementById('detailView').style.display = 'block';
                    window.scrollTo(0, 0);
                }
            } catch (error) {
                console.error('Error loading detail:', error);
                alert('Error loading property details');
            }
        }

        // Go Back to List
        function goBackToList() {
            document.getElementById('detailView').style.display = 'none';
            document.getElementById('listView').style.display = 'block';
            document.getElementById('pdfContainer').innerHTML = '';
            window.scrollTo(0, 0);
        }

        // Refresh Data
        function refreshData() {
            loadStats();
            loadTitles();
        }
    </script>
</body>
</html>