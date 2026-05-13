-- ============================================================
--  BZU Online Grading System — Redesigned Database Schema
--  Database Management Techniques Applied:
--  1. Normalization (3NF) — no repeating groups, no partial/transitive deps
--  2. Referential Integrity — foreign keys with ON DELETE/UPDATE rules
--  3. Proper Data Types — INT for numbers, VARCHAR with correct sizes
--  4. Indexes — on frequently-searched columns (roll_no, session_year)
--  5. Computed Column (total_marks) — auto-calculated via TRIGGER
--  6. Single unified `marks` table replaces 4 redundant session tables
--  7. Passwords hashed (SHA2-256) — not stored plain-text
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS `myproject`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_general_ci;

USE `myproject`;

-- ============================================================
-- TABLE: admins
-- Stores admin accounts. Password stored as SHA2-256 hash.
-- ============================================================
CREATE TABLE IF NOT EXISTS `admins` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(100)    NOT NULL,
  `email`      VARCHAR(255)    NOT NULL,
  `password`   VARCHAR(64)     NOT NULL COMMENT 'SHA2-256 hex hash',
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Default admin: password is SHA2('admin123', 256)
INSERT INTO `admins` (`name`, `email`, `password`) VALUES
('aqib', 'aqib@gmail.com', SHA2('123', 256));

-- ============================================================
-- TABLE: teachers
-- Stores teacher profiles (one row per teacher).
-- ============================================================
CREATE TABLE IF NOT EXISTS `teachers` (
  `id`         INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `name`       VARCHAR(150)    NOT NULL,
  `email`      VARCHAR(255)    NOT NULL,
  `created_at` DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_teacher_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teachers` (`name`, `email`) VALUES
('Dr.Shahid', 'shahid@bzu.edu.pk'),
('Dr.Ali',    'ali@bzu.edu.pk');

-- ============================================================
-- TABLE: sessions
-- Lookup table for academic sessions (e.g. 2023-27).
-- Centralises session management — no more magic string maps.
-- ============================================================
CREATE TABLE IF NOT EXISTS `sessions` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `session_year` VARCHAR(20)   NOT NULL COMMENT 'e.g. 2023-27',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_session_year` (`session_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sessions` (`session_year`) VALUES
('2021-25'), ('2022-26'), ('2023-27'), ('2024-28');

-- ============================================================
-- TABLE: subjects
-- Master list of subjects / courses.
-- ============================================================
CREATE TABLE IF NOT EXISTS `subjects` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(150)  NOT NULL,
  `course_code` VARCHAR(30)   NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_course_code` (`course_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `subjects` (`name`, `course_code`) VALUES
('DBMS',         'CPE-101'),
('Mathematics',  'CPE-102');

-- ============================================================
-- TABLE: teacher_assignments
-- Which teacher teaches which subject in which session.
-- Replaces the fragile `assign_subjects` table.
-- ============================================================
CREATE TABLE IF NOT EXISTS `teacher_assignments` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `teacher_id`  INT UNSIGNED  NOT NULL,
  `subject_id`  INT UNSIGNED  NOT NULL,
  `session_id`  INT UNSIGNED  NOT NULL,
  `assigned_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_assignment` (`teacher_id`, `subject_id`, `session_id`),
  CONSTRAINT `fk_ta_teacher`  FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_subject`  FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ta_session`  FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `teacher_assignments` (`teacher_id`, `subject_id`, `session_id`) VALUES
(1, 1, 3),  -- Dr.Shahid → DBMS → 2023-27
(2, 2, 4);  -- Dr.Ali    → Math → 2024-28

-- ============================================================
-- TABLE: students
-- Student master table.
-- ============================================================
CREATE TABLE IF NOT EXISTS `students` (
  `id`         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `roll_no`    VARCHAR(30)   NOT NULL,
  `name`       VARCHAR(150)  NOT NULL,
  `session_id` INT UNSIGNED  NOT NULL,
  `created_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roll_session` (`roll_no`, `session_id`),
  KEY `idx_name` (`name`),
  CONSTRAINT `fk_student_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TABLE: marks
-- SINGLE unified marks table replacing session21/22/23/24.
-- DB Technique: instead of one table per cohort (bad design),
-- use a discriminator column `session_id` + proper indexes.
-- total_marks is kept as a stored column (auto-updated by TRIGGER).
-- ============================================================
CREATE TABLE IF NOT EXISTS `marks` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `student_id`   INT UNSIGNED  NOT NULL,
  `subject_id`   INT UNSIGNED  NOT NULL,
  `mid_marks`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'max 30',
  `sessional`    TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'max 20',
  `final_marks`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'max 50',
  `total_marks`  TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'auto-calculated by trigger',
  `recorded_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_student_subject` (`student_id`, `subject_id`),
  KEY `idx_subject`  (`subject_id`),
  CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_marks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`  (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_mid`   CHECK (`mid_marks`   <= 30),
  CONSTRAINT `chk_ses`   CHECK (`sessional`   <= 20),
  CONSTRAINT `chk_fin`   CHECK (`final_marks` <= 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- TRIGGER: auto-compute total_marks on INSERT and UPDATE
-- DB Technique: business logic enforced at the DB layer,
-- not just in PHP, so no PHP bug can create inconsistency.
-- ============================================================
DROP TRIGGER IF EXISTS `trg_marks_total_insert`;
DELIMITER $$
CREATE TRIGGER `trg_marks_total_insert`
BEFORE INSERT ON `marks`
FOR EACH ROW
BEGIN
  SET NEW.total_marks = NEW.mid_marks + NEW.sessional + NEW.final_marks;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS `trg_marks_total_update`;
DELIMITER $$
CREATE TRIGGER `trg_marks_total_update`
BEFORE UPDATE ON `marks`
FOR EACH ROW
BEGIN
  SET NEW.total_marks = NEW.mid_marks + NEW.sessional + NEW.final_marks;
END$$
DELIMITER ;

COMMIT;
