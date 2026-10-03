<?php
/**
 * calendar_schedule.php
 * Month-by-month schedule calendar (Jan → Apr 2026) with Prev/Next navigation
 *
 * ✅ Month pages: January, February, March, April 2026
 * ✅ Prev / Next month buttons (bounded Jan–Apr)
 * ✅ Add schedules per day (ONLY within enabled range)
 * ✅ Delete schedules
 * ✅ Download schedules as JSON or CSV
 * ✅ Stores data locally in /data (JSON)
 *
 * Enabled scheduling range:
 *   March 15, 2026 → April 18, 2026 (inclusive)
 */

require_once '../config/check-session.php'; // remove if you want public access

// -----------------------------
// Helpers
// -----------------------------
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function ensure_dir($dir) {
  if (!is_dir($dir)) mkdir($dir, 0775, true);
}

function read_json_file($path) {
  if (!file_exists($path)) return [];
  $raw = file_get_contents($path);
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function write_json_file_atomic($path, $data) {
  $tmp = $path . '.tmp';
  $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  if ($json === false) $json = "{}";
  file_put_contents($tmp, $json, LOCK_EX);
  rename($tmp, $path);
}

function date_key(DateTime $dt) { return $dt->format('Y-m-d'); }

function in_range(DateTime $d, DateTime $start, DateTime $end) {
  return $d >= $start && $d <= $end;
}

function clamp_str($s, $max = 140) {
  $s = trim((string)$s);
  if (mb_strlen($s) > $max) $s = mb_substr($s, 0, $max);
  return $s;
}

function valid_time($t) {
  if ($t === '' || $t === null) return true;
  return (bool)preg_match('/^\d{2}:\d{2}$/', $t);
}

function csv_cell($v) {
  $v = (string)$v;
  $v = str_replace('"', '""', $v);
  return '"' . $v . '"';
}

function parse_month_ym($ym) {
  // expects YYYY-MM
  if (!preg_match('/^\d{4}-\d{2}$/', (string)$ym)) return null;
  $dt = DateTime::createFromFormat('Y-m-d', $ym . '-01');
  if (!$dt) return null;
  if ($dt->format('Y-m') !== $ym) return null;
  return $dt;
}

// -----------------------------
// Fixed scheduling range (enabled days)
// -----------------------------
$range_start = new DateTime('2026-03-15');
$range_end   = new DateTime('2026-04-18');

// -----------------------------
// Allowed month pages (Jan → Apr 2026)
// -----------------------------
$min_month = new DateTime('2026-01-01');
$max_month = new DateTime('2026-04-01');

// -----------------------------
// Storage (JSON file)
// -----------------------------
$data_dir = __DIR__ . '/data';
ensure_dir($data_dir);
$store_path = $data_dir . '/schedules_2026-03-15_to_2026-04-18.json';

$schedules = read_json_file($store_path);
// $schedules['YYYY-MM-DD'] = [ ['id'=>..., 'title'=>..., 'start'=>..., 'end'=>..., 'notes'=>..., 'created_at'=>...], ... ];

// -----------------------------
// DOWNLOAD HANDLERS
// -----------------------------
$download = $_GET['download'] ?? '';
if ($download === 'json') {
  $filename = "schedules_2026-03-15_to_2026-04-18.json";
  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');
  echo json_encode($schedules, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

if ($download === 'csv') {
  $filename = "schedules_2026-03-15_to_2026-04-18.csv";
  header('Content-Type: text/csv; charset=utf-8');
  header('Content-Disposition: attachment; filename="'.$filename.'"');

  echo "Date,Start,End,Title,Notes,Created At\n";
  ksort($schedules);
  foreach ($schedules as $date => $items) {
    if (!is_array($items)) continue;
    foreach ($items as $it) {
      $cols = [
        $date,
        $it['start'] ?? '',
        $it['end'] ?? '',
        $it['title'] ?? '',
        $it['notes'] ?? '',
        $it['created_at'] ?? '',
      ];
      echo implode(',', array_map('csv_cell', $cols)) . "\n";
    }
  }
  exit;
}

// -----------------------------
// POST actions (Add/Delete) — only allowed within enabled range
// -----------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $date   = $_POST['date'] ?? '';

  $dt = DateTime::createFromFormat('Y-m-d', $date);
  $dt_ok = $dt && $dt->format('Y-m-d') === $date && in_range($dt, $range_start, $range_end);

  if ($dt_ok) {
    if ($action === 'add') {
      $title = clamp_str($_POST['title'] ?? '', 120);
      $start = trim((string)($_POST['start'] ?? ''));
      $end   = trim((string)($_POST['end'] ?? ''));
      $notes = clamp_str($_POST['notes'] ?? '', 500);

      if ($title !== '' && valid_time($start) && valid_time($end)) {
        $id = bin2hex(random_bytes(6));
        $entry = [
          'id' => $id,
          'title' => $title,
          'start' => $start,
          'end' => $end,
          'notes' => $notes,
          'created_at' => (new DateTime())->format('Y-m-d H:i:s'),
        ];
        if (!isset($schedules[$date]) || !is_array($schedules[$date])) $schedules[$date] = [];
        $schedules[$date][] = $entry;

        write_json_file_atomic($store_path, $schedules);
      }
    }

    if ($action === 'delete') {
      $entry_id = trim((string)($_POST['entry_id'] ?? ''));
      if ($entry_id !== '' && isset($schedules[$date]) && is_array($schedules[$date])) {
        $schedules[$date] = array_values(array_filter($schedules[$date], function($it) use ($entry_id) {
          return isset($it['id']) && $it['id'] !== $entry_id;
        }));
        write_json_file_atomic($store_path, $schedules);
      }
    }
  }

  // redirect back to the correct month page + open date if valid
  $redirect = basename(__FILE__);

  if ($dt_ok) {
    $month = $dt->format('Y-m');
    $redirect .= '?month=' . urlencode($month) . '&open=' . urlencode($date);
  }

  header("Location: $redirect");
  exit;
}

// -----------------------------
// Determine which month page to show
//   - if open=YYYY-MM-DD is valid and within Jan–Apr 2026, go to that month
//   - else use ?month=YYYY-MM
//   - else default to March 2026
// -----------------------------
$open_date = $_GET['open'] ?? '';
$open_dt = DateTime::createFromFormat('Y-m-d', $open_date);
$open_ok = $open_dt && $open_dt->format('Y-m-d') === $open_date;

// month derived from open (if within Jan–Apr)
$view_month_dt = null;
if ($open_ok) {
  $tmp = clone $open_dt;
  $tmp->modify('first day of this month');
  if ($tmp >= $min_month && $tmp <= $max_month) {
    $view_month_dt = $tmp;
  }
}

if (!$view_month_dt) {
  $month_q = $_GET['month'] ?? '2026-03';
  $m = parse_month_ym($month_q);
  if ($m) {
    $m->modify('first day of this month');
    if ($m < $min_month) $m = clone $min_month;
    if ($m > $max_month) $m = clone $max_month;
    $view_month_dt = $m;
  } else {
    $view_month_dt = new DateTime('2026-03-01');
  }
}

$view_ym = $view_month_dt->format('Y-m');

// Prev/Next month (bounded)
$prev_month_dt = (clone $view_month_dt);
$prev_month_dt->modify('-1 month');
$prev_allowed = $prev_month_dt >= $min_month;

$next_month_dt = (clone $view_month_dt);
$next_month_dt->modify('+1 month');
$next_allowed = $next_month_dt <= $max_month;

$prev_ym = $prev_month_dt->format('Y-m');
$next_ym = $next_month_dt->format('Y-m');

// -----------------------------
// Build month grid (Sun–Sat weeks)
// -----------------------------
$month_start = clone $view_month_dt; // first day
$month_end = clone $view_month_dt;
$month_end->modify('last day of this month');

$grid_start = clone $month_start;
$grid_start->modify('sunday this week');

$grid_end = clone $month_end;
$grid_end->modify('saturday this week');

// open is only “openable” if within enabled scheduling range
$open_in_enabled_range = false;
if ($open_ok) {
  $open_in_enabled_range = in_range($open_dt, $range_start, $range_end);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Schedule Calendar (Jan–Apr 2026)</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    :root { color-scheme: light; }
    body { font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif; }
    .bg-grid {
      background-image: radial-gradient(circle at 1px 1px, rgba(15, 23, 42, .06) 1px, transparent 0);
      background-size: 22px 22px;
    }
    .card {
      border: 1px solid rgba(15,23,42,.08);
      background: rgba(255,255,255,.92);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      box-shadow: 0 12px 30px rgba(2, 6, 23, 0.06);
    }
    .chip {
      border: 1px solid rgba(15,23,42,.10);
      background: rgba(255,255,255,.85);
    }
    .nice-scroll::-webkit-scrollbar { height: 10px; width: 10px; }
    .nice-scroll::-webkit-scrollbar-thumb { background: rgba(15,23,42,.15); border-radius: 999px; }
    .nice-scroll::-webkit-scrollbar-track { background: rgba(15,23,42,.05); }
  </style>
</head>

<body class="min-h-screen bg-slate-50 bg-grid">
  <div class="max-w-7xl mx-auto px-4 md:px-6 py-6 md:py-10">

    <!-- Header -->
    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between mb-6">
      <div>
        <div class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-700 chip rounded-full px-3 py-1">
          <i class="fa-solid fa-calendar-days text-blue-600"></i>
          Daily Schedule Calendar
        </div>
        <h1 class="mt-3 text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
          <?php echo e($view_month_dt->format('F Y')); ?>
        </h1>
        <div class="mt-1 text-sm font-semibold text-slate-600">
          Enabled scheduling: <span class="font-black text-slate-800">March 15, 2026 – April 18, 2026</span>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2">
        <!-- Month nav -->
        <a href="<?php echo e(basename(__FILE__) . '?month=' . ($prev_allowed ? $prev_ym : $view_ym)); ?>"
           class="<?php echo $prev_allowed ? 'px-4 py-2.5 rounded-xl border border-slate-200 bg-white font-extrabold text-slate-800 hover:bg-slate-50 transition' : 'px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-extrabold text-slate-400 cursor-not-allowed pointer-events-none'; ?>">
          <i class="fa-solid fa-chevron-left mr-2"></i> Prev
        </a>

        <a href="<?php echo e(basename(__FILE__) . '?month=' . ($next_allowed ? $next_ym : $view_ym)); ?>"
           class="<?php echo $next_allowed ? 'px-4 py-2.5 rounded-xl border border-slate-200 bg-white font-extrabold text-slate-800 hover:bg-slate-50 transition' : 'px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 font-extrabold text-slate-400 cursor-not-allowed pointer-events-none'; ?>">
          Next <i class="fa-solid fa-chevron-right ml-2"></i>
        </a>

        <!-- Downloads -->
        <a href="?download=json"
           class="px-4 py-2.5 rounded-xl bg-slate-900 text-white font-extrabold hover:bg-slate-800 transition">
          <i class="fa-solid fa-download mr-2"></i> JSON
        </a>
        <a href="?download=csv"
           class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-extrabold hover:bg-emerald-700 transition">
          <i class="fa-solid fa-file-csv mr-2"></i> CSV
        </a>
      </div>
    </div>

    <!-- Calendar -->
    <div class="card rounded-2xl p-4 md:p-6">
      <div class="flex items-center justify-between mb-4">
        <div class="text-slate-800 font-black flex items-center gap-2">
          <i class="fa-solid fa-table-cells-large text-emerald-600"></i>
          Month View
        </div>
        <div class="text-xs font-extrabold text-slate-500">
          Sun–Sat • Only Mar 15–Apr 18 enabled
        </div>
      </div>

      <div class="grid grid-cols-7 gap-2 text-xs font-extrabold text-slate-600 mb-2">
        <div class="px-2 py-1">Sun</div><div class="px-2 py-1">Mon</div><div class="px-2 py-1">Tue</div>
        <div class="px-2 py-1">Wed</div><div class="px-2 py-1">Thu</div><div class="px-2 py-1">Fri</div><div class="px-2 py-1">Sat</div>
      </div>

      <div class="grid grid-cols-7 gap-2">
        <?php
          $d = clone $grid_start;
          while ($d <= $grid_end):
            $key = date_key($d);

            $in_this_month = ($d >= $month_start && $d <= $month_end);
            $enabled = in_range($d, $range_start, $range_end);

            $entries = ($enabled && isset($schedules[$key]) && is_array($schedules[$key])) ? $schedules[$key] : [];
            $count = count($entries);

            $is_today = ($key === (new DateTime())->format('Y-m-d'));
            $is_open = $open_ok && $open_in_enabled_range && $key === $open_date;
        ?>
          <button
            type="button"
            class="<?php
              if (!$in_this_month) {
                echo 'rounded-2xl border border-slate-100 bg-slate-50/60 p-3 text-left min-h-[120px] opacity-40 cursor-default';
              } else if ($enabled) {
                echo 'group rounded-2xl border border-slate-200 bg-white hover:bg-slate-50/70 transition p-3 text-left min-h-[120px] relative';
              } else {
                echo 'rounded-2xl border border-slate-100 bg-slate-50/80 p-3 text-left min-h-[120px] opacity-60 cursor-not-allowed';
              }
            ?>"
            <?php echo ($in_this_month && $enabled) ? 'onclick="openDay(\''.e($key).'\')"' : 'disabled'; ?>
            aria-label="Open day <?php echo e($key); ?>"
          >
            <div class="flex items-start justify-between gap-2">
              <div class="font-black <?php echo $in_this_month ? 'text-slate-900' : 'text-slate-400'; ?> flex items-center gap-2">
                <?php echo (int)$d->format('j'); ?>

                <?php if ($enabled): ?>
                  <span class="text-[10px] font-black px-2 py-0.5 rounded-full border bg-emerald-50 text-emerald-700 border-emerald-200">
                    Enabled
                  </span>
                <?php endif; ?>
              </div>

              <div class="flex items-center gap-1">
                <?php if ($is_today): ?>
                  <span class="text-[10px] font-black px-2 py-0.5 rounded-full border bg-amber-50 text-amber-700 border-amber-200">Today</span>
                <?php endif; ?>
              </div>
            </div>

            <div class="mt-2 space-y-1">
              <?php
                $preview = array_slice($entries, 0, 2);
                foreach ($preview as $it):
                  $time = '';
                  if (($it['start'] ?? '') !== '' || ($it['end'] ?? '') !== '') {
                    $time = trim(($it['start'] ?? '') . '–' . ($it['end'] ?? ''));
                  }
              ?>
                <div class="text-xs font-bold text-slate-700 truncate">
                  <?php if ($time !== ''): ?>
                    <span class="text-slate-500"><?php echo e($time); ?></span>
                    <span class="mx-1 text-slate-300">•</span>
                  <?php endif; ?>
                  <?php echo e($it['title'] ?? ''); ?>
                </div>
              <?php endforeach; ?>

              <?php if ($count > 2): ?>
                <div class="text-[11px] font-extrabold text-slate-400">+<?php echo $count - 2; ?> more</div>
              <?php endif; ?>

              <?php if ($in_this_month && !$enabled): ?>
                <div class="text-[11px] font-extrabold text-slate-400">
                  Disabled
                </div>
              <?php endif; ?>
            </div>

            <?php if ($is_open): ?>
              <div class="absolute inset-0 rounded-2xl ring-2 ring-blue-200 pointer-events-none"></div>
            <?php endif; ?>
          </button>
        <?php
            $d->modify('+1 day');
          endwhile;
        ?>
      </div>
    </div>
  </div>

  <!-- Modal -->
  <div id="modalWrap" class="fixed inset-0 hidden items-center justify-center p-4 z-[9999]">
    <div class="absolute inset-0 bg-slate-900/45 backdrop-blur-sm" onclick="closeModal()"></div>

    <div class="relative w-full max-w-2xl card rounded-2xl p-5 md:p-6">
      <div class="flex items-start justify-between gap-3">
        <div>
          <div class="text-xs font-extrabold text-slate-500">Manage schedules for</div>
          <div class="text-xl font-black text-slate-900" id="modalTitle">Date</div>
        </div>
        <button type="button"
                class="h-10 w-10 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 transition"
                onclick="closeModal()" aria-label="Close">
          <i class="fa-solid fa-xmark text-slate-700"></i>
        </button>
      </div>

      <!-- Existing entries -->
      <div class="mt-4">
        <div class="flex items-center justify-between">
          <div class="font-black text-slate-800">Schedules</div>
          <div class="text-xs font-extrabold text-slate-500" id="entryCount">0</div>
        </div>

        <div id="entryList" class="mt-3 space-y-2 max-h-[220px] overflow-auto nice-scroll pr-1"></div>
      </div>

      <hr class="my-5 border-slate-200"/>

      <!-- Add form -->
      <div>
        <div class="font-black text-slate-800 mb-3">Add Schedule</div>

        <form method="POST" class="grid grid-cols-1 md:grid-cols-12 gap-3">
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="date" id="formDate" value="">

          <div class="md:col-span-6">
            <label class="text-xs font-extrabold text-slate-600">Title</label>
            <input name="title" required maxlength="120"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-200"
              placeholder="e.g., Site inspection, Meeting, Delivery..."
            />
          </div>

          <div class="md:col-span-3">
            <label class="text-xs font-extrabold text-slate-600">Start</label>
            <input name="start" type="time"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-200"
            />
          </div>

          <div class="md:col-span-3">
            <label class="text-xs font-extrabold text-slate-600">End</label>
            <input name="end" type="time"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-emerald-200"
            />
          </div>

          <div class="md:col-span-12">
            <label class="text-xs font-extrabold text-slate-600">Notes (optional)</label>
            <textarea name="notes" rows="3" maxlength="500"
              class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-200"
              placeholder="Add details (people, location, reminders)..."></textarea>
          </div>

          <div class="md:col-span-12 flex items-center justify-end gap-2">
            <button type="button"
              class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white font-extrabold text-slate-800 hover:bg-slate-50 transition"
              onclick="closeModal()"
            >
              Cancel
            </button>
            <button type="submit"
              class="px-4 py-2.5 rounded-xl bg-blue-600 text-white font-extrabold hover:bg-blue-700 transition"
            >
              <i class="fa-solid fa-plus mr-2"></i> Save
            </button>
          </div>
        </form>

        <div class="mt-3 text-xs font-bold text-slate-500">
          Note: You can only add schedules on enabled dates (Mar 15–Apr 18, 2026).
        </div>
      </div>
    </div>
  </div>

<script>
  const SCHEDULES = <?php echo json_encode($schedules, JSON_UNESCAPED_UNICODE); ?>;

  const modalWrap  = document.getElementById('modalWrap');
  const modalTitle = document.getElementById('modalTitle');
  const entryList  = document.getElementById('entryList');
  const entryCount = document.getElementById('entryCount');
  const formDate   = document.getElementById('formDate');

  function formatPrettyDate(yyyyMMdd) {
    const [y,m,d] = yyyyMMdd.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    return dt.toLocaleDateString(undefined, { weekday:'long', year:'numeric', month:'long', day:'numeric' });
  }

  function openDay(dateKey) {
    const entries = Array.isArray(SCHEDULES[dateKey]) ? SCHEDULES[dateKey] : [];
    modalTitle.textContent = formatPrettyDate(dateKey);
    formDate.value = dateKey;

    entryList.innerHTML = '';
    entryCount.textContent = entries.length + ' item' + (entries.length !== 1 ? 's' : '');

    if (entries.length === 0) {
      entryList.innerHTML = `
        <div class="rounded-2xl border border-slate-200 bg-white p-4 text-slate-600 font-semibold">
          No schedules yet. Add one below.
        </div>
      `;
    } else {
      for (const it of entries) {
        const time = (it.start || it.end) ? `${it.start || ''}${(it.start || it.end) ? '–' : ''}${it.end || ''}` : '';
        const notes = (it.notes || '').trim();

        const node = document.createElement('div');
        node.className = "rounded-2xl border border-slate-200 bg-white p-4";

        node.innerHTML = `
          <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
              <div class="font-black text-slate-900 truncate">
                ${time ? `<span class="text-slate-500">${escapeHtml(time)}</span> <span class="mx-1 text-slate-300">•</span>` : ``}
                ${escapeHtml(it.title || '')}
              </div>
              ${notes ? `<div class="mt-1 text-sm font-semibold text-slate-600 whitespace-pre-wrap">${escapeHtml(notes)}</div>` : ``}
            </div>

            <form method="POST" onsubmit="return confirm('Delete this schedule?')" class="shrink-0">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="date" value="${escapeAttr(dateKey)}">
              <input type="hidden" name="entry_id" value="${escapeAttr(it.id || '')}">
              <button type="submit"
                class="h-10 w-10 rounded-xl border border-rose-200 bg-rose-50 text-rose-700 hover:bg-rose-100 transition"
                title="Delete" aria-label="Delete"
              >
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </div>
        `;
        entryList.appendChild(node);
      }
    }

    modalWrap.classList.remove('hidden');
    modalWrap.classList.add('flex');
    document.documentElement.style.overflow = 'hidden';
  }

  function closeModal() {
    modalWrap.classList.add('hidden');
    modalWrap.classList.remove('flex');
    document.documentElement.style.overflow = '';
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }
  function escapeAttr(str) { return escapeHtml(str); }

  (function () {
    const open = <?php echo ($open_ok && $open_in_enabled_range) ? json_encode($open_date) : 'null'; ?>;
    if (open) openDay(open);
  })();
</script>

</body>
</html>
