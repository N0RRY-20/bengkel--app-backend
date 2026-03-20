<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Mechanic;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function daily(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $payments = Payment::whereDate('created_at', $date)->get();

        $byMethod = $payments->groupBy('metode')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

        $bySource = $payments->groupBy('source_type')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

        return response()->json([
            'date' => $date,
            'summary' => [
                'total_transactions' => $payments->count(),
                'total_income' => $payments->sum('total'),
            ],
            'by_method' => $byMethod,
            'by_source' => $bySource,
        ]);
    }

    public function monthly(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $payments = Payment::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get();

        $dailyBreakdown = $payments->groupBy(function ($payment) {
            return $payment->created_at->toDateString();
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('total'),
            ];
        });

        return response()->json([
            'period' => "{$month}/{$year}",
            'summary' => [
                'total_transactions' => $payments->count(),
                'total_income' => $payments->sum('total'),
                'average_per_day' => $dailyBreakdown->count() > 0
                    ? round($payments->sum('total') / $dailyBreakdown->count())
                    : 0,
            ],
            'daily_breakdown' => $dailyBreakdown,
        ]);
    }

    public function mechanics(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $mechanics = Mechanic::with('user')->get()->map(function ($mechanic) use ($month, $year) {
            $workOrders = $mechanic->workOrders()
                ->where('status', 'dibayar')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->with('services')
                ->get();

            $totalJasa = $workOrders->flatMap->services->sum('harga_jasa');
            $pendapatan = $totalJasa * $mechanic->persentase_jasa;

            return [
                'id' => $mechanic->id,
                'nama' => $mechanic->user->name,
                'total_wo' => $workOrders->count(),
                'total_jasa' => $totalJasa,
                'persentase' => $mechanic->persentase_jasa,
                'pendapatan' => round($pendapatan),
            ];
        });

        return response()->json([
            'period' => "{$month}/{$year}",
            'data' => $mechanics,
            'total_dibayar' => $mechanics->sum('pendapatan'),
        ]);
    }

    public function activityLogs(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->latest('created_at')
            ->paginate($request->get('limit', 50));

        return response()->json($logs);
    }

    public function payments(Request $request)
    {
        $query = Payment::with('kasir');

        if ($request->has('date')) {
            $query->whereDate('created_at', $request->date);
        }

        return response()->json([
            'data' => $query->latest()->get()
        ]);
    }
}
