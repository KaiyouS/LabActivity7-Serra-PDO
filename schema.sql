-- ========================================================
-- Lab Activity 7: Relational Databases (RDBMS) & PDO Core
-- Database Schema for: blog_site
-- ========================================================

CREATE DATABASE IF NOT EXISTS `blog_site`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `blog_site`;

-- 1. Users Table
-- PK: id (AUTO_INCREMENT)
-- UNIQUE: email
CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Posts Table (1:N relationship with users)
-- PK: id (AUTO_INCREMENT)
-- FK: user_id -> users(id) ON DELETE CASCADE
CREATE TABLE IF NOT EXISTS `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_posts_user` (`user_id`),
  CONSTRAINT `fk_posts_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Comments Table (Pivot / Junction Table connecting posts and users)
-- PK: id (AUTO_INCREMENT)
-- FK: post_id -> posts(id) ON DELETE CASCADE
-- FK: user_id -> users(id) ON DELETE CASCADE
CREATE TABLE IF NOT EXISTS `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_comments_post` (`post_id`),
  KEY `fk_comments_user` (`user_id`),
  CONSTRAINT `fk_comments_post` FOREIGN KEY (`post_id`) 
    REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) 
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
