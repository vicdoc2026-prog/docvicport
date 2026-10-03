// Album Gallery Functionality
document.addEventListener('DOMContentLoaded', function() {
    // File input display
    const fileInput = document.getElementById('imageFile');
    const fileName = document.querySelector('.file-name');
    
    if (fileInput) {
        fileInput.addEventListener('change', function() {
            fileName.textContent = this.files[0] ? this.files[0].name : 'No file chosen';
        });
    }
    
    // Filter functionality
    const filterButtons = document.querySelectorAll('.filter-btn');
    const galleryCards = document.querySelectorAll('.gallery-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const filter = this.getAttribute('data-filter');
            
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter cards
            galleryCards.forEach(card => {
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'recent') {
                    const cardDate = new Date(card.getAttribute('data-date'));
                    const sevenDaysAgo = new Date();
                    sevenDaysAgo.setDate(sevenDaysAgo.getDate() - 7);
                    
                    card.style.display = cardDate >= sevenDaysAgo ? 'block' : 'none';
                }
            });
        });
    });
});

// Lightbox Functions
let currentImageIndex = 0;
let imageArray = [];

function openLightbox(imageSrc, title, description, date) {
    // Get all gallery images
    const galleryImages = document.querySelectorAll('.gallery-card img');
    imageArray = Array.from(galleryImages).map(img => ({
        src: img.src,
        title: img.alt,
        description: img.closest('.gallery-card').querySelector('.card-description').textContent,
        date: img.closest('.gallery-card').querySelector('.upload-date').textContent.replace('📅 ', '')
    }));
    
    // Find current index
    currentImageIndex = imageArray.findIndex(img => img.src.includes(imageSrc.split('/').pop()));
    
    // Update lightbox content
    document.getElementById('lightboxImg').src = imageSrc;
    document.getElementById('lightboxTitle').textContent = title;
    document.getElementById('lightboxDescription').textContent = description;
    document.getElementById('lightboxDate').textContent = date;
    
    // Show lightbox
    document.getElementById('lightboxModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Add keyboard navigation
    document.addEventListener('keydown', handleKeyboardNavigation);
}

function closeLightbox() {
    document.getElementById('lightboxModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    document.removeEventListener('keydown', handleKeyboardNavigation);
}

function handleKeyboardNavigation(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    } else if (e.key === 'ArrowRight') {
        navigateLightbox(1);
    } else if (e.key === 'ArrowLeft') {
        navigateLightbox(-1);
    }
}

function navigateLightbox(direction) {
    currentImageIndex += direction;
    
    if (currentImageIndex < 0) {
        currentImageIndex = imageArray.length - 1;
    } else if (currentImageIndex >= imageArray.length) {
        currentImageIndex = 0;
    }
    
    const image = imageArray[currentImageIndex];
    document.getElementById('lightboxImg').src = image.src;
    document.getElementById('lightboxTitle').textContent = image.title;
    document.getElementById('lightboxDescription').textContent = image.description;
    document.getElementById('lightboxDate').textContent = image.date;
}

// Close lightbox when clicking outside content
document.getElementById('lightboxModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLightbox();
    }
});

// Form validation
document.querySelector('.upload-form')?.addEventListener('submit', function(e) {
    const fileInput = this.querySelector('input[type="file"]');
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (fileInput.files.length > 0 && fileInput.files[0].size > maxSize) {
        e.preventDefault();
        alert('File size exceeds 5MB limit. Please choose a smaller file.');
        fileInput.value = '';
    }
});