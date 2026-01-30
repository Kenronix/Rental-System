-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 26, 2026 at 05:18 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rental_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cache`
--

CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cache_locks`
--

CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `failed_jobs`
--

CREATE TABLE `failed_jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_batches`
--

CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landlords`
--

CREATE TABLE `landlords` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `landlords`
--

INSERT INTO `landlords` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `address`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'kenneth james batuhan', 'kennethbatuhan57@gmail.com', NULL, '$2y$12$mzU3eJUIh/AF5VYKcY9xG.GwqPHAh.Ci3LHjk0X4rYZGKWTe9xWYO', '09123456789', 'Cebu city', NULL, '2026-01-21 23:10:41', '2026-01-21 23:10:41');

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `migrations`
--

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_01_22_044706_create_landlords_table', 1),
(5, '2026_01_22_050000_create_tenants_table', 2),
(6, '2026_01_22_060000_create_properties_table', 2),
(7, 'create_units_table', 3),
(8, '2026_01_24_020211_add_amenities_to_units_table', 4),
(9, '2026_01_24_030000_create_tenant_applications_table', 5),
(10, '2026_01_24_040000_add_number_of_people_to_tenant_applications_table', 6),
(11, '2026_01_24_050000_add_profile_picture_and_relationships_to_tenant_applications_table', 7),
(12, '2026_01_24_060000_rename_reference_columns_in_tenant_applications_table', 7),
(13, '2026_01_26_035001_add_lease_duration_to_tenant_applications_table', 8);

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `landlord_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('residential','commercial') NOT NULL,
  `street_address` varchar(255) NOT NULL,
  `city` varchar(255) NOT NULL,
  `state` varchar(255) NOT NULL,
  `zip_code` varchar(255) NOT NULL,
  `units` int(11) NOT NULL DEFAULT 0,
  `tenants` int(11) NOT NULL DEFAULT 0,
  `main_photo` varchar(255) DEFAULT NULL,
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `status` enum('active','inactive','vacant') NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `properties`
--

