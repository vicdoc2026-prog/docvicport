ALTER TABLE `pm_financial_records`
  MODIFY COLUMN `current_assets` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `total_assets` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `current_liabilities` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `total_liabilities` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `total_liabilities_and_owners_equity` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `present_net_worth` decimal(18,2) DEFAULT NULL,
  MODIFY COLUMN `gross_annual_turnover_construction` decimal(18,2) DEFAULT NULL;