<?php
session_start();

// If user is already logged in, redirect to categories page
if (isset($_SESSION['user_id'])) {
    header("Location: categories.php");
    exit();
}

// Check for error or timeout messages
$error_message = '';
$logout_success = false;

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'invalid':
            $error_message = 'Invalid username or password.';
            break;
        case 'empty':
            $error_message = 'Please fill in all fields.';
            break;
    }
}

if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please login again.';
}

if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_success = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>School President Portal - Login</title>
  <link rel="stylesheet" href="/bootstrap-5.3.7-dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="/bootstrap-icons-1.13.1/font/bootstrap-icons.css" />
  <script src="/bootstrap-5.3.7-dist/js/bootstrap.bundle.min.js"></script>
  <style>
    :root {
      --primary: #4a6fa5;
      --secondary: #166088;
      --accent: #4fc3f7;
      --light: #f8f9fa;
      --dark: #212529;
      --error: #e63946;
      --success: #10b981;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    /* Logout Success Alert */
    .logout-alert {
      position: fixed;
      top: 30px;
      right: 30px;
      min-width: 350px;
      max-width: 400px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
      padding: 1.5rem;
      display: flex;
      align-items: center;
      gap: 1rem;
      z-index: 9999;
      opacity: 0;
      transform: translateX(450px);
      transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      border-left: 4px solid var(--success);
    }

    .logout-alert.show {
      opacity: 1;
      transform: translateX(0);
    }

    .logout-alert.hide {
      opacity: 0;
      transform: translateX(450px);
    }

    .logout-icon-wrapper {
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #10b981, #059669);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
      animation: iconBounce 0.6s ease;
    }

    @keyframes iconBounce {
      0% {
        transform: scale(0) rotate(-45deg);
        opacity: 0;
      }
      50% {
        transform: scale(1.2) rotate(5deg);
      }
      100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
      }
    }

    .logout-icon-wrapper i {
      color: white;
      font-size: 1.5rem;
    }

    .logout-content {
      flex: 1;
    }

    .logout-title {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--dark);
      margin-bottom: 0.3rem;
    }

    .logout-message {
      font-size: 0.9rem;
      color: #666;
      line-height: 1.4;
    }

    .logout-close-btn {
      background: none;
      border: none;
      color: #999;
      cursor: pointer;
      font-size: 1.5rem;
      padding: 0;
      line-height: 1;
      transition: all 0.3s;
      flex-shrink: 0;
    }

    .logout-close-btn:hover {
      color: var(--dark);
      transform: rotate(90deg);
    }

    @media (max-width: 768px) {
      .logout-alert {
        right: 15px;
        left: 15px;
        min-width: auto;
      }
    }

    .login-container {
      width: 100%;
      max-width: 400px;
      padding: 2rem;
      background: white;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      position: relative;
      overflow: hidden;
    }

    .login-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 8px;
      background: linear-gradient(90deg, var(--primary), var(--accent));
    }

    .logo {
      text-align: center;
      margin-bottom: 2rem;
    }

    .logo img {
      height: 60px;
      margin-bottom: 1rem;
    }

    .logo h1 {
      color: var(--secondary);
      font-size: 1.5rem;
      font-weight: 600;
    }

    .logo p {
      color: #666;
      font-size: 0.9rem;
      margin-top: 0.5rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
      position: relative;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: var(--dark);
      font-weight: 500;
      font-size: 0.9rem;
    }

    .form-control {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(79, 195, 247, 0.2);
    }

    .btn {
      width: 100%;
      padding: 0.8rem;
      background: linear-gradient(90deg, var(--primary), var(--secondary));
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.3s;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .forgot-password {
      text-align: center;
      margin-top: 1rem;
    }

    .forgot-password a {
      color: var(--secondary);
      text-decoration: none;
      font-size: 0.9rem;
    }

    .forgot-password a:hover {
      text-decoration: underline;
    }

    .admin-badge {
      position: absolute;
      top: 20px;
      right: 20px;
      background-color: var(--accent);
      color: white;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 500;
    }

    .alert {
      padding: 0.75rem 1rem;
      margin-bottom: 1rem;
      border-radius: 8px;
      font-size: 0.9rem;
    }

    .alert-danger {
      background-color: #fee;
      color: var(--error);
      border: 1px solid #fcc;
    }

    input::-ms-reveal,
    input::-ms-clear {
      display: none;
    }
  </style>
</head>
<body>
  <!-- Logout Success Alert -->
  <?php if ($logout_success): ?>
  <div class="logout-alert" id="logoutAlert">
    <div class="logout-icon-wrapper">
      <i class="bi bi-box-arrow-right"></i>
    </div>
    <div class="logout-content">
      <div class="logout-title">Logged Out Successfully</div>
      <div class="logout-message">
        You have been securely logged out. See you next time!
      </div>
    </div>
    <button class="logout-close-btn" onclick="closeLogoutAlert()">×</button>
  </div>
  <?php endif; ?>

  <div class="login-container">
    <div class="admin-badge">President</div>

    <div class="logo">
      <img src="https://via.placeholder.com/60x60?text=SCHOOL" alt="School Logo">
      <h1>President Portal</h1>
      <p>Access your administrative dashboard</p>
    </div>

    <?php if ($error_message): ?>
      <div class="alert alert-danger" role="alert">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <form action="config/login-process.php" method="POST">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control" placeholder="Enter your president ID" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div style="position:relative;">
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required style="padding-right:40px;">
          <span id="togglePassword" style="position:absolute; top:50%; right:12px; transform:translateY(-50%); cursor:pointer;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#166088" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
              <circle cx="12" cy="12" r="3"/>
            </svg>
          </span>
        </div>
      </div>

      <button type="submit" class="btn">Login</button>

      <div class="forgot-password">
        <a href="#">Forgot password?</a>
      </div>
    </form>

    <script>
      // Password toggle functionality
      const passwordInput = document.getElementById('password');
      const togglePassword = document.getElementById('togglePassword');
      let visible = false;

      togglePassword.addEventListener('click', function() {
        visible = !visible;
        passwordInput.type = visible ? 'text' : 'password';
        togglePassword.innerHTML = visible
          ? `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#166088" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a21.77 21.77 0 0 1 5.06-7.94"/><path d="M1 1l22 22"/><circle cx="12" cy="12" r="3"/></svg>`
          : `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#166088" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>`;
      });

      // Logout alert functionality
      <?php if ($logout_success): ?>
      window.addEventListener('DOMContentLoaded', function() {
        showLogoutAlert();
        
        // Remove the parameter from URL without reloading
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
      });

      function showLogoutAlert() {
        const alert = document.getElementById('logoutAlert');
        
        // Show the alert with animation
        setTimeout(() => {
          alert.classList.add('show');
        }, 200);

        // Auto-hide after 5 seconds
        setTimeout(() => {
          closeLogoutAlert();
        }, 5000);
      }

      function closeLogoutAlert() {
        const alert = document.getElementById('logoutAlert');
        alert.classList.remove('show');
        alert.classList.add('hide');
      }
      <?php endif; ?>
    </script>
  </div>
</body>
</html>