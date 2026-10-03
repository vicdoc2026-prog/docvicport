<?php
require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2025 Academic Calendar - STI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
        :root {
            --primary: #3498db;
            --secondary: #2ecc71;
            --accent: #e74c3c;
            --light: #f5f7fa;
            --dark: #2c3e50;
            --gray: #ecf0f1;
            --text: #333333;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f9f9f9;
            color: var(--text);
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        
        .year-nav {
            display: flex;
            justify-content: center;
            margin: 15px 0;
        }
        
        .year-nav select {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            font-size: 1.1rem;
            background-color: white;
            color: var(--dark);
        }
        
        .calendar-view {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .view-option {
            padding: 10px 20px;
            background-color: var(--gray);
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .view-option.active {
            background-color: var(--primary);
            color: white;
        }
        
        .calendar-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        
        .month-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background-color: var(--primary);
            color: white;
        }
        
        .month-nav {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0 10px;
        }
        
        .weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background-color: var(--light);
            font-weight: bold;
            text-align: center;
            padding: 10px 0;
        }
        
        .days-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background-color: var(--gray);
        }
        
        .day {
            min-height: 100px;
            padding: 10px;
            background-color: white;
            position: relative;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .day:hover {
            background-color: #f0f8ff;
        }
        
        .day-number {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .event-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 2px;
        }
        
        .event-work {
            background-color: var(--primary);
        }
        
        .event-personal {
            background-color: var(--secondary);
        }
        
        .event-holiday {
            background-color: var(--accent);
        }
        
        .events-list {
            margin-top: 5px;
            font-size: 0.8rem;
        }
        
        .event-item {
            padding: 3px 5px;
            margin-bottom: 3px;
            border-radius: 3px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .event-work-item {
            background-color: rgba(52, 152, 219, 0.1);
            border-left: 3px solid var(--primary);
        }
        
        .event-personal-item {
            background-color: rgba(46, 204, 113, 0.1);
            border-left: 3px solid var(--secondary);
        }
        
        .event-holiday-item {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 3px solid var(--accent);
        }
        
        .other-month {
            color: #bbb;
            background-color: #f9f9f9;
        }
        
        .today {
            background-color: #e1f5fe;
            border: 2px solid var(--primary);
        }
        
        .event-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background-color: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .close-modal {
            float: right;
            font-size: 1.5rem;
            font-weight: bold;
            cursor: pointer;
        }
        
        .event-form label {
            display: block;
            margin: 10px 0 5px;
        }
        
        .event-form input, .event-form select, .event-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .form-actions {
            margin-top: 20px;
            text-align: right;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-left: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary);
            color: white;
        }
        
        .btn-secondary {
            background-color: var(--gray);
            color: var(--dark);
        }
        
        @media (max-width: 768px) {
            .day {
                min-height: 70px;
                padding: 5px;
                font-size: 0.9rem;
            }
            
            .events-list {
                display: none;
            }
            
            .event-dot {
                width: 6px;
                height: 6px;
            }
        }
        
        .legend {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 15px;
            height: 15px;
            border-radius: 3px;
        }
    </style>
<style>
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
            color: #333;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            padding: 30px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        header h1 {
            font-size: 2.8rem;
            color: #1a2a6c;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }

        .academic-year {
            background: #e63946;
            color: white;
            display: inline-block;
            padding: 8px 25px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 1.2rem;
            margin: 15px 0;
        }

        .calendar-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 20px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .nav-btn {
            background: #1a2a6c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .nav-btn:hover {
            background: #e63946;
            transform: translateY(-2px);
        }

        .year-display {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1a2a6c;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .month-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .month-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        }

        .month-header {
            padding-bottom: 15px;
            margin-bottom: 15px;
            border-bottom: 3px solid #e63946;
            text-align: center;
        }

        .month-header h2 {
            font-size: 1.8rem;
            color: #1a2a6c;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 5px;
            margin-bottom: 20px;
        }

        .day-header {
            text-align: center;
            font-weight: 700;
            padding: 8px 0;
            color: #1a2a6c;
        }

        .day-cell {
            aspect-ratio: 1;
            background: #f8f9fa;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            padding: 5px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }

        .day-cell:hover {
            background: #e9ecef;
            transform: scale(1.05);
        }

        .day-cell.has-event {
            background: #ffe6e6;
        }

        .day-number {
            font-weight: 700;
            font-size: 0.9rem;
            color: #495057;
            margin-bottom: 3px;
        }

        .event-indicator {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            margin: 1px;
        }

        .events-list {
            list-style: none;
            margin-top: 15px;
            border-top: 2px dashed #dee2e6;
            padding-top: 15px;
        }

        .event-item {
            background: #f8f9fa;
            border-left: 4px solid #457b9d;
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 0 8px 8px 0;
            transition: all 0.2s ease;
            position: relative;
        }

        .event-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
            border-left: 4px solid #e63946;
        }

        .event-date {
            font-weight: 700;
            color: #e63946;
            display: block;
            margin-bottom: 5px;
            font-size: 1.05rem;
        }

        .event-desc {
            color: #495057;
            line-height: 1.5;
            font-size: 0.95rem;
        }

        .event-category {
            position: absolute;
            top: 12px;
            right: 15px;
            font-size: 0.75rem;
            background: #457b9d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-weight: 600;
        }

        .legend {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 30px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .enrollment { background-color: #a8dadc; }
        .training { background-color: #f4a261; }
        .orientation { background-color: #2a9d8f; }
        .deadline { background-color: #e9c46a; }
        .examination { background-color: #e76f51; }
        .meeting { background-color: #9b5de5; }
        .event { background-color: #00bbf9; }

        footer {
            text-align: center;
            color: white;
            padding: 20px;
            font-size: 0.9rem;
            margin-top: 30px;
            opacity: 0.8;
        }

        @media (max-width: 768px) {
            .calendar-grid {
                grid-template-columns: 1fr;
            }
            
            header h1 {
                font-size: 2.2rem;
            }
            
            .calendar-navigation {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1><i class="fas fa-calendar-alt"></i> 2025 Academic Calendar</h1>
            <div class="academic-year">School Year: 2025–2026</div>
            <p>STI Institutional Activities and Important Dates</p>
        </header>

        <div class="legend">
            <div class="legend-item">
                <div class="legend-color enrollment"></div>
                <span>Enrollment</span>
            </div>
            <div class="legend-item">
                <div class="legend-color training"></div>
                <span>Training & Workshop</span>
            </div>
            <div class="legend-item">
                <div class="legend-color orientation"></div>
                <span>Orientation</span>
            </div>
            <div class="legend-item">
                <div class="legend-color deadline"></div>
                <span>Submission Deadline</span>
            </div>
            <div class="legend-item">
                <div class="legend-color examination"></div>
                <span>Examination</span>
            </div>
            <div class="legend-item">
                <div class="legend-color meeting"></div>
                <span>Meeting</span>
            </div>
            <div class="legend-item">
                <div class="legend-color event"></div>
                <span>Special Event</span>
            </div>
        </div>


                <div class="calendar-container">
            <div class="month-header">
                <button class="month-nav" id="prev-month">&#10094;</button>
                <h2 id="current-month">January 2025</h2>
                <button class="month-nav" id="next-month">&#10095;</button>
            </div>
            
            <div class="weekdays">
                <div>Sun</div>
                <div>Mon</div>
                <div>Tue</div>
                <div>Wed</div>
                <div>Thu</div>
                <div>Fri</div>
                <div>Sat</div>
            </div>
            
            <div class="days-grid" id="days-container">
                <!-- Days will be populated by JavaScript -->
            </div>
        </div>
        <footer>
            <p>Doc Vic Portal &copy; 2025 | JIMZ</p>
        </footer>
    </div>

    <script>
        // Navigation functionality
        document.getElementById('prev-year').addEventListener('click', () => {
            document.querySelector('.year-display').textContent = '2024';
        });
        
        document.getElementById('next-year').addEventListener('click', () => {
            document.querySelector('.year-display').textContent = '2026';
        });
    </script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Current date object
            let currentDate = new Date(2025, 0, 1); // Start at January 2025
            let events = {};
            
            // Sample events data
            events['2025-01-01'] = [
                { title: 'New Year\'s Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-01-06'] = [
                { title: 'Epiphany', type: 'holiday', time: 'All day' }
            ];
            events['2025-01-20'] = [
                { title: 'MLK Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-01-25'] = [
                { title: 'Chinese New Year', type: 'holiday', time: 'All day' }
            ];
            events['2025-02-14'] = [
                { title: 'Valentine\'s Day', type: 'personal', time: 'Evening' }
            ];
            events['2025-03-17'] = [
                { title: 'St. Patrick\'s Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-04-20'] = [
                { title: 'Easter Sunday', type: 'holiday', time: 'All day' }
            ];
            events['2025-05-12'] = [
                { title: 'Mother\'s Day', type: 'personal', time: 'All day' }
            ];
            events['2025-07-04'] = [
                { title: 'Independence Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-09-01'] = [
                { title: 'Labor Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-10-31'] = [
                { title: 'Halloween', type: 'personal', time: 'Evening' }
            ];
            events['2025-11-27'] = [
                { title: 'Thanksgiving', type: 'holiday', time: 'All day' }
            ];
            events['2025-12-25'] = [
                { title: 'Christmas Day', type: 'holiday', time: 'All day' }
            ];
            events['2025-12-31'] = [
                { title: 'New Year\'s Eve', type: 'personal', time: 'Evening' }
            ];
            
            // DOM elements
            const daysContainer = document.getElementById('days-container');
            const currentMonthElement = document.getElementById('current-month');
            const prevMonthButton = document.getElementById('prev-month');
            const nextMonthButton = document.getElementById('next-month');
            const eventModal = document.getElementById('event-modal');
            const closeModalButton = document.getElementById('close-modal');
            const cancelEventButton = document.getElementById('cancel-event');
            const eventForm = document.getElementById('event-form');
            const eventDateInput = document.getElementById('event-date');
            
            // Initialize calendar
            renderCalendar(currentDate);
            
            // Event listeners
            prevMonthButton.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() - 1);
                renderCalendar(currentDate);
            });
            
            nextMonthButton.addEventListener('click', () => {
                currentDate.setMonth(currentDate.getMonth() + 1);
                renderCalendar(currentDate);
            });
            
            daysContainer.addEventListener('click', (e) => {
                if (e.target.classList.contains('day')) {
                    const date = e.target.getAttribute('data-date');
                    openAddEventModal(date);
                }
            });
            
            closeModalButton.addEventListener('click', closeEventModal);
            cancelEventButton.addEventListener('click', closeEventModal);
            
            eventForm.addEventListener('submit', (e) => {
                e.preventDefault();
                saveEvent();
            });
            
            // Functions
            function renderCalendar(date) {
                const year = date.getFullYear();
                const month = date.getMonth();
                
                currentMonthElement.textContent = `${getMonthName(month)} ${year}`;
                
                // Clear previous days
                daysContainer.innerHTML = '';
                
                // Get first day of month and total days
                const firstDay = new Date(year, month, 1).getDay();
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                
                // Previous month days
                const daysInPrevMonth = new Date(year, month, 0).getDate();
                for (let i = firstDay - 1; i >= 0; i--) {
                    const day = document.createElement('div');
                    day.className = 'day other-month';
                    day.textContent = daysInPrevMonth - i;
                    daysContainer.appendChild(day);
                }
                
                // Current month days
                const today = new Date();
                for (let i = 1; i <= daysInMonth; i++) {
                    const day = document.createElement('div');
                    day.className = 'day';
                    day.setAttribute('data-date', `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`);
                    
                    // Check if it's today
                    if (year === today.getFullYear() && month === today.getMonth() && i === today.getDate()) {
                        day.classList.add('today');
                    }
                    
                    const dayNumber = document.createElement('div');
                    dayNumber.className = 'day-number';
                    dayNumber.textContent = i;
                    day.appendChild(dayNumber);
                    
                    // Add events if any
                    const dateString = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                    if (events[dateString]) {
                        const eventsList = document.createElement('div');
                        eventsList.className = 'events-list';
                        
                        events[dateString].forEach(event => {
                            const eventDot = document.createElement('span');
                            eventDot.className = `event-dot event-${event.type}`;
                            
                            const eventItem = document.createElement('div');
                            eventItem.className = `event-item event-${event.type}-item`;
                            eventItem.title = event.title;
                            eventItem.appendChild(eventDot);
                            eventItem.appendChild(document.createTextNode(event.title));
                            
                            eventsList.appendChild(eventItem);
                        });
                        
                        day.appendChild(eventsList);
                    }
                    
                    daysContainer.appendChild(day);
                }
                
                // Next month days (to complete the grid)
                const totalCells = 42; // 6 rows x 7 columns
                const nextMonthDays = totalCells - firstDay - daysInMonth;
                for (let i = 1; i <= nextMonthDays; i++) {
                    const day = document.createElement('div');
                    day.className = 'day other-month';
                    day.textContent = i;
                    daysContainer.appendChild(day);
                }
            }
            
            function getMonthName(monthIndex) {
                const months = [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December'
                ];
                return months[monthIndex];
            }
            
            function openAddEventModal(date) {
                eventDateInput.value = date;
                eventModal.style.display = 'flex';
            }
            
            function closeEventModal() {
                eventModal.style.display = 'none';
                eventForm.reset();
            }
            
            function saveEvent() {
                const date = eventDateInput.value;
                const title = document.getElementById('event-title').value;
                const type = document.getElementById('event-type').value;
                const time = document.getElementById('event-time').value;
                const desc = document.getElementById('event-desc').value;
                
                if (!events[date]) {
                    events[date] = [];
                }
                
                events[date].push({
                    title: title,
                    type: type,
                    time: time || 'All day',
                    description: desc
                });
                
                closeEventModal();
                renderCalendar(currentDate);
            }
        });
    </script>
