# MountClimb API

REST API untuk aplikasi pemesanan tiket pendakian MountClimb. Dibuat dengan Laravel dan Laravel Sanctum (autentikasi token), database MySQL.

## Menjalankan

```bash
composer install
copy .env.example .env
php artisan key:generate
```

Import `../../database-script/mountclimb_clean.sql` ke MySQL (database `db_mountclimb`), lalu:

```bash
php artisan serve
```

API tersedia di `http://127.0.0.1:8000/api`.

## Dokumentasi endpoint

Lihat [../../docs/API.md](../../docs/API.md).

## Struktur penting

| Lokasi | Isi |
|---|---|
| `routes/api.php` | Definisi semua endpoint |
| `app/Http/Controllers/AuthController.php` | Register, login, logout, cek email |
| `app/Http/Controllers/GunungController.php` | Daftar gunung, detail, sisa kuota |
| `app/Http/Controllers/BookingController.php` | Booking tiket dan akses foto KTP |
| `app/Models/` | Model `User`, `Gunung`, `Booking` |
| `database/migrations/` | Skema tabel |
| `database/seeders/GunungSeeder.php` | Data awal gunung |
| `tests/Feature/` | Test otomatis (auth, gunung, booking) |

## Menjalankan test

```bash
php artisan test
```

Test memakai SQLite di memori, jadi data MySQL tidak tersentuh.
