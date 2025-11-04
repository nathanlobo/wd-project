USE `codegram`;

-- Add email verification columns to users
ALTER TABLE `users`
  ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0 AFTER `profile_pic`,
  ADD COLUMN `email_verified_at` TIMESTAMP NULL DEFAULT NULL AFTER `email_verified`;

-- Codes table for email verification
CREATE TABLE IF NOT EXISTS `email_verification_codes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `code` VARCHAR(10) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`user_id`),
  INDEX (`code`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
