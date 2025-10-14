-- Set SQL mode and time zone
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- Create database
CREATE DATABASE IF NOT EXISTS `OuwnDB` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `OuwnDB`;

-- Table structure for table `HealthP`
CREATE TABLE `HealthP` (
  `UserID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `HealthP` (`UserID`) VALUES (111);

-- Table structure for table `MedicalNote`

CREATE TABLE `MedicalNote` (
  `id` int NOT NULL,
  `patientid` varchar(255) NOT NULL,
  `note` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `MedicalNote` (`id`, `patientid`, `note`) VALUES
(1, '12', 'bbbb');

-- Table structure for table `Patient`
CREATE TABLE `Patient` (
  `ID` int NOT NULL,
  `FullName` varchar(255) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `DOB` date NOT NULL,
  `Address` varchar(255) NOT NULL,
  `Phone` int NOT NULL,
  `Gender` varchar(255) NOT NULL,
  `BloodType` varchar(2) NOT NULL,
  `UserID` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `Patient` (`ID`, `FullName`, `Email`, `DOB`, `Address`, `Phone`, `Gender`, `BloodType`, `UserID`) VALUES
(12, 'rss', 'ase@200.slx', '2025-10-15', 'almalqa', 508494756, 'M', 'O', 111),
(4566, 'Raseel Almanea', 'raseelalmaneaa@gmail.com', '2025-10-01', 'Almalqa', 50533, 'Female', 'O', 111);

-- Table structure for table `HealthCareP`
CREATE TABLE `HealthCareP` (
  `UserID` varchar(1000) NOT NULL,
  `Email` varchar(1000) NOT NULL,
  `Password` varchar(1000) NOT NULL,
  `Name` text NOT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expires` int(11) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `email_confirmed` tinyint(1) DEFAULT 0,
  `email_token` varchar(255) DEFAULT NULL,
  `email_token_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert users with hashed passwords
INSERT INTO `HealthCareP` (`UserID`, `Email`, `Password`, `Name`) VALUES
('alyasaud', 'alyaalthobiani@gmail.com', '$2y$10$E8fHpYB6F2Hx5t5bREkH8uZb8BvPoXfzGZb8Bv4c5Qh3z7Nz9iTjC', 'alya althobiani'),
('Dalia', 'daly.m.1003@gmail.com', '$2y$10$gF0mH6Q5VkX0n8M/4D5kBeYp7jJqVgf/8KjGfE1PpV6yZ0kPqH4Li', 'Dalia Alsubaie'),
('Raseel', 'raseelalmaneaa@gmail.com', '$2y$10$3xJ7FqL1rP8T9kVb0D4nTeC0sPqQbR4x5Zs9FvV0hW2eU1yY8Q3Za', 'Raseel Almanea');
