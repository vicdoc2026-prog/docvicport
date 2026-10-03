<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Get counts from database
$ordinances_count = 0;
$resolutions_count = 0;

// Count ordinances
$ordinances_result = $conn->query("SELECT COUNT(*) as count FROM ordinances WHERE status = 'approved'");
if ($ordinances_result) {
    $ordinances_row = $ordinances_result->fetch_assoc();
    $ordinances_count = $ordinances_row['count'];
}

// Count resolutions
$resolutions_result = $conn->query("SELECT COUNT(*) as count FROM resolutions WHERE status = 'approved'");
if ($resolutions_result) {
    $resolutions_row = $resolutions_result->fetch_assoc();
    $resolutions_count = $resolutions_row['count'];
}

// Get upcoming sessions (next 2 sessions)
$upcoming_sessions_query = "SELECT * FROM sessions 
                            WHERE session_date >= CURDATE() 
                            AND status IN ('scheduled', 'ongoing') 
                            ORDER BY session_date ASC, start_time ASC 
                            LIMIT 2";
$upcoming_sessions = $conn->query($upcoming_sessions_query);

// Get upcoming events from regular_events with new structure
// Construct date from month and day columns for comparison
$upcoming_events_query = "SELECT *, 
                          CONCAT(YEAR(CURDATE()), '-', 
                                 LPAD(month, 2, '0'), '-', 
                                 LPAD(day, 2, '0')) as constructed_date
                          FROM regular_events 
                          WHERE CONCAT(YEAR(CURDATE()), '-', 
                                      LPAD(month, 2, '0'), '-', 
                                      LPAD(day, 2, '0')) >= CURDATE() 
                          ORDER BY month ASC, day ASC 
                          LIMIT 2";
$upcoming_events_result = $conn->query($upcoming_events_query);

// Store events in array
$upcoming_events = [];
if ($upcoming_events_result && $upcoming_events_result->num_rows > 0) {
    while ($event = $upcoming_events_result->fetch_assoc()) {
        $upcoming_events[] = $event;
    }
}

// Board members data - 1 Vice Governor + 13 Board Members = 14 total
$board_members = [
    [
        'name' => 'Hon. Richard D. Olegario',
        'position' => 'Vice Governor / Presiding Officer',
        'image' => 'SP_IMAGE/VC.jpg',
        'special' => true
    ],
    [
        'name' => 'Hon. Judge GC Sabijon',
        'position' => 'Board Member',
        'district' => 'District 2',
        'image' => 'SP_IMAGE/JUDGE.jpg'
    ],
    [
        'name' => 'Hon. GPS Hofer',
        'position' => 'Board Member',
        'district' => 'District 2',
        'image' => 'SP_IMAGE/F.jpg'
    ],
    [
        'name' => 'Hon. Eufemio D. Javier Jr.',
        'position' => 'Board Member',
        'district' => 'District 2',
        'image' => 'SP_IMAGE/DAS.jpg'
    ],
    [
        'name' => 'Hon. Edwin Alibutdan',
        'position' => 'Board Member',
        'district' => 'District 2',
        'image' => 'SP_IMAGE/CASC.jpg'
    ],
    [
        'name' => 'Hon. Nath Eudela',
        'position' => 'Board Member',
        'district' => 'District 2',
        'image' => 'SP_IMAGE/SCSAC.jpg'
    ],
    [
        'name' => 'Hon. PS Yanga',
        'position' => 'Board Member',
        'district' => 'District 1',
        'image' => 'SP_IMAGE/ssss.jpg'
    ],
    [
        'name' => 'Hon. JVF Mendoza',
        'position' => 'Board Member',
        'district' => 'District 1',
        'image' => 'SP_IMAGE/S.jpg'
    ],
    [
        'name' => 'Hon. JC Yambao',
        'position' => 'Board Member',
        'district' => 'District 1',
        'image' => 'SP_IMAGE/DD.jpg'
    ],
    [
        'name' => 'Hon. Ralph De Los Santos',
        'position' => 'Board Member',
        'district' => 'District 1',
        'image' => 'SP_image/CSACSA.jpg'
    ],
    [
        'name' => 'Hon. Roger P. Lu',
        'position' => 'Board Member',
        'district' => 'District 1',
        'image' => 'SP_IMAGE/CSACSAC.jpg'
    ],
    [
        'name' => 'Hon. AB Musa, LNMB',
        'position' => 'Board Member',
        'district' => 'LNMB',
        'image' => 'SP_IMAGE/musa.jpg'
    ],
    [
        'name' => 'Hon. SL Senarlo, SK',
        'position' => 'Board Member',
        'district' => 'SK Federation President',
        'image' => 'SP_IMAGE/HERS.jpg'
    ],
    [
        'name' => 'Hon. SMA Hasim',
        'position' => 'Board Member',
        'district' => '',
        'image' => 'SP_IMAGE/CCSD.jpg'
    ]
];

