-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 19, 2025 at 02:33 PM
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
-- Database: `isecure`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_audit_logs`
--

CREATE TABLE `admin_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_audit_logs`
--

INSERT INTO `admin_audit_logs` (`id`, `user_id`, `action`, `ip_address`, `user_agent`, `created_at`) VALUES
(32, '690b82279e279', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 11:06:41'),
(33, '690b82279e279', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 11:21:07'),
(34, 'ff94cfd4-c538-11f0-bf01-9c6b0035cf26', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 11:21:39'),
(35, 'ff94cfd4-c538-11f0-bf01-9c6b0035cf26', 'User logged out', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 11:23:13'),
(36, '690b82279e279', 'User logged in', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '2025-11-19 11:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `clearance_badges`
--

CREATE TABLE `clearance_badges` (
  `id` int(11) NOT NULL,
  `visitor_id` int(11) NOT NULL,
  `clearance_level` varchar(255) NOT NULL,
  `key_card_number` varchar(50) NOT NULL,
  `card_name` varchar(255) DEFAULT NULL,
  `validity_start` datetime NOT NULL,
  `validity_end` datetime NOT NULL,
  `status` enum('active','inactive','terminated','expired') DEFAULT 'active',
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `door` enum('DOOR1','DOOR2','ALL') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deleted_users`
--

CREATE TABLE `deleted_users` (
  `id` char(36) NOT NULL,
  `full_name` text NOT NULL,
  `email` text NOT NULL,
  `rank` text DEFAULT NULL,
  `status` text DEFAULT NULL,
  `role` text DEFAULT NULL,
  `password_hash` text DEFAULT NULL,
  `joined_date` datetime DEFAULT NULL,
  `last_active` datetime DEFAULT NULL,
  `deleted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_audit_logs`
--

CREATE TABLE `landing_audit_logs` (
  `id` int(11) NOT NULL,
  `user_id` varchar(255) DEFAULT NULL,
  `action` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` char(36) NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_status` enum('Read','Unread') DEFAULT 'Unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` char(36) NOT NULL,
  `reset_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `reset_token`, `expires_at`) VALUES
(1, '690b82279e279', '1e1547858e204ce11dd9c5cced928d408e3bef463670213ab3d3b94fdd5d51cd', '2025-11-15 02:03:36'),
(2, '690b82279e279', '29a0c15bcc9d7cb52cedcf9e80ca80f074f1e949527891e78ad8fa03dabc40a3', '2025-11-15 02:03:45');

-- --------------------------------------------------------

--
-- Table structure for table `personnel_sessions`
--

CREATE TABLE `personnel_sessions` (
  `id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `token` varchar(64) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personnel_sessions`
--

INSERT INTO `personnel_sessions` (`id`, `user_id`, `token`, `created_at`, `expires_at`) VALUES
('691daa55d0193', '690b82279e279', '7bde5f931765485eb92e49e03f969f2dc4f3b4bf5bc832981a3e1eaefc29a5fd', '2025-11-19 19:30:29', '2025-11-19 20:30:29');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` char(36) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `rank` varchar(50) NOT NULL,
  `status` enum('Active','Inactive','Banned','Pending','Suspended') DEFAULT 'Active',
  `password_hash` varchar(255) NOT NULL,
  `role` enum('Admin','User','Moderator','Guest') DEFAULT 'User',
  `joined_date` datetime DEFAULT current_timestamp(),
  `last_active` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `rank`, `status`, `password_hash`, `role`, `joined_date`, `last_active`) VALUES
('690b82279e279', 'System Admin', 'admin@example.com', 'Captain', 'Active', '$2y$10$qFlN/pa.jLW3gqHCw6X6DeB6abZvjUy4/8ZZrnw4W/n/KEM1AAyIy', 'Admin', '2025-11-06 00:58:15', '2025-11-06 00:58:15'),
('ff94cfd4-c538-11f0-bf01-9c6b0035cf26', 'Gabriel', 'gabriel@example.com', 'General', 'Active', '$2y$10$ubZcAemhY2OTfF3RgtmxXu9L3KUzKBoCniwFFUUnaJ46S9mvrydPu', 'Admin', '2025-11-19 19:14:28', '2025-11-19 19:14:28');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `id` int(11) NOT NULL,
  `visitation_id` int(11) DEFAULT NULL,
  `vehicle_owner` varchar(100) NOT NULL,
  `vehicle_brand` varchar(100) NOT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `plate_number` varchar(50) NOT NULL,
  `vehicle_photo_path` varchar(255) DEFAULT NULL,
  `vehicle_photo_compressed` longblob DEFAULT NULL,
  `entry_time` datetime DEFAULT current_timestamp(),
  `exit_time` datetime DEFAULT NULL,
  `status` enum('Expected','Inside','Exited') DEFAULT 'Expected'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`id`, `visitation_id`, `vehicle_owner`, `vehicle_brand`, `vehicle_model`, `vehicle_color`, `plate_number`, `vehicle_photo_path`, `vehicle_photo_compressed`, `entry_time`, `exit_time`, `status`) VALUES
