<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Mechanic;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('name')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
            ];
        });

        return response()->json(['data' => $users]);
    }

    public function show(User $user)
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'is_active' => $user->is_active,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];

        if ($user->role === 'mekanik' && $user->mechanic) {
            $data['mechanic'] = [
                'id' => $user->mechanic->id,
                'persentase_jasa' => $user->mechanic->persentase_jasa,
            ];
        }

        return response()->json(['data' => $data]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,kasir,mekanik',
            'persentase_jasa' => 'required_if:role,mekanik|nullable|numeric|min:0|max:1',
        ]);

        if ($validated['role'] === 'owner') {
            return response()->json([
                'message' => 'Tidak bisa membuat akun Owner.'
            ], 403);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        if ($validated['role'] === 'mekanik' && isset($validated['persentase_jasa'])) {
            Mechanic::create([
                'user_id' => $user->id,
                'persentase_jasa' => $validated['persentase_jasa'],
            ]);
        }

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'create_user',
            'description' => "Membuat user baru: {$user->name} ({$user->role})",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'User berhasil dibuat.',
            'data' => $user
        ], 201);
    }

    public function update(Request $request, User $user)
    {
        if ($user->role === 'owner' && $request->user()->id !== $user->id) {
            return response()->json([
                'message' => 'Tidak bisa mengedit akun Owner lain.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id),
            ],
            'password' => 'sometimes|string|min:8',
            'role' => 'sometimes|in:admin,kasir,mekanik',
            'persentase_jasa' => 'nullable|numeric|min:0|max:1',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $oldRole = $user->role;
        $newRole = $validated['role'] ?? $oldRole;

        if ($oldRole === 'mekanik' && $newRole !== 'mekanik') {
            $user->mechanic?->delete();
        }

        if ($newRole === 'mekanik' && $oldRole !== 'mekanik') {
            Mechanic::create([
                'user_id' => $user->id,
                'persentase_jasa' => $validated['persentase_jasa'] ?? 0.3,
            ]);
        }

        if ($newRole === 'mekanik' && isset($validated['persentase_jasa'])) {
            $user->mechanic?->update([
                'persentase_jasa' => $validated['persentase_jasa']
            ]);
        }

        unset($validated['persentase_jasa']);
        $user->update($validated);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'update_user',
            'description' => "Update user: {$user->name}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'User berhasil diupdate.',
            'data' => $user->fresh()
        ]);
    }

    public function toggleActive(Request $request, User $user)
    {
        if ($user->role === 'owner') {
            return response()->json([
                'message' => 'Tidak bisa menonaktifkan akun Owner.'
            ], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menonaktifkan akun sendiri.'
            ], 403);
        }

        $user->update([
            'is_active' => !$user->is_active
        ]);

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'toggle_user_status',
            'description' => "User {$user->name} {$status}",
            'created_at' => now(),
        ]);

        if (!$user->is_active) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => "User berhasil {$status}.",
            'data' => $user
        ]);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->role === 'owner') {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun Owner.'
            ], 403);
        }

        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Tidak bisa menghapus akun sendiri.'
            ], 403);
        }

        $hasWorkOrders = $user->createdWorkOrders()->exists();
        $hasPayments = $user->kasirPayments()->exists();

        if ($hasWorkOrders || $hasPayments) {
            $user->update(['is_active' => false]);
            $user->tokens()->delete();

            return response()->json([
                'message' => 'User tidak bisa dihapus karena memiliki riwayat transaksi. User telah dinonaktifkan.'
            ]);
        }

        $userName = $user->name;
        $user->mechanic?->delete();
        $user->tokens()->delete();
        $user->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'delete_user',
            'description' => "Menghapus user: {$userName}",
            'created_at' => now(),
        ]);

        return response()->json([
            'message' => 'User berhasil dihapus.'
        ]);
    }

    public function rolesSummary()
    {
        $summary = User::selectRaw('role, COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active')
            ->groupBy('role')
            ->get()
            ->keyBy('role');

        return response()->json([
            'data' => $summary
        ]);
    }
}
