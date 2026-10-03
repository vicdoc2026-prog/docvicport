<?php 
require_once 'config/check-session.php';
require_once 'config/conn.php';

$username = $_SESSION['username'] ?? '';
$full_name = $_SESSION['full_name'] ?? '';
$role = $_SESSION['role'] ?? '';

$query = "SELECT * FROM quarry_images WHERE quarry_name = 'Damit Quarry' ORDER BY upload_date DESC";
$result = mysqli_query($conn, $query);
$total_images = mysqli_num_rows($result);

$page_title = "Damit Quarry Album";
include '../bar/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> | Quarry Management System</title>
    <link rel="stylesheet" href="css/album.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="layout-container">
        <!-- Header -->
        <header class="main-header">
            <div class="header-content">
                <h1 class="page-title">
                    <i class="fas fa-mountain"></i>
                    Damit Quarry Visual Gallery
                </h1>
                <p class="page-subtitle">Documenting progress and operations through visual records</p>
                <div class="stats-badge">
                    <i class="fas fa-images"></i>
                    <span><?php echo $total_images; ?> Photos</span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Gallery Section -->
            <section class="gallery-section">
                <?php if ($total_images > 0): ?>
                    <div class="gallery-filters">
                        <div class="filter-controls">
                            <button class="filter-btn active" data-filter="all">All Photos</button>
                            <button class="filter-btn" data-filter="recent">Recent</button>
                        </div>
                        <div class="gallery-meta">
                            <span class="image-count">
                                <i class="fas fa-image"></i> Showing <?php echo $total_images; ?> images
                            </span>
                        </div>
                    </div>

                    <div class="gallery-grid" id="galleryGrid">
                        <?php while ($row = mysqli_fetch_assoc($result)): 
                            $upload_date = date('M d, Y', strtotime($row['upload_date']));
                            $description = !empty($row['description']) ? $row['description'] : 'No description provided';
                        ?>
                        <div class="gallery-card" 
                             data-date="<?php echo $row['upload_date']; ?>"
                             onclick="openLightbox('<?php echo htmlspecialchars($row['image_path']); ?>', 
                                      '<?php echo htmlspecialchars($row['title']); ?>',
                                      '<?php echo htmlspecialchars($description); ?>',
                                      '<?php echo $upload_date; ?>')">
                            <div class="card-image">
                                <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($row['title']); ?>"
                                     loading="lazy">
                                <div class="card-overlay">
                                    <span class="view-btn">
                                        <i class="fas fa-expand"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="card-content">
                                <h3 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                                <p class="card-description"><?php echo htmlspecialchars($description); ?></p>
                                <div class="card-footer">
                                    <span class="upload-date">
                                        <i class="far fa-calendar"></i> <?php echo $upload_date; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h2>No Images Available</h2>
                        <p>Start building the visual archive of Damit Quarry</p>
                        <?php if (in_array($role, ['admin', 'belvic_admin'], true)): ?>
                            <a href="#uploadSection" class="btn-primary">
                                <i class="fas fa-cloud-upload-alt"></i> Upload First Image
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Upload Section (Admin Only) -->
            <?php if (in_array($role, ['admin', 'belvic_admin'], true)): ?>
            <section class="upload-section" id="uploadSection">
                <div class="section-header">
                    <h2><i class="fas fa-cloud-upload-alt"></i> Upload New Image</h2>
                    <p>Add new visual documentation to the gallery</p>
                </div>
                
                <div class="upload-card">
                    <form class="upload-form" action="upload_quarry_image.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="quarry_name" value="Damit Quarry">
                        
                        <div class="form-group">
                            <label for="imageTitle">
                                <i class="fas fa-heading"></i> Image Title
                            </label>
                            <input type="text" id="imageTitle" name="title" 
                                   placeholder="Enter descriptive title" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="imageDescription">
                                <i class="fas fa-align-left"></i> Description
                            </label>
                            <textarea id="imageDescription" name="description" 
                                      placeholder="Add context or details about this image"
                                      rows="3"></textarea>
                        </div>
                        
                        <div class="form-group file-upload">
                            <label for="imageFile">
                                <i class="fas fa-file-image"></i> Select Image
                            </label>
                            <div class="file-input-wrapper">
                                <input type="file" id="imageFile" name="image" 
                                       accept="image/*" required>
                                <div class="file-info">
                                    <span class="file-name">No file chosen</span>
                                    <span class="file-browse">Browse</span>
                                </div>
                            </div>
                            <p class="file-hint">Supported formats: JPG, PNG, GIF. Max size: 5MB</p>
                        </div>
                        
                        <div class="form-actions">
                            <button type="reset" class="btn-secondary">
                                <i class="fas fa-redo"></i> Clear
                            </button>
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-upload"></i> Upload Image
                            </button>
                        </div>
                    </form>
                </div>
            </section>
            <?php endif; ?>
        </main>

        <!-- Lightbox Modal -->
        <div class="lightbox-modal" id="lightboxModal">
            <div class="lightbox-content">
                <button class="close-lightbox" onclick="closeLightbox()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="lightbox-body">
                    <div class="lightbox-image">
                        <img id="lightboxImg" src="" alt="">
                    </div>
                    <div class="lightbox-info">
                        <h3 id="lightboxTitle"></h3>
                        <p id="lightboxDescription"></p>
                        <div class="lightbox-meta">
                            <span class="meta-item">
                                <i class="far fa-calendar"></i>
                                <span id="lightboxDate"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/album.js"></script>
</body>
</html>