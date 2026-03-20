<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\WorkOrderService;
use App\Models\WorkOrderPart;
use App\Models\ServiceMaster;
use App\Models\Product;
use App\Models\Payment;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = WorkOrder::with(['mechanic.user', 'creator', 'services', 'parts']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->user()->isMekanik()) {
            $mechanicId = $request->user()->mechanic?->id;
            $query->where('mechanic_id', $mechanicId);
        }

        return response()->json([
            'data' => $query->latest()->get()
        ]);
    }

    public function show(WorkOrder $workOrder)
    {
        $workOrder->load(['mechanic.user', 'creator', 'services', 'parts.product']);

        $totalJasa = $workOrder->services->sum('harga_jasa');
        $totalParts = $workOrder->parts->sum(function ($part) {
            return ($part->harga * $part->qty) - $part->diskon;
        });

        return response()->json([
            'data' => $workOrder,
            'total_jasa' => $totalJasa,
            'total_parts' => $totalParts,
            'grand_total' => $totalJasa + $totalParts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_pemilik' => 'required|string|max:255',
            'plat_nomor' => 'required|string|max:20',
            'mechanic_id' => 'required|exists:mechanics,id',
        ]);

        $workOrder = WorkOrder::create([
            'nama_pemilik' => $validated['nama_pemilik'],
            'plat_nomor' => strtoupper($validated['plat_nomor']),
            'mechanic_id' => $validated['mechanic_id'],
            'created_by' => $request->user()->id,
            'status' => 'dikerjakan',
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create_work_order',
            'description' => "Membuat WO #{$workOrder->id} untuk {$workOrder->plat_nomor}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Work Order berhasil dibuat.',
            'data' => $workOrder->load('mechanic.user'),
        ], 201);
    }

    public function addService(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status === 'dibayar') {
            return response()->json([
                'message' => 'Work Order sudah dibayar, tidak bisa diedit.'
            ], 403);
        }

        $validated = $request->validate([
            'service_master_id' => 'required|exists:services_master,id',
        ]);

        $serviceMaster = ServiceMaster::findOrFail($validated['service_master_id']);

        $service = $workOrder->services()->create([
            'nama_jasa' => $serviceMaster->nama_jasa,
            'harga_jasa' => $serviceMaster->harga,
        ]);

        return response()->json([
            'message' => 'Jasa berhasil ditambahkan.',
            'data' => $service,
        ]);
    }

    public function removeService(WorkOrder $workOrder, $serviceId)
    {
        if ($workOrder->status === 'dibayar') {
            return response()->json([
                'message' => 'Work Order sudah dibayar, tidak bisa diedit.'
            ], 403);
        }

        $workOrder->services()->where('id', $serviceId)->delete();

        return response()->json([
            'message' => 'Jasa berhasil dihapus.',
        ]);
    }

    public function addPart(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status === 'dibayar') {
            return response()->json([
                'message' => 'Work Order sudah dibayar, tidak bisa diedit.'
            ], 403);
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'qty' => 'required|integer|min:1',
            'diskon' => 'nullable|integer|min:0',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        if ($product->stok < $validated['qty']) {
            return response()->json([
                'message' => 'Stok tidak cukup. Tersedia: ' . $product->stok
            ], 422);
        }

        $part = $workOrder->parts()->create([
            'product_id' => $product->id,
            'nama_produk' => $product->nama,
            'harga' => $product->harga_jual,
            'qty' => $validated['qty'],
            'diskon' => $validated['diskon'] ?? 0,
        ]);

        $product->decrement('stok', $validated['qty']);

        return response()->json([
            'message' => 'Sparepart berhasil ditambahkan.',
            'data' => $part,
        ]);
    }

    public function removePart(WorkOrder $workOrder, $partId)
    {
        if ($workOrder->status === 'dibayar') {
            return response()->json([
                'message' => 'Work Order sudah dibayar, tidak bisa diedit.'
            ], 403);
        }

        $part = $workOrder->parts()->findOrFail($partId);

        Product::where('id', $part->product_id)->increment('stok', $part->qty);

        $part->delete();

        return response()->json([
            'message' => 'Sparepart berhasil dihapus.',
        ]);
    }

    public function finish(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'dikerjakan') {
            return response()->json([
                'message' => 'Status tidak valid untuk ditandai selesai.'
            ], 422);
        }

        $workOrder->update(['status' => 'selesai']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'finish_work_order',
            'description' => "WO #{$workOrder->id} ditandai selesai",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Work Order ditandai selesai.',
            'data' => $workOrder,
        ]);
    }

    public function pay(Request $request, WorkOrder $workOrder)
    {
        if ($workOrder->status !== 'selesai') {
            return response()->json([
                'message' => 'Work Order belum selesai dikerjakan.'
            ], 422);
        }

        $validated = $request->validate([
            'metode' => 'required|in:cash,transfer,qris',
        ]);

        $totalJasa = $workOrder->services()->sum('harga_jasa');
        $totalParts = $workOrder->parts()->sum(DB::raw('(harga * qty) - diskon'));
        $total = $totalJasa + $totalParts;

        $payment = Payment::create([
            'source_type' => 'work_order',
            'source_id' => $workOrder->id,
            'total' => $total,
            'metode' => $validated['metode'],
            'kasir_id' => $request->user()->id,
        ]);

        $workOrder->update(['status' => 'dibayar']);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'payment_work_order',
            'description' => "Pembayaran WO #{$workOrder->id} sebesar Rp " . number_format($total),
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Pembayaran berhasil.',
            'data' => $payment,
            'total_jasa' => $totalJasa,
            'total_parts' => $totalParts,
            'grand_total' => $total,
        ]);
    }
}
