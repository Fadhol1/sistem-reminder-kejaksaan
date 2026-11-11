<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Akun Admin PIDUM (Operator Utama)
        User::firstOrCreate(
            ['email' => 'admin@kejaksaan.go.id'],
            [
                'name' => 'Admin PIDUM',
                'password' => Hash::make('password123'),
                'role' => 'pidum',
                'jabatan' => 'Petugas Administrasi Perkara Pidum',
            ]
        );

        // 2. Akun Jaksa Peneliti (Contoh)
        User::firstOrCreate(
            ['email' => 'jaksa@kejaksaan.go.id'],
            [
                'name' => 'Jaksa Peneliti, S.H.',
                'password' => Hash::make('password123'),
                'role' => 'jaksa',
                'jabatan' => 'Jaksa Pratama',
            ]
        );

        // 3. Seeder Berita Awal
        $this->call(BeritaSeeder::class);
    }
}
