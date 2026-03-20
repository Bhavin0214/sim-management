-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 14, 2026 at 07:06 AM
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
-- Database: `sim_managment`
--

-- --------------------------------------------------------

--
-- Table structure for table `activision_e_sim`
--

CREATE TABLE `activision_e_sim` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIMM_NO` varchar(20) DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `REQ_BY` int(11) DEFAULT NULL,
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activision_e_sim`
--

INSERT INTO `activision_e_sim` (`REQ_ID`, `EMP_ID`, `SIMM_NO`, `REJECT_REASON`, `REMARKS`, `STATUS`, `REQ_AT`, `REQ_BY`, `ACT_AT`, `ACT_BY`) VALUES
(0, 5, '98745632100123456789', NULL, 'persoanl', 'pending', '2026-02-10 11:55:19', 5, NULL, NULL),
(1, 7, 'VI898912345678901244', NULL, 'Convert physical SIM to eSIM for international travel', 'complete', '2024-01-25 11:00:00', 7, '2024-01-26 09:30:00', 5),
(2, 8, 'VI898912345678901245', 'DADASD', 'Device supports eSIM, requesting conversion', 'reject', '2024-01-28 14:20:00', 8, NULL, NULL),
(3, 1, 'VI898912345678901247', 'CEO SIM cannot be converted to eSIM per policy', 'CEO corporate SIM', 'reject', '2024-01-22 16:00:00', 1, '2024-01-23 10:15:00', 5);

-- --------------------------------------------------------

--
-- Table structure for table `activision_of_new_sim`
--

