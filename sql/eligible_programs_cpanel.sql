/* ===========================================================================
   Parrot Canada Visa Consultant
   FULL manual schema for cPanel / shared hosting where the DB user can't
   auto-run CREATE TABLE on the fly.

   How to use in cPanel:
   1. cPanel  ->  phpMyAdmin
   2. Pick the CMS database in the left sidebar:  visaeofi_web
   3. Open the SQL tab and paste PART A below, click Go.
   4. Pick the MIS database in the left sidebar: visaeofi_mis_parrot
      (or whatever prefix cPanel gave you - usually <account>_mis_parrot)
   5. Open the SQL tab and paste PART B below, click Go.
   6. Confirm:
        - visaeofi_web         shows 12 tables (admin_users ... university_partners)
        - visaeofi_mis_parrot  shows marketing_brochures with the new columns

   Notes:
   - CREATE TABLE IF NOT EXISTS makes Part A safe to re-run any number of
     times. Seed INSERTs use "WHERE NOT EXISTS ..." or unique keys so they
     never duplicate data on re-runs.
   - The DB user in your backend .env MUST be granted ALL PRIVILEGES on BOTH
     databases (visaeofi_web AND visaeofi_mis_parrot) so the cross-DB join
     in /api/eligible-programs works. Set this in:
       cPanel -> MySQL Databases -> Add User To Database
=========================================================================== */


/* =====================================================================
   PART A  -  Run inside the CMS database  (DB_NAME = visaeofi_web)
   =====================================================================
   12 tables for the backend CMS:
     1. admin_users
     2. settings
     3. menu_items
     4. gallery_images
     5. countries
     6. services
     7. testimonials
     8. university_partners
     9. homepage_sections
    10. news_items
    11. contact_info
    12. eligible_programs_settings
===================================================================== */