// Function to calculate days until event using constructed date
function getDaysUntil($date) {
    $today = new DateTime();
    $event_date = new DateTime($date);
    $diff = $today->diff($event_date);
    return $diff->days;
}

// Function to get event color based on type or random
function getEventColor($index) {
    $colors = ['blue', 'red', 'green', 'yellow'];
    return $colors[$index % count($colors)];
}

// Function to get event icon based on type
function getEventIcon($event_type) {
    $icons = [
        'meeting' => 'fa-handshake',
        'conference' => 'fa-users',
        'training' => 'fa-graduation-cap',
        'default' => 'fa-calendar-day'
    ];
    return $icons[$event_type] ?? $icons['default'];
}

// Function to get month name
function getMonthName($month_num) {
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
    return $months[(int)$month_num] ?? '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sangguniang Panlalawigan Dashboard</title>
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

        .member-card {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .member-card:hover {
            transform: scale(1.05);
        }

        .member-image {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin: 0 auto;
        }

        .vice-governor-card {
            border: 3px solid var(--accent);
            background: linear-gradient(135deg, rgba(26, 58, 108, 0.05), rgba(42, 157, 143, 0.05));
        }

        .vice-governor-image {
            width: 150px;
            height: 150px;
            border: 5px solid var(--accent);
        }

        .session-item {
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .session-item:hover {
            background-color: #f9fafb;
            border-left-color: var(--accent);
        }

        .event-item {
            transition: all 0.3s ease;
        }

        .event-item:hover {
            background-color: #f9fafb;
        }

        .days-badge {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .event-blue {
            border-left: 4px solid #3b82f6;
        }

        .event-red {
            border-left: 4px solid #ef4444;
        }

        .event-green {
            border-left: 4px solid #10b981;
        }

        .event-yellow {
            border-left: 4px solid #f59e0b;
        }

        /* Grid layout for first 10 board members - Fixed 5 columns */
        .members-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 1.5rem;
            grid-auto-rows: minmax(180px, auto);
        }

        /* Grid layout for last 3 board members - 3 columns centered */
        .last-members-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            max-width: 60%;
            margin: 0 auto;
            grid-auto-rows: minmax(180px, auto);
        }

        /* Responsive breakpoints for main grid */
        @media (max-width: 1280px) {
            .members-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            .last-members-grid {
                grid-template-columns: repeat(3, 1fr);
                max-width: 75%;
            }
        }

        @media (max-width: 1024px) {
            .members-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .last-members-grid {
                grid-template-columns: repeat(3, 1fr);
                max-width: 100%;
            }
        }

        @media (max-width: 768px) {
            .members-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .last-members-grid {
                grid-template-columns: repeat(2, 1fr);
                max-width: 100%;
            }
        }

        @media (max-width: 640px) {
            .members-grid {
                grid-template-columns: repeat(1, 1fr);
            }
            .last-members-grid {
                grid-template-columns: repeat(1, 1fr);
                max-width: 100%;
            }
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
            <!-- Dashboard Overview Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                <i class="fas fa-file-contract text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Ordinances Passed</h3>
                                <p class="text-3xl font-bold mt-1"><?php echo $ordinances_count; ?></p>
                                <p class="text-sm text-gray-500 mt-1">Total approved ordinances</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card bg-white">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-amber-100 text-amber-600 mr-4">
                                <i class="fas fa-file-alt text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-gray-500 text-sm uppercase">Resolutions Passed</h3>
                                <p class="text-3xl font-bold mt-1"><?php echo $resolutions_count; ?></p>
                                <p class="text-sm text-gray-500 mt-1">Total approved resolutions</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Sessions and Events -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Upcoming Sessions -->
                <div class="card bg-white">
                    <div class="card-header p-4 bg-gradient-to-r from-blue-50 to-blue-100">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-calendar-check mr-2 text-blue-600"></i>
                                Upcoming Sessions
                            </h2>
                            <a href="session.php" class="text-sm text-blue-600 hover:text-blue-800">View All →</a>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php if ($upcoming_sessions && $upcoming_sessions->num_rows > 0): ?>
                            <?php while ($session = $upcoming_sessions->fetch_assoc()): ?>
                                <?php
                                    $days_until = getDaysUntil($session['session_date']);
                                    $is_near = $days_until <= 7;
                                ?>
                                <div class="session-item p-4 mb-3 bg-white rounded-lg border <?php echo $is_near ? 'border-red-200' : 'border-gray-200'; ?>">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($session['session_number']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <i class="fas fa-calendar mr-2"></i>
                                                <?php echo date('F d, Y', strtotime($session['session_date'])); ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <i class="fas fa-clock mr-2"></i>
                                                <?php echo date('g:i A', strtotime($session['start_time'])) . ' - ' . date('g:i A', strtotime($session['end_time'])); ?>
                                            </p>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <i class="fas fa-map-marker-alt mr-2"></i>
                                                <?php echo htmlspecialchars($session['venue']); ?>
                                            </p>
                                        </div>
                                        <?php if ($is_near): ?>
                                            <span class="days-badge">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                <?php echo $days_until; ?> day<?php echo $days_until != 1 ? 's' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs bg-blue-100 text-blue-800 px-3 py-1 rounded-full">
                                                <?php echo $days_until; ?> days
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                                <p>No upcoming sessions scheduled</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="card bg-white">
                    <div class="card-header p-4 bg-gradient-to-r from-red-50 to-red-100">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                                <i class="fas fa-flag mr-2 text-red-600"></i>
                                Upcoming Events
                            </h2>
                            <a href="event.php" class="text-sm text-red-600 hover:text-red-800">View All →</a>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($upcoming_events)): ?>
                            <?php foreach ($upcoming_events as $index => $event): ?>
                                <?php
                                    $days_until = getDaysUntil($event['constructed_date']);
                                    $is_near = $days_until <= 7;
                                    $color = getEventColor($index);
                                    $icon = getEventIcon($event['event_type'] ?? 'default');
                                    $month_name = getMonthName($event['month']);
                                ?>
                                <div class="event-item event-<?php echo $color; ?> p-4 mb-3 bg-white rounded-lg border border-gray-200">
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-start flex-1">
                                            <div class="p-2 rounded-full bg-<?php echo $color; ?>-100 text-<?php echo $color; ?>-600 mr-3">
                                                <i class="fas <?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="flex-1">
                                                <h3 class="font-bold text-gray-800"><?php echo htmlspecialchars($event['event_name']); ?></h3>
                                                <p class="text-sm text-gray-600 mt-1">
                                                    <i class="fas fa-calendar mr-2"></i>
                                                    <?php echo $month_name . ' ' . $event['day'] . ', ' . date('Y'); ?>
                                                    <?php if (!empty($event['day_description'])): ?>
                                                        <span class="text-xs text-gray-500">(<?php echo htmlspecialchars($event['day_description']); ?>)</span>
                                                    <?php endif; ?>
                                                </p>
                                                <?php if (!empty($event['location'])): ?>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <i class="fas fa-map-marker-alt mr-1"></i>
                                                        <?php echo htmlspecialchars($event['location']); ?>
                                                    </p>
                                                <?php endif; ?>
                                                <?php if (!empty($event['description'])): ?>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        <?php echo htmlspecialchars(substr($event['description'], 0, 50)) . (strlen($event['description']) > 50 ? '...' : ''); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if ($is_near): ?>
                                            <span class="days-badge">
                                                <i class="fas fa-exclamation-circle mr-1"></i>
                                                <?php echo $days_until; ?> day<?php echo $days_until != 1 ? 's' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs bg-gray-100 text-gray-800 px-3 py-1 rounded-full">
                                                <?php echo $days_until; ?> days
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-3 block"></i>
                                <p>No upcoming events</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Board Members Section -->
            <div class="card bg-white mb-8">
                <div class="card-header p-6 border-b">
                    <div class="text-center">
                        <h2 class="text-2xl font-bold text-gray-800">9th Sangguniang Panlalawigan</h2>
                        <p class="text-gray-600 mt-2">Legislative Board Members (2025 - 2028)</p>
                    </div>
                </div>
                <div class="p-6">
                    <!-- Vice Governor - Featured -->
                    <div class="mb-8">
                        <div class="vice-governor-card card p-6 max-w-md mx-auto">
                            <div class="text-center">
                                <img src="<?php echo $board_members[0]['image']; ?>" 
                                     alt="<?php echo $board_members[0]['name']; ?>" 
                                     class="member-image vice-governor-image">
                                <h3 class="text-xl font-bold text-gray-800 mt-4"><?php echo $board_members[0]['name']; ?></h3>
                                <p class="text-accent font-semibold mt-1"><?php echo $board_members[0]['position']; ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- First 10 Board Members Grid - 5 columns -->
                    <div class="members-grid mb-6" style="margin-top: 50px;">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <div class="member-card card bg-white text-center p-4" style="border: 2px solid var(--primary);">
                                <img src="<?php echo $board_members[$i]['image']; ?>" 
                                     alt="<?php echo $board_members[$i]['name']; ?>" 
                                     class="member-image">
                                <h4 class="text-sm font-bold text-gray-800 mt-3"><?php echo $board_members[$i]['name']; ?></h4>
                                <p class="text-xs text-gray-600 mt-1"><?php echo $board_members[$i]['position']; ?></p>
                                <p class="text-xs text-primary font-semibold mt-1"><?php echo $board_members[$i]['district']; ?></p>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <!-- Last 3 Board Members Grid - 3 columns centered -->
                    <div class="last-members-grid">
                        <?php for ($i = 11; $i < count($board_members); $i++): ?>
                            <div class="member-card card bg-white text-center p-4" style="border: 2px solid var(--primary);">
                                <img src="<?php echo $board_members[$i]['image']; ?>" 
                                     alt="<?php echo $board_members[$i]['name']; ?>" 
                                     class="member-image">
                                <h4 class="text-sm font-bold text-gray-800 mt-3"><?php echo $board_members[$i]['name']; ?></h4>
                                <p class="text-xs text-gray-600 mt-1"><?php echo $board_members[$i]['position']; ?></p>
                                <p class="text-xs text-primary font-semibold mt-1"><?php echo $board_members[$i]['district']; ?></p>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <a href="ordinances.php" class="card bg-white p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center">
                        <div class="p-4 rounded-full bg-purple-100 text-purple-600 mr-4">
                            <i class="fas fa-file-contract text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">View Ordinances</h3>
                            <p class="text-sm text-gray-600 mt-1">Browse all ordinances</p>
                        </div>
                    </div>
                </a>

                <a href="resolutions.php" class="card bg-white p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center">
                        <div class="p-4 rounded-full bg-amber-100 text-amber-600 mr-4">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">View Resolutions</h3>
                            <p class="text-sm text-gray-600 mt-1">Browse all resolutions</p>
                        </div>
                    </div>
                </a>

                <a href="session.php" class="card bg-white p-6 hover:shadow-xl transition-all">
                    <div class="flex items-center">
                        <div class="p-4 rounded-full bg-blue-100 text-blue-600 mr-4">
                            <i class="fas fa-calendar-check text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800">View Sessions</h3>
                            <p class="text-sm text-gray-600 mt-1">Browse all sessions</p>
                        </div>
                    </div>
                </a>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile menu toggle
            const mobileMenuBtn = document.querySelector('button.md\\:hidden');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', () => {
                    document.querySelector('.sidebar').classList.toggle('hidden');
                });
            }

            // Member card click effect
            const memberCards = document.querySelectorAll('.member-card');
            memberCards.forEach(card => {
                card.addEventListener('click', function() {
                    const memberName = this.querySelector('h4').textContent;
                    console.log('Clicked on:', memberName);
                });
            });
        });
    </script>
</body>
</html>