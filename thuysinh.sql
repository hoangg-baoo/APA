-- =========================================
-- Create database
-- =========================================
CREATE DATABASE IF NOT EXISTS aquatic_plant_advisor
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE aquatic_plant_advisor;

-- =========================================
-- Drop tables if they already exist
-- (để chạy lại nhiều lần không bị lỗi)
-- =========================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS plant_images;
DROP TABLE IF EXISTS comments;
DROP TABLE IF EXISTS posts;
DROP TABLE IF EXISTS answers;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS plant_logs;
DROP TABLE IF EXISTS water_logs;
DROP TABLE IF EXISTS tank_plants;
DROP TABLE IF EXISTS tanks;
DROP TABLE IF EXISTS plants;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =========================================
-- 1) users
-- =========================================
CREATE TABLE `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user','expert','admin') NOT NULL DEFAULT 'user',
  `avatar` VARCHAR(255) NULL,
  `bio` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2) plants (master plant library)
-- =========================================
CREATE TABLE `plants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `ph_min` FLOAT NULL,
  `ph_max` FLOAT NULL,
  `temp_min` FLOAT NULL,
  `temp_max` FLOAT NULL,
  `light_level` ENUM('low','medium','high') NULL,
  `difficulty` ENUM('easy','medium','hard') NULL,
  `image_sample` VARCHAR(255) NULL,
  `care_guide` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 3) tanks
-- =========================================
CREATE TABLE `tanks` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `size` VARCHAR(50) NULL,
  `volume_liters` FLOAT NULL,
  `substrate` VARCHAR(255) NULL,
  `light` VARCHAR(255) NULL,
  `co2` TINYINT(1) NOT NULL DEFAULT 0,
  `description` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tanks_user_id_index` (`user_id`),
  CONSTRAINT `tanks_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 4) tank_plants (plants currently in a tank)
-- =========================================
CREATE TABLE `tank_plants` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tank_id` BIGINT UNSIGNED NOT NULL,
  `plant_id` BIGINT UNSIGNED NOT NULL,
  `planted_at` DATE NULL,
  `note` TEXT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `tank_plants_tank_id_index` (`tank_id`),
  KEY `tank_plants_plant_id_index` (`plant_id`),
  UNIQUE KEY `tank_plants_unique` (`tank_id`,`plant_id`),
  CONSTRAINT `tank_plants_tank_id_foreign`
    FOREIGN KEY (`tank_id`) REFERENCES `tanks` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `tank_plants_plant_id_foreign`
    FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 5) water_logs (water parameter history per tank)
-- =========================================
CREATE TABLE `water_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tank_id` BIGINT UNSIGNED NOT NULL,
  `logged_at` DATETIME NOT NULL,
  `ph` FLOAT NULL,
  `temperature` FLOAT NULL,
  `no3` FLOAT NULL,
  `other_params` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `water_logs_tank_id_index` (`tank_id`),
  KEY `water_logs_logged_at_index` (`logged_at`),
  CONSTRAINT `water_logs_tank_id_foreign`
    FOREIGN KEY (`tank_id`) REFERENCES `tanks` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 6) plant_logs (optional diary per plant in tank)
-- =========================================
CREATE TABLE `plant_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tank_plant_id` BIGINT UNSIGNED NOT NULL,
  `logged_at` DATE NOT NULL,
  `height` FLOAT NULL,
  `status` VARCHAR(100) NULL,
  `note` TEXT NULL,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plant_logs_tank_plant_id_index` (`tank_plant_id`),
  CONSTRAINT `plant_logs_tank_plant_id_foreign`
    FOREIGN KEY (`tank_plant_id`) REFERENCES `tank_plants` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7) questions (Q&A)
-- =========================================
CREATE TABLE `questions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `tank_id` BIGINT UNSIGNED NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `status` ENUM('open','resolved') NOT NULL DEFAULT 'open',
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `questions_user_id_index` (`user_id`),
  KEY `questions_tank_id_index` (`tank_id`),
  CONSTRAINT `questions_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `questions_tank_id_foreign`
    FOREIGN KEY (`tank_id`) REFERENCES `tanks` (`id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8) answers (Q&A)
-- =========================================
CREATE TABLE `answers` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `is_accepted` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `answers_question_id_index` (`question_id`),
  KEY `answers_user_id_index` (`user_id`),
  CONSTRAINT `answers_question_id_foreign`
    FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `answers_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 9) posts (community articles)
-- =========================================
CREATE TABLE `posts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `image_path` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `posts_user_id_index` (`user_id`),
  CONSTRAINT `posts_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 10) comments (on posts)
-- =========================================
CREATE TABLE `comments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `content` TEXT NOT NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `comments_post_id_index` (`post_id`),
  KEY `comments_user_id_index` (`user_id`),
  CONSTRAINT `comments_post_id_foreign`
    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `comments_user_id_foreign`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 11) plant_images (for image retrieval)
-- =========================================
CREATE TABLE `plant_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plant_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `feature_vector` JSON NULL,
  `created_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `plant_images_plant_id_index` (`plant_id`),
  CONSTRAINT `plant_images_plant_id_foreign`
    FOREIGN KEY (`plant_id`) REFERENCES `plants` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `users` (`name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`)
VALUES (
  'Admin',
  'giathe0901@gmail.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'admin',
  'active',
  NOW(),
  NOW()
);
