# Siaga Masyarakat

Platform digital pelaporan masalah lingkungan, fasilitas umum, dan koordinasi kegiatan sosial berbasis web.

**Live Website:** [https://siaga-masyarakat.xo.je/](https://siaga-masyarakat.xo.je/)

---

## Deskripsi Proyek

**Siaga Masyarakat** (*Peduli Lingkungan, Bersama Membangun Masyarakat*) adalah aplikasi web yang memfasilitasi warga untuk melaporkan permasalahan lingkungan maupun kerusakan fasilitas umum secara cepat, transparan, dan terpantau melalui peta interaktif serta sistem pelacakan status (*tracking*). Selain itu, platform ini menyediakan manajemen kegiatan sosial/gotong royong agar warga dapat berpartisipasi langsung sebagai relawan.

---

## Fitur Utama

### 1. Publik & Autentikasi
- **Beranda (`index.php`):** Ringkasan platform, laporan terbaru, dan jadwal kegiatan sosial.
- **Tracking Laporan (`warga/tracking.php`):** Melacak progres penanganan laporan menggunakan nomor laporan (contoh: `SM-2026-00001`).
- **Peta Sebaran (`warga/peta.php`):** Visualisasi lokasi laporan masyarakat menggunakan peta interaktif.
- **Kegiatan Sosial (`warga/kegiatan.php` & `warga/detail_kegiatan.php`):** Informasi jadwal, kuota, dan lokasi kegiatan gotong royong.
- **Registrasi & Login (`register.php`, `login.php`):** Autentikasi berbasis sesi dengan pembagian role (`warga` dan `admin`).

### 2. Portal Warga (`warga/`)
- **Dashboard (`warga/dashboard.php`):** Ringkasan laporan dan kegiatan yang diikuti.
- **Kelola Laporan (`warga/tambah_laporan.php`, `warga/edit_laporan.php`, `warga/laporan_saya.php`, `warga/detail_laporan.php`):** Membuat, mengubah, dan menghapus laporan dilengkapi unggah foto (JPG/PNG/WEBP, maks. 2 MB) serta pemilihan koordinat lokasi pada peta.
- **Kegiatan Saya (`warga/kegiatan_saya.php`):** Mendaftar dan membatalkan partisipasi pada kegiatan sosial.

### 3. Panel Admin (`admin/`)
- **Dashboard Admin (`admin/dashboard.php`):** Ringkasan statistik dan daftar laporan terbaru.
- **Manajemen Laporan (`admin/laporan.php`, `admin/detail_laporan.php`):** Verifikasi, pembaruan status (`Menunggu`, `Diverifikasi`, `Diproses`, `Selesai`, `Ditolak`), pencatatan riwayat penanganan, serta penghapusan laporan.
- **Manajemen Kategori (`admin/kategori.php`):** Pengelolaan kategori laporan.
- **Manajemen Kegiatan & Relawan (`admin/kegiatan.php`, `admin/peserta.php`):** Pengelolaan acara kegiatan sosial dan daftar peserta terdaftar.
- **Manajemen Pengguna (`admin/pengguna.php`):** Pengelolaan data akun dan status aktif/nonaktif pengguna.
- **Peta & Statistik (`admin/peta.php`, `admin/statistik.php`):** Pemetaan sebaran laporan dan grafik analitik laporan.

---

## Teknologi yang Digunakan

- **Backend:** PHP (Native) dengan MySQLi (Prepared Statements)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, JavaScript, Bootstrap 5.3.3, Bootstrap Icons 1.11.3
- **Peta & Grafik:** Leaflet.js 1.9.4 (OpenStreetMap), Chart.js 4.4.3

---

## Struktur Direktori

```text
ProjekPrakWeb_087_065/
├── admin/                  # Halaman & fitur khusus administrator
│   └── nav/                # Komponen navbar dan sidebar admin
├── assets/
│   ├── css/                # Stylesheet kustom (style.css)
│   └── js/                 # Script kustom (script.js)
├── config/
│   └── koneksi.php         # Konfigurasi database, BASE_URL, dan aturan upload
├── database/
│   └── siaga_masyarakat.sql # Skema tabel dan data awal (dummy)
├── includes/               # Autentikasi, fungsi helper, navbar, footer, halaman 403
├── uploads/
│   ├── activities/         # Direktori unggahan foto kegiatan
│   └── reports/            # Direktori unggahan foto laporan
├── warga/                  # Halaman & fitur portal warga
├── favicon.ico             # Ikon situs
├── index.php               # Halaman utama
├── login.php               # Halaman login
├── register.php            # Halaman registrasi
└── logout.php              # Proses logout
```

---

## Instalasi Lokal

1. **Clone repositori** ke direktori web server (misalnya `htdocs` pada XAMPP/LAMPP):
   ```bash
   git clone <url-repositori>
   ```
2. **Import database:**
   - Buka phpMyAdmin atau MySQL CLI.
   - Buat database `siaga_masyarakat` dan import file `database/siaga_masyarakat.sql`.
3. **Sesuaikan konfigurasi** pada `config/koneksi.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   define('DB_NAME', 'siaga_masyarakat');
   define('BASE_URL', 'http://localhost/PROJECT/ProjekPrakWeb_087_065');
   ```
4. **Pastikan folder `uploads/` memiliki izin tulis (write permission)** untuk menyimpan foto laporan dan kegiatan.
5. **Jalankan Apache & MySQL**, lalu akses melalui browser sesuai `BASE_URL`.

---

## Akun Demo (Data Awal Database)

Semua akun bawaan pada `database/siaga_masyarakat.sql` menggunakan password **`Admin123!`**:

| Role  | Email                        | Password   |
|-------|------------------------------|------------|
| Admin | `admin@siagamasyarakat.id`   | `Admin123!` |
| Warga | `budi@email.com`             | `Admin123!` |
