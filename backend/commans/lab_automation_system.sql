-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 13, 2026 at 12:46 PM
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
-- Database: `lab_automation_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `cpri_submissions`
--

CREATE TABLE `cpri_submissions` (
  `cpri_id` int(11) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `submission_date` date NOT NULL,
  `cpri_reference_number` varchar(50) DEFAULT NULL,
  `expected_approval_date` date DEFAULT NULL,
  `actual_approval_date` date DEFAULT NULL,
  `approval_status` enum('Submitted','Under Review','Approved','Rejected','Resubmission Required') DEFAULT 'Submitted',
  `cpri_remarks` text DEFAULT NULL,
  `documents_submitted` text DEFAULT NULL,
  `submitted_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cpri_submissions`
--

INSERT INTO `cpri_submissions` (`cpri_id`, `product_id`, `submission_date`, `cpri_reference_number`, `expected_approval_date`, `actual_approval_date`, `approval_status`, `cpri_remarks`, `documents_submitted`, `submitted_by`, `created_at`, `updated_at`) VALUES
(1, '1234560197', '2026-01-10', '1543', '2026-01-11', '2026-01-11', 'Approved', NULL, NULL, 4, '2026-01-10 21:31:31', '2026-01-11 12:50:26'),
(2, '4400010101', '2026-01-11', '1', '2026-01-11', '2026-01-11', 'Rejected', NULL, NULL, 1, '2026-01-11 12:55:13', '2026-01-11 13:07:17'),
(3, '1100020109', '2026-01-11', '0013', '2025-11-07', '2026-01-11', 'Approved', NULL, NULL, 1, '2026-01-11 13:26:23', '2026-01-11 13:27:03');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `product_id` varchar(10) NOT NULL,
  `product_type_id` int(11) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `product_code` varchar(6) NOT NULL,
  `revision_number` varchar(2) NOT NULL,
  `manufacturing_number` varchar(2) NOT NULL,
  `batch_number` varchar(50) DEFAULT NULL,
  `manufacturing_date` date NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `specifications` text DEFAULT NULL,
  `current_status` enum('In Testing','Passed','Failed','Re-Manufacturing','Sent to CPRI') DEFAULT 'In Testing',
  `cpri_submission_date` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_type_id`, `product_name`, `product_code`, `revision_number`, `manufacturing_number`, `batch_number`, `manufacturing_date`, `quantity`, `specifications`, `current_status`, `cpri_submission_date`, `remarks`, `created_by`, `created_at`, `updated_at`) VALUES
('0000010101', 1, 'Air Circuit Breaker', '000001', '01', '01', 'ACB-JAN-24-A', '2025-12-31', 1, '', 'Passed', NULL, '', 1, '2026-01-10 17:54:14', '2026-01-12 14:28:07'),
('1100020102', 1, 'Miniature Circuit Breaker', '110002', '01', '02', 'MCB-JAN-24-B', '2024-01-12', 1, '', 'Passed', NULL, '', 1, '2026-01-10 20:39:58', '2026-01-10 21:52:34'),
('1100020109', 2, 'Air Circuit Breaker', '110002', '01', '09', 'ACB-MAY-18-A', '2025-07-10', 1, 'The Air Circuit Breaker (ACB) is a reliable device designed for three or four poles, supporting voltages up to 690V and rated currents from 100A to 400A, with adjustable protection settings. It features a compact design, robust performance with a high breaking capacity, and complies with IEC standards, ensuring safe and efficient operation.', '', '2026-01-11', NULL, 1, '2026-01-11 13:21:42', '2026-01-11 13:27:03'),
('1100030201', 1, 'Molded Case Circuit Breaker', '110003', '02', '01', 'MCCB-FEB-24-A', '2024-01-15', 4, '', 'In Testing', NULL, NULL, 1, '2026-01-10 20:41:19', '2026-01-10 20:41:19'),
('1100040103', 1, 'Surge Protection Device', '110004', '01', '03', 'SPD-FEB-24-B', '2025-06-18', 2, '', 'Failed', NULL, NULL, 1, '2026-01-10 20:42:44', '2026-01-10 20:59:47'),
('1234560197', 3, 'Ceramic Capacitors', '123456', '01', '97', 'MCB-JAN-24-B', '2026-01-11', 1, '', 'Failed', '2026-01-10', '', 4, '2026-01-10 21:28:06', '2026-01-12 10:21:00'),
('2200010101', 2, 'High Rupturing Capacity Fuse', '220001', '01', '01', 'HRC-MAR-24-A', '2025-04-06', 5, '', 'Sent to CPRI', NULL, '', 1, '2026-01-10 20:43:49', '2026-01-10 21:42:12'),
('2200020102', 2, 'Cartridge Fuse', '220002', '01', '02', 'CF-MAR-24-B', '2025-11-26', 1, '', 'In Testing', NULL, NULL, 1, '2026-01-10 20:44:55', '2026-01-10 20:44:55'),
('3300010101', 3, 'Power Factor Correction Capacitor', '330001', '01', '01', 'PFC-APR-24-A', '2024-10-31', 1, '', 'Failed', NULL, '', 1, '2026-01-10 20:45:59', '2026-01-10 21:52:00'),
('3300010201', 3, 'Electrolytic Capacitor', '330001', '02', '01', 'EC-APR-24-B', '2025-05-22', 4, '', 'In Testing', NULL, '', 1, '2026-01-10 20:47:15', '2026-01-11 13:13:28'),
('4400010101', 4, 'Wire-Wound Resistor', '440001', '01', '01', 'WWR-MAY-24-A', '2025-12-24', 1, '', 'Passed', '2026-01-11', '', 1, '2026-01-10 20:49:05', '2026-01-11 13:08:54'),
('4400020102', 4, 'Carbon Film Resistor', '440002', '01', '02', 'CFR-MAY-24-B', '2025-09-23', 1, '', 'In Testing', NULL, NULL, 1, '2026-01-10 20:49:46', '2026-01-10 20:49:46');

