<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nomor_telepon' => fake()->numerify('08##########'),
            'role' => User::ROLE_VENDOR,
            'status_akun' => User::STATUS_AKTIF,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_ADMIN,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function eo(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_EO,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_VENDOR,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function pemda(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_PEMDA,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function komunitas(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_KOMUNITAS,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function akademisi(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_AKADEMISI,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function media(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_MEDIA,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function pengunjung(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_PENGUNJUNG,
            'status_akun' => User::STATUS_AKTIF,
        ]);
    }

    public function menunggu(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_akun' => User::STATUS_MENUNGGU,
        ]);
    }

    public function ditolak(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_akun' => User::STATUS_DITOLAK,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
