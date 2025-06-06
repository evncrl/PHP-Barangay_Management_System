-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 01:13 PM
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
-- Database: `saad`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `appointment_date` datetime NOT NULL,
  `status` enum('Pending','Approved','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `resident_id`, `user_id`, `purpose`, `description`, `appointment_date`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(2, 7, 15, 'MEDICAL CHECK-UP', NULL, '2025-02-26 13:00:00', 'Completed', 'for working requirements', '2025-02-25 04:24:05', '2025-02-25 04:35:49'),
(5, 2, 1, 'MEDICAL CHECK-UP', NULL, '2025-02-26 10:00:00', 'Approved', 'not Available', '2025-02-25 05:13:24', '2025-03-08 16:14:33'),
(6, 7, 15, 'PEDIA', NULL, '2025-03-14 10:00:00', 'Approved', '', '2025-03-01 12:55:42', '2025-03-01 13:01:24'),
(7, 7, 15, 'Office of Barangay Captain', NULL, '2025-03-18 14:00:00', 'Pending', NULL, '2025-03-18 03:46:30', '2025-03-18 03:46:30'),
(9, 2, 1, 'Office of Barangay Captain', 'Meeting', '2025-03-29 08:01:00', 'Completed', '', '2025-03-27 18:53:03', '2025-03-28 02:37:45'),
(10, 7, 15, 'Office of Barangay Captain', 'jsadhiwe', '2025-03-29 14:20:00', 'Pending', 'thahaha miss you', '2025-03-28 02:15:38', '2025-04-01 13:04:34'),
(11, 31, 35, 'Office of Barangay Captain', 'dwqasda', '2025-04-05 21:36:00', 'Approved', 'thahaha miss you', '2025-04-02 13:36:35', '2025-04-07 02:57:47');

-- --------------------------------------------------------

--
-- Table structure for table `appointment_complaints`
--

CREATE TABLE `appointment_complaints` (
  `appointment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `complaint_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `barangay_notes`
--

CREATE TABLE `barangay_notes` (
  `id` int(11) NOT NULL,
  `notes` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `barangay_notes`
--

INSERT INTO `barangay_notes` (`id`, `notes`, `updated_at`) VALUES
(1, 'ANNOUNCEMENT: GOOD MORNING LOWER BICUTAN. ', '2025-04-07 02:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `business_list`
--

CREATE TABLE `business_list` (
  `business_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `business_owner` varchar(255) NOT NULL,
  `business_address` text NOT NULL,
  `document_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `business_list`
--

INSERT INTO `business_list` (`business_id`, `user_id`, `resident_id`, `business_name`, `business_owner`, `business_address`, `document_id`) VALUES
(1, 17, 9, 'Computer Shop', 'Juliane', '11B Katuray Lower Bicutan Taguig City', 90),
(2, 17, 9, 'Computer Shop', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(3, 17, 9, 'Computer Shop', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(4, 17, 9, 'Computer Shop nanaman', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(5, 17, 9, 'Computer Shop nanaman', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(6, 17, 9, 'IT Alley', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(7, 17, 9, 'IT Alley 2', 'Juli Layco Pasteteo', '11B Katuray Lower Bicutan Taguig City', NULL),
(8, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(9, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(10, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(11, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(13, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', 85),
(14, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(15, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(16, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(17, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', NULL),
(18, 17, 9, 'pERFECT sUNDAE', 'Juli Layco Pasteteo', 'Pinagsama', 91),
(19, 17, 9, 'Sari-Sari store', 'Juli Layco Pasteteo', 'Sm Aura', 94),
(20, 1, 2, 'Wala', 'Jerome Sadio Rosario', 'pinagsama', 95);

-- --------------------------------------------------------

--
-- Table structure for table `complaints`
--

CREATE TABLE `complaints` (
  `complaint_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `complaint_type` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `status` enum('pending','in_progress','resolved') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `accussed_person` varchar(255) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `complaints`
--

INSERT INTO `complaints` (`complaint_id`, `user_id`, `complaint_type`, `description`, `status`, `created_at`, `accussed_person`, `image`) VALUES
(31, 15, 'maintenance', 'm,djwkjdqwwd', 'pending', '2025-03-05 14:28:43', 'Dion', NULL),
(34, 17, 'EWAN', 'JHDKJQK', 'pending', '2025-03-06 12:05:18', 'Gerald', NULL),
(35, 20, 'EWAN', 'HASHDGAD', 'resolved', '2025-03-06 12:06:24', 'Gerald', NULL),
(43, 25, 'wsaz', 'cwe/;slf', 'resolved', '2025-03-22 20:41:20', 'Jerome Rosario', '../user/uploads/486020150_1825960304829685_6907477548716145075_n.jpg'),
(44, 25, 'xqwsacwsa', 'csacsa', 'pending', '2025-03-22 21:21:42', 'Jerome Rosario', '../user/uploads/481692948_535878775651687_4126834704351186558_n (1).jpg'),
(45, 25, 'xqwsacwsa', 'csacsa', 'pending', '2025-03-22 21:24:21', 'Jerome Rosario', '../user/uploads/481692948_535878775651687_4126834704351186558_n (1).jpg'),
(46, 15, 'nagwawala', 'shjdgfbjsehfjs', 'resolved', '2025-03-28 02:26:14', 'Eros Tolin', '../user/uploads/486020150_1825960304829685_6907477548716145075_n.jpg'),
(47, 35, 'Miss ko lang', 'ewan ko ba miss ko na siya', 'resolved', '2025-04-02 15:06:35', 'Meriel Lanuza', '../user/uploads/Picapica-photostrip (1).jpg'),
(48, 35, 'noise', 'uihijk', 'pending', '2025-04-07 02:56:36', 'Jerome Rosario', '../user/uploads/487075004_1458136141824397_9187648363581637519_n.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `documents`
--

CREATE TABLE `documents` (
  `document_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `document_type` enum('Certificate of Indigency','Certificate of Residency','Barangay Clearance','Barangay Business Clearance','Clearance for First-time Job Seeker') NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT NULL,
  `request_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `issue_date` timestamp NULL DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `receipt_file` varchar(255) DEFAULT NULL,
  `pdf_file` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents`
--

INSERT INTO `documents` (`document_id`, `user_id`, `document_type`, `purpose`, `status`, `request_date`, `issue_date`, `file_path`, `receipt_file`, `pdf_file`) VALUES
(1, 15, '', 'Scholarship Requirement', 'rejected', '2025-02-16 08:40:33', NULL, NULL, NULL, NULL),
(2, 15, '', 'Scholarship Requirement', 'rejected', '2025-02-16 08:46:05', NULL, NULL, NULL, NULL),
(3, 15, 'Certificate of Residency', 'Scholarship Requirement', 'approved', '2025-02-16 08:48:56', '2025-02-16 09:12:40', NULL, NULL, NULL),
(7, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 11:22:43', NULL, NULL, NULL, NULL),
(8, 15, 'Barangay Business Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 11:28:03', '2025-02-15 16:00:00', '../documents/business_clearance_8.pdf', NULL, NULL),
(9, 15, 'Barangay Business Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 11:36:19', '2025-02-15 16:00:00', '../documents/business_clearance_9.pdf', NULL, NULL),
(10, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 13:22:15', '2025-02-15 16:00:00', '../documents/barangay_clearance_10.pdf', NULL, NULL),
(11, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 13:30:42', '2025-02-15 16:00:00', '../documents/barangay_clearance_11.pdf', NULL, NULL),
(12, 15, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-16 13:32:16', '2025-02-15 16:00:00', '../documents/barangay_clearance_12.pdf', NULL, NULL),
(13, 15, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-16 13:55:43', '2025-02-15 16:00:00', '../documents/barangay_clearance_13.pdf', NULL, NULL),
(14, 17, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 14:04:15', '2025-02-15 16:00:00', '../documents/barangay_clearance_14.pdf', NULL, NULL),
(15, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-16 14:06:20', NULL, '../documents/business_clearance_15.pdf', NULL, NULL),
(16, 17, 'Barangay Clearance', 'pambenta ng shabu', 'approved', '2025-02-16 14:47:17', '2025-02-15 16:00:00', '../documents/barangay_clearance_16.pdf', NULL, NULL),
(17, 17, 'Barangay Clearance', 'tanginang subject to', 'approved', '2025-02-16 14:48:54', '2025-02-15 16:00:00', '../documents/barangay_clearance_17.pdf', NULL, NULL),
(18, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 15:31:37', NULL, NULL, NULL, NULL),
(19, 17, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-16 15:48:57', '2025-02-15 16:00:00', '../documents/barangay_clearance_19.pdf', NULL, NULL),
(20, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 15:52:09', NULL, NULL, NULL, NULL),
(21, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 15:53:02', NULL, NULL, NULL, NULL),
(22, 17, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 15:56:54', '2025-02-15 16:00:00', '../documents/barangay_clearance_22.pdf', NULL, NULL),
(23, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 16:59:02', NULL, NULL, NULL, NULL),
(24, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 17:00:45', '2025-02-15 16:00:00', '../documents/barangay_clearance_24.pdf', NULL, NULL),
(25, 17, 'Clearance for First-time Job Seeker', 'Apply for Job malamang', 'approved', '2025-02-16 17:05:24', '2025-02-15 16:00:00', '../documents/barangay_clearance_25.pdf', NULL, NULL),
(26, 16, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-16 17:07:30', NULL, '../documents/barangay_clearance_26.pdf', NULL, NULL),
(27, 16, 'Clearance for First-time Job Seeker', 'TATAKBO NG TULAK', 'approved', '2025-02-16 17:07:46', '2025-02-15 16:00:00', '../documents/barangay_clearance_27.pdf', NULL, NULL),
(28, 16, 'Barangay Clearance', 'TATAKBO NG TULAK', 'approved', '2025-02-17 02:02:33', NULL, '../documents/barangay_clearance_28.pdf', NULL, NULL),
(29, 16, 'Clearance for First-time Job Seeker', 'Bebenta ng shabu', 'approved', '2025-02-17 02:02:47', '2025-02-16 16:00:00', '../documents/barangay_clearance_29.pdf', NULL, NULL),
(30, 17, 'Certificate of Residency', 'Bakit ba', 'approved', '2025-02-21 07:18:33', NULL, NULL, NULL, NULL),
(31, 17, 'Certificate of Residency', 'lepat ako deto', 'approved', '2025-02-21 07:20:37', '2025-02-20 16:00:00', '../documents/residency_certificate_31.pdf', NULL, NULL),
(32, 17, 'Certificate of Residency', 'lepat ako deto', 'approved', '2025-02-21 07:28:55', '2025-02-20 16:00:00', '../documents/residency_certificate_32.pdf', NULL, NULL),
(33, 17, 'Certificate of Indigency', 'AYOKO NA, PA ACCEPT PO', 'approved', '2025-02-21 07:40:38', '2025-02-20 16:00:00', '../documents/indigency_certificate_33.pdf', NULL, NULL),
(34, 17, 'Certificate of Residency', 'ACCEPT!!', 'approved', '2025-02-21 07:40:51', NULL, '../documents/residency_certificate_34.pdf', NULL, NULL),
(35, 17, 'Certificate of Residency', 'PA APPROVE', 'approved', '2025-02-21 07:46:06', NULL, '../documents/residency_certificate_35.pdf', NULL, NULL),
(36, 17, 'Certificate of Residency', 'THE ISSUED DATE ERROR', 'approved', '2025-02-21 07:52:50', NULL, '../documents/residency_certificate_36.pdf', NULL, NULL),
(37, 17, 'Certificate of Residency', 'THE ISSUED DATE ERROR', 'approved', '2025-02-21 07:54:27', '2025-02-20 16:00:00', '../documents/residency_certificate_37.pdf', NULL, NULL),
(38, 17, 'Barangay Clearance', 'CLEARANCE ISSUED DATE', 'approved', '2025-02-21 07:57:00', NULL, '../documents/barangay_clearance_38.pdf', NULL, NULL),
(39, 17, 'Certificate of Residency', 'RECIDENCY ISSUED DATE', 'approved', '2025-02-21 07:57:40', NULL, '../documents/residency_certificate_39.pdf', NULL, NULL),
(40, 17, 'Certificate of Indigency', 'INDIGENCY ISSUED DATE', 'approved', '2025-02-21 07:57:52', '2025-02-20 16:00:00', '../documents/indigency_certificate_40.pdf', NULL, NULL),
(41, 17, 'Clearance for First-time Job Seeker', 'JOB SEEKER ISSUED DATE', 'approved', '2025-02-21 07:58:06', NULL, '../documents/barangay_clearance_41.pdf', NULL, NULL),
(42, 17, 'Certificate of Residency', 'WALA NANAMAN DATE HAYOP', 'approved', '2025-02-21 07:59:03', NULL, '../documents/residency_certificate_42.pdf', NULL, NULL),
(43, 17, 'Barangay Clearance', 'SHOW ISSUED DATE?', 'approved', '2025-02-21 08:16:36', NULL, '../documents/barangay_clearance_43.pdf', NULL, NULL),
(44, 17, 'Certificate of Residency', 'SHOW ISSUED DATE?', 'approved', '2025-02-21 08:17:45', NULL, '../documents/residency_certificate_44.pdf', NULL, NULL),
(45, 17, 'Certificate of Indigency', 'SHOW ISSUED DATE?', 'approved', '2025-02-21 08:18:27', '2025-02-20 16:00:00', '../documents/indigency_certificate_45.pdf', NULL, NULL),
(46, 17, 'Clearance for First-time Job Seeker', 'SHOW ISSUED DATE?', 'approved', '2025-02-21 08:19:05', NULL, '../documents/barangay_clearance_46.pdf', NULL, NULL),
(47, 15, 'Barangay Clearance', 'school', 'approved', '2025-02-21 13:05:37', NULL, NULL, NULL, NULL),
(48, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 13:09:02', NULL, NULL, NULL, NULL),
(49, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 13:09:36', NULL, '../documents/barangay_clearance_49.pdf', NULL, NULL),
(50, 15, 'Certificate of Indigency', 'Wala lang', 'approved', '2025-02-21 13:15:21', '2025-02-20 16:00:00', '../documents/indigency_certificate_50.pdf', NULL, NULL),
(51, 15, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-21 13:30:25', NULL, '../documents/barangay_clearance_51.pdf', NULL, NULL),
(52, 15, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-21 13:33:46', NULL, '../documents/barangay_clearance_52.pdf', NULL, NULL),
(53, 15, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-21 13:36:42', '2025-02-21 13:49:07', '../documents/barangay_clearance_53.pdf', NULL, NULL),
(54, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 13:51:32', NULL, '../documents/barangay_clearance_54.pdf', NULL, NULL),
(55, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 13:54:38', NULL, '../documents/barangay_clearance_55.pdf', NULL, NULL),
(56, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 14:05:01', NULL, '../documents/barangay_clearance_56.pdf', NULL, NULL),
(57, 15, 'Certificate of Residency', 'Scholarship Requirement', 'approved', '2025-02-21 14:10:04', '2025-02-20 16:00:00', '../documents/residency_certificate_57.pdf', NULL, NULL),
(58, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 14:10:24', '2025-02-20 16:00:00', '../documents/barangay_clearance_58.pdf', NULL, NULL),
(59, 15, 'Certificate of Indigency', 'Scholarship Requirement', 'approved', '2025-02-21 14:10:47', '2025-02-20 16:00:00', '../documents/indigency_certificate_59.pdf', NULL, NULL),
(60, 15, 'Clearance for First-time Job Seeker', 'Wala lang', 'approved', '2025-02-21 14:11:04', '2025-02-21 07:15:36', '../documents/barangay_clearance_60.pdf', NULL, NULL),
(61, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 14:15:30', '2025-02-21 07:15:55', '../documents/barangay_clearance_61.pdf', NULL, NULL),
(62, 15, 'Certificate of Residency', 'Wala lang', 'approved', '2025-02-21 14:15:51', '2025-02-21 07:17:56', '../documents/residency_certificate_62.pdf', NULL, NULL),
(63, 15, 'Barangay Clearance', 'Scholarship Requirement', 'approved', '2025-02-21 14:17:52', '2025-02-21 07:54:42', '../documents/barangay_clearance_63.pdf', NULL, NULL),
(64, 17, 'Certificate of Residency', 'Wala lang', 'approved', '2025-02-21 14:54:37', '2025-02-21 08:04:11', '../documents/residency_certificate_64.pdf', NULL, NULL),
(65, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-21 15:04:06', '2025-02-20 16:00:00', '../documents/business_clearance_65.pdf', NULL, NULL),
(66, 17, 'Clearance for First-time Job Seeker', 'Wala lang', 'approved', '2025-02-21 15:06:07', '2025-02-21 08:06:15', '../documents/barangay_clearance_66.pdf', NULL, NULL),
(67, 17, 'Barangay Business Clearance', 'HEADER TESTING', 'approved', '2025-02-21 15:59:50', '2025-02-20 16:00:00', '../documents/business_clearance_67.pdf', NULL, NULL),
(68, 17, 'Barangay Business Clearance', 'HEADER TESTING', 'approved', '2025-02-21 16:00:35', '2025-02-20 16:00:00', '../documents/business_clearance_68.pdf', NULL, NULL),
(69, 17, 'Barangay Clearance', 'HEADER TESTING', 'approved', '2025-02-21 16:05:41', '2025-02-21 09:06:24', '../documents/barangay_clearance_69.pdf', NULL, NULL),
(70, 17, 'Certificate of Residency', 'HEADER TESTING', 'approved', '2025-02-21 16:05:49', '2025-02-21 09:06:23', '../documents/residency_certificate_70.pdf', NULL, NULL),
(71, 17, 'Certificate of Indigency', 'HEADER TESTING', 'approved', '2025-02-21 16:05:56', '2025-02-21 09:06:22', '../documents/indigency_certificate_71.pdf', NULL, NULL),
(72, 17, 'Clearance for First-time Job Seeker', 'HEADER TESTING', 'approved', '2025-02-21 16:06:01', '2025-02-21 09:06:21', '../documents/barangay_clearance_72.pdf', NULL, NULL),
(73, 17, 'Barangay Business Clearance', 'HEADER TESTING', 'approved', '2025-02-21 16:06:07', '2025-02-20 16:00:00', '../documents/business_clearance_73.pdf', NULL, NULL),
(74, 17, 'Barangay Business Clearance', 'Application for Business Permit', 'approved', '2025-02-21 16:59:21', '2025-02-20 16:00:00', '../documents/business_clearance_74.pdf', NULL, NULL),
(75, 17, 'Barangay Business Clearance', 'Application for Business Permit', 'approved', '2025-02-21 17:10:19', '2025-02-20 16:00:00', '../documents/business_clearance_75.pdf', NULL, NULL),
(76, 17, 'Barangay Business Clearance', 'Application for Business Permit', 'approved', '2025-02-21 17:15:38', '2025-02-20 16:00:00', '../documents/business_clearance_76.pdf', NULL, NULL),
(77, 17, 'Barangay Business Clearance', 'Application for Business Permit', 'approved', '2025-02-21 17:19:50', '2025-02-20 16:00:00', '../documents/business_clearance_77.pdf', NULL, NULL),
(78, 17, 'Barangay Business Clearance', 'Application for Business Permit ulit', 'approved', '2025-02-21 17:35:53', '2025-02-20 16:00:00', '../documents/business_clearance_78.pdf', NULL, NULL),
(79, 17, 'Barangay Business Clearance', 'Application for Business Permit ulit ulit', 'pending', '2025-02-21 17:41:58', NULL, NULL, NULL, NULL),
(80, 17, 'Barangay Business Clearance', 'Application for Business Permit ulit ulit', 'approved', '2025-02-21 17:44:09', '2025-02-21 16:00:00', '../documents/business_clearance_80.pdf', NULL, NULL),
(81, 17, 'Barangay Business Clearance', 'Wala lang', 'pending', '2025-02-22 00:34:51', NULL, NULL, NULL, NULL),
(82, 17, 'Barangay Business Clearance', 'Wala lang', 'rejected', '2025-02-22 00:38:33', NULL, NULL, NULL, NULL),
(83, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 00:54:16', '2025-02-21 16:00:00', '../documents/business_clearance_83.pdf', NULL, NULL),
(84, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:02:57', '2025-02-21 16:00:00', '../documents/business_clearance_84.pdf', NULL, NULL),
(85, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:05:20', '2025-02-21 16:00:00', '../documents/business_clearance_85.pdf', NULL, NULL),
(86, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:20:22', '2025-02-21 16:00:00', '../documents/business_clearance_86.pdf', NULL, NULL),
(87, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:25:13', '2025-02-21 16:00:00', '../documents/business_clearance_87.pdf', NULL, NULL),
(88, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:33:02', '2025-02-21 16:00:00', '../documents/business_clearance_88.pdf', NULL, NULL),
(89, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:34:43', '2025-02-21 16:00:00', '../documents/business_clearance_89.pdf', NULL, NULL),
(90, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:36:14', '2025-02-21 16:00:00', '../documents/business_clearance_90.pdf', NULL, NULL),
(91, 17, 'Barangay Business Clearance', 'Wala lang', 'approved', '2025-02-22 01:47:35', '2025-02-21 16:00:00', '../documents/business_clearance_91.pdf', NULL, NULL),
(92, 17, 'Barangay Clearance', 'Wala lang', 'approved', '2025-02-22 01:48:24', '2025-02-21 19:05:17', '../documents/barangay_clearance_92.pdf', NULL, NULL),
(93, 17, 'Barangay Clearance', 'HEADER TESTING', 'approved', '2025-02-22 02:05:11', '2025-02-21 19:06:16', '../documents/barangay_clearance_93.pdf', NULL, NULL),
(94, 17, 'Barangay Business Clearance', 'Business', 'approved', '2025-02-22 02:06:10', '2025-02-21 16:00:00', '../documents/business_clearance_94.pdf', NULL, NULL),
(95, 1, 'Barangay Business Clearance', 'Application for Business Permit ulit ulit', 'approved', '2025-02-26 01:31:18', '2025-02-25 16:00:00', '../documents/business_clearance_95.pdf', NULL, NULL),
(96, 1, 'Barangay Clearance', 'Application for Work', 'approved', '2025-02-26 05:39:29', '2025-02-25 22:39:46', '../documents/barangay_clearance_96.pdf', NULL, NULL),
(97, 15, 'Barangay Clearance', 'School Requirement', 'approved', '2025-02-28 03:26:44', '2025-02-27 20:27:06', '../documents/barangay_clearance_97.pdf', NULL, NULL),
(98, 18, 'Barangay Clearance', 'School Requirement', 'approved', '2025-02-28 19:14:56', '2025-02-28 12:15:28', '../documents/barangay_clearance_98.pdf', NULL, NULL),
(99, 15, 'Certificate of Indigency', 'school', 'rejected', '2025-03-01 11:45:16', NULL, NULL, NULL, NULL),
(100, 15, 'Barangay Clearance', 'School Requirement', 'approved', '2025-03-01 14:06:48', '2025-03-03 08:36:16', '../documents/barangay_clearance_100.pdf', NULL, NULL),
(101, 15, 'Clearance for First-time Job Seeker', 'JOb', 'approved', '2025-03-02 06:26:08', '2025-03-01 23:27:13', '../documents/barangay_clearance_101.pdf', NULL, NULL),
(102, 15, 'Clearance for First-time Job Seeker', 'JOb', 'approved', '2025-03-02 06:44:48', '2025-03-01 23:51:04', '../documents/barangay_clearance_102.pdf', NULL, NULL),
(103, 15, 'Clearance for First-time Job Seeker', '2 pages with header', 'approved', '2025-03-02 06:50:55', '2025-03-02 00:00:11', '../documents/barangay_clearance_103.pdf', NULL, NULL),
(104, 15, 'Clearance for First-time Job Seeker', '2 pages with header and Title', 'approved', '2025-03-02 07:00:02', '2025-03-02 00:03:32', '../documents/barangay_clearance_104.pdf', NULL, NULL),
(105, 15, 'Clearance for First-time Job Seeker', '2 pages with header and Title Clearance', 'approved', '2025-03-02 07:03:24', '2025-03-02 00:32:11', '../documents/barangay_clearance_105.pdf', NULL, NULL),
(106, 15, 'Clearance for First-time Job Seeker', 'Oath of Undertaking', 'approved', '2025-03-02 07:32:03', '2025-03-02 00:38:43', '../documents/barangay_clearance_106.pdf', NULL, NULL),
(107, 15, 'Clearance for First-time Job Seeker', 'oath of undertaking', 'approved', '2025-03-02 07:38:29', '2025-03-02 00:40:42', '../documents/barangay_clearance_107.pdf', NULL, NULL),
(108, 15, 'Clearance for First-time Job Seeker', 'oath of undertaking', 'approved', '2025-03-02 07:40:35', '2025-03-02 00:42:01', '../documents/barangay_clearance_108.pdf', NULL, NULL),
(109, 15, 'Clearance for First-time Job Seeker', 'oath of undertaking', 'approved', '2025-03-02 07:41:54', '2025-03-02 00:47:55', '../documents/barangay_clearance_109.pdf', NULL, NULL),
(110, 15, 'Clearance for First-time Job Seeker', 'fetched data', 'approved', '2025-03-02 07:47:48', '2025-03-02 00:48:59', '../documents/barangay_clearance_110.pdf', NULL, NULL),
(111, 15, 'Clearance for First-time Job Seeker', 'fetched data', 'approved', '2025-03-02 07:48:52', '2025-03-02 00:58:21', '../documents/barangay_clearance_111.pdf', NULL, NULL),
(112, 15, 'Clearance for First-time Job Seeker', 'iasdjoasd', 'approved', '2025-03-02 07:58:14', '2025-03-02 01:02:05', '../documents/barangay_clearance_112.pdf', NULL, NULL),
(113, 15, 'Clearance for First-time Job Seeker', 'Bold', 'approved', '2025-03-02 08:01:57', '2025-03-02 01:04:52', '../documents/barangay_clearance_113.pdf', NULL, NULL),
(114, 15, 'Clearance for First-time Job Seeker', 'format', 'approved', '2025-03-02 08:04:44', '2025-03-02 01:06:22', '../documents/barangay_clearance_114.pdf', NULL, NULL),
(115, 15, 'Clearance for First-time Job Seeker', 'size', 'approved', '2025-03-02 08:06:15', '2025-03-02 01:08:52', '../documents/barangay_clearance_115.pdf', NULL, NULL),
(116, 15, 'Clearance for First-time Job Seeker', 'page', 'approved', '2025-03-02 08:08:45', '2025-03-02 01:10:53', '../documents/barangay_clearance_116.pdf', NULL, NULL),
(117, 15, 'Clearance for First-time Job Seeker', 'space', 'approved', '2025-03-02 08:10:46', '2025-03-02 01:13:41', '../documents/barangay_clearance_117.pdf', NULL, NULL),
(118, 15, 'Clearance for First-time Job Seeker', 'spacing', 'approved', '2025-03-02 08:13:34', '2025-03-02 01:17:10', '../documents/barangay_clearance_118.pdf', NULL, NULL),
(119, 15, 'Clearance for First-time Job Seeker', 'spacing', 'approved', '2025-03-02 08:17:03', '2025-03-02 01:20:32', '../documents/barangay_clearance_119.pdf', NULL, NULL),
(120, 15, 'Clearance for First-time Job Seeker', 'spacing', 'approved', '2025-03-02 08:20:23', '2025-03-02 01:20:37', '../documents/barangay_clearance_120.pdf', NULL, NULL),
(121, 20, 'Barangay Clearance', 'hhidpslgf', 'pending', '2025-03-06 11:02:37', NULL, NULL, NULL, NULL),
(122, 20, 'Barangay Clearance', 'hhidpslgf', 'approved', '2025-03-06 11:11:05', '2025-03-06 04:31:52', '../documents/barangay_clearance_122.pdf', NULL, NULL),
(123, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 11:14:22', '2025-03-27 09:07:52', '../documents/barangay_clearance_123.pdf', NULL, NULL),
(124, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 11:24:02', '2025-03-26 01:10:21', '../documents/barangay_clearance_124.pdf', 'uploads/Screenshot 2024-11-02 214810.png', NULL),
(125, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 11:30:37', '2025-03-26 01:04:15', '../documents/barangay_clearance_125.pdf', '../user/uploads/Screenshot 2024-11-02 214810.png', NULL),
(126, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 11:31:38', '2025-03-06 04:37:19', '../documents/barangay_clearance_126.pdf', '../user/uploads/Screenshot 2024-11-02 214810.png', NULL),
(127, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 11:37:12', '2025-03-06 04:40:05', '../documents/barangay_clearance_127.pdf', '../user/uploads/Screenshot 2024-11-02 214810.png', NULL),
(128, 20, 'Barangay Clearance', 'admin', 'approved', '2025-03-06 12:53:06', '2025-03-06 05:53:38', '../documents/barangay_clearance_128.pdf', '../user/uploads/Screenshot 2024-11-05 085647.png', NULL),
(129, 15, 'Barangay Clearance', 'Application for Business Permit', 'approved', '2025-03-08 16:40:23', '2025-03-08 09:48:17', '../documents/barangay_clearance_129.pdf', '../user/uploads/1.jpg', NULL),
(130, 15, 'Barangay Clearance', 'School Requirement', 'approved', '2025-03-28 02:11:32', '2025-03-27 19:28:27', '../documents/barangay_clearance_130.pdf', '../user/uploads/486020150_1825960304829685_6907477548716145075_n.jpg', NULL),
(131, 33, 'Barangay Clearance', 'School Requirement', 'approved', '2025-03-29 02:31:52', '2025-03-28 19:32:36', '../documents/barangay_clearance_131.pdf', '../user/uploads/elite.jpg', NULL),
(132, 15, 'Certificate of Indigency', 'tanginamo', 'approved', '2025-04-01 05:51:49', NULL, NULL, '', NULL),
(133, 15, 'Certificate of Residency', 'School Requirement', 'approved', '2025-04-01 05:57:35', '2025-03-31 23:57:57', '../documents/residency_certificate_133.pdf', '', NULL),
(134, 15, 'Certificate of Indigency', 'School Requirement', 'approved', '2025-04-01 06:07:56', NULL, NULL, '', NULL),
(135, 15, 'Certificate of Indigency', 'School Requirement', 'approved', '2025-04-01 06:13:22', NULL, NULL, '', NULL),
(136, 15, 'Clearance for First-time Job Seeker', 'School Requirement', 'approved', '2025-04-01 06:14:12', '2025-04-01 00:14:43', '../documents/barangay_clearance_136.pdf', '', NULL),
(137, 15, 'Certificate of Indigency', 'School Requirement', 'approved', '2025-04-01 06:20:27', NULL, NULL, '', NULL),
(138, 15, 'Certificate of Indigency', 'School Requirement', 'approved', '2025-04-01 06:30:57', NULL, NULL, '', NULL),
(139, 15, 'Certificate of Indigency', 'needed', 'approved', '2025-04-01 08:21:00', NULL, NULL, '../user/uploads/images (1).jpg', NULL),
(140, 17, 'Certificate of Indigency', 'ISSUED DATE ERROR', 'approved', '2025-04-01 08:23:07', NULL, NULL, '../user/uploads/1.jpg', NULL),
(141, 17, 'Certificate of Indigency', 'download error', 'approved', '2025-04-01 08:24:33', NULL, NULL, '../user/uploads/486020150_1825960304829685_6907477548716145075_n.jpg', NULL),
(142, 17, 'Certificate of Indigency', 'download error puke', 'approved', '2025-04-01 08:29:11', NULL, NULL, '../user/uploads/System Flowchart, UCD, IPO, CFD.png', NULL),
(143, 15, 'Certificate of Indigency', 'download error tangina', 'approved', '2025-04-01 14:28:32', '2025-04-01 08:29:48', '../documents/indigency_143.pdf', '../user/uploads/Picapica-photostrip.jpg', 'documents/indigency_143.pdf'),
(144, 15, 'Certificate of Indigency', 'bold', 'approved', '2025-04-01 14:34:26', '2025-04-01 08:35:05', '../documents/indigency_144.pdf', '../user/uploads/481692948_535878775651687_4126834704351186558_n.jpg', 'documents/indigency_144.pdf'),
(145, 15, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-02 05:29:14', '2025-04-01 23:51:35', '../documents/barangay_clearance_145.pdf', '', NULL),
(146, 15, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-02 06:48:01', '2025-04-02 00:48:29', '../documents/barangay_clearance_146.pdf', '', NULL),
(147, 35, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-02 14:34:57', '2025-04-02 08:35:17', '../documents/barangay_clearance_147.pdf', '', NULL),
(148, 35, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-02 14:53:11', '2025-04-02 08:54:11', '../documents/barangay_clearance_148.pdf', '', NULL),
(149, 35, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-07 02:37:05', '2025-04-06 20:37:36', '../documents/barangay_clearance_149.pdf', '', NULL),
(150, 35, 'Barangay Clearance', 'School Requirement', 'pending', '2025-04-07 02:55:40', NULL, NULL, '', NULL),
(151, 35, 'Barangay Clearance', 'School Requirement', 'approved', '2025-04-13 14:19:05', '2025-04-13 08:19:32', '../documents/barangay_clearance_151.pdf', '', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `equipment`
--

CREATE TABLE `equipment` (
  `equipment_id` int(11) NOT NULL,
  `equipment_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `equipment`
--

INSERT INTO `equipment` (`equipment_id`, `equipment_name`, `quantity`, `deleted_at`, `deleted_by`) VALUES
(1, 'Chairs and Tabless', 290, NULL, NULL),
(2, 'Microphones and Speakers	', 44, NULL, NULL),
(3, 'Tent', 30, NULL, NULL),
(4, 'Projectors and Screens', 40, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `facilities`
--

CREATE TABLE `facilities` (
  `facility_id` int(11) NOT NULL,
  `facility_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('available','reserved') DEFAULT 'available',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `facilities`
--

INSERT INTO `facilities` (`facility_id`, `facility_name`, `description`, `status`, `deleted_at`, `deleted_by`) VALUES
(1, 'Milares Covered Court', 'okay', 'reserved', NULL, NULL),
(2, 'Balagtas Covered Court', 'wow', 'reserved', NULL, NULL),
(3, 'J.Patricio Covered Court', NULL, 'available', NULL, NULL),
(4, 'Sentro Kalinga Hall', NULL, 'available', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `household`
--

CREATE TABLE `household` (
  `household_id` int(11) NOT NULL,
  `head_of_family` varchar(255) NOT NULL,
  `address` text NOT NULL,
  `number_of_children` int(11) NOT NULL DEFAULT 0,
  `number_of_male` int(11) NOT NULL DEFAULT 0,
  `number_of_female` int(11) NOT NULL DEFAULT 0,
  `total_family_members` int(11) NOT NULL DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `household`
--

INSERT INTO `household` (`household_id`, `head_of_family`, `address`, `number_of_children`, `number_of_male`, `number_of_female`, `total_family_members`, `is_deleted`, `deleted_at`) VALUES
(2, 'Rommel C. Rosario', '11B Katuray Lower Bicutan Taguig City', 3, 3, 2, 5, 0, NULL),
(7, 'Juan Joel Canceran', '11A Katuray Lower Bicutan Taguig City', 5, 4, 3, 7, 0, NULL),
(8, 'Jorge Buyao', '11B Katuray Lower Bicutan Taguig City', 0, 1, 1, 2, 0, NULL),
(9, 'Angelito De Leon', '11B Katuray Lower Bicutan Taguig City', 3, 3, 2, 5, 0, NULL),
(10, 'Adan S. Sequite ', '11B Katuray Lower Bicutan Taguig City', 0, 1, 1, 2, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `equipment_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`inventory_id`, `item_name`, `quantity`, `last_updated`, `equipment_id`) VALUES
(3, 'Chairs and Tables\r\n', 500, '2025-03-03 10:27:00', NULL),
(4, 'Microphones and Speakers', 35, '2025-03-03 10:27:00', NULL),
(5, 'Tent', 30, '2025-03-03 10:27:00', NULL),
(6, 'Projectors and Screens', 40, '2025-03-03 10:27:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `office_id` int(11) NOT NULL,
  `office_name` varchar(255) NOT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`office_id`, `office_name`, `deleted_at`, `deleted_by`) VALUES
(1, 'Office of Barangay Captain', NULL, NULL),
(2, 'Office of Ecological Solid Waste Management Office', NULL, NULL),
(7, 'VAWC Office', NULL, NULL),
(10, 'Office for Barangay Disaster Risk Reduction Management', NULL, NULL),
(11, 'Tanggapan ng Lupong Lupa', NULL, NULL),
(13, 'Barangay Admin', NULL, NULL),
(14, 'Tanggapan ng Kalihim', NULL, NULL),
(15, 'Tanggapan ng Ingat-Yaman', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `reservation_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `facility_id` int(11) DEFAULT NULL,
  `equipment_id` int(11) DEFAULT NULL,
  `quantity_requested` int(11) DEFAULT NULL,
  `reservation_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`reservation_id`, `user_id`, `resident_id`, `purpose`, `facility_id`, `equipment_id`, `quantity_requested`, `reservation_date`, `end_date`, `status`, `created_at`, `updated_at`, `deleted_at`, `deleted_by`) VALUES
(18, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-14 00:00:00', '2025-03-15 00:00:00', 'Approved', '2025-03-04 11:06:42', '2025-03-04 11:06:42', NULL, NULL),
(19, 15, 7, 'birthday', 3, NULL, NULL, '2025-03-14 00:00:00', '2025-03-15 00:00:00', 'Approved', '2025-03-04 11:08:20', '2025-03-04 11:08:20', NULL, NULL),
(20, 15, 7, 'birthday', 5, NULL, NULL, '2025-03-22 00:00:00', '2025-03-29 00:00:00', 'Pending', '2025-03-04 11:08:47', '2025-03-04 11:08:47', NULL, NULL),
(21, 15, 7, 'birthday', 3, NULL, NULL, '2025-03-14 00:00:00', '2025-03-15 00:00:00', 'Approved', '2025-03-04 11:09:49', '2025-03-04 11:09:49', NULL, NULL),
(22, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Rejected', '2025-03-04 23:21:50', '2025-03-04 23:21:50', NULL, NULL),
(25, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Approved', '2025-03-04 23:22:48', '2025-03-04 23:22:48', NULL, NULL),
(26, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Pending', '2025-03-04 23:38:26', '2025-03-04 23:38:26', NULL, NULL),
(27, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Pending', '2025-03-04 23:40:34', '2025-03-04 23:40:34', NULL, NULL),
(28, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Pending', '2025-03-04 23:42:30', '2025-03-04 23:42:30', NULL, NULL),
(29, 19, 11, 'peram', 2, NULL, 5, '2025-03-05 00:00:00', '2025-03-07 00:00:00', 'Pending', '2025-03-04 23:42:52', '2025-03-04 23:42:52', NULL, NULL),
(30, 19, 11, 'peram', 3, NULL, NULL, '2025-03-22 00:00:00', '2025-03-29 00:00:00', 'Pending', '2025-03-04 23:43:13', '2025-03-04 23:43:13', NULL, NULL),
(31, 19, 11, 'peram', 3, NULL, NULL, '2025-03-22 00:00:00', '2025-03-29 00:00:00', 'Pending', '2025-03-04 23:54:21', '2025-03-04 23:54:21', NULL, NULL),
(39, 19, 11, 'peram', 1, NULL, NULL, '2025-03-01 00:00:00', '2025-03-02 00:00:00', 'Rejected', '2025-03-05 00:03:02', '2025-03-05 00:03:02', NULL, NULL),
(42, 19, 11, 'peram', 1, NULL, NULL, '2025-03-01 00:00:00', '2025-03-02 00:00:00', 'Approved', '2025-03-05 00:13:31', '2025-03-05 00:13:31', NULL, NULL),
(45, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-22 00:00:00', '2025-03-22 00:00:00', 'Pending', '2025-03-05 22:21:26', '2025-03-05 22:21:26', NULL, NULL),
(46, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-22 00:00:00', '2025-03-22 00:00:00', 'Pending', '2025-03-05 22:22:37', '2025-03-05 22:22:37', NULL, NULL),
(47, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-22 00:00:00', '2025-03-22 00:00:00', 'Pending', '2025-03-05 22:23:19', '2025-03-05 22:23:19', NULL, NULL),
(48, 15, 7, 'birthday', NULL, 3, 10, '2025-03-22 00:00:00', '2025-03-22 00:00:00', 'Approved', '2025-03-05 22:23:49', '2025-03-05 22:23:49', NULL, NULL),
(49, 19, 11, 'birthday', NULL, 1, 9, '2025-03-08 00:00:00', '2025-03-11 00:00:00', 'Pending', '2025-03-06 09:32:43', '2025-03-06 09:32:43', NULL, NULL),
(50, 19, 11, 'birthday', NULL, 1, 9, '2025-03-08 00:00:00', '2025-03-11 00:00:00', 'Pending', '2025-03-06 09:35:02', '2025-03-06 09:35:02', NULL, NULL),
(51, 19, 11, 'birthday', NULL, 1, 9, '2025-03-08 00:00:00', '2025-03-11 00:00:00', 'Pending', '2025-03-06 09:42:01', '2025-03-06 09:42:01', NULL, NULL),
(52, 19, 11, 'birthday', 2, 3, 5, '2025-03-15 00:00:00', '2025-03-13 00:00:00', 'Pending', '2025-03-06 09:42:40', '2025-03-06 09:42:40', NULL, NULL),
(53, 19, 11, 'birthday', 2, 3, 5, '2025-03-15 00:00:00', '2025-03-13 00:00:00', 'Pending', '2025-03-06 09:46:20', '2025-03-06 09:46:20', NULL, NULL),
(54, 19, 11, 'birthday', 2, 3, 5, '2025-03-15 00:00:00', '2025-03-13 00:00:00', 'Pending', '2025-03-06 09:52:09', '2025-03-06 09:52:09', NULL, NULL),
(55, 19, 11, 'birthday', 2, 3, 5, '2025-03-15 00:00:00', '2025-03-13 00:00:00', 'Pending', '2025-03-06 09:54:27', '2025-03-06 09:54:27', NULL, NULL),
(60, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:01:20', '2025-03-06 10:01:20', NULL, NULL),
(61, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:01:47', '2025-03-06 10:01:47', NULL, NULL),
(62, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:01:53', '2025-03-06 10:01:53', NULL, NULL),
(63, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:02:09', '2025-03-06 10:02:09', NULL, NULL),
(64, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:02:29', '2025-03-06 10:02:29', NULL, NULL),
(65, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:06:18', '2025-03-06 10:06:18', NULL, NULL),
(66, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:06:35', '2025-03-06 10:06:35', NULL, NULL),
(67, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:07:18', '2025-03-06 10:07:18', NULL, NULL),
(68, 19, 11, 'birthday', NULL, 1, 3, '2025-03-22 00:00:00', '2025-03-24 00:00:00', 'Pending', '2025-03-06 10:09:53', '2025-03-06 10:09:53', NULL, NULL),
(69, 19, 11, 'birthday', NULL, 1, 2, '2025-03-08 00:00:00', '2025-03-15 00:00:00', 'Pending', '2025-03-06 10:10:18', '2025-03-06 10:10:18', NULL, NULL),
(70, 19, 11, 'bbtimeq', 4, NULL, NULL, '2025-03-08 00:00:00', '2025-03-09 00:00:00', 'Pending', '2025-03-06 10:10:47', '2025-03-06 10:10:47', NULL, NULL),
(71, 19, 11, 'bbtimeq', 4, NULL, NULL, '2025-03-08 00:00:00', '2025-03-09 00:00:00', 'Pending', '2025-03-06 10:15:41', '2025-03-06 10:15:41', NULL, NULL),
(72, 19, 11, 'bago to', NULL, 2, 3, '2025-03-08 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:16:11', '2025-03-06 10:16:11', NULL, NULL),
(73, 19, 11, 'bago to', NULL, 2, 3, '2025-03-08 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:27:37', '2025-03-06 10:27:37', NULL, NULL),
(74, 19, 11, 'bago to', NULL, 2, 3, '2025-03-08 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:30:22', '2025-03-06 10:30:22', NULL, NULL),
(75, 19, 11, 'bago to', NULL, 2, 3, '2025-03-08 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:31:05', '2025-03-06 10:31:05', NULL, NULL),
(76, 19, 11, 'bago to', NULL, 2, 3, '2025-03-08 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:32:09', '2025-03-06 10:32:09', NULL, NULL),
(77, 19, 11, 'hala', NULL, 4, 1, '2025-03-01 00:00:00', '2025-03-08 00:00:00', 'Pending', '2025-03-06 10:32:35', '2025-03-06 10:32:35', NULL, NULL),
(78, 19, 11, 'hala', NULL, 4, 1, '2025-03-01 00:00:00', '2025-03-08 00:00:00', 'Returned', '2025-03-06 10:35:38', '2025-03-06 10:35:38', NULL, NULL),
(79, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Pending', '2025-03-06 10:35:55', '2025-03-06 10:35:55', NULL, NULL),
(80, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Pending', '2025-03-06 10:36:41', '2025-03-06 10:36:41', NULL, NULL),
(81, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Pending', '2025-03-06 10:39:23', '2025-03-06 10:39:23', NULL, NULL),
(82, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Approved', '2025-03-06 10:39:42', '2025-03-06 10:39:42', NULL, NULL),
(83, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Approved', '2025-03-06 10:39:58', '2025-03-06 10:39:58', NULL, NULL),
(84, 19, 11, 'birthday', 2, NULL, NULL, '2025-02-27 00:00:00', '2025-02-28 00:00:00', 'Returned', '2025-03-06 10:42:29', '2025-03-06 10:42:29', NULL, NULL),
(87, 15, 7, 'Seminar', NULL, 1, 10, '2025-03-10 00:00:00', '2025-03-10 00:00:00', 'Returned', '2025-03-10 23:24:45', '2025-03-10 23:24:45', NULL, NULL),
(89, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-30 00:00:00', '2025-03-30 00:00:00', 'Rejected', '2025-03-28 02:40:46', '2025-03-28 02:40:46', NULL, NULL),
(90, 15, 7, 'birthday', 1, NULL, NULL, '2025-03-30 00:00:00', '2025-03-30 00:00:00', 'Returned', '2025-03-28 02:41:04', '2025-03-28 02:41:04', NULL, NULL),
(91, 15, 7, 'birthday', NULL, 3, 1, '2025-03-29 00:00:00', '2025-03-29 00:00:00', 'Returned', '2025-03-28 02:51:27', '2025-03-28 02:51:27', NULL, NULL),
(92, 15, 7, 'birthday', 2, NULL, NULL, '2025-04-05 08:00:00', '2025-04-05 09:00:00', 'Returned', '2025-04-02 02:46:32', '2025-04-02 02:46:32', '2025-04-01 18:57:45', 29),
(93, 15, 7, 'birthday', 2, NULL, NULL, '2025-04-06 08:00:00', '2025-04-06 10:00:00', 'Approved', '2025-04-02 02:59:24', '2025-04-02 02:59:24', '2025-04-01 19:18:25', 29),
(94, 33, 29, 'birthday', 4, NULL, NULL, '2025-04-05 10:00:00', '2025-04-05 11:00:00', 'Rejected', '2025-04-02 03:17:50', '2025-04-02 03:17:50', '2025-04-01 19:18:35', 29),
(95, 33, 29, 'birthday', 1, NULL, NULL, '2025-04-06 08:00:00', '2025-04-06 11:00:00', 'Returned', '2025-04-02 03:21:37', '2025-04-02 03:21:37', NULL, NULL),
(96, 15, 7, 'birthday', 1, NULL, NULL, '2025-04-05 08:00:00', '2025-04-05 17:00:00', 'Returned', '2025-04-02 10:23:26', '2025-04-02 10:23:26', NULL, NULL),
(99, 35, 31, 'birthday', 2, NULL, NULL, '2025-04-07 08:00:00', '2025-04-07 10:00:00', 'Approved', '2025-04-02 21:31:18', '2025-04-02 21:31:18', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `residents`
--

CREATE TABLE `residents` (
  `resident_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `address` varchar(255) NOT NULL,
  `fname` varchar(255) NOT NULL,
  `mname` varchar(255) NOT NULL,
  `lname` varchar(255) NOT NULL,
  `place_of_birth` varchar(255) NOT NULL,
  `age` int(11) NOT NULL,
  `civil_status` varchar(50) NOT NULL,
  `occupation` varchar(255) NOT NULL,
  `citizenship` varchar(100) NOT NULL,
  `voter_status` enum('Yes','No') NOT NULL,
  `birthdate` date DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `phone` varchar(25) NOT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `years_of_residency` int(11) NOT NULL DEFAULT 0,
  `valid_id` varchar(255) DEFAULT NULL,
  `verification_status` enum('Approved','Rejected','Deactivate','Pending') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `residents`
--

INSERT INTO `residents` (`resident_id`, `user_id`, `address`, `fname`, `mname`, `lname`, `place_of_birth`, `age`, `civil_status`, `occupation`, `citizenship`, `voter_status`, `birthdate`, `title`, `phone`, `profile_image`, `years_of_residency`, `valid_id`, `verification_status`) VALUES
(2, 1, '11B Katuray St Lower Bicutan Taguig City', 'Jerome', 'Sadio', 'Rosario', 'Taguig', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-04-04', 'Mr', '09123456788', 'uploads/1_profile_67ae8d0a4da9b.png', 19, NULL, 'Approved'),
(3, 11, '11B Katuray St Lower Bicutan Taguig City', '', '', '', '', 0, '', '', '', 'Yes', NULL, '', '', NULL, 0, NULL, 'Pending'),
(4, 12, '11B Katuray St Lower Bicutan Taguig City', 'Test1', '', 'Po1', '', 0, '', '', '', 'Yes', NULL, 'Ms', '09124675367', 'uploads/12_profile_67ad62a2ca922.png', 0, NULL, 'Pending'),
(5, 13, 'Jerome Lower Bicutan', 'CInnamon', '', 'Spice', '', 0, '', '', '', 'Yes', NULL, 'MR', '091234543567', 'uploads/13_profile_67ad63bf968e0.jpg', 0, NULL, 'Pending'),
(6, 14, 'Quirino St. Lower Bicutan, Taguig City', 'Ernesto III', 'Marcelino', 'Tolin', 'Manila City', 20, 'Single', 'Student', 'Filipino', 'Yes', '2004-08-19', 'Mr', '09123456789', 'uploads/14_profile_67ae8c7235dbf.png', 0, NULL, 'Pending'),
(7, 15, 'lower bicutan', 'Meriel', 'Namora', 'Lanuza', 'Pateros', 19, 'Married', 'Student', 'Filipino', 'Yes', '2006-01-18', 'Ms', '09123456575', 'uploads/15_profile_67de631a59474.JPG', 19, 'uploads/15_valid_id_67e562c30f05a.jpg', 'Approved'),
(8, 16, 'Lower Bicutan', 'Evan', 'Tian', 'Piad', 'Caloocan City', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-10-07', 'Mr', '09206147887', 'uploads/16_profile_67b190d1b7830.jpg', 0, NULL, 'Approved'),
(9, 17, 'Lower Bicutan', 'Juli', 'Layco', 'Pasteteo', 'Lipa City, Batangas', 20, 'Taken', 'Student', 'Filipino', 'Yes', '2004-09-10', 'Ms', '09944994183', 'uploads/17_profile_67b1f0568a440.jpg', 20, NULL, 'Approved'),
(10, 18, 'Lower Bicutan', 'AJI', 'N/A', 'Pasteteo', 'PASIG CITY', 20, 'Single', 'Student', 'Filipino', 'Yes', '2004-07-01', 'Ms', '0973643742', 'uploads/18_profile_67c20b1c0da58.jpg', 20, NULL, 'Pending'),
(11, 19, 'lower bicutan', 'kath', 'regalario', 'priol-odena', 'taguig city', 20, 'married', 'it girl', 'filipino', 'Yes', '2004-08-12', 'ms', '09296785178', NULL, 20, NULL, 'Pending'),
(12, 20, 'lower bicutan', 'Evan', 'Tian', 'Piad', 'Caloocan City', 19, 'Single', 'Student', 'Pelepens', 'Yes', '2005-10-07', 'IT Student', '09763374561', 'uploads/20_profile_67c96f05df971.png', 19, NULL, 'Approved'),
(13, 21, '11B Katuray Lower Bicutan Taguig City', 'JEROME', 'sadio', 'ROSARIO', 'Taguig City', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-04-04', 'Mr.', '09123456575', 'uploads/21_profile_67d0e468096a8.jpg', 19, 'uploads/21_valid_id_67d0e4680999b.jpg', 'Approved'),
(14, 22, '11B Katuray Lower Bicutan Taguig City', 'Ernesto III', 'Marcelino', 'Tolin', 'Manila City', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-08-19', 'Mr.', '09123456575', 'uploads/22_profile_67d0e60a8d7bc.jpg', 19, 'uploads/22_valid_id_67d0e60a8da2a.jpg', 'Pending'),
(15, 23, '11B Katuray Lower Bicutan Taguig City', 'Carl Evan', 'Tian', 'Piad', 'Caloocan City', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-10-07', 'Mr', '09123456575', 'uploads/23_profile_67d8d7ef5326a.png', 19, 'uploads/23_valid_id_67d8d7ef53815.jpg', 'Approved'),
(16, 24, '11B Katuray Lower Bicutan Taguig City', 'JEROME', 'SADIO', 'ROSARIO', 'Taguig City', 19, 'Single', 'Student', 'Filipino', 'Yes', '2005-04-04', 'Mr.', '09123456575', 'uploads/24_profile_67d8e6a82fa5b.jpg', 19, 'uploads/24_valid_id_67d8e6a82ff3c.jpg', 'Pending'),
(20, 25, 'Lower Bicutan', 'Kevin', 'F', 'Ofracio', 'PASIG CITY', 24, 'Single', 'STUDENT', 'FILIPINO', 'Yes', '2000-07-06', 'Mr', '092736728', 'uploads/25_profile_67e036a37108a.jpg', 15, 'uploads/25_valid_id_67e036a371ddd.jpg', 'Approved'),
(25, 29, 'Lower Bicutan', 'Jessica', 'L.', 'Soho', 'PASIG CITY', 30, 'Married', 'Staff', 'FILIPINO', 'Yes', '1994-09-10', 'Ms', '09348582348', NULL, 20, NULL, 'Approved'),
(26, 30, 'Lower Bicutan', 'Ed', 'D', 'Caluag', 'PASIG CITY', 30, 'Single', 'Staff', 'FILIPINO', 'Yes', '1994-07-17', 'Mr', '0923827813', NULL, 18, NULL, 'Approved'),
(27, 31, 'Lower Bicutan', 'Erica', 'K', 'Mercado', 'PASIG CITY', 30, 'Married', 'Staff', 'FILIPINO', 'Yes', '1994-09-18', 'Ms', '0973874823', NULL, 20, NULL, 'Deactivate'),
(28, 32, 'Lower Bicutan', 'Maria', 'k', 'Labi-labi', 'PASIG CITY', 19, 'Single', 'STUDENT', 'FILIPINO', 'Yes', '2005-09-10', 'Ms', '0937647337', 'uploads/32_profile_67e6048bb9e8c.jpg', 19, 'uploads/32_valid_id_67e6048bba8c7.jpg', 'Pending'),
(29, 33, 'Lower Bicutan', 'Julia Christine', 'L.', 'Pasteteo', 'Batangas', 21, 'Single', 'STUDENT', 'FILIPINO', 'Yes', '2003-06-17', 'Ms', '0972647263', 'uploads/33_profile_67e75a9e4aecc.jpg', 5, 'uploads/33_valid_id_67e75a9e4c236.jpg', 'Approved'),
(31, 35, 'Lower Bicutan', 'Juliane', 'L.', 'Pasteteo', 'Batangas', 20, 'Single', 'STUDENT', 'FILIPINO', 'Yes', '2004-09-10', 'Ms', '09726372342', 'uploads/35_profile_67ed3be5a669a.png', 6, 'uploads/35_valid_id_67ed3be5a685e.jpg', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','resident','secretary','maintenance','lupon','offices') DEFAULT 'resident',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `password_hash`, `email`, `role`, `created_at`, `address`) VALUES
(1, '$2y$10$z5EVO3B.J1v1VL6.EP6V5uGl/sCn0VyEjp/EcDukhlqcvyjmTSUu6', 'rosario.jeromesteven4@gmail.com', 'admin', '2025-02-11 12:56:33', '11B Katuray Lower Bicutan Taguig City'),
(7, '$2y$10$0JIMhX2.kxLL5Lw8OCTtge9.5Bd.c.9/7c4iAhsx8ZmiUih4UAAZ.', 'jeromesteven.rosario@gmail.com', 'admin', '2025-02-13 02:25:44', ''),
(8, '$2y$10$hHQhJMWfXT6DZIDyYk1zF.be0H67l9cuTPbJvurlYfTc3VL0M8DLe', 'eros123@gmail.com', 'admin', '2025-02-13 02:31:43', ''),
(9, '$2y$10$pqq1yyffYItXrwFcfwRBruirNAecAJqNMu1pQEekbwGZ8Hh.PzE/K', 'eros1234@gmail.com', 'admin', '2025-02-13 02:34:27', ''),
(12, '$2y$10$mcp./3G0gl8vwW.zdBJE6uCwAqgvnIGpMkF3jr4ygyr9aAZSp3/du', 'testpo@gmail.com', 'resident', '2025-02-13 02:59:02', '11B Katuray St Lower Bicutan Taguig City'),
(13, '$2y$10$gIpMEOHSt.vOD.nOpj1y2O7KpDT.RHjvQ.dH9ruX8Ac5UrsTmmjgC', 'jerome123@gmail.com', 'resident', '2025-02-13 03:14:41', 'Jerome Lower Bicutan'),
(14, '$2y$10$jhoLSqP8U6qF7lruLN9t8.7pWOe2xbtXZtGa6Owu5uTLl6IfZ6P.G', 'eros19@gmail.com', 'resident', '2025-02-13 15:08:37', 'Quirino St. Lower Bicutan, Taguig City'),
(15, '$2y$10$9jaXTCm4SdGW/A5C55Ag.OhF8a0UR9d9gj6EN55euOV1weWy1spC2', 'meriel@gmail.com', 'resident', '2025-02-15 07:07:43', 'lower bicutan'),
(16, '$2y$10$RMEPjeKpBlG6Cuo7wVyzAuW7k.K4HbawSpgpAl3UON49GIfkQrH9i', 'ivan@gmail.com', 'resident', '2025-02-16 07:15:03', 'Lower Bicutan'),
(17, '$2y$10$DbiF0JQ8D0Rt23ZpaDoR0eFVfreuOJ1.SxD4qkkaaEHtY.o5zPy0u', 'juli@gmail.com', 'secretary', '2025-02-16 14:03:06', 'Lower Bicutan'),
(18, '$2y$10$C2Sqoes8Lqcyv/7sXqtPKuQg.hT5mUICk.2h/pxGVX5YI1t/ZuNge', 'inamo@gmail.com', 'resident', '2025-02-28 19:11:07', 'Lower Bicutan'),
(19, '$2y$10$0k6Zh2D4jMz1OE7J2MypEe/vUzASiLRALo6ug5gvXgxz90takqdYe', 'kath@gmail.com', 'resident', '2025-03-03 09:08:01', 'lower bicutan'),
(20, '$2y$10$WSI.7ke0GCvUaJmk7dOu4.Lx3Vg.j0IUYqrbZZO5071k3/fxPCsFO', 'evan@gmail.com', 'resident', '2025-03-06 09:13:03', 'lower bicutan'),
(22, '$2y$10$S3lVyRpOazJLdJEMWG5Tcuq5YHol70CMhAOtKyZZOO4qV8cKa8dKS', 'erosiii@gmail.com', 'resident', '2025-03-12 01:39:24', '11B Katuray Lower Bicutan Taguig City'),
(23, '$2y$10$FX4A8SkcppmZxjXNLo5YcO50/yXqTAbo8oYpfhhULTzRSnYo8ZvlO', 'carl@gmail.com', 'resident', '2025-03-18 02:16:57', '11B Katuray Lower Bicutan Taguig City'),
(24, '$2y$10$PX2yP63xSYQ4jYwWNM6z4ekbNU3F2Hxj8RneAZ9Oael.B7tav4rJq', 'jeromesteven@gmail.com', 'resident', '2025-03-18 03:20:04', '11B Katuray Lower Bicutan Taguig City'),
(25, '$2y$10$TSv68DaWIzdA1HWCGxSHS.8brdLYEDwpvdiyclGC.fOReCD3O1BVq', 'kevin@gmail.com', 'maintenance', '2025-03-22 07:22:32', 'Lower Bicutan'),
(29, '$2y$10$fPYU0Vk9eFNmBqHmkGfwsOMTNYBvobgFgVQyAdcUf1/WUA39qoekG', 'jessica@gmail.com', 'maintenance', '2025-03-27 13:31:52', 'Lower Bicutan'),
(30, '$2y$10$0IYTrTIfLetir4smVIKjSOEAC./KUaPFuXlAdeTe0JfXZMVx7/8eq', 'ed@gmail.com', 'lupon', '2025-03-27 14:20:43', 'Lower Bicutan'),
(31, '$2y$10$5/PoVZUFZjFLx7ryuDntEudGpzYmeKlhcS16WJGVGQHJYEyGhkNHq', 'erica@gmail.com', 'offices', '2025-03-27 14:27:54', 'Lower Bicutan'),
(32, '$2y$10$R3Dr7nim2VgBdoV/l02REu7ZIF8Tq47fCkO8sIewgEOZDcKST4U0G', 'emkim@gmail.com', 'resident', '2025-03-28 02:06:18', 'Lower Bicutan'),
(33, '$2y$10$.bwDZNtW7ft8Gc6m3z.vUOi3TeVPylMYg/r/gWpUTwWxZOs5H0Yn6', 'julia@gmail.com', 'resident', '2025-03-29 02:25:55', 'Lower Bicutan'),
(35, '$2y$10$4BYQHOejBXn10EEf9cnfHuQxqr0fGsd/2R9O.JGfjtoznmSgPzWAC', 'hulyana.jcp@gmail.com', 'resident', '2025-04-02 13:29:11', 'Lower Bicutan');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `resident_id` (`resident_id`);

--
-- Indexes for table `appointment_complaints`
--
ALTER TABLE `appointment_complaints`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `complaint_id` (`complaint_id`);

--
-- Indexes for table `barangay_notes`
--
ALTER TABLE `barangay_notes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `business_list`
--
ALTER TABLE `business_list`
  ADD PRIMARY KEY (`business_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `fk_business_document` (`document_id`);

--
-- Indexes for table `complaints`
--
ALTER TABLE `complaints`
  ADD PRIMARY KEY (`complaint_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`document_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `equipment`
--
ALTER TABLE `equipment`
  ADD PRIMARY KEY (`equipment_id`);

--
-- Indexes for table `facilities`
--
ALTER TABLE `facilities`
  ADD PRIMARY KEY (`facility_id`);

--
-- Indexes for table `household`
--
ALTER TABLE `household`
  ADD PRIMARY KEY (`household_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`inventory_id`),
  ADD KEY `fk_inventory_equipment` (`equipment_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`office_id`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`reservation_id`),
  ADD KEY `fk_user_reservations` (`user_id`),
  ADD KEY `fk_facility` (`facility_id`),
  ADD KEY `fk_equipment` (`equipment_id`);

--
-- Indexes for table `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`resident_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `appointment_complaints`
--
ALTER TABLE `appointment_complaints`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `barangay_notes`
--
ALTER TABLE `barangay_notes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `business_list`
--
ALTER TABLE `business_list`
  MODIFY `business_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `complaints`
--
ALTER TABLE `complaints`
  MODIFY `complaint_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `documents`
--
ALTER TABLE `documents`
  MODIFY `document_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=152;

--
-- AUTO_INCREMENT for table `equipment`
--
ALTER TABLE `equipment`
  MODIFY `equipment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `facilities`
--
ALTER TABLE `facilities`
  MODIFY `facility_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `household`
--
ALTER TABLE `household`
  MODIFY `household_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `inventory_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `office_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `reservation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=100;

--
-- AUTO_INCREMENT for table `residents`
--
ALTER TABLE `residents`
  MODIFY `resident_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE;

--
-- Constraints for table `appointment_complaints`
--
ALTER TABLE `appointment_complaints`
  ADD CONSTRAINT `appointment_complaints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appointment_complaints_ibfk_2` FOREIGN KEY (`complaint_id`) REFERENCES `complaints` (`complaint_id`) ON DELETE CASCADE;

--
-- Constraints for table `business_list`
--
ALTER TABLE `business_list`
  ADD CONSTRAINT `business_list_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `business_list_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`resident_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_business_document` FOREIGN KEY (`document_id`) REFERENCES `documents` (`document_id`) ON DELETE SET NULL;

--
-- Constraints for table `complaints`
--
ALTER TABLE `complaints`
  ADD CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_equipment` FOREIGN KEY (`equipment_id`) REFERENCES `equipment` (`equipment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_user_reservations` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
