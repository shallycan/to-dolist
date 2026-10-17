
<?php
/*
|--------------------------------------------------------------------------
| KONFIGURASI KONEKSI DATABASE
|--------------------------------------------------------------------------
| Sesuaikan username dan password dengan konfigurasi MySQL lokal.
*/

$host = 'localhost';
$username = 'root';
$password = '';
$database = 'todolist_db';

// Membuat koneksi ke MySQL.
$koneksi = new mysqli(
    $host,
    $username,
    $password,
    $database
);

// Memeriksa koneksi.
if ($koneksi->connect_error) {
    error_log('Database connection error: ' . $koneksi->connect_error);
    http_response_code(500);
    exit('Koneksi database gagal. Periksa konfigurasi MySQL.');
}

// Menggunakan UTF-8 agar karakter tersimpan dengan benar.
$koneksi->set_charset('utf8mb4');
