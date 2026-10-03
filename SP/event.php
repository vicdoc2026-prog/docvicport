<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Function to fetch all regular events
function fetchAllRegularEvents($conn) {
    $sql = "SELECT * FROM regular_events ORDER BY month, day";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $events = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $events[] = $row;
        }
        return $events;
    }
    
    return [];
}

// Function to count events
function countRegularEvents($conn) {
    $sql = "SELECT COUNT(*) as total FROM regular_events";
    $result = mysqli_query($conn, $sql);
    
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    
    return 0;
}

// Function to count events by type
function countEventsByType($conn, $type) {
    $sql = "SELECT COUNT(*) as total FROM regular_events WHERE event_type = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $type);
    mysqli_stmt_execute($stmt);
    
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    mysqli_stmt_close($stmt);
    
    return $row['total'];
}

// Get event counts
$totalEvents = countRegularEvents($conn);
$festivalCount = countEventsByType($conn, 'Festival');
$holidayCount = countEventsByType($conn, 'National Holiday');
$religiousCount = countEventsByType($conn, 'Religious Event');

// Pagination settings
$records_per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Get all events with optional filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['filter_type']) ? trim($_GET['filter_type']) : '';
$filter_month = isset($_GET['filter_month']) ? trim($_GET['filter_month']) : '';

// Count total records for pagination
$count_query = "SELECT COUNT(*) as total FROM regular_events WHERE 1=1";
$count_params = [];
$count_types = "";

if (!empty($search)) {
    $count_query .= " AND (event_name LIKE ? OR location LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_params[] = $search_param;
    $count_types .= "sss";
}

if (!empty($filter_type)) {
    $count_query .= " AND event_type = ?";
    $count_params[] = $filter_type;
    $count_types .= "s";
}

if (!empty($filter_month)) {
    $count_query .= " AND month = ?";
    $count_params[] = $filter_month;
    $count_types .= "i";
}

