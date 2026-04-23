-- Parrot Canada Visa Consultant - Content Management System Database
-- Created for full content management of the website

CREATE DATABASE IF NOT EXISTS parrot_visa_cms;
USE parrot_visa_cms;

-- Admin users table
CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `role` enum('admin','editor') DEFAULT 'admin',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Website settings table
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  `setting_type` enum('text','textarea','image','file') DEFAULT 'text',
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Navigation menu items
CREATE TABLE `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `url` varchar(255) NOT NULL,
  `parent_id` int(11) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `icon_class` varchar(100) DEFAULT NULL,
  `target` enum('_self','_blank') DEFAULT '_self',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gallery images for homepage slider
CREATE TABLE `gallery_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `image_url` varchar(255) NOT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Countries for study destinations
CREATE TABLE `countries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `flag_emoji` varchar(10) DEFAULT NULL,
  `description` text,
  `route_url` varchar(255) DEFAULT NULL,
  `is_popular` tinyint(1) DEFAULT 0,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Services
CREATE TABLE `services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `description` text,
  `icon_class` varchar(100) DEFAULT NULL,
  `features` json DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Testimonials
CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `country` varchar(100) DEFAULT NULL,
  `university` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` int(1) DEFAULT 5,
  `image_url` varchar(255) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- University partners
CREATE TABLE `university_partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `country` varchar(100) DEFAULT NULL,
  `website_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Homepage sections content
CREATE TABLE `homepage_sections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `section_name` varchar(100) NOT NULL,
  `section_title` varchar(200) DEFAULT NULL,
  `section_content` longtext,
  `background_image` varchar(255) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `section_name` (`section_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- News/Announcements for ticker
CREATE TABLE `news_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `content` text,
  `link_url` varchar(255) DEFAULT NULL,
  `badge` varchar(50) DEFAULT NULL,
  `order_index` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Contact information
CREATE TABLE `contact_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `info_type` enum('phone','email','address','social') NOT NULL,
  `info_value` varchar(255) NOT NULL,
  `info_label` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `order_index` int(11) DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`) VALUES
('admin', 'admin@parrotvisa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin');

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('site_title', 'Parrot Canada Visa Consultant', 'text', 'Website title'),
('site_description', 'Your trusted partner for international education and visa services', 'textarea', 'Website meta description'),
('hero_title', 'Your Gateway to Global Education', 'text', 'Homepage hero section title'),
('hero_subtitle', 'Parrot Canada Visa Consultant - Your trusted partner for international education', 'textarea', 'Homepage hero section subtitle'),
('contact_phone', '+1 (431) 302-0226', 'text', 'Contact phone number'),
('contact_email', 'infos@visaconsultantcanada.com', 'text', 'Contact email'),
('contact_address', 'Town Center Building, 2nd Floor, Door: F2B-022C, Nyarugenge, Kigali, Rwanda', 'textarea', 'Office address');

-- Insert default menu items
INSERT INTO `menu_items` (`title`, `url`, `parent_id`, `order_index`, `icon_class`) VALUES
('Home', '/', 0, 1, 'Home'),
('About', '/about', 0, 2, 'Info'),
('Services', '/services', 0, 3, 'Wrench'),
('Pay Here', '#', 0, 4, 'CreditCard'),
('Scholarship', '/scholarship', 0, 5, 'Plane'),
('Universities', '/partnership-universities', 0, 6, 'Building'),
('E-Learning', '/e-learning', 0, 7, 'Book'),
('Contact', '/contact', 0, 8, 'Mail');

-- Insert default countries
INSERT INTO `countries` (`name`, `flag_emoji`, `description`, `route_url`, `is_popular`, `order_index`) VALUES
('Canada', '🇨🇦', 'Study in top Canadian universities', '/study/canada', 1, 1),
('USA', '🇺🇸', 'American dream education', '/study/usa', 1, 2),
('Germany', '🇩🇪', 'Free education in Germany', '/study/germany', 0, 3),
('Turkey', '🇹🇷', 'Affordable Turkish education', '/study/turkey', 0, 4),
('Ireland', '🇮🇪', 'Irish education excellence', '/study/ireland', 0, 5),
('Netherlands', '🇳🇱', 'Innovation hub of Europe', '/study/netherlands', 0, 6),
('Poland', '🇵🇱', 'Growing education destination', '/study/poland', 0, 7),
('Australia', '🇦🇺', 'Quality down under education', '/study/australia', 0, 8);

-- Insert default services
INSERT INTO `services` (`title`, `description`, `icon_class`, `features`, `order_index`) VALUES
('Student Visa', 'Complete assistance with student visa applications for all countries', 'GraduationCap', '["Document preparation", "Application filing", "Interview preparation", "Status tracking"]', 1),
('Study Abroad', 'Comprehensive guidance for international education', 'Plane', '["University selection", "Application assistance", "Scholarship guidance", "Pre-departure briefing"]', 2),
('Scholarships', 'Help with scholarship applications and funding opportunities', 'Award', '["Scholarship search", "Application guidance", "Essay writing", "Follow-up support"]', 3),
('Immigration', 'Expert advice on permanent residency pathways', 'Building', '["PR applications", "Work permits", "Family sponsorship", "Citizenship guidance"]', 4);

-- Insert default testimonials
INSERT INTO `testimonials` (`name`, `country`, `university`, `message`, `rating`, `order_index`) VALUES
('Sarah Kagame', 'Rwanda', 'University of Toronto', 'Parrot Canada made my dream of studying in Canada a reality. Their guidance was invaluable throughout the visa process.', 5, 1),
('James Mwangi', 'Kenya', 'McGill University', 'Professional and reliable service. They helped me secure admission and scholarship at my dream university.', 5, 2),
('Amina Diallo', 'Ghana', 'University of British Columbia', 'Excellent support from application to arrival. I couldn\'t have done it without their expertise.', 5, 3),
('David Chen', 'Nigeria', 'Franklin University', 'Great guidance for my scholarship application. Thank you Parrot for making it possible!', 5, 4);

-- Insert default university partners
INSERT INTO `university_partners` (`name`, `country`, `order_index`) VALUES
('University of Toronto', 'Canada', 1),
('McGill University', 'Canada', 2),
('University of British Columbia', 'Canada', 3),
('Franklin University', 'USA', 4),
('Technical University of Munich', 'Germany', 5),
('University of Amsterdam', 'Netherlands', 6);

-- Insert default news items
INSERT INTO `news_items` (`title`, `content`, `link_url`, `badge`, `order_index`) VALUES
('Book your visa consultation appointment today', 'Expert guidance available for all visa applications', '/contact', 'Available', 1),
('Schedule your free assessment call', 'Free consultation with our immigration consultants', '/contact', 'Book Now', 2),
('Walk-in consultations available', 'Visit our Kigali office today!', '/contact', 'Open', 3);

-- Insert default contact info
INSERT INTO `contact_info` (`info_type`, `info_value`, `info_label`, `order_index`) VALUES
('phone', '+1 (431) 302-0226', 'Phone', 1),
('email', 'infos@visaconsultantcanada.com', 'Email', 2),
('address', 'Town Center Building (near Simba Supermarket), 2nd Floor, Door: F2B-022C, Nyarugenge', 'Office Address', 3);
