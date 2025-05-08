-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 08, 2025 at 07:34 AM
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
-- Database: `sitin`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `ID` int(11) NOT NULL,
  `USERNAME` varchar(30) NOT NULL,
  `PASSWORD` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`ID`, `USERNAME`, `PASSWORD`) VALUES
(1, 'admin', '$2y$10$.49YWrDzrpT/WeKp.cevvuxzjZ5eYgkfZ8eIrRdZEFccueRMmHaSC');

-- --------------------------------------------------------

--
-- Table structure for table `announcement`
--

CREATE TABLE `announcement` (
  `ID` int(11) NOT NULL,
  `TITLE` varchar(255) NOT NULL,
  `CONTENT` text NOT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `announcement`
--

INSERT INTO `announcement` (`ID`, `TITLE`, `CONTENT`, `CREATED_AT`) VALUES
(1, 'HW', 'Hello World', '2025-03-24 10:11:02'),
(2, 'Hi', 'Hi Kalibutan', '2025-03-24 10:11:12');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `ID` int(11) NOT NULL,
  `SITIN_RECORD_ID` int(11) NOT NULL,
  `STUDENT_ID` varchar(50) NOT NULL,
  `RATING` int(1) NOT NULL,
  `COMMENT` text DEFAULT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`ID`, `SITIN_RECORD_ID`, `STUDENT_ID`, `RATING`, `COMMENT`, `CREATED_AT`) VALUES
(0, 1003, '1000', 5, 'meh', '2025-05-06 02:59:56');

-- --------------------------------------------------------

--
-- Table structure for table `labschedules`
--

