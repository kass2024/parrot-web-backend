/* ===========================================================================
   Parrot Canada Visa Consultant
   Manual schema for cPanel / shared hosting where the DB user can't auto-run
   CREATE TABLE on the fly.

   How to use in cPanel:
   1. cPanel  ->  phpMyAdmin
   2. Pick the CMS database in the left sidebar:  visaeofi_web
   3. Open the SQL tab and paste PART A below, click Go.
   4. Pick the MIS database in the left sidebar: visaeofi_mis_parrot
      (or whatever prefix cPanel gave you — usually <account>_mis_parrot)
   5. Open the SQL tab and paste PART B below, click Go.
   6. (Optional) Confirm the menu was seeded by browsing the menu_items table
      in visaeofi_web — you should see "Eligible Programs for Study Canada
      Loan for Professional Courses" at /eligible-programs-canada-loan.

   Notes:
   - CREATE TABLE IF NOT EXISTS + ADD COLUMN IF NOT EXISTS make this script
     safe to re-run any number of times.
   - The DB_USER in your backend .env MUST be granted ALL PRIVILEGES on BOTH
     databases (visaeofi_web AND visaeofi_mis_parrot) so the cross-DB join
     in /api/eligible-programs works.
=========================================================================== */


/* =====================================================================
   PART A  —  Run inside the CMS database  (DB_NAME = visaeofi_web)
   =====================================================================
   Tables required for the backend CMS to function and for the new
   "Eligible Programs for Study Canada" feature.
===================================================================== */

/* ---- A.1  Admin users ---------------------------------------------- */
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT(11)                          NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)                      NOT NULL,
    `email`      VARCHAR(100)                     NOT NULL,
    `password`   VARCHAR(255)                     NOT NULL,
    `full_name`  VARCHAR(100)                     DEFAULT NULL,
    `role`       ENUM('admin','editor')           DEFAULT 'admin',
    `created_at` TIMESTAMP                        DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP                        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME                         DEFAULT NULL,
    `is_active`  TINYINT(1)                       DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_admin_username` (`username`),
    UNIQUE KEY `uniq_admin_email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- A.2  Website navigation menu ---------------------------------- */
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`          INT(11)                         NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(190)                    NOT NULL,
    `url`         VARCHAR(255)                    NOT NULL,
    `parent_id`   INT(11)                         DEFAULT 0,
    `order_index` INT(11)                         DEFAULT 0,
    `is_active`   TINYINT(1)                      DEFAULT 1,
    `icon_class`  VARCHAR(100)                    DEFAULT NULL,
    `target`      ENUM('_self','_blank')          DEFAULT '_self',
    `created_at`  TIMESTAMP                       DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP                       DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- A.3  Eligible Programs CMS overrides -------------------------- */
CREATE TABLE IF NOT EXISTS `eligible_programs_settings` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `brochure_slug`    VARCHAR(190) NOT NULL,
    `display_title`    VARCHAR(255) NULL,
    `display_subtitle` VARCHAR(255) NULL,
    `is_featured`      TINYINT(1)   DEFAULT 0,
    `is_hidden`        TINYINT(1)   DEFAULT 0,
    `position`         INT(11)      DEFAULT 0,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_eligible_brochure_slug` (`brochure_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- A.4  Other CMS tables (gallery, settings, etc.) --------------- */
CREATE TABLE IF NOT EXISTS `settings` (
    `id`            INT(11)                              NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100)                         NOT NULL,
    `setting_value` TEXT                                 DEFAULT NULL,
    `setting_type`  ENUM('text','textarea','image','file') DEFAULT 'text',
    `description`   VARCHAR(255)                         DEFAULT NULL,
    `updated_at`    TIMESTAMP                            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gallery_images` (
    `id`            INT(11)      NOT NULL AUTO_INCREMENT,
    `title`         VARCHAR(200) NOT NULL,
    `description`   TEXT         DEFAULT NULL,
    `image_url`     VARCHAR(255) NOT NULL,
    `thumbnail_url` VARCHAR(255) DEFAULT NULL,
    `order_index`   INT(11)      DEFAULT 0,
    `is_active`     TINYINT(1)   DEFAULT 1,
    `created_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- A.5  Seed the new website menu (skips if it already exists) --- */
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `is_active`, `icon_class`, `target`)
SELECT 'Eligible Programs for Study Canada Loan for Professional Courses',
       '/eligible-programs-canada-loan',
       0,
       COALESCE((SELECT MAX(order_index) FROM (SELECT order_index FROM menu_items WHERE parent_id = 0) AS t), 0) + 1,
       1,
       'GraduationCap',
       '_self'
WHERE NOT EXISTS (
    SELECT 1 FROM `menu_items` WHERE `url` = '/eligible-programs-canada-loan'
);

/* ---- A.6  Default admin (password: admin123 — change after login) -- */
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`)
SELECT 'admin', 'admin@parrotvisa.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'Administrator', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM `admin_users` WHERE `username` = 'admin'
);


