<div align="center">

# 🎫 DeskFlow // Enterprise IT Helpdesk & Ticketing System

[![PHP](https://img.shields.io/badge/PHP-Native-777BB4?style=flat&logo=php&logoColor=white)](https://php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-CSS-38BDF8?style=flat&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=flat&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Status](https://img.shields.io/badge/Status-Active%20Development-emerald?style=flat)]()

*Streamlining IT issue resolution with a sleek, high-performance interface and rock-solid B2B workflow architecture.*

</div>

---

## 🚀 About The Project

**DeskFlow** adalah platform manajemen layanan *IT Helpdesk & Ticketing* berbasis web yang didesain khusus untuk menjawab realita operasional perusahaan modern. Proyek ini memangkas kerumitan pelaporan kendala—mulai dari *hardware breakdown*, *software glitches*, hingga *network issues*—ke dalam satu sistem terpusat yang transparan, cepat, dan *accountable*.

Bukan sekadar *school assignment* biasa, sistem ini dirancang dengan standar arsitektur industri (*Enterprise-grade B2B flow*) menggunakan pendekatan *clean layout* dan *dark-mode aesthetic* khas *tech-core ecosystem*.

---

## 👥 Core Roles & Access Control

Sistem ini membagi hak akses ke dalam 3 entitas utama untuk menjaga efisiensi penanganan kendala:

1. **👨‍💻 Karyawan (User / Reporter):**
   * Membuat laporan tiket baru dengan memilih kategori masalah dan aset perangkat terdaftar.
   * Memantau *real-time status* perbaikan tiket (*Open* ➔ *In Progress* ➔ *Resolved*).
   * Memberikan *rating & feedback* kepuasan layanan setelah tiket selesai.
2. **🛠️ Teknisi IT (IT Support):**
   * Mengakses *Workspace* penugasan harian.
   * Melakukan *troubleshooting* dan memperbarui status pengerjaan tiket.
3. **📊 Admin IT (Managerial Dashboard):**
   * Mengelola master data sistem, kategori masalah, dan inventaris aset perangkat.
   * Melakukan disposisi tiket masuk ke teknisi yang tersedia.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, JavaScript, **Tailwind CSS (CDN)** — *Clean, responsive, and futuristic dark slate UI.*
* **Backend:** **PHP Native** — *Secure, lightweight, and fast routing.*
* **Database:** **MySQL / MariaDB** — *Relational database with normalized 6-table schema.*
* **Architecture:** MVC-inspired structure with secure session handling and SQL injection prevention.

---

## 🗄️ Database Architecture (ERD & Schema)

Struktur database **`db_it_helpdesk`** dirancang secara ter-normalisasi melalui 6 tabel utama yang saling berelasi erat:
* `pengguna` — Menyimpan data akun (Admin, Teknisi, Karyawan).
* `kategori_masalah` — Klasifikasi jenis kendala IT.
* `aset_perangkat` — Inventaris hardware/perangkat milik perusahaan.
* `tiket` — Pusat transaksi laporan kendala (*Core transaction*).
* `riwayat_penanganan` — *Troubleshooting log* oleh teknisi.
* `ulasan_layanan` — Catatan *CSAT rating* dari karyawan.

*(Visualisasi lengkap diagram ERD, Use Case, Activity, dan Sequence tersedia di folder `/UML` repository ini).*

---

## 📂 Project Directory Structure

Sistem IT Helpdesk & Ticketing/
├── codingan/                 # Core source code aplikasi web
│   ├── koneksi.php           # Konfigurasi database MySQL
│   ├── login.php             # Secure authentication & role redirection
│   ├── logout.php            # Session destruction handler
│   ├── dashboard_karyawan.php# Portal pelaporan & riwayat tiket user
│   ├── dashboard_teknisi.php # Workspace penanganan tiket teknisi
│   └── dashboard_admin.php   # Managerial dashboard & system settings
└── UML/                      # Dokumentasi perancangan sistem
    ├── Use Case.png
    ├── Activity.png
    ├── Sequence.png
    └── ERD.png

---

## ⚙️ Getting Started / Local Installation

Ingin menjalankan project ini secara lokal di komputermu? Ikuti *step-by-step* berikut:

1. **Clone repository ini** atau unduh sebagai ZIP:
   `git clone [https://github.com/username/deskflow-it-ticketing.git](https://github.com/username/deskflow-it-ticketing.git)`
2. Pindahkan folder project ke direktori web server lokalmu (contoh: `htdocs` di XAMPP).
3. Nyalakan **Apache** dan **MySQL** melalui XAMPP Control Panel.
4. Buka **phpMyAdmin** (`http://localhost/phpmyadmin`), buat database baru dengan nama `db_it_helpdesk`.
5. Import struktur tabel dan data *dummy* awal *(kamu bisa merujuk ke skrip SQL yang telah disiapkan di dokumen perancangan)*.
6. Akses aplikasi melalui browser:
   `http://localhost/Sistem%20IT%20Helpdesk%20&%20Ticketing/codingan/login.php`

---

## 🔐 Default Demo Accounts

Untuk pengujian cepat, kamu bisa masuk menggunakan kredensial *dummy* berikut:

| Role | Email | Password |
| :--- | :--- | :--- |
| **Admin IT** | `admin@helpdesk.com` | `admin123` |
| **Teknisi** | `teknisi@helpdesk.com` | `teknisi123` |
| **Karyawan** | `Risky@helpdesk.com` | `Risky123` |

---

## 💻 Author

* **Nesya Kirani Nurroffi** — *Software Engineering Student (XII PPLG-RPL)* 
* *Building clean code and aesthetic digital experiences.*

<div align="center">
  <p>✨ <i>Built with precision, coffee, and clean code vibes.</i> ✨</p>
</div>
