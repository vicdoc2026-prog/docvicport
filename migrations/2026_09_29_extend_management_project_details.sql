ALTER TABLE `management_projects`
  CHANGE COLUMN `contract_name` `contract_description` varchar(5000) NOT NULL,
  ADD COLUMN `owner` varchar(255) NOT NULL DEFAULT '' AFTER `contract_description`,
  ADD COLUMN `participation_percentage` decimal(5,2) NOT NULL DEFAULT 0.00 AFTER `owner`,
  ADD COLUMN `contract_date_started` date DEFAULT NULL AFTER `participation_percentage`,
  ADD COLUMN `contract_date_completed` date DEFAULT NULL AFTER `contract_date_started`,
  ADD COLUMN `major_categories_of_work` varchar(500) NOT NULL DEFAULT '' AFTER `contract_date_completed`,
  ADD COLUMN `dimension_km` varchar(1000) DEFAULT NULL AFTER `major_categories_of_work`,
  ADD COLUMN `total_as_built_cost_per_major_work_category` int(11) DEFAULT NULL AFTER `dimension_km`,
  ADD CONSTRAINT `chk_management_projects_participation_percentage`
    CHECK (`participation_percentage` >= 0 AND `participation_percentage` <= 100);