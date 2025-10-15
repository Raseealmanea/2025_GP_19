-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Oct 15, 2025 at 08:28 AM
-- Server version: 8.0.40
-- PHP Version: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `OuwnDB`
--

-- --------------------------------------------------------

--
-- Table structure for table `HealthCareP`
--

CREATE TABLE `HealthCareP` (
  `UserID` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `Password` varchar(1000) COLLATE utf8mb4_general_ci NOT NULL,
  `Name` text COLLATE utf8mb4_general_ci NOT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `token_expires` int DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `email_confirmed` tinyint(1) DEFAULT '0',
  `email_token` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `email_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `HealthCareP`
--

INSERT INTO `HealthCareP` (`UserID`, `Email`, `Password`, `Name`, `reset_token`, `token_expires`, `reset_expires`, `email_confirmed`, `email_token`, `email_token_expires`) VALUES
('alyasaud', 'alyaalthobiani@gmail.com', '$2y$10$E8fHpYB6F2Hx5t5bREkH8uZb8BvPoXfzGZb8Bv4c5Qh3z7Nz9iTjC', 'alya althobiani', NULL, NULL, NULL, 0, NULL, NULL),
('Dalia', 'daly.m.1003@gmail.com', '$2y$10$gF0mH6Q5VkX0n8M/4D5kBeYp7jJqVgf/8KjGfE1PpV6yZ0kPqH4Li', 'Dalia Alsubaie', NULL, NULL, NULL, 0, NULL, NULL),
('Raseel', 'raseelalmaneaa@gmail.com', '$2y$10$3xJ7FqL1rP8T9kVb0D4nTeC0sPqQbR4x5Zs9FvV0hW2eU1yY8Q3Za', 'Raseel Almanea', NULL, NULL, NULL, 0, NULL, NULL),
('raseel777', 'Raseel25@gmail.com', '$2y$10$pDXbBulANEAA5jRl/ma8/OsYzJd0qpylCJlQigsSASPqkMSarRrfS', 'Ras', NULL, NULL, NULL, 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `MedicalNote`
--

CREATE TABLE `MedicalNote` (
  `id` int NOT NULL,
  `patientid` int NOT NULL,
  `note` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `MedicalNote`
--

INSERT INTO `MedicalNote` (`id`, `patientid`, `note`) VALUES
(1, 12, 'bbbb'),
(2, 12, 'dmkldsle'),
(3, 6666, 'jkjkehjkc');

-- --------------------------------------------------------

--
-- Table structure for table `Patient`
--

CREATE TABLE `Patient` (
  `ID` int NOT NULL,
  `FullName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `DOB` date NOT NULL,
  `Address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Phone` int NOT NULL,
  `Gender` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `BloodType` varchar(2) COLLATE utf8mb4_general_ci NOT NULL,
  `UserID` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Patient`
--

INSERT INTO `Patient` (`ID`, `FullName`, `Email`, `DOB`, `Address`, `Phone`, `Gender`, `BloodType`, `UserID`) VALUES
(12, 'rss', 'ase@200.slx', '2025-10-15', 'almalqa', 508494756, 'M', 'O', 'alyasaud'),
(6666, 'Raseel Almanea', 'raseelalmaneaa@gmail.com', '2025-10-15', 'Almalqa', 50533, 'F', 'k', 'raseel777'),
(44444, 'Raseel Almanea', '444201169@student.ksu.edu.sa', '2025-09-30', 'Almalqa', 5088, 'Female', 'o', 'raseel777');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `HealthCareP`
--
ALTER TABLE `HealthCareP`
  ADD PRIMARY KEY (`UserID`);

--
-- Indexes for table `MedicalNote`
--
ALTER TABLE `MedicalNote`
  ADD PRIMARY KEY (`id`),
  ADD KEY `patientid` (`patientid`);

--
-- Indexes for table `Patient`
--
ALTER TABLE `Patient`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `patient_ibfk_1` (`UserID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `MedicalNote`
--
ALTER TABLE `MedicalNote`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `MedicalNote`
--
ALTER TABLE `MedicalNote`
  ADD CONSTRAINT `medicalnote_ibfk_1` FOREIGN KEY (`patientid`) REFERENCES `Patient` (`ID`) ON DELETE RESTRICT ON UPDATE RESTRICT;

--
-- Constraints for table `Patient`
--
ALTER TABLE `Patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `HealthCareP` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