(7, 10, 'Gabriel De Mesa Pecson', 'toyota', '', 'blue', 'RMG 631', NULL, NULL, '2025-11-19 19:12:01', '2025-11-19 19:30:50', 'Exited');

-- --------------------------------------------------------

--
-- Table structure for table `visitation_requests`
--

CREATE TABLE `visitation_requests` (
  `id` int(11) NOT NULL,
  `first_name` varchar(255) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(255) DEFAULT NULL,
  `home_address` varchar(255) NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `email` varchar(100) NOT NULL,
  `valid_id_path` varchar(255) NOT NULL,
  `selfie_photo_path` varchar(255) NOT NULL,
  `vehicle_owner` varchar(100) DEFAULT NULL,
  `vehicle_brand` varchar(100) DEFAULT NULL,
  `plate_number` varchar(50) DEFAULT NULL,
  `vehicle_color` varchar(50) DEFAULT NULL,
  `vehicle_model` varchar(100) DEFAULT NULL,
  `vehicle_photo_path` varchar(255) DEFAULT NULL,
  `reason` text NOT NULL,
  `personnel_related` varchar(100) DEFAULT NULL,
  `visit_date` date NOT NULL,
  `visit_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Approved','Rejected','Cancelled') DEFAULT NULL,
  `office_to_visit` enum('ICT Facility','Training Facility','Personnels Office') DEFAULT NULL,
  `driver_name` varchar(255) DEFAULT NULL,
  `driver_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitation_requests`
--

INSERT INTO `visitation_requests` (`id`, `first_name`, `middle_name`, `last_name`, `home_address`, `contact_number`, `email`, `valid_id_path`, `selfie_photo_path`, `vehicle_owner`, `vehicle_brand`, `plate_number`, `vehicle_color`, `vehicle_model`, `vehicle_photo_path`, `reason`, `personnel_related`, `visit_date`, `visit_time`, `created_at`, `status`, `office_to_visit`, `driver_name`, `driver_id`) VALUES
(10, 'Gabriel', 'De Mesa', 'Pecson', 'purok 6', '09289555509', 'gidpecs@gmail.com', 'php/routes/Pages/uploads/ids/1763550241_id.jpg', 'php/routes/Pages/uploads/selfies/86aa162bc04374ddcd0f269d40e5ce250438967603b85aa6581d0919eb7c2985.jpg', 'Gabriel De Mesa Pecson', 'toyota', 'RMG 631', 'blue', '', NULL, 'Visitation', 'Gabriel Pecson', '2025-11-21', '12:00:00', '2025-11-19 11:04:01', 'Approved', 'ICT Facility', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `visitors`
--

CREATE TABLE `visitors` (
  `id` int(11) NOT NULL,
  `visitation_request_id` int(11) DEFAULT NULL,
  `first_name` varchar(50) DEFAULT NULL,
  `middle_name` varchar(255) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `id_photo_path` varchar(255) DEFAULT NULL,
  `id_photo_compressed` longblob DEFAULT NULL,
  `selfie_photo_path` varchar(255) DEFAULT NULL,
  `selfie_photo_compressed` longblob DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `date` date NOT NULL,
  `time_in` time DEFAULT NULL,
  `time_out` time DEFAULT NULL,
  `status` enum('Expected','Inside','Exited','Cancelled') DEFAULT NULL,
  `key_card_number` varchar(255) DEFAULT NULL,
  `office_to_visit` enum('ICT Facility','Training Facility','Personnels Office') DEFAULT NULL,
  `personnel_related` varchar(100) DEFAULT NULL,
  `visitation_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitors`
--

INSERT INTO `visitors` (`id`, `visitation_request_id`, `first_name`, `middle_name`, `last_name`, `contact_number`, `email`, `address`, `id_photo_path`, `id_photo_compressed`, `selfie_photo_path`, `selfie_photo_compressed`, `reason`, `date`, `time_in`, `time_out`, `status`, `key_card_number`, `office_to_visit`, `personnel_related`, `visitation_id`) VALUES
(10, NULL, 'Gabriel', 'De Mesa', 'Pecson', '09289555509', 'gidpecs@gmail.com', 'purok 6', 'php/routes/Pages/uploads/ids/1763550241_id.jpg', NULL, 'php/routes/Pages/uploads/selfies/86aa162bc04374ddcd0f269d40e5ce250438967603b85aa6581d0919eb7c2985.jpg', NULL, 'Visitation', '2025-11-21', '19:12:01', '19:30:50', 'Exited', NULL, 'ICT Facility', 'Gabriel Pecson', 10);

-- --------------------------------------------------------

--
-- Table structure for table `visitor_sessions`
--

CREATE TABLE `visitor_sessions` (
  `user_token` char(64) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `selfie_photo_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `visitor_sessions`
--

INSERT INTO `visitor_sessions` (`user_token`, `created_at`, `expires_at`, `selfie_photo_path`) VALUES
('86aa162bc04374ddcd0f269d40e5ce250438967603b85aa6581d0919eb7c2985', '2025-11-19 11:03:26', '2025-11-19 20:03:26', 'php/routes/Pages/uploads/selfies/86aa162bc04374ddcd0f269d40e5ce250438967603b85aa6581d0919eb7c2985.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `clearance_badges`
--
ALTER TABLE `clearance_badges`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitor_id` (`visitor_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `deleted_users`
--
ALTER TABLE `deleted_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `landing_audit_logs`
--
ALTER TABLE `landing_audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_landing_session` (`user_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `reset_token` (`reset_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `personnel_sessions`
--
ALTER TABLE `personnel_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visitation_id` (`visitation_id`);

--
-- Indexes for table `visitation_requests`
--
ALTER TABLE `visitation_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visitors`
--
ALTER TABLE `visitors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_visitors_visitation` (`visitation_id`);

--
-- Indexes for table `visitor_sessions`
--
ALTER TABLE `visitor_sessions`
  ADD PRIMARY KEY (`user_token`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_audit_logs`
--
ALTER TABLE `admin_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `clearance_badges`
--
ALTER TABLE `clearance_badges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landing_audit_logs`
--
ALTER TABLE `landing_audit_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `visitation_requests`
--
ALTER TABLE `visitation_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `visitors`
--
ALTER TABLE `visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `clearance_badges`
--
ALTER TABLE `clearance_badges`
  ADD CONSTRAINT `clearance_badges_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `landing_audit_logs`
--
ALTER TABLE `landing_audit_logs`
  ADD CONSTRAINT `fk_landing_session` FOREIGN KEY (`user_id`) REFERENCES `visitor_sessions` (`user_token`) ON DELETE CASCADE;

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `personnel_sessions`
--
ALTER TABLE `personnel_sessions`
  ADD CONSTRAINT `personnel_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_ibfk_1` FOREIGN KEY (`visitation_id`) REFERENCES `visitation_requests` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visitors`
--
ALTER TABLE `visitors`
  ADD CONSTRAINT `fk_visitors_visitation` FOREIGN KEY (`visitation_id`) REFERENCES `visitation_requests` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
