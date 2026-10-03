<?php
require_once 'config/check-session.php';
require_once 'config/conn_pdo.php';

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
  <title>Academic Calendar - STI</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* [Keep all your existing CSS - no changes needed] */
:root {
  --brand: #4a6fc0;
  --brand-2: #6b8ed9;
  --accent: #e74c3c;
  --ink: #2c3e50;
  --muted: #7f8c8d;
  --bg: #f8f9fa;
  --panel: #ffffff;
  --panel-2: #ffffffcc;
  --ring: rgba(79, 106, 173, 0.15);
  --chip-bg: #edf2f7;
  --chip-ink: #2d3748;

  --enroll: #4cc9f0;
  --train: #f9c74f;
  --orient: #90be6d;
  --dead: #f8961e;
  --exam: #f94144;
  --meet: #7209b7;
  --event: #4361ee;

  --radius: 12px;
  --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  --transition: all 0.3s ease;
  --container-max: 1366px;
}

* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

body {
  background: linear-gradient(135deg, #f5f7fa 0%, #e4e7eb 100%);
  color: var(--ink);
  padding: 20px;
  min-height: 100vh;
  line-height: 1.6;
}

.container{
  max-width: var(--container-max);
  width: 100%;
  margin: 0 auto;
  padding-inline: clamp(12px, 2vw, 40px);
}

.top-controls {
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 20px; flex-wrap: wrap; gap: 12px;
}

.btn-chip {
  background: linear-gradient(135deg, var(--brand), var(--brand-2));
  color: #fff; border: 0; border-radius: 40px; cursor: pointer;
  padding: 12px 20px; font-weight: 700; display: flex; align-items: center; gap: 8px;
  transition: var(--transition); box-shadow: var(--shadow);
}
.btn-chip:hover { transform: translateY(-2px); box-shadow: 0 12px 30px -5px rgba(79,111,173,0.4); }
.btn-chip.secondary { background: var(--chip-bg); color: var(--chip-ink); }

header {
  text-align: center; padding: 30px 20px; background: var(--panel);
  border-radius: var(--radius); margin-bottom: 30px; box-shadow: var(--shadow);
  position: relative; overflow: hidden;
}
header::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px;
  background: linear-gradient(to right, var(--brand), var(--accent));
}
header h1 {
  font-size: clamp(1.8rem, 2.4vw, 2.6rem);
  color: var(--brand); display: flex; align-items: center; justify-content: center; gap: 12px; margin-bottom: 10px;
}
.academic-year {
  background: var(--accent); color: #fff; display: inline-block; padding: 8px 22px; border-radius: 30px;
  font-weight: 700; font-size: 1rem; margin: 15px 0; box-shadow: 0 4px 10px rgba(231,76,60,.3);
}
header p { color: var(--muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

.legend {
  display: flex; justify-content: center; gap: clamp(8px, 1vw, 16px); flex-wrap: wrap; margin: 25px 0; padding: 16px;
  background: var(--panel); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid rgba(0,0,0,.05);
}
.legend-item {
  display: flex; align-items: center; gap: 8px; font-weight: 600; color: var(--ink);
  padding: 6px 12px; border-radius: 20px; background: var(--chip-bg); transition: var(--transition); cursor: pointer;
}
.legend-item:hover { transform: translateY(-2px); }
.legend-item.active { background: var(--brand); color: #fff; }
.legend-color { width: 16px; height: 16px; border-radius: 4px; }
.all-events .legend-color { background-color: var(--brand); }
.enrollment .legend-color { background-color: var(--enroll); }
.training .legend-color { background-color: var(--train); }
.orientation .legend-color { background-color: var(--orient); }
.deadline .legend-color { background-color: var(--dead); }
.examination .legend-color { background-color: var(--exam); }
.meeting .legend-color { background-color: var(--meet); }
.event .legend-color { background-color: var(--event); }

.calendar-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: clamp(16px, 1.5vw, 25px);
}
.month-card {
  background: var(--panel); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow);
  transition: var(--transition); position: relative; overflow: hidden; border: 1px solid rgba(0,0,0,.05);
  animation: fadeIn 0.5s ease-out;
}
.month-card.hidden { display: none; }
.month-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px -10px rgba(0,0,0,.15); }
.month-header { padding-bottom: 15px; margin-bottom: 15px; border-bottom: 1px solid rgba(0,0,0,.08); text-align: center; }
.month-header h2 {
  font-size: clamp(1.1rem, 1.25vw, 1.4rem);
  color: var(--brand); display: flex; align-items: center; justify-content: center; gap: 10px;
}

.calendar { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; margin-bottom: 15px; }
.day-header { text-align: center; font-weight: 700; padding: 10px 0; color: var(--muted); font-size: 0.85rem; }

