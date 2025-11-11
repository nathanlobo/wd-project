USE `codegram`;

-- Codeas (Reels) table - stores video posts
CREATE TABLE IF NOT EXISTS `codeas` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `video_path` VARCHAR(500) NOT NULL,
  `caption` TEXT,
  `likes_count` INT UNSIGNED DEFAULT 0,
  `comments_count` INT UNSIGNED DEFAULT 0,
  `shares_count` INT UNSIGNED DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  INDEX (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Codeas likes table
CREATE TABLE IF NOT EXISTS `codeas_likes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codea_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_like` (`codea_id`, `user_id`),
  INDEX (`codea_id`),
  INDEX (`user_id`),
  FOREIGN KEY (`codea_id`) REFERENCES `codeas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Codeas comments table
CREATE TABLE IF NOT EXISTS `codeas_comments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codea_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`codea_id`),
  INDEX (`user_id`),
  FOREIGN KEY (`codea_id`) REFERENCES `codeas`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User follows table (if not exists) - for follow functionality
CREATE TABLE IF NOT EXISTS `follows` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `follower_id` INT UNSIGNED NOT NULL,
  `following_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_follow` (`follower_id`, `following_id`),
  INDEX (`follower_id`),
  INDEX (`following_id`),
  FOREIGN KEY (`follower_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`following_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
