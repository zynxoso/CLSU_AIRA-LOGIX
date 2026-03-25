<?php

namespace App\Http\Controllers;

use App\Models\IctServiceRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $metrics = Cache::remember('dashboard.metrics.summary', now()->addSeconds(60), function () {
            $row = IctServiceRequest::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open")
                ->selectRaw("SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress")
                ->selectRaw("SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved")
                ->first();

            return [
                'total' => (int) ($row?->total ?? 0),
                'open' => (int) ($row?->open ?? 0),
                'in_progress' => (int) ($row?->in_progress ?? 0),
                'resolved' => (int) ($row?->resolved ?? 0),
            ];
        });

        $query = IctServiceRequest::query();

        // Get unique requesters for the filter dropdown
        $requesters = Cache::remember('dashboard.requesters.list', now()->addMinutes(10), function () {
            return IctServiceRequest::query()
                ->select('name')
                ->distinct()
                ->orderBy('name')
                ->pluck('name');
        });

        if ($request->boolean('archived')) {
            $query->onlyTrashed();
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function($q) use ($search) {
                $q->where('control_no', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhere('request_type', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('office_unit', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('request_type', $request->type);
        }

        if ($request->filled('requester') && $request->requester !== 'all') {
            $query->where('name', $request->requester);
        }

        $requests = $query->latest('updated_at')->paginate(10)->withQueryString();

        return Inertia::render('dashboard', [
            'metrics' => $metrics,
            'requests' => $requests,
            'requesters' => $requesters,
            'filters' => $request->only(['search', 'status', 'type', 'requester', 'archived']),
        ]);
    }
}
