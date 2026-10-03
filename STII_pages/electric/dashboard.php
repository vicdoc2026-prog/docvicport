<?php 

// Include session check - this will redirect if not logged in
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
  <title>Electric Bill Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet"href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.4.4/flowbite.min.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;--secondary:#10b981;--danger:#ef4444;--warning:#f59e0b;--purple:#8b5cf6;--light:#f3f4f6;--dark:#1f2937;--gray:#6b7280;--light-gray:#e5e7eb;
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
    .filter-section{background:#fff;border-radius:10px;padding:20px;box-shadow:0 4px 6px rgba(0,0,0,.05);margin-bottom:20px}
    .filter-section-title{font-weight:600;font-size:14px;color:var(--gray);margin-bottom:12px;display:flex;align-items:center;gap:8px}
    .filter-options{display:flex;gap:12px;flex-wrap:wrap;align-items:center}
    .filter-select,.chart-mode-select{padding:10px 15px;border-radius:8px;border:1px solid var(--light-gray);background:#fff;font-size:14px;cursor:pointer;transition:all .2s}
    .filter-select:hover,.chart-mode-select:hover{border-color:var(--primary)}
    .filter-select:focus,.chart-mode-select:focus{outline:none;border-color:var(--primary);box-shadow:0 0 0 3px rgba(37,99,235,.1)}
    .filter-label{font-size:13px;color:var(--gray);font-weight:500}
    .bills-table{width:100%;border-collapse:collapse;margin-top:15px}
    .bills-table th,.bills-table td{padding:14px 16px;text-align:left;border-bottom:1px solid var(--light-gray)}
    .bills-table th{font-weight:600;color:var(--dark);background:#f8fafc;font-size:13px;text-transform:uppercase;letter-spacing:.5px}
    .bills-table tr:hover{background:#f9fafb}
    .location-badge{padding:6px 12px;border-radius:20px;font-size:12px;font-weight:600}
    .location-belvic{background:#dbeafe;color:var(--primary)}
    .location-stii-main{background:#d1fae5;color:var(--secondary)}
    .location-stii-pangi{background:#fef3c7;color:var(--warning)}
    .location-stii-sanito{background:#ede9fe;color:var(--purple)}
    .supplier-info{display:flex;align-items:center;gap:15px;padding:15px 20px;background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:10px;margin-bottom:20px}
    .supplier-logo{width:50px;height:50px;border-radius:10px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:18px}
    .pagination{display:flex;justify-content:center;margin-top:25px;gap:8px}
    .pagination button{padding:10px 14px;border:1px solid var(--light-gray);background:#fff;border-radius:6px;cursor:pointer;transition:all .2s;font-weight:500}
    .pagination button:hover:not(:disabled){background:#f8fafc;border-color:var(--primary)}
    .pagination button.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .pagination button:disabled{opacity:.4;cursor:not-allowed}
    .charts-grid{display:grid;grid-template-columns:1fr;gap:20px}
    .chart-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.06);border:1px solid #f0f0f0}
    .chart-title{font-weight:700;margin-bottom:12px;color:var(--dark);display:flex;justify-content:space-between;align-items:center;font-size:16px}
    .chart-subtitle{font-size:12px;color:var(--gray);font-weight:400}
    .chart-wrap{position:relative;height:240px;width:100%;margin-top:10px}
    .chart-wrap canvas{width:100% !important;height:100% !important}
    .legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px}
    .summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:15px}
    .summary-card{text-align:center;padding:20px;background:linear-gradient(135deg,#f0f9ff 0%,#e0f2fe 100%);border-radius:10px;border:1px solid #bfdbfe}
    .summary-value{font-size:28px;font-weight:700;color:var(--primary);margin-bottom:5px}
    .summary-label{color:var(--gray);font-size:13px;font-weight:500;text-transform:uppercase;letter-spacing:.5px}
    .section-divider{height:1px;background:var(--light-gray);margin:20px 0}
    .chart-legend{font-size:12px;color:var(--gray);margin-top:12px;padding:10px;background:#f9fafb;border-radius:6px}
    .loading{text-align:center;padding:40px;color:var(--gray)}
    .error{background:#fee;color:#c00;padding:15px;border-radius:8px;margin:20px 0}
    .btn{padding:10px 14px;border:none;border-radius:8px;background:var(--primary);color:#fff;font-weight:700;cursor:pointer;transition:all .2s}
    .btn:hover{background:var(--primary-dark);transform:translateY(-1px)}
    .btn:disabled{opacity:.6;cursor:not-allowed}
    .btn-sm{padding:6px 12px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px}
    .btn-view{background:#10b981;color:#fff}
    .btn-view:hover{background:#059669}
    @media (max-width:768px){
      .main-content{padding:15px}
      .filter-options{flex-direction:column;width:100%}
      .filter-select,.chart-mode-select{width:100%}
      .bills-table{display:block;overflow-x:auto}
      .chart-wrap{height:220px}
      .summary-grid{grid-template-columns:1fr 1fr}
    }
    .modal{position:fixed;inset:0;background:rgba(0,0,0,.5);display:none;align-items:center;justify-content:center;z-index:200}
    .modal.show{display:flex}
    .modal-content{background:#fff;border-radius:12px;padding:18px 18px 14px;width:100%;max-width:420px;box-shadow:0 10px 30px rgba(0,0,0,.18);border:1px solid #e5e7eb}
    .modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    .modal-header h2{font-size:18px;font-weight:800;color:var(--primary)}
    .modal-header button{background:none;border:none;font-size:22px;cursor:pointer;color:var(--gray)}
    .modal-body label{display:block;margin-bottom:6px;font-size:13px;color:var(--dark);font-weight:600}
    .modal-body input,.modal-body select{width:100%;padding:10px 12px;border:1px solid var(--light-gray);border-radius:8px;margin-bottom:12px;font-size:14px}
    .modal-footer{display:flex;justify-content:flex-end;gap:10px;margin-top:4px}
    .btn-secondary{background:#9ca3af}
    .toast{position:fixed;right:16px;bottom:16px;background:#fff;border:1px solid #e5e7eb;box-shadow:0 8px 24px rgba(0,0,0,.15);padding:12px 14px;border-radius:10px;font-weight:600;z-index:300}
    .toast.ok{border-color:#86efac;color:#065f46}
    .toast.err{border-color:#fecaca;color:#991b1b}

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
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    .nav-link.active::after {
      content: '';
      position: absolute;
      bottom: -4px;
      left: 50%;
      transform: translateX(-50%);
      width: 20px;
      height: 2px;
      background: #667eea;
      border-radius: 1px;
    }

    .nav-icon {
      font-size: 16px;
      opacity: 0.9;
    }

    .nav-link.active .nav-icon {
      opacity: 1;
    }

    .no-data-message {
      text-align: center;
      padding: 30px;
      color: var(--gray);
      font-size: 14px;
    }

    @media (max-width: 480px) {
      .supplier-info {
        flex-direction: column;
        text-align: center;
      }
      
      .supplier-nav {
        flex-direction: column;
      }
      
      .nav-link {
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  
  <nav class="top-nav">
    <a href="../portal.php" class="back-btn"><i class="fas fa-arrow-left"></i> back</a>
    <div class="logo">
      <i class="fas fa-bolt" style="color:var(--primary);font-size:28px"></i>
      <h1>STII ELECTRICITY Dashboard</h1>
    </div>
    <button class="btn" id="addBillBtn"><i class="fas fa-plus"></i> Add Bill</button>
  </nav>

  <div class="main-content">
    <nav class="supplier-nav">
      <a href="electricity.php" class="nav-link active">
        <span class="nav-icon">⚡</span>
        Electricity
      </a>
      <a href="solar.php" class="nav-link">
        <span class="nav-icon">☀️</span>
        Solar
      </a>
      <a href="generator.php" class="nav-link">
        <span class="nav-icon">🔧</span>
        Generator
      </a>
    </nav>

    <div class="supplier-info">
      <div class="supplier-logo">ZII</div>
      <div class="supplier-content">
        <div class="supplier-title">Zamsureco-II Electric Cooperative</div>
        <div class="supplier-subtitle">Complete billing history for all 4 locations</div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-bottom:20px;font-size:18px">Billing Summary</h3>
      <div class="summary-grid">
        <div class="summary-card"><div class="summary-value" id="totalBills">0</div><div class="summary-label">Total Bills</div></div>
        <div class="summary-card"><div class="summary-value" id="totalConsumption">0 kWh</div><div class="summary-label">Total Consumption</div></div>
        <div class="summary-card"><div class="summary-value" id="totalAmount">₱ 0.00</div><div class="summary-label">Total Amount</div></div>
        <div class="summary-card"><div class="summary-value" id="averageAmount">₱ 0.00</div><div class="summary-label">Average per Bill</div></div>
      </div>
    </div>

    <div class="filter-section">
      <div class="filter-section-title"><i class="fas fa-chart-line"></i> Chart Display Options</div>
      <div class="filter-options">
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Chart View</label>
          <select class="chart-mode-select" id="chartLocationFilter">
            <option value="all">Show All Locations</option>
            <option value="STII-MAIN">STII-MAIN Only</option>
            <option value="Belvic Taway">Belvic Taway Only</option>
            <option value="STII-Pangi">STII-Pangi Only</option>
            <option value="STII-Sanito">STII-Sanito Only</option>
          </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Time Period</label>
          <select class="chart-mode-select" id="chartMode">
            <option value="monthly">Monthly (by Year)</option>
            <option value="yearly">Yearly Totals</option>
          </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Year</label>
          <select class="filter-select" id="chartYearFilter">
            <option>All Years</option>
            <option>2025</option><option>2024</option><option>2023</option><option>2022</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <div style="margin-bottom:20px">
        <div style="font-weight:700;font-size:18px;margin-bottom:5px">Billing Trends</div>
        <div class="chart-legend">
          <span class="legend-dot" style="background:var(--danger)"></span>Highest point
          <span style="margin:0 8px">•</span>
          <span class="legend-dot" style="background:var(--secondary)"></span>Lowest point
          <span style="margin-left:15px;color:var(--gray)">| Monthly view follows selected year. Yearly view shows all years. Only months/years with data are displayed.</span>
        </div>
      </div>
      <div class="charts-grid" id="chartsContainer">
        <div class="loading"><i class="fas fa-spinner fa-spin"></i> Loading charts...</div>
      </div>
    </div>

    <div class="section-divider"></div>

    <div class="filter-section">
      <div class="filter-section-title"><i class="fas fa-filter"></i> Filter Bills Table</div>
      <div class="filter-options">
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Location</label>
          <select class="filter-select" id="locationFilter">
            <option>All Locations</option>
            <option>STII-MAIN</option><option>Belvic Taway</option><option>STII-Pangi</option><option>STII-Sanito</option>
          </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Year</label>
          <select class="filter-select" id="yearFilter">
            <option>All Years</option>
            <option>2025</option><option>2024</option><option>2023</option><option>2022</option>
          </select>
        </div>
        <div style="display:flex;flex-direction:column;gap:5px">
          <label class="filter-label">Month</label>
          <select class="filter-select" id="monthFilter">
            <option>All Months</option>
            <option>January</option><option>February</option><option>March</option>
            <option>April</option><option>May</option><option>June</option>
            <option>July</option><option>August</option><option>September</option>
            <option>October</option><option>November</option><option>December</option>
          </select>
        </div>
      </div>
    </div>

    <div class="card">
      <h3 style="margin-bottom:15px;font-size:18px">Bills History</h3>
      <table class="bills-table">
        <thead>
          <tr><th>Location</th><th>Due Date</th><th>kWh Usage</th><th>Amount</th><th style="text-align:center">Actions</th></tr>
        </thead>
        <tbody id="billsTableBody">
          <tr><td colspan="5" class="loading"><i class="fas fa-spinner fa-spin"></i> Loading bills...</td></tr>
        </tbody>
      </table>
      <div class="pagination" id="pagination"></div>
    </div>
  </div>

  <div class="modal" id="billModal" aria-hidden="true">
    <div class="modal-content" role="dialog" aria-modal="true">
      <div class="modal-header">
        <h2>Add New Bill</h2>
        <button title="Close" onclick="closeModal()">&times;</button>
      </div>
      <div class="modal-body">
        <label>Location</label>
        <select id="billLocation" required>
          <option value="">-- Select Location --</option>
          <option value="STII-MAIN">STII-MAIN</option>
          <option value="Belvic Taway">Belvic Taway</option>
          <option value="STII-Pangi">STII-Pangi</option>
          <option value="STII-Sanito">STII-Sanito</option>
        </select>

        <label>Due Date</label>
        <input type="date" id="billDueDate" required>

        <label>kWh Usage</label>
        <input type="number" id="billKwh" step="0.01" placeholder="e.g., 1234.56" required>

        <label>Amount (₱)</label>
        <input type="number" id="billAmount" step="0.01" placeholder="e.g., 9876.54" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
        <button class="btn" id="saveBillBtn" onclick="submitBill()"><i class="fas fa-save"></i> Save</button>
      </div>
    </div>
  </div>

  <script>
    // ---------------------- GLOBALS ----------------------
    const monthNames=['January','February','March','April','May','June','July','August','September','October','November','December'];
    const LOCATIONS=["STII-MAIN","Belvic Taway","STII-Pangi","STII-Sanito"];
    let currentPage=1;
    const chartRefs={};

    // ---------------------- API CALLS ----------------------
    async function fetchAPI(endpoint, params={}) {
      try {
        const url = new URL('api.php', window.location.href);
        url.searchParams.append('action', endpoint);
        Object.keys(params).forEach(key => {
          if(params[key] !== undefined && params[key] !== null) url.searchParams.append(key, params[key]);
        });
        const response = await fetch(url);
        const data = await response.json();
        if(!data.success) throw new Error(data.message || 'API request failed');
        return data;
      } catch(error) {
        showError(error.message);
        return null;
      }
    }
    
    async function postAPI(endpoint, payload = {}) {
      try {
        const url = new URL('api.php', window.location.href);
        url.searchParams.append('action', endpoint);
        const response = await fetch(url.toString(), {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(payload)
        });
        const data = await response.json();
        if(!data.success) throw new Error(data.message || 'API request failed');
        return data;
      } catch (error) {
        showError(error.message);
        return null;
      }
    }

    // ---------------------- HELPERS ----------------------
    function formatNumber(num){return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g,',')}
    function formatDate(dateStr){const dt=new Date(dateStr);return dt.toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'});}
    function showError(msg) {
      const container = document.querySelector('.main-content');
      const errorDiv = document.createElement('div');
      errorDiv.className = 'error';
      errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${msg}`;
      container.insertBefore(errorDiv, container.firstChild);
      setTimeout(()=>errorDiv.remove(), 6000);
    }
    function toast(msg, ok=true){
      const t=document.createElement('div');
      t.className = `toast ${ok?'ok':'err'}`;
      t.innerHTML = ok ? `<i class="fa-solid fa-circle-check"></i> ${msg}` : `<i class="fa-solid fa-triangle-exclamation"></i> ${msg}`;
      document.body.appendChild(t);
      setTimeout(()=>t.remove(), 2800);
    }

    // ---------------------- TABLE + SUMMARY ----------------------
    async function loadBills() {
      const location = document.getElementById('locationFilter').value;
      const year = document.getElementById('yearFilter').value;
      const month = document.getElementById('monthFilter').value;
      const data = await fetchAPI('get_bills', {location, year, month, page: currentPage, limit: 15});
      if(data) { renderTable(data.data); renderPagination(data.totalPages); await loadSummary(); }
    }
    
    function renderTable(bills) {
      const tb = document.getElementById('billsTableBody'); tb.innerHTML = '';
      if(!bills.length) {
        tb.innerHTML='<tr><td colspan="5" style="text-align:center;padding:30px;color:var(--gray)"><i class="fas fa-inbox" style="font-size:48px;margin-bottom:10px;display:block;opacity:0.3"></i>No bills found matching your filters</td></tr>';
        return;
      }
      bills.forEach(b => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td><span class="location-badge ${b.locationClass}">${b.location}</span></td>
          <td>${formatDate(b.dueDate)}</td>
          <td>${formatNumber(Number(b.kwh).toFixed(2).replace(/\.00$/,''))} kWh</td>
          <td style="font-weight:600">₱ ${formatNumber(Number(b.amount).toFixed(2))}</td>
          <td style="text-align:center">
            <a href="details.php?id=${b.id}" class="btn btn-sm btn-view">
              <i class="fas fa-eye"></i> View Details
            </a>
          </td>
        `;
        tb.appendChild(tr);
      });
    }
    
    function renderPagination(totalPages) {
      const p = document.getElementById('pagination'); p.innerHTML = '';
      if(totalPages <= 1) return;
      const prev = document.createElement('button'); prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
      prev.disabled = currentPage === 1;
      prev.addEventListener('click', () => { if(currentPage > 1) { currentPage--; loadBills(); }});
      p.appendChild(prev);
      for(let i = 1; i <= totalPages; i++) {
        const b = document.createElement('button'); b.textContent = i;
        b.classList.toggle('active', i === currentPage);
        b.addEventListener('click', () => { currentPage = i; loadBills(); });
        p.appendChild(b);
      }
      const next = document.createElement('button'); next.innerHTML = '<i class="fas fa-chevron-right"></i>';
      next.disabled = currentPage === totalPages;
      next.addEventListener('click', () => { if(currentPage < totalPages) { currentPage++; loadBills(); }});
      p.appendChild(next);
    }
    
    async function loadSummary() {
      const location = document.getElementById('locationFilter').value;
      const year = document.getElementById('yearFilter').value;
      const month = document.getElementById('monthFilter').value;
      const data = await fetchAPI('get_summary', {location, year, month});
      if(data) {
        const s = data.data;
        document.getElementById('totalBills').textContent = formatNumber(s.totalBills);
        document.getElementById('totalConsumption').textContent = `${formatNumber(s.totalConsumption.toFixed(2))} kWh`;
        document.getElementById('totalAmount').textContent = `₱ ${formatNumber(s.totalAmount.toFixed(2))}`;
        document.getElementById('averageAmount').textContent = `₱ ${formatNumber(s.averageAmount.toFixed(2))}`;
      }
    }

    // ---------------------- CHARTS ----------------------
    function buildPointStyles(values) {
      if(!values.length) return {pointBackgroundColor:[],pointRadius:[],pointBorderWidth:[],pointBorderColor:[]};
      const max=Math.max(...values),min=Math.min(...values);
      const css=getComputedStyle(document.documentElement);
      return {
        pointBackgroundColor:values.map(v=>v===max?css.getPropertyValue('--danger').trim():v===min?css.getPropertyValue('--secondary').trim():'rgba(37,99,235,0.4)'),
        pointRadius:values.map(v=>(v===max||v===min)?6:3),
        pointBorderWidth:values.map(v=>(v===max||v===min)?2:1),
        pointBorderColor:values.map(v=>(v===max||v===min)?'#ffffff':'rgba(37,99,235,0.6)')
      };
    }
    
    function createChart(location, canvasId, chartData, mode, selectedYear) {
      const ctx = document.getElementById(canvasId).getContext('2d');
      let labels = [], values = [], subtitle = '';
      
      if(mode === 'yearly') {
        const yearMap = new Map();
        chartData.filter(d => d.location === location).forEach(d => {
          yearMap.set(d.year, (yearMap.get(d.year) || 0) + d.amount);
        });
        
        // Only include years that have data
        const years = [...yearMap.keys()].sort((a,b) => a - b);
        labels = years.map(String);
        values = years.map(y => yearMap.get(y));
        subtitle = `Yearly totals (${years.length} year${years.length !== 1 ? 's' : ''} with data)`;
        
      } else {
        // Monthly mode
        const filtered = chartData.filter(d => d.location === location && (selectedYear === 'All Years' || d.year === parseInt(selectedYear)));
        const yearToUse = selectedYear !== 'All Years' ? parseInt(selectedYear) : (filtered.length ? Math.max(...filtered.map(d => d.year)) : new Date().getFullYear());
        
        const monthMap = new Map();
        filtered.filter(d => d.year === yearToUse).forEach(d => { 
          monthMap.set(d.month, (monthMap.get(d.month) || 0) + d.amount); 
        });
        
        // Only include months that have data
        const monthsWithData = [...monthMap.keys()].sort((a, b) => a - b);
        
        if(monthsWithData.length === 0) {
          // No data available for this location and year
          return null;
        }
        
        labels = monthsWithData.map(m => monthNames[m - 1].slice(0,3));
        values = monthsWithData.map(m => monthMap.get(m));
        subtitle = `Monthly totals for ${yearToUse} (${monthsWithData.length} month${monthsWithData.length !== 1 ? 's' : ''} with data)`;
      }
      
      // If no data at all, return null
      if(values.length === 0 || values.every(v => v === 0)) {
        return null;
      }
      
      const style = buildPointStyles(values);
      const css = getComputedStyle(document.documentElement);
      
      if(chartRefs[canvasId]) chartRefs[canvasId].destroy();
      
      chartRefs[canvasId] = new Chart(ctx, {
        type: 'line',
        data: { 
          labels, 
          datasets: [{ 
            data: values, 
            fill:false, 
            borderWidth:2, 
            tension:.3,
            borderColor: css.getPropertyValue('--primary').trim(),
            pointBackgroundColor: style.pointBackgroundColor,
            pointRadius: style.pointRadius,
            pointBorderWidth: style.pointBorderWidth,
            pointBorderColor: style.pointBorderColor 
          }]
        },
        options: {
          responsive:true, 
          maintainAspectRatio:false,
          plugins:{
            legend:{display:false},
            tooltip:{
              backgroundColor:'rgba(0,0,0,0.8)',
              padding:12,
              titleFont:{size:13,weight:'600'},
              bodyFont:{size:12},
              callbacks:{
                label:(c)=>`Amount: ₱ ${formatNumber((c.raw||0).toFixed(2))}`
              }
            }
          },
          scales:{
            x:{
              grid:{display:false},
              ticks:{font:{size:11}}
            },
            y:{
              grid:{color:'rgba(0,0,0,0.05)'},
              ticks:{
                font:{size:11},
                callback:(v)=>'₱'+formatNumber(Number(v).toFixed(0))
              }
            }
          }
        }
      });
      
      return subtitle;
    }
    
    async function loadCharts() {
      const mode = document.getElementById('chartMode').value;
      const selectedYear = document.getElementById('chartYearFilter').value;
      const chartLoc = document.getElementById('chartLocationFilter').value;
      const data = await fetchAPI('get_chart_data', {location: chartLoc, mode, year: selectedYear});
      
      if(!data) return;
      
      const container = document.getElementById('chartsContainer'); 
      container.innerHTML = '';
      
      const locationsToShow = chartLoc === 'all' ? LOCATIONS : [chartLoc];
      
      locationsToShow.forEach(loc => {
        const canvasId = `chart-${loc.toLowerCase().replace(/\s+/g,'-')}`;
        const card = document.createElement('div');
        card.className = 'chart-card';
        card.innerHTML = `
          <div class="chart-title">${loc}<span class="chart-subtitle" id="sub-${canvasId}"></span></div>
          <div class="chart-wrap"><canvas id="${canvasId}"></canvas></div>`;
        container.appendChild(card);
        
        setTimeout(() => {
          const subtitle = createChart(loc, canvasId, data.data, mode, selectedYear);
          
          if(subtitle === null) {
            // No data available, show message
            const chartWrap = document.getElementById(canvasId).parentElement;
            chartWrap.innerHTML = `
              <div class="no-data-message">
                <i class="fas fa-chart-line" style="font-size:36px;opacity:0.2;margin-bottom:8px;display:block;"></i>
                No billing data available for this location and period
              </div>`;
            document.getElementById(`sub-${canvasId}`).textContent = '— No data';
          } else {
            document.getElementById(`sub-${canvasId}`).textContent = `— ${subtitle}`;
          }
        }, 10);
      });
    }

    // ---------------------- ADD BILL (Modal) ----------------------
    const billModal = document.getElementById('billModal');
    document.getElementById('addBillBtn').addEventListener('click', openModal);
    
    function openModal(){ 
      billModal.classList.add('show'); 
    }
    
    function closeModal(){ 
      billModal.classList.remove('show'); 
    }
    
    async function submitBill(){
      const btn = document.getElementById('saveBillBtn');
      const location = document.getElementById('billLocation').value;
      const dueDate = document.getElementById('billDueDate').value;
      const kwh = parseFloat(document.getElementById('billKwh').value);
      const amount = parseFloat(document.getElementById('billAmount').value);
      
      if(!location || !dueDate || !isFinite(kwh) || !isFinite(amount)){
        toast('Please fill all fields correctly.', false); 
        return;
      }
      
      btn.disabled = true; 
      btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
      
      const res = await postAPI('add_bill', {location, dueDate, kwh, amount});
      
      btn.disabled = false; 
      btn.innerHTML = '<i class="fas fa-save"></i> Save';
      
      if(res){
        toast('Bill added successfully!');
        closeModal();
        
        // Reset inputs
        document.getElementById('billLocation').value='';
        document.getElementById('billDueDate').value='';
        document.getElementById('billKwh').value='';
        document.getElementById('billAmount').value='';
        
        // Refresh data
        currentPage = 1;
        await loadBills();
        await loadCharts();
      }
    }

    // ---------------------- INIT ----------------------
    document.addEventListener('DOMContentLoaded', () => {
      loadBills();
      loadCharts();
      
      document.getElementById('yearFilter').addEventListener('change', () => { currentPage = 1; loadBills(); });
      document.getElementById('locationFilter').addEventListener('change', () => { currentPage = 1; loadBills(); });
      document.getElementById('monthFilter').addEventListener('change', () => { currentPage = 1; loadBills(); });
      
      document.getElementById('chartMode').addEventListener('change', loadCharts);
      document.getElementById('chartYearFilter').addEventListener('change', loadCharts);
      document.getElementById('chartLocationFilter').addEventListener('change', loadCharts);
      
      // Close modal on backdrop click
      billModal.addEventListener('click', (e)=>{ 
        if(e.target===billModal) closeModal(); 
      });
      
      // Esc to close
      window.addEventListener('keydown',(e)=>{ 
        if(e.key==='Escape' && billModal.classList.contains('show')) closeModal(); 
      });
    });
  </script>
</body>
</html>