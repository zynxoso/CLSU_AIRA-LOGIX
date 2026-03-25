<?php

namespace App\Http\Controllers;

use App\Models\IctServiceRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $statusCounts = IctServiceRequest::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->get();

        $typeCounts = IctServiceRequest::select('request_type', DB::raw('count(*) as total'))
            ->groupBy('request_type')
            ->get();

        $officeCounts = IctServiceRequest::select('office_unit', DB::raw('count(*) as total'))
            ->groupBy('office_unit')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        // Check DB driver to use correct date formatting
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', date_of_request)" 
            : ($driver === 'pgsql' ? "to_char(date_of_request, 'YYYY-MM')" : "DATE_FORMAT(date_of_request, '%Y-%m')");

        $monthlyRequests = IctServiceRequest::select(
                DB::raw("$dateFormat as month"),
                DB::raw('count(*) as total')
            )
            ->whereNotNull('date_of_request')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Average Resolution Time (in hours)
        $avgResolutionTime = 0;
        if ($driver === 'sqlite') {
            $avgResolutionTime = IctServiceRequest::whereNotNull('date_time_completed')
                ->whereNotNull('date_of_request')
                ->select(DB::raw('AVG((julianday(date_time_completed) - julianday(date_of_request)) * 24) as avg_hours'))
                ->first()->avg_hours ?? 0;
        } else {
            $avgResolutionTime = IctServiceRequest::whereNotNull('date_time_completed')
                ->whereNotNull('date_of_request')
                ->select(DB::raw('AVG(TIMESTAMPDIFF(HOUR, date_of_request, date_time_completed)) as avg_hours'))
                ->first()->avg_hours ?? 0;
        }

        // Top Personnel
        $personnelStats = IctServiceRequest::whereNotNull('conducted_by')
            ->select('conducted_by', DB::raw('count(*) as total'))
            ->groupBy('conducted_by')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        return Inertia::render('reports', [
            'stats' => [
                'status' => $statusCounts,
                'type' => $typeCounts,
                'offices' => $officeCounts,
                'monthly' => $monthlyRequests,
                'personnel' => $personnelStats,
                'total' => IctServiceRequest::count(),
                'resolved' => IctServiceRequest::whereIn('status', ['Resolved', 'Completed'])->count(),
                'pending' => IctServiceRequest::whereIn('status', ['Pending', 'Open'])->count(),
                'in_progress' => IctServiceRequest::where('status', 'In Progress')->count(),
                'avg_resolution_hours' => round($avgResolutionTime, 1),
            ]
        ]);
    }
}