/* ---- A.1  admin_users --------------------------------------------- */
CREATE TABLE IF NOT EXISTS `admin_users` (
    `id`         INT(11)                NOT NULL AUTO_INCREMENT,
    `username`   VARCHAR(50)            NOT NULL,
    `email`      VARCHAR(100)           NOT NULL,
    `password`   VARCHAR(255)           NOT NULL,
    `full_name`  VARCHAR(100)           DEFAULT NULL,
    `role`       ENUM('admin','editor') DEFAULT 'admin',
    `created_at` TIMESTAMP              DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP              DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `last_login` DATETIME               DEFAULT NULL,
    `is_active`  TINYINT(1)             DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `username` (`username`),
    UNIQUE KEY `email`    (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.2  settings ------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `settings` (
    `id`            INT(11)                                  NOT NULL AUTO_INCREMENT,
    `setting_key`   VARCHAR(100)                             NOT NULL,
    `setting_value` TEXT                                     DEFAULT NULL,
    `setting_type`  ENUM('text','textarea','image','file')   DEFAULT 'text',
    `description`   VARCHAR(255)                             DEFAULT NULL,
    `updated_at`    TIMESTAMP                                DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.3  menu_items ---------------------------------------------- */
CREATE TABLE IF NOT EXISTS `menu_items` (
    `id`          INT(11)                NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(190)           NOT NULL,
    `url`         VARCHAR(255)           NOT NULL,
    `parent_id`   INT(11)                DEFAULT 0,
    `order_index` INT(11)                DEFAULT 0,
    `is_active`   TINYINT(1)             DEFAULT 1,
    `icon_class`  VARCHAR(100)           DEFAULT NULL,
    `target`      ENUM('_self','_blank') DEFAULT '_self',
    `created_at`  TIMESTAMP              DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP              DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.4  gallery_images ------------------------------------------ */
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.5  countries ----------------------------------------------- */
CREATE TABLE IF NOT EXISTS `countries` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `flag_emoji`  VARCHAR(10)  DEFAULT NULL,
    `description` TEXT         DEFAULT NULL,
    `route_url`   VARCHAR(255) DEFAULT NULL,
    `is_popular`  TINYINT(1)   DEFAULT 0,
    `order_index` INT(11)      DEFAULT 0,
    `is_active`   TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.6  services ------------------------------------------------ */
CREATE TABLE IF NOT EXISTS `services` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200) NOT NULL,
    `description` TEXT         DEFAULT NULL,
    `icon_class`  VARCHAR(100) DEFAULT NULL,
    `features`    JSON         DEFAULT NULL,
    `order_index` INT(11)      DEFAULT 0,
    `is_active`   TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.7  testimonials -------------------------------------------- */
CREATE TABLE IF NOT EXISTS `testimonials` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `country`     VARCHAR(100) DEFAULT NULL,
    `university`  VARCHAR(200) DEFAULT NULL,
    `message`     TEXT         NOT NULL,
    `rating`      INT(1)       DEFAULT 5,
    `image_url`   VARCHAR(255) DEFAULT NULL,
    `order_index` INT(11)      DEFAULT 0,
    `is_active`   TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.8  university_partners ------------------------------------- */
CREATE TABLE IF NOT EXISTS `university_partners` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(200) NOT NULL,
    `logo_url`    VARCHAR(255) DEFAULT NULL,
    `country`     VARCHAR(100) DEFAULT NULL,
    `website_url` VARCHAR(255) DEFAULT NULL,
    `description` TEXT         DEFAULT NULL,
    `order_index` INT(11)      DEFAULT 0,
    `is_active`   TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.9  homepage_sections --------------------------------------- */
CREATE TABLE IF NOT EXISTS `homepage_sections` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `section_name`     VARCHAR(100) NOT NULL,
    `section_title`    VARCHAR(200) DEFAULT NULL,
    `section_content`  LONGTEXT     DEFAULT NULL,
    `background_image` VARCHAR(255) DEFAULT NULL,
    `order_index`      INT(11)      DEFAULT 0,
    `is_active`        TINYINT(1)   DEFAULT 1,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.10  news_items --------------------------------------------- */
CREATE TABLE IF NOT EXISTS `news_items` (
    `id`          INT(11)      NOT NULL AUTO_INCREMENT,
    `title`       VARCHAR(200) NOT NULL,
    `content`     TEXT         DEFAULT NULL,
    `link_url`    VARCHAR(255) DEFAULT NULL,
    `badge`       VARCHAR(50)  DEFAULT NULL,
    `order_index` INT(11)      DEFAULT 0,
    `is_active`   TINYINT(1)   DEFAULT 1,
    `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.11  contact_info ------------------------------------------- */
CREATE TABLE IF NOT EXISTS `contact_info` (
    `id`          INT(11)                                       NOT NULL AUTO_INCREMENT,
    `info_type`   ENUM('phone','email','address','social')      NOT NULL,
    `info_value`  VARCHAR(255)                                  NOT NULL,
    `info_label`  VARCHAR(100)                                  DEFAULT NULL,
    `is_active`   TINYINT(1)                                    DEFAULT 1,
    `order_index` INT(11)                                       DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

/* ---- A.12  eligible_programs_settings ----------------------------- */
CREATE TABLE IF NOT EXISTS `eligible_programs_settings` (
    `id`               INT(11)      NOT NULL AUTO_INCREMENT,
    `brochure_slug`    VARCHAR(190) NOT NULL,
    `display_title`    VARCHAR(255) DEFAULT NULL,
    `display_subtitle` VARCHAR(255) DEFAULT NULL,
    `is_featured`      TINYINT(1)   DEFAULT 0,
    `is_hidden`        TINYINT(1)   DEFAULT 0,
    `position`         INT(11)      DEFAULT 0,
    `created_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_eligible_brochure_slug` (`brochure_slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


/* =====================================================================
   PART A - SEED DATA (safe to re-run; uses WHERE NOT EXISTS / unique keys)
===================================================================== */

/* ---- Default admin (username: admin, password: admin123) ---------- */
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`)
SELECT 'admin', 'admin@parrotvisa.com',
       '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
       'Administrator', 'admin'
WHERE NOT EXISTS (SELECT 1 FROM `admin_users` WHERE `username` = 'admin');

/* ---- Default settings (INSERT IGNORE uses unique setting_key) ----- */
INSERT IGNORE INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_title',       'Parrot Canada Visa Consultant',                                                                          'text',     'Website title'),
('site_description', 'Your trusted partner for international education and visa services',                                     'textarea', 'Website meta description'),
('hero_title',       'Your Gateway to Global Education',                                                                       'text',     'Homepage hero section title'),
('hero_subtitle',    'Parrot Canada Visa Consultant - Your trusted partner for international education',                       'textarea', 'Homepage hero section subtitle'),
('contact_phone',    '+1 (431) 302-0226',                                                                                      'text',     'Contact phone number'),
('contact_email',    'infos@visaconsultantcanada.com',                                                                         'text',     'Contact email'),
('contact_address',  'Town Center Building, 2nd Floor, Door: F2B-022C, Nyarugenge, Kigali, Rwanda',                            'textarea', 'Office address');

/* ---- Default menu items (each insert skips if URL already exists) -- */
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Home', '/', 0, 1, 'Home'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'About', '/about', 0, 2, 'Info'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/about');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Services', '/services', 0, 3, 'Wrench'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/services');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Pay Here', '#', 0, 4, 'CreditCard'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `title` = 'Pay Here');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Scholarship', '/scholarship', 0, 5, 'Plane'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/scholarship');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Universities', '/partnership-universities', 0, 6, 'Building'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/partnership-universities');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'E-Learning', '/e-learning', 0, 7, 'Book'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/e-learning');
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`)
SELECT 'Contact', '/contact', 0, 8, 'Mail'
WHERE NOT EXISTS (SELECT 1 FROM `menu_items` WHERE `url` = '/contact');

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

/* ---- Default countries (skips by unique name) --------------------- */
INSERT IGNORE INTO `countries` (`name`, `flag_emoji`, `description`, `route_url`, `is_popular`, `order_index`) VALUES
('Canada',      'CA', 'Study in top Canadian universities', '/study/canada',      1, 1),
('USA',         'US', 'American dream education',           '/study/usa',         1, 2),
('Germany',     'DE', 'Free education in Germany',          '/study/germany',     0, 3),
('Turkey',      'TR', 'Affordable Turkish education',       '/study/turkey',      0, 4),
('Ireland',     'IE', 'Irish education excellence',         '/study/ireland',     0, 5),
('Netherlands', 'NL', 'Innovation hub of Europe',           '/study/netherlands', 0, 6),
('Poland',      'PL', 'Growing education destination',      '/study/poland',      0, 7),
('Australia',   'AU', 'Quality down under education',       '/study/australia',   0, 8);

/* ---- Default services (skips when title already exists) ----------- */
INSERT INTO `services` (`title`, `description`, `icon_class`, `features`, `order_index`)
SELECT 'Student Visa', 'Complete assistance with student visa applications for all countries', 'GraduationCap',
       '["Document preparation","Application filing","Interview preparation","Status tracking"]', 1
WHERE NOT EXISTS (SELECT 1 FROM `services` WHERE `title` = 'Student Visa');
INSERT INTO `services` (`title`, `description`, `icon_class`, `features`, `order_index`)
SELECT 'Study Abroad', 'Comprehensive guidance for international education', 'Plane',
       '["University selection","Application assistance","Scholarship guidance","Pre-departure briefing"]', 2
WHERE NOT EXISTS (SELECT 1 FROM `services` WHERE `title` = 'Study Abroad');
INSERT INTO `services` (`title`, `description`, `icon_class`, `features`, `order_index`)
SELECT 'Scholarships', 'Help with scholarship applications and funding opportunities', 'Award',
       '["Scholarship search","Application guidance","Essay writing","Follow-up support"]', 3
WHERE NOT EXISTS (SELECT 1 FROM `services` WHERE `title` = 'Scholarships');
INSERT INTO `services` (`title`, `description`, `icon_class`, `features`, `order_index`)
SELECT 'Immigration', 'Expert advice on permanent residency pathways', 'Building',
       '["PR applications","Work permits","Family sponsorship","Citizenship guidance"]', 4
WHERE NOT EXISTS (SELECT 1 FROM `services` WHERE `title` = 'Immigration');

/* ---- Default testimonials ----------------------------------------- */
INSERT INTO `testimonials` (`name`, `country`, `university`, `message`, `rating`, `order_index`)
SELECT 'Sarah Kagame', 'Rwanda', 'University of Toronto',
       'Parrot Canada made my dream of studying in Canada a reality. Their guidance was invaluable throughout the visa process.',
       5, 1
WHERE NOT EXISTS (SELECT 1 FROM `testimonials` WHERE `name` = 'Sarah Kagame');
INSERT INTO `testimonials` (`name`, `country`, `university`, `message`, `rating`, `order_index`)
SELECT 'James Mwangi', 'Kenya', 'McGill University',
       'Professional and reliable service. They helped me secure admission and scholarship at my dream university.',
       5, 2
WHERE NOT EXISTS (SELECT 1 FROM `testimonials` WHERE `name` = 'James Mwangi');
INSERT INTO `testimonials` (`name`, `country`, `university`, `message`, `rating`, `order_index`)
SELECT 'Amina Diallo', 'Ghana', 'University of British Columbia',
       'Excellent support from application to arrival. I could not have done it without their expertise.',
       5, 3
WHERE NOT EXISTS (SELECT 1 FROM `testimonials` WHERE `name` = 'Amina Diallo');
INSERT INTO `testimonials` (`name`, `country`, `university`, `message`, `rating`, `order_index`)
SELECT 'David Chen', 'Nigeria', 'Franklin University',
       'Great guidance for my scholarship application. Thank you Parrot for making it possible!',
       5, 4
WHERE NOT EXISTS (SELECT 1 FROM `testimonials` WHERE `name` = 'David Chen');

/* ---- Default university partners ---------------------------------- */
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'University of Toronto', 'Canada', 1
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'University of Toronto');
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'McGill University', 'Canada', 2
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'McGill University');
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'University of British Columbia', 'Canada', 3
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'University of British Columbia');
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'Franklin University', 'USA', 4
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'Franklin University');
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'Technical University of Munich', 'Germany', 5
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'Technical University of Munich');
INSERT INTO `university_partners` (`name`, `country`, `order_index`)
SELECT 'University of Amsterdam', 'Netherlands', 6
WHERE NOT EXISTS (SELECT 1 FROM `university_partners` WHERE `name` = 'University of Amsterdam');

