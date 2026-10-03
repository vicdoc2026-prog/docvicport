CREATE TABLE `management_projects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department` varchar(255) NOT NULL,
  `contract_id` varchar(100) NOT NULL,
  `contract_name` text NOT NULL,
  `location` text NOT NULL,
  `status` enum('Planning','Active','On Hold','Completed') NOT NULL DEFAULT 'Planning',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_management_projects_contract_id` (`contract_id`),
  KEY `idx_management_projects_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `management_projects` (`department`, `contract_id`, `contract_name`, `location`, `status`) VALUES
('Department of Public Works and Highways', '25JO0072', 'Rehabilitation/Reconstruction/Upgrading of Damaged paved roads - Primary Roads - Maharlika Highway (Lanao-Pagadian-Zamboanga City Rd)', 'Zamboanga Sibugay 2nd, K1846+858 - K1848 + 000, K1846 + 352 - K1849 +407, K1849 + 607 - K1849 + 735, NET LENGTH: 1,316.00 LN.M./2.632 LANE KM', 'Active');