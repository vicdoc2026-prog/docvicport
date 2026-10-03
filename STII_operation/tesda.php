<?php
include 'conn.php';

require_once 'config/check-session.php';
require_once 'config/conn.php';

$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Fetch all data
$tesda_sql = "SELECT qualification, ntr_wtr, ctpr_number, date_issued, validity_date, no_of_hours, no_of_days, training_cost 
              FROM tesda_qualifications ORDER BY qualification";
$tesda_result = $conn->query($tesda_sql);

$training_sql = "SELECT center_name, address, accreditation_number, date_accredited, validity_date 
                 FROM training_centers ORDER BY center_name";
$training_result = $conn->query($training_sql);

$assessment_sql = "SELECT center_name, qualifications, address, accreditation_number, date_accredited, validity_date 
                  FROM assessment_centers ORDER BY center_name";
$assessment_result = $conn->query($assessment_sql);

// Prepare data + totals
$qualifications = [];
$training_centers = [];
$assessment_centers = [];

$total_qual = $total_train = $total_assess = $total_hours = $total_days = 0;
$total_cost = 0.0;

if ($tesda_result->num_rows > 0) {
    while ($row = $tesda_result->fetch_assoc()) {
        $qualifications[] = $row;
        $total_qual++;

        $total_hours += (int)preg_replace('/\D/', '', $row['no_of_hours'] ?? '0');
        $total_days += (int)preg_replace('/\D/', '', $row['no_of_days'] ?? '0');

        $cost = str_replace(',', '', $row['training_cost'] ?? '0');
        $total_cost += is_numeric($cost) ? (float)$cost : 0;
    }
}

if ($training_result->num_rows > 0) {
    while ($row = $training_result->fetch_assoc()) {
        $training_centers[] = $row;
        $total_train++;
    }
}

if ($assessment_result->num_rows > 0) {
    while ($row = $assessment_result->fetch_assoc()) {
        $assessment_centers[] = $row;
        $total_assess++;
    }
}

