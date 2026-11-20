<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Gunung;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MountainTest extends TestCase
{
    use RefreshDatabase;

    private function buatGunung(string $slug = 'semeru', int $kuota = 100): Gunung
    {
        return Gunung::create([
            'slug' => $slug,
            'nama_gunung' => 'Gunung ' . ucfirst($slug),
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'rating' => 4.8,
            'harga' => 90000,
            'tingkat_kesulitan' => 'Sulit',
            'deskripsi' => 'Deskripsi uji.',
            'info_pendakian' => 'Info uji.',
            'thumbnail' => "assets/images/$slug.jpg",
            'gambar' => ["assets/images/$slug.jpg"],
            'kuota_harian' => $kuota,
        ]);
    }

    private function buatBooking(Gunung $gunung, int $jumlah, string $tanggal, string $status = 'Aktif'): Booking
    {
        return Booking::create([
            'kode_booking' => 'MC-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'user_id' => User::factory()->create()->id,
            'gunung_id' => $gunung->id,
            'atas_nama' => 'Uji Coba',
            'tanggal_pendakian' => $tanggal,
            'jumlah_pendaki' => $jumlah,
            'total_harga' => $gunung->harga * $jumlah,
            'status' => $status,
        ]);
    }

    public function test_daftar_gunung(): void
    {
        $this->buatGunung('semeru');
        $this->buatGunung('rinjani');

        $this->getJson('/api/mountains')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', 'semeru');
    }

    public function test_alias_gunung_sama_dengan_mountains(): void
    {
        $this->buatGunung('semeru');

        $this->getJson('/api/gunung')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_detail_gunung_berdasarkan_slug(): void
    {
        $this->buatGunung('semeru');

        $this->getJson('/api/mountains/semeru')
            ->assertOk()
            ->assertJsonPath('data.name', 'Gunung Semeru')
            ->assertJsonPath('data.price', 90000)
            ->assertJsonPath('data.daily_quota', 100);
    }

    public function test_detail_gunung_tidak_ditemukan(): void
    {
        $this->getJson('/api/mountains/tidak-ada')->assertStatus(404);
    }

    public function test_kuota_penuh_jika_belum_ada_booking(): void
    {
        $this->buatGunung('semeru', 100);

        $this->getJson('/api/mountains/semeru/quota?date=2026-12-01')
            ->assertOk()
            ->assertJsonPath('data.daily_quota', 100)
            ->assertJsonPath('data.remaining', 100);
    }

    public function test_kuota_berkurang_oleh_booking_aktif_saja(): void
    {
        $gunung = $this->buatGunung('semeru', 100);
        $this->buatBooking($gunung, 10, '2026-12-01');
        $this->buatBooking($gunung, 5, '2026-12-01', 'Dibatalkan');
        $this->buatBooking($gunung, 20, '2026-12-02');

        $this->getJson('/api/mountains/semeru/quota?date=2026-12-01')
            ->assertOk()
            ->assertJsonPath('data.remaining', 90);
    }

    public function test_kuota_butuh_parameter_date(): void
    {
        $this->buatGunung('semeru');

        $this->getJson('/api/mountains/semeru/quota')
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }
}
