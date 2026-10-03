<?php
require_once '../electric/config/check-session.php';
require_once 'db_config.php';
// Create uploads directory if it doesn't exist
if (!file_exists('uploads')) {
    mkdir('uploads', 0777, true);
}
// Fetch all saved lots
$lots = [];
try {
    $stmt = $pdo->query("SELECT * FROM land_lots ORDER BY created_at DESC");
    $lots = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if ($e->getCode() == '42S02' || strpos($e->getMessage(), 'no such table') !== false) {
        $pdo->exec("CREATE TABLE land_lots (
            id INT AUTO_INCREMENT PRIMARY KEY,
            lot_name VARCHAR(255) NOT NULL,
            location VARCHAR(255) NOT NULL,
            owner_name VARCHAR(255),
            reference_number VARCHAR(255),
            document_path VARCHAR(255),
            description TEXT,
            coordinates TEXT NOT NULL,
            area DECIMAL(15,6) NOT NULL,
            perimeter DECIMAL(15,6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        $lots = [];
    } else {
        $lots = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Title Management System</title>
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
            --radius: 8px;
            --radius-lg: 12px;
            --sidebar-width: 380px;
            --sidebar-collapsed-width: 0px;
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
            overflow-x: hidden;
        }
     
        /* Header */
        .header {
            background: var(--card-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
     
        .header-container {
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }
     
        .brand-section {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }
     
        .header h1 {
            color: var(--dark);
            font-size: 1.5rem;
            font-weight: 600;
        }
     
        /* Buttons */
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 6px;
            border: 1px solid transparent;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            white-space: nowrap;
        }
     
        .btn-primary {
            background: var(--primary);
            color: white;
        }
     
        .btn-primary:hover {
            background: var(--primary-light);
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }
     
        .btn-secondary {
            background: white;
            color: var(--dark);
            border-color: var(--border);
        }
     
        .btn-secondary:hover {
            background: var(--light);
            border-color: var(--primary);
            color: var(--primary);
        }
     
        .btn-success {
            background: var(--success);
            color: white;
        }
     
        .btn-success:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
     
        .btn-warning {
            background: var(--warning);
            color: white;
        }
     
        .btn-warning:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
     
        .btn-danger {
            background: var(--danger);
            color: white;
        }
     
        .btn-danger:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
     
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
     
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
            border-radius: 4px;
        }
     
        .btn-icon {
            padding: 0.5rem;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
     
        /* Main Layout */
        .main-container {
            display: flex;
            height: calc(100vh - 72px);
            max-width: 1800px;
            margin: 0 auto;
            position: relative;
        }
     
        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--card-bg);
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            position: relative;
            z-index: 100;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: translateX(0);
        }
     
        .sidebar.collapsed {
            transform: translateX(-100%);
            width: 0;
            border-right: none;
        }
     
        .sidebar-toggle {
            position: absolute;
            top: 1rem;
            right: -1.25rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 50%;
            width: 2.5rem;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 101;
            box-shadow: var(--shadow);
            transition: all 0.2s ease;
        }
     
        .sidebar-toggle:hover {
            background: var(--light);
            transform: scale(1.05);
        }
     
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
            position: relative;
        }
     
        .sidebar-header h2 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
     
        .search-box {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            background: white;
            color: var(--dark);
            transition: all 0.2s ease;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2364748b' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 1rem center;
        }
     
        .search-box:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
     
        .lot-list {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
     
        .lot-card {
            background: white;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 0.75rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
     
        .lot-card:hover {
            border-color: var(--primary);
            box-shadow: var(--shadow);
            transform: translateY(-2px);
        }
     
        .lot-card.active {
            border-color: var(--primary);
            background: var(--primary-bg);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
     
        .lot-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 0.75rem;
        }
     
        .lot-card-title {
            font-weight: 600;
            font-size: 1rem;
            color: var(--dark);
            flex: 1;
        }
     
        .lot-card-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
     
        .lot-card-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--secondary);
            margin-bottom: 0.5rem;
        }
     
        .lot-card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
     
        .no-lots {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary);
        }
     
        .no-lots i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--border);
        }
     
        /* Document badge */
        .document-badge {
            background: var(--success);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-left: 0.5rem;
        }
     
        /* Map Area */
        .map-area {
            flex: 1;
            position: relative;
            background: #f8fafc;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
     
        .map-area.full-width {
            width: 100%;
        }
     
        #map {
            width: 100%;
            height: 100%;
        }
     
        /* Floating Sidebar Toggle for collapsed state */
        .floating-sidebar-toggle {
            position: absolute;
            top: 1.5rem;
            left: 1.5rem;
            background: white;
            border: 1px solid var(--border);
            border-radius: 6px;
            width: 3rem;
            height: 3rem;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 100;
            box-shadow: var(--shadow-lg);
            transition: all 0.2s ease;
        }
     
        .floating-sidebar-toggle:hover {
            background: var(--light);
            transform: scale(1.05);
        }
     
        /* Map Controls */
        .map-controls {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: white;
            border-radius: 8px;
            padding: 1.25rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            z-index: 100;
            min-width: 200px;
        }
     
        .map-controls h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
     
        .control-group {
            margin-bottom: 1rem;
        }
     
        .control-group label {
            display: block;
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--secondary);
            margin-bottom: 0.375rem;
        }
     
        .control-group select,
        .control-group input[type="color"] {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: 4px;
            font-size: 0.875rem;
            background: white;
            color: var(--dark);
        }
     
        .control-group input[type="color"] {
            height: 2.5rem;
            cursor: pointer;
            padding: 0.25rem;
        }
     
        /* Info Panel */
        .info-panel {
            position: absolute;
            bottom: 1.5rem;
            right: 1.5rem;
            left: 1.5rem;
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border);
            z-index: 100;
            max-width: 400px;
            margin-left: auto;
        }
     
        .info-panel h3 {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
     
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
     
        .info-item {
            display: flex;
            flex-direction: column;
        }
     
        .info-label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--secondary);
            margin-bottom: 0.25rem;
        }
     
        .info-value {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--dark);
        }
     
        .document-view {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
        }
     
        /* Modals */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            backdrop-filter: blur(4px);
        }
     
        .modal-content {
            background: white;
            margin: 60px auto;
            padding: 0;
            border-radius: 12px;
            max-width: 500px;
            box-shadow: var(--shadow-lg);
            animation: modalSlide 0.3s ease;
        }
     
        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
     
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border);
        }
     
        .modal-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark);
        }
     
        .close {
            font-size: 1.5rem;
            font-weight: 300;
            color: var(--secondary);
            cursor: pointer;
            width: 2rem;
            height: 2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
     
        .close:hover {
            background: var(--light);
            color: var(--dark);
        }
     
        .modal-body {
            padding: 1.5rem;
            max-height: 70vh;
            overflow-y: auto;
        }
     
        .form-group {
            margin-bottom: 1.25rem;
        }
     
        .form-group label {
            display: block;
            font-weight: 500;
            color: var(--dark);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
     
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            background: white;
            color: var(--dark);
        }
     
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
     
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
     
        .form-group input[type="file"] {
            padding: 0.5rem;
        }
     
        /* File upload preview */
        .file-upload-area {
            border: 2px dashed var(--border);
            border-radius: 6px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }
     
        .file-upload-area:hover {
            border-color: var(--primary);
            background: var(--primary-bg);
        }
     
        .file-upload-area.dragover {
            border-color: var(--primary);
            background: var(--primary-bg);
        }
     
        .file-preview {
            margin-top: 1rem;
            padding: 1rem;
            background: var(--light);
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
     
        .file-preview i {
            font-size: 2rem;
            color: var(--danger);
        }
     
        .file-info {
            flex: 1;
        }
     
        .file-name {
            font-weight: 500;
            color: var(--dark);
        }
     
        .file-size {
            font-size: 0.75rem;
            color: var(--secondary);
        }
     
        .remove-file {
            color: var(--danger);
            cursor: pointer;
        }
     
        /* Document Modal */
        .document-modal .modal-content {
            max-width: 800px;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }
     
        .document-viewer {
            flex: 1;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
        }
     
        .document-actions {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border);
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }
     
        /* Quick Stats Bar */
        .quick-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }
     
        .stat-badge {
            background: var(--light);
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
     
        .stat-badge i {
            color: var(--primary);
        }
     
        /* Responsive Design */
        @media (max-width: 1200px) {
            .sidebar {
                position: fixed;
                height: calc(100vh - 72px);
                background: white;
                box-shadow: var(--shadow-lg);
                z-index: 200;
            }
         
            .floating-sidebar-toggle {
                display: flex;
            }
         
            .map-area.full-width {
                width: 100%;
            }
         
            .lot-list {
                display: block;
            }
         
            .lot-card {
                min-width: auto;
            }
        }
     
        @media (max-width: 768px) {
            .header {
                padding: 1rem;
            }
         
            .header-container {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
         
            .brand-section {
                justify-content: space-between;
            }
         
            .map-controls {
                top: auto;
                bottom: 1.5rem;
                right: 1.5rem;
                left: 1.5rem;
            }
         
            .info-panel {
                bottom: 180px;
                right: 1.5rem;
                left: 1.5rem;
            }
         
            .modal-content {
                margin: 20px;
                width: calc(100% - 40px);
            }
         
            .quick-stats {
                flex-direction: column;
            }
        }
     
        @media (max-width: 480px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
         
            .btn {
                padding: 0.5rem 0.75rem;
                font-size: 0.75rem;
            }
         
            .lot-card {
                min-width: 280px;
            }
         
            .header-actions {
                flex-wrap: wrap;
            }
         
            .header-actions .btn {
                flex: 1;
                min-width: 140px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-container">
            <div class="brand-section">
                <button class="btn btn-secondary" onclick="window.location.href='dashboard.php'">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <h1>Land Title Management</h1>
            </div>
            <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;" class="header-actions">
                <button class="btn btn-warning" onclick="startDrawing()" id="drawBtn">
                    <i class="fas fa-draw-polygon"></i> Draw Lot
                </button>
                <button class="btn btn-primary" onclick="editBoundary()" id="editBtn" disabled>
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-success" onclick="saveLotModal()" id="saveBtn" disabled>
                    <i class="fas fa-save"></i> Save
                </button>
                <button class="btn btn-secondary" onclick="toggleSidebar()" id="sidebarToggleBtn">
                    <i class="fas fa-list"></i> Lots List
                </button>
            </div>
        </div>
    </div>
    <!-- Main Container -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-chevron-left" id="sidebarToggleIcon"></i>
            </div>
         
            <div class="sidebar-header">
                <h2>
                    Saved Lots
                    <span style="color: var(--secondary); font-weight: normal;">(<?php echo count($lots); ?>)</span>
                    <button class="btn btn-icon btn-secondary" onclick="refreshLots()" title="Refresh">
                        <i class="fas fa-redo-alt"></i>
                    </button>
                </h2>
             
                <div class="quick-stats">
                    <div class="stat-badge">
                        <i class="fas fa-ruler-combined"></i>
                        <span>Total: <?php echo count($lots); ?> lots</span>
                    </div>
                    <?php if (!empty($lots)): ?>
                    <div class="stat-badge">
                        <i class="fas fa-map"></i>
                        <span>Avg: <?php echo number_format(array_sum(array_column($lots, 'area')) / count($lots), 1); ?> sq m</span>
                    </div>
                    <?php endif; ?>
                </div>
             
                <input type="text" class="search-box" id="searchBox"
                       placeholder="Search lots by name, location, or owner..."
                       onkeyup="filterLots()">
            </div>
         
            <div class="lot-list" id="lotList">
                <?php if (empty($lots)): ?>
                    <div class="no-lots">
                        <i class="fas fa-map-marked-alt"></i>
                        <p>No lots saved yet.</p>
                        <p>Draw a boundary on the map to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($lots as $lot): ?>
                        <div class="lot-card" data-lot-id="<?php echo $lot['id']; ?>"
                             onclick="selectLot(<?php echo $lot['id']; ?>)">
                            <div class="lot-card-header">
                                <div class="lot-card-title">
                                    <?php echo htmlspecialchars($lot['lot_name']); ?>
                                    <?php if (!empty($lot['document_path'])): ?>
                                        <span class="document-badge" title="Has document">
                                            <i class="fas fa-file-pdf"></i> Document
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="lot-card-badge">
                                    <?php echo number_format($lot['area'], 1); ?> sq m
                                </div>
                            </div>
                            <div class="lot-card-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($lot['location']); ?></span>
                            </div>
                            <?php if (!empty($lot['owner_name'])): ?>
                            <div class="lot-card-info">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($lot['owner_name']); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="lot-card-actions">
                                <button class="btn btn-danger btn-sm"
                                        onclick="event.stopPropagation(); deleteLot(<?php echo $lot['id']; ?>)"
                                        title="Delete lot">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php if (!empty($lot['document_path'])): ?>
                                <button class="btn btn-primary btn-sm"
                                        onclick="event.stopPropagation(); viewDocument('<?php echo htmlspecialchars($lot['document_path']); ?>', '<?php echo htmlspecialchars($lot['lot_name']); ?>')"
                                        title="View document">
                                    <i class="fas fa-eye"></i> View Doc
                                </button>
                                <?php endif; ?>
                                <button class="btn btn-secondary btn-sm"
                                        onclick="event.stopPropagation(); zoomToLot(<?php echo $lot['id']; ?>)"
                                        title="Zoom to lot">
                                    <i class="fas fa-search-plus"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Floating Toggle for Mobile -->
        <div class="floating-sidebar-toggle" onclick="toggleSidebar()" id="floatingToggle">
            <i class="fas fa-bars"></i>
        </div>
        <!-- Map Area -->
        <div class="map-area" id="mapArea">
            <div id="map"></div>
         
            <!-- Map Controls -->
            <div class="map-controls">
                <h4>Map Controls</h4>
                <div class="control-group">
                    <label>Map Type</label>
                    <select id="mapType" onchange="changeMapType()">
                        <option value="roadmap">Roadmap</option>
                        <option value="satellite" selected>Satellite</option>
                        <option value="hybrid">Hybrid</option>
                        <option value="terrain">Terrain</option>
                    </select>
                </div>
                <div class="control-group">
                    <label>Boundary Color</label>
                    <input type="color" id="boundaryColor" value="#FFFF00" onchange="updateBoundaryColor()">
                </div>
                <div class="control-group">
                    <label>Fill Color</label>
                    <input type="color" id="fillColor" value="#FFFF99" onchange="updateFillColor()">
                </div>
                <div class="control-group">
                    <label>Drawing Mode</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <button class="btn btn-secondary btn-sm" onclick="startDrawing()" style="flex: 1;">
                            <i class="fas fa-draw-polygon"></i> Draw
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="clearDrawing()" style="flex: 1;">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>
            <!-- Info Panel -->
            <div class="info-panel" id="infoPanel" style="display: none;">
                <h3 id="infoPanelTitle">
                    <span id="lotTitle">Current Lot</span>
                    <button class="btn btn-secondary btn-sm" onclick="deselectLot()">
                        <i class="fas fa-times"></i>
                    </button>
                </h3>
                <div class="info-grid">
                    <div class="info-item">
                        <span class="info-label">Area</span>
                        <span class="info-value" id="areaValue">0 sq meters</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Perimeter</span>
                        <span class="info-value" id="perimeterValue">0 meters</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Points</span>
                        <span class="info-value" id="pointsCount">0</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Status</span>
                        <span class="info-value" id="lotStatus">New</span>
                    </div>
                </div>
                <div class="document-view" id="documentViewSection" style="display: none;">
                    <button class="btn btn-primary" onclick="viewCurrentDocument()">
                        <i class="fas fa-file-pdf"></i> View Document
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Save Lot Modal -->
    <div id="saveLotModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Save Lot Information</h2>
                <span class="close" onclick="closeSaveLotModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="saveLotForm" enctype="multipart/form-data">
                    <input type="hidden" id="lotId" name="lot_id">
                    <div class="form-group">
                        <label>Lot Name/Number *</label>
                        <input type="text" id="lotName" name="lot_name" required
                               placeholder="e.g., LOT 728-B-2-E">
                    </div>
                    <div class="form-group">
                        <label>Location *</label>
                        <input type="text" id="lotLocation" name="location" required
                               placeholder="e.g., Brgy. Sanito, Ipil, Zamboanga Sibugay">
                    </div>
                    <div class="form-group">
                        <label>Owner Name</label>
                        <input type="text" id="ownerName" name="owner_name"
                               placeholder="Property owner name">
                    </div>
                    <div class="form-group">
                        <label>Reference Number</label>
                        <input type="text" id="referenceNumber" name="reference_number"
                               placeholder="Title or reference number">
                    </div>
                    <div class="form-group">
                        <label>Document (PDF)</label>
                        <div class="file-upload-area" id="fileUploadArea" onclick="document.getElementById('documentFile').click()">
                            <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--primary); margin-bottom: 1rem;"></i>
                            <p>Click to upload or drag and drop</p>
                            <p style="font-size: 0.75rem; color: var(--secondary);">PDF files only (Max 10MB)</p>
                        </div>
                        <input type="file" id="documentFile" name="document_file" accept=".pdf" style="display: none;"
                               onchange="handleFileSelect(this)">
                        <div id="filePreview" style="display: none;" class="file-preview">
                            <i class="fas fa-file-pdf"></i>
                            <div class="file-info">
                                <div class="file-name" id="fileName"></div>
                                <div class="file-size" id="fileSize"></div>
                            </div>
                            <i class="fas fa-times remove-file" onclick="removeSelectedFile()"></i>
                        </div>
                        <div style="margin-top: 0.5rem;">
                            <small>Or enter URL:</small>
                            <input type="url" id="documentUrl" name="document_url"
                                   placeholder="https://example.com/document.pdf" style="width: 100%; margin-top: 0.25rem;">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea id="lotDescription" name="description"
                                  placeholder="Additional notes about this lot..."></textarea>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                        <button type="button" class="btn btn-secondary" onclick="closeSaveLotModal()">
                            Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Save Lot
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Document Viewer Modal -->
    <div id="documentModal" class="modal document-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="documentTitle">Document Viewer</h2>
                <span class="close" onclick="closeDocumentModal()">&times;</span>
            </div>
            <div class="document-viewer" id="documentViewer">
                <div style="text-align: center; color: var(--secondary);">
                    <i class="fas fa-file-pdf" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>Loading document...</p>
                </div>
            </div>
            <div class="document-actions">
                <button class="btn btn-secondary" onclick="closeDocumentModal()">Close</button>
                <button class="btn btn-primary" id="downloadBtn" style="display: none;">
                    <i class="fas fa-download"></i> Download
                </button>
                <button class="btn btn-success" id="printBtn" style="display: none;">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
    <script>
        let map;
        let drawingManager;
        let currentPolygon = null;
        let currentLotId = null;
        let allLotPolygons = {};
        let lotsData = <?php echo json_encode($lots); ?>;
        let isEditing = false;
        let isSidebarCollapsed = false;
        let currentDocumentPath = '';
        let selectedFile = null;
        function initMap() {
            const center = { lat: 7.418333, lng: 122.578889 };
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 16,
                center: center,
                mapTypeId: 'satellite',
                mapTypeControl: false,
                streetViewControl: true,
                fullscreenControl: true,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.LEFT_TOP
                }
            });
            // Add custom map type control
            const mapTypeControlDiv = document.createElement('div');
            const mapTypeControl = new MapTypeControl(mapTypeControlDiv, map);
            mapTypeControlDiv.index = 1;
            map.controls[google.maps.ControlPosition.TOP_RIGHT].push(mapTypeControlDiv);
            drawingManager = new google.maps.drawing.DrawingManager({
                drawingMode: null,
                drawingControl: false,
                polygonOptions: {
                    strokeColor: '#FFFF00',
                    strokeOpacity: 1.0,
                    strokeWeight: 3,
                    fillColor: '#FFFF99',
                    fillOpacity: 0.3,
                    editable: false,
                    draggable: false
                }
            });
            drawingManager.setMap(map);
            google.maps.event.addListener(drawingManager, 'polygoncomplete', function(polygon) {
                if (currentPolygon) {
                    if (currentLotId) {
                        // If editing existing lot, keep it on map but clear selection
                        deselectLot();
                    } else {
                        // If new drawing, remove previous
                        currentPolygon.setMap(null);
                    }
                }
                // Reset form for new lot
                document.getElementById('saveLotForm').reset();
                document.getElementById('lotId').value = '';
                removeSelectedFile();
                document.getElementById('documentUrl').value = '';
                document.getElementById('modalTitle').textContent = 'Save Lot Information';
             
                currentPolygon = polygon;
                currentLotId = null;
                drawingManager.setDrawingMode(null);
             
                // Enable controls
                document.getElementById('editBtn').disabled = false;
                document.getElementById('saveBtn').disabled = false;
                document.getElementById('drawBtn').innerHTML = '<i class="fas fa-plus"></i> Add Another Lot';
             
                // Show info panel
                updateInfo();
                document.getElementById('infoPanel').style.display = 'block';
                document.getElementById('infoPanelTitle').innerHTML = '<span id="lotTitle">New Lot</span> <button class="btn btn-secondary btn-sm" onclick="deselectLot()"><i class="fas fa-times"></i></button>';
                document.getElementById('lotStatus').textContent = 'New (Unsaved)';
                document.getElementById('documentViewSection').style.display = 'none';
                // Clear active selection in sidebar
                clearActiveSelection();
                // Add listeners for updates
                google.maps.event.addListener(polygon.getPath(), 'set_at', updateInfo);
                google.maps.event.addListener(polygon.getPath(), 'insert_at', updateInfo);
            });
            // Setup drag and drop for file upload
            setupFileUpload();
            // Load all saved lots on map
            loadAllLots();
         
            // Center map on all lots if they exist
            if (lotsData.length > 0) {
                setTimeout(() => {
                    const bounds = new google.maps.LatLngBounds();
                    lotsData.forEach(lot => {
                        const coords = JSON.parse(lot.coordinates);
                        coords.forEach(coord => {
                            bounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
                        });
                    });
                    map.fitBounds(bounds);
                }, 500);
            }
        }
        function setupFileUpload() {
            const uploadArea = document.getElementById('fileUploadArea');
         
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
         
            uploadArea.addEventListener('dragleave', function(e) {
                this.classList.remove('dragover');
            });
         
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
             
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    handleFileSelect({ files: files });
                }
            });
        }
        function handleFileSelect(input) {
            const file = input.files[0];
            if (!file) return;
         
            // Check if file is PDF
            if (file.type !== 'application/pdf') {
                alert('Please upload a PDF file only.');
                return;
            }
         
            // Check file size (20MB max)
            if (file.size > 20 * 1024 * 1024) {
                alert('File size must be less than 20MB.');
                return;
            }
         
            selectedFile = file;
         
            // Show preview
            document.getElementById('filePreview').style.display = 'flex';
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatFileSize(file.size);
         
            // Clear URL input
            document.getElementById('documentUrl').value = '';
        }
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        function removeSelectedFile() {
            selectedFile = null;
            document.getElementById('filePreview').style.display = 'none';
            document.getElementById('documentFile').value = '';
        }
        // Custom Map Type Control
        function MapTypeControl(controlDiv, map) {
            const controlUI = document.createElement('div');
            controlUI.style.backgroundColor = '#fff';
            controlUI.style.borderRadius = '4px';
            controlUI.style.boxShadow = '0 2px 6px rgba(0,0,0,0.1)';
            controlUI.style.cursor = 'pointer';
            controlUI.style.marginTop = '10px';
            controlUI.style.textAlign = 'center';
            controlUI.title = 'Click to change map type';
            controlDiv.appendChild(controlUI);
            const controlText = document.createElement('div');
            controlText.style.color = 'rgb(25,25,25)';
            controlText.style.fontFamily = 'Roboto,Arial,sans-serif';
            controlText.style.fontSize = '12px';
            controlText.style.lineHeight = '28px';
            controlText.style.padding = '0 10px';
            controlText.innerHTML = 'Satellite';
            controlUI.appendChild(controlText);
            const mapTypes = ['roadmap', 'satellite', 'hybrid', 'terrain'];
            const mapTypeLabels = ['Roadmap', 'Satellite', 'Hybrid', 'Terrain'];
            let currentIndex = 1;
            controlUI.addEventListener('click', function() {
                currentIndex = (currentIndex + 1) % mapTypes.length;
                map.setMapTypeId(mapTypes[currentIndex]);
                controlText.innerHTML = mapTypeLabels[currentIndex];
                document.getElementById('mapType').value = mapTypes[currentIndex];
            });
        }
        function loadAllLots() {
            lotsData.forEach(lot => {
                try {
                    const coordinates = JSON.parse(lot.coordinates);
                 
                    const polygon = new google.maps.Polygon({
                        paths: coordinates,
                        strokeColor: '#888888',
                        strokeOpacity: 0.6,
                        strokeWeight: 2,
                        fillColor: '#CCCCCC',
                        fillOpacity: 0.2,
                        editable: false,
                        draggable: false,
                        clickable: true
                    });
                    polygon.setMap(map);
                    allLotPolygons[lot.id] = polygon;
                    // Add click listener
                    google.maps.event.addListener(polygon, 'click', function() {
                        selectLot(lot.id);
                    });
                    // Add hover effects
                    google.maps.event.addListener(polygon, 'mouseover', function() {
                        if (!currentLotId || currentLotId != lot.id) {
                            polygon.setOptions({
                                strokeWeight: 3,
                                strokeOpacity: 0.8
                            });
                        }
                    });
                    google.maps.event.addListener(polygon, 'mouseout', function() {
                        if (!currentLotId || currentLotId != lot.id) {
                            polygon.setOptions({
                                strokeWeight: 2,
                                strokeOpacity: 0.6
                            });
                        }
                    });
                } catch (e) {
                    console.error('Error loading lot:', lot.id, e);
                }
            });
        }
        function selectLot(lotId) {
            // If already editing, stop editing first
            if (isEditing) {
                editBoundary();
            }
            // If clicking same lot, deselect it
            if (currentLotId === lotId) {
                deselectLot();
                return;
            }
            // Find lot data
            const lot = lotsData.find(l => l.id == lotId);
            if (!lot) return;
            // Remove current polygon if it's a new drawing
            if (currentPolygon && !currentLotId) {
                currentPolygon.setMap(null);
                currentPolygon = null;
            }
            // Reset all polygons to default style
            Object.keys(allLotPolygons).forEach(id => {
                allLotPolygons[id].setOptions({
                    strokeColor: '#888888',
                    strokeOpacity: 0.6,
                    strokeWeight: 2,
                    fillColor: '#CCCCCC',
                    fillOpacity: 0.2,
                    editable: false,
                    draggable: false
                });
            });
            // Highlight selected polygon
            const selectedPolygon = allLotPolygons[lotId];
            selectedPolygon.setOptions({
                strokeColor: '#FFFF00',
                strokeOpacity: 1.0,
                strokeWeight: 3,
                fillColor: '#FFFF99',
                fillOpacity: 0.3
            });
            currentPolygon = selectedPolygon;
            currentLotId = lotId;
            currentDocumentPath = lot.document_path || '';
            // Fit map to polygon bounds
            const coordinates = JSON.parse(lot.coordinates);
            const bounds = new google.maps.LatLngBounds();
            coordinates.forEach(coord => {
                bounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
            });
            map.fitBounds(bounds);
            // Update UI
            document.getElementById('editBtn').disabled = false;
            document.getElementById('saveBtn').disabled = false;
            document.getElementById('infoPanel').style.display = 'block';
            document.getElementById('lotTitle').textContent = lot.lot_name;
            document.getElementById('lotStatus').textContent = 'Saved';
         
            // Show/hide document button
            if (lot.document_path) {
                document.getElementById('documentViewSection').style.display = 'block';
            } else {
                document.getElementById('documentViewSection').style.display = 'none';
            }
         
            updateInfo();
            // Pre-fill form with lot data
            document.getElementById('lotId').value = lot.id;
            document.getElementById('lotName').value = lot.lot_name;
            document.getElementById('lotLocation').value = lot.location;
            document.getElementById('ownerName').value = lot.owner_name || '';
            document.getElementById('referenceNumber').value = lot.reference_number || '';
            document.getElementById('documentUrl').value = lot.document_path || '';
            document.getElementById('lotDescription').value = lot.description || '';
            document.getElementById('modalTitle').textContent = 'Edit Lot Information';
            // Clear file selection
            removeSelectedFile();
            // Highlight active card
            clearActiveSelection();
            const lotCard = document.querySelector(`[data-lot-id="${lotId}"]`);
            if (lotCard) {
                lotCard.classList.add('active');
                if (!isSidebarCollapsed) {
                    lotCard.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
            // Add event listeners for editing
            google.maps.event.clearListeners(selectedPolygon.getPath(), 'set_at');
            google.maps.event.clearListeners(selectedPolygon.getPath(), 'insert_at');
            google.maps.event.addListener(selectedPolygon.getPath(), 'set_at', updateInfo);
            google.maps.event.addListener(selectedPolygon.getPath(), 'insert_at', updateInfo);
        }
        function zoomToLot(lotId) {
            const lot = lotsData.find(l => l.id == lotId);
            if (!lot) return;
            const coordinates = JSON.parse(lot.coordinates);
            const bounds = new google.maps.LatLngBounds();
            coordinates.forEach(coord => {
                bounds.extend(new google.maps.LatLng(coord.lat, coord.lng));
            });
         
            map.fitBounds(bounds);
            map.setZoom(map.getZoom() + 1); // Zoom in a bit more
         
            // Select the lot
            selectLot(lotId);
        }
        function deselectLot() {
            if (currentPolygon) {
                if (currentLotId && allLotPolygons[currentLotId]) {
                    allLotPolygons[currentLotId].setOptions({
                        strokeColor: '#888888',
                        strokeOpacity: 0.6,
                        strokeWeight: 2,
                        fillColor: '#CCCCCC',
                        fillOpacity: 0.2,
                        editable: false,
                        draggable: false
                    });
                } else if (!currentLotId) {
                    currentPolygon.setMap(null);
                }
            }
            currentPolygon = null;
            currentLotId = null;
            currentDocumentPath = '';
            document.getElementById('editBtn').disabled = true;
            document.getElementById('saveBtn').disabled = true;
            document.getElementById('infoPanel').style.display = 'none';
            document.getElementById('drawBtn').innerHTML = '<i class="fas fa-draw-polygon"></i> Draw Lot';
            clearActiveSelection();
            isEditing = false;
            document.getElementById('editBtn').innerHTML = '<i class="fas fa-edit"></i> Edit';
            // Reset form on deselect
            document.getElementById('saveLotForm').reset();
            document.getElementById('lotId').value = '';
            removeSelectedFile();
            document.getElementById('documentUrl').value = '';
            document.getElementById('modalTitle').textContent = 'Save Lot Information';
        }
        function clearActiveSelection() {
            document.querySelectorAll('.lot-card').forEach(card => {
                card.classList.remove('active');
            });
        }
        function startDrawing() {
            if (currentPolygon && !confirm('This will clear the current selection. Continue?')) {
                return;
            }
            deselectLot();
            drawingManager.setDrawingMode(google.maps.drawing.OverlayType.POLYGON);
        }
        function clearDrawing() {
            if (currentPolygon && !currentLotId) {
                currentPolygon.setMap(null);
                currentPolygon = null;
                document.getElementById('infoPanel').style.display = 'none';
            } else if (currentLotId) {
                deselectLot();
            }
        }
        function editBoundary() {
            if (!currentPolygon) return;
         
            isEditing = !isEditing;
            currentPolygon.setEditable(isEditing);
            currentPolygon.setDraggable(isEditing);
         
            const editBtn = document.getElementById('editBtn');
            if (isEditing) {
                editBtn.innerHTML = '<i class="fas fa-stop"></i> Stop';
                editBtn.classList.remove('btn-primary');
                editBtn.classList.add('btn-warning');
                document.getElementById('lotStatus').textContent = 'Editing';
            } else {
                editBtn.innerHTML = '<i class="fas fa-edit"></i> Edit';
                editBtn.classList.remove('btn-warning');
                editBtn.classList.add('btn-primary');
                document.getElementById('lotStatus').textContent = 'Saved';
            }
        }
        function updateInfo() {
            if (!currentPolygon) return;
            const path = currentPolygon.getPath();
            const area = google.maps.geometry.spherical.computeArea(path);
            const perimeter = google.maps.geometry.spherical.computeLength(path);
            document.getElementById('areaValue').textContent = area.toFixed(2) + ' sq m (' + (area / 10000).toFixed(4) + ' ha)';
            document.getElementById('perimeterValue').textContent = perimeter.toFixed(2) + ' meters';
            document.getElementById('pointsCount').textContent = path.getLength();
        }
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const mapArea = document.getElementById('mapArea');
            const toggleIcon = document.getElementById('sidebarToggleIcon');
            const floatingToggle = document.getElementById('floatingToggle');
         
            isSidebarCollapsed = !isSidebarCollapsed;
         
            if (isSidebarCollapsed) {
                sidebar.classList.add('collapsed');
                mapArea.classList.add('full-width');
                toggleIcon.classList.remove('fa-chevron-left');
                toggleIcon.classList.add('fa-chevron-right');
                floatingToggle.style.display = 'flex';
            } else {
                sidebar.classList.remove('collapsed');
                mapArea.classList.remove('full-width');
                toggleIcon.classList.remove('fa-chevron-right');
                toggleIcon.classList.add('fa-chevron-left');
                floatingToggle.style.display = 'none';
            }
        }
        function saveLotModal() {
            if (!currentPolygon) {
                alert('Please draw or select a boundary first!');
                return;
            }
            document.getElementById('saveLotModal').style.display = 'block';
        }
        function closeSaveLotModal() {
            document.getElementById('saveLotModal').style.display = 'none';
            removeSelectedFile();
        }
document.getElementById('saveLotForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!currentPolygon) {
        alert('No boundary to save!');
        return;
    }
    const path = currentPolygon.getPath();
    const coordinates = [];
    
    for (let i = 0; i < path.getLength(); i++) {
        const point = path.getAt(i);
        coordinates.push({
            lat: point.lat(),
            lng: point.lng()
        });
    }
    const area = google.maps.geometry.spherical.computeArea(path);
    const perimeter = google.maps.geometry.spherical.computeLength(path);
    const formData = new FormData(this);
    formData.append('coordinates', JSON.stringify(coordinates));
    formData.append('area', area);
    formData.append('perimeter', perimeter);
    
    // FIX: Append the selected file if one exists
    if (selectedFile) {
        formData.append('document_file', selectedFile);
    } else {
        // Append document URL if no file is selected
        const documentUrl = document.getElementById('documentUrl').value;
        if (documentUrl) {
            formData.append('document_url', documentUrl);
        }
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
    submitBtn.disabled = true;
    fetch('save_lot.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log(data); // Added for debugging
        if (data.success) {
            alert('Lot saved successfully!');
            closeSaveLotModal();
            location.reload();
        } else {
            alert('Error saving lot: ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error saving lot. Please try again.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
        function deleteLot(lotId) {
            if (!confirm('Are you sure you want to delete this lot? This action cannot be undone.')) {
                return;
            }
            fetch('delete_lot.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'id=' + lotId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Lot deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting lot: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting lot. Please try again.');
            });
        }
        function changeMapType() {
            const mapType = document.getElementById('mapType').value;
            map.setMapTypeId(mapType);
        }
        function updateBoundaryColor() {
            const color = document.getElementById('boundaryColor').value;
            if (currentPolygon) {
                currentPolygon.setOptions({ strokeColor: color });
            }
            drawingManager.setOptions({
                polygonOptions: {
                    ...drawingManager.get('polygonOptions'),
                    strokeColor: color
                }
            });
        }
        function updateFillColor() {
            const color = document.getElementById('fillColor').value;
            if (currentPolygon) {
                currentPolygon.setOptions({ fillColor: color });
            }
            drawingManager.setOptions({
                polygonOptions: {
                    ...drawingManager.get('polygonOptions'),
                    fillColor: color
                }
            });
        }
        function filterLots() {
            const searchValue = document.getElementById('searchBox').value.toLowerCase();
            const lotCards = document.querySelectorAll('.lot-card');
            let visibleCount = 0;
         
            lotCards.forEach(card => {
                const text = card.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            // Show no results message
            const noResults = document.querySelector('.no-lots');
            if (visibleCount === 0 && !noResults) {
                const lotList = document.getElementById('lotList');
                const noResultsDiv = document.createElement('div');
                noResultsDiv.className = 'no-lots';
                noResultsDiv.innerHTML = `
                    <i class="fas fa-search"></i>
                    <p>No lots found matching "${searchValue}"</p>
                    <p>Try a different search term</p>
                `;
                lotList.appendChild(noResultsDiv);
            } else if (visibleCount > 0 && noResults) {
                noResults.remove();
            }
        }
        function viewCurrentDocument() {
            if (!currentDocumentPath) {
                alert('No document available for this lot.');
                return;
            }
            const lotName = document.getElementById('lotName').value || 'Lot Document';
            viewDocument(currentDocumentPath, lotName);
        }
        function viewDocument(documentPath, title) {
            document.getElementById('documentModal').style.display = 'block';
            document.getElementById('documentTitle').textContent = title + ' - Document';
         
            const viewer = document.getElementById('documentViewer');
            const downloadBtn = document.getElementById('downloadBtn');
            const printBtn = document.getElementById('printBtn');
         
            // Check if it's a local file or URL
            if (documentPath.startsWith('uploads/')) {
                // Local file
                viewer.innerHTML = `
                    <iframe src="${documentPath}"
                            style="width:100%; height:100%; border:none;"
                            id="pdfViewer"></iframe>
                `;
                downloadBtn.style.display = 'inline-flex';
                downloadBtn.onclick = function() {
                    const link = document.createElement('a');
                    link.href = documentPath;
                    link.download = title + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                };
                printBtn.style.display = 'inline-flex';
                printBtn.onclick = function() {
                    const iframe = document.getElementById('pdfViewer');
                    if (iframe.contentWindow) {
                        iframe.contentWindow.print();
                    }
                };
            } else if (documentPath.toLowerCase().endsWith('.pdf')) {
                // External PDF URL
                viewer.innerHTML = `
                    <iframe src="${documentPath}"
                            style="width:100%; height:100%; border:none;"
                            id="pdfViewer"></iframe>
                `;
                downloadBtn.style.display = 'inline-flex';
                downloadBtn.onclick = function() {
                    const link = document.createElement('a');
                    link.href = documentPath;
                    link.download = title + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                };
                printBtn.style.display = 'inline-flex';
                printBtn.onclick = function() {
                    const iframe = document.getElementById('pdfViewer');
                    if (iframe.contentWindow) {
                        iframe.contentWindow.print();
                    }
                };
            } else {
                viewer.innerHTML = `
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: var(--warning); margin-bottom: 1rem;"></i>
                        <p>Document preview not available for this file type.</p>
                        <button class="btn btn-primary" onclick="window.open('${documentPath}', '_blank')">
                            <i class="fas fa-external-link-alt"></i> Open Document
                        </button>
                    </div>
                `;
                downloadBtn.style.display = 'none';
                printBtn.style.display = 'none';
            }
        }
        function refreshLots() {
            location.reload();
        }
        function closeDocumentModal() {
            document.getElementById('documentModal').style.display = 'none';
            document.getElementById('documentViewer').innerHTML = `
                <div style="text-align: center; color: var(--secondary);">
                    <i class="fas fa-file-pdf" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                    <p>Loading document...</p>
                </div>
            `;
            document.getElementById('downloadBtn').style.display = 'none';
            document.getElementById('printBtn').style.display = 'none';
        }
        window.onclick = function(event) {
            if (event.target == document.getElementById('saveLotModal')) {
                closeSaveLotModal();
            }
            if (event.target == document.getElementById('documentModal')) {
                closeDocumentModal();
            }
        }
        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Escape key to deselect
            if (e.key === 'Escape') {
                if (isEditing) {
                    editBoundary();
                } else {
                    deselectLot();
                }
            }
            // Ctrl+D to start drawing
            if (e.ctrlKey && e.key === 'd') {
                e.preventDefault();
                startDrawing();
            }
            // Ctrl+S to save
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                saveLotModal();
            }
            // Ctrl+B to toggle sidebar
            if (e.ctrlKey && e.key === 'b') {
                e.preventDefault();
                toggleSidebar();
            }
        });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDy5w9gXbt4WqE0jmaXIwFllb-CecSxFyQ&libraries=drawing,geometry&callback=initMap" async defer></script>
</body>
</html>