CREATE TABLE `labschedules` (
  `ID` int(11) NOT NULL,
  `ROOM_NUMBER` varchar(10) DEFAULT NULL,
  `DAY_GROUP` varchar(10) DEFAULT NULL,
  `TIME_SLOT` varchar(20) DEFAULT NULL,
  `STATUS` varchar(20) DEFAULT NULL,
  `NOTES` text DEFAULT NULL,
  `LAST_UPDATED` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lab_resources`
--

CREATE TABLE `lab_resources` (
  `ID` int(11) NOT NULL,
  `TITLE` varchar(255) NOT NULL,
  `DESCRIPTION` text NOT NULL,
  `CATEGORY` varchar(100) NOT NULL,
  `RESOURCE_TYPE` varchar(50) NOT NULL,
  `LINK` text NOT NULL,
  `FILE_PATH` varchar(255) NOT NULL,
  `UPLOAD_DATE` datetime NOT NULL,
  `FILE_NAME` varchar(255) NOT NULL,
  `FILE_TYPE` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lab_resources`
--

INSERT INTO `lab_resources` (`ID`, `TITLE`, `DESCRIPTION`, `CATEGORY`, `RESOURCE_TYPE`, `LINK`, `FILE_PATH`, `UPLOAD_DATE`, `FILE_NAME`, `FILE_TYPE`) VALUES
(1, 'Sit In System', 'github repository', 'Programming', 'Other', 'https://github.com/jarombustillo/sitin', '', '2025-05-07 21:35:21', '', ''),
(2, 'Xampp', 'xampp installer', 'Database', 'Other', 'https://www.apachefriends.org/', '', '2025-05-07 21:35:54', '', ''),
(3, 'Visual Studio Code', 'visual studio code', 'Programming', 'Document', 'https://code.visualstudio.com/', '', '2025-05-07 21:37:03', '', ''),
(4, 'About You ', 'About you - 1975', 'Other', 'Other', '', '681b6219c6d6b_aboutyou.jpg', '2025-05-07 21:37:29', 'aboutyou.jpg', 'jpg'),
(5, 'Canvas', 'UC LMS', 'Programming', 'Document', 'https://universityofcebu.instructure.com/login/canvas', '', '2025-05-07 21:37:59', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `ID` int(11) NOT NULL,
  `USER_ID` int(11) NOT NULL,
  `MESSAGE` text NOT NULL,
  `TYPE` varchar(50) DEFAULT 'general',
  `DETAILS` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `IS_READ` tinyint(1) DEFAULT 0,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`ID`, `USER_ID`, `MESSAGE`, `TYPE`, `DETAILS`, `IS_READ`, `CREATED_AT`) VALUES
(1, 0, 'New reservation submitted by student ID 2000 for Lab Lab 524, PC 47, 2025-05-08 (16:00-17:00).', 'admin', '', 0, '2025-05-08 05:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `pc_status`
--

CREATE TABLE `pc_status` (
  `ID` int(11) NOT NULL,
  `ROOM_NUMBER` varchar(10) NOT NULL,
  `PC_NUMBER` int(11) NOT NULL,
  `STATUS` varchar(20) NOT NULL DEFAULT 'available',
  `LAST_UPDATED` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `points_history`
--

CREATE TABLE `points_history` (
  `ID` int(11) NOT NULL,
  `IDNO` varchar(20) NOT NULL,
  `FULLNAME` varchar(100) NOT NULL,
  `POINTS_EARNED` int(11) DEFAULT 1,
  `CONVERTED_TO_SESSION` tinyint(1) DEFAULT 0,
  `CONVERSION_DATE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `points_history`
--

INSERT INTO `points_history` (`ID`, `IDNO`, `FULLNAME`, `POINTS_EARNED`, `CONVERTED_TO_SESSION`, `CONVERSION_DATE`) VALUES
(1, '1000', 'Jarom Bustillo', 10, 0, '2025-05-08 09:21:10'),
(2, '2000', 'John Doe', 10, 0, '2025-05-08 09:21:21'),
(3, '3000', 'Marphine Faith Mangubat', 10, 0, '2025-05-08 09:21:32'),
(4, '1000', 'Jarom Bustillo', 10, 0, '2025-05-08 09:22:56');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `ID` int(11) NOT NULL,
  `IDNO` int(11) NOT NULL,
  `LABORATORY` varchar(50) NOT NULL,
  `PC_NUMBER` int(11) NOT NULL,
  `DATE` date NOT NULL,
  `TIME_SLOT` varchar(20) NOT NULL,
  `PURPOSE` text NOT NULL,
  `STATUS` enum('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  `CREATED_AT` datetime NOT NULL,
  `UPDATED_AT` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`ID`, `IDNO`, `LABORATORY`, `PC_NUMBER`, `DATE`, `TIME_SLOT`, `PURPOSE`, `STATUS`, `CREATED_AT`, `UPDATED_AT`) VALUES
(1, 1000, 'Lab 1', 2, '2025-05-07', '09:00-10:00', 'submitting a programming assignment', 'confirmed', '2025-05-06 16:13:57', '2025-05-06 16:28:51'),
(2, 3000, 'Lab 524', 0, '2025-05-07', '08:00-09:00', 'docs purposes', 'confirmed', '2025-05-06 16:34:48', '2025-05-06 16:35:17'),
(3, 1000, 'Lab 524', 3, '2025-05-08', '08:00-09:00', 'sss', 'cancelled', '2025-05-08 01:16:42', '2025-05-08 01:20:31'),
(4, 1000, 'Lab 526', 1, '2025-05-09', '08:00-09:00', 'zzzz', 'confirmed', '2025-05-08 01:21:02', '2025-05-08 01:21:47'),
(5, 3000, 'Lab 524', 2, '2025-05-08', '14:00-15:00', 'kkk', 'cancelled', '2025-05-08 13:13:28', '2025-05-08 13:21:14'),
(6, 2000, 'Lab 526', 42, '2025-05-08', '16:00-17:00', 'huhu', 'cancelled', '2025-05-08 13:17:10', '2025-05-08 13:21:15'),
(7, 2000, 'Lab 524', 47, '2025-05-08', '16:00-17:00', 'wewe', 'pending', '2025-05-08 13:24:39', '2025-05-08 13:24:39');

-- --------------------------------------------------------

--
-- Table structure for table `reward_points`
--

CREATE TABLE `reward_points` (
  `ID` int(11) NOT NULL,
  `STUDENT_ID` varchar(50) DEFAULT NULL,
  `POINTS` int(11) DEFAULT 0,
  `LAST_REWARD_DATE` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reward_points`
--

INSERT INTO `reward_points` (`ID`, `STUDENT_ID`, `POINTS`, `LAST_REWARD_DATE`) VALUES
(13, '1000', 10, '2025-05-08'),
(14, '2000', 10, '2025-05-08'),
(15, '3000', 10, '2025-05-08'),
(16, '1000', 10, '2025-05-08');

-- --------------------------------------------------------

--
-- Table structure for table `sitin_records`
--

CREATE TABLE `sitin_records` (
  `ID` int(11) NOT NULL,
  `IDNO` int(11) NOT NULL,
  `PURPOSE` varchar(255) NOT NULL,
  `LABORATORY` varchar(30) NOT NULL,
  `TIME_IN` datetime NOT NULL,
  `TIME_OUT` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sitin_records`
--

INSERT INTO `sitin_records` (`ID`, `IDNO`, `PURPOSE`, `LABORATORY`, `TIME_IN`, `TIME_OUT`) VALUES
(0, 1000, 'Programming', '23', '2025-03-24 15:50:30', '2025-03-24 16:49:25'),
(1000, 0, 'Programming', '23', '0000-00-00 00:00:00', '2025-03-24 15:46:38'),
(1001, 1000, 'Research', '', '2025-03-24 17:58:00', '2025-03-24 17:58:19'),
(1002, 1000, 'C#', '530', '2025-03-24 18:12:52', '2025-03-24 18:15:15'),
(1003, 1000, 'C#', '524', '2025-03-24 18:15:29', '2025-03-24 18:15:53'),
(1004, 1000, 'ASP.Net', '530', '2025-05-07 19:00:37', '2025-05-07 19:01:12'),
(1005, 1000, 'PHP', '524', '2025-05-07 19:22:18', '2025-05-07 19:22:47'),
(1006, 3000, 'Java', '530', '2025-05-08 03:01:38', '2025-05-08 03:01:55'),
(1007, 3000, 'Python', '526', '2025-05-08 03:02:14', '2025-05-08 03:07:09'),
(1008, 1000, 'C', '530', '2025-05-08 03:06:54', '2025-05-08 03:07:10'),
(1009, 1000, 'C#', '526', '2025-05-08 03:07:34', '2025-05-08 03:08:59'),
(1010, 3000, 'C#', '524', '2025-05-08 03:07:48', '2025-05-08 03:08:57'),
(1011, 2000, 'ASP.Net', '530', '2025-05-08 03:08:04', '2025-05-08 03:08:57'),
(1012, 1000, 'Python', '526', '2025-05-08 03:11:49', '2025-05-08 03:13:18'),
(1013, 1000, 'Java', '526', '2025-05-08 03:17:23', '2025-05-08 03:20:54'),
(1014, 2000, 'C', '542', '2025-05-08 03:17:33', '2025-05-08 03:20:53'),
(1015, 3000, 'Python', '530', '2025-05-08 03:17:48', '2025-05-08 03:20:52'),
(1016, 1000, 'PHP', '524', '2025-05-08 03:21:10', '2025-05-08 03:22:28'),
(1017, 2000, 'Java', '544', '2025-05-08 03:21:21', '2025-05-08 03:22:28'),
(1018, 3000, 'Python', '526', '2025-05-08 03:21:32', '2025-05-08 03:22:27'),
(1019, 1000, 'C#', '530', '2025-05-08 03:22:56', '2025-05-08 03:23:47');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `IDNO` int(11) NOT NULL,
  `Lastname` varchar(50) NOT NULL,
  `Firstname` varchar(50) NOT NULL,
  `Midname` varchar(50) NOT NULL,
  `course` varchar(50) NOT NULL,
  `year_level` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `profilepic` varchar(255) NOT NULL DEFAULT 'default.png',
  `session_count` int(11) NOT NULL DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`IDNO`, `Lastname`, `Firstname`, `Midname`, `course`, `year_level`, `username`, `password`, `profilepic`, `session_count`) VALUES
(1000, 'Bustillo', 'Jarom', '', 'BSIT', 3, 'jarom', '$2y$10$oBYe0lAvh75kYIAG1e.2GOX.L6oJRK3xQSM0zK064cknPwCU3q4lW', 'default.png', 19),
(2000, 'Doe', 'John', '', 'BSIT', 2, 'john.doe', '$2y$10$WsMO/pJPL/7WQWzCZBualuF6ER6.66geH41yBqXPHYHCxHsVk.UPi', 'default.png', 27),
(3000, 'Mangubat', 'Marphine Faith', 'Jagdon', 'BSCS', 1, 'marphine', '$2y$10$gKSONNZcgY2OXCGLElMOjOGxqDiUfbI.7eGPDNCHNkLEg4EhocTs2', 'user_1746637336.jpg', 25),
(4000, 'Doe', 'Jane', '', 'BSCPE', 4, 'jane.doe', '$2y$10$23gW3R/EiRe58JTG2rZmT.X9XZUPCd7AHs4xpZsVkIx33a/bftHO2', 'default.png', 30),
(5000, 'Barcenas', 'Ezekiel', '', 'BSCS', 1, 'ezekiel', '$2y$10$X0ZGJ4intn2lwB85y8SOl.FJsCt9Pc0/0qT4P1pzpXELvVsjsYP02', 'default.png', 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `announcement`
--
ALTER TABLE `announcement`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `labschedules`
--
ALTER TABLE `labschedules`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `lab_resources`
--
ALTER TABLE `lab_resources`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `pc_status`
--
ALTER TABLE `pc_status`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `points_history`
--
ALTER TABLE `points_history`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `IDNO` (`IDNO`);

--
-- Indexes for table `reward_points`
--
ALTER TABLE `reward_points`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `sitin_records`
--
ALTER TABLE `sitin_records`
  ADD PRIMARY KEY (`ID`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`IDNO`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `announcement`
--
ALTER TABLE `announcement`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `labschedules`
--
ALTER TABLE `labschedules`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lab_resources`
--
ALTER TABLE `lab_resources`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pc_status`
--
ALTER TABLE `pc_status`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `points_history`
--
ALTER TABLE `points_history`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `reward_points`
--
ALTER TABLE `reward_points`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`IDNO`) REFERENCES `user` (`IDNO`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
