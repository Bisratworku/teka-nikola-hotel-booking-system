-- Consolidated HotelApp Database Schema
-- Version: 2.1
-- Description: Corrected schema with proper drop order and all columns.

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

--
-- Drop Tables in reverse dependency order
--
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `room_photos`;
DROP TABLE IF EXISTS `password_resets`;
DROP TABLE IF EXISTS `Services_in_Booking`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `room_services`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `rooms`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `login_attempts`;

--
-- Table structure for table `users`
--
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(80) NOT NULL,
  `email` varchar(120) NOT NULL,
  `role` enum('customer','staff','admin') DEFAULT 'customer',
  `password_hash` varchar(255) NOT NULL,
  `address` TEXT DEFAULT NULL,
  `date_of_birth` DATE DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `rooms`
--
CREATE TABLE `rooms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` varchar(10) NOT NULL,
  `type` varchar(40) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `rate_cents` int(11) NOT NULL,
  `max_guests` int(11) DEFAULT 2,
  `inventory` int(11) DEFAULT 1,
  `floor` int(11) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `number` (`number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `bookings`
--
CREATE TABLE `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `unit_number` int(11) DEFAULT NULL,
  `guests` int(11) NOT NULL DEFAULT 1,
  `check_in` date NOT NULL,
  `check_out` date NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') DEFAULT 'pending',
  `actual_check_in` datetime DEFAULT NULL,
  `actual_check_out` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `room_id` (`room_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `reviews`
--
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `rating` tinyint(4) NOT NULL CHECK (`rating` between 1 and 5),
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `room_id` (`room_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `room_photos`
--
CREATE TABLE `room_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_id` int(11) NOT NULL,
  `url` varchar(255) NOT NULL,
  `photo_type` enum('main','bathroom','other') NOT NULL DEFAULT 'main',
  `caption` varchar(140) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_room_photos_room` (`room_id`),
  CONSTRAINT `fk_room_photos_room` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `password_resets`
--
CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL UNIQUE,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `room_services`
--
CREATE TABLE `room_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `price` decimal(10, 2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `Services_in_Booking`
--
CREATE TABLE `Services_in_Booking` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `service_id` (`service_id`),
  CONSTRAINT `fk_booking_services_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_booking_services_service` FOREIGN KEY (`service_id`) REFERENCES `room_services` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Table structure for table `sessions`
--
CREATE TABLE `sessions` (
  `session_id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `data` text NOT NULL,
  `last_accessed` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


SET foreign_key_checks = 1;

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_agent` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for audit logs
CREATE TABLE `audit_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `event_type` varchar(80) NOT NULL,
  `event_details` text,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for reservation rules
CREATE TABLE `reservation_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `min_nights` int(11) NOT NULL DEFAULT 1,
  `max_nights` int(11) NOT NULL DEFAULT 30,
  `min_advance_days` int(11) NOT NULL DEFAULT 0,
  `max_advance_days` int(11) NOT NULL DEFAULT 365,
  `max_guests` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `reservation_rules` (min_nights, max_nights, min_advance_days, max_advance_days, max_guests) VALUES (1, 30, 1, 365, NULL);

--
-- Dumping data for table `users`
--
INSERT INTO `users` (`name`, `email`, `role`, `password_hash`, `address`, `date_of_birth`) VALUES
('John Doe', 'john.doe@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '123 Main St, Anytown, USA', '1990-01-15'),
('Jane Smith', 'jane.smith@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '456 Oak Ave, Somewhere, USA', '1985-05-20'),
('Peter Jones', 'peter.jones@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '789 Pine St, Nowhere, USA', '1992-08-10'),
('Mary Williams', 'mary.williams@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '101 Maple Ave, Anywhere, USA', '1988-12-01'),
('David Brown', 'david.brown@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '212 Birch St, Elsewhere, USA', '1995-03-25'),
('Susan Davis', 'susan.davis@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '323 Cedar Ave, Overthere, USA', '1980-07-30'),
('Michael Miller', 'michael.miller@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '434 Elm St, Anytown, USA', '1998-11-12'),
('Jennifer Wilson', 'jennifer.wilson@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '545 Spruce Ave, Somewhere, USA', '1983-02-18'),
('William Moore', 'william.moore@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '656 Fir St, Nowhere, USA', '1991-06-05'),
('Linda Taylor', 'linda.taylor@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '767 Redwood Ave, Anywhere, USA', '1987-09-22'),
('Richard Anderson', 'richard.anderson@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '878 Sequoia St, Elsewhere, USA', '1993-04-14'),
('Patricia Thomas', 'patricia.thomas@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '989 Willow Ave, Overthere, USA', '1989-10-28'),
('Charles Jackson', 'charles.jackson@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '109 Cypress St, Anytown, USA', '1996-01-08'),
('Barbara White', 'barbara.white@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '210 Palm Ave, Somewhere, USA', '1984-05-16'),
('Joseph Harris', 'joseph.harris@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '321 Poplar St, Nowhere, USA', '1994-08-24'),
('Sarah Martin', 'sarah.martin@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '432 Aspen Ave, Anywhere, USA', '1986-11-03'),
('Thomas Thompson', 'thomas.thompson@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '543 Cherry St, Elsewhere, USA', '1997-02-09'),
('Nancy Garcia', 'nancy.garcia@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '654 Peach Ave, Overthere, USA', '1982-06-20'),
('Daniel Martinez', 'daniel.martinez@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '765 Plum St, Anytown, USA', '1999-09-01'),
('Karen Robinson', 'karen.robinson@example.com', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '876 Pear Ave, Somewhere, USA', '1981-12-31'),
('Admin User', 'admin@example.com', 'admin', '$2y$10$RrMbSEFmfFpALUQ/oZ0/8evJT1e0/vYo0aBnB3MycocP8wpGz3d6W', NULL, NULL);

--
-- Dumping data for table `rooms`
--
INSERT INTO `rooms` (`id`, `number`, `type`, `image_url`, `description`, `rate_cents`, `max_guests`, `inventory`, `floor`, `is_active`) VALUES
(18,'101','Queen','https://images.pexels.com/photos/4890676/pexels-photo-4890676.jpeg','Cozy queen with city view',12999,2,1,1,1),
(19,'102','King','https://images.pexels.com/photos/33495802/pexels-photo-33495802.jpeg','Spacious king room near lobby',15999,3,1,1,1),
(20,'201','Suite','https://images.pexels.com/photos/2506990/pexels-photo-2506990.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2','One-bedroom suite, living area',21999,4,1,1,1),
(21,'202','Double','https://images.pexels.com/photos/20276961/pexels-photo-20276961/free-photo-of-twin-room-hotel-london-mowbray-court-hotel-central-london.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2','Two doubles for families',13999,4,1,1,1);

--
-- Dumping data for table `room_photos`
--
LOCK TABLES `room_photos` WRITE;
/*!40000 ALTER TABLE `room_photos` DISABLE KEYS */;
INSERT INTO `room_photos` VALUES (2,21,'https://images.pexels.com/photos/6585755/pexels-photo-6585755.jpeg','bathroom',NULL,'2025-09-24 20:48:40'),(4,21,'https://images.pexels.com/photos/20276961/pexels-photo-20276961.jpeg','main',NULL,'2025-09-24 20:49:48'),(5,20,'https://images.pexels.com/photos/17840522/pexels-photo-17840522.jpeg','bathroom',NULL,'2025-09-24 20:50:44'),(6,20,'https://images.pexels.com/photos/2506990/pexels-photo-2506990.jpeg','main',NULL,'2025-09-24 20:51:08'),(7,19,'https://images.pexels.com/photos/33495802/pexels-photo-33495802.jpeg','main',NULL,'2025-09-24 20:51:33'),(8,19,'https://images.pexels.com/photos/6585755/pexels-photo-6585755.jpeg','bathroom',NULL,'2025-09-24 20:52:14'),(9,18,'https://images.pexels.com/photos/6585755/pexels-photo-6585755.jpeg','bathroom',NULL,'2025-09-24 20:52:31'),(10,18,'https://images.pexels.com/photos/4890676/pexels-photo-4890676.jpeg','main',NULL,'2025-09-24 20:52:54');
/*!40000 ALTER TABLE `room_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping data for table `room_services`
--
INSERT INTO `room_services` (`name`, `price`) VALUES
('Back Massage', 45.00),
('Full Body Massage', 85.00),
('Manicure', 35.00),
('Pedicure', 40.00),
('Facial', 65.00),
('Champagne', 55.00),
('Handmade Cigar', 39.00);
