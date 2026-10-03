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
    background: linear-gradient(180deg, var(--primary), var(--dark)),
                linear-gradient(90deg, var(--primary) 80%, rgba(26, 58, 108, 0.2) 100%);
    transition: all 0.3s ease;
    box-shadow: 3px 0 8px rgba(0, 0, 0, 0.03);
  }

  .sidebar-item {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
    padding-left: 0.5rem;
  }

  .sidebar-item:hover {
    background-color: rgba(255, 255, 255, 0.08);
    transform: translateX(4px);
    border-left: 4px solid #ffffff;
    border-bottom-left-radius: 0px;
    border-top-left-radius: 0px;
    border-bottom-right-radius: 0px;
    border-top-right-radius: 0px;

  }

  .sidebar-item.sidebar-active {
    background-color: rgba(255, 255, 255, 0.12);
    border-left: 4px solid #ffffff;
    border-bottom-left-radius: 0px;
    border-top-left-radius: 0px;
    border-bottom-right-radius: 0px;
    border-top-right-radius: 0px;
  }

  .sidebar-item span {
    flex-wrap: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
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

  .voting-table tr {
    border-bottom: 1px solid #e2e8f0;
  }

  .voting-table tr:last-child {
    border-bottom: none;
  }

  .btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--dark));
    transition: all 0.3s ease;
  }

  .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px rgba(26, 58, 108, 0.3);
  }

  .tab-btn {
    transition: all 0.2s ease;
  }

  .tab-btn:hover {
    background-color: rgba(42, 157, 143, 0.1);
  }

  .tab-btn.active {
    border-bottom: 3px solid var(--accent);
  }

  /* Added styles for submenu dropdown */
  .sidebar-submenu {
    display: none;
    overflow: hidden;
    transition: max-height 0.3s ease;
    max-height: 0;
  }

  .sidebar-submenu.show {
    display: block;
    max-height: 500px; /* Adjust based on content */
  }

  .chevron {
    transition: transform 0.3s ease;
  }

  .sidebar-toggle.expanded .chevron {
    transform: rotate(90deg);
  }
</style>

<!-- Sidebar Navigation -->
<div class="sidebar w-44 text-white flex-shrink-0 hidden md:block bg-blue-900 min-h-screen">
  <div class="p-4">
    <!-- Logo and Title -->
    <div class="flex items-center mb-6">
      <a href="../categories.php" class="mr-2 flex items-center justify-center bg-white text-gray-800 rounded-full w-8 h-8 hover:bg-gray-200 transition">
        <i class="fas fa-arrow-left"></i>
      </a>
      <h1 class="text-base font-bold">Sangguniang Panlalawigan</h1>
    </div>

    <!-- Navigation -->
    <nav class="mt-6">
      <ul class="space-y-1">
        <!-- Dashboard -->
        <li>
          <a href="dashboard.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'sidebar-active' : '' ?>">
            <i class="fas fa-home mr-2"></i>
            <span>Dashboard</span>
          </a>
        </li>
        <li>
          <a href="committee.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs mt-4 <?= basename($_SERVER['PHP_SELF']) == 'committee.php' ? 'sidebar-active' : '' ?>">
            <i class="fas fa-gavel mr-2" title="Committee Hearing"></i>
            <span>Committee</span>
          </a>
        </li>
        
        <!-- Events -->
        <li class="mt-4">
          <p class="text-[0.65rem] uppercase text-gray-400 tracking-wider px-2 mb-1">Events</p>
          <ul class="space-y-1">
            <li>
              <a href="araw.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'araw.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-calendar-day mr-2"></i>
                <span>Araw & Fiesta</span>
              </a>
            </li>
            <!-- <li>
              <a href="fiesta.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'fiesta.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-flag mr-2"></i>
                <span>Fiesta</span>
              </a>
            </li> -->
            <li>
              <a href="event.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'event.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-calendar-check mr-2"></i>
                <span>Regular Events</span>
              </a>
            </li>
                        <li>
              <a href="spcalendar/invitation.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'spcalendar.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-calendar-check mr-2"></i>
                <span>Invitation</span>
              </a>
            </li>
            <li>
              <a href="calendar.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'calendar.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-calendar-check mr-2"></i>
                <span>Calendar</span>
              </a>
            </li>
              <li>
                <a href="quick_index.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'quick_index.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-book mr-2"></i>
                <span>Quick Index</span>
                </a>
            </li>
                          <li>
                <a href="notes.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs <?= basename($_SERVER['PHP_SELF']) == 'notes.php' ? 'sidebar-active' : '' ?>">
                <i class="fas fa-sticky-note mr-2"></i>
                <span>Notes</span>
                </a>
            </li>
          </ul>
        </li>

        <!-- Sessions -->
        <li class="mt-4">
          <p class="text-[0.65rem] uppercase text-gray-400 tracking-wider px-2 mb-1">Sessions</p>
          <ul class="space-y-1">
            <!-- Ordinances with sub-menu -->
            <li>
              <div class="sidebar-item sidebar-toggle flex items-center justify-between p-2 rounded-lg text-xs" onclick="toggleSubmenu(this)" data-pages="ordinances.php,draft_ordinances.php">
                <div class="flex items-center">
                  <i class="fas fa-file-contract mr-2"></i>
                  <span>Ordinances</span>
                </div>
                <i class="fas fa-chevron-right text-xs chevron"></i>
              </div>
              <ul class="sidebar-submenu space-y-1 mt-1">
                <li>
                  <a href="ordinances.php" class="sidebar-item sidebar-subitem flex items-center p-2 rounded-lg text-xs" data-page="ordinances.php">
                    <i class="fas fa-list mr-2"></i>
                    <span>Approved Ordinance </span>
                  </a>
                </li>
                <li>
                  <a href="draft_ordinances.php" class="sidebar-item sidebar-subitem flex items-center p-2 rounded-lg text-xs" data-page="draft_ordinances.php">
                    <i class="fas fa-edit mr-2"></i>
                    <span>Draft Ordinances</span>
                  </a>
                </li>
              </ul>
            </li>
            
            <!-- Resolutions with sub-menu -->
            <li>
              <div class="sidebar-item sidebar-toggle flex items-center justify-between p-2 rounded-lg text-xs" onclick="toggleSubmenu(this)" data-pages="resolutions.php,draft_resolutions.php">
                <div class="flex items-center">
                  <i class="fas fa-file-alt mr-2"></i>
                  <span>Resolutions</span>
                </div>
                <i class="fas fa-chevron-right text-xs chevron"></i>
              </div>
              <ul class="sidebar-submenu space-y-1 mt-1">
                <li>
                  <a href="resolutions.php" class="sidebar-item sidebar-subitem flex items-center p-2 rounded-lg text-xs" data-page="resolutions.php">
                    <i class="fas fa-list mr-2"></i>
                    <span>Approved Resolutions</span>
                  </a>
                </li>
                <li>
                  <a href="draft_resolutions.php" class="sidebar-item sidebar-subitem flex items-center p-2 rounded-lg text-xs" data-page="draft_resolutions.php">
                    <i class="fas fa-edit mr-2"></i>
                    <span>Draft Resolutions</span>
                  </a>
                </li>
              </ul>
            </li>
          </ul>
        </li>

        <!-- Voting -->
        <li>
          <a href="vote.php" class="sidebar-item flex items-center p-2 rounded-lg text-xs mt-4 <?= basename($_SERVER['PHP_SELF']) == 'vote.php' ? 'sidebar-active' : '' ?>">
            <i class="fas fa-vote-yea mr-2"></i>
            <span>Vote Garnered</span>
          </a>
        </li>

      </ul>
    </nav>
  </div>
