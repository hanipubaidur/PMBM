-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql105.infinityfree.com
-- Generation Time: Apr 11, 2026 at 06:48 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41320441_pmbm26`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jalur_pendaftaran`
--

CREATE TABLE `jalur_pendaftaran` (
  `id` int(11) NOT NULL,
  `nama_jalur` varchar(50) DEFAULT NULL,
  `kuota` int(11) DEFAULT NULL,
  `tanggal_buka` datetime DEFAULT NULL,
  `tanggal_tutup` datetime DEFAULT NULL,
  `tanggal_pengumuman` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `status_penerimaan` enum('diterima','ditolak') NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `admin_id` int(11) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `peserta`
--

CREATE TABLE `peserta` (
  `id` int(11) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(100) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` enum('Laki-Laki','Perempuan') DEFAULT NULL,
  `agama_siswa` enum('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `asal_sekolah` varchar(100) DEFAULT NULL,
  `tahun_lulus` year(4) DEFAULT NULL,
  `no_wa_siswa` varchar(20) DEFAULT NULL,
  `alamat_siswa_jalan` varchar(255) DEFAULT NULL,
  `alamat_siswa_rt` varchar(10) DEFAULT NULL,
  `alamat_siswa_rw` varchar(10) DEFAULT NULL,
  `alamat_siswa_kelurahan` varchar(100) DEFAULT NULL,
  `alamat_siswa_kecamatan` varchar(100) DEFAULT NULL,
  `alamat_siswa_kota` varchar(100) DEFAULT NULL,
  `no_kk` varchar(16) DEFAULT NULL,
  `nama_ayah` varchar(100) DEFAULT NULL,
  `tempat_lahir_ayah` varchar(100) DEFAULT NULL,
  `tanggal_lahir_ayah` date DEFAULT NULL,
  `nama_ibu` varchar(100) DEFAULT NULL,
  `tempat_lahir_ibu` varchar(100) DEFAULT NULL,
  `tanggal_lahir_ibu` date DEFAULT NULL,
  `pekerjaan_ayah` varchar(100) DEFAULT NULL,
  `pekerjaan_ibu` varchar(100) DEFAULT NULL,
  `agama_ayah` enum('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `agama_ibu` enum('Islam','Kristen','Katolik','Hindu','Buddha','Konghucu') DEFAULT NULL,
  `no_telp_ortu` varchar(20) DEFAULT NULL,
  `alamat_ortu_jalan` varchar(255) DEFAULT NULL,
  `alamat_ortu_rt` varchar(10) DEFAULT NULL,
  `alamat_ortu_rw` varchar(10) DEFAULT NULL,
  `alamat_ortu_kelurahan` varchar(100) DEFAULT NULL,
  `alamat_ortu_kecamatan` varchar(100) DEFAULT NULL,
  `alamat_ortu_kota` varchar(100) DEFAULT NULL,
  `jalur_id` int(11) DEFAULT NULL,
  `jarak_ke_sekolah` decimal(5,2) DEFAULT NULL,
  `file_kk` varchar(255) DEFAULT NULL,
  `file_akte` varchar(255) DEFAULT NULL,
  `file_photo` varchar(255) DEFAULT NULL,
  `file_ijazah_skl` varchar(255) DEFAULT NULL,
  `file_raport_1` varchar(255) DEFAULT NULL,
  `file_raport_2` varchar(255) DEFAULT NULL,
  `file_raport_3` varchar(255) DEFAULT NULL,
  `file_raport_4` varchar(255) DEFAULT NULL,
  `file_raport_5` varchar(255) DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prestasi`
--

CREATE TABLE `prestasi` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `bidang_prestasi` enum('Prestasi Akademik','Prestasi Olahraga','Prestasi Seni','Prestasi Non-Akademik') DEFAULT NULL,
  `judul_prestasi` varchar(255) DEFAULT NULL,
  `peringkat` varchar(50) DEFAULT NULL,
  `tingkat` enum('Kecamatan','Kabupaten','Provinsi','Nasional','Internasional') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `verifikasi_peserta`
--

CREATE TABLE `verifikasi_peserta` (
  `id` int(11) NOT NULL,
  `peserta_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `jarak_ke_sekolah` decimal(5,2) DEFAULT NULL,
  `status_verifikasi` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `catatan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `jalur_pendaftaran`
--
ALTER TABLE `jalur_pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_jalur` (`nama_jalur`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `peserta`
--
ALTER TABLE `peserta`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `nik` (`nik`),
  ADD UNIQUE KEY `no_kk` (`no_kk`),
  ADD KEY `jalur_id` (`jalur_id`);

--
-- Indexes for table `prestasi`
--
ALTER TABLE `prestasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`);

--
-- Indexes for table `verifikasi_peserta`
--
ALTER TABLE `verifikasi_peserta`
  ADD PRIMARY KEY (`id`),
  ADD KEY `peserta_id` (`peserta_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jalur_pendaftaran`
--
ALTER TABLE `jalur_pendaftaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `peserta`
--
ALTER TABLE `peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prestasi`
--
ALTER TABLE `prestasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `verifikasi_peserta`
--
ALTER TABLE `verifikasi_peserta`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
