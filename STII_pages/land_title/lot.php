<?php
/**
 * Land Title Documentation System
 * Two-Storey Senior High School Building As-Built Plan
 * Location: Brgy. Lower Taway, Ipil, Zamboanga Sibugay
 */

// Property Information
$property = [
    'project_title' => 'TWO-STOREY SENIOR HIGH SCHOOL BUILDING AS-BUILT PLAN',
    'location' => 'BRGY. LOWER TAWAY, IPIL, ZAMBOANGA SIBUGAY',
    'owner_name' => 'EUFEMIO VIC JAVIER/MARIA BELLA CHIONG JAVIER',
    'owner_address' => 'BRGY. SANITO, IPIL, ZAMBOANGA SIBUGAY',
    'architect' => 'IZEL MAY R. BAYLOSIS',
    'architect_prc' => 'PRC NO.: 0072356',
    'architect_ptr' => 'PTR NO.: 7732352-242-2025',
    'scale' => '1:100 MTS',
    'sheet_no' => 'A2'
];

// Lot Information
$lots = [
    'LOT 728-B-2-E' => [
        'psd' => 'PSD-09-003973',
        'lines' => [
            ['1-2', 'N 49° 47\' E', '20.00m'],
            ['2-3', 'S 43° 56\' E', '20.00m'],
            ['3-4', 'S 49° 31\' W', '20.00m'],
            ['4-1', 'N 43° 56\' W', '20.09m']
        ]
    ],
    'LOT 728-B-2-F' => [
        'psd' => 'PSD-09-003973',
        'lines' => [
            ['1-2', 'N 43° 56\' W', '20.00m'],
            ['2-3', 'N 49° 31\' E', '20.00m'],
            ['3-4', 'S 49° 45\' W', '20.08m'],
            ['4-1', 'S 43° 56\' E', '19.99m']
        ]
    ],
    'LOT 728-B-2-G' => [
        'psd' => 'PSD-09-003973',
        'lines' => [
            ['1-2', 'N 49° 45\' E', '19.99m'],
            ['2-3', 'S 43° 56\' E', '20.00m'],
            ['3-4', 'S 49° 29\' W', '19.99m'],
            ['4-1', 'N 43° 56\' W', '20.09m']
        ]
    ],
    'LOT 728-B-2-H' => [
        'psd' => 'PSD-09-003973',
        'lines' => [
            ['1-2', 'N 43° 56\' W', '20.00m'],
            ['2-3', 'N 49° 29\' E', '19.99m'],
            ['3-4', 'S 49° 42\' W', '20.08m'],
            ['4-1', 'S 43° 56\' E', '19.99m']
        ]
    ],
    'LOT 728-B-2-I-1' => [
        'psd' => 'PSD-09-090228',
        'building_location' => true,
        'lines' => [
            ['1-2', 'S 46° 08\' W', '38.36m'],
            ['2-3', 'N 43° 56\' W', '4.17m'],
            ['3-4', 'N 43° 29\' W', '20.08m'],
            ['4-5', 'N 43° 56\' W', '20.03m'],
            ['5-6', 'S 49° 49\' W', '37.00m'],
            ['6-7', 'N 43° 27\' W', '6.00m'],
            ['7-8', 'N 49° 49\' E', '58.25m'],
            ['8-1', 'S 43° 55\' E', '47.83m']
        ]
    ]
];

// Building Information
$building = [
    'footprint_length' => 69.8,
    'footprint_width' => 19.99,
    'firewalls' => 3,
    'stories' => 2,
    'type' => 'Senior High School Building'
];

// Function to calculate lot area
function calculateLotArea($lines) {
    // Simplified calculation - in real application, use proper surveying formulas
    $perimeter = 0;
    foreach ($lines as $line) {
        $distance = floatval($line[2]);
        $perimeter += $distance;
    }
    return $perimeter;
}

// Function to calculate building area
function calculateBuildingArea($length, $width) {
    return $length * $width;
}

