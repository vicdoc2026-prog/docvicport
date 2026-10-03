<?php

// Include session check - this will redirect if not logged in
require_once '../electric/config/check-session.php';
require_once '../electric/config/conn.php';

// Get user info from session
$username = $_SESSION['username'];
$full_name = $_SESSION['full_name'];
$role = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1"/>
  <title>ECOLIFT Maintenance Reports | STII Services Portal</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    }
    .header-gradient {
      background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
    }
    .card-hover {
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      border: 1px solid #e2e8f0;
      border-radius: 0.75rem;
      overflow: hidden;
      background: white;
    }
    .card-hover:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 5px 10px -5px rgba(0, 0, 0, 0.04);
    }
    .image-container {
      position: relative;
      border-radius: 0.5rem;
      overflow: hidden;
      background: white;
    }
    .image-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(0, 0, 0, 0.7);
      color: white;
      padding: 0.75rem;
      transform: translateY(100%);
      transition: transform 0.3s ease;
    }
    .image-container:hover .image-overlay {
      transform: translateY(0);
    }
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.9);
      overflow: auto;
    }
    .modal-content {
      margin: auto;
      display: block;
      max-width: 90%;
      max-height: 90%;
      margin-top: 2%;
      position: relative;
    }
    .close {
      position: absolute;
      top: 15px;
      right: 35px;
      color: #f1f1f1;
      font-size: 40px;
      font-weight: bold;
      cursor: pointer;
      z-index: 1001;
    }
    .modal-nav-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(255, 255, 255, 0.2);
      color: white;
      padding: 1rem;
      border: none;
      cursor: pointer;
      border-radius: 50%;
      transition: all 0.3s ease;
      font-size: 1.5rem;
      width: 60px;
      height: 60px;
      display: flex;
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(10px);
      z-index: 1001;
    }
    .modal-nav-btn:hover {
      background: rgba(255, 255, 255, 0.3);
      transform: translateY(-50%) scale(1.1);
    }
    .modal-nav-btn.prev {
      left: 30px;
    }
    .modal-nav-btn.next {
      right: 30px;
    }
    .modal-counter {
      position: absolute;
      bottom: 20px;
      left: 50%;
      transform: translateX(-50%);
      color: white;
      background: rgba(0, 0, 0, 0.7);
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 0.9rem;
      z-index: 1001;
    }
    .logo-container {
      background: white;
      border-radius: 0.5rem;
      padding: 1rem;
      box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
    .carousel {
      position: relative;
      width: 100%;
      height: 200px;
    }
    .carousel img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
      border-radius: 0.5rem;
    }
    .carousel img.active {
      display: block;
    }
    .carousel-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: rgba(0, 0, 0, 0.5);
      color: white;
      padding: 0.5rem;
      border: none;
      cursor: pointer;
      border-radius: 0.25rem;
      transition: background 0.3s ease;
    }
    .carousel-btn:hover {
      background: rgba(0, 0, 0, 0.7);
    }
    .carousel-btn.prev {
      left: 10px;
    }
    .carousel-btn.next {
      right: 10px;
    }
  </style>
</head>

