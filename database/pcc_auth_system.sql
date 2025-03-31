-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2025 at 09:10 AM
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
-- Database: `pcc_auth_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `centers`
--

CREATE TABLE `centers` (
  `center_id` int(11) NOT NULL,
  `center_code` varchar(10) NOT NULL,
  `center_name` varchar(100) NOT NULL,
  `center_type` enum('Headquarters','Regional') NOT NULL,
  `logo_path` varchar(255) NOT NULL,
  `region` varchar(50) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `centers`
--

INSERT INTO `centers` (`center_id`, `center_code`, `center_name`, `center_type`, `logo_path`, `region`, `is_active`) VALUES
(1, 'HQ', 'PCC Headquarters', 'Headquarters', 'images/headquarters.png', 'National', 1),
(2, 'CLSU', 'Central Luzon State University', 'Regional', 'images/clsu-pic.png', 'Region III', 1),
(3, 'CMU', 'Central Mindanao University', 'Regional', 'images/cmu.png', 'Region XII', 1),
(4, 'CSU', 'Cagayan State University', 'Regional', 'images/csu.png', 'Region II', 1),
(5, 'DMMMSU', 'Don Mariano Marcos Memorial State University', 'Regional', 'images/dmmmsu-pic.png', 'Region I', 1),
(6, 'GP', 'Gene Pool', 'Regional', 'images/genepool.jpg', 'Region III', 1),
(7, 'LCSF', 'La Carlota Stock Farm', 'Regional', 'images/lcsf.png', 'Region VI', 1),
(8, 'NIZ', 'National Impact Zone', 'Regional', 'images/niz.jpg', 'Region III', 1),
(9, 'MLPC', 'Mindanao Livestock Production Center', 'Regional', 'images/mlpc-pic.png', 'Region XII', 1),
(10, 'MMSU', 'Mariano Marcos State University', 'Regional', 'images/mmsu2.png', 'Region I', 1),
(11, 'USF', 'Ubay Stock Farm', 'Regional', 'images/usf.jpg', 'Region VII', 1),
(12, 'UPLB', 'University of the Philippines Los Baños', 'Regional', 'images/uplb.png', 'Region IV-A', 1),
(13, 'USM', 'University of Southern Mindanao', 'Regional', 'images/usm.jpg', 'Region XII', 1),
(14, 'VSU', 'Visayas State University', 'Regional', 'images/vsu.jpg', 'Region VIII', 1),
(15, 'WVSU', 'West Visayas State University', 'Regional', 'images/wvsu.jpg', 'Region VI', 1);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `center_code` varchar(10) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `center_code`, `username`, `email`, `password_hash`, `full_name`, `position`, `is_active`, `last_login`) VALUES
(1, 'HQ', 'administrator', 'pccadministrator@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'PCC National Administrator', 'System Administrator', 1, '2025-03-31 14:25:16'),
(2, 'DMMMSU', 'dmmmsu_admin', 'dmmmsu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Juan Dela Cruz', 'Center Manager - DMMMSU', 1, NULL),
(3, 'MMSU', 'mmsu_admin', 'mmsu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Maria Santos', 'Center Manager - MMSU', 1, NULL),
(4, 'CSU', 'csu_admin', 'csu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Roberto Garcia', 'Center Manager - CSU', 1, NULL),
(5, 'CLSU', 'clsu_admin', 'clsu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Andrea Reyes', 'Center Manager - CLSU', 1, NULL),
(6, 'GP', 'gp_admin', 'genepool@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Carlos Mendoza', 'Gene Pool Supervisor', 1, NULL),
(7, 'NIZ', 'niz_admin', 'niz@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lourdes Tan', 'NIZ Coordinator', 1, NULL),
(8, 'UPLB', 'uplb_admin', 'uplb@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Emmanuel Rivera', 'Center Manager - UPLB', 1, NULL),
(9, 'LCSF', 'lcsf_admin', 'lcsf@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Sofia Hernandez', 'Center Manager - La Carlota', 1, NULL),
(10, 'WVSU', 'wvsu_admin', 'wvsu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Antonio Lopez', 'Center Manager - WVSU', 1, NULL),
(11, 'USF', 'usf_admin', 'usf@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Patricia Gomez', 'Center Manager - Ubay', 1, NULL),
(12, 'VSU', 'vsu_admin', 'vsu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ferdinand Castro', 'Center Manager - VSU', 1, NULL),
(13, 'CMU', 'cmu_admin', 'cmu@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Rosalinda Fernando', 'Center Manager - CMU', 1, NULL),
(14, 'MLPC', 'mlpc_admin', 'mlpc@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ricardo Dizon', 'Center Manager - MLPC', 1, NULL),
(15, 'USM', 'usm_admin', 'usm@pcc.gov.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Lorna Ramirez', 'Center Manager - USM', 1, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `centers`
--
ALTER TABLE `centers`
  ADD PRIMARY KEY (`center_id`),
  ADD UNIQUE KEY `center_code` (`center_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `center_code` (`center_code`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `centers`
--
ALTER TABLE `centers`
  MODIFY `center_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`center_code`) REFERENCES `centers` (`center_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
