-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 06:49 PM
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
-- Database: `doorlock`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_logs`
--

CREATE TABLE `access_logs` (
  `log_id` int(11) NOT NULL,
  `uid` varchar(32) DEFAULT NULL,
  `door` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `access_logs`
--

INSERT INTO `access_logs` (`log_id`, `uid`, `door`, `status`, `reason`, `timestamp`) VALUES
(1, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:55:43'),
(2, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:55:47'),
(3, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:55:53'),
(4, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:55:54'),
(5, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:55:57'),
(6, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:56:00'),
(7, '6C817F05', 'DOOR1', 'REJECTED', 'NOT_FOUND_OR_INVALID', '2025-11-11 06:56:05'),
(8, '6C817F05', 'DOOR2', 'REJECTED', 'NOT_FOUND_OR_INVALID', '2025-11-11 06:56:08'),
(9, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:57:25'),
(10, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:57:31'),
(11, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:57:37'),
(12, '91098105', 'DOOR1', 'REJECTED', 'NOT_FOUND_OR_INVALID', '2025-11-11 06:59:19'),
(13, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:59:27'),
(14, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 06:59:30'),
(15, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:59:33'),
(16, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 06:59:35'),
(17, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 07:00:34'),
(18, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED', '2025-11-11 07:01:00'),
(19, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 07:26:01'),
(20, 'F73DA889', 'DOOR2', 'REJECTED', 'DOOR_MISMATCH', '2025-11-11 07:26:06'),
(21, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 07:29:32'),
(22, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 07:29:38'),
(23, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 07:31:16'),
(24, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 07:31:42'),
(25, '91098105', 'DOOR1', 'REJECTED', 'NOT_FOUND_OR_INVALID', '2025-11-11 11:26:27'),
(26, '91098105', 'DOOR1', 'REJECTED', 'NOT_FOUND_OR_INVALID', '2025-11-11 11:26:30'),
(27, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 11:26:36'),
(28, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 11:26:42'),
(29, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:37:04'),
(30, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:37:11'),
(31, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:37:17'),
(32, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:41:54'),
(33, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:42:07'),
(34, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:44:30'),
(35, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:44:45'),
(36, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:44:55'),
(37, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:45:48'),
(38, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:45:54'),
(39, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:46:05'),
(40, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:48:24'),
(41, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:52:06'),
(42, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:52:10'),
(43, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:52:17'),
(44, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:52:20'),
(45, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:16'),
(46, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:26'),
(47, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:31'),
(48, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:42'),
(49, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:47'),
(50, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:53:52'),
(51, 'F73DA889', 'DOOR1', 'GRANTED', 'MATCHED_DOOR1', '2025-11-11 14:54:03');

-- --------------------------------------------------------

--
-- Table structure for table `card_holders`
--

CREATE TABLE `card_holders` (
  `holder_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `card_holders`
--

INSERT INTO `card_holders` (`holder_id`, `first_name`, `last_name`) VALUES
(1, 'Test', 'User'),
(2, 'Mark', 'Lim'),
(3, 'Mark', 'Lim');

-- --------------------------------------------------------

--
-- Table structure for table `registered_cards`
--

CREATE TABLE `registered_cards` (
  `card_id` int(11) NOT NULL,
  `uid` varchar(32) NOT NULL,
  `holder_id` int(11) DEFAULT NULL,
  `door` varchar(20) DEFAULT 'ALL',
  `valid_from` datetime DEFAULT NULL,
  `valid_to` datetime DEFAULT NULL,
  `status` enum('ACTIVE','INACTIVE') DEFAULT 'ACTIVE',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registered_cards`
--

INSERT INTO `registered_cards` (`card_id`, `uid`, `holder_id`, `door`, `valid_from`, `valid_to`, `status`, `created_at`) VALUES
(1, 'DB9E6A05', NULL, 'ALL', NULL, NULL, 'ACTIVE', '2025-11-11 06:24:11'),
(2, '91098105', NULL, 'ALL', NULL, NULL, 'ACTIVE', '2025-11-11 06:24:14'),
(3, 'F73DA889', 3, 'DOOR1', '2025-11-11 14:30:00', '2025-11-12 14:45:00', 'ACTIVE', '2025-11-11 06:24:17'),
(4, 'F5A88005', NULL, 'ALL', NULL, NULL, 'ACTIVE', '2025-11-11 06:24:18'),
(5, '6C817F05', NULL, 'ALL', NULL, NULL, 'ACTIVE', '2025-11-11 06:24:20');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`log_id`);

--
-- Indexes for table `card_holders`
--
ALTER TABLE `card_holders`
  ADD PRIMARY KEY (`holder_id`);

--
-- Indexes for table `registered_cards`
--
ALTER TABLE `registered_cards`
  ADD PRIMARY KEY (`card_id`),
  ADD UNIQUE KEY `uid` (`uid`),
  ADD KEY `fk_registered_cards_holder_id` (`holder_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT for table `card_holders`
--
ALTER TABLE `card_holders`
  MODIFY `holder_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `registered_cards`
--
ALTER TABLE `registered_cards`
  MODIFY `card_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `registered_cards`
--
ALTER TABLE `registered_cards`
  ADD CONSTRAINT `fk_registered_cards_holder_id` FOREIGN KEY (`holder_id`) REFERENCES `card_holders` (`holder_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
