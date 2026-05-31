-- Scrum Project Management Database
-- Created for Web Development 2 Eindopdracht
-- phpMyAdmin SQL Dump
-- version 5.1.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================
-- TABLE: USERS
-- ============================================
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `refresh_token` VARCHAR(500) NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`user_id`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: TEAMS
-- ============================================
CREATE TABLE IF NOT EXISTS `teams` (
  `team_id` INT(11) NOT NULL AUTO_INCREMENT,
  `team_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `owner_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`team_id`),
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  INDEX `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: TEAM_MEMBERS (Many-to-Many)
-- ============================================
CREATE TABLE IF NOT EXISTS `team_members` (
  `member_id` INT(11) NOT NULL AUTO_INCREMENT,
  `team_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `role` ENUM('admin','member') NOT NULL DEFAULT 'member',
  `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`member_id`),
  FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_team_user` (`team_id`, `user_id`),
  INDEX `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: TEAM_INVITATIONS
-- ============================================
CREATE TABLE IF NOT EXISTS `team_invitations` (
  `invitation_id` INT(11) NOT NULL AUTO_INCREMENT,
  `team_id` INT(11) NOT NULL,
  `invited_user_id` INT(11) NOT NULL,
  `invited_by` INT(11) NOT NULL,
  `status` ENUM('pending','accepted','declined') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`invitation_id`),
  FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`invited_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  INDEX `idx_invited_user` (`invited_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: PROJECTS
-- ============================================
CREATE TABLE IF NOT EXISTS `projects` (
  `project_id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `team_id` INT(11) NOT NULL,
  `owner_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`project_id`),
  FOREIGN KEY (`team_id`) REFERENCES `teams` (`team_id`) ON DELETE CASCADE,
  FOREIGN KEY (`owner_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  INDEX `idx_team_id` (`team_id`),
  INDEX `idx_owner_id` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: PROJECT_ROLES (Many-to-Many with Roles)
-- ============================================
CREATE TABLE IF NOT EXISTS `project_roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `project_id` INT(11) NOT NULL,
  `role` ENUM('owner', 'editor', 'viewer') NOT NULL DEFAULT 'viewer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_user_project_role` (`user_id`, `project_id`),
  INDEX `idx_project_id` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: SPRINTS
-- ============================================
CREATE TABLE IF NOT EXISTS `sprints` (
  `sprint_id` INT(11) NOT NULL AUTO_INCREMENT,
  `project_id` INT(11) NOT NULL,
  `sprint_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `status` ENUM('planned', 'active', 'completed') NOT NULL DEFAULT 'planned',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sprint_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: TASKS
-- ============================================
CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` INT(11) NOT NULL AUTO_INCREMENT,
  `sprint_id` INT(11) NULL,
  `project_id` INT(11) NOT NULL,
  `task_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `assigned_to` INT(11) NULL,
  `status` ENUM('backlog', 'todo', 'doing', 'done') NOT NULL DEFAULT 'backlog',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`task_id`),
  FOREIGN KEY (`sprint_id`) REFERENCES `sprints` (`sprint_id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  INDEX `idx_sprint_id` (`sprint_id`),
  INDEX `idx_project_id` (`project_id`),
  INDEX `idx_assigned_to` (`assigned_to`),
  INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TABLE: ACTIVITY_LOG
-- ============================================
CREATE TABLE IF NOT EXISTS `activity_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `project_id` INT(11) NULL,
  `entity_type` ENUM('task','sprint','project','member') NOT NULL,
  `action` ENUM('created','updated','deleted','started','completed','assigned','status_changed') NOT NULL,
  `entity_name` VARCHAR(255) NULL,
  `details` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
  INDEX `idx_project_created` (`project_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- EXAMPLE DATA
-- ============================================

-- Insert Users
INSERT INTO `users` (`user_id`, `firstname`, `lastname`, `email`, `password`, `refresh_token`) VALUES
(1, 'Luciano', 'Developer', 'luciano@example.com', '$2y$10$eb94iZvawdT2cZ5afTYGLO1SyoRUWzmoUNcBVS5A3Tvs6WiNs5NMS', NULL),
(2, 'Jan', 'TeamLead', 'jan@example.com', '$2y$10$eb94iZvawdT2cZ5afTYGLO1SyoRUWzmoUNcBVS5A3Tvs6WiNs5NMS', NULL),
(3, 'Maria', 'Designer', 'maria@example.com', '$2y$10$eb94iZvawdT2cZ5afTYGLO1SyoRUWzmoUNcBVS5A3Tvs6WiNs5NMS', NULL);

-- Insert Teams
INSERT INTO `teams` (`team_id`, `team_name`, `description`, `owner_id`) VALUES
(1, 'Development Team', 'Main development team for Scrum Project Manager', 1);

-- Insert Team Members
INSERT INTO `team_members` (`team_id`, `user_id`, `role`) VALUES
(1, 1, 'admin'),
(1, 2, 'member'),
(1, 3, 'member');

-- Insert Projects
INSERT INTO `projects` (`project_id`, `project_name`, `description`, `team_id`, `owner_id`) VALUES
(1, 'Scrum Manager MVP', 'Minimal Viable Product for Scrum Project Management', 1, 1);

-- Insert Project Roles
INSERT INTO `project_roles` (`user_id`, `project_id`, `role`) VALUES
(1, 1, 'owner'),
(2, 1, 'editor'),
(3, 1, 'viewer');

-- Insert Sprints
INSERT INTO `sprints` (`sprint_id`, `project_id`, `sprint_name`, `description`, `start_date`, `end_date`, `status`) VALUES
(1, 1, 'Sprint 1', 'Initial MVP features', '2024-04-08', '2024-04-19', 'active'),
(2, 1, 'Sprint 2', 'Additional features', '2024-04-22', '2024-05-03', 'planned');

-- Insert Tasks
INSERT INTO `tasks` (`sprint_id`, `project_id`, `task_name`, `description`, `assigned_to`, `status`) VALUES
(1, 1, 'Setup Backend API', 'Create all REST endpoints', 1, 'doing'),
(1, 1, 'Create Database Schema', 'Design and implement database', 1, 'done'),
(1, 1, 'Build Authentication', 'JWT login and registration', 1, 'todo'),
(1, 1, 'Frontend Navigation', 'Create main navigation structure', 3, 'backlog'),
(2, 1, 'Filtering & Pagination', 'Add to all list endpoints', 1, 'backlog'),
(2, 1, 'Dashboard Statistics', 'Create progress dashboard', 2, 'backlog');

-- ============================================
-- AUTO INCREMENT SETTINGS
-- ============================================
ALTER TABLE `users` AUTO_INCREMENT = 4;
ALTER TABLE `teams` AUTO_INCREMENT = 2;
ALTER TABLE `team_members` AUTO_INCREMENT = 4;
ALTER TABLE `projects` AUTO_INCREMENT = 2;
ALTER TABLE `project_roles` AUTO_INCREMENT = 4;
ALTER TABLE `sprints` AUTO_INCREMENT = 3;
ALTER TABLE `tasks` AUTO_INCREMENT = 7;

COMMIT;
