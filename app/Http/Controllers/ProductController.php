<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search')) {
            $query->where('nama', 'like', '%' . $request->search . '%');
        }

        if ($request->has('tipe')) {
            $query->where('tipe_pembeli', $request->tipe);
        }

        if ($request->has('low_stock')) {
            $query->where('stok', '<=', 5);
        }

        return response()->json([
            'data' => $query->orderBy('nama')->get()
        ]);
    }

    public function show(Product $product)
    {
        return response()->json(['data' => $product]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => 'required|string|max:255',
            'harga_jual' => 'required|integer|min:0',
            'stok' => 'required|integer|min:0',
            'tipe_pembeli' => 'required|in:umum,bengkel',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Produk berhasil ditambahkan.',
            'data' => $product
        ], 201);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'nama' => 'sometimes|string|max:255',
            'harga_jual' => 'sometimes|integer|min:0',
            'stok' => 'sometimes|integer|min:0',
            'tipe_pembeli' => 'sometimes|in:umum,bengkel',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Produk berhasil diupdate.',
            'data' => $product
        ]);
    }

    public function destroy(Product $product)
    {
        $usedInWO = $product->workOrderParts()->exists();
        $usedInSale = $product->saleItems()->exists();

        if ($usedInWO || $usedInSale) {
            return response()->json([
                'message' => 'Produk tidak bisa dihapus karena sudah digunakan dalam transaksi.'
            ], 422);
        }

        $product->delete();

        return response()->json([
            'message' => 'Produk berhasil dihapus.'
        ]);
    }

    public function updateStock(Request $request, Product $product)
    {
        $validated = $request->validate([
            'adjustment' => 'required|integer',
            'reason' => 'required|string|max:255',
        ]);

        $newStock = $product->stok + $validated['adjustment'];

        if ($newStock < 0) {
            return response()->json([
                'message' => 'Stok tidak boleh minus.'
            ], 422);
        }

        $product->update(['stok' => $newStock]);

        return response()->json([
            'message' => 'Stok berhasil diupdate.',
            'data' => $product
        ]);
    }
}
