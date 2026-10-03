<?php 
require_once 'config/check-session.php';
require_once 'config/conn.php';

$username = $_SESSION['username'];

// Handle edit order
if (isset($_POST['edit_order'])) {
    $order_id = $_POST['order_id'];
    $order_date = $_POST['order_date'];
    $quantity = floatval($_POST['quantity']);
    $amount = floatval($_POST['amount']);

    $stmt = $conn->prepare("UPDATE orders SET order_date = ?, quantity = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("sddi", $order_date, $quantity, $amount, $order_id);
    $stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['month']) ? "?month=" . $_GET['month'] : ""));
    exit;
}

// Handle edit delivery
if (isset($_POST['edit_delivery'])) {
    $delivery_id = $_POST['delivery_id'];
    $delivery_date = $_POST['delivery_date'];
    $supplier = $_POST['supplier'];
    $amount = floatval($_POST['amount']);
    $old_date = $_POST['old_date'];

    $stmt = $conn->prepare("UPDATE deliveries SET delivery_date = ?, supplier = ?, amount = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $delivery_date, $supplier, $amount, $delivery_id);
    $stmt->execute();

    recalculateAllMonthsFrom($conn, date('Y-m-01', strtotime($old_date)));
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['month']) ? "?month=" . $_GET['month'] : ""));
    exit;
}

// Handle update consumption
if (isset($_POST['update_consumption'])) {
    $old_date = $_POST['old_date'];
    $consumption_date = $_POST['consumption_date'];

    $stmt = $conn->prepare("DELETE FROM consumptions WHERE consumption_date = ?");
    $stmt->bind_param("s", $old_date);
    $stmt->execute();

    foreach ($_POST['liters'] as $eq_id => $liters) {
        $liters = floatval($liters);
        if ($liters > 0) {
            $eq_query = $conn->query("SELECT driver_id FROM equipment WHERE id = $eq_id");
            $eq_row = $eq_query->fetch_assoc();
            $driver_id = $eq_row['driver_id'];
            
            $stmt = $conn->prepare("INSERT INTO consumptions (consumption_date, driver_id, equipment_id, liters) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siid", $consumption_date, $driver_id, $eq_id, $liters);
            $stmt->execute();
        }
    }

    recalculateAllMonthsFrom($conn, date('Y-m-01', strtotime($old_date)));
    header("Location: " . $_SERVER['PHP_SELF'] . (isset($_GET['month']) ? "?month=" . $_GET['month'] : ""));
    exit;
}

function recalculateAllMonthsFrom($conn, $start_date) {
    $year = date('Y', strtotime($start_date));
    
    $years_query = $conn->query("SELECT DISTINCT YEAR(delivery_date) as y FROM deliveries UNION SELECT DISTINCT YEAR(consumption_date) as y FROM consumptions ORDER BY y");
    $years = [];
    while($r = $years_query->fetch_assoc()) {
        if($r['y'] >= $year) $years[] = $r['y'];
    }
    
    foreach($years as $y) {
        $deliveries_query = "SELECT delivery_date, amount FROM deliveries WHERE YEAR(delivery_date) = $y ORDER BY delivery_date";
        $consumptions_query = "SELECT consumption_date, SUM(liters) as daily_total FROM consumptions WHERE YEAR(consumption_date) = $y GROUP BY consumption_date ORDER BY consumption_date";
        
        $del_result = $conn->query($deliveries_query);
        $cons_result = $conn->query($consumptions_query);
        
        $deliveries = [];
        while ($row = $del_result->fetch_assoc()) {
            $deliveries[$row['delivery_date']] = $row['amount'];
        }
        
        $consumptions = [];
        while ($row = $cons_result->fetch_assoc()) {
            $consumptions[$row['consumption_date']] = $row['daily_total'];
        }
        
        $all_dates = array_unique(array_merge(array_keys($deliveries), array_keys($consumptions)));
        sort($all_dates);
        
        $prev_year = $y - 1;
        $prev_bal_query = $conn->query("SELECT running_balance FROM monthly_summaries WHERE year = $prev_year ORDER BY month_int DESC LIMIT 1");
        $prev_bal = 0;
        if($prev_bal_query && $prev_row = $prev_bal_query->fetch_assoc()) {
            $prev_bal = $prev_row['running_balance'];
        }
        
        $balance = $prev_bal;
        $monthly_data = [];
        
        foreach ($all_dates as $date) {
            $current_month = date('m', strtotime($date));
            $month_key = "$y-$current_month";
            
            if (!isset($monthly_data[$month_key])) {
                $monthly_data[$month_key] = [
                    'year' => $y,
                    'month' => intval($current_month),
                    'consumption' => 0,
                    'ending_balance' => $balance
                ];
            }
            
            if (isset($deliveries[$date])) {
                $balance += $deliveries[$date];
            }
            
            if (isset($consumptions[$date])) {
                $daily_cons = $consumptions[$date];
                $balance -= $daily_cons;
                $monthly_data[$month_key]['consumption'] += $daily_cons;
            }
            
            $monthly_data[$month_key]['ending_balance'] = $balance;
        }
        
        foreach ($monthly_data as $data) {
            $stmt = $conn->prepare("REPLACE INTO monthly_summaries (month_int, year, total_consumption, running_balance) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iidd", $data['month'], $data['year'], $data['consumption'], $data['ending_balance']);
            $stmt->execute();
        }
    }
}

