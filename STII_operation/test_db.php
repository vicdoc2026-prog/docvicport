<?php
/**
 * Database Connection and Calendar Events Checker
 * Place this file in your project root and access it via browser
 * Example: http://localhost/your-project/test-db.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-left: 4px solid green; }
        .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-left: 4px solid red; }
        .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-left: 4px solid blue; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background: #f2f2f2; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
        h1 { color: #333; }
        h2 { color: #555; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
    </style>
</head>
<body>
    <h1>🔧 Database Connection & Calendar Events Test</h1>";

// Test 1: Check if config file exists
echo "<h2>Test 1: Configuration File</h2>";
if (file_exists('config/conn_pdo.php')) {
    echo "<div class='success'>✓ config/conn_pdo.php exists</div>";
} else {
    echo "<div class='error'>✗ config/conn_pdo.php not found</div>";
    echo "<div class='info'>Please create config/conn_pdo.php with your database credentials</div>";
    exit;
}

// Test 2: Include config and check connection
echo "<h2>Test 2: Database Connection</h2>";
try {
    require_once 'config/conn_pdo.php';
    
    if (isset($conn)) {
        echo "<div class='success'>✓ Database connection variable exists</div>";
        
        // Check if it's a PDO instance
        if ($conn instanceof PDO) {
            echo "<div class='success'>✓ Connection is a valid PDO instance</div>";
        } else {
            echo "<div class='error'>✗ Connection exists but is NOT a PDO instance</div>";
            echo "<div class='info'>Current type: " . gettype($conn) . "</div>";
            echo "<div class='info'>Your config/conn_pdo.php should use PDO, not mysqli</div>";
            exit;
        }
    } else {
        echo "<div class='error'>✗ Database connection variable (\$conn) not set</div>";
        exit;
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error including config: " . $e->getMessage() . "</div>";
    exit;
}

// Test 3: Check PDO attributes
echo "<h2>Test 3: PDO Configuration</h2>";
try {
    $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "<div class='success'>✓ PDO Driver: " . $driver . "</div>";
    
    $errorMode = $conn->getAttribute(PDO::ATTR_ERRMODE);
    $errorModes = [
        PDO::ERRMODE_SILENT => 'SILENT',
        PDO::ERRMODE_WARNING => 'WARNING',
        PDO::ERRMODE_EXCEPTION => 'EXCEPTION'
    ];
    echo "<div class='success'>✓ Error Mode: " . ($errorModes[$errorMode] ?? 'UNKNOWN') . "</div>";
    
    if ($errorMode !== PDO::ERRMODE_EXCEPTION) {
        echo "<div class='info'>ℹ Recommended: Set error mode to EXCEPTION in your config</div>";
        echo "<div class='info'><code>\$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);</code></div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>✗ Error checking PDO attributes: " . $e->getMessage() . "</div>";
}

// Test 4: Check if table exists
echo "<h2>Test 4: Table Existence</h2>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'calendar_events'");
    $result = $stmt->fetch();
    
    if ($result) {
        echo "<div class='success'>✓ Table 'calendar_events' exists</div>";
    } else {
        echo "<div class='error'>✗ Table 'calendar_events' does NOT exist</div>";
        echo "<div class='info'>Please run the SQL script from calendar_events.sql</div>";
        exit;
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error checking table: " . $e->getMessage() . "</div>";
    exit;
}

// Test 5: Check table structure
echo "<h2>Test 5: Table Structure</h2>";
try {
    $stmt = $conn->query("DESCRIBE calendar_events");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error checking table structure: " . $e->getMessage() . "</div>";
}

// Test 6: Count events
echo "<h2>Test 6: Event Data</h2>";
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM calendar_events");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='success'>✓ Total events in database: " . $count['count'] . "</div>";
    
    if ($count['count'] == 0) {
        echo "<div class='info'>ℹ No events found. Run the INSERT statements from calendar_events.sql</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error counting events: " . $e->getMessage() . "</div>";
}

// Test 7: Fetch sample events
echo "<h2>Test 7: Sample Events (First 5)</h2>";
try {
    $stmt = $conn->query("SELECT id, start_date, end_date, title, category FROM calendar_events ORDER BY start_date LIMIT 5");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($events) > 0) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Start Date</th><th>End Date</th><th>Title</th><th>Category</th></tr>";
        foreach ($events as $event) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($event['id']) . "</td>";
            echo "<td>" . htmlspecialchars($event['start_date']) . "</td>";
            echo "<td>" . htmlspecialchars($event['end_date'] ?? 'NULL') . "</td>";
            echo "<td>" . htmlspecialchars($event['title']) . "</td>";
            echo "<td>" . htmlspecialchars($event['category']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No events to display</div>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error fetching events: " . $e->getMessage() . "</div>";
}

// Test 8: Test API endpoint simulation
echo "<h2>Test 8: API Simulation (get_all)</h2>";
try {
    $stmt = $conn->prepare("SELECT id, start_date, end_date, title, category FROM calendar_events ORDER BY start_date");
    $stmt->execute();
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted = array_map(function($event) {
        return [
            'id' => 'ev' . $event['id'],
            'start' => $event['start_date'],
            'end' => $event['end_date'],
            'title' => $event['title'],
            'cat' => $event['category']
        ];
    }, $events);
    
    echo "<div class='success'>✓ Successfully formatted " . count($formatted) . " events for frontend</div>";
    echo "<div class='info'>Sample JSON output (first event):</div>";
    if (count($formatted) > 0) {
        echo "<pre>" . json_encode($formatted[0], JSON_PRETTY_PRINT) . "</pre>";
    }
} catch (PDOException $e) {
    echo "<div class='error'>✗ Error in API simulation: " . $e->getMessage() . "</div>";
}

// Test 9: Check API file
echo "<h2>Test 9: API File Check</h2>";
if (file_exists('api/calendar.php')) {
    echo "<div class='success'>✓ api/calendar.php exists</div>";
    echo "<div class='info'>Test the API endpoint: <a href='api/calendar.php?action=get_all' target='_blank'>api/calendar.php?action=get_all</a></div>";
} else {
    echo "<div class='error'>✗ api/calendar.php not found</div>";
    echo "<div class='info'>Please create the api directory and place calendar.php inside</div>";
}

// Test 10: Check session
echo "<h2>Test 10: Session Check</h2>";
if (file_exists('config/check-session.php')) {
    echo "<div class='success'>✓ config/check-session.php exists</div>";
    
    session_start();
    if (isset($_SESSION['username'])) {
        echo "<div class='success'>✓ Session active for user: " . htmlspecialchars($_SESSION['username']) . "</div>";
    } else {
        echo "<div class='info'>ℹ No active session. The API requires an active session to work.</div>";
    }
} else {
    echo "<div class='error'>✗ config/check-session.php not found</div>";
}

echo "<hr>";
echo "<h2>✅ Summary</h2>";
echo "<div class='success'><strong>If all tests passed, your database setup is correct!</strong></div>";
echo "<div class='info'>Next steps:";
echo "<ol>";
echo "<li>Make sure you're logged in (active session)</li>";
echo "<li>Open calendar.php in your browser</li>";
echo "<li>Check browser console for any JavaScript errors</li>";
echo "<li>Check browser Network tab to see API responses</li>";
echo "</ol></div>";

echo "</body></html>";
?>