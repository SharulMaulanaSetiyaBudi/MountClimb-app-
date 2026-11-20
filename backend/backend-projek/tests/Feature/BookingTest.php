<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Gunung;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function buatGunung(int $kuota = 100): Gunung
    {
        return Gunung::create([
            'slug' => 'semeru',
            'nama_gunung' => 'Gunung Semeru',
            'lokasi' => 'Jawa Timur',
            'ketinggian' => 3676,
            'rating' => 4.8,
            'harga' => 90000,
            'tingkat_kesulitan' => 'Sulit',
            'deskripsi' => 'Deskripsi uji.',
            'info_pendakian' => 'Info uji.',
            'thumbnail' => 'assets/images/semeru.jpg',
            'gambar' => ['assets/images/semeru.jpg'],
            'kuota_harian' => $kuota,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'mountain_id' => 'semeru',
            'holder_name' => 'Sharul',
            'date' => now()->addDays(7)->toDateString(),
            'hiker_count' => 3,
            'phone' => '081234567890',
            'emergency_name' => 'Ibu',
            'emergency_phone' => '081200000000',
            'members' => ['Budi', 'Ani'],
            'id_photo' => UploadedFile::fake()->image('ktp.jpg'),
        ], $override);
    }

    public function test_booking_butuh_login(): void
    {
        $this->postJson('/api/bookings', [])->assertStatus(401);
        $this->getJson('/api/bookings')->assertStatus(401);
    }

    public function test_booking_berhasil(): void
    {
        $this->buatGunung();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->post('/api/bookings', $this->payload(), ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJsonPath('message', 'Booking berhasil')
            ->assertJsonPath('data.hiker_count', 3)
            ->assertJsonPath('data.total_price', 270000)
            ->assertJsonPath('data.status', 'Aktif');

        $this->assertMatchesRegularExpression('/^MC-[A-Z0-9]{8}$/', $response->json('data.booking_code'));
        $this->assertDatabaseCount('bookings', 1);
        $this->assertCount(1, Storage::disk('local')->files('ktp'));
    }

    public function test_booking_gagal_tanpa_foto_ktp(): void
    {
        $this->buatGunung();
        Sanctum::actingAs(User::factory()->create());

        $data = $this->payload();
        unset($data['id_photo']);

        $this->post('/api/bookings', $data, ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('id_photo');
    }

    public function test_booking_gagal_jika_tanggal_sudah_lewat(): void
    {
        $this->buatGunung();
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/bookings', $this->payload(['date' => now()->subDays(2)->toDateString()]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('date');
    }

    public function test_booking_gagal_jika_kuota_tidak_cukup_dan_foto_dihapus(): void
    {
        $this->buatGunung(kuota: 2);
        Sanctum::actingAs(User::factory()->create());

        $this->post('/api/bookings', $this->payload(['hiker_count' => 3]), ['Accept' => 'application/json'])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseCount('bookings', 0);
        $this->assertCount(0, Storage::disk('local')->files('ktp'));
    }

    public function test_daftar_tiket_hanya_milik_sendiri(): void
    {
        $gunung = $this->buatGunung();
        $saya = User::factory()->create();
        $lain = User::factory()->create();

        foreach ([[$saya, 'MC-SAYA0001'], [$lain, 'MC-LAIN0001']] as [$user, $kode]) {
            Booking::create([
                'kode_booking' => $kode,
                'user_id' => $user->id,
                'gunung_id' => $gunung->id,
                'atas_nama' => 'Uji',
                'tanggal_pendakian' => now()->addDays(5)->toDateString(),
                'jumlah_pendaki' => 1,
                'total_harga' => 90000,
                'status' => 'Aktif',
            ]);
        }

        Sanctum::actingAs($saya);

        $this->getJson('/api/bookings')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.booking_code', 'MC-SAYA0001');
    }

    public function test_foto_ktp_hanya_bisa_dilihat_pemilik(): void
    {
        $gunung = $this->buatGunung();
        $pemilik = User::factory()->create();
        $orangLain = User::factory()->create();

        Storage::disk('local')->put('ktp/contoh.jpg', 'isi-foto');
        Booking::create([
            'kode_booking' => 'MC-PEMILIK1',
            'user_id' => $pemilik->id,
            'gunung_id' => $gunung->id,
            'atas_nama' => 'Uji',
            'tanggal_pendakian' => now()->addDays(5)->toDateString(),
            'jumlah_pendaki' => 1,
            'total_harga' => 90000,
            'status' => 'Aktif',
            'foto_identitas' => 'ktp/contoh.jpg',
        ]);

        Sanctum::actingAs($orangLain);
        $this->getJson('/api/bookings/MC-PEMILIK1/id-photo')->assertStatus(404);

        Sanctum::actingAs($pemilik);
        $this->get('/api/bookings/MC-PEMILIK1/id-photo')->assertOk();
    }
}
