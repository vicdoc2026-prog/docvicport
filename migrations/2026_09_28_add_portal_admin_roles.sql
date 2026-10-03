-- Add portal-scoped administrator roles while preserving existing roles.
ALTER TABLE `users`
  MODIFY `role` enum('president','admin','stii_admin','belvic_admin') DEFAULT 'president';

-- Keep FarmManager as the STII-only administrator.
UPDATE `users`
SET `role` = 'stii_admin'
WHERE `username` = 'FarmManager@gmail.com';

-- Bootstrap the Belvic account with FarmManager's current password hash.
-- Change the Belvic password after its first login. Existing Belvic passwords
-- are preserved when this migration is rerun.
INSERT INTO `users` (`username`, `password`, `full_name`, `role`, `status`)
SELECT 'belvicOp@gmail.com', `password`, 'Belvic Operations Admin', 'belvic_admin', 'active'
FROM `users`
WHERE `username` = 'FarmManager@gmail.com'
ON DUPLICATE KEY UPDATE
  `full_name` = VALUES(`full_name`),
  `role` = VALUES(`role`),
  `status` = VALUES(`status`);