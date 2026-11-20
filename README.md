# MountClimb

Aplikasi pemesanan tiket pendakian gunung berbasis Flutter (frontend) dan Laravel + MySQL (backend).

## Identitas

| | |
|---|---|
| Nama | Sharul Maulana Setiya Budi |
| NIM | 24051214219 |
| Kelas | Sistem Informasi G 2024 |

## Tentang Aplikasi

MountClimb membantu pendaki memesan tiket pendakian secara online tanpa antre di basecamp. Pengguna dapat menjelajahi gunung, melihat detail dan kuota harian, memilih tanggal pendakian, mengisi data rombongan, lalu membayar dan mendapat kode booking.

### Fitur
- **Autentikasi** — daftar, login, lupa password, logout (token dari backend).
- **Home** — header animasi, pencarian, akses cepat ke tiket, riwayat, dan panduan pendakian.
- **Explore** — cari gunung dan filter tingkat kesulitan (Mudah / Sedang / Sulit).
- **Detail Gunung** — slider foto, ketinggian, rating, harga, dan informasi pendakian.
- **Pemesanan Tiket** — pilih tanggal naik/turun, jumlah pendaki, paket Reguler atau Rombongan (min. 5 orang), cek sisa kuota harian dari database.
- **Data Pendaki** — data ketua, anggota rombongan, kontak darurat, dan upload foto KTP.
- **Pembayaran & Kode Booking** — pilih metode bayar, kode booking dibuat otomatis.
- **Tiket Saya & Riwayat** — tiket aktif dan riwayat pendakian dari database.
- **Profil & Pengaturan** — edit data diri, notifikasi, satuan ketinggian.
- **Panduan Pendakian** — checklist perlengkapan dan tips keselamatan.

### Teknologi
- Frontend: Flutter (Dart), paket `http`, `image_picker`
- Backend: Laravel (REST API, autentikasi token dengan Laravel Sanctum)
- Database: MySQL

## Struktur Folder

```
MountClimb/
├── backend/
│   └── backend-projek/     # REST API Laravel
├── database-script/        # Script SQL MySQL
├── docs/                   # Dokumentasi API
├── frontend/
│   └── mountclimb/         # Aplikasi Flutter
├── .gitignore
└── README.md
```

## Cara Menjalankan

### 1. Database
1. Jalankan Laragon (**Start All**: Apache dan MySQL).
2. Buka HeidiSQL atau phpMyAdmin dari Laragon.
3. Import file `database-script/mountclimb_clean.sql`. Database `db_mountclimb` akan dibuat otomatis oleh file tersebut.

### 2. Backend (Laravel)
```bash
cd backend/backend-projek
composer install
copy .env.example .env
php artisan key:generate
```
Lalu buka file `.env` dan pastikan pengaturan database seperti berikut (sesuaikan username dan password MySQL di Laragon):
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=db_mountclimb
DB_USERNAME=root
DB_PASSWORD=
```
Jalankan server:
```bash
php artisan serve
```
API tersedia di `http://127.0.0.1:8000/api`.

### 3. Frontend (Flutter)
```bash
cd frontend/mountclimb
flutter pub get
flutter run
```
Alamat API diatur di `lib/service/api_config.dart`. Emulator Android memakai `http://10.0.2.2:8000/api`, sedangkan web/desktop memakai `http://127.0.0.1:8000/api`. Untuk HP asli, ganti dengan IP LAN komputer, misalnya `http://192.168.1.10:8000/api`.

## Dokumentasi API

Daftar endpoint, aturan validasi, dan contoh respons ada di [docs/API.md](docs/API.md).

## Pengujian (Backend)

Test otomatis memakai database SQLite sementara di memori, jadi data MySQL tidak tersentuh.

```bash
cd backend/backend-projek
php artisan test
```

Cakupan test: autentikasi (register, login, logout), daftar/detail gunung dan kuota harian, serta booking (validasi, batas kuota, dan akses foto KTP hanya untuk pemilik).

Detail tiap bagian ada di README masing-masing folder.