-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 29, 2025 at 10:49 PM
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
-- Database: `kene_therapy`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `hour` time NOT NULL,
  `status` enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`id`, `user_id`, `location_id`, `date`, `hour`, `status`, `created_at`, `updated_at`) VALUES
(133, 12, 2, '2025-04-28', '08:00:00', 'cancelled', '2025-04-27 21:26:20', '2025-04-29 12:28:49'),
(134, 52, 2, '2025-04-28', '09:00:00', 'confirmed', '2025-04-27 21:34:38', '2025-04-28 22:18:34'),
(136, 5, 2, '2025-05-01', '10:00:00', 'cancelled', '2025-04-29 08:00:00', '2025-04-29 12:42:55'),
(137, 6, 3, '2025-05-02', '14:00:00', 'confirmed', '2025-04-29 09:00:00', '2025-04-28 22:18:34'),
(139, 8, 5, '2025-05-04', '15:30:00', 'completed', '2025-04-29 11:00:00', '2025-04-28 22:18:34'),
(140, 9, 8, '2025-05-05', '09:15:00', 'confirmed', '2025-04-29 12:00:00', '2025-04-28 22:18:34'),
(144, 13, 2, '2025-05-09', '13:00:00', 'cancelled', '2025-04-29 16:00:00', '2025-04-28 22:18:34'),
(145, 14, 3, '2025-05-10', '10:30:00', 'confirmed', '2025-04-29 17:00:00', '2025-04-28 22:18:34'),
(147, 16, 5, '2025-05-12', '08:00:00', 'confirmed', '2025-04-29 19:00:00', '2025-04-28 22:18:34'),
(148, 28, 8, '2025-05-13', '14:30:00', 'pending', '2025-04-29 20:00:00', '2025-04-28 22:18:34'),
(152, 32, 2, '2025-05-17', '12:15:00', 'pending', '2025-04-30 00:00:00', '2025-04-28 22:18:34'),
(153, 33, 3, '2025-05-18', '15:00:00', 'completed', '2025-04-30 01:00:00', '2025-04-28 22:18:34'),
(156, 5, 2, '2025-05-01', '10:00:00', 'confirmed', '2025-04-29 08:00:00', '2025-04-28 22:18:34'),
(157, 6, 3, '2025-05-02', '14:00:00', 'confirmed', '2025-04-29 09:00:00', '2025-04-28 22:18:34'),
(159, 8, 5, '2025-05-04', '15:30:00', 'completed', '2025-04-29 11:00:00', '2025-04-28 22:18:34'),
(160, 9, 8, '2025-05-05', '09:15:00', 'confirmed', '2025-04-29 12:00:00', '2025-04-28 22:18:34'),
(164, 13, 2, '2025-05-09', '13:00:00', 'cancelled', '2025-04-29 16:00:00', '2025-04-28 22:18:34'),
(165, 14, 3, '2025-05-10', '10:30:00', 'pending', '2025-04-29 17:00:00', '2025-04-28 22:18:34'),
(167, 16, 5, '2025-05-12', '08:00:00', 'confirmed', '2025-04-29 19:00:00', '2025-04-28 22:18:34'),
(168, 28, 8, '2025-05-13', '14:30:00', 'pending', '2025-04-29 20:00:00', '2025-04-28 22:18:34'),
(172, 32, 2, '2025-05-17', '12:15:00', 'pending', '2025-04-30 00:00:00', '2025-04-28 22:17:46'),
(173, 33, 3, '2025-05-18', '15:00:00', 'completed', '2025-04-30 01:00:00', '2025-04-28 22:18:34'),
(176, 5, 2, '2025-05-01', '10:00:00', 'confirmed', '2025-04-29 08:00:00', '2025-04-28 22:18:34'),
(177, 6, 3, '2025-05-02', '14:00:00', 'confirmed', '2025-04-29 09:00:00', '2025-04-28 22:18:34'),
(179, 8, 5, '2025-05-04', '15:30:00', 'completed', '2025-04-29 11:00:00', '2025-04-28 22:18:34'),
(180, 9, 8, '2025-05-05', '09:15:00', 'confirmed', '2025-04-29 12:00:00', '2025-04-28 22:18:34'),
(184, 13, 2, '2025-05-09', '13:00:00', 'cancelled', '2025-04-29 16:00:00', '2025-04-28 22:18:34'),
(185, 14, 3, '2025-05-10', '10:30:00', 'pending', '2025-04-29 17:00:00', '2025-04-28 22:18:34'),
(187, 16, 5, '2025-05-12', '08:00:00', 'confirmed', '2025-04-29 19:00:00', '2025-04-28 22:18:34'),
(188, 28, 8, '2025-05-13', '14:30:00', 'pending', '2025-04-29 20:00:00', '2025-04-28 22:18:34'),
(192, 32, 2, '2025-05-17', '12:15:00', 'pending', '2025-04-30 00:00:00', '2025-04-28 22:18:34'),
(193, 33, 3, '2025-05-18', '15:00:00', 'completed', '2025-04-30 01:00:00', '2025-04-28 22:18:34'),
(196, 5, 2, '2025-05-01', '10:00:00', 'confirmed', '2025-04-29 08:00:00', '2025-04-28 22:18:34'),
(197, 6, 3, '2025-05-02', '14:00:00', 'cancelled', '2025-04-29 09:00:00', '2025-04-28 22:18:34'),
(199, 8, 5, '2025-05-04', '15:30:00', 'completed', '2025-04-29 11:00:00', '2025-04-28 22:18:34'),
(200, 9, 8, '2025-05-05', '09:15:00', 'confirmed', '2025-04-29 12:00:00', '2025-04-28 22:18:34'),
(204, 13, 2, '2025-05-09', '13:00:00', 'cancelled', '2025-04-29 16:00:00', '2025-04-28 22:18:34'),
(205, 14, 3, '2025-05-10', '10:30:00', 'pending', '2025-04-29 17:00:00', '2025-04-28 22:18:34'),
(207, 16, 5, '2025-05-12', '08:00:00', 'confirmed', '2025-04-29 19:00:00', '2025-04-28 22:18:34'),
(208, 28, 8, '2025-05-13', '14:30:00', 'pending', '2025-04-29 20:00:00', '2025-04-28 22:18:34'),
(212, 32, 2, '2025-05-17', '12:15:00', 'confirmed', '2025-04-30 00:00:00', '2025-04-28 22:18:34'),
(213, 33, 3, '2025-05-18', '15:00:00', 'completed', '2025-04-30 01:00:00', '2025-04-28 22:18:34'),
(215, 3, 10, '2025-04-28', '08:00:00', 'confirmed', '2025-04-29 09:13:34', '2025-04-29 10:24:27'),
(216, 3, 10, '2025-04-28', '09:00:00', 'confirmed', '2025-04-29 09:21:14', '2025-04-29 12:45:20'),
(217, 3, 2, '2025-04-29', '08:00:00', 'confirmed', '2025-04-29 10:19:26', '2025-04-29 12:35:57'),
(218, 3, 10, '2025-04-28', '08:00:00', 'cancelled', '2025-04-29 10:57:46', '2025-04-29 12:45:30'),
(219, 3, 4, '2025-04-28', '08:00:00', 'confirmed', '2025-04-29 11:08:22', '2025-04-29 12:08:12'),
(220, 3, 8, '2025-04-30', '08:00:00', 'cancelled', '2025-04-29 11:08:31', '2025-04-29 11:26:36'),
(221, 3, 10, '2025-05-01', '08:00:00', 'cancelled', '2025-04-29 11:14:36', '2025-04-29 12:40:57'),
(222, 3, 10, '2025-04-28', '13:00:00', 'confirmed', '2025-04-29 11:25:33', '2025-04-29 11:25:33'),
(223, 3, 10, '2025-04-28', '08:00:00', 'confirmed', '2025-04-29 12:24:30', '2025-04-29 12:24:30'),
(224, 3, 10, '2025-04-28', '10:00:00', 'cancelled', '2025-04-29 12:26:59', '2025-04-29 12:28:51'),
(225, 3, 3, '2025-05-01', '14:00:00', 'confirmed', '2025-04-29 12:41:09', '2025-04-29 12:41:09');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `status`) VALUES
(1, 'Cabinet Tunis Belvedere', 'Rue du Japon, Tunis', 'active'),
(2, 'Cabinet Bizerte Centre Ville', 'Avenue de Carthage, Bizerte', 'active'),
(3, 'Cabinet Djerba Midoun', 'Zone Touristique, Djerba', 'active'),
(4, 'Centre Ville', '123 Main Street', 'active'),
(5, 'Quartier Nord', '456 North Avenue', 'active'),
(8, 'Centre Ville Test', '123 Main Street, City Center', 'active'),
(9, 'Quartier Nord Test', '456 North Avenue, North District', 'active'),
(10, 'ben daha', 'reontpoint ben daha ', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `id` int(11) NOT NULL,
  `appointment_id` int(11) NOT NULL,
  `therapist_id` int(11) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reports`
--

INSERT INTO `reports` (`id`, `appointment_id`, `therapist_id`, `content`, `created_at`, `updated_at`) VALUES
(1, 133, 18, 'Initial assessment completed. Patient shows good response to basic', '2025-04-28 19:08:06', '2025-04-28 22:15:35'),
(2, 134, 18, 'Follow-up session focused on mobility improvement. Progress observed.', '2025-04-28 19:08:06', '2025-04-28 19:08:06'),
(4, 136, 90, 'Therapy session interrupted due to equipment issues. Rescheduled.', '2025-04-28 19:08:06', '2025-04-28 19:08:06'),
(7, 139, 18, 'Introduced new stretching routine. Patient adapting well.', '2025-04-28 19:08:06', '2025-04-28 19:08:06'),
(8, 140, 90, 'Post-surgery rehabilitation progressing as planned.', '2025-04-28 19:08:06', '2025-04-28 19:08:06'),
(11, 133, 18, 'Initial assessment completed. Patient shows good response to basic', '2025-04-28 19:08:40', '2025-04-28 22:15:35'),
(12, 134, 18, 'Follow-up session focused on mobility improvement. Progress observed.', '2025-04-28 19:08:40', '2025-04-28 19:08:40'),
(14, 136, 90, 'Therapy session interrupted due to equipment issues. Rescheduled.', '2025-04-28 19:08:40', '2025-04-28 19:08:40'),
(17, 139, 18, 'Introduced new stretching routine. Patient adapting well.', '2025-04-28 19:08:40', '2025-04-28 19:08:40'),
(18, 140, 90, 'Post-surgery rehabilitation progressing as planned.', '2025-04-28 19:08:40', '2025-04-28 19:08:40'),
(21, 133, 18, 'Initial assessment completed. Patient shows good response to basic', '2025-04-28 19:08:57', '2025-04-28 22:15:35'),
(22, 134, 18, 'Follow-up session focused on mobility improvement. Progress observed.', '2025-04-28 19:08:57', '2025-04-28 19:08:57'),
(24, 136, 90, 'Therapy session interrupted due to equipment issues. Rescheduled.', '2025-04-28 19:08:57', '2025-04-28 19:08:57'),
(27, 139, 18, 'Introduced new stretching routine. Patient adapting well.', '2025-04-28 19:08:57', '2025-04-28 19:08:57'),
(28, 140, 90, 'Post-surgery rehabilitation progressing as planned.', '2025-04-28 19:08:57', '2025-04-28 19:08:57'),
(31, 133, 18, 'Initial assessment completed. Patient shows good response to basic', '2025-04-28 19:09:18', '2025-04-28 22:15:35'),
(32, 134, 18, 'Follow-up session focused on mobility improvement. Progress observed.', '2025-04-28 19:09:18', '2025-04-28 19:09:18'),
(34, 136, 90, 'Therapy session interrupted due to equipment issues. Rescheduled.', '2025-04-28 19:09:18', '2025-04-28 19:09:18'),
(37, 139, 18, 'Introduced new stretching routine. Patient adapting well.', '2025-04-28 19:09:18', '2025-04-28 19:09:18'),
(38, 140, 90, 'Post-surgery rehabilitation progressing as planned.', '2025-04-28 19:09:18', '2025-04-28 19:09:18');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `name` varchar(255) NOT NULL,
  `value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`name`, `value`, `updated_at`) VALUES
