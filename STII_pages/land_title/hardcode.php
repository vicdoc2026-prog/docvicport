<?php 

// Include session check - this will redirect if not logged in
require_once '../electric/config/check-session.php';
require_once '../electric/config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];


?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Land Lot Development Plan (6 Lots)</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        body { background-color: #f5f7f9; color: #333; line-height: 1.6; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 0 20px rgba(0,0,0,.1); overflow: hidden; }
        header { background: linear-gradient(135deg, #2c3e50, #4a6491); color: white; padding: 25px; text-align: center; }
        h1 { font-size: 2.2rem; margin-bottom: 10px; }
        .subtitle { font-size: 1.05rem; opacity: .9; }
        .content { display: flex; flex-wrap: wrap; min-height: 560px; }
        .map-container { flex: 1; min-width: 360px; background: #2c3e50; padding: 20px; display: flex; align-items: center; justify-content: center; }
        .map { width: 100%; height: 460px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,.2); }
        .lot { cursor: pointer; transition: opacity .25s; }
        .lot:hover { opacity: 0.9; }
        .lot.selected polygon { stroke: #e67e22; stroke-width: 4; }
        .details-container { flex: 1; min-width: 360px; padding: 25px; background: #f8f9fa; }
        .details-container h2 { color: #2c3e50; margin-bottom: 18px; padding-bottom: 10px; border-bottom: 2px solid #e67e22; }
        .lot-details { display: grid; gap: 14px; }
        .lot-item { background: white; border-left: 4px solid #3498db; padding: 14px; border-radius: 0 6px 6px 0; box-shadow: 0 2px 5px rgba(0,0,0,.05); }
        .lot-item h3 { color: #2c3e50; margin-bottom: 6px; }
        .specs { display: flex; gap: 8px; flex-wrap: wrap; color: #596a74; font-size: .92rem; }
        .specs span { background: #f1f4f7; padding: 4px 8px; border-radius: 999px; }
        .note { font-size: .88rem; color: #6b7c86; margin-top: 8px; }
        .footer { background: #2c3e50; color: white; text-align: center; padding: 18px; font-size: .92rem; }
        @media (max-width: 768px) { .content { flex-direction: column; } .map-container { min-height: 300px; } }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Land Lot Development Plan</h1>
            <p class="subtitle">Whole lot divided into 6 sub-lots · Lot 1 includes provided center bearings</p>
        </header>

        <div class="content">
            <div class="map-container">
                <svg class="map" viewBox="0 0 250 410">
                    <!-- Highway label -->
                    <text x="100" y="35" text-anchor="middle" fill="white" font-weight="700" font-size="20" letter-spacing="0.5">HIGHWAY</text>

                    <!-- Lots -->
                    <g class="lot large" data-lot="1">
                        <polygon points="0,365 82,379 59,200 8,200" fill="#9b59b6" />
                        <text x="37" y="280" text-anchor="middle" fill="white" font-size="14">LOT 1<tspan x="37" dy="15">Click for details</tspan></text>
                    </g>
                    <g class="lot medium" data-lot="2">
                        <polygon points="82,379 153,392 131,200 59,200" fill="#2ecc71" />
                        <text x="106" y="280" text-anchor="middle" fill="white" font-size="14">LOT 2<tspan x="106" dy="15">Click for details</tspan></text>
                    </g>
                    <g class="lot small" data-lot="3">
                        <polygon points="153,392 205,401 214,200 131,200" fill="#3498db" />
                        <text x="176" y="280" text-anchor="middle" fill="white" font-size="14">LOT 3<tspan x="176" dy="15">Click for details</tspan></text>
                    </g>
                    <g class="lot small" data-lot="4">
                        <polygon points="8,200 59,200 68,8 17,0" fill="#3498db" />
                        <text x="38" y="100" text-anchor="middle" fill="white" font-size="14">LOT 4<tspan x="38" dy="15">Click for details</tspan></text>
                    </g>
                    <g class="lot medium" data-lot="5">
                        <polygon points="59,200 131,200 139,20 68,8" fill="#2ecc71" />
                        <text x="99" y="100" text-anchor="middle" fill="white" font-size="14">LOT 5<tspan x="99" dy="15">Click for details</tspan></text>
                    </g>
                    <g class="lot large" data-lot="6">
                        <polygon points="131,200 214,200 221,33 139,20" fill="#9b59b6" />
                        <text x="176" y="100" text-anchor="middle" fill="white" font-size="14">LOT 6<tspan x="176" dy="15">Click for details</tspan></text>
                    </g>

                    <!-- Center point for Lot 1 -->
                    <circle cx="37" cy="280" r="4" fill="#e74c3c" />
                    <circle cx="37" cy="280" r="10" fill="none" stroke="#e74c3c" stroke-dasharray="4 4" stroke-width="2"/>
                    <line x1="37" y1="260" x2="37" y2="300" stroke="#e74c3c" stroke-width="1"/>
                    <line x1="17" y1="280" x2="57" y2="280" stroke="#e74c3c" stroke-width="1"/>

                    <!-- Bearings label bubble -->
                    <g id="bearingLabel" transform="translate(60, 240)">
                        <rect x="0" y="0" rx="6" ry="6" width="220" height="140" fill="rgba(255,255,255,0.95)" stroke="#e67e22" stroke-width="3" />
                        <text x="20" y="45" font-size="16" fill="#2c3e50">Lot 1 Center</text>
                        <text x="20" y="75" font-size="14" fill="#2c3e50">S.02'30"E., 36.83M</text>
                        <text x="20" y="105" font-size="14" fill="#2c3e50">S.86'38"W. / N.3'05"W., 37.78M / E.12'68"N</text>
                    </g>

                    <!-- Boundary labels (approximated positions and rotations) -->
                    <!-- Top -->
                    <text x="100" y="390" fill="black" font-size="12" transform="rotate(9 100 390)">N.80°21'E., 20.74M</text>
                    <!-- Right -->
                    <text x="220" y="200" fill="black" font-size="12" transform="rotate(90 220 200)">S.02°30'E., 36.83M</text>
                    <!-- Bottom -->
                    <text x="110" y="25" fill="black" font-size="12" transform="rotate(-9 110 25)">S.80°53'W., 20.59M</text>
                    <!-- Left -->
                    <text x="5" y="200" fill="black" font-size="12" transform="rotate(-90 5 200)">N.3°05'W., 37.78M</text>

                    <!-- Road label -->
                    <text x="110" y="55" text-anchor="middle" fill="white" font-weight="700" font-size="20" letter-spacing="0.5" transform="rotate(-9 110 55)">ROAD</text>

                    <!-- Main lot label -->
                    <text x="110" y="200" text-anchor="middle" fill="white" font-size="16">LOT 4077</text>
                    <text x="110" y="220" text-anchor="middle" fill="white" font-size="14">PLS-249</text>
                    <text x="110" y="240" text-anchor="middle" fill="white" font-size="14">AREA = 755.5 SQM</text>
                </svg>
            </div>

            <div class="details-container">
                <h2>Lot Details</h2>

                <div class="lot-details" id="lotDetails">
                    <div class="lot-item">
                        <h3 id="lotTitle">LOT 1 (Center bearings provided)</h3>
                        <p id="lotDesc">Center bearings as provided for Lot 1.</p>
                        <div class="specs" id="lotSpecs">
                            <span>S.02'30"E., 36.83M</span>
                            <span>S.86'38"W.</span>
                            <span>N.3'05"W., 37.78M</span>
                            <span>E.12'68"N</span>
                        </div>
                        <p class="note">Note: Text above reflects the exact bearings as given. If you want, I can normalize format to standard survey notation (e.g., <em>S 02°30' E</em>).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>Land Development Plan © 2025 | 6-lot subdivision | Visual only (not to scale)</p>
        </div>
    </div>

    <script>
        // Simple interactivity: click a lot to update the detail panel
        const lots = document.querySelectorAll('.lot');
        const lotTitle = document.getElementById('lotTitle');
        const lotDesc = document.getElementById('lotDesc');
        const lotSpecs = document.getElementById('lotSpecs');
        const bearingLabel = document.getElementById('bearingLabel');

        const lotData = {
            1: {
                title: 'LOT 1 (Center bearings provided)',
                desc: 'Center bearings as provided for Lot 1.',
                specs: ["S.02'30\"E., 36.83M", "S.86'38\"W.", "N.3'05\"W., 37.78M", "E.12'68\"N"],
                showBearing: true,
            },
            2: { title: 'LOT 2', desc: 'No bearings provided yet.', specs: ['—'], showBearing: false },
            3: { title: 'LOT 3', desc: 'No bearings provided yet.', specs: ['—'], showBearing: false },
            4: { title: 'LOT 4', desc: 'No bearings provided yet.', specs: ['—'], showBearing: false },
            5: { title: 'LOT 5', desc: 'No bearings provided yet.', specs: ['—'], showBearing: false },
            6: { title: 'LOT 6', desc: 'No bearings provided yet.', specs: ['—'], showBearing: false },
        };

        function selectLot(n) {
            lots.forEach(el => el.classList.toggle('selected', el.dataset.lot === String(n)));
            const d = lotData[n];
            lotTitle.textContent = d.title;
            lotDesc.textContent = d.desc;
            lotSpecs.innerHTML = d.specs.map(s => `<span>${s}</span>`).join('');
            bearingLabel.style.display = d.showBearing ? 'block' : 'none';
        }

        lots.forEach(el => {
            el.addEventListener('click', () => selectLot(Number(el.dataset.lot)));
        });

        // default selection
        selectLot(1);
    </script>
</body>
</html>