/* ---- Default news items ------------------------------------------- */
INSERT INTO `news_items` (`title`, `content`, `link_url`, `badge`, `order_index`)
SELECT 'Book your visa consultation appointment today',
       'Expert guidance available for all visa applications', '/contact', 'Available', 1
WHERE NOT EXISTS (SELECT 1 FROM `news_items` WHERE `title` = 'Book your visa consultation appointment today');
INSERT INTO `news_items` (`title`, `content`, `link_url`, `badge`, `order_index`)
SELECT 'Schedule your free assessment call',
       'Free consultation with our immigration consultants', '/contact', 'Book Now', 2
WHERE NOT EXISTS (SELECT 1 FROM `news_items` WHERE `title` = 'Schedule your free assessment call');
INSERT INTO `news_items` (`title`, `content`, `link_url`, `badge`, `order_index`)
SELECT 'Walk-in consultations available',
       'Visit our Kigali office today!', '/contact', 'Open', 3
WHERE NOT EXISTS (SELECT 1 FROM `news_items` WHERE `title` = 'Walk-in consultations available');

/* ---- Default contact info ----------------------------------------- */
INSERT INTO `contact_info` (`info_type`, `info_value`, `info_label`, `order_index`)
SELECT 'phone', '+1 (431) 302-0226', 'Phone', 1
WHERE NOT EXISTS (SELECT 1 FROM `contact_info` WHERE `info_type` = 'phone' AND `info_value` = '+1 (431) 302-0226');
INSERT INTO `contact_info` (`info_type`, `info_value`, `info_label`, `order_index`)
SELECT 'email', 'infos@visaconsultantcanada.com', 'Email', 2
WHERE NOT EXISTS (SELECT 1 FROM `contact_info` WHERE `info_type` = 'email' AND `info_value` = 'infos@visaconsultantcanada.com');
INSERT INTO `contact_info` (`info_type`, `info_value`, `info_label`, `order_index`)
SELECT 'address',
       'Town Center Building (near Simba Supermarket), 2nd Floor, Door: F2B-022C, Nyarugenge',
       'Office Address', 3
