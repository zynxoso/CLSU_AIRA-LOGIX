<?php

namespace App\Http\Controllers;

use App\Models\AiUsageLog;
use App\Models\IctServiceRequest;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminDashboardController extends Controller
{
    public function index(): Response
    {
        $metrics = [
            'totalRequests' => IctServiceRequest::count(),
            'openRequests' => IctServiceRequest::where('status', 'Open')->count(),
            'inProgressRequests' => IctServiceRequest::where('status', 'In Progress')->count(),
            'resolvedRequests' => IctServiceRequest::where('status', 'Resolved')->count(),
            'adminCount' => User::where('role', 'admin')->count(),
            'superAdminCount' => User::where('role', 'super_admin')->count(),
            'totalTokens' => (int) AiUsageLog::sum('total_tokens'),
            'estimatedCost' => (float) AiUsageLog::sum('estimated_cost'),
        ];

        return Inertia::render('superadmin/dashboard', [
            'metrics' => $metrics,
        ]);
    }
}
