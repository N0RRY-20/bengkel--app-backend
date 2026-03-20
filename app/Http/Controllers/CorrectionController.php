<?php

namespace App\Http\Controllers;

use App\Models\Correction;
use App\Models\WorkOrder;
use App\Models\Sale;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class CorrectionController extends Controller
{
    public function index()
    {
        $corrections = Correction::with('owner')
            ->latest()
            ->paginate(20);

        return response()->json($corrections);
    }

    public function show(Correction $correction)
    {
        $correction->load('owner');

        return response()->json(['data' => $correction]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'source_type' => 'required|in:work_order,sale',
            'source_id' => 'required|integer',
            'alasan' => 'required|string|min:10|max:500',
            'refund_amount' => 'nullable|integer|min:0',
        ]);

        if ($validated['source_type'] === 'work_order') {
            $source = WorkOrder::with(['services', 'parts'])->findOrFail($validated['source_id']);
        } else {
            $source = Sale::with('items')->findOrFail($validated['source_id']);
        }

        $sebelum = $source->toArray();
        $sesudah = $source->fresh()->toArray();

        $correction = Correction::create([
            'source_type' => $validated['source_type'],
            'source_id' => $validated['source_id'],
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
            'alasan' => $validated['alasan'],
            'owner_id' => $request->user()->id,
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'correction',
            'description' => "Koreksi {$validated['source_type']} #{$validated['source_id']}: {$validated['alasan']}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'Koreksi berhasil dicatat.',
            'data' => $correction
        ], 201);
    }
}
