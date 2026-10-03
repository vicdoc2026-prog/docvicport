-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Aug 26, 2025 at 03:56 AM
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
-- Database: `doc_vic`
--

-- --------------------------------------------------------

--
-- Table structure for table `land_title`
--

CREATE TABLE `land_title` (
  `land_title_id` int(11) NOT NULL,
  `ref_no` varchar(255) NOT NULL,
  `ARP/TD No.` varchar(100) NOT NULL,
  `lot_no` varchar(255) NOT NULL,
  `area` varchar(255) NOT NULL,
  `land_owner` varchar(50) NOT NULL,
  `registered_degree` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `acquisition_cost` varchar(255) NOT NULL,
  `CLOAN` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `land_title`
--

INSERT INTO `land_title` (`land_title_id`, `ref_no`, `ARP/TD No.`, `lot_no`, `area`, `land_owner`, `registered_degree`, `location`, `acquisition_cost`, `CLOAN`) VALUES
(1, '130-2018001715', '05-0014-00847', '7048, PSL-248', '740sqm.', 'SIBUGAY TECHNICAL INSTITUTE INC. ', 'SIBUGAY TECHNICAL INSTITUTE INC. ', 'lower taway, ipil, ZSP', '370,000.00', ''),
(2, '130-2021001109', '05-0001-01668', '3972', '1024sqm.', 'DR. EUFEMIO JAVIER JR. ', '', 'lower taway, ipil, ZSP', '500,000.00', ''),
(3, 'TCT# 52,848', '130-2014001052', '4075-A, CSD-1069', '166sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'lower taway, ipil, ZSP', '85,000.00', ''),
(4, '(P-55781) 130-2018000249', 'OCT# P-55,781', '4077, PLS-248', '755sqm.', 'DR. EUFEMIO JAVIER JR.', '', 'lower taway, ipil, ZSP', '375,000.00', ''),
(5, 'OCT# 130-2018000249', 'TCT# 45,741', '1009-A, Psd-09-047127', '120sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'Guito-an, ipil, ZSP', '35,000.00', ''),
(6, 'TCT# 45,757', 'TCT# 45,757', '1000-A, Psd-09-047127', '120sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'Guito-an, ipil, ZSP', '35,000.00', ''),
(7, 'T-45,742', 'OCT# 0-6,427', '1009-A, Psd-09-047127', '120sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'Guito-an, ipil,ZSP', '', ''),
(8, 'T-30,013', 'OCT# 0-5,011', '1256, Pls-248', '246sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'ipil heights townsite,ipil,ZSP', '', ''),
(9, 'E-31,741', '', '1369-G, Csd-09-004100-D-AR/CA', '9,050sqm.', 'Isabelo Divino', '', 'tiayon,ipil,ZSP', '', ''),
(10, 'TCT# GARP2017000151', '', '650-A, Psd-09-075290-AR', '21,770sqm.', 'DR. EUFEMIO JAVIER JR. MARIA BELLA C. JAVIER ', '', 'upper pangi, ipil, ZSP', '', ''),
(11, 'CLOA No. 00361667', 'TCT# 26,376', '', '30,000sqm.', 'Ma. Bella C. Javier & Doc. Eufemio D. Javier Jr.', '', 'Upper Pangi, ipil, ZSP', '575,000.00', ''),
(12, 'CLOA No. 00361669', 'TCT# E-25,160', '', '30,000sqm.', 'Wilfredo Deocampo', '', 'Upper Pangi, ipil, ZSP', '250,000.00', ''),
(13, 'Lot 4069, PLS E9-B', 'TCT # T-6,235', '', '762sqm.', 'Ma. Bella C. Javier ', '', 'Lower Taway, Ipil, ZSP', '380,000.00', ''),
(14, 'Pat.097309-98-741P', 'OCT# P-42,944', '', '51,000sqm.', 'Luvila D. Javier', '', 'Poblacion, Ipil, ZSP', '450,000.00', ''),
(15, 'CLOA #00364321', 'TCT# T-25,159', '', '30,001sqm.', 'Amparo Deocampo', '', 'Upper pangi, ipil, ZSP', '300,000.00', ''),
(16, 'Lot 733-B, Psd-60587', 'TCT# T-28,090', '', '10,000sqm.', 'Eufemio D. Javier Jr.', '', 'Poblacion, Ipil, ZSP', '285,000.00', ''),
(17, 'CLOA# 00364321', 'TCT# E-31,243', '', '450sqm.', 'Eufemio D. Javier Jr.', '', 'Sanito, ipil, ZSP', '150,000.00', ''),
(18, 'TCT# 130-2022001485', 'TCT# T-14,392', '', '1,200sqm.', 'Ma. Bella C. Javier', '', 'Sanito, Ipil, ZSP', '600,000.00', ''),
(19, 'Lot 728-A-9-A 09-001678', 'TCT# T-17,832', '', '1,774sqm.', 'Ma.Bella C. Javier', '', 'Sanito, Ipil, ZSP', '850,000.00', ''),
(20, 'TCT# 130-2023000274', 'Lot T29-B-2-T-1', '', '2,000sqm.', 'Eufemio D. Javier Jr.', '', 'Sanito, Ipil, ZSP', '750,000.00', ''),
(21, 'TCT# 130-2024000153', 'Lot 1156-C-2-T-2', '', '2,624sqm', 'Ma. Bella C. Javier', '', 'Taway, Ipil, ZSP', '450,000.00', ''),
(22, '1 unit Condo \"Victoria de Manila 2\" Manila***', 'OCT/TCT No.002-2018018216', '', '2,624sqm.', 'N/A', '', 'Manila', '2,267,680sqm.', ''),
(23, 'TCT# 130-2014001052', 'Lot 4076 PLS-248', '', '544sqm.', 'Eufemio D. Javier Jr.', '', 'Lower taway, ipil, ZSP', '275,000.00', ''),
(24, 'Lot 11-B-6-1 09-001553', 'TCT# T-36,664', '', '342sqm.', 'Eufemio D. Javier Jr.', '', 'Riverside, Kabasalan, ZSP', '175,000.00', ''),
(25, 'Lot# Gss-09-03-000070', 'TCT# T-27,235', '', '21,221sqm.', 'Rogelio P. Yao', '', 'sanghanan, Kabasalan, ZSP', '250,000.00', ''),
(26, 'TCT# T-32,462', 'TCT# T-32,462', 'Lot 10649, Csd-2751-D', '323,71sqm.', 'Ma.Bella C. Javier & Eufemio D. Javier Jr.', '', 'Lower taway, ipil, ZSP', '175,000.00', ''),
(27, 'Lot# 11. Gss-09-03-00007', 'TCT# T-27,234', '', '29,972sqm.', 'Rogelio P. yao', '', 'sanghanan, Kabasalan, ZSP', '200,000.00', ''),
(28, 'TCT# 130-2022001529', 'OCT-00-2847', '', '400sqm.', 'Ma. Bella C. Javier & Eufemio D. Javier Jr.', '', 'sanito, ipil, ZSP', '200,000.00', ''),
(29, '1 unit Condo \"FUTURA Vinta\" Zbga.City-Floor1 - A-005***', 'N/A', '', 'N/A', 'Eufemio D. Javier Jr.', '', 'Zamboanga City', '985,500.00', ''),
(30, 'Fishpond TD# 8949 05-0020-01911', 'N/A', '', '', 'Eufemio D. Javier Jr.', '', 'sanito, ipil, ZSP', '250,000.00', ''),
(31, '1 unit Condo \"Torre Lorenzo Development Corporation\" ', '', '', '', '', '', 'Taft Avenue, Malate, Manila', '3,750,000.00', ''),
(32, 'TD# 05-0014-00394', 'TCT# T-59,958', '', '748sqm.', 'Maisarah Ibra/Marie Ibra', '', 'Poblacion, ipil, ZSP', '250,000.00', ''),
(33, 'TCT 130-2020000821', 'OCt-0-5483', '', '756sqm.', 'Reynaldo Javier', '', 'Poblacion, Ipil, ZSP', '250,000.00', ''),
(34, 'TCT 130-2017001954', 'TCT# 28,090', '', '10,001sqm.', 'Luisa Duque', '', 'Poblacion, Ipil, ZSP', '1,000,000.00', ''),
(35, 'TCT 130-2018000361', 'TCT-15,822', '', '1,774sqm.', 'Edirose C. Javier', '', 'Taway, Ipil, ZSP', '450,000.00', ''),
(36, 'CARP 2020000225', 'CLOA 01501578', '', '2,771sqm.', 'Pelagio Pabillar', '', 'Guitu-an, Ipil, ZSP', '350,000.00', ''),
(37, 'Lot 4 Psb-09-053303', 'TCT-42,742', '', '120sqm.', 'Romel T. Benzon', '', 'BRGY. Guito-an, ipil, ZSP', '120,000.00', ''),
(38, 'TCT 130-2022000940', 'OCT-0-2847', '', '400sqm.', 'Eufemio D. Javier', '', 'sanito, ipil, ZSP', '200,000.00', ''),
(39, 'TCT 130-2022000941', 'OCT-0-2847', '', '400sqm.', 'Isagani C. Balladares', '', 'Sanito, ipil, ZSP', '200,000.00', ''),
(40, 'TCT 130-2022001042', 'OCT-0-2749', '', '400sqm.', 'Isagani C. Balladares', '', 'Sanito, ipil, ZSP', '200,000.00', ''),
(41, 'TCT 130-2022001529', 'OCt-0-2847', '', '400sqm.', 'Eufemio D. Javier Jr.', '', 'Sanito, ipil, ZSp', '200,000.00', ''),
(42, 'PSD-09-028862-AR/VOS', 'E 1,193 Lot 729', '', '20,000sqm.', 'Helen Rabanes', '', 'Pangi, Ipil, ZSP', '24,000,000.00', ''),
(43, 'TCT#130-2023000274', '', '728-B-2-I-1', 'N/A', 'DR. EUFEMIO JAVIER JR.', '', 'Sanito, ipil, Zamboanga Sibugay', '', ''),
(44, 'TCT#130-2020000821', '', '', '756sqm', 'DR. Eufemio D. Javier Jr.', '', 'lower taway, ipil, Zamboanga Sibugay', '', ''),
(45, 'OCT#E-17,011', '', '4496, Pls-248, C-1', '92,612sqm.', 'Romulo Pugoy', '', 'Gango, RT.Lim,ZSP', '', '');

-- --------------------------------------------------------

--
-- Table structure for table `lto`
--

CREATE TABLE `lto` (
  `id` int(11) NOT NULL,
  `mv_file_no` varchar(25) DEFAULT NULL,
  `code` varchar(50) DEFAULT NULL,
  `make` varchar(50) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `plate_no` varchar(20) DEFAULT NULL,
  `due_date_actual` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lto`
--

INSERT INTO `lto` (`id`, `mv_file_no`, `code`, `make`, `type`, `plate_no`, `due_date_actual`) VALUES
(27, '1301-00000335249', 'BUS-3', 'KING LONG', 'TRUCK/BUS', 'ALA9721', '2026-01-05'),
(28, '1017-00008169146', 'HILUX RC', 'TOYOTA', 'HILUX PICK UP', 'KAQ1741', '2026-01-05'),
(29, '0901-00000672243', 'MOTOR', 'HONDA BEAT', 'MOTORCYCLE', '872JOQ', '2026-01-05'),
(30, '1301-00001846090', 'DT-12', 'HOWO A7', 'DUMP TRUCK', 'NFL7842', '2026-02-05'),
(31, '1241-00000080643', 'DT-8', 'HYUNDAI', 'DUMP TRUCK', 'MFU582', '2026-02-05'),
(32, '1017-00000015915', 'DT-9', 'HYUNDAI', 'DUMP TRUCK', '122602', '2026-02-05'),
(33, '1301-00001733323', 'DT-13', 'HOWO A7', 'DUMP TRUCK', 'NEV4433', '2026-03-05'),
(34, '1017-00000015915', 'TM-2', 'ISUZU', 'TRANSIT MIXER', 'KGT 634', '2026-04-05'),
(35, '1301-00000619782', 'TM-3', 'HOWO', 'CONCRETE MIXER TRUCK', 'NCZ8114', '2026-04-05'),
(36, '0901-00000678429', 'HILUX', 'TOYOTA', 'HILUX PICK UP', 'JAE6784', '2026-04-05'),
(37, '1301-00001492148', 'BUS2', 'KING LONG', 'TRUCK/BUS', 'NFZ1415', '2026-05-05'),
(38, '0912-00000063877', 'JEEP', 'MITSUBISHI', 'JEEP', 'JCY825', '2026-05-05'),
(39, '0901-00000880138', 'MAXIMA', 'BAJAJ', 'MAXIMA CARGO', 'J125AG', '2026-05-05'),
(40, '0716-00000105111', 'DT-5', 'ASIA', 'DUMP TRUCK', 'T615T', '2026-05-05'),
(41, '0301-00000923863', 'DT-11', 'FUSO', 'DUMP TRUCK', 'CAO-7526', '2026-06-05'),
(42, '0907-00006354177', 'TM-1', 'ISUZU', 'TRANSIT MIXER', 'JDK 147', '2026-07-05'),
(43, '0901-00000271524', 'BUS-1', 'HYUNDAI', 'COUNTY MINIBUS', 'JAA-4927', '2026-07-05'),
(44, '0716-00000108634', 'ELF', 'MITSUBISHI', 'DROPSIDE', 'GHZ 737', '2026-07-05'),
(45, '1001-00000284639', 'AVANZA', 'TOYOTA', 'WAGON 1.3E MT', 'KAB4618', '2026-08-05'),
(46, '0908-00003351933', 'DT-10', 'MITSUBISHI', 'DUMP TRUCK', '90808', '2026-08-05'),
(47, '0716-147813', 'MD', 'FUSO', 'MINI DUMP', 'JBK 808', '2025-08-11'),
(48, '0724-00000111872', 'BT', 'ISUZU', 'ELF CARGO BOOM TRUCK', 'YGU 648', '2025-08-25'),
(49, '1312-00000117019', 'DT-7', 'MITSUBISHI', 'DUMP TRUCK', 'UMG 110', '2025-10-06'),
(50, '0386-00000092679', 'SL', 'ISUZU', 'SELF LOADING', 'RBZ 620', '2025-10-13'),
(51, '0901-00000474170', 'FORTUNER', 'TOYOTA', 'FORTUNER 4X2', 'JAD-4460', '2025-10-13'),
(52, '0100-98021420230153', 'FUEL TRUCK', 'SINO TRUK', 'SINO TRUK 6WHEELER', 'NHE4920', '2025-10-13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `land_title`
--
ALTER TABLE `land_title`
  ADD PRIMARY KEY (`land_title_id`);

--
-- Indexes for table `lto`
--
ALTER TABLE `lto`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `land_title`
--
ALTER TABLE `land_title`
  MODIFY `land_title_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `lto`
--
ALTER TABLE `lto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
