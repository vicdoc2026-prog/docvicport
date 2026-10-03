<?php
require_once 'config/check-session.php';


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
  <title>Cooperative Dashboard</title>

  <!-- Bootstrap + Icons + Font -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --brand-1:#1e40af;
      --brand-2:#3b82f6;
      --brand-3:#06b6d4;

      --ok:#16a34a;
      --warn:#f59e0b;

      --bg:#f6f8fc;
      --text:#0f172a;
      --muted:#64748b;
      --border:#e5e7eb;

      --shadow: 0 10px 30px rgba(15,23,42,.06);
      --shadow-sm: 0 3px 10px rgba(15,23,42,.05);

      /* ✅ reduced border radius (smaller corners) */
      --radius: 6px;
      --radius-sm: 4px;
      --radius-pill: 999px;
    }

    *{ box-sizing:border-box; }
    body{
      background: radial-gradient(1200px 600px at 10% -20%, rgba(59,130,246,.16), transparent 60%),
                  radial-gradient(900px 500px at 110% 0%, rgba(6,182,212,.14), transparent 60%),
                  var(--bg);
      font-family:'Inter', sans-serif;
      color:var(--text);
      font-size:14px;
    }
    .container-fluid{ max-width: 1400px; padding: 22px 18px; }

    /* Top Bar */
    .topbar{
      background: rgba(255,255,255,.78);
      backdrop-filter: blur(14px);
      border: 1px solid rgba(229,231,235,.9);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      padding: 14px 16px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap: 14px;
      margin-bottom: 16px;
    }
    .brand{ display:flex; align-items:center; gap:12px; min-width: 240px; }
    .brand-badge{
      width: 40px; height: 40px;
      border-radius: var(--radius);
      background: linear-gradient(135deg, var(--brand-1), var(--brand-2));
      display:flex; align-items:center; justify-content:center;
      color:#fff; box-shadow: 0 12px 30px rgba(30,64,175,.25);
    }
    .brand h1{ font-size: 16px; margin:0; font-weight: 800; letter-spacing:.2px; line-height:1.1; }
    .brand p{ margin:0; font-size: 12px; color: var(--muted); }

    .topbar-actions{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
    .btn-soft{
      border: 1px solid rgba(229,231,235,.9);
      background: rgba(255,255,255,.75);
      color: var(--text);
      border-radius: var(--radius);
      padding: 9px 12px;
      font-weight: 600;
      font-size: 13px;
      display:inline-flex;
      align-items:center;
      gap:8px;
      transition: .2s ease;
    }
    .btn-soft:hover{ transform: translateY(-1px); box-shadow: var(--shadow-sm); border-color: rgba(59,130,246,.35); }
    .btn-primary-soft{
      background: linear-gradient(135deg, rgba(30,64,175,.12), rgba(59,130,246,.10));
      border-color: rgba(59,130,246,.25);
      color: var(--brand-1);
    }

    .back-btn{
      width: 40px; height: 40px;
      border-radius: var(--radius);
      border: 1px solid rgba(229,231,235,.9);
      background: rgba(255,255,255,.75);
      display:flex; align-items:center; justify-content:center;
      transition: .2s ease;
    }
    .back-btn:hover{ transform: translateY(-1px); box-shadow: var(--shadow-sm); border-color: rgba(59,130,246,.35); }

    /* TOP STII TEXT BLOCK (ALWAYS VISIBLE) */
    .stii-top{
      background: rgba(255,255,255,.88);
      border: 1px solid rgba(229,231,235,.9);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      padding: 16px 16px;
      margin-bottom: 16px;
    }
    .stii-top .title{
      display:flex;
      align-items:center;
      gap:10px;
      margin-bottom: 10px;
    }
    .stii-top .title .icon{
      width: 40px; height: 40px;
      border-radius: var(--radius);
      background: linear-gradient(135deg, rgba(30,64,175,.14), rgba(6,182,212,.10));
      border: 1px solid rgba(59,130,246,.20);
      color: var(--brand-1);
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .stii-top h2{
      margin:0;
      font-size: 15px;
      font-weight: 900;
    }
    .stii-top p.lead{
      margin: 0;
      color: var(--muted);
      font-size: 12.5px;
      font-weight: 600;
    }

    .stii-grid{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 10px;
      margin-top: 12px;
    }
    .stii-card{
      border-radius: var(--radius);
      border: 1px solid rgba(229,231,235,.9);
      background: rgba(255,255,255,.96);
      box-shadow: var(--shadow-sm);
      padding: 14px;
    }
    .stii-card h3{
      margin: 0 0 8px 0;
      font-size: 12px;
      font-weight: 900;
      text-transform: uppercase;
      letter-spacing: .75px;
      color: #334155;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .stii-card p{
      margin:0;
      color:#334155;
      font-size: 13px;
      line-height: 1.65;
      font-weight: 500;
    }
    .stii-card ul{ margin:0; padding-left: 18px; }
    .stii-card li{
      margin-bottom: 8px;
      color:#334155;
      font-size: 13px;
      line-height: 1.6;
      font-weight: 600;
    }

    /* Stats */
    .stats-grid{
      display:grid;
      grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
      gap: 10px;
      margin-bottom: 14px;
    }
    .stat{
      background: rgba(255,255,255,.85);
      border: 1px solid rgba(229,231,235,.9);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      padding: 14px 14px;
      display:flex; align-items:center; gap: 12px;
      transition: .2s ease;
    }
    .stat:hover{ transform: translateY(-2px); box-shadow: var(--shadow); border-color: rgba(59,130,246,.25); }
    .stat .ico{
      width: 40px; height: 40px;
      border-radius: var(--radius);
      display:flex; align-items:center; justify-content:center;
      background: linear-gradient(135deg, rgba(30,64,175,.14), rgba(59,130,246,.10));
      color: var(--brand-1);
    }
    .stat .ico.ok{ background: linear-gradient(135deg, rgba(22,163,74,.14), rgba(34,197,94,.10)); color: var(--ok); }
    .stat .ico.warn{ background: linear-gradient(135deg, rgba(245,158,11,.16), rgba(251,191,36,.10)); color: var(--warn); }
    .stat h4{ margin:0; font-size: 22px; font-weight: 900; line-height: 1; }
    .stat p{ margin: 4px 0 0 0; color: var(--muted); font-size: 12px; font-weight: 600; }

    /* Members Card */
    .members-section{
      background: rgba(255,255,255,.90);
      border: 1px solid rgba(229,231,235,.9);
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      overflow:hidden;
      display:block;
    }
    .section-header{
      padding: 14px 16px;
      border-bottom: 1px solid rgba(229,231,235,.9);
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:12px;
      flex-wrap: wrap;
    }
    .section-header h3{
      margin:0;
      font-size: 14px;
      font-weight: 900;
      display:flex;
      align-items:center;
      gap:10px;
    }

    .filters-container{
      padding: 12px 16px;
      background: rgba(248,250,252,.8);
      border-bottom: 1px solid rgba(229,231,235,.9);
      display:flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items:center;
    }
    .filter-group{ display:flex; align-items:center; gap:8px; }
    .filter-group label{
      font-size: 12px;
      font-weight: 700;
      color: var(--muted);
      white-space: nowrap;
    }
    .form-select, .form-control{
      border-radius: var(--radius-sm) !important;
      border: 1px solid rgba(229,231,235,.95) !important;
      font-size: 13px !important;
      padding: 8px 10px !important;
      background: rgba(255,255,255,.95) !important;
    }
    .form-select:focus, .form-control:focus{
      border-color: rgba(59,130,246,.55) !important;
      box-shadow: 0 0 0 .2rem rgba(59,130,246,.14) !important;
    }
    .search-box{
      position: relative;
      margin-left: auto;
      min-width: 240px;
      flex: 1 1 260px;
      max-width: 360px;
    }
    .search-box i{
      position:absolute;
      left: 12px;
      top:50%;
      transform: translateY(-50%);
      color: #94a3b8;
      font-size: 13px;
    }
    .search-box input{ padding-left: 34px !important; width: 100%; }

    /* Table */
    .table-wrapper{ overflow-x:auto; max-height: 620px; }
    .members-table{ width:100%; border-collapse: collapse; }
    .members-table th{
      background: rgba(248,250,252,.95);
      padding: 12px 14px;
      text-align:left;
      font-weight: 900;
      color:#334155;
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: .75px;
      border-bottom: 1px solid rgba(229,231,235,.95);
      position: sticky;
      top:0;
      z-index: 5;
    }
    .members-table td{
      padding: 12px 14px;
      border-bottom: 1px solid rgba(229,231,235,.75);
      font-size: 13px;
      color:#334155;
      vertical-align: middle;
    }
    .members-table tbody tr:hover{ background: rgba(248,250,252,.9); }

    /* Pills */
    .pill{
      display:inline-flex;
      align-items:center;
      gap:6px;
      padding: 6px 10px;
      border-radius: var(--radius-pill);
      font-size: 11px;
      font-weight: 800;
      border: 1px solid rgba(229,231,235,.75);
      background: rgba(255,255,255,.85);
    }
    .pill.pos-president{ background: rgba(239,68,68,.10); color:#b91c1c; border-color: rgba(239,68,68,.20); }
    .pill.pos-vice-president{ background: rgba(59,130,246,.10); color:#1d4ed8; border-color: rgba(59,130,246,.20); }
    .pill.pos-secretary{ background: rgba(245,158,11,.14); color:#b45309; border-color: rgba(245,158,11,.22); }
    .pill.pos-treasurer{ background: rgba(34,197,94,.12); color:#15803d; border-color: rgba(34,197,94,.22); }
    .pill.pos-auditor{ background: rgba(168,85,247,.12); color:#7c3aed; border-color: rgba(168,85,247,.22); }
    .pill.pos-member{ background: rgba(148,163,184,.16); color:#475569; border-color: rgba(148,163,184,.28); }

    .pill.status-active{ background: rgba(34,197,94,.12); color:#15803d; border-color: rgba(34,197,94,.22); }
    .pill.status-pending{ background: rgba(245,158,11,.14); color:#b45309; border-color: rgba(245,158,11,.22); }

    .pill.date{ background: rgba(59,130,246,.10); color:#1e40af; border-color: rgba(59,130,246,.20); }
    .pill.loan{ background: rgba(34,197,94,.12); color:#15803d; border-color: rgba(34,197,94,.22); }
    .pill.noloan{ background: rgba(148,163,184,.16); color:#475569; border-color: rgba(148,163,184,.28); }

    /* Pagination */
    .pagination-container{
      display:flex;
      justify-content:space-between;
      align-items:center;
      padding: 12px 16px;
      background: rgba(248,250,252,.8);
      border-top: 1px solid rgba(229,231,235,.9);
      gap: 12px;
      flex-wrap: wrap;
    }
    .pagination-info{ color: var(--muted); font-size: 12.5px; font-weight: 700; }
    .pagination{ display:flex; gap: 6px; margin:0; padding:0; list-style:none; }
    .page-link{
      border-radius: var(--radius-sm);
      padding: 7px 12px;
      font-size: 13px;
      font-weight: 800;
      color: #334155;
      border: 1px solid rgba(229,231,235,.95);
      background: rgba(255,255,255,.92);
      transition: .15s ease;
    }
    .page-link:hover{
      transform: translateY(-1px);
      border-color: rgba(59,130,246,.35);
      color: var(--brand-1);
      box-shadow: var(--shadow-sm);
    }
    .page-item.active .page-link{
      background: linear-gradient(135deg, var(--brand-1), var(--brand-2));
      color:#fff;
      border-color: transparent;
    }
    .page-item.disabled .page-link{ opacity:.5; pointer-events:none; }

    /* Loading */
    .loading{
      text-align:center;
      padding: 40px 16px;
      color: var(--muted);
      font-weight: 700;
    }
    .spinner{
      border: 3px solid rgba(226,232,240,.9);
      border-top: 3px solid rgba(30,64,175,.85);
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 0 auto 14px;
    }
    @keyframes spin{ to{ transform: rotate(360deg);} }

    .footer{
      text-align:center;
      margin: 16px 0 6px 0;
      color: #94a3b8;
      font-size: 12.5px;
      font-weight: 700;
    }

    @media (max-width: 768px){
      .topbar{ flex-direction: column; align-items: stretch; }
      .brand{ min-width:auto; justify-content:center; text-align:center; }
      .topbar-actions{ justify-content:center; }
      .filters-container{ flex-direction: column; align-items: stretch; }
      .search-box{ margin-left: 0; max-width: none; }
    }
  </style>
</head>

<body>
<div class="container-fluid">

  <!-- TOP: STII Mission/Vision/Objectives -->
  <div class="stii-top">
    <div class="title">
      <div class="icon"><i class="fas fa-bullseye"></i></div>
      <div>
        <h2>STII Mission, Vision & Quality Objectives</h2>
        <p class="lead">A clear guide for learning excellence and global competitiveness</p>
      </div>
    </div>

    <div class="stii-grid">
      <div class="stii-card">
        <h3><i class="fas fa-bullseye"></i> Our Mission</h3>
        <p>
          STII commits itself to promote responsive, relevant and innovative curricula that meet the demands of national and global industry,
          and to provide the students with the necessary knowledge, attitudes, values and skills to become successful in their chosen careers.
        </p>
      </div>

      <div class="stii-card">
        <h3><i class="fas fa-eye"></i> Our Vision</h3>
        <p>
          STII envisions itself as a leading educational institution focusing on holistic formation of individuals for global competitiveness.
        </p>
      </div>

      <div class="stii-card">
        <h3><i class="fas fa-award"></i> Quality Objectives</h3>
        <ul>
          <li>Be globally competitive education institution</li>
          <li>Produce competent graduates equipped with knowledge, skills, values, and attitudes</li>
          <li>Implement a Quality Management System to meet the needs and expectation of our students, faculty, and our stakeholders</li>
        </ul>
      </div>
    </div>
  </div>

  <!-- Top Bar -->
  <div class="d-flex align-items-start gap-3 mb-3">
    <button class="back-btn" onclick="window.history.back()" title="Back">
      <i class="fas fa-arrow-left"></i>
    </button>

    <div class="topbar flex-grow-1">
      <div class="brand">
        <div class="brand-badge">
          <i class="fas fa-layer-group"></i>
        </div>
        <div>
          <h1>SEACCO Dashboard</h1>
          <p>Welcome, <?php echo htmlspecialchars($full_name); ?></p>
        </div>
      </div>

      <div class="topbar-actions">
        <button class="btn-soft btn-primary-soft" onclick="exportData()">
          <i class="fas fa-download"></i> Export
        </button>
        <button class="btn-soft" onclick="window.print()">
          <i class="fas fa-print"></i> Print
        </button>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" id="stats-grid">
    <div class="stat">
      <div class="ico"><i class="fas fa-users"></i></div>
      <div>
        <h4 id="total-members">0</h4>
        <p>Total Members</p>
      </div>
    </div>

    <div class="stat">
      <div class="ico ok"><i class="fas fa-user-check"></i></div>
      <div>
        <h4 id="active-members">0</h4>
        <p>Active Members</p>
      </div>
    </div>

    <div class="stat">
      <div class="ico warn"><i class="fas fa-clock"></i></div>
      <div>
        <h4 id="pending-members">0</h4>
        <p>Pending Verification</p>
      </div>
    </div>

    <div class="stat">
      <div class="ico"><i class="fas fa-chart-line"></i></div>
      <div>
        <h4 id="active-percentage">0%</h4>
        <p>Active Rate</p>
      </div>
    </div>
  </div>

  <!-- Members -->
  <div class="members-section" id="members-section">
    <div class="section-header">
      <h3><i class="fas fa-users"></i> Cooperative Members</h3>
      <button class="btn-soft btn-primary-soft" onclick="toggleViewAll()" id="view-all-btn">
        <i class="fas fa-list"></i> View All
      </button>
    </div>

    <div class="filters-container">
      <div class="filter-group">
        <label for="position-filter">Position</label>
        <select id="position-filter" class="form-select" onchange="applyFilters()">
          <option value="all">All</option>
          <option value="president">President</option>
          <option value="vice-president">Vice President</option>
          <option value="secretary">Secretary</option>
          <option value="treasurer">Treasurer</option>
          <option value="auditor">Auditor</option>
          <option value="member">Member</option>
        </select>
      </div>

      <div class="filter-group">
        <label for="status-filter">Status</label>
        <select id="status-filter" class="form-select" onchange="applyFilters()">
          <option value="all">All</option>
          <option value="active">Active</option>
          <option value="pending">Pending</option>
        </select>
      </div>

      <div class="filter-group">
        <label for="loan-filter">Loan</label>
        <select id="loan-filter" class="form-select" onchange="applyFilters()">
          <option value="all">All</option>
          <option value="loaned">Has Loan</option>
          <option value="no-loan">No Loan</option>
        </select>
      </div>

      <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="text" class="form-control" placeholder="Search members..." id="searchInput" oninput="searchMembers()">
      </div>
    </div>

    <div class="table-wrapper">
      <table class="members-table">
        <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Position</th>
          <th>Membership Date</th>
          <th>Status</th>
          <th>Loan Details</th>
        </tr>
        </thead>
        <tbody id="members-table-body">
        <tr>
          <td colspan="6" class="loading">
            <div class="spinner"></div>
            Loading members data...
          </td>
        </tr>
        </tbody>
      </table>
    </div>

    <div class="pagination-container">
      <div class="pagination-info" id="pagination-info">Showing 0 to 0 of 0 entries</div>
      <ul class="pagination" id="pagination"></ul>
    </div>
  </div>

  <div class="footer">
    Doc Vic Portal &copy; 2025 | JIMZ
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  let currentPage = 1;
  let rowsPerPage = 10;
  let isViewAll = false;
  let searchTimeout = null;

  function toggleViewAll() {
    isViewAll = !isViewAll;
    const btn = document.getElementById('view-all-btn');

    if (isViewAll) {
      rowsPerPage = 999999;
      btn.innerHTML = '<i class="fas fa-list"></i> View Paginated';
    } else {
      rowsPerPage = 10;
      btn.innerHTML = '<i class="fas fa-list"></i> View All';
    }

    currentPage = 1;
    loadMembers();
  }

  async function loadMembers() {
    const position = document.getElementById('position-filter').value;
    const status = document.getElementById('status-filter').value;
    const loan = document.getElementById('loan-filter').value;
    const search = document.getElementById('searchInput').value;

    const params = new URLSearchParams({
      position,
      status,
      loan,
      search,
      page: currentPage,
      limit: rowsPerPage
    });

    try {
      const response = await fetch(`api/get_members.php?${params}`);
      const data = await response.json();

      if (data.success) {
        updateStats(data.stats);
        renderTable(data.data);
        renderPagination(data.pagination);
      } else {
        throw new Error(data.message || 'API returned success=false');
      }
    } catch (error) {
      console.error('Error loading members:', error);
      document.getElementById('members-table-body').innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-danger py-4">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Error loading data
          </td>
        </tr>
      `;
    }
  }

  function updateStats(stats) {
    document.getElementById('total-members').textContent = stats.totalMembers;
    document.getElementById('active-members').textContent = stats.activeMembers;
    document.getElementById('pending-members').textContent = stats.pendingMembers;
    document.getElementById('active-percentage').textContent = stats.activePercentage + '%';
  }

  function renderTable(members) {
    const tbody = document.getElementById('members-table-body');

    if (!members || members.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="6" class="text-center text-muted py-4">
            <i class="fas fa-inbox me-2"></i>No members found
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = members.map(member => {
      const posClass = `pos-${member.position}`;
      const statusClass = `status-${member.status}`;

      const dateContent = member.date === 'Not set'
        ? `<span class="pill noloan"><i class="fas fa-calendar-xmark"></i>${escapeHtml(member.date)}</span>`
        : `<span class="pill date"><i class="fas fa-calendar-day"></i>${escapeHtml(member.date)}</span>`;

      const loanContent = member.loan
        ? `<span class="pill loan"><i class="fas fa-peso-sign"></i>₱${Number(member.loan.amount).toLocaleString()} • ${escapeHtml(member.loan.batch)}</span>`
        : `<span class="pill noloan"><i class="fas fa-ban"></i>No Loan</span>`;

      return `
        <tr>
          <td>${escapeHtml(member.id)}</td>
          <td>${escapeHtml(member.name)}</td>
          <td><span class="pill ${posClass}"><i class="fas fa-id-badge"></i>${formatPosition(member.position)}</span></td>
          <td>${dateContent}</td>
          <td><span class="pill ${statusClass}"><i class="fas fa-circle-check"></i>${capitalize(member.status)}</span></td>
          <td>${loanContent}</td>
        </tr>
      `;
    }).join('');
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"']/g, (m) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    }[m]));
  }

  function capitalize(s) {
    s = String(s ?? '');
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
  }

  function formatPosition(position) {
    return String(position ?? '')
      .split('-')
      .map(word => word.charAt(0).toUpperCase() + word.slice(1))
      .join(' ');
  }

  function renderPagination(pagination) {
    const info = document.getElementById('pagination-info');
    const paginationEl = document.getElementById('pagination');

    const total = Number(pagination.total || 0);
    const page = Number(pagination.page || 1);
    const limit = Number(pagination.limit || rowsPerPage);
    const totalPages = Number(pagination.totalPages || 1);

    const start = total === 0 ? 0 : (page - 1) * limit + 1;
    const end = Math.min(page * limit, total);

    info.textContent = `Showing ${start} to ${end} of ${total} entries`;

    if (isViewAll || totalPages <= 1) {
      paginationEl.innerHTML = '';
      return;
    }

    let html = `
      <li class="page-item ${page === 1 ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${page - 1}); return false;">
          <i class="fas fa-chevron-left"></i>
        </a>
      </li>
    `;

    const maxPages = 5;
    let startPage = Math.max(1, page - Math.floor(maxPages / 2));
    let endPage = Math.min(totalPages, startPage + maxPages - 1);
    if (endPage - startPage < maxPages - 1) startPage = Math.max(1, endPage - maxPages + 1);

    for (let i = startPage; i <= endPage; i++) {
      html += `
        <li class="page-item ${i === page ? 'active' : ''}">
          <a class="page-link" href="#" onclick="goToPage(${i}); return false;">${i}</a>
        </li>
      `;
    }

    html += `
      <li class="page-item ${page === totalPages ? 'disabled' : ''}">
        <a class="page-link" href="#" onclick="goToPage(${page + 1}); return false;">
          <i class="fas fa-chevron-right"></i>
        </a>
      </li>
    `;

    paginationEl.innerHTML = html;
  }

  function goToPage(page) {
    currentPage = page;
    loadMembers();
  }

  function applyFilters() {
    currentPage = 1;
    loadMembers();
  }

  function searchMembers() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
      currentPage = 1;
      loadMembers();
    }, 450);
  }

  function exportData() {
    window.location.href = 'api/export_members.php';
  }

  window.addEventListener('DOMContentLoaded', () => {
    loadMembers();
  });
</script>
</body>
</html>
