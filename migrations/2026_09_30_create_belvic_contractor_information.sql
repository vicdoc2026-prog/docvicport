CREATE TABLE `belvic_contractor_profile` (
  `id` tinyint unsigned NOT NULL,
  `company_name` varchar(255) NOT NULL,
  `contractor_id` varchar(50) NOT NULL,
  `tin` varchar(50) NOT NULL DEFAULT '',
  `head_office_location` varchar(500) NOT NULL DEFAULT '',
  `telephone` varchar(100) NOT NULL DEFAULT '',
  `fax` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(255) NOT NULL DEFAULT '',
  `manager_name` varchar(255) NOT NULL DEFAULT '',
  `manager_designation` varchar(255) NOT NULL DEFAULT '',
  `manager_phone` varchar(100) NOT NULL DEFAULT '',
  `liaison_name` varchar(255) NOT NULL DEFAULT '',
  `liaison_designation` varchar(255) NOT NULL DEFAULT '',
  `liaison_phone` varchar(100) NOT NULL DEFAULT '',
  `pcab_firm_type` varchar(100) NOT NULL DEFAULT '',
  `pcab_license_number` varchar(100) NOT NULL DEFAULT '',
  `pcab_license_first_issue_date` date DEFAULT NULL,
  `pcab_license_valid_from` date DEFAULT NULL,
  `pcab_license_valid_to` date DEFAULT NULL,
  `pcab_registration_number` varchar(100) NOT NULL DEFAULT '',
  `pcab_category` varchar(100) NOT NULL DEFAULT '',
  `pcab_registration_date` date DEFAULT NULL,
  `pcab_registration_valid_from` date DEFAULT NULL,
  `pcab_registration_valid_to` date DEFAULT NULL,
  `pcab_principal_classification` varchar(255) NOT NULL DEFAULT '',
  `pcab_other_classification` varchar(255) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_belvic_contractor_profile_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `belvic_contractor_profile` (
  `id`, `company_name`, `contractor_id`, `tin`, `head_office_location`, `telephone`, `fax`, `email`,
  `manager_name`, `manager_designation`, `manager_phone`, `liaison_name`, `liaison_designation`, `liaison_phone`,
  `pcab_firm_type`, `pcab_license_number`, `pcab_license_first_issue_date`, `pcab_license_valid_from`, `pcab_license_valid_to`,
  `pcab_registration_number`, `pcab_category`, `pcab_registration_date`, `pcab_registration_valid_from`, `pcab_registration_valid_to`,
  `pcab_principal_classification`, `pcab_other_classification`
) VALUES (
  1, 'BELVIC ENTERPRISES & CONSTRUCTION', '25337', '126-635-857-000', 'Lower Taway, Ipil, Zamboanga del Sur IX', '(062) 3332-469', '(062) 3332-212', 'aliface01@yahoo.com',
  'Eufemio D. Javier, Jr.', 'General Manager', '(062) 3332-469', 'Arnold S. Bustillo', 'Liaison Officer', '(062) 3332-626',
  'Sole Proprietorship', '25337', '1998-08-14', '2023-07-01', '2026-08-14',
  '1873-2024', 'B', '2024-05-09', '2024-05-09', '2027-08-11',
  'General Building', 'General Engineering'
);

CREATE TABLE `belvic_contractor_certificates` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `certificate_number` varchar(255) DEFAULT NULL,
  `details` varchar(500) DEFAULT NULL,
  `valid_from` date DEFAULT NULL,
  `valid_to` date DEFAULT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_belvic_certificates_active` (`archived_at`, `sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `belvic_contractor_certificates` (`name`, `certificate_number`, `details`, `valid_from`, `valid_to`, `sort_order`) VALUES
  ('PCAB Contractor License', '25337', 'Sole Proprietorship · Category B', '2023-07-01', '2026-08-14', 10),
  ('DPWH Tax Clearance Certificate', '15-93B-06-10-RO620-2026-M', 'Tax Clearance Certificate', '2026-06-10', '2027-06-10', 20),
  ('Business / Mayor’s Permit', 'BP-2026-0437', 'Business permit', '2026-01-19', '2026-12-31', 30),
  ('DTI Business Name Registration', '6086930', 'Certificate of Business Name Registration', '2024-07-16', '2029-07-16', 40),
  ('PhilGEPS Registration', '200708-16107-834619475', 'Certificate of PhilGEPS Registration', '2026-02-12', '2027-02-12', 50),
  ('ISO Certificate', NULL, NULL, NULL, NULL, 60);

CREATE TABLE `belvic_contractor_classifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `project_kind` varchar(500) NOT NULL,
  `size_range` varchar(100) NOT NULL,
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_belvic_classifications_active` (`archived_at`, `sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `belvic_contractor_classifications` (`project_kind`, `size_range`, `sort_order`) VALUES
  ('Building and Industrial Plant', 'Small B', 10),
  ('Dam, Reservoir and Tunneling', 'Small B', 20),
  ('Irrigation and Flood Control', 'Small B', 30),
  ('Park, Playground or Recreational Work', 'Small B', 40),
  ('Road, Highway Pavement and Railways, Airport Horizontal Structures and Bridges', 'Medium A', 50),
  ('Water Supply', 'Small B', 60);