-- --------------------------------------------------------

--
-- Table structure for table `product_status_history`
--

CREATE TABLE `product_status_history` (
  `history_id` int(11) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `previous_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `change_reason` text DEFAULT NULL,
  `change_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_test_mapping`
--

CREATE TABLE `product_test_mapping` (
  `mapping_id` int(11) NOT NULL,
  `product_type_id` int(11) NOT NULL,
  `test_type_id` int(11) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `test_sequence` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `product_types`
--

CREATE TABLE `product_types` (
  `product_type_id` int(11) NOT NULL,
  `product_type_code` varchar(10) NOT NULL,
  `product_type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_types`
--

INSERT INTO `product_types` (`product_type_id`, `product_type_code`, `product_type_name`, `description`, `is_active`, `created_at`) VALUES
(1, 'SG', 'Switch Gears', 'Electrical switch gears and circuit breakers', 1, '2026-01-10 15:26:20'),
(2, 'FS', 'Fuses', 'Electrical fuses for circuit protection', 1, '2026-01-10 15:26:20'),
(3, 'CP', 'Capacitors', 'Electrical capacitors for power factor correction', 1, '2026-01-10 15:26:20'),
(4, 'RS', 'Resistors', 'Electrical resistors for current limiting', 1, '2026-01-10 15:26:20');

-- --------------------------------------------------------

--
-- Table structure for table `remanufacturing_records`
--

CREATE TABLE `remanufacturing_records` (
  `remanufacturing_id` int(11) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `original_test_id` varchar(12) DEFAULT NULL,
  `remanufacturing_date` date NOT NULL,
  `reason` text NOT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `completed_date` date DEFAULT NULL,
  `new_product_id` varchar(10) DEFAULT NULL,
  `status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `remanufacturing_records`
--

INSERT INTO `remanufacturing_records` (`remanufacturing_id`, `product_id`, `original_test_id`, `remanufacturing_date`, `reason`, `cost`, `completed_date`, `new_product_id`, `status`, `remarks`, `created_by`, `created_at`) VALUES
(2, '3300010201', NULL, '2026-01-10', 'Insulation breakdown detected', NULL, NULL, '231553', 'Pending', '', 4, '2026-01-10 21:51:02');

-- --------------------------------------------------------

--
-- Table structure for table `reports`
--

CREATE TABLE `reports` (
  `report_id` int(11) NOT NULL,
  `report_name` varchar(150) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `generated_by` int(11) NOT NULL,
  `generation_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `filters_applied` text DEFAULT NULL,
  `report_data` longtext DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_config`
--

CREATE TABLE `system_config` (
  `config_id` int(11) NOT NULL,
  `config_key` varchar(100) NOT NULL,
  `config_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_config`
--

INSERT INTO `system_config` (`config_id`, `config_key`, `config_value`, `description`, `updated_by`, `updated_at`) VALUES
(1, 'product_id_format', '10', 'Product ID length in digits', NULL, '2026-01-10 15:26:20'),
(2, 'test_id_format', '12', 'Test ID length in digits', NULL, '2026-01-10 15:26:20'),
(3, 'backup_frequency', 'daily', 'Database backup frequency', NULL, '2026-01-10 15:26:20'),
(4, 'max_retest_count', '3', 'Maximum number of retests allowed', NULL, '2026-01-10 15:26:20');

-- --------------------------------------------------------

--
-- Table structure for table `tests`
--

CREATE TABLE `tests` (
  `test_id` varchar(12) NOT NULL,
  `product_id` varchar(10) NOT NULL,
  `test_type_id` int(11) NOT NULL,
  `product_code` varchar(6) NOT NULL,
  `revision_number` varchar(2) NOT NULL,
  `testing_code` varchar(2) NOT NULL,
  `roll_number` varchar(2) NOT NULL,
  `test_date` date NOT NULL,
  `test_time` time NOT NULL,
  `tester_id` int(11) NOT NULL,
  `test_status` enum('Pending','In Progress','Passed','Failed') DEFAULT 'Pending',
  `observed_results` text DEFAULT NULL,
  `test_criteria_met` tinyint(1) DEFAULT NULL,
  `test_remarks` text DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `retest_required` tinyint(1) DEFAULT 0,
  `retest_count` int(11) DEFAULT 0,
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tests`
--

INSERT INTO `tests` (`test_id`, `product_id`, `test_type_id`, `product_code`, `revision_number`, `testing_code`, `roll_number`, `test_date`, `test_time`, `tester_id`, `test_status`, `observed_results`, `test_criteria_met`, `test_remarks`, `failure_reason`, `retest_required`, `retest_count`, `approved_by`, `approval_date`, `created_at`, `updated_at`) VALUES
('00000101CT01', '0000010101', 2, '000001', '01', 'CT', '1', '2026-01-10', '18:56:00', 1, 'Passed', 'All Done!', 1, '', '', 0, 0, NULL, NULL, '2026-01-10 17:56:50', '2026-01-10 20:25:04'),
('00000101VT01', '0000010101', 1, '000001', '01', 'VT', '1', '2026-01-10', '21:51:00', 1, 'Pending', 'Voltage readings are within the acceptable tolerance range during initial observation.', 0, 'Test initiated as per standard procedure.', '', 0, 0, NULL, NULL, '2026-01-10 20:55:56', '2026-01-10 20:55:56'),
('11000201CT01', '1100020102', 2, '110002', '01', 'CT', '1', '2026-01-10', '21:55:00', 1, 'Passed', 'Breaker successfully carried rated current without tripping.', 1, 'Performance satisfactory.', '', 0, 0, NULL, NULL, '2026-01-10 20:57:35', '2026-01-10 20:57:35'),
('11000201DT01', '1100020109', 5, '110002', '01', 'DT', '1', '2025-09-10', '14:22:00', 1, 'Passed', 'The Air Circuit Breaker performed within the expected current test specifications reliably.', 1, 'The Air Circuit Breaker performed well, meeting specifications with no operational issues during the testing phase.', '', 0, 0, NULL, NULL, '2026-01-11 13:25:09', '2026-01-11 13:25:29'),
('11000302DT01', '1100030201', 5, '110003', '02', 'DT', '1', '2025-08-21', '21:57:00', 1, 'Passed', 'Breaker operated smoothly during repeated ON/OFF cycles.', 1, 'No mechanical defects found.', '', 0, 0, NULL, NULL, '2026-01-10 20:58:32', '2026-01-10 20:58:32'),
('11000401IT01', '1100040103', 4, '110004', '01', 'IT', '1', '2025-10-29', '21:58:00', 1, 'Failed', 'Insulation resistance value below acceptable limit.', 0, 'Device insulation weak under high voltage.', 'Insulation breakdown detected', 0, 0, NULL, NULL, '2026-01-10 20:59:47', '2026-01-10 20:59:47'),
('12345601CT01', '1234560197', 2, '123456', '01', 'CT', '1', '2026-01-10', '22:28:00', 4, 'Passed', '', 1, '', '', 0, 0, NULL, NULL, '2026-01-10 21:28:33', '2026-01-10 21:28:33'),
('12345601CT02', '1234560197', 2, '123456', '01', 'CT', '02', '2025-12-17', '15:17:00', 4, 'Failed', '', 1, '', '', 0, 0, NULL, NULL, '2026-01-12 10:18:21', '2026-01-12 10:21:00'),
('22000101CT01', '2200010101', 2, '220001', '01', 'CT', '1', '2026-01-02', '21:59:00', 1, 'Passed', 'Fuse handled rated current and ruptured correctly under overload.', 1, 'Test successful.', '', 0, 0, NULL, NULL, '2026-01-10 21:00:34', '2026-01-10 21:00:34'),
('22000201RT01', '2200020102', 3, '220002', '01', 'RT', '1', '2025-01-10', '22:00:00', 1, 'Passed', 'Resistance value within permissible range.', 1, 'No abnormal heating observed.', '', 0, 0, NULL, NULL, '2026-01-10 21:01:28', '2026-01-10 21:01:28'),
('33000101VT01', '3300010101', 1, '330001', '01', 'VT', '1', '2026-01-09', '22:01:00', 1, 'In Progress', '', 0, 'Test in progress', '', 0, 0, NULL, NULL, '2026-01-10 21:02:18', '2026-01-10 21:07:06'),
('33000102LT01', '3300010201', 6, '330001', '02', 'LT', '1', '2026-01-01', '22:03:00', 1, 'Failed', 'Leakage current exceeded safe limits.', 0, 'Capacitor shows internal leakage.', 'Excessive leakage current', 0, 0, NULL, NULL, '2026-01-10 21:04:48', '2026-01-10 21:04:48'),
('44000101HT01', '4400010101', 7, '440001', '01', 'HT', '1', '2026-01-10', '22:04:00', 1, 'In Progress', '', 0, '', 'Test in progress', 0, 0, NULL, NULL, '2026-01-10 21:05:36', '2026-01-10 21:05:36'),
('44000201RT01', '4400020102', 3, '440002', '01', 'RT', '1', '2025-02-10', '22:05:00', 1, 'Passed', 'Measured resistance matches rated value.', 1, 'Component approved.', '', 0, 0, NULL, NULL, '2026-01-10 21:06:20', '2026-01-10 21:06:20');

-- --------------------------------------------------------

--
-- Table structure for table `test_parameters`
--

CREATE TABLE `test_parameters` (
  `parameter_id` int(11) NOT NULL,
  `test_id` varchar(12) NOT NULL,
  `parameter_name` varchar(100) NOT NULL,
  `expected_value` varchar(100) DEFAULT NULL,
  `actual_value` varchar(100) DEFAULT NULL,
  `unit` varchar(20) DEFAULT NULL,
  `is_within_range` tinyint(1) DEFAULT NULL,
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `test_types`
--

CREATE TABLE `test_types` (
  `test_type_id` int(11) NOT NULL,
  `test_type_code` varchar(6) NOT NULL,
  `test_type_name` varchar(100) NOT NULL,
  `test_description` text DEFAULT NULL,
  `test_criteria` text DEFAULT NULL,
  `test_parameters` text DEFAULT NULL,
  `department` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `test_types`
--

INSERT INTO `test_types` (`test_type_id`, `test_type_code`, `test_type_name`, `test_description`, `test_criteria`, `test_parameters`, `department`, `is_active`, `created_at`) VALUES
(1, 'VT', 'Voltage Test', 'Test voltage tolerance and insulation', NULL, NULL, 'Electrical Testing', 1, '2026-01-10 15:26:20'),
(2, 'CT', 'Current Test', 'Test current carrying capacity', NULL, NULL, 'Electrical Testing', 1, '2026-01-10 15:26:20'),
(3, 'RT', 'Resistance Test', 'Test electrical resistance values', NULL, NULL, 'Electrical Testing', 1, '2026-01-10 15:26:20'),
(4, 'IT', 'Insulation Test', 'Test insulation resistance', NULL, NULL, 'Electrical Testing', 1, '2026-01-10 15:26:20'),
(5, 'DT', 'Durability Test', 'Test physical durability and life cycle', NULL, NULL, 'Mechanical Testing', 1, '2026-01-10 15:26:20'),
(6, 'LT', 'Leakage Test', NULL, NULL, NULL, 'Electrical', 1, '2026-01-10 21:03:31'),
(7, 'HT', 'Heat Test', NULL, NULL, NULL, 'Thermal', 1, '2026-01-10 21:03:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `user_type` enum('Admin','Tester','Supervisor') NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `email`, `phone`, `user_type`, `is_active`, `created_at`, `last_login`) VALUES
(1, 'admin', '0192023a7bbd73250516f069df18b500', 'System Administrator', 'admin@electrical.com', '', 'Admin', 1, '2026-01-10 15:26:19', '2026-01-12 20:06:39'),
(4, 'tester', '8e607a4752fa2e59413e5790536f2b42', 'Products Tester', 'tester@electrical.com', NULL, 'Tester', 1, '2026-01-10 18:13:33', '2026-01-12 19:31:56'),
(7, 'supervisor', '1425d5d3160aa6bd140605cc75e63ce0', 'Lab Supervisor', 'supervisor@electrical.com', '', 'Supervisor', 1, '2026-01-11 12:01:10', '2026-01-12 20:17:39');

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(255) NOT NULL,
  `table_affected` varchar(50) DEFAULT NULL,
  `record_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `action`, `table_affected`, `record_id`, `ip_address`, `timestamp`) VALUES
(1, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 15:30:56'),
(2, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 17:42:11'),
(3, 1, 'Added new product', 'products', '0000010101', '::1', '2026-01-10 17:54:14'),
(4, 1, 'Added new test', 'tests', '00000101CT01', '::1', '2026-01-10 17:56:50'),
(5, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:13:49'),
(6, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:14:49'),
(7, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:15:13'),
(8, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:18:16'),
(9, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:19:51'),
(10, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:20:41'),
(11, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:21:49'),
(12, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:22:17'),
(13, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:22:58'),
(14, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:25:07'),
(15, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:26:07'),
(16, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:26:30'),
(17, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:28:36'),
(18, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:29:06'),
(19, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:29:22'),
(20, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:29:40'),
(21, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:30:34'),
(22, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:30:57'),
(23, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:31:27'),
(24, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 18:32:36'),
(25, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:51:56'),
(26, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:55:59'),
(27, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:57:17'),
(28, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:57:25'),
(29, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:58:02'),
(30, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 19:58:21'),
(31, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:02:20'),
(32, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:04:51'),
(33, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:07:49'),
(34, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:08:02'),
(35, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:08:55'),
(36, 1, 'Edited product', 'products', '0000010101', '::1', '2026-01-10 20:23:57'),
(37, 1, 'Edited test', 'tests', '00000101CT01', '::1', '2026-01-10 20:25:04'),
(38, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:26:04'),
(39, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 20:27:43'),
(40, 1, 'Edited user', 'users', 'admin', '::1', '2026-01-10 20:30:28'),
(41, 1, 'Added user', 'users', 'okasha', '::1', '2026-01-10 20:32:04'),
(42, 1, 'Deleted user', 'users', '5', '::1', '2026-01-10 20:32:36'),
(43, 1, 'Edited product', 'products', '0000010101', '::1', '2026-01-10 20:35:10'),
(44, 1, 'Added new product', 'products', '1100020102', '::1', '2026-01-10 20:39:58'),
(45, 1, 'Added new product', 'products', '1100030201', '::1', '2026-01-10 20:41:19'),
(46, 1, 'Added new product', 'products', '1100040103', '::1', '2026-01-10 20:42:44'),
(47, 1, 'Added new product', 'products', '2200010101', '::1', '2026-01-10 20:43:49'),
(48, 1, 'Added new product', 'products', '2200020102', '::1', '2026-01-10 20:44:55'),
(49, 1, 'Added new product', 'products', '3300010101', '::1', '2026-01-10 20:45:59'),
(50, 1, 'Added new product', 'products', '3300010201', '::1', '2026-01-10 20:47:15'),
(51, 1, 'Added new product', 'products', '4400010101', '::1', '2026-01-10 20:49:05'),
(52, 1, 'Added new product', 'products', '4400020102', '::1', '2026-01-10 20:49:46'),
(53, 1, 'Edited product', 'products', '0000010101', '::1', '2026-01-10 20:50:20'),
(54, 1, 'Added new test', 'tests', '00000101VT01', '::1', '2026-01-10 20:55:56'),
(55, 1, 'Added new test', 'tests', '11000201CT01', '::1', '2026-01-10 20:57:35'),
(56, 1, 'Added new test', 'tests', '11000302DT01', '::1', '2026-01-10 20:58:32'),
(57, 1, 'Added new test', 'tests', '11000401IT01', '::1', '2026-01-10 20:59:47'),
(58, 1, 'Added new test', 'tests', '22000101CT01', '::1', '2026-01-10 21:00:34'),
(59, 1, 'Added new test', 'tests', '22000201RT01', '::1', '2026-01-10 21:01:28'),
(60, 1, 'Added new test', 'tests', '33000101VT01', '::1', '2026-01-10 21:02:18'),
(61, 1, 'Added new test', 'tests', '33000102LT01', '::1', '2026-01-10 21:04:48'),
(62, 1, 'Added new test', 'tests', '44000101HT01', '::1', '2026-01-10 21:05:36'),
(63, 1, 'Added new test', 'tests', '44000201RT01', '::1', '2026-01-10 21:06:20'),
(64, 1, 'Edited test', 'tests', '33000101VT01', '::1', '2026-01-10 21:07:06'),
(65, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 21:07:54'),
(66, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 21:13:43'),
(67, 4, 'User logged in', NULL, NULL, '::1', '2026-01-10 21:27:31'),
(68, 4, 'Added new product', 'products', '1234560197', '::1', '2026-01-10 21:28:06'),
(69, 4, 'Added new test', 'tests', '12345601CT01', '::1', '2026-01-10 21:28:33'),
(70, 4, 'Submitted product to CPRI', 'products', '1234560197', '::1', '2026-01-10 21:31:31'),
(71, 4, 'Edited product', 'products', '1234560197', '::1', '2026-01-10 21:41:53'),
(72, 4, 'Edited product', 'products', '4400010101', '::1', '2026-01-10 21:42:02'),
(73, 4, 'Edited product', 'products', '2200010101', '::1', '2026-01-10 21:42:12'),
(74, 4, 'Created remanufacturing record', 'remanufacturing_records', '3300010201', '::1', '2026-01-10 21:51:02'),
(75, 4, 'Edited product', 'products', '3300010101', '::1', '2026-01-10 21:52:00'),
(76, 4, 'Edited product', 'products', '1100020102', '::1', '2026-01-10 21:52:15'),
(77, 4, 'Edited product', 'products', '1100020102', '::1', '2026-01-10 21:52:34'),
(78, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 21:53:22'),
(79, 1, 'Added user', 'users', 'Supervisor', '::1', '2026-01-10 21:59:31'),
(81, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 22:00:53'),
(82, 1, 'Deleted user', 'users', '6', '::1', '2026-01-10 22:01:13'),
(83, 1, 'User logged in', NULL, NULL, '::1', '2026-01-10 22:05:06'),
(84, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 11:59:51'),
(85, 1, 'Added user', 'users', 'supervisor', '::1', '2026-01-11 12:01:10'),
(86, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:01:33'),
(87, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:07:37'),
(88, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:26:40'),
(89, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:38:20'),
(90, 7, 'Approved CPRI submission', 'cpri_submissions', '1', '::1', '2026-01-11 12:50:26'),
(91, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:50:45'),
(92, 1, 'Edited product', 'products', '4400010101', '::1', '2026-01-11 12:51:20'),
(93, 1, 'Submitted product to CPRI', 'products', '4400010101', '::1', '2026-01-11 12:55:13'),
(94, 4, 'User logged in', NULL, NULL, '::1', '2026-01-11 12:59:23'),
(95, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 13:00:21'),
(96, 7, 'Rejected CPRI submission', 'cpri_submissions', '2', '::1', '2026-01-11 13:07:17'),
(97, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 13:07:52'),
(98, 1, 'Edited product', 'products', '4400010101', '::1', '2026-01-11 13:08:03'),
(99, 1, 'Edited product', 'products', '4400010101', '::1', '2026-01-11 13:08:39'),
(100, 1, 'Edited product', 'products', '4400010101', '::1', '2026-01-11 13:08:54'),
(101, 1, 'Updated remanufacturing record', 'remanufacturing_records', '2', '::1', '2026-01-11 13:09:21'),
(102, 1, 'Updated remanufacturing record', 'remanufacturing_records', '2', '::1', '2026-01-11 13:09:30'),
(103, 4, 'User logged in', NULL, NULL, '::1', '2026-01-11 13:09:47'),
(104, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 13:12:57'),
(105, 1, 'Edited product', 'products', '3300010201', '::1', '2026-01-11 13:13:28'),
(106, 1, 'Added new product', 'products', '1100020109', '::1', '2026-01-11 13:21:42'),
(107, 1, 'Added new test', 'tests', '11000201DT01', '::1', '2026-01-11 13:25:09'),
(108, 1, 'Edited test', 'tests', '11000201DT01', '::1', '2026-01-11 13:25:29'),
(109, 1, 'Submitted product to CPRI', 'products', '1100020109', '::1', '2026-01-11 13:26:23'),
(110, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 13:26:50'),
(111, 7, 'Approved CPRI submission', 'cpri_submissions', '3', '::1', '2026-01-11 13:27:03'),
(112, 1, 'User logged in', NULL, NULL, '::1', '2026-01-11 16:59:14'),
(113, 4, 'User logged in', NULL, NULL, '::1', '2026-01-11 17:01:34'),
(114, 7, 'User logged in', NULL, NULL, '::1', '2026-01-11 17:01:51'),
(115, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 10:10:41'),
(116, 4, 'Created new test: 12345601CT02', 'tests', '12345601CT02', '::1', '2026-01-12 10:18:21'),
(117, 4, 'Updated test: 12345601CT02', 'tests', '12345601CT02', '::1', '2026-01-12 10:18:50'),
(118, 4, 'Updated test: 12345601CT02', 'tests', '12345601CT02', '::1', '2026-01-12 10:19:15'),
(119, 4, 'Updated test: 12345601CT02', 'tests', '12345601CT02', '::1', '2026-01-12 10:20:00'),
(120, 4, 'Updated test: 12345601CT02', 'tests', '12345601CT02', '::1', '2026-01-12 10:21:00'),
(121, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 10:22:34'),
(122, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 10:22:40'),
(123, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 10:26:42'),
(124, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 10:26:51'),
(125, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 11:11:11'),
(126, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 11:11:17'),
(127, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 11:39:00'),
(128, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 11:39:07'),
(129, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 11:39:16'),
(130, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 11:41:51'),
(131, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 11:56:26'),
(132, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 11:56:38'),
(133, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 12:06:34'),
(134, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 12:06:40'),
(135, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 12:13:23'),
(136, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 12:13:33'),
(137, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 12:14:04'),
(138, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 13:14:13'),
(139, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 13:28:35'),
(140, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 13:28:43'),
(141, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:14:09'),
(142, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 14:14:29'),
(143, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:15:15'),
(144, 1, 'User logged in', NULL, NULL, '::1', '2026-01-12 14:24:38'),
(145, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:25:35'),
(146, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:27:07'),
(147, 7, 'Updated product status', 'products', '0000010101', '::1', '2026-01-12 14:28:07'),
(148, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 14:52:17'),
(149, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:52:24'),
(150, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 14:53:22'),
(151, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 14:53:39'),
(152, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 18:41:41'),
(153, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 18:46:00'),
(154, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 18:46:11'),
(155, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 18:48:31'),
(156, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 18:48:38'),
(157, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 18:48:58'),
(158, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 18:49:14'),
(159, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 18:55:18'),
(160, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 18:55:29'),
(161, 1, 'Edited remanufacturing record', 'remanufacturing_records', '2', '::1', '2026-01-12 19:30:54'),
(162, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 19:31:46'),
(163, 4, 'Logged in', NULL, NULL, '::1', '2026-01-12 19:31:56'),
(164, 4, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:06:27'),
(165, 1, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:06:39'),
(166, 1, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:06:48'),
(167, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:07:03'),
(168, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:07:27'),
(169, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:08:04'),
(170, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:11:52'),
(171, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:12:44'),
(172, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:16:51'),
(173, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:17:05'),
(174, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:17:08'),
(175, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:17:22'),
(176, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:17:28'),
(177, 7, 'Logged in', NULL, NULL, '::1', '2026-01-12 20:17:39'),
(178, 7, 'Logged out', NULL, NULL, '::1', '2026-01-12 20:19:12');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_cpri_ready_products`
-- (See below for the actual view)
--
CREATE TABLE `v_cpri_ready_products` (
`product_id` varchar(10)
,`product_name` varchar(150)
,`product_type_name` varchar(100)
,`batch_number` varchar(50)
,`total_tests` bigint(21)
,`passed_tests` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_product_testing_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_product_testing_summary` (
`product_id` varchar(10)
,`product_name` varchar(150)
,`product_type_name` varchar(100)
,`batch_number` varchar(50)
,`current_status` enum('In Testing','Passed','Failed','Re-Manufacturing','Sent to CPRI')
,`total_tests` bigint(21)
,`passed_tests` decimal(22,0)
,`failed_tests` decimal(22,0)
,`pending_tests` decimal(22,0)
,`product_created_date` timestamp
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_remanufacturing_required`
-- (See below for the actual view)
--
CREATE TABLE `v_remanufacturing_required` (
`product_id` varchar(10)
,`product_name` varchar(150)
,`product_type_name` varchar(100)
,`test_id` varchar(12)
,`test_type_name` varchar(100)
,`failure_reason` text
,`test_date` date
,`tester_name` varchar(100)
);

-- --------------------------------------------------------

--
-- Structure for view `v_cpri_ready_products`
--
DROP TABLE IF EXISTS `v_cpri_ready_products`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_cpri_ready_products`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `pt`.`product_type_name` AS `product_type_name`, `p`.`batch_number` AS `batch_number`, count(`t`.`test_id`) AS `total_tests`, sum(case when `t`.`test_status` = 'Passed' then 1 else 0 end) AS `passed_tests` FROM ((`products` `p` join `product_types` `pt` on(`p`.`product_type_id` = `pt`.`product_type_id`)) left join `tests` `t` on(`p`.`product_id` = `t`.`product_id`)) WHERE `p`.`current_status` = 'Passed' GROUP BY `p`.`product_id` HAVING `total_tests` = `passed_tests` AND `total_tests` > 0 ;

-- --------------------------------------------------------

--
-- Structure for view `v_product_testing_summary`
--
DROP TABLE IF EXISTS `v_product_testing_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_product_testing_summary`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `pt`.`product_type_name` AS `product_type_name`, `p`.`batch_number` AS `batch_number`, `p`.`current_status` AS `current_status`, count(`t`.`test_id`) AS `total_tests`, sum(case when `t`.`test_status` = 'Passed' then 1 else 0 end) AS `passed_tests`, sum(case when `t`.`test_status` = 'Failed' then 1 else 0 end) AS `failed_tests`, sum(case when `t`.`test_status` = 'Pending' then 1 else 0 end) AS `pending_tests`, `p`.`created_at` AS `product_created_date` FROM ((`products` `p` left join `product_types` `pt` on(`p`.`product_type_id` = `pt`.`product_type_id`)) left join `tests` `t` on(`p`.`product_id` = `t`.`product_id`)) GROUP BY `p`.`product_id` ;

-- --------------------------------------------------------

--
-- Structure for view `v_remanufacturing_required`
--
DROP TABLE IF EXISTS `v_remanufacturing_required`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_remanufacturing_required`  AS SELECT `p`.`product_id` AS `product_id`, `p`.`product_name` AS `product_name`, `pt`.`product_type_name` AS `product_type_name`, `t`.`test_id` AS `test_id`, `tt`.`test_type_name` AS `test_type_name`, `t`.`failure_reason` AS `failure_reason`, `t`.`test_date` AS `test_date`, `u`.`full_name` AS `tester_name` FROM ((((`products` `p` join `product_types` `pt` on(`p`.`product_type_id` = `pt`.`product_type_id`)) join `tests` `t` on(`p`.`product_id` = `t`.`product_id`)) join `test_types` `tt` on(`t`.`test_type_id` = `tt`.`test_type_id`)) join `users` `u` on(`t`.`tester_id` = `u`.`user_id`)) WHERE `t`.`test_status` = 'Failed' AND `p`.`current_status` = 'Failed' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cpri_submissions`
--
ALTER TABLE `cpri_submissions`
  ADD PRIMARY KEY (`cpri_id`),
  ADD UNIQUE KEY `cpri_reference_number` (`cpri_reference_number`),
  ADD KEY `submitted_by` (`submitted_by`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_approval_status` (`approval_status`),
  ADD KEY `idx_submission_date` (`submission_date`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `product_type_id` (`product_type_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_product_code` (`product_code`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_current_status` (`current_status`),
  ADD KEY `idx_manufacturing_date` (`manufacturing_date`);

--
-- Indexes for table `product_status_history`
--
ALTER TABLE `product_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `changed_by` (`changed_by`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_change_date` (`change_date`);

--
-- Indexes for table `product_test_mapping`
--
ALTER TABLE `product_test_mapping`
  ADD PRIMARY KEY (`mapping_id`),
  ADD UNIQUE KEY `unique_mapping` (`product_type_id`,`test_type_id`),
  ADD KEY `test_type_id` (`test_type_id`);

--
-- Indexes for table `product_types`
--
ALTER TABLE `product_types`
  ADD PRIMARY KEY (`product_type_id`),
  ADD UNIQUE KEY `product_type_code` (`product_type_code`),
  ADD KEY `idx_product_type_code` (`product_type_code`);

--
-- Indexes for table `remanufacturing_records`
--
ALTER TABLE `remanufacturing_records`
  ADD PRIMARY KEY (`remanufacturing_id`),
  ADD KEY `original_test_id` (`original_test_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `reports`
--
ALTER TABLE `reports`
  ADD PRIMARY KEY (`report_id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`),
  ADD KEY `idx_generation_date` (`generation_date`);

--
-- Indexes for table `system_config`
--
ALTER TABLE `system_config`
  ADD PRIMARY KEY (`config_id`),
  ADD UNIQUE KEY `config_key` (`config_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `tests`
--
ALTER TABLE `tests`
  ADD PRIMARY KEY (`test_id`),
  ADD KEY `test_type_id` (`test_type_id`),
  ADD KEY `approved_by` (`approved_by`),
  ADD KEY `idx_product_id` (`product_id`),
  ADD KEY `idx_test_status` (`test_status`),
  ADD KEY `idx_test_date` (`test_date`),
  ADD KEY `idx_tester_id` (`tester_id`);

--
-- Indexes for table `test_parameters`
--
ALTER TABLE `test_parameters`
  ADD PRIMARY KEY (`parameter_id`),
  ADD KEY `idx_test_id` (`test_id`);

--
-- Indexes for table `test_types`
--
ALTER TABLE `test_types`
  ADD PRIMARY KEY (`test_type_id`),
  ADD UNIQUE KEY `test_type_code` (`test_type_code`),
  ADD KEY `idx_test_type_code` (`test_type_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_user_type` (`user_type`),
  ADD KEY `idx_username` (`username`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_timestamp` (`timestamp`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cpri_submissions`
--
ALTER TABLE `cpri_submissions`
  MODIFY `cpri_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `product_status_history`
--
ALTER TABLE `product_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_test_mapping`
--
ALTER TABLE `product_test_mapping`
  MODIFY `mapping_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `product_types`
--
ALTER TABLE `product_types`
  MODIFY `product_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `remanufacturing_records`
--
ALTER TABLE `remanufacturing_records`
  MODIFY `remanufacturing_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `reports`
--
ALTER TABLE `reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_config`
--
ALTER TABLE `system_config`
  MODIFY `config_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `test_parameters`
--
ALTER TABLE `test_parameters`
  MODIFY `parameter_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `test_types`
--
ALTER TABLE `test_types`
  MODIFY `test_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cpri_submissions`
--
ALTER TABLE `cpri_submissions`
  ADD CONSTRAINT `cpri_submissions_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cpri_submissions_ibfk_2` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`product_type_id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `product_status_history`
--
ALTER TABLE `product_status_history`
  ADD CONSTRAINT `product_status_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `product_test_mapping`
--
ALTER TABLE `product_test_mapping`
  ADD CONSTRAINT `product_test_mapping_ibfk_1` FOREIGN KEY (`product_type_id`) REFERENCES `product_types` (`product_type_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_test_mapping_ibfk_2` FOREIGN KEY (`test_type_id`) REFERENCES `test_types` (`test_type_id`) ON DELETE CASCADE;

--
-- Constraints for table `remanufacturing_records`
--
ALTER TABLE `remanufacturing_records`
  ADD CONSTRAINT `remanufacturing_records_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `remanufacturing_records_ibfk_2` FOREIGN KEY (`original_test_id`) REFERENCES `tests` (`test_id`),
  ADD CONSTRAINT `remanufacturing_records_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `reports`
--
ALTER TABLE `reports`
  ADD CONSTRAINT `reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `system_config`
--
ALTER TABLE `system_config`
  ADD CONSTRAINT `system_config_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `tests`
--
ALTER TABLE `tests`
  ADD CONSTRAINT `tests_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tests_ibfk_2` FOREIGN KEY (`test_type_id`) REFERENCES `test_types` (`test_type_id`),
  ADD CONSTRAINT `tests_ibfk_3` FOREIGN KEY (`tester_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `tests_ibfk_4` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `test_parameters`
--
ALTER TABLE `test_parameters`
  ADD CONSTRAINT `test_parameters_ibfk_1` FOREIGN KEY (`test_id`) REFERENCES `tests` (`test_id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
