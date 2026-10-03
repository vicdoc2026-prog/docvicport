<!-- Header.php -->
<?php
// bar/header.php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user's full name from session, default to 'Guest' if not set
$full_name = isset($_SESSION['full_name']) ? $_SESSION['full_name'] : 'Guest';
$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .header-container {
            background-color: #ffffff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-container .relative {
            position: relative;
        }

        .header-container .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ef4444;
            color: white;
            font-size: 0.6rem;
            width: 0.875rem;
            height: 0.875rem;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }

        .header-container .fa-bell {
            font-size: 1.25rem;
            color: #4b5563;
        }

        .header-container .fa-bell:hover {
            color: #1f2937;
        }

        .header-container .truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            top: 100%;
            margin-top: 0.5rem;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border-radius: 0.375rem;
            z-index: 50;
            border: 1px solid #e5e7eb;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-header {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .dropdown-header .user-name {
            font-size: 0.875rem;
            font-weight: 600;
            color: #1f2937;
            display: block;
        }

        .dropdown-header .user-role {
            font-size: 0.75rem;
            color: #6b7280;
            display: block;
            margin-top: 0.25rem;
            text-transform: capitalize;
        }

        .dropdown-item {
            display: block;
            width: 100%;
            padding: 0.75rem 1rem;
            text-align: left;
            font-size: 0.875rem;
            color: #374151;
            text-decoration: none;
            transition: background-color 0.15s ease-in-out;
            border: none;
            background: none;
            cursor: pointer;
        }

        .dropdown-item:hover {
            background-color: #f3f4f6;
        }

        .dropdown-item i {
            margin-right: 0.5rem;
            width: 1rem;
            text-align: center;
        }

        .dropdown-item.logout {
            color: #dc2626;
            border-top: 1px solid #e5e7eb;
        }

        .dropdown-item.logout:hover {
            background-color: #fee2e2;
        }

        .user-avatar-button {
            display: flex;
            align-items: center;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
        }

        .user-avatar-button:focus {
            outline: none;
        }
    </style>
</head>
<body>
    <header class="header-container">
        <div class="flex items-center justify-between px-6 py-4">
            <div class="flex items-center">
                <button class="md:hidden text-gray-600 mr-4">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <h1 class="text-base font-semibold text-gray-800 truncate"> Farm</h1>
            </div>

            <div class="flex items-center space-x-6">
                <div class="relative">
                    <!-- <button class="text-gray-600 hover:text-gray-900">
                        <i class="fas fa-bell text-xl"></i>
                        <span class="notification-badge">3</span>
                    </button> -->
                </div>

                <div class="dropdown">
                    <button class="user-avatar-button" id="userMenuButton">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($full_name); ?>&background=1a3a6c&color=fff" alt="User" class="w-6 h-6 rounded-full">
                        <span class="ml-2 text-xs text-gray-700 font-medium hidden sm:inline-block truncate"><?php echo htmlspecialchars($full_name); ?></span>
                        <i class="fas fa-chevron-down ml-1 text-gray-600"></i>
                    </button>
                    
                    <div class="dropdown-menu" id="userDropdown">
                        <div class="dropdown-header">
                            <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                            <?php if (!empty($role)): ?>
                                <span class="user-role"><?php echo htmlspecialchars($role); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Profile
                        </a>
                        <a href="settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                        <a href="../config/logout.php" class="dropdown-item logout">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <script>
        // Dropdown toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const userMenuButton = document.getElementById('userMenuButton');
            const userDropdown = document.getElementById('userDropdown');

            userMenuButton.addEventListener('click', function(e) {
                e.stopPropagation();
                userDropdown.classList.toggle('show');
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userMenuButton.contains(e.target) && !userDropdown.contains(e.target)) {
                    userDropdown.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>