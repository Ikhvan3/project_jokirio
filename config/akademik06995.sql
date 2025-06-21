-- Database: sisfo_nilai_akademik
-- Sistem Informasi Nilai Akademik dengan Authentication

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Tabel Users untuk Authentication
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','dosen','mahasiswa') NOT NULL DEFAULT 'mahasiswa',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Mahasiswa (Updated)
CREATE TABLE `mahasiswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `nim` varchar(14) NOT NULL UNIQUE,
  `nama` varchar(100) NOT NULL,
  `jurusan` varchar(100) DEFAULT NULL,
  `semester` int(2) DEFAULT 1,
  `angkatan` varchar(4) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `filepath` varchar(255) DEFAULT NULL,
  `thumbpath` varchar(255) DEFAULT NULL,
  `width` int(11) DEFAULT 0,
  `height` int(11) DEFAULT 0,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Dosen
CREATE TABLE `dosen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `nip` varchar(20) NOT NULL UNIQUE,
  `nama` varchar(100) NOT NULL,
  `mata_kuliah` varchar(100) NOT NULL,
  `fakultas` varchar(100) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Mata Kuliah
CREATE TABLE `mata_kuliah` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_mk` varchar(10) NOT NULL UNIQUE,
  `nama_mk` varchar(100) NOT NULL,
  `sks` int(2) NOT NULL DEFAULT 3,
  `semester` int(2) NOT NULL,
  `dosen_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`dosen_id`) REFERENCES `dosen`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Nilai (Transaksi Utama)
CREATE TABLE `nilai` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mahasiswa_id` int(11) NOT NULL,
  `mata_kuliah_id` int(11) NOT NULL,
  `dosen_id` int(11) NOT NULL,
  `tahun_akademik` varchar(9) NOT NULL, -- Format: 2024/2025
  `semester` enum('ganjil','genap') NOT NULL,
  `nilai_tugas` decimal(5,2) DEFAULT 0.00,
  `nilai_uts` decimal(5,2) DEFAULT 0.00,
  `nilai_uas` decimal(5,2) DEFAULT 0.00,
  `nilai_akhir` decimal(5,2) DEFAULT 0.00,
  `grade` varchar(2) DEFAULT NULL,
  `status` enum('draft','final') NOT NULL DEFAULT 'draft',
  `keterangan` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_nilai` (`mahasiswa_id`, `mata_kuliah_id`, `tahun_akademik`, `semester`),
  FOREIGN KEY (`mahasiswa_id`) REFERENCES `mahasiswa`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`mata_kuliah_id`) REFERENCES `mata_kuliah`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`dosen_id`) REFERENCES `dosen`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Tabel Sessions untuk keamanan
CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('admin', 'admin@sisfo.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active');

-- Insert sample dosen
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('dosen001', 'dosen1@sisfo.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen', 'active'),
('dosen002', 'dosen2@sisfo.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'dosen', 'active');

INSERT INTO `dosen` (`user_id`, `nip`, `nama`, `mata_kuliah`, `fakultas`) VALUES
(2, '198501012010121001', 'Dr. Ahmad Wijaya, M.Kom', 'Pemrograman Web Lanjut', 'Fakultas Teknologi Informasi'),
(3, '198701022012122002', 'Prof. Siti Nurhaliza, Ph.D', 'Basis Data Lanjut', 'Fakultas Teknologi Informasi');

-- Insert sample mata kuliah
INSERT INTO `mata_kuliah` (`kode_mk`, `nama_mk`, `sks`, `semester`, `dosen_id`) VALUES
('PWL001', 'Pemrograman Web Lanjut', 3, 5, 1),
('BDL001', 'Basis Data Lanjut', 3, 5, 2),
('RPL001', 'Rekayasa Perangkat Lunak', 3, 4, 1),
('SBD001', 'Sistem Basis Data', 3, 3, 2);

-- Update existing mahasiswa data with user accounts
INSERT INTO `users` (`username`, `email`, `password`, `role`, `status`) VALUES
('A12202306911', 'joko@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa', 'active'),
('A12202306974', 'syahnal@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa', 'active'),
('A12202306971', 'alvin@student.ac.id', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mahasiswa', 'active');

-- Update mahasiswa table to link with users
UPDATE `mahasiswa` SET 
  `user_id` = 4, 
  `semester` = 5, 
  `angkatan` = '2023',
  `nim` = 'A12.2023.06911'
WHERE `id` = 8;

UPDATE `mahasiswa` SET 
  `user_id` = 5, 
  `semester` = 5, 
  `angkatan` = '2023',
  `nim` = 'A12.2023.06974'
WHERE `id` = 9;

UPDATE `mahasiswa` SET 
  `user_id` = 6, 
  `semester` = 5, 
  `angkatan` = '2023',
  `nim` = 'A12.2023.06971'
WHERE `id` = 10;

-- Insert sample nilai (transaksi)
INSERT INTO `nilai` (`mahasiswa_id`, `mata_kuliah_id`, `dosen_id`, `tahun_akademik`, `semester`, `nilai_tugas`, `nilai_uts`, `nilai_uas`, `nilai_akhir`, `grade`, `status`, `created_by`) VALUES
(8, 1, 1, '2024/2025', 'ganjil', 85.00, 80.00, 82.00, 82.50, 'A-', 'final', 2),
(8, 2, 2, '2024/2025', 'ganjil', 78.00, 75.00, 80.00, 77.75, 'B+', 'final', 3),
(9, 1, 1, '2024/2025', 'ganjil', 90.00, 88.00, 85.00, 87.25, 'A', 'final', 2),
(10, 2, 2, '2024/2025', 'ganjil', 70.00, 72.00, 75.00, 72.75, 'B', 'draft', 3);

COMMIT;