$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m'));
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
$month_str = str_pad($month, 2, '0', STR_PAD_LEFT);
$month_name = date('F', mktime(0, 0, 0, $month, 1));

$summary_query = $conn->query("SELECT total_consumption, running_balance FROM monthly_summaries WHERE month_int = $month AND year = $year");
$summary = $summary_query->fetch_assoc();
$total_consumption = $summary ? $summary['total_consumption'] : 0;
$running_balance = $summary ? $summary['running_balance'] : 0;

$cost_query = $conn->query("SELECT SUM(amount) as total_cost FROM orders WHERE YEAR(order_date) = $year");
$total_cost_row = $cost_query->fetch_assoc();
$total_cost = $total_cost_row['total_cost'] ?? 0;

$equipment = [];
$eq_result = $conn->query("SELECT e.id, e.name, d.name as driver FROM equipment e LEFT JOIN drivers d ON e.driver_id = d.id ORDER BY e.id");
while ($row = $eq_result->fetch_assoc()) {
    $equipment[$row['id']] = [
        'name' => $row['name'],
        'driver' => $row['driver'] ?: 'N/A'
    ];
}

$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}
$prev_summary_query = $conn->query("SELECT running_balance FROM monthly_summaries WHERE month_int = $prev_month AND year = $prev_year");
$prev_summary = $prev_summary_query->fetch_assoc();
$initial_balance = $prev_summary ? $prev_summary['running_balance'] : 0;

$graph_days = [];
$graph_balances = [];
$max_per_eq = array_fill_keys(array_keys($equipment), ['max' => 0, 'date' => '']);
$total_per_eq = array_fill_keys(array_keys($equipment), 0);

$del_result = $conn->query("SELECT id, delivery_date, supplier, amount FROM deliveries WHERE MONTH(delivery_date) = $month AND YEAR(delivery_date) = $year ORDER BY delivery_date");
$deliveries = [];
while ($row = $del_result->fetch_assoc()) {
    $deliveries[$row['delivery_date']] = ['id' => $row['id'], 'supplier' => $row['supplier'], 'amount' => $row['amount']];
}

$cons_result = $conn->query("SELECT consumption_date, equipment_id, liters FROM consumptions WHERE MONTH(consumption_date) = $month AND YEAR(consumption_date) = $year ORDER BY consumption_date");
$consumptions = [];
while ($row = $cons_result->fetch_assoc()) {
    $consumptions[$row['consumption_date']][$row['equipment_id']] = $row['liters'];
}

$all_dates = array_unique(array_merge(array_keys($deliveries), array_keys($consumptions)));
sort($all_dates);

$balance = $initial_balance;
$overall_max = 0;
$overall_eq = '';
$overall_date = '';

