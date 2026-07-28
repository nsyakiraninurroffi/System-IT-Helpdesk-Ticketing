<?php
// ============================================================
// koneksi.php - Konfigurasi & Koneksi Database
// IT Helpdesk & Ticketing System
// ============================================================

define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'db_it_helpdesk');
define('DB_CHARSET',  'utf8mb4');

$koneksi = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

// Cek koneksi
if ($koneksi->connect_error) {
    die(json_encode([
        'status'  => 'error',
        'message' => 'Koneksi database gagal: ' . $koneksi->connect_error
    ]));
}

// Set charset agar mendukung karakter UTF-8 (emoji, dll)
$koneksi->set_charset(DB_CHARSET);
