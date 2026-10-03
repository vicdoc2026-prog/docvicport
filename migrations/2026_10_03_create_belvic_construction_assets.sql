CREATE TABLE IF NOT EXISTS belvic_construction_assets (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Active',
    image_path VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_belvic_construction_assets_serial (serial_number),
    KEY idx_belvic_construction_assets_category (category),
    KEY idx_belvic_construction_assets_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO belvic_construction_assets
    (name, category, serial_number, status, image_path)
VALUES
    ('10 WHEELER DUMP TRUCK (DT-5)', 'Dump Truck', 'D8AYJ02614', 'Active', 'image_assets/DT5.jpg'),
    ('10 WHEELER DUMP TRUCK (DT-7)', 'Dump Truck', '8DC9254037', 'Maintenance', 'image_assets/DT7.jpg'),
    ('10 WHEELER DUMP TRUCK (DT-8)', 'Dump Truck', 'D8AYM056468', 'Repair Needed', 'image_assets/DT8.jpg')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    category = VALUES(category),
    status = VALUES(status),
    image_path = VALUES(image_path);
