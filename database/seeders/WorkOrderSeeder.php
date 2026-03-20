<?php

namespace Database\Seeders;

use App\Models\WorkOrder;
use App\Models\Mechanic;
use App\Models\User;
use App\Models\ServiceMaster;
use App\Models\Product;
use Illuminate\Database\Seeder;

class WorkOrderSeeder extends Seeder
{
    public function run(): void
    {
        $kasir = User::where('role', 'kasir')->first();
        $mechanics = Mechanic::all();
        $services = ServiceMaster::all();
        $products = Product::where('stok', '>', 0)->get();

        if ($mechanics->isEmpty() || !$kasir) {
            return;
        }

        // WO 1: Dikerjakan
        $wo1 = WorkOrder::create([
            'nama_pemilik' => 'Pak Budi',
            'plat_nomor' => 'B 1234 ABC',
            'mechanic_id' => $mechanics->random()->id,
            'created_by' => $kasir->id,
            'status' => 'dikerjakan',
        ]);

        if ($services->count() > 0) {
            $service = $services->random();
            $wo1->services()->create([
                'nama_jasa' => $service->nama_jasa,
                'harga_jasa' => $service->harga,
            ]);
        }

        // WO 2: Selesai (menunggu bayar)
        $wo2 = WorkOrder::create([
            'nama_pemilik' => 'Bu Siti',
            'plat_nomor' => 'D 5678 XYZ',
            'mechanic_id' => $mechanics->random()->id,
            'created_by' => $kasir->id,
            'status' => 'selesai',
        ]);

        if ($services->count() >= 2) {
            $selectedServices = $services->random(2);
            foreach ($selectedServices as $service) {
                $wo2->services()->create([
                    'nama_jasa' => $service->nama_jasa,
                    'harga_jasa' => $service->harga,
                ]);
            }
        }

        if ($products->count() > 0) {
            $product = $products->random();
            $wo2->parts()->create([
                'product_id' => $product->id,
                'nama_produk' => $product->nama,
                'harga' => $product->harga_jual,
                'qty' => 1,
                'diskon' => 0,
            ]);
        }

        // WO 3: Dibayar (completed)
        $wo3 = WorkOrder::create([
            'nama_pemilik' => 'Mas Joko',
            'plat_nomor' => 'AB 9012 KLM',
            'mechanic_id' => $mechanics->random()->id,
            'created_by' => $kasir->id,
            'status' => 'dibayar',
        ]);

        if ($services->count() > 0) {
            $service = $services->random();
            $wo3->services()->create([
                'nama_jasa' => $service->nama_jasa,
                'harga_jasa' => $service->harga,
            ]);
        }
    }
}