// Helper function to calculate status
function calculateStatus($validity_date) {
    if (empty($validity_date) || $validity_date === '0000-00-00') {
        return ['status' => 'N/A', 'badge' => 'badge-secondary', 'formatted' => 'N/A'];
    }
    
    try {
        $validityDateTime = new DateTime($validity_date);
        $today = new DateTime('today');
        
        $interval = $today->diff($validityDateTime);
        $days = (int)$interval->format('%r%a');
        
        if ($days < 0) {
            $status = 'Expired';
            $badge = 'badge-danger';
        } elseif ($days <= 90) {
            $status = 'Expiring Soon';
            $badge = 'badge-warning';
        } else {
            $status = 'Valid';
            $badge = 'badge-success';
        }
        
        return [
            'status' => $status,
            'badge' => $badge,
            'formatted' => $validityDateTime->format('M d, Y')
        ];
    } catch (Exception $e) {
        return ['status' => 'N/A', 'badge' => 'badge-secondary', 'formatted' => 'N/A'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TESDA NC2 Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&family=Orbitron:wght@700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f0f9ff, #e6f7ff, #d1eeff);
            background-size: 400% 400%;
            animation: gradientBG 18s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        @keyframes gradientBG { 
            0% { background-position: 0% 50%; } 
            50% { background-position: 100% 50%; } 
            100% { background-position: 0% 50%; } 
        }

        .glitch-effect {
            position: relative;
            display: inline-block;
        }
        .glitch-effect::before,
        .glitch-effect::after {
            content: attr(data-text);
            position: absolute;
            top: 0; 
            left: 0;
            width: 100%;
            overflow: hidden;
            clip: rect(0, 900px, 0, 0);
        }
        .glitch-effect::before { 
            left: 2px; 
            text-shadow: -2px 0 #ff00c1; 
            animation: glitch 2s infinite linear alternate-reverse; 
        }
        .glitch-effect::after { 
            left: -2px; 
            text-shadow: -2px 0 #00fff9; 
            animation: glitch 2s infinite linear alternate-reverse; 
        }
        
        @keyframes glitch { 
            0% { clip: rect(42px,9999px,44px,0); } 
            100% { clip: rect(12px,9999px,18px,0); } 
        }

        .glass-container {
            background: rgba(255,255,255,0.88);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 28px;
            box-shadow: 0 15px 45px rgba(0,0,0,0.1);
            flex: 1;
            width: 100%;
        }

        .brand-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            font-size: 3.2rem;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6, #10b981);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dashboard-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2.6rem;
            color: #10b981;
        }

        .tesda-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        .tesda-table th {
            background: #10b981;
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 600;
        }
        .tesda-table td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        .tesda-table tr:nth-child(even) { 
            background-color: #f8fafc; 
        }
        .tesda-table tr:hover { 
            background-color: rgba(16,185,129,0.12); 
        }

        .badge {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-success { 
            background: #d1fae5; 
            color: #065f46; 
        }
        .badge-warning { 
            background: #fef3c7; 
            color: #92400e; 
        }
        .badge-danger { 
            background: #fee2e2; 
            color: #991b1b; 
        }
        .badge-secondary { 
            background: #e2e8f0; 
            color: #475569; 
        }

        .tab-btn {
            padding: 1rem 2rem;
            border-bottom: 4px solid transparent;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s;
        }
        .tab-btn:hover { 
            color: #10b981; 
            border-bottom-color: #10b981; 
        }
        .tab-btn.active-tab { 
            color: #10b981; 
            border-bottom-color: #10b981; 
        }
        .tab-count {
            background: #e2e8f0;
            color: #475569;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.8rem;
            margin-left: 8px;
        }
        .tab-btn.active-tab .tab-count { 
            background: #d1fae5; 
            color: #10b981; 
        }

        .particles { 
            position: fixed; 
            top: 0; 
            left: 0; 
            width: 100%; 
            height: 100%; 
            pointer-events: none; 
            z-index: 0; 
        }

        .pdf-btn {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .pdf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(239, 68, 68, 0.4);
        }

        .pdf-controls {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        /* Main content adjustments */
        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
            width: calc(100% - 280px);
        }

        .sidebar.collapsed ~ .main-content {
            margin-left: 90px;
            width: calc(100% - 90px);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
                padding: 1rem;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .dashboard-text {
                font-size: 1.5rem;
            }
            
            .glass-container {
                padding: 1rem !important;
            }
            
            .tab-btn {
                padding: 0.75rem 1rem;
                font-size: 0.875rem;
            }
        }

        /* Mobile sidebar adjustments */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 1rem;
            }
            
            .sidebar.collapsed {
                width: 100%;
                padding: 1rem;
            }
            
            .sidebar.collapsed .sidebar-link span,
            .sidebar.collapsed .sidebar-back span {
                opacity: 1;
                width: auto;
                position: relative;
            }
            
            .sidebar.collapsed .sidebar-logo p {
                opacity: 1;
                height: auto;
                margin-top: 0.5rem;
            }
            
            .toggle-sidebar {
                display: none;
            }
        }
    </style>
</head>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" defer></script>
<body>

    <div class="particles" id="particles"></div>

    <!-- Include sidebar -->
    <?php include 'bar/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="glass-container w-full p-8 md:p-12 relative z-10">
            
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="brand-title glitch-effect" data-text="Sibugay Technical Institute Incorporated">
                    Sibugay Technical Institute Incorporated
                </h1>
                <p class="dashboard-text">TESDA Management Dashboard</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-12">
                <div class="bg-white/90 backdrop-blur rounded-2xl p-4 md:p-6 text-center shadow-lg hover:shadow-xl transition-shadow">
                    <i class="fas fa-certificate text-green-500 text-4xl md:text-5xl mb-2 md:mb-3"></i>
                    <div class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($total_qual); ?></div>
                    <div class="text-xs md:text-sm text-gray-600">Qualifications</div>
                </div>
                <div class="bg-white/90 backdrop-blur rounded-2xl p-4 md:p-6 text-center shadow-lg hover:shadow-xl transition-shadow">
                    <i class="fas fa-building text-blue-500 text-4xl md:text-5xl mb-2 md:mb-3"></i>
                    <div class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($total_train); ?></div>
                    <div class="text-xs md:text-sm text-gray-600">Training Centers</div>
                </div>
                <div class="bg-white/90 backdrop-blur rounded-2xl p-4 md:p-6 text-center shadow-lg hover:shadow-xl transition-shadow">
                    <i class="fas fa-clipboard-check text-purple-500 text-4xl md:text-5xl mb-2 md:mb-3"></i>
                    <div class="text-2xl md:text-3xl font-bold text-gray-800"><?php echo number_format($total_assess); ?></div>
                    <div class="text-xs md:text-sm text-gray-600">Assessment Centers</div>
                </div>
            </div>

            <!-- Tabs Section -->
            <div class="bg-white/95 backdrop-blur-lg rounded-3xl shadow-2xl overflow-hidden">
                <div class="border-b border-gray-200">
                    <nav class="flex flex-wrap gap-4 px-8 pt-6">
                        <button class="tab-btn active-tab" data-tab="qualifications">
                            <i class="fas fa-list-check mr-2"></i> NC2 Qualifications
                            <span class="tab-count">(<?php echo $total_qual; ?>)</span>
                        </button>
                        <button class="tab-btn" data-tab="training">
                            <i class="fas fa-building mr-2"></i> Training Centers
                            <span class="tab-count">(<?php echo $total_train; ?>)</span>
                        </button>
                        <button class="tab-btn" data-tab="assessment">
                            <i class="fas fa-clipboard-check mr-2"></i> Assessment Centers
                            <span class="tab-count">(<?php echo $total_assess; ?>)</span>
                        </button>
                    </nav>
                </div>

                <div class="p-8">

                    <!-- Qualifications Tab -->
                    <div id="qualifications" class="tab-content">
                        <div class="pdf-controls">
                            <button onclick="downloadQualificationsPDF()" class="pdf-btn">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="tesda-table" id="qualifications-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Qualification</th>
                                        <th>NTR/WTR</th>
                                        <th>CTPR Number</th>
                                        <th>Date Issued</th>
                                        <th>Validity Date</th>
                                        <th>Status</th>
                                        <th>Hours</th>
                                        <th>Days</th>
                                        <th>Training Cost</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($qualifications)): ?>
                                        <tr><td colspan="10" class="text-center py-12 text-gray-500">No qualifications found</td></tr>
                                    <?php else: $no = 1; foreach ($qualifications as $row):
                                        $statusInfo = calculateStatus($row['validity_date']);
                                        $issued = $row['date_issued'] && $row['date_issued'] !== '0000-00-00' ? date('M d, Y', strtotime($row['date_issued'])) : 'N/A';
                                        $hours = htmlspecialchars($row['no_of_hours'] ?? '');
                                        $days_display = htmlspecialchars($row['no_of_days'] ?? '');
                                        $cost = $row['training_cost'] ? '₱' . number_format(str_replace(',', '', $row['training_cost']), 2) : 'N/A';
                                    ?>
                                        <tr>
                                            <td class="text-center font-medium"><?php echo $no++; ?></td>
                                            <td class="font-semibold"><?php echo htmlspecialchars($row['qualification']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ntr_wtr']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ctpr_number']); ?></td>
                                            <td><?php echo $issued; ?></td>
                                            <td><?php echo $statusInfo['formatted']; ?></td>
                                            <td><span class="badge <?php echo $statusInfo['badge']; ?>"><?php echo $statusInfo['status']; ?></span></td>
                                            <td class="text-center"><?php echo $hours; ?></td>
                                            <td class="text-center"><?php echo $days_display; ?></td>
                                            <td class="text-right font-medium" data-cost="<?php echo $row['training_cost'] ? str_replace(',', '', $row['training_cost']) : '0'; ?>"><?php echo $cost; ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Training Centers Tab -->
                    <div id="training" class="tab-content hidden">
                        <div class="pdf-controls">
                            <button onclick="downloadTrainingCentersPDF()" class="pdf-btn">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="tesda-table" id="training-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Center Name</th>
                                        <th>Accreditation No.</th>
                                        <th>Date Accredited</th>
                                        <th>Validity Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($training_centers)): ?>
                                        <tr><td colspan="7" class="text-center py-12 text-gray-500">No training centers found</td></tr>
                                    <?php else: $no = 1; foreach ($training_centers as $row):
                                        $statusInfo = calculateStatus($row['validity_date']);
                                        $accredited = $row['date_accredited'] && $row['date_accredited'] !== '0000-00-00' ? date('M d, Y', strtotime($row['date_accredited'])) : 'N/A';
                                    ?>
                                        <tr>
                                            <td class="text-center font-medium"><?php echo $no++; ?></td>
                                            <td class="font-semibold"><?php echo htmlspecialchars($row['center_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['accreditation_number']); ?></td>
                                            <td><?php echo $accredited; ?></td>
                                            <td><?php echo $statusInfo['formatted']; ?></td>
                                            <td><span class="badge <?php echo $statusInfo['badge']; ?>"><?php echo $statusInfo['status']; ?></span></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Assessment Centers Tab -->
                    <div id="assessment" class="tab-content hidden">
                        <div class="pdf-controls">
                            <button onclick="downloadAssessmentCentersPDF()" class="pdf-btn">
                                <i class="fas fa-file-pdf"></i> Download PDF
                            </button>
                        </div>
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="tesda-table" id="assessment-table">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Center Name</th>
                                        <th>Qualifications</th>
                                        <th>Accreditation No.</th>
                                        <th>Date Accredited</th>
                                        <th>Validity Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($assessment_centers)): ?>
                                        <tr><td colspan="8" class="text-center py-12 text-gray-500">No assessment centers found</td></tr>
                                    <?php else: $no = 1; foreach ($assessment_centers as $row):
                                        $statusInfo = calculateStatus($row['validity_date']);
                                        $accredited = $row['date_accredited'] && $row['date_accredited'] !== '0000-00-00' ? date('M d, Y', strtotime($row['date_accredited'])) : 'N/A';
                                    ?>
                                        <tr>
                                            <td class="text-center font-medium"><?php echo $no++; ?></td>
                                            <td class="font-semibold"><?php echo htmlspecialchars($row['center_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['qualifications']); ?></td>
                                            <td><?php echo htmlspecialchars($row['accreditation_number']); ?></td>
                                            <td><?php echo $accredited; ?></td>
                                            <td><?php echo $statusInfo['formatted']; ?></td>
                                            <td><span class="badge <?php echo $statusInfo['badge']; ?>"><?php echo $statusInfo['status']; ?></span></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Download All Button -->
                    <div class="mt-6 text-center">
                        <button onclick="downloadAllPDF()" class="pdf-btn text-sm px-4 py-2 rounded-lg">
                            <i class="fas fa-download"></i> Download All Reports (Combined PDF)
                        </button>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-12 text-gray-600">
                © 2025 Doc Vic – JIMZ All rights reserved.
            </div>
        </div>
    </div>

    <script>
        const { jsPDF } = window.jspdf;

        // Tab switching
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active-tab'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.add('hidden'));
                btn.classList.add('active-tab');
                document.getElementById(btn.dataset.tab).classList.remove('hidden');
            });
        });

        // Helper function to clean cost text
        function cleanCostText(text) {
            // Remove all currency symbols and keep only numbers, commas, and periods
            return text.replace(/[^\d,\.]/g, '').trim();
        }

        // PDF Generation Functions
        function downloadQualificationsPDF() {
            const doc = new jsPDF('l', 'mm', 'a4');
            
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.text('Sibugay Technical Institute Incorporated', doc.internal.pageSize.width / 2, 15, { align: 'center' });
            
            doc.setFontSize(14);
            doc.text('TESDA NC2 Qualifications Report', doc.internal.pageSize.width / 2, 23, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('Generated: ' + new Date().toLocaleString(), doc.internal.pageSize.width / 2, 30, { align: 'center' });

            const table = document.getElementById('qualifications-table');
            const rows = [];
            const tbody = table.querySelector('tbody');
            
            tbody.querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach((td, index) => {
                        if (index === 9) { // Cost column
                            // Get the raw cost value from data attribute
                            const costValue = td.getAttribute('data-cost');
                            if (costValue && costValue !== '0') {
                                row.push('P' + parseFloat(costValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                            } else {
                                row.push('N/A');
                            }
                        } else {
                            const badge = td.querySelector('.badge');
                            row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                        }
                    });
                    rows.push(row);
                }
            });

            doc.autoTable({
                startY: 35,
                head: [['#', 'Qualification', 'NTR/WTR', 'CTPR Number', 'Date Issued', 'Validity Date', 'Status', 'Hours', 'Days', 'Training Cost']],
                body: rows,
                theme: 'grid',
                headStyles: { 
                    fillColor: [16, 185, 129],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                styles: {
                    fontSize: 8,
                    cellPadding: 3,
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { cellWidth: 50 },
                    2: { cellWidth: 25 },
                    3: { cellWidth: 30 },
                    4: { cellWidth: 25 },
                    5: { cellWidth: 25 },
                    6: { halign: 'center', cellWidth: 25 },
                    7: { halign: 'center', cellWidth: 15 },
                    8: { halign: 'center', cellWidth: 15 },
                    9: { halign: 'right', cellWidth: 30 }
                },
                didParseCell: function(data) {
                    if (data.column.index === 6 && data.section === 'body') {
                        const status = data.cell.raw;
                        if (status === 'Valid') {
                            data.cell.styles.fillColor = [209, 250, 229];
                            data.cell.styles.textColor = [6, 95, 70];
                        } else if (status === 'Expiring Soon') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                        } else if (status === 'Expired') {
                            data.cell.styles.fillColor = [254, 226, 226];
                            data.cell.styles.textColor = [153, 27, 27];
                        }
                    }
                }
            });

            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
            }

            doc.save('TESDA_NC2_Qualifications_' + new Date().toISOString().split('T')[0] + '.pdf');
        }

        function downloadTrainingCentersPDF() {
            const doc = new jsPDF('l', 'mm', 'a4');
            
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.text('Sibugay Technical Institute Incorporated', doc.internal.pageSize.width / 2, 15, { align: 'center' });
            
            doc.setFontSize(14);
            doc.text('TESDA Training Centers Report', doc.internal.pageSize.width / 2, 23, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('Generated: ' + new Date().toLocaleString(), doc.internal.pageSize.width / 2, 30, { align: 'center' });

            const table = document.getElementById('training-table');
            const rows = [];
            const tbody = table.querySelector('tbody');
            
            tbody.querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach(td => {
                        const badge = td.querySelector('.badge');
                        row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                    });
                    rows.push(row);
                }
            });

            doc.autoTable({
                startY: 35,
                head: [['#', 'Center Name', 'Accreditation No.', 'Date Accredited', 'Validity Date', 'Status']],
                body: rows,
                theme: 'grid',
                headStyles: { 
                    fillColor: [59, 130, 246],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4,
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { cellWidth: 70 },
                    2: { cellWidth: 80 },
                    3: { cellWidth: 40 },
                    4: { cellWidth: 30 },
                    5: { cellWidth: 30 },
                    6: { halign: 'center', cellWidth: 30 }
                },
                didParseCell: function(data) {
                    if (data.column.index === 6 && data.section === 'body') {
                        const status = data.cell.raw;
                        if (status === 'Valid') {
                            data.cell.styles.fillColor = [209, 250, 229];
                            data.cell.styles.textColor = [6, 95, 70];
                        } else if (status === 'Expiring Soon') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                        } else if (status === 'Expired') {
                            data.cell.styles.fillColor = [254, 226, 226];
                            data.cell.styles.textColor = [153, 27, 27];
                        }
                    }
                }
            });

            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
            }

            doc.save('TESDA_Training_Centers_' + new Date().toISOString().split('T')[0] + '.pdf');
        }

        function downloadAssessmentCentersPDF() {
            const doc = new jsPDF('l', 'mm', 'a4');
            
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.text('Sibugay Technical Institute Incorporated', doc.internal.pageSize.width / 2, 15, { align: 'center' });
            
            doc.setFontSize(14);
            doc.text('TESDA Assessment Centers Report', doc.internal.pageSize.width / 2, 23, { align: 'center' });
            
            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.text('Generated: ' + new Date().toLocaleString(), doc.internal.pageSize.width / 2, 30, { align: 'center' });

            const table = document.getElementById('assessment-table');
            const rows = [];
            const tbody = table.querySelector('tbody');
            
            tbody.querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach(td => {
                        const badge = td.querySelector('.badge');
                        row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                    });
                    rows.push(row);
                }
            });

            doc.autoTable({
                startY: 35,
                head: [['#', 'Center Name', 'Qualifications', 'Accreditation No.', 'Date Accredited', 'Validity Date', 'Status']],
                body: rows,
                theme: 'grid',
                headStyles: { 
                    fillColor: [139, 92, 246],
                    textColor: 255,
                    fontStyle: 'bold',
                    halign: 'center'
                },
                styles: {
                    fontSize: 9,
                    cellPadding: 4,
                },
                columnStyles: {
                    0: { halign: 'center', cellWidth: 10 },
                    1: { cellWidth: 50 },
                    2: { cellWidth: 50 },
                    3: { cellWidth: 70 },
                    4: { cellWidth: 35 },
                    5: { cellWidth: 25 },
                    6: { cellWidth: 25 },
                    7: { halign: 'center', cellWidth: 25 }
                },
                didParseCell: function(data) {
                    if (data.column.index === 7 && data.section === 'body') {
                        const status = data.cell.raw;
                        if (status === 'Valid') {
                            data.cell.styles.fillColor = [209, 250, 229];
                            data.cell.styles.textColor = [6, 95, 70];
                        } else if (status === 'Expiring Soon') {
                            data.cell.styles.fillColor = [254, 243, 199];
                            data.cell.styles.textColor = [146, 64, 14];
                        } else if (status === 'Expired') {
                            data.cell.styles.fillColor = [254, 226, 226];
                            data.cell.styles.textColor = [153, 27, 27];
                        }
                    }
                }
            });

            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
            }

            doc.save('TESDA_Assessment_Centers_' + new Date().toISOString().split('T')[0] + '.pdf');
        }

        function downloadAllPDF() {
            const doc = new jsPDF('l', 'mm', 'a4');
            let currentY = 15;

            // Cover Page
            doc.setFontSize(22);
            doc.setFont(undefined, 'bold');
            doc.text('Sibugay Technical Institute Incorporated', doc.internal.pageSize.width / 2, 80, { align: 'center' });
            
            doc.setFontSize(18);
            doc.text('TESDA Comprehensive Report', doc.internal.pageSize.width / 2, 100, { align: 'center' });
            
            doc.setFontSize(12);
            doc.setFont(undefined, 'normal');
            doc.text('Generated: ' + new Date().toLocaleString(), doc.internal.pageSize.width / 2, 120, { align: 'center' });
            
            // Summary Statistics
            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.text('Summary Statistics', doc.internal.pageSize.width / 2, 140, { align: 'center' });
            doc.setFontSize(11);
            doc.setFont(undefined, 'normal');
            doc.text('NC2 Qualifications: <?php echo $total_qual; ?>', doc.internal.pageSize.width / 2, 150, { align: 'center' });
            doc.text('Training Centers: <?php echo $total_train; ?>', doc.internal.pageSize.width / 2, 158, { align: 'center' });
            doc.text('Assessment Centers: <?php echo $total_assess; ?>', doc.internal.pageSize.width / 2, 166, { align: 'center' });

            // Section 1: NC2 Qualifications
            doc.addPage();
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('NC2 Qualifications Report', 14, 15);
            
            const qualTable = document.getElementById('qualifications-table');
            const qualRows = [];
            qualTable.querySelector('tbody').querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach((td, index) => {
                        if (index === 9) { // Cost column
                            const costValue = td.getAttribute('data-cost');
                            if (costValue && costValue !== '0') {
                                row.push('P' + parseFloat(costValue).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ','));
                            } else {
                                row.push('N/A');
                            }
                        } else {
                            const badge = td.querySelector('.badge');
                            row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                        }
                    });
                    qualRows.push(row);
                }
            });

            doc.autoTable({
                startY: 20,
                head: [['#', 'Qualification', 'NTR/WTR', 'CTPR', 'Issued', 'Validity', 'Status', 'Hrs', 'Days', 'Cost']],
                body: qualRows,
                theme: 'grid',
                headStyles: { fillColor: [16, 185, 129], textColor: 255, fontStyle: 'bold' },
                styles: { fontSize: 7, cellPadding: 2 },
                columnStyles: {
                    0: { cellWidth: 8 },
                    1: { cellWidth: 45 },
                    2: { cellWidth: 22 },
                    3: { cellWidth: 25 },
                    4: { cellWidth: 22 },
                    5: { cellWidth: 22 },
                    6: { cellWidth: 22 },
                    7: { cellWidth: 12 },
                    8: { cellWidth: 12 },
                    9: { cellWidth: 28 }
                }
            });

            // Section 2: Training Centers
            doc.addPage();
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('Training Centers Report', 14, 15);
            
            const trainTable = document.getElementById('training-table');
            const trainRows = [];
            trainTable.querySelector('tbody').querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach(td => {
                        const badge = td.querySelector('.badge');
                        row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                    });
                    trainRows.push(row);
                }
            });

            doc.autoTable({
                startY: 20,
                head: [['#', 'Center Name', 'Address', 'Accreditation No.', 'Date Accredited', 'Validity Date', 'Status']],
                body: trainRows,
                theme: 'grid',
                headStyles: { fillColor: [59, 130, 246], textColor: 255, fontStyle: 'bold' },
                styles: { fontSize: 8, cellPadding: 3 },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 60 },
                    2: { cellWidth: 75 },
                    3: { cellWidth: 35 },
                    4: { cellWidth: 28 },
                    5: { cellWidth: 28 },
                    6: { cellWidth: 24 }
                }
            });

            // Section 3: Assessment Centers
            doc.addPage();
            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.text('Assessment Centers Report', 14, 15);
            
            const assessTable = document.getElementById('assessment-table');
            const assessRows = [];
            assessTable.querySelector('tbody').querySelectorAll('tr').forEach(tr => {
                const cols = tr.querySelectorAll('td');
                if (cols.length > 0 && !cols[0].hasAttribute('colspan')) {
                    const row = [];
                    cols.forEach(td => {
                        const badge = td.querySelector('.badge');
                        row.push(badge ? badge.textContent.trim() : td.textContent.trim());
                    });
                    assessRows.push(row);
                }
            });

            doc.autoTable({
                startY: 20,
                head: [['#', 'Center Name', 'Qualifications', 'Address', 'Accreditation No.', 'Date Accredited', 'Validity Date', 'Status']],
                body: assessRows,
                theme: 'grid',
                headStyles: { fillColor: [139, 92, 246], textColor: 255, fontStyle: 'bold' },
                styles: { fontSize: 8, cellPadding: 3 },
                columnStyles: {
                    0: { cellWidth: 10 },
                    1: { cellWidth: 50 },
                    2: { cellWidth: 50 },
                    3: { cellWidth: 70 },
                    4: { cellWidth: 30 },
                    5: { cellWidth: 25 },
                    6: { cellWidth: 25 },
                    7: { cellWidth: 22 }
                }
            });

            const pageCount = doc.internal.getNumberOfPages();
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.text('Page ' + i + ' of ' + pageCount, doc.internal.pageSize.width / 2, doc.internal.pageSize.height - 10, { align: 'center' });
            }

            doc.save('TESDA_Complete_Report_' + new Date().toISOString().split('T')[0] + '.pdf');
        }

        // Particles
        const particles = document.getElementById('particles');
        for (let i = 0; i < 35; i++) {
            let p = document.createElement('div');
            p.style.position = 'absolute';
            p.style.width = p.style.height = Math.random() * 8 + 4 + 'px';
            p.style.background = 'rgba(16, 185, 129, 0.35)';
            p.style.borderRadius = '50%';
            p.style.left = Math.random() * 100 + '%';
            p.style.animation = `float ${Math.random() * 15 + 10}s linear infinite`;
            particles.appendChild(p);
        }
        
        // Add floating animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0%, 100% { transform: translateY(0) rotate(0deg); }
                50% { transform: translateY(-20px) rotate(180deg); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
<script>
    const widgetId = turnstile.render("#turnstile-container", {
      sitekey: "0x4AAAAAAB7_MDnjKW6-izVL",
      callback: function (token) {
        console.log("Success:", token);
      },
    });
</script>
</html>

<?php $conn->close(); ?>