$count_stmt = $conn->prepare($count_query);
if (!empty($count_params)) {
    $count_stmt->bind_param($count_types, ...$count_params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get paginated events
$query = "SELECT * FROM regular_events WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $query .= " AND (event_name LIKE ? OR location LIKE ? OR description LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if (!empty($filter_type)) {
    $query .= " AND event_type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($filter_month)) {
    $query .= " AND month = ?";
    $params[] = $filter_month;
    $types .= "i";
}

$query .= " ORDER BY month, day LIMIT ? OFFSET ?";
$params[] = $records_per_page;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events_result = $stmt->get_result();

// Helper function to build pagination URL
function buildPaginationUrl($page, $search, $filter_type, $filter_month, $per_page) {
    $params = ['page' => $page];
    if (!empty($search)) $params['search'] = $search;
    if (!empty($filter_type)) $params['filter_type'] = $filter_type;
    if (!empty($filter_month)) $params['filter_month'] = $filter_month;
    if (!empty($per_page) && $per_page != 10) $params['per_page'] = $per_page;
    return 'event.php?' . http_build_query($params);
}

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Regular Events - Sangguniang Panlalawigan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--dark));
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
        }

        .event-card {
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .event-card:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(26, 58, 108, 0.15);
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .type-festival {
            background-color: #fef3c7;
            color: #92400e;
        }

        .type-holiday {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .type-religious {
            background-color: #e0e7ff;
            color: #4338ca;
        }

        .type-cultural {
            background-color: #fce7f3;
            color: #9f1239;
        }

        .type-celebration {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Pagination Styles */
        .pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            text-decoration: none;
            color: #374151;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .pagination a:hover {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
            font-weight: 600;
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
    <?php include 'bar/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'bar/header.php'; ?>
        
        <!-- Events Content -->
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <!-- Page Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Regular Events</h1>
                    <p class="text-gray-600 text-sm mt-1">Philippine calendar events throughout the year</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                <i class="fas fa-calendar-alt text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Total Events</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $totalEvents; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                <i class="fas fa-flag text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Festivals</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $festivalCount; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                <i class="fas fa-star text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Holidays</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $holidayCount; ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-church text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Religious</h3>
                                <p class="text-2xl font-bold mt-1"><?php echo $religiousCount; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events List -->
            <div class="card bg-white">
                <div class="card-header p-4 flex justify-between items-center">
                    <h2 class="text-lg font-semibold text-gray-800">All Events</h2>
                    <form method="GET" class="flex items-center space-x-4">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search events..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:border-blue-500">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                        <select name="filter_month" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">All Months</option>
                            <?php foreach ($months as $num => $name): ?>
                                <option value="<?php echo $num; ?>" <?php echo $filter_month == $num ? 'selected' : ''; ?>>
                                    <?php echo $name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="filter_type" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:outline-none focus:border-blue-500">
                            <option value="">All Types</option>
                            <option value="Festival" <?php echo $filter_type === 'Festival' ? 'selected' : ''; ?>>Festival</option>
                            <option value="National Holiday" <?php echo $filter_type === 'National Holiday' ? 'selected' : ''; ?>>National Holiday</option>
                            <option value="Religious Event" <?php echo $filter_type === 'Religious Event' ? 'selected' : ''; ?>>Religious Event</option>
                            <option value="Cultural Event" <?php echo $filter_type === 'Cultural Event' ? 'selected' : ''; ?>>Cultural Event</option>
                            <option value="Celebration" <?php echo $filter_type === 'Celebration' ? 'selected' : ''; ?>>Celebration</option>
                        </select>
                        <button type="submit" class="btn-primary text-white px-4 py-2 rounded-lg text-sm">
                            <i class="fas fa-filter"></i>
                        </button>
                    </form>
                </div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Name</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Month</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="py-3 px-4 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php if ($events_result->num_rows > 0): ?>
                                    <?php while ($event = $events_result->fetch_assoc()): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 font-semibold text-gray-900"><?php echo htmlspecialchars($event['event_name']); ?></td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo $months[$event['month']]; ?></td>
                                            <td class="py-3 px-4 text-gray-700">
                                                <?php 
                                                    echo $event['day'] ? $event['day'] : htmlspecialchars($event['day_description']); 
                                                ?>
                                            </td>
                                            <td class="py-3 px-4 text-gray-700"><?php echo htmlspecialchars($event['location']); ?></td>
                                            <td class="py-3 px-4">
                                                <?php
                                                    $type_class = 'type-';
                                                    $type_icon = '';
                                                    
                                                    switch($event['event_type']) {
                                                        case 'Festival':
                                                            $type_class .= 'festival';
                                                            $type_icon = 'fa-flag';
                                                            break;
                                                        case 'National Holiday':
                                                            $type_class .= 'holiday';
                                                            $type_icon = 'fa-star';
                                                            break;
                                                        case 'Religious Event':
                                                            $type_class .= 'religious';
                                                            $type_icon = 'fa-church';
                                                            break;
                                                        case 'Cultural Event':
                                                            $type_class .= 'cultural';
                                                            $type_icon = 'fa-theater-masks';
                                                            break;
                                                        case 'Celebration':
                                                            $type_class .= 'celebration';
                                                            $type_icon = 'fa-cake';
                                                            break;
                                                        default:
                                                            $type_class .= 'festival';
                                                            $type_icon = 'fa-calendar';
                                                    }
                                                ?>
                                                <span class="type-badge <?php echo $type_class; ?>">
                                                    <i class="fas <?php echo $type_icon; ?> mr-1"></i> <?php echo htmlspecialchars($event['event_type']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 text-sm"><?php echo htmlspecialchars($event['description']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="py-8 px-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            No events found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="border-t border-gray-200 px-4 py-4">
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                                <!-- Pagination Info -->
                                <div class="pagination-info">
                                    Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $total_records); ?> of <?php echo $total_records; ?> events
                                </div>

                                <!-- Pagination Links -->
                                <div class="pagination">
                                    <!-- First Page -->
                                    <?php if ($current_page > 1): ?>
                                        <a href="<?php echo buildPaginationUrl(1, $search, $filter_type, $filter_month, $records_per_page); ?>" title="First Page">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class="fas fa-angle-double-left"></i>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Previous Page -->
                                    <?php if ($current_page > 1): ?>
                                        <a href="<?php echo buildPaginationUrl($current_page - 1, $search, $filter_type, $filter_month, $records_per_page); ?>" title="Previous Page">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class="fas fa-angle-left"></i>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Page Numbers -->
                                    <?php
                                    $start_page = max(1, $current_page - 2);
                                    $end_page = min($total_pages, $current_page + 2);

                                    // Show first page if not in range
                                    if ($start_page > 1) {
                                        echo '<a href="' . buildPaginationUrl(1, $search, $filter_type, $filter_month, $records_per_page) . '">1</a>';
                                        if ($start_page > 2) {
                                            echo '<span class="disabled">...</span>';
                                        }
                                    }

                                    // Show page numbers
                                    for ($i = $start_page; $i <= $end_page; $i++) {
                                        if ($i == $current_page) {
                                            echo '<span class="active">' . $i . '</span>';
                                        } else {
                                            echo '<a href="' . buildPaginationUrl($i, $search, $filter_type, $filter_month, $records_per_page) . '">' . $i . '</a>';
                                        }
                                    }

                                    // Show last page if not in range
                                    if ($end_page < $total_pages) {
                                        if ($end_page < $total_pages - 1) {
                                            echo '<span class="disabled">...</span>';
                                        }
                                        echo '<a href="' . buildPaginationUrl($total_pages, $search, $filter_type, $filter_month, $records_per_page) . '">' . $total_pages . '</a>';
                                    }
                                    ?>

                                    <!-- Next Page -->
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="<?php echo buildPaginationUrl($current_page + 1, $search, $filter_type, $filter_month, $records_per_page); ?>" title="Next Page">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class="fas fa-angle-right"></i>
                                        </span>
                                    <?php endif; ?>

                                    <!-- Last Page -->
                                    <?php if ($current_page < $total_pages): ?>
                                        <a href="<?php echo buildPaginationUrl($total_pages, $search, $filter_type, $filter_month, $records_per_page); ?>" title="Last Page">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="disabled">
                                            <i class="fas fa-angle-double-right"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Records Per Page Selector -->
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-600">Per page:</label>
                                    <select onchange="changePerPage(this.value)" class="border border-gray-300 rounded px-2 py-1 text-sm">
                                        <option value="10" <?php echo $records_per_page == 10 ? 'selected' : ''; ?>>10</option>
                                        <option value="25" <?php echo $records_per_page == 25 ? 'selected' : ''; ?>>25</option>
                                        <option value="50" <?php echo $records_per_page == 50 ? 'selected' : ''; ?>>50</option>
                                        <option value="100" <?php echo $records_per_page == 100 ? 'selected' : ''; ?>>100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Change records per page
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.set('page', '1'); // Reset to first page
            window.location.href = url.toString();
        }

        // Handle search form submission
        document.addEventListener('DOMContentLoaded', function() {
            // Reset to page 1 when filters change
            const searchForm = document.querySelector('form[method="GET"]');
            if (searchForm) {
                const inputs = searchForm.querySelectorAll('input[name="search"], select[name="filter_month"], select[name="filter_type"]');
                inputs.forEach(input => {
                    input.addEventListener('change', function() {
                        // Remove page parameter to reset to page 1
                        const pageInput = searchForm.querySelector('input[name="page"]');
                        if (pageInput) {
                            pageInput.remove();
                        }
                    });
                });
            }

            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('button.md\\:hidden');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', () => {
                    document.querySelector('.sidebar').classList.toggle('hidden');
                });
            }
        });
    </script>
</body>
</html>