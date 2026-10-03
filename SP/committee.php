<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];

// Committee structure data
$committees = [
    [
        'name' => 'HEALTH',
        'chairman' => ['name' => 'HON. RALPH H. DE LOS SANTOS, MD', 'image' => 'SP_image/CSACSA.jpg'],
        'vice_chairman' => ['name' => 'HON. EUFEMIO D. JAVIER, JR.', 'image' => 'SP_IMAGE/DAS.jpg'],
        'member' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg']
    ],
    [
        'name' => 'HUMAN RIGHTS, LABOR, JUSTICE AND EMPLOYMENT',
        'chairman' => ['name' => 'ATTY. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg'],
        'vice_chairman' => ['name' => 'JUDGE GLENN C. SABIJON', 'image' => 'SP_IMAGE/JUDGE.jpg'],
        'member' => ['name' => 'HON. PEPITO S. YANGA, JR.', 'image' => 'SP_IMAGE/ssss.jpg']
    ],
    [
        'name' => 'INFORMATION COMMUNICATION TECHNOLOGY (ICT), RURAL IMPACT SOURCING (RIS) & COMPUTERIZATION',
        'chairman' => ['name' => 'HON. EUFEMIO D. JAVIER, JR.', 'image' => 'SP_IMAGE/DAS.jpg'],
        'vice_chairman' => ['name' => 'HON. JONATHAN C. YAMBAO', 'image' => 'SP_IMAGE/DD.jpg'],
        'member' => ['name' => 'HON. NATH ANTHONY R. EUDELA', 'image' => 'SP_IMAGE/SCSAC.jpg']
    ],
    [
        'name' => 'INFRASTRUCTURE, PUBLIC WORKS, ENGINEERING & ASSET AND MANAGEMENT',
        'chairman' => ['name' => 'HON. GLENN C. SABIJON', 'image' => 'SP_IMAGE/JUDGE.jpg'],
        'vice_chairman' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg'],
        'member' => ['name' => 'HON. ROGER P. LU', 'image' => 'SP_IMAGE/CSACSAC.jpg']
    ],
    [
        'name' => 'INTERNATIONAL RELATIONS & EXTERNAL AFFAIRS',
        'chairman' => ['name' => 'HON. RALPH H. DE LOS SANTOS, MD', 'image' => 'SP_image/CSACSA.jpg'],
        'vice_chairman' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg'],
        'member' => ['name' => 'HON. PEPITO S. YANGA, JR.', 'image' => 'SP_IMAGE/ssss.jpg']
    ],
    [
        'name' => 'LAND USE, HOUSING, RURAL & URBAN DEVELOPMENT',
        'chairman' => ['name' => 'HON. JONATHAN C. YAMBAO', 'image' => 'SP_IMAGE/DD.jpg'],
        'vice_chairman' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg'],
        'member' => ['name' => 'HON. ROGER P. LU', 'image' => 'SP_IMAGE/CSACSAC.jpg']
    ],
    [
        'name' => 'LEGISLATIVE TRACKING AND ANALYSIS',
        'chairman' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg'],
        'vice_chairman' => ['name' => 'HON. JONATHAN C. YAMBAO', 'image' => 'SP_IMAGE/DD.jpg'],
        'member' => ['name' => '', 'image' => '']
    ],
    [
        'name' => 'MINING AND OTHER RESOURCES',
        'chairman' => ['name' => 'ATTY. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg'],
        'vice_chairman' => ['name' => 'HON. PEPITO S. YANGA, JR.', 'image' => 'SP_IMAGE/ssss.jpg'],
        'member' => ['name' => 'HON. NATH ANTHONY R. EUDELA', 'image' => 'SP_IMAGE/SCSAC.jpg']
    ],
    [
        'name' => 'MUNICIPAL AFFAIRS',
        'chairman' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg'],
        'vice_chairman' => ['name' => 'HON. JUNE VITA F. MENDOZA', 'image' => 'SP_IMAGE/S.jpg'],
        'member' => ['name' => 'HON. ABDULMUKIM B. MUSA', 'image' => 'SP_IMAGE/musa.jpg']
    ],
    [
        'name' => 'MUSLIM, INDIGENOUS PEOPLES & CULTURAL COMMUNITIES',
        'chairman' => ['name' => 'HON. ABDURAUP A. ABISON', 'image' => 'SP_IMAGE/ABISON.jpg'],
        'vice_chairman' => ['name' => 'HON. ABDULMUKIM B. MUSA', 'image' => 'SP_IMAGE/musa.jpg'],
        'member' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg']
    ],
    [
        'name' => 'OVERSIGHT',
        'chairman' => ['name' => 'ATTY. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg'],
        'vice_chairman' => ['name' => 'JUDGE GLENN C. SABIJON', 'image' => 'SP_IMAGE/JUDGE.jpg'],
        'member' => ['name' => 'HON. ABDULMUKIM B. MUSA', 'image' => 'SP_IMAGE/musa.jpg']
    ],
    [
        'name' => 'PEACE AND ORDER, CALAMITIES, FIRE & PUBLIC SAFETY',
        'chairman' => ['name' => 'ATTY. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg'],
        'vice_chairman' => ['name' => 'HON. SMA Hasim', 'image' => 'SP_IMAGE/CCSD.jpg'],
        'member' => ['name' => 'HON. ABDURAUP A. ABISON', 'image' => 'SP_IMAGE/ABISON.jpg']
    ],
    [
        'name' => 'PRIVILEGES',
        'chairman' => ['name' => 'HON. ROGER P. LU', 'image' => 'SP_IMAGE/CSACSAC.jpg'],
        'vice_chairman' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg'],
        'member' => ['name' => 'HON. HERSHEYMAE L. SENARLO', 'image' => 'SP_IMAGE/HERS.jpg']
    ],
    [
        'name' => 'RULES, ORDINANCES & RESOLUTIONS',
        'chairman' => ['name' => 'HON. GLENN C. SABIJON', 'image' => 'SP_IMAGE/JUDGE.jpg'],
        'vice_chairman' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg'],
        'member' => ['name' => 'HON. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg']
    ],
    [
        'name' => 'SENIOR CITIZEN, DIFFERENTLY ABLED PERSONS, WOMEN, CHILDREN, FAMILY & OTHER SOCIAL SERVICES',
        'chairman' => ['name' => 'HON. JUNE VITA F. MENDOZA', 'image' => 'SP_IMAGE/S.jpg'],
        'vice_chairman' => ['name' => '', 'image' => ''],
        'member' => ['name' => '', 'image' => '']
    ],
    [
        'name' => 'STEERING & INTER-AGENCY AFFAIRS',
        'chairman' => ['name' => 'HON. JONATHAN C. YAMBAO', 'image' => 'SP_IMAGE/DD.jpg'],
        'vice_chairman' => ['name' => 'JUDGE GLENN C. SABIJON', 'image' => 'SP_IMAGE/JUDGE.jpg'],
        'member' => ['name' => 'ATTY. ELDWIN M. ALIBUTDAN', 'image' => 'SP_IMAGE/CASC.jpg']
    ],
    [
        'name' => 'TRADE, INDUSTRY, & SPECIAL INVESTMENTS',
        'chairman' => ['name' => 'HON. ROGER P. LU', 'image' => 'SP_IMAGE/CSACSAC.jpg'],
        'vice_chairman' => ['name' => 'HON. JUNE VITA F. MENDOZA', 'image' => 'SP_IMAGE/S.jpg'],
        'member' => ['name' => 'HON. PEPITO S. YANGA, JR.', 'image' => 'SP_IMAGE/ssss.jpg']
    ],
    [
        'name' => 'TOURISM',
        'chairman' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg'],
        'vice_chairman' => ['name' => 'HON. RALPH H. DE LOS SANTOS, MD', 'image' => 'SP_image/CSACSA.jpg'],
        'member' => ['name' => 'HON. JUNE VITA F. MENDOZA', 'image' => 'SP_IMAGE/S.jpg']
    ],
    [
        'name' => 'YOUTH & SPORTS DEVELOPMENT',
        'chairman' => ['name' => 'HON. HERSHEYMAE L. SENARLO', 'image' => 'SP_IMAGE/HERS.jpg'],
        'vice_chairman' => ['name' => 'HON. NATH ANTHONY R. EUDELA', 'image' => 'SP_IMAGE/SCSAC.jpg'],
        'member' => ['name' => 'HON. GEORGE PAVLO S. HOFER', 'image' => 'SP_IMAGE/F.jpg']
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Committee Structure - Sangguniang Panlalawigan</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #1a3a6c;
            --secondary: #e63946;
            --accent: #2a9d8f;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
        }
        
        .sidebar {
            background: linear-gradient(180deg, var(--primary), #1d3557);
        }

        .committee-section {
            margin-bottom: 2rem;
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .committee-header {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 0.75rem 1.5rem;
            font-weight: bold;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .committee-content {
            background: #fffacd;
            padding: 1.5rem;
        }

        .member-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 2rem;
        }

        .member-card {
            text-align: center;
        }

        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            margin: 0 auto 1rem;
        }

        .member-name {
            font-weight: 700;
            color: #1a1a1a;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
        }

        .member-position {
            font-weight: 600;
            color: var(--primary);
            font-size: 0.85rem;
            text-transform: uppercase;
        }

        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-input, .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 0.9rem;
            width: 100%;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 1rem;
        }

        .reset-btn {
            background: var(--secondary);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }

        @media (max-width: 1024px) {
            .member-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .member-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-50">
    <?php include 'bar/sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col overflow-hidden">
        <?php include 'bar/header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <h2 class="text-3xl font-bold text-gray-800 mb-6 text-center">Committee Structure</h2>
            
            <div class="filter-section">
                <div class="filter-grid">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-search mr-2"></i>Search by Name
                        </label>
                        <input type="text" id="searchInput" class="search-input" placeholder="Type member name...">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-filter mr-2"></i>Filter by Committee
                        </label>
                        <select id="committeeFilter" class="filter-select">
                            <option value="">All Committees</option>
                            <?php foreach ($committees as $committee): ?>
                                <option value="<?php echo htmlspecialchars($committee['name']); ?>">
                                    <?php echo htmlspecialchars($committee['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">&nbsp;</label>
                        <button id="resetBtn" class="reset-btn">
                            <i class="fas fa-redo mr-2"></i>Reset
                        </button>
                    </div>
                </div>
            </div>

            <div id="committeeContainer">
                <?php foreach ($committees as $committee): ?>
                    <div class="committee-section" data-committee="<?php echo htmlspecialchars($committee['name']); ?>">
                        <div class="committee-header">
                            <?php echo htmlspecialchars($committee['name']); ?>
                        </div>
                        <div class="committee-content">
                            <div class="member-grid">
                                <?php if (!empty($committee['chairman']['name'])): ?>
                                    <div class="member-card" data-name="<?php echo strtolower($committee['chairman']['name']); ?>">
                                        <img src="<?php echo htmlspecialchars($committee['chairman']['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($committee['chairman']['name']); ?>" 
                                             class="member-photo">
                                        <div class="member-name"><?php echo htmlspecialchars($committee['chairman']['name']); ?></div>
                                        <div class="member-position">Chairman</div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($committee['vice_chairman']['name'])): ?>
                                    <div class="member-card" data-name="<?php echo strtolower($committee['vice_chairman']['name']); ?>">
                                        <img src="<?php echo htmlspecialchars($committee['vice_chairman']['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($committee['vice_chairman']['name']); ?>" 
                                             class="member-photo">
                                        <div class="member-name"><?php echo htmlspecialchars($committee['vice_chairman']['name']); ?></div>
                                        <div class="member-position">Vice-Chairman</div>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($committee['member']['name'])): ?>
                                    <div class="member-card" data-name="<?php echo strtolower($committee['member']['name']); ?>">
                                        <img src="<?php echo htmlspecialchars($committee['member']['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($committee['member']['name']); ?>" 
                                             class="member-photo">
                                        <div class="member-name"><?php echo htmlspecialchars($committee['member']['name']); ?></div>
                                        <div class="member-position">Member</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div id="noResults" style="display: none; text-align: center; padding: 3rem; color: #64748b;">
                <i class="fas fa-search" style="font-size: 3rem; margin-bottom: 1rem; color: #cbd5e1;"></i>
                <h3 style="font-size: 1.25rem; font-weight: 600; color: #475569;">No Results Found</h3>
                <p style="color: #64748b; margin-top: 0.5rem;">Try adjusting your search or filter criteria</p>
            </div>
        </main>
    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const committeeFilter = document.getElementById('committeeFilter');
        const resetBtn = document.getElementById('resetBtn');
        const committeeContainer = document.getElementById('committeeContainer');
        const noResults = document.getElementById('noResults');
        const allSections = document.querySelectorAll('.committee-section');

        function filterCommittees() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const selectedCommittee = committeeFilter.value;
            let visibleCount = 0;

            allSections.forEach(section => {
                const committeeName = section.getAttribute('data-committee');
                let shouldShow = true;

                if (selectedCommittee && committeeName !== selectedCommittee) {
                    shouldShow = false;
                }

                if (searchTerm && shouldShow) {
                    const memberCards = section.querySelectorAll('.member-card');
                    let hasMatch = false;
                    
                    memberCards.forEach(card => {
                        const memberName = card.getAttribute('data-name');
                        if (memberName && memberName.includes(searchTerm)) {
                            hasMatch = true;
                        }
                    });

                    shouldShow = hasMatch;
                }

                if (shouldShow) {
                    section.style.display = 'block';
                    visibleCount++;
                } else {
                    section.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                committeeContainer.style.display = 'none';
                noResults.style.display = 'block';
            } else {
                committeeContainer.style.display = 'block';
                noResults.style.display = 'none';
            }
        }

        function resetFilters() {
            searchInput.value = '';
            committeeFilter.value = '';
            allSections.forEach(section => {
                section.style.display = 'block';
            });
            committeeContainer.style.display = 'block';
            noResults.style.display = 'none';
        }

        searchInput.addEventListener('input', filterCommittees);
        committeeFilter.addEventListener('change', filterCommittees);
        resetBtn.addEventListener('click', resetFilters);
    </script>
</body>
</html>