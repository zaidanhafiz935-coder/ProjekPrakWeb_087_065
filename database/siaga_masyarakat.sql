-- ============================================================
-- DATABASE: siaga_masyarakat
-- Website: Siaga Masyarakat - Platform Pelaporan Lingkungan
-- Versi: 1.0
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- Buat database
CREATE DATABASE IF NOT EXISTS `siaga_masyarakat`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `siaga_masyarakat`;

-- ============================================================
-- TABEL: users
-- ============================================================
CREATE TABLE `users` (
  `id`           INT(11)      NOT NULL AUTO_INCREMENT,
  `nama`         VARCHAR(150) NOT NULL,
  `email`        VARCHAR(150) NOT NULL,
  `no_telepon`   VARCHAR(20)  DEFAULT NULL,
  `alamat`       TEXT         DEFAULT NULL,
  `password`     VARCHAR(255) NOT NULL,
  `role`         ENUM('warga','admin') NOT NULL DEFAULT 'warga',
  `status_akun`  ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: categories
-- ============================================================
CREATE TABLE `categories` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `nama_kategori`  VARCHAR(100) NOT NULL,
  `deskripsi`      TEXT         DEFAULT NULL,
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nama_kategori` (`nama_kategori`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: reports
-- ============================================================
CREATE TABLE `reports` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `nomor_laporan`  VARCHAR(20)  NOT NULL,
  `user_id`        INT(11)      NOT NULL,
  `category_id`    INT(11)      NOT NULL,
  `judul`          VARCHAR(200) NOT NULL,
  `deskripsi`      TEXT         NOT NULL,
  `foto`           VARCHAR(255) DEFAULT NULL,
  `alamat_lokasi`  TEXT         DEFAULT NULL,
  `latitude`       DECIMAL(10,8) DEFAULT NULL,
  `longitude`      DECIMAL(11,8) DEFAULT NULL,
  `status`         ENUM('Menunggu','Diverifikasi','Diproses','Selesai','Ditolak') NOT NULL DEFAULT 'Menunggu',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomor_laporan` (`nomor_laporan`),
  KEY `idx_user_id`     (`user_id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_status`      (`status`),
  KEY `idx_created_at`  (`created_at`),
  CONSTRAINT `fk_reports_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reports_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: report_histories
-- ============================================================
CREATE TABLE `report_histories` (
  `id`         INT(11)      NOT NULL AUTO_INCREMENT,
  `report_id`  INT(11)      NOT NULL,
  `admin_id`   INT(11)      DEFAULT NULL,
  `status`     ENUM('Menunggu','Diverifikasi','Diproses','Selesai','Ditolak') NOT NULL,
  `catatan`    TEXT         DEFAULT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  KEY `idx_admin_id`  (`admin_id`),
  CONSTRAINT `fk_rh_report` FOREIGN KEY (`report_id`) REFERENCES `reports` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rh_admin`  FOREIGN KEY (`admin_id`)  REFERENCES `users`   (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: activities
-- ============================================================
CREATE TABLE `activities` (
  `id`             INT(11)      NOT NULL AUTO_INCREMENT,
  `nama_kegiatan`  VARCHAR(200) NOT NULL,
  `deskripsi`      TEXT         DEFAULT NULL,
  `foto`           VARCHAR(255) DEFAULT NULL,
  `lokasi`         TEXT         DEFAULT NULL,
  `latitude`       DECIMAL(10,8) DEFAULT NULL,
  `longitude`      DECIMAL(11,8) DEFAULT NULL,
  `tanggal`        DATE         NOT NULL,
  `waktu`          TIME         NOT NULL,
  `kuota`          INT(11)      NOT NULL DEFAULT 50,
  `status`         ENUM('Akan Datang','Berlangsung','Selesai','Dibatalkan') NOT NULL DEFAULT 'Akan Datang',
  `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status`  (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABEL: activity_participants
-- ============================================================
CREATE TABLE `activity_participants` (
  `id`                  INT(11)  NOT NULL AUTO_INCREMENT,
  `activity_id`         INT(11)  NOT NULL,
  `user_id`             INT(11)  NOT NULL,
  `status_pendaftaran`  ENUM('Terdaftar','Dibatalkan') NOT NULL DEFAULT 'Terdaftar',
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_activity_user` (`activity_id`, `user_id`),
  KEY `idx_user_id`     (`user_id`),
  CONSTRAINT `fk_ap_activity` FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ap_user`     FOREIGN KEY (`user_id`)     REFERENCES `users`      (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DATA DUMMY: users
-- Password semua akun: Admin123!
-- Hash dibuat dengan: password_hash('Admin123!', PASSWORD_DEFAULT)
-- ============================================================
INSERT INTO `users` (`nama`, `email`, `no_telepon`, `alamat`, `password`, `role`, `status_akun`, `created_at`) VALUES
('Administrator Siaga', 'admin@siagamasyarakat.id', '081200000001', 'Jl. Merdeka No. 1, Jakarta Pusat', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'admin', 'aktif', '2026-01-01 08:00:00'),
('Budi Santoso', 'budi@email.com', '081234567890', 'Jl. Mawar No. 12, Jakarta Selatan', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'warga', 'aktif', '2026-01-05 09:00:00'),
('Siti Rahayu', 'siti@email.com', '081298765432', 'Jl. Melati No. 7, Depok', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'warga', 'aktif', '2026-01-10 10:00:00'),
('Andi Wijaya', 'andi@email.com', '085712345678', 'Jl. Kenanga No. 3, Bekasi', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'warga', 'aktif', '2026-01-15 11:00:00'),
('Dewi Kusuma', 'dewi@email.com', '087812345678', 'Jl. Anggrek No. 22, Tangerang', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'warga', 'aktif', '2026-02-01 08:30:00'),
('Riko Pratama', 'riko@email.com', '082312345678', 'Jl. Flamboyan No. 9, Bogor', '$2y$10$0o6/udGMXgUfq0gELgROUevFD2Ef.OmwVUdnfkvpFXTW7oHkYUsJy', 'warga', 'aktif', '2026-02-10 09:30:00');

-- ============================================================
-- DATA DUMMY: categories
-- ============================================================
INSERT INTO `categories` (`nama_kategori`, `deskripsi`, `created_at`) VALUES
('Jalan Rusak',         'Laporan kerusakan jalan, berlubang, retak, atau tidak layak dilalui.', '2026-01-01 08:00:00'),
('Sampah Menumpuk',     'Laporan tumpukan sampah yang tidak terangkut atau pembuangan liar.', '2026-01-01 08:00:00'),
('Drainase Tersumbat',  'Laporan saluran air tersumbat yang berpotensi menyebabkan banjir.', '2026-01-01 08:00:00'),
('Lampu Jalan Mati',    'Laporan lampu penerangan jalan yang tidak berfungsi.', '2026-01-01 08:00:00'),
('Fasilitas Umum Rusak','Laporan kerusakan fasilitas publik seperti taman, bangku, halte, dll.', '2026-01-01 08:00:00'),
('Pohon Tumbang',       'Laporan pohon tumbang atau dahan berbahaya yang mengancam keselamatan.', '2026-01-01 08:00:00'),
('Banjir',              'Laporan genangan banjir yang mengganggu aktivitas warga.', '2026-01-01 08:00:00'),
('Lainnya',             'Laporan masalah lingkungan lainnya yang belum termasuk kategori di atas.', '2026-01-01 08:00:00');

-- ============================================================
-- DATA DUMMY: reports
-- ============================================================
INSERT INTO `reports` (`nomor_laporan`, `user_id`, `category_id`, `judul`, `deskripsi`, `foto`, `alamat_lokasi`, `latitude`, `longitude`, `status`, `created_at`, `updated_at`) VALUES
('SM-2026-00001', 2, 1, 'Jalan Berlubang di Jl. Sudirman', 'Terdapat lubang besar di tengah jalan Sudirman km 3 yang sangat membahayakan pengendara, terutama pada malam hari. Lubang berdiameter sekitar 50cm dan kedalaman 20cm.', NULL, 'Jl. Jend. Sudirman KM 3, Jakarta Selatan', -6.2088000, 106.8456000, 'Selesai',    '2026-01-20 10:00:00', '2026-02-01 14:00:00'),
('SM-2026-00002', 3, 2, 'Tumpukan Sampah Pasar Minggu', 'Sampah menumpuk di sekitar Pasar Minggu sudah 3 hari tidak diangkut. Bau menyengat dan mengundang lalat. Warga sekitar sangat terganggu.', NULL, 'Pasar Minggu, Jakarta Selatan', -6.2894000, 106.8371000, 'Diproses',   '2026-02-01 09:30:00', '2026-02-05 10:00:00'),
('SM-2026-00003', 4, 3, 'Gorong-gorong Mampet Jl. Cempaka', 'Gorong-gorong di Jl. Cempaka tersumbat sampah plastik sehingga air tidak dapat mengalir dan menyebabkan genangan di musim hujan.', NULL, 'Jl. Cempaka, Bekasi Barat', -6.2383000, 106.9756000, 'Diverifikasi','2026-02-10 11:00:00', '2026-02-12 09:00:00'),
('SM-2026-00004', 5, 4, 'Lampu Jalan Mati di Gang Melati', 'Sudah 2 minggu lampu jalan di Gang Melati No. 1-10 mati semua. Warga takut keluar malam hari karena sangat gelap.', NULL, 'Gang Melati, Tangerang Kota', -6.1784000, 106.6319000, 'Menunggu',   '2026-02-15 14:00:00', '2026-02-15 14:00:00'),
('SM-2026-00005', 6, 7, 'Banjir Setinggi 50cm di RT 005', 'Setelah hujan deras semalam, banjir setinggi 50cm menggenangi permukiman RT 005/RW 002. Warga kesulitan beraktivitas dan barang-barang terendam.', NULL, 'RT 005/RW 002, Bogor Selatan', -6.5971000, 106.8060000, 'Selesai',    '2026-02-18 07:00:00', '2026-02-20 16:00:00'),
('SM-2026-00006', 2, 5, 'Bangku Taman Rusak di Taman Kota', 'Beberapa bangku di taman kota sudah rusak parah. Besi berkarat dan papan kayu lapuk sehingga tidak aman digunakan warga terutama anak-anak.', NULL, 'Taman Kota, Jakarta Selatan', -6.2100000, 106.8250000, 'Menunggu',   '2026-03-01 09:00:00', '2026-03-01 09:00:00'),
('SM-2026-00007', 3, 6, 'Pohon Tumbang Menutup Jalan', 'Pohon besar tumbang akibat angin kencang dan menutup setengah badan jalan di Jl. Raya Depok. Lalu lintas terganggu.', NULL, 'Jl. Raya Depok, Depok', -6.4024000, 106.7942000, 'Diproses',   '2026-03-05 06:30:00', '2026-03-05 08:00:00');

-- ============================================================
-- DATA DUMMY: report_histories
-- ============================================================
INSERT INTO `report_histories` (`report_id`, `admin_id`, `status`, `catatan`, `created_at`) VALUES
-- Laporan 1 (Jalan Berlubang - Selesai)
(1, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-01-20 10:00:00'),
(1, 1,    'Diverifikasi','Laporan telah diverifikasi. Tim teknis akan ditugaskan.', '2026-01-22 09:00:00'),
(1, 1,    'Diproses',    'Tim Dinas PU telah ditugaskan ke lokasi dan sedang melakukan perbaikan.', '2026-01-25 10:00:00'),
(1, 1,    'Selesai',     'Perbaikan jalan selesai dilakukan. Aspal baru sudah dipasang. Terima kasih atas laporan Anda.', '2026-02-01 14:00:00'),
-- Laporan 2 (Sampah - Diproses)
(2, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-02-01 09:30:00'),
(2, 1,    'Diverifikasi','Laporan sampah menumpuk telah dikonfirmasi. Petugas kebersihan akan dihubungi.', '2026-02-03 10:00:00'),
(2, 1,    'Diproses',    'Petugas kebersihan Dinas Lingkungan Hidup sedang menangani pengangkutan sampah.', '2026-02-05 10:00:00'),
-- Laporan 3 (Drainase - Diverifikasi)
(3, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-02-10 11:00:00'),
(3, 1,    'Diverifikasi','Laporan gorong-gorong tersumbat telah diverifikasi. Akan dijadwalkan pembersihan.', '2026-02-12 09:00:00'),
-- Laporan 4 (Lampu - Menunggu)
(4, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-02-15 14:00:00'),
-- Laporan 5 (Banjir - Selesai)
(5, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-02-18 07:00:00'),
(5, 1,    'Diverifikasi','Kondisi banjir dikonfirmasi. Tim penanganan darurat diaktifkan.', '2026-02-18 09:00:00'),
(5, 1,    'Diproses',    'Pompa air darurat dipasang dan tim evakuasi membantu warga.', '2026-02-18 12:00:00'),
(5, 1,    'Selesai',     'Banjir telah surut. Pembersihan lingkungan selesai dilakukan.', '2026-02-20 16:00:00'),
-- Laporan 6 (Bangku Taman - Menunggu)
(6, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-03-01 09:00:00'),
-- Laporan 7 (Pohon Tumbang - Diproses)
(7, NULL, 'Menunggu',    'Laporan berhasil dikirim. Menunggu verifikasi admin.', '2026-03-05 06:30:00'),
(7, 1,    'Diverifikasi','Laporan pohon tumbang dikonfirmasi. Tim evakuasi segera dikirim.', '2026-03-05 07:00:00'),
(7, 1,    'Diproses',    'Tim sedang melakukan penebangan dan evakuasi pohon dari badan jalan.', '2026-03-05 08:00:00');

-- ============================================================
-- DATA DUMMY: activities
-- ============================================================
INSERT INTO `activities` (`nama_kegiatan`, `deskripsi`, `foto`, `lokasi`, `latitude`, `longitude`, `tanggal`, `waktu`, `kuota`, `status`, `created_at`) VALUES
('Kerja Bakti Bersama RT 01', 'Kegiatan kerja bakti membersihkan lingkungan RT 01/RW 03, meliputi pembersihan selokan, penyapuan jalan, dan pengecatan pagar. Mari bergabung dan wujudkan lingkungan bersih bersama!', NULL, 'RT 01/RW 03, Jl. Mawar, Jakarta Selatan', -6.2100000, 106.8230000, '2026-04-15', '07:00:00', 50,  'Akan Datang', '2026-03-10 08:00:00'),
('Pembersihan Sungai Ciliwung', 'Aksi bersih Sungai Ciliwung bersama komunitas lingkungan. Kegiatan meliputi pengambilan sampah di bantaran sungai dan penanaman pohon di tepi sungai.', NULL, 'Bantaran Sungai Ciliwung, Depok', -6.3800000, 106.8200000, '2026-04-20', '06:30:00', 100, 'Akan Datang', '2026-03-12 09:00:00'),
('Penanaman 1000 Pohon', 'Program penanaman 1000 pohon dalam rangka peringatan Hari Bumi. Mari berkontribusi untuk penghijauan kota dan mengurangi pemanasan global.', NULL, 'Taman Kota, Tangerang Selatan', -6.2900000, 106.7800000, '2026-04-22', '07:30:00', 200, 'Akan Datang', '2026-03-15 10:00:00'),
('Sosialisasi Pilah Sampah',  'Sosialisasi dan edukasi cara memilah sampah organik dan anorganik kepada warga. Akan ada demonstrasi pembuatan kompos sederhana dari sampah rumah tangga.', NULL, 'Balai RW 05, Bekasi Barat', -6.2400000, 106.9900000, '2026-04-10', '09:00:00', 60,  'Berlangsung', '2026-03-20 11:00:00'),
('Bakti Sosial Banjir Bogor',  'Bantuan sosial untuk korban banjir di wilayah Bogor Selatan. Kegiatan meliputi distribusi sembako, pakaian layak pakai, dan obat-obatan.', NULL, 'Kelurahan Mulyaharja, Bogor Selatan', -6.6100000, 106.8000000, '2026-03-01', '08:00:00', 80,  'Selesai',     '2026-02-20 08:00:00');

-- ============================================================
-- DATA DUMMY: activity_participants
-- ============================================================
INSERT INTO `activity_participants` (`activity_id`, `user_id`, `status_pendaftaran`, `created_at`) VALUES
(1, 2, 'Terdaftar',   '2026-03-11 09:00:00'),
(1, 3, 'Terdaftar',   '2026-03-12 10:00:00'),
(1, 4, 'Terdaftar',   '2026-03-13 11:00:00'),
(2, 3, 'Terdaftar',   '2026-03-13 08:00:00'),
(2, 5, 'Terdaftar',   '2026-03-14 09:00:00'),
(2, 6, 'Dibatalkan',  '2026-03-15 10:00:00'),
(3, 2, 'Terdaftar',   '2026-03-16 07:00:00'),
(3, 4, 'Terdaftar',   '2026-03-17 08:00:00'),
(3, 6, 'Terdaftar',   '2026-03-18 09:00:00'),
(4, 5, 'Terdaftar',   '2026-03-21 10:00:00'),
(5, 2, 'Terdaftar',   '2026-02-21 09:00:00'),
(5, 3, 'Terdaftar',   '2026-02-21 10:00:00'),
(5, 4, 'Terdaftar',   '2026-02-22 08:00:00'),
(5, 5, 'Terdaftar',   '2026-02-22 09:00:00'),
(5, 6, 'Terdaftar',   '2026-02-23 08:00:00');

COMMIT;