CREATE TABLE `activision_of_new_sim` (
  `REQUEST_ID` varchar(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `ADMIN_NOTES` text DEFAULT NULL,
  `MOBILE_NO` varchar(15) DEFAULT NULL,
  `req_at` datetime DEFAULT current_timestamp(),
  `req_by` int(11) DEFAULT NULL,
  `act_at` datetime DEFAULT NULL,
  `act_by` int(11) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `level_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activision_of_new_sim`
--

INSERT INTO `activision_of_new_sim` (`REQUEST_ID`, `EMP_ID`, `SIM_NO`, `REMARKS`, `REJECT_REASON`, `ADMIN_NOTES`, `MOBILE_NO`, `req_at`, `req_by`, `act_at`, `act_by`, `STATUS`, `level_id`) VALUES
('ACT001', 9, 'VI898912345678901248', 'Manager level SIM activation', NULL, NULL, '9876543315', '2024-01-12 09:30:00', 2, '2024-01-15 14:00:00', 5, 'complete', NULL),
('ACT002', 10, 'AT898912345678901246', 'Airtel connection requested', NULL, NULL, '9876543313', '2024-01-14 10:15:00', 2, '2024-01-16 11:30:00', 5, 'complete', NULL),
('ACT003', 11, 'VI898912345678901242', 'Trainee SIM', NULL, NULL, '9876543309', '2024-01-20 14:30:00', 2, NULL, NULL, 'pending', NULL),
('ACT004', 3, 'VI898912345678901237', 'HOD SIM for IT department', 'SIM already assigned to another employee', NULL, '9876543304', '2024-01-08 15:45:00', 2, '2024-01-10 10:00:00', 5, 'reject', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `admin_actions`
--

CREATE TABLE `admin_actions` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `action` varchar(500) NOT NULL,
  `target_id` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `air_for_personal_use`
--

CREATE TABLE `air_for_personal_use` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `DURATION` int(11) DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `REQ_BY` int(11) DEFAULT NULL,
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `air_for_personal_use`
--

INSERT INTO `air_for_personal_use` (`REQ_ID`, `EMP_ID`, `SIM_NO`, `REASON`, `DURATION`, `REJECT_REASON`, `REQ_AT`, `REQ_BY`, `ACT_AT`, `ACT_BY`, `STATUS`) VALUES
(1, 7, 'VI898912345678901244', 'Family vacation to Thailand', 10, NULL, '2024-01-18 11:00:00', 7, '2024-01-18 15:30:00', 5, 'complete'),
(2, 8, 'VI898912345678901245', 'Honeymoon in Maldives', 12, 'Personal travel during peak project time', '2024-01-20 09:45:00', 8, '2024-01-20 14:00:00', 5, 'reject'),
(3, 10, 'AT898912345678901246', 'Medical emergency abroad', 7, NULL, '2024-01-22 16:30:00', 10, NULL, NULL, 'complete');

-- --------------------------------------------------------

--
-- Table structure for table `air_offical_usage`
--

CREATE TABLE `air_offical_usage` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `DURATION` int(11) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `REQ_BY` int(11) DEFAULT NULL,
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `air_offical_usage`
--

INSERT INTO `air_offical_usage` (`REQ_ID`, `EMP_ID`, `SIM_NO`, `REASON`, `DURATION`, `STATUS`, `REQ_AT`, `REQ_BY`, `ACT_AT`, `ACT_BY`, `REJECT_REASON`) VALUES
(1, 1, 'VI898912345678901247', 'CEO US business trip', 15, 'complete', '2024-01-02 09:00:00', 1, '2024-01-02 11:30:00', 5, NULL),
(2, 3, 'VI898912345678901247', 'IT conference in Singapore', 7, 'complete', '2024-01-10 14:00:00', 3, '2024-01-10 16:45:00', 5, NULL),
(3, 7, 'VI898912345678901244', 'Client meeting in Dubai', 5, 'reject', '2024-01-15 10:30:00', 7, '2024-01-15 14:00:00', 5, 'Travel dates not approved by CEO');

-- --------------------------------------------------------

--
-- Table structure for table `deactivision_of_sim`
--

CREATE TABLE `deactivision_of_sim` (
  `REQ_ID` varchar(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL,
  `ACT_AT` datetime DEFAULT NULL,
  `REQ_BY` int(11) DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `deactivision_of_sim`
--

INSERT INTO `deactivision_of_sim` (`REQ_ID`, `EMP_ID`, `ACT_BY`, `ACT_AT`, `REQ_BY`, `REQ_AT`, `SIM_NO`, `REASON`, `STATUS`) VALUES
('DEACT001', 7, 5, '2024-01-30 15:00:00', 2, '2024-01-28 10:00:00', 'VI898912345678901244', 'Employee resigned', 'complete'),
('DEACT002', 9, NULL, NULL, 2, '2024-02-01 09:30:00', 'VI898912345678901248', 'Department transfer', 'pending'),
('DEACT003', 12, 5, '2024-01-29 11:45:00', 2, '2024-01-27 14:30:00', NULL, 'Probation not cleared', 'reject');

-- --------------------------------------------------------

--
-- Table structure for table `isd_activision`
--

CREATE TABLE `isd_activision` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `REQ_BY` int(11) DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `isd_activision`
--

INSERT INTO `isd_activision` (`REQ_ID`, `EMP_ID`, `SIM_NO`, `REJECT_REASON`, `REMARKS`, `STATUS`, `REQ_BY`, `REQ_AT`, `ACT_AT`, `ACT_BY`) VALUES
(1, 7, 'VI898912345678901244', NULL, 'Required for US client meetings', 'complete', 7, '2024-01-05 11:00:00', '2024-01-05 15:30:00', 5),
(2, 3, 'VI898912345678901247', NULL, 'IT team international support', 'complete', 3, '2024-01-08 10:15:00', '2024-01-08 14:00:00', 5),
(3, 9, 'VI898912345678901248', 'Department budget exceeded', 'For vendor communication', 'reject', 9, '2024-01-12 09:45:00', '2024-01-12 16:00:00', 5);

-- --------------------------------------------------------

--
-- Table structure for table `master_employee`
--

CREATE TABLE `master_employee` (
  `ID` int(11) NOT NULL,
  `FULL_NAME` varchar(255) NOT NULL,
  `MOBILE_NO` varchar(15) DEFAULT NULL,
  `EMAIL_ID` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `ADDRESS` text DEFAULT NULL,
  `DESGINATION` varchar(100) DEFAULT NULL,
  `LOCATION` varchar(100) DEFAULT NULL,
  `REG_AT` date DEFAULT NULL,
  `ROLE` enum('HR','HOD','admin','CEO','Employee') NOT NULL,
  `STATUS` varchar(20) DEFAULT 'active',
  `level_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `master_employee`
--

INSERT INTO `master_employee` (`ID`, `FULL_NAME`, `MOBILE_NO`, `EMAIL_ID`, `password`, `ADDRESS`, `DESGINATION`, `LOCATION`, `REG_AT`, `ROLE`, `STATUS`, `level_id`) VALUES
(1, 'Bhavin Pamrar', '9876543210', 'bhavin.parmar@company.com', 'Bhavin@0214', 'Mumbai, Maharashtra', 'CEO', 'Mumbai', '2023-01-15', 'CEO', 'active', 1),
(2, 'Priya Sharma', '9876543211', 'priya.sharma@company.com', '0214', 'Delhi, India', 'HR Manager', 'Delhi', '2023-02-20', 'HR', 'active', 1),
(3, 'Chirag Agara', '9876543212', 'chirag.agara@company.com', 'Chirag@000\r\n', 'Ahmedabad, Gujarat', 'Head of IT', 'Ahmedabad', '2023-03-10', 'HOD', 'active', 2),
(4, 'Sneha Reddy', '9876543213', 'sneha.reddy@company.com', 'Sneha@123', 'Hyderabad, Telangana', 'Head of Finance', 'Hyderabad', '2023-03-15', 'HOD', 'active', 2),
(5, 'Ravi Jha', '9876543214', 'ravi.jha@company.com', 'Ravi@123', 'Vadinar, Gujarat', 'Admin Manager', 'Vadinar', '2023-04-01', 'admin', 'active', 3),
(6, 'Anjali Mehta', '9876543215', 'anjali.mehta@company.com', 'Anjali@123', 'Mumbai, Maharashtra', 'Admin Assistant', 'Mumbai', '2023-04-05', 'admin', 'active', 3),
(7, 'Vikram Singh', '9876543216', 'vikram.singh@company.com', '', 'Delhi, India', 'Senior Manager', 'Delhi', '2023-05-01', 'Employee', 'active', 3),
(8, 'Neha Gupta', '9876543217', 'neha.gupta@company.com', 'Neha@123', 'Mumbai, Maharashtra', 'Manager', 'Mumbai', '2023-05-10', 'Employee', 'active', 3),
(9, 'Rohan Verma', '9876543218', 'rohan.verma@company.com', '', 'Ahmedabad, Gujarat', 'Assistant Manager', 'Ahmedabad', '2023-06-01', 'Employee', 'active', 4),
(10, 'Pooja Mishra', '9876543219', 'pooja.mishra@company.com', '', 'Hyderabad, Telangana', 'Executive', 'Hyderabad', '2023-06-15', 'Employee', 'active', 4),
(11, 'Arjun Rao', '9876543220', 'arjun.rao@company.com', '', 'Vadinar, Gujarat', 'Junior Executive', 'Vadinar', '2023-07-01', 'Employee', 'active', 6),
(12, 'Sonia Kapoor', '9876543221', 'sonia.kapoor@company.com', '', 'Mumbai, Maharashtra', 'Trainee', 'Mumbai', '2023-07-10', 'Employee', 'active', 6);

-- --------------------------------------------------------

--
-- Table structure for table `porting_cocp`
--

CREATE TABLE `porting_cocp` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL,
  `REQ_BY` int(11) DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `CURRENT` varchar(50) DEFAULT NULL,
  `TO_SIM` varchar(50) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `REJECT_REASIN` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `porting_cocp`
--

INSERT INTO `porting_cocp` (`REQ_ID`, `EMP_ID`, `ACT_AT`, `ACT_BY`, `REQ_BY`, `REQ_AT`, `SIM_NO`, `REASON`, `CURRENT`, `TO_SIM`, `STATUS`, `REJECT_REASIN`) VALUES
(1, 7, '2024-01-25 11:00:00', 5, 2, '2024-01-20 10:00:00', 'VI898912345678901244', 'Employee leaving organization - ownership transfer', 'COCP', 'IOIP', 'complete', NULL),
(2, 9, NULL, NULL, 2, '2024-01-28 09:30:00', 'VI898912345678901248', 'Personal preference to continue number', 'COCP', 'IOIP', 'complete', NULL),
(3, 12, '2024-01-26 14:45:00', 5, 2, '2024-01-24 11:15:00', NULL, 'Probationary employee', 'COCP', 'IOIP', 'reject', 'Employee not eligible for ownership transfer');

-- --------------------------------------------------------

--
-- Table structure for table `rental_plan`
--

CREATE TABLE `rental_plan` (
  `level_id` int(11) NOT NULL,
  `level` varchar(50) NOT NULL,
  `mobile_usage_limits` int(11) NOT NULL,
  `vi_monthly_rental_plan` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rental_plan`
--

INSERT INTO `rental_plan` (`level_id`, `level`, `mobile_usage_limits`, `vi_monthly_rental_plan`) VALUES
(1, 'M-1', 4000, 399.00),
(2, 'M-2', 3000, 399.00),
(3, 'M-3 & M-4', 1500, 399.00),
(4, 'M-5 to M-7', 1000, 399.00),
(6, 'M-8 - TO M-11', 750, 399.00),
(8, 'Common', 500, 349.00);

-- --------------------------------------------------------

--
-- Table structure for table `sim_inventory`
--

CREATE TABLE `sim_inventory` (
  `SIM_ID` int(20) NOT NULL,
  `SIM_NUMBER` varchar(20) NOT NULL,
  `ICCID` varchar(20) DEFAULT NULL,
  `Mobile_Number` int(15) DEFAULT NULL,
  `Service_Provider` enum('VI','AIRTEL','OTHERS') NOT NULL,
  `SIM_TYPE` enum('Physical','eSIM') DEFAULT 'Physical',
  `Location` varchar(100) DEFAULT NULL,
  `Status` enum('AVAILABLE','ASSIGNED','ACTIVE','SUSPENDED','DEACTIVATED') DEFAULT 'AVAILABLE',
  `emp_id` int(11) DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sim_inventory`
--

INSERT INTO `sim_inventory` (`SIM_ID`, `SIM_NUMBER`, `ICCID`, `Mobile_Number`, `Service_Provider`, `SIM_TYPE`, `Location`, `Status`, `emp_id`, `assigned_at`, `created_at`, `updated_at`) VALUES
(1, 'VI898912345678901234', NULL, 2147483647, 'VI', 'Physical', 'Vadinar', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(2, 'VI898912345678901235', NULL, 2147483647, 'VI', 'Physical', 'Mumbai', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(3, 'VI898912345678901236', NULL, 2147483647, 'VI', 'Physical', 'Delhi', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(4, 'VI898912345678901237', NULL, 2147483647, 'VI', 'Physical', 'Ahmedabad', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(5, 'AT898912345678901238', NULL, 2147483647, 'AIRTEL', 'Physical', 'Hyderabad', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(6, 'AT898912345678901239', NULL, 2147483647, 'AIRTEL', 'Physical', 'Vadinar', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(7, 'VI898912345678901240', NULL, 2147483647, 'VI', 'Physical', 'Mumbai', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(8, 'VI898912345678901241', NULL, 2147483647, 'VI', 'Physical', 'Delhi', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(9, 'VI898912345678901242', NULL, 2147483647, 'VI', 'Physical', 'Vadinar', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(10, 'AT898912345678901243', NULL, 2147483647, 'AIRTEL', 'Physical', 'Ahmedabad', 'AVAILABLE', NULL, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(11, 'VI898912345678901244', NULL, 2147483647, 'VI', 'Physical', 'Vadinar', 'ACTIVE', 7, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(12, 'VI898912345678901245', NULL, 2147483647, 'VI', 'Physical', 'Mumbai', 'ACTIVE', 8, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(13, 'AT898912345678901246', NULL, 2147483647, 'AIRTEL', 'Physical', 'Hyderabad', 'ACTIVE', 10, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(14, 'VI898912345678901247', NULL, 2147483647, 'VI', 'Physical', 'Delhi', 'ACTIVE', 2, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13'),
(15, 'VI898912345678901248', NULL, 2147483647, 'VI', 'Physical', 'Ahmedabad', 'ACTIVE', 9, NULL, '2026-03-05 05:34:12', '2026-03-05 05:34:13');

-- --------------------------------------------------------

--
-- Table structure for table `sim_issue`
--

CREATE TABLE `sim_issue` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `POI_DOC_NO` varchar(50) DEFAULT NULL,
  `POI_DOC_TYPE` varchar(50) DEFAULT NULL,
  `RENTAL_PLAN` varchar(50) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `act_by` int(11) DEFAULT NULL,
  `act_at` datetime DEFAULT NULL,
  `req_by` int(11) DEFAULT NULL,
  `req_at` datetime DEFAULT current_timestamp(),
  `MOBILE_NO_AL` varchar(15) DEFAULT NULL,
  `REMARKS` text DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `ADMIN_NOTES` text DEFAULT NULL,
  `POI` longblob DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sim_issue`
--

INSERT INTO `sim_issue` (`REQ_ID`, `EMP_ID`, `POI_DOC_NO`, `POI_DOC_TYPE`, `RENTAL_PLAN`, `SIM_NO`, `STATUS`, `act_by`, `act_at`, `req_by`, `req_at`, `MOBILE_NO_AL`, `REMARKS`, `REJECT_REASON`, `ADMIN_NOTES`, `POI`) VALUES
(1, 7, 'ABCD1234567890', 'Aadhaar', 'M-3 & M-4', 'VI898912345678901244', 'complete', 5, '2024-01-05 10:30:00', 2, '2024-01-01 09:00:00', '9876543311', 'New joinee SIM allocation', NULL, NULL, NULL),
(2, 8, 'EFGH1234567890', 'Aadhaar', 'M-3 & M-4', 'VI898912345678901245', 'complete', 5, '2024-01-10 11:15:00', 2, '2024-01-06 10:00:00', '9876543312', 'Promotion - SIM upgrade', NULL, NULL, NULL),
(3, 11, 'IJKL1234567890', 'Aadhaar', 'M-8 - TO M-11', 'VI898912345678901242', 'complete', NULL, NULL, 2, '2024-01-20 14:30:00', '9876543309', 'New trainee', NULL, NULL, NULL),
(4, 12, 'MNOP1234567890', 'Aadhaar', 'M-8 - TO M-11', NULL, 'reject', 5, '2024-01-18 16:45:00', 2, '2024-01-15 11:00:00', '9876543310', 'Document verification pending', 'Aadhaar document unclear, please resubmit', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `sim_replacement`
--

CREATE TABLE `sim_replacement` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `REQ_BY` int(11) DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sim_replacement`
--

INSERT INTO `sim_replacement` (`REQ_ID`, `EMP_ID`, `SIM_NO`, `REASON`, `REJECT_REASON`, `REQ_BY`, `REQ_AT`, `ACT_AT`, `ACT_BY`, `STATUS`) VALUES
(1, 7, 'VI898912345678901244', 'SIM card damaged', NULL, 7, '2024-01-15 13:45:00', '2024-01-16 10:30:00', 5, 'complete'),
(2, 10, 'AT898912345678901246', 'Lost SIM card', 'Need to file police complaint first', 10, '2024-01-18 09:15:00', '2024-01-19 14:00:00', 5, 'reject'),
(3, 9, 'VI898912345678901248', 'Switching to dual SIM phone', NULL, 9, '2024-01-22 11:30:00', NULL, NULL, 'complete');

-- --------------------------------------------------------

--
-- Table structure for table `temporary_suspension`
--

CREATE TABLE `temporary_suspension` (
  `REQ_ID` int(20) NOT NULL,
  `EMP_ID` int(11) DEFAULT NULL,
  `REASON` text DEFAULT NULL,
  `SIM_NO` varchar(20) DEFAULT NULL,
  `REJECT_REASON` text DEFAULT NULL,
  `STATUS` enum('complete','pending','reject') DEFAULT 'pending',
  `REQ_BY` int(11) DEFAULT NULL,
  `REQ_AT` datetime DEFAULT current_timestamp(),
  `ACT_AT` datetime DEFAULT NULL,
  `ACT_BY` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `temporary_suspension`
--

INSERT INTO `temporary_suspension` (`REQ_ID`, `EMP_ID`, `REASON`, `SIM_NO`, `REJECT_REASON`, `STATUS`, `REQ_BY`, `REQ_AT`, `ACT_AT`, `ACT_BY`) VALUES
(1, 7, 'Phone lost during travel', 'VI898912345678901244', NULL, 'complete', 7, '2024-01-10 18:30:00', '2024-01-10 19:00:00', 5),
(2, 8, 'SIM card misplaced', 'VI898912345678901245', 'Already reported last month', 'reject', 8, '2024-01-20 16:45:00', '2024-01-21 10:30:00', 5),
(3, 10, 'International travel - security reasons', 'AT898912345678901246', NULL, 'complete', 10, '2024-01-25 14:00:00', NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activision_e_sim`
--
ALTER TABLE `activision_e_sim`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- Indexes for table `activision_of_new_sim`
--
ALTER TABLE `activision_of_new_sim`
  ADD PRIMARY KEY (`REQUEST_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `req_by` (`req_by`),
  ADD KEY `act_by` (`act_by`),
  ADD KEY `fk_new_sim_employee_level` (`level_id`);

--
-- Indexes for table `admin_actions`
--
ALTER TABLE `admin_actions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `air_for_personal_use`
--
ALTER TABLE `air_for_personal_use`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- Indexes for table `air_offical_usage`
--
ALTER TABLE `air_offical_usage`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- Indexes for table `deactivision_of_sim`
--
ALTER TABLE `deactivision_of_sim`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `ACT_BY` (`ACT_BY`),
  ADD KEY `REQ_BY` (`REQ_BY`);

--
-- Indexes for table `isd_activision`
--
ALTER TABLE `isd_activision`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- Indexes for table `master_employee`
--
ALTER TABLE `master_employee`
  ADD PRIMARY KEY (`ID`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `porting_cocp`
--
ALTER TABLE `porting_cocp`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `ACT_BY` (`ACT_BY`),
  ADD KEY `REQ_BY` (`REQ_BY`);

--
-- Indexes for table `rental_plan`
--
ALTER TABLE `rental_plan`
  ADD PRIMARY KEY (`level_id`);

--
-- Indexes for table `sim_inventory`
--
ALTER TABLE `sim_inventory`
  ADD PRIMARY KEY (`SIM_ID`),
  ADD UNIQUE KEY `SIM_NUMBER` (`SIM_NUMBER`),
  ADD KEY `emp_id` (`emp_id`);

--
-- Indexes for table `sim_issue`
--
ALTER TABLE `sim_issue`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `act_by` (`act_by`),
  ADD KEY `req_by` (`req_by`),
  ADD KEY `SIM_NO` (`SIM_NO`);

--
-- Indexes for table `sim_replacement`
--
ALTER TABLE `sim_replacement`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- Indexes for table `temporary_suspension`
--
ALTER TABLE `temporary_suspension`
  ADD PRIMARY KEY (`REQ_ID`),
  ADD KEY `EMP_ID` (`EMP_ID`),
  ADD KEY `REQ_BY` (`REQ_BY`),
  ADD KEY `ACT_BY` (`ACT_BY`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_actions`
--
ALTER TABLE `admin_actions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `master_employee`
--
ALTER TABLE `master_employee`
  MODIFY `ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activision_e_sim`
--
ALTER TABLE `activision_e_sim`
  ADD CONSTRAINT `activision_e_sim_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `activision_e_sim_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `activision_e_sim_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `activision_of_new_sim`
--
ALTER TABLE `activision_of_new_sim`
  ADD CONSTRAINT `activision_of_new_sim_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `activision_of_new_sim_ibfk_2` FOREIGN KEY (`req_by`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `activision_of_new_sim_ibfk_3` FOREIGN KEY (`act_by`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `fk_new_sim_employee_level` FOREIGN KEY (`level_id`) REFERENCES `master_employee` (`level_id`);

--
-- Constraints for table `air_for_personal_use`
--
ALTER TABLE `air_for_personal_use`
  ADD CONSTRAINT `air_for_personal_use_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `air_for_personal_use_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `air_for_personal_use_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `air_offical_usage`
--
ALTER TABLE `air_offical_usage`
  ADD CONSTRAINT `air_offical_usage_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `air_offical_usage_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `air_offical_usage_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `deactivision_of_sim`
--
ALTER TABLE `deactivision_of_sim`
  ADD CONSTRAINT `deactivision_of_sim_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `deactivision_of_sim_ibfk_2` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `deactivision_of_sim_ibfk_3` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `isd_activision`
--
ALTER TABLE `isd_activision`
  ADD CONSTRAINT `isd_activision_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `isd_activision_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `isd_activision_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `porting_cocp`
--
ALTER TABLE `porting_cocp`
  ADD CONSTRAINT `porting_cocp_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `porting_cocp_ibfk_2` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `porting_cocp_ibfk_3` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `sim_inventory`
--
ALTER TABLE `sim_inventory`
  ADD CONSTRAINT `sim_inventory_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `sim_issue`
--
ALTER TABLE `sim_issue`
  ADD CONSTRAINT `sim_issue_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `sim_issue_ibfk_2` FOREIGN KEY (`act_by`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `sim_issue_ibfk_3` FOREIGN KEY (`req_by`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `sim_issue_ibfk_4` FOREIGN KEY (`SIM_NO`) REFERENCES `sim_inventory` (`SIM_NUMBER`);

--
-- Constraints for table `sim_replacement`
--
ALTER TABLE `sim_replacement`
  ADD CONSTRAINT `sim_replacement_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `sim_replacement_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `sim_replacement_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);

--
-- Constraints for table `temporary_suspension`
--
ALTER TABLE `temporary_suspension`
  ADD CONSTRAINT `temporary_suspension_ibfk_1` FOREIGN KEY (`EMP_ID`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `temporary_suspension_ibfk_2` FOREIGN KEY (`REQ_BY`) REFERENCES `master_employee` (`ID`),
  ADD CONSTRAINT `temporary_suspension_ibfk_3` FOREIGN KEY (`ACT_BY`) REFERENCES `master_employee` (`ID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