INSERT INTO `properties` (`id`, `landlord_id`, `name`, `description`, `type`, `street_address`, `city`, `state`, `zip_code`, `units`, `tenants`, `main_photo`, `photos`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Kenneth Apartment', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'storage/properties/photos/main_6971de981c151_1769070232.jpeg', '[\"storage\\/properties\\/photos\\/photo_0_6971de9a8ef88_1769070234.jpeg\"]', 'active', '2026-01-22 00:23:54', '2026-01-22 00:23:54'),
(3, 1, 'Jhana Apartment', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6971e08164043_1769070721.jpeg', '[\"properties\\/photos\\/photo_0_6971e0816c8b6_1769070721.jpeg\"]', 'active', '2026-01-22 00:32:01', '2026-01-22 00:32:01'),
(4, 1, 'Jamaica Apartment', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6971e13c0a96b_1769070908.jpeg', '[\"properties\\/photos\\/photo_0_6971e13c12083_1769070908.jpeg\"]', 'active', '2026-01-22 00:35:08', '2026-01-22 00:35:08'),
(5, 1, 'Felix Homes', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6971e2f08aea3_1769071344.jpeg', '[\"properties\\/photos\\/photo_0_6971e2f098334_1769071344.jpeg\"]', 'active', '2026-01-22 00:42:24', '2026-01-22 00:42:24'),
(6, 1, 'Poland Warehouse', NULL, 'commercial', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6971e526ba85c_1769071910.jpeg', '[\"properties\\/photos\\/photo_0_6971e526c1c40_1769071910.jpeg\"]', 'active', '2026-01-22 00:51:50', '2026-01-22 00:51:50'),
(7, 1, 'James Warehouse', NULL, 'commercial', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6972d072dbb1a_1769132146.jpeg', '[\"properties\\/photos\\/photo_0_6972d074ed75e_1769132148.jpeg\"]', 'active', '2026-01-22 17:35:48', '2026-01-22 17:35:48'),
(8, 1, 'James Warehouse', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Cebu', 'Philippines', '6047', 0, 0, 'properties/photos/main_6972e58b11d54_1769137547.jpeg', '[\"properties\\/photos\\/photo_0_6972e58b1d2f5_1769137547.jpeg\"]', 'active', '2026-01-22 17:35:49', '2026-01-22 19:05:47'),
(9, 1, 'Bernard Apartelle', NULL, 'residential', 'Talisay, Santa Fe, Cebu', 'Santa Fe', 'Cebu', '6047', 5, 5, 'properties/photos/main_6972e680743e3_1769137792.jpeg', '[\"properties\\/photos\\/photo_0_6972e6807eaee_1769137792.jpeg\"]', 'active', '2026-01-22 19:08:41', '2026-01-25 20:00:26'),
(10, 1, 'Saint Jude Apartment', NULL, 'residential', 'Urgello, Cebu', 'Cebu', 'Philippines', '6000', 2, 2, 'properties/photos/main_69730fcc9921f_1769148364.jpeg', '[\"properties\\/photos\\/photo_0_69730fce0d7f2_1769148366.jpeg\"]', 'active', '2026-01-22 22:06:06', '2026-01-25 19:34:35');

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
('T4M1gQX36eqGEg8femoZuqWQmWgfGSXlcqppaWnq', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', 'YTo0OntzOjY6Il90b2tlbiI7czo0MDoieXFHYW84VllBMEFzU3ZYS1FMNVhob2NNMFQwVTZqa2tBNTR5ZWpXdyI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzM6Imh0dHA6Ly8xMjcuMC4wLjE6ODAwMC9hcGkvdGVuYW50cyI7czo1OiJyb3V0ZSI7Tjt9czo1NToibG9naW5fbGFuZGxvcmRfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI7aToxO30=', 1769401038);

-- --------------------------------------------------------

--
-- Table structure for table `tenants`
--

CREATE TABLE `tenants` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenants`
--

INSERT INTO `tenants` (`id`, `name`, `email`, `email_verified_at`, `password`, `phone`, `address`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'kenneth batuhan', 'kennethjamesbatuhan734@gmail.com', NULL, '$2y$12$3z8WVQjp2cAbEvlENUQyuOIc4QUzrh.qtZKiW7ONBc8aA3XfXv6P2', '09293204854', 'Cebu city', NULL, '2026-01-22 21:00:37', '2026-01-22 21:00:37'),
(2, 'Kenneth James Batuhan', 'kennethbatuhan57@gmail.com', NULL, '$2y$12$PQ3X.nLEuRlJGARbsKSpBeECqyIzx4rZjhwhQBQWa9/OpCvs3gZL2', '09123456789', 'Talisay, Santa Fe, Cebu', NULL, '2026-01-25 17:38:59', '2026-01-25 17:38:59'),
(3, 'Kakakaka', 'akkaka@gmail.com', NULL, '$2y$12$peGN1E6qiFWyrZxG.7qOs.9i60rKUcLmRVvLWrEZgZ8Zx3P7WPDCy', '09123456789', 'Talisay, Santa Fe, Cebu', NULL, '2026-01-25 20:00:26', '2026-01-25 20:00:26');

-- --------------------------------------------------------

--
-- Table structure for table `tenant_applications`
--

CREATE TABLE `tenant_applications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `unit_id` bigint(20) UNSIGNED NOT NULL,
  `id_picture` varchar(255) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) NOT NULL,
  `whatsapp` varchar(255) NOT NULL,
  `occupation` varchar(255) NOT NULL,
  `monthly_income` int(11) NOT NULL,
  `address` text NOT NULL,
  `number_of_people` int(11) NOT NULL DEFAULT 1,
  `lease_duration_months` int(11) DEFAULT NULL,
  `reference1_name` varchar(255) DEFAULT NULL,
  `reference1_address` text DEFAULT NULL,
  `reference1_phone` varchar(255) DEFAULT NULL,
  `reference1_email` varchar(255) DEFAULT NULL,
  `reference1_relationship` varchar(255) DEFAULT NULL,
  `reference2_name` varchar(255) DEFAULT NULL,
  `reference2_address` text DEFAULT NULL,
  `reference2_phone` varchar(255) DEFAULT NULL,
  `reference2_email` varchar(255) DEFAULT NULL,
  `reference2_relationship` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tenant_applications`
--

INSERT INTO `tenant_applications` (`id`, `unit_id`, `id_picture`, `profile_picture`, `name`, `email`, `phone`, `whatsapp`, `occupation`, `monthly_income`, `address`, `number_of_people`, `lease_duration_months`, `reference1_name`, `reference1_address`, `reference1_phone`, `reference1_email`, `reference1_relationship`, `reference2_name`, `reference2_address`, `reference2_phone`, `reference2_email`, `reference2_relationship`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(2, 2, 'tenant-applications/id-pictures/id_picture_6976c5932267d_1769391507.png', NULL, 'Kenneth James Batuhan', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 30000, 'Talisay, Santa Fe, Cebu', 3, NULL, 'Ken', 'Talisay, Santa Fe, Cebu', 'w', 'q@a.com', NULL, 'q', 'Talisay, Santa Fe, Cebu', 'q', 'q@a.com', NULL, 'approved', NULL, '2026-01-25 17:38:28', '2026-01-25 17:39:00'),
(3, 4, 'tenant-applications/id-pictures/id_picture_6976d386754fe_1769395078.png', 'tenant-applications/id-pictures/profile_picture_6976d3867da50_1769395078.avif', 'Kenneth James Batuhan', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 30000, 'Talisay, Santa Fe, Cebu', 3, NULL, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Father', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'approved', NULL, '2026-01-25 18:37:58', '2026-01-25 18:38:33'),
(4, 3, 'tenant-applications/id-pictures/id_picture_6976daee301ed_1769396974.jpeg', 'tenant-applications/id-pictures/profile_picture_6976daee3ac61_1769396974.jpeg', 'James Illut', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 10000, 'Talisay, Santa Fe, Cebu', 1, NULL, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'approved', NULL, '2026-01-25 19:09:34', '2026-01-25 19:09:50'),
(5, 5, 'tenant-applications/id-pictures/id_picture_6976dca05dda4_1769397408.png', 'tenant-applications/id-pictures/profile_picture_6976dca063ac8_1769397408.jpeg', 'James Illut', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 10000, 'Talisay, Santa Fe, Cebu', 1, NULL, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Mother', 'approved', NULL, '2026-01-25 19:16:48', '2026-01-25 19:17:14'),
(6, 1, 'tenant-applications/id-pictures/id_picture_6976df9955053_1769398169.jpeg', 'tenant-applications/id-pictures/profile_picture_6976df995e857_1769398169.png', 'James Illut', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 10000, 'Talisay, Santa Fe, Cebu', 1, NULL, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'approved', NULL, '2026-01-25 19:29:29', '2026-01-25 19:29:47'),
(7, 6, 'tenant-applications/id-pictures/id_picture_6976e0b4a83e6_1769398452.png', 'tenant-applications/id-pictures/profile_picture_6976e0b4ad416_1769398452.avif', 'James Illut', 'kennethbatuhan57@gmail.com', '09123456789', '09123456789', 'Student', 10000, 'Talisay, Santa Fe, Cebu', 1, NULL, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Sibling', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Father', 'approved', NULL, '2026-01-25 19:34:12', '2026-01-25 19:34:35'),
(8, 7, 'tenant-applications/id-pictures/id_picture_6976e6c91c13f_1769400009.png', 'tenant-applications/id-pictures/profile_picture_6976e6c923813_1769400009.jpeg', 'Kakakaka', 'akkaka@gmail.com', '09123456789', '09123456789', 'Student', 15000, 'Talisay, Santa Fe, Cebu', 2, 6, 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Father', 'Kenneth James Batuhan', 'Talisay, Santa Fe, Cebu', '09123456789', 'kennethbatuhan57@gmail.com', 'Father', 'approved', NULL, '2026-01-25 20:00:09', '2026-01-25 20:00:26');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `property_id` bigint(20) UNSIGNED NOT NULL,
  `unit_number` varchar(255) NOT NULL,
  `unit_type` varchar(255) NOT NULL,
  `bedrooms` int(11) NOT NULL,
  `bathrooms` int(11) NOT NULL,
  `square_footage` int(11) DEFAULT NULL,
  `monthly_rent` int(11) NOT NULL,
  `security_deposit` int(11) NOT NULL,
  `advance_deposit` int(11) NOT NULL,
  `description` text NOT NULL,
  `amenities` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`amenities`)),
  `photos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos`)),
  `status` enum('active','inactive','vacant') NOT NULL DEFAULT 'active',
  `is_occupied` tinyint(1) NOT NULL DEFAULT 0,
  `tenant_id` bigint(20) UNSIGNED DEFAULT NULL,
  `lease_start` date DEFAULT NULL,
  `lease_end` date DEFAULT NULL,
  `lease_duration` int(11) DEFAULT NULL,
  `lease_amount` int(11) DEFAULT NULL,
  `lease_deposit` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `property_id`, `unit_number`, `unit_type`, `bedrooms`, `bathrooms`, `square_footage`, `monthly_rent`, `security_deposit`, `advance_deposit`, `description`, `amenities`, `photos`, `status`, `is_occupied`, `tenant_id`, `lease_start`, `lease_end`, `lease_duration`, `lease_amount`, `lease_deposit`, `created_at`, `updated_at`) VALUES
(1, 9, '502', 'apartment', 2, 1, NULL, 25000, 0, 0, 'Cozy', '[\"dishwasher\",\"elevator\",\"balcony\",\"pet_friendly\",\"air_conditioning\",\"pool\"]', '[\"\\/units\\/photos\\/photo_0_6972f9d20e2ad_1769142738.jpeg\",\"\\/units\\/photos\\/photo_1_6972f9d21bb28_1769142738.jpeg\",\"\\/units\\/photos\\/photo_2_6972f9d21cf19_1769142738.jpeg\",\"\\/units\\/photos\\/photo_3_6972f9d2211bc_1769142738.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 25000, 0, '2026-01-22 20:32:18', '2026-01-25 19:29:47'),
(2, 9, '608', 'apartment', 1, 1, NULL, 30000, 0, 0, 'Nice', '[\"dishwasher\",\"elevator\",\"pet_friendly\",\"balcony\",\"pool\",\"air_conditioning\",\"parking\"]', '[\"\\/units\\/photos\\/photo_0_6972fa2e0be01_1769142830.jpeg\",\"\\/units\\/photos\\/photo_1_6972fa2e127db_1769142830.jpeg\",\"\\/units\\/photos\\/photo_2_6972fa2e13cc0_1769142830.jpeg\",\"\\/units\\/photos\\/photo_3_6972fa2e16ca7_1769142830.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 30000, 0, '2026-01-22 20:33:50', '2026-01-25 17:38:59'),
(3, 9, '719', 'apartment', 2, 1, NULL, 30000, 0, 0, 'HAHHAHAHA', '[\"wifi\",\"gym\",\"balcony\",\"pet_friendly\",\"elevator\",\"dishwasher\",\"air_conditioning\",\"washer_dryer\"]', '[\"\\/units\\/photos\\/photo_0_6973013f826ab_1769144639.jpeg\",\"\\/units\\/photos\\/photo_1_6973013f8bb49_1769144639.jpeg\",\"\\/units\\/photos\\/photo_2_6973013f8d2ad_1769144639.jpeg\",\"\\/units\\/photos\\/photo_3_6973013f9225b_1769144639.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 30000, 0, '2026-01-22 21:03:59', '2026-01-25 19:09:50'),
(4, 10, '502', 'apartment', 2, 1, NULL, 25000, 0, 0, 'Nice', NULL, '[\"units\\/photos\\/photo_0_6973101433b0a_1769148436.jpeg\",\"units\\/photos\\/photo_1_697310143a241_1769148436.jpeg\",\"units\\/photos\\/photo_2_697310143b45d_1769148436.jpeg\",\"units\\/photos\\/photo_3_6973101443350_1769148436.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 25000, 0, '2026-01-22 22:07:16', '2026-01-25 18:38:33'),
(5, 9, '812', 'apartment', 1, 1, NULL, 12000, 0, 0, 'a', '[\"balcony\",\"pet_friendly\",\"dishwasher\",\"elevator\"]', '[\"units\\/photos\\/photo_0_6976dc64c13ee_1769397348.jpeg\",\"units\\/photos\\/photo_1_6976dc64ca14c_1769397348.jpeg\",\"units\\/photos\\/photo_2_6976dc64cc46f_1769397348.jpeg\",\"units\\/photos\\/photo_3_6976dc64d1f68_1769397348.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 12000, 0, '2026-01-25 19:15:48', '2026-01-25 19:17:14'),
(6, 10, '812', 'apartment', 1, 1, NULL, 12000, 0, 0, 'd', '[\"dishwasher\",\"elevator\",\"balcony\",\"pet_friendly\"]', '[\"units\\/photos\\/photo_0_6976e07e51e7f_1769398398.jpeg\",\"units\\/photos\\/photo_1_6976e07e59103_1769398398.jpeg\",\"units\\/photos\\/photo_2_6976e07e5a693_1769398398.jpeg\",\"units\\/photos\\/photo_3_6976e07e5d690_1769398398.jpeg\"]', 'active', 1, 2, '2026-01-26', '2027-01-26', 12, 12000, 0, '2026-01-25 19:33:18', '2026-01-25 19:34:35'),
(7, 9, '888', 'apartment', 1, 1, NULL, 12000, 0, 0, 'www', '[\"air_conditioning\",\"pet_friendly\",\"balcony\",\"dishwasher\",\"elevator\"]', '[\"units\\/photos\\/photo_0_6976e68a40410_1769399946.jpeg\",\"units\\/photos\\/photo_1_6976e68a49b61_1769399946.jpeg\",\"units\\/photos\\/photo_2_6976e68a4b81f_1769399946.jpeg\",\"units\\/photos\\/photo_3_6976e68a4f2e4_1769399946.jpeg\"]', 'active', 1, 3, '2026-01-26', '2026-07-26', 6, 12000, 0, '2026-01-25 19:59:06', '2026-01-25 20:00:26');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Test User', 'test@example.com', '2026-01-22 20:59:23', '$2y$12$QUshYU92NcsV9JdMKvCAlugnNkmTjMSAKcvgEndh1m2M339gQvi5i', 'uRl24IOHKR', '2026-01-22 20:59:24', '2026-01-22 20:59:24');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cache`
--
ALTER TABLE `cache`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_expiration_index` (`expiration`);

--
-- Indexes for table `cache_locks`
--
ALTER TABLE `cache_locks`
  ADD PRIMARY KEY (`key`),
  ADD KEY `cache_locks_expiration_index` (`expiration`);

--
-- Indexes for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jobs_queue_index` (`queue`);

--
-- Indexes for table `job_batches`
--
ALTER TABLE `job_batches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landlords`
--
ALTER TABLE `landlords`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `landlords_email_unique` (`email`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`email`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`id`),
  ADD KEY `properties_landlord_id_foreign` (`landlord_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `tenants`
--
ALTER TABLE `tenants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `tenants_email_unique` (`email`);

--
-- Indexes for table `tenant_applications`
--
ALTER TABLE `tenant_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `tenant_applications_unit_id_foreign` (`unit_id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD KEY `units_property_id_foreign` (`property_id`),
  ADD KEY `units_tenant_id_foreign` (`tenant_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `failed_jobs`
--
ALTER TABLE `failed_jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landlords`
--
ALTER TABLE `landlords`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tenants`
--
ALTER TABLE `tenants`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tenant_applications`
--
ALTER TABLE `tenant_applications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_landlord_id_foreign` FOREIGN KEY (`landlord_id`) REFERENCES `landlords` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tenant_applications`
--
ALTER TABLE `tenant_applications`
  ADD CONSTRAINT `tenant_applications_unit_id_foreign` FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_property_id_foreign` FOREIGN KEY (`property_id`) REFERENCES `properties` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `units_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
