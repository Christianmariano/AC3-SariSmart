-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 08, 2025 at 12:51 AM
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
-- Database: `ac_sarismart`
--

-- --------------------------------------------------------

--
-- Table structure for table `daily_records`
--

CREATE TABLE `daily_records` (
  `id` int(10) NOT NULL,
  `income_store` float(10,2) NOT NULL,
  `income_school_service` float(10,2) NOT NULL,
  `expense_store` float(10,2) NOT NULL,
  `expense_school_service` float(10,2) NOT NULL,
  `date` date NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `saving` float(10,2) NOT NULL,
  `total_sale` decimal(10,2) NOT NULL,
  `gcash` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `daily_records`
--

INSERT INTO `daily_records` (`id`, `income_store`, `income_school_service`, `expense_store`, `expense_school_service`, `date`, `updated_at`, `saving`, `total_sale`, `gcash`) VALUES
(95, 4342.00, 30.00, 90.00, 10.00, '2025-09-12', '2025-09-13 07:17:21', 200.00, 0.00, 0.00),
(100, 444.00, 456.00, 46.00, 33.00, '2025-09-25', '2025-09-25 13:48:56', 566.00, 0.00, 0.00),
(101, 1231.00, 2324.00, 34343.00, 34.00, '2025-09-25', '2025-09-25 09:50:00', 4343.00, 0.00, 0.00),
(102, 1.00, 1.00, 1.00, 1.00, '2025-09-25', '2025-09-25 09:54:31', 1.00, 0.00, 0.00),
(103, 23424.00, 111.00, 12.00, 12.00, '2025-10-02', '2025-10-02 04:56:47', 1000.00, 0.00, 0.00),
(107, 0.00, 0.00, 0.00, 0.00, '2025-10-08', '2025-10-07 22:18:32', 0.00, 0.00, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `income_summary`
--

CREATE TABLE `income_summary` (
  `total_income` float(10,2) NOT NULL,
  `total_expense` float(10,2) NOT NULL,
  `net_income` float(10,2) NOT NULL,
  `date` date NOT NULL,
  `id` int(10) NOT NULL,
  `saving` float(10,2) NOT NULL,
  `gcash` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `income_summary`
--

INSERT INTO `income_summary` (`total_income`, `total_expense`, `net_income`, `date`, `id`, `saving`, `gcash`) VALUES
(4372.00, 100.00, 4472.00, '2025-09-12', 25, 200.00, 0.00),
(4457.00, 34458.00, -25091.00, '2025-09-25', 29, 4910.00, 0.00),
(23535.00, 24.00, 24511.00, '2025-10-02', 30, 1000.00, 0.00),
(0.00, 0.00, 8.00, '2025-10-08', 34, 0.00, 8.00);

-- --------------------------------------------------------

--
-- Table structure for table `login`
--

CREATE TABLE `login` (
  `id` int(10) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `confirm_password` varchar(255) NOT NULL,
  `new_password` varchar(255) NOT NULL,
  `profile_image` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login`
--

INSERT INTO `login` (`id`, `username`, `password`, `confirm_password`, `new_password`, `profile_image`, `created_at`, `email`) VALUES
(1, 'Admin', '$2y$10$Ep6C0VTiCnGv2TiVi0FmOemOE09sAqS1wcXOvdzBlVjitpVUzqHU.', '$2y$10$vT6huUrzVyQjz4Ja9Sl67elvv4Tiq2wMPl1nzc8eTMLPdzx0SARE6', '$2y$10$Ep6C0VTiCnGv2TiVi0FmOemOE09sAqS1wcXOvdzBlVjitpVUzqHU.', 'wp4174051-win-wallpapers.jpg', '2025-09-25 09:37:33', 'christianmariano1024@gmail.com');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(10) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `barcode` varchar(255) NOT NULL,
  `quantity` int(10) NOT NULL,
  `create_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `quantity_sale` int(10) NOT NULL,
  `price` float(10,2) NOT NULL,
  `sale` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL,
  `sale_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `product_name`, `image`, `barcode`, `quantity`, `create_at`, `quantity_sale`, `price`, `sale`, `status`, `sale_amount`) VALUES
(119, 'chocolate', 'default_image.png', 'chocolate-133-11.00', 0, '2025-10-07 22:38:47', 0, 11.00, 1353.00, 'sold out', 1353.00);

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(10) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sale_amount` decimal(10,2) NOT NULL,
  `totalSales` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `created_at`, `sale_amount`, `totalSales`) VALUES
(0, '2025-09-27 18:52:54', 143.00, 0.00);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `daily_records`
--
ALTER TABLE `daily_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `income_summary`
--
ALTER TABLE `income_summary`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `login`
--
ALTER TABLE `login`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `daily_records`
--
ALTER TABLE `daily_records`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `income_summary`
--
ALTER TABLE `income_summary`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(10) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=120;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
