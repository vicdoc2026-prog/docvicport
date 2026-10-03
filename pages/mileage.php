<?php 

require_once 'config/check-session.php';
require_once 'config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];


?>
<?php include '../conn.php'; ?>
<?php include '../bar/header.php'; ?>
<?php include '../bar/navbar.php'; ?>
<?php include '../bar/footer.php'; 
?>