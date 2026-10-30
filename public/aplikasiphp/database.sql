-- WaliCare Full Database Schema
-- Last Updated: 2026-04-30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Database schema updated for aaPanel compatibility
-- Last Updated: 2026-05-04


-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE IF NOT EXISTS `pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_aplikasi` varchar(100) NOT NULL DEFAULT 'WaliCare',
  `nama_sekolah` varchar(150) NOT NULL DEFAULT 'Sekolah Contoh',
  `alamat_sekolah` text DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`id`, `nama_aplikasi`, `nama_sekolah`, `alamat_sekolah`, `npsn`) VALUES
(1, 'WaliCare', 'SMA Negeri 1 Nusantara', 'Jl. Pendidikan No. 123, Jakarta Pusat', '10203040');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','guru_wali','orang_tua','kepsek') NOT NULL,
  `tingkatan` enum('A','B','C') DEFAULT NULL,
  `no_telp` varchar(20) DEFAULT NULL,
  `last_activity` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
-- Default Password: password123 (MD5)
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `role`, `tingkatan`, `no_telp`) VALUES
(1, 'admin', '482c352da77c663a73c4e334d23a7e32', 'Administrator Sistem', 'admin', NULL, '08123456789'),
(2, 'guru', '482c352da77c663a73c4e334d23a7e32', 'Budi Santoso, S.Pd', 'guru_wali', 'A', '081222333444'),
(3, 'ortu', '482c352da77c663a73c4e334d23a7e32', 'Siti Aminah', 'orang_tua', NULL, '089988776655');

-- --------------------------------------------------------

--
-- Table structure for table `siswa`
--

CREATE TABLE IF NOT EXISTS `siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nis` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `tingkatan` enum('A','B','C') NOT NULL DEFAULT 'A',
  `kelas` varchar(50) NOT NULL,
  `alamat` text DEFAULT NULL,
  `id_orang_tua` int(11) DEFAULT NULL,
  `id_guru_wali` int(11) DEFAULT NULL,
  `status_terakhir` varchar(50) DEFAULT 'Belum Ada Data',
  PRIMARY KEY (`id`),
  UNIQUE KEY `nis` (`nis`),
  KEY `id_orang_tua` (`id_orang_tua`),
  KEY `id_guru_wali` (`id_guru_wali`),
  CONSTRAINT `siswa_ibfk_1` FOREIGN KEY (`id_orang_tua`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `siswa_ibfk_2` FOREIGN KEY (`id_guru_wali`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `siswa`
--

INSERT INTO `siswa` (`id`, `nis`, `nama`, `tingkatan`, `kelas`, `alamat`, `id_orang_tua`, `id_guru_wali`, `status_terakhir`) VALUES
(1, '10101', 'Andi Dermawan', 'A', '10-A', 'Jl. Merdeka No. 1', 3, 2, 'Baik');

-- --------------------------------------------------------

--
-- Table structure for table `catatan_bimbingan`
--

CREATE TABLE IF NOT EXISTS `catatan_bimbingan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_siswa` int(11) NOT NULL,
  `id_guru` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `pertemuan` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `catatan` text DEFAULT NULL,
  `tindak_lanjut` text DEFAULT NULL,
  `akademik_membaca` varchar(20) DEFAULT 'Baik',
  `akademik_menulis` varchar(20) DEFAULT 'Baik',
  `akademik_berhitung` varchar(20) DEFAULT 'Baik',
  `akademik_bacaan_quran` varchar(20) DEFAULT 'Baik',
  `akademik_bacaan_sholat` varchar(20) DEFAULT 'Baik',
  `karakter_sopan_santun` varchar(20) DEFAULT 'Baik',
  `karakter_kedisiplinan` varchar(20) DEFAULT 'Baik',
  `karakter_tanggung_jawab` varchar(20) DEFAULT 'Baik',
  `karakter_kejujuran` varchar(20) DEFAULT 'Baik',
  `pribadi_emosional` varchar(20) DEFAULT 'Baik',
  `pribadi_sosial` varchar(20) DEFAULT 'Baik',
  `pribadi_kemandirian` varchar(20) DEFAULT 'Baik',
  `pribadi_interaksi` varchar(20) DEFAULT 'Baik',
  `pribadi_prilaku` varchar(20) DEFAULT 'Baik',
  `catatan_potensi` text DEFAULT NULL,
  `pelatihan_potensi` enum('Ya','Tidak') DEFAULT 'Tidak',
  PRIMARY KEY (`id`),
  KEY `id_siswa` (`id_siswa`),
  KEY `id_guru` (`id_guru`),
  CONSTRAINT `catatan_bimbingan_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `catatan_bimbingan_ibfk_2` FOREIGN KEY (`id_guru`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE IF NOT EXISTS `pengumuman` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  `tanggal` datetime DEFAULT CURRENT_TIMESTAMP,
  `target_role` enum('admin','guru_wali','orang_tua','kepsek','semua') DEFAULT 'semua',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`judul`, `isi`, `target_role`) VALUES
('Selamat Datang di WaliCare', 'Gunakan sistem ini untuk memantau perkembangan siswa secara real-time.', 'semua');

COMMIT;

