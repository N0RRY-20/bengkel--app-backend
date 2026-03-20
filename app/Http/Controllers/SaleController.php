<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $query = Sale::with(['kasir', 'items']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return response()->json([
            'data' => $query->latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipe_pembeli' => 'required|in:umum,bengkel',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'items.*.diskon' => 'nullable|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            $sale = Sale::create([
                'tipe_pembeli' => $validated['tipe_pembeli'],
                'total' => 0,
                'kasir_id' => $request->user()->id,
                'status' => 'pending',
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);

                if ($product->stok < $item['qty']) {
                    throw new \Exception("Stok {$product->nama} tidak cukup.");
                }

                $subtotal = ($product->harga_jual * $item['qty']) - ($item['diskon'] ?? 0);
                $total += $subtotal;

                $sale->items()->create([
                    'product_id' => $product->id,
                    'nama_produk' => $product->nama,
                    'harga' => $product->harga_jual,
                    'qty' => $item['qty'],
                    'diskon' => $item['diskon'] ?? 0,
                ]);

                $product->decrement('stok', $item['qty']);
            }

            $sale->update(['total' => $total]);

            DB::commit();

            return response()->json([
                'message' => 'Penjualan berhasil dibuat.',
                'data' => $sale->load('items')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function pay(Request $request, Sale $sale)
    {
        if ($sale->status !== 'pending') {
            return response()->json([
                'message' => 'Penjualan sudah diproses.'
            ], 422);
        }

        $validated = $request->validate([
            'metode' => 'required|in:cash,transfer,qris',
        ]);

        Payment::create([
            'source_type' => 'sale',
            'source_id' => $sale->id,
            'total' => $sale->total,
            'metode' => $validated['metode'],
            'kasir_id' => $request->user()->id,
        ]);

        $sale->update(['status' => 'selesai']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'payment_sale',
            'description' => "Pembayaran penjualan #{$sale->id} sebesar Rp " . number_format($sale->total),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil.',
            'data' => $sale
        ]);
    }
}
