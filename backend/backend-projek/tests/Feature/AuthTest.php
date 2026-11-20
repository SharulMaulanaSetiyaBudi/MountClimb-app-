<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_berhasil_dan_mengembalikan_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Sharul',
            'email' => 'sharul@example.com',
            'password' => 'rahasia123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'sharul@example.com']);
    }

    public function test_register_gagal_jika_email_sudah_dipakai(): void
    {
        User::factory()->create(['email' => 'dobel@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Dobel',
            'email' => 'dobel@example.com',
            'password' => 'rahasia123',
        ])->assertStatus(422)->assertJsonValidationErrors('email');
    }

    public function test_register_gagal_jika_password_kurang_dari_6_karakter(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Pendek',
            'email' => 'pendek@example.com',
            'password' => '123',
        ])->assertStatus(422)->assertJsonValidationErrors('password');
    }

    public function test_login_berhasil(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'rahasia123',
        ])->assertStatus(200)
            ->assertJsonPath('message', 'Login berhasil')
            ->assertJsonStructure(['token']);
    }

    public function test_login_gagal_jika_password_salah(): void
    {
        User::factory()->create([
            'email' => 'salah@example.com',
            'password' => 'rahasia123',
        ]);

        $this->postJson('/api/login', [
            'email' => 'salah@example.com',
            'password' => 'bukan-passwordnya',
        ])->assertStatus(401)
            ->assertJsonPath('message', 'Email atau password salah');
    }

    public function test_check_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->postJson('/api/check-email', ['email' => 'ada@example.com'])
            ->assertOk()->assertJson(['exists' => true]);

        $this->postJson('/api/check-email', ['email' => 'tidakada@example.com'])
            ->assertOk()->assertJson(['exists' => false]);
    }

    public function test_endpoint_user_butuh_login(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_logout_menghapus_token(): void
    {
        User::factory()->create([
            'email' => 'keluar@example.com',
            'password' => 'rahasia123',
        ]);

        $token = $this->postJson('/api/login', [
            'email' => 'keluar@example.com',
            'password' => 'rahasia123',
        ])->json('token');

        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
