-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 24, 2025 at 06:19 PM
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
(0, 'HW', 'Hello World', '2025-03-24 10:11:02'),
(0, 'Hi', 'Hi Kalibutan', '2025-03-24 10:11:12');

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
(1003, 1000, 'C#', '524', '2025-03-24 18:15:29', '2025-03-24 18:15:53');

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
(1000, 'Bustillo', 'Jarom', '', 'BSIT', 3, 'jarom', '$2y$10$oBYe0lAvh75kYIAG1e.2GOX.L6oJRK3xQSM0zK064cknPwCU3q4lW', 'default.png', 27),
(2000, 'Doe', 'John', '', 'BSIT', 2, 'john.doe', '$2y$10$WsMO/pJPL/7WQWzCZBualuF6ER6.66geH41yBqXPHYHCxHsVk.UPi', 'default.png', 30),
(3000, 'Mangubat', 'Marphine Faith', 'Jagdon', 'BSCS', 1, 'marphine', '$2y$10$gKSONNZcgY2OXCGLElMOjOGxqDiUfbI.7eGPDNCHNkLEg4EhocTs2', 'default.png', 30),
(4000, 'Doe', 'Jane', '', 'BSCPE', 4, 'jane.doe', '$2y$10$23gW3R/EiRe58JTG2rZmT.X9XZUPCd7AHs4xpZsVkIx33a/bftHO2', 'default.png', 30);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
