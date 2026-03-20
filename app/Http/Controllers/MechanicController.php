<?php

namespace App\Http\Controllers;

use App\Models\Mechanic;
use App\Models\User;
use Illuminate\Http\Request;

class MechanicController extends Controller
{
    public function index()
    {
        $mechanics = Mechanic::with('user')->get()->map(function ($mechanic) {
            return [
                'id' => $mechanic->id,
                'user_id' => $mechanic->user_id,
                'nama' => $mechanic->user->name,
                'email' => $mechanic->user->email,
                'persentase_jasa' => $mechanic->persentase_jasa,
                'persentase_display' => ($mechanic->persentase_jasa * 100) . '%',
            ];
        });

        return response()->json(['data' => $mechanics]);
    }

    public function show(Mechanic $mechanic)
    {
        $mechanic->load('user');

        return response()->json(['data' => $mechanic]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id|unique:mechanics,user_id',
            'persentase_jasa' => 'required|numeric|min:0|max:1',
        ]);

        $user = User::findOrFail($validated['user_id']);
        if ($user->role !== 'mekanik') {
            return response()->json([
                'message' => 'User harus memiliki role mekanik.'
            ], 422);
        }

        $mechanic = Mechanic::create($validated);
        $mechanic->load('user');

        return response()->json([
            'message' => 'Data mekanik berhasil dibuat.',
            'data' => $mechanic
        ], 201);
    }

    public function update(Request $request, Mechanic $mechanic)
    {
        $validated = $request->validate([
            'persentase_jasa' => 'required|numeric|min:0|max:1',
        ]);

        $mechanic->update($validated);

        return response()->json([
            'message' => 'Data mekanik berhasil diupdate.',
            'data' => $mechanic
        ]);
    }

    public function earnings(Request $request, Mechanic $mechanic)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $workOrders = $mechanic->workOrders()
            ->where('status', 'dibayar')
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->with('services')
            ->get();

        $totalJasa = $workOrders->flatMap->services->sum('harga_jasa');
        $pendapatan = $totalJasa * $mechanic->persentase_jasa;

        return response()->json([
            'mechanic' => $mechanic->user->name,
            'period' => "{$month}/{$year}",
            'total_work_orders' => $workOrders->count(),
            'total_jasa' => $totalJasa,
            'persentase' => $mechanic->persentase_jasa,
            'pendapatan' => round($pendapatan),
        ]);
    }

    public function myEarnings(Request $request)
    {
        $mechanic = $request->user()->mechanic;

        if (!$mechanic) {
            return response()->json([
                'message' => 'Data mekanik tidak ditemukan.'
            ], 404);
        }

        return $this->earnings($request, $mechanic);
    }
}
