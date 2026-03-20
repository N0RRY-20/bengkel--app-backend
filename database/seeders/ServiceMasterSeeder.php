<?php

namespace Database\Seeders;

use App\Models\ServiceMaster;
use Illuminate\Database\Seeder;

class ServiceMasterSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            ['nama_jasa' => 'Ganti Oli Mesin', 'harga' => 25000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Oli Gardan', 'harga' => 20000, 'is_active' => true],
            ['nama_jasa' => 'Service Ringan', 'harga' => 50000, 'is_active' => true],
            ['nama_jasa' => 'Service Besar', 'harga' => 150000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Kampas Rem Depan', 'harga' => 35000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Kampas Rem Belakang', 'harga' => 35000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Ban Depan', 'harga' => 20000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Ban Belakang', 'harga' => 25000, 'is_active' => true],
            ['nama_jasa' => 'Setting Karburator', 'harga' => 30000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Busi', 'harga' => 15000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Rantai + Gear', 'harga' => 50000, 'is_active' => true],
            ['nama_jasa' => 'Turun Mesin (Overhaul)', 'harga' => 500000, 'is_active' => true],
            ['nama_jasa' => 'Ganti V-Belt', 'harga' => 40000, 'is_active' => true],
            ['nama_jasa' => 'Ganti Roller', 'harga' => 30000, 'is_active' => true],
            ['nama_jasa' => 'Balancing Roda', 'harga' => 25000, 'is_active' => true],
        ];

        foreach ($services as $service) {
            ServiceMaster::create($service);
        }
    }
}
