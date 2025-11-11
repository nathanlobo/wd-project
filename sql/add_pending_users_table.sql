USE `codegram`;

-- Holds signups until email verification succeeds
CREATE TABLE IF NOT EXISTS `pending_users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(100) DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pending_username` (`username`),
  UNIQUE KEY `uniq_pending_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