foreach ($all_dates as $date_str) {
    $date_obj = new DateTime($date_str);
    $formatted_date = $date_obj->format('M-d');
    $has_delivery = isset($deliveries[$date_str]);
    $has_cons = isset($consumptions[$date_str]);

    if ($has_delivery) {
        $amount = $deliveries[$date_str]['amount'];
        $balance += $amount;
    }

    if ($has_cons) {
        $day_cons = $consumptions[$date_str];
        $day_total = 0;
        foreach ($equipment as $eq_id => $eq_data) {
            $lit = isset($day_cons[$eq_id]) ? $day_cons[$eq_id] : 0;
            $day_total += $lit;
            $total_per_eq[$eq_id] += $lit;
            if ($lit > $max_per_eq[$eq_id]['max']) {
                $max_per_eq[$eq_id]['max'] = $lit;
                $max_per_eq[$eq_id]['date'] = $date_str;
            }
            if ($lit > $overall_max) {
                $overall_max = $lit;
                $overall_eq = $eq_data['name'];
                $overall_date = $formatted_date;
            }
        }
        $balance -= $day_total;
    }

    if ($has_delivery || $has_cons) {
        $graph_days[] = $formatted_date;
        $graph_balances[] = round($balance, 2);
    }
}

$month_names = [];
$monthly_cons = array_fill(1, 12, 0);
for ($m = 1; $m <= 12; $m++) {
    $month_names[] = date('F', mktime(0, 0, 0, $m, 1));
}
$monthly_result = $conn->query("SELECT month_int, total_consumption FROM monthly_summaries WHERE year = $year ORDER BY month_int");
while ($row = $monthly_result->fetch_assoc()) {
    $monthly_cons[$row['month_int']] = $row['total_consumption'];
}
 include '../bar/navbar.php'; 
 
?>

<title>Analytics - Fuel Management System</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    .dashboard-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .equipment-card {
        transition: all 0.3s ease;
    }
    .equipment-card:hover {
        background-color: #f3f4f6;
    }
    
    .equipment-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 12px;
    }
</style>

<div class="p-6">

  <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-sm p-6 mb-8 border border-gray-100">
    <!-- Back Button (Top of the Title) -->
    <div class="mb-4">
      <button 
        onclick="window.location.href='dashboard.php'" 
        class="flex items-center space-x-2 bg-[#7DF9FF] text-gray-800 font-semibold text-sm px-4 py-2 rounded-full shadow-md hover:bg-[#5ee6f2] active:scale-95 transition duration-200"
      >
        <i class="fas fa-arrow-left text-sm"></i>
        <span>Back to Dashboard</span>
      </button>
    </div>

    <!-- Title, Description and Filters -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <!-- Left Side (Title and Description) -->
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-1">
          <span class="text-[#7DF9FF]">Analytics</span> Dashboard
        </h1>
        <p class="text-gray-500 text-sm">
          Fuel consumption analytics and insights for 
          <span class="font-medium text-gray-700"><?php echo $month_name; ?></span>
          <?php echo $year; ?>
        </p>
      </div>

      <!-- Right Side (Filters) -->
      <div class="flex items-center space-x-3">
        <select 
          id="month-select" 
          class="rounded-xl border border-gray-300 bg-white text-gray-700 text-sm px-4 py-2 shadow-sm hover:border-[#7DF9FF] focus:ring-2 focus:ring-[#7DF9FF] focus:border-[#7DF9FF] transition-all duration-200"
        >
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>>
              <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
            </option>
          <?php endfor; ?>
        </select>

        <select 
          id="year-select" 
          class="rounded-xl border border-gray-300 bg-white text-gray-700 text-sm px-4 py-2 shadow-sm hover:border-[#7DF9FF] focus:ring-2 focus:ring-[#7DF9FF] focus:border-[#7DF9FF] transition-all duration-200"
        >
          <?php for ($y = 2024; $y <= 2026; $y++): ?>
            <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>>
              <?php echo $y; ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Rest of your content goes here -->