.day-cell {
  aspect-ratio: 1; background: #fbfcfe; display: flex; flex-direction: column; justify-content: flex-start; align-items: flex-start;
  padding: 8px; border-radius: 10px; position: relative; overflow: hidden; transition: var(--transition);
  border: 1px solid rgba(0,0,0,.05); cursor: pointer;
}
.day-cell:hover { background: #edf2ff; }
.day-cell.has-event { background: rgba(79,111,173,.05); outline: 2px dashed rgba(79,111,173,.2); outline-offset: -4px; }
.day-number { font-weight: 700; font-size: 0.95rem; color: var(--ink); margin-bottom: 6px; }
.day-cell.today .day-number {
  background: var(--accent); color: white; width: 26px; height: 26px; display: flex; align-items: center; justify-content: center;
  border-radius: 50%; margin: -4px 0 4px -4px;
}

.ev-dots { display: flex; flex-wrap: wrap; gap: 4px; margin-top: auto; width: 100%; justify-content: center; min-height: 10px; padding: 2px; }
.ev-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; box-shadow: 0 0 3px rgba(0,0,0,.2); }

.events-list { list-style: none; margin-top: 12px; border-top: 1px dashed rgba(0,0,0,.1); padding-top: 12px; }
.event-item {
  background: #fafbfc; border-left: 4px solid; padding: 12px; margin-bottom: 12px; border-radius: 0 8px 8px 0;
  position: relative; border: 1px solid rgba(0,0,0,.05); transition: var(--transition);
}
.event-item:hover { transform: translateX(3px); }
.event-date { font-weight: 700; color: var(--brand); display: block; margin-bottom: 5px; font-size: 0.95rem; }
.event-desc { color: var(--ink); line-height: 1.5; font-size: 0.92rem; font-weight: bold; }
.event-category { position: absolute; top: 12px; right: 12px; font-size: 0.7rem; background: rgba(0,0,0,.05); color: var(--ink); padding: 4px 10px; border-radius: 999px; font-weight: 700; }
.event-actions { position: absolute; top: 10px; right: 80px; display: flex; gap: 10px; }
.event-actions i { cursor: pointer; color: var(--muted); }
.event-actions i:hover { color: var(--brand); }

.enrollment { border-left-color: var(--enroll); }
.training { border-left-color: var(--train); }
.orientation { border-left-color: var(--orient); }
.deadline { border-left-color: var(--dead); }
.examination { border-left-color: var(--exam); }
.meeting { border-left-color: var(--meet); }
.event { border-left-color: var(--event); }

.enrollment .ev-dot, .enrollment.wide-dot { background-color: var(--enroll); }
.training .ev-dot, .training.wide-dot { background-color: var(--train); }
.orientation .ev-dot, .orientation.wide-dot { background-color: var(--orient); }
.deadline .ev-dot, .deadline.wide-dot { background-color: var(--dead); }
.examination .ev-dot, .examination.wide-dot { background-color: var(--exam); }
.meeting .ev-dot, .meeting.wide-dot { background-color: var(--meet); }
.event .ev-dot, .event.wide-dot { background-color: var(--event); }

.day-cell.cat-enrollment, .wide-cell.cat-enrollment { border-color: var(--enroll); box-shadow: inset 0 0 0 2px var(--enroll); }
.day-cell.cat-training,   .wide-cell.cat-training   { border-color: var(--train); box-shadow: inset 0 0 0 2px var(--train); }
.day-cell.cat-orientation,.wide-cell.cat-orientation{ border-color: var(--orient); box-shadow: inset 0 0 0 2px var(--orient); }
.day-cell.cat-deadline,   .wide-cell.cat-deadline   { border-color: var(--dead);   box-shadow: inset 0 0 0 2px var(--dead); }
.day-cell.cat-examination,.wide-cell.cat-examination{ border-color: var(--exam);   box-shadow: inset 0 0 0 2px var(--exam); }
.day-cell.cat-meeting,    .wide-cell.cat-meeting    { border-color: var(--meet);   box-shadow: inset 0 0 0 2px var(--meet); }
.day-cell.cat-event,      .wide-cell.cat-event      { border-color: var(--event);  box-shadow: inset 0 0 0 2px var(--event); }

