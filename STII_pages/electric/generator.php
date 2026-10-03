<?php 

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Generator Documents - STII Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.4.4/flowbite.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;--secondary:#10b981;--danger:#ef4444;--warning:#f59e0b;--light:#f3f4f6;--dark:#1f2937;--gray:#6b7280;--light-gray:#e5e7eb;--success:#10b981;
    }
    .logo h1 {font-family:'Orbitron',sans-serif;font-size:24px;font-weight:700;letter-spacing:1px;color:var(--primary)}
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
    body{background:#f9fafb;color:var(--dark);display:flex;flex-direction:column;min-height:100vh}
    .top-nav{background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.1);padding:15px 30px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:100;gap:16px}
    .logo{display:flex;align-items:center;gap:10px}
    .logo h1{font-size:24px;color:var(--primary)}
    .back-btn{padding:10px 15px;display:flex;align-items:center;gap:8px;color:var(--gray);text-decoration:none;border-radius:5px;transition:all .3s}
    .back-btn:hover{background:#f1f5f9;color:var(--primary)}
    .main-content{flex:1;padding:30px;overflow-y:auto}
    .card{background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 6px rgba(0,0,0,.05);margin-bottom:20px}
    .btn{padding:10px 14px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;transition:all .2s}
    .btn:hover{background:var(--primary-dark);transform:translateY(-1px)}
    
    /* Supplier Navigation */
    .supplier-info {
      display: flex;
      align-items: flex-start;
      gap: 16px;
      padding: 20px;
      background: #fff;
      border-radius: 12px;
      box-shadow: 0 2px 12px rgba(0,0,0,0.08);
      margin-bottom: 20px;
      margin-top: 20px;
    }
    .supplier-logo {
      width: 60px;
      height: 60px;
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 700;
      font-size: 18px;
      flex-shrink: 0;
    }
    .supplier-content {
      flex: 1;
    }
    .supplier-title {
      font-weight: 700;
      font-size: 18px;
      color: #1a1a1a;
      margin-bottom: 4px;
    }
    .supplier-subtitle {
      font-size: 14px;
      color: #666;
      margin-bottom: 16px;
    }
    .supplier-nav {
      display: flex;
      gap: 8px;
      padding: 4px;
      border-radius: 10px;
    }
    .nav-link {
      display: flex;
      align-items: center;
      gap: 8px;
      color: var(--primary);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      padding: 8px 16px;
      border-radius: 8px;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      flex: 1;
      justify-content: center;
      white-space: nowrap;
      background: var(--light-gray);
    }
    .nav-link:hover {
      background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);
      transform: translateY(-1px);
    }
    .nav-link.active {
      color:var(--light);
      background: var(--primary-dark);
      box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
      font-weight: 600;
    }
    .nav-icon {
      font-size: 16px;
      opacity: 0.9;
    }
    .nav-link.active .nav-icon {
      opacity: 1;
    }

    /* Generator Details Grid */
    .solar-details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .detail-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 2px solid #e5e7eb;
      transition: all 0.3s ease;
    }

    .detail-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .overview-card { border-color: #f59e0b; }
    .installation-card { border-color: #10b981; }
    .performance-card { border-color: #3b82f6; }
    .equipment-card { border-color: #8b5cf6; }

    .detail-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      margin-bottom: 15px;
    }

    .overview-card .detail-icon {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #f59e0b;
    }

    .installation-card .detail-icon {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #10b981;
    }

    .performance-card .detail-icon {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #3b82f6;
    }

    .equipment-card .detail-icon {
      background: linear-gradient(135deg, #ede9fe, #ddd6fe);
      color: #8b5cf6;
    }

    .detail-content h3 {
      font-size: 16px;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 15px;
    }

    .detail-info {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .info-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #f3f4f6;
    }

    .info-row:last-child {
      border-bottom: none;
    }

    .info-label {
      font-size: 13px;
      color: var(--gray);
      font-weight: 500;
    }

    .info-value {
      font-size: 14px;
      color: var(--dark);
      font-weight: 600;
    }

    /* Cost Table */
    .cost-breakdown-section {
      margin-top: 30px;
    }

    .cost-table-wrapper {
      overflow-x: auto;
      border-radius: 12px;
      border: 2px solid #e5e7eb;
      margin-bottom: 30px;
    }

    .cost-table {
      width: 100%;
      border-collapse: collapse;
      background: white;
    }

    .cost-table thead {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
    }

    .cost-table th {
      padding: 15px;
      text-align: left;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .cost-table tbody tr {
      border-bottom: 1px solid #f3f4f6;
      transition: background 0.2s ease;
    }

    .cost-table tbody tr:hover {
      background: #f9fafb;
    }

    .cost-table td {
      padding: 15px;
      font-size: 14px;
      color: var(--dark);
    }

    .item-desc {
      display: flex;
      align-items: center;
      font-weight: 500;
    }

    .subtotal-row {
      background: #f9fafb;
      font-weight: 600;
    }

    .tax-row {
      background: #fef3c7;
    }

    .total-row {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      font-weight: 700;
      font-size: 16px;
    }

    /* Financial Summary */
    .financial-summary {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .summary-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 2px solid #e5e7eb;
      display: flex;
      gap: 15px;
      align-items: flex-start;
      transition: all 0.3s ease;
    }

    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
    }

    .savings-card { border-color: #10b981; }
    .payback-card { border-color: #3b82f6; }
    .roi-card { border-color: #f59e0b; }
    .environmental-card { border-color: #22c55e; }

    .summary-icon {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }

    .savings-card .summary-icon {
      background: linear-gradient(135deg, #d1fae5, #a7f3d0);
      color: #10b981;
    }

    .payback-card .summary-icon {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      color: #3b82f6;
    }

    .roi-card .summary-icon {
      background: linear-gradient(135deg, #fef3c7, #fde68a);
      color: #f59e0b;
    }

    .environmental-card .summary-icon {
      background: linear-gradient(135deg, #dcfce7, #bbf7d0);
      color: #22c55e;
    }

    .summary-content {
      flex: 1;
    }

    .summary-label {
      font-size: 12px;
      color: var(--gray);
      font-weight: 500;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .summary-value {
      font-size: 24px;
      font-weight: 700;
      color: var(--dark);
      margin-bottom: 4px;
    }

    .summary-note {
      font-size: 11px;
      color: var(--gray);
    }

    /* Maintenance Grid */
    .maintenance-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }

    .maintenance-card {
      background: white;
      border-radius: 12px;
      padding: 20px;
      border: 2px solid #e5e7eb;
      transition: all 0.3s ease;
    }

    .maintenance-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
      border-color: var(--primary);
    }

    .maintenance-header {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-bottom: 15px;
      padding-bottom: 15px;
      border-bottom: 2px solid #f3f4f6;
    }

    .maintenance-header i {
      font-size: 24px;
      color: var(--primary);
    }

    .maintenance-header span {
      font-size: 16px;
      font-weight: 700;
      color: var(--dark);
    }

    .maintenance-items {
      display: flex;
      flex-direction: column;
      gap: 10px;
      margin-bottom: 15px;
    }

    .maintenance-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--dark);
    }

    .maintenance-item i {
      color: var(--success);
      font-size: 14px;
    }

    .maintenance-cost {
      background: linear-gradient(135deg, #dbeafe, #bfdbfe);
      padding: 10px 15px;
      border-radius: 8px;
      text-align: center;
      font-weight: 700;
      color: var(--primary-dark);
      font-size: 14px;
    }

    /* Documents */
    .documents-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

.single-document-card {
  background: white;
  border-radius: 12px;
  padding: 0;
  border: 1px solid #e5e7eb;
}

.scanned-images-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 0;
}

.scanned-image-item {
  display: flex;
  align-items: center;
  padding: 20px;
  border-bottom: 1px solid #f3f4f6;
  border-right: 1px solid #f3f4f6;
  transition: all 0.3s ease;
  cursor: pointer;
}

.scanned-image-item:hover {
  background: #f9fafb;
  transform: translateY(-2px);
}

.scanned-image-item:nth-child(even) {
  border-right: none;
}

.scanned-image-item:nth-last-child(-n+2) {
  border-bottom: none;
}

.image-preview {
  width: 80px;
  height: 80px;
  background: #f0f9ff;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 15px;
  flex-shrink: 0;
  overflow: hidden;
  border: 2px solid #e5e7eb;
}

.thumbnail-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.image-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.image-info {
  flex: 1;
}

.image-title {
  font-weight: 600;
  font-size: 16px;
  color: var(--dark);
  margin-bottom: 4px;
  word-break: break-all;
}

.image-meta {
  display: flex;
  gap: 15px;
  font-size: 12px;
  color: var(--gray);
}

.image-meta span {
  display: flex;
  align-items: center;
  gap: 4px;
}

.image-actions {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}

.btn-outline {
  background: transparent;
  border: 1px solid var(--primary);
  color: var(--primary);
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
  text-decoration: none;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  cursor: pointer;
}

.btn-outline:hover {
  background: var(--primary);
  color: white;
}

/* Modal Styles */
.modal {
  position: fixed;
  z-index: 1000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.8);
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 800px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  position: relative;
}

.close-btn {
  position: absolute;
  right: 15px;
  top: 15px;
  font-size: 28px;
  font-weight: bold;
  color: #aaa;
  cursor: pointer;
  z-index: 1001;
  background: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
}

.close-btn:hover {
  color: var(--dark);
  background: #f5f5f5;
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
  margin: 0;
  color: var(--dark);
}

.modal-body {
  padding: 20px;
  flex: 1;
  overflow: auto;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-footer {
  padding: 20px;
  border-top: 1px solid #e5e7eb;
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

    /* Responsive */
    @media (max-width: 768px){
      .main-content{padding:15px}
      .documents-grid{grid-template-columns:1fr}
      .supplier-info{flex-direction:column;text-align:center}
      .supplier-nav{flex-direction:column}
      .nav-link{justify-content:center}
      .solar-details-grid{grid-template-columns:1fr}
      .financial-summary{grid-template-columns:1fr}
      .maintenance-grid{grid-template-columns:1fr}
      .cost-table-wrapper{overflow-x:scroll}
      .scanned-images-grid {
        grid-template-columns: 1fr;
      }
      .scanned-image-item:nth-child(even) {
        border-right: 1px solid #f3f4f6;
      }
      .scanned-image-item:nth-last-child(2) {
        border-bottom: 1px solid #f3f4f6;
      }
      .modal-content {
        width: 95%;
        margin: 20px;
      }
    }
  </style>
</head>
<body>
  
<nav class="top-nav">
    <a href="../portal.php" class="back-btn"><i class="fas fa-arrow-left"></i> back</a>
    <div class="logo" style="text-align:center;flex:1;justify-content:center;">
        <i class="fas fa-bolt" style="color:var(--primary);font-size:28px"></i>
        <h1>STII ELECTRICITY Dashboard</h1>
    </div>
</nav>

  <div class="main-content">
    <nav class="supplier-nav">
      <a href="dashboard.php" class="nav-link">
        <span class="nav-icon">⚡</span>
        Electricity
      </a>
      <a href="solar.php" class="nav-link">
        <span class="nav-icon">☀️</span>
        Solar
      </a>
      <a href="generator.php" class="nav-link active">
        <span class="nav-icon">🔧</span>
        Generator
      </a>
    </nav>

<div class="supplier-info">
  <div class="supplier-logo">
    <i class="fas fa-cogs"></i>
  </div>
  <div class="supplier-content">
    <div class="supplier-title">STII Generator (Genset)</div>
    <div class="supplier-subtitle">Cummins 250 kVA Diesel Generator - Installation documents and maintenance records</div>
  </div>
</div>

<!-- Generator System Details & Cost -->
<div class="card">
  <h2 style="font-size:20px;font-weight:600;margin-bottom:20px;color:var(--dark);">
    <i class="fas fa-info-circle" style="color:var(--primary);margin-right:10px;"></i>
    Generator System Details & Cost Breakdown
  </h2>
  
  <div class="solar-details-grid">
    <!-- System Overview -->
    <div class="detail-card overview-card">
      <div class="detail-icon">
        <i class="fas fa-cog"></i>
      </div>
      <div class="detail-content">
        <h3>System Overview</h3>
        <div class="detail-info">
          <div class="info-row">
            <span class="info-label">Generator Capacity:</span>
            <span class="info-value">250 kVA</span>
          </div>
          <div class="info-row">
            <span class="info-label">Type:</span>
            <span class="info-value">Diesel Generator Set</span>
          </div>
          <div class="info-row">
            <span class="info-label">Power Output:</span>
            <span class="info-value">200 kW (Prime)</span>
          </div>
          <div class="info-row">
            <span class="info-label">Voltage:</span>
            <span class="info-value">400V/230V, 3-Phase</span>
          </div>
          <div class="info-row">
            <span class="info-label">Frequency:</span>
            <span class="info-value">60 Hz</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Brand & Engine Details -->
    <div class="detail-card installation-card">
      <div class="detail-icon">
        <i class="fas fa-industry"></i>
      </div>
      <div class="detail-content">
        <h3>Brand & Engine Details</h3>
        <div class="detail-info">
          <div class="info-row">
            <span class="info-label">Brand:</span>
            <span class="info-value">Cummins</span>
          </div>
          <div class="info-row">
            <span class="info-label">Model:</span>
            <span class="info-value">C250D5</span>
          </div>
          <div class="info-row">
            <span class="info-label">Engine Type:</span>
            <span class="info-value">6-Cylinder, Turbo Diesel</span>
          </div>
          <div class="info-row">
            <span class="info-label">Engine Model:</span>
            <span class="info-value">6LTAA8.9-G2</span>
          </div>
          <div class="info-row">
            <span class="info-label">Displacement:</span>
            <span class="info-value">8.9 Liters</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Performance Specs -->
    <div class="detail-card performance-card">
      <div class="detail-icon">
        <i class="fas fa-tachometer-alt"></i>
      </div>
      <div class="detail-content">
        <h3>Performance Specifications</h3>
        <div class="detail-info">
          <div class="info-row">
            <span class="info-label">Fuel Type:</span>
            <span class="info-value">Diesel</span>
          </div>
          <div class="info-row">
            <span class="info-label">Fuel Tank Capacity:</span>
            <span class="info-value">500 Liters</span>
          </div>
          <div class="info-row">
            <span class="info-label">Fuel Consumption:</span>
            <span class="info-value">~50 L/hour (75% load)</span>
          </div>
          <div class="info-row">
            <span class="info-label">Runtime (Full Tank):</span>
            <span class="info-value">~10 hours</span>
          </div>
          <div class="info-row">
            <span class="info-label">Noise Level:</span>
            <span class="info-value">75 dB @ 7m</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Installation Details -->
    <div class="detail-card equipment-card">
      <div class="detail-icon">
        <i class="fas fa-tools"></i>
      </div>
      <div class="detail-content">
        <h3>Installation Details</h3>
        <div class="detail-info">
          <div class="info-row">
            <span class="info-label">Installation Date:</span>
            <span class="info-value">March 10, 2024</span>
          </div>
          <div class="info-row">
            <span class="info-label">Warranty Period:</span>
            <span class="info-value">2 Years / 2,000 Hours</span>
          </div>
          <div class="info-row">
            <span class="info-label">Location:</span>
            <span class="info-value">Generator Room, Ground Floor</span>
          </div>
          <div class="info-row">
            <span class="info-label">Control Panel:</span>
            <span class="info-value">Deep Sea DSE7320 MKII</span>
          </div>
          <div class="info-row">
            <span class="info-label">Start System:</span>
            <span class="info-value">Electric Start + Auto Transfer Switch</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Cost Breakdown -->
  <div class="cost-breakdown-section">
    <h3 style="font-size:18px;font-weight:600;margin:30px 0 20px 0;color:var(--dark);">
      <i class="fas fa-dollar-sign" style="color:var(--success);margin-right:8px;"></i>
      Cost Breakdown
    </h3>
    
    <div class="cost-table-wrapper">
      <table class="cost-table">
        <thead>
          <tr>
            <th>Item Description</th>
            <th>Quantity</th>
            <th>Unit Cost</th>
            <th>Total Cost</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-cog" style="color:var(--warning);margin-right:8px;"></i>
                Cummins 250 kVA Diesel Generator Set (C250D5)
              </div>
            </td>
            <td>1 Unit</td>
            <td>₱2,800,000.00</td>
            <td>₱2,800,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-microchip" style="color:var(--primary);margin-right:8px;"></i>
                Control Panel (Deep Sea DSE7320 MKII)
              </div>
            </td>
            <td>1 Unit</td>
            <td>₱180,000.00</td>
            <td>₱180,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-exchange-alt" style="color:var(--secondary);margin-right:8px;"></i>
                Automatic Transfer Switch (ATS) - 315A
              </div>
            </td>
            <td>1 Unit</td>
            <td>₱250,000.00</td>
            <td>₱250,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-car-battery" style="color:var(--danger);margin-right:8px;"></i>
                Battery Set (12V Heavy Duty) with Charger
              </div>
            </td>
            <td>2 Units</td>
            <td>₱25,000.00</td>
            <td>₱50,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-oil-can" style="color:var(--warning);margin-right:8px;"></i>
                Fuel Tank (500L) with Installation
              </div>
            </td>
            <td>1 Set</td>
            <td>₱120,000.00</td>
            <td>₱120,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-volume-mute" style="color:var(--gray);margin-right:8px;"></i>
                Acoustic Enclosure & Soundproofing
              </div>
            </td>
            <td>1 Set</td>
            <td>₱180,000.00</td>
            <td>₱180,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-wind" style="color:var(--primary);margin-right:8px;"></i>
                Exhaust System & Ventilation
              </div>
            </td>
            <td>1 System</td>
            <td>₱90,000.00</td>
            <td>₱90,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-plug" style="color:var(--secondary);margin-right:8px;"></i>
                Electrical Installation & Wiring
              </div>
            </td>
            <td>1 Project</td>
            <td>₱200,000.00</td>
            <td>₱200,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-hard-hat" style="color:var(--warning);margin-right:8px;"></i>
                Civil Works & Foundation
              </div>
            </td>
            <td>1 Project</td>
            <td>₱150,000.00</td>
            <td>₱150,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-tools" style="color:var(--gray);margin-right:8px;"></i>
                Installation & Commissioning
              </div>
            </td>
            <td>1 Service</td>
            <td>₱200,000.00</td>
            <td>₱200,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px;"></i>
                Permits & Documentation
              </div>
            </td>
            <td>1 Set</td>
            <td>₱30,000.00</td>
            <td>₱30,000.00</td>
          </tr>
          <tr>
            <td>
              <div class="item-desc">
                <i class="fas fa-shield-alt" style="color:var(--success);margin-right:8px;"></i>
                Warranty & Insurance (2 Years)
              </div>
            </td>
            <td>1 Period</td>
            <td>₱100,000.00</td>
            <td>₱100,000.00</td>
          </tr>
          <tr class="subtotal-row">
            <td colspan="3"><strong>Subtotal</strong></td>
            <td><strong>₱4,350,000.00</strong></td>
          </tr>
          <tr class="tax-row">
            <td colspan="3">VAT (12%)</td>
            <td>₱522,000.00</td>
          </tr>
          <tr class="total-row">
            <td colspan="3"><strong>TOTAL PROJECT COST</strong></td>
            <td><strong>₱4,872,000.00</strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Operational Summary Cards -->
    <div class="financial-summary">
      <div class="summary-card savings-card">
        <div class="summary-icon">
          <i class="fas fa-bolt"></i>
        </div>
        <div class="summary-content">
          <div class="summary-label">Power Capacity</div>
          <div class="summary-value">250 kVA</div>
          <div class="summary-note">Prime Power Output</div>
        </div>
      </div>
      
      <div class="summary-card payback-card">
        <div class="summary-icon">
          <i class="fas fa-clock"></i>
        </div>
        <div class="summary-content">
          <div class="summary-label">Runtime</div>
          <div class="summary-value">10 Hours</div>
          <div class="summary-note">On full tank (500L)</div>
        </div>
      </div>
      
      <div class="summary-card roi-card">
        <div class="summary-icon">
          <i class="fas fa-gas-pump"></i>
        </div>
        <div class="summary-content">
          <div class="summary-label">Fuel Consumption</div>
          <div class="summary-value">50 L/hr</div>
          <div class="summary-note">At 75% load capacity</div>
        </div>
      </div>
      
      <div class="summary-card environmental-card">
        <div class="summary-icon">
          <i class="fas fa-shield-alt"></i>
        </div>
        <div class="summary-content">
          <div class="summary-label">Warranty Period</div>
          <div class="summary-value">2 Years</div>
          <div class="summary-note">Or 2,000 running hours</div>
        </div>
      </div>
    </div>

    <!-- Maintenance Schedule -->
    <h3 style="font-size:18px;font-weight:600;margin:30px 0 20px 0;color:var(--dark);">
      <i class="fas fa-calendar-check" style="color:var(--primary);margin-right:8px;"></i>
      Maintenance Schedule & Costs
    </h3>

    <div class="maintenance-grid">
      <div class="maintenance-card">
        <div class="maintenance-header">
          <i class="fas fa-calendar-day"></i>
          <span>Weekly Maintenance</span>
        </div>
        <div class="maintenance-items">
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Visual Inspection</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Check Fuel & Oil Levels</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Test Run (15 minutes)</span>
          </div>
        </div>
        <div class="maintenance-cost">Cost: ₱0/week (In-house)</div>
      </div>

      <div class="maintenance-card">
        <div class="maintenance-header">
          <i class="fas fa-calendar-alt"></i>
          <span>Monthly Maintenance</span>
        </div>
        <div class="maintenance-items">
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Oil & Filter Change</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Battery Check & Clean</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Coolant Level Check</span>
          </div>
        </div>
        <div class="maintenance-cost">Cost: ₱8,000/month</div>
      </div>

      <div class="maintenance-card">
        <div class="maintenance-header">
          <i class="fas fa-calendar"></i>
          <span>Quarterly Maintenance</span>
        </div>
        <div class="maintenance-items">
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Full System Inspection</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Air Filter Replacement</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Load Bank Testing</span>
          </div>
        </div>
        <div class="maintenance-cost">Cost: ₱25,000/quarter</div>
      </div>

      <div class="maintenance-card">
        <div class="maintenance-header">
          <i class="fas fa-calendar-check"></i>
          <span>Annual Maintenance</span>
        </div>
        <div class="maintenance-items">
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Complete Overhaul Service</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Replace All Consumables</span>
          </div>
          <div class="maintenance-item">
            <i class="fas fa-check-circle"></i>
            <span>Performance Testing</span>
          </div>
        </div>
        <div class="maintenance-cost">Cost: ₱150,000/year</div>
      </div>
    </div>
  </div>
</div>

<!-- Generator Documents Content -->
<div class="card">
  <h2 style="font-size:20px;font-weight:600;margin-bottom:20px;color:var(--dark);">
    <i class="fas fa-cogs" style="color:var(--warning);margin-right:10px;"></i>
    Generator Document Files
  </h2>
  
  <div class="single-document-card">
    <div class="scanned-images-grid">
      <?php
      // Define the path to your generator images directory
      $imagePath = "image/";
      
      // Array of generator document images (replace with your actual image files)
      $generatorImages = [
        "generator_001.jpg",
        "generator_002.jpg", 
        "generator_003.jpg",
        "generator_004.jpg",
      ];
      
      // Display each image
      foreach ($generatorImages as $index => $imageFile) {
        $fullPath = $imagePath . $imageFile;
        $imageNumber = $index + 1;
        $fileSize = "2." . ($index + 1) . " MB"; // Simulated file sizes
        $uploadDate = "2024-0" . ($index + 3) . "-" . (15 + $index);
        
        echo '
        <div class="scanned-image-item" onclick="viewImage(\'' . $fullPath . '\', \'' . $imageFile . '\')">
          <div class="image-preview">
            <img src="' . $fullPath . '" alt="' . $imageFile . '" class="thumbnail-image" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\'">
            <div class="image-fallback" style="display:none">
              <i class="fas fa-file-image" style="font-size:32px;color:var(--primary);"></i>
            </div>
          </div>
          <div class="image-info">
            <div class="image-title">' . $imageFile . '</div>
            <div class="image-meta">
              <span><i class="fas fa-calendar"></i> ' . $uploadDate . '</span>
              <span><i class="fas fa-file-image"></i> ' . $fileSize . '</span>
            </div>
          </div>
          <div class="image-actions">
            <button class="btn-outline view-btn" onclick="event.stopPropagation(); viewImage(\'' . $fullPath . '\', \'' . $imageFile . '\')">
              <i class="fas fa-eye"></i>
            </button>
            <a href="' . $fullPath . '" download class="btn-outline download-btn" onclick="event.stopPropagation()">
              <i class="fas fa-download"></i>
            </a>
          </div>
        </div>';
      }
      ?>
    </div>
  </div>
</div>

<!-- Image Viewer Modal -->
<div id="imageModal" class="modal" style="display:none">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal()">&times;</span>
    <div class="modal-header">
      <h3 id="modalTitle">Generator Document</h3>
    </div>
    <div class="modal-body">
      <img id="modalImage" src="" alt="Generator Document" style="max-width:100%; max-height:70vh; display:block; margin:0 auto">
    </div>
    <div class="modal-footer">
      <a id="downloadLink" href="" download class="btn">
        <i class="fas fa-download"></i> Download
      </a>
      <button class="btn btn-outline" onclick="closeModal()">
        <i class="fas fa-times"></i> Close
      </button>
    </div>
  </div>
</div>

<script>
function viewImage(imagePath, fileName) {
  // Set modal image and title
  document.getElementById('modalImage').src = imagePath;
  document.getElementById('modalTitle').textContent = fileName;
  document.getElementById('downloadLink').href = imagePath;
  
  // Show modal
  document.getElementById('imageModal').style.display = 'flex';
}

function closeModal() {
  document.getElementById('imageModal').style.display = 'none';
}

// Close modal when clicking outside the content
window.onclick = function(event) {
  const modal = document.getElementById('imageModal');
  if (event.target === modal) {
    closeModal();
  }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
  if (event.key === 'Escape') {
    closeModal();
  }
});
</script>
  </div>

</body>
</html>