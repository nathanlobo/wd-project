-- ===============================================================
-- CODEGRAM - Complete Database Schema
-- Instagram Clone with Messaging, Notifications, and Reels
-- ===============================================================
-- 
-- This file contains the complete database schema for Codegram
-- Execute this file to set up all required tables and relationships
-- 
-- Last Updated: November 19, 2025
-- ===============================================================

-- ===============================================================
-- SECTION 1: DATABASE CREATION
-- Creates the codegram database with UTF-8 support
-- ===============================================================

CREATE DATABASE IF NOT EXISTS `codegram` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `codegram`;

-- ===============================================================
-- SECTION 2: USERS TABLE
-- Core user accounts with authentication and profile information
-- Stores: username, email, password hash, display name, profile picture, bio
-- ===============================================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(150) DEFAULT NULL,
  `bio` TEXT NULL DEFAULT NULL,
  `profile_pic` VARCHAR(255) DEFAULT NULL,
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ===============================================================
-- SECTION 3: PENDING USERS TABLE
-- Temporary storage for user signups pending email verification
-- Users are moved to the main users table after email verification
-- ===============================================================

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

-- ===============================================================
-- SECTION 4: EMAIL VERIFICATION
-- Stores temporary verification codes sent to users' emails
-- Codes expire after a set time period for security
-- ===============================================================

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

-- ===============================================================
-- SECTION 5: PASSWORD RESET TOKENS
-- Manages secure password reset tokens sent via email
-- Tokens are time-limited and single-use for security
-- ===============================================================

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `expires_at` TIMESTAMP NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE,
  INDEX (`token`),
  INDEX (`expires_at`)
);

-- ===============================================================
-- SECTION 6: POSTS TABLE
-- Main feed posts with images or videos
-- Supports captions and tracks media type for proper rendering
-- ===============================================================

CREATE TABLE IF NOT EXISTS `posts` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT UNSIGNED NOT NULL,
  `caption` TEXT,
  `media_path` VARCHAR(255) NOT NULL,
  `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
);

-- ===============================================================
-- SECTION 7: POST LIKES TABLE
-- Tracks which users liked which posts
-- Composite primary key prevents duplicate likes
-- ===============================================================

CREATE TABLE IF NOT EXISTS `likes` (
  `post_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`post_id`,`user_id`),
  FOREIGN KEY (`post_id`) REFERENCES posts(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
);

-- ===============================================================
-- SECTION 8: POST COMMENTS TABLE
-- User comments on feed posts
-- Each comment is linked to a specific post and user
-- ===============================================================

CREATE TABLE IF NOT EXISTS `comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `post_id` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `comment` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`post_id`) REFERENCES posts(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES users(`id`) ON DELETE CASCADE
);

-- ===============================================================
-- SECTION 9: CODEAS (REELS/SHORT VIDEOS)
-- Instagram Reels-style vertical video posts
-- Includes engagement metrics: likes, comments, shares
-- ===============================================================

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

-- ===============================================================
-- SECTION 10: CODEAS LIKES TABLE
-- Tracks likes on Codeas (video posts)
-- Unique constraint prevents duplicate likes
-- ===============================================================

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

-- ===============================================================
-- SECTION 11: CODEAS COMMENTS TABLE
-- User comments on Codeas video posts
-- Linked to specific video and commenting user
-- ===============================================================

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

-- ===============================================================
-- SECTION 12: FOLLOWS TABLE
-- Manages follower/following relationships between users
-- Enables feed personalization and social connections
-- ===============================================================

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

-- ===============================================================
-- SECTION 13: NOTIFICATIONS TABLE
-- System notifications for likes, comments, and follows
-- Types: 'like', 'comment', 'follow'
-- Tracks read status and links to relevant content via reference_id
-- ===============================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `from_user_id` INT UNSIGNED NOT NULL,
  `reference_id` INT UNSIGNED NULL,
  `message` TEXT NOT NULL,
  `read_at` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  INDEX (`read_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===============================================================
-- INSTALLATION COMPLETE
-- ===============================================================
-- All tables have been created successfully!
-- 
-- TABLES CREATED:
-- - users: User accounts and profiles
-- - pending_users: Unverified signups
-- - email_verification_codes: Email verification codes
-- - password_reset_tokens: Password reset tokens
-- - posts: Feed posts with images/videos
-- - likes: Post likes
-- - comments: Post comments
-- - codeas: Short video posts (Reels)
-- - codeas_likes: Codeas likes
-- - codeas_comments: Codeas comments
-- - follows: User follow relationships
-- - notifications: System notifications
-- 
-- Next steps:
-- 1. Configure your PHP database connection in includes/db.php
-- 2. Set up email configuration in includes/mail_config.php
-- 3. Create the required directories: Media/uploads, Media/profiles, Media/videos
-- 4. Start using Codegram!
-- ===============================================================
