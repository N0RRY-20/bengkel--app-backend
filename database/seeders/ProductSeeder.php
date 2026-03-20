<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            // Oli
            ['nama' => 'Oli Yamalube 0.8L', 'harga_jual' => 45000, 'stok' => 50, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Oli Castrol 1L', 'harga_jual' => 75000, 'stok' => 30, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Oli Federal 0.8L', 'harga_jual' => 38000, 'stok' => 40, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Oli Gardan', 'harga_jual' => 25000, 'stok' => 20, 'tipe_pembeli' => 'umum'],

            // Busi
            ['nama' => 'Busi NGK Standard', 'harga_jual' => 18000, 'stok' => 100, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Busi Iridium', 'harga_jual' => 85000, 'stok' => 25, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Busi Denso', 'harga_jual' => 22000, 'stok' => 60, 'tipe_pembeli' => 'umum'],

            // Kampas Rem
            ['nama' => 'Kampas Rem Depan Vario', 'harga_jual' => 35000, 'stok' => 30, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Kampas Rem Belakang Beat', 'harga_jual' => 28000, 'stok' => 40, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Kampas Rem Depan Mio', 'harga_jual' => 32000, 'stok' => 35, 'tipe_pembeli' => 'umum'],

            // Ban
            ['nama' => 'Ban IRC 70/90-17', 'harga_jual' => 150000, 'stok' => 15, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Ban IRC 80/90-17', 'harga_jual' => 175000, 'stok' => 12, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Ban FDR 90/80-14', 'harga_jual' => 200000, 'stok' => 10, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Ban Dalam 17', 'harga_jual' => 25000, 'stok' => 50, 'tipe_pembeli' => 'umum'],

            // V-Belt & Roller
            ['nama' => 'V-Belt Vario 125', 'harga_jual' => 120000, 'stok' => 20, 'tipe_pembeli' => 'umum'],
            ['nama' => 'V-Belt Beat', 'harga_jual' => 95000, 'stok' => 25, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Roller Set Vario', 'harga_jual' => 45000, 'stok' => 30, 'tipe_pembeli' => 'umum'],

            // Rantai & Gear
            ['nama' => 'Rantai SSS 428', 'harga_jual' => 85000, 'stok' => 20, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Gear Set Supra X', 'harga_jual' => 150000, 'stok' => 15, 'tipe_pembeli' => 'umum'],

            // Aki
            ['nama' => 'Aki Yuasa GTZ5S', 'harga_jual' => 185000, 'stok' => 10, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Aki GS Astra', 'harga_jual' => 165000, 'stok' => 12, 'tipe_pembeli' => 'umum'],

            // Lampu
            ['nama' => 'Lampu LED H4', 'harga_jual' => 75000, 'stok' => 30, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Lampu Sein LED', 'harga_jual' => 25000, 'stok' => 50, 'tipe_pembeli' => 'umum'],
            ['nama' => 'Lampu Rem LED', 'harga_jual' => 35000, 'stok' => 40, 'tipe_pembeli' => 'umum'],

            // Stok Rendah (untuk testing)
            ['nama' => 'Filter Udara Racing', 'harga_jual' => 55000, 'stok' => 3, 'tipe_pembeli' => 'umum'],
            ['nama' => 'CDI Racing', 'harga_jual' => 150000, 'stok' => 2, 'tipe_pembeli' => 'bengkel'],

            // Stok Habis (untuk testing)
            ['nama' => 'Kopling Set Racing', 'harga_jual' => 250000, 'stok' => 0, 'tipe_pembeli' => 'bengkel'],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