/* =====================================================================
   PART B  —  Run inside the MIS database  (PARROT_MIS_DB = visaeofi_mis_parrot)
   =====================================================================
   Brings the brochure tables up to date with the new "university_id"
   feature and the "Done by" admin tracking on payment receipts.
===================================================================== */

/* ---- B.1  Marketing brochures core table --------------------------- */
CREATE TABLE IF NOT EXISTS `marketing_brochures` (
    `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `region_id`         INT UNSIGNED NULL,
    `university_id`     INT UNSIGNED NULL,
    `title`             VARCHAR(255) NOT NULL,
    `slug`              VARCHAR(190) NOT NULL,
    `description`       TEXT NULL,
    `pdf_filename`      VARCHAR(255) NOT NULL,
    `pdf_path`          VARCHAR(500) NOT NULL,
    `pdf_size_bytes`    INT UNSIGNED NOT NULL DEFAULT 0,
    `attach_pdf`        TINYINT(1) NOT NULL DEFAULT 1,
    `cover_image`       VARCHAR(500) NULL,
    `extracted_text`    LONGTEXT NULL,
    `html_content`      LONGTEXT NULL,
    `extraction_status` VARCHAR(32) NOT NULL DEFAULT 'pending',
    `view_count`        INT UNSIGNED NOT NULL DEFAULT 0,
    `share_count`       INT UNSIGNED NOT NULL DEFAULT 0,
    `is_active`         TINYINT(1) NOT NULL DEFAULT 1,
    `created_by`        INT UNSIGNED NULL,
    `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_brochure_slug`       (`slug`),
    KEY        `idx_brochure_region`      (`region_id`),
    KEY        `idx_brochure_university`  (`university_id`),
    KEY        `idx_brochure_active`      (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- B.2  Marketing brochures share tracking ----------------------- */
CREATE TABLE IF NOT EXISTS `marketing_brochure_shares` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brochure_id`     INT UNSIGNED NOT NULL,
    `share_token`     VARCHAR(64) NOT NULL,
    `recipient_name`  VARCHAR(190) NULL,
    `recipient_phone` VARCHAR(40) NULL,
    `recipient_email` VARCHAR(190) NULL,
    `channel`         ENUM('copy','whatsapp','email','sms','other') NOT NULL DEFAULT 'copy',
    `matched_table`   VARCHAR(80) NULL,
    `matched_row_id`  INT UNSIGNED NULL,
    `is_new_contact`  TINYINT(1) NOT NULL DEFAULT 0,
    `shared_by`       INT UNSIGNED NULL,
    `notes`           VARCHAR(255) NULL,
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_opened_at`  TIMESTAMP NULL DEFAULT NULL,
    `open_count`      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_share_brochure` (`brochure_id`),
    KEY `idx_share_phone`    (`recipient_phone`),
    KEY `idx_share_token`    (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- B.3  Regions + Universities (safety nets) --------------------- */
CREATE TABLE IF NOT EXISTS `regions` (
    `id`   INT(11)      NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `universities` (
    `id`         INT(11)      NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(255) NOT NULL,
    `region_id`  INT(11)      NULL,
    `country_id` INT(11)      NULL,
    PRIMARY KEY (`id`),
    KEY `idx_university_region` (`region_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- B.4  Add missing columns on existing installs ---------------
   Older brochure installs may already have marketing_brochures without
   university_id / attach_pdf / html_content / extraction_status.
   These ALTERs are idempotent across MySQL 8: they fail silently on
   re-run because the column already exists. If your MySQL is older
   (< 8.0.29) and complains "Duplicate column name", just skip those.
------------------------------------------------------------------ */
ALTER TABLE `marketing_brochures` ADD COLUMN `university_id` INT UNSIGNED NULL DEFAULT NULL AFTER `region_id`;
ALTER TABLE `marketing_brochures` ADD INDEX `idx_brochure_university` (`university_id`);
ALTER TABLE `marketing_brochures` ADD COLUMN `attach_pdf` TINYINT(1) NOT NULL DEFAULT 1 AFTER `pdf_size_bytes`;
ALTER TABLE `marketing_brochures` ADD COLUMN `html_content` LONGTEXT NULL AFTER `extracted_text`;
ALTER TABLE `marketing_brochures` ADD COLUMN `extraction_status` VARCHAR(32) NOT NULL DEFAULT 'pending' AFTER `html_content`;

/* ---- B.5  Payment receipts "Done by" admin tracking ---------------- */
ALTER TABLE `payment_receipts` ADD COLUMN `recorded_by` INT UNSIGNED NULL DEFAULT NULL AFTER `payment_method`;
ALTER TABLE `payment_receipts` ADD COLUMN `recorded_by_name` VARCHAR(120) NULL DEFAULT NULL AFTER `recorded_by`;
