-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Feb 24, 2026 at 12:00 PM
-- Server version: 8.0.26
-- PHP Version: 8.0.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ghana_telecom`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_attempts`
--

CREATE TABLE `admin_login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_login_logs`
--

CREATE TABLE `admin_login_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `login_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `admin_id` (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT '0',
  `attempt_time` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `order_id` varchar(50) NOT NULL,
  `package_type` enum('data','airtime','cable','result_checker','afa') NOT NULL,
  `package_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `recipients` json NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','processing','completed','failed') DEFAULT 'pending',
  `payment_ref` varchar(100) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` enum('data','airtime','cable','result_checker','afa') NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `network` varchar(20) DEFAULT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_token` (`email`,`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_cart`
--

CREATE TABLE `user_cart` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `package_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `recipients` json NOT NULL,
  `added_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `package_id` (`package_id`),
  CONSTRAINT `user_cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_cart_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `last_login` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`type`, `name`, `price`, `network`, `description`) VALUES
-- MTN Data Bundles
('data', '1GB', 5.00, 'MTN', '1GB data bundle for MTN network'),
('data', '2GB', 10.00, 'MTN', '2GB data bundle for MTN network'),
('data', '3GB', 15.00, 'MTN', '3GB data bundle for MTN network'),
('data', '4GB', 20.00, 'MTN', '4GB data bundle for MTN network'),
('data', '5GB', 25.00, 'MTN', '5GB data bundle for MTN network'),
('data', '6GB', 30.00, 'MTN', '6GB data bundle for MTN network'),
('data', '8GB', 40.00, 'MTN', '8GB data bundle for MTN network'),
('data', '10GB', 45.00, 'MTN', '10GB data bundle for MTN network'),
('data', '15GB', 66.00, 'MTN', '15GB data bundle for MTN network'),
('data', '20GB', 85.00, 'MTN', '20GB data bundle for MTN network'),
('data', '25GB', 108.00, 'MTN', '25GB data bundle for MTN network'),
('data', '30GB', 126.00, 'MTN', '30GB data bundle for MTN network'),
('data', '40GB', 165.00, 'MTN', '40GB data bundle for MTN network'),
('data', '50GB', 197.00, 'MTN', '50GB data bundle for MTN network'),
('data', '100GB', 370.00, 'MTN', '100GB data bundle for MTN network'),

-- AirtelTigo Data Bundles
('data', '1GB', 5.00, 'AirtelTigo', '1GB data bundle for AirtelTigo network'),
('data', '2GB', 10.00, 'AirtelTigo', '2GB data bundle for AirtelTigo network'),
('data', '3GB', 15.00, 'AirtelTigo', '3GB data bundle for AirtelTigo network'),
('data', '4GB', 19.00, 'AirtelTigo', '4GB data bundle for AirtelTigo network'),
('data', '5GB', 24.00, 'AirtelTigo', '5GB data bundle for AirtelTigo network'),
('data', '6GB', 27.00, 'AirtelTigo', '6GB data bundle for AirtelTigo network'),
('data', '8GB', 36.00, 'AirtelTigo', '8GB data bundle for AirtelTigo network'),
('data', '10GB', 45.00, 'AirtelTigo', '10GB data bundle for AirtelTigo network'),
('data', '15GB', 63.00, 'AirtelTigo', '15GB data bundle for AirtelTigo network'),
('data', '20GB', 81.00, 'AirtelTigo', '20GB data bundle for AirtelTigo network'),
('data', '30GB', 90.00, 'AirtelTigo', '30GB data bundle for AirtelTigo network'),
('data', '40GB', 100.00, 'AirtelTigo', '40GB data bundle for AirtelTigo network'),
('data', '50GB', 130.00, 'AirtelTigo', '50GB data bundle for AirtelTigo network'),
('data', '100GB', 210.00, 'AirtelTigo', '100GB data bundle for AirtelTigo network'),

-- Telecel Data Bundles
('data', '5GB', 25.00, 'Telecel', '5GB data bundle for Telecel network'),
('data', '10GB', 45.00, 'Telecel', '10GB data bundle for Telecel network'),
('data', '15GB', 65.00, 'Telecel', '15GB data bundle for Telecel network'),
('data', '20GB', 87.00, 'Telecel', '20GB data bundle for Telecel network'),
('data', '30GB', 120.00, 'Telecel', '30GB data bundle for Telecel network'),
('data', '50GB', 200.00, 'Telecel', '50GB data bundle for Telecel network'),
('data', '100GB', 370.00, 'Telecel', '100GB data bundle for Telecel network'),

-- Airtime
('airtime', 'Airtime', 5.00, 'MTN', 'MTN airtime'),
('airtime', 'Airtime', 5.00, 'AirtelTigo', 'AirtelTigo airtime'),
('airtime', 'Airtime', 5.00, 'Telecel', 'Telecel airtime'),

-- Cable TV
('cable', 'Startimes Basic', 30.00, 'Startimes', 'Startimes Basic package'),
('cable', 'Startimes Smart', 50.00, 'Startimes', 'Startimes Smart package'),
('cable', 'Startimes Classic', 70.00, 'Startimes', 'Startimes Classic package'),
('cable', 'DSTV Compact', 120.00, 'DSTV', 'DSTV Compact package'),
('cable', 'DSTV Compact Plus', 180.00, 'DSTV', 'DSTV Compact Plus package'),
('cable', 'DSTV Confam', 250.00, 'DSTV', 'DSTV Confam package'),
('cable', 'DSTV Premium', 450.00, 'DSTV', 'DSTV Premium package'),

-- Result Checkers
('result_checker', 'BECE Result Checker', 5.00, NULL, 'Check BECE results'),
('result_checker', 'WASSCE Result Checker', 10.00, NULL, 'Check WASSCE results'),

-- AFA Registration
('afa', 'AFA Registration', 15.00, NULL, 'Ghana Football Association registration');

-- --------------------------------------------------------

--
-- Dumping data for table `admin_users`
--

-- Note: The password hash is for a randomly generated password
-- In production, the admin should change this immediately
INSERT INTO `admin_users` (`username`, `password_hash`) VALUES
('admin', '$2y$12$X9t6jZqyJpZqyJpZqyJpZqyJpZqyJpZqyJpZqyJpZqyJpZqyJpZqy'); -- Default password is random

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_login_logs`
--
ALTER TABLE `admin_login_logs`
  ADD CONSTRAINT `admin_login_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;