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
    <title>2025 Event Task Calendar</title>
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
</head>
<body>
    <div class="container">
        <header>
            <h1>2025 Event Task Calendar</h1>
            <p>Plan your year with this interactive calendar</p>
            <div class="year-nav">
                <select id="year-select">
                    <option value="2024">2024</option>
                    <option value="2025" selected>2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
        </header>
        
        <div class="calendar-view">
            <button class="view-option" id="view-year">Year View</button>
            <button class="view-option active" id="view-month">Month View</button>
            <button class="view-option" id="view-week">Week View</button>
        </div>
        
        <div class="legend">
            <div class="legend-item">
                <div class="legend-color" style="background-color: #3498db;"></div>
                <span>Work Events</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #2ecc71;"></div>
                <span>Personal Tasks</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background-color: #e74c3c;"></div>
                <span>Holidays</span>
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
    </div>
    
    <div class="event-modal" id="event-modal">
        <div class="modal-content">
            <span class="close-modal" id="close-modal">&times;</span>
            <h3>Add New Event</h3>
            <form class="event-form" id="event-form">
                <input type="hidden" id="event-date">
                <label for="event-title">Event Title</label>
                <input type="text" id="event-title" required>
                
                <label for="event-type">Event Type</label>
                <select id="event-type">
                    <option value="work">Work Event</option>
                    <option value="personal">Personal Task</option>
                    <option value="holiday">Holiday</option>
                </select>
                
                <label for="event-time">Time</label>
                <input type="time" id="event-time">
                
                <label for="event-desc">Description (Optional)</label>
                <textarea id="event-desc" rows="3"></textarea>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" id="cancel-event">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Event</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            
            let currentDate = new Date(2025, 0, 1);
            let events = {};
            
          
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
</body>
</html>