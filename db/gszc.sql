-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 06, 2025 at 09:19 AM
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
-- Database: `gszc_events`
--
CREATE DATABASE IF NOT EXISTS `gszc_events` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `gszc_events`;

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance_sheets`
--

CREATE TABLE `attendance_sheets` (
  `attendance_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `event_workshop_id` int(11) NOT NULL,
  `note` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE `events` (
  `event_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `date` date NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `name`, `date`, `location`, `status`) VALUES
(6, 'Harmadik Esemény', '2025-04-19', 'Budapest', 'ready'),
(7, 'Gizmo simogatás', '2005-12-12', 'Miskolc', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `event_workshop`
--

CREATE TABLE `event_workshop` (
  `event_workshop_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `max_workable_hours` int(11) NOT NULL DEFAULT 0,
  `number_of_mentors_required` int(11) NOT NULL DEFAULT 0,
  `number_of_teachers_required` int(11) NOT NULL DEFAULT 0,
  `busyness` enum('low','high') NOT NULL DEFAULT 'high',
  `ew_status` enum('pending','inviting','ready','failed','completed') NOT NULL DEFAULT 'pending' COMMENT 'Status of this specific workshop instance'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `event_workshop`
--

INSERT INTO `event_workshop` (`event_workshop_id`, `event_id`, `workshop_id`, `max_workable_hours`, `number_of_mentors_required`, `number_of_teachers_required`, `busyness`, `ew_status`) VALUES
(21, 6, 1, 5, 2, 1, 'high', 'pending'),
(22, 6, 3, 5, 2, 1, 'high', 'pending'),
(29, 7, 1, 5, 2, 1, 'high', 'inviting'),
(30, 7, 2, 5, 3, 1, 'high', 'failed'),
(31, 7, 3, 5, 2, 1, 'high', 'failed');

-- --------------------------------------------------------

--
-- Table structure for table `mentor_workshop`
--

CREATE TABLE `mentor_workshop` (
  `mentor_workshop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `ranking_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mentor_workshop`
--

INSERT INTO `mentor_workshop` (`mentor_workshop_id`, `user_id`, `workshop_id`, `ranking_number`) VALUES
(22, 1, 1, 1),
(23, 2, 1, 1),
(24, 3, 1, 1),
(25, 6, 3, 1),
(26, 7, 3, 1),
(27, 4, 1, 1),
(28, 5, 1, 1),
(29, 8, 3, 1);

-- --------------------------------------------------------

--
-- Table structure for table `participants`
--

CREATE TABLE `participants` (
  `user_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `type` enum('student','teacher') NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `school_id` int(11) NOT NULL,
  `total_hours_worked` int(11) DEFAULT 0,
  `events_elapsed` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participants`
--

INSERT INTO `participants` (`user_id`, `name`, `email`, `type`, `teacher_id`, `school_id`, `total_hours_worked`, `events_elapsed`) VALUES
(1, 'Kovács Cecil', 'kovacscecil@gmail.com', 'student', 1, 1, 0, 0),
(2, 'Kis Ferenc', 'kisferi@gmail.com', 'student', 1, 1, 0, 0),
(3, 'Nagy Jónás', 'nagyjonas@gmail.com', 'student', 1, 1, 0, 0),
(4, 'Asztalos Tamás', 'asztalostomi@gmail.com', 'teacher', NULL, 1, 0, 0),
(5, 'Tasziló', 'taszilo@gmail.com', 'teacher', NULL, 1, 0, 0),
(6, 'Balla István', 'ballaistvan@gmail.com', 'student', 1, 1, 0, 0),
(7, 'Oros Jenő', 'orosjeno@gmail.com', 'student', 1, 1, 0, 0),
(8, 'Balázs', 'balazs@gmail.com', 'teacher', NULL, 1, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `participant_invitations`
--

CREATE TABLE `participant_invitations` (
  `invitation_id` int(11) NOT NULL,
  `event_workshop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ranking_number` int(11) NOT NULL,
  `status` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `participant_invitations`
--

INSERT INTO `participant_invitations` (`invitation_id`, `event_workshop_id`, `user_id`, `ranking_number`, `status`) VALUES
(12, 21, 3, 1, 'accepted'),
(13, 21, 2, 2, 'rejected'),
(14, 21, 5, 1, 'rejected'),
(15, 22, 7, 1, 'accepted'),
(16, 22, 6, 2, 'accepted'),
(17, 22, 8, 1, 'accepted'),
(18, 21, 1, 3, 'accepted'),
(19, 21, 4, 2, 'accepted'),
(20, 29, 1, 1, 'pending'),
(21, 29, 2, 2, 'pending'),
(22, 29, 5, 1, 'pending'),
(23, 31, 6, 1, 'pending'),
(24, 31, 7, 2, 'pending'),
(25, 31, 8, 1, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `rankings`
--

CREATE TABLE `rankings` (
  `ranking_id` int(11) NOT NULL,
  `event_workshop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ranking_number` int(11) NOT NULL,
  `user_type` enum('student','teacher') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rankings`
--

INSERT INTO `rankings` (`ranking_id`, `event_workshop_id`, `user_id`, `ranking_number`, `user_type`) VALUES
(19, 21, 1, 3, 'student'),
(20, 21, 2, 2, 'student'),
(21, 21, 3, 1, 'student'),
(22, 22, 6, 2, 'student'),
(23, 22, 7, 1, 'student'),
(24, 21, 5, 1, 'teacher'),
(25, 21, 4, 2, 'teacher'),
(26, 22, 8, 1, 'teacher'),
(27, 29, 1, 1, 'student'),
(28, 29, 2, 2, 'student'),
(29, 29, 3, 3, 'student'),
(30, 31, 6, 1, 'student'),
(31, 31, 7, 2, 'student'),
(32, 29, 4, 2, 'teacher'),
(33, 29, 5, 1, 'teacher'),
(34, 31, 8, 1, 'teacher');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `school_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`school_id`, `name`, `address`) VALUES
(1, 'Középiskola', 'középiskola címe');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `school_id` int(11) NOT NULL,
  `email` varchar(130) NOT NULL,
  `phone` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `name`, `school_id`, `email`, `phone`) VALUES
(1, 'Vicc Elek', 1, 'viccelek@gmail.com', '012345678');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`) VALUES
(1, 'kovacscecil', '$2y$10$Z9wk.E4/tBU4VVFouGKOwOtw8iwW29rnw4DtSKSLVxVLxZKYk8yjy'),
(2, 'kisferi@gmail.com', '$2y$10$GSpNmhPlnTLl2Cg.QcwL2.v1Ljf6OM394Y9X1hkq1AeYWlNS1BxYK'),
(3, 'nagyjonas', '$2y$10$zrJgxJib3oUZ21wJ20macOoqJvf5jO7Bh.gcssLfdRPMVfthep1vu'),
(4, 'asztalostomi', '$2y$10$wWlWgoEXOglltYg6v4FT2O51LNlmxl.qEEnKKJr4nSBn5FOdsoNte'),
(5, 'taszilo', '$2y$10$oidwjm02EAHMfczFPiaAae4XkTrMZJZm9/YftOmVzp6In2MzTl/R6'),
(6, 'ballaistvan', '$2y$10$B.uUeydOCRCAyddEIWodpO/hvNsb6VQQQbyFYLJ0kXnoFOvZ3PgZS'),
(7, 'orosjeno', '$2y$10$HzBVujtjmBLdOGOKOsntyuDZKhoY2W.YB3zyELYRtT.0QPPv/KwdG'),
(8, 'balazs', '$2y$10$Mm6q48SUySflC0Gm6sJ9dOj23jG2PDme1sJKgiuvAT/3hdByZuc7i');

-- --------------------------------------------------------

--
-- Table structure for table `workshops`
--

CREATE TABLE `workshops` (
  `workshop_id` int(11) NOT NULL,
  `name` varchar(130) NOT NULL,
  `description` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `workshops`
--

INSERT INTO `workshops` (`workshop_id`, `name`, `description`) VALUES
(1, 'CNC Programozás', 'Iskolai foglalkozás'),
(2, 'Robotprogramozás', 'Iskolai foglalkozás'),
(3, 'Forrasztás', 'Iskolai foglalkozás');

-- --------------------------------------------------------

--
-- Table structure for table `workshop_ranking`
--

CREATE TABLE `workshop_ranking` (
  `workshop_ranking_id` int(11) NOT NULL,
  `workshop_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ranking_number` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `attendance_sheets`
--
ALTER TABLE `attendance_sheets`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `attendance_sheets_ibfk_user` (`user_id`),
  ADD KEY `attendance_sheets_ibfk_event_workshop` (`event_workshop_id`);

--
-- Indexes for table `events`
--
ALTER TABLE `events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes for table `event_workshop`
--
ALTER TABLE `event_workshop`
  ADD PRIMARY KEY (`event_workshop_id`),
  ADD KEY `fk_event_workshop_event` (`event_id`),
  ADD KEY `fk_event_workshop_workshop` (`workshop_id`);

--
-- Indexes for table `mentor_workshop`
--
ALTER TABLE `mentor_workshop`
  ADD PRIMARY KEY (`mentor_workshop_id`),
  ADD KEY `mentor_workshop_ibfk_user` (`user_id`),
  ADD KEY `mentor_workshop_ibfk_workshop` (`workshop_id`);

--
-- Indexes for table `participants`
--
ALTER TABLE `participants`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `participants_ibfk_teacher` (`teacher_id`),
  ADD KEY `participants_ibfk_school` (`school_id`);

--
-- Indexes for table `participant_invitations`
--
ALTER TABLE `participant_invitations`
  ADD PRIMARY KEY (`invitation_id`),
  ADD KEY `participant_invitations_ibfk_event_workshop` (`event_workshop_id`),
  ADD KEY `participant_invitations_ibfk_user` (`user_id`);

--
-- Indexes for table `rankings`
--
ALTER TABLE `rankings`
  ADD PRIMARY KEY (`ranking_id`),
  ADD KEY `rankings_ibfk_event_workshop` (`event_workshop_id`),
  ADD KEY `rankings_ibfk_user` (`user_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`school_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD KEY `fk_teacher_school` (`school_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `workshops`
--
ALTER TABLE `workshops`
  ADD PRIMARY KEY (`workshop_id`);

--
-- Indexes for table `workshop_ranking`
--
ALTER TABLE `workshop_ranking`
  ADD PRIMARY KEY (`workshop_ranking_id`),
  ADD KEY `workshop_ranking_ibfk_workshop` (`workshop_id`),
  ADD KEY `workshop_ranking_ibfk_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance_sheets`
--
ALTER TABLE `attendance_sheets`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `events`
--
ALTER TABLE `events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `event_workshop`
--
ALTER TABLE `event_workshop`
  MODIFY `event_workshop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `mentor_workshop`
--
ALTER TABLE `mentor_workshop`
  MODIFY `mentor_workshop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `participant_invitations`
--
ALTER TABLE `participant_invitations`
  MODIFY `invitation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `rankings`
--
ALTER TABLE `rankings`
  MODIFY `ranking_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `school_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `workshops`
--
ALTER TABLE `workshops`
  MODIFY `workshop_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `workshop_ranking`
--
ALTER TABLE `workshop_ranking`
  MODIFY `workshop_ranking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admins`
--
ALTER TABLE `admins`
  ADD CONSTRAINT `admins_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance_sheets`
--
ALTER TABLE `attendance_sheets`
  ADD CONSTRAINT `attendance_sheets_ibfk_event_workshop` FOREIGN KEY (`event_workshop_id`) REFERENCES `event_workshop` (`event_workshop_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `attendance_sheets_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `participants` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `event_workshop`
--
ALTER TABLE `event_workshop`
  ADD CONSTRAINT `fk_event_workshop_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`event_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_event_workshop_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE;

--
-- Constraints for table `mentor_workshop`
--
ALTER TABLE `mentor_workshop`
  ADD CONSTRAINT `mentor_workshop_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `participants` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mentor_workshop_ibfk_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE;

--
-- Constraints for table `participants`
--
ALTER TABLE `participants`
  ADD CONSTRAINT `participants_ibfk_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participants_ibfk_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participants_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `participant_invitations`
--
ALTER TABLE `participant_invitations`
  ADD CONSTRAINT `participant_invitations_ibfk_event_workshop` FOREIGN KEY (`event_workshop_id`) REFERENCES `event_workshop` (`event_workshop_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `participant_invitations_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `participants` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `rankings`
--
ALTER TABLE `rankings`
  ADD CONSTRAINT `rankings_ibfk_event_workshop` FOREIGN KEY (`event_workshop_id`) REFERENCES `event_workshop` (`event_workshop_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `rankings_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `participants` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teacher_school` FOREIGN KEY (`school_id`) REFERENCES `schools` (`school_id`) ON DELETE CASCADE;

--
-- Constraints for table `workshop_ranking`
--
ALTER TABLE `workshop_ranking`
  ADD CONSTRAINT `workshop_ranking_ibfk_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `workshop_ranking_ibfk_workshop` FOREIGN KEY (`workshop_id`) REFERENCES `workshops` (`workshop_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