.day-cell.multi-cat, .wide-cell.multi-cat {
  border: 2px solid transparent;
  background:
    linear-gradient(#fbfcfe, #fbfcfe) padding-box,
    var(--mix, conic-gradient(var(--brand), var(--accent))) border-box;
}

footer { text-align: center; color: var(--muted); padding: 25px; font-size: 0.95rem; margin-top: 40px; }

@media (max-width: 768px) {
  .calendar-grid { grid-template-columns: 1fr; }
  header h1 { font-size: 2rem; }
  .legend { flex-direction: column; align-items: center; }
  .top-controls { flex-direction: column; align-items: stretch; }
  .btn-chip { justify-content: center; }
}

@media (min-width: 1440px){
  :root { --container-max: 1600px; }
  .calendar-grid { grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: clamp(18px, 1.2vw, 32px); }
}

@media (min-width: 1720px){
  :root { --container-max: 1800px; }
  .calendar-grid { grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); }
}

@media (min-width: 2000px){
  :root { --container-max: 2000px; }
  .calendar-grid { grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
}

.wide-overlay {
  position: fixed; inset: 0; z-index: 1200; display: none; overflow: auto;
  background: rgba(247,247,249,.95); backdrop-filter: blur(5px); padding: 20px 0 40px;
}
.wide-wrap {
  width: min(95vw, var(--container-max));
  margin: 0 auto;
  padding: 0 20px;
}
.wide-top {
  position: sticky; top: 15px; z-index: 3; display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
  background: #ffffff; border: 1px solid rgba(0,0,0,.1); box-shadow: var(--shadow); border-radius: var(--radius); padding: 12px 20px;
}
.wide-title { font-weight: 800; letter-spacing: .2px; font-size: 1.4rem; color: var(--brand); display: flex; align-items: center; gap: 10px; }
.wide-actions { display: flex; gap: 10px; }
.wide-btn {
  background: linear-gradient(135deg, var(--brand), var(--brand-2)); color: #fff; border: 0; padding: 10px 16px; border-radius: var(--radius);
  cursor: pointer; font-weight: 700; display: flex; align-items: center; gap: 8px; transition: var(--transition);
}
.wide-btn:hover { filter: brightness(1.1); transform: translateY(-2px); }
.wide-secondary { background: var(--chip-bg); color: var(--chip-ink); border: 1px solid rgba(0,0,0,.1); }
.wide-secondary:hover { background: #e2e8f0; }
.wide-grid { width: 100%; border-collapse: separate; border-spacing: clamp(8px, 1vw, 14px); table-layout: fixed; }
.wide-grid thead th {
  color: var(--ink); font-size: 0.9rem; font-weight: 800; letter-spacing: .4px; text-align: center; padding: 15px 0;
  background: var(--panel); border-radius: var(--radius); box-shadow: var(--shadow);
}
.wide-cell {
  background: var(--panel); border: 1px solid rgba(0,0,0,.08); border-radius: var(--radius); padding: 12px;
  height: clamp(140px, 18vh, 240px); vertical-align: top;
  color: var(--ink); position: relative; box-shadow: 0 4px 10px rgba(0,0,0,.05); overflow: hidden; transition: var(--transition);
}
.wide-cell:hover { border-color: var(--brand); transform: translateY(-3px); box-shadow: 0 8px 15px rgba(0,0,0,.08); }
.wide-daynum {
  font-weight: 800; font-size: 1rem; color: var(--ink); position: absolute; top: 10px; right: 12px;
  width: 26px; height: 26px; display: flex; align-items: center; justify-content: center; border-radius: 50%;
}
.wide-cell.today .wide-daynum { background: var(--accent); color: white; }
.wide-events { margin-top: 25px; max-height: 110px; overflow-y: auto; padding-right: 5px; }
.wide-events::-webkit-scrollbar { width: 4px; }
.wide-events::-webkit-scrollbar-thumb { background: var(--muted); border-radius: 10px; }
.wide-event {
  display: flex; align-items: center; gap: 8px; background: var(--chip-bg); border-left: 3px solid; padding: 8px; border-radius: 6px;
  margin: 8px 0; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.85rem; font-weight: bold;
}
.wide-event:hover { transform: translateX(3px); }
.wide-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 0 3px rgba(0,0,0,.2); }
.wide-event.enrollment { border-left-color: var(--enroll); }
.wide-event.training   { border-left-color: var(--train); }
.wide-event.orientation{ border-left-color: var(--orient); }
.wide-event.deadline   { border-left-color: var(--dead); }
.wide-event.examination{ border-left-color: var(--exam); }
.wide-event.meeting    { border-left-color: var(--meet); }
.wide-event.event      { border-left-color: var(--event); }
.wide-more { font-size: 0.82rem; color: var(--muted); margin-top: 8px; text-align: center; cursor: pointer; }
.wide-more:hover { color: var(--brand); }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px);} to { opacity: 1; transform: translateY(0);} }