</div>
<!-- Font Awesome (required for icons) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<script>
function toggleSubmenu(element) {
  const submenu = element.nextElementSibling;
  const isExpanded = submenu.classList.contains('show');
  
  // Close all other submenus
  document.querySelectorAll('.sidebar-submenu').forEach(menu => {
    menu.classList.remove('show');
  });
  
  document.querySelectorAll('.sidebar-toggle').forEach(toggle => {
    toggle.classList.remove('expanded');
  });
  
  // Toggle current submenu
  if (isExpanded) {
    // If already expanded, close it (already handled by removing 'show' above)
  } else {
    submenu.classList.add('show');
    element.classList.add('expanded');
  }
}

// Demo: Handle active states when clicking sidebar items
document.addEventListener('DOMContentLoaded', function() {
  // Remove active class from all items
  function clearActiveStates() {
    document.querySelectorAll('.sidebar-item').forEach(item => {
      item.classList.remove('sidebar-active');
    });
  }
  
  // Add click handlers to all sidebar links
  document.querySelectorAll('.sidebar-item[data-page]').forEach(item => {
    item.addEventListener('click', function(e) {
      // e.preventDefault(); // Uncomment if you want to prevent navigation for demo
      clearActiveStates();
      this.classList.add('sidebar-active');
      
      // If it's a sub-item, also mark parent as active and expand submenu
      if (this.classList.contains('sidebar-subitem')) {
        const parentToggle = this.closest('.sidebar-submenu').previousElementSibling;
        if (parentToggle) {
          parentToggle.classList.add('sidebar-active');
          parentToggle.nextElementSibling.classList.add('show');
          parentToggle.classList.add('expanded');
        }
      }
    });
  });
  
  // Handle parent toggle clicks (in addition to onclick)
  document.querySelectorAll('.sidebar-toggle').forEach(toggle => {
    toggle.addEventListener('click', function() {
      const submenu = this.nextElementSibling;
      const isExpanded = submenu.classList.contains('show');
      
      if (!isExpanded) {
        clearActiveStates();
        this.classList.add('sidebar-active');
      } else {
        // If closing, remove active if no subitem is active
        const activeSub = submenu.querySelector('.sidebar-active');
        if (!activeSub) {
          this.classList.remove('sidebar-active');
        }
      }
    });
  });
  
  // Set initial active state and expand submenus if necessary
  const currentPage = window.location.pathname.split('/').pop(); // Get current filename
  if (currentPage) {
    const activeItem = document.querySelector(`.sidebar-item[data-page="${currentPage}"]`);
    if (activeItem) {
      activeItem.classList.add('sidebar-active');
      if (activeItem.classList.contains('sidebar-subitem')) {
        const parentToggle = activeItem.closest('.sidebar-submenu').previousElementSibling;
        if (parentToggle) {
          parentToggle.classList.add('sidebar-active');
          parentToggle.classList.add('expanded');
          parentToggle.nextElementSibling.classList.add('show');
        }
      }
    } else {
      // Fallback to dashboard if no match
      const defaultItem = document.querySelector('.sidebar-item[href="dashboard.php"]');
      if (defaultItem) {
        defaultItem.classList.add('sidebar-active');
      }
    }
  }
});
</script>