-- ========================================================
-- SISTEM MANAJEMEN MESIN DAN MAINTENANCE DTY
-- Database: db_dty_maintenance
-- Engine: InnoDB, Charset: utf8mb4
-- ========================================================

CREATE DATABASE IF NOT EXISTS `db_dty_maintenance` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `db_dty_maintenance`;

-- --------------------------------------------------------
-- 1. TABEL USERS & HAK AKSES (Manajemen, Teknisi 1, Teknisi 2)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `log_maintenance_harian`;
DROP TABLE IF EXISTS `pengiriman_dty`;
DROP TABLE IF EXISTS `reject_packing_detail`;
DROP TABLE IF EXISTS `reject_packing_header`;
DROP TABLE IF EXISTS `operasional_mesin`;
DROP TABLE IF EXISTS `jadwal_maintenance`;
DROP TABLE IF EXISTS `mesin`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `nama_lengkap` VARCHAR(100) NOT NULL,
  `role` ENUM('manajemen', 'teknisi1', 'teknisi2') NOT NULL,
  `status` ENUM('aktif', 'nonaktif') DEFAULT 'aktif',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 2. TABEL MASTER MESIN DTY
-- --------------------------------------------------------
CREATE TABLE `mesin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `kode_mesin` VARCHAR(30) NOT NULL UNIQUE COMMENT 'Contoh: MC-01, DTY-02',
  `nama_mesin` VARCHAR(100) NOT NULL,
  `merk_tipe` VARCHAR(100) NULL COMMENT 'Contoh: Barmag FK6-1000, Murata 33H',
  `jumlah_posisi` INT DEFAULT 216 COMMENT 'Total spindel / spindle positions',
  `tahun_instalasi` YEAR NULL,
  `status_kondisi` ENUM('running', 'breakdown', 'maintenance', 'idle') DEFAULT 'running',
  `catatan` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 3. TABEL JADWAL PREVENTIVE MAINTENANCE
-- --------------------------------------------------------
CREATE TABLE `jadwal_maintenance` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mesin_id` INT NOT NULL,
  `tanggal_rencana` DATE NOT NULL,
  `tanggal_realisasi` DATE NULL,
  `selesai_pada` TIMESTAMP NULL,
  `tipe_maintenance` ENUM('rutin_2bulan', 'overhaul', 'inspeksi_khusus') DEFAULT 'rutin_2bulan',
  `status` ENUM('terjadwal', 'proses', 'selesai', 'terlambat') DEFAULT 'terjadwal',
  `teknisi_id` INT NULL,
  `catatan_perawatan` TEXT NULL,
  `catatan_realisasi` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_jadwal_mesin` FOREIGN KEY (`mesin_id`) REFERENCES `mesin`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_jadwal_teknisi` FOREIGN KEY (`teknisi_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 4. TEMPLATE 1: OPERASIONAL MESIN (Shift, EFF, Break, Idle)
-- --------------------------------------------------------
CREATE TABLE `operasional_mesin` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mesin_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `shift` ENUM('Shift 1', 'Shift 2', 'Shift 3') NOT NULL,
  `operator_id` INT NULL,
  `eff_persen` DECIMAL(5,2) NOT NULL COMMENT 'I / EFF (Contoh: 94.50%)',
  `no_dog_break` VARCHAR(50) NULL COMMENT 'Posisi Spindle / Dog putus',
  `total_break` INT DEFAULT 0 COMMENT 'Total frekuensi break benang',
  `status_idle` ENUM('None', 'Permanen', 'Non-Permanen') DEFAULT 'None',
  `vessel_status` VARCHAR(100) NULL COMMENT 'Kondisi heater/vessel tekanan/suhu',
  `anomali` TEXT NULL COMMENT 'Temuan suara asing, getaran, oil leak, dll',
  `jam_start` TIME NOT NULL,
  `jam_stop` TIME NOT NULL,
  `material` VARCHAR(100) NOT NULL COMMENT 'Spesifikasi raw yarn / POY Lot',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_ops_mesin` FOREIGN KEY (`mesin_id`) REFERENCES `mesin`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ops_user` FOREIGN KEY (`operator_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 5. TEMPLATE 2: VISUAL CHECK REJECT PACKING (QC)
-- --------------------------------------------------------
CREATE TABLE `reject_packing_header` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `mesin_id` INT NOT NULL,
  `tanggal` DATE NOT NULL,
  `nama_operator` VARCHAR(100) NOT NULL,
  `no_dog` VARCHAR(50) NOT NULL COMMENT 'Identifikasi spindle/dog',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_reject_mesin` FOREIGN KEY (`mesin_id`) REFERENCES `mesin`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE `reject_packing_detail` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `header_id` INT NOT NULL,
  `nomor_urut` INT NOT NULL,
  `nomor_posisi` VARCHAR(30) NOT NULL COMMENT 'Nomor posisi pada mesin',
  `item_reject` ENUM(
    'Broken filament',
    'X stitch',
    'Double tail',
    'Tanpa tail',
    'Bulging',
    'DP',
    'Static',
    'Saddle',
    'Gulungan kena oil',
    'Layer falling',
    'Over diameter'
  ) NOT NULL,
  `keterangan` VARCHAR(255) NULL,
  CONSTRAINT `fk_reject_detail_header` FOREIGN KEY (`header_id`) REFERENCES `reject_packing_header`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- --------------------------------------------------------
-- 6. TEMPLATE 3: CATATAN BUKU (Log Maintenance Harian)
-- --------------------------------------------------------
CREATE TABLE `log_maintenance_harian` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tanggal` DATE NOT NULL,
  `mesin_id` INT NOT NULL,
  `posisi_mesin` VARCHAR(50) NOT NULL COMMENT 'Posisi Spindle / Bagian Mesin',
  `status_idle` ENUM('Permanen', 'Non-Permanen', 'None') NOT NULL DEFAULT 'Non-Permanen',
  `problem` TEXT NOT NULL COMMENT 'Kendala atau gejala kerusakan',
  `action` TEXT NOT NULL COMMENT 'Tindakan perbaikan yang dilakukan',
  `keterangan` VARCHAR(255) NULL COMMENT 'Keterangan tambahan / part diganti',
  `teknisi_id` INT NULL,
  `status_perbaikan` ENUM('proses', 'selesai', 'pending_sparepart', 'monitoring') NOT NULL DEFAULT 'proses',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_log_mesin` FOREIGN KEY (`mesin_id`) REFERENCES `mesin`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_log_teknisi` FOREIGN KEY (`teknisi_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- INDEX OPTIMISASI QUERY
CREATE INDEX `idx_ops_tgl_shift` ON `operasional_mesin` (`tanggal`, `shift`);
CREATE INDEX `idx_jadwal_status` ON `jadwal_maintenance` (`status`, `tanggal_rencana`);
CREATE INDEX `idx_log_tgl_mesin` ON `log_maintenance_harian` (`tanggal`, `mesin_id`);

-- --------------------------------------------------------
-- DATA AWAL (SEEDERS)
-- Default password: admin123 (Hash bcrypt: $2y$10$w09ZkH8w2/78q0PZ2Q/FkOXH8E.u0j.6Ekn85D5f5z/i4o3C5R.6e)
-- --------------------------------------------------------
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('manajemen', '$2y$10$w09ZkH8w2/78q0PZ2Q/FkOXH8E.u0j.6Ekn85D5f5z/i4o3C5R.6e', 'Supervisor Indorama', 'manajemen'),
('teknisi1', '$2y$10$w09ZkH8w2/78q0PZ2Q/FkOXH8E.u0j.6Ekn85D5f5z/i4o3C5R.6e', 'Ahmad Teknisi', 'teknisi1'),
('teknisi2', '$2y$10$w09ZkH8w2/78q0PZ2Q/FkOXH8E.u0j.6Ekn85D5f5z/i4o3C5R.6e', 'Teknisi Indorama', 'teknisi2');

INSERT INTO `mesin` (`kode_mesin`, `nama_mesin`, `merk_tipe`, `jumlah_posisi`, `tahun_instalasi`, `status_kondisi`, `catatan`) VALUES
('MC-01', 'DTY Machine Line 1', 'Barmag FK6-1000', 216, 2018, 'running', 'Kondisi stabil, running POY 150D'),
('MC-02', 'DTY Machine Line 2', 'Barmag FK6-1000', 216, 2019, 'running', 'Normal operation, shift 24 jam'),
('MC-03', 'DTY Machine Line 3', 'Murata 33H', 192, 2020, 'breakdown', 'Vessel heater 2 overheating - menunggu suku cadang'),
('MC-04', 'DTY Machine Line 4', 'Murata 33H', 192, 2021, 'maintenance', 'Sedang menjalani preventive maintenance');

INSERT INTO `jadwal_maintenance` (`mesin_id`, `tanggal_rencana`, `tipe_maintenance`, `status`, `teknisi_id`, `catatan_perawatan`) VALUES
(4, CURDATE(), 'rutin_2bulan', 'proses', 2, 'Pembersihan heater plate, ganti oil traverse box'),
(1, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'rutin_2bulan', 'terjadwal', 2, 'Cek bearing spindle dan kalibrasi sensor tension'),
(2, DATE_ADD(CURDATE(), INTERVAL 28 DAY), 'rutin_2bulan', 'terjadwal', NULL, 'Inspeksi belt drive dan inverter motor');

INSERT INTO `operasional_mesin` (`mesin_id`, `tanggal`, `shift`, `operator_id`, `eff_persen`, `no_dog_break`, `total_break`, `status_idle`, `vessel_status`, `anomali`, `jam_start`, `jam_stop`, `material`) VALUES
(1, CURDATE(), 'Shift 1', 3, 96.80, 'Dog #14, #88', 2, 'Non-Permanen', 'Normal 210C', 'Tidak ada anomali suhu', '07:00:00', '15:00:00', 'POY 150D/48F Semi Dull'),
(2, CURDATE(), 'Shift 1', 3, 93.40, 'Dog #03', 5, 'None', 'Normal 210C', 'Vibrasi ringan di traverse section 2', '07:00:00', '15:00:00', 'POY 75D/36F Bright');

INSERT INTO `log_maintenance_harian` (`tanggal`, `mesin_id`, `posisi_mesin`, `status_idle`, `problem`, `action`, `keterangan`, `teknisi_id`, `status_perbaikan`) VALUES
(CURDATE(), 3, 'Heater Vessel #2', 'Permanen', 'Sensor thermocouple putus dan suhu drop ke 160C', 'Isolasi jalur kabel & permintaan part thermocouple baru', 'Part sedang diambil di gudang', 2, 'pending_sparepart'),
(CURDATE(), 1, 'Spindle Posisi 14', 'Non-Permanen', 'Yarn guide aus menyebabkan benang sering break', 'Penggantian ceramic yarn guide baru', 'Selesai diganti, running lancar', 2, 'selesai');

INSERT INTO `reject_packing_header` (`mesin_id`, `tanggal`, `nama_operator`, `no_dog`) VALUES
(1, CURDATE(), 'Siti Rahma', 'Dog #14');

INSERT INTO `reject_packing_detail` (`header_id`, `nomor_urut`, `nomor_posisi`, `item_reject`, `keterangan`) VALUES
(1, 1, 'Sec-1/Pos-14', 'Broken filament', 'Filamen terurai sebelum masuk suction gun'),
(1, 2, 'Sec-1/Pos-15', 'Bulging', 'Gulungan paket bergelombang/tidak rata');
