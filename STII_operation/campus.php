<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>STII Campus Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --light: #ecf0f1;
            --dark: #2c3e50;
            --success: #27ae60;
            --warning: #f39c12;
        }

        body {
            background-color: #f5f7fa;
            color: var(--dark);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Navbar Styles */
        .navbar {
            background: linear-gradient(135deg, var(--primary), #1a2530);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            height: 70px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo-icon {
            font-size: 28px;
            color: var(--secondary);
        }

        .logo-text {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .logo span {
            color: var(--secondary);
        }

        .nav-links {
            display: flex;
            gap: 25px;
            list-style: none;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            font-weight: 500;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .nav-links a.active {
            background-color: var(--secondary);
            color: white;
        }

        .user-section {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .notification {
            position: relative;
            cursor: pointer;
        }

        .notification i {
            font-size: 1.4rem;
        }

        .notification-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background-color: var(--accent);
            color: white;
            font-size: 0.7rem;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: var(--secondary);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            color: white;
        }

        /* Main Content */
        .container {
            padding: 100px 30px 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .dashboard-title {
            font-size: 2.2rem;
            color: var(--primary);
        }

        .date-display {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        /* Cards Grid */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--primary);
        }

        .card-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 1.5rem;
        }

        .icon-main {
            background-color: rgba(52, 152, 219, 0.2);
            color: var(--secondary);
        }

        .icon-sanito {
            background-color: rgba(46, 204, 113, 0.2);
            color: #2ecc71;
        }

        .icon-farm {
            background-color: rgba(155, 89, 182, 0.2);
            color: #9b59b6;
        }

        .icon-bus {
            background-color: rgba(241, 196, 15, 0.2);
            color: #f1c40f;
        }

        .card-content {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 15px;
        }

        .card-footer {
            color: #7f8c8d;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .positive {
            color: var(--success);
        }

        .negative {
            color: var(--accent);
        }

        /* Bus Tracking Section */
        .section-title {
            font-size: 1.8rem;
            color: var(--primary);
            margin: 40px 0 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #eee;
        }

        .bus-container {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .bus-map {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
            height: 400px;
            position: relative;
            overflow: hidden;
        }

        .map-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: var(--primary);
        }

        .map-content {
            position: relative;
            height: 300px;
            background: #f9f9f9;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #eee;
        }

        .map-route {
            position: relative;
            height: 100%;
            background-image: linear-gradient(to right, #d5f3ff, #e8f7ff);
        }

        .route-line {
            position: absolute;
            top: 50%;
            left: 10%;
            width: 80%;
            height: 4px;
            background: var(--secondary);
            border-radius: 2px;
        }

        .stops {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 10%;
        }

        .stop {
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: var(--secondary);
            position: relative;
        }

        .stop-label {
            position: absolute;
            top: 25px;
            left: 50%;
            transform: translateX(-50%);
            white-space: nowrap;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .stop-main .stop-label {
            top: -30px;
        }

        .bus-icon {
            position: absolute;
            top: 50%;
            left: 35%;
            transform: translate(-50%, -50%);
            font-size: 2.5rem;
            color: var(--accent);
            animation: moveBus 15s linear infinite;
        }

        @keyframes moveBus {
            0% { left: 10%; }
            25% { left: 35%; }
            50% { left: 65%; }
            75% { left: 35%; }
            100% { left: 10%; }
        }

        .bus-info {
            flex: 1;
            min-width: 300px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }

        .bus-details {
            margin-bottom: 30px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .detail-label {
            color: #7f8c8d;
            font-weight: 500;
        }

        .detail-value {
            font-weight: 600;
        }

        .bus-driver {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-top: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }

        .driver-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--secondary);
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .driver-info {
            flex: 1;
        }

        .driver-name {
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 5px;
        }

        .driver-contact {
            color: #7f8c8d;
            font-size: 0.95rem;
        }


        @media (max-width: 768px) {
            .navbar {
                flex-direction: column;
                height: auto;
                padding: 15px;
            }
            
            .nav-links {
                margin: 15px 0;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .user-section {
                margin-top: 10px;
            }
            
            .container {
                padding-top: 160px;
            }
            
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .card-content {
                font-size: 2rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <div class="logo">
            <div class="logo-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="logo-text">STII <span>Campus</span></div>
        </div>
        
        <ul class="nav-links">
            <li><a href="#" class="active">Dashboard</a></li>
            <li><a href="#">Main Campus</a></li>
            <li><a href="#">Sanito HS</a></li>
            <li><a href="#">Farm Campus</a></li>
            <li><a href="#">Transport Bus</a></li>
            <li><a href="#">Calendar</a></li>
            <li><a href="#">Reports</a></li>
        </ul>
        
        <div class="user-section">
            <div class="notification">
                <i class="fas fa-bell"></i>
                <span class="notification-badge">3</span>
            </div>
            <div class="user-profile">
                <div class="user-avatar">JD</div>
                <div>John Doe</div>
            </div>
        </div>
    </nav>


    <div class="container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Campus Dashboard</h1>
            <div class="date-display">
                <i class="fas fa-calendar-alt"></i> 
                <span id="current-date">August 15, 2025</span>
            </div>

        <div class="cards-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Main Campus</h2>
                    <div class="card-icon icon-main">
                        <i class="fas fa-university"></i>
                    </div>
                </div>
                <div class="card-content">1,842</div>
                <div class="card-footer">
                    <i class="fas fa-users"></i> 
                    <span>Total Students</span>
                    <span class="positive"><i class="fas fa-arrow-up"></i> 5.2%</span>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Sanito HS</h2>
                    <div class="card-icon icon-sanito">
                        <i class="fas fa-school"></i>
                    </div>
                </div>
                <div class="card-content">726</div>
                <div class="card-footer">
                    <i class="fas fa-users"></i> 
                    <span>Total Students</span>
                    <span class="positive"><i class="fas fa-arrow-up"></i> 2.3%</span>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Farm Campus</h2>
                    <div class="card-icon icon-farm">
                        <i class="fas fa-tractor"></i>
                    </div>
                </div>
                <div class="card-content">315</div>
                <div class="card-footer">
                    <i class="fas fa-users"></i> 
                    <span>Total Students</span>
                    <span class="positive"><i class="fas fa-arrow-up"></i> 7.8%</span>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">Transport Buses</h2>
                    <div class="card-icon icon-bus">
                        <i class="fas fa-bus"></i>
                    </div>
                </div>
                <div class="card-content">8</div>
                <div class="card-footer">
                    <i class="fas fa-road"></i> 
                    <span>Active Routes</span>
                    <span class="negative"><i class="fas fa-exclamation-circle"></i> 1 delayed</span>
                </div>
            </div>
        </div>


        <h2 class="section-title">Transport Bus Tracking</h2>
        
        <div class="bus-container">
            <div class="bus-map">
                <h3 class="map-title">Route 4: Main Campus to Farm</h3>
                <div class="map-content">
                    <div class="map-route">
                        <div class="route-line"></div>
                        <div class="stops">
                            <div class="stop stop-main">
                                <span class="stop-label">Main Campus</span>
                            </div>
                            <div class="stop">
                                <span class="stop-label">Sanito Junction</span>
                            </div>
                            <div class="stop">
                                <span class="stop-label">Sanito HS</span>
                            </div>
                            <div class="stop stop-farm">
                                <span class="stop-label">Farm Campus</span>
                            </div>
                        </div>
                        <div class="bus-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bus-info">
                <h3>Bus Details</h3>
                <div class="bus-details">
                    <div class="detail-item">
                        <span class="detail-label">Bus Number:</span>
                        <span class="detail-value">STII-B04</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Route:</span>
                        <span class="detail-value">Main → Sanito HS → Farm</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value" style="color: var(--warning);">In Transit</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Current Location:</span>
                        <span class="detail-value">Near Sanito Junction</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Next Stop:</span>
                        <span class="detail-value">Sanito High School</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Estimated Arrival:</span>
                        <span class="detail-value">15 minutes</span>
                    </div>
                </div>
                
                <h3>Driver Information</h3>
                <div class="bus-driver">
                    <div class="driver-avatar">MR</div>
                    <div class="driver-info">
                        <div class="driver-name">Michael Rodriguez</div>
                        <div class="driver-contact">
                            <i class="fas fa-phone"></i> +63 912 345 6789
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>

        const now = new Date();
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('en-US', options);
        

        const navLinks = document.querySelectorAll('.nav-links a');
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                if(this.textContent === 'Transport Bus') {
                    document.querySelector('.section-title').scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>