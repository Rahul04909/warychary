-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 18, 2025 at 02:44 PM
-- Server version: 9.1.0
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `warycharycare`
--

-- --------------------------------------------------------

--
-- Table structure for table `bank_details`
--

DROP TABLE IF EXISTS `bank_details`;
CREATE TABLE IF NOT EXISTS `bank_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) NOT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `is_verified` tinyint(1) DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `bank_details`
--

INSERT INTO `bank_details` (`id`, `partner_id`, `bank_name`, `account_number`, `ifsc_code`, `account_holder_name`, `is_verified`, `created_at`, `updated_at`) VALUES
(1, 1, 'Uniion Bank Of India', '644202010008357', 'UBIN0564427', 'Rahul Dhiman', 0, '2025-09-18 08:43:43', '2025-09-18 08:43:50');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `guest_name` varchar(100) NOT NULL,
  `guest_email` varchar(100) NOT NULL,
  `guest_phone` varchar(20) NOT NULL,
  `guest_state` varchar(50) NOT NULL,
  `guest_district` varchar(50) DEFAULT NULL,
  `guest_pincode` varchar(10) NOT NULL,
  `guest_full_address` text NOT NULL,
  `product_id` int NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_price` decimal(10,2) NOT NULL,
  `quantity` int DEFAULT '1',
  `total_amount` decimal(10,2) NOT NULL,
  `payment_id` varchar(255) DEFAULT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','processing','shipped','delivered','cancelled') DEFAULT 'pending',
  `razorpay_order_id` varchar(255) DEFAULT NULL,
  `razorpay_payment_id` varchar(255) DEFAULT NULL,
  `razorpay_signature` varchar(255) DEFAULT NULL,
  `tracking_number` varchar(255) DEFAULT NULL COMMENT 'Tracking number provided by courier service',
  `courier_name` varchar(100) DEFAULT NULL COMMENT 'Name of the courier/shipping company',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `product_id` (`product_id`),
  KEY `idx_tracking_number` (`tracking_number`(250)),
  KEY `idx_courier_name` (`courier_name`)
) ENGINE=MyISAM AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `guest_name`, `guest_email`, `guest_phone`, `guest_state`, `guest_district`, `guest_pincode`, `guest_full_address`, `product_id`, `product_name`, `product_price`, `quantity`, `total_amount`, `payment_id`, `payment_status`, `order_status`, `razorpay_order_id`, `razorpay_payment_id`, `razorpay_signature`, `tracking_number`, `courier_name`, `created_at`, `updated_at`) VALUES
(1, NULL, 'RAHUL DHIMAN', 'rahul.dhiman.mohanlal@gmail.com', '08059982049', 'Haryana', 'jind', '126125', 'KANDELA06', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'pending', 'pending', 'order_RIi1rguxQ9vCz6', NULL, NULL, NULL, NULL, '2025-09-17 14:55:12', '2025-09-17 14:55:14'),
(2, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 2, 'WaryChary Sanitary Pad (Pack Of 30)', 20.00, 1, 20.00, NULL, 'pending', 'pending', 'order_RIiEA7Fs31z3jr', NULL, NULL, NULL, NULL, '2025-09-17 15:06:52', '2025-09-17 15:06:53'),
(3, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'order_RIiHQ5QZVGOcDl', 'pay_RIiIkBbDDKOmSd', '8558be072b3d8f530c5b8990166e4eabcd8e7f927723b02ec09a67932cf45e55', NULL, NULL, '2025-09-17 15:09:57', '2025-09-17 15:11:28'),
(4, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'order_RIierISFhYtKQu', 'pay_RIifEqkV8NiL51', '144a4e44ba517bc099176d4773918bbbccaa3e1d77e2126895d705c7f10c6bdc', NULL, NULL, '2025-09-17 15:32:08', '2025-09-17 15:32:46'),
(5, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'order_RIindQs4lZqN6D', 'pay_RIio4er2xLIYLz', 'ddfb4b1a082c996977b0b8effcac078ba95a3c84cea6c6cf542002e20b09e7f3', NULL, NULL, '2025-09-17 15:40:27', '2025-09-17 15:41:07'),
(6, 2, 'Test User', 'user@test.com', '9876543210', 'Test State', 'Test District', '123456', 'Test Address', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', NULL, NULL, NULL, NULL, NULL, '2025-09-17 15:46:11', '2025-09-17 15:46:11'),
(7, 2, 'Test User', 'user@test.com', '9876543210', 'Test State', 'Test District', '123456', 'Test Address', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', NULL, NULL, NULL, NULL, NULL, '2025-09-17 15:47:33', '2025-09-17 15:47:33'),
(8, 2, 'Test User', 'user@test.com', '9876543210', 'Test State', 'Test District', '123456', 'Test Address', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', NULL, NULL, NULL, NULL, NULL, '2025-09-17 15:51:41', '2025-09-17 15:51:41'),
(9, 2, 'Test User', 'user@test.com', '9876543210', 'Test State', 'Test District', '123456', 'Test Address', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'AWB873273937', 'India Post', NULL, NULL, NULL, '2025-09-17 15:52:24', '2025-09-18 10:28:46'),
(10, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'order_RIj2V9MHokMUfu', 'pay_RIj2ul3zmgULrZ', 'd35fcee2ba87d3f2c76d479032c806a111a73038fe2efb197d92a0dd6f70db4e', NULL, NULL, '2025-09-17 15:54:31', '2025-09-17 15:55:10'),
(11, 1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'processing', 'rahul0049', 'India Post', 'eca1197aa055e1e1e798626b6fcb8eb19de5441b959c223318517ddce315c285', NULL, NULL, '2025-09-17 16:01:43', '2025-09-18 10:28:33'),
(12, 3, 'Anurag Dhiman', 'thesarkarinaukeri@gmail.com', '73837283738', 'Haryana', 'Jind', '126125', 'NEAR PNB BANK, V.P.O KANDELA, JIND, HARYANA', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'confirmed', 'order_RJ1uoeCF7IfZdE', 'pay_RJ1vFNwTGPV0yz', '95f01aecce835977bbf67a5eb1a4d7aa43e5bbd34003c0bce8d7f0dcdf6733e5', NULL, NULL, '2025-09-18 10:22:24', '2025-09-18 10:23:05'),
(13, 4, 'shilpa brar', 'shilpabrar7@gmail.com', '9350103202', 'Haryana', 'Jind', '126102', 'ShivColonyJind', 1, 'WaryChary Sanitary Pad (Pack Of 20)', 1.00, 1, 1.00, NULL, 'completed', 'pending', 'order_RJ2VbhnY3u7DIW', 'pay_RJ2VxoF7AanUQQ', '58800a0a8b4742ba8e090fb4c27e16c40cf5b506e7131d9dc4528e8a767d380b', 'AWB873273937', 'India Post', '2025-09-18 10:57:14', '2025-09-18 14:38:26');

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

DROP TABLE IF EXISTS `partners`;
CREATE TABLE IF NOT EXISTS `partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` varchar(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` enum('Male','Female','Other') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `state` varchar(255) NOT NULL,
  `district` varchar(255) NOT NULL,
  `pincode` varchar(6) NOT NULL,
  `full_address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `referral_code` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `earning` decimal(10,2) DEFAULT '0.00',
  `total_earnings` decimal(10,2) DEFAULT '0.00',
  `referred_by_senior_partner` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `referred_by_senior_partner` (`referred_by_senior_partner`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `partner_id`, `name`, `email`, `phone`, `gender`, `image`, `state`, `district`, `pincode`, `full_address`, `password`, `referral_code`, `status`, `created_at`, `updated_at`, `earning`, `total_earnings`, `referred_by_senior_partner`) VALUES
(1, '56B6C355', 'Aman Dhiman', 'jhdindustrialsolution@gmail.com', '8078850999', 'Male', 'images/partners/partner_68ca756b6bb3a.webp', 'Haryana', 'Jind', '126125', 'Near Pnb Bank, Kandela, Jind, Haryana India', '$2y$10$VYxv1Zl4bo51e8ubS8DM/.t7sdfqOcQ/BeS0PBZ2aiC/GVOO5xzWW', 'rahul.dhiman.mohanlal@gmail.com', 'approved', '2025-09-17 08:46:35', '2025-09-18 10:46:03', 0.00, 0.90, 1),
(2, '419MAD0Y', 'Sakshi Sharma', 'sakshisharma27511@gmail.com', '9350103202', 'Male', 'images/partners/partner_68ca77773dcbe.webp', 'Haryana', 'Jind', '126125', 'Near Shiv Mandir, Kandela, Jind, Haryana, India', '$2y$10$VYxv1Zl4bo51e8ubS8DM/.t7sdfqOcQ/BeS0PBZ2aiC/GVOO5xzWW', 'rahul.dhiman.mohanlal@gmail.com', 'approved', '2025-09-17 08:55:19', '2025-09-18 10:46:03', 0.00, 0.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `partner_bank_details`
--

DROP TABLE IF EXISTS `partner_bank_details`;
CREATE TABLE IF NOT EXISTS `partner_bank_details` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `bank_name` varchar(255) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `ifsc_code` varchar(20) NOT NULL,
  `account_holder_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_partner_bank` (`partner_id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `partner_bank_details`
--

INSERT INTO `partner_bank_details` (`id`, `partner_id`, `bank_name`, `account_number`, `ifsc_code`, `account_holder_name`, `created_at`, `updated_at`) VALUES
(1, 1, 'PUNJAB NATIONAL BANK', '1196000100351034', 'PUNB0119600', 'Rahul', '2025-09-18 08:37:50', '2025-09-18 08:39:16');

-- --------------------------------------------------------

--
-- Table structure for table `partner_earnings`
--

DROP TABLE IF EXISTS `partner_earnings`;
CREATE TABLE IF NOT EXISTS `partner_earnings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `earning_amount` decimal(10,2) NOT NULL,
  `earning_percentage` decimal(5,2) NOT NULL,
  `order_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `partner_earnings`
--

INSERT INTO `partner_earnings` (`id`, `partner_id`, `order_id`, `user_id`, `earning_amount`, `earning_percentage`, `order_amount`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 2, 0.15, 15.00, 1.00, '', '2025-09-17 15:46:11', '2025-09-17 15:46:11'),
(2, 1, 7, 2, 0.15, 15.00, 1.00, '', '2025-09-17 15:47:33', '2025-09-17 15:47:33'),
(3, 1, 8, 2, 0.15, 15.00, 1.00, '', '2025-09-17 15:51:41', '2025-09-17 15:51:41'),
(4, 1, 9, 2, 0.15, 15.00, 1.00, '', '2025-09-17 15:52:24', '2025-09-17 15:52:24'),
(5, 1, 10, 1, 0.15, 15.00, 1.00, 'pending', '2025-09-17 15:55:13', '2025-09-17 15:55:13'),
(6, 1, 11, 1, 0.15, 15.00, 1.00, 'pending', '2025-09-17 16:02:24', '2025-09-17 16:02:24');

-- --------------------------------------------------------

--
-- Table structure for table `partner_payout_history`
--

DROP TABLE IF EXISTS `partner_payout_history`;
CREATE TABLE IF NOT EXISTS `partner_payout_history` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` int NOT NULL,
  `payout_amount` decimal(10,2) NOT NULL,
  `payout_date` date NOT NULL,
  `payout_month` varchar(7) NOT NULL,
  `earnings_before_payout` decimal(10,2) NOT NULL,
  `earnings_after_payout` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('bank_transfer','upi','cash','cheque') DEFAULT 'bank_transfer',
  `transaction_reference` varchar(255) DEFAULT NULL,
  `notes` text,
  `processed_by` varchar(255) DEFAULT 'Admin',
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_partner_payout` (`partner_id`,`payout_month`),
  KEY `idx_payout_date` (`payout_date`),
  KEY `idx_payout_month` (`payout_month`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
CREATE TABLE IF NOT EXISTS `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `product_image` varchar(255) NOT NULL,
  `product_description` text NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `sales_price` decimal(10,2) NOT NULL,
  `mrp` decimal(10,2) NOT NULL,
  `offer_product_name` varchar(255) DEFAULT NULL,
  `offer_product_image` varchar(255) DEFAULT NULL,
  `offer_product_purchase_price` decimal(10,2) DEFAULT NULL,
  `offer_product_sales_price` decimal(10,2) DEFAULT NULL,
  `offer_product_mrp` decimal(10,2) DEFAULT NULL,
  `delivery_cost` decimal(10,2) NOT NULL,
  `packing_cost` decimal(10,2) NOT NULL,
  `total_expense` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `product_image`, `product_description`, `purchase_price`, `sales_price`, `mrp`, `offer_product_name`, `offer_product_image`, `offer_product_purchase_price`, `offer_product_sales_price`, `offer_product_mrp`, `delivery_cost`, `packing_cost`, `total_expense`, `created_at`, `updated_at`) VALUES
(1, 'WaryChary Sanitary Pad (Pack Of 20)', 'product_68caabec3fea6.png', '<p data-start=\"371\" data-end=\"607\">Stay confident and worry-free during your periods with <strong data-start=\"426\" data-end=\"453\">WaryChary Sanitary Pads</strong>. Designed for maximum comfort and long-lasting protection, this <strong data-start=\"518\" data-end=\"553\">Pack of 20 ultra-absorbent pads</strong> is your perfect companion for those challenging days.</p><p>\r\n</p><p data-start=\"609\" data-end=\"888\">Crafted with a soft cottony top layer and advanced gel-lock technology, these pads quickly absorb heavy flow and lock it in, keeping you dry and fresh for hours. The extra-wide wings ensure a secure fit with no shifting or leakage, whether you\'re at work, traveling, or sleeping.</p>', 70.00, 1.00, 100.00, 'T Shirt', 'offer_68ca97fe35ed2.webp', 30.00, 0.00, 0.00, 20.00, 30.00, 150.00, '2025-09-17 11:14:06', '2025-09-17 12:39:08'),
(2, 'WaryChary Sanitary Pad (Pack Of 30)', 'product_68ca9b928caaf.png', '<h4 data-start=\"1102\" data-end=\"1134\"><strong data-start=\"1107\" data-end=\"1132\">Why Choose WaryChary?</strong></h4><ul data-start=\"1135\" data-end=\"1332\">\r\n<li data-start=\"1135\" data-end=\"1225\">\r\n<p data-start=\"1137\" data-end=\"1225\">Thoughtfully designed for everyday use — whether at home, at work, or while traveling.</p>\r\n</li>\r\n<li data-start=\"1226\" data-end=\"1271\">\r\n<p data-start=\"1228\" data-end=\"1271\">Reliable during light to heavy flow days.</p>\r\n</li>\r\n<li data-start=\"1272\" data-end=\"1332\">\r\n<p data-start=\"1274\" data-end=\"1332\">Made with skin-safe materials and eco-conscious processes.</p>\r\n</li>\r\n</ul><h4 data-start=\"1334\" data-end=\"1359\"><strong data-start=\"1339\" data-end=\"1357\">Pack Contains:</strong></h4><p>\r\n\r\n\r\n</p><ul data-start=\"1360\" data-end=\"1455\">\r\n<li data-start=\"1360\" data-end=\"1401\">\r\n<p data-start=\"1362\" data-end=\"1401\">30 individually wrapped sanitary pads</p>\r\n</li>\r\n<li data-start=\"1402\" data-end=\"1430\">\r\n<p data-start=\"1404\" data-end=\"1430\">Size: Regular with wings</p>\r\n</li>\r\n<li data-start=\"1431\" data-end=\"1455\">\r\n<p data-start=\"1433\" data-end=\"1455\">Eco-friendly packaging</p></li></ul>', 100.00, 20.00, 100.00, 'T Shirt', 'offer_68ca9b70a5227.png', 200.00, 0.00, 0.00, 90.00, 80.00, 470.00, '2025-09-17 11:28:48', '2025-09-17 11:29:22');

-- --------------------------------------------------------

--
-- Table structure for table `razorpay_settings`
--

DROP TABLE IF EXISTS `razorpay_settings`;
CREATE TABLE IF NOT EXISTS `razorpay_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `razorpay_key_id` varchar(255) NOT NULL,
  `razorpay_key_secret` varchar(255) NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `razorpay_settings`
--

INSERT INTO `razorpay_settings` (`id`, `razorpay_key_id`, `razorpay_key_secret`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'rzp_live_RGl6Kw27psVjyZ', 'CpSqyUW5IE84XS2w47dKAMoW', 1, '2025-09-16 16:29:14', '2025-09-16 16:29:14');

-- --------------------------------------------------------

--
-- Table structure for table `senior_partners`
--

DROP TABLE IF EXISTS `senior_partners`;
CREATE TABLE IF NOT EXISTS `senior_partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_id` varchar(8) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(191) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `district` varchar(255) DEFAULT NULL,
  `full_address` text,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `earning` decimal(10,2) DEFAULT '0.00',
  `total_earnings` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`),
  UNIQUE KEY `partner_id` (`partner_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `senior_partners`
--

INSERT INTO `senior_partners` (`id`, `partner_id`, `name`, `email`, `phone`, `image`, `gender`, `state`, `district`, `full_address`, `password`, `created_at`, `updated_at`, `earning`, `total_earnings`) VALUES
(1, 'WO7QZ3KM', 'Rahul Dhiman', 'rahul.dhiman.mohanlal@gmail.com', '8059982049', 'images/senior-partners/partner_68c9a3ff6eebb.jpg', 'Male', 'Haryana', 'Jind', 'NEAR PNB BANK, V.P.O KANDELA, JIND, HARYANA', '$2y$10$x1ct/ixclyN8/BLPI0fJdOGDxkz48RelFA.fPAdDMIzGrV..khexC', '2025-09-16 17:53:03', '2025-09-18 07:58:23', 2.00, 0.14),
(2, 'VHF1S2N2', 'Anurag Dhiman', 'ecemanager.online@gmail.com', '9261836281', 'images/senior-partners/partner_68c9a59274b36.webp', 'Male', 'Haryana', 'Jind', 'Kandela jind', '$2y$10$40SAy9cyyQxrSudZTL/nuu0fOA1C4tHvS2st63IXxMfW1mMIbWO96', '2025-09-16 17:59:46', '2025-09-16 18:14:41', 2.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `senior_partner_earnings`
--

DROP TABLE IF EXISTS `senior_partner_earnings`;
CREATE TABLE IF NOT EXISTS `senior_partner_earnings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `senior_partner_id` int NOT NULL,
  `partner_id` int NOT NULL,
  `order_id` int NOT NULL,
  `user_id` int NOT NULL,
  `earning_amount` decimal(10,2) NOT NULL,
  `earning_percentage` decimal(5,2) NOT NULL,
  `order_amount` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `senior_partner_id` (`senior_partner_id`),
  KEY `partner_id` (`partner_id`),
  KEY `order_id` (`order_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `senior_partner_earnings`
--

INSERT INTO `senior_partner_earnings` (`id`, `senior_partner_id`, `partner_id`, `order_id`, `user_id`, `earning_amount`, `earning_percentage`, `order_amount`, `created_at`) VALUES
(1, 1, 1, 9, 2, 0.02, 2.00, 1.00, '2025-09-17 15:52:46'),
(2, 1, 1, 6, 2, 0.02, 2.00, 1.00, '2025-09-17 15:52:46'),
(3, 1, 1, 7, 2, 0.02, 2.00, 1.00, '2025-09-17 15:52:46'),
(4, 1, 1, 8, 2, 0.02, 2.00, 1.00, '2025-09-17 15:52:46'),
(5, 1, 1, 9, 2, 0.02, 2.00, 1.00, '2025-09-17 15:52:46'),
(6, 1, 1, 10, 1, 0.02, 2.00, 1.00, '2025-09-17 15:55:13'),
(7, 1, 1, 11, 1, 0.02, 2.00, 1.00, '2025-09-17 16:02:24'),
(8, 1, 1, 0, 1, 150.00, 5.00, 3000.00, '2025-09-13 01:27:27'),
(9, 1, 2, 0, 2, 200.00, 5.00, 4000.00, '2025-09-15 01:27:27'),
(10, 1, 1, 0, 1, 100.00, 5.00, 2000.00, '2025-09-17 01:27:27'),
(11, 1, 2, 0, 2, 250.00, 5.00, 5000.00, '2025-09-18 01:27:27'),
(12, 1, 1, 0, 1, 150.00, 5.00, 3000.00, '2025-09-13 03:33:55'),
(13, 1, 2, 0, 2, 200.00, 5.00, 4000.00, '2025-09-15 03:33:55'),
(14, 1, 1, 0, 1, 100.00, 5.00, 2000.00, '2025-09-17 03:33:55'),
(15, 1, 2, 0, 2, 250.00, 5.00, 5000.00, '2025-09-18 03:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `smtp_settings`
--

DROP TABLE IF EXISTS `smtp_settings`;
CREATE TABLE IF NOT EXISTS `smtp_settings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `smtp_host` varchar(255) NOT NULL,
  `smtp_port` int NOT NULL,
  `smtp_username` varchar(255) NOT NULL,
  `smtp_password` varchar(255) NOT NULL,
  `smtp_from_email` varchar(255) NOT NULL,
  `smtp_from_name` varchar(255) NOT NULL,
  `smtp_encryption` enum('tls','ssl') NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `smtp_settings`
--

INSERT INTO `smtp_settings` (`id`, `smtp_host`, `smtp_port`, `smtp_username`, `smtp_password`, `smtp_from_email`, `smtp_from_name`, `smtp_encryption`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'mail.warychary.com', 465, 'info@warychary.com', 'Sukh@2025', 'info@warychary.com', 'WaryChary Care', 'ssl', 1, '2025-09-16 14:33:19', '2025-09-16 14:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `state` varchar(50) NOT NULL,
  `district` varchar(50) NOT NULL,
  `pincode` varchar(10) NOT NULL,
  `full_address` text NOT NULL,
  `password` varchar(255) NOT NULL,
  `referral_code` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `referred_by_partner` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `referred_by_partner` (`referred_by_partner`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `phone`, `gender`, `image`, `state`, `district`, `pincode`, `full_address`, `password`, `referral_code`, `created_at`, `updated_at`, `referred_by_partner`) VALUES
(1, 'Dipanshu Mehra', 'jobysthanindia@gmail.com', '9729187489', 'male', 'images/users/user_68ca85957f00d.webp', 'Haryana', 'Jind', '126127', 'Near Pnb Bank, Kandela, Jind, Haryana', '$2y$10$DH5eNTtK3Iv24H7Pb.deOODHiO3qgmytDTT7kKCC6iCOG91F1SFpa', '9350103202', '2025-09-17 09:55:33', '2025-09-17 15:48:43', 1),
(2, 'Test User', 'user@test.com', '1234567892', 'male', NULL, '', '', '', '', '$2y$10$Nu5zWitCna4O1Tjj/cIyGeJ6W/Igy99hynfI8SvYm/o/CB0URglt2', NULL, '2025-09-17 15:31:05', '2025-09-17 15:31:05', 1),
(3, 'Anurag Dhiman', 'thesarkarinaukeri@gmail.com', '73837283738', 'male', 'images/users/user_68cbdd0f5fa8d.webp', 'Haryana', 'Jind', '126125', 'NEAR PNB BANK, V.P.O KANDELA, JIND, HARYANA', '$2y$10$CY1YvoE0Tkk4jPOUGxU/t.VHZIYhkSyEfIwM20dojOBzZ8eAsfldK', '419MAD0Y', '2025-09-18 10:21:03', '2025-09-18 14:41:31', 2),
(4, 'shilpa brar', 'shilpabrar7@gmail.com', '9350103202', 'female', 'images/users/user_68cbe54e4b696.jpg', 'Haryana', 'Jind', '126102', 'ShivColonyJind', '$2y$10$BKOXqRCcFKeF2z9KW6TVyu2CsI3BMEkAvT7IpVF69klmzp5hMiz.K', 'sakshisharma27511@gmail.com', '2025-09-18 10:56:14', '2025-09-18 10:56:14', 2);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
