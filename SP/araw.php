<?php
// araw-events.php (REVISED)
// ✅ Adds "Download PDF (All Events)" button
// ✅ PDF is grouped by MONTH, each month starts on a NEW PAGE
// ✅ Uses jsPDF + autoTable (client-side, no extra PHP libraries)

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username  = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role      = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sangguniang Panlalawigan Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- ✅ PDF Libraries -->
  <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/jspdf-autotable@3.8.2/dist/jspdf.plugin.autotable.min.js"></script>

  <style>
    :root {
      --primary: #1a3a6c;
      --secondary: #e63946;
      --accent: #2a9d8f;
      --light: #f1faee;
      --dark: #1d3557;
      --gray: #8d99ae;
      --light-gray: #edf2f4;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8fafc;
    }

    .card {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      transition: all 0.3s ease;
      border-radius: 12px;
      overflow: hidden;
    }

    .card:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 12px rgba(0, 0, 0, 0.1);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--primary), var(--dark));
      transition: all 0.3s ease;
      color: white;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
    }

    .btn-secondary {
      background: linear-gradient(135deg, #10b981, #059669);
      transition: all 0.3s ease;
      color: white;
    }
    .btn-secondary:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 6px rgba(16, 185, 129, 0.35);
    }

    .table-modern thead {
      background: var(--primary);
      color: white;
    }

    .table-modern tbody tr:hover {
      background: rgba(42, 157, 143, 0.08);
      transition: 0.2s ease;
    }

    /* Pulse animation for urgent events */
    @keyframes pulse-red {
      0%, 100% {
        background-color: rgba(239, 68, 68, 0.15);
        transform: scale(1);
      }
      50% {
        background-color: rgba(239, 68, 68, 0.25);
        transform: scale(1.001);
      }
    }

    @keyframes glow-pulse {
      0%, 100% {
        box-shadow: 0 0 5px rgba(239, 68, 68, 0.5), 0 0 10px rgba(239, 68, 68, 0.3);
      }
      50% {
        box-shadow: 0 0 15px rgba(239, 68, 68, 0.8), 0 0 25px rgba(239, 68, 68, 0.5);
      }
    }

    .urgent-event {
      animation: pulse-red 2s infinite, glow-pulse 2s infinite;
      border-left: 5px solid #ef4444 !important;
      position: relative;
    }

    .urgent-event::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 5px;
      background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
      animation: pulse-border 2s infinite;
    }

    @keyframes pulse-border {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }

    .urgent-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 4px 10px;
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
      border-radius: 6px;
      font-size: 0.75rem;
      font-weight: 700;
      animation: badge-pulse 2s infinite;
      border: 1px solid #fca5a5;
      box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
    }

    @keyframes badge-pulse {
      0%, 100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.85; transform: scale(1.02); }
    }

    .urgent-badge i {
      animation: icon-shake 2s infinite;
      color: #dc2626;
    }

    @keyframes icon-shake {
      0%, 100% { transform: rotate(0deg); }
      25% { transform: rotate(-5deg); }
      75% { transform: rotate(5deg); }
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 50;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.5);
      animation: fadeIn 0.3s;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background-color: #fefefe;
      padding: 0;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      max-height: 90vh;
      overflow-y: auto;
      animation: slideDown 0.3s;
      box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    @keyframes slideDown {
      from { transform: translateY(-50px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }

    .close {
      color: #aaa;
      float: right;
      font-size: 28px;
      font-weight: bold;
      line-height: 20px;
      cursor: pointer;
      transition: 0.3s;
    }

    .close:hover { color: #e63946; }

    .form-input {
      width: 100%;
      padding: 10px 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      transition: all 0.3s;
    }

    .form-input:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(42, 157, 143, 0.1);
    }

    .alert {
      padding: 12px 20px;
      border-radius: 6px;
      margin-bottom: 15px;
      animation: slideDown 0.3s;
    }

    .alert-success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .pagination-btn {
      padding: 8px 12px;
      border-radius: 6px;
      transition: all 0.3s;
      cursor: pointer;
      border: 1px solid #ddd;
    }

    .pagination-btn:hover {
      background-color: var(--accent);
      color: white;
      border-color: var(--accent);
    }

    .pagination-btn.active {
      background-color: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .fiesta-badge {
      display: inline-block;
      padding: 2px 8px;
      background-color: #fef3c7;
      color: #92400e;
      border-radius: 4px;
      font-size: 0.75rem;
      font-weight: 600;
    }
  </style>
</head>

<body class="flex h-screen overflow-hidden bg-gray-50">
  <?php include 'bar/sidebar.php';?>

  <!-- Main Content -->
  <div class="flex-1 flex flex-col overflow-hidden">
    <?php include 'bar/header.php';?>

    <!-- Dashboard Content -->
    <main class="flex-1 overflow-y-auto p-6 bg-gray-50">

      <!-- Alert Messages -->
      <div id="alertContainer"></div>

      <!-- Card -->
      <div class="card bg-white p-6">
        <div class="flex flex-col md:flex-row md:justify-between md:items-center gap-3 mb-4">
          <h2 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fas fa-calendar-day mr-2 text-accent"></i> Araw Events
          </h2>

          <div class="flex flex-wrap gap-2">
            <!-- ✅ NEW: Download PDF button -->
            <button id="downloadPdfBtn" class="btn-secondary px-4 py-2 rounded-lg text-sm">
              <i class="fas fa-file-pdf mr-2"></i> Download PDF (All Events)
            </button>

            <button id="addEventBtn" class="btn-primary px-4 py-2 rounded-lg text-sm">
              <i class="fas fa-plus mr-2"></i> Add Event
            </button>
          </div>
        </div>

        <!-- Search and Filter -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-search mr-1"></i> Search
            </label>
            <input type="text" id="searchInput" placeholder="Search by municipality, barangay, or action..." class="form-input">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
              <i class="fas fa-filter mr-1"></i> Filter by Month
            </label>
            <select id="monthFilter" class="form-input">
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
          </div>
        </div>

        <!-- Modern Table -->
        <div class="overflow-x-auto">
          <table class="min-w-full border-collapse table-modern">
            <thead>
              <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold">Araw Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold">Fiesta Date</th>
                <th class="px-6 py-3 text-left text-sm font-semibold">Municipality</th>
                <th class="px-6 py-3 text-left text-sm font-semibold">Barangay</th>
                <th class="px-6 py-3 text-center text-sm font-semibold">Actions</th>
              </tr>
            </thead>
            <tbody id="eventsTableBody" class="text-sm text-gray-700">
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                  <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                  <p>Loading events...</p>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex justify-between items-center mt-4">
          <div id="paginationInfo" class="text-sm text-gray-600"></div>
          <div id="pagination" class="flex gap-2"></div>
        </div>
      </div>

    </main>
  </div>

  <!-- Modal for Add/Edit Event -->
  <div id="eventModal" class="modal">
    <div class="modal-content">
      <div class="bg-gradient-to-r from-blue-900 to-blue-700 text-white px-6 py-4 flex justify-between items-center">
        <h3 id="modalTitle" class="text-xl font-semibold">
          <i class="fas fa-calendar-plus mr-2"></i>Add New Event
        </h3>
        <span class="close">&times;</span>
      </div>

      <form id="eventForm" class="p-6">
        <input type="hidden" id="eventId" name="id">

        <div class="mb-4">
          <label class="block text-gray-700 font-semibold mb-2">
            <i class="fas fa-city mr-1 text-accent"></i>Municipality <span class="text-red-500">*</span>
          </label>
          <input type="text" id="municipality" name="municipality" class="form-input" required>
        </div>

        <div class="mb-4">
          <label class="block text-gray-700 font-semibold mb-2">
            <i class="fas fa-map-marker-alt mr-1 text-accent"></i>Barangay <span class="text-red-500">*</span>
          </label>
          <input type="text" id="barangay" name="barangay" class="form-input" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
          <div>
            <label class="block text-gray-700 font-semibold mb-2">
              <i class="fas fa-calendar mr-1 text-accent"></i>Araw Date <span class="text-red-500">*</span>
            </label>
            <input type="date" id="event_date" name="event_date" class="form-input" required>
          </div>

          <div>
            <label class="block text-gray-700 font-semibold mb-2">
              <i class="fas fa-glass-cheers mr-1 text-yellow-600"></i>Fiesta Date
            </label>
            <input type="date" id="fiesta" name="fiesta" class="form-input">
            <small class="text-gray-500 text-xs">Optional</small>
          </div>
        </div>

        <div class="mb-6">
          <label class="block text-gray-700 font-semibold mb-2">
            <i class="fas fa-tasks mr-1 text-accent"></i>Action/Description <span class="text-red-500">*</span>
          </label>
          <textarea id="event_action" name="event_action" rows="4" class="form-input"></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" id="cancelBtn" class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition">
            <i class="fas fa-times mr-2"></i>Cancel
          </button>
          <button type="submit" class="btn-primary px-6 py-2 rounded-lg">
            <i class="fas fa-save mr-2"></i>Save Event
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    $(document).ready(function() {
      let currentPage = 1;
      let searchTimeout;

      // Load events on page load
      loadEvents(currentPage);

      // Search input listener with debounce
      $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
          currentPage = 1;
          loadEvents(currentPage);
        }, 500);
      });

      // Month filter listener
      $('#monthFilter').on('change', function() {
        currentPage = 1;
        loadEvents(currentPage);
      });

      // Open modal for adding
      $('#addEventBtn').click(function() {
        $('#modalTitle').html('<i class="fas fa-calendar-plus mr-2"></i>Add New Event');
        $('#eventForm')[0].reset();
        $('#eventId').val('');
        $('#eventModal').addClass('active');
      });

      // Close modal
      $('.close, #cancelBtn').click(function() {
        $('#eventModal').removeClass('active');
      });

      // Close modal when clicking outside
      $(window).click(function(event) {
        if (event.target.id == 'eventModal') {
          $('#eventModal').removeClass('active');
        }
      });

      // Submit form (Add/Edit)
      $('#eventForm').submit(function(e) {
        e.preventDefault();

        const eventId = $('#eventId').val();
        const action = eventId ? 'update' : 'create';

        const formData = {
          action: action,
          id: eventId,
          municipality: $('#municipality').val(),
          barangay: $('#barangay').val(),
          event_date: $('#event_date').val(),
          fiesta: $('#fiesta').val(),
          event_action: $('#event_action').val()
        };

        $.ajax({
          url: 'araw-events-crud.php',
          method: 'POST',
          data: formData,
          dataType: 'json',
          success: function(response) {
            if(response.success) {
              showAlert('success', response.message);
              $('#eventModal').removeClass('active');
              loadEvents(currentPage);
            } else {
              showAlert('error', response.message);
            }
          },
          error: function() {
            showAlert('error', 'An error occurred. Please try again.');
          }
        });
      });

      // ✅ NEW: Download PDF (All Events), grouped by month pages
      $('#downloadPdfBtn').on('click', function() {
        downloadMonthlyPdf();
      });

      async function downloadMonthlyPdf() {
        const btn = document.getElementById('downloadPdfBtn');
        btn.disabled = true;
        btn.innerHTML = `<i class="fas fa-spinner fa-spin mr-2"></i>Generating PDF...`;

        try {
          // Pull ALL events (ignore current pagination/search/month filter)
          // IMPORTANT: this assumes your API supports limit large enough.
          // If your dataset can be very large, tell me and I'll switch to an "all=1" backend mode.
          const res = await $.ajax({
            url: 'araw-events-crud.php',
            method: 'GET',
            data: {
              action: 'read',
              page: 1,
              limit: 100000, // big limit for "all"
              search: '',
              month: ''
            },
            dataType: 'json'
          });

          if (!res || !res.success) {
            showAlert('error', res?.message || 'Failed to load events for PDF.');
            return;
          }

          const events = Array.isArray(res.data) ? res.data : [];
          if (events.length === 0) {
            showAlert('error', 'No events to export.');
            return;
          }

          // Group by month (based on event_date)
          const monthNamesFull = [
            'January','February','March','April','May','June',
            'July','August','September','October','November','December'
          ];

          const groups = {}; // {1: [..], 2: [..]}
          events.forEach(ev => {
            if (!ev.event_date) return;
            const d = new Date(ev.event_date);
            if (isNaN(d.getTime())) return;
            const m = d.getMonth() + 1;
            if (!groups[m]) groups[m] = [];
            groups[m].push(ev);
          });

          // Sort months ascending and events inside by date ascending
          const monthsSorted = Object.keys(groups)
            .map(Number)
            .sort((a,b) => a-b);

          monthsSorted.forEach(m => {
            groups[m].sort((a,b) => new Date(a.event_date) - new Date(b.event_date));
          });

          const { jsPDF } = window.jspdf;
          const doc = new jsPDF('p', 'mm', 'a4');

          // Helpers
          const pad2 = n => String(n).padStart(2,'0');
          const fmtShort = (dateStr) => {
            if (!dateStr || dateStr === '0000-00-00') return 'N/A';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return 'N/A';
            return `${monthNamesFull[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
          };

          let isFirstPage = true;

          monthsSorted.forEach((m) => {
            if (!isFirstPage) doc.addPage();
            isFirstPage = false;

            // Page Header (Month)
            doc.setFont('helvetica', 'bold');
            doc.setFontSize(16);
            doc.text(`Araw Events - ${monthNamesFull[m-1]}`, 14, 18);

            doc.setFont('helvetica', 'normal');
            doc.setFontSize(10);
            const generated = new Date();
            doc.text(`Generated: ${generated.toLocaleString()}`, 14, 24);
            doc.text(`Generated by: <?= addslashes($full_name) ?>`, 14, 29);

            // Table data
            const body = groups[m].map(ev => ([
              fmtShort(ev.event_date),
              fmtShort(ev.fiesta),
              ev.municipality || '',
              ev.barangay || '',
              // Some API responses use "action" not "event_action"
              (ev.action || ev.event_action || '').toString()
            ]));

            doc.autoTable({
              startY: 34,
              head: [[ 'Araw Date', 'Fiesta Date', 'Municipality', 'Barangay', 'Action/Description' ]],
              body: body,
              styles: { fontSize: 9, cellPadding: 2 },
              headStyles: { fillColor: [26, 58, 108] },
              columnStyles: {
                0: { cellWidth: 32 },
                1: { cellWidth: 32 },
                2: { cellWidth: 35 },
                3: { cellWidth: 35 },
                4: { cellWidth: 'auto' }
              },
              didDrawPage: function (data) {
                // Footer page number
                const pageCount = doc.internal.getNumberOfPages();
                doc.setFontSize(9);
                doc.text(`Page ${pageCount}`, 200, 290, { align: 'right' });
              }
            });
          });

          const filename = `Araw_Events_Monthly_${new Date().toISOString().slice(0,10)}.pdf`;
          doc.save(filename);

        } catch (err) {
          console.error(err);
          showAlert('error', 'An error occurred while generating the PDF.');
        } finally {
          btn.disabled = false;
          btn.innerHTML = `<i class="fas fa-file-pdf mr-2"></i> Download PDF (All Events)`;
        }
      }

      // Load events function
      function loadEvents(page) {
        const search = $('#searchInput').val();
        const month  = $('#monthFilter').val();
        const limit  = 10;

        $.ajax({
          url: 'araw-events-crud.php',
          method: 'GET',
          data: {
            action: 'read',
            page: page,
            limit: limit,
            search: search,
            month: month
          },
          dataType: 'json',
          success: function(response) {
            if(response.success) {
              let tableBody = '';
              const today = new Date();
              const oneWeekFromNow = new Date();
              oneWeekFromNow.setDate(today.getDate() + 7);

              // Sort events - urgent ones first
              let sortedEvents = response.data.slice().sort(function(a, b) {
                const aDate = new Date(a.event_date);
                const bDate = new Date(b.event_date);
                const aIsUrgent = aDate >= today && aDate <= oneWeekFromNow;
                const bIsUrgent = bDate >= today && bDate <= oneWeekFromNow;

                if (aIsUrgent && !bIsUrgent) return -1;
                if (!aIsUrgent && bIsUrgent) return 1;
                return aDate - bDate;
              });

              if(sortedEvents.length > 0) {
                sortedEvents.forEach(function(event) {
                  // Format event date as "Month Day" only
                  let formattedEventDate = '<span class="text-gray-400">N/A</span>';
                  let isUrgent = false;

                  if (event.event_date && event.event_date !== '0000-00-00') {
                    const eventDate = new Date(event.event_date);

                    if (!isNaN(eventDate.getTime())) {
                      const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                      formattedEventDate = monthNames[eventDate.getMonth()] + ' ' + eventDate.getDate();

                      // Check if event is within 1 week
                      if (eventDate >= today && eventDate <= oneWeekFromNow) {
                        isUrgent = true;
                        const daysUntil = Math.ceil((eventDate - today) / (1000 * 60 * 60 * 24));
                        formattedEventDate = `<span class="urgent-badge"><i class="fas fa-exclamation-circle mr-1"></i>${formattedEventDate} (${daysUntil} day${daysUntil !== 1 ? 's' : ''})</span>`;
                      }
                    }
                  }

                  // Format fiesta date
                  let formattedFiestaDate = '<span class="text-gray-400">N/A</span>';
                  if (event.fiesta && event.fiesta !== '0000-00-00') {
                    const fiestaDate = new Date(event.fiesta);
                    if (!isNaN(fiestaDate.getTime())) {
                      const monthNames = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
                      formattedFiestaDate =
                        '<span class="fiesta-badge"><i class="fas fa-glass-cheers mr-1"></i>' +
                        monthNames[fiestaDate.getMonth()] + ' ' + fiestaDate.getDate() + '</span>';
                    }
                  }

                  const urgentClass = isUrgent ? 'urgent-event' : '';

                  tableBody += `
                    <tr class="${urgentClass}">
                      <td class="px-6 py-4 font-medium">${formattedEventDate}</td>
                      <td class="px-6 py-4">${formattedFiestaDate}</td>
                      <td class="px-6 py-4">${event.municipality}</td>
                      <td class="px-6 py-4">${event.barangay}</td>
                      <td class="px-6 py-4 text-center">
                        <button class="edit-btn text-yellow-500 hover:text-yellow-600 mx-1" data-id="${event.id}" title="Edit">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button class="delete-btn text-red-500 hover:text-red-600 mx-1" data-id="${event.id}" title="Delete">
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  `;
                });
              } else {
                tableBody = `
                  <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                      <i class="fas fa-inbox text-4xl mb-2 block"></i>
                      <p class="font-medium">No events found</p>
                      <p class="text-sm">Try adjusting your search or filter criteria</p>
                    </td>
                  </tr>
                `;
              }
              $('#eventsTableBody').html(tableBody);

              // Build pagination
              const total = response.total || 0;
              const totalPages = Math.ceil(total / limit);
              const start = (page - 1) * limit + 1;
              const end = Math.min(page * limit, total);

              if (total > 0) {
                $('#paginationInfo').html(`Showing ${start}-${end} of ${total} events`);
              } else {
                $('#paginationInfo').html('No events to display');
              }

              let paginationHtml = '';
              if (totalPages > 1) {
                if (page > 1) {
                  paginationHtml += `<button class="pagination-btn" data-page="${page - 1}">
                    <i class="fas fa-chevron-left"></i>
                  </button>`;
                }

                let startPage = Math.max(1, page - 2);
                let endPage = Math.min(totalPages, page + 2);

                if (startPage > 1) {
                  paginationHtml += `<button class="pagination-btn" data-page="1">1</button>`;
                  if (startPage > 2) paginationHtml += `<span class="px-2">...</span>`;
                }

                for (let i = startPage; i <= endPage; i++) {
                  const activeClass = (i === page) ? 'active' : '';
                  paginationHtml += `<button class="pagination-btn ${activeClass}" data-page="${i}">${i}</button>`;
                }

                if (endPage < totalPages) {
                  if (endPage < totalPages - 1) paginationHtml += `<span class="px-2">...</span>`;
                  paginationHtml += `<button class="pagination-btn" data-page="${totalPages}">${totalPages}</button>`;
                }

                if (page < totalPages) {
                  paginationHtml += `<button class="pagination-btn" data-page="${page + 1}">
                    <i class="fas fa-chevron-right"></i>
                  </button>`;
                }
              }
              $('#pagination').html(paginationHtml);
            }
          },
          error: function() {
            $('#eventsTableBody').html(`
              <tr>
                <td colspan="6" class="px-6 py-8 text-center text-red-500">
                  <i class="fas fa-exclamation-triangle text-4xl mb-2 block"></i>
                  <p>Error loading events. Please refresh the page.</p>
                </td>
              </tr>
            `);
          }
        });
      }

      // Pagination click handler
      $(document).on('click', '.pagination-btn', function() {
        currentPage = parseInt($(this).data('page'));
        loadEvents(currentPage);
        $('main').scrollTop(0);
      });

      // Edit event
      $(document).on('click', '.edit-btn', function() {
        const eventId = $(this).data('id');

        $.ajax({
          url: 'araw-events-crud.php',
          method: 'GET',
          data: { action: 'get_single', id: eventId },
          dataType: 'json',
          success: function(response) {
            if(response.success) {
              $('#modalTitle').html('<i class="fas fa-edit mr-2"></i>Edit Event');
              $('#eventId').val(response.data.id);
              $('#municipality').val(response.data.municipality);
              $('#barangay').val(response.data.barangay);
              $('#event_date').val(response.data.event_date);
              $('#fiesta').val(response.data.fiesta);
              $('#event_action').val(response.data.action);
              $('#eventModal').addClass('active');
            }
          }
        });
      });

      // Delete event
      $(document).on('click', '.delete-btn', function() {
        const eventId = $(this).data('id');

        if(confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
          $.ajax({
            url: 'araw-events-crud.php',
            method: 'POST',
            data: { action: 'delete', id: eventId },
            dataType: 'json',
            success: function(response) {
              if(response.success) {
                showAlert('success', response.message);
                const tableRows = $('#eventsTableBody tr').length;
                if (tableRows === 1 && currentPage > 1) currentPage--;
                loadEvents(currentPage);
              } else {
                showAlert('error', response.message);
              }
            }
          });
        }
      });

      // Show alert function
      function showAlert(type, message) {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
        const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const alertHtml = `
          <div class="alert ${alertClass}">
            <i class="fas ${icon} mr-2"></i>${message}
          </div>
        `;

        $('#alertContainer').html(alertHtml);

        setTimeout(function() {
          $('#alertContainer').fadeOut(300, function() {
            $(this).html('').show();
          });
        }, 3000);
      }

      // Make loadEvents accessible globally for pagination
      window.loadEvents = loadEvents;
    });
  </script>
</body>
</html>
