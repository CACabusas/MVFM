-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 18, 2025 at 04:13 AM
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
-- Database: `mvfm`
--

-- --------------------------------------------------------

--
-- Table structure for table `accidents`
--

CREATE TABLE `accidents` (
  `vehicle_id` int(65) NOT NULL,
  `date` date NOT NULL,
  `description` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `acquisition_costs`
--

CREATE TABLE `acquisition_costs` (
  `id` int(65) NOT NULL,
  `vehicle_id` int(65) NOT NULL,
  `acquisition_cost` decimal(65,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `assessment_id` int(65) NOT NULL,
  `vehicle_id` int(65) NOT NULL,
  `fuel_consumption` decimal(65,2) NOT NULL,
  `fuel_cost` decimal(65,2) NOT NULL,
  `distance_travelled` decimal(65,2) NOT NULL,
  `maintenance_cost` decimal(65,2) NOT NULL,
  `no_of_maintenance` int(65) NOT NULL,
  `no_of_accidents` int(65) NOT NULL,
  `c_maintenance_cost` decimal(65,2) NOT NULL,
  `p_maintenance_cost` decimal(65,2) NOT NULL,
  `km_per_liter` decimal(65,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact`
--

CREATE TABLE `contact` (
  `contact_id` int(65) NOT NULL,
  `name` varchar(255) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `contact_type` enum('Driver','Supplier','End-User') NOT NULL,
  `number` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact`
--

INSERT INTO `contact` (`contact_id`, `name`, `image_url`, `contact_type`, `number`, `email`) VALUES
(1, 'Test', 'uploads/404.png', 'Driver', '123456', 'test@test.com'),
(5, 'I got a glock in my \'rari', 'contact/contact_68395a768704f1.53190054.png', 'End-User', '1234567890', 'johnsnow@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `drivers`
--

CREATE TABLE `drivers` (
  `driver_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `license_number` varchar(20) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `drivers`
--

INSERT INTO `drivers` (`driver_id`, `first_name`, `last_name`, `license_number`, `contact_number`, `status`, `created_at`) VALUES
(1, 'don', '', NULL, NULL, 'active', '2025-07-17 23:03:10'),
(2, '', '', NULL, NULL, 'active', '2025-07-17 23:04:08'),
(3, 'don', 'pol', NULL, NULL, 'active', '2025-07-18 00:28:33'),
(4, 'rain', 'drop', NULL, NULL, 'active', '2025-07-18 00:29:21'),
(5, 'ball', 'pen', NULL, NULL, 'active', '2025-07-18 01:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `driver_schedules`
--

CREATE TABLE `driver_schedules` (
  `schedule_id` int(11) NOT NULL,
  `driver_id` int(11) NOT NULL,
  `driver_name` varchar(100) DEFAULT NULL,
  `vehicle_id` int(11) DEFAULT NULL,
  `start_datetime` datetime NOT NULL,
  `end_datetime` datetime NOT NULL,
  `purpose` varchar(255) NOT NULL,
  `destination` varchar(255) DEFAULT NULL,
  `status` enum('scheduled','in-progress','completed','cancelled') DEFAULT 'scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `driver_schedules`
--

INSERT INTO `driver_schedules` (`schedule_id`, `driver_id`, `driver_name`, `vehicle_id`, `start_datetime`, `end_datetime`, `purpose`, `destination`, `status`, `created_at`) VALUES
(5, 1, NULL, 10, '2025-07-18 02:30:00', '2025-07-18 03:30:00', 'trip', NULL, 'in-progress', '2025-07-17 23:03:57'),
(7, 1, NULL, 10, '2025-07-17 19:00:00', '2025-07-17 20:00:00', 'trip', NULL, 'scheduled', '2025-07-17 23:04:22'),
(12, 3, NULL, 10, '2025-07-15 00:00:00', '2025-07-15 01:00:00', 'trip', NULL, 'completed', '2025-07-18 00:28:33'),
(13, 4, NULL, 10, '2025-07-14 00:00:00', '2025-07-14 09:00:00', 'trip', NULL, 'completed', '2025-07-18 00:29:21'),
(14, 5, NULL, 10, '2025-07-16 00:00:00', '2025-07-16 01:00:00', 'trip', NULL, 'scheduled', '2025-07-18 01:21:14');

-- --------------------------------------------------------

--
-- Table structure for table `forms`
--

CREATE TABLE `forms` (
  `form_id` int(65) NOT NULL,
  `form_name` varchar(255) NOT NULL,
  `form_url` varchar(255) NOT NULL,
  `form_type` enum('form','policy') NOT NULL DEFAULT 'form'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `forms`
--

INSERT INTO `forms` (`form_id`, `form_name`, `form_url`, `form_type`) VALUES
(1, 'Trip Ticket', 'resources/TripTicket.pdf', 'form'),
(2, 'Fuel Requisition Slip', 'resources/wrs.pdf', 'form'),
(4, 'Test', 'https://docs.google.com/document/d/1MxufDwxPMrC3qt9lfGVHx6OhL3vhS_4N5qON8n0LWqs/edit?usp=sharing', 'policy');

-- --------------------------------------------------------

--
-- Table structure for table `maintenance`
--

CREATE TABLE `maintenance` (
  `maintenance_id` int(65) NOT NULL,
  `vehicle_id` int(65) NOT NULL,
  `date` date NOT NULL,
  `frequency` int(65) NOT NULL,
  `type` enum('Corrective','Preventive','Mixed') NOT NULL,
  `repair_shop` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(65,2) NOT NULL,
  `file_type` varchar(255) DEFAULT NULL,
  `file_type_value` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance`
--

INSERT INTO `maintenance` (`maintenance_id`, `vehicle_id`, `date`, `frequency`, `type`, `repair_shop`, `description`, `cost`, `file_type`, `file_type_value`) VALUES
(1, 10, '2025-04-01', 10, 'Corrective', 'Princess Peach', 'Pailis og sticker sa windshield og sa likod', 500.00, NULL, NULL),
(2, 10, '2025-06-04', 4, 'Preventive', 'June-ah', 'Tire replacement', 2500.00, NULL, NULL),
(4, 11, '2025-06-09', 28, 'Corrective', 'ISTD Dentistry', 'Nagpa ayo ko sa akong ngipon, pero nagpa postiso nalang ko', 7000.00, NULL, NULL),
(5, 11, '2025-06-07', 1, 'Preventive', '7Eleven', 'Buying of brake pads, brake shoes, brake cleaner, and brake fluid', 13921.87, NULL, NULL),
(6, 11, '2025-02-13', 3, 'Mixed', 'PROLINK', 'Replace serpentine belt:preventive,Transmission and differential oil:corrective', 7500.00, '', ''),
(7, 10, '2025-01-03', 12, 'Corrective', 'PETRUS', 'Pailis og tubig sa banyo', 10.00, NULL, NULL),
(8, 10, '2025-03-07', 7, 'Corrective', 'Star Rail', 'March 7', 307.00, NULL, NULL),
(10, 11, '2025-06-16', 0, 'Preventive', 'N-Vision', 'Test 1, Test 2, Test 3, Test 4', 1234.00, 'Purchase Order Number', '123456'),
(13, 11, '2025-01-01', 0, 'Mixed', 'Test', 'Test 1:preventive,Test 2:preventive,Test 3:corrective', 1234.00, 'Purchase Order Number', '1234'),
(15, 11, '2025-01-01', 0, 'Mixed', 'Commas', 'Test 1:preventive,test 2:preventive,test 3:preventive,Test 4:corrective', 1234.00, 'Purchase Order Number', '1234'),
(16, 11, '2019-10-01', 0, 'Mixed', 'Lorem ipsum', 'Lorem:preventive,ipsum:preventive,dolor:corrective,sit:corrective,amet:corrective', 100.00, 'Contract Number', '10012019'),
(17, 11, '2018-01-01', 0, 'Preventive', 'sdfgh', 'c:preventive', 2.00, 'Purchase Order Number', '1123');

-- --------------------------------------------------------

--
-- Table structure for table `mileage`
--

CREATE TABLE `mileage` (
  `mileage_id` int(65) NOT NULL,
  `vehicle_id` int(65) NOT NULL,
  `date` date NOT NULL,
  `odometer_begin` int(65) NOT NULL,
  `odometer_end` int(65) NOT NULL,
  `distance` int(65) NOT NULL,
  `liters` int(65) NOT NULL,
  `cost` decimal(65,2) NOT NULL,
  `invoice` varchar(255) NOT NULL,
  `station` varchar(255) NOT NULL,
  `driver` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mileage`
--

INSERT INTO `mileage` (`mileage_id`, `vehicle_id`, `date`, `odometer_begin`, `odometer_end`, `distance`, `liters`, `cost`, `invoice`, `station`, `driver`) VALUES
(7, 10, '2025-05-20', 148096, 148229, 133, 45, 2700.00, '123456', 'Seaoil', 'Peach'),
(8, 10, '2025-05-22', 148229, 148461, 232, 5, 300.00, '234567', 'Seaoil', 'Jane'),
(9, 11, '2025-03-07', 149496, 149700, 204, 0, 0.00, '', '', 'Sir Edgar'),
(10, 11, '2025-03-08', 149700, 149900, 200, 40, 2280.00, '123456', 'Shoppe 24', 'Sir Edgar'),
(11, 11, '2025-05-01', 149900, 150300, 400, 40, 2360.00, '101010', 'DieSELL', 'Sir Edgar'),
(13, 11, '2025-05-03', 150300, 150400, 100, 0, 0.00, '', '', 'Sir Edgar');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `user_type` enum('officer','admin') NOT NULL,
  `password_hash` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `user_type`, `password_hash`) VALUES
(1, 'officer', '$2y$10$4kM7xGBM0IA3V4liXW7IV.pIfsCw7cW0.R2LjUS/cSKKrcPOlpKoy'),
(2, 'admin', '$2y$10$9v6FJbne2m6oGB5ru4UKqufzIKUmucuV2KPULRTPpEfjFb2JLHA4q');

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

CREATE TABLE `vehicles` (
  `vehicle_id` int(65) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `type` varchar(255) NOT NULL,
  `brand` varchar(255) NOT NULL,
  `model` varchar(255) NOT NULL,
  `year` year(4) NOT NULL,
  `age` int(65) NOT NULL,
  `plate` varchar(255) NOT NULL,
  `lto_mv` varchar(255) NOT NULL,
  `npc_mv` varchar(255) NOT NULL,
  `reg_no` varchar(255) NOT NULL,
  `reg_exp` date NOT NULL,
  `chassis` varchar(255) NOT NULL,
  `engine` varchar(255) NOT NULL,
  `fuel` varchar(255) NOT NULL,
  `assignment` varchar(255) NOT NULL,
  `cost` decimal(65,2) NOT NULL,
  `acquisition` date NOT NULL,
  `par_to` varchar(255) NOT NULL,
  `position` varchar(255) NOT NULL,
  `salary_grade` int(65) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `vehicles`
--

INSERT INTO `vehicles` (`vehicle_id`, `image_url`, `type`, `brand`, `model`, `year`, `age`, `plate`, `lto_mv`, `npc_mv`, `reg_no`, `reg_exp`, `chassis`, `engine`, `fuel`, `assignment`, `cost`, `acquisition`, `par_to`, `position`, `salary_grade`) VALUES
(7, '1748246483_Screenshot 2025-03-18 101629.png', 'He', 'Haha', 'Hehehe', '2025', 0, '555555', '555555', '555555', '555555', '2025-05-05', '555555', '555555', '555555', 'Hehehehe', 555555.00, '2025-05-05', 'Hehehehehe', 'Hehehehehehe', 555555),
(10, '1749437240_happy life 2.png', 'Van', 'Toyota', 'Grandia (Test)', '2017', 7, 'SAB 6821', '1312-00000417270', '3657', 'ABC-1234', '2025-06-08', 'JTFRT13P6G8010449', '1KD2675164', 'Diesel', 'ISTD', 1076844.00, '2018-12-28', 'Angel Peach R. Rico', 'Manager', 20),
(11, '1749437778_happy life.png', 'Pickup', 'Mitsubishi', 'Strada (Test)', '2018', 5, 'SAB 8501', '1312-00000428574', '3746', 'ZYX-9876', '2025-06-28', 'MMBJNKK30JH050848', '4D56UAU0986', 'Diesel', 'OM', 1076844.00, '2018-12-28', 'Ronald Alan Salazar', 'CSO-C', 22);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accidents`
--
ALTER TABLE `accidents`
  ADD PRIMARY KEY (`vehicle_id`);

--
-- Indexes for table `acquisition_costs`
--
ALTER TABLE `acquisition_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `contact`
--
ALTER TABLE `contact`
  ADD PRIMARY KEY (`contact_id`);

--
-- Indexes for table `drivers`
--
ALTER TABLE `drivers`
  ADD PRIMARY KEY (`driver_id`);

--
-- Indexes for table `driver_schedules`
--
ALTER TABLE `driver_schedules`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `driver_id` (`driver_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `forms`
--
ALTER TABLE `forms`
  ADD PRIMARY KEY (`form_id`);

--
-- Indexes for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD PRIMARY KEY (`maintenance_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `mileage`
--
ALTER TABLE `mileage`
  ADD PRIMARY KEY (`mileage_id`),
  ADD KEY `vehicle_id` (`vehicle_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`vehicle_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accidents`
--
ALTER TABLE `accidents`
  MODIFY `vehicle_id` int(65) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `acquisition_costs`
--
ALTER TABLE `acquisition_costs`
  MODIFY `id` int(65) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `assessment_id` int(65) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact`
--
ALTER TABLE `contact`
  MODIFY `contact_id` int(65) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `drivers`
--
ALTER TABLE `drivers`
  MODIFY `driver_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `driver_schedules`
--
ALTER TABLE `driver_schedules`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `forms`
--
ALTER TABLE `forms`
  MODIFY `form_id` int(65) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `maintenance`
--
ALTER TABLE `maintenance`
  MODIFY `maintenance_id` int(65) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `mileage`
--
ALTER TABLE `mileage`
  MODIFY `mileage_id` int(65) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `vehicle_id` int(65) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `acquisition_costs`
--
ALTER TABLE `acquisition_costs`
  ADD CONSTRAINT `acquisition_costs_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `assessment_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `driver_schedules`
--
ALTER TABLE `driver_schedules`
  ADD CONSTRAINT `driver_schedules_ibfk_1` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`driver_id`),
  ADD CONSTRAINT `driver_schedules_ibfk_2` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `maintenance`
--
ALTER TABLE `maintenance`
  ADD CONSTRAINT `maintenance_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);

--
-- Constraints for table `mileage`
--
ALTER TABLE `mileage`
  ADD CONSTRAINT `mileage_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicle_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
