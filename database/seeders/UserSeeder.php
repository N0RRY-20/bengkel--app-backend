<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Mechanic;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // ========== OWNER ==========
        User::create([
            'name' => 'Owner Bengkel',
            'email' => 'owner@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'owner',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // ========== ADMIN ==========
        User::create([
            'name' => 'Admin Bengkel',
            'email' => 'admin@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // ========== KASIR ==========
        User::create([
            'name' => 'Kasir Satu',
            'email' => 'kasir@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Kasir Dua',
            'email' => 'kasir2@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        // ========== MEKANIK ==========
        $mekanik1 = User::create([
            'name' => 'Budi Mekanik',
            'email' => 'budi@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'mekanik',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Mechanic::create([
            'user_id' => $mekanik1->id,
            'persentase_jasa' => 0.30,
        ]);

        $mekanik2 = User::create([
            'name' => 'Andi Mekanik',
            'email' => 'andi@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'mekanik',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Mechanic::create([
            'user_id' => $mekanik2->id,
            'persentase_jasa' => 0.25,
        ]);

        $mekanik3 = User::create([
            'name' => 'Candra Mekanik',
            'email' => 'candra@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'mekanik',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        Mechanic::create([
            'user_id' => $mekanik3->id,
            'persentase_jasa' => 0.35,
        ]);

        // ========== USER NON-AKTIF (untuk testing) ==========
        User::create([
            'name' => 'User Nonaktif',
            'email' => 'nonaktif@bengkel.com',
            'password' => Hash::make('password'),
            'role' => 'kasir',
            'is_active' => false,
            'email_verified_at' => now(),
        ]);
    }
}