<body class="min-h-screen p-4 md:p-8">
  <div class="max-w-screen-2xl mx-auto">
    <!-- Back button -->
    <div class="mb-6">
      <a href="../portal.php" class="inline-flex items-center text-gray-600 hover:text-gray-900 transition-colors duration-200 text-sm">
        <i class="fas fa-arrow-left mr-2"></i>
        <span>Back to Services</span>
      </a>
    </div>

    <!-- Header with company info -->
    <div class="header-gradient rounded-2xl p-6 md:p-8 text-white mb-8">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <div class="text-center md:text-left mb-4 md:mb-0">
          <h1 class="text-3xl md:text-4xl font-bold mb-2">ECOLIFT</h1>
          <p class="text-lg opacity-90">Elevator and Escalator Corporation</p>
          <p class="text-sm opacity-80 mt-1">ISO 9001:2015 Certified</p>
        </div>
        <div class="logo-container">
          <div class="text-blue-900 text-center">
            <i class="fas fa-building text-4xl mb-2"></i>
            <p class="font-bold text-lg">MAINTENANCE REPORTS</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Reports Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <!-- Report 1 -->
      <div class="card-hover">
        <div class="image-container">
          <div class="carousel">
            <img src="../images/Elevator_000.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 1" 
                 class="carousel-img active" 
                 onclick="openModal(0)">
            <img src="../images/Elevator_001.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 2" 
                 class="carousel-img" 
                 onclick="openModal(1)">
            <img src="../images/Elevator_002.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 3" 
                 class="carousel-img" 
                 onclick="openModal(2)">
            <img src="../images/Elevator_003.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 4" 
                 class="carousel-img" 
                 onclick="openModal(3)">
            <img src="../images/Elevator_004.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 5" 
                 class="carousel-img" 
                 onclick="openModal(4)">
            <img src="../images/Elevator_005.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 6" 
                 class="carousel-img" 
                 onclick="openModal(5)">
            <img src="../images/Elevator_006.jpg" 
                 alt="ECOLIFT Maintenance Report - Image 7" 
                 class="carousel-img" 
                 onclick="openModal(6)">
            <button class="carousel-btn prev" onclick="changeImage(-1)"><i class="fas fa-chevron-left"></i></button>
            <button class="carousel-btn next" onclick="changeImage(1)"><i class="fas fa-chevron-right"></i></button>
          </div>
          <div class="image-overlay">
            <p class="text-sm font-medium">Scanned Document</p>
            <p class="text-xs">September 27, 2025 | Elevator Unit 01</p>
          </div>
        </div>
        <div class="p-4 bg-white">
          <div class="flex justify-between items-center mb-2">
            <span class="text-sm font-medium text-gray-700">Status:</span>
            <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">Active</span>
          </div>
        </div>
      </div>

      <!-- Empty state if no images -->
      <div id="empty-state" class="hidden text-center py-12">
        <i class="fas fa-file-alt text-5xl text-gray-300 mb-4"></i>
        <h3 class="text-xl font-medium text-gray-500">No maintenance reports available</h3>
        <p class="text-gray-400 mt-2">ECOLIFT maintenance records will appear here once uploaded.</p>
      </div>
    </div>

    <!-- Modal for enlarged image view -->
    <div id="imageModal" class="modal">
      <span class="close" onclick="closeModal()">&times;</span>
      <button class="modal-nav-btn prev" onclick="changeModalImage(-1)">
        <i class="fas fa-chevron-left"></i>
      </button>
      <button class="modal-nav-btn next" onclick="changeModalImage(1)">
        <i class="fas fa-chevron-right"></i>
      </button>
      <img class="modal-content" id="modalImage">
      <div class="modal-counter" id="modalCounter">1 / 7</div>
    </div>

    <script>
      // Carousel functionality
      let currentIndex = 0;
      let currentModalIndex = 0;
      const images = document.querySelectorAll('.carousel-img');
      const totalImages = images.length;

      // Array of image sources for modal navigation
      const imageSources = [
        '../images/Elevator_000.jpg',
        '../images/Elevator_001.jpg',
        '../images/Elevator_002.jpg',
        '../images/Elevator_003.jpg',
        '../images/Elevator_004.jpg',
        '../images/Elevator_005.jpg',
        '../images/Elevator_006.jpg'
      ];

      const imageAlts = [
        'ECOLIFT Maintenance Report - Image 1',
        'ECOLIFT Maintenance Report - Image 2',
        'ECOLIFT Maintenance Report - Image 3',
        'ECOLIFT Maintenance Report - Image 4',
        'ECOLIFT Maintenance Report - Image 5',
        'ECOLIFT Maintenance Report - Image 6',
        'ECOLIFT Maintenance Report - Image 7'
      ];

      function changeImage(direction) {
        images[currentIndex].classList.remove('active');
        currentIndex = (currentIndex + direction + totalImages) % totalImages;
        images[currentIndex].classList.add('active');
      }

      // Function to open modal with clicked image
      function openModal(imageIndex) {
        const modal = document.getElementById('imageModal');
        const modalImg = document.getElementById('modalImage');
        const counter = document.getElementById('modalCounter');
        
        currentModalIndex = imageIndex;
        modal.style.display = 'block';
        modalImg.src = imageSources[currentModalIndex];
        modalImg.alt = imageAlts[currentModalIndex];
        counter.textContent = `${currentModalIndex + 1} / ${imageSources.length}`;
      }

      // Function to change image in modal
      function changeModalImage(direction) {
        const modalImg = document.getElementById('modalImage');
        const counter = document.getElementById('modalCounter');
        
        currentModalIndex = (currentModalIndex + direction + imageSources.length) % imageSources.length;
        modalImg.src = imageSources[currentModalIndex];
        modalImg.alt = imageAlts[currentModalIndex];
        counter.textContent = `${currentModalIndex + 1} / ${imageSources.length}`;
      }

      // Function to close modal
      function closeModal() {
        document.getElementById('imageModal').style.display = 'none';
      }

      // Close modal when clicking outside the image
      window.onclick = function(event) {
        const modal = document.getElementById('imageModal');
        if (event.target === modal) {
          closeModal();
        }
      }

      // Keyboard navigation for modal
      document.addEventListener('keydown', function(event) {
        const modal = document.getElementById('imageModal');
        if (modal.style.display === 'block') {
          switch(event.key) {
            case 'ArrowLeft':
              changeModalImage(-1);
              break;
            case 'ArrowRight':
              changeModalImage(1);
              break;
            case 'Escape':
              closeModal();
              break;
          }
        }
      });

      // Check if there are any images to display
      document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('.carousel-img');
        const emptyState = document.getElementById('empty-state');
        
        if (images.length === 0) {
          emptyState.classList.remove('hidden');
        }
      });
    </script>
  </div>
</body>
</html>