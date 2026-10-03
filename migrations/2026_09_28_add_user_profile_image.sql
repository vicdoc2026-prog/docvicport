ALTER TABLE `users`
  ADD COLUMN `profile_image` varchar(255) DEFAULT NULL AFTER `full_name`;

UPDATE `users`
SET `profile_image` = 'STII_pages/images/ULOL4_40.jpg'
WHERE `role` = 'president' AND `profile_image` IS NULL;