<?php

namespace App\Http\Controllers;

use App\Models\ServiceMaster;
use Illuminate\Http\Request;

class ServiceMasterController extends Controller
{
    public function index(Request $request)
    {
        $query = ServiceMaster::query();

        if (!$request->has('all')) {
            $query->where('is_active', true);
        }

        return response()->json([
            'data' => $query->orderBy('nama_jasa')->get()
        ]);
    }

    public function show(ServiceMaster $serviceMaster)
    {
        return response()->json(['data' => $serviceMaster]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_jasa' => 'required|string|max:255',
            'harga' => 'required|integer|min:0',
        ]);

        $service = ServiceMaster::create([
            'nama_jasa' => $validated['nama_jasa'],
            'harga' => $validated['harga'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Jasa berhasil ditambahkan.',
            'data' => $service
        ], 201);
    }

    public function update(Request $request, ServiceMaster $serviceMaster)
    {
        $validated = $request->validate([
            'nama_jasa' => 'sometimes|string|max:255',
            'harga' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
        ]);

        $serviceMaster->update($validated);

        return response()->json([
            'message' => 'Jasa berhasil diupdate.',
            'data' => $serviceMaster
        ]);
    }

    public function destroy(ServiceMaster $serviceMaster)
    {
        $serviceMaster->update(['is_active' => false]);

        return response()->json([
            'message' => 'Jasa berhasil dinonaktifkan.'
        ]);
    }
}
