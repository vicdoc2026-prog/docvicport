<?php
// sidebar.php - Navigation sidebar component with collapse/expand functionality
?>

<style>
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        width: 280px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-right: 1px solid rgba(255, 255, 255, 0.6);
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.08);
        padding: 2rem 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        z-index: 1000;
        overflow-y: auto;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.collapsed {
        width: 90px;
        padding: 2rem 0.75rem;
    }

    .sidebar-logo {
        text-align: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 2px solid rgba(59, 130, 246, 0.2);
        transition: all 0.3s ease;
    }

    .sidebar.collapsed .sidebar-logo {
        padding: 0 0.5rem 1.5rem 0.5rem;
        margin-bottom: 1.5rem;
    }

    .sidebar-logo h2 {
        font-family: 'Orbitron', sans-serif;
        font-weight: 900;
        font-size: 1.5rem;
        background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 50%, #10b981 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
        transition: all 0.3s ease;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-logo h2 {
        font-size: 1.2rem;
        margin-bottom: 0;
    }

    .sidebar-logo p {
        font-size: 0.75rem;
        color: #64748b;
        font-weight: 600;
        transition: all 0.3s ease;
        opacity: 1;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-logo p {
        opacity: 0;
        height: 0;
        margin: 0;
    }

    .sidebar-nav {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sidebar-link {
        color: #475569;
        font-weight: 600;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 1rem;
        text-decoration: none;
        border: 2px solid transparent;
        background: transparent;
        cursor: pointer;
        width: 100%;
        text-align: left;
        position: relative;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-link {
        padding: 1rem;
        justify-content: center;
    }

    .sidebar-link i {
        font-size: 1.25rem;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
        transition: transform 0.2s ease;
    }

    .sidebar-link:hover i {
        transform: scale(1.1);
    }

    .sidebar-link span {
        transition: all 0.3s ease;
        opacity: 1;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-link span {
        opacity: 0;
        width: 0;
        position: absolute;
    }

    .sidebar-link:hover {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
        transform: translateX(5px);
    }

    .sidebar.collapsed .sidebar-link:hover {
        transform: translateX(2px);
    }

    .sidebar-link.active {
        background: linear-gradient(135deg, #0e3ecdff, #1960d3ff);
        color: white;
        border-color: #10419cff;
        box-shadow: 0 4px 12px rgba(124, 58, 237, 0.3);
    }

    .sidebar-link.active i {
        transform: scale(1.1);
    }

    .sidebar-link.active::after {
        content: '';
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        background: white;
        border-radius: 50%;
        opacity: 0.8;
    }

    .sidebar.collapsed .sidebar-link.active::after {
        right: 5px;
    }

    .sidebar-link.college {
        color: #3b82f6;
    }

    .sidebar-link.college:hover {
        background: rgba(59, 130, 246, 0.15);
        color: #1d4ed8;
    }

    .sidebar-link.tesda {
        color: #059669;
    }

    .sidebar-link.tesda:hover {
        background: rgba(16, 185, 129, 0.15);
        color: #047857;
    }

    .sidebar-link.faculty {
        color: #dc2626;
    }

    .sidebar-link.faculty:hover {
        background: rgba(220, 38, 38, 0.15);
        color: #b91c1c;
    }

    .sidebar-footer {
        margin-top: auto;
        padding-top: 1.5rem;
        border-top: 2px solid rgba(59, 130, 246, 0.2);
        transition: all 0.3s ease;
    }

    .sidebar.collapsed .sidebar-footer {
        padding: 1rem 0.5rem 0 0.5rem;
    }

    .sidebar-back {
        background: #64748b;
        color: white;
        padding: 1rem 1.25rem;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .sidebar.collapsed .sidebar-back {
        padding: 1rem;
        justify-content: center;
    }

    .sidebar-back span {
        transition: all 0.3s ease;
        opacity: 1;
        white-space: nowrap;
        overflow: hidden;
    }

    .sidebar.collapsed .sidebar-back span {
        opacity: 0;
        width: 0;
        position: absolute;
    }

    .sidebar-back:hover {
        background: #475569;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(100, 116, 139, 0.4);
    }

    .toggle-sidebar {
        position: absolute;
        top: 1.5rem;
        right: -15px;
        width: 30px;
        height: 30px;
        background: white;
        border: 2px solid rgba(59, 130, 246, 0.3);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 1001;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .toggle-sidebar:hover {
        background: #3b82f6;
        color: white;
        transform: scale(1.1);
        border-color: #3b82f6;
    }

    .toggle-sidebar i {
        transition: transform 0.4s ease;
        font-size: 0.9rem;
    }

    .sidebar.collapsed .toggle-sidebar i {
        transform: rotate(180deg);
    }

    /* Tooltip for collapsed state */
    .sidebar-link .tooltip {
        position: absolute;
        left: calc(100% + 15px);
        top: 50%;
        transform: translateY(-50%);
        background: #1e293b;
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 6px;
        font-size: 0.875rem;
        font-weight: 500;
        white-space: nowrap;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
        z-index: 1002;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        pointer-events: none;
    }

    .sidebar-link .tooltip::before {
        content: '';
        position: absolute;
        left: -6px;
        top: 50%;
        transform: translateY(-50%);
        width: 0;
        height: 0;
        border-top: 6px solid transparent;
        border-bottom: 6px solid transparent;
        border-right: 6px solid #1e293b;
    }

    .sidebar.collapsed .sidebar-link:hover .tooltip {
        opacity: 1;
        visibility: visible;
        left: calc(100% + 20px);
    }

    /* Mobile responsive */
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

        .sidebar-logo h2 {
            font-size: 1.25rem;
        }

        .sidebar-link:hover {
            transform: translateX(0);
        }

        .toggle-sidebar {
            position: fixed;
            top: 1rem;
            right: 1rem;
            background: #3b82f6;
            color: white;
        }
    }

    /* Adjust main content when sidebar is present */
    .main-content-wrapper {
        margin-left: 280px;
        padding: 2rem;
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .sidebar.collapsed ~ .main-content-wrapper {
        margin-left: 90px;
    }

    @media (max-width: 768px) {
        .main-content-wrapper {
            margin-left: 0;
            padding: 1rem;
        }

        .sidebar.collapsed ~ .main-content-wrapper {
            margin-left: 0;
        }
    }
</style>

<aside class="sidebar" id="sidebar">
    <div class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-chevron-left"></i>
    </div>

    <div class="sidebar-logo">
        <h2>STII</h2>
    </div>

    <nav class="sidebar-nav">
        <a href="stii_operation.php" class="sidebar-link college" data-page="stii_operation">
            <i class="fas fa-graduation-cap"></i>
            <span>College</span>
            <div class="tooltip">College Operations</div>
        </a>
        
        <a href="stii_operation_SHS.php" class="sidebar-link active" data-page="stii_operation_shs">
            <i class="fas fa-school"></i>
            <span>High School</span>
            <div class="tooltip">High School Operations</div>
        </a>
        
        <a href="tesda.php" class="sidebar-link tesda" data-page="tesda">
            <i class="fas fa-certificate"></i>
            <span>TESDA</span>
            <div class="tooltip">TESDA Programs</div>
        </a>
        
        <a href="faculty.php" class="sidebar-link faculty" data-page="faculty">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Faculty</span>
            <div class="tooltip">Faculty Management</div>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="portal.php" class="sidebar-back">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Portal</span>
            
        </a>
    </div>
</aside>

<script>
// Toggle sidebar collapse/expand
const toggleSidebar = document.getElementById('toggleSidebar');
const sidebar = document.getElementById('sidebar');
const mainContent = document.querySelector('.main-content-wrapper');

// Check localStorage for saved state
const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';

// Apply saved state on page load
if (isCollapsed) {
    sidebar.classList.add('collapsed');
    if (mainContent) {
        mainContent.classList.add('collapsed');
    }
}

toggleSidebar.addEventListener('click', () => {
    sidebar.classList.toggle('collapsed');
    
    // Update main content margin if it exists
    if (mainContent) {
        mainContent.classList.toggle('collapsed');
    }
    
    // Save state to localStorage
    const isNowCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isNowCollapsed.toString());
});

// Active link management
document.addEventListener('DOMContentLoaded', function() {
    // Get current page from URL
    const currentPage = window.location.pathname.split('/').pop().split('.')[0];
    
    // Remove active class from all links
    const allLinks = document.querySelectorAll('.sidebar-link');
    allLinks.forEach(link => {
        link.classList.remove('active');
        
        // Check if this link matches current page
        const linkPage = link.getAttribute('data-page');
        if (linkPage && linkPage.toLowerCase() === currentPage.toLowerCase()) {
            link.classList.add('active');
        }
    });
    
    // If no active link found and there are links, default to first
    const activeLinks = document.querySelectorAll('.sidebar-link.active');
    if (activeLinks.length === 0 && allLinks.length > 0) {
        // You can set a default active link based on your preference
        // For example, check URL parameters or set based on user role
    }
    
    // Add click event to all sidebar links
    allLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            // Don't prevent default for actual navigation
            // Just update active state
            allLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            
            // If sidebar is collapsed on mobile, auto-collapse after click
            if (window.innerWidth <= 768 && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                if (mainContent) {
                    mainContent.classList.remove('collapsed');
                }
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        });
    });
    
    // Handle resize events
    window.addEventListener('resize', function() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
            if (mainContent) {
                mainContent.classList.remove('collapsed');
            }
            // Don't save this state as it's responsive behavior
        }
    });
    
    // Check initial mobile state
    if (window.innerWidth <= 768) {
        sidebar.classList.remove('collapsed');
        if (mainContent) {
            mainContent.classList.remove('collapsed');
        }
    }
});
</script>