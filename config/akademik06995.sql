-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 29, 2025 at 04:07 AM
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
-- Database: `akademik06995`
--

-- --------------------------------------------------------

--
-- Table structure for table `mahasiswa`
--

CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL,
  `nim` varchar(14) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `foto` varchar(255) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `filepath` varchar(255) NOT NULL,
  `thumbpath` varchar(255) NOT NULL,
  `width` int(11) NOT NULL,
  `height` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `mahasiswa`
--

INSERT INTO `mahasiswa` (`id`, `nim`, `nama`, `jurusan`, `foto`, `filename`, `filepath`, `thumbpath`, `width`, `height`, `uploaded_at`) VALUES
(8, 'A12.2023.06911', 'JOKO', 'Teknik Informatika', '68372c53af796_A12.2017.03000.jpg', '68372c53af796_A12.2017.03000.jpg', 'uploads/68372c53af796_A12.2017.03000.jpg', 'thumbs/thumb_68372c53af796_A12.2017.03000.jpg', 201, 250, '2025-05-28 15:31:31'),
(9, 'A12.2023.06974', 'syahnal', 'Sistem Informasi', '68372c9b2f728_A12.2017.05041.jpg', '68372c9b2f728_A12.2017.05041.jpg', 'uploads/68372c9b2f728_A12.2017.05041.jpg', 'thumbs/thumb_68372c9b2f728_A12.2017.05041.jpg', 194, 259, '2025-05-28 15:32:43'),
(10, 'A12.2023.06971', 'ALVIN', 'Sistem Informasi', '68372d3d48df9_A12.2016.02901.jpg', '68372d3d48df9_A12.2016.02901.jpg', 'uploads/68372d3d48df9_A12.2016.02901.jpg', 'thumbs/thumb_68372d3d48df9_A12.2016.02901.jpg', 184, 274, '2025-05-28 15:35:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `mahasiswa`
--
ALTER TABLE `mahasiswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
