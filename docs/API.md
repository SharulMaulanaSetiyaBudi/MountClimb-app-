# MountClimb REST API

Base URL (Laragon): `http://localhost:8000/api` (sesuaikan dengan `api_config.dart`).
Format respons: JSON. Endpoint terproteksi memakai header `Authorization: Bearer <token>` (Laravel Sanctum).

## Ringkasan Endpoint

| Method | Endpoint | Auth | Keterangan |
|---|---|---|---|
| POST | `/register` | - | Daftar akun baru |
| POST | `/login` | - | Login, mengembalikan token |
| POST | `/check-email` | - | Cek email terdaftar (halaman Lupa Password) |
| GET | `/mountains` | - | Daftar semua gunung |
| GET | `/gunung` | - | Alias lama dari `/mountains` |
| GET | `/mountains/{slug}` | - | Detail satu gunung |
| GET | `/mountains/{slug}/quota?date=YYYY-MM-DD` | - | Sisa kuota harian |
| GET | `/user` | Ya | Data user yang sedang login |
| POST | `/logout` | Ya | Hapus token yang sedang dipakai |
| GET | `/bookings` | Ya | Daftar tiket milik user |
| POST | `/bookings` | Ya | Buat booking (multipart/form-data) |
| GET | `/bookings/{kode}/id-photo` | Ya | Lihat foto KTP (hanya pemilik) |

## Autentikasi

### POST `/register`
| Field | Aturan |
|---|---|
| `name` | wajib, string, maks 255 |
| `email` | wajib, format email, unik |
| `password` | wajib, string, min 6 |

Sukses `201`: `{ status, message, token, user }`. Gagal validasi `422`.

### POST `/login`
Body: `email`, `password`.
Sukses `200`: `{ status, message, token, user }`. Salah email/password `401`: `{ status: "error", message: "Email atau password salah" }`.

### POST `/check-email`
Body: `email`. Respons: `{ "exists": true|false }`.

### POST `/logout` (auth)
Menghapus token yang dipakai. Respons: `{ status: "success", message: "Logout berhasil" }`.

## Gunung

### GET `/mountains` dan GET `/mountains/{slug}`
Respons: `{ status: "success", data: ... }`. Objek gunung:

`id` (slug), `name`, `location`, `height_meters`, `rating`, `price`, `difficulty` (Mudah/Sedang/Sulit), `description`, `hiking_info`, `thumbnail`, `images`, `daily_quota`.

Slug tidak ditemukan: `404`.

### GET `/mountains/{slug}/quota?date=2026-12-01`
Parameter `date` wajib (format tanggal), jika tidak ada `422`.

```json
{ "status": "success", "data": { "date": "2026-12-01", "daily_quota": 500, "remaining": 480 } }
```

Booking berstatus `Dibatalkan` tidak dihitung sebagai kuota terpakai.

## Booking

### GET `/bookings` (auth)
Daftar tiket milik user yang login, terbaru dulu.

### POST `/bookings` (auth, `multipart/form-data`)
| Field | Aturan |
|---|---|
| `mountain_id` | wajib, slug gunung yang ada |
| `holder_name` | wajib, maks 255 |
| `date` | wajib, tanggal, tidak boleh sebelum hari ini |
| `end_date` | opsional, harus setelah `date` |
| `hiker_count` | wajib, integer 1-50 |
| `phone` | wajib, maks 20 |
| `emergency_name` | wajib, maks 255 |
| `emergency_phone` | wajib, maks 20 |
| `members[]` | opsional, nama anggota rombongan |
| `id_photo` | wajib, gambar jpg/jpeg/png, maks 5 MB |

Perilaku:
- Kode booking dibuat otomatis berformat `MC-XXXXXXXX`.
- Total harga = harga gunung x jumlah pendaki.
- Pengecekan kuota dilakukan dalam transaksi dengan kunci baris (`lockForUpdate`) agar dua booking bersamaan tidak melewati kuota.
- Kuota tidak cukup: `422` dan foto KTP yang sempat tersimpan dihapus.
- Foto KTP disimpan di disk privat `storage/app/private/ktp`, tidak bisa diakses lewat URL publik.

Sukses `201`: `{ status, message: "Booking berhasil", data: <booking> }`.

### GET `/bookings/{kode}/id-photo` (auth)
Mengembalikan file foto KTP. Hanya pemilik booking yang bisa mengakses, selain itu `404`.
