<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Mechanic;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function owner(Request $request)
    {
        $today = now()->toDateString();
        $thisMonth = now()->month;
        $thisYear = now()->year;

        $todayIncome = Payment::whereDate('created_at', $today)->sum('total');
        $todayTransactions = Payment::whereDate('created_at', $today)->count();

        $monthlyIncome = Payment::whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->sum('total');

        $woStats = [
            'dikerjakan' => WorkOrder::where('status', 'dikerjakan')->count(),
            'selesai' => WorkOrder::where('status', 'selesai')->count(),
            'dibayar_hari_ini' => WorkOrder::where('status', 'dibayar')
                ->whereDate('updated_at', $today)
                ->count(),
        ];

        $lowStockProducts = Product::where('stok', '<=', 5)
            ->orderBy('stok')
            ->limit(10)
            ->get(['id', 'nama', 'stok']);

        $topMechanics = Mechanic::with('user')
            ->withCount(['workOrders' => function ($query) use ($thisMonth, $thisYear) {
                $query->where('status', 'dibayar')
                    ->whereMonth('created_at', $thisMonth)
                    ->whereYear('created_at', $thisYear);
            }])
            ->orderByDesc('work_orders_count')
            ->limit(5)
            ->get()
            ->map(function ($mechanic) {
                return [
                    'nama' => $mechanic->user->name,
                    'total_wo' => $mechanic->work_orders_count,
                ];
            });

        return response()->json([
            'today' => [
                'income' => $todayIncome,
                'transactions' => $todayTransactions,
            ],
            'monthly_income' => $monthlyIncome,
            'work_orders' => $woStats,
            'low_stock_products' => $lowStockProducts,
            'top_mechanics' => $topMechanics,
        ]);
    }

    public function admin(Request $request)
    {
        $today = now()->toDateString();

        $woStats = [
            'dikerjakan' => WorkOrder::where('status', 'dikerjakan')->count(),
            'selesai' => WorkOrder::where('status', 'selesai')->count(),
            'total_hari_ini' => WorkOrder::whereDate('created_at', $today)->count(),
        ];

        $productStats = [
            'total' => Product::count(),
            'low_stock' => Product::where('stok', '<=', 5)->count(),
            'out_of_stock' => Product::where('stok', 0)->count(),
        ];

        $lowStockProducts = Product::where('stok', '<=', 5)
            ->orderBy('stok')
            ->limit(10)
            ->get(['id', 'nama', 'stok', 'harga_jual']);

        $mechanics = Mechanic::with('user')
            ->whereHas('user', function ($query) {
                $query->where('is_active', true);
            })
            ->get()
            ->map(function ($mechanic) {
                $activeWO = $mechanic->workOrders()->where('status', 'dikerjakan')->count();
                return [
                    'id' => $mechanic->id,
                    'nama' => $mechanic->user->name,
                    'active_wo' => $activeWO,
                ];
            });

        return response()->json([
            'work_orders' => $woStats,
            'products' => $productStats,
            'low_stock_products' => $lowStockProducts,
            'mechanics' => $mechanics,
        ]);
    }

    public function kasir(Request $request)
    {
        $today = now()->toDateString();
        $userId = $request->user()->id;

        $pendingWO = WorkOrder::where('status', 'selesai')
            ->with(['mechanic.user', 'services', 'parts'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($wo) {
                $totalJasa = $wo->services->sum('harga_jasa');
                $totalParts = $wo->parts->sum(function ($part) {
                    return ($part->harga * $part->qty) - $part->diskon;
                });
                return [
                    'id' => $wo->id,
                    'plat_nomor' => $wo->plat_nomor,
                    'nama_pemilik' => $wo->nama_pemilik,
                    'mekanik' => $wo->mechanic->user->name,
                    'total' => $totalJasa + $totalParts,
                ];
            });

        $myTransactionsToday = Payment::where('kasir_id', $userId)
            ->whereDate('created_at', $today)
            ->count();

        $myIncomeToday = Payment::where('kasir_id', $userId)
            ->whereDate('created_at', $today)
            ->sum('total');

        $woInProgress = WorkOrder::where('status', 'dikerjakan')->count();

        return response()->json([
            'pending_payment' => $pendingWO,
            'my_stats_today' => [
                'transactions' => $myTransactionsToday,
                'total_processed' => $myIncomeToday,
            ],
            'wo_in_progress' => $woInProgress,
        ]);
    }

    public function mekanik(Request $request)
    {
        $user = $request->user();
        $mechanic = $user->mechanic;

        if (!$mechanic) {
            return response()->json([
                'message' => 'Data mekanik tidak ditemukan.'
            ], 404);
        }

        $thisMonth = now()->month;
        $thisYear = now()->year;

        $activeWO = $mechanic->workOrders()
            ->where('status', 'dikerjakan')
            ->with(['services', 'parts'])
            ->latest()
            ->get()
            ->map(function ($wo) {
                return [
                    'id' => $wo->id,
                    'plat_nomor' => $wo->plat_nomor,
                    'nama_pemilik' => $wo->nama_pemilik,
                    'services_count' => $wo->services->count(),
                    'parts_count' => $wo->parts->count(),
                    'created_at' => $wo->created_at,
                ];
            });

        $monthlyStats = [
            'total_wo' => $mechanic->workOrders()
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->count(),
            'completed' => $mechanic->workOrders()
                ->whereIn('status', ['selesai', 'dibayar'])
                ->whereMonth('created_at', $thisMonth)
                ->whereYear('created_at', $thisYear)
                ->count(),
        ];

        $completedWO = $mechanic->workOrders()
            ->where('status', 'dibayar')
            ->whereMonth('created_at', $thisMonth)
            ->whereYear('created_at', $thisYear)
            ->with('services')
            ->get();

        $totalJasa = $completedWO->flatMap->services->sum('harga_jasa');
        $estimatedEarning = $totalJasa * $mechanic->persentase_jasa;

        return response()->json([
            'active_work_orders' => $activeWO,
            'monthly_stats' => $monthlyStats,
            'estimated_earning' => round($estimatedEarning),
            'persentase_jasa' => $mechanic->persentase_jasa,
        ]);
    }
}