WHERE NOT EXISTS (SELECT 1 FROM `contact_info` WHERE `info_type` = 'address');


/* =====================================================================
   PART B  -  Run inside the MIS database  (PARROT_MIS_DB = visaeofi_mis_parrot)
   =====================================================================
   Brings the brochure tables up to date with the "university_id" feature
   and the "Done by" admin tracking on payment receipts.
===================================================================== */

/* ---- B.1  marketing_brochures core table -------------------------- */
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
    UNIQUE KEY `uniq_brochure_slug`      (`slug`),
    KEY        `idx_brochure_region`     (`region_id`),
    KEY        `idx_brochure_university` (`university_id`),
    KEY        `idx_brochure_active`     (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- B.2  marketing_brochure_shares ------------------------------- */
CREATE TABLE IF NOT EXISTS `marketing_brochure_shares` (
    `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `brochure_id`     INT UNSIGNED NOT NULL,
    `share_token`     VARCHAR(64)  NOT NULL,
    `recipient_name`  VARCHAR(190) NULL,
    `recipient_phone` VARCHAR(40)  NULL,
    `recipient_email` VARCHAR(190) NULL,
    `channel`         ENUM('copy','whatsapp','email','sms','other') NOT NULL DEFAULT 'copy',
    `matched_table`   VARCHAR(80)  NULL,
    `matched_row_id`  INT UNSIGNED NULL,
    `is_new_contact`  TINYINT(1)   NOT NULL DEFAULT 0,
    `shared_by`       INT UNSIGNED NULL,
    `notes`           VARCHAR(255) NULL,
    `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `last_opened_at`  TIMESTAMP    NULL DEFAULT NULL,
    `open_count`      INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_share_brochure` (`brochure_id`),
    KEY `idx_share_phone`    (`recipient_phone`),
    KEY `idx_share_token`    (`share_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/* ---- B.3  Safety-net for regions / universities ------------------- */
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

/* ---- B.4  Add missing columns / indexes on existing installs ------
   Each block checks information_schema first, so it does nothing
   when the column / index already exists. 100% safe to re-run.
   Skips silently if `payment_receipts` doesn't exist on your install.
------------------------------------------------------------------- */

/* marketing_brochures.university_id */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'marketing_brochures'
         AND COLUMN_NAME  = 'university_id') = 0,
    'ALTER TABLE `marketing_brochures` ADD COLUMN `university_id` INT UNSIGNED NULL DEFAULT NULL AFTER `region_id`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

/* marketing_brochures index idx_brochure_university */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.STATISTICS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'marketing_brochures'
         AND INDEX_NAME   = 'idx_brochure_university') = 0,
    'ALTER TABLE `marketing_brochures` ADD INDEX `idx_brochure_university` (`university_id`)',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