</div>



    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow p-6 dashboard-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Consumption</p>
                    <h3 class="text-2xl font-bold text-gray-900"><?php echo number_format($total_consumption, 2); ?> L</h3>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $month_name; ?> <?php echo $year; ?></p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 dashboard-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Running Balance</p>
                    <h3 class="text-2xl font-bold <?php echo $running_balance < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                        <?php echo number_format($running_balance, 2); ?> L
                    </h3>
                    <?php if ($running_balance < 0): ?>
                        <p class="text-xs text-red-600 mt-1"><i class="fas fa-exclamation-triangle"></i> Negative balance</p>
                    <?php endif; ?>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-gas-pump text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6 dashboard-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 mb-1">Total Cost</p>
                    <h3 class="text-2xl font-bold text-gray-900">₱<?php echo number_format($total_cost, 2); ?></h3>
                    <p class="text-xs text-gray-500 mt-1">Year <?php echo $year; ?></p>
                </div>
                <div class="bg-yellow-100 p-3 rounded-full">
                    <i class="fas fa-dollar-sign text-yellow-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Consumption Graphs</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div>
                <h4 class="font-medium mb-2">Daily Running Balance - <?php echo $month_name; ?></h4>
                <canvas id="balanceLineChart" height="200"></canvas>
            </div>
            <div>
                <h4 class="font-medium mb-2">Monthly Consumption <?php echo $year; ?></h4>
                <canvas id="monthlyBarChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Equipment Consumption Statistics - <?php echo $month_name; ?> <?php echo $year; ?></h3>
        <div class="equipment-grid">
            <?php foreach ($equipment as $eq_id => $eq_data): ?>
                <div class="bg-gray-50 rounded-lg p-4 equipment-card border">
                    <h4 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($eq_data['name']); ?></h4>
                    <div class="space-y-1">
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold">Total:</span> 
                            <span class="text-blue-600"><?php echo number_format($total_per_eq[$eq_id], 2); ?> L</span>
                        </p>
                        <p class="text-sm text-gray-600">
                            <span class="font-semibold">Max:</span> 
                            <span class="text-green-600"><?php echo number_format($max_per_eq[$eq_id]['max'], 2); ?> L</span>
                        </p>
                        <?php if ($max_per_eq[$eq_id]['date']): ?>
                            <p class="text-xs text-gray-500">on <?php echo date('M d, Y', strtotime($max_per_eq[$eq_id]['date'])); ?></p>
                        <?php else: ?>
                            <p class="text-xs text-gray-400">No consumption this month</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Equipment Consumption Comparison - <?php echo $month_name; ?> <?php echo $year; ?></h3>
        <canvas id="equipmentComparisonChart" height="80"></canvas>
    </div>

</div>

