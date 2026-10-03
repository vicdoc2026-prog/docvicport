CREATE TABLE `pm_financial_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `record_year` smallint(5) unsigned NOT NULL,
  `current_assets` decimal(18,2) DEFAULT NULL,
  `total_assets` decimal(18,2) DEFAULT NULL,
  `current_liabilities` decimal(18,2) DEFAULT NULL,
  `total_liabilities` decimal(18,2) DEFAULT NULL,
  `total_liabilities_and_owners_equity` decimal(18,2) DEFAULT NULL,
  `present_net_worth` decimal(18,2) DEFAULT NULL,
  `gross_annual_turnover_construction` decimal(18,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pm_financial_records_year` (`record_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;