
# Aplikasi To-Do List

## Deskripsi
Aplikasi web untuk mencatat dan mengelola tugas harian menggunakan PHP Native, MySQL, HTML, CSS, dan Bootstrap 5.

## Fitur
- Tambah tugas.
- Tampilkan daftar tugas.
- Edit judul tugas.
- Tandai tugas selesai atau belum selesai.
- Hapus tugas dengan konfirmasi.
- Statistik dan progres penyelesaian.
- Validasi input dan notifikasi.
- Penyimpanan data permanen menggunakan MySQL.

## Teknologi
- PHP Native
- MySQL
- MySQLi
- HTML5 dan CSS3
- Bootstrap 5.3.3
- XAMPP

## Struktur Folder
```text
todolist/
├── index.php
├── README.md
├── config/
│   └── koneksi.php
└── assets/
    └── css/
        └── style.css
```

## Database
Nama database: `todolist_db`

Nama tabel: `tasks`

Kolom:
- `id`: ID tugas, primary key dan auto increment.
- `title`: judul tugas.
- `status`: status tugas, belum atau selesai.
- `created_at`: waktu pembuatan.
- `updated_at`: waktu pembaruan.

## Cara Menjalankan
1. Jalankan Apache dan MySQL melalui XAMPP.
2. Buka `http://localhost/phpmyadmin`.
3. Impor atau jalankan SQL pembuatan database.
4. Pastikan konfigurasi pada `config/koneksi.php` sesuai.
5. Simpan proyek di `C:\xampp\htdocs\todolist`.
6. Buka `http://localhost/todolist/`.

## Pengujian
Uji fitur tambah, edit, hapus, perubahan status, validasi input, serta persistensi data setelah halaman dimuat ulang.

## Catatan
Data tugas disimpan dalam MySQL sehingga tetap tersedia setelah browser ditutup. Aplikasi ini merupakan proyek latihan dan belum menggunakan sistem autentikasi pengguna.

## Pengembang
Nama: [Nama Anda]
Proyek: Praktik Demonstrasi Pemrograman Web