('cancel_time', '22', '2025-04-28 19:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `special_days`
--

CREATE TABLE `special_days` (
  `id` int(11) NOT NULL,
  `date` date NOT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `is_whole_day` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `special_days`
--

INSERT INTO `special_days` (`id`, `date`, `start_time`, `end_time`, `is_whole_day`, `created_at`, `updated_at`, `location_id`) VALUES
(24, '2025-05-03', '00:00:00', '23:59:00', 1, '2025-04-29 11:16:22', '2025-04-29 11:16:22', 4),
(25, '2025-05-04', '08:00:00', '10:00:00', 0, '2025-04-29 11:20:26', '2025-04-29 11:20:26', 4),
(26, '2025-05-11', '00:00:00', '23:59:00', 1, '2025-04-29 12:40:20', '2025-04-29 12:40:20', 10);

-- --------------------------------------------------------

--
-- Table structure for table `therapist_locations`
--

CREATE TABLE `therapist_locations` (
  `therapist_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `therapist_locations`
--

INSERT INTO `therapist_locations` (`therapist_id`, `location_id`) VALUES
(7, 1),
(7, 2),
(17, 1),
(17, 2),
(17, 3),
(18, 1),
(18, 2),
(18, 3),
(18, 4),
(18, 5),
(18, 8),
(18, 9),
(90, 2),
(90, 9),
(90, 10),
(93, 1),
(93, 2),
(93, 3),
(93, 4),
(93, 5),
(93, 8),
(93, 9),
(93, 10);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','therapist','client') NOT NULL DEFAULT 'client',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `status`, `created_at`, `updated_at`, `phone`, `address`) VALUES
(3, 'Nadia Ben Amor', 'nadia.benamor@example.com', '0000', 'client', 'active', '2025-04-27 15:35:21', '2025-04-28 11:42:43', '', ''),
(4, 'Walid Slim', 'walid.slim@example.com', '$2y$10$Y78pXVvEGLj3Dq5RW2uLiuJ11zN2eaxDEKKokw23gYpbXMBmsO53Ga', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 20:27:22', NULL, NULL),
(5, 'Imen Zarrouk', 'imen.zarrouk@example.com', '$2y$10$abcdefghijklmnopqrs14', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(6, 'Fares Ayadi', 'fares.ayadi@example.com', '$2y$10$abcdefghijklmnopqrs15', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(7, 'Rania Mabrouk', 'rania.mabrouk@example.com', '$2y$10$abcdefghijklmnopqrs16', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(8, 'Hassan Chaieb', 'hassan.chaieb@example.com', '$2y$10$abcdefghijklmnopqrs17', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(9, 'Olfa Mrad', 'olfa.mrad@example.com', '$2y$10$abcdefghijklmnopqrs18', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(10, 'Youssef Dhaouadi', 'youssef.dhaouadi@example.com', '$2y$10$abcdefghijklmnopqrs19', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(11, 'Sihem Ben Salem', 'sihem.bensalem@example.com', '$2y$10$abcdefghijklmnopqrs20', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(12, 'Ahmed Khemiri', 'ahmed.khemiri@example.com', '$2y$10$abcdefghijklmnopqrs21', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(13, 'Ines Guesmi', 'ines.guesmi@example.com', '$2y$10$abcdefghijklmnopqrs22', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(14, 'Mehdi Ben Romdhane', 'mehdi.benromdhane@example.com', '$2y$10$abcdefghijklmnopqrs23', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(15, 'Safa Toumi', 'safa.toumi@example.com', '$2y$10$abcdefghijklmnopqrs24', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(16, 'Karim Ben Mustapha', 'karim.benmustapha@example.com', '$2y$10$abcdefghijklmnopqrs25', 'client', 'active', '2025-04-27 15:35:21', '2025-04-27 15:35:21', NULL, NULL),
(17, 'Dr. Salma Ben Youssef', 'salma.benyoussef@kenetherapy.com', '$2y$10$abcdefghijklmnopqrs26', 'therapist', 'active', '2025-04-27 15:35:21', '2025-04-28 22:33:36', '', ''),
(18, 'M. Sofiene Trabelsi', 'sofiene.trabelsi@kenetherapy.com', '$2y$10$Y78pXVvEGLj3Dq5RW2uLiuJ11zN2eaxDEKKokw23gYpbXMBmsO53G', 'therapist', 'active', '2025-04-27 15:35:21', '2025-04-28 22:33:31', '', ''),
(27, 'Admin Easy', 'admin@easy.com', '0000', 'admin', 'active', '2025-04-27 16:22:59', '2025-04-28 11:42:34', NULL, NULL),
(28, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '1234567890', NULL),
(29, 'Jane Smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '2345678901', NULL),
(30, 'Mike Johnson', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '3456789012', NULL),
(31, 'Sarah Williams', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '4567890123', NULL),
(32, 'David Brown', 'david@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '5678901234', NULL),
(33, 'Emily Davis', 'emily@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '6789012345', NULL),
(34, 'Robert Wilson', 'robert@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '7890123456', NULL),
(35, 'Lisa Moore', 'lisa@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '8901234567', NULL),
(36, 'James Taylor', 'james@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '9012345678', NULL),
(37, 'Patricia Anderson', 'patricia@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:39:08', '2025-04-27 19:39:08', '0123456789', NULL),
(48, 'John Doe', 'john.doe.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '1234567890', '123 Main St'),
(50, 'Mike Johnson', 'mike.johnson.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '3456789012', '789 Pine Rd'),
(51, 'Sarah Williams', 'sarah.williams.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '4567890123', '321 Elm St'),
(52, 'David Brown', 'david.brown.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '5678901234', '654 Maple Dr'),
(53, 'Emily Davis', 'emily.davis.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '6789012345', '987 Cedar Ln'),
(54, 'Robert Wilson', 'robert.wilson.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '7890123456', '147 Birch St'),
(55, 'Lisa Moore', 'lisa.moore.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '8901234567', '258 Willow Ave'),
(56, 'James Taylor', 'james.taylor.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '9012345678', '369 Spruce Rd'),
(57, 'Patricia Anderson', 'patricia.anderson.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '0123456789', '741 Oak St'),
(59, 'Admin User', 'admin.test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2025-04-27 19:43:01', '2025-04-27 19:43:01', '9998887777', '123 Admin Ave'),
(82, 'Mike Test', 'mike.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '3456789012', '789 Pine Rd'),
(85, 'Emily Test', 'emily.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '6789012345', '987 Cedar Ln'),
(86, 'Robert Test', 'robert.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '7890123456', '147 Birch St'),
(87, 'Lisa Test', 'lisa.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '8901234567', '258 Willow Ave'),
(88, 'James Test', 'james.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '9012345678', '369 Spruce Rd'),
(89, 'Patricia Test', 'patricia.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '0123456789', '741 Oak St'),
(90, 'Dr. Test', 'therapist.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'therapist', 'active', '2025-04-27 19:45:32', '2025-04-28 22:33:26', '1112223333', '555 Clinic St'),
(91, 'Admin Test', 'admin.test.unique@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2025-04-27 19:45:32', '2025-04-27 19:45:32', '9998887777', '123 Admin Ave'),
(92, 'safoune', 'safoune@wsmple.tn', '$2y$10$gacAUZmHMmE/bRmxBJDtMeh.c/I/GMonacGuuI9gMKIZ0YpyTEMCa', 'client', 'active', '2025-04-28 19:04:59', '2025-04-29 13:37:08', '', 'sads'),
(93, 'ahmed', 'ahmed@test.com', '0000', 'therapist', 'active', '2025-04-28 22:06:49', '2025-04-29 13:36:55', '934131063', 'dsm');

--
-- Notification system table
--
CREATE TABLE `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL, -- recipient (user, therapist, or admin)
  `type` VARCHAR(50) NOT NULL, -- e.g. 'appointment', 'system'
  `message` TEXT NOT NULL,
  `appointment_id` INT DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_appointments_date` (`date`),
  ADD KEY `idx_appointments_user` (`user_id`),
  ADD KEY `idx_appointments_location` (`location_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`),
  ADD KEY `idx_locations_status` (`status`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `appointment_id` (`appointment_id`),
  ADD KEY `therapist_id` (`therapist_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`name`);

--
-- Indexes for table `special_days`
--
ALTER TABLE `special_days`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_special_days_location` (`location_id`);

--
-- Indexes for table `therapist_locations`
--
ALTER TABLE `therapist_locations`
  ADD PRIMARY KEY (`therapist_id`,`location_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=226;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `special_days`
--
ALTER TABLE `special_days`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reports_ibfk_2` FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `special_days`
--
ALTER TABLE `special_days`
  ADD CONSTRAINT `fk_special_days_location` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `therapist_locations`
--
ALTER TABLE `therapist_locations`
  ADD CONSTRAINT `therapist_locations_ibfk_1` FOREIGN KEY (`therapist_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `therapist_locations_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