<div id="editDeliveryModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modal-title">Edit Delivery</h3>
                    <input type="hidden" name="delivery_id" id="edit_delivery_id">
                    <input type="hidden" name="old_date" id="edit_delivery_old_date">
                    <div class="space-y-4">
                        <div>
                            <label for="edit_delivery_date" class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" name="delivery_date" id="edit_delivery_date" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="edit_supplier" class="block text-sm font-medium text-gray-700">Supplier</label>
                            <input type="text" name="supplier" id="edit_supplier" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label for="edit_delivery_amount" class="block text-sm font-medium text-gray-700">Amount (L)</label>
                            <input type="number" name="amount" id="edit_delivery_amount" step="0.01" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="edit_delivery" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('editDeliveryModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="editConsModal" class="hidden fixed z-50 inset-0 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
            <form method="POST">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 mb-4" id="modal-title">Edit Daily Consumption</h3>
                    <input type="hidden" name="old_date" id="edit_cons_old_date">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Consumption Date</label>
                        <input type="date" name="consumption_date" id="edit_cons_date" required class="mt-1 block w-full md:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <?php foreach ($equipment as $id => $eq_data): ?>
                            <div>
                                <label class="block text-sm font-medium text-gray-700"><?php echo $eq_data['name']; ?></label>
                                <input type="number" name="liters[<?php echo $id; ?>]" step="0.01" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" name="update_consumption" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">Save</button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeModal('editConsModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const consumptionsData = <?php echo json_encode($consumptions); ?>;

    function closeModal(modalId) {
        document.getElementById(modalId).classList.add('hidden');
    }

    document.getElementById('month-select').addEventListener('change', function() {
        const year = document.getElementById('year-select').value;
        window.location.href = '?month=' + this.value + '&year=' + year;
    });
    
    document.getElementById('year-select').addEventListener('change', function() {
        const month = document.getElementById('month-select').value;
        window.location.href = '?month=' + month + '&year=' + this.value;
    });

    const graphDays = <?php echo json_encode($graph_days); ?>;
    const graphBalances = <?php echo json_encode($graph_balances); ?>;
    const monthNames = <?php echo json_encode($month_names); ?>;
    const monthlyCons = <?php echo json_encode(array_values($monthly_cons)); ?>;
    const equipmentNames = <?php echo json_encode(array_values(array_map(function($e) { return $e['name']; }, $equipment))); ?>;
    const equipmentTotals = <?php echo json_encode(array_values($total_per_eq)); ?>;

    var balanceCtx = document.getElementById('balanceLineChart').getContext('2d');
    new Chart(balanceCtx, {
        type: 'line',
        data: {
            labels: graphDays,
            datasets: [{
                label: 'Running Balance (L)',
                data: graphBalances,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.1,
                fill: true
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: true,
            scales: { 
                y: { 
                    beginAtZero: false,
                    ticks: {
                        callback: function(value) {
                            return value + ' L';
                        }
                    }
                } 
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Balance: ' + context.parsed.y.toFixed(2) + ' L';
                        }
                    }
                }
            }
        }
    });

    var monthlyCtx = document.getElementById('monthlyBarChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: monthNames,
            datasets: [{
                label: 'Consumption (L)',
                data: monthlyCons,
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: { 
            responsive: true,
            maintainAspectRatio: true,
            scales: { 
                y: { 
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' L';
                        }
                    }
                } 
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Consumption: ' + context.parsed.y.toFixed(2) + ' L';
                        }
                    }
                }
            }
        }
    });

    var equipmentCtx = document.getElementById('equipmentComparisonChart').getContext('2d');
    new Chart(equipmentCtx, {
        type: 'bar',
        data: {
            labels: equipmentNames,
            datasets: [{
                label: 'Consumption (L)',
                data: equipmentTotals,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.5)',
                    'rgba(54, 162, 235, 0.5)',
                    'rgba(255, 206, 86, 0.5)',
                    'rgba(75, 192, 192, 0.5)',
                    'rgba(153, 102, 255, 0.5)',
                    'rgba(255, 159, 64, 0.5)',
                    'rgba(199, 199, 199, 0.5)',
                    'rgba(83, 102, 255, 0.5)',
                    'rgba(255, 99, 255, 0.5)',
                    'rgba(99, 255, 132, 0.5)',
                    'rgba(255, 192, 203, 0.5)',
                    'rgba(176, 224, 230, 0.5)',
                    'rgba(244, 164, 96, 0.5)',
                    'rgba(147, 112, 219, 0.5)',
                    'rgba(255, 215, 0, 0.5)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)',
                    'rgba(255, 159, 64, 1)',
                    'rgba(199, 199, 199, 1)',
                    'rgba(83, 102, 255, 1)',
                    'rgba(255, 99, 255, 1)',
                    'rgba(99, 255, 132, 1)',
                    'rgba(255, 192, 203, 1)',
                    'rgba(176, 224, 230, 1)',
                    'rgba(244, 164, 96, 1)',
                    'rgba(147, 112, 219, 1)',
                    'rgba(255, 215, 0, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value + ' L';
                        }
                    }
                }
            },
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': ' + context.parsed.x.toFixed(2) + ' L';
                        }
                    }
                }
            }
        }
    });

    document.querySelectorAll('.edit-delivery').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_delivery_id').value = this.dataset.id;
            document.getElementById('edit_delivery_old_date').value = this.dataset.date;
            document.getElementById('edit_delivery_date').value = this.dataset.date;
            document.getElementById('edit_supplier').value = this.dataset.supplier;
            document.getElementById('edit_delivery_amount').value = this.dataset.amount;
            document.getElementById('editDeliveryModal').classList.remove('hidden');
        });
    });

    document.querySelectorAll('.edit-cons').forEach(button => {
        button.addEventListener('click', function() {
            const date = this.dataset.date;
            document.getElementById('edit_cons_old_date').value = date;
            document.getElementById('edit_cons_date').value = date;
            const dayData = consumptionsData[date] || {};
            const modal = document.getElementById('editConsModal');
            
            modal.querySelectorAll('input[type="number"]').forEach(input => {
                input.value = '';
            });
            
            for (let eq in dayData) {
                const input = modal.querySelector(`input[name="liters[${eq}]"]`);
                if (input) input.value = dayData[eq];
            }
            
            modal.classList.remove('hidden');
        });
    });
</script>
 