/* marketing_brochures.attach_pdf */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'marketing_brochures'
         AND COLUMN_NAME  = 'attach_pdf') = 0,
    'ALTER TABLE `marketing_brochures` ADD COLUMN `attach_pdf` TINYINT(1) NOT NULL DEFAULT 1 AFTER `pdf_size_bytes`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

/* marketing_brochures.html_content */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'marketing_brochures'
         AND COLUMN_NAME  = 'html_content') = 0,
    'ALTER TABLE `marketing_brochures` ADD COLUMN `html_content` LONGTEXT NULL AFTER `extracted_text`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

/* marketing_brochures.extraction_status */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'marketing_brochures'
         AND COLUMN_NAME  = 'extraction_status') = 0,
    'ALTER TABLE `marketing_brochures` ADD COLUMN `extraction_status` VARCHAR(32) NOT NULL DEFAULT ''pending'' AFTER `html_content`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;


/* ---- B.5  Payment receipts "Done by" admin tracking ---------------
   Skipped entirely if `payment_receipts` table doesn't exist.
------------------------------------------------------------------- */

/* payment_receipts.recorded_by */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'payment_receipts') = 1
    AND
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'payment_receipts'
         AND COLUMN_NAME  = 'recorded_by') = 0,
    'ALTER TABLE `payment_receipts` ADD COLUMN `recorded_by` INT UNSIGNED NULL DEFAULT NULL AFTER `payment_method`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;

/* payment_receipts.recorded_by_name */
SET @sql := IF(
    (SELECT COUNT(*) FROM information_schema.TABLES
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'payment_receipts') = 1
    AND
    (SELECT COUNT(*) FROM information_schema.COLUMNS
       WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME   = 'payment_receipts'
         AND COLUMN_NAME  = 'recorded_by_name') = 0,
    'ALTER TABLE `payment_receipts` ADD COLUMN `recorded_by_name` VARCHAR(120) NULL DEFAULT NULL AFTER `recorded_by`',
    'SELECT 1'
);
PREPARE _s FROM @sql; EXECUTE _s; DEALLOCATE PREPARE _s;
