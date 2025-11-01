-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Generation Time: Nov 01, 2025 at 04:59 PM
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
('raseel99', 'raseelalmaneaa@gmail.com', '$2y$10$nYGpbo40oov4IOUwbydR9u.xZ90AYtDnvIIs5vWUnyHfgbD4ZGovm', 'fff Almanea', NULL, NULL, NULL, 0, NULL, NULL),
('raseelalmanea', 'Raseel25.m@gmail.com', '$2y$10$W84xI4U2O4IGtAUdF6z.eeh2CbM9HU5ku34jRsnjCXEpl9UxikiBC', 'Raseel Almanea', '05fd79dda4aa0754aa4a667e51c99e8d', NULL, '2025-10-30 12:44:53', 0, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `MedicalNote`
--

CREATE TABLE `MedicalNote` (
  `id` int NOT NULL,
  `PatientID` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `Note` varchar(244) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `MedicalNote`
--

INSERT INTO `MedicalNote` (`id`, `PatientID`, `Note`) VALUES
(10, '4444477700', 'hhhhhh');

-- --------------------------------------------------------

--
-- Table structure for table `Patient`
--

CREATE TABLE `Patient` (
  `ID` char(10) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `FullName` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Email` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `DOB` date NOT NULL,
  `Address` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `Phone` char(10) COLLATE utf8mb4_general_ci NOT NULL,
  `Gender` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `BloodType` varchar(4) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `UserID` varchar(255) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `Patient`
--

INSERT INTO `Patient` (`ID`, `FullName`, `Email`, `DOB`, `Address`, `Phone`, `Gender`, `BloodType`, `UserID`) VALUES
('1119484747', 'Raseel Almanea', 'blackoutcorps@pm.me', '2025-10-26', 'Almalqa', '0512345644', 'M', 'A+', 'raseel99'),
('4444099494', 'Raseel', 'blackoutc@orpspm.me', '2025-10-16', 'Almalqa', '0505335555', 'F', 'A-', 'raseel99'),
('4444477700', 'nura Almanea', '7moodii25.m@gmail.com', '2025-10-08', 'Almalqa', '0512345644', 'M', 'B+', 'raseel99'),
('4444477777', 'Raseel Almanea', '7moodii25.m@gmail.com', '2025-10-08', 'Almalqa', '0505335555', 'M', 'A+', 'raseel99'),
('4444488888', 'Raseel Almanea', '444201169@students.ksu.edu.sa', '2025-10-01', 'Almalqa', '0512345644', 'F', 'A+', 'raseel99');

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
  ADD KEY `medicalnote_ibfk_1` (`PatientID`);

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `MedicalNote`
--
ALTER TABLE `MedicalNote`
  ADD CONSTRAINT `medicalnote_ibfk_1` FOREIGN KEY (`PatientID`) REFERENCES `Patient` (`ID`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `Patient`
--
ALTER TABLE `Patient`
  ADD CONSTRAINT `patient_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `HealthCareP` (`UserID`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
