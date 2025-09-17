-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 17, 2025 at 11:59 AM
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
-- Database: `chama_management`
--

-- --------------------------------------------------------

--
-- Table structure for table `apologies`
--

CREATE TABLE `apologies` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approved_by_superadmin` tinyint(1) DEFAULT 0,
  `approved_by_chairperson` tinyint(1) DEFAULT 0,
  `email_sent` tinyint(1) DEFAULT 0,
  `rejection_reason` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `apologies`
--

INSERT INTO `apologies` (`id`, `user_id`, `meeting_id`, `reason`, `submitted_at`, `status`, `approved_by`, `approved_by_superadmin`, `approved_by_chairperson`, `email_sent`, `rejection_reason`) VALUES
(1, 15, 5, 'Illness', '2025-02-04 14:00:00', 'approved', NULL, 1, 1, 1, NULL),
(2, 19, 5, 'Medical appointment', '2025-02-06 05:00:00', 'approved', NULL, 1, 1, 1, NULL),
(3, 28, 5, 'Personal issue', '2025-02-08 06:00:00', 'approved', NULL, 1, 1, 1, NULL),
(4, 31, 9, 'Work commitment', '2025-04-02 06:00:00', 'approved', NULL, 1, 1, 1, NULL),
(5, 2, 11, 'Family event', '2025-04-24 22:00:00', 'approved', NULL, 1, 1, 1, NULL),
(6, 31, 11, 'Family emergency', '2025-04-29 14:00:00', 'approved', NULL, 1, 1, 1, NULL),
(7, 3, 15, 'Conflicting appointment', '2025-06-27 00:00:00', 'approved', NULL, 1, 1, 1, NULL),
(8, 11, 17, 'Illness', '2025-07-26 17:00:00', 'approved', NULL, 1, 1, 1, NULL),
(9, 10, 19, 'Work commitment', '2025-08-14 01:00:00', 'approved', NULL, 1, 1, 1, NULL),
(10, 2, 2, 'Family event', '2025-01-08 02:00:00', 'approved', NULL, 1, 1, 1, NULL),
(11, 28, 2, 'Illness', '2025-01-08 08:00:00', 'approved', NULL, 1, 1, 1, NULL),
(12, 2, 6, 'Work commitment', '2025-02-23 23:00:00', 'approved', NULL, 1, 1, 1, NULL),
(13, 9, 8, 'Travel conflict', '2025-03-19 21:00:00', 'approved', NULL, 1, 1, 1, NULL),
(14, 15, 10, 'Personal issue', '2025-04-13 23:00:00', 'approved', NULL, 1, 1, 1, NULL),
(15, 31, 10, 'Family emergency', '2025-04-09 15:00:00', 'approved', NULL, 1, 1, 1, NULL),
(16, 10, 12, 'Family emergency', '2025-05-13 17:00:00', 'approved', NULL, 1, 1, 1, NULL),
(17, 9, 18, 'Family event', '2025-08-03 21:00:00', 'approved', NULL, 1, 1, 1, NULL),
(18, 15, 18, 'Conflicting appointment', '2025-08-07 23:00:00', 'approved', NULL, 1, 1, 1, NULL),
(19, 2, 32, 'Conflicting appointment', '2025-08-14 21:35:00', 'approved', NULL, 1, 1, 1, NULL),
(20, 10, 32, 'Family emergency', '2025-08-15 16:35:00', 'approved', NULL, 1, 1, 1, NULL),
(21, 31, 32, 'Illness', '2025-08-15 03:35:00', 'approved', NULL, 1, 1, 1, NULL),
(22, 2, 33, 'Work commitment', '2025-08-19 01:00:00', 'approved', NULL, 1, 1, 1, NULL),
(23, 10, 33, 'Illness', '2025-08-19 14:00:00', 'approved', NULL, 1, 1, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `attended_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('present','absent','absent_with_apology') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `meeting_id`, `attended_at`, `status`) VALUES
(1, 2, 3, '2025-01-25 11:10:00', 'present'),
(2, 3, 3, '2025-01-25 11:11:00', 'present'),
(3, 9, 3, '2025-01-25 11:02:00', 'present'),
(4, 10, 3, '2025-01-25 11:11:00', 'present'),
(5, 11, 3, '2025-01-25 11:12:00', 'present'),
(6, 15, 3, '2025-01-25 11:08:00', 'present'),
(7, 18, 3, '2025-01-25 11:05:00', 'present'),
(8, 19, 3, '2025-01-25 11:11:00', 'present'),
(9, 28, 3, '2025-01-25 11:12:00', 'present'),
(10, 31, 3, '2025-01-25 12:00:00', 'absent'),
(11, 2, 5, '2025-02-12 08:00:00', 'absent'),
(12, 3, 5, '2025-02-12 08:00:00', 'absent'),
(13, 9, 5, '2025-02-12 07:12:00', 'present'),
(14, 10, 5, '2025-02-12 08:00:00', 'absent'),
(15, 11, 5, '2025-02-12 08:00:00', 'absent'),
(16, 15, 5, '2025-02-12 08:00:00', 'absent_with_apology'),
(17, 18, 5, '2025-02-12 07:10:00', 'present'),
(18, 19, 5, '2025-02-12 08:00:00', 'absent_with_apology'),
(19, 28, 5, '2025-02-12 08:00:00', 'absent_with_apology'),
(20, 31, 5, '2025-02-12 07:04:00', 'present'),
(21, 2, 7, '2025-03-15 11:01:00', 'present'),
(22, 3, 7, '2025-03-15 11:08:00', 'present'),
(23, 9, 7, '2025-03-15 11:06:00', 'present'),
(24, 10, 7, '2025-03-15 11:15:00', 'present'),
(25, 11, 7, '2025-03-15 12:00:00', 'absent'),
(26, 15, 7, '2025-03-15 11:09:00', 'present'),
(27, 18, 7, '2025-03-15 11:03:00', 'present'),
(28, 19, 7, '2025-03-15 12:00:00', 'absent'),
(29, 28, 7, '2025-03-15 11:03:00', 'present'),
(30, 31, 7, '2025-03-15 11:05:00', 'present'),
(31, 2, 9, '2025-04-05 08:00:00', 'absent'),
(32, 3, 9, '2025-04-05 07:01:00', 'present'),
(33, 9, 9, '2025-04-05 07:00:00', 'present'),
(34, 10, 9, '2025-04-05 07:00:00', 'present'),
(35, 11, 9, '2025-04-05 08:00:00', 'absent'),
(36, 15, 9, '2025-04-05 07:15:00', 'present'),
(37, 18, 9, '2025-04-05 07:02:00', 'present'),
(38, 19, 9, '2025-04-05 07:12:00', 'present'),
(39, 28, 9, '2025-04-05 07:07:00', 'present'),
(40, 31, 9, '2025-04-05 08:00:00', 'absent_with_apology'),
(41, 2, 11, '2025-05-01 12:00:00', 'absent_with_apology'),
(42, 3, 11, '2025-05-01 12:00:00', 'absent'),
(43, 9, 11, '2025-05-01 11:13:00', 'present'),
(44, 10, 11, '2025-05-01 11:00:00', 'present'),
(45, 11, 11, '2025-05-01 11:02:00', 'present'),
(46, 15, 11, '2025-05-01 12:00:00', 'absent'),
(47, 18, 11, '2025-05-01 12:00:00', 'absent'),
(48, 19, 11, '2025-05-01 11:11:00', 'present'),
(49, 28, 11, '2025-05-01 12:00:00', 'absent'),
(50, 31, 11, '2025-05-01 12:00:00', 'absent_with_apology'),
(51, 2, 13, '2025-06-01 08:14:00', 'present'),
(52, 3, 13, '2025-06-01 08:00:00', 'present'),
(53, 9, 13, '2025-06-01 09:00:00', 'absent'),
(54, 10, 13, '2025-06-01 08:06:00', 'present'),
(55, 11, 13, '2025-06-01 08:04:00', 'present'),
(56, 15, 13, '2025-06-01 09:00:00', 'absent'),
(57, 18, 13, '2025-06-01 08:12:00', 'present'),
(58, 19, 13, '2025-06-01 08:14:00', 'present'),
(59, 28, 13, '2025-06-01 08:05:00', 'present'),
(60, 31, 13, '2025-06-01 08:12:00', 'present'),
(61, 2, 15, '2025-07-01 08:00:00', 'absent'),
(62, 3, 15, '2025-07-01 08:00:00', 'absent_with_apology'),
(63, 9, 15, '2025-07-01 07:14:00', 'present'),
(64, 10, 15, '2025-07-01 07:01:00', 'present'),
(65, 11, 15, '2025-07-01 08:00:00', 'absent'),
(66, 15, 15, '2025-07-01 08:00:00', 'absent'),
(67, 18, 15, '2025-07-01 08:00:00', 'absent'),
(68, 19, 15, '2025-07-01 08:00:00', 'absent'),
(69, 28, 15, '2025-07-01 07:07:00', 'present'),
(70, 31, 15, '2025-07-01 07:00:00', 'present'),
(71, 2, 17, '2025-07-30 12:00:00', 'absent'),
(72, 3, 17, '2025-07-30 11:08:00', 'present'),
(73, 9, 17, '2025-07-30 12:00:00', 'absent'),
(74, 10, 17, '2025-07-30 11:01:00', 'present'),
(75, 11, 17, '2025-07-30 12:00:00', 'absent_with_apology'),
(76, 15, 17, '2025-07-30 11:15:00', 'present'),
(77, 18, 17, '2025-07-30 11:09:00', 'present'),
(78, 19, 17, '2025-07-30 12:00:00', 'absent'),
(79, 28, 17, '2025-07-30 11:10:00', 'present'),
(80, 31, 17, '2025-07-30 11:15:00', 'present'),
(81, 2, 19, '2025-08-20 12:08:00', 'present'),
(82, 3, 19, '2025-08-20 13:00:00', 'absent'),
(83, 9, 19, '2025-08-20 12:09:00', 'present'),
(84, 10, 19, '2025-08-20 13:00:00', 'absent_with_apology'),
(85, 11, 19, '2025-08-20 12:12:00', 'present'),
(86, 15, 19, '2025-08-20 13:00:00', 'absent'),
(87, 18, 19, '2025-08-20 13:00:00', 'absent'),
(88, 19, 19, '2025-08-20 12:01:00', 'present'),
(89, 28, 19, '2025-08-20 12:06:00', 'present'),
(90, 31, 19, '2025-08-20 12:09:00', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `fines`
--

CREATE TABLE `fines` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `issued_at` datetime NOT NULL,
  `status` enum('pending','paid') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `loan_applications`
--

CREATE TABLE `loan_applications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` enum('Personal','Emergency','Business') NOT NULL,
  `purpose` text NOT NULL,
  `repayment_period` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `disbursement_status` enum('Pending','Disbursed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `loan_applications`
--

INSERT INTO `loan_applications` (`id`, `user_id`, `amount`, `category`, `purpose`, `repayment_period`, `status`, `applied_at`, `updated_at`, `approved_by`, `rejection_reason`, `disbursement_status`) VALUES
(1, 9, 2000.00, 'Personal', 'I want to handle some family affairs', 1, 'approved', '2025-08-25 18:02:50', '2025-08-25 18:05:04', 11, NULL, 'Disbursed');

-- --------------------------------------------------------

--
-- Table structure for table `meetings`
--

CREATE TABLE `meetings` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `online_link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meetings`
--

INSERT INTO `meetings` (`id`, `title`, `description`, `meeting_date`, `online_link`, `created_at`) VALUES
(1, 'Annual General Meeting', '*Purpose* - This is a meeting for all members of the chama - we will be discussing major issues in the chama and welcoming new members', '2025-07-29 09:00:00', NULL, '2025-07-28 13:38:10'),
(2, 'January Monthly Meeting', '*Purpose* - To kick off the year with financial planning\n- Review member contributions\n- Set savings goals', '2025-01-15 09:00:00', NULL, '2024-12-20 05:00:00'),
(3, 'Financial Literacy Seminar', '*Purpose* - To educate members on managing finances\n- Budgeting tips\n- Investment basics', '2025-01-25 14:00:00', 'https://zoom.us/j/91234567890?pwd=Xyz789Abc123', '2024-12-25 06:00:00'),
(4, 'Loan Approval Review', '*Purpose* - To evaluate loan applications\n- Discuss repayment terms\n- Update loan policies', '2025-02-05 15:00:00', NULL, '2025-01-10 07:00:00'),
(5, 'February Monthly Meeting', '*Purpose* - To monitor savings progress\n- Plan welfare initiatives\n- Address member queries', '2025-02-12 10:00:00', 'https://zoom.us/j/92345678901?pwd=Def456Ghi789', '2025-01-15 05:00:00'),
(6, 'Emergency Fund Discussion', '*Purpose* - To review emergency fund usage\n- Approve disbursements\n- Set contribution targets', '2025-03-01 11:00:00', NULL, '2025-02-05 06:00:00'),
(7, 'March Monthly Meeting', '*Purpose* - To discuss quarterly financials\n- Plan loan disbursements\n- Member feedback session', '2025-03-15 14:00:00', 'https://zoom.us/j/93456789012?pwd=Jkl012Mno345', '2025-02-20 07:00:00'),
(8, 'Welfare Planning Session', '*Purpose* - To address member welfare needs\n- Plan community outreach\n- Allocate welfare funds', '2025-03-25 13:00:00', NULL, '2025-03-01 05:00:00'),
(9, 'Investment Strategy Meeting', '*Purpose* - To explore investment options\n- Review past investments\n- Set financial goals', '2025-04-05 10:00:00', 'https://zoom.us/j/94567890123?pwd=Pqr678Stu901', '2025-03-10 06:00:00'),
(10, 'April Monthly Meeting', '*Purpose* - To review savings and loans\n- Prepare for mid-year review\n- Discuss member issues', '2025-04-15 15:00:00', NULL, '2025-03-20 07:00:00'),
(11, 'Mid-Year Financial Review', '*Purpose* - To assess half-year performance\n- Adjust budgets\n- Approve new loans', '2025-05-01 14:00:00', 'https://zoom.us/j/95678901234?pwd=Uvw123Xyz456', '2025-04-05 05:00:00'),
(12, 'May Monthly Meeting', '*Purpose* - To discuss welfare programs\n- Review loan repayments\n- Plan upcoming events', '2025-05-15 09:00:00', NULL, '2025-04-20 06:00:00'),
(13, 'Loan Policy Revision', '*Purpose* - To update loan eligibility criteria\n- Address repayment issues\n- Member feedback', '2025-06-01 11:00:00', 'https://zoom.us/j/96789012345?pwd=Abc789Def012', '2025-05-10 07:00:00'),
(14, 'June Monthly Meeting', '*Purpose* - To prepare for AGM\n- Review financial reports\n- Discuss member concerns', '2025-06-15 14:00:00', NULL, '2025-05-20 05:00:00'),
(15, 'Community Outreach Planning', '*Purpose* - To plan community support projects\n- Allocate welfare funds\n- Encourage member participation', '2025-07-01 10:00:00', 'https://zoom.us/j/97890123456?pwd=Ghi345Jkl678', '2025-06-05 06:00:00'),
(16, 'July Monthly Meeting', '*Purpose* - To finalize AGM preparations\n- Review savings targets\n- Address loan defaults', '2025-07-15 15:00:00', NULL, '2025-06-20 07:00:00'),
(17, 'Post-AGM Follow-Up', '*Purpose* - To implement AGM resolutions\n- Discuss new financial goals\n- Member feedback', '2025-07-30 14:00:00', 'https://zoom.us/j/98901234567?pwd=Mno901Pqr234', '2025-07-28 05:00:00'),
(18, 'August Monthly Meeting', '*Purpose* - To review post-AGM financial plans\n- Discuss member contributions\n- Plan welfare activities', '2025-08-10 09:00:00', NULL, '2025-07-15 06:00:00'),
(19, 'Loan Disbursement Meeting', '*Purpose* - To disburse approved loans\n- Review repayment schedules\n- Update loan policies', '2025-08-20 15:00:00', 'https://zoom.us/j/99012345678?pwd=Stu567Uvw890', '2025-07-25 07:00:00'),
(20, 'Financial Education Workshop', '*Purpose* - To educate members on financial planning\n- Investment strategies\n- Savings techniques', '2025-09-01 10:00:00', NULL, '2025-08-05 05:00:00'),
(21, 'September Monthly Meeting', '*Purpose* - To review quarterly savings\n- Plan year-end activities\n- Address member queries', '2025-09-15 14:00:00', 'https://zoom.us/j/90123456789?pwd=Xyz123Abc456', '2025-08-20 06:00:00'),
(22, 'Welfare Fund Allocation', '*Purpose* - To allocate funds for member welfare\n- Discuss community projects\n- Approve disbursements', '2025-09-25 11:00:00', NULL, '2025-08-25 07:00:00'),
(23, 'Investment Performance Review', '*Purpose* - To assess investment outcomes\n- Explore new opportunities\n- Set investment targets', '2025-10-05 15:00:00', 'https://zoom.us/j/91234567890?pwd=Def789Ghi012', '2025-09-10 05:00:00'),
(24, 'October Monthly Meeting', '*Purpose* - To discuss financial reports\n- Plan holiday season activities\n- Address member concerns', '2025-10-15 09:00:00', NULL, '2025-09-20 06:00:00'),
(25, 'Loan Repayment Review', '*Purpose* - To review loan repayment progress\n- Address defaults\n- Update eligibility criteria', '2025-10-25 14:00:00', 'https://zoom.us/j/92345678901?pwd=Jkl345Mno678', '2025-09-25 07:00:00'),
(26, 'November Monthly Meeting', '*Purpose* - To prepare for year-end review\n- Discuss savings targets\n- Plan for 2026', '2025-11-10 10:00:00', NULL, '2025-10-15 05:00:00'),
(27, 'Year-End Planning Session', '*Purpose* - To plan year-end activities\n- Review annual performance\n- Set 2026 goals', '2025-11-20 15:00:00', 'https://zoom.us/j/93456789012?pwd=Pqr901Stu234', '2025-10-25 06:00:00'),
(28, 'Community Support Review', '*Purpose* - To evaluate community outreach programs\n- Allocate additional funds\n- Member participation', '2025-12-01 11:00:00', NULL, '2025-11-05 07:00:00'),
(29, 'December Monthly Meeting', '*Purpose* - To finalize year-end financials\n- Discuss holiday contributions\n- Member feedback', '2025-12-10 14:00:00', 'https://zoom.us/j/94567890123?pwd=Uvw567Xyz890', '2025-11-15 05:00:00'),
(30, 'Year-End Celebration Planning', '*Purpose* - To plan year-end member celebration\n- Allocate celebration budget\n- Organize activities', '2025-12-20 10:00:00', NULL, '2025-11-25 06:00:00'),
(31, 'Final 2025 Review', '*Purpose* - To close out 2025 financials\n- Prepare annual report\n- Set preliminary 2026 plans', '2025-12-30 15:00:00', 'https://zoom.us/j/95678901234?pwd=Abc123Def456', '2025-12-05 07:00:00'),
(32, 'Loan management meeting', '*Purpose*  This meeting is to discuss the chama\'s loans and how disbursement is supposed to be carried out', '2025-08-18 11:35:00', NULL, '2025-08-18 08:20:05'),
(33, 'Orientaation meeting', '*Purpose* - This meeting is purposely for the new members in the chama. This meeting is for orientation.', '2025-08-24 15:00:00', NULL, '2025-08-24 11:56:20'),
(34, '2nd Orientation meeting ', '*Purpose* - This is a continuation of the orientation process in the chama. - purpose to attend ', '2025-08-25 10:13:00', NULL, '2025-08-25 07:10:00');

-- --------------------------------------------------------

--
-- Table structure for table `meeting_attendance`
--

CREATE TABLE `meeting_attendance` (
  `id` int(11) NOT NULL,
  `meeting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_present` tinyint(1) DEFAULT 0,
  `has_apology` tinyint(1) DEFAULT 0,
  `recorded_at` datetime DEFAULT current_timestamp(),
  `status` enum('present','absent','absent_with_apology') DEFAULT 'present'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `meeting_attendance`
--

INSERT INTO `meeting_attendance` (`id`, `meeting_id`, `user_id`, `is_present`, `has_apology`, `recorded_at`, `status`) VALUES
(1, 1, 2, 1, 0, '2025-07-29 09:03:00', 'present'),
(2, 1, 3, 1, 0, '2025-07-29 09:02:00', 'present'),
(3, 1, 9, 0, 0, '2025-07-29 10:00:00', 'absent'),
(4, 1, 10, 1, 0, '2025-07-29 09:08:00', 'present'),
(5, 1, 11, 1, 0, '2025-07-29 09:09:00', 'present'),
(6, 1, 15, 1, 0, '2025-07-29 09:08:00', 'present'),
(7, 1, 18, 1, 0, '2025-07-29 09:12:00', 'present'),
(8, 1, 19, 1, 0, '2025-07-29 09:08:00', 'present'),
(9, 1, 28, 0, 0, '2025-07-29 10:00:00', 'absent'),
(10, 1, 31, 0, 0, '2025-07-29 10:00:00', 'absent'),
(11, 2, 2, 0, 1, '2025-01-15 10:00:00', 'absent_with_apology'),
(12, 2, 3, 1, 0, '2025-01-15 09:15:00', 'present'),
(13, 2, 9, 1, 0, '2025-01-15 09:10:00', 'present'),
(14, 2, 10, 1, 0, '2025-01-15 09:11:00', 'present'),
(15, 2, 11, 1, 0, '2025-01-15 09:11:00', 'present'),
(16, 2, 15, 1, 0, '2025-01-15 09:08:00', 'present'),
(17, 2, 18, 1, 0, '2025-01-15 09:13:00', 'present'),
(18, 2, 19, 1, 0, '2025-01-15 09:10:00', 'present'),
(19, 2, 28, 0, 1, '2025-01-15 10:00:00', 'absent_with_apology'),
(20, 2, 31, 1, 0, '2025-01-15 09:14:00', 'present'),
(21, 4, 2, 1, 0, '2025-02-05 15:01:00', 'present'),
(22, 4, 3, 0, 0, '2025-02-05 16:00:00', 'absent'),
(23, 4, 9, 1, 0, '2025-02-05 15:09:00', 'present'),
(24, 4, 10, 0, 0, '2025-02-05 16:00:00', 'absent'),
(25, 4, 11, 1, 0, '2025-02-05 15:14:00', 'present'),
(26, 4, 15, 1, 0, '2025-02-05 15:12:00', 'present'),
(27, 4, 18, 1, 0, '2025-02-05 15:12:00', 'present'),
(28, 4, 19, 1, 0, '2025-02-05 15:08:00', 'present'),
(29, 4, 28, 1, 0, '2025-02-05 15:08:00', 'present'),
(30, 4, 31, 1, 0, '2025-02-05 15:05:00', 'present'),
(31, 6, 2, 0, 1, '2025-03-01 12:00:00', 'absent_with_apology'),
(32, 6, 3, 1, 0, '2025-03-01 11:00:00', 'present'),
(33, 6, 9, 1, 0, '2025-03-01 11:00:00', 'present'),
(34, 6, 10, 1, 0, '2025-03-01 11:14:00', 'present'),
(35, 6, 11, 0, 0, '2025-03-01 12:00:00', 'absent'),
(36, 6, 15, 0, 0, '2025-03-01 12:00:00', 'absent'),
(37, 6, 18, 0, 0, '2025-03-01 12:00:00', 'absent'),
(38, 6, 19, 1, 0, '2025-03-01 11:11:00', 'present'),
(39, 6, 28, 1, 0, '2025-03-01 11:07:00', 'present'),
(40, 6, 31, 1, 0, '2025-03-01 11:14:00', 'present'),
(41, 8, 2, 1, 0, '2025-03-25 13:11:00', 'present'),
(42, 8, 3, 1, 0, '2025-03-25 13:03:00', 'present'),
(43, 8, 9, 0, 1, '2025-03-25 14:00:00', 'absent_with_apology'),
(44, 8, 10, 1, 0, '2025-03-25 13:00:00', 'present'),
(45, 8, 11, 0, 0, '2025-03-25 14:00:00', 'absent'),
(46, 8, 15, 1, 0, '2025-03-25 13:01:00', 'present'),
(47, 8, 18, 1, 0, '2025-03-25 13:05:00', 'present'),
(48, 8, 19, 1, 0, '2025-03-25 13:08:00', 'present'),
(49, 8, 28, 1, 0, '2025-03-25 13:06:00', 'present'),
(50, 8, 31, 1, 0, '2025-03-25 13:10:00', 'present'),
(51, 10, 2, 1, 0, '2025-04-15 15:09:00', 'present'),
(52, 10, 3, 1, 0, '2025-04-15 15:12:00', 'present'),
(53, 10, 9, 1, 0, '2025-04-15 15:15:00', 'present'),
(54, 10, 10, 1, 0, '2025-04-15 15:13:00', 'present'),
(55, 10, 11, 1, 0, '2025-04-15 15:09:00', 'present'),
(56, 10, 15, 0, 1, '2025-04-15 16:00:00', 'absent_with_apology'),
(57, 10, 18, 1, 0, '2025-04-15 15:14:00', 'present'),
(58, 10, 19, 0, 0, '2025-04-15 16:00:00', 'absent'),
(59, 10, 28, 0, 0, '2025-04-15 16:00:00', 'absent'),
(60, 10, 31, 0, 1, '2025-04-15 16:00:00', 'absent_with_apology'),
(61, 12, 2, 1, 0, '2025-05-15 09:13:00', 'present'),
(62, 12, 3, 1, 0, '2025-05-15 09:10:00', 'present'),
(63, 12, 9, 1, 0, '2025-05-15 09:02:00', 'present'),
(64, 12, 10, 0, 1, '2025-05-15 10:00:00', 'absent_with_apology'),
(65, 12, 11, 0, 0, '2025-05-15 10:00:00', 'absent'),
(66, 12, 15, 1, 0, '2025-05-15 09:14:00', 'present'),
(67, 12, 18, 1, 0, '2025-05-15 09:07:00', 'present'),
(68, 12, 19, 1, 0, '2025-05-15 09:08:00', 'present'),
(69, 12, 28, 1, 0, '2025-05-15 09:07:00', 'present'),
(70, 12, 31, 1, 0, '2025-05-15 09:14:00', 'present'),
(71, 14, 2, 1, 0, '2025-06-15 14:01:00', 'present'),
(72, 14, 3, 1, 0, '2025-06-15 14:10:00', 'present'),
(73, 14, 9, 0, 0, '2025-06-15 15:00:00', 'absent'),
(74, 14, 10, 1, 0, '2025-06-15 14:15:00', 'present'),
(75, 14, 11, 1, 0, '2025-06-15 14:01:00', 'present'),
(76, 14, 15, 1, 0, '2025-06-15 14:00:00', 'present'),
(77, 14, 18, 1, 0, '2025-06-15 14:01:00', 'present'),
(78, 14, 19, 0, 0, '2025-06-15 15:00:00', 'absent'),
(79, 14, 28, 1, 0, '2025-06-15 14:12:00', 'present'),
(80, 14, 31, 1, 0, '2025-06-15 14:06:00', 'present'),
(81, 16, 2, 1, 0, '2025-07-15 15:15:00', 'present'),
(82, 16, 3, 1, 0, '2025-07-15 15:08:00', 'present'),
(83, 16, 9, 0, 0, '2025-07-15 16:00:00', 'absent'),
(84, 16, 10, 0, 0, '2025-07-15 16:00:00', 'absent'),
(85, 16, 11, 1, 0, '2025-07-15 15:12:00', 'present'),
(86, 16, 15, 0, 0, '2025-07-15 16:00:00', 'absent'),
(87, 16, 18, 1, 0, '2025-07-15 15:08:00', 'present'),
(88, 16, 19, 1, 0, '2025-07-15 15:02:00', 'present'),
(89, 16, 28, 1, 0, '2025-07-15 15:01:00', 'present'),
(90, 16, 31, 1, 0, '2025-07-15 15:11:00', 'present'),
(91, 18, 2, 1, 0, '2025-08-10 09:04:00', 'present'),
(92, 18, 3, 1, 0, '2025-08-10 09:00:00', 'present'),
(93, 18, 9, 0, 1, '2025-08-10 10:00:00', 'absent_with_apology'),
(94, 18, 10, 1, 0, '2025-08-10 09:08:00', 'present'),
(95, 18, 11, 1, 0, '2025-08-10 09:02:00', 'present'),
(96, 18, 15, 0, 1, '2025-08-10 10:00:00', 'absent_with_apology'),
(97, 18, 18, 1, 0, '2025-08-10 09:08:00', 'present'),
(98, 18, 19, 1, 0, '2025-08-10 09:02:00', 'present'),
(99, 18, 28, 1, 0, '2025-08-10 09:07:00', 'present'),
(100, 18, 31, 1, 0, '2025-08-10 09:14:00', 'present'),
(101, 32, 2, 0, 1, '2025-08-18 12:35:00', 'absent_with_apology'),
(102, 32, 3, 0, 0, '2025-08-18 12:35:00', 'absent'),
(103, 32, 9, 1, 0, '2025-08-18 11:37:00', 'present'),
(104, 32, 10, 0, 1, '2025-08-18 12:35:00', 'absent_with_apology'),
(105, 32, 11, 1, 0, '2025-08-18 11:37:00', 'present'),
(106, 32, 15, 1, 0, '2025-08-18 11:49:00', 'present'),
(107, 32, 18, 1, 0, '2025-08-18 11:45:00', 'present'),
(108, 32, 19, 0, 0, '2025-08-18 12:35:00', 'absent'),
(109, 32, 28, 1, 0, '2025-08-18 11:37:00', 'present'),
(110, 32, 31, 0, 1, '2025-08-18 12:35:00', 'absent_with_apology'),
(111, 33, 2, 0, 1, '2025-08-24 16:00:00', 'absent_with_apology'),
(112, 33, 3, 1, 0, '2025-08-24 15:12:00', 'present'),
(113, 33, 9, 1, 0, '2025-08-24 15:15:00', 'present'),
(114, 33, 10, 0, 1, '2025-08-24 16:00:00', 'absent_with_apology'),
(115, 33, 11, 1, 0, '2025-08-24 15:06:00', 'present'),
(116, 33, 15, 1, 0, '2025-08-24 15:13:00', 'present'),
(117, 33, 18, 1, 0, '2025-08-24 15:15:00', 'present'),
(118, 33, 19, 1, 0, '2025-08-24 15:13:00', 'present'),
(119, 33, 28, 1, 0, '2025-08-24 15:13:00', 'present'),
(120, 33, 31, 1, 0, '2025-08-24 15:15:00', 'present'),
(121, 34, 2, 1, 0, '2025-08-25 10:21:00', 'present'),
(122, 34, 3, 0, 0, '2025-08-25 11:13:00', 'absent'),
(123, 34, 9, 1, 0, '2025-08-25 10:21:00', 'present'),
(124, 34, 10, 1, 0, '2025-08-25 10:21:00', 'present'),
(125, 34, 11, 1, 0, '2025-08-25 10:22:00', 'present'),
(126, 34, 15, 0, 0, '2025-08-25 11:13:00', 'absent'),
(127, 34, 18, 1, 0, '2025-08-25 10:24:00', 'present'),
(128, 34, 19, 1, 0, '2025-08-25 10:21:00', 'present'),
(129, 34, 28, 1, 0, '2025-08-25 10:22:00', 'present'),
(130, 34, 31, 1, 0, '2025-08-25 10:18:00', 'present');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`) VALUES
(1, 'waithakas2003@gmail.com', 'b77cfaabe5477c2499ce07ffba310a53a08a341787ae445bad7bcc83fa7d87c8', '2025-08-28 20:37:38'),
(2, 'waithakas2003@gmail.com', '9f70d1685e9009d13d001b4744218f3ce393e21938f27ae3ccfb8cb83d9dd022', '2025-08-28 20:37:58'),
(3, 'waithakas2003@gmail.com', '00c39c62cfc5291e7cf72eb940c2114fd1587bc2cc9ad0ddda6d2415798f60ff', '2025-08-28 20:49:31'),
(4, 'waithakas2003@gmail.com', '800868a1e8c0c1e5bd43bc6d6684991823376913d4867e199ac07ae752a9aef7', '2025-08-28 20:54:53');

-- --------------------------------------------------------

--
-- Table structure for table `removal_requests`
--

CREATE TABLE `removal_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by_superadmin` tinyint(1) DEFAULT 0,
  `reason` text DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `removal_requests`
--

INSERT INTO `removal_requests` (`id`, `user_id`, `requested_by`, `requested_at`, `status`, `approved_by_superadmin`, `reason`, `rejection_reason`) VALUES
(3, 3, 11, '2025-08-13 16:43:21', 'approved', 1, NULL, NULL),
(4, 2, 11, '2025-08-13 17:05:56', 'approved', 1, NULL, NULL),
(5, 2, 11, '2025-08-13 17:14:00', 'approved', 1, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `role_changes`
--

CREATE TABLE `role_changes` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `new_role` enum('member','chairperson','secretary') NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changed_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_changes`
--

INSERT INTO `role_changes` (`id`, `user_id`, `new_role`, `changed_by`, `changed_at`) VALUES
(1, 2, 'member', 10, '2025-07-28 14:23:20'),
(2, 3, 'member', 10, '2025-07-30 16:57:40');

-- --------------------------------------------------------

--
-- Table structure for table `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(12) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(50) NOT NULL,
  `merchant_request_id` varchar(100) DEFAULT NULL,
  `checkout_request_id` varchar(100) NOT NULL,
  `mpesa_receipt` varchar(100) DEFAULT NULL,
  `status` enum('pending','completed','failed') NOT NULL DEFAULT 'pending',
  `transaction_desc` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transactions`
--

INSERT INTO `transactions` (`id`, `user_id`, `phone_number`, `amount`, `type`, `merchant_request_id`, `checkout_request_id`, `mpesa_receipt`, `status`, `transaction_desc`, `created_at`, `updated_at`) VALUES
(1, 2, '254712345678', 100.00, 'monthly', '0', 'ws_CO_2025011512345678', 'TH12AB3CDE', 'completed', 'Chama Payment - Monthly', '2025-01-15 09:00:00', '2025-01-15 09:01:00'),
(2, 2, '254712345678', 100.00, 'monthly', '9', 'ws_CO_2025021012345678', 'TH45FG6HIJ', 'completed', 'Chama Payment - Monthly', '2025-02-10 10:30:00', '2025-02-10 10:31:00'),
(3, 2, '254712345678', 100.00, 'monthly', '0', 'ws_CO_2025032012345678', 'TH78KL9MNO', 'completed', 'Chama Payment - Monthly', '2025-03-20 14:00:00', '2025-03-20 14:01:00'),
(4, 2, '254712345678', 1.00, 'emergency', '92', 'ws_CO_2025040512345678', NULL, 'pending', 'Chama Payment - Emergency', '2025-04-05 09:15:00', '2025-04-05 09:15:00'),
(5, 2, '254712345678', 100.00, 'monthly', '9', 'ws_CO_2025051512345678', 'TH01PQ2RST', 'completed', 'Chama Payment - Monthly', '2025-05-15 11:45:00', '2025-05-15 11:46:00'),
(6, 2, '254712345678', 1700.00, 'investment', '0', 'ws_CO_2025061012345678', 'TH34UV5WXY', 'completed', 'Chama Payment - Investment', '2025-06-10 10:00:00', '2025-06-10 10:01:00'),
(7, 2, '254712345678', 1.00, 'emergency', '92', 'ws_CO_2025071512345678', 'TH67ZA8BCD', 'completed', 'Chama Payment - Emergency', '2025-07-15 09:30:00', '2025-07-15 09:31:00'),
(8, 9, '254796258348', 1.00, 'investment', '92', 'ws_CO_200820251322499796258348', 'THK55NYBLB', 'completed', 'Chama Payment - Investment', '2025-08-20 13:23:28', '2025-08-20 13:23:37'),
(9, 3, '254723456789', 100.00, 'monthly', '9', 'ws_CO_20250215123456789', NULL, 'pending', 'Chama Payment - Monthly', '2025-02-15 13:30:00', '2025-02-15 13:30:00'),
(10, 3, '254723456789', 100.00, 'monthly', '0', 'ws_CO_20250320123456789', NULL, 'failed', 'Chama Payment - Monthly', '2025-03-20 10:00:00', '2025-03-20 10:00:00'),
(11, 3, '254723456789', 1.00, 'emergency', '92', 'ws_CO_20250405123456789', 'TH56ZA7BCD', 'completed', 'Chama Payment - Emergency', '2025-04-05 09:30:00', '2025-04-05 09:31:00'),
(12, 3, '254723456789', 300.00, 'investment', '9', 'ws_CO_20250510123456789', 'TH89EF0GHI', 'completed', 'Chama Payment - Investment', '2025-05-10 11:00:00', '2025-05-10 11:01:00'),
(13, 3, '254723456789', 1.00, 'emergency', '0', 'ws_CO_20250615123456789', 'TH12GH3IJK', 'completed', 'Chama Payment - Emergency', '2025-06-15 10:30:00', '2025-06-15 10:31:00'),
(14, 9, '254796258348', 100.00, 'monthly', '0', 'ws_CO_202501051796258348', 'TH45OP6QRS', 'completed', 'Chama Payment - Monthly', '2025-01-05 08:30:00', '2025-01-05 08:31:00'),
(15, 9, '254796258348', 100.00, 'monthly', '9', 'ws_CO_202502121796258348', 'TH78TU9VWX', 'completed', 'Chama Payment - Monthly', '2025-02-12 11:00:00', '2025-02-12 11:01:00'),
(16, 9, '254796258348', 100.00, 'monthly', '0', 'ws_CO_202503101796258348', 'TH01YZ2ABC', 'completed', 'Chama Payment - Monthly', '2025-03-10 09:00:00', '2025-03-10 09:01:00'),
(17, 9, '254796258348', 1.00, 'emergency', '92', 'ws_CO_202504151796258348', NULL, 'pending', 'Chama Payment - Emergency', '2025-04-15 10:30:00', '2025-04-15 10:30:00'),
(18, 9, '254796258348', 2502.00, 'investment', '9', 'ws_CO_202505201796258348', 'TH34DE5FGH', 'completed', 'Chama Payment - Investment', '2025-05-20 12:00:00', '2025-05-20 12:01:00'),
(19, 10, '254720903569', 100.00, 'monthly', '0', 'ws_CO_202501081720903569', 'TH67IJ8KLM', 'completed', 'Chama Payment - Monthly', '2025-01-08 09:00:00', '2025-01-08 09:01:00'),
(20, 10, '254720903569', 100.00, 'monthly', '9', 'ws_CO_202502181720903569', 'TH90NO1PQR', 'completed', 'Chama Payment - Monthly', '2025-02-18 10:45:00', '2025-02-18 10:46:00'),
(21, 10, '254720903569', 100.00, 'monthly', '0', 'ws_CO_202503151720903569', 'TH23ST4UVX', 'completed', 'Chama Payment - Monthly', '2025-03-15 11:15:00', '2025-03-15 11:16:00'),
(22, 10, '254720903569', 1.00, 'emergency', '92', 'ws_CO_202504011720903569', NULL, 'failed', 'Chama Payment - Emergency', '2025-04-01 12:00:00', '2025-04-01 12:00:00'),
(23, 10, '254720903569', 100.00, 'monthly', '9', 'ws_CO_202505051720903569', 'TH56WX7YZA', 'completed', 'Chama Payment - Monthly', '2025-05-05 12:15:00', '2025-05-05 12:16:00'),
(24, 10, '254720903569', 2600.00, 'investment', '0', 'ws_CO_202506151720903569', 'TH89BC0DEF', 'completed', 'Chama Payment - Investment', '2025-06-15 10:30:00', '2025-06-15 10:31:00'),
(25, 10, '254720903569', 1.00, 'emergency', '92', 'ws_CO_202507201720903569', 'TH12LM6NOP', 'completed', 'Chama Payment - Emergency', '2025-07-20 09:45:00', '2025-07-20 09:46:00'),
(26, 11, '254713490096', 100.00, 'monthly', '0', 'ws_CO_202501121713490096', 'TH45QR9STU', 'completed', 'Chama Payment - Monthly', '2025-01-12 08:15:00', '2025-01-12 08:16:00'),
(27, 11, '254713490096', 100.00, 'monthly', '9', 'ws_CO_202502101713490096', 'TH78UV2WXY', 'completed', 'Chama Payment - Monthly', '2025-02-10 12:30:00', '2025-02-10 12:31:00'),
(28, 11, '254713490096', 100.00, 'monthly', '0', 'ws_CO_202503051713490096', 'TH01ZA5BCD', 'completed', 'Chama Payment - Monthly', '2025-03-05 09:45:00', '2025-03-05 09:46:00'),
(29, 11, '254713490096', 1.00, 'emergency', '92', 'ws_CO_202504101713490096', NULL, 'pending', 'Chama Payment - Emergency', '2025-04-10 09:00:00', '2025-04-10 09:00:00'),
(30, 11, '254713490096', 100.00, 'monthly', '9', 'ws_CO_202505151713490096', 'TH34EF8GHI', 'completed', 'Chama Payment - Monthly', '2025-05-15 10:30:00', '2025-05-15 10:31:00'),
(31, 11, '254713490096', 2100.00, 'investment', '0', 'ws_CO_202506201713490096', 'TH67IJ1KLM', 'completed', 'Chama Payment - Investment', '2025-06-20 11:00:00', '2025-06-20 11:01:00'),
(32, 11, '254713490096', 1.00, 'emergency', '92', 'ws_CO_202507251713490096', 'TH90NO4PQR', 'completed', 'Chama Payment - Emergency', '2025-07-25 10:15:00', '2025-07-25 10:16:00'),
(33, 15, '254703073997', 100.00, 'monthly', '0', 'ws_CO_202501101703073997', 'TH23ST7UVX', 'completed', 'Chama Payment - Monthly', '2025-01-10 08:00:00', '2025-01-10 08:01:00'),
(34, 15, '254703073997', 100.00, 'monthly', '9', 'ws_CO_202502051703073997', 'TH56WX0YZA', 'completed', 'Chama Payment - Monthly', '2025-02-05 10:15:00', '2025-02-05 10:16:00'),
(35, 15, '254703073997', 100.00, 'monthly', '0', 'ws_CO_202503151703073997', 'TH89BC3DEF', 'completed', 'Chama Payment - Monthly', '2025-03-15 11:00:00', '2025-03-15 11:01:00'),
(36, 15, '254703073997', 1.00, 'emergency', '92', 'ws_CO_202504201703073997', NULL, 'failed', 'Chama Payment - Emergency', '2025-04-20 09:30:00', '2025-04-20 09:30:00'),
(37, 15, '254703073997', 100.00, 'monthly', '9', 'ws_CO_202505101703073997', 'TH12GH6IJK', 'completed', 'Chama Payment - Monthly', '2025-05-10 12:00:00', '2025-05-10 12:01:00'),
(38, 15, '254703073997', 2000.00, 'investment', '0', 'ws_CO_202506151703073997', 'TH45LM9NOP', 'completed', 'Chama Payment - Investment', '2025-06-15 10:45:00', '2025-06-15 10:46:00'),
(39, 15, '254703073997', 1.00, 'emergency', '92', 'ws_CO_202507201703073997', 'TH78QR2STU', 'completed', 'Chama Payment - Emergency', '2025-07-20 09:15:00', '2025-07-20 09:16:00'),
(40, 18, '254765510916', 100.00, 'monthly', '0', 'ws_CO_202501051765510916', 'TH01UV5WXY', 'completed', 'Chama Payment - Monthly', '2025-01-05 08:30:00', '2025-01-05 08:31:00'),
(41, 18, '254765510916', 100.00, 'monthly', '9', 'ws_CO_202502121765510916', 'TH34ZA8BCD', 'completed', 'Chama Payment - Monthly', '2025-02-12 10:00:00', '2025-02-12 10:01:00'),
(42, 18, '254765510916', 100.00, 'monthly', '0', 'ws_CO_202503101765510916', 'TH67EF1GHI', 'completed', 'Chama Payment - Monthly', '2025-03-10 09:00:00', '2025-03-10 09:01:00'),
(43, 18, '254765510916', 1.00, 'emergency', '92', 'ws_CO_202504151765510916', NULL, 'pending', 'Chama Payment - Emergency', '2025-04-15 10:30:00', '2025-04-15 10:30:00'),
(44, 18, '254765510916', 100.00, 'monthly', '9', 'ws_CO_202505201765510916', 'TH90IJ4KLM', 'completed', 'Chama Payment - Monthly', '2025-05-20 11:45:00', '2025-05-20 11:46:00'),
(45, 18, '254765510916', 2600.00, 'investment', '0', 'ws_CO_202506101765510916', 'TH23NO7PQR', 'completed', 'Chama Payment - Investment', '2025-06-10 10:00:00', '2025-06-10 10:01:00'),
(46, 18, '254765510916', 1.00, 'emergency', '92', 'ws_CO_202507151765510916', 'TH56ST0UVX', 'completed', 'Chama Payment - Emergency', '2025-07-15 09:30:00', '2025-07-15 09:31:00'),
(47, 19, '254706827562', 100.00, 'monthly', '0', 'ws_CO_202501081706827562', 'TH89WX3YZA', 'completed', 'Chama Payment - Monthly', '2025-01-08 09:15:00', '2025-01-08 09:16:00'),
(48, 19, '254706827562', 100.00, 'monthly', '9', 'ws_CO_202502151706827562', 'TH12BC6DEF', 'completed', 'Chama Payment - Monthly', '2025-02-15 11:30:00', '2025-02-15 11:31:00'),
(49, 19, '254706827562', 100.00, 'monthly', '0', 'ws_CO_202503201706827562', 'TH45GH9IJK', 'completed', 'Chama Payment - Monthly', '2025-03-20 10:30:00', '2025-03-20 10:31:00'),
(50, 19, '254706827562', 1.00, 'emergency', '92', 'ws_CO_202504051706827562', NULL, 'failed', 'Chama Payment - Emergency', '2025-04-05 12:15:00', '2025-04-05 12:15:00'),
(51, 19, '254706827562', 100.00, 'monthly', '9', 'ws_CO_202505101706827562', 'TH78LM2NOP', 'completed', 'Chama Payment - Monthly', '2025-05-10 11:00:00', '2025-05-10 11:01:00'),
(52, 19, '254706827562', 2300.00, 'investment', '0', 'ws_CO_202506151706827562', 'TH01QR5STU', 'completed', 'Chama Payment - Investment', '2025-06-15 10:45:00', '2025-06-15 10:46:00'),
(53, 19, '254706827562', 1.00, 'emergency', '92', 'ws_CO_202507201706827562', 'TH34UV8WXY', 'completed', 'Chama Payment - Emergency', '2025-07-20 09:00:00', '2025-07-20 09:01:00'),
(54, 28, '254781485954', 100.00, 'monthly', '0', 'ws_CO_202501051781485954', 'TH67ZA1BCD', 'completed', 'Chama Payment - Monthly', '2025-01-05 08:45:00', '2025-01-05 08:46:00'),
(55, 28, '254781485954', 100.00, 'monthly', '9', 'ws_CO_202502121781485954', 'TH90EF4GHI', 'completed', 'Chama Payment - Monthly', '2025-02-12 10:30:00', '2025-02-12 10:31:00'),
(56, 28, '254781485954', 100.00, 'monthly', '0', 'ws_CO_202503101781485954', 'TH23IJ7KLM', 'completed', 'Chama Payment - Monthly', '2025-03-10 09:15:00', '2025-03-10 09:16:00'),
(57, 28, '254781485954', 1.00, 'emergency', '92', 'ws_CO_202504151781485954', NULL, 'pending', 'Chama Payment - Emergency', '2025-04-15 10:00:00', '2025-04-15 10:00:00'),
(58, 28, '254781485954', 100.00, 'monthly', '9', 'ws_CO_202505201781485954', 'TH56NO0PQR', 'completed', 'Chama Payment - Monthly', '2025-05-20 11:30:00', '2025-05-20 11:31:00'),
(59, 28, '254781485954', 2500.00, 'investment', '0', 'ws_CO_202506101781485954', 'TH89ST3UVX', 'completed', 'Chama Payment - Investment', '2025-06-10 10:15:00', '2025-06-10 10:16:00'),
(60, 28, '254781485954', 1.00, 'emergency', '92', 'ws_CO_202507151781485954', 'TH12WX6YZA', 'completed', 'Chama Payment - Emergency', '2025-07-15 09:30:00', '2025-07-15 09:31:00'),
(61, 31, '254723483891', 100.00, 'monthly', '0', 'ws_CO_202501121723483891', 'TH45BC9DEF', 'completed', 'Chama Payment - Monthly', '2025-01-12 09:00:00', '2025-01-12 09:01:00'),
(62, 31, '254723483891', 100.00, 'monthly', '9', 'ws_CO_202502151723483891', 'TH78GH2IJK', 'completed', 'Chama Payment - Monthly', '2025-02-15 11:15:00', '2025-02-15 11:16:00'),
(63, 31, '254723483891', 100.00, 'monthly', '0', 'ws_CO_202503201723483891', 'TH01LM5NOP', 'completed', 'Chama Payment - Monthly', '2025-03-20 10:00:00', '2025-03-20 10:01:00'),
(64, 31, '254723483891', 1.00, 'emergency', '92', 'ws_CO_202504051723483891', NULL, 'failed', 'Chama Payment - Emergency', '2025-04-05 12:00:00', '2025-04-05 12:00:00'),
(65, 31, '254723483891', 100.00, 'monthly', '9', 'ws_CO_202505101723483891', 'TH34QR8STU', 'completed', 'Chama Payment - Monthly', '2025-05-10 11:45:00', '2025-05-10 11:46:00'),
(66, 31, '254723483891', 2300.00, 'investment', '0', 'ws_CO_202506151723483891', 'TH67UV1WXY', 'completed', 'Chama Payment - Investment', '2025-06-15 10:30:00', '2025-06-15 10:31:00'),
(67, 31, '254723483891', 1.00, 'emergency', '92', 'ws_CO_202507201723483891', 'TH90ZA4BCD', 'completed', 'Chama Payment - Emergency', '2025-07-20 09:00:00', '2025-07-20 09:01:00'),
(90, 3, '254723456789', 100.00, 'monthly', '0', 'ws_CO_20250110123456789', 'TH23UV4WXY', 'completed', 'Chama Payment - Monthly', '2025-01-10 12:00:00', '2025-01-10 12:01:00'),
(91, 9, '254796258348', 1.00, 'emergency', '0', 'ws_CO_270820251347217796258348', 'THR56AC9NZ', 'completed', 'Chama Payment - Emergency', '2025-08-27 13:48:09', '2025-08-27 13:48:29'),
(92, 9, '254796258348', 1.00, 'monthly', '6', 'ws_CO_280820252352208796258348', 'THS5EJKKWZ', 'completed', 'Chama Payment - Monthly', '2025-08-28 23:53:09', '2025-08-28 23:53:22'),
(93, 9, '254796258348', 1.00, 'investment', '6', 'ws_CO_290820250107113796258348', 'THT9ELOKI7', 'completed', 'Chama Payment - Investment', '2025-08-29 01:08:00', '2025-08-29 01:08:10'),
(94, 9, '254796258348', 1.00, 'emergency', '0', 'ws_CO_290820251120062796258348', 'THT8FULFQ6', 'completed', 'Chama Payment - Emergency', '2025-08-29 11:20:56', '2025-08-29 11:21:06'),
(95, 9, '254796258348', 1.00, 'emergency', '3440', 'ws_CO_290820251334522796258348', NULL, 'pending', 'Chama Payment - Emergency', '2025-08-29 13:35:41', NULL),
(96, 9, '254796258348', 1.00, 'emergency', '1', 'ws_CO_290820251335563796258348', 'THT7GHBAP3', 'completed', 'Chama Payment - Emergency', '2025-08-29 13:36:45', '2025-08-29 13:36:53'),
(97, 9, '254796258348', 1.00, 'emergency', '1', 'ws_CO_290820251409182796258348', 'THT1GN563N', 'completed', 'Chama Payment - Emergency', '2025-08-29 14:10:07', '2025-08-29 14:10:16'),
(98, 9, '254796258348', 10.00, 'emergency', '1', 'ws_CO_290820251526388796258348', 'THT9H09UO9', 'completed', 'Chama Payment - Emergency', '2025-08-29 15:27:28', '2025-08-29 15:27:43');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(15) NOT NULL,
  `national_id` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` enum('male','female','other') NOT NULL,
  `role` enum('member','admin','superadmin','chairperson','secretary') NOT NULL DEFAULT 'member',
  `verification_token` varchar(255) DEFAULT NULL,
  `email_verified` tinyint(1) DEFAULT 0,
  `approval_status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `approved_by_chairperson` tinyint(1) DEFAULT 0,
  `approved_by_superadmin` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `first_name`, `last_name`, `username`, `email`, `password`, `phone_number`, `national_id`, `address`, `date_of_birth`, `gender`, `role`, `verification_token`, `email_verified`, `approval_status`, `created_at`, `approved_by_chairperson`, `approved_by_superadmin`) VALUES
(2, 'Mary', 'Njeri', 'mary_njeri', 'marynjeri@gmail.com', '$2y$10$X1y2z3a4b5c6d7e8f9g0hA.1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R', '+254712345678', '12345678', 'Westlands, Nairobi', '1995-06-15', 'female', 'member', NULL, 1, 'approved', '2025-04-22 09:15:55', 1, 1),
(3, 'Peter', 'Kamau', 'peter_kamau', 'peterkamau@gmail.com', '$2y$10$Y2z3a4b5c6d7e8f9g0h1iA.2B3C4D5E6F7G8H9I0J1K2L3M4N5O6P7Q', '+254723456789', '87654321', 'Embakasi, Nairobi', '1990-09-20', 'male', 'member', NULL, 1, 'approved', '2025-04-17 16:37:12', 1, 1),
(9, 'Samuel', 'Njuguna', 'sammy', 'waithakas2003@gmail.com', '$2y$10$7OiCEZisAouNbmrLqYtKJuI1pQ6c0i/te0gehVDWJsTtlgRflnyNO', '254796258348', '40687555', 'Kasarani, Nairobi', '2003-10-22', 'male', 'member', 'a07d907492c3d9a810014032f8f95ccbc1a5199157b3d88ddd0692ae55db233b', 1, 'approved', '2025-04-26 10:18:19', 1, 1),
(10, 'Douglas', 'Waithaka', 'onlywaithaka', '1046031@cuea.edu', '$2y$10$vudg.VdjmAXLm.cBks0QQeCt.P2YZhdvpTH76OX9/XciijvI4/aDS', '254720903569', '5917933', 'Karen Niarobi', '1998-03-11', 'male', 'superadmin', '98d1b085133504b9d64b07c4655f1a1e33ce993b4a2874fdc839e48f94e183cd', 1, 'approved', '2025-04-23 04:39:57', 1, 1),
(11, 'Rick', 'Owens', 'Rickowens', 'sammyusa060@gmail.com', '$2y$10$bUc6G3.qLcvzSpYT3/GJxeHjeAfD44Ccjpz2lL8o38HK/pRtWd1yG', '254713490096', '9325990', 'Buruburu, Kenya', '1990-02-10', 'male', 'chairperson', 'c99a96a88ef5dd56471a90d257fd293ed998813933c0dba424e7d28ffe684db3', 1, 'approved', '2025-05-11 19:24:51', 1, 1),
(15, 'Alice', 'Mutua', 'alice_mutua', 'alicemutua@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254703073997', '43087926', 'Kakamega', '1985-12-17', 'female', 'member', '6ee018d588fa53d21eddbfc38ac9f4ecdfed10eac8abb614058eceb5e8ffebbe', 1, 'approved', '2025-04-22 14:04:22', 1, 1),
(16, 'Brian', 'Ochieng', 'brian_ochieng', 'brianochieng@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254717993824', '17869598', 'Westlands, Nairobi', '2001-04-18', 'male', 'member', '1efdcb8ecd6fbb3fdbf7737dc4d6bbebbfdec9eae21dd0f2dcd1ec5e1a90a326', 1, 'pending', '2025-05-23 15:07:20', 1, 0),
(17, 'Catherine', 'Wanjiku', 'catherine_wanjiku', 'catherinewanjiku@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254776391211', '56651967', 'Nyeri', '2002-06-11', 'female', 'member', 'bba268e113fc90a88bd6fbf8ef9ae1a658bbfe7ddac2bd31d0cc8aec1dbfa594', 0, 'pending', '2025-04-22 12:23:34', 1, 0),
(18, 'David', 'Kiprop', 'david_kiprop', 'davidkiprop@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254765510916', '27442790', 'Nakuru Town', '1997-03-03', 'male', 'member', 'fcb9ee8a98bacf64099bf3a3524df9b4ce5e39409cbceaa8aca153ca788c83eb', 1, 'approved', '2025-04-17 19:35:48', 1, 1),
(19, 'Esther', 'Achieng', 'esther_achieng', 'estherachieng@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254706827562', '42997976', 'Karen, Nairobi', '1982-06-14', 'female', 'member', '6eebfbdddffb5a34b9fba83eec049a5dfee1e30d728cc9edec958df749bd2f75', 1, 'approved', '2025-04-26 15:48:22', 1, 1),
(20, 'Francis', 'Mwangi', 'francis_mwangi', 'francismwangi@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254722706817', '19193368', 'Kakamega', '1997-01-08', 'male', 'member', 'e3f6e69bf5d0184dac68e0c343dfdfc3f60564782b3f15dc46edafc9856cefed', 1, 'rejected', '2025-04-23 23:14:27', 0, 0),
(21, 'Grace', 'Kariuki', 'grace_kariuki', 'gracekariuki@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254705103702', '01174366', 'Kasarani, Nairobi', '1987-09-26', 'female', 'member', '8fbb4fab6fc1e9719b68d66e0fe44f6e9d9e8aa86cadc3febbaaae5e95c5c7ac', 0, 'pending', '2025-05-14 23:47:08', 0, 1),
(22, 'Henry', 'Otieno', 'henry_otieno', 'henryotieno@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254741863941', '75143118', 'Embakasi, Nairobi', '2002-05-24', 'male', 'member', '1bfb93d1e1dbabbab1160cccdbb75ad0dfac8d3635dc73bc1d17bc144b0a85dc', 1, 'pending', '2025-05-06 04:12:18', 1, 0),
(23, 'Irene', 'Njoki', 'irene_njoki', 'irenenjoki@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254708671915', '69963325', 'Meru', '1994-04-24', 'female', 'member', '816bd593fccea8d9ab6ca33ba55afbe2cde19f2ae5e7c00f78dd100e628c85de', 0, 'pending', '2025-05-21 00:40:09', 1, 0),
(24, 'James', 'Kimani', 'james_kimani', 'jameskimani@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254757106090', '58538606', 'Thika', '2003-05-19', 'male', 'member', 'afbdf8afd9a1e95b7a652e0f6ff487dbe1a8c64a50bafdebc1ff4cfa4f9c4147', 0, 'pending', '2025-04-29 17:43:49', 0, 1),
(25, 'Kelly', 'Atieno', 'kelly_atieno', 'kellyatieno@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254780232005', '00058002', 'Eldoret', '2002-01-24', 'female', 'member', 'deefd048579b3a1d5bb9fae15d1ebd83b2128ad3dbc8a0c8a6dbb6fa8a0d5ced', 0, 'rejected', '2025-03-31 17:38:41', 0, 0),
(26, 'Leonard', 'Wambua', 'leonard_wambua', 'leonardwambua@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254747207901', '87569216', 'Ruaraka, Nairobi', '1999-02-17', 'male', 'member', 'e3f6a928bd73b7ff9c0cd9caeeb7eeab47e3dbdb5d63f9b46ca29429cdfff8ce', 0, 'pending', '2025-05-11 14:01:59', 0, 1),
(27, 'Monica', 'Chebet', 'monica_chebet', 'monicachebet@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254764888716', '28434283', 'Kisumu City', '1983-08-23', 'female', 'member', '7a4cdd1e98f8a19eb685cb90ee003dca3a9b3f76d9fb72e9e84df1f5243f6e6e', 1, 'pending', '2025-04-27 20:13:53', 0, 0),
(28, 'Nicholas', 'Maina', 'nicholas_maina', 'nicholasmaina@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254781485954', '12051608', 'Kisii', '2004-08-30', 'male', 'member', 'fafaa6afedadfaf99003ba8c2cba84ef73fafc0cb0abaa38670b01b8f2caabac', 1, 'approved', '2025-04-19 14:03:29', 1, 1),
(29, 'Olivia', 'Njoroge', 'olivia_njoroge', 'olivianjoroge@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254706314716', '28313184', 'Kakamega', '1988-12-15', 'female', 'member', 'a3b29ad3842bf7c050ca0f65011dd9bf7a8abbf4b842e8fcd0ae6906deddafdb', 1, 'pending', '2025-04-19 12:35:46', 1, 0),
(30, 'Patrick', 'Kibet', 'patrick_kibet', 'patrickkibet@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254761824121', '58127281', 'Westlands, Nairobi', '1980-08-10', 'male', 'member', '86c7f5686e71aaaba33bbac95e94cef6be397b02c4ea9ce5d69bcfb1c0fbd82f', 0, 'pending', '2025-05-13 23:48:23', 0, 1),
(31, 'Quinn', 'Adhiambo', 'quinn_adhiambo', 'quinnadhiambo@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254723483891', '40975601', 'Nyeri', '1985-06-30', 'female', 'member', 'b414cfdeaf1eea88e7fb8b9cebc5ff7e149be3172d4eeabdd1f6cfa2cdc5d6db', 1, 'approved', '2025-05-14 12:14:39', 1, 1),
(32, 'Robert', 'Githinji', 'robert_githinji', 'robertgithinji@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254778868684', '41552247', 'Embakasi, Nairobi', '2002-09-08', 'male', 'member', '6681659f97f22daa0ccdb152b226bad3dc4bdd803c99b039748b8bd1c15d3e4d', 1, 'pending', '2025-05-04 16:48:05', 0, 0),
(33, 'Sarah', 'Muthoni', 'sarah_muthoni', 'sarahmuthoni@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254101998096', '64536350', 'Buruburu, Nairobi', '1999-08-18', 'female', 'member', 'f9ca32c53ad38155d4d438fd63194fea1c8f0e5cec3ddf3682ec5f0bdc81bbd8', 1, 'pending', '2025-05-15 02:16:27', 0, 1),
(34, 'Thomas', 'Kariuki', 'thomas_kariuki', 'thomaskariuki@gmail.com', '$2y$10$h8A48EI3MdWBpG050W.qFe4GB4vd/S2cqpOq0YJvrqgzwCsQT9mnq', '+254792353709', '79733084', 'Nyeri', '1990-11-14', 'male', 'member', 'f5e7addba60a722671d41a7eabbf7fded363eb48ddbd8dd160a4b99bdeac01af', 1, 'pending', '2025-04-04 11:58:04', 0, 0),
(35, 'Boniface', 'Warui', 'Waruimain', 'warui12@gmail.com', '$2y$10$a6Sk2OXxoJz42mjXuuPysOu3tNm7Gf12AoKyXsJdr6HShY3VKxUfa', '254789679045', '1367892', 'Kasarani, Nairobi', '2000-06-14', 'male', 'member', 'eb73a00083a2ed4ac4eaab395d156eaed6ac245e6ef76b83442e9b7fefc9748f', 0, 'approved', '2025-08-29 12:21:20', 1, 1);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `apologies`
--
ALTER TABLE `apologies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `meeting_id` (`meeting_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`meeting_id`),
  ADD KEY `meeting_id` (`meeting_id`);

--
-- Indexes for table `fines`
--
ALTER TABLE `fines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_fine_user_id` (`user_id`);

--
-- Indexes for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `approved_by` (`approved_by`);

--
-- Indexes for table `meetings`
--
ALTER TABLE `meetings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`meeting_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `removal_requests`
--
ALTER TABLE `removal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `requested_by` (`requested_by`);

--
-- Indexes for table `role_changes`
--
ALTER TABLE `role_changes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone_number` (`phone_number`),
  ADD UNIQUE KEY `national_id` (`national_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `apologies`
--
ALTER TABLE `apologies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT for table `fines`
--
ALTER TABLE `fines`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `loan_applications`
--
ALTER TABLE `loan_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `meetings`
--
ALTER TABLE `meetings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `removal_requests`
--
ALTER TABLE `removal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `role_changes`
--
ALTER TABLE `role_changes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `apologies`
--
ALTER TABLE `apologies`
  ADD CONSTRAINT `apologies_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `apologies_ibfk_2` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`),
  ADD CONSTRAINT `apologies_ibfk_3` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`);

--
-- Constraints for table `fines`
--
ALTER TABLE `fines`
  ADD CONSTRAINT `fk_fine_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `loan_applications`
--
ALTER TABLE `loan_applications`
  ADD CONSTRAINT `loan_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `loan_applications_ibfk_2` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `meeting_attendance`
--
ALTER TABLE `meeting_attendance`
  ADD CONSTRAINT `meeting_attendance_ibfk_1` FOREIGN KEY (`meeting_id`) REFERENCES `meetings` (`id`),
  ADD CONSTRAINT `meeting_attendance_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`) ON DELETE CASCADE;

--
-- Constraints for table `removal_requests`
--
ALTER TABLE `removal_requests`
  ADD CONSTRAINT `removal_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `removal_requests_ibfk_2` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `role_changes`
--
ALTER TABLE `role_changes`
  ADD CONSTRAINT `role_changes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `role_changes_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `transactions`
--
ALTER TABLE `transactions`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