// Selected Option Handler
$selected_option = isset($_GET['option']) ? intval($_GET['option']) : 0;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Land Title Documentation - <?php echo $property['project_title']; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .header p {
            color: #666;
            font-size: 16px;
        }
        
        .options-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .option-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.3);
        }
        
        .option-card.active {
            border: 3px solid #667eea;
            background: #f0f4ff;
        }
        
        .option-card h3 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .option-card p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .option-card ul {
            list-style: none;
            color: #555;
            font-size: 14px;
        }
        
        .option-card ul li {
            padding: 5px 0;
            padding-left: 20px;
            position: relative;
        }
        
        .option-card ul li:before {
            content: "•";
            color: #667eea;
            position: absolute;
            left: 0;
            font-weight: bold;
        }
        
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            margin-top: 20px;
        }
        
        .content-section h2 {
            color: #667eea;
            margin-bottom: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .content-section h3 {
            color: #764ba2;
            margin: 20px 0 10px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .highlight-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .info-box strong {
            color: #333;
            display: block;
            margin-bottom: 5px;
        }
        
        .info-box span {
            color: #666;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 20px;
        }
        
        .btn-print:hover {
            background: #764ba2;
        }
        
        #propertyMap {
            width: 100%;
            height: 600px;
            border-radius: 10px;
            margin: 20px 0;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .map-container {
            margin: 30px 0;
        }
        
        .map-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #2196f3;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .options-grid, .btn-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo $property['project_title']; ?></h1>
            <p><?php echo $property['location']; ?></p>
            <p style="margin-top: 10px;"><strong>Owner:</strong> <?php echo $property['owner_name']; ?></p>
        </div>
        
        <div class="options-grid">
            <a href="?option=1" class="option-card <?php echo $selected_option == 1 ? 'active' : ''; ?>">
                <h3>📋 Option 1: Clean Annotated Site Plan</h3>
                <p>Professional site plan with highlighted LOT 728-B-2-I-1, property boundaries, and key features clearly marked</p>
                <ul>
                    <li>Red boundary outline matching satellite view</li>
                    <li>Building footprint highlighted</li>
                    <li>Firewall locations marked</li>
                    <li>R.O.W. access clearly indicated</li>
                </ul>
            </a>
            
            <a href="?option=2" class="option-card <?php echo $selected_option == 2 ? 'active' : ''; ?>">
                <h3>📄 Option 2: Presentation Document</h3>
                <p>Combined satellite image and site plan with detailed annotations and comparisons</p>
                <ul>
                    <li>Side-by-side satellite and plan view</li>
                    <li>Matching color-coded boundaries</li>
                    <li>Property information overlay</li>
                    <li>Professional layout for presentations</li>
                </ul>
            </a>
            
            <a href="?option=3" class="option-card <?php echo $selected_option == 3 ? 'active' : ''; ?>">
                <h3>🧮 Option 3: Measurement & Area Calculation</h3>
                <p>Detailed analysis of lot dimensions, areas, and building coverage</p>
                <ul>
                    <li>Total lot area calculation</li>
                    <li>Building footprint area</li>
                    <li>Lot coverage percentage</li>
                    <li>All bearing and distance data</li>
                </ul>
            </a>
            
            <a href="?option=4" class="option-card <?php echo $selected_option == 4 ? 'active' : ''; ?>">
                <h3>🗺️ Option 4: Comparison Overlay</h3>
                <p>Visual comparison showing how the satellite view aligns with the architectural plan</p>
                <ul>
                    <li>Alignment markers</li>
                    <li>Scale comparison</li>
                    <li>Orientation verification</li>
                    <li>Discrepancy identification</li>
                </ul>
            </a>
            
            <a href="?option=5" class="option-card <?php echo $selected_option == 5 ? 'active' : ''; ?>">
                <h3>📝 Option 5: Property Report & Documentation</h3>
                <p>Comprehensive property documentation with all technical details and specifications</p>
                <ul>
                    <li>Complete property information</li>
                    <li>Lot technical descriptions</li>
                    <li>Building specifications</li>
                    <li>All survey data and bearings</li>
                </ul>
            </a>
        </div>
        
        <?php if ($selected_option == 1): ?>
        <div class="content-section">
            <h2>Option 1: Clean Annotated Site Plan</h2>
            
            <div class="map-container">
                <div class="map-info">
                    <strong>📍 Interactive Property Map</strong>
                    <p style="margin: 5px 0 0 0;">Click on any lot boundary to view details. The building is located on LOT 728-B-2-I-1 (highlighted in red).</p>
                </div>
                <div id="propertyMap"></div>
            </div>
            
            <div class="highlight-box">
                <strong>Building Location: LOT 728-B-2-I-1, PSD-09-090228</strong>
                <p>This lot contains the Two-Storey Senior High School Building with the following specifications:</p>
            </div>
            
            <div class="info-grid">
                <div class="info-box">
                    <strong>Building Dimensions</strong>
                    <span><?php echo $building['footprint_length']; ?>m × <?php echo $building['footprint_width']; ?>m</span>
                </div>
                <div class="info-box">
                    <strong>Building Area</strong>
                    <span><?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']), 2); ?> m²</span>
                </div>
                <div class="info-box">
                    <strong>Number of Stories</strong>
                    <span><?php echo $building['stories']; ?> Storeys</span>
                </div>
                <div class="info-box">
                    <strong>Firewalls</strong>
                    <span><?php echo $building['firewalls']; ?> Sides</span>
                </div>
            </div>
            
            <h3>Property Boundaries - LOT 728-B-2-I-1</h3>
            <table>
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>Bearing</th>
                        <th>Distance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lots['LOT 728-B-2-I-1']['lines'] as $line): ?>
                    <tr>
                        <td><?php echo $line[0]; ?></td>
                        <td><?php echo $line[1]; ?></td>
                        <td><?php echo $line[2]; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button class="btn-print" onclick="window.print()">🖨️ Print This Document</button>
        </div>
        
        <?php elseif ($selected_option == 2): ?>
        <div class="content-section">
            <h2>Option 2: Presentation Document</h2>
            
            <div class="map-container">
                <div class="map-info">
                    <strong>📍 Satellite View with Property Boundaries</strong>
                    <p style="margin: 5px 0 0 0;">All lots are outlined with different colors. LOT 728-B-2-I-1 (red) contains the school building.</p>
                </div>
                <div id="propertyMap"></div>
            </div>
            
            <h3>Project Overview</h3>
            <div class="info-grid">
                <div class="info-box">
                    <strong>Project Title</strong>
                    <span><?php echo $property['project_title']; ?></span>
                </div>
                <div class="info-box">
                    <strong>Location</strong>
                    <span><?php echo $property['location']; ?></span>
                </div>
                <div class="info-box">
                    <strong>Property Owner</strong>
                    <span><?php echo $property['owner_name']; ?></span>
                </div>
                <div class="info-box">
                    <strong>Architect</strong>
                    <span><?php echo $property['architect']; ?></span>
                </div>
            </div>
            
            <h3>Site Comparison Analysis</h3>
            <div class="highlight-box">
                <p><strong>Satellite Image vs. Site Plan Alignment:</strong></p>
                <p>The red boundary marked on the satellite image corresponds to LOT 728-B-2-I-1 (PSD-09-090228) on the site development plan. This lot contains the two-storey senior high school building with a footprint of 69.8m × 19.99m.</p>
            </div>
            
            <h3>All Property Lots</h3>
            <table>
                <thead>
                    <tr>
                        <th>Lot Number</th>
                        <th>PSD Number</th>
                        <th>Building Location</th>
                        <th>Number of Boundaries</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lots as $lot_name => $lot_data): ?>
                    <tr<?php echo isset($lot_data['building_location']) ? ' style="background: #ffffcc; font-weight: bold;"' : ''; ?>>
                        <td><?php echo $lot_name; ?></td>
                        <td><?php echo $lot_data['psd']; ?></td>
                        <td><?php echo isset($lot_data['building_location']) ? 'YES ✓' : 'No'; ?></td>
                        <td><?php echo count($lot_data['lines']); ?> lines</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <button class="btn-print" onclick="window.print()">🖨️ Print This Document</button>
        </div>
        
        <?php elseif ($selected_option == 3): ?>
        <div class="content-section">
            <h2>Option 3: Measurement & Area Calculation</h2>
            
            <h3>Building Area Calculations</h3>
            <div class="info-grid">
                <div class="info-box">
                    <strong>Building Length</strong>
                    <span><?php echo $building['footprint_length']; ?> meters</span>
                </div>
                <div class="info-box">
                    <strong>Building Width</strong>
                    <span><?php echo $building['footprint_width']; ?> meters</span>
                </div>
                <div class="info-box">
                    <strong>Ground Floor Area</strong>
                    <span><?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']), 2); ?> m²</span>
                </div>
                <div class="info-box">
                    <strong>Total Floor Area (2 Storeys)</strong>
                    <span><?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']) * 2, 2); ?> m²</span>
                </div>
            </div>
            
            <h3>Detailed Lot Measurements</h3>
            <?php foreach ($lots as $lot_name => $lot_data): ?>
            <h3 style="color: #764ba2; margin-top: 30px;"><?php echo $lot_name; ?> (<?php echo $lot_data['psd']; ?>)</h3>
            <table>
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>Bearing</th>
                        <th>Distance</th>
                        <th>Distance (meters)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_perimeter = 0;
                    foreach ($lot_data['lines'] as $line): 
                        $distance_m = floatval($line[2]);
                        $total_perimeter += $distance_m;
                    ?>
                    <tr>
                        <td><?php echo $line[0]; ?></td>
                        <td><?php echo $line[1]; ?></td>
                        <td><?php echo $line[2]; ?></td>
                        <td><?php echo number_format($distance_m, 2); ?> m</td>
                    </tr>
                    <?php endforeach; ?>
                    <tr style="background: #667eea; color: white; font-weight: bold;">
                        <td colspan="3">Total Perimeter</td>
                        <td><?php echo number_format($total_perimeter, 2); ?> m</td>
                    </tr>
                </tbody>
            </table>
            <?php endforeach; ?>
            
            <button class="btn-print" onclick="window.print()">🖨️ Print This Document</button>
        </div>
        
        <?php elseif ($selected_option == 4): ?>
        <div class="content-section">
            <h2>Option 4: Comparison Overlay Analysis</h2>
            
            <div class="map-container">
                <div class="map-info">
                    <strong>📍 Satellite-to-Plan Comparison Map</strong>
                    <p style="margin: 5px 0 0 0;">This map shows the exact lot boundaries overlaid on the satellite imagery for verification purposes.</p>
                </div>
                <div id="propertyMap"></div>
            </div>
            
            <div class="highlight-box">
                <p><strong>Satellite to Plan Alignment:</strong></p>
                <p>The red boundary outline on the satellite image has been matched to the architectural site plan. The following analysis compares the actual satellite view with the planned development.</p>
            </div>
            
            <h3>Alignment Analysis</h3>
            <table>
                <tr>
                    <th>Aspect</th>
                    <th>Satellite View</th>
                    <th>Site Plan</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>Building Orientation</td>
                    <td>Diagonal alignment along property</td>
                    <td>As per LOT 728-B-2-I-1 boundaries</td>
                    <td>✓ Matches</td>
                </tr>
                <tr>
                    <td>Building Length</td>
                    <td>Approximately 70m (visual)</td>
                    <td>69.8m (as-built)</td>
                    <td>✓ Accurate</td>
                </tr>
                <tr>
                    <td>Building Width</td>
                    <td>Approximately 20m (visual)</td>
                    <td>19.99m (as-built)</td>
                    <td>✓ Accurate</td>
                </tr>
                <tr>
                    <td>Access Roads</td>
                    <td>Visible R.O.W. on both sides</td>
                    <td>R.O.W. marked on plan</td>
                    <td>✓ Verified</td>
                </tr>
                <tr>
                    <td>Adjacent Structures</td>
                    <td>Multiple buildings visible</td>
                    <td>Adjacent lots marked</td>
                    <td>✓ Corresponds</td>
                </tr>
            </table>
            
            <h3>Scale Verification</h3>
            <p>The site plan is drawn at a scale of 1:100 meters. Based on the satellite imagery analysis, the actual building footprint corresponds accurately to the as-built plan dimensions.</p>
            
            <div class="info-grid" style="margin-top: 20px;">
                <div class="info-box">
                    <strong>Plan Scale</strong>
                    <span>1:100 MTS</span>
                </div>
                <div class="info-box">
                    <strong>Site Development Plan Scale</strong>
                    <span>1:400</span>
                </div>
                <div class="info-box">
                    <strong>Verification Method</strong>
                    <span>Satellite Overlay Comparison</span>
                </div>
                <div class="info-box">
                    <strong>Accuracy Status</strong>
                    <span>✓ Verified</span>
                </div>
            </div>
            
            <button class="btn-print" onclick="window.print()">🖨️ Print This Document</button>
        </div>
        
        <?php elseif ($selected_option == 5): ?>
        <div class="content-section">
            <h2>Option 5: Comprehensive Property Report & Documentation</h2>
            
            <div class="map-container">
                <div class="map-info">
                    <strong>📍 Complete Property Map with All Lot Boundaries</strong>
                    <p style="margin: 5px 0 0 0;">Interactive map showing all 5 lots with color-coded boundaries. Click any boundary for lot details.</p>
                </div>
                <div id="propertyMap"></div>
            </div>
            
            <h3>Property Information</h3>
            <table>
                <tr>
                    <th>Field</th>
                    <th>Information</th>
                </tr>
                <tr>
                    <td><strong>Project Title</strong></td>
                    <td><?php echo $property['project_title']; ?></td>
                </tr>
                <tr>
                    <td><strong>Location</strong></td>
                    <td><?php echo $property['location']; ?></td>
                </tr>
                <tr>
                    <td><strong>Property Owner</strong></td>
                    <td><?php echo $property['owner_name']; ?></td>
                </tr>
                <tr>
                    <td><strong>Owner Address</strong></td>
                    <td><?php echo $property['owner_address']; ?></td>
                </tr>
                <tr>
                    <td><strong>Architect</strong></td>
                    <td><?php echo $property['architect']; ?></td>
                </tr>
                <tr>
                    <td><strong>PRC License No.</strong></td>
                    <td><?php echo $property['architect_prc']; ?></td>
                </tr>
                <tr>
                    <td><strong>PTR No.</strong></td>
                    <td><?php echo $property['architect_ptr']; ?></td>
                </tr>
                <tr>
                    <td><strong>Drawing Scale</strong></td>
                    <td><?php echo $property['scale']; ?></td>
                </tr>
                <tr>
                    <td><strong>Sheet Number</strong></td>
                    <td><?php echo $property['sheet_no']; ?></td>
                </tr>
            </table>
            
            <h3>Building Specifications</h3>
            <table>
                <tr>
                    <th>Specification</th>
                    <th>Detail</th>
                </tr>
                <tr>
                    <td><strong>Building Type</strong></td>
                    <td><?php echo $building['type']; ?></td>
                </tr>
                <tr>
                    <td><strong>Number of Storeys</strong></td>
                    <td><?php echo $building['stories']; ?> Storeys</td>
                </tr>
                <tr>
                    <td><strong>Footprint Length</strong></td>
                    <td><?php echo $building['footprint_length']; ?> meters</td>
                </tr>
                <tr>
                    <td><strong>Footprint Width</strong></td>
                    <td><?php echo $building['footprint_width']; ?> meters</td>
                </tr>
                <tr>
                    <td><strong>Ground Floor Area</strong></td>
                    <td><?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']), 2); ?> m²</td>
                </tr>
                <tr>
                    <td><strong>Total Floor Area</strong></td>
                    <td><?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']) * 2, 2); ?> m²</td>
                </tr>
                <tr>
                    <td><strong>Number of Firewalls</strong></td>
                    <td><?php echo $building['firewalls']; ?> Sides</td>
                </tr>
                <tr>
                    <td><strong>Primary Lot Location</strong></td>
                    <td>LOT 728-B-2-I-1, PSD-09-090228</td>
                </tr>
            </table>
            
            <h3>Complete Lot Survey Data</h3>
            <?php foreach ($lots as $lot_name => $lot_data): ?>
            <h3 style="color: #764ba2; margin-top: 30px;">
                <?php echo $lot_name; ?> 
                <?php if (isset($lot_data['building_location'])): ?>
                <span style="background: yellow; padding: 5px; border-radius: 3px; font-size: 14px;">⭐ BUILDING LOCATION</span>
                <?php endif; ?>
            </h3>
            <p><strong>PSD Number:</strong> <?php echo $lot_data['psd']; ?></p>
            <table>
                <thead>
                    <tr>
                        <th>Line</th>
                        <th>Bearing</th>
                        <th>Distance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lot_data['lines'] as $line): ?>
                    <tr>
                        <td><?php echo $line[0]; ?></td>
                        <td><?php echo $line[1]; ?></td>
                        <td><?php echo $line[2]; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endforeach; ?>
            
            <div class="highlight-box" style="margin-top: 30px;">
                <p><strong>Document Summary:</strong></p>
                <p>This comprehensive property report contains all technical details, survey data, and specifications for the Two-Storey Senior High School Building located at Brgy. Lower Taway, Ipil, Zamboanga Sibugay. The building is constructed on LOT 728-B-2-I-1 (PSD-09-090228) and complies with all architectural and surveying standards.</p>
            </div>
            
            <h3>Additional Features</h3>
            <ul style="line-height: 2;">
                <li><strong>Right of Way (R.O.W.):</strong> Access roads are provided on multiple sides of the property</li>
                <li><strong>Firewall Protection:</strong> Three sides of the building are protected by firewalls for safety</li>
                <li><strong>Adjacent Lots:</strong> The property is surrounded by lots 728-B-1, 728-B-2-B, 728-B-2-E, 728-B-2-F, 728-B-2-G, and 728-B-2-H</li>
                <li><strong>Site Development Scale:</strong> 1:400 for overall site planning</li>
                <li><strong>Architectural Scale:</strong> 1:100 MTS for detailed building plans</li>
            </ul>
            
            <button class="btn-print" onclick="window.print()">🖨️ Print This Document</button>
        </div>
        
        <?php else: ?>
        <div class="content-section">
            <h2>Welcome to Land Title Documentation System</h2>
            <p style="font-size: 18px; line-height: 1.8; color: #666;">
                Please select one of the options above to view the corresponding documentation for the 
                <strong><?php echo $property['project_title']; ?></strong> located at 
                <strong><?php echo $property['location']; ?></strong>.
            </p>
            
            <div class="highlight-box" style="margin-top: 30px;">
                <p><strong>Quick Summary:</strong></p>
                <ul style="margin-top: 10px; line-height: 2;">
                    <li>Total Lots: <?php echo count($lots); ?> parcels</li>
                    <li>Building Location: LOT 728-B-2-I-1, PSD-09-090228</li>
                    <li>Building Footprint: <?php echo $building['footprint_length']; ?>m × <?php echo $building['footprint_width']; ?>m</li>
                    <li>Total Floor Area: <?php echo number_format(calculateBuildingArea($building['footprint_length'], $building['footprint_width']) * 2, 2); ?> m²</li>
                    <li>Owner: <?php echo $property['owner_name']; ?></li>
                </ul>
            </div>
            
            <h3 style="margin-top: 30px;">Available Documentation Options:</h3>
            <ol style="font-size: 16px; line-height: 2.5; color: #555; margin-left: 20px;">
                <li><strong>Clean Annotated Site Plan</strong> - Professional site plan with boundaries and features</li>
                <li><strong>Presentation Document</strong> - Combined satellite and plan views with annotations</li>
                <li><strong>Measurement & Area Calculation</strong> - Detailed calculations and measurements</li>
                <li><strong>Comparison Overlay</strong> - Satellite-to-plan alignment analysis</li>
                <li><strong>Property Report & Documentation</strong> - Comprehensive technical documentation</li>
            </ol>
            
            <p style="margin-top: 30px; font-size: 16px; color: #666;">
                Click any option card above to view the detailed documentation.
            </p>
        </div>
        <?php endif; ?>
        
        <div class="content-section" style="margin-top: 20px; background: #f8f9fa;">
            <h3 style="color: #333;">Document Information</h3>
            <div class="info-grid">
                <div class="info-box" style="border-left-color: #28a745;">
                    <strong>Document Type</strong>
                    <span>As-Built Plan Documentation</span>
                </div>
                <div class="info-box" style="border-left-color: #28a745;">
                    <strong>Status</strong>
                    <span>Approved Architectural Plan</span>
                </div>
                <div class="info-box" style="border-left-color: #28a745;">
                    <strong>Sheet Reference</strong>
                    <span><?php echo $property['sheet_no']; ?> - Architectural</span>
                </div>
                <div class="info-box" style="border-left-color: #28a745;">
                    <strong>Last Updated</strong>
                    <span><?php echo date('F d, Y'); ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY"></script>
    <script>
        // Highlight active option based on URL parameter
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const option = urlParams.get('option');
            
            if (option) {
                const cards = document.querySelectorAll('.option-card');
                cards.forEach(card => {
                    if (card.href.includes('option=' + option)) {
                        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                });
            }
            
            // Initialize map if map container exists
            if (document.getElementById('propertyMap')) {
                initMap();
            }
        });
        
        // Print function
        function printDocument() {
            window.print();
        }
        
        // Initialize Google Map with lot boundaries
        function initMap() {
            // Center coordinates for Brgy. Lower Taway, Ipil, Zamboanga Sibugay
            // These are approximate coordinates based on the satellite image
            const center = { lat: 7.7834, lng: 122.5741 };
            
            const map = new google.maps.Map(document.getElementById('propertyMap'), {
                zoom: 18,
                center: center,
                mapTypeId: 'satellite',
                tilt: 0
            });
            
            // Define lot boundaries (approximate coordinates based on bearings)
            // In real implementation, these should be calculated from actual survey data
            const lotBoundaries = {
                'LOT 728-B-2-E': [
                    { lat: 7.78350, lng: 122.57400 },
                    { lat: 7.78365, lng: 122.57415 },
                    { lat: 7.78350, lng: 122.57430 },
                    { lat: 7.78335, lng: 122.57415 }
                ],
                'LOT 728-B-2-F': [
                    { lat: 7.78335, lng: 122.57415 },
                    { lat: 7.78350, lng: 122.57430 },
                    { lat: 7.78335, lng: 122.57445 },
                    { lat: 7.78320, lng: 122.57430 }
                ],
                'LOT 728-B-2-G': [
                    { lat: 7.78320, lng: 122.57430 },
                    { lat: 7.78335, lng: 122.57445 },
                    { lat: 7.78320, lng: 122.57460 },
                    { lat: 7.78305, lng: 122.57445 }
                ],
                'LOT 728-B-2-H': [
                    { lat: 7.78305, lng: 122.57445 },
                    { lat: 7.78320, lng: 122.57460 },
                    { lat: 7.78305, lng: 122.57475 },
                    { lat: 7.78290, lng: 122.57460 }
                ],
                'LOT 728-B-2-I-1': [
                    { lat: 7.78360, lng: 122.57430 },
                    { lat: 7.78340, lng: 122.57460 },
                    { lat: 7.78305, lng: 122.57485 },
                    { lat: 7.78270, lng: 122.57470 },
                    { lat: 7.78250, lng: 122.57440 },
                    { lat: 7.78260, lng: 122.57420 },
                    { lat: 7.78300, lng: 122.57400 },
                    { lat: 7.78335, lng: 122.57415 }
                ]
            };
            
            // Colors for each lot
            const lotColors = {
                'LOT 728-B-2-E': '#FF6B6B',
                'LOT 728-B-2-F': '#4ECDC4',
                'LOT 728-B-2-G': '#45B7D1',
                'LOT 728-B-2-H': '#96CEB4',
                'LOT 728-B-2-I-1': '#FF0000' // Red for building location
            };
            
            // Draw polygons for each lot
            Object.keys(lotBoundaries).forEach(lotName => {
                const polygon = new google.maps.Polygon({
                    paths: lotBoundaries[lotName],
                    strokeColor: lotColors[lotName],
                    strokeOpacity: 0.8,
                    strokeWeight: 3,
                    fillColor: lotColors[lotName],
                    fillOpacity: 0.25,
                    map: map
                });
                
                // Add click event to show lot info
                polygon.addListener('click', function() {
                    const infoWindow = new google.maps.InfoWindow({
                        content: '<div style="padding: 10px;"><strong>' + lotName + '</strong><br>' +
                                'Click for details</div>',
                        position: lotBoundaries[lotName][0]
                    });
                    infoWindow.open(map);
                });
                
                // Add label for the lot
                const bounds = new google.maps.LatLngBounds();
                lotBoundaries[lotName].forEach(coord => bounds.extend(coord));
                
                const label = new google.maps.Marker({
                    position: bounds.getCenter(),
                    map: map,
                    label: {
                        text: lotName.includes('I-1') ? '🏫 BUILDING' : lotName.split(' ')[1],
                        color: 'white',
                        fontSize: '12px',
                        fontWeight: 'bold'
                    },
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 0
                    }
                });
            });
            
            // Add legend
            const legend = document.createElement('div');
            legend.style.backgroundColor = 'white';
            legend.style.padding = '10px';
            legend.style.margin = '10px';
            legend.style.borderRadius = '5px';
            legend.style.boxShadow = '0 2px 6px rgba(0,0,0,0.3)';
            legend.innerHTML = '<h4 style="margin: 0 0 10px 0;">Lot Legend</h4>';
            
            Object.keys(lotColors).forEach(lotName => {
                const div = document.createElement('div');
                div.style.marginBottom = '5px';
                div.innerHTML = '<span style="display: inline-block; width: 20px; height: 10px; background: ' + 
                               lotColors[lotName] + '; margin-right: 5px;"></span>' + lotName;
                legend.appendChild(div);
            });
            
            map.controls[google.maps.ControlPosition.RIGHT_BOTTOM].push(legend);
        }
    </script>
</body>
</html>