.fab {
  position: fixed; bottom: 30px; right: 30px; background: linear-gradient(135deg, var(--brand), var(--brand-2));
  color: #fff; border: none; border-radius: 50%; width: 60px; height: 60px; font-size: 24px; cursor: pointer; box-shadow: var(--shadow);
  transition: var(--transition); z-index: 1000;
}
.fab:hover { transform: scale(1.1); box-shadow: 0 12px 30px rgba(79,111,173,.4); }

.modal { display: none; position: fixed; z-index: 1300; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
.modal-content { background-color: var(--panel); margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: var(--radius); box-shadow: var(--shadow); }
.close { color: #aaa; float: right; font-size: 28px; font-weight: bold; }
.close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
#eventForm label { display: block; margin-top: 10px; }
#eventForm input, #eventForm select { width: 100%; padding: 8px; margin-top: 5px; border: 1px solid #ccc; border-radius: 4px; }
#eventForm button { margin-top: 20px; padding: 10px; background: var(--brand); color: white; border: none; cursor: pointer; width: 100%; border-radius: 4px; }

.loading { text-align: center; padding: 40px; color: var(--muted); }
.error-message { background: #fee; color: #c33; padding: 15px; border-radius: var(--radius); margin: 20px 0; border-left: 4px solid #c33; }

/* Year dropdown styling */
.year-select-wrap{
  display:flex; gap:10px; align-items:center; justify-content:center;
  margin-top: 10px;
}
.year-select-wrap label{ font-weight: 800; color: var(--ink); }
#yearSelect{
  padding: 10px 14px;
  border-radius: 999px;
  border: 1px solid rgba(0,0,0,.15);
  background: #fff;
  font-weight: 800;
  color: var(--brand);
  box-shadow: 0 6px 14px rgba(0,0,0,.08);
  cursor: pointer;
}
</style>
</head>

<body>
  <div class="container">
    <div class="top-controls">
      <button class="btn-chip secondary" id="backBtn"><i class="fas fa-arrow-left"></i> Back</button>
      <button class="btn-chip secondary" id="clearFilterBtn" style="display: none;"><i class="fas fa-filter"></i> Clear Filter</button>
      <button class="btn-chip" id="wideBtn"><i class="fas fa-expand"></i> Wide Calendar View</button>
    </div>

    <header>
      <h1><i class="fas fa-calendar-alt"></i> Academic Calendar</h1>

      <div class="year-select-wrap">
        <label for="yearSelect"><i class="fa-solid fa-calendar-days"></i> Year:</label>
        <select id="yearSelect"></select>
      </div>

      <div class="academic-year" id="schoolYearBadge">School Year: 2025–2026</div>
      <p>STI Institutional Activities and Important Dates</p>
    </header>

    <div class="legend">
      <div class="legend-item all-events active" data-cat="all"><div class="legend-color"></div><span>All Events</span></div>
      <div class="legend-item enrollment" data-cat="enrollment"><div class="legend-color"></div><span>Enrollment</span></div>
      <div class="legend-item training" data-cat="training"><div class="legend-color"></div><span>Training & Workshop</span></div>
      <div class="legend-item orientation" data-cat="orientation"><div class="legend-color"></div><span>Orientation</span></div>
      <div class="legend-item deadline" data-cat="deadline"><div class="legend-color"></div><span>Submission Deadline</span></div>
      <div class="legend-item examination" data-cat="examination"><div class="legend-color"></div><span>Examination</span></div>
      <div class="legend-item meeting" data-cat="meeting"><div class="legend-color"></div><span>Meeting</span></div>
      <div class="legend-item event" data-cat="event"><div class="legend-color"></div><span>Special Event</span></div>
    </div>

    <div id="loadingIndicator" class="loading" style="display:none;">
      <i class="fas fa-spinner fa-spin fa-3x"></i>
      <p>Loading calendar events...</p>
    </div>

    <div id="errorContainer"></div>

    <!-- Dynamic month cards -->
    <div class="calendar-grid" id="calendarGrid"></div>

    <footer><p>Doc Vic Portal &copy; <span id="footerYear"></span> | JIMZ</p></footer>
    <button class="fab" id="addEventBtn"><i class="fas fa-plus"></i></button>

    <!-- Wide View -->
    <div id="wideOverlay" class="wide-overlay">
      <div class="wide-wrap">
        <div class="wide-top">
          <div class="wide-title" id="wideTitle"><i class="far fa-calendar"></i> Month Year</div>
          <div class="wide-actions">
            <button class="wide-btn wide-secondary" id="wideBack"><i class="fas fa-times"></i> Close</button>
            <button class="wide-btn wide-secondary" id="widePrev"><i class="fas fa-chevron-left"></i> Prev</button>
            <button class="wide-btn" id="wideNext">Next <i class="fas fa-chevron-right"></i></button>
          </div>
        </div>
        <table class="wide-grid" id="wideGrid"></table>
      </div>
    </div>

    <!-- Modal -->
    <div id="eventModal" class="modal">
      <div class="modal-content">
        <span class="close">&times;</span>
        <h2 id="modalTitle">Add Event</h2>
        <form id="eventForm">
          <input type="hidden" id="eventId">
          <label for="startDate">Start Date:</label>
          <input type="date" id="startDate" required>
          <label for="endDate">End Date (optional):</label>
          <input type="date" id="endDate">
          <label for="title">Event Title:</label>
          <input type="text" id="title" required>
          <label for="category">Category:</label>
          <select id="category" required>
            <option value="enrollment">Enrollment</option>
            <option value="training">Training & Workshop</option>
            <option value="orientation">Orientation</option>
            <option value="deadline">Submission Deadline</option>
            <option value="examination">Examination</option>
            <option value="meeting">Meeting</option>
            <option value="event">Special Event</option>
          </select>
          <button type="submit">Save Event</button>
        </form>
      </div>
    </div>
  </div>

<script>
  /* ===== Config & Helpers ===== */
  const YEAR_MIN = 2025;
  const YEAR_MAX = 2050;

  const MONTHS = ["January","February","March","April","May","June","July","August","September","October","November","December"];
  const MONTH_ICONS = ["snowflake","heart","seedling","flower","graduation-cap","sun","umbrella-beach","sun","leaf","cloud-sun","turkey","snowflake"];
  const DAY_NAMES = ['SUN','MON','TUE','WED','THU','FRI','SAT'];

  const CAT_LIST = ['enrollment','training','orientation','deadline','examination','meeting','event'];
  const CAT_CLASS_LIST = CAT_LIST.map(c => `cat-${c}`);
  const CAT_VAR = {
    enrollment: '--enroll',
    training: '--train',
    orientation: '--orient',
    deadline: '--dead',
    examination: '--exam',
    meeting: '--meet',
    event: '--event'
  };

  let events = [];
  let activeFilter = 'all';
  let wideMonth = new Date().getMonth();

  // Year state
  let currentYear = YEAR_MIN;

  // Back button
  document.getElementById('backBtn').addEventListener('click', () => window.history.back());

  function mkDateStr(y, mIndex, d) {
    const mm = String(mIndex + 1).padStart(2, '0');
    const dd = String(d).padStart(2, '0');
    return `${y}-${mm}-${dd}`;
  }

  function* dateRangeInclusive(y, sm, sd, em, ed) {
    let d = new Date(y, sm, sd);
    const end = new Date(y, em, ed);
    while (d <= end) {
      yield `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
      d.setDate(d.getDate() + 1);
    }
  }

  function showError(message) {
    const errorContainer = document.getElementById('errorContainer');
    errorContainer.innerHTML = `<div class="error-message"><strong>Error:</strong> ${message}</div>`;
    setTimeout(() => errorContainer.innerHTML = '', 5000);
  }

  function showLoading(show) {
    document.getElementById('loadingIndicator').style.display = show ? 'block' : 'none';
  }

  function updateHeaderYear() {
    document.getElementById('schoolYearBadge').textContent = `School Year: ${currentYear}–${currentYear + 1}`;
    document.getElementById('footerYear').textContent = currentYear;
  }

  /* ===== Build Month Cards Dynamically ===== */
  function buildMonthCards() {
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    const today = new Date();

    for (let m = 0; m < 12; m++) {
      const firstDay = new Date(currentYear, m, 1).getDay();
      const daysInMonth = new Date(currentYear, m + 1, 0).getDate();

      const card = document.createElement('div');
      card.className = 'month-card';
      card.dataset.month = String(m);

      card.innerHTML = `
        <div class="month-header">
          <h2><i class="fas fa-${MONTH_ICONS[m]}"></i> ${MONTHS[m]} ${currentYear}</h2>
        </div>
        <div class="calendar"></div>
        <ul class="events-list"></ul>
      `;

      const cal = card.querySelector('.calendar');

      // day headers
      ["Sun","Mon","Tue","Wed","Thu","Fri","Sat"].forEach(dn => {
        const dh = document.createElement('div');
        dh.className = 'day-header';
        dh.textContent = dn;
        cal.appendChild(dh);
      });

      // blanks before first day
      for (let i = 0; i < firstDay; i++) {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        cell.innerHTML = `<div class="ev-dots"></div>`;
        cal.appendChild(cell);
      }

      // days
      for (let day = 1; day <= daysInMonth; day++) {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        cell.dataset.date = mkDateStr(currentYear, m, day);
        cell.innerHTML = `<div class="day-number">${day}</div><div class="ev-dots"></div>`;

        if (today.getFullYear() === currentYear && today.getMonth() === m && today.getDate() === day) {
          cell.classList.add('today');
        }

        cal.appendChild(cell);
      }

      // trailing blanks to complete the last week
      const used = firstDay + daysInMonth;
      const trailing = (7 - (used % 7)) % 7;
      for (let i = 0; i < trailing; i++) {
        const cell = document.createElement('div');
        cell.className = 'day-cell';
        cell.innerHTML = `<div class="ev-dots"></div>`;
        cal.appendChild(cell);
      }

      grid.appendChild(card);
    }
  }

  /* ===== API Functions ===== */
  async function fetchEvents() {
    try {
      showLoading(true);
      const response = await fetch(`api/calendar.php?action=get_all&year=${encodeURIComponent(currentYear)}`);
      const data = await response.json();

      if (data.success) {
        events = data.events || [];
        renderEvents();
        filterEvents(activeFilter);
      } else {
        showError(data.error || 'Failed to load events');
      }
    } catch (error) {
      showError('Network error: ' + error.message);
    } finally {
      showLoading(false);
    }
  }

  async function saveEvent(eventData) {
    try {
      showLoading(true);
      const formData = new FormData();
      formData.append('action', eventData.id ? 'update' : 'add');
      formData.append('id', eventData.id || '');
      formData.append('start_date', eventData.start);
      formData.append('end_date', eventData.end || '');
      formData.append('title', eventData.title);
      formData.append('category', eventData.cat);
      formData.append('year', String(currentYear));

      const response = await fetch('api/calendar.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        await fetchEvents();
        return true;
      } else {
        showError(data.error || 'Failed to save event');
        return false;
      }
    } catch (error) {
      showError('Network error: ' + error.message);
      return false;
    } finally {
      showLoading(false);
    }
  }

  async function deleteEvent(eventId) {
    try {
      showLoading(true);
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', eventId);

      const response = await fetch('api/calendar.php', {
        method: 'POST',
        body: formData
      });

      const data = await response.json();

      if (data.success) {
        await fetchEvents();
        return true;
      } else {
        showError(data.error || 'Failed to delete event');
        return false;
      }
    } catch (error) {
      showError('Network error: ' + error.message);
      return false;
    } finally {
      showLoading(false);
    }
  }

  function applyHighlightToCell(cell, cats) {
    if (!cell) return;
    CAT_CLASS_LIST.forEach(cc => cell.classList.remove(cc));
    cell.classList.remove('multi-cat');
    cell.style.removeProperty('--mix');

    if (!cats || cats.length === 0) return;

    if (cats.length === 1) {
      cell.classList.add(`cat-${cats[0]}`);
    } else {
      const stops = [];
      const step = 100 / cats.length;
      let start = 0;
      cats.forEach((c, i) => {
        const color = `var(${CAT_VAR[c]})`;
        const end = i === cats.length - 1 ? 100 : start + step;
        stops.push(`${color} ${start}% ${end}%`);
        start = end;
      });
      const gradient = `conic-gradient(${stops.join(',')})`;
      cell.classList.add('multi-cat');
      cell.style.setProperty('--mix', gradient);
    }
  }

  function renderEvents() {
    document.querySelectorAll('.events-list').forEach(list => list.innerHTML = '');
    document.querySelectorAll('.day-cell').forEach(cell => {
      cell.classList.remove('has-event');
      const dotsContainer = cell.querySelector('.ev-dots');
      if (dotsContainer) dotsContainer.innerHTML = '';
      applyHighlightToCell(cell, []);
    });

    const catMap = {};

    events.forEach(event => {
      if (activeFilter !== 'all' && event.cat !== activeFilter) return;

      const [y, m, d] = event.start.split('-').map(Number);
      if (y !== currentYear) return;

      const end = event.end ? event.end : event.start;
      const [ey, em, ed] = end.split('-').map(Number);

      for (const date of dateRangeInclusive(y, m - 1, d, em - 1, ed)) {
        if (!date.startsWith(String(currentYear) + "-")) continue;

        const cell = document.querySelector(`.day-cell[data-date="${date}"]`);
        if (cell) {
          cell.classList.add('has-event');
          catMap[date] = catMap[date] || new Set();
          catMap[date].add(event.cat);

          let dotsContainer = cell.querySelector('.ev-dots');
          if (!dotsContainer) {
            const dc = document.createElement('div');
            dc.className = 'ev-dots';
            cell.appendChild(dc);
            dotsContainer = dc;
          }
          const dot = document.createElement('span');
          dot.className = `ev-dot ${event.cat}`;
          dotsContainer.appendChild(dot);
        }
      }

      const [, sm, sd] = event.start.split('-').map(Number);
      const monthCard = document.querySelector(`.month-card[data-month="${sm - 1}"]`);
      if (monthCard) {
        const list = monthCard.querySelector('.events-list');
        const li = document.createElement('li');
        li.className = `event-item ${event.cat}`;
        li.dataset.eventId = event.id;

        const endLabel = (event.end && event.end !== event.start)
          ? ` - ${MONTHS[em - 1]} ${ed}`
          : '';

        li.innerHTML = `
          <span class="event-date">${MONTHS[sm - 1]} ${sd}${endLabel}</span>
          <span class="event-desc">${event.title}</span>
          <span class="event-category">${event.cat.charAt(0).toUpperCase() + event.cat.slice(1)}</span>
          <div class="event-actions">
            <i class="fas fa-edit" data-action="edit" data-event-id="${event.id}"></i>
            <i class="fas fa-trash" data-action="delete" data-event-id="${event.id}"></i>
          </div>
        `;
        list.appendChild(li);
      }
    });

    for (const [date, setCats] of Object.entries(catMap)) {
      const cell = document.querySelector(`.day-cell[data-date="${date}"]`);
      if (cell) applyHighlightToCell(cell, Array.from(setCats));
    }
  }

  /* ===== Filtering ===== */
  function filterEvents(category) {
    activeFilter = category;
    const cards = document.querySelectorAll('.month-card');
    const clearBtn = document.getElementById('clearFilterBtn');

    if (category === 'all') {
      cards.forEach(card => card.classList.remove('hidden'));
      clearBtn.style.display = 'none';
      document.querySelectorAll('.legend-item').forEach(item => item.classList.toggle('active', item.dataset.cat === 'all'));
    } else {
      cards.forEach(card => {
        const mIndex = parseInt(card.dataset.month, 10);
        const hasEvents = events.some(event => {
          if (event.cat !== category) return false;
          const [y, m] = event.start.split('-').map(Number);
          if (y !== currentYear) return false;
          const end = event.end || event.start;
          const [, em] = end.split('-').map(Number);
          return mIndex >= m - 1 && mIndex <= em - 1;
        });
        card.classList.toggle('hidden', !hasEvents);
      });
      clearBtn.style.display = 'inline-flex';
      document.querySelectorAll('.legend-item').forEach(item => item.classList.toggle('active', item.dataset.cat === category));
    }

    renderEvents();
    renderWideCalendar();
  }

  document.querySelectorAll('.legend-item').forEach(item => item.addEventListener('click', () => filterEvents(item.dataset.cat)));
  document.getElementById('clearFilterBtn').addEventListener('click', () => filterEvents('all'));

  /* ===== Wide Calendar View ===== */
  function renderWideCalendar() {
    const grid = document.getElementById('wideGrid');
    const title = document.getElementById('wideTitle');
    title.innerHTML = `<i class="far fa-calendar"></i> ${MONTHS[wideMonth]} ${currentYear}`;
    grid.innerHTML = '';

    const firstDay = new Date(currentYear, wideMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, wideMonth + 1, 0).getDate();
    const weeks = Math.ceil((daysInMonth + firstDay) / 7);

    const thead = document.createElement('thead');
    thead.innerHTML = DAY_NAMES.map(day => `<th>${day}</th>`).join('');
    grid.appendChild(thead);

    const tbody = document.createElement('tbody');
    const today = new Date();
    let day = 1;

    for (let i = 0; i < weeks; i++) {
      const row = document.createElement('tr');
      for (let j = 0; j < 7; j++) {
        const cell = document.createElement('td');
        cell.className = 'wide-cell';

        if ((i === 0 && j < firstDay) || day > daysInMonth) {
          row.appendChild(cell);
          continue;
        }

        const dateStr = mkDateStr(currentYear, wideMonth, day);
        cell.dataset.date = dateStr;

        if (today.getFullYear() === currentYear &&
            today.getMonth() === wideMonth &&
            today.getDate() === day) {
          cell.classList.add('today');
        }

        cell.innerHTML = `<div class="wide-daynum">${day}</div><div class="wide-events"></div>`;
        const eventsDiv = cell.querySelector('.wide-events');

        const dayEvents = events.filter(e => {
          if (activeFilter !== 'all' && e.cat !== activeFilter) return false;
          const [y, m, d] = e.start.split('-').map(Number);
          if (y !== currentYear) return false;

          const end = e.end || e.start;
          const [ey, em, ed] = end.split('-').map(Number);

          const startDate = new Date(y, m - 1, d);
          const endDate = new Date(ey, em - 1, ed);
          const currDate = new Date(currentYear, wideMonth, day);
          return currDate >= startDate && currDate <= endDate;
        });

        dayEvents.forEach(e => {
          const eventDiv = document.createElement('div');
          eventDiv.className = `wide-event ${e.cat}`;
          eventDiv.innerHTML = `<span class="wide-dot ${e.cat}"></span><span>${e.title}</span>`;
          eventsDiv.appendChild(eventDiv);
        });

        const catSet = Array.from(new Set(dayEvents.map(e => e.cat)));
        applyHighlightToCell(cell, catSet);

        if (dayEvents.length > 2) {
          const more = document.createElement('div');
          more.className = 'wide-more';
          more.textContent = `+${dayEvents.length - 2} more`;
          eventsDiv.appendChild(more);
        }

        row.appendChild(cell);
        day++;
      }
      tbody.appendChild(row);
    }
    grid.appendChild(tbody);
  }

  document.getElementById('wideBtn').addEventListener('click', () => {
    document.getElementById('wideOverlay').style.display = 'block';
    renderWideCalendar();
  });
  document.getElementById('wideBack').addEventListener('click', () => {
    document.getElementById('wideOverlay').style.display = 'none';
  });
  document.getElementById('widePrev').addEventListener('click', () => {
    wideMonth = wideMonth === 0 ? 11 : wideMonth - 1;
    renderWideCalendar();
  });
  document.getElementById('wideNext').addEventListener('click', () => {
    wideMonth = wideMonth === 11 ? 0 : wideMonth + 1;
    renderWideCalendar();
  });

  /* ===== Event Modal ===== */
  const modal = document.getElementById('eventModal');
  const form = document.getElementById('eventForm');
  const modalTitle = document.getElementById('modalTitle');
  const addBtn = document.getElementById('addEventBtn');
  const closeBtn = document.querySelector('.modal .close');

  function openModal(event = null) {
    modalTitle.textContent = event ? 'Edit Event' : 'Add Event';
    if (event) {
      document.getElementById('startDate').value = event.start;
      document.getElementById('endDate').value = event.end || '';
      document.getElementById('title').value = event.title;
      document.getElementById('category').value = event.cat;
      document.getElementById('eventId').value = event.id;
    } else {
      form.reset();
      document.getElementById('eventId').value = '';
    }
    modal.style.display = 'block';
  }

  addBtn.addEventListener('click', () => openModal());
  closeBtn.addEventListener('click', () => modal.style.display = 'none');
  window.addEventListener('click', e => { if (e.target === modal) modal.style.display = 'none'; });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const eventData = {
      id: document.getElementById('eventId').value || '',
      start: document.getElementById('startDate').value,
      end: document.getElementById('endDate').value || null,
      title: document.getElementById('title').value,
      cat: document.getElementById('category').value
    };

    // Guard: keep events inside selected year (prevents confusion)
    if (!eventData.start || !eventData.start.startsWith(String(currentYear) + "-")) {
      showError(`Start Date must be within year ${currentYear}.`);
      return;
    }
    if (eventData.end && !eventData.end.startsWith(String(currentYear) + "-")) {
      showError(`End Date must be within year ${currentYear}.`);
      return;
    }

    const success = await saveEvent(eventData);
    if (success) modal.style.display = 'none';
  });

  document.addEventListener('click', async (e) => {
    const action = e.target.dataset.action;
    const id = e.target.dataset.eventId;
    if (action && id) {
      if (action === 'edit') {
        const ev = events.find(evv => String(evv.id) === String(id));
        if (ev) openModal(ev);
      } else if (action === 'delete') {
        if (confirm('Are you sure you want to delete this event?')) {
          await deleteEvent(id);
        }
      }
    }
  });

  /* ===== Year Dropdown ===== */
  function initYearSelect() {
    const sel = document.getElementById('yearSelect');
    sel.innerHTML = '';

    for (let y = YEAR_MIN; y <= YEAR_MAX; y++) {
      const opt = document.createElement('option');
      opt.value = String(y);
      opt.textContent = String(y);
      sel.appendChild(opt);
    }

    // Default year = current year if in range, else YEAR_MIN
    const nowYear = new Date().getFullYear();
    currentYear = (nowYear >= YEAR_MIN && nowYear <= YEAR_MAX) ? nowYear : YEAR_MIN;
    sel.value = String(currentYear);

    sel.addEventListener('change', async () => {
      currentYear = parseInt(sel.value, 10);
      updateHeaderYear();
      buildMonthCards();
      // keep wideMonth same (or reset to Jan if you want)
      await fetchEvents();
      renderWideCalendar();
    });
  }

  /* ===== Initialization ===== */
  document.addEventListener('DOMContentLoaded', async () => {
    initYearSelect();
    updateHeaderYear();
    buildMonthCards();
    await fetchEvents();
  });
</script>
</body>
</html>
