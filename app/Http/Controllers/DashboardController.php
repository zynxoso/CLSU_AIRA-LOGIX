<?php

namespace App\Http\Controllers;

use App\Models\IctServiceRequest;
use App\Models\MisoAccomplishment;
use App\Services\MisoAccomplishmentSyncService;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function __construct(
        protected MisoAccomplishmentSyncService $misoSyncService
    ) {}

    public function index(Request $request)
    {
        $tabCategoryMap = $this->misoSyncService->tabCategoryMap();
        $activeTab = (string) $request->query('tab', 'ict');

        if ($activeTab !== 'ict' && !array_key_exists($activeTab, $tabCategoryMap)) {
            $activeTab = 'ict';
        }

        $metrics = Cache::remember('dashboard.metrics.summary', now()->addSeconds(60), function () {
            $row = IctServiceRequest::query()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) as open")
                ->selectRaw("SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress")
                ->selectRaw("SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved")
                ->selectRaw("SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending")
                ->selectRaw("SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed")
                ->first();

            return [
                'total' => (int) ($row?->total ?? 0),
                'open' => (int) ($row?->open ?? 0),
                'in_progress' => (int) ($row?->in_progress ?? 0),
                'resolved' => (int) ($row?->resolved ?? 0),
                'pending' => (int) ($row?->pending ?? 0),
                'completed' => (int) ($row?->completed ?? 0),
            ];
        });

        $tabs = array_merge([
            ['key' => 'ict', 'label' => 'ICT Form Requests'],
        ], collect($this->misoSyncService->tabLabels())
            ->map(fn (string $label, string $key) => ['key' => $key, 'label' => $label])
            ->values()
            ->all());

        $requests = null;
        $requesters = [];
        $availableStatuses = [];
        $availableTypes = [];
        $filterMeta = [
            'searchPlaceholder' => 'Search records...',
            'typeLabel' => 'Type of Request',
            'requesterLabel' => 'Requester',
            'statusLabel' => 'Status',
        ];

        if ($activeTab === 'ict') {
            $query = IctServiceRequest::query();

            $requesters = Cache::remember('dashboard.requesters.list', now()->addMinutes(10), function () {
                return IctServiceRequest::query()
                    ->select('name')
                    ->distinct()
                    ->orderBy('name')
                    ->pluck('name')
                    ->filter(fn (?string $name) => filled($name))
                    ->values();
            });

            if ($request->boolean('archived')) {
                $query->onlyTrashed();
            }

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
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
            $availableStatuses = [
                'Pending',
                'In Progress',
                'Resolved',
                'Completed',
                'Cancelled',
                'Open',
            ];
            $availableTypes = [
                'Technical Support',
                'Network/Internet',
                'Hardware Repair',
                'Software Install',
                'User Account Management',
                'System Development',
                'Others',
            ];
        } else {
            $category = $tabCategoryMap[$activeTab];
            $query = MisoAccomplishment::query()->where('category', $category);

            if ($request->boolean('archived')) {
                $query->onlyTrashed();
            }

            if ($request->filled('search')) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('project_title', 'like', "%{$search}%")
                        ->orWhere('project_lead', 'like', "%{$search}%")
                        ->orWhere('implementing_unit', 'like', "%{$search}%")
                        ->orWhere('target_activities', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('overall_status', $request->status);
            }

            if ($request->filled('type') && $request->type !== 'all') {
                $query->where('implementing_unit', $request->type);
            }

            if ($request->filled('requester') && $request->requester !== 'all') {
                $query->where('project_lead', $request->requester);
            }

            $requests = $query->latest('updated_at')->paginate(10)->withQueryString();

            $requesters = MisoAccomplishment::query()
                ->where('category', $category)
                ->whereNotNull('project_lead')
                ->where('project_lead', '!=', '')
                ->select('project_lead')
                ->distinct()
                ->orderBy('project_lead')
                ->pluck('project_lead');

            $availableStatuses = MisoAccomplishment::query()
                ->where('category', $category)
                ->whereNotNull('overall_status')
                ->where('overall_status', '!=', '')
                ->select('overall_status')
                ->distinct()
                ->orderBy('overall_status')
                ->pluck('overall_status')
                ->values()
                ->all();

            $availableTypes = MisoAccomplishment::query()
                ->where('category', $category)
                ->whereNotNull('implementing_unit')
                ->where('implementing_unit', '!=', '')
                ->select('implementing_unit')
                ->distinct()
                ->orderBy('implementing_unit')
                ->pluck('implementing_unit')
                ->values()
                ->all();

            $filterMeta = [
                'searchPlaceholder' => 'Search project title, lead, unit, activities...',
                'typeLabel' => 'Implementing Unit',
                'requesterLabel' => 'Project Lead',
                'statusLabel' => 'Overall Status',
            ];
        }

        return Inertia::render('dashboard', [
            'metrics' => $metrics,
            'requests' => $requests,
            'requesters' => $requesters,
            'availableStatuses' => $availableStatuses,
            'availableTypes' => $availableTypes,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
            'filterMeta' => $filterMeta,
            'filters' => $request->only(['tab', 'search', 'status', 'type', 'requester', 'archived']),
        ]);
    }
}
