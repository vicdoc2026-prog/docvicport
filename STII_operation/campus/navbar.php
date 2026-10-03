<?php
/**
 * navbar.php - Navigation Bar Component
 * 
 * Requires the following session variables:
 * - $_SESSION['full_name']
 * - $_SESSION['role']
 */
?>
<nav class="top-nav">
    <div class="nav-container">
        <div class="nav-left">
            <a href="../portal.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                Back to Portal
            </a>
        </div>
        
        <div class="nav-center">
            <div class="nav-brand">
                <div class="brand-logo">
                    <i class="fas fa-university"></i>
                </div>
                <div class="brand-text">
                    <h1>STII Asset Management</h1>
                    <p>Campus Infrastructure Dashboard</p>
                </div>
            </div>
        </div>
        
        <div class="nav-right">
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo isset($_SESSION['full_name']) ? strtoupper(substr($_SESSION['full_name'], 0, 1)) : 'U'; ?>
                </div>
                <div class="user-details">
                    <h4><?php echo isset($_SESSION['full_name']) ? htmlspecialchars($_SESSION['full_name']) : 'User'; ?></h4>
                    <p><?php echo isset($_SESSION['role']) ? htmlspecialchars(ucfirst($_SESSION['role'])) : 'User'; ?> Account</p>
                </div>
                <div class="user-dropdown">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
    </div>
</nav>

<style>
    /* Navigation Styles (will be included in main CSS) */
    .top-nav {
        background: white;
        padding: 1rem 2rem;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 100;
        border-bottom: 1px solid var(--border);
    }
    
    .nav-container {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .nav-left, .nav-center, .nav-right {
        flex: 1;
        display: flex;
        align-items: center;
    }
    
    .nav-left {
        justify-content: flex-start;
    }
    
    .nav-center {
        justify-content: center;
    }
    
    .nav-right {
        justify-content: flex-end;
    }
    
    .nav-brand {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    
    .brand-logo {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
    }
    
    .brand-text h1 {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        letter-spacing: -0.5px;
    }
    
    .brand-text p {
        font-size: 0.8rem;
        color: var(--gray);
        margin-top: 2px;
    }
    
    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        color: var(--primary);
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        background: var(--light);
        border: 1px solid var(--border);
    }
    
    .back-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateX(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .user-info {
        display: flex;
        align-items: center;
        gap: 1rem;
        background: var(--light);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        border: 1px solid var(--border);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .user-info:hover {
        background: var(--primary-light);
        color: white;
    }
    
    .user-info:hover .user-details h4,
    .user-info:hover .user-details p {
        color: white;
    }
    
    .user-avatar {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--secondary), var(--accent));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }
    
    .user-details h4 {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
        transition: color 0.3s ease;
    }
    
    .user-details p {
        font-size: 0.75rem;
        color: var(--gray);
        transition: color 0.3s ease;
    }
    
    .user-dropdown {
        color: var(--gray);
        transition: transform 0.3s ease;
    }
    
    .user-info:hover .user-dropdown {
        color: white;
        transform: rotate(180deg);
    }
    
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 1rem;
        }
        
        .nav-left, .nav-center, .nav-right {
            width: 100%;
            justify-content: center;
        }
        
        .nav-left {
            order: 1;
        }
        
        .nav-center {
            order: 2;
        }
        
        .nav-right {
            order: 3;
        }
    }
</style>