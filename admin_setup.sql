-- Admin Table Structure and Sample Data
-- Run this SQL to create the admins table and add a sample admin user

-- Create admins table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admins_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample admin user
-- Email: admin@rentalsystem.com
-- Password: admin123
INSERT INTO `admins` (`name`, `email`, `password`, `phone`, `created_at`, `updated_at`) VALUES
('Admin User', 'admin@rentalsystem.com', '$2y$10$RqiVsl5fnrWrgnSMHMygzORyiwsqB97UBdpcNDHAwiDneMvED2fey', '09123456789', NOW(), NOW());

