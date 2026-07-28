-- ============================================================
-- IT Helpdesk & Ticketing System - Database Script
-- Database: db_it_helpdesk
-- Generated based on ERD Diagram (UML/erd diagram.png)
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db_it_helpdesk`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `db_it_helpdesk`;

-- ============================================================
-- TABEL 1: Pengguna
-- Entitas utama: Karyawan, Teknisi IT, Admin IT
-- ============================================================
CREATE TABLE IF NOT EXISTS `Pengguna` (
    `id_user`  INT          NOT NULL AUTO_INCREMENT,
    `nama`     VARCHAR(100) NOT NULL,
    `email`    VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role`     ENUM('Karyawan','Teknisi','Admin') NOT NULL DEFAULT 'Karyawan',
    PRIMARY KEY (`id_user`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 2: Kategori_Masalah
-- Master data kategori tiket (kelola oleh Admin)
-- ============================================================
CREATE TABLE IF NOT EXISTS `Kategori_Masalah` (
    `id_kategori`    INT          NOT NULL AUTO_INCREMENT,
    `nama_kategori`  VARCHAR(100) NOT NULL,
    PRIMARY KEY (`id_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 3: Aset_Perangkat
-- Master data aset IT yang dimiliki pengguna
-- ============================================================
CREATE TABLE IF NOT EXISTS `Aset_Perangkat` (
    `id_aset`   INT          NOT NULL AUTO_INCREMENT,
    `id_user`   INT          NOT NULL,
    `kode_aset` VARCHAR(50)  NOT NULL UNIQUE,
    `nama_aset` VARCHAR(150) NOT NULL,
    PRIMARY KEY (`id_aset`),
    CONSTRAINT `fk_aset_user` FOREIGN KEY (`id_user`) REFERENCES `Pengguna`(`id_user`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 4: Tiket
-- Entitas utama sistem: menyimpan semua tiket kendala
-- ============================================================
CREATE TABLE IF NOT EXISTS `Tiket` (
    `id_tiket`          INT          NOT NULL AUTO_INCREMENT,
    `id_pelapor`        INT          NOT NULL,
    `id_teknisi`        INT          NULL DEFAULT NULL,
    `id_kategori`       INT          NOT NULL,
    `id_aset`           INT          NULL DEFAULT NULL,
    `kode_tiket`        VARCHAR(20)  NOT NULL UNIQUE,
    `judul_masalah`     VARCHAR(255) NOT NULL,
    `deskripsi`         TEXT         NOT NULL,
    `tingkat_prioritas` ENUM('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
    `status_tiket`      ENUM('Open','In Progress','Resolved') NOT NULL DEFAULT 'Open',
    `tanggal_dibuat`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_tiket`),
    CONSTRAINT `fk_tiket_pelapor`  FOREIGN KEY (`id_pelapor`)  REFERENCES `Pengguna`(`id_user`)          ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_tiket_teknisi`  FOREIGN KEY (`id_teknisi`)  REFERENCES `Pengguna`(`id_user`)          ON DELETE SET NULL  ON UPDATE CASCADE,
    CONSTRAINT `fk_tiket_kategori` FOREIGN KEY (`id_kategori`) REFERENCES `Kategori_Masalah`(`id_kategori`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_tiket_aset`     FOREIGN KEY (`id_aset`)     REFERENCES `Aset_Perangkat`(`id_aset`)    ON DELETE SET NULL  ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 5: Riwayat_Penanganan
-- Log catatan perbaikan dari Teknisi IT
-- ============================================================
CREATE TABLE IF NOT EXISTS `Riwayat_Penanganan` (
    `id_riwayat`        INT      NOT NULL AUTO_INCREMENT,
    `id_tiket`          INT      NOT NULL,
    `id_teknisi`        INT      NOT NULL,
    `catatan_perbaikan` TEXT     NOT NULL,
    `tanggal_update`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_riwayat`),
    CONSTRAINT `fk_riwayat_tiket`   FOREIGN KEY (`id_tiket`)   REFERENCES `Tiket`(`id_tiket`)       ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT `fk_riwayat_teknisi` FOREIGN KEY (`id_teknisi`) REFERENCES `Pengguna`(`id_user`)     ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL 6: Ulasan_Layanan
-- Feedback/rating dari Karyawan setelah tiket Resolved
-- ============================================================
CREATE TABLE IF NOT EXISTS `Ulasan_Layanan` (
    `id_ulasan`         INT      NOT NULL AUTO_INCREMENT,
    `id_tiket`          INT      NOT NULL UNIQUE,
    `id_user`           INT      NOT NULL,
    `rating`            INT      NOT NULL CHECK (`rating` BETWEEN 1 AND 5),
    `komentar_kepuasan` TEXT     NULL,
    PRIMARY KEY (`id_ulasan`),
    CONSTRAINT `fk_ulasan_tiket` FOREIGN KEY (`id_tiket`) REFERENCES `Tiket`(`id_tiket`)   ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT `fk_ulasan_user`  FOREIGN KEY (`id_user`)  REFERENCES `Pengguna`(`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- DATA DUMMY
-- Password plaintext: admin123, teknisi123, karyawan123
-- ============================================================

INSERT INTO `Pengguna` (`nama`, `email`, `password`, `role`) VALUES
('Admin IT', 'admin@helpdesk.com',  MD5('admin123'),    'Admin'),
('Nesya',    'nesya@helpdesk.com',  MD5('teknisi123'),  'Teknisi'),
('Risky',    'risky@helpdesk.com',  MD5('karyawan123'), 'Karyawan');

INSERT INTO `Kategori_Masalah` (`nama_kategori`) VALUES
('Hardware'),
('Software'),
('Jaringan / Network'),
('Akun & Akses'),
('Printer & Peripheral');

-- Aset milik Risky (id_user=3) dan Nesya (id_user=2)
INSERT INTO `Aset_Perangkat` (`id_user`, `kode_aset`, `nama_aset`) VALUES
(3, 'AST-PC-001',  'Laptop Dell Inspiron 15 - Risky'),
(3, 'AST-PRN-001', 'Printer HP LaserJet 1020 - Lantai 2'),
(2, 'AST-PC-002',  'Workstation Teknisi - Nesya');

INSERT INTO `Tiket` (`id_pelapor`, `id_teknisi`, `id_kategori`, `id_aset`, `kode_tiket`, `judul_masalah`, `deskripsi`, `tingkat_prioritas`, `status_tiket`, `tanggal_dibuat`) VALUES
(3, 2, 1, 1, '#TCK-001', 'Laptop Tidak Bisa Menyala', 'Laptop Dell saya tiba-tiba tidak mau menyala sejak pagi. Sudah dicoba dicas selama 2 jam tapi tetap tidak ada respon.', 'High',   'Resolved',    '2025-07-10 08:30:00'),
(3, 2, 2, 1, '#TCK-002', 'Microsoft Office Error Saat Dibuka', 'Saat membuka MS Word muncul pesan error Application not found. Sudah coba restart namun masih sama.', 'Medium', 'In Progress', '2025-07-18 09:15:00'),
(3, NULL, 3, NULL, '#TCK-003', 'Koneksi Internet Lambat', 'Koneksi internet di ruangan saya sangat lambat sejak kemarin. Speed test menunjukkan hanya 2 Mbps, padahal normalnya 100 Mbps.', 'Urgent', 'Open', '2025-07-25 10:00:00'),
(3, NULL, 4, NULL, '#TCK-004', 'Lupa Password Email Kantor', 'Saya tidak bisa login ke email kantor karena lupa password. Mohon reset password akun risky@perusahaan.com.', 'Low', 'Open', '2025-07-26 14:20:00');

INSERT INTO `Riwayat_Penanganan` (`id_tiket`, `id_teknisi`, `catatan_perbaikan`, `tanggal_update`) VALUES
(1, 2, 'Dilakukan pengecekan awal. Ditemukan adaptor charger rusak. Penggantian adaptor dijadwalkan.', '2025-07-10 10:00:00'),
(1, 2, 'Adaptor charger baru telah dipasang. Laptop berhasil menyala dan berfungsi normal. Masalah terselesaikan.', '2025-07-11 14:30:00'),
(2, 2, 'Dilakukan pengecekan instalasi MS Office. Ditemukan file registry yang corrupt. Sedang dalam proses reinstall.', '2025-07-18 11:00:00');

INSERT INTO `Ulasan_Layanan` (`id_tiket`, `id_user`, `rating`, `komentar_kepuasan`) VALUES
(1, 3, 5, 'Pelayanan sangat cepat dan responsif. Teknisi Nesya sangat profesional dan ramah. Masalah selesai dalam 1 hari. Terima kasih!');
